<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\BirthdayGreetingService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class SendBirthdayGreetings extends Command
{
    protected $signature = 'birthdays:send-today';
    protected $description = 'Envía los saludos de cumpleaños a los usuarios que cumplen años hoy';

    public function handle(BirthdayGreetingService $service): int
    {
        $today = now();
        $users = User::query()
            ->select(['users.id', 'users.name', 'users.email'])
            ->where('is_internal', true)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereHas('employeeProfile', function (Builder $q) use ($today) {
                $q->whereMonth('birth_date', $today->month)
                  ->whereDay('birth_date', $today->day);
            })
            ->with(['employeeProfile'])
            ->get();

        $this->info("Usuarios que cumplen años hoy: " . $users->count());

        foreach ($users as $user) {
            $service->sendEmail($user);
            $this->line("Enviado a: {$user->name} <{$user->email}>");
        }

        return Command::SUCCESS;
    }
}
