<?php

namespace App\Services;

use App\Mail\BirthdayGreetingMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Services\SettingService;
use Illuminate\Support\Str;

class BirthdayGreetingService
{
    public function renderFromTemplate(string $template, string $userName, string $company): string
    {
        $content = $template !== '' ? $template : $this->defaultTemplate();
        $content = $this->replacePlaceholders($content, $userName, $company);

        if (! preg_match('/<\w+[^>]*>/', $content)) {
            $content = Str::of($content)->markdown()->toString();
        }

        return $this->absolutizeAssetUrls($content);
    }

    public function renderContent(User $user): string
    {
        $company = app(SettingService::class)->get('company_name', config('app.name'));
        $settings = app(SettingService::class);
        $content = (string) $settings->get('birthday_greeting_template', '');

        if ($content === '') {
            $content = $this->defaultTemplate();
        }

        $content = $this->replacePlaceholders($content, $user->name, $company);

        // Si el contenido es Markdown (sin etiquetas HTML), convertir a HTML
        if (! preg_match('/<\w+[^>]*>/', $content)) {
            $content = Str::of($content)->markdown()->toString();
        }

        $content = $this->absolutizeAssetUrls($content);
        return $content;
    }

    public function sendEmail(User $user): void
    {
        $html = $this->renderContent($user);
        $company = app(SettingService::class)->get('company_name', config('app.name'));
        $subject = "Feliz cumpleaños, {$user->name} — {$company}";

        try {
            Mail::to($user->email)->queue(new BirthdayGreetingMail($subject, $html));
        } catch (\Throwable $e) {
            Log::error('Error enviando saludo de cumpleaños', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getDefaultTemplate(): string
    {
        return $this->defaultTemplate();
    }

    protected function defaultTemplate(): string
    {
        return <<<HTML
        <div style="font-family: Arial, Helvetica, sans-serif; line-height:1.6;">
            <h2 style="color:#0ea5e9; margin-bottom: 0.5rem;">¡Feliz cumpleaños, {{nombre}}! 🎉</h2>
            <p>De parte de todo el equipo de <strong>{{empresa}}</strong>, te deseamos un día lleno de alegría y nuevos comienzos.</p>
            <p>Gracias por ser parte de nuestra comunidad. ¡Que este nuevo año te traiga muchos éxitos y momentos felices!</p>
            <hr style="border:none; border-top:1px solid #e5e7eb; margin:1.5rem 0;">
            <p style="font-size: 0.9rem; color:#6b7280;">Este mensaje fue enviado automáticamente por {{empresa}}.</p>
        </div>
        HTML;
    }

    protected function replacePlaceholders(string $html, string $nombre, string $empresa): string
    {
        return str_replace(
            ['{{nombre}}', '{{empresa}}'],
            [$nombre, $empresa],
            $html
        );
    }

    protected function absolutizeAssetUrls(string $html): string
    {
        $appUrl = rtrim(config('app.url'), '/');
        // Reemplaza src="/storage/..." por src="https://app.url/storage/..."
        $html = preg_replace(
            '#src=["\'](/storage/[^"\']+)#i',
            'src="' . $appUrl . '$1',
            $html
        );
        // Reemplaza href="/storage/..." por href="https://app.url/storage/..."
        $html = preg_replace(
            '#href=["\'](/storage/[^"\']+)#i',
            'href="' . $appUrl . '$1',
            $html
        );
        return $html;
    }
}
