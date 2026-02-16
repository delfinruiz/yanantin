<?php

namespace App\Filament\Resources\AbsenceRequests;

use App\Filament\Resources\AbsenceRequests\Pages\CreateAbsenceRequest;
use App\Filament\Resources\AbsenceRequests\Pages\EditAbsenceRequest;
use App\Filament\Resources\AbsenceRequests\Pages\ListAbsenceRequests;
use App\Filament\Resources\AbsenceRequests\Schemas\AbsenceRequestForm;
use App\Filament\Resources\AbsenceRequests\Tables\AbsenceRequestsTable;
use App\Models\AbsenceRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class AbsenceRequestResource extends Resource
{
    protected static ?string $model = AbsenceRequest::class;

    public static function getModelLabel(): string
    {
        return __('absence_requests.title_singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('absence_requests.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('absence_requests.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.human_resources');
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return AbsenceRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AbsenceRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAbsenceRequests::route('/'),
         //   'create' => CreateAbsenceRequest::route('/create'),
         //   'edit' => EditAbsenceRequest::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        $count = 0;

        // 1. Supervisores de departamento: ven solicitudes pendientes de su equipo
        if ($user->supervisedDepartments()->exists()) {
            $count += AbsenceRequest::query()
                ->accessibleBy($user)
                ->where('status', 'pending')
                ->count();
        }

        // 2. Rol aprobador_vacaciones: ven solicitudes aprobadas por supervisor
        if ($user->hasRole(['aprobador_vacaciones'])) {
            $count += AbsenceRequest::query()
                ->where('status', 'approved_supervisor')
                ->count();
        }

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
