<?php

declare(strict_types=1);

namespace App\Domains\Documents\Actions;

use App\Domains\Audit\AuditActor;
use App\Domains\Audit\AuditRecorder;
use App\Domains\Documents\DocumentType;
use App\Domains\Documents\Exceptions\DocumentTooLarge;
use App\Domains\Documents\Exceptions\UnsupportedDocumentType;
use App\Domains\Documents\Models\Document;
use App\Domains\Documents\ScanStatus;
use App\Domains\Documents\SignatureStatus;
use App\Integrations\Contracts\DocumentStorageGateway;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Almacenar un documento acreditativo aportado a un expediente.
 *
 * Imita el patrón transaccional de `SubmitApplication`, con una diferencia deliberada:
 * aquí la transacción de base de datos NO envuelve la escritura en el almacén de
 * objetos. Un almacén de objetos no participa en la transacción de PostgreSQL —no hay
 * forma de hacer un `COMMIT` conjunto entre los dos sistemas—, así que hay que decidir
 * conscientemente el orden y aceptar la clase de huérfano que puede quedar.
 *
 * EL ORDEN, Y POR QUÉ
 *
 *   1. Se valida el fichero (tipo MIME real, tamaño) ANTES de tocar nada persistente.
 *   2. Se calcula el hash y se busca un duplicado ya almacenado en el mismo expediente.
 *      Si existe, se devuelve sin volver a guardar el binario ni escribir una fila: la
 *      gente sube el mismo PDF varias veces «por si acaso», y duplicar el
 *      almacenamiento de un documento idéntico no aporta nada, sólo gasta espacio y
 *      confunde a quien revise el expediente.
 *   3. Se escribe el binario en el almacén de objetos, FUERA de la transacción.
 *   4. Se abre la transacción de base de datos: se inserta la fila del documento y se
 *      registra la auditoría. Los dos, o ninguno.
 *
 * LOS DOS HUÉRFANOS POSIBLES, Y CUÁL SE ACEPTA
 *
 * Si el paso 3 (almacén) falla, la excepción se propaga antes de que exista ninguna
 * transacción de base de datos: no se llega a escribir la fila. No queda un expediente
 * que apunte a un documento inexistente, que es el huérfano inaceptable —alguien abriría
 * el expediente, vería el documento listado y no podría descargarlo nunca.
 *
 * Si el paso 4 (base de datos) falla DESPUÉS de que el paso 3 haya tenido éxito, el
 * binario queda en el almacén sin ninguna fila que lo referencie. Éste es el huérfano
 * que se acepta: un fichero inaccesible por cualquier vía de la aplicación (no hay fila
 * ⇒ no hay `storage_key` conocido ⇒ nadie puede pedirlo), que un proceso de limpieza
 * periódico puede barrer comparando las claves existentes en el almacén contra las
 * `storage_key` de la tabla `documents`. Cuesta espacio en disco durante un tiempo
 * acotado; no cuesta la integridad del expediente.
 *
 * No se intenta compensar el fallo del paso 4 borrando el objeto recién subido: ese
 * borrado es una tercera operación de red que puede fallar por su cuenta, y perseguir
 * ese caso añade complejidad para evitar un huérfano que ya es aceptable por diseño.
 */
