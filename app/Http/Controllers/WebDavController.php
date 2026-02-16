<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\WebDav\WebDavAuthenticator;
use App\WebDav\DirectoryNode;
use Sabre\DAV\Server;

class WebDavController extends Controller
{
    public function handle(Request $request)
    {
        Log::debug('🚪 WebDavController ENTRADA', [
            'method' => $request->method(),
            'uri'    => $request->getRequestUri(),
            'headers' => $request->headers->all(),
            'auth_header' => $request->headers->get('Authorization'),
            'depth' => $request->headers->get('Depth'),
            'translate' => $request->headers->get('Translate'),
        ]);

        // 1️⃣ Autenticación
        $auth = new WebDavAuthenticator();
        $user = $auth->authenticate();

        Log::debug('🔐 Resultado autenticación', [
            'authenticated' => (bool) $user,
            'user_id' => $user?->id,
        ]);

        if (! $user) {
            Log::warning('⛔ WebDAV Unauthorized');
            return response('', 401)->withHeaders([
                'WWW-Authenticate' => 'Basic realm="FileManager"',
                'DAV' => '1,2',
                'MS-Author-Via' => 'DAV',
                'Allow' => 'OPTIONS, GET, HEAD, PUT, POST, DELETE, PROPFIND, MKCOL, MOVE, COPY, LOCK, UNLOCK',
            ]);
        }

        // 2️⃣ Root del usuario
        $userRoot = storage_path('app/public/users/' . $user->id);

        Log::debug('📂 Root usuario', [
            'path'   => $userRoot,
            'exists' => is_dir($userRoot),
        ]);

        if (! is_dir($userRoot)) {
            mkdir($userRoot, 0755, true);
            Log::debug('📁 Carpeta creada');
        }

        // 3️⃣ Nodo raíz
        Log::debug('🌳 Creando DirectoryNode');

        $rootNode = new DirectoryNode($userRoot, true);

        // 4️⃣ Servidor SabreDAV
        Log::debug('🚀 Inicializando SabreDAV', [
            'base_uri' => (rtrim($request->getBaseUrl(), '/') === '' ? '/dav/' : rtrim($request->getBaseUrl(), '/') . '/dav/'),
        ]);

        $server = new Server($rootNode);
        $server->setBaseUri(rtrim($request->getBaseUrl(), '/') === '' ? '/dav/' : rtrim($request->getBaseUrl(), '/') . '/dav/');
        $server->on('exception', function ($e) {
            if ($e instanceof \Sabre\DAV\Exception\NotFound) {
                Log::debug('🧭 WebDAV 404', [
                    'message' => $e->getMessage(),
                ]);
                return;
            }
            Log::error('💥 WebDAV exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        });

        Log::debug('▶️ Ejecutando SabreDAV');

        $server->exec();

        Log::debug('⏹ SabreDAV terminó (esto casi nunca se ve 😄)');
        exit;
    }
}
