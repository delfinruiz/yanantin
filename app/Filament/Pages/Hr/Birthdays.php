<?php

namespace App\Filament\Pages\Hr;

use App\Models\User;
use App\Services\AiProviderService;
use App\Services\SettingService;
use Filament\Schemas\Components\Section;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use App\Mail\BirthdayGreetingMail;
use App\Services\BirthdayGreetingService;


class Birthdays extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationLabel = 'Cumpleaños';
    protected static ?string $title = 'Cumpleaños';
    protected string $view = 'filament.pages.hr.birthdays';

    public static function getNavigationGroup(): ?string
    {
        return __('filament-navigation.hr');
    }

    public static function canAccess(): bool
    {
        return Gate::allows('View:' . static::class);
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->select(['users.id', 'users.name'])
                    ->whereHas('emailAccount') // Solo usuarios internos (con correo interno asignado)
                    ->with([
                        'employeeProfile:id,user_id,birth_date',
                        'departments:id,name',
                        // relaciones adicionales si se requieren más adelante
                    ])
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre completo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->state('Interno'),
                TextColumn::make('employeeProfile.birth_date')
                    ->label('F. nacimiento')
                    ->state(function (User $record) {
                        $date = optional($record->employeeProfile)->birth_date;
                        return $date ? $date->format('d/m') : 'Sin dato';
                    })
                    ->badge()
                    ->color(fn ($state) => $state === 'Sin dato' ? 'danger' : 'gray')
                    ->sortable(),
                TextColumn::make('departments.name')
                    ->label('Departamento')
                    ->formatStateUsing(function ($state, User $record) {
                        $dep = optional($record->departments->first())->name;
                        return $dep ?: 'Público';
                    })
                    ->searchable()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('set_birth_date')
                    ->label('Fecha de nacimiento')
                    ->icon('heroicon-m-calendar')
                    ->color('warning')
                    ->visible(fn (User $record) => optional($record->employeeProfile)->birth_date === null)
                    ->schema([
                        DatePicker::make('birth_date')
                            ->label('Fecha de nacimiento')
                            ->native(false)
                            ->maxDate(now())
                            ->required(),
                    ])
                    ->fillForm(function (User $record) {
                        $profile = $record->employeeProfile;
                        return [
                            'birth_date' => $profile?->birth_date,
                        ];
                    })
                    ->action(function (array $data, User $record) {
                        $profile = $record->employeeProfile;
                        if (! $profile) {
                            $profile = $record->employeeProfile()->create([]);
                        }

                        $profile->birth_date = $data['birth_date'];
                        $profile->save();

                        Notification::make()
                            ->title('Fecha de nacimiento actualizada')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('name')
            ->headerActions([
                Action::make('saludo_global')
                    ->label('Saludo de cumpleaños')
                    ->icon('heroicon-m-sparkles')
                    ->modalHeading('Saludo de cumpleaños para todos')
                    ->modalDescription('Este mensaje se enviará automáticamente por correo a los usuarios internos en su cumpleaños. Puedes probarlo antes de guardar la plantilla.')
                    ->modalSubmitActionLabel('Guardar plantilla')
                    ->schema([
                        Section::make('Plantilla del saludo')
                            ->schema([
                                MarkdownEditor::make('content')
                                    ->label('Contenido del saludo')
                                    ->placeholder("Escribe el saludo en Markdown. Usa {{nombre}} y {{empresa}} para personalizar el mensaje.")
                                    ->helperText('Se enviará tal como lo veas aquí, remplazando {{nombre}} y {{empresa}} por los datos de cada usuario.')
                                    ->toolbarButtons([
                                        ['bold', 'italic', 'strike'],
                                        ['heading'],
                                        ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                                        ['table', 'attachFiles'],
                                        ['undo', 'redo'],
                                    ])
                                    ->fileAttachmentsDirectory('uploads/birthdays')
                                    ->columnSpanFull()
                                    ->required(),
                            ])
                            ->columns(1),
                        Section::make('Enviar correo de prueba')
                            ->schema([
                                TextInput::make('test_email')
                                    ->label('Correo de prueba')
                                    ->placeholder('usuario@ejemplo.com')
                                    ->email()
                                    ->maxLength(191)
                                    ->helperText('Ingresa un correo para enviarte este saludo como prueba.'),
                                SchemaActions::make([
                                    Action::make('ai_suggest')
                                        ->label('Sugerir con IA')
                                        ->icon('heroicon-m-sparkles')
                                        ->color('success')
                                        ->outlined()
                                        ->action(function (Set $schemaSet, AiProviderService $ai) {
                                            if (! $ai->hasToken()) {
                                                Notification::make()
                                                    ->title('IA no configurada')
                                                    ->body('Configura el token de IA en Ajustes antes de usar esta función.')
                                                    ->danger()
                                                    ->send();

                                                return;
                                            }

                                            try {
                                                $prompt = "Redacta una PLANTILLA de saludo de cumpleaños breve, cálida y profesional en ESPAÑOL en formato MARKDOWN. ".
                                                    "Debe incluir literalmente los placeholders {{nombre}} y {{empresa}} en el texto (no reemplazarlos), ".
                                                    "para que luego se sustituyan automáticamente. Usa párrafos y negritas si corresponde. ".
                                                    "No incluyas firmas ni datos confidenciales. Tono positivo y cercano.";

                                                $response = $ai->text()
                                                    ->using('openai', 'gpt-4o-mini')
                                                    ->withPrompt($prompt)
                                                    ->asText();

                                                $text = is_object($response) && property_exists($response, 'text')
                                                    ? $response->text
                                                    : (string) $response;

                                                $schemaSet('content', $text);
                                            } catch (\Throwable $e) {
                                                Log::error('Error al generar saludo de cumpleaños con IA', [
                                                    'exception' => $e,
                                                ]);

                                                Notification::make()
                                                    ->title('Error al llamar a la IA')
                                                    ->body('No se pudo generar el saludo automáticamente. Revisa la configuración del proveedor de IA.')
                                                    ->danger()
                                                    ->send();
                                            }
                                        }),
                                ]),
                                SchemaActions::make([
                                    Action::make('send_test')
                                        ->label('Enviar prueba')
                                        ->icon('heroicon-m-paper-airplane')
                                        ->color('info')
                                        ->action(function (\Filament\Schemas\Components\Utilities\Get $schemaGet, SettingService $settings, BirthdayGreetingService $service) {
                                            $email = trim((string) $schemaGet('test_email'));
                                            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                                Notification::make()
                                                    ->title('Correo inválido')
                                                    ->body('Ingresa un correo válido para la prueba.')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            try {
                                                $template = (string) $schemaGet('content');
                                                if ($template === '') {
                                                    $template = (string) $settings->get('birthday_greeting_template', '');
                                                }
                                                if ($template === '') {
                                                    $template = $service->getDefaultTemplate();
                                                }

                                                $settings->update([
                                                    'birthday_greeting_template' => $template,
                                                ]);

                                                $company = $settings->get('company_name', config('app.name'));
                                                $subject = "Prueba de saludo de cumpleaños — {$company}";

                                                $html = $service->renderFromTemplate($template, 'Usuario de Prueba', $company);

                                                Mail::to($email)->send(new BirthdayGreetingMail($subject, $html));

                                                Notification::make()
                                                    ->title('Enviado')
                                                    ->body("Se envió un correo de prueba a {$email}.")
                                                    ->success()
                                                    ->send();
                                            } catch (\Throwable $e) {
                                                Log::error('Error enviando prueba de saludo', ['exception' => $e]);
                                                Notification::make()
                                                    ->title('Error al enviar')
                                                    ->body('No se pudo enviar el correo de prueba. Revisa la configuración de correo.')
                                                    ->danger()
                                                    ->send();
                                            }
                                        }),
                                ])->columns(2),
                            ])
                            ->columns(1),
                    ])
                    ->fillForm(function () {
                        $settings = app(SettingService::class);
                        return [
                            'content' => (string) $settings->get('birthday_greeting_template', ''),
                        ];
                    })
                    ->action(function (array $data) {
                        $settings = app(SettingService::class);
                        $settings->update([
                            'birthday_greeting_template' => $data['content'],
                        ]);
                        Notification::make()
                            ->title('Plantilla guardada')
                            ->success()
                            ->send();
                    })
                    ->modalWidth('7xl'),
            ])
            ->paginated([25, 50, 100])
            ->deferLoading()
            ->searchable()
            ->emptyStateHeading('Sin usuarios')
            ->emptyStateDescription('No hay usuarios para mostrar.');
    }
}
