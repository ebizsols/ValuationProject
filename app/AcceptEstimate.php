<?php

namespace App;

use App\Observers\AcceptEstimateObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AcceptEstimate extends BaseModel
{
    protected static function boot()
    {
        parent::boot();

        static::observe(AcceptEstimateObserver::class);

        $company = company();

        static::addGlobalScope('company', function (Builder $builder) use($company) {
            if ($company) {
                $builder->where('accept_estimates.company_id', '=', $company->id);
            }
        });
    }

    public function getSignatureAttribute()
    {
        return asset_url('estimate/accept/'.$this->attributes['signature']);
    }
}
