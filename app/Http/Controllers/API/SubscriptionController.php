<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Subscription;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'payment_method' => 'required|in:cash,transfer,card'
        ]);

        try {
            DB::beginTransaction();

            // Kiểm tra gói hiện tại
            $currentSubscription = Subscription::where('user_id', auth()->id())
                ->where('end_date', '>', Carbon::now())
                ->where('status', 'active')
                ->where('payment_status', 'completed')
                ->with('package')
                ->first();

            $newPackage = Package::findOrFail($validated['package_id']);
            
            if ($currentSubscription) {
                // Tính toán thời gian còn lại của gói hiện tại
                $remainingDays = Carbon::now()->diffInDays($currentSubscription->end_date);
                $totalDays = Carbon::parse($currentSubscription->start_date)
                    ->diffInDays($currentSubscription->end_date);
                
                // Tính giá trị còn lại của gói hiện tại
                $remainingValue = round(($remainingDays / $totalDays) * $currentSubscription->amount_paid, 0);

                if ($newPackage->price < $currentSubscription->package->price) {
                    return response()->json([
                        'message' => 'Không thể hạ cấp gói khi gói hiện tại còn hiệu lực'
                    ], 400);
                }

                // Tính phí nâng cấp
                $upgradeFee = round($newPackage->price - $remainingValue, 0);
                if ($upgradeFee < 0) $upgradeFee = 0;

                // Tạo subscription mới
                $startDate = Carbon::now();
                $endDate = $startDate->copy()->addMonths($newPackage->duration);

                $subscription = Subscription::create([
                    'user_id' => auth()->id(),
                    'package_id' => $newPackage->id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'amount_paid' => $upgradeFee,
                    'payment_method' => $validated['payment_method'],
                    'payment_status' => 'pending',
                    'status' => 'pending'
                ]);

                // Vô hiệu hóa gói cũ
                $currentSubscription->update([
                    'status' => 'cancelled',
                    'end_date' => Carbon::now()
                ]);

            } else {
                // Tạo subscription mới bình thường
                $startDate = Carbon::now();
                $endDate = $startDate->copy()->addMonths($newPackage->duration);

                $subscription = Subscription::create([
                    'user_id' => auth()->id(),
                    'package_id' => $newPackage->id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'amount_paid' => $newPackage->price,
                    'payment_method' => $validated['payment_method'],
                    'payment_status' => 'pending',
                    'status' => 'pending'
                ]);
            }

            // Xử lý thanh toán
            switch ($validated['payment_method']) {
                case 'cash':
                    $subscription->update([
                        'payment_status' => 'completed',
                        'status' => 'active'
                    ]);
                    break;
                
                case 'transfer':
                    return response()->json([
                        'message' => 'Vui lòng chuyển khoản theo thông tin sau',
                        'bank_info' => [
                            'account_number' => '123456789',
                            'bank_name' => 'VietcomBank',
                            'amount' => $subscription->amount_paid,
                            'content' => "THANHTOAN{$subscription->id}"
                        ],
                        'subscription' => $subscription
                    ]);
                
                case 'card':
                    // TODO: Implement card payment gateway
                    break;
            }

            // Gửi thông báo khi đăng ký thành công
            if ($subscription->payment_status === 'completed') {
                $this->notificationService->notifySubscriptionCreated($subscription);
            }

            // Gửi thông báo khi thanh toán thành công (nếu là thanh toán online)
            if ($subscription->payment_method !== 'cash' && $subscription->payment_status === 'completed') {
                $this->notificationService->notifyPaymentSuccess($subscription);
            }

            DB::commit();
            return response()->json([
                'message' => 'Đăng ký gói thành công',
                'subscription' => $subscription->load('package'),
                'upgrade_info' => $currentSubscription ? [
                    'previous_package' => $currentSubscription->package->name,
                    'remaining_value' => $remainingValue,
                    'upgrade_fee' => $upgradeFee ?? 0
                ] : null
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Đăng ký gói thất bại: ' . $e->getMessage()
            ], 500);
        }
    }

    public function current()
    {
        $subscription = Subscription::where('user_id', auth()->id())
            ->where('end_date', '>', now())
            ->where('status', 'active')
            ->where('payment_status', 'completed')
            ->with('package')
            ->first();

        return response()->json($subscription);
    }
} 