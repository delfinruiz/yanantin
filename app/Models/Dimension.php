<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dimension extends Model
{
    protected $fillable = [
        'survey_name',
        'item',
        'kpi_target',
        'weight',
    ];
}
