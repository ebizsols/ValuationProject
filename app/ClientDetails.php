<?php

namespace App;

use App\Observers\ClientDetailObserver;
use App\Traits\CustomFieldsTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ClientDetails extends BaseModel
{
    use CustomFieldsTrait;

    protected $table = 'client_details';

    protected $appends = ['image_url'];
    protected static function boot()
    {
        parent::boot();

        static::observe(ClientDetailObserver::class);

        $company = company();

        static::addGlobalScope('company', function (Builder $builder) use ($company) {
            if ($company) {
                $builder->where('client_details.company_id', '=', $company->id);
            }
        });
    }

    public function getImageUrlAttribute()
    {
        return ($this->image) ? asset_url('avatar/' . $this->image) : asset('default-profile-2.png');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withoutGlobalScopes(['active']);
    }

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id')->withoutGlobalScopes(['active', 'company']);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
