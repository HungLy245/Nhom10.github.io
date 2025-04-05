<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BookReservation;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;
use App\Notifications\BookUnavailableNotification;
use Illuminate\Support\Facades\Log;

class ProcessBookQuantityChanges extends Command
{
    protected $signature = 'books:process-quantity-changes';
    protected $description = 'Process book quantity changes and send notifications';

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle()
    {
        try {
            $changes = DB::table('book_quantity_changes')
                ->orderBy('created_at')
                ->get();

            if ($changes->isEmpty()) {
                $this->info('No changes to process');
                return;
            }

            foreach ($changes as $change) {
                DB::beginTransaction();
                try {
                    // Khi số lượng tăng từ 0
                    if ($change->old_quantity == 0 && $change->new_quantity > 0) {
                        $this->processBookAvailable($change);
                    }
                    // Khi số lượng giảm xuống 0
                    elseif ($change->old_quantity > 0 && $change->new_quantity == 0) {
                        $this->processBookUnavailable($change);
                    }

                    // Xóa record sau khi xử lý thành công
                    DB::table('book_quantity_changes')
                        ->where('id', $change->id)
                        ->delete();

                    DB::commit();
                    $this->info("Processed and deleted change ID {$change->id}");

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("Error processing change ID {$change->id}: " . $e->getMessage());
                    $this->error($e->getMessage());
                }
            }

        } catch (\Exception $e) {
            Log::error('Error fetching changes: ' . $e->getMessage());
            $this->error($e->getMessage());
        }
    }

    protected function processBookAvailable($change)
    {
        $pendingReservations = BookReservation::where('book_id', $change->book_id)
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        foreach ($pendingReservations as $reservation) {
            try {
                $this->notificationService->notifyBookAvailable($reservation);
            } catch (\Exception $e) {
                Log::error('Error sending book available notification:', [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    protected function processBookUnavailable($change)
    {
        $availableReservations = BookReservation::where('book_id', $change->book_id)
            ->where('status', 'available')
            ->get();

        foreach ($availableReservations as $reservation) {
            try {
                $reservation->update([
                    'status' => 'pending',
                    'available_until' => null
                ]);

                // Tạo thông báo
                Notification::create([
                    'user_id' => $reservation->user_id,
                    'type' => 'book_unavailable',
                    'title' => 'Sách tạm thời hết hàng',
                    'content' => "Sách \"{$reservation->book->title}\" hiện đã hết. Chúng tôi sẽ thông báo khi có sách."
                ]);

                Mail::to($reservation->user->email)
                    ->send(new BookUnavailableNotification($reservation));

            } catch (\Exception $e) {
                Log::error('Error processing book unavailable notification:', [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
} 