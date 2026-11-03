<?php
header('Content-Type: text/plain; charset=utf-8');
foreach (['HTTPS','SERVER_PORT','HTTP_X_FORWARDED_PROTO','HTTP_X_FORWARDED_PORT','HTTP_X_REAL_PROTO','REQUEST_SCHEME','HTTP_HOST'] as $k) {
  echo $k, ' = ', isset($_SERVER[$k]) ? $_SERVER[$k] : '(нет)', "\n";
}
