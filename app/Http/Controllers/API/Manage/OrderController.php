<?php

namespace App\Http\Controllers\API\Manage;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Helper;
use App\Models\Mitra;
use App\Models\Offer;
use App\Models\Order;
use App\Models\OrderAttachment;
use App\Models\Problem;
use App\Models\User;
use App\Models\Rating;
use App\Http\Controllers\API\Manage\NotificationController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Pusher\Pusher;

class OrderController extends Controller
{
    public function storeOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'problem_id' => ['required', 'exists:problems,id'],
            'description' => ['string'],
            'attachments' => ['array', 'max:2'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png', 'max:5000'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 400);
        }

        $user = $request->user();
        $exitingOrder = Order::where('user_id', $user->id)->orderBy('created_at', 'DESC')->first();
        $status = ['pending', 'booked', 'paid', 'otw', 'arrive', 'in_progress'];

        if ($exitingOrder && in_array($exitingOrder->status, $status)) {
            return response()->json(['message' => 'Anda sudah membuat Order yang belum diproses'], 400);
        }

        $problem = Problem::find($request->problem_id);

        $order = new Order();
        $order->latitude = $request->latitude;
        $order->longitude = $request->longitude;
        $order->description = $request->description;
        $order->order_time = now();
        $order->user_id = $request->user()->id;
        $order->problem_id = $problem->id;
        $order->category_id = $problem->helper->category_id;
        $order->save();

        if ($request->has('attachments')) {
            foreach ($request->attachments as $attachment) {
                $imageName = Str::uuid() . '.' . $attachment->getClientOriginalExtension();
                $imagePath = $attachment->storeAs('images/orders/' . $order->id, $imageName, 'public');

                $image = new OrderAttachment();
                $image->image_path = $imagePath;
                $image->order_id = $order->id;
                $image->save();
            }
        }

        $category = Category::where('id', $order->category_id)->first();
        $attachments = OrderAttachment::where('order_id', $order->id)->get();

        $userMitra = Mitra::whereHas('helpers', function ($query) use ($problem) {
            $query->where('mitra_helper.helper_id', $problem->helper_id)->where('is_verified', true); // Menambahkan alias tabel
        })->with('owner.fcmTokens')->get();
        $tokens = $userMitra->flatMap(function ($mitra) {
            return $mitra->owner->fcmTokens()->pluck('fcm_token');
        });

        $responseOrder = [
            'id' => $order->id,
            'category' => $category->name,
            'problem' => $problem->name,
            'latitude' => $order->latitude,
            'longitude' => $order->longitude,
            'description' => $order->description,
            'attachments' => $attachments->isEmpty() ? null : $attachments->map(function ($attachment) {
                return asset($attachment->image_path);
            })
        ];

