<?php

namespace App\Filament\Resources\Meetings\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Html;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MeetingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 3])
            ->components([
                Section::make(__('meetings.details'))
                    ->columnSpan(['default' => 1, 'lg' => 2])
                    ->schema([
                        TextInput::make('topic')
                            ->label(__('meetings.topic'))
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label(__('meetings.description'))
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Select::make('type')
                            ->label(__('meetings.type'))
                            ->options([
                                2 => __('meetings.types.scheduled'),
                                1 => __('meetings.types.instant'),
                            ])
                            ->default(2)
                            ->required()
                            ->reactive(),
                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('start_time')
                                    ->label(__('meetings.start_time'))
                                    ->required()
                                    ->native(false)
                                    ->hidden(fn ($get) => $get('type') == 1),
                                TextInput::make('duration')
                                    ->label(__('meetings.duration'))
                                    ->numeric()
                                    ->required()
                                    ->default(40)
                                    ->maxValue(40)
                                    ->hidden(fn ($get) => $get('type') == 1),
                            ]),
                        TextInput::make('password')
                            ->label(__('meetings.password'))
                            ->password()
                            ->revealable(),
                    ]),
                Group::make()
                    ->columnSpan(['default' => 1, 'lg' => 1])
                    ->schema([
                        Section::make(__('meetings.zoom_settings'))
                            ->schema([
                                Toggle::make('settings.join_before_host')
                                    ->label(__('meetings.settings.join_before_host')),
                                Toggle::make('settings.host_video')
                                    ->label(__('meetings.settings.host_video')),
                                Toggle::make('settings.participant_video')
                                    ->label(__('meetings.settings.participant_video')),
                                Toggle::make('settings.mute_upon_entry')
                                    ->label(__('meetings.settings.mute_upon_entry')),
                                Toggle::make('settings.waiting_room')
                                    ->label(__('meetings.settings.waiting_room')),
                            ])->columns(1),
                        Section::make(__('meetings.important_info.title'))
                            ->schema([
                                Html::make(function () {
                                    $items = __('meetings.important_info.items');
                                    if (!is_array($items)) return '';
                                    
                                    $html = '<ul style="list-style-type: disc; padding-left: 20px; color: #ef4444;">';
                                    foreach ($items as $item) {
                                        $html .= "<li>{$item}</li>";
                                    }
                                    $html .= '</ul>';
                                    
                                    return new \Illuminate\Support\HtmlString($html);
                                }),
                            ]),
                    ]),
            ]);
    }
}
