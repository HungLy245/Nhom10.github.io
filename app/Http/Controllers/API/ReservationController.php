<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookReservation;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookReservedNotification;

class ReservationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function reserve(Request $request, $bookId): JsonResponse
    {
        try {
            DB::beginTransaction();

            $book = Book::findOrFail($bookId);
            $user = auth()->user();

            // Kiểm tra subscription
            $subscription = $user->subscription;
            if (!$subscription || !$subscription->package->can_reserve) {
                return response()->json([
                    'message' => 'Gói của bạn không hỗ trợ đặt trước sách',
                    'code' => 'SUBSCRIPTION_NOT_ALLOWED'
                ], 403);
            }

            // Kiểm tra nếu sách còn
            if ($book->quantity > 0) {
                return response()->json([
                    'message' => 'Sách này vẫn còn, bạn có thể mượn ngay',
                    'code' => 'BOOK_AVAILABLE',
                    'data' => [
                        'book_id' => $book->id,
                        'quantity' => $book->quantity
                    ]
                ], 200);
            }

            // Kiểm tra đặt trước tồn tại
            $existingReservation = BookReservation::where('user_id', $user->id)
                ->where('book_id', $bookId)
                ->whereIn('status', ['pending', 'available'])
                ->first();

            if ($existingReservation) {
                return response()->json([
                    'message' => 'Bạn đã đặt trước quyển sách này rồi',
                    'code' => 'ALREADY_RESERVED'
                ], 400);
            }

            // Tạo đặt trước mới
            $reservation = BookReservation::create([
                'user_id' => $user->id,
                'book_id' => $bookId,
                'status' => 'pending'
            ]);

            // Gửi thông báo
            try {
                $this->notificationService->notifyBookReserved($reservation);
            } catch (\Exception $e) {
                Log::error('Notification error: ' . $e->getMessage());
                // Không throw exception, chỉ log lỗi
            }

            DB::commit();

            return response()->json([
                'message' => 'Đặt trước sách thành công',
                'data' => $reservation
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error reserving book:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Có lỗi xảy ra khi đặt trước sách',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function cancel($id): JsonResponse
    {
        $reservation = BookReservation::where('user_id', auth()->id())
            ->where('id', $id)
            ->where('status', 'pending')
            ->firstOrFail();

        $reservation->update(['status' => 'cancelled']);

        return response()->json($reservation);
    }

    public function myReservations(): JsonResponse
    {
        $reservations = BookReservation::with('book')
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return response()->json($reservations);
    }
} 