<?php

namespace App;

use App\Observers\DesignationObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Designation extends BaseModel
{
    protected $fillable = ['name', 'company_id'];

    protected static function boot()
    {
        parent::boot();

        static::observe(DesignationObserver::class);

        $company = company();

        static::addGlobalScope('company', function (Builder $builder) use($company) {
            if ($company) {
                $builder->where('designations.company_id', '=', $company->id);
            }
        });
    }

    public function members()
    {
        return $this->hasMany(EmployeeDetails::class, 'designation_id');
    }

}
