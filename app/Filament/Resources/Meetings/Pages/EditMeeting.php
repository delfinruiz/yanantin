<?php

namespace App\Filament\Resources\Meetings\Pages;

use App\Filament\Resources\Meetings\MeetingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Services\ZoomService;
use App\Mail\MeetingCanceled;
use Illuminate\Support\Facades\Mail;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;
use Jubaer\Zoom\Facades\Zoom;

class EditMeeting extends EditRecord
{
    protected static string $resource = MeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function ($record) {
                    if ($record->zoom_id) {
                         try {
                             if ($record->host && $record->host->email) {
                                 Mail::to($record->host->email)->send(new MeetingCanceled($record));
                             }

                             $zoomService = new ZoomService();
                             $response = $zoomService->deleteMeeting($record->zoom_id, ['schedule_for_reminder' => 'false']);

                             if (!$response['status']) {
                                 Notification::make()
                                    ->title('Zoom Delete Error')
                                    ->body($response['message'])
                                    ->warning()
                                    ->send();
                             }
                         } catch (\Exception $e) {
                                 Notification::make()
                                    ->title('Zoom Delete Error')
                                    ->body($e->getMessage())
                                    ->warning()
                                    ->send();
                         }
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!empty($data['zoom_id'])) {
             $zoomData = [
                'topic' => $data['topic'],
                'type' => $data['type'] ?? 2, 
                'start_time' => isset($data['start_time']) ? Carbon::parse($data['start_time'])->toIso8601String() : Carbon::now()->toIso8601String(),
                'duration' => $data['duration'] ?? 60,
                'password' => $data['password'] ?? null,
                'agenda' => $data['description'] ?? '',
                'settings' => $data['settings'] ?? [],
            ];
            
            try {
                 $response = Zoom::updateMeeting($data['zoom_id'], $zoomData);
                 
                 if (!$response['status']) {
                     Notification::make()
                        ->title('Zoom Update Warning')
                        ->body('Could not update meeting in Zoom: ' . ($response['message'] ?? 'Unknown error'))
                        ->warning()
                        ->send();
                 }
            } catch (\Exception $e) {
                 Notification::make()
                    ->title('Zoom Update Error')
                    ->body($e->getMessage())
                    ->warning()
                    ->send();
            }
        }

        return $data;
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }
}
