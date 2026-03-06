<?php

namespace App\Filament\Resources\JobOffers\Tables;

use App\Filament\Resources\JobOffers\JobOfferResource;
use App\Models\JobOffer;
use App\Events\JobOfferPublished;
use Filament\Actions\Action;
use Filament\Actions\CreateAction; // Unused
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;

class JobOffersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label(__('job_offers.columns.title') ?: 'Título')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Departamento')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('contract_type')
                    ->label(__('job_offers.columns.contract_type') ?: 'Tipo de contrato')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hierarchical_level')
                    ->label('Nivel')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('vacancies_count')
                    ->label('Vacantes')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('job_applications_count')
                    ->counts('jobApplications')
                    ->label('Postulantes')
                    ->sortable()
                    ->default(0)
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'info' : 'gray'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('job_offers.columns.is_active') ?: 'Activa')
                    ->boolean(),
                Tables\Columns\TextColumn::make('deadline')
                    ->label(__('job_offers.columns.deadline') ?: 'Fecha límite')
                    ->date()
                    ->badge()
                    ->color(fn (JobOffer $record) => $record->deadline <= now() ? 'danger' : ($record->deadline->isToday() ? 'warning' : 'success'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('job_offers.filters.active') ?: 'Activa'),
            ])
            ->recordActions([
                Action::make('view_applications')
                    ->label('Postulaciones')
                    ->icon('heroicon-o-users')
                    ->color('info')
                    ->disabled(fn (JobOffer $record) => $record->jobApplications()->count() === 0)
                    ->url(fn (JobOffer $record) => $record->jobApplications()->count() > 0 ? JobOfferResource::getUrl('applications', ['record' => $record]) : null),
                EditAction::make()
                    ->disabled(function (JobOffer $record) {
                        // Si hay una solicitud de cambio APROBADA para esta oferta, permitir edición INCLUSO si hay postulantes
                        $hasApprovedRequest = $record->changeRequests()
                            ->where('status', 'approved')
                            ->exists();
                        
                        if ($hasApprovedRequest) {
                            return false; // Habilitado
                        }

                        // Comportamiento normal: Bloquear si hay postulantes
                        return $record->jobApplications()->count() > 0;
                    }),
                Action::make('publish')
                    ->label(__('job_offers.actions.publish') ?: 'Publicar')
                    ->requiresConfirmation()
                    ->icon('heroicon-o-megaphone')
                    ->visible(fn (JobOffer $record) => ! $record->is_active)
                    ->action(function (JobOffer $record) {
                        $record->is_active = true;
                        $record->published_at = now();
                        $record->save();
                        event(new JobOfferPublished($record));
                    }),
                Action::make('unpublish')
                    ->label(__('job_offers.actions.unpublish') ?: 'Despublicar')
                    ->requiresConfirmation()
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (JobOffer $record) => $record->is_active)
                    ->action(function (JobOffer $record) {
                        $record->is_active = false;
                        $record->save();
                    }),
                DeleteAction::make()
                    ->disabled(fn (JobOffer $record) => $record->jobApplications()->count() > 0),
            ])
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
