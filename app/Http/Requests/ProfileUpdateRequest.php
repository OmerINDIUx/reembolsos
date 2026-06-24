<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'regex:/^\s*\S+(?:\s+\S+){2,}\s*$/u'],
            'rfc' => ['required', 'string', 'min:12', 'max:13', 'regex:/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/i'],
            'bank_name' => ['required', 'string', 'max:255'],
            'clabe' => ['required', 'string', 'digits:18'],
            'personal_info_confirmed' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Escribe tu nombre completo con nombre y dos apellidos.',
            'rfc.regex' => 'El RFC debe tener un formato válido de 12 o 13 caracteres.',
            'personal_info_confirmed.accepted' => 'Debes confirmar que tu información personal es correcta.',
        ];
    }
}
