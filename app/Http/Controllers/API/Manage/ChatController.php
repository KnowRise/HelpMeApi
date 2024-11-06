<?php

namespace App\Http\Controllers\API\Manage;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Mitra;
use App\Models\Order;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Pusher\Pusher;

class ChatController extends Controller
{
    public function createOrGetChat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => ['required', 'exists:orders,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 400);
        }

        $order = Order::find($request->order_id);
        if ($order->status == 'cancelled' || $order->status == 'complete') {
            return response()->json(['message' => "The order is not valid"], 400);
        }

        $chat = Chat::where('order_id', $order->id)->first();
        $code_room = Str::uuid()->toString();

        if (!$chat) {
            $chat = new Chat();
            $chat->client_id = $order->user_id;
            $chat->mitra_id = $order->acceptedOffer->mitra->owner->id;
            $chat->order_id = $order->id;
            $chat->code_room = $code_room;
            $chat->save();
        }

        // $chatId = rand

        return response()->json($chat, 200);
    }

    public function sendMessage(Request $request)
    {
        $user = $request->user();
        // Validasi kustom untuk memastikan hanya satu di antara message atau attachment yang bisa diisi
        $validator = Validator::make($request->all(), [
            'code_room' => ['required', 'exists:chats,code_room'],
            'message' => ['nullable', 'string', 'required_without:attachment'],  // Jika attachment tidak diisi, message harus ada
            'attachment' => ['nullable', 'file', 'mimes:jpg,png,jpeg', 'max:5000', 'required_without:message'],  // Jika message tidak diisi, attachment harus ada
        ]);

        // Tambahkan validasi bahwa hanya satu yang bisa diisi, message atau attachment
        $validator->after(function ($validator) use ($request) {
            if ($request->filled('message') && $request->hasFile('attachment')) {
                $validator->errors()->add('message', 'Only one of message or attachment should be provided.');
                $validator->errors()->add('attachment', 'Only one of message or attachment should be provided.');
            }
        });

        $chat = Chat::where('code_room', $request->code_room)->first();

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 400);
        }

        if (!$chat) {
            return response()->json(['message' => 'chat not found'], 400);
        }

        if ($user->role == 'client') {
            if ($user->id != $chat->client_id) {
                return response()->json(['message' => 'Unauthorize'], 403);
            }
        } else if ($user->role == 'mitra') {
            if ($user->id != $chat->mitra_id) {
                return response()->json(['message' => 'Unauthorize'], 403);
            }
        }

        // Simpan pesan
        $message = new Message();
        $message->chat_id = $chat->id;
        $message->sender_id = $request->user()->id;

        // Jika message diisi, simpan message dan set attachment ke null
        if ($request->filled('message')) {
            $message->message = $request->message;
            $message->attachment = null;
        }

        // Jika attachment diisi, simpan attachment dan set message ke null
        if ($request->hasFile('attachment')) {
            $attachmentName = Str::uuid() . '.' . $request->attachment->getClientOriginalExtension();
            $path = $request->file('attachment')->storeAs('images/chats/' . $chat->id, $attachmentName, 'public');
            $message->attachment = $path;
            $message->message = null;  // Set message ke null jika attachment diisi
        }

        $message->save();

        $imgaeUrl = $message->attachment ? asset($message->attachment) : null;

        $messageData = [
            'sender_id' => $message->sender_id,
            'message' => $message->message == null ? $imgaeUrl : $message->message,
            'created_at' => $message->created_at->format('Y-m-d H:i:s'),
        ];

        $notification = new NotificationController();
        $receiverUser = $user->role == 'client' ? $chat->userAsMitra : $chat->userAsClient;
        $notification->sendNotification($receiverUser->fcmTokens()->pluck('fcm_token')->toArray(), 'chat', 'new message', $messageData);

        return response()->json([
            'message' => 'Pesan berhasil dikirim',
            'data' => $messageData,
        ], 200);
    }

    public function getMessages(Request $request, $code_room)
    {
        $chat = Chat::where('code_room', $code_room)->first();
        $user = $request->user();

        if (!$chat) {
            return response()->json(['message' => 'Chat not found'], 400);
        }

        $order = Order::find($chat->order_id);
        if ($order->status == 'cancelled' || $order->status == 'complete') {
            return response()->json(['message' => "The order is not valid"], 400);
        }

        if ($user->role == 'client') {
            if ($user->id != $order->user_id) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
        }

        if ($user->role == 'mitra') {
            $mitra = Mitra::where('owner_identifier', $user->identifier)->first();
            if (!$mitra || $mitra->id != $order->acceptedOffer->mitra_id) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
        }

        $messages = Message::where('chat_id', $chat->id)->orderBy('created_at', 'asc')->get();
        // $messages = Message::where('chat_id', $chat->id)->get();

        return response()->json(
            $messages->map(function ($message) {
                $imgaeUrl = $message->attachment ? asset($message->attachment) : null;
                return [
                    'sender_id' => $message->sender_id,
                    'message' => $message->message == null ? $imgaeUrl : $message->message,
                    'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            200
        );
    }
}
