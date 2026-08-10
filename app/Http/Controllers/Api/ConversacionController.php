<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Conversacion;
use App\Models\Mensaje;
use App\Models\User;
use App\Services\WhatsAppService;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ConversacionController extends Controller
{
    public function index(Request $request)
    {
        $usuario = $request->user();
        $buscar = trim((string) $request->query('buscar', ''));

        $conversaciones = $this->consultaAccesible($usuario)
            ->with([
                'cita.cliente',
                'cita.tecnico',
                'cita.equipo',
                'participantes:id,name,email,telefono,rol,tecnico_id',
                'ultimoMensaje.remitente:id,name',
            ])
            ->when($buscar, function (Builder $query) use ($buscar) {
                $query->where(function (Builder $subquery) use ($buscar) {
                    $subquery->where('asunto', 'like', "%{$buscar}%")
                        ->orWhereHas('cita.cliente', fn (Builder $q) => $q->where('nombre', 'like', "%{$buscar}%"));
                });
            })
            ->orderByDesc(DB::raw('COALESCE(ultimo_mensaje_at, created_at)'))
            ->limit(100)
            ->get();

        $conversaciones->each(function (Conversacion $conversacion) use ($usuario) {
            $conversacion->setAttribute('no_leidos', $this->contarNoLeidos($conversacion, $usuario));
        });

        return response()->json($conversaciones);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'cita_id' => ['nullable', 'exists:citas,id'],
            'asunto' => ['required', 'string', 'max:180'],
            'tipo' => ['nullable', Rule::in(['interna', 'whatsapp'])],
            'telefono_destino' => ['nullable', 'string', 'max:30'],
            'participantes' => ['nullable', 'array'],
            'participantes.*' => ['integer', 'exists:users,id'],
        ]);

        $usuario = $request->user();
        $cita = !empty($datos['cita_id']) ? Cita::findOrFail($datos['cita_id']) : null;
        $this->autorizarCreacion($usuario, $cita);
        $telefonoDestino = PhoneNumber::normalize(
            $datos['telefono_destino'] ?? $cita?->cliente?->telefono
        );

        if (($datos['tipo'] ?? 'interna') === 'whatsapp' && !$telefonoDestino) {
            throw ValidationException::withMessages([
                'telefono_destino' => ['Indica un teléfono válido para la conversación de WhatsApp.'],
            ]);
        }

        $conversacion = DB::transaction(function () use ($datos, $usuario, $cita, $telefonoDestino) {
            $conversacion = Conversacion::create([
                'cita_id' => $cita?->id,
                'creado_por' => $usuario->id,
                'asunto' => $datos['asunto'],
                'tipo' => $datos['tipo'] ?? 'interna',
                'canal_externo_id' => $telefonoDestino,
            ]);

            $participantes = collect($datos['participantes'] ?? [])
                ->push($usuario->id);

            if ($cita?->tecnico_id) {
                $participantes = $participantes->merge(
                    User::where('tecnico_id', $cita->tecnico_id)->pluck('id')
                );
            }

            $conversacion->participantes()->sync($participantes->unique()->values());

            return $conversacion;
        });

        return response()->json([
            'mensaje' => 'Conversación creada correctamente.',
            'conversacion' => $conversacion->load(['cita.cliente', 'participantes']),
        ], 201);
    }

    public function show(Request $request, Conversacion $conversacion)
    {
        $this->autorizarAcceso($request->user(), $conversacion);
        $despuesDe = (int) $request->query('despues_de', 0);

        $mensajes = $conversacion->mensajes()
            ->with('remitente:id,name,rol')
            ->when($despuesDe > 0, fn (Builder $query) => $query->where('id', '>', $despuesDe))
            ->orderBy('id')
            ->limit(200)
            ->get();

        return response()->json([
            'conversacion' => $conversacion->load([
                'cita.cliente',
                'cita.tecnico',
                'cita.equipo',
                'cita.pagos',
                'participantes:id,name,email,telefono,rol,tecnico_id',
            ]),
            'mensajes' => $mensajes,
        ]);
    }

    public function enviar(Request $request, Conversacion $conversacion, WhatsAppService $whatsApp)
    {
        $usuario = $request->user();
        $this->autorizarAcceso($usuario, $conversacion);

        $datos = $request->validate([
            'contenido' => ['nullable', 'string', 'max:5000', 'required_without:archivo'],
            'canal' => ['nullable', Rule::in(['interno', 'whatsapp', 'whatsapp_manual'])],
            'archivo' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx'],
        ]);

        $canal = $datos['canal'] ?? 'interno';
        $archivo = $request->file('archivo');
        $ruta = $archivo?->store("mensajes/{$conversacion->id}", 'public');
        $externoId = null;
        $metadata = null;
        $telefono = null;

        if (in_array($canal, ['whatsapp', 'whatsapp_manual'], true)) {
            abort_if(
                $archivo,
                422,
                'Para enviar un archivo, ábrelo directamente desde WhatsApp Business.'
            );
            $telefono = PhoneNumber::normalize(
                $conversacion->cita?->cliente?->telefono
                    ?? $conversacion->canal_externo_id
            );
            abort_unless($telefono, 422, 'La conversación no tiene un teléfono destino válido.');

            if ($canal === 'whatsapp') {
                $respuesta = $whatsApp->enviarTexto($telefono, $datos['contenido']);
                $externoId = data_get($respuesta, 'messages.0.id');
                $metadata = ['respuesta_meta' => $respuesta];
            } else {
                $metadata = [
                    'modo' => 'aplicacion',
                    'telefono' => $telefono,
                ];
            }

            if ($conversacion->canal_externo_id !== $telefono) {
                $conversacion->update(['canal_externo_id' => $telefono]);
            }
        }

        $mensaje = DB::transaction(function () use (
            $conversacion,
            $usuario,
            $datos,
            $canal,
            $archivo,
            $ruta,
            $externoId,
            $metadata
        ) {
            $mensaje = $conversacion->mensajes()->create([
                'remitente_id' => $usuario->id,
                'canal' => $canal,
                'contenido' => $datos['contenido'] ?? null,
                'archivo_ruta' => $ruta,
                'archivo_nombre' => $archivo?->getClientOriginalName(),
                'archivo_tipo' => $archivo?->getClientMimeType(),
                'mensaje_externo_id' => $externoId,
                'estado' => match ($canal) {
                    'whatsapp' => 'aceptado',
                    'whatsapp_manual' => 'preparado',
                    default => 'enviado',
                },
                'metadata' => $metadata,
                'enviado_at' => now(),
            ]);

            $conversacion->update(['ultimo_mensaje_at' => now()]);
            $this->marcarLeida($conversacion, $usuario);

            return $mensaje;
        });

        $whatsappUrl = $canal === 'whatsapp_manual'
            ? 'https://wa.me/'.preg_replace('/\D+/', '', (string) $telefono)
                .'?text='.rawurlencode((string) ($datos['contenido'] ?? ''))
            : null;

        return response()->json([
            'mensaje' => $canal === 'whatsapp_manual'
                ? 'Mensaje preparado para WhatsApp Business.'
                : 'Mensaje enviado.',
            'data' => $mensaje->load('remitente:id,name,rol'),
            'whatsapp_url' => $whatsappUrl,
        ], 201);
    }

    public function marcarComoLeida(Request $request, Conversacion $conversacion)
    {
        $this->autorizarAcceso($request->user(), $conversacion);
        $this->marcarLeida($conversacion, $request->user());

        return response()->json(['mensaje' => 'Conversación marcada como leída.']);
    }

    public function noLeidos(Request $request)
    {
        $usuario = $request->user();
        $total = $this->consultaAccesible($usuario)
            ->get()
            ->sum(fn (Conversacion $conversacion) => $this->contarNoLeidos($conversacion, $usuario));

        return response()->json(['total' => (int) $total]);
    }

    private function consultaAccesible(User $usuario): Builder
    {
        $query = Conversacion::query();

        if (in_array($usuario->rol, [User::ROL_ADMINISTRADOR, User::ROL_RECEPCION], true)) {
            return $query;
        }

        return $query->where(function (Builder $subquery) use ($usuario) {
            $subquery->whereHas('participantes', fn (Builder $q) => $q->where('users.id', $usuario->id));

            if ($usuario->tecnico_id) {
                $subquery->orWhereHas('cita', fn (Builder $q) => $q->where('tecnico_id', $usuario->tecnico_id));
            }
        });
    }

    private function autorizarCreacion(User $usuario, ?Cita $cita): void
    {
        if ($usuario->rol !== User::ROL_TECNICO) {
            return;
        }

        abort_unless($cita && $usuario->tecnico_id === $cita->tecnico_id, 403, 'Solo puedes crear conversaciones de tus atenciones.');
    }

    private function autorizarAcceso(User $usuario, Conversacion $conversacion): void
    {
        if (in_array($usuario->rol, [User::ROL_ADMINISTRADOR, User::ROL_RECEPCION], true)) {
            return;
        }

        $participa = $conversacion->participantes()->where('users.id', $usuario->id)->exists();
        $asignado = $usuario->tecnico_id
            && $conversacion->cita?->tecnico_id === $usuario->tecnico_id;

        abort_unless($participa || $asignado, 403, 'No tienes acceso a esta conversación.');
    }

    private function marcarLeida(Conversacion $conversacion, User $usuario): void
    {
        $conversacion->participantes()->syncWithoutDetaching([
            $usuario->id => ['leido_hasta_at' => now()],
        ]);
        $conversacion->participantes()->updateExistingPivot($usuario->id, ['leido_hasta_at' => now()]);
    }

    private function contarNoLeidos(Conversacion $conversacion, User $usuario): int
    {
        $pivot = DB::table('conversacion_usuario')
            ->where('conversacion_id', $conversacion->id)
            ->where('user_id', $usuario->id)
            ->first();

        $query = Mensaje::where('conversacion_id', $conversacion->id)
            ->where(function (Builder $q) use ($usuario) {
                $q->whereNull('remitente_id')->orWhere('remitente_id', '!=', $usuario->id);
            });

        if ($pivot?->leido_hasta_at) {
            $query->where('created_at', '>', $pivot->leido_hasta_at);
        }

        return $query->count();
    }
}
