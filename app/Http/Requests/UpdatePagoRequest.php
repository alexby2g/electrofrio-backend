<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePagoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'servicio_id'   => 'sometimes|required|exists:servicios,id',
            'monto'         => 'sometimes|required|numeric|min:0',
            'fecha_pago'    => 'sometimes|required|date',
            'metodo_pago'   => 'nullable|string|max:50',
            'estado'        => 'nullable|string|max:50',
            'observaciones' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'servicio_id.exists' => 'El servicio seleccionado no existe.',
            'monto.required'     => 'El monto es obligatorio.',
            'monto.numeric'      => 'El monto debe ser numérico.',
            'monto.min'          => 'El monto no puede ser negativo.',
            'fecha_pago.date'    => 'La fecha de pago no tiene un formato válido.',
        ];
    }
}
