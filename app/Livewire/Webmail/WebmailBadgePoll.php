<?php

namespace App\Livewire\Webmail;

use App\Models\EmailAccount;
use App\Services\ImapService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WebmailBadgePoll extends Component
{
    public $badgeContent = null;
    public $shouldShow = false;
    public $webmailUrl = '#';

    public function mount()
    {
        $this->updateBadge();
    }

    public function updateBadge()
    {
        $user = Auth::user();
        if (! $user) {
            $this->badgeContent = null;
            $this->shouldShow = false;
            $this->webmailUrl = '#';
            return;
        }

        $account = EmailAccount::where('user_id', $user->id)->first();
        if (! $account) {
            $this->badgeContent = null;
            $this->shouldShow = false;
            $this->webmailUrl = '#';
            return;
        }

        try {
            $count = app(ImapService::class)->unreadCount($account);
            $this->badgeContent = $count > 0 ? (string) $count : null;
            $this->shouldShow = $count > 0;
        } catch (\Throwable $e) {
            $this->badgeContent = null;
            $this->shouldShow = false;
        }

        $password = $account->decrypted_password;
        if ($password) {
            $domain = $account->domain ?? substr(strrchr($account->email, '@'), 1);
            $host = config('cpanel.host') ?: $domain;
            $this->webmailUrl = "https://{$host}:2096/login?user={$account->email}&pass={$password}";
        } else {
            $this->webmailUrl = '#';
        }
    }

    public function render()
    {
        return view('livewire.webmail.webmail-badge-poll');
    }
}

