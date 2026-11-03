<?php
require __DIR__ . '/common.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$j = bt_catalog_raw();
unset($j['_comment']);
echo json_encode($j, JSON_UNESCAPED_UNICODE);
