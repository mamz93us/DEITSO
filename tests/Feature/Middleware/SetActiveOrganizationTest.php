<?php

declare(strict_types=1);

use App\Http\Middleware\ResolveOrganizationFromHost;
use App\Http\Middleware\SetActiveOrganization;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    (new PermissionSeeder)->setContainer(app())->run();

    Route::middleware(['web', ResolveOrganizationFromHost::class, SetActiveOrganization::class])
        ->get('/_org/probe', function () {
            $org = app()->bound('current.organization') ? app('current.organization') : null;

            return response()->json([
                'resolved' => $org !== null,
                'slug' => $org?->slug,
            ]);
        });
});

it('binds the user default org membership when no host or session resolution exists', function () {
    $orgA = Organization::create(['slug' => 'orga', 'name' => ['en' => 'OrgA'], 'status' => 'active']);
    $orgB = Organization::create(['slug' => 'orgb', 'name' => ['en' => 'OrgB'], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();

    $user = User::create([
        'name' => 'Multi-Org User', 'email' => 'multi@test.test',
        'password' => bcrypt('x'), 'locale' => 'en', 'timezone' => 'UTC',
    ]);
    $orgA->users()->attach($user->id, ['default_role' => 'End User', 'joined_at' => now(), 'is_default' => false]);
    $orgB->users()->attach($user->id, ['default_role' => 'Org Admin', 'joined_at' => now(), 'is_default' => true]);

    $response = $this->actingAs($user)->getJson('http://localhost/_org/probe');

    $response->assertOk();
    expect($response->json('slug'))->toBe('orgb'); // is_default wins
});

it('binds the only org membership for a single-org user', function () {
    $org = Organization::create(['slug' => 'only', 'name' => ['en' => 'Only'], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();

    $user = User::create([
        'name' => 'Single', 'email' => 'single@test.test',
        'password' => bcrypt('x'), 'locale' => 'en', 'timezone' => 'UTC',
    ]);
    $org->users()->attach($user->id, ['default_role' => 'Org Admin', 'joined_at' => now(), 'is_default' => false]);

    $response = $this->actingAs($user)->getJson('http://localhost/_org/probe');

    expect($response->json('slug'))->toBe('only');
});

it('persists the resolved org to session so the next request skips the DB lookup', function () {
    $org = Organization::create(['slug' => 'persist', 'name' => ['en' => 'P'], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();

    $user = User::create([
        'name' => 'P', 'email' => 'p@test.test',
        'password' => bcrypt('x'), 'locale' => 'en', 'timezone' => 'UTC',
    ]);
    $org->users()->attach($user->id, ['default_role' => 'Org Admin', 'joined_at' => now(), 'is_default' => true]);

    $this->actingAs($user)
        ->get('http://localhost/_org/probe')
        ->assertSessionHas('active_organization_id', $org->id);
});

it('leaves system admin unbound when they have no membership (system context)', function () {
    $sysAdmin = User::create([
        'name' => 'Sys', 'email' => 'sys@test.test',
        'password' => bcrypt('x'), 'locale' => 'en', 'timezone' => 'UTC',
        'is_system_admin' => true,
    ]);

    $response = $this->actingAs($sysAdmin)->getJson('http://localhost/_org/probe');

    $response->assertOk();
    expect($response->json('resolved'))->toBeFalse();
});

it('leaves unauthenticated requests unbound (no membership to resolve from)', function () {
    $response = $this->getJson('http://localhost/_org/probe');

    $response->assertOk();
    expect($response->json('resolved'))->toBeFalse();
});
