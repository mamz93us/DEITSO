<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\PermissionRegistrar;

it('scopes roles per organization (no leakage across tenants)', function () {
    // Seed the global permission catalog.
    (new PermissionSeeder)->setContainer(app())->run();

    $orgA = Organization::create([
        'slug' => 'tenant-a',
        'name' => ['en' => 'Tenant A'],
        'status' => 'active',
    ]);
    $orgB = Organization::create([
        'slug' => 'tenant-b',
        'name' => ['en' => 'Tenant B'],
        'status' => 'active',
    ]);

    // RoleSeeder iterates orgs to create roles per org.
    (new RoleSeeder)->setContainer(app())->run();

    $user = User::create([
        'name' => 'Cross Org User',
        'email' => 'x@test.local',
        'password' => bcrypt('password'),
        'locale' => 'en',
        'timezone' => 'UTC',
        'is_system_admin' => false,
    ]);

    // Give user "Org Admin" only in tenant A.
    app(PermissionRegistrar::class)->setPermissionsTeamId($orgA->id);
    $user->assignRole('Org Admin');

    // In tenant A: hasRole = true.
    app(PermissionRegistrar::class)->setPermissionsTeamId($orgA->id);
    expect($user->fresh()->hasRole('Org Admin'))->toBeTrue();

    // In tenant B: hasRole = false (would be true without team scope).
    app(PermissionRegistrar::class)->setPermissionsTeamId($orgB->id);
    expect($user->fresh()->hasRole('Org Admin'))->toBeFalse();
});
