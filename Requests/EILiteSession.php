<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EILiteSession extends FormRequest
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
            'title' => 'required|min:5|max:100',
            'questions' => 'required|array|min:5|max:15',
            'questions.*.content' => 'required|max:255',
            'questions.*.type' => 'required|in:audio,text',
        ];
    }
}
