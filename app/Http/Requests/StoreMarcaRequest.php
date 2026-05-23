<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMarcaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $marcaId = $this->route('id') ?? $this->route('marca');
        $tipo = $this->input('tipo', 'split');

        return [
            'nombre' => [
                'required', 'string', 'max:255',
                Rule::unique('marcas', 'nombre')->where(fn ($query) => $query->where('tipo', $tipo))->ignore($marcaId),
            ],
            'tipo' => 'nullable|string|max:100',
            'logo' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la marca es obligatorio.',
            'nombre.unique'   => 'Esta marca ya está registrada para ese tipo de equipo.',
        ];
    }
}
