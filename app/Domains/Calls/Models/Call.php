<?php

declare(strict_types=1);

namespace App\Domains\Calls\Models;

use App\Domains\Calls\CallStatus;
use App\Domains\Housing\Models\Property;
use Database\Factories\CallFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

/**
 * Convocatoria de adjudicación: plazos, ámbito y versión de las reglas de
 * elegibilidad y baremo aplicables por defecto. Cada solicitud copia su
 * propia versión al presentarse, así que un cambio posterior aquí no
 * afecta a solicitudes ya presentadas.
 *
 * @property string $id
 * @property string $reference_code
 * @property string $name
 * @property string|null $scope_description
 * @property string $eligibility_rule_version
 * @property string $scoring_rule_version
 * @property array<string, mixed>|null $quotas
 * @property Carbon $opens_at
 * @property Carbon $closes_at
 * @property CallStatus $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Property> $properties
 */
class Call extends Model
{
    /** @use HasFactory<CallFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'calls';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'reference_code',
        'name',
        'scope_description',
        'eligibility_rule_version',
        'scoring_rule_version',
        'quotas',
        'opens_at',
        'closes_at',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quotas' => 'array',
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
            'status' => CallStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $call): void {
            if (! $call->getKey()) {
                $call->{$call->getKeyName()} = (string) Str::uuid7();
            }
        });
    }

    protected static function newFactory(): CallFactory
    {
        return CallFactory::new();
    }

    /**
     * @return BelongsToMany<Property, $this>
     */
    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'call_property')->withTimestamps();
    }

    /**
     * Está abierta a presentar solicitudes: su estado administrativo es
     * `open` y el instante actual está dentro del plazo, ambos extremos
     * incluidos.
     */
    public function isOpen(): bool
    {
        $now = Date::now();

        return $this->status === CallStatus::Open
            && $now->greaterThanOrEqualTo($this->opens_at)
            && $now->lessThanOrEqualTo($this->closes_at);
    }

    /**
     * El plazo de presentación ya ha vencido, tanto si el estado
     * administrativo se ha actualizado como si todavía no lo ha hecho.
     */
    public function hasClosed(): bool
    {
        return Date::now()->greaterThan($this->closes_at);
    }

    /**
     * Días naturales que restan hasta el cierre, redondeados al alza.
     * `null` si la convocatoria no está abierta ahora mismo.
     */
    public function daysRemaining(): ?int
    {
        if (! $this->isOpen()) {
            return null;
        }

        $secondsRemaining = Date::now()->diffInSeconds($this->closes_at, absolute: true);

        return (int) ceil($secondsRemaining / 86400);
    }
}
