<?php

namespace App\Observers;

use App\Notifications\OfflinePackageChangeConfirmation;
use App\Notifications\OfflinePackageChangeRequest;
use App\OfflinePlanChange;
use App\User;
use Illuminate\Support\Facades\Notification;

class OfflinePlanChangeObserver
{

    public function created(OfflinePlanChange $offlinePlanChange)
    {
        if (!isRunningInConsoleOrSeeding()) {
            $company = company();
            $superAdmin = User::withoutGlobalScope('company')->whereNull('company_id')->get();
            Notification::send($superAdmin, new OfflinePackageChangeRequest($company, $offlinePlanChange));
        }
    }

    public function updated(OfflinePlanChange $offlinePlanChange)
    {
        if (!isRunningInConsoleOrSeeding()) {
            if ($offlinePlanChange->isDirty('status')) {

                $offlinePlanChange->company->notify(new OfflinePackageChangeConfirmation($offlinePlanChange));
            }
        }
    }
}