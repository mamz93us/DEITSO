<?php

declare(strict_types=1);

namespace App\Services\Cpanel;

use App\Models\MailServer;

/**
 * Interface for the WHM/cPanel UAPI client. Hand-rolled (not via the stale
 * gregoriohc/laravel-cpanel-whm package) so we can keep the surface area
 * minimal and auditable.
 *
 * Every method must return an array with at least:
 *   - status: 'success' | 'failed'
 *   - response: raw provider payload (sanitized — no passwords)
 *   - error?: string explanation if failed
 */
interface CpanelClientInterface
{
    public function ping(MailServer $server): array;

    public function createEmailAccount(MailServer $server, string $domain, string $localPart, string $password, ?int $quotaMb = null): array;

    public function resetPassword(MailServer $server, string $email, string $newPassword): array;

    public function changeQuota(MailServer $server, string $email, int $quotaMb): array;

    public function suspend(MailServer $server, string $email): array;

    public function unsuspend(MailServer $server, string $email): array;

    public function deleteAccount(MailServer $server, string $email): array;
}