final readonly class StoreDocument
{
    /**
     * 10 MiB. Suficiente para un PDF escaneado a buena resolución o una fotografía de
     * un carnet; no tan grande como para que la subida de un documento se convierta en
     * un vector de agotamiento de disco. No hay un límite normativo que fije esta cifra:
     * es un valor operativo, ajustable si la práctica lo exige.
     */
    private const MAX_SIZE_BYTES = 10 * 1024 * 1024;

    /**
     * Lista blanca de tipos MIME reales admitidos. Cerrada a propósito: cada tipo que
     * se añade es una superficie nueva que el analizador antivirus y los visores
     * posteriores tienen que saber tratar.
     *
     * @var list<string>
     */
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/tiff',
    ];

    public function __construct(
        private ConnectionInterface $db,
        private DocumentStorageGateway $storage,
        private AuditRecorder $audit,
    ) {}

    /**
     * @throws DocumentTooLarge si el fichero supera el tamaño máximo admitido.
     * @throws UnsupportedDocumentType si el contenido real no está en la lista blanca.
     */
    public function __invoke(
        UploadedFile $file,
        DocumentType $documentType,
        string $documentableType,
        string $documentableId,
        AuditActor $actor,
        ?string $uploadedByType = null,
        ?string $uploadedById = null,
    ): Document {
        $path = $file->getRealPath();

        if ($path === false) {
            throw new RuntimeException('El fichero subido no tiene una ruta temporal legible.');
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException('No se pudo leer el contenido del fichero subido.');
        }

        $sizeBytes = strlen($contents);

        if ($sizeBytes > self::MAX_SIZE_BYTES) {
            throw new DocumentTooLarge($sizeBytes, self::MAX_SIZE_BYTES);
        }

        // El tipo MIME real se detecta del contenido, nunca de la extensión del nombre
        // ni de la cabecera Content-Type: ambas las controla quien sube el fichero.
        $mimeType = $this->detectRealMimeType($contents);

        if (! in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new UnsupportedDocumentType($mimeType);
        }

        $checksum = hash('sha256', $contents);

        $duplicate = $this->findDuplicate($documentableType, $documentableId, $checksum);

        if ($duplicate !== null) {
            return $duplicate;
        }

        // Fuera de la transacción: ver el bloque de comentarios de la clase.
        $storageKey = $this->storage->put($contents, $mimeType);

        return $this->db->transaction(function () use (
            $documentType,
            $documentableType,
            $documentableId,
            $uploadedByType,
            $uploadedById,
            $storageKey,
            $checksum,
            $mimeType,
            $sizeBytes,
            $actor,
        ): Document {
            // Repetir la comprobación de duplicado dentro de la transacción reduce —sin
            // eliminarla— la ventana de una carrera entre dos subidas concurrentes del
            // mismo contenido: no hay restricción única en la base que la cierre del
            // todo, y añadirla queda fuera del alcance de este módulo.
            $duplicate = $this->findDuplicate($documentableType, $documentableId, $checksum);

            if ($duplicate !== null) {
                return $duplicate;
            }

            $document = Document::query()->create([
                'documentable_type' => $documentableType,
                'documentable_id' => $documentableId,
                'uploaded_by_type' => $uploadedByType,
                'uploaded_by_id' => $uploadedById,
                'document_type' => $documentType,
                'storage_key' => $storageKey,
                'checksum_sha256' => $checksum,
                'mime_type' => $mimeType,
                'size_bytes' => $sizeBytes,
                'scan_status' => ScanStatus::Pending,
                'signature_status' => SignatureStatus::NotApplicable,
            ]);

            $this->audit->record(
                action: 'document.stored',
                entityType: 'document',
                entityId: $document->id,
                actor: $actor,
                metadata: [
                    'documentable_type' => $documentableType,
                    'documentable_id' => $documentableId,
                    'document_type' => $documentType->value,
                    'mime_type' => $mimeType,
                    'size_bytes' => $sizeBytes,
                    'checksum_sha256' => $checksum,
                ],
            );

            return $document;
        });
    }

    private function findDuplicate(string $documentableType, string $documentableId, string $checksum): ?Document
    {
        /** @var Document|null */
        return Document::query()
            ->where('documentable_type', $documentableType)
            ->where('documentable_id', $documentableId)
            ->where('checksum_sha256', $checksum)
            ->first();
    }

    private function detectRealMimeType(string $contents): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detected = $finfo->buffer($contents);

        return $detected === false ? 'application/octet-stream' : $detected;
    }
}
