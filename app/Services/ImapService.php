<?php

namespace App\Services;

use App\Models\EmailAccount;
use Illuminate\Support\Facades\Cache;

class ImapService
{
    protected function withSuppressedErrors(\Closure $callback)
    {
        $prevHandler = set_error_handler(static function () {
        }, E_ALL);
        $prevReporting = error_reporting();
        error_reporting($prevReporting & ~E_WARNING & ~E_NOTICE);
        try {
            return $callback();
        } finally {
            restore_error_handler();
            error_reporting($prevReporting);
            if (function_exists('imap_errors')) {
                imap_errors();
            }
        }
    }

    protected function domain(EmailAccount $account): string
    {
        return $account->domain ?? substr(strrchr($account->email, '@'), 1);
    }

    protected function hosts(EmailAccount $account): array
    {
        $domain = $this->domain($account);
        return [
            'mail.' . $domain,
            $domain,
        ];
    }

    public function open(EmailAccount $account)
    {
        if (! function_exists('imap_open')) {
            throw new \RuntimeException('IMAP extension is not available');
        }

        $username = $account->username ?: $account->email;
        $password = $account->decrypted_password;
        if (! $password) {
            throw new \RuntimeException('Missing decrypted password');
        }

        $lastErr = null;
        foreach ($this->hosts($account) as $host) {
            $mailbox = '{' . $host . ':993/imap/ssl/novalidate-cert}INBOX';
            $conn = $this->withSuppressedErrors(function () use ($mailbox, $username, $password) {
                return @imap_open($mailbox, $username, $password, 0, 1);
            });
            if ($conn !== false) {
                return $conn;
            }
            $lastErr = imap_last_error();
        }

        throw new \RuntimeException($lastErr ?: 'IMAP open failed');
    }

    public function unreadCount(EmailAccount $account): int
    {
        $cacheKey = 'imap_unread_' . $account->id;
        return Cache::remember($cacheKey, now()->addSeconds(15), function () use ($account) {
            $conn = $this->open($account);
            $ids = $this->withSuppressedErrors(function () use ($conn) {
                return @imap_search($conn, 'UNSEEN') ?: [];
            });
            $this->withSuppressedErrors(function () use ($conn) {
                return @imap_close($conn);
            });
            return is_array($ids) ? count($ids) : 0;
        });
    }
}
