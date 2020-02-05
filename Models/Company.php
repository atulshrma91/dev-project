<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $connection = 'wp';

    protected $table = 'wpjb_company';

    public $timestamps = false;

    protected $fillable = ['user_id', 'pin', 'company_name', 'company_website', 'company_info', 'company_country', 'company_state', 'company_zip_code', 'company_location', 'jobs_posted', 'cis_public', 'is_active', 'is_verified'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users(){
        return $this->hasMany(CompanyUser::class, 'company_id');
    }

    public function keywords(){
        return $this->hasMany('App\Models\CEIRiskKeyword', 'company_id');
    }



}
