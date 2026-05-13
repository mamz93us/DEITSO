<?php

declare(strict_types=1);

use App\Actions\Assets\CreateAsset;
use App\Models\AssetCategory;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    (new PermissionSeeder)->setContainer(app())->run();
});

it('redirects an unauthenticated scan to login', function () {
    $org = Organization::create(['slug' => 'scanco', 'name' => ['en' => 'Scan'], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $cat = AssetCategory::create([
        'organization_id' => $org->id,
        'name' => ['en' => 'L'],
        'code' => 'L',
        'tracking_mode' => AssetCategory::TRACKING_SERIALIZED,
    ]);
    $asset = app(CreateAsset::class)($org->id, ['category_id' => $cat->id]);

    $response = $this->get('/scan/'.$asset->id);

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('login');
});

it('redirects an authenticated scan to the asset edit page', function () {
    $org = Organization::create(['slug' => 'auth', 'name' => ['en' => 'A'], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $cat = AssetCategory::create([
        'organization_id' => $org->id,
        'name' => ['en' => 'L'],
        'code' => 'L',
        'tracking_mode' => AssetCategory::TRACKING_SERIALIZED,
    ]);
    $asset = app(CreateAsset::class)($org->id, ['category_id' => $cat->id]);

    $user = User::create([
        'name' => 'Scanner',
        'email' => 'scanner@auth.test',
        'password' => bcrypt('x'),
        'locale' => 'en',
        'timezone' => 'UTC',
        'is_system_admin' => true,
    ]);

    $response = $this->actingAs($user)->get('/scan/'.$asset->id);

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('/app/assets/'.$asset->id.'/edit');
});

it('returns 404 for an unknown ULID', function () {
    $user = User::create([
        'name' => 'Scanner', 'email' => 'unknown@scan.test',
        'password' => bcrypt('x'), 'locale' => 'en', 'timezone' => 'UTC', 'is_system_admin' => true,
    ]);

    // 26-char ULID that doesn't exist.
    $response = $this->actingAs($user)->get('/scan/00000000000000000000000000');

    $response->assertNotFound();
});
