<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTecnicoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre'       => 'required|string|max:255',
            'telefono'     => 'nullable|string|max:30',
            'especialidad' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del técnico es obligatorio.',
            'nombre.string'   => 'El nombre debe ser texto.',
        ];
    }
}
