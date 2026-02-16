<?php

namespace App\Filament\Pages\FormBuilder;

use App\FormBuilder\FormDefinition;
use App\Services\FormBuilder\FormStorage;
use App\FormBuilder\Theme;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Schema;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class ManageForms extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;
    use HasPageShield;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected string $view = 'filament.pages.formbuilder.manage-forms';
    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('filament-navigation.tools');
    }

    public static function getNavigationLabel(): string
    {
        return __('formbuilder.manage_forms');
    }

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return __('formbuilder.manage_forms');
    }

    public array $themes = [];
    public array $forms = [];
    public ?string $embedFormId = null;

    public function mount(FormStorage $storage): void
    {
        $this->themes = $storage->listThemes();
        $this->forms = $storage->listForms();
        
        if (empty($this->data)) {
            $this->data = [
                'id' => null,
                'name' => __('formbuilder.new_form'),
                'slug' => null,
                'version' => 1,
                'themeId' => null,
                'elements' => [],
                'button' => [
                    'label' => __('formbuilder.submit'),
                    'bg_color' => '#288cfa',
                    'text_color' => '#ffffff',
                ],
            ];
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->createThemeAction(),
        ];
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function createThemeAction(): Action
    {
        return Action::make('createTheme')
            ->label(__('formbuilder.themes'))
            ->icon('heroicon-o-paint-brush')
            ->modalHeading(__('formbuilder.theme_manager'))
            ->modalSubmitActionLabel(__('formbuilder.save'))
            ->schema([
                Select::make('theme_id')
                    ->label(__('formbuilder.edit_theme_placeholder'))
                    ->options($this->themeOptions())
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        if ($state) {
                            $storage = app(FormStorage::class);
                            $theme = $storage->getTheme($state);
                            if ($theme) {
                                $data = $theme->toArray();
                                $set('name', $data['name']);
                                $set('colors.primary', $data['tokens']['colors']['primary'] ?? '#3b82f6');
                                $set('colors.secondary', $data['tokens']['colors']['secondary'] ?? '#1e293b');
                                $set('colors.text', $data['tokens']['colors']['text'] ?? '#0f172a');
                                $set('colors.background', $data['tokens']['colors']['background'] ?? '#ffffff');
                                $set('colors.page', $data['tokens']['colors']['page'] ?? '#f8fafc');
                                $set('fonts.base', $data['tokens']['fonts']['base'] ?? 'sans-serif');
                                $set('radius.md', $data['tokens']['radius']['md'] ?? '0.5rem');
                            }
                        } else {
                            $set('name', '');
                            $set('colors.primary', '#3b82f6');
                            $set('colors.secondary', '#1e293b');
                            $set('colors.text', '#0f172a');
                            $set('colors.background', '#ffffff');
                            $set('colors.page', '#f8fafc');
                            $set('fonts.base', 'sans-serif');
                            $set('radius.md', '0.5rem');
                        }
                    }),
                TextInput::make('name')->label(__('formbuilder.theme_name'))->required(),
                Section::make(__('formbuilder.colors'))->schema([
                    ColorPicker::make('colors.primary')->label(__('formbuilder.primary_color'))->default('#3b82f6'),
                    ColorPicker::make('colors.secondary')->label(__('formbuilder.secondary_color'))->default('#1e293b'),
                    ColorPicker::make('colors.text')->label(__('formbuilder.text_color'))->default('#0f172a'),
                    ColorPicker::make('colors.background')->label(__('formbuilder.background_color'))->default('#ffffff'),
                    ColorPicker::make('colors.page')->label(__('formbuilder.page_background_color'))->default('#f8fafc'),
                ])->columns(2),
                Section::make(__('formbuilder.styles'))->schema([
                    Select::make('fonts.base')
                        ->label(__('formbuilder.base_font'))
                        ->options([
                            'sans-serif' => 'System Default (Sans-Serif)',
                            'Roboto' => 'Roboto',
                            'Open Sans' => 'Open Sans',
                            'Lato' => 'Lato',
                            'Montserrat' => 'Montserrat',
                            'Raleway' => 'Raleway',
                            'Poppins' => 'Poppins',
                            'Merriweather' => 'Merriweather',
                            'Playfair Display' => 'Playfair Display',
                            'Arial, Helvetica, sans-serif' => 'Arial',
                            '"Times New Roman", Times, serif' => 'Times New Roman',
                            '"Courier New", Courier, monospace' => 'Courier New',
                            'Verdana, Geneva, sans-serif' => 'Verdana',
                            'Georgia, serif' => 'Georgia',
                            'Tahoma, Geneva, sans-serif' => 'Tahoma',
                            '"Trebuchet MS", Helvetica, sans-serif' => 'Trebuchet MS',
                            '"Arial Black", Gadget, sans-serif' => 'Arial Black',
                            'Impact, Charcoal, sans-serif' => 'Impact',
                            '"Lucida Sans Unicode", "Lucida Grande", sans-serif' => 'Lucida Sans',
                        ])
                        ->default('sans-serif')
                        ->native(false),
                    Select::make('radius.md')->label(__('formbuilder.border_radius'))->options([
                        '0px' => __('formbuilder.square') . ' (0px)',
                        '0.25rem' => __('formbuilder.small') . ' (4px)',
                        '0.5rem' => __('formbuilder.medium') . ' (8px)',
                        '1rem' => __('formbuilder.large') . ' (16px)',
                        '9999px' => __('formbuilder.pill'),
                    ])->default('0.5rem'),
                ])->columns(2),
            ])
            ->action(function (array $data, FormStorage $storage) {
                $tokens = [
                    'colors' => $data['colors'],
                    'fonts' => $data['fonts'],
                    'radius' => $data['radius'],
                    'spacing' => ['md' => '1rem'], // Default value
                ];
                
                $themeData = [
                    'name' => $data['name'],
                    'tokens' => $tokens
                ];

                if (!empty($data['theme_id'])) {
                    $themeData['id'] = $data['theme_id'];
                }
                
                $theme = Theme::fromArray($themeData);
                
                $storage->saveTheme($theme);
                
                $this->themes = $storage->listThemes();
                $msg = !empty($data['theme_id']) ? __('formbuilder.theme_updated') : __('formbuilder.theme_created');
                Notification::make()->title($msg)->success()->send();
            });
    }

    public function openEmbed(string $id): void
    {
        $this->embedFormId = $id;
        $this->mountAction('embed');
    }

    public function embedAction(): Action
    {
        return Action::make('embed')
            ->modalHeading(__('formbuilder.embed_code'))
            ->fillForm(function () {
                $id = $this->embedFormId;
                $src = route('forms.embed', $id);
                $iframeCode = '<iframe src="' . $src . '" width="100%" style="border:0;overflow:hidden;" scrolling="no"></iframe>';
                $scriptCode = '<div id="form-' . $id . '"></div>'
                    . "\n"
                    . '<script>(function(){var d=document,container=d.getElementById("form-' . $id . '");if(!container)return;var f=d.createElement("iframe");f.src="' . $src . '";f.style.border="0";f.style.width="100%";f.style.overflow="hidden";f.setAttribute("scrolling","no");container.appendChild(f);function receive(e){if(!e.data||e.data.type!=="formbuilder:resize"||e.data.id!=="' . $id . '")return;f.style.height=e.data.height+"px";}window.addEventListener("message",receive,false);}());</script>';

                return [
                    'iframe' => $iframeCode,
                    'script' => $scriptCode,
                ];
            })
            ->schema([
                \Filament\Forms\Components\Textarea::make('iframe')
                    ->label(__('formbuilder.iframe'))
                    ->rows(4)
                    ->readOnly()
                    ->extraAttributes(['class' => 'font-mono text-xs']),
                \Filament\Forms\Components\Textarea::make('script')
                    ->label(__('formbuilder.script'))
                    ->rows(6)
                    ->readOnly()
                    ->extraAttributes(['class' => 'font-mono text-xs']),
            ])
            ->modalSubmitAction(false)
            ->modalCancelAction(fn (Action $action) => $action->label(__('formbuilder.close')));
    }

    public function editForm(string $id, FormStorage $storage): void
    {
        $def = $storage->getForm($id);
        if ($def) {
            $data = $def->toArray();
            // Transform options back to array of objects for Repeater
            if (!empty($data['elements'])) {
                foreach ($data['elements'] as &$el) {
                    if (!empty($el['props']['options']) && is_array($el['props']['options'])) {
                        $opts = [];
                        foreach ($el['props']['options'] as $l => $v) {
                            $opts[] = ['label' => $l, 'value' => $v];
                        }
                        $el['props']['options'] = $opts;
                    }
                }
            }
            $this->data = $data;
            Notification::make()->title(__('formbuilder.form_loaded'))->success()->send();
        }
    }

    public function deleteFormAction(): Action
    {
        return Action::make('deleteForm')
            ->requiresConfirmation()
            ->modalHeading(__('formbuilder.delete_form'))
            ->modalDescription(__('formbuilder.delete_confirmation'))
            ->modalSubmitActionLabel(__('formbuilder.delete'))
            ->color('danger')
            ->icon('heroicon-o-trash')
            ->label(__('formbuilder.delete'))
            ->action(function (array $arguments, FormStorage $storage) {
                if (isset($arguments['id'])) {
                    $storage->deleteForm($arguments['id']);
                    $this->forms = $storage->listForms();
                    Notification::make()->title(__('formbuilder.form_deleted'))->success()->send();
                }
            });
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->columns(2)
            ->schema([
                \Filament\Schemas\Components\Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make(__('formbuilder.definition'))
                            ->collapsible()
                            ->schema([
                                Grid::make(['default' => 1, 'md' => 2])->schema([
                                    TextInput::make('name')->label(__('formbuilder.name'))->required()->live(onBlur: true),
                                    TextInput::make('slug')->label(__('formbuilder.slug')),
                                    Select::make('themeId')->label(__('formbuilder.theme'))->options($this->themeOptions())->live(),
                                    TextInput::make('button.label')->label(__('formbuilder.button_label'))->default(__('formbuilder.submit'))->live(onBlur: true),
                                    ColorPicker::make('button.bg_color')
                                        ->label(__('formbuilder.button_color'))
                                        ->default('#288cfa')
                                        ->hidden(fn ($get) => $get('themeId') !== null)
                                        ->live(onBlur: true),
                                    ColorPicker::make('button.text_color')
                                        ->label(__('formbuilder.button_text_color'))
                                        ->default('#ffffff')
                                        ->hidden(fn ($get) => $get('themeId') !== null)
                                        ->live(onBlur: true),
                                ]),
                            ]),
                        Section::make(__('formbuilder.elements'))
                            ->schema([
                                Repeater::make('elements')
                                    ->label(__('formbuilder.fields'))
                                    ->live()
                                    ->reorderable()
                                    ->collapsible()
                                    ->collapsed()
                                    ->itemLabel(fn (array $state): ?string => ($state['label'] ?? __('formbuilder.no_label')) . ' (' . ($this->typeOptions()[$state['type'] ?? ''] ?? $state['type'] ?? 'N/A') . ')')
                                    ->schema([
                                        Grid::make(['default' => 1, 'sm' => 2, 'xl' => 4])->schema([
                                            TextInput::make('label')->label(__('formbuilder.label'))->required()->columnSpan(['default' => 1, 'sm' => 2])->live(onBlur: true),
                                            TextInput::make('name')->label(__('formbuilder.name'))->required()->columnSpan(1)->live(onBlur: true),
                                            Select::make('type')->label(__('formbuilder.type'))->options($this->typeOptions())->required()->columnSpan(1)->live(),
                                        ]),
                                        Textarea::make('props.placeholder')->label(__('formbuilder.placeholder'))->rows(1)->live(onBlur: true),
                                        Repeater::make('props.options')
                                            ->label(__('formbuilder.options'))
                                            ->schema([
                                                Grid::make(['default' => 1, 'sm' => 2])->schema([
                                                    TextInput::make('label')->label(__('formbuilder.label'))->live(onBlur: true),
                                                    TextInput::make('value')->label(__('formbuilder.value'))->live(onBlur: true),
                                                ]),
                                            ])
                                            ->collapsed()
                                            ->visible(fn ($get) => in_array($get('type'), ['select', 'radio']))
                                            ->live(),
                                        Grid::make(['default' => 1, 'sm' => 3])->schema([
                                            Select::make('validations.required')->label(__('formbuilder.required'))->options(['0' => __('formbuilder.no'), '1' => __('formbuilder.yes')]),
                                            TextInput::make('validations.min')->label(__('formbuilder.min')),
                                            TextInput::make('validations.max')->label(__('formbuilder.max')),
                                        ]),
                                    ])
                                    ->columns(1)
                                    ->live(),
                            ]),
                    ]),
                \Filament\Schemas\Components\Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make(__('formbuilder.preview'))
                            ->schema([
                                ViewField::make('preview')
                                    ->view('filament.pages.formbuilder.preview', [
                                        'themes' => $this->themes,
                                    ])
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    protected function themeOptions(): array
    {
        $opts = [];
        foreach ($this->themes as $t) {
            $opts[$t['id']] = $t['name'];
        }
        return $opts;
    }

    protected function typeOptions(): array
    {
        return [
            'text' => __('formbuilder.text'),
            'number' => __('formbuilder.number'),
            'email' => __('formbuilder.email'),
            'url' => __('formbuilder.url'),
            'textarea' => __('formbuilder.textarea'),
            'select' => __('formbuilder.select'),
            'radio' => __('formbuilder.radio'),
            'checkbox' => __('formbuilder.checkbox'),
            'date' => __('formbuilder.date'),
            'datetime' => __('formbuilder.datetime'),
            'file' => __('formbuilder.file'),
        ];
    }

    public function save(FormStorage $storage): void
    {
        $elements = $this->data['elements'] ?? [];
        foreach ($elements as &$el) {
            if (empty($el['name']) && !empty($el['label'])) {
                $el['name'] = str()->slug($el['label'], '_');
            }
            if (!empty($el['props']['options'])) {
                $opts = [];
                foreach ($el['props']['options'] as $o) {
                    if (($o['label'] ?? '') !== '' && ($o['value'] ?? '') !== '') {
                        $opts[$o['label']] = $o['value'];
                    }
                }
                $el['props']['options'] = $opts;
            }
        }
        $this->data['elements'] = $elements;
        if (empty($this->data['id'])) {
            $this->data['id'] = FormDefinition::newId();
        }
        $def = FormDefinition::fromArray($this->data);
        $storage->saveForm($def);
        $this->forms = $storage->listForms();
        Notification::make()->title(__('formbuilder.form_saved'))->success()->send();
    }
}
