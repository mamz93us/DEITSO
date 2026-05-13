<?php

declare(strict_types=1);

use App\Http\Middleware\ResolveOrganizationFromHost;
use App\Models\Organization;
use App\Models\OrganizationDomain;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    config()->set('app.platform_base_domain', 'it.deevar.cloud');

    Route::middleware([ResolveOrganizationFromHost::class])->get('/_tenancy/probe', function () {
        $org = app()->bound('current.organization') ? app('current.organization') : null;

        return response()->json([
            'resolved' => $org !== null,
            'slug' => $org->slug ?? null,
            'host' => request()->getHost(),
        ]);
    });
});

it('resolves an organization via platform subdomain', function () {
    Organization::create([
        'slug' => 'acme',
        'name' => ['en' => 'Acme Inc', 'ar' => 'اكمي'],
        'status' => 'active',
    ]);

    $response = $this->get('http://acme.it.deevar.cloud/_tenancy/probe');

    $response->assertOk();
    $response->assertJson(['resolved' => true, 'slug' => 'acme']);
});

it('resolves an organization via a verified custom domain', function () {
    $org = Organization::create([
        'slug' => 'partner',
        'name' => ['en' => 'Partner Co'],
        'status' => 'active',
    ]);

    OrganizationDomain::withoutEvents(function () use ($org) {
        OrganizationDomain::create([
            'organization_id' => $org->id,
            'host' => 'support.partner.com',
            'type' => OrganizationDomain::TYPE_CUSTOM,
            'dns_status' => OrganizationDomain::DNS_VERIFIED,
            'tls_status' => OrganizationDomain::TLS_ACTIVE,
            'is_primary' => true,
        ]);
    });

    $response = $this->get('http://support.partner.com/_tenancy/probe');

    $response->assertOk();
    $response->assertJson(['resolved' => true, 'slug' => 'partner']);
});

it('does not resolve a custom domain that is only pending verification', function () {
    $org = Organization::create([
        'slug' => 'pendingco',
        'name' => ['en' => 'Pending Co'],
        'status' => 'active',
    ]);

    OrganizationDomain::create([
        'organization_id' => $org->id,
        'host' => 'support.pendingco.com',
        'type' => OrganizationDomain::TYPE_CUSTOM,
        'dns_status' => OrganizationDomain::DNS_PENDING,
        'is_primary' => true,
    ]);

    $response = $this->get('http://support.pendingco.com/_tenancy/probe');

    $response->assertOk();
    $response->assertJson(['resolved' => false]);
});
