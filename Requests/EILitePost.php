<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EILitePost extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // return $this->user()->can('create', FSIVRPost::class);
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'company_job_req_id' => 'required',
            'candidate_name' => 'required|min:2|max:255',
            'candidate_email' => 'required|email|min:2|max:255',
            'employer_pin' => 'required',
            'candidate_pin' => 'required'
        ];
    }
}
