<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Audit;

use App\Domains\Audit\AuditActor;
use App\Domains\Audit\AuditRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * La traza de auditoría detecta que la han tocado.
 *
 * Una tabla de auditoría corriente demuestra qué pasó, pero no demuestra que no se haya
 * modificado después. Si alguien con acceso a la base borra la fila que registra un
 * acceso indebido a los datos de salud de un solicitante, la tabla queda perfectamente
 * coherente y nadie lo sabrá nunca.
 *
 * Encadenando cada fila con el hash de la anterior, cualquier borrado o modificación
 * rompe la cadena en ese punto. Esto es lo que exige el ENS sobre integridad de la traza,
 * y en un sistema que decide quién accede a una vivienda pública es además lo mínimo.
 */
final class CadenaDeAuditoriaTest extends TestCase
{
    use RefreshDatabase;

    private function grabar(string $action = 'application.viewed'): string
    {
        return DB::transaction(
            fn (): string => (new AuditRecorder(DB::connection()))->record(
                $action,
                'application',
                (string) Str::uuid7(),
                AuditActor::staff((string) Str::uuid7(), '192.0.2.1'),
                ['motivo' => 'revisión de expediente'],
            )
        );
    }

    private function recorder(): AuditRecorder
    {
        return new AuditRecorder(DB::connection());
    }

    #[Test]
    public function una_cadena_intacta_se_verifica_sin_incidencias(): void
    {
        foreach (range(1, 5) as $ignored) {
            $this->grabar();
        }

        $this->assertNull(
            $this->recorder()->verifyChain(),
            'Una cadena recién escrita debe verificarse sin errores.',
        );
    }

    #[Test]
    public function el_primer_eslabon_no_tiene_anterior_y_los_demas_si(): void
    {
        $primero = $this->grabar();
        $segundo = $this->grabar();

        $this->assertNull(DB::table('audit_events')->where('id', $primero)->value('previous_hash'));

        $this->assertSame(
            DB::table('audit_events')->where('id', $primero)->value('hash'),
            DB::table('audit_events')->where('id', $segundo)->value('previous_hash'),
        );
    }

    #[Test]
    public function modificar_un_evento_rompe_la_cadena_y_se_detecta(): void
    {
        $this->grabar();
        $manipulado = $this->grabar('application.approved');
        $this->grabar();

        // Alguien reescribe la acción registrada para que parezca otra cosa.
        DB::table('audit_events')
            ->where('id', $manipulado)
            ->update(['action' => 'application.viewed']);

        $this->assertSame(
            $manipulado,
            $this->recorder()->verifyChain(),
            'La verificación debe señalar exactamente el evento manipulado.',
        );
    }

    #[Test]
    public function borrar_un_evento_intermedio_tambien_se_detecta(): void
    {
        $this->grabar();
        $borrado = $this->grabar();
        $this->grabar();

        DB::table('audit_events')->where('id', $borrado)->delete();

        $this->assertNotNull(
            $this->recorder()->verifyChain(),
            'Borrar un eslabón deja huérfano al siguiente y debe detectarse.',
        );
    }

    #[Test]
    public function el_evento_registra_quien_desde_donde_y_con_que_motivo(): void
    {
        $id = $this->grabar();

        $event = DB::table('audit_events')->where('id', $id)->first();

        $this->assertNotNull($event);
        $this->assertSame('staff_user', $event->actor_type);
        $this->assertSame('192.0.2.1', $event->ip_address);
        $this->assertStringContainsString('revisión de expediente', (string) $event->metadata);
    }

    #[Test]
    public function una_accion_del_sistema_no_se_atribuye_a_ninguna_persona(): void
    {
        $id = DB::transaction(
            fn (): string => $this->recorder()->record(
                'application.expired',
                'application',
                (string) Str::uuid7(),
                AuditActor::system(),
            )
        );

        $event = DB::table('audit_events')->where('id', $id)->first();

        $this->assertNotNull($event);
        $this->assertNull($event->actor_type);
        $this->assertNull($event->actor_id);
    }
}
