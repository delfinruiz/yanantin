<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class CvAttachmentRow extends Model
{
    use Sushi;

    protected static array $rows = [];

    protected $schema = [
        'id' => 'string',
        'label' => 'string',
        'path' => 'string',
        'disk' => 'string',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    public static function setRows(array $rows): void
    {
        static::$rows = $rows;
    }

    public function getRows(): array
    {
        return static::$rows;
    }
}

