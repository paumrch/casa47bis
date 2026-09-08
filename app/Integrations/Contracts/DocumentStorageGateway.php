<?php

declare(strict_types=1);

namespace App\Integrations\Contracts;

/**
 * Puerto de almacenamiento documental.
 *
 * Deliberadamente pequeño. Sólo usa el subconjunto común a todas las
 * implementaciones compatibles con S3, porque ahí es donde vive la portabilidad real:
 * las diferencias entre proveedores aparecen en las URL firmadas, las políticas de
 * ciclo de vida y el cifrado con clave propia. Este contrato evita esa superficie.
 *
 * La suite de pruebas se ejecuta contra DOS implementaciones distintas en integración
 * continua. La portabilidad se verifica; no se declara en un diagrama.
 */
interface DocumentStorageGateway
{
    /** @return string Clave de almacenamiento. */
    public function put(string $contents, string $mimeType): string;

    public function get(string $storageKey): string;

    /** URL firmada de vigencia corta. Nunca se expone el objeto directamente. */
    public function temporaryUrl(string $storageKey, \DateTimeInterface $expiresAt): string;

    public function delete(string $storageKey): void;

    public function exists(string $storageKey): bool;
}
