<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EILitePost extends Model
{
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $connection = 'mysql';

    protected $table = 'ei_lite_posts';
    
    protected $fillable = ['user_id','company_job_req_id', 'candidate_name', 'candidate_email', 'candidate_number', 'host_key', 'pin', 'conference_key', 'score'];

    public function job(){
        return $this->hasOne(CompanyJobReq::class, 'id', 'company_job_req_id');
    }

    public function session(){
        return $this->hasOne(EILiteSession::class, 'ei_lite_post_id', 'id');
    }

}
