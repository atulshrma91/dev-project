<?php

namespace App\Repositories;

use App\Models\CEIRiskKeyword;
use Illuminate\Support\Facades\Auth;

class CIERiskKeywordsReqRepository
{

    public function getById($id){
        $post = CEIRiskKeyword::find($id);
        if (empty($post)){
            return false;
        }
        return $post;
    }

    public function getAll(){
        $post = CEIRiskKeyword::where('company_id', company_info()->id)->get();
        return $post;
    }

    public function create($data)
    {

        $session = new CEIRiskKeyword();
        $session->company_id = isset($data['company_id']) ? $data['company_id'] : '';
        $session->risk_keyword = isset($data['risk_keyword']) ? $data['risk_keyword'] : '';
        $session->is_active = true;
        $session->save();

        return $session;
    }

    public function status($data){
        $keyword = CEIRiskKeyword::find($data['id']);
        $keyword->is_active = isset($data['status']) ? $data['status'] : false;
        $keyword->save();
        return $keyword;
    }

    public function statusColumn($data){
        $keyword = CEIRiskKeyword::find($data['id']);
        if (isset($data['column']) && $data['column'] == 'risk_keyword') {
            $keyword->risk_keyword = isset($data['status']) ? $data['status'] : false;
        }else if (isset($data['column']) && $data['column'] == 'is_active'){
            $keyword->is_active = isset($data['status']) ? $data['status'] : false;
        }

        $keyword->save();
        return $keyword;
    }

}
