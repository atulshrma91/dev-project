<?php

namespace App\Models;

use App\MasterAudio;
use Illuminate\Database\Eloquent\Model;

class EILiteSession extends Model
{
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $connection = 'mysql';

    protected $table = 'ei_lite_sessions';
    
    protected $fillable = ['ei_lite_post_id', 'call_sid', 'employer_caller_id', 'candidate_caller_id'];

    public function post(){
        return $this->belongsTo(EILitePost::class, 'id', 'ei_lite_post_id');
    }

    public function audio(){
        return $this->hasOne(MasterAudio::class, 'object_id');
    }

}
