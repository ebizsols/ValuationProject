<?php

namespace App;

use App\Observers\LeadAgentObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class LeadAgent extends BaseModel
{
    protected $table = 'lead_agents';

    protected static function boot()
    {
        parent::boot();

        static::observe(LeadAgentObserver::class);

        $company = company();

        static::addGlobalScope('company', function (Builder $builder) use($company) {
            if ($company) {
                $builder->where('lead_agents.company_id', '=', $company->id);
            }
        });
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function lead(){
        return $this->hasOne(Lead::class);
    }

}
