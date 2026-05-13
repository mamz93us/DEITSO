<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Remote-access credentials for an Asset (TeamViewer, AnyDesk, RDP, etc.).
 *
 * The `password_encrypted` column is automatically encrypted at rest via the
 * Eloquent `encrypted` cast. The plaintext value never lives in the database
 * column or in activity-log payloads. Viewing the decrypted password requires
 * the `itam.asset.view_remote_credentials` permission (enforced in the UI).
 *
 * Tenant isolation: this model does not have its own organization_id column;
 * instead a `booted` hook scopes every read to assets whose organization_id
 * matches the active org. This blocks ULID-guessing attacks across tenants.
 */
class AssetRemoteAccess extends Model
{
    use HasUlids;
    use LogsActivity;

    protected $table = 'asset_remote_access';

    public const TYPE_TEAMVIEWER = 'teamviewer';

    public const TYPE_ANYDESK = 'anydesk';

    public const TYPE_RDP = 'rdp';

    public const TYPE_VNC = 'vnc';

    public const TYPE_SSH = 'ssh';

    public const TYPE_RUSTDESK = 'rustdesk';

    public const TYPE_OTHER = 'other';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'asset_id',
        'type',
        'identifier',
        'username',
        'password_encrypted',
        'port',
        'notes',
    ];

    protected $hidden = [
        'password_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'password_encrypted' => 'encrypted',
            'port' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Tenant-isolation scope: only return rows whose parent Asset is in
        // the active organization. Mirrors what BelongsToOrganization does
        // for org-scoped rows, but via a subquery against assets.organization_id.
        static::addGlobalScope('asset_organization', function (Builder $builder) {
            if (! app()->bound('current.organization')) {
                // CLI / system context: no filter.
                return;
            }

            $org = app('current.organization');
            if ($org === null) {
                return;
            }

            $orgId = is_object($org) ? $org->id : $org;
            $builder->whereExists(function ($q) use ($orgId) {
                $q->select(DB::raw(1))
                    ->from('assets')
                    ->whereColumn('assets.id', 'asset_remote_access.asset_id')
                    ->where('assets.organization_id', $orgId);
            });
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'identifier', 'username', 'port'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
