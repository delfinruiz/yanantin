<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'timezone',
        'logo_light',
        'logo_dark',
        'favicon',
        'company_name',
        'token_cpanel',
        'token_ai',
        'birthday_greeting_template',
        'token_zadarma',
        'token_sms',
        'token_email_marketing',
        'cpanel_host',
        'cpanel_username',
        'cpanel_token',
    ];
}
