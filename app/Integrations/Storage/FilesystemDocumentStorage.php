<?php

declare(strict_types=1);

namespace App\Integrations\Storage;

use App\Integrations\Contracts\DocumentStorageGateway;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Almacenamiento documental sobre la abstracción de discos de Laravel.
 *
 * Un único adaptador sirve para MinIO, para el almacenamiento de objetos de cualquier
 * proveedor y para un disco local. No hay una clase por proveedor porque no hace falta:
 * el contrato de §10.4 se limita al subconjunto común, que es exactamente donde vive la
 * portabilidad real.
 *
 * Esta clase se ejecuta contra DOS implementaciones distintas en cada integración
 * continua. Si alguien introdujera aquí una llamada específica de un proveedor, una de
 * las dos ejecuciones fallaría y nadie podría subirla. Ésa es toda la idea: la
 * portabilidad no se declara en un diagrama, se rompe la CI cuando se pierde.
 */
final readonly class FilesystemDocumentStorage implements DocumentStorageGateway
{
    public function __construct(private Filesystem $disk) {}

    public function put(string $contents, string $mimeType): string
    {
        $key = $this->generateKey($mimeType);

        // La escritura de un documento acreditativo no puede fallar en silencio: si
        // no se guarda la nómina de alguien, hay que enterarse ahora, no cuando un
        // gestor abra el expediente y no encuentre nada.
        if ($this->disk->put($key, $contents) === false) {
            throw new RuntimeException("No se pudo almacenar el documento con clave «{$key}».");
        }

        return $key;
    }

    public function get(string $storageKey): string
    {
        $contents = $this->disk->get($storageKey);

        if ($contents === null) {
            throw new RuntimeException("No existe ningún documento con clave «{$storageKey}».");
        }

        return $contents;
    }

    public function temporaryUrl(string $storageKey, \DateTimeInterface $expiresAt): string
    {
        // Los documentos nunca se sirven desde una URL permanente ni a través de la
        // aplicación: se entrega una URL firmada de vigencia corta.
        return $this->disk->temporaryUrl($storageKey, $expiresAt);
    }

    public function delete(string $storageKey): void
    {
        $this->disk->delete($storageKey);
    }

    public function exists(string $storageKey): bool
    {
        return $this->disk->exists($storageKey);
    }

    /**
     * Clave opaca, particionada por fecha.
     *
     * No lleva el nombre original del fichero ni ningún dato de la persona: el nombre
     * que sube un ciudadano puede contener su NIF, y las claves acaban en registros,
     * en trazas y en incidencias.
     */
    private function generateKey(string $mimeType): string
    {
        return sprintf(
            'documents/%s/%s%s',
            now()->format('Y/m'),
            (string) Str::uuid7(),
            $this->extensionFor($mimeType),
        );
    }

    private function extensionFor(string $mimeType): string
    {
        return match ($mimeType) {
            'application/pdf' => '.pdf',
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
            'image/tiff' => '.tif',
            default => '.bin',
        };
    }
}
