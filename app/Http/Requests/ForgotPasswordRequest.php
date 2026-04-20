<?php

namespace App\Http\Requests;

class ForgotPasswordRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'email' => 'required|email'
        ];
    }
}
