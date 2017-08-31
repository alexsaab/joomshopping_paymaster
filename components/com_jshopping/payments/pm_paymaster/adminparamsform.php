<?php
//защита от прямого доступа
defined('_JEXEC') or die();
?>
<div class="col100">
    <fieldset class="adminform">
        <table class="admintable" width="100%">
            <tr>
                <td class="key" width="300">
                    <?php echo _JSHOP_CFG_PAYMASTER_MERCHANT_ID; ?></td>
                <td>
                    <input type="text" name="pm_params[paymaster_merchant_id]" class="inputbox"
                           value="<?php echo $params['paymaster_merchant_id']; ?>"/>
                </td>
            </tr>
            <tr>
                <td class="key">
                    <?php echo _JSHOP_CFG_PAYMASTER_SECRET_KEY; ?>
                </td>
                <td>
                    <input type="text" name="pm_params[paymaster_secret_key]" class="inputbox"
                           value="<?php echo $params['paymaster_secret_key']; ?>"/>
                </td>
            </tr>
            <tr>
                <td class="key">
                    <?php echo _JSHOP_CFG_PAYMASTER_PAYMENT_DETAIL; ?>
                </td>
                <td>
                    <input type="text" name="pm_params[paymaster_payment_detail]" class="inputbox"
                           value="<?php echo ($params['paymaster_payment_detail']) ? $params['paymaster_payment_detail'] : 'Оплата счета #'; ?>"/>
                </td>
            </tr>
            <tr>
                <td class="key">
                    <?php echo _JSHOP_CFG_PAYMASTER_SIGN_METHOD; ?>
                </td>
                <td>
                    <?php
                    echo JHTML::_('select.genericlist', $this->paymaster_sign_options(), 'pm_params[paymaster_sign_method]', 'class="inputbox" size="1"', 'paymaster_sign_method', 'name', $params['paymaster_sign_method']);
                    ?>
                </td>
            </tr>
            <tr>
                <td class="key">
                    <?php echo _JSHOP_CFG_PAYMASTER_VAT_DELIVERY; ?>
                </td>
                <td>
                    <?php
                    echo JHTML::_('select.genericlist', $this->paymaster_vat_options(), 'pm_params[paymaster_vat_delivery]', 'class="inputbox" size="1"', 'paymaster_vat_delivery', 'name', $params['paymaster_vat_delivery']);
                    ?>
                </td>
            </tr>
            <tr>
                <td class="key">
                    <?php echo _JSHOP_TRANSACTION_END; ?>
                </td>
                <td>
                    <?php
                    echo JHTML::_('select.genericlist', $orders->getAllOrderStatus(), 'pm_params[transaction_end_status]', 'class="inputbox" size="1"', 'status_id', 'name', $params['transaction_end_status']);
                    ?>
                </td>
            </tr>
            <tr>
                <td class="key">
                    <?php echo _JSHOP_TRANSACTION_PENDING; ?>
                </td>
                <td>
                    <?php
                    echo JHTML::_('select.genericlist', $orders->getAllOrderStatus(), 'pm_params[transaction_pending_status]', 'class="inputbox" size="1"', 'status_id', 'name', $params['transaction_pending_status']);
                    ?>
                </td>
            </tr>
            <tr>
                <td class="key">
                    <?php echo _JSHOP_TRANSACTION_FAILED; ?>
                </td>
                <td>
                    <?php
                    echo JHTML::_('select.genericlist', $orders->getAllOrderStatus(), 'pm_params[transaction_failed_status]', 'class="inputbox" size="1"', 'status_id', 'name', $params['transaction_failed_status']);
                    ?>
                </td>
            </tr>
        </table>
    </fieldset>
</div>
<div class="clr"></div>


<?php
saveToLog("payment.log", "TEST 25");
?>