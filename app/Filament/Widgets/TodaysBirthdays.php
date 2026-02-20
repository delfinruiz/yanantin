<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class TodaysBirthdays extends Widget
{
    protected string $view = 'filament.widgets.todays-birthdays';
    protected int|string|array $columnSpan = 4;
    protected static ?int $sort = 5;

    public static function canView(): bool
    {
        $today = now();

        return User::query()
            ->whereHas('emailAccount')
            ->whereHas('employeeProfile', function (Builder $q) use ($today) {
                $q->whereMonth('birth_date', $today->month)
                    ->whereDay('birth_date', $today->day);
            })
            ->exists();
    }

    protected function getViewData(): array
    {
        $today = now();

        $birthdays = User::query()
            ->whereHas('emailAccount')
            ->whereHas('employeeProfile', function (Builder $q) use ($today) {
                $q->whereMonth('birth_date', $today->month)
                    ->whereDay('birth_date', $today->day);
            })
            ->with(['employeeProfile:id,user_id,birth_date', 'departments:id,name'])
            ->orderBy('name')
            ->get()
            ->map(function (User $user) {
                $profile = $user->employeeProfile;
                $department = optional($user->departments->first())->name ?: 'Público';
                $birthDate = $profile?->birth_date ? $profile->birth_date->format('d/m') : null;

                return [
                    'name' => $user->name,
                    'department' => $department,
                    'birth_date' => $birthDate,
                ];
            });

        return [
            'birthdays' => $birthdays,
        ];
    }
}
