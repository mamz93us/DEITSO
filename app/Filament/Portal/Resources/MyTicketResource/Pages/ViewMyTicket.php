<?php

declare(strict_types=1);

namespace App\Filament\Portal\Resources\MyTicketResource\Pages;

use App\Actions\Tickets\AddCommentToTicket;
use App\Filament\Portal\Resources\MyTicketResource;
use App\Models\Ticket;
use App\Models\TicketComment;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

/**
 * Chat-style ticket view. Header at the top, conversation timeline below,
 * compose box at the bottom. Internal staff notes are filtered out so the
 * end user only sees what's intended for them.
 */
class ViewMyTicket extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = MyTicketResource::class;

    protected static string $view = 'filament.portal.pages.view-my-ticket';

    public Ticket $record;

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public function mount(Ticket $record): void
    {
        // Livewire's URL-to-property hydration resolves `{record}` to a Ticket
        // model via Eloquent (global OrganizationScope applies, so cross-tenant
        // tickets 404 here). We re-verify ownership explicitly because
        // resolveRouteBinding does not run the resource's getEloquentQuery.
        abort_unless($record->requester_user_id === auth()->id(), 404);

        $this->record = $record;
        $this->form->fill();
    }

    public function getTitle(): string
    {
        return $this->record->code.' — '.$this->record->subject;
    }

    public function getHeading(): string
    {
        return $this->getTitle();
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Textarea::make('body')
                    ->label('Your message')
                    ->placeholder('Type a reply to support...')
                    ->rows(3)
                    ->required(),
                FileUpload::make('attachments')
                    ->label('Attach files (optional)')
                    ->multiple()
                    ->maxFiles(5)
                    ->preserveFilenames(),
            ]);
    }

    public function send(): void
    {
        $state = $this->form->getState();

        $comment = app(AddCommentToTicket::class)(
            $this->record,
            auth()->user(),
            $state['body'],
            TicketComment::AUTHOR_END_USER,
            false,
        );

        $files = $state['attachments'] ?? [];
        foreach ($files as $file) {
            $path = is_string($file) ? storage_path('app/'.$file) : null;
            if ($path && is_file($path)) {
                $comment->addMedia($path)->toMediaCollection('attachments');
            }
        }

        Notification::make()->title('Message sent')->success()->send();

        $this->form->fill();
        $this->dispatch('comment-added');
    }

    public function getComments(): Collection
    {
        return TicketComment::query()
            ->with(['user', 'media'])
            ->where('ticket_id', $this->record->id)
            ->where('is_internal', false)
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
