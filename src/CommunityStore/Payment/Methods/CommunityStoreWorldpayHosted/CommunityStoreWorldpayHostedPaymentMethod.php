<?php

namespace Concrete\Package\CommunityStoreWorldpayHosted\Src\CommunityStore\Payment\Methods\CommunityStoreWorldpayHosted;

use Core;
use URL;
use Config;
use Session;
use Log;
use FileList;
use File;

use \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as StorePaymentMethod;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Cart\Cart as StoreCart;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order as StoreOrder;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Customer\Customer as StoreCustomer;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Order\OrderStatus\OrderStatus as StoreOrderStatus;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Utilities\Calculator as StoreCalculator;

class CommunityStoreWorldpayHostedPaymentMethod extends StorePaymentMethod
{
    public function dashboardForm()
    {
        $this->set('worldpayInstId', Config::get('community_store_worldpay_hosted.worldpayInstId'));
        $this->set('worldpayPaymentResponsePassword', Config::get('community_store_worldpay_hosted.worldpayPaymentResponsePassword'));
        $this->set('worldpayTestMode', Config::get('community_store_worldpay_hosted.worldpayTestMode'));
        $this->set('worldpayCurrency', Config::get('community_store_worldpay_hosted.worldpayCurrency'));
        $currencies = array(
            'AUD' => "Australian Dollar",
            'CAD' => "Canadian Dollar",
            'CZK' => "Czech Koruna",
            'DKK' => "Danish Krone",
            'EUR' => "Euro",
            'HKD' => "Hong Kong Dollar",
            'HUF' => "Hungarian Forint",
            'ILS' => "Israeli New Sheqel",
            'JPY' => "Japanese Yen",
            'MXN' => "Mexican Peso",
            'NOK' => "Norwegian Krone",
            'NZD' => "New Zealand Dollar",
            'PHP' => "Philippine Peso",
            'PLN' => "Polish Zloty",
            'GBP' => "Pound Sterling",
            'SGD' => "Singapore Dollar",
            'SEK' => "Swedish Krona",
            'CHF' => "Swiss Franc",
            'TWD' => "Taiwan New Dollar",
            'THB' => "Thai Baht",
            'USD' => "U.S. Dollar"
        );
        $this->set('currencies', $currencies);
        $this->set('form', Core::make("helper/form"));
    }

    public function save(array $data = array())
    {
        Config::save('community_store_worldpay_hosted.worldpayInstId', $data['worldpayInstId']);
        Config::save('community_store_worldpay_hosted.worldpayPaymentResponsePassword', $data['worldpayPaymentResponsePassword']);
        Config::save('community_store_worldpay_hosted.worldpayTestMode', $data['worldpayTestMode']);
        Config::save('community_store_worldpay_hosted.worldpayCurrency', $data['worldpayCurrency']);
    }

    public function validate($args, $e)
    {
        // Don't need email for WorldPay
        //        $pm = StorePaymentMethod::getByHandle('community_store_worldpay_hosted');
        //        if($args['paymentMethodEnabled'][$pm->getID()]==1){
        //            if($args['worldpayEmail']==""){
        //                $e->add(t("WorldPay Email must be set")); // For Paypal
        //            }
        //        }
        return $e;

    }

    public function redirectForm()
    {
        $customer = new StoreCustomer();

        $worldpayInstId = Config::get('community_store_worldpay_hosted.worldpayInstId');
        $worldpayTestMode = Config::get('community_store_worldpay_hosted.worldpayTestMode');
        $order = StoreOrder::getByID(Session::get('orderID'));
        $this->set('worldpayInstId', $worldpayInstId);
        $this->set('worldpayTestMode', $worldpayTestMode);
        $this->set('siteName', Config::get('concrete.site'));
        $this->set('customer', $customer);
        $this->set('total', $order->getTotal());
        $this->set('notifyURL', URL::to('/checkout/worldpay_hosted_response'));
        $this->set('orderID', $order->getOrderID());
        $this->set('returnURL', URL::to('/checkout/complete'));
        $this->set('cancelReturn', URL::to('/checkout'));
        $currencyCode = Config::get('community_store_worldpay_hosted.worldpayCurrency');
        if (!$currencyCode) {
            $currencyCode = "GBP";
        }
        $this->set('currencyCode', $currencyCode);
    }

    public function submitPayment()
    {
        return array('error' => 0, 'transactionReference' => '');
    }

    public function getAction()
    {
        if (Config::get('community_store_worldpay_hosted.worldpayTestMode') == true) {
            return "https://secure-test.worldpay.com/wcc/purchase";
        } else {
            return "https://secure.worldpay.com/wcc/purchase";
        }
    }

