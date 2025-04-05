<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Borrow;
use App\Models\Book;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Response;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use App\Models\BookReservation;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;
use App\Notifications\BookUnavailableNotification;

class BorrowController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(): JsonResponse
    {
        $borrows = Borrow::with(['user', 'book', 'book.category'])
            ->latest()
            ->get();
        return response()->json($borrows);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Validate request
            $request->validate([
                'book_id' => 'required|exists:books,id'
            ]);

            // Kiểm tra subscription
            $subscription = Subscription::where('user_id', auth()->id())
                ->where('end_date', '>', Carbon::now())
                ->first();

            if (!$subscription) {
                return response()->json([
                    'message' => 'Vui lòng đăng ký gói thành viên để mượn sách',
                    'code' => 'NO_SUBSCRIPTION'
                ], 403);
            }

            // Kiểm tra số lượng sách đã mượn
            $currentBorrows = Borrow::where('user_id', auth()->id())
                ->whereIn('status', ['pending', 'borrowed'])
                ->count();

            if ($currentBorrows >= $subscription->package->max_borrows) {
                return response()->json([
                    'message' => 'Bạn đã đạt giới hạn số sách được mượn',
                    'code' => 'MAX_BORROWS_REACHED'
                ], 403);
            }

            $book = Book::findOrFail($request->book_id);

            if ($book->quantity <= 0) {
                return response()->json([
                    'message' => 'Sách đã hết',
                    'code' => 'BOOK_UNAVAILABLE'
                ], 400);
            }

            // Tạo borrow record
            $borrow = Borrow::create([
                'user_id' => auth()->id(),
                'book_id' => $book->id,
                'borrow_date' => now(),
                'due_date' => now()->addDays($subscription->package->borrow_duration),
                'status' => 'borrowed'
            ]);

            // Giảm số lượng sách
            $book->decrement('quantity');

            // Gửi email và notification
            try {
                Mail::to(auth()->user()->email)->send(new \App\Mail\BookBorrowed($borrow));
                
                // Tạo notification
                Notification::create([
                    'user_id' => auth()->id(),
                    'type' => 'book_borrowed',
                    'title' => 'Mượn sách thành công',
                    'content' => "Bạn đã mượn sách \"{$book->title}\" thành công",
                    'data' => [
                        'borrow_id' => $borrow->id,
                        'book_id' => $book->id,
                        'book_title' => $book->title
                    ]
                ]);
            } catch (\Exception $e) {
                \Log::error('Error sending borrow notification: ' . $e->getMessage());
                // Không throw exception để transaction vẫn complete
            }

            DB::commit();

            return response()->json([
                'message' => 'Mượn sách thành công',
                'data' => $borrow
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error borrowing book: ' . $e->getMessage());
            return response()->json([
                'message' => 'Có lỗi xảy ra khi mượn sách',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function return(Request $request, $id): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $borrow = Borrow::findOrFail($id);
            $borrow->update([
                'status' => 'returned',
                'return_date' => now()
            ]);

            // Tăng số lượng sách
            $book = $borrow->book;
            $book->increment('quantity');

            // Kiểm tra và thông báo cho tất cả người đặt trước
            $pendingReservations = BookReservation::where('book_id', $book->id)
                ->where('status', 'pending')
                ->orderBy('created_at')
                ->get();

            if ($pendingReservations->isNotEmpty() && $book->quantity > 0) {
                foreach ($pendingReservations as $reservation) {
                    try {
                        $this->notificationService->notifyBookAvailable($reservation);
                    } catch (\Exception $e) {
                        \Log::error('Error sending book available notification:', [
                            'reservation_id' => $reservation->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json([
                'message' => 'Trả sách thành công'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error returning book:', [
                'borrow_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'message' => 'Có lỗi xảy ra khi trả sách'
            ], 500);
        }
    }

    public function userBorrows(): JsonResponse
    {
        $borrows = Borrow::where('user_id', auth()->id())
            ->with(['book'])
            ->latest()
            ->get();
        
        return response()->json($borrows);
    }

    public function borrow(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            // Kiểm tra subscription
            $subscription = Subscription::where('user_id', auth()->id())
                ->where('end_date', '>', now())
                ->where('status', 'active')
                ->where('payment_status', 'completed')
                ->first();

            if (!$subscription) {
                return response()->json([
                    'message' => 'Bạn cần có gói thành viên để mượn sách',
                    'code' => 'NO_SUBSCRIPTION'
                ], 403);
            }

            $validated = $request->validate([
                'book_id' => 'required|exists:books,id'
            ]);

            $book = Book::findOrFail($validated['book_id']);

            // Kiểm tra số lượng sách
            if ($book->quantity <= 0) {
                return response()->json([
                    'message' => 'Sách này đã hết',
                    'code' => 'BOOK_OUT_OF_STOCK'
                ], 400);
            }

            // Kiểm tra nếu đã mượn sách này
            $existingBorrow = Borrow::where('user_id', auth()->id())
                ->where('book_id', $book->id)
                ->whereIn('status', ['pending', 'borrowed'])
                ->first();

            if ($existingBorrow) {
                return response()->json([
                    'message' => 'Bạn đã mượn quyển sách này rồi',
                    'code' => 'ALREADY_BORROWED'
                ], 400);
            }

            // Kiểm tra số lượng sách đã mượn
            $currentBorrows = Borrow::where('user_id', auth()->id())
                ->whereIn('status', ['pending', 'borrowed'])
                ->count();

            if ($currentBorrows >= $subscription->package->max_borrows) {
                return response()->json([
                    'message' => 'Bạn đã đạt giới hạn số sách được mượn',
                    'code' => 'MAX_BORROWS_REACHED'
                ], 403);
            }

            $borrow = Borrow::create([
                'user_id' => auth()->id(),
                'book_id' => $book->id,
                'borrow_date' => now(),
                'due_date' => now()->addDays($subscription->package->borrow_duration),
                'status' => 'borrowed'
            ]);

            $book->decrement('quantity');

            // Thêm try-catch cho notification
            try {
                $this->notificationService->notifyBookBorrowed($borrow);
            } catch (\Exception $e) {
                \Log::error('Notification error: ' . $e->getMessage());
                // Không throw exception, chỉ log lỗi
            }

            DB::commit();
            
            return response()->json([
                'message' => 'Mượn sách thành công',
                'data' => $borrow
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error borrowing book: ' . $e->getMessage());
            return response()->json([
                'message' => 'Có lỗi xảy ra khi mượn sách',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateQuantity(Request $request, $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $book = Book::findOrFail($id);
            $oldQuantity = $book->quantity;
            $newQuantity = $request->quantity;
            
            $book->update([
                'quantity' => $newQuantity
            ]);

            // Nếu số lượng tăng từ 0 lên, kiểm tra và thông báo sách có sẵn
            if ($oldQuantity == 0 && $newQuantity > 0) {
                $pendingReservations = BookReservation::where('book_id', $book->id)
                    ->where('status', 'pending')
                    ->orderBy('created_at')
                    ->get();

                foreach ($pendingReservations as $reservation) {
                    try {
                        $this->notificationService->notifyBookAvailable($reservation);
                    } catch (\Exception $e) {
                        \Log::error('Error sending book available notification:', [
                            'reservation_id' => $reservation->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            // Thêm logic kiểm tra khi sách hết hàng
            elseif ($oldQuantity > 0 && $newQuantity == 0) {
                // Tìm các đặt trước đang ở trạng thái available
                $availableReservations = BookReservation::where('book_id', $book->id)
                    ->where('status', 'available')
                    ->get();

                foreach ($availableReservations as $reservation) {
                    try {
                        // Cập nhật trạng thái về pending
                        $reservation->update([
                            'status' => 'pending',
                            'available_until' => null
                        ]);

                        // Tạo thông báo
                        Notification::create([
                            'user_id' => $reservation->user_id,
                            'type' => 'book_unavailable',
                            'title' => 'Sách tạm thời hết hàng',
                            'content' => "Sách \"{$book->title}\" hiện đã hết. Chúng tôi sẽ thông báo khi có sách.",
                            'data' => [
                                'book_id' => $book->id,
                                'book_title' => $book->title
                            ]
                        ]);

                        // Gửi email thông báo
                        Mail::to($reservation->user->email)
                            ->send(new BookUnavailableNotification($reservation));

                    } catch (\Exception $e) {
                        \Log::error('Error sending book unavailable notification:', [
                            'reservation_id' => $reservation->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json([
                'message' => 'Cập nhật số lượng sách thành công'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating book quantity:', [
                'book_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'message' => 'Có lỗi xảy ra khi cập nhật số lượng sách'
            ], 500);
        }
    }
} 