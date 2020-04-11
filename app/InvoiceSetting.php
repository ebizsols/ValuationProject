<?php

namespace App;

use App\Observers\InvoiceSettingObserver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InvoiceSetting extends BaseModel
{
    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute()
    {
        return (is_null($this->logo)) ? asset('img/worksuite-logo.png') : asset_url('app-logo/' . $this->logo);
    }

    protected static function boot()
    {
        parent::boot();

        static::observe(InvoiceSettingObserver::class);

        $company = company();

        static::addGlobalScope('company', function (Builder $builder) use($company) {
            if ($company) {
                $builder->where('invoice_settings.company_id', '=', $company->id);
            }
        });
    }
}
