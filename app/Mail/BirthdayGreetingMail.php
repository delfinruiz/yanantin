<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class BirthdayGreetingMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectText,
        public string $htmlContent,
    ) {
        //
    }

    public function build(): static
    {
        return $this
            ->subject($this->subjectText)
            ->view('emails.birthdays.greeting', [
                'html' => $this->htmlContent,
            ]);
    }
}
