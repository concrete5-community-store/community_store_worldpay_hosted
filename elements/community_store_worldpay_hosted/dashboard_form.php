<?php 
defined('C5_EXECUTE') or die(_("Access Denied."));
extract($vars);
?>

<div class="form-group">
    <label><?= t("WorldPay Installation Id")?></label>
    <input type="text" name="worldpayInstId" value="<?= $worldpayInstId?>" class="form-control">
</div>

<div class="form-group">
    <label><?= t('Test Mode')?></label>
    <?= $form->select('worldpayTestMode', array(false=>'Live',true=>'Test Mode'), $worldpayTestMode); ?>
</div>

<div class="form-group">
    <?= $form->label('worldpayPaymentResponsePassword',t("WorldPay Payment Response Password")); ?>
    <input type="text" name="worldpayPaymentResponsePassword" value="<?= $worldpayPaymentResponsePassword?>" class="form-control">
</div>

<div class="form-group">
    <?= $form->label('worldpayCurrency',t("Currency")); ?>
    <?= $form->select('worldpayCurrency', $currencies, $worldpayCurrency?$worldpayCurrency:'GBP');?>
</div>
