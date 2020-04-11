<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class GdprSetting extends BaseModel
{
    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();

        $company = company();

        static::addGlobalScope('company', function (Builder $builder) use($company) {
            if ($company) {
                $builder->where('gdpr_settings.company_id', '=', $company->id);
            }
        });
    }
}
