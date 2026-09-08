<?php

declare(strict_types=1);

namespace App\Domains\Applications\Models;

use App\Domains\Applications\ApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Solicitud de vivienda: la entidad central del sistema.
 *
 * Los campos `household_snapshot_*` no son una desnormalización por rendimiento. Son una
 * instantánea deliberada de la unidad de convivencia en el instante de presentar, porque
 * la solicitud debe evaluarse con los datos de entonces aunque la unidad cambie después.
 * Sin eso, resolver un recurso dos años más tarde es imposible.
 *
 * @property string $id
 * @property string $call_id
 * @property string $household_id
 * @property string $applicant_document_hash
 * @property ApplicationStatus $status
 * @property string $eligibility_rule_version
 * @property string $scoring_rule_version
 * @property Carbon|null $submitted_at
 * @property string $household_snapshot_reference_code
 * @property int $household_snapshot_member_count
 * @property array<int, array<string, mixed>> $household_snapshot_members
 */
final class Application extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected static function booted(): void
    {
        self::creating(static function (self $application): void {
            $application->id ??= (string) Str::uuid7();
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'submitted_at' => 'immutable_datetime',
            'household_snapshot_members' => 'array',
            'household_snapshot_member_count' => 'integer',
        ];
    }
}
