<?php

namespace App\Livewire\JobApplications;

use App\Models\CvAttachmentRow;
use App\Models\JobApplication;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Filament\Support\Contracts\TranslatableContentDriver;
use Livewire\Component;

class CvFilesTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public int $jobApplicationId;

    public function mount(int $jobApplicationId): void
    {
        $this->jobApplicationId = $jobApplicationId;

        $application = JobApplication::query()->find($this->jobApplicationId);
        $cv = (array) ($application?->cv_snapshot ?? []);

        CvAttachmentRow::setRows($this->extractCvAttachments($cv));
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(CvAttachmentRow::query())
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->label('Archivo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('path')
                    ->label('Ruta')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(function (CvAttachmentRow $record) {
                        $disk = $record->disk ?: 'public';
                        return $this->filesystem($disk)->url($record->path);
                    })
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('Sin adjuntos en el currículum.');
    }

    public function render(): View
    {
        return view('livewire.job-applications.cv-files-table');
    }

    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null;
    }

    protected function filesystem(string $disk): FilesystemAdapter
    {
        return Storage::disk($disk);
    }

    protected function extractCvAttachments(array $cv): array
    {
        $rows = [];

        $push = function (string $label, string $path, string $disk = 'public') use (&$rows) {
            $id = hash('sha1', $label . '|' . $disk . '|' . $path);
            $rows[] = [
                'id' => $id,
                'label' => $label,
                'path' => $path,
                'disk' => $disk,
            ];
        };

        if (isset($cv['languages']) && is_array($cv['languages'])) {
            foreach ($cv['languages'] as $i => $lang) {
                if (! is_array($lang)) {
                    continue;
                }
                $path = $lang['attachment'] ?? null;
                if (is_string($path) && $path !== '') {
                    $name = $lang['language'] ?? ('Idioma #' . ($i + 1));
                    $push('Certificación de idioma: ' . $name, $path);
                }
            }
        }

        if (isset($cv['certifications']) && is_array($cv['certifications'])) {
            foreach ($cv['certifications'] as $i => $cert) {
                if (! is_array($cert)) {
                    continue;
                }
                $path = $cert['attachment'] ?? null;
                if (is_string($path) && $path !== '') {
                    $name = $cert['name'] ?? ('Certificación #' . ($i + 1));
                    $push('Certificación: ' . $name, $path);
                }
            }
        }

        return $rows;
    }
}
