<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendWhatsAppRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to' => ['required', 'string', 'max:64'],
            'message' => ['required', 'string', 'max:4000'],
            'session' => ['nullable', 'string', 'max:128'],
        ];
    }
}
