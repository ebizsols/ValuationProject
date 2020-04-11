<?php

namespace App;

use App\Observers\RemovalRequestObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class RemovalRequest extends BaseModel
{

    protected $table = 'removal_requests';

    protected static function boot()
    {
        parent::boot();

        static::observe(RemovalRequestObserver::class);

        $company = company();

        static::addGlobalScope('company', function (Builder $builder) use($company) {
            if ($company) {
                $builder->where('removal_requests.company_id', '=', $company->id);
            }
        });
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
}
