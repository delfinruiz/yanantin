<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use App\Models\FileItem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class FileSharedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public FileItem $file,
        public string $diskPath,
        public string $mailSubject,
        public string $mailBody,
    ) {}

public function build()
{
    return $this
        ->subject($this->mailSubject)
        ->view('emails.file-shared')
        ->with([
            'mailBody' => $this->mailBody,
            'file' => $this->file,
        ])
        ->attach(
            Storage::disk('public')->path($this->diskPath),
            [
                'as' => $this->file->name,
            ]
        );
}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.file-shared',
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
