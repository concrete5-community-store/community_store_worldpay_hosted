<?php defined('C5_EXECUTE') or die(_("Access Denied."));
extract($vars);
// Set up the dashboard form to submit to WorldPay.
// This form will automatically submit itself 

// Use Test Mode variables or Customer Name
$testOrLive = "";
if($worldpayTestMode==true) {
    $testOrLive .= '<input type="hidden" name="testMode" value="100">';
    $testOrLive .= '<input type="hidden" name="name" value="'. $worldpayTestResponse .'">';
} else {
    $testOrLive .= '<input type="hidden" name="name" value="'. $customer->getValue("billing_first_name") .' '. $customer->getValue("billing_last_name").'">';
}
?>

<!-- These first four elements are mandatory. -->
<input type="hidden" name="instId" value="<?= $worldpayInstId?>">
<input type="hidden" name="cartId" value="<?= $orderID?>">
<input type="hidden" name="currency" value="<?= $currencyCode?>">
<input type="hidden" name="amount" value="<?= $total?>">

<? echo $testOrLive; ?>

<input type="hidden" name="address1" value="<?= $customer->getValue("billing_address")->address1?>">
<input type="hidden" name="address2" value="<?= $customer->getValue("billing_address")->address2?>">
<input type="hidden" name="town" value="<?= $customer->getValue("billing_address")->city?>">
<input type="hidden" name="region" value="<?= $customer->getValue("billing_address")->state_province?>">
<input type="hidden" name="postcode" value="<?= $customer->getValue("billing_address")->postal_code?>">
<input type="hidden" name="country" value="<?= $customer->getValue("billing_address")->country?>">
<input type="hidden" name="email" value="<?= $customer->getEmail()?>">
<input type="hidden" name="desc" value="<?= t('Order from %s', $siteName)?>">
<input type="hidden" name="notify_url" value="<?= $notifyURL?>">
<input type="hidden" name="return_url" value="<?= $returnURL?>">


