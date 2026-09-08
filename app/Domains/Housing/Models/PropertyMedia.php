<?php

declare(strict_types=1);

namespace App\Domains\Housing\Models;

use App\Domains\Housing\PropertyMediaType;
use Database\Factories\PropertyMediaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Fotografía, plano o tour virtual de una vivienda. Sólo guarda metadatos
 * y la referencia de almacenamiento; el binario vive fuera de la base de
 * datos.
 *
 * @property string $id
 * @property string $property_id
 * @property PropertyMediaType $media_type
 * @property string $storage_key
 * @property int $position
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Property $property
 */
class PropertyMedia extends Model
{
    /** @use HasFactory<PropertyMediaFactory> */
    use HasFactory;

    protected $table = 'property_media';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'property_id',
        'media_type',
        'storage_key',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'media_type' => PropertyMediaType::class,
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $media): void {
            if (! $media->getKey()) {
                $media->{$media->getKeyName()} = (string) Str::uuid7();
            }
        });
    }

    protected static function newFactory(): PropertyMediaFactory
    {
        return PropertyMediaFactory::new();
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
