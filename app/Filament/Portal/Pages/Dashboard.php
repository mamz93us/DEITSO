<?php

declare(strict_types=1);

namespace App\Filament\Portal\Pages;

use App\Filament\Portal\Resources\MyAssetResource;
use App\Filament\Portal\Resources\MyRequestResource;
use App\Filament\Portal\Resources\MyTicketResource;
use App\Models\Asset;
use App\Models\EmployeeRequest;
use App\Models\Ticket;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

/**
 * Custom self-service dashboard for the employee portal. Shows a personalised
 * welcome, four KPI tiles (open tickets, pending requests, assigned assets,
 * resolved this month), quick-action CTAs, and condensed lists of the user's
 * most recent tickets / requests / assets.
 */
class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Home';

    protected static ?int $navigationSort = -10;

    protected static string $view = 'filament.portal.pages.dashboard';

    protected static ?string $slug = '/';

    public function getTitle(): string
    {
        return 'Welcome back, '.(auth()->user()?->name ?? 'there');
    }

    public function getHeading(): string
    {
        return $this->getTitle();
    }

    /**
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        $userId = auth()->id();
        $employee = auth()->user()?->employee;

        $openTickets = Ticket::query()
            ->where('requester_user_id', $userId)
            ->whereNotIn('state', ['resolved', 'closed', 'cancelled'])
            ->count();

        $resolvedThisMonth = Ticket::query()
            ->where('requester_user_id', $userId)
            ->whereIn('state', ['resolved', 'closed'])
            ->where('updated_at', '>=', now()->startOfMonth())
            ->count();

        $pendingRequests = $employee
            ? EmployeeRequest::query()
                ->where('requester_employee_id', $employee->id)
                ->whereNotIn('state', ['fulfilled', 'cancelled', 'manager_rejected', 'admin_rejected'])
                ->count()
            : 0;

        $myAssets = $employee
            ? Asset::query()->where('assigned_employee_id', $employee->id)->count()
            : 0;

        return [
            'open_tickets' => $openTickets,
            'pending_requests' => $pendingRequests,
            'my_assets' => $myAssets,
            'resolved_this_month' => $resolvedThisMonth,
        ];
    }

    public function getRecentTickets(): Collection
    {
        return Ticket::query()
            ->where('requester_user_id', auth()->id())
            ->latest('created_at')
            ->limit(5)
            ->get();
    }

    public function getRecentRequests(): Collection
    {
        $employee = auth()->user()?->employee;
        if (! $employee) {
            return new Collection;
        }

        return EmployeeRequest::query()
            ->where('requester_employee_id', $employee->id)
            ->latest('created_at')
            ->limit(5)
            ->get();
    }

    public function getMyAssetsList(): Collection
    {
        $employee = auth()->user()?->employee;
        if (! $employee) {
            return new Collection;
        }

        return Asset::query()
            ->with(['category', 'assetModel'])
            ->where('assigned_employee_id', $employee->id)
            ->limit(6)
            ->get();
    }

    /**
     * @return array<string, string>
     */
    public function getQuickLinks(): array
    {
        return [
            'new_ticket' => MyTicketResource::getUrl('create'),
            'all_tickets' => MyTicketResource::getUrl('index'),
            'new_request' => MyRequestResource::getUrl('create'),
            'all_requests' => MyRequestResource::getUrl('index'),
            'all_assets' => MyAssetResource::getUrl('index'),
        ];
    }
}
