<?php

namespace App\Mail;

use App\Models\ObjectiveCheckin;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CheckinSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public ObjectiveCheckin $checkin
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nuevo Reporte de Avance: ' . $this->checkin->objective->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.checkins.submitted',
            with: [
                'objectiveTitle' => $this->checkin->objective->title,
                'employeeName' => $this->checkin->objective->owner->name,
                'periodDate' => $this->checkin->period_date?->format('d/m/Y') ?? 'N/A',
                'url' => \App\Filament\Resources\Evaluations\StrategicObjectiveResource::getUrl('edit', ['record' => $this->checkin->objective->id]),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
