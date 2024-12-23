<?php

namespace App\Http\Controllers\API\Manage;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function createAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => ['required', 'string'],
            'username' => ['required', 'string', 'unique:users,username', 'min:2'],
            'phone_number' => ['required', 'numeric', 'unique:users,phone_number'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = new User();
        $user->full_name = $request->full_name;
        $user->username = $request->username;
        $user->phone_number = $request->phone_number;
        $user->password = bcrypt($request->password);
        $user->role = $request->role;
        $user->image_profile = 'images/profiles/default.jpeg';
        $user->save();

        $rolePrefix = 'ADM';
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

    public function deleteAdmin(Request $request)
    {
        $users = User::where('role', 'admin')->count();
        if ($users <= 1) {
            return response()->json([
                'message' => 'Cannot delete the last admin'
            ], 403);
        }

        $user = $request->user();
        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }

    public function toggleAccountStatus($id)
    {
        $user = User::find($id);

        if ($user) {
            $user->is_active = !$user->is_active;
            $user->save();

            $status = $user->is_active ? 'active' : 'inactive';

            $notification = new NotificationController();
            $notification->sendNotification($user->fcmTokens()->pluck('fcm_token')->toArray(), 'status skun', 'status akun anda telah diubah menjadi ' . $status);

            return response()->json([
                'message' => 'Status account berhasil diubah'
            ], 200);
        }

        return response()->json([
            'message' => 'Data user not found'
        ], 400);
    }

    public function VerifyMitra($id)
    {
        $mitra = Mitra::with('owner')->find($id);

        if ($mitra) {
            if ($mitra->is_verified) {
                return response()->json(['message' => 'Mitra sudah di verifikasi'], 400);
            }

            $mitra->is_verified = !$mitra->is_verified;
            $mitra->save();

            $notification = new NotificationController();
            $notification->sendNotification($mitra->owner->fcmTokens()->pluck('fcm_token')->toArray(), 'status mitra', 'mitra anda telah terverifikasi');

            return response()->json([
                'message' => 'Status verifikasi mitra berhasil diubah'
            ], 200);
        }

        return response()->json([
            'message' => 'Mitra not found'
        ], 400);
    }

    public function getUsers(Request $request)
    {
        $statsQuery = $request->query('stats');

        if ($statsQuery) {
            return $this->handleStatsQuery($request, $statsQuery);
        }

        return $this->handleFilterQuery($request);
    }

    private function handleFilterQuery(Request $request)
    {
        $roleQuery = $request->query('role');
        $statusQuery = $request->query('status');

        $query = User::query()->where('role', '!=', 'admin');

        if ($roleQuery) {
            if ($roleQuery == 'admin') {
                return response()->json(['message' => 'Data user not found'], 400);
            }
            $query->where('role', $roleQuery);
        }

        if ($statusQuery) {
            $statusValid = ['active', 'inactive'];
            if (!in_array($statusQuery, $statusValid)) {
                return response()->json(['message' => 'Invalid status query'], 400);
            }
            $status = ($statusQuery == 'active') ? true : false;
            $query->where('is_active', $status);
        }

        $users = $query->get()->makeHidden(['created_at', 'updated_at', 'image_profile']);

        if ($users->isEmpty()) {
            return response()->json(['message' => 'Data user not found'], 400);
        }

        return response()->json($users, 200);
    }

    private function handleStatsQuery(Request $request, $statsQuery)
    {
        if ($statsQuery == 'client-mitra') {
            $userCount = User::where('role', 'client')->count();
            $mitraCount = User::where('role', 'mitra')->count();
            return response()->json([
                'all' => $userCount + $mitraCount,
                'client_count' => $userCount,
                'mitra_count' => $mitraCount,
            ], 200);
        }

        if ($statsQuery == 'granularity') {
            return $this->handleGranularityStats($request);
        }

        return response()->json(['message' => 'Invalid stats query'], 400);
    }

    public function handleGranularityStats(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'granularity' => ['required', 'in:monthly,yearly'],
            'year' => ['required_if:granularity,monthly', 'integer'],
            'start_year' => ['required_if:granularity,yearly', 'integer'],
            'end_year' => ['required_if:granularity,yearly', 'integer', 'gte:start_year'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 400);
        }

        $granularity = $request->query('granularity');

        if ($granularity === 'monthly') {
            return $this->getMonthlyStats($request->query('year'));
        }

        if ($granularity === 'yearly') {
            return $this->getYearlyStats(
                $request->query('start_year'),
                $request->query('end_year')
            );
        }

        return response()->json(['message' => 'Invalid granularity query'], 400);
    }

    private function getMonthlyStats($year)
    {
        $statsQuery = User::selectRaw('COUNT(*) as count, MONTH(created_at) as month')
            ->whereYear('created_at', $year)
            ->where('role', '!=', 'admin')
            ->groupBy('month')
            ->get();

        $months = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December'
        ];

        $result = array_map(fn($month) => ['count' => 0, 'period' => $month], $months);

        foreach ($statsQuery as $item) {
            $result[$item->month - 1]['count'] = $item->count;
        }

        return response()->json($result, 200);
    }

    private function getYearlyStats($startYear, $endYear)
    {
        $statsQuery = User::selectRaw('COUNT(*) as count, YEAR(created_at) as year')
            ->whereBetween(DB::raw('YEAR(created_at)'), [$startYear, $endYear])
            ->where('role', '!=', 'admin')
            ->groupBy('year')
            ->get();

        $yearsRange = range($startYear, $endYear);
        $result = array_map(fn($year) => ['count' => 0, 'period' => $year], $yearsRange);

        foreach ($statsQuery as $item) {
            $index = array_search($item->year, $yearsRange);
            if ($index !== false) {
                $result[$index]['count'] = $item->count;
            }
        }

        return response()->json($result, 200);
    }
}
