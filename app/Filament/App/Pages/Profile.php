<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Self-service user profile page. Covers display name, locale/timezone, password
 * change, and per-channel notification preferences (mail, WhatsApp). The
 * underlying columns are added by the 2026_05_14_080000 migration.
 */
class Profile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 99;

    protected static ?string $navigationLabel = 'My profile';

    protected static string $view = 'filament.app.pages.profile';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public function mount(): void
    {
        $user = Auth::user();
        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
            'locale' => $user->locale ?? 'en',
            'timezone' => $user->timezone ?? config('app.timezone'),
            'notify_via_mail' => (bool) ($user->notify_via_mail ?? true),
            'notify_via_whatsapp' => (bool) ($user->notify_via_whatsapp ?? false),
            'whatsapp_phone' => $user->whatsapp_phone,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Section::make('Account')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->required()->maxLength(255),
                        TextInput::make('email')->email()->required()->maxLength(255)->disabled()->dehydrated(false),
                        Select::make('locale')->options(['en' => 'English', 'ar' => 'العربية'])->required(),
                        TextInput::make('timezone')->required()->maxLength(64)->placeholder('Africa/Cairo'),
                    ]),
                Section::make('Notifications')
                    ->description('Choose how we contact you. In-app notifications are always on.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('notify_via_mail')->label('Email me notifications')->default(true),
                        Toggle::make('notify_via_whatsapp')->label('WhatsApp me notifications')->default(false),
                        TextInput::make('whatsapp_phone')
                            ->label('WhatsApp number (E.164, e.g. +201234567890)')
                            ->tel()
                            ->maxLength(32)
                            ->visible(fn (callable $get) => (bool) $get('notify_via_whatsapp')),
                    ]),
                Section::make('Change password')
                    ->columns(2)
                    ->schema([
                        TextInput::make('current_password')->password()->revealable()->dehydrated(false),
                        TextInput::make('new_password')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->dehydrated(false)
                            ->same('new_password_confirmation'),
                        TextInput::make('new_password_confirmation')
                            ->password()
                            ->revealable()
                            ->dehydrated(false)
                            ->label('Confirm new password'),
                    ]),
            ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $user = Auth::user();

        $update = [
            'name' => $state['name'],
            'locale' => $state['locale'],
            'timezone' => $state['timezone'],
            'notify_via_mail' => (bool) ($state['notify_via_mail'] ?? false),
            'notify_via_whatsapp' => (bool) ($state['notify_via_whatsapp'] ?? false),
            'whatsapp_phone' => $state['whatsapp_phone'] ?? null,
        ];

        $current = request()->input('data.current_password');
        $new = request()->input('data.new_password');

        if ($new) {
            if (! $current || ! Hash::check($current, $user->password)) {
                Notification::make()->title('Current password is incorrect')->danger()->send();

                return;
            }
            $update['password'] = Hash::make($new);
        }

        $user->update($update);

        Notification::make()->title('Profile updated')->success()->send();
    }
}
