<?php
require __DIR__ . '/common.php';
$cfg = bt_config();
$raw = file_get_contents('php://input');
@file_put_contents(bt_orders_dir() . '/notify.log', date('c') . ' ' . $raw . "\n", FILE_APPEND);
$d = json_decode($raw, true);
if (!is_array($d)) { http_response_code(400); exit('bad'); }
$tx = isset($d['transactionID']) && $d['transactionID'] !== null ? $d['transactionID'] : (isset($d['transactionUID']) ? $d['transactionUID'] : (isset($d['transactionUid']) ? $d['transactionUid'] : ''));
$ext = isset($d['extOrderID']) ? $d['extOrderID'] : '';
$str = $cfg['access_key'] . '|' . (isset($d['orderID']) ? $d['orderID'] : '') . '|' . $tx . '|' . $ext . '|' . (isset($d['amount']) ? $d['amount'] : '') . '|' . (isset($d['currencyCode']) ? $d['currencyCode'] : '') . '|' . $cfg['notification_secret'];
$sign = hash('sha256', $str);
if (empty($d['requestSign']) || !hash_equals($sign, (string)$d['requestSign'])) {
    @file_put_contents(bt_orders_dir() . '/notify-rejected.log', date('c') . ' ' . json_encode($d, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
    http_response_code(403); exit('sign');
}
$o = bt_load_order($ext);
if ($o) {
    $st = isset($d['status']) ? $d['status'] : '';
    $o['notify'][] = array('t' => date('c'), 'status' => $st, 'method' => isset($d['paymentMethod']) ? $d['paymentMethod'] : '', 'test' => isset($d['testMode']) ? $d['testMode'] : null);
    if ($st === 'Completed' || $st === 'Authorized') {
        $first = ($o['status'] !== 'paid');
        $o['status'] = 'paid';
        bt_save_order($ext, $o);
        if ($first && empty($d['testMode'])) {
            // списание остатков и письмо, если товар заканчивается
            $raw = json_decode(file_get_contents(__DIR__ . '/catalog.json'), true);
            $rev = isset($raw['stock_rev']) ? $raw['stock_rev'] : 1;
            $sold = bt_sold($rev);
            $sl = isset($o['stock_lines']) ? $o['stock_lines'] : array();
            foreach ($sl as $i) { $sold[$i['id']] = (isset($sold[$i['id']]) ? $sold[$i['id']] : 0) + (int)$i['qty']; }
            file_put_contents(bt_sold_file(), json_encode(array('rev' => $rev, 'sold' => $sold)), LOCK_EX);
            $cur = bt_catalog(); $warn = '';
            $thr = isset($cfg['stock_alert']) ? (int)$cfg['stock_alert'] : 3;
            foreach ($sl as $i) {
                if (isset($cur[$i['id']]) && $cur[$i['id']]['stock'] !== null && $cur[$i['id']]['stock'] <= $thr)
                    $warn .= '- ' . $cur[$i['id']]['name'] . ': осталось ' . $cur[$i['id']]['stock'] . " шт\n";
            }
            if ($warn !== '') bt_notify_mail('Заканчивается товар на сайте', "По данным сайта осталось мало или закончилось:\n" . $warn . "\nСообщите Claude новые остатки, когда пополните склад.");
        }
        if ($first) {
            $lines = '';
            foreach ($o['items'] as $i) $lines .= '- ' . $i['name'] . ' × ' . $i['quantity'] . "\n";
            bt_notify_mail((!empty($d['testMode']) ? '[ТЕСТ] ' : '') . 'Оплачен заказ ' . $ext . ' на ' . $o['total'] . ' ₽',
                "Заказ: $ext\nСумма: {$o['total']} ₽\nПокупатель: {$o['name']}, {$o['phone']}, {$o['email']}\nДоставка Ozon: " . ($o['delivery'] ? 'да' : 'нет') . "\n\n$lines\nКомментарий: {$o['comment']}\n");
        }
    } else { bt_save_order($ext, $o); }
}
http_response_code(200); echo 'OK';
