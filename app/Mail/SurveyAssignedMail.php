<?php

namespace App\Mail;

use App\Models\Survey;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SurveyAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Survey $survey;

    public function __construct(Survey $survey)
    {
        $this->survey = $survey;
    }

    public function build()
    {
        $url = route('surveys.respond.show', $this->survey);
        return $this->subject('Nueva encuesta asignada')
            ->view('emails.survey-assigned')
            ->with(['url' => url($url), 'title' => $this->survey->title]);
    }
}
