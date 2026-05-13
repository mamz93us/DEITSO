<?php

declare(strict_types=1);

use App\Actions\Assets\CreateAsset;
use App\Models\AssetCategory;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\LicenseExpiringSoon;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    (new PermissionSeeder)->setContainer(app())->run();
});

it('notifies org admins about licenses expiring within the threshold', function () {
    Notification::fake();

    $org = Organization::create(['slug' => 'expco', 'name' => ['en' => 'Exp'], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $cat = AssetCategory::create([
        'organization_id' => $org->id,
        'name' => ['en' => 'L'],
        'code' => 'L',
        'tracking_mode' => AssetCategory::TRACKING_LICENSE,
    ]);

    app(CreateAsset::class)($org->id, ['category_id' => $cat->id, 'expiry_date' => now()->addDays(10)->toDateString()]);
    app(CreateAsset::class)($org->id, ['category_id' => $cat->id, 'expiry_date' => now()->subDays(2)->toDateString()]);
    app(CreateAsset::class)($org->id, ['category_id' => $cat->id, 'expiry_date' => now()->addDays(180)->toDateString()]);

    $admin = User::create([
        'name' => 'Admin', 'email' => 'a@expco.test',
        'password' => bcrypt('x'), 'locale' => 'en', 'timezone' => 'UTC',
    ]);
    $org->users()->attach($admin->id, [
        'default_role' => 'Org Admin',
        'joined_at' => now(),
        'is_default' => true,
    ]);

    $this->artisan('licenses:check-expiry', ['--days' => 30])->assertSuccessful();

    Notification::assertSentTo($admin, LicenseExpiringSoon::class, function ($notification) {
        return $notification->expiring->count() === 1
            && $notification->expired->count() === 1;
    });
});

it('dry-run does not send notifications', function () {
    Notification::fake();

    $org = Organization::create(['slug' => 'dryco', 'name' => ['en' => 'Dry'], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $cat = AssetCategory::create([
        'organization_id' => $org->id, 'name' => ['en' => 'L'], 'code' => 'L',
        'tracking_mode' => AssetCategory::TRACKING_LICENSE,
    ]);
    app(CreateAsset::class)($org->id, ['category_id' => $cat->id, 'expiry_date' => now()->addDays(5)->toDateString()]);

    $admin = User::create([
        'name' => 'Admin', 'email' => 'a@dryco.test',
        'password' => bcrypt('x'), 'locale' => 'en', 'timezone' => 'UTC',
    ]);
    $org->users()->attach($admin->id, ['default_role' => 'Org Admin', 'joined_at' => now(), 'is_default' => true]);

    $this->artisan('licenses:check-expiry', ['--days' => 30, '--dry-run' => true])->assertSuccessful();

    Notification::assertNothingSent();
});
