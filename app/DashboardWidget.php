<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class DashboardWidget extends BaseModel
{
    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();

        $company = company();

        static::addGlobalScope('company', function (Builder $builder) use($company) {
            if ($company) {
                $builder->where('dashboard_widgets.company_id', '=', $company->id);
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
