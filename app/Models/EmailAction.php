<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable audit row. Use create() only — never update / delete.
 */
class EmailAction extends Model
{
    use BelongsToOrganization;
    use HasUlids;

    public const UPDATED_AT = null; // append-only

    public const ACTION_CREATE = 'create';

    public const ACTION_RESET_PASSWORD = 'reset_password';

    public const ACTION_SUSPEND = 'suspend';

    public const ACTION_UNSUSPEND = 'unsuspend';

    public const ACTION_DELETE = 'delete';

    public const ACTION_CHANGE_QUOTA = 'change_quota';

    public const ACTION_CONNECTION_CHECK = 'connection_check';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'organization_id', 'mail_server_id', 'email_account_id',
        'action', 'actor_user_id', 'actor_ip', 'status',
        'request_payload', 'response_payload', 'error_message',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function mailServer(): BelongsTo
    {
        return $this->belongsTo(MailServer::class);
    }

    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class);
    }
}
