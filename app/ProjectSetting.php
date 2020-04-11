<?php

namespace App;

use App\Observers\ProjectSettingObserver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProjectSetting extends BaseModel
{
    protected static function boot()
    {
        parent::boot();

        static::observe(ProjectSettingObserver::class);

        $company = company();

        static::addGlobalScope('company', function (Builder $builder) use($company) {
            if ($company) {
                $builder->where('project_settings.company_id', '=', $company->id);
            }
        });
    }

    public function getRemindToAttribute($value)
    {
        return json_decode($value);
    }

    public function setRemindToAttribute($value)
    {
        $this->attributes['remind_to'] = json_encode($value);
    }
}
