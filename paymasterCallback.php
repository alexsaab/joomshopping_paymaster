<?php
/**
 * @package    Joomla.Site
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Define the application's minimum supported PHP version as a constant so it can be referenced within the application.
 */
define('JOOMLA_MINIMUM_PHP', '5.3.10');


if (version_compare(PHP_VERSION, JOOMLA_MINIMUM_PHP, '<'))
{
	die('Your host needs to use PHP ' . JOOMLA_MINIMUM_PHP . ' or higher to run this version of Joomla!');
}

// Saves the start time and memory usage.
$startTime = microtime(1);
$startMem  = memory_get_usage();

/**
 * Constant that is checked in included files to prevent direct access.
 * define() is used in the installation folder rather than "const" to not error for PHP 5.2 and lower
 */
define('_JEXEC', 1);

if (file_exists(__DIR__ . '/defines.php'))
{
	include_once __DIR__ . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', __DIR__);
	require_once JPATH_BASE . '/includes/defines.php';
}

require_once JPATH_BASE . '/includes/framework.php';

// Set profiler start time and memory usage and mark afterLoad in the profiler.
JDEBUG ? JProfiler::getInstance('Application')->setStart($startTime, $startMem)->mark('afterLoad') : null;

// Instantiate the application.
$app = JFactory::getApplication('site');

//Получаем Post параметры
$callbackParamsPost = JRequest::get('post');
$callbackParamsGet  = JRequest::get('get');


include_once('components/com_jshopping/lib/factory.php');
include_once ('components/com_jshopping/payments/pm_paymaster/pm_paymaster.php');

$paymaster = new pm_paymaster();



$jshopConfig = JSFactory::getConfig();


$order = JSFactory::getTable('order', 'jshop');

$order_id = $callbackParamsGet['order_id'];

//if (!$order_id)
//	die();
//
//$order->load($order_id);




print_r($payment_method);

//$pmconfigs = $pm_method->getConfigs();
print_r($pmconfigs);

die();

$transaction     = $callbackParamsPost["LMI_SYS_PAYMENT_ID"];
$transactiondata = array(
	'LMI_SYS_PAYMENT_ID'   => $callbackParamsPost['LMI_SYS_PAYMENT_ID'],
	'LMI_PAYMENT_NO'       => $callbackParamsPost['LMI_PAYMENT_NO'],
	'LMI_SYS_PAYMENT_DATE' => $callbackParamsPost['LMI_SYS_PAYMENT_DATE'],
	'LMI_CURRENCY'         => $callbackParamsPost['LMI_CURRENCY'],
	'LMI_PAID_AMOUNT'      => $callbackParamsPost['LMI_PAID_AMOUNT'],
	'LMI_PAYMENT_SYSTEM'   => $callbackParamsPost['LMI_PAYMENT_SYSTEM'],
	'LMI_SIM_MODE'         => $callbackParamsPost['LMI_PAYMENT_SYLMI_SIM_MODESTEM'],
	'ORDER_ID'             => $order->order_id,
	'ORDER_NUMBER'         => $order->order_number);

$hash = $paymaster->paymaster_get_hash($callbackParams["LMI_MERCHANT_ID"], $callbackParams["LMI_PAYMENT_NO"], $callbackParams["LMI_SYS_PAYMENT_ID"], $callbackParams["LMI_SYS_PAYMENT_DATE"], $callbackParams["LMI_PAYMENT_AMOUNT"], $callbackParams["LMI_CURRENCY"], $callbackParams["LMI_PAID_AMOUNT"], $callbackParams["LMI_PAID_CURRENCY"], $callbackParams["LMI_PAYMENT_SYSTEM"], $callbackParams["LMI_SIM_MODE"], $pmconfigs['paymaster_secret_key'], $pmconfigs['paymaster_sign_method']);

saveToLog("paymentdata.log", "HASH: " . $hash);

$sign = $paymaster->paymaster_get_sign($callbackParams["LMI_MERCHANT_ID"], $callbackParams["LMI_PAYMENT_NO"], $callbackParams["LMI_PAYMENT_AMOUNT"], $callbackParams["LMI_CURRENCY"], $pmconfigs['paymaster_secret_key'], $pmconfigs['paymaster_sign_method']);

saveToLog("paymentdata.log", "SIGN: " . $sign);

if (($callbackParams["LMI_HASH"] == $hash) && ($callbackParams["SIGN"] == $sign))
{
	$paymaster->finishOrder($order);
	return array(1, 'Payment for order #' . $order->order_number . ' was received', $transaction, $transactiondata);
}