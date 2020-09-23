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
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Payment;
use App\Helpers\Helper;
use PayPal\Api\PaymentExecution;
use App\TrademarkApplication;
use App\TrademarkStatusHistory;
use Mail;

class PaymentController extends Controller
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
    /**
     * Initiate paypal transaction.
     * @param Request $request
     * @return Illuminate\Support\Facades\Redirect;
     **/
    public function payWithpaypal(Request $request)
    {

        $amountVal = 10;

        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        $item_1 = new Item();

        $item_1->setName(__('Tradeamrk Application'))
        /** item name **/
            ->setCurrency('USD')
            ->setQuantity(1)
            ->setPrice($amountVal);
        /** unit price **/

        $item_list = new ItemList();
        $item_list->setItems(array(
            $item_1
        ));

        $amount = new Amount();
        $amount->setCurrency('USD')
            ->setTotal($amountVal);

        $transaction = new Transaction();
        $transaction->setAmount($amount)->setItemList($item_list)
        ->setDescription("{$appName}: trademark application #{$trdToBePay}");
                                        
        $redirect_urls = new RedirectUrls();
        $redirect_urls->setReturnUrl(URL::route('payment_status'))
        /** Specify return URL **/
            ->setCancelUrl(URL::route('payment_status'));

        $payment = new Payment();
        $payment->setIntent('Sale')
            ->setPayer($payer)->setRedirectUrls($redirect_urls)->setTransactions(array($transaction));
        /** dd($payment->create($this->_api_context));exit; **/
        try {
            $payment->create($this->_api_context);
        } catch (\PayPal\Exception\PPConnectionException $ex) {
            if (\Config::get('app.debug')) {
                \Session::put('error', 'Connection timeout');
                return Redirect::route('paywithpaypal');
            } else {
                \Session::put('error', 'Some error occur, sorry for inconvenient');
                return Redirect::route('paywithpaypal');
            }
        }

        foreach ($payment->getLinks() as $link) {
            if ($link->getRel() == 'approval_url') {
                $redirect_url = $link->getHref();
                break;
            }
        }

        /** add payment ID to session **/
        Session::put('paypal_payment_id', $payment->getId());
        Session::put('trademark_id', $trdToBePay);

        if (isset($redirect_url)) {
            /** redirect to paypal **/
            return Redirect::away($redirect_url);
        }

        \Session::put('error', 'Unknown error occurred');
        return Redirect::route('paywithpaypal');
    }
    /**
     * paypal transaction Response.
     * @param Request $request;
     * @return View;
     **/
    public function paypalResponse(Request $request)
    {

        //print_r($request->all());
        
        /** Get the payment ID before session clear **/
        $payment_id = Session::get('paypal_payment_id');
        $trademark_id = Session::get('trademark_id');

        /** clear the session payment ID **/
        // Session::forget('paypal_payment_id');
        if (empty($request->PayerID) || empty($request->token)) {
            TrademarkApplication::findOrFail($trademark_id)->update(['application_status' => 'payment_failed']);

            TrademarkStatusHistory::where('trademark_country_maps.trademark_id', $trademark_id)->leftjoin('trademark_country_maps', 'trademark_country_maps.id', '=', 'trademark_status_histories.trademark_country_map_id')
                ->update(['trademark_status_histories.status' => 'payment_failed']);

            Session::forget('trademark_id'); //remove trademark id from session id
            return view('frontend.paymentstatus')
                ->with('payment', 'The PayerID is blank');
            die("The PayerID is blank.");
        }

        $payment = Payment::get($payment_id, $this->_api_context);
        $execution = new PaymentExecution();
        $execution->setPayerId($request->PayerID);

        /**Execute the payment **/
        $result = $payment->execute($execution, $this->_api_context);

        if ($result->getState() == 'approved') {
            // status update for trademark.
            $TrademarkTrdData = TrademarkApplication::select('application_status', 'parent_assessment_id')->where('id', $trademark_id)->get()
                ->first();

            if ($TrademarkTrdData->application_status == 'form_filled_assesment') {
                $statusIs = 'assessment_application';
            } else {
                $statusIs = 'received';
            }

            TrademarkApplication::findOrFail($trademark_id)->update(['application_status' => $statusIs]);

            TrademarkStatusHistory::where('trademark_country_maps.trademark_id', $trademark_id)->leftjoin('trademark_country_maps', 'trademark_country_maps.id', '=', 'trademark_status_histories.trademark_country_map_id')
                ->update(['trademark_status_histories.current_status' => '0']);

            TrademarkStatusHistory::where('trademark_country_maps.trademark_id', $trademark_id)->where('trademark_status_histories.status', '!=', 'form_filled_assesment')
                ->leftjoin('trademark_country_maps', 'trademark_country_maps.id', '=', 'trademark_status_histories.trademark_country_map_id')
                ->update(['trademark_status_histories.status' => 'received', 'trademark_status_histories.current_status' => '1']);
                
            TrademarkStatusHistory::where('trademark_country_maps.trademark_id', $trademark_id)->where('trademark_status_histories.status', 'form_filled_assesment')
                ->leftjoin('trademark_country_maps', 'trademark_country_maps.id', '=', 'trademark_status_histories.trademark_country_map_id')
                ->update(['trademark_status_histories.status' => 'assessment_application', 'trademark_status_histories.current_status' => '1']);

            //update status on trademark history table.
            

            //update payment status fto parent assessment application.
            if ($TrademarkTrdData->parent_assessment_id) {
                TrademarkApplication::findOrFail($TrademarkTrdData->parent_assessment_id)
                    ->update(['post_assessment_payment' => 1]);
            }

            //MAIL TO ADMIN FOR TRADEMARK RECEIVED SUCCESSFULLY
            $trdAppData = TrademarkApplication::select('parent_assessment_id', 'assessment_report', 'trademark_reference_number', 'case_no', 'receipt_no', 'application_status', 'contact_p_name', 'contact_p_email')->where('id', $trademark_id)->first();

            $trdMailData = array(
                'recnumber' => $trdAppData->receipt_no,
                'casenumber' => $trdAppData->case_no
            );

            Mail::send(
                [
                    'html' => 'emails.trdconfmail'
                ],
                $trdMailData,
                function ($message) use ($trdMailData) {
                    $message->to(
                        config('app.administration_mail'),
                        'Admin'
                    )
                      ->subject('Trademark application received');
                }
            );

            //Confirmation email to user.
            $appliCationType = '';
            //!$trdAppData->trademark_reference_number
            if ($trdAppData->application_status == 'received') {
                $appliCationType = 'Trademark application.';
            } elseif ($trdAppData->application_status == 'assessment_application') {
                $appliCationType = 'Assessment application.';
            } else {
                $appliCationType = 'New Application';
            }

            if ($trdAppData->parent_assessment_id) {
                $appliCationType = 'Post assessment application.';
            }

            $usrMailData = array(
                'usrDataObj' => $trdAppData,
                'application_type' => $appliCationType
            );

            $baseUrl = URL::to('/');
            

            $fileData = file_get_contents($baseUrl . '/getreceiptdata?id=' . $trademark_id);

            Mail::send(
                [
                    'html' => 'emails.application-conf'
                ],
                $usrMailData,
                function ($message) use ($usrMailData, $trdAppData, $trademark_id, $fileData) {
                    $message->to($trdAppData->contact_p_email, 'User')
                    ->subject('Trademark Application Received');
                    $message->attachData($fileData, $trdAppData->receipt_no . '.pdf');
                }
            );

            /*Notification to all Active staff member and Super admin.*/
            $mailSend = helper::trdNotifyToAll($usrMailData, $trdAppData, $appliCationType);
            /**************EndMailNotification****************/

            //Session::forget('trademark_id'); //remove trademark id from session id
            return view('frontend.paymentstatus')->with('payment', 'success')
                ->with('trdid', $trademark_id);
        }

        /*\Session::put('error', 'Payment failed');
         return Redirect::route('/');  */
        TrademarkApplication::findOrFail($trademark_id)->update(['application_status' => 'payment_failed']);
        Session::forget('trademark_id'); //remove trademark id from session id
        return view('frontend.paymentstatus')
            ->with('payment', 'failed'); //redirect for display
        //die("Nothing happened :/");
    }
}
