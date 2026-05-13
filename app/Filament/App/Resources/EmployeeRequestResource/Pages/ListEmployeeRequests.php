<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\EmployeeRequestResource\Pages;

use App\Filament\App\Resources\EmployeeRequestResource;
use App\Models\EmployeeRequest;
use App\Models\States\EmployeeRequest\AdminApproved;
use App\Models\States\EmployeeRequest\AdminRejected;
use App\Models\States\EmployeeRequest\Cancelled;
use App\Models\States\EmployeeRequest\Fulfilled;
use App\Models\States\EmployeeRequest\InProcurement;
use App\Models\States\EmployeeRequest\ManagerApproved;
use App\Models\States\EmployeeRequest\ManagerRejected;
use App\Models\States\EmployeeRequest\Submitted;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListEmployeeRequests extends ListRecords
{
    protected static string $resource = EmployeeRequestResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),

            'awaiting_my_approval' => Tab::make('Awaiting my approval')
                ->modifyQueryUsing(function (Builder $q) {
                    return $q->where(function (Builder $q) {
                        $q->where('state', Submitted::class)
                            ->orWhere('state', ManagerApproved::class);
                    });
                })
                ->badge(fn () => EmployeeRequest::query()
                    ->whereIn('state', [Submitted::class, ManagerApproved::class])
                    ->count()),

            'in_procurement' => Tab::make('In procurement')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereIn('state', [AdminApproved::class, InProcurement::class]))
                ->badge(fn () => EmployeeRequest::query()
                    ->whereIn('state', [AdminApproved::class, InProcurement::class])
                    ->count()),

            'fulfilled' => Tab::make('Fulfilled')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('state', Fulfilled::class)),

            'cancelled_rejected' => Tab::make('Cancelled / Rejected')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereIn('state', [
                    Cancelled::class,
                    ManagerRejected::class,
                    AdminRejected::class,
                ])),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New request'),
        ];
    }
}
