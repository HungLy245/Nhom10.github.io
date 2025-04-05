<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class BookReserved extends Mailable
{
    public $reservation;

    public function __construct($reservation)
    {
        $this->reservation = $reservation;
    }

    public function build()
    {
        return $this->markdown('emails.books.reserved')
                    ->subject('Đặt trước sách thành công');
    }
} 