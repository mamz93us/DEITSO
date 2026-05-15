<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    (new PermissionSeeder)->setContainer(app())->run();
});

function bootstrapPortalTicket(string $orgSlug, string $userEmail, ?string $code = null): array
{
    $org = Organization::create(['slug' => $orgSlug, 'name' => ['en' => ucfirst($orgSlug)], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $user = User::create(['name' => 'U', 'email' => $userEmail, 'password' => bcrypt('x')]);
    $org->users()->attach($user->id, ['joined_at' => now(), 'is_default' => true]);

    $ticket = Ticket::create([
        'code' => $code ?? 'TCK-2026-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
        'organization_id' => $org->id,
        'requester_user_id' => $user->id,
        'priority' => Ticket::PRIORITY_NORMAL,
        'state' => 'new',
        'source' => Ticket::SOURCE_PORTAL,
        'subject' => 'Test',
        'description' => 'B',
        'opened_at' => now(),
    ]);

    return ['org' => $org, 'user' => $user, 'ticket' => $ticket];
}

it('serves the portal view page for a ticket owned by the authenticated user', function () {
    ['user' => $user, 'ticket' => $ticket] = bootstrapPortalTicket('acme', 'alice@acme.test');

    $response = $this->actingAs($user)->get("/portal/my-tickets/{$ticket->id}");

    expect($response->status())->toBe(200);
});

it('returns 404 when the ticket belongs to a different user in the same org', function () {
    ['org' => $org, 'ticket' => $ticket] = bootstrapPortalTicket('acme', 'alice@acme.test');

    $other = User::create(['name' => 'Bob', 'email' => 'bob@acme.test', 'password' => bcrypt('x')]);
    $org->users()->attach($other->id, ['joined_at' => now(), 'is_default' => true]);

    $response = $this->actingAs($other)->get("/portal/my-tickets/{$ticket->id}");

    expect($response->status())->toBe(404);
});

it('returns 404 when the ticket belongs to a different organization', function () {
    ['ticket' => $ticket] = bootstrapPortalTicket('acme', 'alice@acme.test');

    $otherOrg = Organization::create(['slug' => 'rival', 'name' => ['en' => 'Rival'], 'status' => 'active']);
    $intruder = User::create(['name' => 'Eve', 'email' => 'eve@rival.test', 'password' => bcrypt('x')]);
    $otherOrg->users()->attach($intruder->id, ['joined_at' => now(), 'is_default' => true]);
    app()->instance('current.organization', $otherOrg);

    $response = $this->actingAs($intruder)->get("/portal/my-tickets/{$ticket->id}");

    expect($response->status())->toBe(404);
});
