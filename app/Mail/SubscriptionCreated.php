<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Mail\Mailable;

class SubscriptionCreated extends Mailable
{
    public $subscription;

    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }

    public function build()
    {
        return $this->markdown('emails.subscription.created')
                    ->subject('Đăng ký thành viên Thư viện Online');
    }
} 