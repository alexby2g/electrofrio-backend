<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEquipoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'cliente_id' => 'required|exists:clientes,id',
            'tipo'       => 'required|string|max:100',
            'marca'      => 'nullable|string|max:100',
            'modelo'     => 'nullable|string|max:100',
            'capacidad'  => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.required' => 'Debes seleccionar un cliente para este equipo.',
            'cliente_id.exists'   => 'El cliente seleccionado no existe.',
            'tipo.required'       => 'El tipo de equipo es obligatorio.',
        ];
    }
}
