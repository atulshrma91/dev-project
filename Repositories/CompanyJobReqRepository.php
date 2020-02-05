<?php

namespace App\Repositories;

use App\Models\CompanyJobReq;
use Illuminate\Support\Facades\Auth;

class CompanyJobReqRepository
{

    public function getById($id){
        $post = CompanyJobReq::find($id);
        if (empty($post)){
            return false;
        }
        return $post;
    }
    
    public function getAll(){
        $post = CompanyJobReq::where('company_id', company_info()->id)->get();
        return $post;
    }
    
    public function create($data)
    {
        
        $session = new CompanyJobReq();
        $session->user_id = isset($data['user_id']) ? $data['user_id'] : '';
        $session->company_id = isset($data['company_id']) ? $data['company_id'] : '';
        $session->req_desc = isset($data['req_desc']) ? $data['req_desc'] : '';
        $session->is_ii = isset($data['is_ii']) ? $data['is_ii'] : false;
        $session->is_ii_lite = isset($data['is_ii_lite']) ? $data['is_ii_lite'] : false;
        $session->is_fs = isset($data['is_fs']) ? $data['is_fs'] : false;
        $session->is_fs_lite = isset($data['is_fs_lite']) ? $data['is_fs_lite'] : false;
        $session->is_active = true;
        $session->pin = isset($data['pin']) ? $data['pin'] : 'NULL';
        $session->save();

        return $session;
    }

    public function status($data){
        $job = CompanyJobReq::find($data['id']);
        $job->is_active = isset($data['status']) ? $data['status'] : false;
        $job->save();
        return $job;
    }

    public function statusColumn($data){
        $job = CompanyJobReq::find($data['id']);
        if (isset($data['column']) && $data['column'] == 'is_ii') {
            $job->is_ii = isset($data['status']) ? $data['status'] : false;
        }else if (isset($data['column']) && $data['column'] == 'is_ii_lite'){
            $job->is_ii_lite = isset($data['status']) ? $data['status'] : false;
        }else if (isset($data['column']) && $data['column'] == 'is_fs'){
            $job->is_fs = isset($data['status']) ? $data['status'] : false;
        }else if (isset($data['column']) && $data['column'] == 'is_fs_lite'){
            $job->is_fs_lite = isset($data['status']) ? $data['status'] : false;
        }
        $job->save();
        return $job;
    }

}
