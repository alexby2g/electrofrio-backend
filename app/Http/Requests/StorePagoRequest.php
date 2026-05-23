<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePagoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'servicio_id'   => 'required|exists:servicios,id',
            'monto'         => 'required|numeric|min:0',
            'fecha_pago'    => 'required|date',
            'metodo_pago'   => 'nullable|string|max:50',
            'estado'        => 'nullable|string|max:50',
            'observaciones' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'servicio_id.required' => 'El pago debe asociarse a un servicio.',
            'servicio_id.exists'   => 'El servicio seleccionado no existe.',
            'monto.required'       => 'El monto es obligatorio.',
            'monto.numeric'        => 'El monto debe ser numérico.',
            'monto.min'            => 'El monto no puede ser negativo.',
            'fecha_pago.required'  => 'La fecha de pago es obligatoria.',
            'fecha_pago.date'      => 'La fecha de pago no tiene un formato válido.',
        ];
    }
}
