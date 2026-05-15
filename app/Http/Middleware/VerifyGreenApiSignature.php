<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies inbound webhooks from Green API (WhatsApp delivery receipts, replies).
 *
 * Pre-written ahead of the inbound webhook feature so the route is safe-by-default
 * when enabled. Two verification modes are supported, in priority order:
 *
 *  1. HMAC mode — if the request carries `X-Green-Api-Signature`, the value is
 *     compared (timing-safe) against HMAC-SHA256 of the raw body using
 *     env('GREEN_API_WEBHOOK_SECRET'). Use this when "Sign incoming
 *     notifications" is enabled in the Green API instance settings.
 *
 *  2. Bearer mode — otherwise, the request must carry
 *     `Authorization: Bearer <token>` matching env('GREEN_API_WEBHOOK_TOKEN').
 *     Use this when the webhook URL itself is the shared secret and you want
 *     an extra header-level gate.
 *
 * Both env vars are required; if either is unset, ALL inbound webhooks are
 * rejected (fail-closed). Denied attempts are logged with the remote IP so
 * intrusion attempts surface in audit.
 *
 * To use, attach via route middleware:
 *   Route::post('/webhooks/green-api', WebhookController::class)
 *       ->middleware(VerifyGreenApiSignature::class);
 *
 * Per-org variant (later): swap env() for a lookup on the route-bound
 * Organization (via organization.settings.green_api.webhook_secret).
 */
class VerifyGreenApiSignature
{
    public const SIGNATURE_HEADER = 'X-Green-Api-Signature';

    public function handle(Request $request, Closure $next): Response
    {
        $expectedSecret = (string) env('GREEN_API_WEBHOOK_SECRET', '');
        $expectedToken = (string) env('GREEN_API_WEBHOOK_TOKEN', '');
        $signature = (string) $request->header(self::SIGNATURE_HEADER, '');

        if ($signature !== '') {
            if ($expectedSecret === '' || ! $this->signatureMatches($request, $signature, $expectedSecret)) {
                return $this->deny($request, 'signature_mismatch');
            }

            return $next($request);
        }

        $providedToken = (string) $request->bearerToken();
        if ($expectedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            return $this->deny($request, 'bearer_mismatch');
        }

        return $next($request);
    }

    protected function signatureMatches(Request $request, string $signature, string $secret): bool
    {
        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }

    protected function deny(Request $request, string $reason): Response
    {
        Log::warning('greenapi.webhook.denied', [
            'reason' => $reason,
            'ip' => $request->ip(),
            'path' => $request->path(),
        ]);

        return response('unauthorized', 401);
    }
}
