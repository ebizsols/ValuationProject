<?php

namespace App;

use App\Helper\Reply;
use App\Observers\ContractObserver;
use App\Observers\ContractRenewObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ContractRenew extends BaseModel
{

    protected static function boot()
    {
        parent::boot();

        static::observe(ContractRenewObserver::class);
        $company = company();

        static::addGlobalScope('company', function (Builder $builder) use($company) {
            if ($company) {
                $builder->where('contract_renews.company_id', '=', $company->id);
            }
        });
    }
    protected $dates = [
        'start_date',
        'end_date'
    ];
    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }
    public function renewedBy()
    {
        return $this->belongsTo(User::class, 'renewed_by');
    }
}