        if (!$tokens->isEmpty()) {
            $notificationController = new NotificationController();
            $notificationController->sendNotification($tokens->toArray(), 'New Order', 'Ini ada Order Baru nich', $responseOrder);
        }

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $responseOrder,
        ], 201);
    }

    public function offerOrder(Request $request, $orderId)
    {
        $validator = Validator::make($request->all(), [
            'price' => ['required', 'integer'],
            'estimated_time' => ['required', 'integer'],  // Ubah validasi untuk jam:menit
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 400);
        }

        $order = Order::find($orderId);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 400);
        }

        $mitra = Mitra::where('owner_identifier', $request->user()->identifier)->first();
        if (!$mitra) {
            return response()->json(['message' => 'Account Anda belum memiliki Mitra'], 403); // Tambahkan pengecekan ini
        } else if (!$mitra->is_verified) {
            return response()->json(['message' => 'Mitra anda belum terverifikasi'], 403);
        }

        $orderHelperId = $order->problem->helper_id;
        if (!$mitra->helpers()->where('helpers.id', $orderHelperId)->exists()) {
            return response()->json(['message' => 'Mitra anda tidak memiliki helper yang sama dengan order ini'], 400);
        }

        $exitingOffer = Offer::where('mitra_id', $mitra->id)->where('order_id', $order->id)->first();
        if ($exitingOffer) {
            return response()->json(['message' => 'Anda sudah menawarkan harga pada order ini'], 400);
        }

        $transportCost = $request->price;
        // $platformFee = $transportCost * 0.3;
        $markupCost = 4000;
        // $totalPrice = $transportCost + $platformFee + $markupCost;
        $totalPrice = $transportCost + $markupCost;

        // Buat penawaran baru
        $newOffer = new Offer();
        $newOffer->order_id = $order->id;
        $newOffer->mitra_id = $mitra->id;
        $newOffer->transport_cost = $transportCost;
        // $newOffer->platform_fee = $platformFee;
        $newOffer->markup_cost = $markupCost;
        $newOffer->total_price = $totalPrice;
        $newOffer->estimated_time = $request->estimated_time;
        $newOffer->save();

        $numberFormatted = number_format($newOffer->total_price, 0, ',', '.');

        $offer = [
            'order_id' => $order->id,
            'offer_id' => $newOffer->id,
            'mitra_id' => $newOffer->mitra->id,
            'mitra_name' => $newOffer->mitra->name,
            'price' => $numberFormatted,
            'estimated_time' => $newOffer->estimated_time,
            'latitude' => $newOffer->mitra->latitude,
            'longitude' => $newOffer->mitra->longitude,
        ];

        $notificationController = new NotificationController();
        $notificationController->sendNotification($order->user->fcmTokens()->pluck('fcm_token')->toArray(), 'New Offer', 'Ini ada Offer Baru nich buat Orderan kamu', $offer);

        return response()->json([
            'message' => 'Offer has been sent.',
            'offer' => $offer
        ], status: 201);
    }

    public function offerList(Request $request, $orderId)
    {
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 400);
        }

        $user = $request->user();

        if ($user->id != $order->user_id) {
            return response()->json([
                'message' => 'You are not authorized to view this order'
            ], 403);
        }

        // Memuat tawaran dengan relasi mitra dan owner
        $offers = Offer::where('order_id', $orderId)
            ->with(['mitra.owner']) // Memuat relasi mitra dan owner
            ->get();


        if ($offers->isEmpty()) {
            return response()->json([
                'message' => 'No Offer was found'
            ], 200);
        }

        return response()->json([
            'data' => $offers->map(function ($offer) {
                $numberFormatted = number_format($offer->total_price, 0, ',', '.');
                $mitraOwner = $offer->mitra->owner;
                $mitraProfile = asset($mitraOwner->image_profile);
                return [
                    'order_id' => $offer->order_id,
                    'offer_id' => $offer->id,
                    'mitra_id' => $offer->mitra->id,
                    'mitra_name' => $offer->mitra->name,
                    'mitra_profile' => $mitraProfile,
                    'price' => $numberFormatted,
                    'estimated_time' => $offer->estimated_time,
                    'latitude' => $offer->mitra->latitude,
                    'longitude' => $offer->mitra->longitude
                ];
            })
        ], 200);
    }

    public function selectMitra(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'offer_id' => ['required', 'exists:offers,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 400);
        }

        $offer = Offer::find($request->offer_id);
        $order = Order::find($offer->order->id);

        if ($order->status != 'pending') {
            return response()->json(['message' => 'Order yang dipilih tidak valid'], 400);
        }

        if ($order->offer_id) {
            return response()->json(['message' => 'Mitra sudah dipilih sebelumnya'], 400);
        }

        $user = $request->user();
        if ($user->id != $order->user_id) {
            return response()->json(['message' => 'Unauthorize'], 403);
        }

        $order->offer_id = $offer->id;
        $order->status = 'booked';
        $order->save();

        $numberFormatted = number_format($order->acceptedOffer->total_price, 0, ',', '.');
        $mitraTerpilih = $order->acceptedOffer->mitra;
        $userMitra = User::where('identifier', $mitraTerpilih->owner_identifier)->first();
        $offerList = Offer::where('order_id', $order->id)->where('id', '!=', $offer->id)->with('mitra.owner')->get();

        $notification = new NotificationController();
        $notification->sendNotification($userMitra->fcmTokens()->pluck('fcm_token')->toArray(), 'Offer', 'Penawaran kamu telah dipilih');

        if (!$offerList->isEmpty()) {
            foreach($offerList as $offer) {
                $userMitra = $offer->mitra->owner;
                $notification->sendNotification($userMitra->fcmTokens()->pluck('fcm_token')->toArray(), 'Offer', 'Sayang Sekali Kamu Tidak Dipilih');
            }
        }

        return response()->json([
            'message' => 'Mitra berhasil dipilih',
            'order' => [
                'order_id' => $order->id,
                'price' => $numberFormatted,
                'latitude' => $mitraTerpilih->latitude,
                'longitude' => $mitraTerpilih->longitude,
                'mitra' => $mitraTerpilih->name,
                'mitra_profile' => asset($userMitra->image_profile),
            ],
        ], 200);
    }

    public function orderList(Request $request, $id = null)
    {
        $user = $request->user();
        $statusQuery = $request->query('status');
        $statsQuery = $request->query('stats');
        $query = Order::query();

        // Cek apakah pengguna adalah admin jika ada query stats
        if ($statsQuery && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Cek role pengguna
        if ($user->role == 'client') {
            $query->where('user_id', $user->id);
        }

        if ($user->role == 'mitra') {
            $mitra = Mitra::where('owner_identifier', $user->identifier)->first();

            if (!$mitra) {
                return response()->json([
                    'message' => "You are Mitra, but you don't have a Mitra yet"
                ], 400);
            }

            // Ambil semua helper_id yang berhubungan dengan mitra
            $helperIds = $mitra->helpers()->pluck('id')->toArray();

            $query->whereHas('problem', function ($q) use ($helperIds) {
                $q->whereIn('helper_id', $helperIds);
            });
        }

        // Cek jika ada id untuk mendapatkan order tertentu
        if ($id != null) {
            $order = Order::find($id);

            if (!$order) {
                return response()->json(['message' => 'order not found'], 400);
            }

            if ($user->role == 'client') {
                if ($order->user_id != $user->id) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
            }

            if ($user->role == 'mitra') {
                $sameHelper = $mitra->helpers()->where('id', $order->problem->helper_id)->exists();
                if (!$sameHelper) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
            }

            $attachments = OrderAttachment::where('order_id', $order->id)->get();
            $response = [
                'order_id' => $order->id,
                'order_status' => $order->status,
                'order_time' => $order->order_time,
                'user' => $order->user->username,
                'description' => $order->description,
                'latitude' => $order->latitude,
                'longitude' => $order->longitude,
                'price' => '0',
                'problem' => $order->problem->name,
                'is_rated' => $order->status == 'rated' ? true : false,
                'attachments' => $attachments->map(function ($attachment) {
                    $imageUrl = asset($attachment->image_path);
                    return $imageUrl;
                }),
            ];
            if ($order->offer_id != null) {
                $response['price'] = number_format($order->acceptedOffer->total_price, 0, '.', '.');
                $response['mitra'] = $order->acceptedOffer->mitra->name;
                $response['mitra_profile'] = asset($order->acceptedOffer->mitra->owner->image_profile);
                $response['phone_number_mitra'] = $order->acceptedOffer->mitra->owner->phone_number;
            } else {
                $response['mitra'] = null;
                $response['mitra_profile'] = null;
                $response['phone_number_mitra'] = null;
            }

            return response()->json($response, 200);
        }

        if ($statusQuery) {
            if ($statusQuery == 'complete') {
                $query->whereIn('status', ['complete', 'rated']);
            } else {
                $query->where('status', $statusQuery);
            }
        }

        // Jika ada query stats, kita jalankan logika untuk statistik
        if ($statsQuery) {
            switch ($statsQuery) {
                case 'hourly':
                    $date = $request->date;
                    if (!$date) {
                        return response()->json(['message' => 'Date is required']);
                    }

                    // Validasi date
                    if (!$date || !Carbon::createFromFormat('Y-m-d', $date)) {
                        return response()->json(['message' => 'Invalid date format. Use Y-m-d.'], 400);
                    }

                    $orders = $query->whereDate('order_time', $date)
                        ->selectRaw('HOUR(order_time) as hour, COUNT(*) as count')
                        ->groupBy('hour')
                        ->get();

                    $result = [];
                    for ($i = 0; $i < 24; $i++) {
                        $result[$i] = ['period' => 'Jam ' . $i, 'count' => 0];
                    }

                    foreach ($orders as $order) {
                        $result[$order['hour']]['count'] += $order['count'];
                    }

                    return response()->json($result, 200);

                case 'daily':
                    $startDate = $request->start_date;
                    $endDate = $request->end_date;

                    if (!$startDate || !$endDate) {
                        return response()->json(['message' => 'Start Date and End Date is required']);
                    }

                    // Validasi start_date dan end_date
                    if (!$startDate || !$endDate || !Carbon::createFromFormat('Y-m-d', $startDate) || !Carbon::createFromFormat('Y-m-d', $endDate)) {
                        return response()->json(['message' => 'Invalid date format. Use Y-m-d.'], 400);
                    }

                    if ($startDate > $endDate) {
                        return response()->json(['message' => 'Start date must be less than or equal to end date.'], 400);
                    }

                    // Ambil data order dalam rentang tanggal
                    $orders = $query->whereBetween('order_time', [$startDate, $endDate])
                        ->selectRaw('DATE(order_time) as date, COUNT(*) as count')
                        ->groupBy('date')
                        ->get();

                    // Menghasilkan array untuk menyimpan hasil dari startDate ke endDate
                    $dateRange = [];
                    $period = Carbon::parse($startDate)->daysUntil($endDate);

                    foreach ($period as $date) {
                        $dateString = $date->toDateString();
                        $count = $orders->firstWhere('date', $dateString)?->count ?? 0; // Ambil count atau 0 jika tidak ada
                        $dateRange[] = ['period' => $dateString, 'count' => $count];
                    }

                    return response()->json($dateRange, 200);

                case 'monthly':
                    $year = $request->year;

                    if (!$year) {
                        return response()->json(['message' => 'Year id required']);
                    }

                    // Validasi year
                    if (!$year || !is_numeric($year) || $year < 2000 || $year > date('Y')) {
                        return response()->json(['message' => 'Invalid year.'], 400);
                    }

                    $orders = $query->whereYear('order_time', $year)
                        ->selectRaw('MONTH(order_time) as month, COUNT(*) as count')
                        ->groupBy('month')
                        ->get();

                    $result = array_fill(1, 12, ['period' => '', 'count' => 0]);
                    foreach ($orders as $order) {
                        // Pastikan bulan disimpan dengan benar di index array
                        $months = [
                            1 => 'January',
                            2 => 'February',
                            3 => 'March',
                            4 => 'April',
                            5 => 'May',
                            6 => 'June',
                            7 => 'July',
                            8 => 'August',
                            9 => 'September',
                            10 => 'October',
                            11 => 'November',
                            12 => 'December',
                        ];
                        $result[$order->month]['period'] = $months[$order->month];
                        $result[$order->month]['count'] = $order->count;
                    }

                    return response()->json(array_values($result), 200);

                case 'yearly':
                    $startYear = $request->start_year;
                    $endYear = $request->end_year;

                    if (!$startYear || !$endYear) {
                        return response()->json(['message' => 'Start year and End year is Required']);
                    }

                    // Validasi start_year dan end_year
                    if (!$startYear || !$endYear || !is_numeric($startYear) || !is_numeric($endYear)) {
                        return response()->json(['message' => 'Invalid year format.'], 400);
                    }

                    if ($startYear > $endYear) {
                        return response()->json(['message' => 'Start year must be less than or equal to end year.'], 400);
                    }

                    $orders = $query->whereBetween(DB::raw('YEAR(order_time)'), [$startYear, $endYear])
                        ->selectRaw('YEAR(order_time) as year, COUNT(*) as count')
                        ->groupBy('year')
                        ->get();

                    $result = [];
                    for ($year = $startYear; $year <= $endYear; $year++) {
                        $result[] = ['period' => $year, 'count' => 0];
                    }

                    foreach ($orders as $order) {
                        $index = $order->year - $startYear;
                        $result[$index]['count'] = $order->count;
                    }

                    return response()->json($result, 200);

                default:
                    return response()->json(['message' => 'Invalid stats query'], 400);
            }
        }

        // Fetching orders without statistics
        $orders = $query->with(['user', 'category'])->get();

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'No Order Found'], 200);
        }

        return response()->json(
            $orders->map(function ($order) {
                $attachments = OrderAttachment::where('order_id', $order->id)->get();
                $price = $order->offer_id != null ?  number_format($order->acceptedOffer->total_price, 0, '.', '.') : '0';
                return [
                    'order_id' => $order->id,
                    'order_status' => $order->status,
                    'latitude' => $order->latitude,
                    'longitude' => $order->longitude,
                    'description' => $order->description,
                    'order_time' => $order->order_time,
                    'user' => $order->user->username,
                    'user_profile' => asset($order->user->image_profile),
                    'price' => $price,
                    'problem' => $order->problem->name,
                    'is_rated' => $order->status == 'rated' ? true : false,
                    'attachment' => $attachments->map(function ($attachment) {
                        $imageUrl = asset($attachment->image_path);
                        return $imageUrl;
                    }),
                ];
            }),
            200
        );
    }

    public function updateStatus(Request $request, $id)
    {
        $order = Order::find($id);
        $user = $request->user();

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 400);
        }

        $mitra = Mitra::find($order->acceptedOffer->mitra_id);

        if (!$mitra || $mitra->owner_identifier != $user->identifier) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $status = $request->status;
        $validStatuses = ['pending', 'otw', 'arrived', 'in_progress', 'complete'];

        if (!in_array($status, $validStatuses)) {
            return response()->json(['error' => 'Invalid status'], 400);
        }

        // Cek apakah status baru sesuai urutan dan tidak melompat
        $currentStatusIndex = array_search($order->status, $validStatuses);
        $newStatusIndex = array_search($status, $validStatuses);

        // Pastikan status baru tepat satu level di atas status saat ini
        if ($newStatusIndex != $currentStatusIndex + 1) {
            return response()->json(['error' => 'Status must be updated sequentially'], 400);
        }

        $userOrder = User::find($order->user_id);
        $notification = new NotificationController();
        $notification->sendNotification($userOrder->fcmTokens()->pluck('fcm_token')->toArray(), 'order', 'status updated', ['status' => $status]);

        // Update status di database
        $order->status = $status;
        $order->save();

        return response()->json(['message' => 'Status updated successfully']);
    }
}
