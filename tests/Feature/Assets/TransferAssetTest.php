<?php

declare(strict_types=1);

use App\Actions\Assets\AssignAssetToEmployee;
use App\Actions\Assets\CreateAsset;
use App\Actions\Assets\TransferAsset;
use App\Actions\Employees\CreateEmployee;
use App\Models\AssetAssignment;
use App\Models\AssetCategory;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\States\AssetTransfer\Approved;
use App\Models\States\AssetTransfer\Cancelled;
use App\Models\States\AssetTransfer\Completed;
use App\Models\States\AssetTransfer\Draft;
use App\Models\States\AssetTransfer\Pending;
use App\Models\States\AssetTransfer\Rejected;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Spatie\ModelStates\Exceptions\TransitionNotFound;

beforeEach(function () {
    (new PermissionSeeder)->setContainer(app())->run();
});

it('completes a transfer through Draft → Pending → Approved → Completed', function () {
    $org = Organization::create(['slug' => 'tx', 'name' => ['en' => 'TX'], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $branch = Branch::create(['organization_id' => $org->id, 'name' => ['en' => 'HQ'], 'code' => 'HQ']);
    $cat = AssetCategory::create([
        'organization_id' => $org->id,
        'name' => ['en' => 'Laptops'],
        'code' => 'LAP',
        'tracking_mode' => AssetCategory::TRACKING_SERIALIZED,
    ]);
    $asset = app(CreateAsset::class)($org->id, ['category_id' => $cat->id, 'branch_id' => $branch->id]);
    $alice = app(CreateEmployee::class)($org->id, [
        'email' => 'a@tx.test', 'first_name' => 'A', 'branch_id' => $branch->id,
    ])['employee'];
    $bob = app(CreateEmployee::class)($org->id, [
        'email' => 'b@tx.test', 'first_name' => 'B', 'branch_id' => $branch->id,
    ])['employee'];
    $requester = User::create([
        'name' => 'Requester', 'email' => 'r@tx.test',
        'password' => bcrypt('x'), 'locale' => 'en', 'timezone' => 'UTC',
    ]);

    app(AssignAssetToEmployee::class)($asset, $alice);

    $svc = app(TransferAsset::class);
    $transfer = $svc->create($asset, [
        'to_employee_id' => $bob->id,
        'reason' => 'role change',
        'requested_by_user_id' => $requester->id,
    ]);

    expect($transfer->state)->toBeInstanceOf(Draft::class);

    $transfer->state->transitionTo(Pending::class);
    expect($transfer->fresh()->state)->toBeInstanceOf(Pending::class);

    $transfer->state->transitionTo(Approved::class);
    $svc->complete($transfer, $requester->id);

    $transfer->refresh();
    expect($transfer->state)->toBeInstanceOf(Completed::class)
        ->and($transfer->approved_by_user_id)->toBe($requester->id)
        ->and($transfer->transferred_at)->not->toBeNull();

    $asset->refresh();
    expect($asset->assigned_employee_id)->toBe($bob->id);

    // Two assignment rows now exist; the latest is open and points to Bob.
    $assignments = AssetAssignment::query()->where('asset_id', $asset->id)->orderBy('from_at')->get();
    expect($assignments)->toHaveCount(2)
        ->and($assignments->last()->assigned_to_id)->toBe($bob->id)
        ->and($assignments->last()->to_at)->toBeNull();
});

it('cannot transition from Completed (terminal state)', function () {
    $org = Organization::create(['slug' => 'term', 'name' => ['en' => 'Term'], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $cat = AssetCategory::create([
        'organization_id' => $org->id, 'name' => ['en' => 'L'], 'code' => 'L',
        'tracking_mode' => AssetCategory::TRACKING_SERIALIZED,
    ]);
    $asset = app(CreateAsset::class)($org->id, ['category_id' => $cat->id]);
    $requester = User::create([
        'name' => 'X', 'email' => 'x@term.test',
        'password' => bcrypt('x'), 'locale' => 'en', 'timezone' => 'UTC',
    ]);

    $svc = app(TransferAsset::class);
    $transfer = $svc->create($asset, [
        'requested_by_user_id' => $requester->id,
    ]);
    $transfer->state->transitionTo(Pending::class);
    $transfer->state->transitionTo(Approved::class);
    $svc->complete($transfer);

    expect(fn () => $transfer->fresh()->state->transitionTo(Cancelled::class))
        ->toThrow(TransitionNotFound::class);
});

it('cannot go directly from Pending to Completed (must go through Approved)', function () {
    $org = Organization::create(['slug' => 'skip', 'name' => ['en' => 'Skip'], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $cat = AssetCategory::create([
        'organization_id' => $org->id, 'name' => ['en' => 'L'], 'code' => 'L',
        'tracking_mode' => AssetCategory::TRACKING_SERIALIZED,
    ]);
    $asset = app(CreateAsset::class)($org->id, ['category_id' => $cat->id]);
    $requester = User::create([
        'name' => 'X', 'email' => 'x@skip.test',
        'password' => bcrypt('x'), 'locale' => 'en', 'timezone' => 'UTC',
    ]);

    $transfer = app(TransferAsset::class)->create($asset, [
        'requested_by_user_id' => $requester->id,
    ]);
    $transfer->state->transitionTo(Pending::class);

    expect(fn () => $transfer->state->transitionTo(Completed::class))
        ->toThrow(TransitionNotFound::class);
});

it('rejected transfers are terminal', function () {
    $org = Organization::create(['slug' => 'rej', 'name' => ['en' => 'Rej'], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $cat = AssetCategory::create([
        'organization_id' => $org->id, 'name' => ['en' => 'L'], 'code' => 'L',
        'tracking_mode' => AssetCategory::TRACKING_SERIALIZED,
    ]);
    $asset = app(CreateAsset::class)($org->id, ['category_id' => $cat->id]);
    $requester = User::create([
        'name' => 'X', 'email' => 'x@rej.test',
        'password' => bcrypt('x'), 'locale' => 'en', 'timezone' => 'UTC',
    ]);

    $transfer = app(TransferAsset::class)->create($asset, ['requested_by_user_id' => $requester->id]);
    $transfer->state->transitionTo(Pending::class);
    $transfer->state->transitionTo(Rejected::class);

    expect($transfer->fresh()->state)->toBeInstanceOf(Rejected::class);
    expect(fn () => $transfer->fresh()->state->transitionTo(Approved::class))
        ->toThrow(TransitionNotFound::class);
});
