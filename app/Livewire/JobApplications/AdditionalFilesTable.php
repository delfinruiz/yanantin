<?php

namespace App\Livewire\JobApplications;

use App\Models\JobApplicationFile;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Filament\Support\Contracts\TranslatableContentDriver;
use Livewire\Component;

class AdditionalFilesTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public int $jobApplicationId;

    public function mount(int $jobApplicationId): void
    {
        $this->jobApplicationId = $jobApplicationId;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                JobApplicationFile::query()
                    ->where('job_application_id', $this->jobApplicationId)
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mime_type')
                    ->label('Tipo')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('size')
                    ->label('Tamaño')
                    ->formatStateUsing(fn ($state) => $state ? number_format(((int) $state) / 1024, 0) . ' KB' : '—')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Subido')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (JobApplicationFile $record) => $this->filesystem($record->disk)->url($record->path))
                    ->openUrlInNewTab(),
                Action::make('rename')
                    ->label('Renombrar')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->fillForm(fn (JobApplicationFile $record) => ['name' => $record->name])
                    ->action(function (JobApplicationFile $record, array $data) {
                        $record->update(['name' => $data['name']]);
                    }),
                Action::make('delete')
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (JobApplicationFile $record) {
                        $this->filesystem($record->disk)->delete($record->path);
                        $record->delete();
                    }),
            ])
            ->toolbarActions([
                Action::make('upload')
                    ->label('Subir archivo')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        FileUpload::make('file')
                            ->label('Archivo')
                            ->required()
                            ->disk('public')
                            ->directory(fn () => 'job-application-files/' . $this->jobApplicationId)
                            ->visibility('public'),
                    ])
                    ->action(function (array $data) {
                        $path = (string) $data['file'];
                        $disk = 'public';

                        JobApplicationFile::create([
                            'job_application_id' => $this->jobApplicationId,
                            'uploaded_by' => Auth::id(),
                            'disk' => $disk,
                            'path' => $path,
                            'name' => (string) $data['name'],
                            'mime_type' => $this->filesystem($disk)->mimeType($path),
                            'size' => $this->filesystem($disk)->size($path),
                        ]);
                    }),
            ])
            ->emptyStateHeading('Sin archivos adicionales.');
    }

    public function render(): View
    {
        return view('livewire.job-applications.additional-files-table');
    }

    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null;
    }

    protected function filesystem(string $disk): FilesystemAdapter
    {
        return Storage::disk($disk);
    }
}
