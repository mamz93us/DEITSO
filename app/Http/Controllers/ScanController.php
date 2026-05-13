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
 * Unauthenticated users are bounced to the app-panel login with an intended
 * destination so they land on the asset after signing in. Cross-tenant assets
 * 404. Soft-deleted assets 410 (Gone).
 */
class ScanController extends Controller
{
    public function show(Request $request, string $ulid)
    {
        $asset = Asset::withoutGlobalScopes()->find($ulid);
        if (! $asset) {
            return response('Asset not found.', Response::HTTP_NOT_FOUND);
        }

        if ($asset->trashed()) {
            return response('Asset has been deleted.', Response::HTTP_GONE);
        }

        $target = URL::route('filament.app.resources.assets.edit', ['record' => $asset->id]);

        if (! Auth::check()) {
            // Stash the destination so login redirects there.
            session()->put('url.intended', $target);

            return redirect()->route('filament.app.auth.login');
        }

        return redirect()->to($target);
    }
}
