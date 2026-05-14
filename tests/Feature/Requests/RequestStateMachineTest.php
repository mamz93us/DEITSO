<?php

declare(strict_types=1);

use App\Actions\Assets\CreateAsset;
use App\Actions\Employees\CreateEmployee;
use App\Actions\Requests\ApproveByAdmin;
use App\Actions\Requests\ApproveByManager;
use App\Actions\Requests\CancelRequest;
use App\Actions\Requests\FulfillFromStock;
use App\Actions\Requests\RejectRequest;
use App\Actions\Requests\SubmitRequest;
use App\Models\AssetCategory;
use App\Models\EmployeeRequest;
use App\Models\Organization;
use App\Models\States\EmployeeRequest\AdminApproved;
use App\Models\States\EmployeeRequest\AdminRejected;
use App\Models\States\EmployeeRequest\Cancelled;
use App\Models\States\EmployeeRequest\Fulfilled;
use App\Models\States\EmployeeRequest\ManagerApproved;
use App\Models\States\EmployeeRequest\ManagerRejected;
use App\Models\States\EmployeeRequest\Submitted;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Spatie\ModelStates\Exceptions\TransitionNotFound;

beforeEach(function () {
    (new PermissionSeeder)->setContainer(app())->run();
});

function bootstrapRequestEnv(string $slug = 'reqco'): array
{
    $org = Organization::create(['slug' => $slug, 'name' => ['en' => $slug], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $cat = AssetCategory::create([
        'organization_id' => $org->id,
        'name' => ['en' => 'Laptops'],
        'code' => 'LAP',
        'tracking_mode' => AssetCategory::TRACKING_SERIALIZED,
    ]);

    $mgrEmp = app(CreateEmployee::class)($org->id, [
        'email' => 'mgr-emp@'.$slug.'.test',
        'first_name' => 'MgrEmp',
    ])['employee'];

    $emp = app(CreateEmployee::class)($org->id, [
        'email' => 'requester@'.$slug.'.test',
        'first_name' => 'Requester',
        'manager_employee_id' => $mgrEmp->id,
    ])['employee'];

    $manager = User::create([
        'name' => 'Mgr', 'email' => 'm@'.$slug.'.test',
        'password' => bcrypt('x'), 'locale' => 'en', 'timezone' => 'UTC',
    ]);
    $admin = User::create([
        'name' => 'Adm', 'email' => 'a@'.$slug.'.test',
        'password' => bcrypt('x'), 'locale' => 'en', 'timezone' => 'UTC',
    ]);

    return ['org' => $org, 'category' => $cat, 'requester' => $emp, 'manager' => $manager, 'admin' => $admin];
}

it('submits a request with auto-generated REQ-YYYY-0001 code', function () {
    ['org' => $org, 'requester' => $emp, 'category' => $cat] = bootstrapRequestEnv();

    $r = app(SubmitRequest::class)($org->id, [
        'requester_employee_id' => $emp->id,
        'category_id' => $cat->id,
        'type' => EmployeeRequest::TYPE_NEW_ASSET,
        'title' => 'I need a laptop',
        'description' => 'Mine is dying',
        'urgency' => EmployeeRequest::URGENCY_NORMAL,
    ]);

    expect($r->state)->toBeInstanceOf(Submitted::class)
        ->and($r->code)->toStartWith('REQ-'.now()->format('Y').'-')
        ->and($r->code)->toEndWith('0001');
});

it('walks the two-step approval flow → Submitted → ManagerApproved → AdminApproved → Fulfilled', function () {
    ['org' => $org, 'requester' => $emp, 'category' => $cat, 'manager' => $mgr, 'admin' => $adm] = bootstrapRequestEnv();

    $r = app(SubmitRequest::class)($org->id, [
        'requester_employee_id' => $emp->id,
        'category_id' => $cat->id,
        'type' => EmployeeRequest::TYPE_NEW_ASSET,
        'title' => 'T', 'description' => 'D',
    ]);

    $r = app(ApproveByManager::class)($r, $mgr, 'looks fine');
    expect($r->state)->toBeInstanceOf(ManagerApproved::class)
        ->and($r->manager_approval_user_id)->toBe($mgr->id)
        ->and($r->manager_approval_notes)->toBe('looks fine');

    $r = app(ApproveByAdmin::class)($r, $adm);
    expect($r->state)->toBeInstanceOf(AdminApproved::class)
        ->and($r->admin_approval_user_id)->toBe($adm->id);

    $asset = app(CreateAsset::class)($org->id, ['category_id' => $cat->id]);
    $r = app(FulfillFromStock::class)($r, $asset);

    expect($r->state)->toBeInstanceOf(Fulfilled::class)
        ->and($r->fulfillment_asset_id)->toBe($asset->id)
        ->and($r->fulfilled_at)->not->toBeNull()
        ->and($asset->fresh()->source_request_id)->toBe($r->id)
        ->and($asset->fresh()->assigned_employee_id)->toBe($emp->id);
});

it('supports the single-step approval flow → Submitted → AdminApproved (skipping manager)', function () {
    ['org' => $org, 'requester' => $emp, 'category' => $cat, 'admin' => $adm] = bootstrapRequestEnv();

    $r = app(SubmitRequest::class)($org->id, [
        'requester_employee_id' => $emp->id,
        'category_id' => $cat->id,
        'type' => EmployeeRequest::TYPE_NEW_ASSET,
        'title' => 'T', 'description' => 'D',
    ]);

    $r = app(ApproveByAdmin::class)($r, $adm);

    expect($r->state)->toBeInstanceOf(AdminApproved::class)
        ->and($r->manager_approval_user_id)->toBeNull();
});

it('lets a manager reject a Submitted request', function () {
    ['org' => $org, 'requester' => $emp, 'category' => $cat, 'manager' => $mgr] = bootstrapRequestEnv();

    $r = app(SubmitRequest::class)($org->id, [
        'requester_employee_id' => $emp->id,
        'category_id' => $cat->id,
        'type' => EmployeeRequest::TYPE_NEW_ASSET,
        'title' => 'T', 'description' => 'D',
    ]);

    $r = app(RejectRequest::class)($r, $mgr, 'manager', 'not approved');

    expect($r->state)->toBeInstanceOf(ManagerRejected::class)
        ->and($r->manager_approval_notes)->toBe('not approved');
});

it('lets an admin reject a ManagerApproved request', function () {
    ['org' => $org, 'requester' => $emp, 'category' => $cat, 'manager' => $mgr, 'admin' => $adm] = bootstrapRequestEnv();

    $r = app(SubmitRequest::class)($org->id, [
        'requester_employee_id' => $emp->id,
        'category_id' => $cat->id,
        'type' => EmployeeRequest::TYPE_NEW_ASSET,
        'title' => 'T', 'description' => 'D',
    ]);

    $r = app(ApproveByManager::class)($r, $mgr);
    $r = app(RejectRequest::class)($r, $adm, 'admin', 'budget cut');

    expect($r->state)->toBeInstanceOf(AdminRejected::class);
});

it('cancels a request before procurement', function () {
    ['org' => $org, 'requester' => $emp, 'category' => $cat] = bootstrapRequestEnv();

    $r = app(SubmitRequest::class)($org->id, [
        'requester_employee_id' => $emp->id,
        'category_id' => $cat->id,
        'type' => EmployeeRequest::TYPE_NEW_ASSET,
        'title' => 'T', 'description' => 'D',
    ]);

    $r = app(CancelRequest::class)($r);

    expect($r->state)->toBeInstanceOf(Cancelled::class);
});

it('cannot cancel a Fulfilled request', function () {
    ['org' => $org, 'requester' => $emp, 'category' => $cat, 'admin' => $adm] = bootstrapRequestEnv();

    $r = app(SubmitRequest::class)($org->id, [
        'requester_employee_id' => $emp->id,
        'category_id' => $cat->id,
        'type' => EmployeeRequest::TYPE_NEW_ASSET,
        'title' => 'T', 'description' => 'D',
    ]);
    $r = app(ApproveByAdmin::class)($r, $adm);
    $asset = app(CreateAsset::class)($org->id, ['category_id' => $cat->id]);
    $r = app(FulfillFromStock::class)($r, $asset);

    expect(fn () => app(CancelRequest::class)($r))->toThrow(RuntimeException::class, 'already in procurement or fulfilled');
});

it('cannot reach Fulfilled directly from Submitted (must go through approvals)', function () {
    ['org' => $org, 'requester' => $emp, 'category' => $cat] = bootstrapRequestEnv();

    $r = app(SubmitRequest::class)($org->id, [
        'requester_employee_id' => $emp->id,
        'category_id' => $cat->id,
        'type' => EmployeeRequest::TYPE_NEW_ASSET,
        'title' => 'T', 'description' => 'D',
    ]);

    expect(fn () => $r->state->transitionTo(Fulfilled::class))->toThrow(TransitionNotFound::class);
});

it('fulfills cross-tenant rejection', function () {
    ['org' => $orgA, 'requester' => $emp, 'category' => $cat, 'admin' => $adm] = bootstrapRequestEnv('orga');

    $r = app(SubmitRequest::class)($orgA->id, [
        'requester_employee_id' => $emp->id,
        'category_id' => $cat->id,
        'type' => EmployeeRequest::TYPE_NEW_ASSET,
        'title' => 'T', 'description' => 'D',
    ]);
    $r = app(ApproveByAdmin::class)($r, $adm);

    $orgB = Organization::create(['slug' => 'orgb', 'name' => ['en' => 'B'], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $orgB);
    $catB = AssetCategory::create([
        'organization_id' => $orgB->id, 'name' => ['en' => 'L'], 'code' => 'L',
        'tracking_mode' => AssetCategory::TRACKING_SERIALIZED,
    ]);
    $strangerAsset = app(CreateAsset::class)($orgB->id, ['category_id' => $catB->id]);

    app()->instance('current.organization', $orgA);
    expect(fn () => app(FulfillFromStock::class)($r, $strangerAsset))
        ->toThrow(RuntimeException::class, 'Cross-tenant');
});

it('per-org code sequence resets per year via REQ-YYYY-XXXX pattern', function () {
    ['org' => $org, 'requester' => $emp, 'category' => $cat] = bootstrapRequestEnv();

    $r1 = app(SubmitRequest::class)($org->id, [
        'requester_employee_id' => $emp->id, 'category_id' => $cat->id,
        'type' => EmployeeRequest::TYPE_NEW_ASSET,
        'title' => 'A', 'description' => 'A',
    ]);
    $r2 = app(SubmitRequest::class)($org->id, [
        'requester_employee_id' => $emp->id, 'category_id' => $cat->id,
        'type' => EmployeeRequest::TYPE_NEW_ASSET,
        'title' => 'B', 'description' => 'B',
    ]);

    expect($r1->code)->toEndWith('-0001')
        ->and($r2->code)->toEndWith('-0002');
});
