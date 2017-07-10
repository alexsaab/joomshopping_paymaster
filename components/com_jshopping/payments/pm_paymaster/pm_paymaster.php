<?php
defined('_JEXEC') or die('Restricted access');


class pm_paymaster extends PaymentRoot
{
    public function showPaymentForm($params, $pmconfigs)
    {
        include(dirname(__FILE__) . "/paymentform.php");
    }

    public function loadLanguageFile()
    {
        $lang = JFactory::getLanguage();
        $langtag = $lang->getTag();

        if (file_exists(JPATH_ROOT . '/components/com_jshopping/payments/pm_paymaster/lang/' . $langtag . '.php')) {
            require_once(JPATH_ROOT . '/components/com_jshopping/payments/pm_paymaster/lang/' . $langtag . '.php');
        } else {
            require_once(JPATH_ROOT . '/components/com_jshopping/payments/pm_paymaster/lang/ru-RU.php'); //если языковый файл не найден, то подключаем en-GB.php
        }
    }

    public function showAdminFormParams($params)
    {
        $array_params = array(
            'paymaster_merchant_id',
            'paymaster_secret_key',
            'paymaster_sign_method',
            'paymaster_payment_detail',
            'paymaster_vat_delivery',
            'transaction_end_status',
            'transaction_pending_status',
            'transaction_failed_status'
        );

        foreach ($array_params as $key) {
            if (!isset($params[$key])) {
                $params[$key] = '';
            }
        }

        $orders = JModelLegacy::getInstance('orders', 'JshoppingModel');

        $this->loadLanguageFile(); //подключаем нужный язык

        include(dirname(__FILE__) . '/adminparamsform.php');
    }

    public function getUrlParams($pmconfigs)
    {
        if (isset($_POST["LMI_PAYMENT_NO"])) {
            $_REQUEST['LMI_PAYMENT_NO'] = $_POST['LMI_PAYMENT_NO'];
        }

        $params = array();
        $params['order_id'] = $_POST['LMI_PAYMENT_NO'];
        $params['hash'] = '';
        $params['checkHash'] = 0;
        $params['checkReturnParams'] = 0;

        return $params;
    }

