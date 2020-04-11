<?php
namespace App;

/**
 * Class Constant Helper
 * 
 * Holds all constants and lists for use in models and view commonly
 */
class ConstantHelper
{
    const EMP_NATURE_FREELANCER = 1;
    const EMP_NATURE_FULLTIME = 2;
    const EMP_NATURE_PARTTIME = 3;
    
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    
    /**
     * List All Statuses with same as their keys
     *
     * @return \Illuminate\Contracts\Translation\Translator[]|string[]|array[]|NULL[]
     */
    public static function listAllStatusesWithStringKeys()
    {
        return [
            'enabled' => trans('app.enabled'),
            'disabled' => trans('app.disabled'),
            
            'approved' => trans('app.statuses.approved'),
            'pending' => trans('app.statuses.pending'),
            'paid' => trans('app.statuses.paid'),
            'repayment' => trans('app.statuses.repayment'),
            'rejected' => trans('app.statuses.rejected'),
            
            'cancelled' => trans('app.statuses.cancelled'),
        ];
    }
    
    /**
     * List basic Statuses with same as their keys
     * 
     * @return \Illuminate\Contracts\Translation\Translator[]|string[]|array[]|NULL[]
     */
    public static function listStatusesWithStringKeys()
    {
        $items = [
            'enabled' => trans('app.enabled'),
            'disabled' => trans('app.disabled'),
        ];
        
        return $items;
    }
    
    /**
     * List All loan Statuses with same as their keys
     *
     * @return \Illuminate\Contracts\Translation\Translator[]|string[]|array[]|NULL[]
     */
    public static function listLoanStatusesWithStringKeys()
    {
        $items = [
            'approved' => trans('app.statuses.approved'),
            'pending' => trans('app.statuses.pending'),
            'paid' => trans('app.statuses.paid'),
            'repayment' => trans('app.statuses.repayment'),
            'rejected' => trans('app.statuses.rejected'),
            'cancelled' => trans('app.statuses.cancelled'),
        ];
        
        return $items;
    }
    
    /**
     * List All Travel Statuses with same as their keys
     *
     * @return \Illuminate\Contracts\Translation\Translator[]|string[]|array[]|NULL[]
     */
    public static function listTravelStatusesWithStringKeys()
    {
        $items = [
            'approved' => trans('app.statuses.approved'),
            'pending' => trans('app.statuses.pending'),
            'cancelled' => trans('app.statuses.cancelled'),
            'rejected' => trans('app.statuses.rejected'),
        ];
        
        return $items;
    }
    
    /**
     * List job Natures for to be used in Employee Terms Controllers
     * 
     * @return \Illuminate\Contracts\Translation\Translator[]|string[]|array[]|NULL[]
     */
    public static function listJobNatures()
    {
        $items = [
            1 => trans('app.jobNature.freeLancer'),
            2 => trans('app.jobNature.fullTime'),
            3 => trans('app.jobNature.partTime'),
        ];
        
        return $items;
    }
}