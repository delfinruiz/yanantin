<?php

namespace App\Exports;

use App\Models\Department;
use App\Models\Response;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SurveyResponsesExport implements FromCollection, WithHeadings
{
    public function __construct(protected Survey $survey) {}

    public function headings(): array
    {
        if ($this->survey->is_public) {
            return ['Dimensión', 'Pregunta', 'Respuesta', 'Usuario', 'Email', 'Departamento', 'Fecha'];
        }
        return ['Dimensión', 'Pregunta', 'Respuesta', 'Fecha'];
    }

    public function collection(): Collection
    {
        $questions = $this->survey->questions()->orderBy('order')->get(['id', 'item', 'content']);
        $qMap = $questions->keyBy('id');
        $qIds = $questions->pluck('id');

        $responses = Response::whereIn('question_id', $qIds)->get(['question_id', 'user_id', 'value', 'created_at']);

        $userIds = $responses->pluck('user_id')->filter()->unique()->values();
        $users = User::whereIn('id', $userIds)->get(['id', 'name', 'email'])->keyBy('id');

        $du = DB::table('department_user')->whereIn('user_id', $userIds)->get(['user_id', 'department_id']);
        $deptIds = collect($du)->pluck('department_id')->unique()->values();
        $depts = Department::whereIn('id', $deptIds)->get(['id', 'name'])->keyBy('id');
        $deptByUser = collect($du)->groupBy('user_id')->map(function ($rows) use ($depts) {
            return implode(', ', collect($rows)->map(fn ($r) => $depts->get($r->department_id)?->name)->filter()->all());
        });

        $rows = [];
        foreach ($responses as $r) {
            $q = $qMap->get($r->question_id);
            if (!$q) continue;
            $val = $r->value;
            if (is_string($val) && str_starts_with($val, '[')) {
                $decoded = json_decode($val, true);
                if (is_array($decoded)) {
                    $val = implode(', ', $decoded);
                }
            }
            $date = optional($r->created_at)->format('Y-m-d H:i');
            if ($this->survey->is_public) {
                $u = $users->get($r->user_id);
                $deptName = $deptByUser->get($r->user_id);
                $rows[] = [
                    $q->item,
                    $q->content,
                    (string) $val,
                    $u?->name,
                    $u?->email,
                    $deptName,
                    $date,
                ];
            } else {
                $rows[] = [
                    $q->item,
                    $q->content,
                    (string) $val,
                    $date,
                ];
            }
        }

        return collect($rows);
    }
}
