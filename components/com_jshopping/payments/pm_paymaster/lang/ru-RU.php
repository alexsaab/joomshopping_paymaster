<?php
//защита от прямого доступа
defined('_JEXEC') or die();

//определяем константы для русского языка
define('_JSHOP_CFG_PAYMASTER_MERCHANT_ID', 'ID продавца');
define('_JSHOP_CFG_PAYMASTER_SECRET_KEY', 'Секретный ключ');
define('_JSHOP_CFG_PAYMASTER_SIGN_METHOD', 'Тип подписи ключа');
define('_JSHOP_CFG_PAYMASTER_PAYMENT_DETAIL', 'Детали платежа по заказу');
define('_JSHOP_CFG_PAYMASTER_VAT_DELIVERY', 'Значение НДС для доставки');
define('_JSHOP_CFG_VAT18', 'НДС 18%');
define('_JSHOP_CFG_VAT10', 'НДС 10%');
define('_JSHOP_CFG_VAT_FORMULA_18_118', 'НДС по формуле 18/118');
define('_JSHOP_CFG_VAT_FORMULA_10_110', 'НДС по формуле 10/110');
define('_JSHOP_CFG_VATO', 'НДС 0%');
define('_JSHOP_CFG_NO_VAT', 'Без НДС');
