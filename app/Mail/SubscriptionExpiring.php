<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Mail\Mailable;

class SubscriptionExpiring extends Mailable
{
    public $subscription;

    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }

    public function build()
    {
        return $this->markdown('emails.subscription.expiring')
                    ->subject('Gói thành viên sắp hết hạn');
    }
} 