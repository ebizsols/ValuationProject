<?php

namespace App;

use App\Observers\OfflineInvoiceObserver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OfflineInvoice extends BaseModel
{

    protected $dates = [
        'pay_date',
        'next_pay_date'
    ];

    protected static function boot()
    {
        parent::boot();

        static::observe(OfflineInvoiceObserver::class);

        $company = company();

        static::addGlobalScope('company', function (Builder $builder) use ($company) {
            if ($company) {
                $builder->where('offline_invoices.company_id', '=', $company->id);
            }
        });
    }

    public function company() {
        return $this->belongsTo(Company::class, 'company_id')->withoutGlobalScopes(['active']);
    }

    public function package() {
        return $this->belongsTo(Package::class, 'package_id');
    }

    public function offline_payment_method() {
        return $this->belongsTo(OfflinePaymentMethod::class, 'offline_method_id')->whereNull('company_id');
    }

    public function offline_plan_change_request() {
        return $this->hasOne(OfflinePlanChange::class, 'invoice_id');
    }


}
