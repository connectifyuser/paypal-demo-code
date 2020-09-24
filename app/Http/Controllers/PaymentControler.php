<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Payer;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Payment;
use App\Helpers\Helper;
use PayPal\Api\PaymentExecution;
use App\TrademarkApplication;
use App\TrademarkStatusHistory;
use Mail;

class PaymentControler extends Controller
{
     

    public function execute(Request $request)
    {
      	$apiContext = new \PayPal\Rest\ApiContext(
		  new \PayPal\Auth\OAuthTokenCredential(
		    'ASz2aeWWeLH4wAmWd1sM1JRuvtq3JM5fSMbJwk4jERuv2jQpL93aSM6OszArElh46x-xeRegtcuinqkX',
		    'EHNsJ_rDft46PCZZnfc9EiBmJd3qjhME7PqkUEN-tWs9gRHe1qz0e3TlNjvgF9oWNDhP-qov3MKfUVy_'
		  )
		);
        $paymentId = $request->paymentId;
    	$payment = Payment::get($paymentId, $apiContext);

    	$execution = new PaymentExecution();
    	$execution->setPayerId($request->PayerID);

		$transaction = new Transaction();
		$amount = new Amount();
		$details = new Details();

		$details
        ->setSubtotal(100);


	    $amount->setCurrency('USD');
	    $amount->setTotal(100);
	    $amount->setDetails($details);

	     $item_1 = new Item();

        $item_1->setName(__('Tradeamrk Application'))
        /** item name **/
            ->setCurrency('USD')
            ->setQuantity(1)
            ->setPrice(100);
        /** unit price **/

        $item_list = new ItemList();
        $item_list->setItems(array(
            $item_1
        ));
	    $transaction->setAmount($amount)->setItemList($item_list);

	    $execution->addTransaction($transaction);

	    $result = $payment->execute($execution, $apiContext);

	    return $result;
    }
}
