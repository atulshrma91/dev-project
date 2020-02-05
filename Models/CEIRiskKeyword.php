<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CEIRiskKeyword extends Model
{
  //protected $connection = 'wp';

  protected $table = 'cei_risk_keywords';

  public $timestamps = true;

  protected $fillable = ['company_id', 'risk_keyword', 'is_active', 'last_active'];

  public function company()
  {
      return $this->belongsTo('App\Models\Company', 'company_id');
  }

}
