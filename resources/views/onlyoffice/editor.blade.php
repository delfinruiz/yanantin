{{-- resources/views/onlyoffice/editor.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editor de Documento</title>
    @inject('settingService', 'App\Services\SettingService')
    @php
        $favicon = $settingService->get('favicon');
    @endphp
    <link rel="icon" href="{{ $favicon ? \Illuminate\Support\Facades\Storage::url($favicon) : asset('/asset/images/favicon.ico') }}">
    <script src="https://onlyoffice.cahilt.pro/web-apps/apps/api/documents/api.js"></script>
    <style>
        html, body, #editor {
            margin: 0;
            padding: 0;
            height: 100%;
        }
    </style>
</head>
<body>
    <div id="editor"></div>

<script> const docEditor = new DocsAPI.DocEditor("editor", <?= json_encode($config) ?>); </script>
</body>
</html>
