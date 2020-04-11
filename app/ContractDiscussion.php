<?php

namespace App;

use App\Observers\ContractDiscussionObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ContractDiscussion extends BaseModel
{
    protected static function boot()
    {
        parent::boot();

        static::observe(ContractDiscussionObserver::class);

        $company = company();

        static::addGlobalScope('company', function (Builder $builder) use($company) {
            if ($company) {
                $builder->where('contract_discussions.company_id', '=', $company->id);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'from', 'id');
    }
}
