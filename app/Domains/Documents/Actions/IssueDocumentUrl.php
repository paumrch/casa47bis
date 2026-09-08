<?php

declare(strict_types=1);

namespace App\Domains\Documents\Actions;

use App\Domains\Audit\AuditActor;
use App\Domains\Audit\AuditRecorder;
use App\Domains\Documents\Exceptions\DocumentAccessDenied;
use App\Domains\Documents\Exceptions\DocumentNotScanned;
use App\Domains\Documents\Models\Document;
use App\Integrations\Contracts\DocumentStorageGateway;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;

/**
 * Emitir una URL firmada de descarga para un documento acreditativo.
 *
 * SE AUDITA LA EMISIÓN, NO LA DESCARGA
 *
 * La URL firmada, una vez emitida, se sirve directamente por el almacén de objetos: la
 * aplicación no está en medio de esa petición HTTP y no puede saber si el navegador
 * llegó a descargar el binario, si la petición se canceló a mitad, o si la URL se abrió
 * dos veces desde la vista previa y la descarga real. Auditar «se descargó» exigiría un
 * evento que el almacén de objetos no ofrece de forma portable -ver el contrato
 * `DocumentStorageGateway»- y que además no sería fiable.
 *
 * Lo que SÍ es un hecho preciso, ocurrido dentro de esta aplicación y en este instante,
 * es que alguien con derecho de acceso pidió y obtuvo la capacidad de descargar el
 * documento durante los próximos minutos. Eso es lo que se audita, y es información
 * suficiente para reconstruir quién pudo haber visto un documento y cuándo.
 */
final readonly class IssueDocumentUrl
{
    /**
     * Vigencia corta a propósito: minutos, no horas. Una URL firmada de larga duración
     * es, en la práctica, un enlace público mientras no caduque -cualquiera que la
     * intercepte o la reenvíe puede usarla sin volver a pasar por esta comprobación de
     * acceso-.
     */
    private const TTL_MINUTES = 5;

    public function __construct(
        private ConnectionInterface $db,
        private DocumentStorageGateway $storage,
        private AuditRecorder $audit,
    ) {}

    /**
     * @throws DocumentAccessDenied si el solicitante no tiene derecho a este documento.
     * @throws DocumentNotScanned si el documento no está en estado `Clean`.
     */
    public function __invoke(Document $document, AuditActor $requester): string
    {
        if (! $this->isAuthorized($document, $requester)) {
            throw new DocumentAccessDenied($document->id);
        }

        if (! $document->isDownloadable()) {
            throw new DocumentNotScanned($document->scan_status);
        }

        $expiresAt = Carbon::now()->addMinutes(self::TTL_MINUTES);

        return $this->db->transaction(function () use ($document, $requester, $expiresAt): string {
            $url = $this->storage->temporaryUrl($document->storage_key, $expiresAt);

            $this->audit->record(
                action: 'document.url_issued',
                entityType: 'document',
                entityId: $document->id,
                actor: $requester,
                metadata: [
                    'expires_at' => $expiresAt->toIso8601String(),
                    'ttl_minutes' => self::TTL_MINUTES,
                ],
            );

            return $url;
        });
    }

    /**
     * Quién tiene derecho a pedir la URL de un documento.
     *
     * El personal gestor (`staff_user`) y el propio sistema tienen acceso a cualquier
     * documento: la comprobación de si un gestor concreto debe ver un expediente
     * concreto pertenece a la capa de autorización de la aplicación, no a este módulo.
     * Un solicitante (`account`) sólo tiene derecho al documento que él mismo subió:
     * este módulo no conoce la relación entre una solicitud y la unidad de convivencia
     * que la presentó, así que no puede (ni debe) resolver aquí un caso más amplio.
     */
    private function isAuthorized(Document $document, AuditActor $requester): bool
    {
        if ($requester->type === null || $requester->type === 'staff_user') {
            return true;
        }

        return $requester->type === $document->uploaded_by_type
            && $requester->id === $document->uploaded_by_id;
    }
}
