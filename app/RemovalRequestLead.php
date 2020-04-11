<?php

namespace App;

use App\Observers\RemovalRequestLeadObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class RemovalRequestLead extends BaseModel
{

    protected $table = 'removal_requests_lead';

    protected static function boot()
    {
        parent::boot();

        static::observe(RemovalRequestLeadObserver::class);

        $company = company();

        static::addGlobalScope('company', function (Builder $builder) use($company) {
            if ($company) {
                $builder->where('removal_requests_lead.company_id', '=', $company->id);
            }
        });
    }

    public function lead(){
        return $this->belongsTo(Lead::class);
    }
}
