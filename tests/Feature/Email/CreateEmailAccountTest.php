<?php

declare(strict_types=1);

use App\Actions\Email\CreateEmailAccount;
use App\Models\EmailAccount;
use App\Models\EmailAction;
use App\Models\EmailDomain;
use App\Models\MailServer;
use App\Models\Organization;
use App\Services\Cpanel\CpanelClientInterface;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    (new PermissionSeeder)->setContainer(app())->run();
});

function fakeCpanelClient(array $createReturn): CpanelClientInterface
{
    return new class($createReturn) implements CpanelClientInterface
    {
        public function __construct(private array $createReturn) {}

        public function ping(MailServer $s): array
        {
            return ['status' => 'success', 'response' => []];
        }

        public function createEmailAccount(MailServer $s, string $d, string $l, string $p, ?int $q = null): array
        {
            return $this->createReturn;
        }

        public function resetPassword(MailServer $s, string $email, string $newPassword): array
        {
            return ['status' => 'success', 'response' => []];
        }

        public function changeQuota(MailServer $s, string $email, int $quotaMb): array
        {
            return ['status' => 'success', 'response' => []];
        }

        public function suspend(MailServer $s, string $email): array
        {
            return ['status' => 'success', 'response' => []];
        }

        public function unsuspend(MailServer $s, string $email): array
        {
            return ['status' => 'success', 'response' => []];
        }

        public function deleteAccount(MailServer $s, string $email): array
        {
            return ['status' => 'success', 'response' => []];
        }
    };
}

function bootstrapEmailEnv(): array
{
    $org = Organization::create(['slug' => 'mailco', 'name' => ['en' => 'Mail'], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $server = MailServer::create([
        'organization_id' => $org->id,
        'name' => 'Primary',
        'hostname' => 'mail.example.test',
        'username' => 'root',
        'api_token_encrypted' => 'token-abc',
        'port' => 2087,
        'status' => MailServer::STATUS_ACTIVE,
    ]);

    $domain = EmailDomain::create([
        'mail_server_id' => $server->id,
        'organization_id' => $org->id,
        'domain' => 'example.test',
    ]);

    return ['org' => $org, 'server' => $server, 'domain' => $domain];
}

it('creates an email account, returns a one-time password, never persists it', function () {
    ['domain' => $domain] = bootstrapEmailEnv();

    app()->instance(CpanelClientInterface::class, fakeCpanelClient([
        'status' => 'success',
        'response' => ['cpanelresult' => ['event' => ['result' => 1]]],
    ]));

    $result = app(CreateEmailAccount::class)($domain, 'alice', 1024);

    expect($result['one_time_password'])->toBeString()
        ->and(strlen($result['one_time_password']))->toBeGreaterThanOrEqual(20)
        ->and($result['account'])->toBeInstanceOf(EmailAccount::class)
        ->and($result['account']->full_address)->toBe('alice@example.test')
        ->and($result['account']->status)->toBe(EmailAccount::STATUS_ACTIVE);

    // EmailAction audit row written.
    $audit = EmailAction::query()
        ->where('action', EmailAction::ACTION_CREATE)
        ->where('status', EmailAction::STATUS_SUCCESS)
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->request_payload['local_part'])->toBe('alice');

    // The audit row's request_payload must NOT contain the password.
    $raw = DB::table('email_actions')->where('id', $audit->id)->value('request_payload');
    expect($raw)->not->toContain($result['one_time_password']);
});

it('writes a failure audit row and throws when cpanel rejects the request', function () {
    ['domain' => $domain] = bootstrapEmailEnv();

    app()->instance(CpanelClientInterface::class, fakeCpanelClient([
        'status' => 'failed',
        'response' => null,
        'error' => 'quota exceeded',
    ]));

    expect(fn () => app(CreateEmailAccount::class)($domain, 'bob'))
        ->toThrow(RuntimeException::class, 'quota exceeded');

    // Audit row for the failure exists; no EmailAccount row was created.
    expect(EmailAction::query()->where('status', EmailAction::STATUS_FAILED)->exists())->toBeTrue()
        ->and(EmailAccount::query()->where('local_part', 'bob')->exists())->toBeFalse();
});
