<?php
require __DIR__ . '/common.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') bt_json(array('error' => 'method'), 405);
$cfg = bt_config();
if (empty($cfg['access_key']) || empty($cfg['secret_key'])) bt_json(array('error' => 'Оплата ещё не подключена. Позвоните нам: 8 800 350 46 42'), 503);

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) bt_json(array('error' => 'Некорректный запрос'), 400);
if (!empty($in['website'])) bt_json(array('error' => 'spam'), 400); // honeypot

$name  = trim(isset($in['name']) ? $in['name'] : '');
$email = trim(isset($in['email']) ? $in['email'] : '');
$phone = preg_replace('/\D/', '', isset($in['phone']) ? $in['phone'] : '');
if (strlen($phone) === 11 && ($phone[0] === '8' || $phone[0] === '7')) $phone = '7' . substr($phone, 1);
elseif (strlen($phone) === 10) $phone = '7' . $phone;
if (mb_strlen($name) < 2 || mb_strlen($name) > 100) bt_json(array('error' => 'Укажите имя'), 422);
if (strlen($phone) !== 11) bt_json(array('error' => 'Укажите телефон в формате +7 900 000-00-00'), 422);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) bt_json(array('error' => 'Укажите корректную почту: на неё придёт чек'), 422);
if (empty($in['agree'])) bt_json(array('error' => 'Нужно согласие с офертой и политикой конфиденциальности'), 422);

$cat = bt_catalog();
$lines = array(); $total = 0; $allSku = true; $qtyAll = 0;
$cart = isset($in['items']) && is_array($in['items']) ? $in['items'] : array();
if (!$cart || count($cart) > 30) bt_json(array('error' => 'Корзина пуста'), 422);
foreach ($cart as $it) {
    $id = isset($it['id']) ? (string)$it['id'] : '';
    $q  = isset($it['qty']) ? (int)$it['qty'] : 0;
    if (!isset($cat[$id]) || $q < 1 || $q > 50) bt_json(array('error' => 'Товар не найден, обновите корзину'), 422);
    $v = $cat[$id];
    $line = array(
        'name' => $v['name'], 'extId' => $v['id'], 'type' => 'TYPE_PRODUCT',
        'quantity' => $q, 'price' => array('currencyCode' => '643', 'value' => (string)($v['price'] * 100)),
        'vat' => isset($cfg['vat']) ? $cfg['vat'] : 'VAT_NONE',
    );
    if (!empty($cfg['need_mark'])) $line['needMark'] = true;
    if ($v['sku']) $line['sku'] = $v['sku']; else $allSku = false;
    $lines[] = $line;
    $total += $v['price'] * $q; $qtyAll += $q;
}
$wantDelivery = !empty($in['delivery']);
$delivery = $wantDelivery && $allSku;

$accessKey = $cfg['access_key']; $secret = $cfg['secret_key'];
$extId = 'BT-' . date('ymdHis') . '-' . bin2hex(random_bytes(2));
$expires = gmdate('Y-m-d\TH:i:s', time() + 86400) . '.000Z';
$fiscal = isset($cfg['fiscalization_type']) ? $cfg['fiscalization_type'] : '';
$algo = 'PAY_ALGO_SMS';
$amountVal = (string)($total * 100);
$site = isset($cfg['site']) ? $cfg['site'] : 'https://www.biotela.ru';

$payload = array(
    'accessKey' => $accessKey, 'extId' => $extId, 'expiresAt' => $expires,
    'paymentAlgorithm' => $algo, 'mode' => 'MODE_FULL',
    'amount' => array('currencyCode' => '643', 'value' => $amountVal),
    'items' => $lines,
    'fiscalizationPhone' => '+' . $phone, 'receiptEmail' => $email,
    'successUrl' => $site . '/oplata-uspeshna?order=' . $extId,
    'failUrl' => $site . '/oplata-oshibka?order=' . $extId,
    'notificationUrl' => $site . '/shop/notify.php',
);
if ($fiscal !== '') $payload['fiscalizationType'] = $fiscal;
if ($delivery) $payload['deliverySettings'] = array('isEnabled' => true, 'payMode' => isset($cfg['delivery_pay_mode']) ? $cfg['delivery_pay_mode'] : 'PAY_MODE_CUSTOMER');
$payload['requestSign'] = hash('sha256', $accessKey . $expires . $extId . $fiscal . $algo . '643' . $amountVal . $secret);

$order = array('extId' => $extId, 'created' => date('c'), 'name' => $name, 'phone' => '+' . $phone, 'email' => $email,
    'comment' => mb_substr(trim(isset($in['comment']) ? $in['comment'] : ''), 0, 500),
    'items' => $lines, 'total' => $total, 'delivery' => $delivery, 'status' => 'created');
bt_save_order($extId, $order);

$ch = curl_init(isset($cfg['api_url']) ? $cfg['api_url'] : 'https://payapi.ozon.ru/v1/createOrder');
curl_setopt_array($ch, array(
    CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25,
    CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
));
$resp = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); $cerr = curl_error($ch); curl_close($ch);
$j = json_decode($resp, true);
$order['api_http'] = $code; $order['api_response'] = $j ? $j : $resp . $cerr;
if ($code === 200 && isset($j['order']['payLink'])) {
    $order['ozon_id'] = $j['order']['id']; $order['status'] = 'awaiting_payment';
    bt_save_order($extId, $order);
    bt_json(array('payLink' => $j['order']['payLink'], 'extId' => $extId, 'delivery' => $delivery));
}
$order['status'] = 'api_error'; bt_save_order($extId, $order);
bt_json(array('error' => 'Не удалось создать заказ. Попробуйте ещё раз или позвоните: 8 800 350 46 42', 'ref' => $extId), 502);
