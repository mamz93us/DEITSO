<?php

declare(strict_types=1);

namespace App\Actions\Tickets;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Adds a comment to a ticket. Records first_response_at the first time staff
 * posts a non-internal comment so SLA-response breach can be evaluated.
 */
class AddCommentToTicket
{
    public function __invoke(
        Ticket $ticket,
        User $author,
        string $body,
        string $authorType = TicketComment::AUTHOR_STAFF,
        bool $isInternal = false,
    ): TicketComment {
        return DB::transaction(function () use ($ticket, $author, $body, $authorType, $isInternal) {
            $comment = TicketComment::create([
                'ticket_id' => $ticket->id,
                'user_id' => $author->id,
                'author_type' => $authorType,
                'body' => $body,
                'is_internal' => $isInternal,
            ]);

            $isFirstStaffPublicResponse = $authorType === TicketComment::AUTHOR_STAFF
                && ! $isInternal
                && $ticket->first_response_at === null;

            if ($isFirstStaffPublicResponse) {
                $ticket->update(['first_response_at' => Carbon::now()]);
            }

            return $comment;
        });
    }
}
