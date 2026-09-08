<?php

declare(strict_types=1);

namespace App\Domains\Housing\Models;

use App\Domains\Housing\PropertyStatus;
use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Vivienda concreta del parque público: la unidad real sobre la que se
 * presenta, bareme y adjudica una solicitud. Se conserva con borrado
 * lógico porque su histórico importa aunque deje de ofertarse.
 *
 * @property string $id
 * @property string $development_id
 * @property string $reference_code
 * @property int $bedrooms
 * @property int $bathrooms
 * @property string $surface_m2
 * @property string|null $floor
 * @property bool $has_elevator
 * @property bool $accessible
 * @property bool $has_garage
 * @property bool $has_storage_room
 * @property string $monthly_rent
 * @property PropertyStatus $status
 * @property string|null $address_line
 * @property string $municipality
 * @property string $province
 * @property string|null $postal_code
 * @property string|null $latitude
 * @property string|null $longitude
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Development $development
 * @property-read Collection<int, PropertyMedia> $media
 */
class Property extends Model
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'properties';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'development_id',
        'reference_code',
        'bedrooms',
        'bathrooms',
        'surface_m2',
        'floor',
        'has_elevator',
        'accessible',
        'has_garage',
        'has_storage_room',
        'monthly_rent',
        'status',
        'address_line',
        'municipality',
        'province',
        'postal_code',
        'latitude',
        'longitude',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bedrooms' => 'integer',
            'bathrooms' => 'integer',
            'surface_m2' => 'decimal:2',
            'has_elevator' => 'boolean',
            'accessible' => 'boolean',
            'has_garage' => 'boolean',
            'has_storage_room' => 'boolean',
            'monthly_rent' => 'decimal:2',
            'status' => PropertyStatus::class,
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $property): void {
            if (! $property->getKey()) {
                $property->{$property->getKeyName()} = (string) Str::uuid7();
            }
        });
    }

    protected static function newFactory(): PropertyFactory
    {
        return PropertyFactory::new();
    }

    /**
     * @return BelongsTo<Development, $this>
     */
    public function development(): BelongsTo
    {
        return $this->belongsTo(Development::class);
    }

    /**
     * @return HasMany<PropertyMedia, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(PropertyMedia::class);
    }

    /*
     * NO existe aquí una relación `calls()`.
     *
     * La tenía, y deptrac la rechazó: creaba un ciclo entre el catálogo y las
     * convocatorias, y dos módulos que se importan mutuamente no son dos módulos.
     *
     * La dirección correcta es la que refleja el procedimiento: una convocatoria
     * incluye viviendas; una vivienda no necesita saber en qué convocatorias entró.
     * Cuando haga falta esa consulta, se hace desde el otro lado:
     *
     *     Call::query()->whereHas('properties', fn ($q) => $q->whereKey($propertyId));
     *
     * Se deja escrito porque es un ejemplo de la regla funcionando: la frontera no se
     * respetó por disciplina, se respetó porque la integración continua se puso en rojo.
     */

    /**
     * Filtro de catálogo: municipio exacto.
     *
     * @param  Builder<Property>  $query
     * @return Builder<Property>
     */
    public function scopeInMunicipality(Builder $query, string $municipality): Builder
    {
        return $query->where('municipality', $municipality);
    }

    /**
     * Filtro de catálogo: número exacto de dormitorios.
     *
     * @param  Builder<Property>  $query
     * @return Builder<Property>
     */
    public function scopeWithBedrooms(Builder $query, int $bedrooms): Builder
    {
        return $query->where('bedrooms', $bedrooms);
    }

    /**
     * Filtro de catálogo: renta mensual no superior al límite dado.
     *
     * @param  Builder<Property>  $query
     * @return Builder<Property>
     */
    public function scopeWithMaxRent(Builder $query, string|float $maxRent): Builder
    {
        return $query->where('monthly_rent', '<=', $maxRent);
    }

    /**
     * Filtro de catálogo: sólo viviendas accesibles.
     *
     * @param  Builder<Property>  $query
     * @return Builder<Property>
     */
    public function scopeAccessible(Builder $query): Builder
    {
        return $query->where('accessible', true);
    }

    /**
     * Filtro de catálogo: sólo viviendas disponibles para solicitud.
     *
     * @param  Builder<Property>  $query
     * @return Builder<Property>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', PropertyStatus::Available);
    }

    /**
     * Búsqueda textual tolerante a erratas sobre municipio y referencia
     * catastral, mediante `pg_trgm` (operador `%`, ya habilitado en la
     * migración de `properties`).
     *
     * @param  Builder<Property>  $query
     * @return Builder<Property>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $query) use ($term): void {
            $query->whereRaw('municipality % ?', [$term])
                ->orWhereRaw('reference_code % ?', [$term]);
        });
    }
}
