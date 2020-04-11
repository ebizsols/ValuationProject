<?php

namespace App;

use App\Observers\TicketFileObserver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TicketFile extends BaseModel
{

    protected $appends = ['file_url'];

    public function getFileUrlAttribute()
    {
        return (!is_null($this->external_link)) ? $this->external_link : asset_url_local_s3('ticket-files/'.$this->ticket_reply_id.'/'.$this->hashname);
    }

    protected static function boot()
    {
        parent::boot();

        static::observe(TicketFileObserver::class);

        $company = company();

        static::addGlobalScope('company', function (Builder $builder) use($company) {
            if ($company) {
                $builder->where('ticket_files.company_id', '=', $company->id);
            }
        });
    }
}
