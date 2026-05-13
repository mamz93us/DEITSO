<?php

declare(strict_types=1);

namespace App\Actions\Email;

use App\Models\EmailAccount;
use App\Models\EmailAction;
use App\Models\EmailDomain;
use App\Services\Cpanel\CpanelClientInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Creates an email account on the WHM/cPanel server AND writes a corresponding
 * EmailAccount row + audit log entry. The password is generated server-side
 * (24-char strong, no symbols for shell-paste safety) and surfaced to the
 * caller exactly once — it is never stored.
 */
class CreateEmailAccount
{
    public function __construct(private CpanelClientInterface $cpanel) {}

    /**
     * @return array{account: EmailAccount, one_time_password: string}
     */
    public function __invoke(EmailDomain $domain, string $localPart, ?int $quotaMb = null, ?string $employeeId = null): array
    {
        $password = Str::password(24, symbols: false);
        $email = "{$localPart}@{$domain->domain}";

        $server = $domain->mailServer;
        if (! $server) {
            throw new RuntimeException('Email domain has no mail server linked.');
        }

        $result = $this->cpanel->createEmailAccount($server, $domain->domain, $localPart, $password, $quotaMb);

        // Failure: write the audit row OUTSIDE any transaction so it survives,
        // then throw. Auditing the failure is mandatory for security review.
        if ($result['status'] === 'failed') {
            EmailAction::create([
                'organization_id' => $domain->organization_id,
                'mail_server_id' => $server->id,
                'action' => EmailAction::ACTION_CREATE,
                'actor_user_id' => Auth::id(),
                'actor_ip' => Request::ip(),
                'status' => EmailAction::STATUS_FAILED,
                'request_payload' => ['domain' => $domain->domain, 'local_part' => $localPart, 'quota_mb' => $quotaMb],
                'response_payload' => $result['response'],
                'error_message' => $result['error'] ?? 'cpanel returned failure',
            ]);

            throw new RuntimeException('Failed to create email account: '.($result['error'] ?? 'unknown'));
        }

        // Success: account + success audit row in one transaction.
        return DB::transaction(function () use ($domain, $server, $localPart, $email, $quotaMb, $employeeId, $password, $result) {
            $account = EmailAccount::create([
                'email_domain_id' => $domain->id,
                'organization_id' => $domain->organization_id,
                'local_part' => $localPart,
                'full_address' => $email,
                'quota_mb' => $quotaMb,
                'assigned_employee_id' => $employeeId,
                'status' => EmailAccount::STATUS_ACTIVE,
                'last_sync_at' => now(),
            ]);

            EmailAction::create([
                'organization_id' => $domain->organization_id,
                'mail_server_id' => $server->id,
                'email_account_id' => $account->id,
                'action' => EmailAction::ACTION_CREATE,
                'actor_user_id' => Auth::id(),
                'actor_ip' => Request::ip(),
                'status' => EmailAction::STATUS_SUCCESS,
                'request_payload' => ['domain' => $domain->domain, 'local_part' => $localPart, 'quota_mb' => $quotaMb],
                'response_payload' => $result['response'],
            ]);

            return ['account' => $account, 'one_time_password' => $password];
        });
    }
}
