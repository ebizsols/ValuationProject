<?php

namespace App\Observers;

use App\ClientPayment;
use App\Notifications\InvoicePaymentReceived;
use App\User;
use Illuminate\Support\Facades\Notification;

class InvoicePaymentReceivedObserver
{
    public function created(ClientPayment $payment)
    {
        if (!isRunningInConsoleOrSeeding()) {
            $admins = User::allAdmins();
            Notification::send($admins, new InvoicePaymentReceived($payment->invoice));
        }
    }
}
