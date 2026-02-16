<?php

use App\Models\EmailAccount;
use App\Services\ImapService;

uses(Tests\TestCase::class);

it('extrae usuario y dominio desde EmailAccount', function () {
    $account = EmailAccount::query()->first();

    if (! $account) {
        test()->markTestSkipped('No hay registros en email_accounts para probar.');
    }

    $username = $account->username ?? substr($account->email, 0, strrpos($account->email, '@'));
    $domain = $account->domain ?? substr(strrchr($account->email, '@'), 1);

    expect($username)->toBeString()->not->toBeEmpty();
    expect($domain)->toBeString()->not->toBeEmpty();
});

it('obtiene número de mensajes no leídos vía IMAP', function () {
    $account = EmailAccount::where('email', 'ivan.ruiz@micode.cl')->first()
        ?? EmailAccount::query()->whereNotNull('encrypted_password')->first();

    if (! $account) {
        test()->markTestSkipped('No hay registros con encrypted_password para probar.');
    }

    if (! function_exists('imap_open')) {
        test()->markTestSkipped('La extensión IMAP no está disponible en PHP.');
    }

    if (! $account->decrypted_password) {
        test()->markTestSkipped('La cuenta no tiene contraseña desencriptable.');
    }

    $service = app(ImapService::class);
    $count = $service->unreadCount($account);

    expect($count)->toBeInt()->and($count)->toBeGreaterThanOrEqual(0);
});