    function showEndForm($pmconfigs, $order)
    {

        $url = (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

        $amount = number_format((float)$order->order_total, 2, '.', '');

        $fields = array(
            'LMI_PAYMENT_AMOUNT' => $amount,
            'LMI_PAYMENT_DESC' => $pmconfigs['paymaster_payment_detail'] . $order->order_id,
            'LMI_PAYMENT_NO' => $order->order_id,
            'LMI_MERCHANT_ID' => $pmconfigs['paymaster_merchant_id'],
            'LMI_CURRENCY' => $order->currency_code_iso,
            'LMI_PAYMENT_NOTIFICATION_URL' => $handler_url,
            'LMI_SUCCESS_URL' => $url . '/index.php?option=com_jshopping&controller=checkout&task=step7&act=success&js_paymentclass=pm_paymaster',
            'LMI_FAILURE_URL' => $url . '/index.php?option=com_jshopping&controller=checkout&task=step7&act=cancel&js_paymentclass=pm_paymaster',
            'SIGN' => $this->paymaster_get_sign($pmconfigs['paymaster_merchant_id'], $order->order_id, $amount, $order->currency_code_iso, $pmconfigs['paymaster_secret_key'], $pmconfigs['paymaster_sign_method']),
        );

        foreach ($order->getAllItems() as $key => $product) {
            switch ($product->product_tax) {
                case 18:
                    $tax = 'vat118';
                    break;
                case 10:
                    $tax = 'vat110';
                    break;
                case 0:
                    $tax = 'vat0';
                    break;
                default:
                    $tax = 'no_vat';
                    break;
            }

            $fields["LMI_SHOPPINGCART.ITEM[{$key}].NAME"] = $product->product_name;
            $fields["LMI_SHOPPINGCART.ITEM[{$key}].QTY"] = $product->product_quantity;
            $fields["LMI_SHOPPINGCART.ITEM[{$key}].PRICE"] = $product->product_item_price;
            $fields["LMI_SHOPPINGCART.ITEM[{$key}].TAX"] = $tax;
        }


        //Добавляем доставку в форму

        if ($order->order_shipping > 0) {
            $key++;
            if (isset($order->quote['rate']) && ($order->quote['rate'] > 0)) {
                $fields["LMI_SHOPPINGCART.ITEM[{$key}].NAME"] = "Доставка заказа № " . $order->order_id;
                $fields["LMI_SHOPPINGCART.ITEM[{$key}].QTY"] = 1;
                $fields["LMI_SHOPPINGCART.ITEM[{$key}].PRICE"] = number_format((float)$order->order_shipping, 2, '.', '');
                $fields["LMI_SHOPPINGCART.ITEM[{$key}].TAX"] = $pmconfigs['paymaster_vat_delivery'];
            }
        }


        $form = '
    <form name="paymaster" method="POST" action="https://paymaster.ru/Payment/Init">' . PHP_EOL;
        foreach ($fields as $key => $value) {
            $form .= '<input type="hidden" name="' . $key . '" value="' . $value . '">' . PHP_EOL;
        }
        $form .= '</form>
    <script type="text/javascript">
      document.paymaster.submit();
    </script>
    ';

        echo $form;
        die;
    }

    function checkTransaction($pmconfigs, $order, $act)
    {
        if ($_SERVER["REQUEST_METHOD"] == "POST" && $act == 'notify') {
            if (isset($_POST["LMI_PREREQUEST"]) && ($_POST["LMI_PREREQUEST"] == "1" || $_POST["LMI_PREREQUEST"] == "2")) {
                echo "YES";
                die;
            } else {
                $hash = paymaster_get_hash($_POST["LMI_MERCHANT_ID"], $_POST["LMI_PAYMENT_NO"], $_POST["LMI_SYS_PAYMENT_ID"], $_POST["LMI_SYS_PAYMENT_DATE"], $_POST["LMI_PAYMENT_AMOUNT"], $_POST["LMI_CURRENCY"], $_POST["LMI_PAID_AMOUNT"], $_POST["LMI_PAID_CURRENCY"], $_POST["LMI_PAYMENT_SYSTEM"], $_POST["LMI_SIM_MODE"], $pmconfigs['paymaster_secret_key'], $pmconfigs['paymaster_sign_method']);

                if (($_POST["LMI_HASH"] == $hash) && ($_POST["SING"] == paymaster_get_sign($_POST["LMI_MERCHANT_ID"], $_POST["LMI_PAYMENT_NO"], $_POST["LMI_PAID_AMOUNT"], $_POST["LMI_PAID_CURRENCY"], $pmconfigs['paymaster_secret_key'], $pmconfigs['paymaster_sign_method']))) {
                    return array(1, $order->order_id);
                }
            }
        }

        if ($act == 'success' && $pmconfigs['transaction_end_status'] == $order->order_status) {
            return array(1, $order->order_id);
        } else if ($act == 'cancel') {
            return array(0, 'Платеж отменен');
        }
    }


    /**
     * Return request sign options (method of hash)
     * @return mixed
     */
    function paymaster_sign_options()
    {
        return array(
            'sha256' => 'sha256',
            'md5' => 'md5',
            'sha1' => 'sha1',
        );
    }

    /**
     * Возвращаем массив значений НДС
     * @return array
     */
    function paymaster_vat_options()
    {
        return array(
            'vat18' => _JSHOP_CFG_VAT18,
            'vat10' => _JSHOP_CFG_VAT10,
            'vat118' => _JSHOP_CFG_VAT_FORMULA_18_118,
            'vat110' => _JSHOP_CFG_VAT_FORMULA_10_110,
            'vat0' => _JSHOP_CFG_VATO,
            'no_vat' => _JSHOP_CFG_NO_VAT,
        );
    }

    /**
     * Функция возвращает подписанный запрос непонятно для чего?!
     * @param $merchant_id
     * @param $order_id
     * @param $amount
     * @param $lmi_currency
     * @param $secret_key
     * @param string $sign_method
     */
    function paymaster_get_sign($merchant_id, $order_id, $amount, $lmi_currency, $secret_key, $sign_method = 'md5')
    {
        $plain_sign = $merchant_id . $order_id . $amount . $lmi_currency . $secret_key;
        $sign = base64_encode(hash($sign_method, $plain_sign, TRUE));

        return $sign;
    }


    /**
     * Функция возвращает hash по определенному алгоритму
     * @param $merchant_id
     * @param $order_id
     * @param $amount
     * @param $lmi_currency
     * @param $secret_key
     * @param string $sign_method
     */
    function paymaster_get_hash($LMI_MERCHANT_ID, $LMI_PAYMENT_NO, $LMI_SYS_PAYMENT_ID, $LMI_SYS_PAYMENT_DATE, $LMI_PAYMENT_AMOUNT, $LMI_CURRENCY, $LMI_PAID_AMOUNT, $LMI_PAID_CURRENCY, $LMI_PAYMENT_SYSTEM, $LMI_SIM_MODE, $SECRET, $hash_method = 'md5')
    {
        $string = $LMI_MERCHANT_ID . ";" . $LMI_PAYMENT_NO . ";" . $LMI_SYS_PAYMENT_ID . ";" . $LMI_SYS_PAYMENT_DATE . ";" . $LMI_PAYMENT_AMOUNT . ";" . $LMI_CURRENCY . ";" . $LMI_PAID_AMOUNT . ";" . $LMI_PAID_CURRENCY . ";" . $LMI_PAYMENT_SYSTEM . ";" . $LMI_SIM_MODE . ";" . $SECRET;

        $hash = base64_encode(hash($hash_method, $string, TRUE));

        return $hash;
    }


}