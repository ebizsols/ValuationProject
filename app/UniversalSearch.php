<?php

namespace App;

use App\Observers\UniversalSearchObserver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UniversalSearch extends BaseModel
{
    protected $table = 'universal_search';

    protected static function boot()
    {
        parent::boot();

        static::observe(UniversalSearchObserver::class);

        $company = company();

        static::addGlobalScope('company', function (Builder $builder) use($company) {
            if ($company) {
                $builder->where('universal_search.company_id', '=', $company->id);
            }
        });
    }
}
