<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Library\SslCommerz\SslCommerzNotification;
use Illuminate\Support\Str;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;

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

        $validated = $request->validate([
            'bus_id' => ['required', 'integer', 'exists:buses,id'],
            'ticketlist' => ['required', 'array', 'min:1', 'max:40'],
            'ticketlist.*' => ['required', 'string', 'regex:/^[A-J][1-4]$/'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_mobile' => ['required', 'digits:11'],
            'address' => ['required', 'string', 'max:500'],
        ]);

        $bus = Bus::findOrFail($validated['bus_id']);
        $ticketlist = array_values(array_unique($validated['ticketlist']));
        $seatIndexes = array_flip($this->seatNames($bus->total_seats));

        foreach ($ticketlist as $seat) {
            if (!array_key_exists($seat, $seatIndexes)) {
                return redirect()->back()->withInput()->with('error', "Seat {$seat} does not exist on this coach.");
            }

            $index = $seatIndexes[$seat];
            if (($bus->view[$index] ?? '1') !== '0') {
                return redirect()->back()->withInput()->with('error', "Seat {$seat} is no longer available.");
            }
        }

        $post_data = [];
        $post_data['total_amount'] = (float) $bus->fare * count($ticketlist);
        $post_data['currency'] = "BDT";
        $post_data['tran_id'] = Str::random(30); // tran_id must be unique

        $post_data['cus_name'] = $validated['customer_name'];
        $post_data['cus_email'] = $validated['customer_email'];
        $post_data['cus_add1'] = $validated['address'];
        $post_data['cus_phone'] = $validated['customer_mobile'];
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

        // ---------------------------------------------------------------------
        // A3 hybrid guest checkout: resolve the account this order belongs to
        // BEFORE writing the order. Security rules are deliberately strict —
        // unauthenticated billing input must never be able to take over a real
        // account.
        // ---------------------------------------------------------------------
        $userId = $this->resolveOrderUser($validated);

        $update_product = Order::updateOrCreate(
            ['transaction_id' => $post_data['tran_id']],
            [
                'user_id' => $userId,
                'name' => $post_data['cus_name'],
                'email' => $post_data['cus_email'],
                'phone' => $post_data['cus_phone'],
                'amount' => $post_data['total_amount'],
                'status' => 'Pending',
                'address' => $post_data['cus_add1'],
                'transaction_id' => $post_data['tran_id'],
                'currency' => $post_data['currency'],
                'bus_id' => $bus->id,
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

    /**
     * Resolve the user id to stamp on a new order (A3 guest checkout).
     *
     * @param  array{customer_name:string,customer_email:string,customer_mobile:string}  $validated
     */
    private function resolveOrderUser(array $validated): ?int
    {
        // (a) Already authenticated: trust the session, create nothing.
        if (Auth::check()) {
            return Auth::id();
        }

        // (b) Guest. Look for an account that already owns this email OR mobile.
        $existing = User::where('email', $validated['customer_email'])
            ->orWhere('mobile_no', $validated['customer_mobile'])
            ->first();

        if ($existing) {
            // A real account matches. Link the order to it for history/tracking,
            // but DO NOT log the guest in as them and DO NOT modify that account.
            // Anyone can type someone else's email/phone into a billing form —
            // we never hijack a real account from unauthenticated input.
            return $existing->id;
        }

        // (c) No match: auto-provision a NEW, unclaimed account (password = null).
        try {
            $newUser = User::create([
                'name' => $validated['customer_name'],
                'email' => $validated['customer_email'],
                'mobile_no' => $validated['customer_mobile'],
                'password' => null, // NULL marks an unclaimed auto-created account
            ]);
        } catch (QueryException $e) {
            // Race: a concurrent request inserted the same email/mobile between
            // our lookup and insert (unique(email)/unique(mobile_no) violation).
            // Re-fetch and attach by id — but treat it as pre-existing, so we do
            // NOT log in on this path (only a freshly created account is logged in).
            $raced = User::where('email', $validated['customer_email'])
                ->orWhere('mobile_no', $validated['customer_mobile'])
                ->first();

            return $raced?->id;
        }

        // Only ever auto-login a FRESHLY created account, never a pre-existing
        // one. wasRecentlyCreated is true only for the insert we just performed.
        if ($newUser->wasRecentlyCreated) {
            Auth::login($newUser);
        }

        return $newUser->id;
    }

    public function success(Request $request)
    {
        $tran_id = trim((string) $request->input('tran_id'));
        if ($tran_id === '') {
            return redirect()->route('home')->with('error', 'The payment callback is missing a transaction ID.');
        }

        $order = Order::where('transaction_id', $tran_id)->first();
        if (!$order) {
            return redirect()->route('home')->with('error', 'The payment transaction could not be found.');
        }

        $bus = Bus::find($order->bus_id);
        if (!$bus) {
            return redirect()->route('home')->with('error', 'The bus for this transaction is no longer available.');
        }

        $card_issuer = $request->input('card_issuer');

        if ($order->status === 'Pending') {
            $sslc = new SslCommerzNotification();
            $validation = $sslc->orderValidate(
                $request->all(),
                $tran_id,
                $order->amount,
                $order->currency
            );

            if (!$validation) {
                return redirect()->route('home')->with('error', 'SSLCommerz could not validate this payment.');
            }

            $seatUpdateSucceeded = $this->completeOrder($order, $card_issuer);

            if (!$seatUpdateSucceeded) {
                return redirect()->route('home')->with(
                    'error',
                    'Payment was received, but one or more seats were no longer available. Please contact support with transaction ' . $tran_id . '.'
                );
            }

            $order->refresh();
            $bus->refresh();
        } elseif (!in_array($order->status, ['Processing', 'Complete'], true)) {
            return redirect()->route('home')->with('error', 'This transaction is not in a successful state.');
        }

        return view('showdownloadinfo', compact('order', 'bus', 'card_issuer'));
    }

    private function seatNames(int $totalSeats): array
    {
        $seats = [];

        for ($row = 'A'; $row <= 'J' && count($seats) < $totalSeats; $row++) {
            for ($number = 1; $number <= 4 && count($seats) < $totalSeats; $number++) {
                $seats[] = $row . $number;
            }
        }

        return $seats;
    }

    private function completeOrder(Order $order, ?string $cardIssuer = null): bool
    {
        return DB::transaction(function () use ($order, $cardIssuer) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (in_array($lockedOrder->status, ['Processing', 'Complete'], true)) {
                return true;
            }

            if ($lockedOrder->status !== 'Pending') {
                return false;
            }

            $lockedBus = Bus::whereKey($lockedOrder->bus_id)->lockForUpdate()->first();
            if (!$lockedBus || !UpdateSeatInfo($lockedOrder, $lockedBus)) {
                $lockedOrder->update(['status' => 'Conflict', 'card_issuer' => $cardIssuer]);
                return false;
            }

            $lockedOrder->update([
                'status' => 'Processing',
                'card_issuer' => $cardIssuer,
            ]);

            return true;
        });
    }

    public function fail(Request $request)
    {
        return $this->closeUnsuccessfulOrder($request, 'Failed', 'Payment failed. No ticket was issued.');
    }

    public function cancel(Request $request)
    {
        return $this->closeUnsuccessfulOrder($request, 'Canceled', 'Payment was canceled. No ticket was issued.');
    }

    public function ipn(Request $request)
    {
        $tranId = trim((string) $request->input('tran_id'));
        if ($tranId === '') {
            return response('Invalid data', 422);
        }

        $order = Order::where('transaction_id', $tranId)->first();
        if (!$order) {
            return response('Invalid transaction', 404);
        }

        if (in_array($order->status, ['Processing', 'Complete'], true)) {
            return response('Transaction already successfully completed');
        }

        if ($order->status !== 'Pending') {
            return response('Invalid transaction state', 409);
        }

        $sslc = new SslCommerzNotification();
        $validation = $sslc->orderValidate(
            $request->all(),
            $tranId,
            $order->amount,
            $order->currency
        );

        if (!$validation) {
            return response('Payment validation failed', 422);
        }

        if (!$this->completeOrder($order, $request->input('card_issuer'))) {
            return response('Payment received but seat assignment failed', 409);
        }

        return response('Transaction successfully completed');
    }

    private function closeUnsuccessfulOrder(Request $request, string $status, string $message)
    {
        $tranId = trim((string) $request->input('tran_id'));
        if ($tranId === '') {
            return redirect()->route('home')->with('error', 'The payment callback is missing a transaction ID.');
        }

        $order = Order::where('transaction_id', $tranId)->first();
        if (!$order) {
            return redirect()->route('home')->with('error', 'The payment transaction could not be found.');
        }

        if (in_array($order->status, ['Processing', 'Complete'], true)) {
            return redirect()->route('purchase_history')->with('success', 'This transaction was already completed.');
        }

        if ($order->status === 'Pending') {
            $order->update(['status' => $status]);
        }

        return redirect()->route('home')->with('error', $message);
    }
}
