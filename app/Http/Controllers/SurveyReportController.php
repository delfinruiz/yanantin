<?php

namespace App\Http\Controllers;

use App\Filament\Resources\Surveys\SurveyResource;
use App\Models\Response;
use App\Models\Survey;
use App\Services\SurveyStatsService;
use Illuminate\Support\Facades\Storage;

class SurveyReportController extends Controller
{
    public function downloadPdf(Survey $survey)
    {
        $service = app(SurveyStatsService::class);
        $dimensions = $service->dimensionStats($survey);
        $weightedAvg = $service->weightedAvg($dimensions);
        $globalAvg = $weightedAvg ?? $service->globalAvg($dimensions);
        $totalUsers = \App\Models\User::count();
        $qIds = $survey->questions()->pluck('id');
        
        $respondentNames = [];
        if ($survey->is_public) {
            $uIds = Response::whereIn('question_id', $qIds)->whereNotNull('user_id')->distinct()->pluck('user_id');
            $userNames = \App\Models\User::whereIn('id', $uIds)->pluck('name')->all();
            $guestNames = Response::whereIn('question_id', $qIds)
                ->whereNull('user_id')
                ->whereNotNull('guest_name')
                ->select('guest_email', 'guest_name')
                ->distinct()
                ->get()
                ->pluck('guest_name')
                ->all();
            $respondentNames = array_merge($userNames, $guestNames);
        }
        
        $typeSummary = $service->typeSummary($survey);
        
        $userCount = Response::whereIn('question_id', $qIds)->whereNotNull('user_id')->distinct('user_id')->count('user_id');
        $guestCount = Response::whereIn('question_id', $qIds)->whereNull('user_id')->distinct('guest_email')->count('guest_email');
        $respondedCount = $userCount + $guestCount;

        // Participants target
        $participantsLabel = null;
        $participantsTarget = null;
        if ($survey->is_public) {
            $participantsLabel = 'Encuesta abierta';
            $participantsTarget = null;
        } else {
            $assignedDistinct = \Illuminate\Support\Facades\DB::table('survey_user')
                ->join('users', 'users.id', '=', 'survey_user.user_id')
                ->where('survey_id', $survey->id)
                ->distinct()
                ->count('survey_user.user_id');
            $deptIds = $survey->departments()->pluck('departments.id');
            if ($deptIds->count() > 0) {
                $deptUserDistinct = \Illuminate\Support\Facades\DB::table('department_user')
                    ->join('users', 'users.id', '=', 'department_user.user_id')
                    ->whereIn('department_id', $deptIds)
                    ->distinct()
                    ->count('department_user.user_id');
                $participantsTarget = min($deptUserDistinct, $totalUsers);
                $participantsLabel = 'Usuarios en departamentos asignados';
            } else {
                $participantsTarget = min($assignedDistinct, $totalUsers);
                $participantsLabel = 'Usuarios asignados';
            }
        }

        // Participation and reliability classification
        $participationPct = null;
        if (!is_null($participantsTarget) && $participantsTarget > 0) {
            $participationPct = min(100, round(($respondedCount / $participantsTarget) * 100, 2));
        } elseif ($survey->is_public && $totalUsers > 0) {
            $participationPct = min(100, round(($respondedCount / $totalUsers) * 100, 2));
        }
        $reliability = null;
        $reliabilityColor = null; // hex color for alert bar/text
        $reliabilityIcon = null;  // unicode icon
        if (!is_null($participationPct)) {
            if ($participationPct >= 70) {
                $reliability = 'Alta';
                $reliabilityColor = '#10b981'; // green
                $reliabilityIcon = '✅';
                $reliabilityClass = 'high';
            } elseif ($participationPct >= 40) {
                $reliability = 'Media';
                $reliabilityColor = '#f59e0b'; // amber
                $reliabilityIcon = '🟡';
                $reliabilityClass = 'medium';
            } else {
                $reliability = 'Baja';
                $reliabilityColor = '#ef4444'; // red
                $reliabilityIcon = '⚠️';
                $reliabilityClass = 'low';
            }
        }

        $settings = app(\App\Services\SettingService::class)->getSettings();
        $logoPath = null;
        if ($settings?->logo_light) {
            $logoPath = Storage::disk('public')->path($settings->logo_light);
        } else {
            $logoPath = public_path('asset/images/logo-light.png');
        }
        $logoDataUri = null;
        if ($logoPath && file_exists($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

        $colors = ['#1A2A4F','#3b82f6','#10b981','#f59e0b','#ef4444','#6366f1','#14b8a6','#f97316','#84cc16','#db2777'];
        $colorIndex = 0;
        $pieSlices = [];
        // Prefer weights if available; otherwise fall back to average-based share
        $totalWeight = collect($dimensions)->filter(function ($info) {
            return isset($info['weight']) && is_numeric($info['weight']) && ((float)$info['weight']) > 0;
        })->sum(function ($info) { return (float) $info['weight']; });
        if ($totalWeight > 0) {
            foreach ($dimensions as $dim => $info) {
                $avg = isset($info['avg']) ? (float) $info['avg'] : 0;
                $w = isset($info['weight']) ? (float) $info['weight'] : 0;
                if ($w <= 0) continue;
                $share = $w / $totalWeight;
                $pieSlices[] = [
                    'dim' => $dim,
                    'avg' => $avg,
                    'weight' => $share,
                    'pct' => round($share * 100, 2),
                    'color' => $colors[$colorIndex % count($colors)],
                ];
                $colorIndex++;
            }
        } else {
            $totalAvg = collect($dimensions)->filter(function ($info) {
                return isset($info['avg']) && is_numeric($info['avg']) && $info['avg'] > 0;
            })->sum('avg');
            foreach ($dimensions as $dim => $info) {
                $avg = isset($info['avg']) ? (float) $info['avg'] : 0;
                $weight = $totalAvg > 0 ? ($avg / $totalAvg) : 0;
                $pieSlices[] = [
                    'dim' => $dim,
                    'avg' => $avg,
                    'weight' => $weight,
                    'pct' => round($weight * 100, 2),
                    'color' => $colors[$colorIndex % count($colors)],
                ];
                $colorIndex++;
            }
        }
        // Generate small color swatches for legend
        $swatches = [];
        if (function_exists('imagecreatetruecolor')) {
            foreach ($pieSlices as $idx => $s) {
                $im = imagecreatetruecolor(10, 10);
                imagesavealpha($im, true);
                $transparent = imagecolorallocatealpha($im, 0, 0, 0, 0);
                imagefill($im, 0, 0, $transparent);
                $hex = ltrim($s['color'], '#');
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
                $col = imagecolorallocate($im, $r, $g, $b);
                imagefilledrectangle($im, 0, 0, 10, 10, $col);
                ob_start();
                imagepng($im);
                $pngData = ob_get_clean();
                // imagedestroy($im); // Deprecated in PHP 8.0+
                $swatches[$idx] = 'data:image/png;base64,' . base64_encode($pngData);
            }
            foreach ($pieSlices as $i => &$slice) {
                $slice['swatch'] = $swatches[$i] ?? null;
            }
            unset($slice);
        }
        $piePng = null;
        if (!empty($pieSlices) && function_exists('imagecreatetruecolor')) {
            $size = 240;
            $im = imagecreatetruecolor($size, $size);
            imagesavealpha($im, true);
            $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
            imagefill($im, 0, 0, $transparent);
            $cx = $size / 2;
            $cy = $size / 2;
            $diam = $size - 10;
            $start = 0.0;
            foreach ($pieSlices as $s) {
                $angle = 360.0 * $s['weight'];
                if ($angle <= 0) {
                    continue;
                }
                $hex = ltrim($s['color'], '#');
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
                $col = imagecolorallocate($im, $r, $g, $b);
                $end = $start + $angle;
                imagefilledarc($im, (int)$cx, (int)$cy, (int)$diam, (int)$diam, (int)$start, (int)$end, $col, IMG_ARC_PIE);
                $start = $end;
            }
            $white = imagecolorallocate($im, 255, 255, 255);
            imagefilledellipse($im, (int)$cx, (int)$cy, (int)($diam * 0.45), (int)($diam * 0.45), $white);
            ob_start();
            imagepng($im);
            $pngData = ob_get_clean();
            // imagedestroy($im); // Deprecated in PHP 8.0+
            $piePng = 'data:image/png;base64,' . base64_encode($pngData);
        }

        $pdf = app('dompdf.wrapper');
        $pdf->setPaper('legal', 'portrait');
        $pdf->setOptions(['isRemoteEnabled' => true]);
        $pdf->loadView('surveys.report-pdf', [
            'survey' => $survey,
            'dimensions' => $dimensions,
            'globalAvg' => $globalAvg ? round($globalAvg, 2) : null,
            'weighted' => $weightedAvg !== null,
            'respondents' => $respondentNames,
            'participants' => $participantsTarget,
            'participants_label' => $participantsLabel,
            'responded_count' => $respondedCount,
            'participation_pct' => $participationPct,
            'reliability' => $reliability,
            'reliability_color' => $reliabilityColor,
            'reliability_icon' => $reliabilityIcon,
            'type_summary' => $typeSummary,
            'company' => $settings?->company_name ?? config('app.name', 'Finanzas Personales'),
            'logo' => $logoDataUri,
            'pie_slices' => $pieSlices,
            'pie_png' => $piePng,
            'reliability_class' => $reliabilityClass ?? null,
        ]);

        $filename = 'Reporte_' . str_replace([' ', '/', '\\'], '_', $survey->title) . '.pdf';
        return $pdf->download($filename);
    }
}
