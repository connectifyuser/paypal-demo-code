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
use PayPal\Api\Authorization;
use App\Helpers\Helper;
use PayPal\Api\PaymentExecution;
use App\TrademarkApplication;
use App\TrademarkStatusHistory;
use Mail;

class HomeController extends Controller
{
	public function __construct()
    {
		/** PayPal api context **/
		$paypal_conf = \Config::get('paypal');

		$this->_api_context = new
		ApiContext(new OAuthTokenCredential(
			$paypal_conf['client_id'],
			$paypal_conf['secret']
		));

		$this
			->_api_context
			->setConfig($paypal_conf['settings']);
    }

	public function paypaldemo()
	{
		return view('paypaldemo');
	}

	public function createOrder(){
		$payer = new Payer();
		$payer->setPaymentMethod("paypal");
		$item1 = new Item();
		$item1->setName('Trademark application')
			    ->setCurrency('USD')
			    ->setQuantity(1)
		    	->setPrice(25);

		$itemList = new ItemList();
		$itemList->setItems(array($item1));

		$details = new Details();
		$details->setSubtotal(25);

		$amount = new Amount();
		$amount->setCurrency("USD")
		    ->setTotal(25)
		    ->setDetails($details);

		$transaction = new Transaction();
		$transaction->setAmount($amount)
		    ->setItemList($itemList)
		    ->setDescription("Payment description")
		    ->setInvoiceNumber(uniqid());

		$baseUrl = URL::to('/');
		$redirectUrls = new RedirectUrls();
		$redirectUrls->setReturnUrl("$baseUrl/paypaldemo")
		    ->setCancelUrl("$baseUrl/paypaldemo");

		$payment = new Payment();
		$payment->setIntent("sale")
			    ->setPayer($payer)
			    ->setRedirectUrls($redirectUrls)
			    ->setTransactions(array($transaction));

		$request = clone $payment;

		try {
		    $payment->create($this->_api_context);
		} catch (Exception $ex) {
    		exit(1);
		}
		$approvalUrl = $payment->getApprovalLink();
		
	   	return $payment;
	   	die;
	}

	public function executePayment(Request $request)
	{
		$paymentId = $request->paymentid;
	
    	$payment = Payment::get($paymentId, $this->_api_context);
    	
    	$execution = new PaymentExecution();
    	$execution->setPayerId($request->payerid);

		$execution = new PaymentExecution();
		$execution->setPayerId($request->payerid);

		$execution = new PaymentExecution();
		$execution->setPayerId($request->payerid);

		$amount = new Amount();
		$amount->setCurrency('USD');
	    $amount->setTotal(25);

	    $transaction = new Transaction();
	    $transaction->setAmount($amount);

	    $execution->addTransaction($transaction);

	    try {
			$result = $payment->execute($execution, $this->_api_context);
		        try {
		            $payment = Payment::get($paymentId, $this->_api_context);
		        } catch (Exception $ex) {
		            ResultPrinter::printError("Get Payment", "Payment", null, null, $ex);
		            exit(1);
		        }
		    } catch (Exception $ex) {
		        exit(1);
		    }
		    //return $result;
		    var_dump($result);
		    die;
	}
}