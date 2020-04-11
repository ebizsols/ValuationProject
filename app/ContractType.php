<?php

namespace App;

use App\Observers\ContractObserver;
use App\Observers\ContractTypeObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ContractType extends BaseModel
{
    protected static function boot()
    {
        parent::boot();

        static::observe(ContractTypeObserver::class);

        $company = company();

        static::addGlobalScope('company', function (Builder $builder) use($company) {
            if ($company) {
                $builder->where('contract_types.company_id', '=', $company->id);
            }
        });
    }
}
