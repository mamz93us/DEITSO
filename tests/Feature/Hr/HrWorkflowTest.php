<?php

declare(strict_types=1);

use App\Actions\Assets\AssignAssetToEmployee;
use App\Actions\Assets\CreateAsset;
use App\Actions\Employees\CreateEmployee;
use App\Actions\Hr\CompleteProcess;
use App\Actions\Hr\ExecuteTask;
use App\Actions\Hr\InitiateProcess;
use App\Models\AssetCategory;
use App\Models\HrProcess;
use App\Models\HrWorkflowTemplate;
use App\Models\HrWorkflowTemplateTask;
use App\Models\Organization;
use App\Models\States\HrProcess\Completed as ProcessCompleted;
use App\Models\States\HrProcess\InProgress as ProcessInProgress;
use App\Models\States\HrProcessTask\Completed as TaskCompleted;
use App\Models\States\HrProcessTask\Pending;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    (new PermissionSeeder)->setContainer(app())->run();
});

function bootstrapHrEnv(): array
{
    $org = Organization::create(['slug' => 'hrco', 'name' => ['en' => 'HR'], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $cat = AssetCategory::create([
        'organization_id' => $org->id,
        'name' => ['en' => 'Laptops'],
        'code' => 'LAP',
        'tracking_mode' => AssetCategory::TRACKING_SERIALIZED,
    ]);

    return ['org' => $org, 'category' => $cat];
}

it('initiates an onboarding with snapshotted tasks from the matching template', function () {
    ['org' => $org] = bootstrapHrEnv();

    $template = HrWorkflowTemplate::create([
        'organization_id' => $org->id,
        'type' => HrWorkflowTemplate::TYPE_ONBOARDING,
        'name' => ['en' => 'Default onboarding'],
        'is_default' => true,
        'is_active' => true,
    ]);
    $template->tasks()->createMany([
        [
            'order_index' => 1,
            'title' => ['en' => 'Issue laptop'],
            'type' => HrWorkflowTemplateTask::TYPE_ASSIGN_ASSET,
            'assignee_role' => HrWorkflowTemplateTask::ROLE_IT_TECHNICIAN,
            'due_offset_days' => 0,
        ],
        [
            'order_index' => 2,
            'title' => ['en' => 'Welcome session'],
            'type' => HrWorkflowTemplateTask::TYPE_MANUAL,
            'assignee_role' => HrWorkflowTemplateTask::ROLE_HR,
            'due_offset_days' => 1,
        ],
    ]);

    $employee = app(CreateEmployee::class)($org->id, [
        'email' => 'alice@hrco.test',
        'first_name' => 'Alice',
    ])['employee'];

    $process = app(InitiateProcess::class)(
        HrProcess::TYPE_ONBOARDING,
        $employee,
        null, // auto-match
        now()->addDays(7)->toDateString(),
    );

    expect($process)->toBeInstanceOf(HrProcess::class)
        ->and($process->state)->toBeInstanceOf(ProcessInProgress::class)
        ->and($process->code)->toStartWith('ONB-'.now()->format('Y').'-')
        ->and($process->template_id)->toBe($template->id);

    expect($process->tasks)->toHaveCount(2)
        ->and($process->tasks->first()->type)->toBe(HrWorkflowTemplateTask::TYPE_ASSIGN_ASSET)
        ->and($process->tasks->first()->state)->toBeInstanceOf(Pending::class);
});

it('executes a manual task and marks it completed', function () {
    ['org' => $org] = bootstrapHrEnv();
    $template = HrWorkflowTemplate::create([
        'organization_id' => $org->id,
        'type' => HrWorkflowTemplate::TYPE_ONBOARDING,
        'name' => ['en' => 'T'],
        'is_default' => true,
    ]);
    $template->tasks()->create([
        'order_index' => 1,
        'title' => ['en' => 'Welcome session'],
        'type' => HrWorkflowTemplateTask::TYPE_MANUAL,
    ]);

    $employee = app(CreateEmployee::class)($org->id, [
        'email' => 'm@hrco.test', 'first_name' => 'M',
    ])['employee'];

    $process = app(InitiateProcess::class)(HrProcess::TYPE_ONBOARDING, $employee);
    $task = $process->tasks->first();

    app(ExecuteTask::class)($task, ['note' => 'Did the welcome chat']);

    expect($task->fresh()->state)->toBeInstanceOf(TaskCompleted::class)
        ->and($task->fresh()->result['note'])->toBe('Did the welcome chat');
});

it('executes an assign_asset task and links the asset', function () {
    ['org' => $org, 'category' => $cat] = bootstrapHrEnv();

    $template = HrWorkflowTemplate::create([
        'organization_id' => $org->id,
        'type' => HrWorkflowTemplate::TYPE_ONBOARDING,
        'name' => ['en' => 'T'],
        'is_default' => true,
    ]);
    $template->tasks()->create([
        'order_index' => 1,
        'title' => ['en' => 'Laptop'],
        'type' => HrWorkflowTemplateTask::TYPE_ASSIGN_ASSET,
    ]);

    $asset = app(CreateAsset::class)($org->id, ['category_id' => $cat->id]);
    $employee = app(CreateEmployee::class)($org->id, [
        'email' => 'a@hrco.test', 'first_name' => 'A',
    ])['employee'];

    $process = app(InitiateProcess::class)(HrProcess::TYPE_ONBOARDING, $employee);
    $task = $process->tasks->first();

    app(ExecuteTask::class)($task, ['asset_id' => $asset->id]);

    $task = $task->fresh();
    expect($task->state)->toBeInstanceOf(TaskCompleted::class)
        ->and($task->result['asset_id'])->toBe($asset->id)
        ->and($asset->fresh()->assigned_employee_id)->toBe($employee->id);
});

it('completes an offboarding process and generates a handover PDF', function () {
    ['org' => $org, 'category' => $cat] = bootstrapHrEnv();

    $template = HrWorkflowTemplate::create([
        'organization_id' => $org->id,
        'type' => HrWorkflowTemplate::TYPE_OFFBOARDING,
        'name' => ['en' => 'Offboard'],
        'is_default' => true,
    ]);
    $template->tasks()->create([
        'order_index' => 1,
        'title' => ['en' => 'Collect laptop'],
        'type' => HrWorkflowTemplateTask::TYPE_COLLECT_ASSET,
    ]);

    $employee = app(CreateEmployee::class)($org->id, [
        'email' => 'l@hrco.test', 'first_name' => 'L',
    ])['employee'];

    // Pre-assign an asset to this employee so collect_asset has something to collect.
    $asset = app(CreateAsset::class)($org->id, ['category_id' => $cat->id]);
    app(AssignAssetToEmployee::class)($asset, $employee);

    $process = app(InitiateProcess::class)(HrProcess::TYPE_OFFBOARDING, $employee);
    $task = $process->tasks->first();

    app(ExecuteTask::class)($task, ['asset_id' => $asset->id, 'condition' => 'good']);

    Storage::fake('local');

    app(CompleteProcess::class)($process->fresh());

    $process = $process->fresh();
    expect($process->state)->toBeInstanceOf(ProcessCompleted::class)
        ->and($process->handover_pdf_path)->toContain($process->code)
        ->and($process->handover_pdf_path)->toEndWith('.pdf');

    Storage::disk('local')->assertExists($process->handover_pdf_path);
});

it('refuses to complete a process with incomplete required tasks', function () {
    ['org' => $org] = bootstrapHrEnv();

    $template = HrWorkflowTemplate::create([
        'organization_id' => $org->id,
        'type' => HrWorkflowTemplate::TYPE_ONBOARDING,
        'name' => ['en' => 'T'],
        'is_default' => true,
    ]);
    $template->tasks()->create([
        'order_index' => 1,
        'title' => ['en' => 'X'],
        'type' => HrWorkflowTemplateTask::TYPE_MANUAL,
    ]);

    $employee = app(CreateEmployee::class)($org->id, [
        'email' => 'x@hrco.test', 'first_name' => 'X',
    ])['employee'];
    $process = app(InitiateProcess::class)(HrProcess::TYPE_ONBOARDING, $employee);

    expect(fn () => app(CompleteProcess::class)($process))
        ->toThrow(RuntimeException::class, 'not yet finished');
});
