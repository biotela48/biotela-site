<?php
// ШАБЛОН. Реальный файл создаётся ВНЕ папки сайта:
//   /home/h618022041/biotela-config.php   (на уровень выше папки h618022041.nichost.ru)
// Значения берутся из личного кабинета Озон Банка (Эквайринг → токен). В чат и в GitHub их не отправляйте.
return array(
    'access_key'          => 'ВСТАВЬТЕ_ИДЕНТИФИКАТОР_ТОКЕНА',      // accessKey
    'secret_key'          => 'ВСТАВЬТЕ_СЕКРЕТНЫЙ_КЛЮЧ',            // secretKey
    'notification_secret' => 'ВСТАВЬТЕ_КЛЮЧ_ДЛЯ_УВЕДОМЛЕНИЙ',       // notificationSecretKey
    'api_url'             => 'https://payapi.ozon.ru/v1/createOrder',
    'vat'                 => 'VAT_5',               // ставка НДС 5%
    'fiscalization_type'  => '',                    // для заказов без маркируемых товаров ('' = по умолчанию магазина)
    'delivery_pay_mode'   => 'PAY_MODE_CUSTOMER',   // доставка за счёт покупателя
    'notify_email'        => 'biotela@inbox.ru',    // куда присылать письма о новых оплатах
    'site'                => 'https://www.biotela.ru',
);
