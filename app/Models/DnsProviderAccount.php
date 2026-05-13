<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\EncryptedJson;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A system-level DNS provider configuration (not org-scoped). Stores credentials
 * encrypted at rest. Only system admins manage these.
 */
class DnsProviderAccount extends Model
{
    use HasUlids;
    use LogsActivity;
    use SoftDeletes;

    public const PROVIDER_GODADDY = 'godaddy';

    public const PROVIDER_CLOUDFLARE = 'cloudflare';

    public const PROVIDER_ROUTE53 = 'route53';

    public const ENV_PRODUCTION = 'production';

    public const ENV_OTE = 'ote';

    public const ENV_SANDBOX = 'sandbox';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'provider',
        'name',
        'base_domain',
        'credentials_encrypted',
        'environment',
        'is_default',
        'status',
        'last_check_at',
    ];

    protected $hidden = [
        'credentials_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'credentials_encrypted' => EncryptedJson::class,
            'is_default' => 'boolean',
            'last_check_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        // Never log the encrypted credentials blob — log everything else.
        return LogOptions::defaults()
            ->logOnly(['provider', 'name', 'base_domain', 'environment', 'is_default', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
