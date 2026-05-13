<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 * Not org-scoped via trait because rows are reached through Asset and inherit
 * its org via the asset_id FK — the AssetResource's nav already enforces scope.
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
            // Laravel's built-in encrypted cast (Crypt::encryptString round-trip).
            'password_encrypted' => 'encrypted',
            'port' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        // Never log the plaintext password — only the fact that it changed.
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
