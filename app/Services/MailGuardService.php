<?php

namespace App\Services;

class MailGuardService
{
    public function canSend(string $email): bool
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (! (bool) env('MAIL_SEND_ENABLED', true)) {
            return false;
        }

        $domain = substr(strrchr($email, '@'), 1);
        if (! $domain) {
            return false;
        }

        $blocked = [
            'example.com',
            'example.net',
            'example.org',
            'invalid',
            'localhost',
            'localdomain',
            'test',
        ];
        if (in_array(strtolower($domain), $blocked, true)) {
            return false;
        }

        $allowedEnv = env('MAIL_ALLOWED_DOMAINS');
        if ($allowedEnv) {
            $allowed = array_filter(array_map('trim', explode(',', $allowedEnv)));
            return in_array(strtolower($domain), array_map('strtolower', $allowed), true);
        }

        return true;
    }
}

