<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $fileItem->name }} - Compartido</title>
    @inject('settingService', 'App\Services\SettingService')
    @php
        $favicon = $settingService->get('favicon');
    @endphp
    <link rel="icon" href="{{ $favicon ? \Illuminate\Support\Facades\Storage::url($favicon) : asset('/asset/images/favicon.ico') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 font-sans antialiased">
    <div class="min-h-screen flex flex-col">
        <!-- Navbar -->
        <nav class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <span class="text-xl font-bold text-indigo-600">Finanzas Personales</span>
                        <span class="ml-2 text-gray-500">| Archivo Compartido</span>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-grow container mx-auto px-4 py-8">
            <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg overflow-hidden">
                
                <!-- Header -->
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        @if($fileItem->is_folder)
                            <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
                        @else
                            <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        @endif
                        <h1 class="text-lg font-semibold text-gray-800">{{ $fileItem->name }}</h1>
                    </div>
                    
                    @if(!$fileItem->is_folder)
                        <div class="flex space-x-2">
                            @php
                                $ext = strtolower(pathinfo($fileItem->name, PATHINFO_EXTENSION));
                                $isOffice = in_array($ext, ['docx', 'doc', 'xlsx', 'xls', 'pptx', 'ppt', 'pdf', 'txt', 'csv', 'odt', 'ods', 'odp', 'rtf']);
                            @endphp
                            
                            @if($isOffice)
                                <a href="{{ route('public.onlyoffice', ['token' => $link->token]) }}" target="_blank" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    Abrir
                                </a>
                            @endif

                            <a href="{{ route('public.download', ['token' => $link->token]) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Descargar
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Body -->
                <div class="p-6">
                    
                    @if($isFolder)
                        <!-- Breadcrumbs -->
                        <nav class="flex mb-4 text-sm text-gray-500" aria-label="Breadcrumb">
                            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                                <li class="inline-flex items-center">
                                    <a href="{{ route('public.share', ['token' => $link->token]) }}" class="inline-flex items-center hover:text-gray-900">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>
                                        Inicio
                                    </a>
                                </li>
                                @foreach($breadcrumbs as $crumb)
                                    <li>
                                        <div class="flex items-center">
                                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                                            <a href="{{ $crumb['url'] }}" class="ml-1 font-medium text-gray-700 hover:text-gray-900 md:ml-2">{{ $crumb['name'] }}</a>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </nav>

                        <!-- File List -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tamaño</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @if(count($items) === 0)
                                        <tr>
                                            <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">Carpeta vacía</td>
                                        </tr>
                                    @endif
                                    @foreach($items as $item)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-8 w-8">
                                                        @if($item->is_folder)
                                                            <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
                                                        @else
                                                            <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                                        @endif
                                                    </div>
                                                    <div class="ml-4">
                                                        @if($item->is_folder)
                                                            <a href="{{ route('public.share', ['token' => $link->token, 'path' => ($currentPath ? $currentPath . '/' : '') . $item->name]) }}" class="text-sm font-medium text-gray-900 hover:text-indigo-600">{{ $item->name }}</a>
                                                        @else
                                                            <span class="text-sm font-medium text-gray-900">{{ $item->name }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                @if(!$item->is_folder)
                                                    {{ number_format($item->size / 1024, 2) }} KB
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                @if(!$item->is_folder)
                                                    @php
                                                        $itemExt = strtolower(pathinfo($item->name, PATHINFO_EXTENSION));
                                                        $itemIsOffice = in_array($itemExt, ['docx', 'doc', 'xlsx', 'xls', 'pptx', 'ppt', 'pdf', 'txt', 'csv', 'odt', 'ods', 'odp', 'rtf']);
                                                    @endphp
                                                    
                                                    @if($itemIsOffice)
                                                        <a href="{{ route('public.onlyoffice', ['token' => $link->token, 'path' => ($currentPath ? $currentPath . '/' : '') . $item->name]) }}" target="_blank" class="text-indigo-600 hover:text-indigo-900 mr-3">Abrir</a>
                                                    @endif
                                                
                                                    <a href="{{ route('public.download', ['token' => $link->token, 'path' => ($currentPath ? $currentPath . '/' : ''), 'file' => $item->name]) }}" class="text-indigo-600 hover:text-indigo-900">Descargar</a>
                                                @else
                                                    <a href="{{ route('public.share', ['token' => $link->token, 'path' => ($currentPath ? $currentPath . '/' : '') . $item->name]) }}" class="text-gray-600 hover:text-gray-900">Abrir</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <!-- Single File Preview -->
                        <div class="text-center py-8">
                            <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $fileItem->name }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ number_format($fileItem->size / 1024, 2) }} KB</p>
                            <div class="mt-6">
                                <a href="{{ route('public.download', ['token' => $link->token]) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    Descargar Archivo
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                    <p class="text-xs text-gray-500 text-center">
                        Compartido por {{ $link->fileItem->user->name ?? 'Usuario' }} · Expira: {{ $link->expires_at ? $link->expires_at->format('d/m/Y') : 'Nunca' }}
                    </p>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
