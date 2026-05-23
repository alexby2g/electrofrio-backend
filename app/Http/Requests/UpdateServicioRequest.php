<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServicioRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'cliente_id'     => 'sometimes|required|exists:clientes,id',
            'equipo_id'      => 'sometimes|required|exists:equipos,id',
            'tecnico_id'     => 'sometimes|required|exists:tecnicos,id',
            'tipo_servicio'  => 'sometimes|required|string|max:150',
            'descripcion'    => 'nullable|string',
            'fecha'          => 'sometimes|required|date',
            'hora'           => 'nullable|date_format:H:i',
            'costo'          => 'nullable|numeric|min:0',
            'estado'         => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.exists'      => 'El cliente seleccionado no existe.',
            'equipo_id.exists'       => 'El equipo seleccionado no existe.',
            'tecnico_id.exists'      => 'El técnico seleccionado no existe.',
            'tipo_servicio.required' => 'El tipo de servicio es obligatorio.',
            'fecha.required'         => 'La fecha del servicio es requerida.',
            'hora.date_format'       => 'La hora debe tener formato HH:MM.',
            'costo.numeric'          => 'El costo debe ser numérico.',
            'costo.min'              => 'El costo no puede ser negativo.',
        ];
    }
}
