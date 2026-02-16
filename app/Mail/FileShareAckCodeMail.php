<?php

namespace App\Mail;

use App\Models\FileItem;
use App\Services\SettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class FileShareAckCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public FileItem $file,
        public string $code,
        public ?\Carbon\Carbon $expiresAt,
        public string $senderName,
    ) {}

    public function build()
    {
        $settings = app(SettingService::class)->getSettings();
        $companyName = $settings?->company_name ?? config('app.name');
        $logoUrl = $settings?->logo_light
            ? url(Storage::url($settings->logo_light))
            : asset('/asset/images/logo-light.png');

        return $this
            ->subject(__('FileManager_Mail_Subject_Ack'))
            ->view('emails.file-share-ack')
            ->with([
                'file' => $this->file,
                'code' => $this->code,
                'expiresAt' => $this->expiresAt,
                'senderName' => $this->senderName,
                'companyName' => $companyName,
                'logoUrl' => $logoUrl,
            ]);
    }
}
