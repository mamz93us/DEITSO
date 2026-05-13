<?php

declare(strict_types=1);

use App\Actions\Visits\CheckInVisit;
use App\Actions\Visits\CloseVisit;
use App\Actions\Visits\ScheduleVisit;
use App\Models\Organization;
use App\Models\RateCard;
use App\Models\TravelZone;
use App\Models\Visit;
use App\Models\VisitPart;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    (new PermissionSeeder)->setContainer(app())->run();
});

function bootstrapCostingEnv(): array
{
    $org = Organization::create(['slug' => 'cst', 'name' => ['en' => 'Cost'], 'status' => 'active']);
    (new RoleSeeder)->setContainer(app())->run();
    app()->instance('current.organization', $org);

    $rate = RateCard::create([
        'organization_id' => $org->id,
        'name' => ['en' => 'Standard'],
        'visit_type' => 'any',
        'technician_seniority' => 'any',
        'hourly_rate_minor' => 50000, // 500.00 EGP per hour
        'minimum_charge_minor' => 25000, // 250.00 EGP minimum
        'billing_increment_minutes' => 15,
        'is_default' => true,
    ]);

    return ['org' => $org, 'rate' => $rate];
}

it('computes labour charge from duration × hourly_rate rounded to billing increment', function () {
    ['org' => $org] = bootstrapCostingEnv();

    $v = app(ScheduleVisit::class)($org->id, [
        'type' => Visit::TYPE_OFFLINE,
        'scheduled_at' => now(),
    ]);
    app(CheckInVisit::class)($v);
    $v->update(['started_at' => now()->subMinutes(50)]); // 50-min visit

    app(CloseVisit::class)($v->fresh());

    // 50 mins → rounds up to 60 (next 15-min increment) → 1 hour × 500 = 500 EGP = 50000 minor.
    expect($v->fresh()->total_charge_minor)->toBe(50000);
});

it('enforces the minimum charge floor', function () {
    ['org' => $org] = bootstrapCostingEnv();

    $v = app(ScheduleVisit::class)($org->id, [
        'type' => Visit::TYPE_OFFLINE,
        'scheduled_at' => now(),
    ]);
    app(CheckInVisit::class)($v);
    $v->update(['started_at' => now()->subMinutes(5)]); // 5-min visit

    app(CloseVisit::class)($v->fresh());

    // 5 mins → rounds to 15 → 0.25h × 500 = 125 (12500 minor) → bumped up to 250 (25000) minimum.
    expect($v->fresh()->total_charge_minor)->toBe(25000);
});

it('adds travel-zone flat fee on top of labour', function () {
    ['org' => $org] = bootstrapCostingEnv();
    $zone = TravelZone::create([
        'organization_id' => $org->id,
        'name' => ['en' => 'Greater Cairo'],
        'flat_fee_minor' => 15000, // 150.00 EGP
    ]);

    $v = app(ScheduleVisit::class)($org->id, [
        'type' => Visit::TYPE_OFFLINE,
        'scheduled_at' => now(),
        'travel_zone_id' => $zone->id,
    ]);
    app(CheckInVisit::class)($v);
    $v->update(['started_at' => now()->subMinutes(60)]);

    app(CloseVisit::class)($v->fresh());

    // 60 mins → 1h × 500 = 500 (50000) + travel 150 (15000) = 65000 minor.
    expect($v->fresh()->total_charge_minor)->toBe(65000);
});

it('adds parts cost to the total', function () {
    ['org' => $org] = bootstrapCostingEnv();

    $v = app(ScheduleVisit::class)($org->id, [
        'type' => Visit::TYPE_OFFLINE,
        'scheduled_at' => now(),
    ]);
    app(CheckInVisit::class)($v);
    $v->update(['started_at' => now()->subMinutes(60)]);

    VisitPart::create([
        'visit_id' => $v->id,
        'description' => 'RJ45 connector',
        'quantity' => 4,
        'unit_cost_minor' => 1000, // 10.00 EGP each
    ]);

    app(CloseVisit::class)($v->fresh());

    // Labour 50000 + parts 4*1000 = 4000 → 54000 minor.
    expect($v->fresh()->total_charge_minor)->toBe(54000);
});

it('charges zero on non-billable visits', function () {
    ['org' => $org] = bootstrapCostingEnv();

    $v = app(ScheduleVisit::class)($org->id, [
        'type' => Visit::TYPE_OFFLINE,
        'scheduled_at' => now(),
        'is_billable' => false,
    ]);
    app(CheckInVisit::class)($v);
    $v->update(['started_at' => now()->subMinutes(120)]);

    app(CloseVisit::class)($v->fresh());

    expect($v->fresh()->total_charge_minor)->toBe(0);
});
