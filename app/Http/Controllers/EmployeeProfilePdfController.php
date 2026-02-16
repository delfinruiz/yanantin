<?php

namespace App\Http\Controllers;

use App\Models\EmployeeProfile;
use App\Models\Task;
use App\Services\AbsenceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class EmployeeProfilePdfController extends Controller
{
    public function download(EmployeeProfile $record)
    {
        // Ensure relationships are loaded to avoid N+1 and ensure data availability
        $record->load([
            'user', 
            'user.roles', 
            'cargo', 
            'contractType', 
            'medicalLicenses', 
            'absenceRequests', 
            'absenceRequests.type',
            'vacationLedgers' // Load this to ensure we have the data
        ]);

        $absenceService = new AbsenceService();
        // Recalculate to ensure we get the latest balance
        $vacationBalance = $absenceService->getVacationBalance($record);
        
        \Illuminate\Support\Facades\Log::info("PDF Generation: Vacation Balance for {$record->rut} is {$vacationBalance}");

        // Fetch completed and rated tasks
        $tasks = collect();
        $averageRating = 0;
        
        if ($record->user_id) {
            $tasks = Task::with('creator')
                ->where('assigned_to', $record->user_id)
                ->where('status_id', 2) // Completed
                ->whereNotNull('rating')
                ->where('rating', '>', 0)
                ->orderBy('due_date', 'desc')
                ->limit(10) // Limit to last 10 tasks
                ->get();
                
            // Calculate average from ALL completed/rated tasks, not just the limit
            $allRatedTasksCount = Task::where('assigned_to', $record->user_id)
                ->where('status_id', 2)
                ->whereNotNull('rating')
                ->where('rating', '>', 0)
                ->count();
                
            $sumRating = Task::where('assigned_to', $record->user_id)
                ->where('status_id', 2)
                ->whereNotNull('rating')
                ->where('rating', '>', 0)
                ->sum('rating');
                
            if ($allRatedTasksCount > 0) {
                $averageRating = round($sumRating / $allRatedTasksCount, 1);
            }
        }

        $pdf = Pdf::loadView('pdf.employee-profile', [
            'record' => $record,
            'vacationBalance' => $vacationBalance,
            'tasks' => $tasks,
            'averageRating' => $averageRating,
        ]);
        
        // Optional: Set paper size and orientation
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("Ficha_Empleado_{$record->rut}.pdf");
    }
}
