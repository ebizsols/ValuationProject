<?php

namespace App;

use App\Observers\PurposeConsentObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PurposeConsent extends BaseModel
{
    protected $table = 'purpose_consent';
    protected $fillable = ['name', 'description'];

    protected static function boot()
    {
        parent::boot();

        static::observe(PurposeConsentObserver::class);

        $company = company();

        static::addGlobalScope('company', function (Builder $builder) use($company) {
            if ($company) {
                $builder->where('purpose_consent.company_id', '=', $company->id);
            }
        });
    }

    public function lead()
    {
        return $this->hasOne(PurposeConsentLead::class, 'purpose_consent_id', 'id');
    }

    public function user()
    {
        return $this->hasOne(PurposeConsentUser::class, 'purpose_consent_id', 'id');
    }
}
