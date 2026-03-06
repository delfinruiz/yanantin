<?php

namespace App\Notifications;

use App\Models\JobOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewJobOfferNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public JobOffer $jobOffer)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nueva oferta laboral: ' . $this->jobOffer->title)
            ->greeting('¡Nueva oportunidad!')
            ->line('Se ha publicado la oferta: ' . $this->jobOffer->title)
            ->action('Ver ofertas', route('careers.index'))
            ->line('Ubicación: ' . ($this->jobOffer->location ?: 'No especificada'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'job_offer_id' => $this->jobOffer->id,
            'title' => $this->jobOffer->title,
        ];
    }
}

