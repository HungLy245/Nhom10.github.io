<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Borrow;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Subscription;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6|confirmed',
                'is_admin' => 'boolean'
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_admin' => $validated['is_admin'] ?? false
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token,
                'message' => 'Đăng ký thành công'
            ], 201)->header('Access-Control-Allow-Credentials', 'true')
              ->header('Access-Control-Allow-Origin', 'http://localhost:3000');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Đăng ký thất bại: ' . $e->getMessage()
            ], 400);
        }
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($validated)) {
            return response()->json([
                'message' => 'Email hoặc mật khẩu không chính xác'
            ], 401);
        }

        $user = User::where('email', $validated['email'])->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        try {
            // Revoke all tokens...
            $request->user()->tokens()->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Đăng xuất thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Đã có lỗi xảy ra khi đăng xuất'
            ], 500);
        }
    }

    public function profile(): JsonResponse
    {
        $user = Auth::user();
        $subscription = Subscription::where('user_id', $user->id)
            ->where('end_date', '>', Carbon::now())
            ->with('package')
            ->first();

        return response()->json([
            'user' => $user,
            'subscription' => $subscription
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
        ]);

        $user->update($validated);

        return response()->json($user);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:3|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Mật khẩu hiện tại không đúng'
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'message' => 'Đổi mật khẩu thành công'
        ]);
    }

    public function statistics(): JsonResponse 
    {
        $user = Auth::user();
        
        $stats = [
            'total_borrows' => Borrow::where('user_id', $user->id)->count(),
            'current_borrows' => Borrow::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'borrowed'])
                ->count(),
            'overdue_borrows' => Borrow::where('user_id', $user->id)
                ->where('status', 'borrowed')
                ->where('due_date', '<', Carbon::now())
                ->count(),
            'returned_borrows' => Borrow::where('user_id', $user->id)
                ->where('status', 'returned')
                ->count()
        ];

        return response()->json($stats);
    }

    public function activities(): JsonResponse
    {
        $activities = Borrow::with(['book', 'book.category'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($borrow) {
                return [
                    'id' => $borrow->id,
                    'type' => 'borrow',
                    'book' => $borrow->book->name,
                    'status' => $borrow->status,
                    'date' => $borrow->created_at,
                    'due_date' => $borrow->due_date,
                    'return_date' => $borrow->return_date
                ];
            });

        return response()->json($activities);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users'
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Đã gửi email khôi phục mật khẩu'
            ]);
        }

        return response()->json([
            'message' => 'Không thể gửi email khôi phục'
        ], 400);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:3|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->update([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60)
                ]);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Đặt lại mật khẩu thành công'
            ]);
        }

        return response()->json([
            'message' => 'Không thể đặt lại mật khẩu'
        ], 400);
    }

    public function getUser(Request $request)
    {
        try {
            $user = $request->user();
            return response()->json($user);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Không thể lấy thông tin người dùng'
            ], 500);
        }
    }
} 