<?php

namespace App\Filament\Resources\JobOfferChangeRequests;

use App\Filament\Resources\JobOfferChangeRequests\Pages\CreateJobOfferChangeRequest;
use App\Filament\Resources\JobOfferChangeRequests\Pages\EditJobOfferChangeRequest;
use App\Filament\Resources\JobOfferChangeRequests\Pages\ListJobOfferChangeRequests;
use App\Filament\Resources\JobOfferChangeRequests\Schemas\JobOfferChangeRequestForm;
use App\Filament\Resources\JobOfferChangeRequests\Tables\JobOfferChangeRequestsTable;
use App\Models\JobOfferChangeRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class JobOfferChangeRequestResource extends Resource
{
    protected static ?string $model = JobOfferChangeRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentCheck;

    public static function getNavigationGroup(): ?string
    {
        return 'Gestión Laboral';
    }

    public static function getNavigationLabel(): string
    {
        return 'Solicitudes de Modificación';
    }

    public static function getModelLabel(): string
    {
        return 'Solicitud de Modificación';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Solicitudes de Modificación';
    }

    public static function getNavigationSort(): ?int
    {
        return 6;
    }

    public static function getNavigationBadge(): ?string
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        // Si es Super Admin o RRHH, ve todo lo pendiente
        if ($user->hasRole(['super_admin', 'recursos_humanos'])) {
             return static::getModel()::where('status', 'pending')->count() ?: null;
        }

        // Si no es admin, verificar si es jefe de alguien
        // Asumimos que si tiene subordinados, debe ver las solicitudes de ellos
        // Primero obtenemos los IDs de sus subordinados directos
        $subordinateIds = \App\Models\EmployeeProfile::where('reports_to', $user->id)
            ->pluck('user_id')
            ->toArray();

        if (!empty($subordinateIds)) {
            // Contar solicitudes pendientes donde el solicitante sea uno de sus subordinados
            $count = static::getModel()::where('status', 'pending')
                ->whereIn('requester_id', $subordinateIds)
                ->count();
            
            return $count > 0 ? (string) $count : null;
        }

        // Si es empleado normal (sin subordinados y no admin), no ve badge
        return null;
    }

    public static function form(Schema $schema): Schema
    {
        return JobOfferChangeRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JobOfferChangeRequestsTable::configure($table);
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
            'index' => ListJobOfferChangeRequests::route('/'),
        //    'create' => CreateJobOfferChangeRequest::route('/create'),
        //    'edit' => EditJobOfferChangeRequest::route('/{record}/edit'),
        ];
    }
}
