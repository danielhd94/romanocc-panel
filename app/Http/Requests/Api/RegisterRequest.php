<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6',
            'password_confirmation' => 'required|same:password',
            'accepted_terms' => 'required|boolean|accepted',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es requerido.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'phone.required' => 'El número de teléfono es requerido.',
            'phone.unique' => 'El número de teléfono ya está registrado.',
            'password.required' => 'La contraseña es requerida.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'password_confirmation.required' => 'La confirmación de contraseña es requerida.',
            'password_confirmation.same' => 'Las contraseñas no coinciden.',
            'accepted_terms.required' => 'Debe aceptar los términos y condiciones.',
            'accepted_terms.accepted' => 'Debe aceptar los términos y condiciones.',
        ];
    }
}
