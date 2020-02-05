<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyUser extends Model
{
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $connection = 'mysql';

    protected $table = 'company_users';

    protected $fillable = ['user_id', 'company_id'];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
