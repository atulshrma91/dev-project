<?php

namespace App\Repositories;

use App\Models\EILitePost;
use App\Models\EILiteSession;
use Illuminate\Support\Facades\Auth;

class EILitePostRepository
{

    public function getById($id){
        $post = EILitePost::find($id);
        if (empty($post)){
            return false;
        }
        return $post;
    }

    public function findEILitePost($id){
        $post = EILitePost::with('session')->where('pin', $id)->first();
        if (empty($post)){
            return [];
        }
        return $post;
    }
    
    public function getAll(){
        $post = EILitePost::with('session')->where('user_id', Auth::id())->get();
        return $post;
    }
    
    public function create($data)
    {
        
        $session = new EILitePost();
        $session->user_id = isset($data['user_id']) ? $data['user_id'] : '';
        $session->company_job_req_id = isset($data['company_job_req_id']) ? $data['company_job_req_id'] : '';
        $session->candidate_name = isset($data['candidate_name']) ? $data['candidate_name'] : '';
        $session->candidate_email = isset($data['candidate_email']) ? $data['candidate_email'] : '';
        $session->candidate_number = isset($data['candidate_number']) ? $data['candidate_number'] : '';
        $session->host_key = isset($data['employer_pin']) ? $data['employer_pin'] : '';
        $session->pin = isset($data['candidate_pin']) ? $data['candidate_pin'] : '';
        $session->conference_key = isset($data['full_pin']) ? $data['full_pin'] : '';
        $session->save();

        return $session;
    }

    public function createIVRSession($data)
    {

        $session = new EILiteSession();
        $session->from = $data['from'];
        $session->to = $data['to'];
        $session->account_sid = $data['account_sid'];
        $session->recording_url = $data['recording_url'];
        $session->recording_sid = $data['recording_sid'];
        $session->employer_caller_id = $data['employer_caller_id'];
        $session->candidate_caller_id = $data['call_sid'];
        $session->call_sid = $data['call_sid'];
        $session->ei_lite_post_id = $data['ei_lite_post_id'];
        $session->dial_call_status = $data['dial_call_status'];
        $session->call_status = $data['call_status'];
        $session->save();

        return $session;
    }

    public function delete($id){
        return EILitePost::destroy($id);
    }


}
