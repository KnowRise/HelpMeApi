<?php

namespace App\Http\Controllers\API\Manage;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use App\Models\Order;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RatingController extends Controller
{
    public function storeRating(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => ['required', 'exists:orders,id'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'review' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 400);
        }

        $order = Order::find($request->order_id);

        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($order->status != 'complete') {
            return response()->json(['message' => 'Order belum selesai, tidak dapat memberikan rating'], 400);
        }

        if ($order->status == 'rated') {
            return response()->json(['message' => 'Anda sudah memberikan rating untuk order ini'], 400);
        }

        $rating = new Rating();
        $rating->rating = $request->rating;
        $rating->review = $request->review;
        $rating->mitra_id = $order->acceptedOffer->mitra_id;
        $rating->user_id = $order->user_id;
        $rating->order_id = $order->id;
        $rating->save();

        $order->status = 'rated';
        $order->save();

        return response()->json([
            'message' => 'Rating berhasil diberikan',
            'rating' => $rating,
        ], 201);
    }

    public function getMitraRatings(Request $request, $mitraId)
    {
        $ratings = Rating::where('mitra_id', $mitraId)->get();
        $average = Rating::where('mitra_id', $mitraId)->avg('rating');

        return response()->json([
            'average' => round($average, 1),
            'ratings' => $ratings->makeHidden(['created_at', 'updated_at']),
        ]);
    }
}
