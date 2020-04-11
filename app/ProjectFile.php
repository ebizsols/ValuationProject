<?php

namespace App;

use App\Observers\ProjectFileObserver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProjectFile extends BaseModel
{
    protected $appends = ['file_url'];

    public function getFileUrlAttribute()
    {
        return (!is_null($this->external_link)) ? $this->external_link : asset_url_local_s3('project-files/'.$this->project_id.'/'.$this->hashname);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    protected static function boot()
    {
        parent::boot();


        static::observe(ProjectFileObserver::class);

        $company = company();

        static::addGlobalScope('company', function (Builder $builder) use($company) {
            if ($company) {
                $builder->where('project_files.company_id', '=', $company->id);
            }
        });
    }
}
