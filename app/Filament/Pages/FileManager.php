<?php

namespace App\Filament\Pages;

use App\Mail\FileShareAckCodeMail;
use App\Models\FileItem;
use App\Models\FileItemShare;
use App\Models\FileShareLink;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Enums\IconSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Arr;
use Filament\Support\Enums\Size;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class FileManager extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;
    //definir ruta compartida para todos los usuarios
    public const SHARED_PATH = 'Compartidos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;
    protected string $view = 'filament.pages.file-manager';

    public static function getNavigationLabel(): string
    {
        return __('navigation.labels.my_files');
    }

    public static function getNavigationSort(): ?int
    {
        return 0;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.my_apps');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = FileItemShare::where('user_id', Auth::id())
            ->where('requires_ack', true)
            ->whereNull('ack_completed_at')
            ->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('FileManager_Page_Title');
    }

    // 📁 Ruta actual en el FileManager
    public string $currentPath = '/';
    // 📁 Ruta raíz del usuario en el disco público
    public string $userRoot;

    public ?int $currentFileItemId = null;

    /* =====================================================
     |  MOUNT
     ===================================================== */
    public function mount(): void
    {
        // 📁 Ruta raíz del usuario en el disco público
        $this->userRoot = 'users/' . Auth::id();

        if (!Storage::disk('public')->exists($this->userRoot)) {
            Storage::disk('public')->makeDirectory($this->userRoot);
        }

        // 📁 Carpeta física de compartidos
        $sharedDiskPath = $this->userRoot . '/' . self::SHARED_PATH;

        if (!Storage::disk('public')->exists($sharedDiskPath)) {
            Storage::disk('public')->makeDirectory($sharedDiskPath);
        }

        // registrar en BD (siempre verificar que exista)
        FileItem::firstOrCreate([
            'user_id'   => Auth::id(),
            'path'      => '/',
            'name'      => self::SHARED_PATH,
            'is_folder' => true,
        ]);

        // 📁 Normalizar la ruta actual
        $this->currentPath = $this->normalizePath($this->currentPath);
    }

    // 📁 Verificar si es la carpeta compartida raíz
    private function isSharedRoot(FileItem $record): bool
    {
        return
            $record->is_folder &&
            $record->name === self::SHARED_PATH &&
            $record->path === '/';
    }

    // 🔒 Verificar si el usuario actual debe tomar conocimiento
    private function currentUserPendingAck(FileItem $record): bool
    {
        // Propietario nunca tiene pendiente
        if ($record->user_id === Auth::id()) {
            return false;
        }

        // Buscar el share específico para el usuario actual
        $share = $record->sharedWith->first(fn($u) => $u->id === Auth::id());

        // Si no está cargada la relación, intentamos cargarla (fallback seguro)
        if (!$share && !$record->relationLoaded('sharedWith')) {
            $share = $record->sharedWith()->where('users.id', Auth::id())->first();
        }

        if (!$share) {
            return false;
        }

        // Verificar flags
        return ($share->pivot->requires_ack ?? false) && is_null($share->pivot->ack_completed_at);
    }
    // =====================================================
    //  MAX CONTENT WIDTH
    // =====================================================
    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    /* =====================================================
     |  PATH HELPERS (ÚNICA FUENTE DE VERDAD)
     ===================================================== */
    private function normalizePath(string $path): string
    {
        $path = preg_replace('#/+#', '/', $path);

        if ($path === '' || $path === '/') {
            return '/';
        }

        return '/' . trim($path, '/') . '/';
    }

    private function diskPath(string $path = '', string $name = ''): string
    {
        $path = trim($path, '/');

        return trim(
            $this->userRoot
                . ($path !== '' ? '/' . $path : '')
                . ($name !== '' ? '/' . $name : ''),
            '/'
        );
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    private function getFolderSize(string $path): int
    {
        $disk = Storage::disk('public');
        $total = 0;

        foreach ($disk->allFiles($path) as $file) {
            $total += $disk->size($file);
        }

        return $total;
    }


    /* =====================================================
     |  HEADER ACTIONS
     ===================================================== */
    protected function getHeaderActions(): array
    {

        return [

            /* 📁 CREAR CARPETA */
            CreateAction::make('createFolder')
                ->label(__('FileManager_Create_Folder'))
                ->icon('heroicon-o-folder-plus')
                ->successNotification(null)
                ->schema([
                    TextInput::make('folderName')->label(__('FileManager_Folder_Name'))->required(),
                ])
                ->disabled(
                    fn() =>
                    $this->currentPath === '/' . self::SHARED_PATH . '/'
                )
                ->tooltip(
                    fn() =>
                    $this->currentPath === '/' . self::SHARED_PATH . '/'
                        ? __('FileManager_No_Folders_In_Shared')
                        : null
                )
                ->action(function (array $data) {

                    $path = $this->normalizePath($this->currentPath);
                    $name = trim($data['folderName']);

                    // 🚫 duplicado
                    if ($this->existsInCurrentPath($name)) {
                        Notification::make()
                            ->title(__('FileManager_Folder_Duplicate_Title'))
                            ->body(__('FileManager_Folder_Duplicate_Body'))
                            ->warning()
                            ->send();
                        return;
                    }

                    Storage::disk('public')->makeDirectory(
                        $this->diskPath($path, $data['folderName'])
                    );

                    FileItem::create([
                        'user_id'   => Auth::id(),
                        'disk'      => 'public',
                        'path'      => $path,
                        'name'      => $data['folderName'],
                        'is_folder' => true,
                    ]);

                    $this->resetTable();

                    Notification::make()->title(__('FileManager_Folder_Created'))->success()->send();
                }),

            /* 📄 CREAR ARCHIVO */
            CreateAction::make('createFile')
                ->label(__('FileManager_Create_File'))
                ->icon('heroicon-o-document-plus')
                ->successNotification(null)
                ->disabled(
                    fn() =>
                    $this->currentPath === '/' . self::SHARED_PATH . '/'
                )
                ->tooltip(
                    fn() =>
                    $this->currentPath === '/' . self::SHARED_PATH . '/'
                        ? __('FileManager_No_Files_In_Shared')
                        : null
                )
                ->schema([
                    TextInput::make('fileName')->label(__('FileManager_File_Name'))->required(),
                    Select::make('fileType')->label(__('FileManager_File_Type'))->required()->options([
                        'txt' => __('FileManager_File_Type_Text'),
                        'xlsx' => __('FileManager_File_Type_Excel'),
                        'docx' => __('FileManager_File_Type_Word'),
                        'pptx' => __('FileManager_File_Type_PowerPoint'),
                    ]),
                ])
                ->action(function (array $data) {

                    $fileName = "{$data['fileName']}.{$data['fileType']}";
                    $path = $this->normalizePath($this->currentPath);

                    // 🚫 duplicado
                    if ($this->existsInCurrentPath($fileName)) {
                        Notification::make()
                            ->title(__('FileManager_File_Duplicate_Title'))
                            ->body(__('FileManager_File_Duplicate_Body'))
                            ->warning()
                            ->send();
                        return;
                    }

                    $diskPath = $this->diskPath($path, $fileName);

                    Storage::disk('public')->put(
                        $diskPath,
                        ''
                    );

                    $mime = mime_content_type(Storage::disk('public')->path($diskPath)) ?: 'application/x-empty';

                    FileItem::create([
                        'user_id'   => Auth::id(),
                        'disk'      => 'public',
                        'path'      => $path,
                        'name'      => $fileName,
                        'filename'  => $fileName,
                        'size'      => 0,
                        'mime_type' => $mime,
                        'is_folder' => false,
                    ]);

                    $this->resetTable();

                    Notification::make()->title(__('FileManager_File_Created'))->success()->send();
                }),

            /* ⬆️ SUBIR ARCHIVOS */
            Action::make('uploadFile')
                ->label(__('FileManager_Upload_Files'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->disabled(
                    fn() =>
                    $this->currentPath === '/' . self::SHARED_PATH . '/'
                )
                ->tooltip(
                    fn() =>
                    $this->currentPath === '/' . self::SHARED_PATH . '/'
                        ? __('FileManager_No_Upload_In_Shared')
                        : null
                )
                ->schema([
                    FileUpload::make('files')
                        ->disk('public')
                        ->directory('tmp')
                        ->multiple()
                        ->preserveFilenames()
                        ->required()
                        ->label(__('FileManager_Upload_Input')),
                ])
                ->action(function (array $data) {

                    $disk = Storage::disk('public');
                    $path = $this->normalizePath($this->currentPath);

                    $uploaded = 0;
                    $skipped = 0;

                    foreach ($data['files'] as $tmp) {

                        $name = basename($tmp);

                        // 🚫 duplicado
                        if ($this->existsInCurrentPath($name)) {
                            $skipped++;
                            continue;
                        }

                        $disk->move(
                            $tmp,
                            $this->diskPath($path, $name)
                        );

                        $finalPath = $this->diskPath($path, $name);
                        $size = $disk->size($finalPath);
                        $mime = mime_content_type($disk->path($finalPath)) ?: 'application/octet-stream';

                        FileItem::create([
                            'user_id'   => Auth::id(),
                            'disk'      => 'public',
                            'path'      => $path,
                            'name'      => $name,
                            'filename'  => $name,
                            'size'      => $size,
                            'mime_type' => $mime,
                            'is_folder' => false,
                        ]);

                        $uploaded++;
                    }

                    $this->resetTable();

                    Notification::make()
                        ->title(__('FileManager_Upload_Completed_Title'))
                        ->body(__('FileManager_Upload_Completed_Body', ['uploaded' => $uploaded, 'skipped' => $skipped]))
                        ->success()
                        ->send();
                }),
        ];
    }

    /* =====================================================
     |  TABLE
     ===================================================== */
    public function table(Table $table): Table
    {
        return $table

            ->query(function () {

                $query = FileItem::query();

                // 🔍 búsqueda global
                if ($this->getTableSearch()) {
                    return $query->accessible()->with([
                        'sharedWith' => function ($q) {
                            $q->where('users.id', Auth::id());
                        },
                    ]);
                }

                // 📁 Carpeta virtual: Compartidos conmigo
                if ($this->currentPath === '/' . self::SHARED_PATH . '/') {
                    return $query
                        ->where('user_id', '!=', Auth::id())
                        ->whereHas('sharedWith', function ($q) {
                            $q->where('users.id', Auth::id());
                        })
                        ->with([
                            'sharedWith' => function ($q) {
                                $q->where('users.id', Auth::id());
                            },
                        ]);
                }

                // 📁 Raíz normal → solo MIS archivos
                if ($this->currentPath === '/') {
                    return $query
                        ->where('user_id', Auth::id())
                        ->where('path', '/');
                }

                // 📁 Subcarpetas → solo MIS archivos
                return $query
                    ->where('user_id', Auth::id())
                    ->where('path', $this->normalizePath($this->currentPath));
            })

            ->defaultPaginationPageOption(50)

            ->checkIfRecordIsSelectableUsing(
                fn(FileItem $record) =>
                $record->user_id === Auth::id()
                    && $record->name !== self::SHARED_PATH
            )

            ->emptyStateIcon('heroicon-o-cloud')
            ->emptyStateHeading(__('FileManager_Empty_State_Heading'))
            ->emptyStateDescription(__('FileManager_Empty_State_Description'))
            ->columns([

                IconColumn::make('is_folder')
                    ->label(__('FileManager_Type'))
                    ->size(IconSize::TwoExtraLarge)
                    ->width('50px')
                    ->icon(function (?FileItem $record) {
                        if (!$record) return 'heroicon-o-document';

                        // Carpeta Compartidos (especial)
                        if ($record->is_folder && $record->name === self::SHARED_PATH) {
                            return 'heroicon-o-share';
                        }

                        if ($record->is_folder) return 'heroicon-o-folder';
                        // Obtenemos la extensión del nombre del archivo 
                        $extension = strtolower(pathinfo($record->name, PATHINFO_EXTENSION));
                        return match ($extension) {
                            'pdf' => 'heroicon-o-document-chart-bar',
                            'xlsx', 'xls', 'csv' => 'heroicon-o-table-cells',
                            'docx', 'doc' => 'heroicon-o-document-text',
                            'pptx', 'ppt' => 'heroicon-o-presentation-chart-bar',
                            'jpg', 'jpeg', 'png', 'gif', 'svg' => 'heroicon-o-photo',
                            'zip', 'rar', '7z' => 'heroicon-o-archive-box',
                            'txt' => 'heroicon-o-document-minus',
                            'mp4', 'avi', 'mov' => 'heroicon-o-film',
                            'mp3', 'wav', 'aac', 'm4a' => 'heroicon-o-musical-note',
                            default => 'heroicon-o-document',
                        };
                    })
                    ->color(function (?FileItem $record) {
                        if (!$record) return 'gray';

                        // 🎨 Carpeta Compartidos (color especial)
                        if ($record->is_folder && $record->name === self::SHARED_PATH) {
                            return 'success'; // azul elegante
                        }
                        if ($record->is_folder) return 'warning';
                        $extension = strtolower(pathinfo($record->name, PATHINFO_EXTENSION));
                        return match ($extension) {
                            'pdf' => 'danger', // Rojo 
                            'xlsx', 'xls' => 'success', // Verde 
                            'docx', 'doc' => 'info', // Azul 
                            'pptx', 'ppt' => 'warning', // Naranja/Amarillo 
                            'jpg', 'jpeg', 'png' => 'primary',
                            'zip', 'rar' => 'gray',
                            'txt' => 'secondary',
                            'mp4', 'avi', 'mov' => 'info',
                            'mp3', 'wav', 'aac', 'm4a' => 'danger',
                            default => 'gray',
                        };
                    })
                    ->action(function (?FileItem $record) {

                        // carpeta virtual
                        if ($record === null && $this->currentPath === '/') {
                            $this->currentPath = '/' . self::SHARED_PATH . '/';
                            $this->resetTable();
                            return;
                        }

                        if (!$record) {
                            return;
                        }

                        // 🔍 Si hay búsqueda activa, bloquear navegación a carpetas
                        if ($record->is_folder && $this->getTableSearch()) {
                            Notification::make()
                                ->title(__('FileManager_Search_Mode_Active_Title'))
                                ->body(__('FileManager_Search_Mode_Active_Body'))
                                ->warning()
                                ->send();

                            return;
                        }

                        // 📁 Navegación normal
                        if ($record->is_folder) {
                            $this->currentPath =
                                '/' . trim($this->currentPath . '/' . $record->name, '/') . '/';

                            $this->resetTable();
                            return;
                        }

                        // 📄 Preview archivo
                        $this->previewFile($record);
                    })
                    ->tooltip(function (?FileItem $record) {
                        if (!$record) return null;

                        if ($record->is_folder && $this->getTableSearch()) {
                            return __('FileManager_Search_Mode_Active_Tooltip');
                        }

                        return $record->is_folder ? __('FileManager_Open_Folder') : __('FileManager_View_File');
                    }),

                TextColumn::make('name')
                    ->label(__('FileManager_Name'))
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(function (string $state, FileItem $record) {
                        if ($record->is_folder && $state === self::SHARED_PATH) {
                            return __('FileManager_Shared_Folder_Name');
                        }
                        return $state;
                    }),

                TextColumn::make('size')
                    ->label(__('FileManager_Size'))
                    ->state(fn(FileItem $record) => $record)
                    ->formatStateUsing(function (FileItem $record) {

                        // Carpeta Compartidos → no se calcula tamaño
                        if ($record->is_folder && $record->name === self::SHARED_PATH) {
                            return '—';
                        }

                        // 📂 Carpetas: calcular dinámicamente
                        if ($record->is_folder) {
                            $disk = Storage::disk('public');
                            $path = $this->recordDiskPath($record);

                            if (! $disk->exists($path)) {
                                return '—';
                            }

                            return $this->formatBytes(
                                $this->getFolderSize($path)
                            );
                        }

                        // 📄 Archivos: usar valor de BD si existe (optimización)
                        if ($record->size !== null) {
                            return $this->formatBytes($record->size);
                        }

                        // Fallback: leer disco
                        $disk = Storage::disk('public');
                        $path = $this->recordDiskPath($record);

                        if (! $disk->exists($path)) {
                            return '—';
                        }

                        return $this->formatBytes(
                            $disk->size($path)
                        );
                    }),



                TextColumn::make('path')
                    ->label(__('FileManager_Location'))
                    //->visible(fn () => (bool) $this->getTableSearch())
                    ->formatStateUsing(function (string $state) {
                        if ($state === '/') {
                            return '/';
                        }
                        return str_replace(
                            self::SHARED_PATH,
                            __('FileManager_Shared_Folder_Name'),
                            $state
                        );
                    }),

                //fecha de modificacion fecha y hora
                TextColumn::make('updated_at')
                    ->label(__('FileManager_Modified'))
                    ->sortable()
                    ->formatStateUsing(
                        fn(Carbon $state) =>
                        $state->format('d-m-Y H:i:s')
                    ),
                TextColumn::make('owner')
                    ->label(__('FileManager_Owner'))
                    ->state(
                        fn(FileItem $record) =>
                        $record->user_id === Auth::id()
                            ? __('FileManager_Me')
                            : $record->user->name
                    )
                    ->badge()
                    ->color(fn($state) => $state === __('FileManager_Me') ? 'success' : 'info'),

                TextColumn::make('permission_type')
                    ->label(__('FileManager_Permission'))
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'full' => 'success',
                        'edit' => 'warning',
                        'view' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'full' => __('FileManager_Permission_Full'),
                        'edit' => __('FileManager_Permission_Edit'),
                        'view' => __('FileManager_Permission_View'),
                        default => '—',
                    }),

                IconColumn::make('shared')
                    ->label(__('FileManager_Shared'))
                    ->state(fn(?FileItem $record) => $record?->isShared() ?? false)

                    ->icon(
                        fn(bool $state) =>
                        $state
                            ? 'heroicon-o-user-group'
                            : 'heroicon-o-user'
                    )

                    ->color(
                        fn(bool $state) =>
                        $state ? 'success' : 'gray'
                    )

                    ->tooltip(function (?FileItem $record) {

                        if (! $record) {
                            return null;
                        }

                        // 🔒 Solo el propietario, fuera de "Compartidos"
                        if (
                            $record->user_id === Auth::id()
                            && ! $this->isSharedRoot($record)
                            && $record->isShared()
                        ) {
                            return __('FileManager_Shared_With_Users', ['count' => $record->sharedCount()]);
                        } else {
                            return __('FileManager_No_Access_Info');
                        }

                        return null;
                    })

                    ->action(function (?FileItem $record) {

                        if (! $record) {
                            return;
                        }

                        if (
                            $record->user_id === Auth::id()
                            && ! $this->isSharedRoot($record)
                            && $record->isShared()
                        ) {
                            $this->openShareInfo($record);
                        }
                    })



            ])

            ->recordActions([

                /* ✏️ RENOMBRAR (ARCHIVOS Y CARPETAS) */
                ActionGroup::make([
                    Action::make('rename')
                        ->visible(
                            fn(FileItem $record) =>
                            $record->user_id === Auth::id()
                                && ! $this->isSharedRoot($record)
                        )
                        ->label(__('FileManager_Rename'))
                        ->size(Size::Large)
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->modalHeading(__('FileManager_Rename'))
                        ->schema([
                            TextInput::make('newName')
                                ->label(__('FileManager_New_Name'))
                                ->required()
                                ->default(
                                    fn(FileItem $record) =>
                                    pathinfo($record->name, PATHINFO_FILENAME)
                                ),
                        ])
                        ->action(function (array $data, FileItem $record) {

                            $disk = Storage::disk('public');
                            $newName = trim($data['newName']);

                            if ($newName === '') {
                                Notification::make()
                                    ->title(__('FileManager_Invalid_Name'))
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // extensión solo para archivos
                            if (! $record->is_folder) {
                                $extension = pathinfo($record->name, PATHINFO_EXTENSION);
                                $newName .= '.' . $extension;
                            }

                            /* ============================
             | Rutas físicas
             ============================ */
                            $oldPath = $this->recordDiskPath($record);
                            $newPath = trim(
                                dirname($oldPath) . '/' . $newName,
                                '/'
                            );

                            if (! $disk->exists($oldPath)) {
                                Notification::make()
                                    ->title(__('FileManager_Item_Not_Found'))
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // evitar duplicados en la misma carpeta
                            $exists = FileItem::where('user_id', Auth::id())
                                ->where('path', $record->path)
                                ->where('name', $newName)
                                ->where('id', '!=', $record->id)
                                ->exists();

                            if ($exists) {
                                Notification::make()
                                    ->title(__('FileManager_Name_Duplicate'))
                                    ->warning()
                                    ->send();
                                return;
                            }

                            /* ============================
             | Renombrar físicamente
             ============================ */
                            $disk->move($oldPath, $newPath);

                            /* ============================
             | Actualizar BD
             ============================ */
                            // Ruta antigua y nueva (lógicas)
                            $oldBasePath = $this->normalizePath(
                                $record->path . $record->name
                            );

                            $newBasePath = $this->normalizePath(
                                $record->path . $newName
                            );

                            // 1️⃣ Si es carpeta → actualizar hijos
                            if ($record->is_folder) {

                                FileItem::where('user_id', $record->user_id)
                                    ->where('path', 'like', $oldBasePath . '%')
                                    ->update([
                                        'path' => DB::raw(
                                            "REPLACE(path, '{$oldBasePath}', '{$newBasePath}')"
                                        )
                                    ]);
                            }

                            // 2️⃣ Actualizar registro actual
                            $record->update([
                                'name'     => $newName,
                                'filename' => $record->is_folder ? null : $newName,
                            ]);

                            $this->resetTable();

                            Notification::make()
                                ->title(__('FileManager_Renamed_Successfully'))
                                ->success()
                                ->send();
                        }),

                    /* 🔓 CONFIRMAR RECEPCIÓN */
                    Action::make('acknowledge')
                        ->label(__('FileManager_Confirm_Ack'))
                        ->size(Size::Large)
                        ->icon('heroicon-o-lock-closed')
                        ->color('danger')
                        ->visible(fn(FileItem $record) => $this->currentUserPendingAck($record))
                        ->modalHeading(__('FileManager_Confirm_Ack_Heading'))
                        ->modalDescription(__('FileManager_Confirm_Ack_Description'))
                        ->schema([
                            TextInput::make('code')
                                ->label(__('FileManager_Ack_Code'))
                                ->required()
                                ->placeholder(__('FileManager_Enter_Code'))
                        ])
                        ->action(function (array $data, FileItem $record, Action $action) {
                            $share = $record->sharedWith()->where('users.id', Auth::id())->first();

                            if (!$share) {
                                Notification::make()->title(__('FileManager_Access_Error'))->danger()->send();
                                $action->halt();
                            }

                            if ($share->pivot->ack_code_expires_at && now()->gt($share->pivot->ack_code_expires_at)) {
                                Notification::make()->title(__('FileManager_Code_Expired'))->body(__('FileManager_Request_Reshare'))->danger()->send();
                                $action->halt();
                            }

                            if (trim($data['code']) !== $share->pivot->ack_code) {
                                Notification::make()->title(__('FileManager_Incorrect_Code'))->danger()->send();
                                $action->halt();
                            }

                            $record->sharedWith()->updateExistingPivot(Auth::id(), [
                                'ack_completed_at' => now(),
                            ]);

                            $this->resetTable();

                            Notification::make()->title(__('FileManager_Ack_Confirmed'))->success()->send();
                        }),

                    /* ⬇️ DESCARGAR (SOLO ARCHIVOS) */
                    Action::make('download')
                        ->label(__('FileManager_Download'))
                        ->size(Size::Large)
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('primary')
                        ->visible(fn(FileItem $record) => ! $record->is_folder && ! $this->currentUserPendingAck($record))
                        ->action(function (FileItem $record): BinaryFileResponse {

                            $path = $this->recordDiskPath($record);

                            abort_unless(
                                Storage::disk('public')->exists($path),
                                404,
                                __('FileManager_File_Not_Found')
                            );

                            return response()->download(
                                Storage::disk('public')->path($path),
                                $record->filename ?? $record->name
                            );
                        }),

                    Action::make('share')
                        ->label(__('FileManager_Share'))
                        ->size(Size::Large)
                        ->icon('heroicon-o-share')
                        ->color('info')
                        ->modalHeading(__('FileManager_Share'))
                        ->schema([
                            Select::make('user_id')
                                ->label(__('FileManager_User'))
                                ->searchable()
                                ->options(
                                    User::where('id', '!=', Auth::id())
                                        ->pluck('name', 'id')
                                )
                                ->required(),

                            Select::make('permission')
                                ->label(__('FileManager_Permission'))
                                ->options([
                                    'view' =>(__('FileManager_Permission_View_Only')),
                                    'edit' =>(__('FileManager_Permission_Edit_Only')),
                                ])
                                ->default('view')
                                ->required(),
                            Toggle::make('requires_ack')
                                ->label(__('FileManager_Ack_Required'))
                                ->helperText(__('FileManager_Ack_Helper'))
                                ->default(false),
                        ])
                        ->action(function (array $data, FileItem $record) {

                            $recipient = User::find($data['user_id']);
                            $userId = $data['user_id'];
                            $permission = $data['permission'];
                            $requiresAck = (bool)($data['requires_ack'] ?? false);
                            $code = null;

                            // 🔍 Buscar si ya está compartido el item principal
                            $existingShare = $record->sharedWith()
                                ->where('users.id', $userId)
                                ->first();

                            // 1️⃣ Ya compartido con el mismo permiso
                            if ($existingShare && $existingShare->pivot->permission === $permission) {
                                $currentAck = (bool)($existingShare->pivot->requires_ack ?? false);
                                if ($currentAck === $requiresAck) {
                                    Notification::make()
                                        ->title(__('FileManager_No_Changes'))
                                        ->body(__('FileManager_Already_Shared'))
                                        ->warning()
                                        ->send();
                                    return;
                                }
                                if ($requiresAck) {
                                    $code = $this->generateAckCode();
                                    $record->sharedWith()->updateExistingPivot($userId, [
                                        'requires_ack' => true,
                                        'ack_code' => $code,
                                        'ack_code_expires_at' => now()->addDays(7),
                                        'ack_completed_at' => null,
                                    ]);
                                    if ($recipient) {
                                        Mail::to($recipient)->send(
                                            new \App\Mail\FileShareAckCodeMail(
                                                file: $record,
                                                code: $code,
                                                expiresAt: now()->addDays(7),
                                                senderName: Auth::user()->name,
                                            )
                                        );
                                    }
                                    Notification::make()
                                        ->title(__('FileManager_Ack_Enabled'))
                                        ->success()
                                        ->send();
                                    $this->dispatch('refresh-sidebar');
                                    return;
                                } else {
                                    $record->sharedWith()->updateExistingPivot($userId, [
                                        'requires_ack' => false,
                                        'ack_code' => null,
                                        'ack_code_expires_at' => null,
                                        'ack_completed_at' => null,
                                    ]);
                                    Notification::make()
                                        ->title(__('FileManager_Ack_Disabled'))
                                        ->success()
                                        ->send();
                                    $this->dispatch('refresh-sidebar');
                                    return;
                                }
                            }

                            // 2️⃣ Compartido pero con permiso distinto → actualizar
                            if ($existingShare && $existingShare->pivot->permission !== $permission) {
                                $update = [
                                    'permission' => $permission,
                                    'requires_ack' => $requiresAck,
                                ];
                                if ($requiresAck && !($existingShare->pivot->requires_ack ?? false)) {
                                    $code = $this->generateAckCode();
                                    $update['ack_code'] = $code;
                                    $update['ack_code_expires_at'] = now()->addDays(7);
                                    $update['ack_completed_at'] = null;

                                    if ($recipient) {
                                        Mail::to($recipient)->send(
                                            new FileShareAckCodeMail(
                                                file: $record,
                                                code: $code,
                                                expiresAt: now()->addDays(7),
                                                senderName: Auth::user()->name,
                                            )
                                        );
                                    }
                                }
                                $record->sharedWith()->updateExistingPivot($userId, $update);

                                // 🔥 Si es carpeta → actualizar permisos internos
                                if ($record->is_folder) {
                                    $basePath = $record->path . $record->name . '/';

                                    FileItem::where('path', 'like', $basePath . '%')
                                        ->each(function ($item) use ($userId, $permission) {
                                            $item->sharedWith()->updateExistingPivot($userId, [
                                                'permission' => $permission,
                                            ]);
                                        });
                                }

                                Notification::make()
                                    ->title(__('FileManager_Permission_Updated'))
                                    ->success()
                                    ->send();

                                $this->dispatch('refresh-sidebar');

                            }

                            // 3️⃣ No existe → compartir
                            $attachData = [
                                'permission' => $permission,
                                'permission' => $permission,
                                'requires_ack' => $requiresAck,
                            ];
                            if ($requiresAck) {
                                $code = $this->generateAckCode();
                                $attachData['ack_code'] = $code;
                                $attachData['ack_code_expires_at'] = now()->addDays(7);
                            }
                            $record->sharedWith()->attach($userId, $attachData);

                            if ($recipient) {
                                Notification::make()
                                    ->title(__('FileManager_Shared_Notification_Title'))
                                    ->body(__('FileManager_Shared_Notification_Body', [
                                        'sender' => Auth::user()->name,
                                        'file' => $record->name,
                                        'permission' => $permission === 'edit' ? __('FileManager_Permission_Edit_Only') : __('FileManager_Permission_View_Only'),
                                        'ack' => $requiresAck ? __('FileManager_Ack_Suffix') : ''
                                    ]))
                                    ->icon('heroicon-o-share')
                                    ->sendToDatabase($recipient);
                                if ($requiresAck && $code) {
                                    Mail::to($recipient)->send(
                                        new FileShareAckCodeMail(
                                            file: $record,
                                            code: $code,
                                            expiresAt: now()->addDays(7),
                                            senderName: Auth::user()->name,
                                        )
                                    );
                                }
                            }

                            // 🔥 Si es carpeta → compartir todo lo de adentro
                            if ($record->is_folder) {
                                $basePath = $record->path . $record->name . '/';

                                FileItem::where('path', 'like', $basePath . '%')
                                    ->each(function ($item) use ($userId, $permission) {
                                        $item->sharedWith()->syncWithoutDetaching([
                                            $userId => [
                                                'permission' => $permission,
                                            ],
                                        ]);
                                    });
                            }

                            Notification::make()
                                ->title(__('FileManager_Shared_Successfully'))
                                ->success()
                                ->send();
                            $this->dispatch('refresh-sidebar');
                        })
                        ->visible(
                            fn(FileItem $record) =>
                            ! $record->is_folder &&
                                $record->user_id === Auth::id() &&
                                ! $this->isSharedRoot($record)
                        ),

                    /* 🔗 ENLACES PÚBLICOS */
                    Action::make('publicLinks')
                        ->label(__('FileManager_Public_Links'))
                        ->icon('heroicon-o-link')
                        ->color('success')
                        ->modalHeading(__('FileManager_Manage_Public_Links'))
                        ->visible(
                            fn(FileItem $record) =>
                            !$record->is_folder && // 🔒 Solo archivos
                                $record->user_id === Auth::id() &&
                                !$this->isSharedRoot($record)
                        )
                        ->mountUsing(fn($form, FileItem $record) => $form->fill([
                            'share_links' => $record->shareLinks->toArray()
                        ]))
                        ->schema(function (FileItem $record) {
                            $isOffice = in_array(strtolower(pathinfo($record->name, PATHINFO_EXTENSION)), ['docx', 'xlsx', 'pptx', 'pdf']);

                            return [
                                Repeater::make('share_links')
                                    ->label(__('FileManager_Active_Links'))
                                    ->addActionLabel(__('FileManager_Generate_Link'))
                                    ->reorderable(false)
                                    ->columns(2)
                                    ->schema([
                                        Hidden::make('id'),

                                        TextInput::make('token')
                                            ->label(__('FileManager_Token_Auto'))
                                            ->default(fn() => Str::random(32))
                                            ->readOnly()
                                            ->required()
                                            ->columnSpan(1),

                                        DatePicker::make('expires_at')
                                            ->label(__('FileManager_Expires_Optional'))
                                            ->minDate(now())
                                            ->columnSpan(1),

                                        Select::make('permission')
                                            ->label(__('FileManager_Permission'))
                                            ->options([
                                                'view' => __('FileManager_Permission_View'),
                                                'edit' => __('FileManager_Permission_Edit'),
                                            ])
                                            ->default('view')
                                            ->required()
                                            ->visible($isOffice)
                                            ->columnSpan(1),

                                        TextInput::make('downloads')
                                            ->label(__('FileManager_Downloads'))
                                            ->default(0)
                                            ->readOnly()
                                            ->columnSpan(1),

                                        TextInput::make('url')
                                            ->label(__('FileManager_Share_Link'))
                                            ->columnSpanFull()
                                            ->readOnly()
                                            ->formatStateUsing(fn($get) => route('public.share', $get('token')))
                                            ->suffixAction(
                                                Action::make('copy')
                                                    ->icon('heroicon-o-clipboard')
                                                    ->label(__('FileManager_Copy'))
                                                    ->action(fn() => null)
                                                    ->extraAttributes(fn($get) => [
                                                        'x-on:click.prevent.stop' => "
                                                            const url = '" . route('public.share', $get('token')) . "';
                                                            console.log('[Debug] URL a copiar:', url);
                                                            
                                                            const copyToClipboard = (text) => {
                                                                if (navigator.clipboard && window.isSecureContext) {
                                                                    console.log('[Debug] Usando API moderna (navigator.clipboard)');
                                                                    return navigator.clipboard.writeText(text);
                                                                } else {
                                                                    console.log('[Debug] Usando fallback (execCommand)');
                                                                    return new Promise((resolve, reject) => {
                                                                        const textArea = document.createElement('textarea');
                                                                        textArea.value = text;
                                                                        
                                                                        // Estilos para asegurar que sea seleccionable pero invisible
                                                                        textArea.style.position = 'fixed';
                                                                        textArea.style.left = '0';
                                                                        textArea.style.top = '0';
                                                                        textArea.style.opacity = '0';
                                                                        textArea.style.pointerEvents = 'none';
                                                                        textArea.setAttribute('readonly', ''); // Evitar teclado virtual en móviles
                                                                        
                                                                        document.body.appendChild(textArea);
                                                                        textArea.focus();
                                                                        textArea.select();
                                                                        textArea.setSelectionRange(0, 99999); // Para móviles
                                                                        
                                                                        try {
                                                                            // Limpiar selección previa para evitar conflictos
                                                                            const selection = window.getSelection();
                                                                            selection.removeAllRanges();
                                                                            const range = document.createRange();
                                                                            range.selectNode(textArea);
                                                                            selection.addRange(range);

                                                                            const successful = document.execCommand('copy');
                                                                            console.log('[Debug] execCommand exitoso:', successful);
                                                                            if (successful) {
                                                                                // Doble verificación (lectura no siempre permitida, pero intentamos)
                                                                                resolve();
                                                                            }
                                                                            else reject(new Error('execCommand devolvió false'));
                                                                        } catch (err) {
                                                                            console.error('[Debug] execCommand error:', err);
                                                                            reject(err);
                                                                        }
                                                                        document.body.removeChild(textArea);
                                                                    });
                                                                }
                                                            };

                                                            copyToClipboard(url)
                                                                .then(() => {
                                                                    console.log('[Debug] Copiado completado correctamente');
                                                                    if (typeof FilamentNotification !== 'undefined') {
                                                                        new FilamentNotification()
                                                                            .title('" . __('FileManager_Link_Copied') . "')
                                                                            .body(url) // Mostrar URL para verificar visualmente
                                                                            .success()
                                                                            .send();
                                                                    } else {
                                                                        alert('" . __('FileManager_Link_Copied') . ": ' + url);
                                                                    }
                                                                })
                                                                .catch((err) => {
                                                                    console.error('[Debug] Fallo general al copiar:', err);
                                                                    if (typeof FilamentNotification !== 'undefined') {
                                                                        new FilamentNotification().title('" . __('FileManager_Copy_Error') . "').body(err.message).danger().send();
                                                                    } else {
                                                                        alert('" . __('FileManager_Copy_Error') . ": ' + err.message);
                                                                    }
                                                                });
                                                        ",
                                                    ])
                                            ),
                                    ])
                            ];
                        })
                        ->action(function (array $data, FileItem $record) {
                            $links = $data['share_links'] ?? [];
                            $keepIds = [];

                            foreach ($links as $item) {
                                // 1. Actualizar existente
                                if (isset($item['id'])) {
                                    $keepIds[] = $item['id'];
                                    FileShareLink::where('id', $item['id'])->update(
                                        Arr::except($item, [
                                            'id',
                                            'url',
                                            'downloads',
                                            'created_at',
                                            'updated_at',
                                            'file_item_id',
                                            'created_by'
                                        ])
                                    );
                                }
                                // 2. Crear nuevo
                                else {
                                    $createData = Arr::except($item, ['url', 'downloads']);
                                    $createData['created_by'] = Auth::id();

                                    $created = $record->shareLinks()->create($createData);
                                    $keepIds[] = $created->id;
                                }
                            }

                            // 3. Eliminar removidos
                            $record->shareLinks()->whereNotIn('id', $keepIds)->delete();

                            Notification::make()
                                ->title(__('FileManager_Links_Updated'))
                                ->success()
                                ->send();
                        }),

                ])
            ])

            ->toolbarActions([
                BulkActionGroup::make([

                    /* 🗑️ DELETE (CASCADE REAL) */
                    DeleteBulkAction::make()
                        ->action(function (Collection $records) {

                            $records = $records->filter(
                                fn(FileItem $record) => $record->user_id === Auth::id()
                            );

                            // 🔒 No eliminar carpeta compartida
                            $records = $records->reject(
                                fn(FileItem $record) => $this->isSharedRoot($record)
                            );

                            if ($records->isEmpty()) {
                                Notification::make()
                                    ->title(__('FileManager_Action_Not_Allowed'))
                                    ->body(__('FileManager_Only_Own_Files'))
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $disk = Storage::disk('public');

                            foreach ($records as $record) {

                                $basePath = $this->normalizePath(
                                    $record->path . $record->name
                                );

                                $physicalPath = trim(
                                    $this->userRoot . '/' . trim($basePath, '/'),
                                    '/'
                                );

                                if ($record->is_folder) {
                                    $disk->deleteDirectory($physicalPath);

                                    FileItem::where('user_id', $record->user_id)
                                        ->where('path', 'like', $basePath . '%')
                                        ->delete();
                                } else {
                                    $disk->delete($physicalPath);
                                }

                                $record->delete();
                            }
                        }),
                    BulkAction::make('zip')
                        ->label(__('FileManager_Zip_Download'))
                        ->icon('heroicon-o-archive-box')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {

                            // 🔒 Validar toma de conocimiento
                            $pendingAck = $records->filter(fn($record) => $this->currentUserPendingAck($record));

                            if ($pendingAck->isNotEmpty()) {
                                Notification::make()
                                    ->title(__('FileManager_Action_Required'))
                                    ->body(__('FileManager_Pending_Ack_Warning'))
                                    ->danger()
                                    ->send();
                                return null;
                            }

                            $zip = new ZipArchive();

                            $zipName = 'archivos_' . now()->format('Ymd_His') . '.zip';
                            $zipPath = storage_path("app/tmp/{$zipName}");

                            if (!is_dir(dirname($zipPath))) {
                                mkdir(dirname($zipPath), 0755, true);
                            }

                            $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

                            $disk = Storage::disk('public');

                            foreach ($records as $record) {

                                /* =====================
                                    |  📁 CARPETAS
                                    ===================== */
                                if ($record->is_folder) {

                                    $folderDiskPath = $this->recordDiskPath($record);
                                    $baseLength = strlen($folderDiskPath) + 1;

                                    // carpeta raíz dentro del zip
                                    $zip->addEmptyDir($record->name);

                                    // subcarpetas
                                    foreach ($disk->directories($folderDiskPath) as $dir) {
                                        $relativeDir = substr($dir, $baseLength);
                                        $zip->addEmptyDir($record->name . '/' . $relativeDir);
                                    }

                                    // archivos
                                    foreach ($disk->allFiles($folderDiskPath) as $file) {
                                        $relativePath = substr($file, $baseLength);

                                        $zip->addFile(
                                            $disk->path($file),
                                            $record->name . '/' . $relativePath
                                        );
                                    }

                                    continue;
                                }

                                /* =====================
                                    |  📄 ARCHIVOS
                                    ===================== */
                                $fileDiskPath = $this->recordDiskPath($record);

                                if ($disk->exists($fileDiskPath)) {
                                    $zip->addFile(
                                        $disk->path($fileDiskPath),
                                        $record->name
                                    );
                                }
                            }

                            $zip->close();

                            return response()
                                ->download($zipPath)
                                ->deleteFileAfterSend(true);
                        }),

                    BulkAction::make('move')
                        ->label(__('FileManager_Move_Selected'))
                        ->icon('heroicon-o-arrows-right-left')
                        ->color('warning')
                        ->modalHeading(__('FileManager_Move_Modal_Heading'))
                        ->requiresConfirmation()
                        ->schema([
                            TextInput::make('targetPath')
                                ->label(__('FileManager_Target_Path'))
                                ->placeholder('/documentos/2025')
                                ->default(fn() => $this->currentPath)
                                ->helperText(__('FileManager_Target_Path_Helper'))
                                ->required(),
                        ])
                        ->action(function (array $data, Collection $records) {

                            $disk = Storage::disk('public');

                            // 🔒 No mover carpeta compartida
                            $records = $records->reject(
                                fn(FileItem $record) => $this->isSharedRoot($record)
                            );

                            /* ============================
                                | 1. Normalizar destino
                                ============================ */
                            $targetPath = $this->normalizePath($data['targetPath']);

                            // validar que la carpeta destino exista (excepto raíz)
                            if ($targetPath !== '/') {

                                $segments = explode('/', trim($targetPath, '/'));
                                $folderName = array_pop($segments);
                                $parentPath = empty($segments)
                                    ? '/'
                                    : '/' . implode('/', $segments) . '/';

                                $exists = FileItem::where('user_id', Auth::id())
                                    ->where('is_folder', true)
                                    ->where('path', $this->normalizePath($parentPath))
                                    ->where('name', $folderName)
                                    ->exists();

                                if (! $exists) {
                                    Notification::make()
                                        ->title(__('FileManager_Path_Not_Found'))
                                        ->body(__('FileManager_Target_Folder_Not_Found'))
                                        ->warning()
                                        ->send();
                                    return;
                                }
                            }

                            $moved = 0;
                            $skipped = 0;

                            /* ============================
                                | 2. Procesar registros
                                ============================ */
                            foreach ($records as $record) {

                                $oldBasePath = $this->normalizePath(
                                    $record->path . $record->name
                                );

                                $newBasePath = $this->normalizePath(
                                    $targetPath . $record->name
                                );

                                /* ============================
                                    | 2.1 Validaciones
                                    ============================ */

                                // no mover carpeta dentro de sí misma
                                if (
                                    $record->is_folder &&
                                    str_starts_with($newBasePath, $oldBasePath)
                                ) {
                                    $skipped++;
                                    continue;
                                }

                                /* ============================
                                    | 2.2 Mover físicamente
                                    ============================ */
                                $oldDiskPath = $this->recordDiskPath($record);
                                $newDiskPath = trim(
                                    $this->userRoot . '/' . trim($newBasePath, '/'),
                                    '/'
                                );

                                if (! $disk->exists($oldDiskPath)) {
                                    $skipped++;
                                    continue;
                                }

                                $disk->move($oldDiskPath, $newDiskPath);

                                /* ============================
                                    | 2.3 Actualizar BD
                                    ============================ */

                                if ($record->is_folder) {

                                    // actualizar hijos
                                    FileItem::where('user_id', $record->user_id)
                                        ->where('path', 'like', $oldBasePath . '%')
                                        ->update([
                                            'path' => DB::raw(
                                                "REPLACE(path, '{$oldBasePath}', '{$newBasePath}')"
                                            ),
                                        ]);
                                }

                                // actualizar registro actual
                                $record->update([
                                    'path' => $targetPath,
                                ]);

                                $moved++;
                            }

                            $this->resetTable();

                            /* ============================
                                | 3. Notificación final
                                ============================ */
                            Notification::make()
                                ->title(__('FileManager_Move_Completed'))
                                ->body(__('FileManager_Move_Stats', ['moved' => $moved, 'skipped' => $skipped]))
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    /* =====================================================
     |  NAVIGATION
     ===================================================== */
    public function navigateTo(string $path): void
    {
        if (str_contains($path, '..')) return;

        $this->currentPath = $this->normalizePath($path);
        $this->resetTable();
    }

    public function goToRoot(): void
    {
        $this->currentPath = '/';
        $this->resetTable();
    }

    /* =====================================================
     |  PREVIEW
     ===================================================== */
    public function previewFile(FileItem $record): void
    {
        $path = $this->recordDiskPath($record);

        if (!Storage::disk('public')->exists($path)) {
            Notification::make()
                ->title(__('FileManager_File_Not_Found'))
                ->danger()
                ->send();
            return;
        }

        $extension = strtolower(pathinfo($record->name, PATHINFO_EXTENSION));

        if ($record->user_id !== Auth::id()) {
            $share = $record->sharedWith->firstWhere('id', Auth::id());
            $pivot = $share?->pivot;
            if ($pivot && ($pivot->requires_ack ?? false) && empty($pivot->ack_completed_at)) {
                $this->currentFileItemId = $record->id;
                $this->dispatch(
                    'open-ack-confirm',
                    fileId: $record->id,
                    type: $extension,
                    name: $record->name
                );
                return;
            }
        }

        if (in_array($extension, [
            'txt',
            'jpg',
            'jpeg',
            'png',
            'gif',
            'svg',
            'mp3',
            'wav',
            'aac',
            'm4a',
            'mp4',
            'avi',
            'mov'
        ])) {

            $this->dispatch(
                'open-preview',
                path: route('file.preview', $record->id),
                type: $extension,
                name: $record->name
            );
            return;
        }

        if (in_array($extension, ['docx', 'xlsx', 'pptx', 'pdf'])) {
            $this->dispatch(
                'open-onlyoffice',
                url: route('onlyoffice.open', [
                    'fileItem' => $record->id,
                    'permission' => $record->permission_type,
                ])
            );
            return;
        }

        Notification::make()
            ->title(__('FileManager_Preview_Not_Available'))
            ->warning()
            ->send();
    }


    private function recordDiskPath(FileItem $record): string
    {
        $path = $this->normalizePath($record->path);

        $name = $record->is_folder
            ? $record->name
            : ($record->filename ?? $record->name);

        return trim(
            'users/' . $record->user_id . '/' . trim($path, '/') . '/' . $name,
            '/'
        );
    }

    private function existsInCurrentPath(string $name): bool
    {
        return FileItem::where('user_id', Auth::id())
            ->where('path', $this->normalizePath($this->currentPath))
            ->where('name', $name)
            ->exists();
    }

    //abre el modal para ver los usuarios compartidos con el archivo
    public function openShareInfo(FileItem $record): void
    {
        $this->currentFileItemId = $record->id;

        $this->dispatch(
            'open-share-info',
            shares: $record->sharedWith
                ->map(fn($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'permission' => $user->pivot->permission,
                    'ack_required' => (bool)($user->pivot->requires_ack ?? false),
                    'ack_completed' => !empty($user->pivot->ack_completed_at),
                    'ack_completed_at' => $user->pivot->ack_completed_at
                        ? Carbon::parse($user->pivot->ack_completed_at)->format('d-m-Y H:i')
                        : null,
                ])
                ->values()
        );
    }

    public function removeShare(int $userId): void
    {
        if (! $this->currentFileItemId) {
            return;
        }

        FileItemShare::where('file_item_id', $this->currentFileItemId)
            ->where('user_id', $userId)
            ->delete();

        Notification::make()
            ->title(__('FileManager_Permission_Removed'))
            ->success()
            ->send();

        $this->dispatch('$refresh');

        $this->dispatch('refresh-sidebar');
        $file = FileItem::with('sharedWith')->find($this->currentFileItemId);
        $this->openShareInfo($file);
    }

    public function confirmAck(string $code): void
    {
        if (!$this->currentFileItemId) {
            return;
        }

        $pivot = FileItemShare::where('file_item_id', $this->currentFileItemId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$pivot || !($pivot->requires_ack ?? false)) {
            Notification::make()
                ->title(__('FileManager_Invalid_Access'))
                ->danger()
                ->send();
            return;
        }

        $validCode = hash_equals((string)($pivot->ack_code ?? ''), trim($code));
        $notExpired = !$pivot->ack_code_expires_at || now()->lte($pivot->ack_code_expires_at);

        if ($validCode && $notExpired) {
            $pivot->ack_completed_at = now();
            $pivot->save();

            $file = FileItem::find($this->currentFileItemId);
            if ($file) {
                $this->dispatch('close-modal', id: 'ack-confirm');
                $this->previewFile($file);
            }
            Notification::make()
                ->title(__('FileManager_Ack_Confirmed'))
                ->success()
                ->send();
            $this->dispatch('refresh-sidebar');
            return;
        }

        Notification::make()
            ->title(__('FileManager_Code_Invalid_Or_Expired'))
            ->danger()
            ->send();
    }

    private function generateAckCode(int $length = 6): string
    {
        $length = max(4, min(12, $length));
        $min = (int) pow(10, $length - 1);
        $max = (int) (pow(10, $length) - 1);
        do {
            $code = (string) random_int($min, $max);
        } while (FileItemShare::where('ack_code', $code)->exists());
        return $code;
    }
}
