<?php

declare(strict_types=1);

use App\Actions\Visits\CheckInVisit;
use App\Actions\Visits\CloseVisit;
use App\Actions\Visits\ScheduleVisit;
use App\Models\Organization;
use App\Models\States\Visit\Cancelled;
use App\Models\States\Visit\Completed;
use App\Models\States\Visit\InProgress;
use App\Models\States\Visit\Scheduled;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    (new PermissionSeeder)->setContainer(app())->run();
});

it('schedules a visit with auto code VST-YYYY-0001', function () {
    $org = Organization::create(['slug' => 'vsco', 'name' => ['en' => 'V'], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $tech = User::create([
        'name' => 'T', 'email' => 't@v.test',
        'password' => bcrypt('x'), 'locale' => 'en', 'timezone' => 'UTC',
    ]);

    $v = app(ScheduleVisit::class)($org->id, [
        'type' => Visit::TYPE_OFFLINE,
        'scheduled_at' => now()->addHours(2),
        'technician_user_id' => $tech->id,
    ]);

    expect($v->code)->toStartWith('VST-'.now()->format('Y').'-')
        ->and($v->state)->toBeInstanceOf(Scheduled::class);
});

it('checks in with GPS and transitions to InProgress', function () {
    $org = Organization::create(['slug' => 'ci', 'name' => ['en' => 'CI'], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $v = app(ScheduleVisit::class)($org->id, [
        'type' => Visit::TYPE_OFFLINE,
        'scheduled_at' => now(),
    ]);

    app(CheckInVisit::class)($v, 30.0444, 31.2357);

    $v = $v->fresh();
    expect($v->state)->toBeInstanceOf(InProgress::class)
        ->and($v->started_at)->not->toBeNull()
        ->and((float) $v->checkin_lat)->toEqual(30.0444);
});

it('closes a visit and computes duration_minutes', function () {
    $org = Organization::create(['slug' => 'cl', 'name' => ['en' => 'CL'], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $v = app(ScheduleVisit::class)($org->id, [
        'type' => Visit::TYPE_OFFLINE,
        'scheduled_at' => now()->subMinutes(45),
    ]);
    app(CheckInVisit::class)($v);
    // Backdate started_at by 30 minutes to simulate elapsed time.
    $v->update(['started_at' => now()->subMinutes(30)]);

    app(CloseVisit::class)($v->fresh(), 30.0, 31.0, 'Replaced PSU.');

    $v = $v->fresh();
    expect($v->state)->toBeInstanceOf(Completed::class)
        ->and($v->duration_minutes)->toBeGreaterThanOrEqual(29)->toBeLessThanOrEqual(31)
        ->and($v->summary)->toBe('Replaced PSU.');
});

it('cannot close a Scheduled visit (must check in first)', function () {
    $org = Organization::create(['slug' => 'noclose', 'name' => ['en' => 'X'], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $v = app(ScheduleVisit::class)($org->id, [
        'type' => Visit::TYPE_OFFLINE,
        'scheduled_at' => now(),
    ]);

    expect(fn () => app(CloseVisit::class)($v))
        ->toThrow(RuntimeException::class, 'InProgress');
});

it('cancels a scheduled visit', function () {
    $org = Organization::create(['slug' => 'canc', 'name' => ['en' => 'C'], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $v = app(ScheduleVisit::class)($org->id, [
        'type' => Visit::TYPE_OFFLINE,
        'scheduled_at' => now(),
    ]);

    $v->state->transitionTo(Cancelled::class);

    expect($v->fresh()->state)->toBeInstanceOf(Cancelled::class);
});
