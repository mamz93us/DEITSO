<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

/**
 * Resolves a QR-encoded `/scan/{ulid}` URL to the matching asset detail page.
 *
 * Security: unauthenticated callers ALWAYS get redirected to login without
 * confirming whether the asset exists, to prevent enumeration of valid
 * asset IDs across tenants. Authorized callers (member of asset's org or
 * system admin) get the normal redirect. Everyone else gets 404.
 */
class ScanController extends Controller
{
    public function show(Request $request, string $ulid)
    {
        // Unauthenticated callers learn nothing about whether the asset
        // exists — they bounce to login first.
        if (! Auth::check()) {
            session()->put('url.intended', $request->fullUrl());

            return redirect()->route('filament.app.auth.login');
        }

        $asset = Asset::withoutGlobalScopes()->find($ulid);
        if (! $asset) {
            return response('Asset not found.', Response::HTTP_NOT_FOUND);
        }

        if ($asset->trashed()) {
            return response('Asset has been deleted.', Response::HTTP_GONE);
        }

        // Cross-tenant fingerprinting protection: an authenticated user must
        // belong to the asset's org (or be a system admin) — otherwise 404
        // so they cannot tell a valid-but-other-org ULID from a fake one.
        $user = Auth::user();
        $isAuthorized = ($user->is_system_admin ?? false) === true
            || $user->organizations()->where('organizations.id', $asset->organization_id)->exists();

        if (! $isAuthorized) {
            return response('Asset not found.', Response::HTTP_NOT_FOUND);
        }

        return redirect()->to(URL::route('filament.app.resources.assets.edit', ['record' => $asset->id]));
    }
}
