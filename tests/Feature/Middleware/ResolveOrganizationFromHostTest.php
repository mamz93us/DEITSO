<?php

declare(strict_types=1);

use App\Http\Middleware\ResolveOrganizationFromHost;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    config()->set('app.platform_base_domain', 'it.deevar.cloud');

    Route::middleware([ResolveOrganizationFromHost::class])->get('/_test/resolve', function () {
        $org = app()->bound('current.organization') ? app('current.organization') : null;

        return response()->json([
            'resolved' => $org !== null,
            'slug' => $org->slug ?? null,
        ]);
    });
});

it('leaves the organization unresolved on the master domain', function () {
    $response = $this->get('http://app.it.deevar.cloud/_test/resolve');

    $response->assertOk();
    $response->assertJson(['resolved' => false]);
});

it('does not resolve when no tenancy tables exist (sprint 0 state)', function () {
    // No organizations table yet — middleware must not throw.
    $response = $this->get('http://acme.it.deevar.cloud/_test/resolve');

    $response->assertOk();
    $response->assertJson(['resolved' => false]);
});

it('does not resolve nested subdomains like a.b.it.deevar.cloud', function () {
    $response = $this->get('http://team.acme.it.deevar.cloud/_test/resolve');

    $response->assertOk();
    $response->assertJson(['resolved' => false]);
});

it('does not resolve unrelated hosts', function () {
    $response = $this->get('http://example.com/_test/resolve');

    $response->assertOk();
    $response->assertJson(['resolved' => false]);
});
