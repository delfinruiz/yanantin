<?php

namespace App\Mail;

use App\Models\ObjectiveCheckin;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CheckinStatusUpdated extends Mailable
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
        $statusLabel = match($this->checkin->review_status) {
            'approved' => 'Aprobado',
            'rejected_with_correction' => 'Rechazado (Corrección Requerida)',
            'incumplido' => 'Incumplido',
            default => 'Actualizado',
        };

        return new Envelope(
            subject: "Revisión de Avance {$statusLabel}: " . $this->checkin->objective->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.checkins.status-updated',
            with: [
                'objectiveTitle' => $this->checkin->objective->title,
                'status' => $this->checkin->review_status,
                'reviewerName' => $this->checkin->reviewer?->name ?? 'Sistema',
                'comment' => $this->checkin->review_comment,
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
