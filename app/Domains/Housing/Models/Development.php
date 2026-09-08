<?php

declare(strict_types=1);

namespace App\Domains\Housing\Models;

use App\Domains\Housing\ConstructionStatus;
use Database\Factories\DevelopmentFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Promoción: conjunto de viviendas de una misma actuación urbanística.
 * Es la unidad de agrupación geográfica del catálogo; el procedimiento de
 * adjudicación opera sobre `Property`, nunca sobre la promoción en sí.
 *
 * @property string $id
 * @property string $name
 * @property string|null $developer_name
 * @property ConstructionStatus $construction_status
 * @property string|null $address_line
 * @property string $municipality
 * @property string $province
 * @property string|null $postal_code
 * @property string|null $latitude
 * @property string|null $longitude
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, Property> $properties
 */
class Development extends Model
{
    /** @use HasFactory<DevelopmentFactory> */
    use HasFactory;

    protected $table = 'developments';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'developer_name',
        'construction_status',
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
            'construction_status' => ConstructionStatus::class,
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $development): void {
            if (! $development->getKey()) {
                $development->{$development->getKeyName()} = (string) Str::uuid7();
            }
        });
    }

    protected static function newFactory(): DevelopmentFactory
    {
        return DevelopmentFactory::new();
    }

    /**
     * @return HasMany<Property, $this>
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }
}
