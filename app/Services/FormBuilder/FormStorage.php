<?php

namespace App\Services\FormBuilder;

use App\FormBuilder\FormDefinition;
use App\FormBuilder\Theme;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class FormStorage
{
    protected string $disk = 'local';
    protected string $base = 'formbuilder';

    protected function ensureDirectories(): void
    {
        Storage::disk($this->disk)->makeDirectory($this->base . '/forms');
        Storage::disk($this->disk)->makeDirectory($this->base . '/themes');
        Storage::disk($this->disk)->makeDirectory($this->base . '/submissions');
        Storage::disk($this->disk)->makeDirectory($this->base . '/uploads');
    }

    protected function formPath(string $id): string
    {
        return $this->base . '/forms/' . $id . '.json';
    }

    public function listForms(): array
    {
        $this->ensureDirectories();
        $files = Storage::disk($this->disk)->files($this->base . '/forms');
        $forms = [];
        foreach ($files as $file) {
            if (!str_ends_with($file, '.json')) {
                continue;
            }
            $data = json_decode(Storage::disk($this->disk)->get($file), true) ?? [];
            $id = $data['id'] ?? pathinfo($file, PATHINFO_FILENAME);
            $forms[] = [
                'id' => $id,
                'name' => $data['name'] ?? 'Formulario',
                'version' => $data['version'] ?? 1,
                'themeId' => $data['themeId'] ?? null,
                'submission_count' => $this->countSubmissions($id),
                'updated_at' => Carbon::createFromTimestamp(Storage::disk($this->disk)->lastModified($file)),
            ] + $data;
        }
        usort($forms, fn ($a, $b) => ($b['updated_at'] <=> $a['updated_at']));
        return $forms;
    }

    public function countSubmissions(string $formId): int
    {
        $path = $this->submissionsPath($formId);
        if (!Storage::disk($this->disk)->exists($path)) {
            return 0;
        }
        
        $count = 0;
        $handle = fopen(Storage::disk($this->disk)->path($path), 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                if (trim($line) !== '') {
                    $count++;
                }
            }
            fclose($handle);
        }
        return $count;
    }

    protected function themePath(string $id): string
    {
        return $this->base . '/themes/' . $id . '.json';
    }

    public function listThemes(): array
    {
        $this->ensureDirectories();
        $files = Storage::disk($this->disk)->files($this->base . '/themes');
        
        // If no themes exist, create default theme
        if (empty($files)) {
            $defaultTheme = Theme::fromArray([
                'id' => 'default',
                'name' => 'Default Theme',
            ]);
            $this->saveTheme($defaultTheme);
            $files = [$this->base . '/themes/default.json'];
        }

        $themes = [];
        foreach ($files as $file) {
            if (!str_ends_with($file, '.json')) {
                continue;
            }
            $data = json_decode(Storage::disk($this->disk)->get($file), true) ?? [];
            $themes[] = [
                'id' => $data['id'] ?? pathinfo($file, PATHINFO_FILENAME),
                'name' => $data['name'] ?? 'Tema',
            ] + $data;
        }
        return $themes;
    }

    public function saveTheme(Theme $theme): void
    {
        $this->ensureDirectories();
        Storage::disk($this->disk)->put($this->themePath($theme->id), json_encode($theme->toArray(), JSON_PRETTY_PRINT));
    }

    protected function submissionsPath(string $formId): string
    {
        return $this->base . '/submissions/' . $formId . '.ndjson';
    }

    public function uploadDir(string $formId, string $submissionId): string
    {
        $date = Carbon::now()->format('Ymd');
        return $this->base . '/uploads/' . $formId . '/' . $date . '/' . $submissionId;
    }

    public function saveForm(FormDefinition $def): void
    {
        $this->ensureDirectories();
        Storage::disk($this->disk)->put($this->formPath($def->id), json_encode($def->toArray()));
    }

    public function getForm(string $id): ?FormDefinition
    {
        $this->ensureDirectories();
        $path = $this->formPath($id);
        if (!Storage::disk($this->disk)->exists($path)) {
            return null;
        }
        $data = json_decode(Storage::disk($this->disk)->get($path), true) ?? [];
        return FormDefinition::fromArray($data);
    }

    public function deleteForm(string $id): void
    {
        $path = $this->formPath($id);
        if (Storage::disk($this->disk)->exists($path)) {
            Storage::disk($this->disk)->delete($path);
        }
    }

    public function getTheme(?string $id): ?Theme
    {
        if (!$id) {
            return null;
        }
        $this->ensureDirectories();
        $path = $this->themePath($id);
        if (!Storage::disk($this->disk)->exists($path)) {
            return null;
        }
        $data = json_decode(Storage::disk($this->disk)->get($path), true) ?? [];
        return Theme::fromArray($data);
    }

    public function appendSubmission(string $formId, array $payload, array $meta = [], ?string $existingSubmissionId = null): string
    {
        $this->ensureDirectories();
        $submissionId = $existingSubmissionId ?? (string) Str::ulid();
        $record = [
            'submission_id' => $submissionId,
            'submitted_at' => Carbon::now()->toIso8601String(),
            'data' => $payload,
            'meta' => $meta,
        ];
        Storage::disk($this->disk)->append($this->submissionsPath($formId), json_encode($record));
        return $submissionId;
    }

    public function readSubmissions(string $formId): array
    {
        $path = $this->submissionsPath($formId);
        if (!Storage::disk($this->disk)->exists($path)) {
            return [];
        }
        $lines = preg_split('/\r\n|\r|\n/', Storage::disk($this->disk)->get($path)) ?: [];
        $items = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $items[] = json_decode($line, true);
        }
        return $items;
    }

    public function deleteSubmission(string $formId, string $submissionId): void
    {
        $path = $this->submissionsPath($formId);
        if (!Storage::disk($this->disk)->exists($path)) {
            return;
        }
        $lines = preg_split('/\r\n|\r|\n/', Storage::disk($this->disk)->get($path)) ?: [];
        $newContent = '';
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $data = json_decode($line, true);
            if (($data['submission_id'] ?? '') === $submissionId) {
                continue;
            }
            $newContent .= $line . "\n";
        }
        Storage::disk($this->disk)->put($path, $newContent);
    }
}
