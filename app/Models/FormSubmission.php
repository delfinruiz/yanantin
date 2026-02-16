<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;
use App\Services\FormBuilder\FormStorage;

class FormSubmission extends Model
{
    use Sushi;

    public static ?string $currentFormId = null;

    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
    ];

    protected $schema = [
        'id' => 'integer',
        'submission_id' => 'string',
        'submitted_at' => 'string',
        'data' => 'json',
    ];

    public function getRows(): array
    {
        if (!self::$currentFormId) {
            return [];
        }

        $storage = app(FormStorage::class);
        $submissions = $storage->readSubmissions(self::$currentFormId);
        
        $rows = [];
        $i = 1;
        foreach ($submissions as $s) {
            // Flatten data for easier searching if needed, 
            // but keeping it in 'data' column is cleaner for schema stability.
            // We map the main fields.
            
            $rows[] = [
                'id' => $i++,
                'submission_id' => $s['submission_id'] ?? uniqid(),
                'submitted_at' => $s['submitted_at'] ?? now()->toDateTimeString(),
                'data' => json_encode($s['data'] ?? []),
            ];
        }

        return $rows;
    }

    protected function sushiShouldCache()
    {
        return false;
    }
}
