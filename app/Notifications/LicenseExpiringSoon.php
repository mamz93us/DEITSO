<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Asset;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class LicenseExpiringSoon extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Collection<int, Asset>  $expiring
     * @param  Collection<int, Asset>  $expired
     */
    public function __construct(
        public Organization $organization,
        public Collection $expiring,
        public Collection $expired,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $msg = (new MailMessage)
            ->subject(sprintf('[%s] License expiry — %d soon, %d expired',
                $this->organization->name,
                $this->expiring->count(),
                $this->expired->count(),
            ));

        if ($this->expired->isNotEmpty()) {
            $msg->line('**Expired licenses:**');
            foreach ($this->expired as $l) {
                $msg->line(sprintf('  • %s (expired %s)', $l->code, $l->expiry_date?->format('Y-m-d')));
            }
        }

        if ($this->expiring->isNotEmpty()) {
            $msg->line('**Expiring within threshold:**');
            foreach ($this->expiring as $l) {
                $msg->line(sprintf('  • %s (expires %s)', $l->code, $l->expiry_date?->format('Y-m-d')));
            }
        }

        return $msg;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'organization_id' => $this->organization->id,
            'expiring_count' => $this->expiring->count(),
            'expired_count' => $this->expired->count(),
            'expiring_ids' => $this->expiring->pluck('id')->all(),
            'expired_ids' => $this->expired->pluck('id')->all(),
        ];
    }
}
