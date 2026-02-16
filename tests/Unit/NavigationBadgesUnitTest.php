<?php

use Illuminate\Support\Facades\View;

it('blade de chat incluye wire:poll', function () {
    $html = View::make('livewire.chat.unread-counter')->render();
    expect($html)->toContain('wire:poll');
});

it('blade de eventos incluye wire:poll', function () {
    $html = View::make('livewire.event-badge-poll')->render();
    expect($html)->toContain('wire:poll');
});

it('blade de encuestas incluye wire:poll', function () {
    $html = View::make('livewire.surveys.pending-badge-poll')->render();
    expect($html)->toContain('wire:poll');
});

it('blade de reuniones incluye wire:poll', function () {
    $html = View::make('livewire.meetings.meetings-badge-poll')->render();
    expect($html)->toContain('wire:poll');
});

it('blade de tareas incluye wire:poll', function () {
    $html = View::make('livewire.tasks.tasks-badge-poll')->render();
    expect($html)->toContain('wire:poll');
});

it('panel inyecta livewire para reuniones y tareas', function () {
    $path = base_path('app/Providers/Filament/AdminPanelProvider.php');
    $code = file_get_contents($path);
    expect($code)->toContain("@livewire('meetings.meetings-badge-poll')");
    expect($code)->toContain("@livewire('tasks.tasks-badge-poll')");
});

