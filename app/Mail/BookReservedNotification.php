<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\BookReservation;

class BookReservedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $reservation;

    public function __construct(BookReservation $reservation)
    {
        $this->reservation = $reservation;
    }

    public function build()
    {
        return $this->view('emails.book-reserved')
            ->subject('Đặt trước sách thành công');
    }
} 