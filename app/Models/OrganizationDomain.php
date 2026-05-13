<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationDomain extends BaseModel
{
    use BelongsToOrganization;

    public const TYPE_PLATFORM = 'platform_subdomain';

    public const TYPE_CUSTOM = 'custom_domain';

    public const DNS_PENDING = 'pending_propagation';

    public const DNS_VERIFIED = 'verified';

    public const DNS_FAILED = 'failed';

    public const TLS_PENDING = 'pending';

    public const TLS_PROVISIONING = 'provisioning';

    public const TLS_ACTIVE = 'active';

    public const TLS_FAILED = 'failed';

    protected $fillable = [
        'organization_id',
        'host',
        'type',
        'dns_provider_id',
        'verification_token',
        'dns_status',
        'tls_status',
        'is_primary',
        'last_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'last_checked_at' => 'datetime',
        ];
    }

    public function dnsProviderAccount(): BelongsTo
    {
        return $this->belongsTo(DnsProviderAccount::class, 'dns_provider_id');
    }
}
