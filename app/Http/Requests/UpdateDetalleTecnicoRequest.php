<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDetalleTecnicoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'equipo_id'             => 'sometimes|exists:equipos,id',
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
            'gas_refrigerante.uppercase' => 'El gas debe estar en MAYÚSCULAS.',
            'voltaje.uppercase'          => 'El voltaje debe estar en MAYÚSCULAS.',
        ];
    }
}