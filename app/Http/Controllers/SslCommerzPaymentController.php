<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Library\SslCommerz\SslCommerzNotification;
use Illuminate\Support\Str;
use App\Models\Order;
use Illuminate\Contracts\Session\Session;

class SslCommerzPaymentController extends Controller
{

    public function exampleEasyCheckout()
    {
        return view('exampleEasycheckout');
    }

    public function exampleHostedCheckout()
    {
        return view('exampleHosted');
    }

    public function index(Request $request)
    {
        # Here you have to receive all the order data to initate the payment.
        # Let's say, your oder transaction informations are saving in a table called "orders"
        # In "orders" table, order unique identity is "transaction_id". "status" field contain status of the transaction, "amount" is the order amount to be paid and "currency" is for storing Site Currency which will be checked with paid currency.

        $post_data = array();
        $ticketlist = $request->input('ticketlist');
        
        // Validate required fields
        if (!$request->input('amount')) {
            return redirect()->back()->with('error', 'Amount is required');
        }
        if (!$request->input('customer_name')) {
            return redirect()->back()->with('error', 'Customer name is required');
        }
        if (!$request->input('customer_email')) {
            return redirect()->back()->with('error', 'Customer email is required');
        }
        if (!$request->input('customer_mobile')) {
            return redirect()->back()->with('error', 'Customer mobile is required');
        }
        if (!$request->input('address')) {
            return redirect()->back()->with('error', 'Address is required');
        }
        
        $post_data['total_amount'] = $request->input('amount'); # You cant not pay less than 10
        $post_data['currency'] = "BDT";
        $post_data['tran_id'] = Str::random(30); // tran_id must be unique

        $post_data['cus_name'] = $request->input('customer_name');
        $post_data['cus_email'] = $request->input('customer_email');
        $post_data['cus_add1'] = $request->input('address');
        $post_data['cus_phone'] = $request->input('customer_mobile');
        $post_data['cus_country'] = "Bangladesh"; // Assuming the country is Bangladesh

        // Shipment Information - Assuming it's the same as customer information
        $post_data['ship_name'] = $request->input('customer_name');
        $post_data['ship_add1'] = $request->input('address');
        $post_data['ship_country'] = "Bangladesh"; // Assuming the country is Bangladesh

        // Other information
        $post_data['shipping_method'] = "NO"; // Assuming no shipping is involved
        $post_data['product_name'] = "Ticket"; // Adjust as per your requirement
        $post_data['product_category'] = "Service"; // Adjust as per your requirement
        $post_data['product_profile'] = "service";

        // Optional Parameters
        $post_data['value_a'] = ""; // Adjust as per your requirement
        $post_data['value_b'] = ""; // Adjust as per your requirement
        $post_data['value_c'] = ""; // Adjust as per your requirement
        $post_data['value_d'] = ""; // Adjust as per your requirement


        #Before  going to initiate the payment order status need to insert or update as Pending.
        $update_product = Order::updateOrCreate(
            ['transaction_id' => $post_data['tran_id']],
            [
                'name' => $post_data['cus_name'],
                'email' => $post_data['cus_email'],
                'phone' => $post_data['cus_phone'],
                'amount' => $post_data['total_amount'],
                'status' => 'Pending',
                'address' => $post_data['cus_add1'],
                'transaction_id' => $post_data['tran_id'],
                'currency' => $post_data['currency'],
                'bus_id' => $request->input('bus_id'),
                // 'card_issuer' => $request->input('card_issuer'),
                'ticketlist' => json_encode($ticketlist),
            ]
        );

        $sslc = new SslCommerzNotification();
        # initiate(Transaction Data , false: Redirect to SSLCOMMERZ gateway/ true: Show all the Payement gateway here )
        $payment_options = $sslc->makePayment($post_data, 'hosted');

        if (!is_array($payment_options)) {
            return redirect()->back()->with('error', is_string($payment_options) ? $payment_options : 'Payment initiation failed.');
        }
    }
    public function success(Request $request)
    {
        // echo "Transaction is Successful";
        // dd($request->all());

        $tran_id = $request->input('tran_id');
        $amount = $request->input('amount');
        $currency = $request->input('currency');
        $card_issuer = $request->input('card_issuer');

        $sslc = new SslCommerzNotification();
        $order_details = Order::where('transaction_id', $tran_id)
            ->select('transaction_id', 'status', 'currency', 'amount')
            ->first();
        $order = Order::where('transaction_id', $tran_id)->first();
        $bus = Bus::find($order->bus_id);
        if ($order_details->status == 'Pending') {
            $validation = $sslc->orderValidate($request->all(), $tran_id, $amount, $currency);

            if ($validation) {
                $update_product = Order::where('transaction_id', $tran_id)
                    ->update(['status' => 'Processing', 'card_issuer' => $card_issuer]);
                UpdateSeatInfo($order, $bus);
                return view('showdownloadinfo', compact('order', 'bus', 'card_issuer'));
            }
        } else if ($order_details->status == 'Processing' || $order_details->status == 'Complete') {
            UpdateSeatInfo($order, $bus);
            return view('showdownloadinfo', compact('order', 'bus', 'card_issuer'));
        } else {
            echo "Invalid Transaction";
        }
    }

