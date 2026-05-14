<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Fired when a ticket breaches its SLA response or resolution deadline.
 * Channels: mail + database (in-app). Respects per-user notification prefs
 * via the User::wantsChannel() helper.
 */
class TicketSlaBreached extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public string $breachType, // 'response' | 'resolution'
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if (method_exists($notifiable, 'wantsChannel') ? $notifiable->wantsChannel('mail') : true) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = $this->breachType === 'response' ? 'Response' : 'Resolution';
        $due = $this->breachType === 'response'
            ? $this->ticket->sla_response_due_at
            : $this->ticket->sla_resolution_due_at;

        return (new MailMessage)
            ->subject(sprintf('[SLA breach] %s — %s deadline missed', $this->ticket->code, $label))
            ->greeting("Ticket {$this->ticket->code}: {$this->ticket->subject}")
            ->line("{$label} SLA deadline passed: ".$due?->format('Y-m-d H:i'))
            ->line('Please act on this ticket as soon as possible.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_code' => $this->ticket->code,
            'subject' => $this->ticket->subject,
            'breach_type' => $this->breachType,
        ];
    }
}
