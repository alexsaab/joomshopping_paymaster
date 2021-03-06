<?php
defined('_JEXEC') or die('Restricted access');


class pm_paymaster extends PaymentRoot
{

	/**
	 * ���������� ����� ������
	 *
	 * @param array $params
	 * @param array $pmconfigs
	 */
	public function showPaymentForm($params, $pmconfigs)
	{
		include(dirname(__FILE__) . "/paymentform.php");
	}

	/**
	 * �������� ������
	 */
	public function loadLanguageFile()
	{
		$lang    = JFactory::getLanguage();
		$langtag = $lang->getTag();

		if (file_exists(JPATH_ROOT . '/components/com_jshopping/payments/pm_paymaster/lang/' . $langtag . '.php'))
		{
			require_once(JPATH_ROOT . '/components/com_jshopping/payments/pm_paymaster/lang/' . $langtag . '.php');
		}
		else
		{
			require_once(JPATH_ROOT . '/components/com_jshopping/payments/pm_paymaster/lang/ru-RU.php'); //���� �������� ���� �� ������, �� ���������� en-GB.php
		}
	}

	/**
	 * ���������� ��������� ��� �����������������
	 *
	 * @param $params
	 */
	public function showAdminFormParams($params)
	{
		$array_params = [
			'paymaster_merchant_id',
			'paymaster_secret_key',
			'paymaster_sign_method',
			'paymaster_payment_detail',
			'paymaster_vat_delivery',
			'transaction_end_status',
			'transaction_pending_status',
			'transaction_failed_status',
		];

		foreach ($array_params as $key)
		{
			if (!isset($params[$key]))
			{
				$params[$key] = '';
			}
		}

		$orders = JModelLegacy::getInstance('orders', 'JshoppingModel');

		$this->loadLanguageFile(); //���������� ������ ����

		include(dirname(__FILE__) . '/adminparamsform.php');
	}

	/**
	 * ��������� ���������� ������
	 *
	 * @param $pmconfigs
	 *
	 * @return array
	 */
	public function getUrlParams($pmconfigs)
	{
		if (isset($_POST["LMI_PAYMENT_NO"]))
		{
			$_REQUEST['LMI_PAYMENT_NO'] = $_POST['LMI_PAYMENT_NO'];
		}

		$params                      = [];
		$params['order_id']          = $_POST['LMI_PAYMENT_NO'];
		$params['hash']              = '';
		$params['checkHash']         = 0;
		$params['checkReturnParams'] = 0;

		return $params;
	}


