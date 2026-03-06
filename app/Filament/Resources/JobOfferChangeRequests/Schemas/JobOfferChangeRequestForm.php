<?php

namespace App\Filament\Resources\JobOfferChangeRequests\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;  // ← Correcto para Forms
use Illuminate\Support\Facades\Auth;

class JobOfferChangeRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Detalle de la Solicitud')
                    ->schema([
                        Select::make('job_offer_id')
                            ->relationship('jobOffer', 'title')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "#{$record->id} - {$record->title}")
                            ->searchable(['title', 'id'])
                            ->label('Oferta Laboral')
                            ->required()
                            ->columnSpanFull(),
                        
                        Select::make('requester_id')
                            ->relationship('requester', 'name')
                            ->label('Solicitante')
                            ->default(fn () => Auth::id())
                            ->disabled()
                            ->dehydrated()
                            ->required(),

                        TextInput::make('requested_at_display')
                            ->label('Fecha de Solicitud')
                            ->disabled()
                            ->readOnly()
                            ->dehydrated(false)
                            ->default(fn () => now()->format('d/m/Y H:i'))
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record) {
                                    $component->state($record->requested_at?->format('d/m/Y H:i'));
                                }
                            }),

                        Textarea::make('reason')
                            ->label('Motivo del Cambio')
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('justification')
                            ->label('Justificación')
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2)
                    ->collapsible()
                    ->columnSpanFull(),

                Section::make('Estado')
                    ->collapsible()
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('status_display')
                            ->label('Estado Actual')
                            ->disabled()
                            ->readOnly()
                            ->dehydrated(false)
                            ->default('Pendiente (Se creará al guardar)')
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record) {
                                    $component->state(match($record->status) {
                                        'pending' => 'Pendiente de Aprobación',
                                        'approved' => 'Aprobada (Pendiente de Edición)',
                                        'rejected' => 'Rechazada',
                                        'completed' => 'Completada (Cambios Aplicados)',
                                        default => $record->status,
                                    });
                                }
                            })
                            ->extraAttributes(fn ($record) => [
                                'class' => $record ? match($record->status) {
                                    'pending' => 'text-warning-600 font-bold',
                                    'approved' => 'text-success-600 font-bold',
                                    'rejected' => 'text-danger-600 font-bold',
                                    'completed' => 'text-primary-600 font-bold',
                                    default => '',
                                } : 'text-gray-500 italic'
                            ]),
                    ]),

                Section::make('Resolución')
                    ->collapsible()
                    ->columnSpanFull()
                    ->visible(fn ($record) => $record?->approver_id !== null || $record?->approved_at !== null)
                    ->schema([
                        Select::make('approver_id')
                            ->relationship('approver', 'name')
                            ->label('Aprobado/Rechazado por')
                            ->disabled()
                            ->visible(fn ($record) => $record?->approver_id !== null),

                        TextInput::make('approved_at')
                            ->label('Fecha de Resolución')
                            ->disabled()
                            ->readOnly()
                            ->dehydrated(false)
                            ->default(fn ($record) => $record?->approved_at?->format('d/m/Y H:i'))
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record && $record->approved_at) {
                                    $component->state($record->approved_at->format('d/m/Y H:i'));
                                }
                            })
                            ->visible(fn ($record) => $record?->approved_at !== null),
                    ])->columns(2),
            ])->columns(1);
    }
}
