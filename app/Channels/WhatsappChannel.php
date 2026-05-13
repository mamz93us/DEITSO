<?php

declare(strict_types=1);

namespace App\Channels;

use App\Models\Organization;
use App\Services\Whatsapp\GreenApiClient;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Notification channel that dispatches a WhatsApp message via Green API.
 *
 * To send a notification through this channel, return ['whatsapp'] from your
 * notification's via() method and implement a public function toWhatsapp()
 * that returns ['phone' => '+201...', 'message' => '...'].
 *
 * Per-org Green API credentials live on the recipient's primary organization
 * (organization.settings.green_api.id_instance + .api_token). If credentials
 * are missing, the send is logged and skipped (so a missing config never
 * fails the whole notification batch).
 */
class WhatsappChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsapp')) {
            return;
        }

        /** @var array{phone: string, message: string, organization?: ?Organization} $payload */
        $payload = $notification->toWhatsapp($notifiable);
        if (empty($payload['phone']) || empty($payload['message'])) {
            return;
        }

        $org = $payload['organization'] ?? null;
        if (! $org) {
            // Try to infer org from the notifiable.
            $org = $this->resolveOrgFromNotifiable($notifiable);
        }

        $settings = (array) ($org?->settings['green_api'] ?? []);
        $idInstance = $settings['id_instance'] ?? null;
        $apiToken = $settings['api_token'] ?? null;

        if (! $idInstance || ! $apiToken) {
            Log::info('whatsapp.skip — missing green_api credentials', [
                'organization_id' => $org?->id,
                'recipient_phone' => $payload['phone'],
            ]);

            return;
        }

        $client = new GreenApiClient($idInstance, $apiToken);
        $result = $client->sendText($payload['phone'], $payload['message']);

        if ($result['status'] !== 'success') {
            Log::warning('whatsapp.send failed', [
                'organization_id' => $org?->id,
                'error' => $result['error'] ?? null,
            ]);
        }
    }

    protected function resolveOrgFromNotifiable(object $notifiable): ?Organization
    {
        if (method_exists($notifiable, 'organizations')) {
            return $notifiable->organizations()->first();
        }

        if (property_exists($notifiable, 'organization')) {
            return $notifiable->organization;
        }

        return null;
    }
}
