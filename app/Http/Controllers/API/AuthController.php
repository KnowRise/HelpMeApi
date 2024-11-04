<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\FcmToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $requiremenns = [
            'full_name' => ['required', 'string'],
            'username' => ['required', 'string', 'unique:users,username', 'min:2', 'alpha_dash'],
            'phone_number' => ['required', 'numeric', 'unique:users,phone_number'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
            'role' => ['required', 'string', 'in:client,mitra'],
            'fcm_token' => ['required', 'string',]
        ];

        if ($request->hasFile('image_profile')) {
            $requiremenns['image_profile'] = ['file', 'mimes:jpg,png,jpeg', 'max:5120'];
        }

        $validator = Validator::make($request->all(), $requiremenns);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = new User();
        $user->full_name = $request->full_name;
        $user->username = $request->username;
        $user->phone_number = $request->phone_number;
        $user->password = bcrypt($request->password);
        $user->role = $request->role;

        if ($request->hasFile('image_profile')) {
            $image = $request->file('image_profile');
            $imageName = Str::uuid() . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('profile/' . $request->username, $imageName, 'public');
            $user->image_profile = $imagePath;
        } else {
            $user->image_profile = 'profiles/default.jpeg';
        }

        $user->save();

        $fcm = new FcmToken();
        $fcm->fcm_token = $request->fcm_token;
        $fcm->user_id = $user->id;
        $fcm->save();

        $rolePrefix = $request->role == 'client' ? 'CLNT' : 'MIT';
        $date = Carbon::now();
        $year = $date->format('y');
        $month = $date->format('m');
        $day = $date->format('d');
        $idFormatted = str_pad($user->id, 4, '0', STR_PAD_LEFT);

        $identifier = $rolePrefix . $year . $month . $day . $idFormatted;

        $user->identifier = $identifier;
        $user->save();

        $token = $user->createToken($user->username)->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $appTypeQuery = $request->query('app_type');

        if (!$appTypeQuery) {
            return response()->json(['message' => 'app_type is required'], 400);
        }

        $appTypeList = ['client', 'admin', 'mitra'];

        if (!in_array($appTypeQuery, $appTypeList)) {
            return response()->json(['message' => 'Invalid App Type'], 400);
        }

        if (auth()->attempt(['username' => $request->username, 'password' => $request->password])) {
            $user = $request->user();

            if ($appTypeQuery === 'admin' && $user->role !== 'admin') {
                return response()->json(['message' => 'Unauthorized: Only admins can access this app'], 403);
            }

            if ($appTypeQuery === 'client' && $user->role !== 'client') {
                return response()->json(['message' => 'Unauthorized: Only users can access this app'], 403);
            }

            if ($appTypeQuery === 'mitra' && $user->role !== 'mitra') {
                return response()->json(['message' => 'Unauthorized: Only mitra can access this app'], 403);
            }


            if ($user->is_active) {
                if ($appTypeQuery == 'admin') {
                    $token = $user->createToken($user->username)->plainTextToken;
                    return response()->json([
                        'message' => 'User logged in successfully',
                        'token' => $token,
                    ], 200);
                } else {
                    $fcmToken = $request->fcm_token;
                    if (!$fcmToken) {
                        return response()->json(['message' => 'fcm token is required'], 400);
                    }

                    $exitingFcmToken = FcmToken::where('fcm_token', $fcmToken)->where('user_id', $user->id)->first();
                    if (!$exitingFcmToken) {
                        if ($fcmToken) {
                            $fcm = new  FcmToken();
                            $fcm->user_id = $user->id;
                            $fcm->fcm_token = $fcmToken;
                            $fcm->save();
                        }
                        $token = $user->createToken($user->username)->plainTextToken;

                        return response()->json([
                            'message' => 'User logged in successfully',
                            'token' => $token,
                        ], 200);
                    } else {
                        $token = $user->createToken($user->username)->plainTextToken;
                        return response()->json([
                            'message' => 'User logged in successfully',
                            'token' => $token,
                        ], 200);
                    }
                }
            }

            return response()->json(['message' => 'Your account is inactive. Please contact support.'], 403);
        }

        // Jika autentikasi gagal
        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    public function aboutMe(Request $request)
    {
        $user = $request->user();
        $imageUrl = asset($user->image_profile);

        $response = [
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'phone_number' => $user->phone_number,
                'username' => $user->username,
                'role' => $user->role,
                'image_profile' => $imageUrl,
                'phone_number_verified_at' => $user->phone_number_verified_at
            ]
        ];


        if ($user->role == 'mitra') {
            $response['user']['saldo'] = $user->mitra->saldo ?? 0;
            dd($response);
        }


        return response()->json($response, 200);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'confirmed', 'min:8'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = $request->user();
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['error' => 'Current password is incorrect'], 400);
        }

        $user->password = bcrypt($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password changed successfully'], 200);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $fcmToken = $request->fcm_token;

        if ($user->role == 'admin') {
            $user->currentAccessToken()->delete();
            return response()->json(['message' => 'User logged out successfully'], 200);
        } else {
            if (!$fcmToken) {
                return response()->json(['message' => 'fcm token field is required'], 200);
            }

            $userFcmToken = $user->fcmTokens()->pluck('fcm_token')->toArray();
            if (!in_array($fcmToken, $userFcmToken)) {
                return response()->json(['message' => 'Invalid fcm token']);
            }

            FcmToken::where('fcm_token', $fcmToken)->delete();
            $user->currentAccessToken()->delete();
            return response()->json(['message' => 'User logged out successfully'], 200);
        }
    }

    public function editProfile(Request $request)
    {
        $user = $request->user();

        $requiremens = [
            'full_name' => ['required', 'string'],
            'username' => ['required', 'string', 'unique:users,username,' . $user->id, 'min:2'],
            'phone_number' => ['required', 'numeric'],
        ];

        if ($request->hasFile('image_profile')) {
            $requiremens['image_profile'] = ['file', 'mimes:jpg,png,jpeg', 'max:5120'];
        }

        $validator = Validator::make($request->all(), $requiremens);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user->username = $request->username;
        $userWithPhoneNumberSame = User::where('phone_number', $request->phone_number)
            ->where('id', '!=', $user->id)
            ->first();

        if ($userWithPhoneNumberSame) {
            return response()->json(['error' => 'Phone number already registered'], 400);
        }

        if ($request->phone_number  != $user->phone_number) {
            $user->phone_number = $request->phone_number;
            $user->phone_number_verified_at = null;
        }

        if ($request->hasFile('image_profile')) {
            $image = $request->file('image_profile');
            $imageName = Str::uuid() . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('profiles/' . $user->username, $imageName, 'public');
            $user->image_profile = $imagePath;
        }

        $user->full_name = $request->full_name;
        $user->save();
        $imageUrl = asset($user->image_profile);
        $user->makeHidden(['created_at', 'updated_at', 'password']);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'phone_number' => $user->phone_number,
                'username' => $user->username,
                'role' => $user->role,
                'image_profile' => $imageUrl
            ]
        ], 200);
    }

    public function sendResetLink(Request $request)
    {
        // Validasi nomor telepon
        $validator = Validator::make($request->all(), [
            'phone_number' => ['required', 'numeric', 'exists:users,phone_number'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], status: 422);
        }

        $user = User::where('phone_number', $request->phone_number)->first();
        if ($user->phone_number_verified_at == null) {
            return response()->json(['message' => 'Nomor telepon belum diverifikasi'], status: 422);
        }

        // Generate token reset password
        $token = Str::random(60);

        // Simpan token di cache (expire setelah 5 menit)
        $phoneNumber = $request->phone_number;
        Cache::put('password_reset_token_' . $phoneNumber, $token, 3000);

        // Buat URL untuk reset password
        $url = 'https://fb61c4ace7a0573d2d1b4a511b4247a2.serveo.net/password-reset/' . $token . '?phone_number=' . $phoneNumber;

        // Kirim SMS dengan link reset password
        $response = Http::withHeaders([
            'Authorization' => env('FONNTE_TOKEN'), // ganti dengan TOKEN Fonnte kamu
        ])->post('https://api.fonnte.com/send', [
            'target' => $phoneNumber,
            'message' => 'Link Untuk Reset Password: ' . $url,
            'countryCode' => '62', // kode negara Indonesia
        ]);

        if ($response->successful()) {
            return response()->json(['message' => 'Link reset password telah dikirim ke nomor ' . $phoneNumber], 200);
        } else {
            return response()->json(['error' => 'Gagal mengirim link reset password'], 500);
        }
    }

    public function reset(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'phone_number' => ['required', 'numeric', 'exists:users,phone_number'],
            'password' => ['required', 'min:8', 'confirmed'],
            'token' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Ambil token dari cache
        $phoneNumber = $request->phone_number;
        $cachedToken = Cache::get('password_reset_token_' . $phoneNumber);

        // Cek apakah token sesuai
        if ($cachedToken && $cachedToken == $request->token) {
            // Jika verifikasi berhasil, hapus token dari cache
            Cache::forget('password_reset_token_' . $phoneNumber);

            // Proses reset password
            $user = User::where('phone_number', $phoneNumber)->first();
            $user->password = bcrypt($request->password); // Hashing password baru
            $user->save();

            Auth::logout(); // Logout pengguna setelah reset

            return response()->json(['message' => 'Password reset successful'], 200);
        } else {
            return response()->json(['error' => 'Token tidak valid atau telah kadaluwarsa'], 400);
        }
    }

    public function sendVerificationCode(Request $request)
    {
        // Validasi input nomor telepon
        $validator = Validator::make($request->all(), [
            'phone_number' => ['required', 'exists:users,phone_number'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('phone_number', $request->phone_number)->first();
        if ($user && $user->phone_number_verified_at != null) {
            return response()->json(['message' => 'Nomor telepon sudah diverifikasi'], 400);
        }

        // Generate kode verifikasi (4-6 digit angka)
        $verificationCode = rand(1000, 9999);

        // Simpan kode verifikasi ke cache atau database (dengan expiry time 5 menit)
        $phoneNumber = $request->phone_number;
        Cache::put('verification_code_' . $phoneNumber, $verificationCode, 300); // simpan 5 menit

        $response = Http::withHeaders([
            'Authorization' => env('FONNTE_TOKEN'), // ganti dengan TOKEN Fonnte kamu
        ])->post('https://api.fonnte.com/send', [
            'target' => $phoneNumber,
            'message' => 'Kode verifikasi kamu adalah: ' . $verificationCode,
            'countryCode' => '62', // kode negara Indonesia
        ]);

        if ($response->successful()) {
            return response()->json(['message' => 'Kode verifikasi telah dikirim ke nomor ' . $phoneNumber], 200);
        } else {
            return response()->json(['error' => 'Gagal mengirim kode verifikasi'], 500);
        }
    }

    public function verifyCode(Request $request)
    {
        // Validasi input nomor telepon dan kode verifikasi
        $validator = Validator::make($request->all(), [
            'phone_number' => ['required', 'exists:users,phone_number'],
            'verification_code' => ['required', 'numeric', 'digits:4'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Ambil kode verifikasi yang tersimpan
        $phoneNumber = $request->phone_number;
        $cachedCode = Cache::get('verification_code_' . $phoneNumber);
        $user = User::where('phone_number', $phoneNumber)->first();

        // Cek apakah kode verifikasi sesuai
        if ($cachedCode && $cachedCode == $request->verification_code) {
            // Jika verifikasi berhasil, hapus kode dari cache
            Cache::forget('verification_code_' . $phoneNumber);
            $user->phone_number_verified_at = now();
            $user->save();

            return response()->json(['message' => 'Nomor telepon berhasil diverifikasi'], 200);
        } else {
            return response()->json(['error' => 'Kode verifikasi salah atau telah kadaluwarsa'], 400);
        }
    }
}
