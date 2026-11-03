<?php
// Общие функции магазина biotela.ru. PHP 7.4+
// Секреты лежат ВНЕ сайта: /home/<логин>/biotela-config.php (см. config.sample.php)
function bt_config() {
    static $c = null;
    if ($c === null) {
        $f = dirname(__DIR__, 3) . '/biotela-config.php';
        $c = is_file($f) ? require $f : array();
    }
    return $c;
}
function bt_sold_file() { return bt_orders_dir() . '/sold.json'; }
function bt_sold($rev) {
    $f = bt_sold_file();
    $d = is_file($f) ? json_decode(file_get_contents($f), true) : null;
    return (is_array($d) && isset($d['rev']) && $d['rev'] == $rev && isset($d['sold'])) ? $d['sold'] : array();
}
// Каталог с учётом продаж: stock = остаток из catalog.json минус проданное с момента stock_rev
function bt_catalog_raw() {
    $j = json_decode(file_get_contents(__DIR__ . '/catalog.json'), true);
    $rev = isset($j['stock_rev']) ? $j['stock_rev'] : 1;
    $sold = bt_sold($rev);
    foreach ($j['products'] as &$p) foreach ($p['variants'] as &$v) {
        if (isset($v['stock']) && $v['stock'] !== null) {
            $v['stock'] = max(0, (int)$v['stock'] - (isset($sold[$v['id']]) ? (int)$sold[$v['id']] : 0));
        }
    }
    return $j;
}
function bt_catalog() {
    $j = bt_catalog_raw();
    $map = array();
    foreach ($j['products'] as $p) {
        foreach ($p['variants'] as $v) {
            $map[$v['id']] = array(
                'id' => $v['id'], 'name' => $p['name'] . ', ' . $v['label'],
                'price' => (int)$v['price'], 'mark' => !empty($v['mark']), 'ozon_id' => isset($v['ozon_id']) ? (string)$v['ozon_id'] : '', 'stock' => array_key_exists('stock',$v) ? $v['stock'] : null, 'sku' => $v['sku'] ? (int)$v['sku'] : null,
            );
        }
    }
    return $map;
}
function bt_orders_dir() {
    $d = dirname(__DIR__, 3) . '/biotela-orders';
    if (!is_dir($d)) { @mkdir($d, 0700, true); }
    return $d;
}
function bt_json($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function bt_save_order($extId, $data) {
    $f = bt_orders_dir() . '/' . preg_replace('/[^A-Za-z0-9\-]/', '', $extId) . '.json';
    file_put_contents($f, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
}
function bt_load_order($extId) {
    $f = bt_orders_dir() . '/' . preg_replace('/[^A-Za-z0-9\-]/', '', $extId) . '.json';
    return is_file($f) ? json_decode(file_get_contents($f), true) : null;
}
function bt_notify_mail($subject, $body) {
    $c = bt_config();
    $to = isset($c['notify_email']) ? $c['notify_email'] : '';
    if (!$to) return;
    $h = "From: shop@biotela.ru\r\nContent-Type: text/plain; charset=utf-8\r\n";
    @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $h);
}
