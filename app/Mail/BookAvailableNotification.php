<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class BookAvailableNotification extends Mailable
{
    public $reservation;

    public function __construct($reservation)
    {
        $this->reservation = $reservation;
    }

    public function build()
    {
        return $this->markdown('emails.books.available')
                    ->subject('Sách bạn đặt trước đã có sẵn');
    }
} 