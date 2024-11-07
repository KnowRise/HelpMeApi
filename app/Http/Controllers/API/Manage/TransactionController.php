<?php

namespace App\Http\Controllers\API\Manage;

use App\Http\Controllers\Controller;
use App\Jobs\CheckExpiryJob;
use App\Models\Mitra;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\WithDraw;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\CoreApi;
use Midtrans\Snap;
use Midtrans\Transaction as MidtransTransaction;

class TransactionController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$clientKey = config('midtrans.client_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    public function createTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => ['required', 'exists:orders,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 400);
        }

        $order = Order::find($request->order_id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 400);
        }

        if ($order->status != 'booked') {
            return response()->json(['message' => 'The selected Order is Invalid'], 400);
        }

        $user = $request->user();
        if ($user->id != $order->user_id) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $extitingTransaction = Transaction::where('order_id', $order->id)->where('status', 'pending')->first();
        if ($extitingTransaction) {
            return response()->json(['message' => 'Transaction already in progress'], 400);
        }

        $invoice = 'HELPME!-' . Str::random(16);
        $transportCost = $order->acceptedOffer->transport_cost;
        // $platformFee = $order->acceptedOffer->platform_fee;
        $markupCost = $order->acceptedOffer->markup_cost;
        $totalPrice = $order->acceptedOffer->total_price;

        $transaction = new Transaction();
        $transaction->transport_cost = $transportCost;
        // $transaction->platform_fee = $platformFee;
        $transaction->markup_cost = $markupCost;
        $transaction->total_price = $totalPrice;
        $transaction->transaction_time = now();
        $transaction->expire_time = now()->addMinutes(10);
        $transaction->user_id = $request->user()->id;
        $transaction->order_id = $request->order_id;
        $transaction->invoice = $invoice;

        $params = [
            'transaction_details' => [
                'order_id' => $invoice,
                'gross_amount' => $totalPrice,
            ],
            'customer_details' => [
                'first_name' => $order->user->full_name,
                'phone' => $order->user->phone_number
            ],
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit' => 'minutes',
                'duration' => '10'
            ]
        ];

        try {
            $snapToken = Snap::getSnapToken($params);
            // $url = Snap::CreateTransaction($params)->redirect_url;
            $transaction->save();
            $delayInSeconds = now()->diffInSeconds($transaction->expire_time);
            CheckExpiryJob::dispatch($transaction)->delay(now()->addSeconds($delayInSeconds + 5));

            return response()->json(['snap_token' => $snapToken]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function notification(Request $request)
    {
        $transaction_status = $request->transaction_status;
        $order_id = $request->order_id;
        $transaction = Transaction::where('invoice', $order_id)->first();
        $order = Order::find($transaction->order_id);
        $mitra = Mitra::find($order->acceptedOffer->mitra_id);

        if ($transaction_status == 'settlement') {
            $transaction->status = 'complete';
            $transaction->save();
            $order->status = 'paid';
            $order->save();
            $mitra->saldo += $transaction->transport_cost;
            $mitra->save();
        } else if ($transaction_status == 'pending') {
            $transaction->status = 'pending';
            $transaction->save();
        } else if ($transaction_status == 'deny') {
            $transaction->status = 'failed';
            $transaction->save();
        } else if ($transaction_status == 'expire') {
            $transaction->status = 'failed';
            $transaction->save();
        } else if ($transaction_status == 'cancel') {
            $transaction->status = 'failed';
            $transaction->save();
        }

        return response()->json([
            'message' => 'success',
            'status_code' => 200,
        ], 200);
    }

    public function refundTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => ['required', 'exists:transactions,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 400);
        }

        $transaction = Transaction::find($request->transaction_id);
        
        $transactionStatus = MidtransTransaction::status($transaction->invoice);
        
        $order = Order::find($transaction->order_id);
        $mitra = Mitra::find($order->acceptedOffer->mitra_id);

        $user = $request->user();
        if ($transaction->user_id != $user->id) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($transaction->status != 'complete') {
            return response()->json(['message' => 'Transaction not complete'], 400);
        }

        $refundKey = 'REFUND-' . $transaction->invoice;

        $params = [
            'refund_key' => $refundKey,
        ];

        try {
            $refund = MidtransTransaction::refund($transaction->invoice, $params);
            $transaction->status = 'refunded';
            $transaction->save();
            $order->status = 'cancelled';
            $order->save();
            $mitra->saldo -= $transaction->transport_cost;
            $mitra->save();

            return response()->json([
                'message' => 'Refund Successfully',
                'data' => $refund,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

        // $url = 'https://api.sandbox.midtrans.com/v2/' . $transaction->invoice . '/refund';

        // $response = Http::withBasicAuth(env('MIDTRANS_SERVER_KEY'), '')
        //     ->post($url, [
        //         'refund_key' => $refundKey,
        //     ]);

        // $responseData = $response->json();

        // if ($responseData['status_code'] == '200') {
        //     $transaction->status = 'refunded';
        //     $transaction->save();
        //     $order->status = 'cancelled';
        //     $order->save();

        //     return response()->json([
        //         'message' => 'Refund successful',
        //         'data' => $responseData,
        //     ], 200);
        // } else {
        //     return response()->json([
        //         'message' => 'Refund failed',
        //         'data' => $responseData,
        //     ], 500);
        // }
    }

    public function transactionList(Request $request, $id = null)
    {
        $user = $request->user();
        $statusQuery = $request->query('status');
        $transactions = Transaction::query();

        if ($id != null) {
            $transaction = Transaction::find($id);

            if ($transaction) {
                $order = $transaction->order;
                $priceFormatted = number_format($transaction->total_price, 0, ',', '.');
                return response()->json([
                    'id' => $transaction->id,
                    'invoice' => $transaction->invoice,
                    'transaction_time' => $transaction->transaction_time,
                    'status' => $transaction->status,
                    'category' => $order->category->name,
                    'price' => $priceFormatted,
                ]);
            }

            return response()->json(['message' => 'Transaction not found'], 400);
        }

        if ($user->role == 'user') {
            $transactions = Transaction::where('user_id', $user->id);
        } else if ($user->role == 'mitra') {
            return response()->json(['message' => 'Unauthorize'], 403);
        }

        if ($statusQuery) {
            $transactions = $transactions->where('status', $statusQuery);
        }

        $transactions = $transactions->get();

        if ($transactions->isNotEmpty()) {
            return response()->json(
                $transactions->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'invoice' => $transaction->invoice,
                        'status' => $transaction->status,
                    ];
                })
            );
        }

        return response()->json(['message' => 'No transaction found'], 400);
    }

    public function getWithdraw(Request $request)
    {
        $user = $request->user();
        $mitra = Mitra::where('owner_identifier', $user->identifier)->first();
        $withdraws = WithDraw::where('mitra_id', $mitra->id)->get();

        return response()->json([
            'data' => $withdraws->map(function ($withdraw) {
                return [
                    'id' => $withdraw->id,
                    'amount' => $withdraw->amount,
                    'transaction_time' => $withdraw->transaction_time,
                    'status' => $withdraw->status,
                ];
            }),
        ], 200);
    }

    public function storeWithdraw(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bank_code' => ['required', 'string'],
            'account_number' => ['required', 'string'],
            'amount' => ['required', 'numeric'],
            // 'note' => ['string']
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // $client = new Client();
        // $sandboxKey = config('midtrans.server_key'); // Masukkan Sandbox Server Key dari Midtrans
        // $auth = base64_encode($sandboxKey . ':');

        // $bankCode = $request->bank_code;  // Pastikan dikirim melalui request
        // $accountNumber = $request->account_number;  // Dikirim melalui request
        // $amount = $request->amount;  // Jumlah uang yang akan ditransfer
        // $note = $request->note ? $request->note : 'Tarik Saldo';  // Catatan untuk transaksi

        // try {
        //     $response = $client->post('https://app.sandbox.midtrans.com/iris/api/v1/payouts', [
        //         'headers' => [
        //             'Authorization' => 'Basic ' . $auth,
        //             'Content-Type' => 'application/json'
        //         ],
        //         'json' => [
        //             "payouts" => [
        //                 [
        //                     "beneficiary_name" => "Jon Snow",
        //                     "beneficiary_account" => $accountNumber,
        //                     "beneficiary_bank" => $bankCode,
        //                     "amount" => $amount,
        //                     "notes" => $note
        //                 ],
        //             ],
        //         ],
        //     ]);

        //     $data = json_decode($response->getBody(), true);

        //     if ($response->getStatusCode() == 200 && isset($data['status']) && $data['status'] === 'pending') {
        //         return response()->json($data); // Disbursement berhasil di sandbox
        //     }

        //     return response()->json(['error' => 'Failed to process disbursement'], 500); // Jika gagal
        // } catch (\Exception $e) {;
        //     return response()->json(['error' => $e->getMessage()], 500);
        // }

        $withDraw = new WithDraw();
        $withDraw->amount = $request->amount;
        $withDraw->transaction_time = now();
        $withDraw->mitra_id = $request->user()->id;
        $withDraw->save();

        return response()->json([
           'message' => 'Withdrawal request submitted successfully',
            'data' => $withDraw,
        ], 200);
    }

    public function approveWithdraw ($id)
    {
        $withDraw = WithDraw::find($id);
        if (!$withDraw || $withDraw->status != 'pending') {
            return response()->json(['message' => 'Invalid Withdraw'], 200);
        }

        $withDraw->status = 'complete';
        $withDraw->save();

        return response()->json(['message' => 'Success'], 200);
    }
}
