<?php

declare(strict_types=1);

namespace App\Domains\Documents\Models;

use App\Domains\Documents\DocumentType;
use App\Domains\Documents\ScanStatus;
use App\Domains\Documents\SignatureStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Documento acreditativo aportado a un expediente: metadatos, hash, referencia de
 * almacenamiento, estado de análisis y de firma. NUNCA el binario, que vive en
 * almacenamiento externo a través de `DocumentStorageGateway` (§9, decisión 9).
 *
 * `documentable` es una relación polimórfica porque un documento puede adjuntarse a una
 * solicitud, una incidencia o, en el futuro, otra entidad, sin necesitar una clave
 * foránea distinta por cada caso. `uploadedBy` también es polimórfica porque quien sube
 * un documento puede ser el propio solicitante (`account`) o un gestor (`staff_user`).
 *
 * @property string $id
 * @property string $documentable_type
 * @property string $documentable_id
 * @property string|null $uploaded_by_type
 * @property string|null $uploaded_by_id
 * @property DocumentType $document_type
 * @property string $storage_key
 * @property string $checksum_sha256
 * @property string $mime_type
 * @property int $size_bytes
 * @property ScanStatus $scan_status
 * @property SignatureStatus $signature_status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Model $documentable
 * @property-read Model|null $uploadedBy
 */
final class Document extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected static function booted(): void
    {
        self::creating(static function (self $document): void {
            $document->id ??= (string) Str::uuid7();
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'scan_status' => ScanStatus::class,
            'signature_status' => SignatureStatus::class,
            'size_bytes' => 'integer',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return MorphTo<Model, $this> */
    public function uploadedBy(): MorphTo
    {
        return $this->morphTo();
    }

    /** Si el estado de análisis actual permite servir el binario. */
    public function isDownloadable(): bool
    {
        return $this->scan_status->isDownloadable();
    }
}
