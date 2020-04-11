<?php

namespace App;

use App\Observers\TaskFileObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class TaskFile extends BaseModel
{

    protected $appends = ['file_url'];

    public function getFileUrlAttribute()
    {
        return (!is_null($this->external_link)) ? $this->external_link : asset_url_local_s3('task-files/'.$this->task_id.'/'.$this->hashname);
    }

    protected static function boot()
    {
        parent::boot();

        static::observe(TaskFileObserver::class);

        $company = company();

        static::addGlobalScope('company', function (Builder $builder) use($company) {
            if ($company) {
                $builder->where('task_files.company_id', '=', $company->id);
            }
        });
    }
}