	/**
	 * ��������� ������� �� ������
	 *
	 * @param $pmconfigs
	 * @param $order
	 */
	function showEndForm($pmconfigs, $order)
	{
		$pm_method = $this->getPmMethod();


		$url = (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

		$amount = number_format((float) $order->order_total, 2, '.', '');

		$fields = [
			'LMI_PAYMENT_AMOUNT'           => $amount,
			'LMI_PAYMENT_DESC'             => $pmconfigs['paymaster_payment_detail'] . $order->order_number,
			'LMI_PAYMENT_NO'               => $order->order_number,
			'LMI_MERCHANT_ID'              => $pmconfigs['paymaster_merchant_id'],
			'LMI_CURRENCY'                 => $order->currency_code_iso,
			'LMI_PAYMENT_NOTIFICATION_URL' => $url . '/index.php?option=com_jshopping&controller=checkout&task=step7&act=notify&js_paymentclass=' . $pm_method->payment_class . '&no_lang=1',
			'LMI_SUCCESS_URL'              => $url . '/index.php?option=com_jshopping&controller=checkout&task=step7&act=return&js_paymentclass=' . $pm_method->payment_class,
			'LMI_FAILURE_URL'              => $url . '/index.php?option=com_jshopping&controller=checkout&task=step7&act=cancel&js_paymentclass=' . $pm_method->payment_class,
			'SIGN'                         => $this->paymaster_get_sign($pmconfigs['paymaster_merchant_id'], $order->order_number, $amount, $order->currency_code_iso, $pmconfigs['paymaster_secret_key'], $pmconfigs['paymaster_sign_method']),
		];


		foreach ($order->getAllItems() as $key => $product)
		{
			switch ($product->product_tax)
			{
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

			$fields["LMI_SHOPPINGCART.ITEM[{$key}].NAME"]  = htmlspecialchars($product->product_name);
			$fields["LMI_SHOPPINGCART.ITEM[{$key}].QTY"]   = $product->product_quantity;
			$fields["LMI_SHOPPINGCART.ITEM[{$key}].PRICE"] = number_format($product->product_item_price, 2, '.', '');
			$fields["LMI_SHOPPINGCART.ITEM[{$key}].TAX"]   = $tax;
		}


		//��������� �������� � �����

		if ($order->order_shipping > 0)
		{
			$key++;
			if (isset($order->order_shipping) && ($order->order_shipping > 0))
			{
				$fields["LMI_SHOPPINGCART.ITEM[{$key}].NAME"]  = iconv("windows-1251", "utf-8", "�������� ������ � " . $order->order_number);
				$fields["LMI_SHOPPINGCART.ITEM[{$key}].QTY"]   = 1;
				$fields["LMI_SHOPPINGCART.ITEM[{$key}].PRICE"] = number_format((float) $order->order_shipping, 2, '.', '');
				$fields["LMI_SHOPPINGCART.ITEM[{$key}].TAX"]   = $pmconfigs['paymaster_vat_delivery'];
			}
		}


		$form = '
    <form name="paymaster" method="POST" action="https://paymaster.ru/Payment/Init">' . PHP_EOL;
		foreach ($fields as $key => $value)
		{
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

	/**
	 * �������� ����������
	 *
	 * @param array  $pmconfigs
	 * @param object $order
	 * @param string $act
	 *
	 * @return array
	 */
	function checkTransaction($pmconfigs, $order, $act)
	{

		if ($_SERVER["REQUEST_METHOD"] == "POST" && $act == 'notify')
		{

			if (isset($_POST["LMI_PREREQUEST"]) && ($_POST["LMI_PREREQUEST"] == "1" || $_POST["LMI_PREREQUEST"] == "2"))
			{
				echo "YES";
				die;
			}
			else
			{
				$transaction     = $_POST["LMI_SYS_PAYMENT_ID"];
				$transactiondata = array(
					'LMI_SYS_PAYMENT_ID'   => $_POST['LMI_SYS_PAYMENT_ID'],
					'LMI_PAYMENT_NO'       => $_POST['LMI_PAYMENT_NO'],
					'LMI_SYS_PAYMENT_DATE' => $_POST['LMI_SYS_PAYMENT_DATE'],
					'LMI_CURRENCY'         => $_POST['LMI_CURRENCY'],
					'LMI_PAID_AMOUNT'      => $_POST['LMI_PAID_AMOUNT'],
					'LMI_PAYMENT_SYSTEM'   => $_POST['LMI_PAYMENT_SYSTEM'],
					'LMI_SIM_MODE'         => $_POST['LMI_PAYMENT_SYLMI_SIM_MODESTEM'],
					'ORDER_ID'             => $order->order_id,
					'ORDER_NUMBER'         => $order->order_number);

				$hash = $this->paymaster_get_hash($_POST["LMI_MERCHANT_ID"], $_POST["LMI_PAYMENT_NO"], $_POST["LMI_PAYMENT_NO"], $_POST["LMI_SYS_PAYMENT_DATE"], $_POST["LMI_PAYMENT_AMOUNT"], $_POST["LMI_CURRENCY"], $_POST["LMI_PAID_AMOUNT"], $_POST["LMI_PAID_CURRENCY"], $_POST["LMI_PAYMENT_SYSTEM"], $_POST["LMI_SIM_MODE"], $pmconfigs['paymaster_secret_key'], $pmconfigs['paymaster_sign_method']);

				if (($_POST["LMI_HASH"] == $hash) && ($_POST["SIGN"] == $this->paymaster_get_sign($_POST["LMI_MERCHANT_ID"], $_POST["LMI_PAYMENT_NO"], $_POST["LMI_PAID_AMOUNT"], $_POST["LMI_PAID_CURRENCY"], $pmconfigs['paymaster_secret_key'], $pmconfigs['paymaster_sign_method'])))
				{
					$this->finishOrder($order, $pmconfigs['transaction_end_status']);
					return array(1, 'Payment for order #' . $order->order_number . ' was received', $transaction, $transactiondata);
				}
			}
		}


		if ($act == 'return' && $pmconfigs['transaction_end_status'] == $order->order_status)
		{

			return array(2, 'Payment for order #' . $order->order_number . ' was received', $transaction, $transactiondata);
		}
		else
		{
			if ($act == 'cancel')
			{
				return array(0, 'Payment for order #' . $order->order_number . ' was canceled', $transaction, $transactiondata);
			}
		}
	}

	/**
	 * Return request sign options (method of hash)
	 *
	 * @return mixed
	 */
	function paymaster_sign_options()
	{
		return [
			'sha256' => 'sha256',
			'md5'    => 'md5',
			'sha1'   => 'sha1',
		];
	}

	/**
	 * ���������� ������ �������� ���
	 *
	 * @return array
	 */
	function paymaster_vat_options()
	{
		return [
			'vat18'  => _JSHOP_CFG_VAT18,
			'vat10'  => _JSHOP_CFG_VAT10,
			'vat118' => _JSHOP_CFG_VAT_FORMULA_18_118,
			'vat110' => _JSHOP_CFG_VAT_FORMULA_10_110,
			'vat0'   => _JSHOP_CFG_VATO,
			'no_vat' => _JSHOP_CFG_NO_VAT,
		];
	}

	/**
	 * ������� ���������� ����������� ������ ��������� ��� ����?!
	 *
	 * @param        $merchant_id
	 * @param        $order_id
	 * @param        $amount
	 * @param        $lmi_currency
	 * @param        $secret_key
	 * @param string $sign_method
	 */
	function paymaster_get_sign($merchant_id, $order_id, $amount, $lmi_currency, $secret_key, $sign_method = 'md5')
	{
		$plain_sign = $merchant_id . $order_id . $amount . $lmi_currency . $secret_key;
		$sign       = base64_encode(hash($sign_method, $plain_sign, true));

		return $sign;
	}


	/**
	 * ������� ���������� hash �� ������������� ���������
	 *
	 * @param        $merchant_id
	 * @param        $order_id
	 * @param        $amount
	 * @param        $lmi_currency
	 * @param        $secret_key
	 * @param string $sign_method
	 */
	function paymaster_get_hash($LMI_MERCHANT_ID, $LMI_PAYMENT_NO, $LMI_SYS_PAYMENT_ID, $LMI_SYS_PAYMENT_DATE, $LMI_PAYMENT_AMOUNT, $LMI_CURRENCY, $LMI_PAID_AMOUNT, $LMI_PAID_CURRENCY, $LMI_PAYMENT_SYSTEM, $LMI_SIM_MODE, $SECRET, $hash_method = 'md5')
	{
		$string = $LMI_MERCHANT_ID . ";" . $LMI_PAYMENT_NO . ";" . $LMI_SYS_PAYMENT_ID . ";" . $LMI_SYS_PAYMENT_DATE . ";" . $LMI_PAYMENT_AMOUNT . ";" . $LMI_CURRENCY . ";" . $LMI_PAID_AMOUNT . ";" . $LMI_PAID_CURRENCY . ";" . $LMI_PAYMENT_SYSTEM . ";" . $LMI_SIM_MODE . ";" . $SECRET;

		$hash = base64_encode(hash($hash_method, $string, true));

		return $hash;
	}


	/**
	 * ����������� � ���� ��� �������, �� �������� ��������� Joomla ��� ������ ��
	 * ��������
	 *
	 * @param $text
	 *
	 *
	 * @since version
	 */
	public function logF($text)
	{
		$f = fopen(JPATH_ROOT . "/components/com_jshopping/log/payment.log", "a+");
		fwrite($f, date('Y-m-d H:i:s') . " " . $text . "\r\n");
		fclose($f);
	}


	/**
	 * @param $order
	 * @param $endStatus
	 *
	 * @return int
	 *
	 * @since version
	 */
	private function finishOrder($order, $endStatus)
	{
		$act            = 'finish';
		$payment_method = 'pm_yandexmoney';
		$no_lang        = '1';

		// joomla 3.x order finish
		/** @var jshopCheckoutBuy $checkout */
		$checkout = JSFactory::getModel('checkoutBuy', 'jshop');
		$checkout->saveToLogPaymentData();
		$checkout->setSendEndForm(0);
		$checkout->setAct($act);
		$checkout->setPaymentMethodClass($payment_method);
		$checkout->setNoLang($no_lang);
		$checkout->loadUrlParams();
		$checkout->setOrderId($order->order_id);
		$codebuy = $checkout->buy();
		if ($codebuy == 0)
		{
			JError::raiseWarning('', $checkout->getError());

			return 0;
		}
		/** @var jshopCheckoutFinish $checkout */
		$checkout = JSFactory::getModel('checkoutFinish', 'jshop');
		$order_id = $checkout->getEndOrderId();
		$text     = $checkout->getFinishStaticText();
		if ($order_id)
		{
			$checkout->paymentComplete($order_id, $text);
		}
		$checkout->clearAllDataCheckout();
	}

}