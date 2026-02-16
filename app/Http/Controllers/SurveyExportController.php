<?php

namespace App\Http\Controllers;

use App\Exports\SurveyResponsesExport;
use App\Models\Survey;
use Maatwebsite\Excel\Facades\Excel;

class SurveyExportController extends Controller
{
    public function exportResponses(Survey $survey)
    {
        $filename = 'Respuestas_' . str_replace([' ', '/', '\\'], '_', $survey->title) . '.xlsx';
        return Excel::download(new SurveyResponsesExport($survey), $filename);
    }
}

