<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Contracts\Support\Htmlable;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Wirechat\Wirechat\Enums\ConversationType;

class Chats extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;
    protected static string|Htmlable|null $navigationBadgeTooltip = 'Mensajes no leídos';
    
    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.my_apps');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.labels.my_chats');
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    protected string $view = 'filament.pages.chats';

   public function getHeading(): ?string
{
    return null;
}
    
    public function getTitle(): string
    {
        return 'Chats';
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return null;
        }

        $count = $user->conversations()
            ->where('type', ConversationType::PRIVATE)
            ->get()
            ->sum(fn ($conv) => $conv->getUnreadCountFor($user));

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return null;
        }

        $count = $user->conversations()
            ->where('type', ConversationType::PRIVATE)
            ->get()
            ->sum(fn ($conv) => $conv->getUnreadCountFor($user));

        return $count > 0 ? 'danger' : null;
    }
}