    public function fail(Request $request)
    {
        $tran_id = $request->input('tran_id');

        $order_details = DB::table('orders')
            ->where('transaction_id', $tran_id)
            ->select('transaction_id', 'status', 'currency', 'amount')->first();

        if ($order_details->status == 'Pending') {
            $update_product = DB::table('orders')
                ->where('transaction_id', $tran_id)
                ->update(['status' => 'Failed']);
            echo "Transaction is Falied";
        } else if ($order_details->status == 'Processing' || $order_details->status == 'Complete') {
            echo "Transaction is already Successful";
        } else {
            echo "Transaction is Invalid";
        }
    }

    public function cancel(Request $request)
    {
        $tran_id = $request->input('tran_id');

        $order_details = DB::table('orders')
            ->where('transaction_id', $tran_id)
            ->select('transaction_id', 'status', 'currency', 'amount')->first();

        if ($order_details->status == 'Pending') {
            $update_product = DB::table('orders')
                ->where('transaction_id', $tran_id)
                ->update(['status' => 'Canceled']);
            echo "Transaction is Cancel";
        } else if ($order_details->status == 'Processing' || $order_details->status == 'Complete') {
            echo "Transaction is already Successful";
        } else {
            echo "Transaction is Invalid";
        }
    }

    public function ipn(Request $request)
    {
        #Received all the payement information from the gateway
        if ($request->input('tran_id')) #Check transation id is posted or not.
        {

            $tran_id = $request->input('tran_id');

            #Check order status in order tabel against the transaction id or order id.
            $order_details = DB::table('orders')
                ->where('transaction_id', $tran_id)
                ->select('transaction_id', 'status', 'currency', 'amount')->first();

            if ($order_details->status == 'Pending') {
                $sslc = new SslCommerzNotification();
                $validation = $sslc->orderValidate($request->all(), $tran_id, $order_details->amount, $order_details->currency);
                if ($validation == TRUE) {
                    /*
                    That means IPN worked. Here you need to update order status
                    in order table as Processing or Complete.
                    Here you can also sent sms or email for successful transaction to customer
                    */
                    $update_product = DB::table('orders')
                        ->where('transaction_id', $tran_id)
                        ->update(['status' => 'Processing']);

                    echo "Transaction is successfully Completed";
                    // return view("success");
                }
            } else if ($order_details->status == 'Processing' || $order_details->status == 'Complete') {

                #That means Order status already updated. No need to udate database.

                echo "Transaction is already successfully Completed";
            } else {
                #That means something wrong happened. You can redirect customer to your product page.

                echo "Invalid Transaction";
            }
        } else {
            echo "Invalid Data";
        }
    }
}
