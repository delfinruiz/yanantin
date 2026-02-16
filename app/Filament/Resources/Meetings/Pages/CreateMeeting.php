<?php

namespace App\Filament\Resources\Meetings\Pages;

use App\Filament\Resources\Meetings\MeetingResource;
use Filament\Resources\Pages\CreateRecord;
use Jubaer\Zoom\Facades\Zoom;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class CreateMeeting extends CreateRecord
{
    protected static string $resource = MeetingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        
        $zoomData = [
            'topic' => $data['topic'],
            'type' => $data['type'] ?? 2,
            'start_time' => isset($data['start_time']) ? Carbon::parse($data['start_time'])->toIso8601String() : Carbon::now()->toIso8601String(),
            'duration' => $data['duration'] ?? 40,
            'password' => $data['password'] ?? null,
            'agenda' => $data['description'] ?? '',
            'settings' => $data['settings'] ?? [],
        ];
        
        $data['start_time'] = isset($data['start_time']) ? Carbon::parse($data['start_time']) : Carbon::now();
        $data['duration'] = $data['duration'] ?? 40;
        
        try {
             $response = Zoom::createMeeting($zoomData);
             
             if ($response['status']) {
                 $meeting = $response['data'];
                 $data['zoom_id'] = (string) $meeting['id'];
                 $data['start_url'] = $meeting['start_url'];
                 $data['join_url'] = $meeting['join_url'];
                 $data['host_id'] = $user->id; 
                 // If type is 1 (Instant), set status to active immediately
                 $data['status'] = ($data['type'] == 1) ? 'active' : 'scheduled';
                 // Ensure settings are saved back if needed, or rely on what we sent
             } else {
                 Notification::make()
                    ->title('Zoom Error')
                    ->body($response['message'] ?? 'Unknown error creating meeting')
                    ->danger()
                    ->send();
                 
                 $this->halt();
             }
        } catch (\Exception $e) {
             Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
            $this->halt();
        }

        return $data;
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }
}