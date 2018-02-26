<?php

namespace Concrete\Package\CommunityStoreWorldpayHosted;

use Package;
use Route;
use Whoops\Exception\ErrorException;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as PaymentMethod;

class Controller extends Package
{
    protected $pkgHandle = 'community_store_worldpay_hosted';
    protected $appVersionRequired = '5.7.2';
    protected $pkgVersion = '1.0';

    public function getPackageDescription()
    {
        return t("WorldPay Hosted Payment Gateway for Community Store");
    }

    public function getPackageName()
    {
        return t("WorldPay Hosted Payment Gateway");
    }

    public function install()
    {
        $installed = Package::getInstalledHandles();
        if(!(is_array($installed) && in_array('community_store',$installed)) ) {
            throw new ErrorException(t('This package requires that Community Store be installed'));
        } else {
            $pkg = parent::install();
            $pm = new PaymentMethod();
            $pm->add('community_store_worldpay_hosted','WorldPay Hosted Payment Gateway',$pkg);
        }

    }
    public function uninstall()
    {
        $pm = PaymentMethod::getByHandle('community_store_worldpay_hosted');
        if ($pm) {
            $pm->delete();
        }
        $pkg = parent::uninstall();
    }

    public function on_start() {
        // Check the Routes are in place
        Route::register('/checkout/worldpay_hosted_response','\Concrete\Package\CommunityStoreWorldpayHosted\Src\CommunityStore\Payment\Methods\CommunityStoreWorldpayHosted\CommunityStoreWorldpayHostedPaymentMethod::validateCompletion');
    }
}
?>