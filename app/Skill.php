<?php

namespace App;

use App\Observers\SkillsObserver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Skill extends BaseModel
{
    protected $table = 'skills';
    protected $fillable = ['name'];

    protected static function boot()
    {
        parent::boot();

        static::observe(SkillsObserver::class);

        $company = company();

        static::addGlobalScope('company', function (Builder $builder) use($company) {
            if ($company) {
                $builder->where('skills.company_id', '=', $company->id);
            }
        });
    }
}
