<?php

namespace App\Http\Controllers\FormBuilder;

use App\FormBuilder\FormDefinition;
use App\FormBuilder\Theme;
use App\Services\FormBuilder\FormStorage;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;

class FormController extends Controller
{
    protected FormStorage $storage;

    public function __construct(FormStorage $storage)
    {
        $this->storage = $storage;
    }

    public function show(string $id)
    {
        $def = $this->storage->getForm($id);
        if (!$def) {
            abort(404);
        }
        $theme = $this->storage->getTheme($def->themeId);
        return view('formbuilder.show', ['def' => $def, 'theme' => $theme]);
    }

    public function embed(string $id)
    {
        $def = $this->storage->getForm($id);
        if (!$def) {
            abort(404);
        }
        $theme = $this->storage->getTheme($def->themeId);
        return view('formbuilder.embed', ['def' => $def, 'theme' => $theme]);
    }

    public function definition(string $id)
    {
        $def = $this->storage->getForm($id);
        if (!$def) {
            abort(404);
        }
        return response()->json($def->toArray());
    }

    public function submit(Request $request, string $id)
    {
        $def = $this->storage->getForm($id);
        if (!$def) {
            abort(404);
        }

        $rules = $this->buildRules($def);
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            // If coming from embed (third-party iframe may block session cookies),
            // return the embed view with an inline error bag instead of relying on session redirect.
            $referer = (string) $request->headers->get('referer', '');
            $theme = $this->storage->getTheme($def->themeId);
            if (str_contains($referer, '/embed')) {
                $bag = new ViewErrorBag();
                $bag->put('default', $validator->errors());
                return response()->view('formbuilder.embed', [
                    'def' => $def,
                    'theme' => $theme,
                    'errors' => $bag,
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $submissionId = (string) Str::ulid();

        $payload = [];
        foreach ($def->elements as $el) {
            $name = $el['name'] ?? null;
            if (!$name) {
                continue;
            }
            
            // Normalize name to match PHP's variable handling (spaces/dots to underscores)
            $inputName = str_replace([' ', '.'], '_', $name);

            $type = $el['type'] ?? 'text';
            if ($type === 'file') {
                if ($request->hasFile($inputName)) {
                    $dir = $this->storage->uploadDir($id, $submissionId);
                    $file = $request->file($inputName);
                    $stored = $file->store($dir, 'local'); // Stores in 'storage/app/formbuilder/...'
                    $payload[$name] = [
                        'original' => $file->getClientOriginalName(),
                        'path' => $stored,
                        'size' => $file->getSize(),
                        'mime' => $file->getClientMimeType(),
                    ];
                }
            } else {
                $payload[$name] = $request->input($inputName);
            }
        }

        $meta = [
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
        ];

        $this->storage->appendSubmission($id, $payload, $meta, $submissionId);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'submission_id' => $submissionId]);
        }

        $theme = $this->storage->getTheme($def->themeId);
        return view('formbuilder.thanks', ['def' => $def, 'theme' => $theme]);
    }

    public function downloadFile(string $formId, string $submissionId, string $field)
    {
        if (!Auth::check()) {
            abort(403);
        }

        $submissions = $this->storage->readSubmissions($formId);
        $submission = collect($submissions)->firstWhere('submission_id', $submissionId);

        if (!$submission) {
            abort(404);
        }

        $fileData = $submission['data'][$field] ?? null;

        if (!is_array($fileData) || !isset($fileData['path'])) {
            abort(404);
        }

        $path = $fileData['path'];
        
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        if (!$disk->exists($path)) {
            abort(404);
        }

        return $disk->download($path, $fileData['original'] ?? null);
    }

    protected function buildRules(FormDefinition $def): array
    {
        $rules = [];
        foreach ($def->elements as $el) {
            $name = $el['name'] ?? null;
            if (!$name) continue;
            
            // Normalize name for validation rules too
            $inputName = str_replace([' ', '.'], '_', $name);

            $elRules = [];
            $validations = $el['validations'] ?? [];
            
            if (!empty($validations['required'])) {
                $elRules[] = 'required';
            } else {
                $elRules[] = 'nullable';
            }

            if (($el['type'] ?? '') === 'email') {
                $elRules[] = 'email';
            }
            if (($el['type'] ?? '') === 'number') {
                $elRules[] = 'numeric';
            }
            if (($el['type'] ?? '') === 'file') {
                $elRules[] = 'file';
            }
            if (!empty($validations['min'])) {
                $elRules[] = 'min:' . $validations['min'];
            }
            if (!empty($validations['max'])) {
                $elRules[] = 'max:' . $validations['max'];
            }
            
            if (!empty($elRules)) {
                $rules[$inputName] = $elRules;
            }
        }
        return $rules;
    }
}
