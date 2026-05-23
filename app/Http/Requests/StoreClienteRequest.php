<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClienteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre'    => 'required|string|max:255',
            'telefono'  => 'nullable|string|max:30',
            'direccion' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del cliente es obligatorio.',
            'nombre.string'   => 'El nombre debe ser texto.',
            'nombre.max'      => 'El nombre no debe superar los 255 caracteres.',
        ];
    }
}
