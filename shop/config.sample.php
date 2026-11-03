<?php
// ШАБЛОН. Реальный файл создаётся ВНЕ папки сайта:
//   /home/h618022041/biotela-config.php   (на уровень выше папки h618022041.nichost.ru)
// Значения берутся из личного кабинета Озон Банка (Эквайринг → токен). В чат и в GitHub их не отправляйте.
return array(
    'access_key'          => 'ВСТАВЬТЕ_ИДЕНТИФИКАТОР_ТОКЕНА',      // accessKey
    'secret_key'          => 'ВСТАВЬТЕ_СЕКРЕТНЫЙ_КЛЮЧ',            // secretKey
    'notification_secret' => 'ВСТАВЬТЕ_КЛЮЧ_ДЛЯ_УВЕДОМЛЕНИЙ',       // notificationSecretKey
    'api_url'             => 'https://payapi.ozon.ru/v1/createOrder',
    'vat'                 => 'VAT_NONE',            // ставка НДС: VAT_NONE, VAT_0, VAT_5, VAT_7, VAT_10, VAT_22 ...
    'fiscalization_type'  => '',                    // '', 'FISCAL_TYPE_SINGLE' или 'FISCAL_TYPE_DOUBLE'
    'need_mark'           => false,                 // true, если товары маркируются
    'delivery_pay_mode'   => 'PAY_MODE_CUSTOMER',   // доставка за счёт покупателя
    'notify_email'        => 'biotela@inbox.ru',    // куда присылать письма о новых оплатах
    'site'                => 'https://www.biotela.ru',
);