    public static function validateCompletion()
    {
        // Get the POST data
        // Reading posted data directly from $_POST causes serialization
        // issues with array data in POST. Reading raw POST data from input stream instead.
        $raw_post_data = file_get_contents('php://input');
        $raw_post_array = explode('&', $raw_post_data);
        $myPost = array();
        foreach ($raw_post_array as $keyval) {
            $keyval = explode('=', $keyval);
            if (count($keyval) == 2)
                $myPost[$keyval[0]] = urldecode($keyval[1]);
        }

        //Setup a default response just in case the worldpay response files haven't been uploaded into the File Manager
        $response = '<html><head><title>'.t("WorldPay Transaction").'</title></head><WPDISPLAY FILE=\"header.html\"><body><h1>'.t("Default Worldpay Payment Response file").'</h1><WPDISPLAY ITEM="banner"><WPDISPLAY FILE="footer.html"></body></html>';
        $res = 'VERIFIED'; // Stays VERIFIED if each test passes

        // First check the Payment Response Transaction Status field, 'tranStatus'.
        $currSection = 'TRANSSTATUS'; // $currSection is used to identify which test has failed
        if (array_key_exists('transStatus', $myPost)) {
            $res = ($myPost['transStatus'] == 'Y') ? 'VERIFIED' : 'UNVERIFIED';
        }

        // If worldpayPaymentResponsePassword exists, check this against 'callbackPW'.
        if (strcmp($res, 'VERIFIED') == 0) {
            $currSection = 'PWTEST';
            if (($localPW = Config::get('community_store_worldpay_hosted.worldpayPaymentResponsePassword')) != '') {
                if (array_key_exists('callbackPW', $myPost)) {
                    $res = ($myPost['callbackPW'] == $localPW) ? 'VERIFIED' : 'UNVERIFIED';
                } else {
                    $res = 'UNVERIFIED'; // No password returned!
                }
            }
        }

        // Verify the local cart total against the returned 'amount'
        if (strcmp($res, 'VERIFIED') == 0) {
            $currSection = 'CARTAMOUNTTEST';
            if (array_key_exists('cartId', $myPost)) {
                $order = StoreOrder::getByID($myPost['cartId']);
                if ($order !== NULL) {
                    $res = ($myPost['amount'] == $order->getTotal()) ? 'VERIFIED' : 'UNVERIFIED';
                } else {
                    $res = 'UNVERIFIED'; // No matching cartId!
                }
            } else {
                $res = 'UNVERIFIED'; // No cartId returned!
            }
        }

        $list = new \Concrete\Core\File\FileList(); // Used to get the response files
        if (strcmp($res, 'VERIFIED') == 0) {
            //Payment Successful! Update the Store
            $order = StoreOrder::getByID($myPost['cartId']);
            $order->completeOrder($myPost['transId']);
            $order->updateStatus(StoreOrderStatus::getStartingStatus()->getHandle());

            //Get the 'success' file and return it to WorldPay to display
            $list->filterByKeywords('worldpay_success_response.txt');
            $files = $list->getResults();
            if (!empty($files)) {
                $response = $files[0]->getFileContents();
            }
        } else {
//            Log::addDebug("currSection: $currSection, Payment failed");
            $errMsg = '';
            switch ($currSection) {
                // Setup the error message and go to an error page.
                case 'TRANSSTATUS': // This mean the transaction was refused by WorldPay. Check the fraud markers using AVS
                    /*
                     * A 4-character string giving the results of 4 internal fraud-related checks. The characters respectively give the results of the following checks:
                     * 1st character - Card Verification Value check
                     * 2nd character - postcode AVS check
                     * 3rd character - address AVS check
                     * 4th character - country comparison check (see also countryMatch)
                     * The possible values for each result character are:
                     * 0 - Not supported, 1 - Not checked, 2 - Matched, 4 - Not matched, 8 - Partially matched
                     */
                    $errMsg .= t('Payment cancelled or declined by WorldPay. The AVS code was:') . $myPost['AVS'];
                    break;
                case 'PWTEST': // Transaction not refused, but there's a problem with the password == Fraud
                    $errMsg .= t('Payment accepted, but the password (callbackPW) does not match the local version.  Please contact Worldpay support at http://www.worldpay.com/');
                    break;
                case 'CARTAMOUNTTEST':  // Transaction not refused, but the cartId amount's do not match == Fraud
                    $errMsg .= t('Payment accepted, but the local cart total does not match the amount received from Worldpay.  Please contact Worldpay support at http://www.worldpay.com/');
                    break;
                default:
                    break;
            }
            //Get the 'failure' file. Insert the specific error message and return it to WorldPay
            $list->filterByKeywords('worldpay_failure_response.txt');
            $files = $list->getResults();
            if (!empty($files)) {
                $response = str_replace('<!--ERRMSG-->', $errMsg, $files[0]->getFileContents());
            }
        }
        echo $response; //Return to WorldPay.
    }


    public function getPaymentMinimum()
    {
        return 0.03;
    }


    public function getName()
    {
        return 'WorldPay Hosted Payment Gateway';
    }

    public function isExternal()
    {
        return true;
    }
}
