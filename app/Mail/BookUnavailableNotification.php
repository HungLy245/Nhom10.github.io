<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class BookUnavailableNotification extends Mailable
{
    public $reservation;

    public function __construct($reservation)
    {
        $this->reservation = $reservation;
    }

    public function build()
    {
        return $this->markdown('emails.books.unavailable')
                    ->subject('Thông báo sách tạm thời hết hàng');
    }
} 