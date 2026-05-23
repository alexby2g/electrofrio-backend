<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDetalleTecnicoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'equipo_id'             => 'required|exists:equipos,id',
            'gas_refrigerante'      => 'nullable|string|uppercase',
            'voltaje'               => 'nullable|string|uppercase',
            'amperaje_nominal'      => 'nullable|numeric',
            'presion_succion_psi'   => 'nullable|integer',
            'presion_descarga_psi'  => 'nullable|integer',
            'observaciones_tecnicas'=> 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'equipo_id.required'         => 'El detalle debe estar enlazado a un equipo.',
            'gas_refrigerante.uppercase' => 'El tipo de gas debe estar en MAYÚSCULAS (ej: R410A).',
            'voltaje.uppercase'          => 'El voltaje debe estar en MAYÚSCULAS (ej: 220V).',
            'amperaje_nominal.numeric'   => 'El amperaje debe ser un número.',
        ];
    }
}