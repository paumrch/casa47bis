<?php

declare(strict_types=1);

namespace App\Http\Queries;

use Illuminate\Http\Request;

/**
 * Los filtros del catálogo, leídos de la cadena de consulta.
 *
 * Objeto de valor y no un array suelto porque estos valores vienen de fuera y no son de
 * fiar: se normalizan y se acotan aquí, una sola vez, en lugar de repetir comprobaciones
 * por la plantilla y el controlador.
 *
 * Cada filtro sabe además si está activo, que es lo que permite decirle al ciudadano
 * cuántos ha aplicado y ofrecerle quitarlos.
 */
final readonly class PropertyFilters
{
    private function __construct(
        public ?string $search,
        public ?string $municipality,
        public ?int $bedrooms,
        public ?int $maxRent,
        public bool $accessibleOnly,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            search: self::text($request->query('q')),
            municipality: self::text($request->query('municipio')),
            bedrooms: self::positiveInt($request->query('dormitorios'), max: 6),
            maxRent: self::positiveInt($request->query('renta_max'), max: 5000),
            accessibleOnly: $request->query('accesible') === '1',
        );
    }

    /** @return array<string, string> Sólo los filtros activos, para reconstruir la URL. */
    public function toQuery(): array
    {
        return array_filter([
            'q' => $this->search,
            'municipio' => $this->municipality,
            'dormitorios' => $this->bedrooms === null ? null : (string) $this->bedrooms,
            'renta_max' => $this->maxRent === null ? null : (string) $this->maxRent,
            'accesible' => $this->accessibleOnly ? '1' : null,
        ], static fn (?string $value): bool => $value !== null && $value !== '');
    }

    public function activeCount(): int
    {
        return count($this->toQuery());
    }

    public function any(): bool
    {
        return $this->activeCount() > 0;
    }

    private static function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        // Se recorta a 100 caracteres: nadie busca un municipio de 4 KB, y sí hay quien
        // prueba a ver qué pasa si lo intenta.
        $trimmed = mb_substr(trim($value), 0, 100);

        return $trimmed === '' ? null : $trimmed;
    }

    private static function positiveInt(mixed $value, int $max): ?int
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? min($int, $max) : null;
    }
}
