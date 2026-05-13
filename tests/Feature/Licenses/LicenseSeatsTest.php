<?php

declare(strict_types=1);

use App\Actions\Assets\AssignLicenseSeat;
use App\Actions\Assets\CreateAsset;
use App\Actions\Assets\RevokeLicenseSeat;
use App\Actions\Employees\CreateEmployee;
use App\Models\AssetCategory;
use App\Models\Organization;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    (new PermissionSeeder)->setContainer(app())->run();
});

function bootstrapLicenseEnv(string $slug = 'licco'): array
{
    $org = Organization::create(['slug' => $slug, 'name' => ['en' => $slug], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $cat = AssetCategory::create([
        'organization_id' => $org->id,
        'name' => ['en' => 'Licenses'],
        'code' => 'LIC',
        'tracking_mode' => AssetCategory::TRACKING_LICENSE,
    ]);

    $license = app(CreateAsset::class)($org->id, [
        'category_id' => $cat->id,
        'name' => 'Office 365 E3',
        'license_key_encrypted' => 'XXXXX-XXXXX-XXXXX-XXXXX',
        'seats_total' => 3,
        'expiry_date' => now()->addMonths(6)->toDateString(),
        'renewable' => true,
        'auto_renewal' => false,
    ]);

    $employees = collect();
    foreach (['Alice', 'Bob', 'Carla', 'Dan'] as $name) {
        $employees->push(app(CreateEmployee::class)($org->id, [
            'email' => strtolower($name).'@'.$slug.'.test',
            'first_name' => $name,
        ])['employee']);
    }

    return ['org' => $org, 'license' => $license, 'employees' => $employees];
}

it('encrypts the license key at rest, decrypts on read', function () {
    ['license' => $license] = bootstrapLicenseEnv();

    // Read back through cast.
    expect($license->fresh()->license_key_encrypted)->toBe('XXXXX-XXXXX-XXXXX-XXXXX');

    // Raw DB value should NOT contain the plaintext.
    $raw = DB::table('assets')->where('id', $license->id)->value('license_key_encrypted');
    expect($raw)->not->toBeNull()
        ->and($raw)->not->toContain('XXXXX-XXXXX');
});

it('assigns license seats and tracks seats_used / seats_available', function () {
    ['license' => $license, 'employees' => $employees] = bootstrapLicenseEnv();

    app(AssignLicenseSeat::class)($license, $employees[0]);
    app(AssignLicenseSeat::class)($license, $employees[1]);

    $license->refresh();
    expect($license->seats_used)->toBe(2)
        ->and($license->seats_available)->toBe(1)
        ->and($license->is_license)->toBeTrue();
});

it('refuses to assign more seats than seats_total', function () {
    ['license' => $license, 'employees' => $employees] = bootstrapLicenseEnv();

    app(AssignLicenseSeat::class)($license, $employees[0]);
    app(AssignLicenseSeat::class)($license, $employees[1]);
    app(AssignLicenseSeat::class)($license, $employees[2]);

    expect(fn () => app(AssignLicenseSeat::class)($license, $employees[3]))
        ->toThrow(RuntimeException::class, 'no seats available');
});

it('refuses double-assignment to the same employee', function () {
    ['license' => $license, 'employees' => $employees] = bootstrapLicenseEnv();

    app(AssignLicenseSeat::class)($license, $employees[0]);

    expect(fn () => app(AssignLicenseSeat::class)($license, $employees[0]))
        ->toThrow(RuntimeException::class, 'already holds a seat');
});

it('revokes a seat and frees it for a new assignment', function () {
    ['license' => $license, 'employees' => $employees] = bootstrapLicenseEnv();

    $a1 = app(AssignLicenseSeat::class)($license, $employees[0]);
    app(AssignLicenseSeat::class)($license, $employees[1]);
    app(AssignLicenseSeat::class)($license, $employees[2]);

    expect($license->fresh()->seats_used)->toBe(3);

    app(RevokeLicenseSeat::class)($a1, 'role change');

    $license->refresh();
    expect($license->seats_used)->toBe(2)
        ->and($license->seats_available)->toBe(1);

    // Now we can assign the freed seat to a 4th person.
    app(AssignLicenseSeat::class)($license, $employees[3]);
    expect($license->fresh()->seats_used)->toBe(3);
});

it('refuses to assign a seat on an expired license', function () {
    ['license' => $license, 'employees' => $employees] = bootstrapLicenseEnv();

    $license->update(['expiry_date' => now()->subDay()->toDateString()]);

    expect(fn () => app(AssignLicenseSeat::class)($license->fresh(), $employees[0]))
        ->toThrow(RuntimeException::class, 'expired');
});

it('refuses to assign seat on non-license tracking mode', function () {
    $org = Organization::create(['slug' => 'mix', 'name' => ['en' => 'Mix'], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $serializedCat = AssetCategory::create([
        'organization_id' => $org->id,
        'name' => ['en' => 'Laptops'],
        'code' => 'LAP',
        'tracking_mode' => AssetCategory::TRACKING_SERIALIZED,
    ]);
    $serialAsset = app(CreateAsset::class)($org->id, ['category_id' => $serializedCat->id]);

    $emp = app(CreateEmployee::class)($org->id, [
        'email' => 'e@mix.test', 'first_name' => 'E',
    ])['employee'];

    expect(fn () => app(AssignLicenseSeat::class)($serialAsset, $emp))
        ->toThrow(RuntimeException::class, 'only valid for license');
});
