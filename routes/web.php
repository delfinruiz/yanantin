<?php

use App\Http\Controllers\LogoutController;
use App\Http\Controllers\OnlyOfficeCallbackController;
use App\Http\Controllers\OnlyOfficeController;
use App\Http\Controllers\PublicShareController;
use App\Http\Controllers\FilePreviewController;
use App\Http\Controllers\WebDavController;
use App\Http\Controllers\CalendarEventController;
use App\Http\Controllers\EmployeeProfilePdfController;
use App\Http\Controllers\PublicSurveyController;
use App\Http\Controllers\FormBuilder\FormController;
 
use Illuminate\Support\Facades\Route;

Route::get('/', function () { 
    return view('home'); 
})->name('home');
Route::get('/features', function () { 
    return view('pages.portada.features'); 
})->name('features');

Route::get('/login', function () {
    return redirect()->route('filament.admin.auth.login');
})->name('login');

Route::get('/s/{token}', [PublicShareController::class, 'show'])->name('public.share');
Route::get('/d/{token}', [PublicShareController::class, 'download'])->name('public.download');
Route::get('/o/{token}', [OnlyOfficeController::class, 'openPublic'])->name('public.onlyoffice');
Route::get('/o/download/{token}', [OnlyOfficeController::class, 'downloadForOnlyOffice'])->name('public.download.onlyoffice');

Route::post('/logout', LogoutController::class)->name('logout');

Route::middleware(['auth'])
    ->get('/onlyoffice/open/{fileItem}', [OnlyOfficeController::class, 'open'])
    ->name('onlyoffice.open');

Route::get('/onlyoffice/download-internal/{fileItem}', [OnlyOfficeController::class, 'downloadInternal'])
    ->name('onlyoffice.download.internal')
    ->middleware('signed');

Route::middleware(['auth'])
    ->get('/file/preview/{fileItem}', [FilePreviewController::class, 'show'])
    ->name('file.preview');

Route::post('/onlyoffice/callback', [OnlyOfficeCallbackController::class, 'handle'])
    ->name('onlyoffice.callback');

Route::middleware(['auth'])->group(function () {
    Route::get('/calendar/events', [CalendarEventController::class, 'index'])->name('calendar.events.index');
    Route::post('/calendar/events', [CalendarEventController::class, 'store'])->name('calendar.events.store');
    Route::put('/calendar/events/{event}', [CalendarEventController::class, 'update'])->name('calendar.events.update');
    Route::delete('/calendar/events/{event}', [CalendarEventController::class, 'destroy'])->name('calendar.events.destroy');

    Route::get('/nominas/{record}/pdf', [EmployeeProfilePdfController::class, 'download'])->name('nominas.pdf');
});

Route::match(
    ['GET', 'POST', 'PUT', 'DELETE', 'PROPFIND', 'PROPPATCH', 'MKCOL', 'COPY', 'MOVE', 'LOCK', 'UNLOCK', 'OPTIONS'], 
    '/dav/{path?}', 
    [WebDavController::class, 'handle']
)
->where('path', '.*')
->middleware(['webdav']);

Route::middleware(['auth'])->group(function () {
    // webmail autologin via direct URL, no internal route needed
    Route::get('/admin/api/pending-surveys-count', [\App\Http\Controllers\SurveyApiController::class, 'pendingCount'])
        ->name('api.pending_surveys_count');
    Route::get('/admin/meetings/join/{meeting}', \App\Http\Controllers\MeetingJoinController::class)
        ->name('meetings.join');
    Route::get('/admin/surveys/respond/{survey}', [\App\Http\Controllers\SurveyRespondController::class, 'show'])
        ->name('surveys.respond.show');
    Route::post('/admin/surveys/respond/{survey}', [\App\Http\Controllers\SurveyRespondController::class, 'submit'])
        ->name('surveys.respond.submit');
    Route::get('/admin/surveys/{survey}/report-pdf', [\App\Http\Controllers\SurveyReportController::class, 'downloadPdf'])
        ->name('surveys.report.pdf');
    Route::get('/admin/surveys/{survey}/export-responses', [\App\Http\Controllers\SurveyExportController::class, 'exportResponses'])
        ->name('surveys.responses.export');
});

Route::middleware('throttle:20,1')->group(function () {
    Route::get('/surveys/public/{token}', [\App\Http\Controllers\PublicSurveyController::class, 'landing'])
        ->name('surveys.public.landing');
    Route::post('/surveys/public/{token}/start', [\App\Http\Controllers\PublicSurveyController::class, 'start'])
        ->name('surveys.public.start');
    Route::get('/surveys/public/{token}/respond', [\App\Http\Controllers\PublicSurveyController::class, 'respond'])
        ->name('surveys.public.respond');
    Route::post('/surveys/public/{token}/submit', [\App\Http\Controllers\PublicSurveyController::class, 'submit'])
        ->name('surveys.public.submit');
    Route::get('/surveys/public/{token}/thanks', [\App\Http\Controllers\PublicSurveyController::class, 'thanks'])
        ->name('surveys.public.thanks');
});

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/forms/{id}', [FormController::class, 'show'])->name('forms.show');
    Route::get('/forms/{id}/embed', [FormController::class, 'embed'])->name('forms.embed');
    Route::get('/forms/{id}/definition.json', [FormController::class, 'definition'])->name('forms.definition');
    Route::post('/forms/{id}/submit', [FormController::class, 'submit'])->name('forms.submit');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/form-builder/download/{formId}/{submissionId}/{field}', [FormController::class, 'downloadFile'])
        ->name('formbuilder.download');
});
