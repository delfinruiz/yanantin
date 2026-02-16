<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Calendar;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\Survey;
use App\Models\Question;
use App\Filament\Pages\MySurveys;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\Meetings\MeetingResource;
use App\Filament\Resources\Tasks\TaskResource;
use Livewire\Livewire;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

it('actualiza badge de encuestas (resource y livewire)', function () {
    $user = User::factory()->create();
    Auth::login($user);

    $survey = Survey::factory()->create(['active' => true]);
    $survey->users()->syncWithoutDetaching([$user->id => ['assigned_at' => now()]]);
    Question::create([
        'survey_id' => $survey->id,
        'item' => 'Req',
        'type' => 'likert',
        'required' => true,
        'options' => ['1' => 'No', '2' => 'Sí'],
    ]);

    expect(MySurveys::getNavigationBadge())->toBe('1');

    Livewire::test(\App\Livewire\Surveys\PendingBadgePoll::class)
        ->call('updateBadge')
        ->assertSet('badgeContent', '1')
        ->assertSet('shouldShow', true);
});

it('actualiza badge de eventos (resource y livewire)', function () {
    $calendar = Calendar::create(['name' => 'Pública', 'is_public' => true]);
    $event = Event::create([
        'calendar_id' => $calendar->id,
        'title' => 'Hoy',
        'starts_at' => now(),
        'ends_at' => null,
        'all_day' => false,
    ]);

    expect(EventResource::getNavigationBadge())->toBe('1');

    Livewire::test(\App\Livewire\EventBadgePoll::class)
        ->call('updateBadge')
        ->assertSet('badgeContent', '1')
        ->assertSet('shouldShow', true);
});

it('actualiza badge de reuniones (resource y livewire)', function () {
    $user = User::factory()->create();
    Auth::login($user);

    $meeting = Meeting::create([
        'title' => 'Reunión',
        'start_time' => now(),
        'status' => 'scheduled',
        'type' => 2,
        'host_id' => $user->id,
    ]);

    expect(MeetingResource::getNavigationBadge())->toBe('1');

    Livewire::test(\App\Livewire\Meetings\MeetingsBadgePoll::class)
        ->call('updateBadge')
        ->assertSet('badgeContent', '1')
        ->assertSet('shouldShow', true);
});

it('actualiza badge de tareas (resource y livewire)', function () {
    Task::create([
        'title' => 'Tarea',
        'due_date' => now(),
        'status_id' => 1, // != 2
    ]);

    expect(TaskResource::getNavigationBadge())->toBe('1');

    Livewire::test(\App\Livewire\Tasks\TasksBadgePoll::class)
        ->call('updateBadge')
        ->assertSet('badgeContent', '1')
        ->assertSet('shouldShow', true);
});

it('unread counter de chat muestra 0 para invitado', function () {
    Auth::logout();
    Livewire::test(\App\Livewire\Chat\UnreadCounter::class)
        ->call('updateCount')
        ->assertSet('count', 0);
});
