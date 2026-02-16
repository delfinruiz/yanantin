<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class CPanelEmailService
{
    public function __construct(
        private string $host,
        private string $username,
        private string $apiToken
    ) {}

    private function baseUrl(): string
    {
        // Puerto 2083 es para usuarios cPanel (no root/WHM)
        // Puerto 2087 es para root/WHM
        // Asumimos 2083 por defecto para mayor compatibilidad con tokens de usuario
        return "https://{$this->host}:2083/json-api/cpanel";
    }

    public function call(string $module, string $function, array $params = []): array
    {
        /** @var Response $response */
        $response = Http::withHeaders([
            'Authorization' => 'cpanel ' . $this->username . ':' . $this->apiToken
        ])->post($this->baseUrl(), [
            'cpanel_jsonapi_user' => $this->username,
            'cpanel_jsonapi_apiversion' => '2',
            'cpanel_jsonapi_module' => $module,
            'cpanel_jsonapi_func' => $function,
            ...$params
        ]);

        $json = method_exists($response, 'json') ? $response->json() : [];
        if (!$json) {
            $body = method_exists($response, 'body') ? $response->body() : '';
            $json = json_decode($body, true) ?? [];
        }

        // Verificar errores en la respuesta de API 2 (cpanelresult.error)
        if (isset($json['cpanelresult']['error'])) {
            throw new \Exception("Error API cPanel: " . $json['cpanelresult']['error']);
        }
        
        // Verificar errores en data (a veces data es un string con error)
        if (isset($json['cpanelresult']['data']) && is_string($json['cpanelresult']['data']) && str_contains(strtolower($json['cpanelresult']['data']), 'access denied')) {
             throw new \Exception("Acceso denegado cPanel: " . $json['cpanelresult']['data']);
        }

        return $json;
    }

    /**
     * Create email account
     * @param string $email
     * @param string $password
     * @param int $quotaMb
     * @return array
     */
    public function create(string $email, string $password, int $quotaMb = 250): array
    {
        $domain = substr(strrchr($email, '@'), 1);
        $username = substr($email, 0, strrpos($email, '@'));
        
        $data = $this->call('Email', 'addpop', [
            'domain' => $domain,
            'email' => $username,
            'password' => $password,
            'quota' => $quotaMb
        ]);

        return $data;
    }

    /**
     * Delete email account
     * @param string $email
     * @return array
     */
    public function delete(string $email): array
    {
        $domain = substr(strrchr($email, '@'), 1);
        $username = substr($email, 0, strrpos($email, '@'));
        
        return $this->call('Email', 'delpop', [
            'domain' => $domain,
            'email' => $username
        ]);
    }

    // Listar correos
    public function list(): array
    {
        // list_pops es UAPI. En API 2 usamos listpopswithdisk para obtener uso y cuota
        $json = $this->call('Email', 'listpopswithdisk');
        
        return $json['cpanelresult']['data'] ?? [];
    }

    /**
     * Change password
     * @param string $email
     * @param string $newPassword
     * @return array
     */
    public function changePassword(string $email, string $newPassword): array
    {
        $domain = substr(strrchr($email, '@'), 1);
        $username = substr($email, 0, strrpos($email, '@'));
        
        return $this->call('Email', 'passwdpop', [
            'domain' => $domain,
            'email' => $username,
            'password' => $newPassword
        ]);
    }

    /**
     * Change quota
     * @param string $email
     * @param int $quotaMb
     * @return array
     */
    public function changeQuota(string $email, int $quotaMb): array
    {
        $domain = substr(strrchr($email, '@'), 1);
        $username = substr($email, 0, strrpos($email, '@'));
        
        return $this->call('Email', 'editquota', [
            'domain' => $domain,
            'email' => $username,
            'quota' => $quotaMb
        ]);
    }
}
