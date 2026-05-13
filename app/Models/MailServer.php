<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MailServer extends BaseModel
{
    use BelongsToOrganization;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DISABLED = 'disabled';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'organization_id', 'name', 'hostname', 'username',
        'api_token_encrypted', 'port', 'status',
        'last_connection_check_at', 'last_connection_status',
    ];

    protected $hidden = [
        'api_token_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'api_token_encrypted' => 'encrypted',
            'port' => 'integer',
            'last_connection_check_at' => 'datetime',
        ];
    }

    public function domains(): HasMany
    {
        return $this->hasMany(EmailDomain::class);
    }
}
