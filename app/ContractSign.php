<?php

namespace App;

use App\Observers\ContractObserver;
use App\Observers\ContractSignObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ContractSign extends BaseModel
{

    protected static function boot()
    {
        parent::boot();

        static::observe(ContractSignObserver::class);

        $company = company();

        static::addGlobalScope('company', function (Builder $builder) use($company) {
            if ($company) {
                $builder->where('contract_signs.company_id', '=', $company->id);
            }
        });
    }
    public function getSignatureAttribute()
    {
        return asset_url('contract/sign/'.$this->attributes['signature']);
    }
}
