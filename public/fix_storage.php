<?php

// Configuración de Errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Aumentar tiempo de ejecución y memoria por si hay muchos archivos
@ini_set('max_execution_time', 300);
@ini_set('memory_limit', '256M');

// ==========================================
// FUNCIONES DE AYUDA (Global Scope)
// ==========================================

function getPerms($path) {
    return substr(sprintf('%o', fileperms($path)), -4);
}

function getFileDate($path) {
    return date("Y-m-d H:i:s", filemtime($path));
}

function recursiveChmod($path, $filePerm=0644, $dirPerm=0755) {
    if (!file_exists($path)) return 0;
    
    $count = 0;
    if (is_dir($path)) {
        // Intentar cambiar permisos del directorio
        @chmod($path, $dirPerm);
        
        $files = @scandir($path);
        if ($files === false) return 0;

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $fullPath = $path . '/' . $file;
            $count += recursiveChmod($fullPath, $filePerm, $dirPerm);
        }
    } elseif (is_file($path)) {
        // Intentar cambiar permisos del archivo
        @chmod($path, $filePerm);
        $count++;
    }
    return $count;
}

function scanDirectory($name, $path, $urlPrefix) {
    echo "<h3>📂 Carpeta: $name</h3>";
    if (!file_exists($path)) {
        echo "❌ La carpeta no existe.<br>";
        return;
    }

    $perms = getPerms($path);
    echo "Permisos de carpeta: <strong>$perms</strong> (Ideal: 0755)<br>";

    $files = @scandir($path);
    if ($files === false) {
        echo "❌ No se pudo leer el contenido (posible error de permisos).<br>";
        return;
    }
    
    $files = array_diff($files, array('.', '..'));
    
    // Sort by modification time (newest first)
    $fileData = [];
    foreach ($files as $f) {
        $p = $path . '/' . $f;
        $fileData[$f] = filemtime($p);
    }
    arsort($fileData);
    $recentFiles = array_slice(array_keys($fileData), 0, 5);

    if (empty($recentFiles)) {
        echo "⚠️ Carpeta vacía.<br>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>Archivo</th><th>Fecha</th><th>Permisos</th><th>Prueba</th></tr>";
        
        foreach ($recentFiles as $file) {
            $filePath = $path . '/' . $file;
            $filePerms = getPerms($filePath);
            $fileDate = getFileDate($filePath);
            $fileUrl = $urlPrefix . '/' . $file;
            
            // Highlight bad permissions
            $permStyle = ($filePerms != '0644') ? "color: red; font-weight: bold;" : "color: green;";
            
            echo "<tr>";
            echo "<td>" . substr($file, 0, 30) . "...</td>";
            echo "<td>$fileDate</td>";
            echo "<td style='$permStyle'>$filePerms (Ideal: 0644)</td>";
            echo "<td><a href='$fileUrl' target='_blank'>Abrir</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

// ==========================================
// LÓGICA PRINCIPAL
// ==========================================

echo "<body style='font-family: sans-serif; padding: 20px; line-height: 1.5;'>";
echo "<h1>Diagnóstico Avanzado y Reparación (Modo Seguro)</h1>";

$baseDir = __DIR__ . '/../'; // Root del proyecto
$storageApp = $baseDir . 'storage/app';
$publicStorage = $baseDir . 'storage/app/public';

// ACCIÓN: REPARAR PERMISOS
if (isset($_GET['fix_perms'])) {
    echo "<div style='background: #eef; padding: 15px; border: 1px solid #ccf; margin-bottom: 20px;'>";
    echo "<h3>🚀 Ejecutando Reparación de Permisos...</h3>";
    
    $totalFixed = 0;
    
    // 1. Directorios Base
    $parents = [
        $baseDir . 'storage',
        $baseDir . 'storage/app',
        $baseDir . 'storage/app/public',
        $baseDir . 'storage/app/formbuilder',
        $baseDir . 'storage/framework',
        $baseDir . 'storage/logs',
        $baseDir . 'bootstrap/cache',
    ];

    foreach ($parents as $p) {
        if (file_exists($p)) {
            @chmod($p, 0755);
            echo "Fixing parent: " . basename($p) . " -> 0755<br>";
        }
    }

    // 2. Reparar carpetas específicas públicas
    $publicDirs = ['avatars', 'attachments'];
    foreach ($publicDirs as $dir) {
        $path = $publicStorage . '/' . $dir;
        if (file_exists($path)) {
            echo "Fixing public dir: $dir... ";
            $count = recursiveChmod($path);
            echo "$count items.<br>";
            $totalFixed += $count;
        }
    }

    // 3. Reparar FormBuilder (CRÍTICO)
    $fbPath = $storageApp . '/formbuilder';
    if (file_exists($fbPath)) {
        echo "Fixing FormBuilder... ";
        $count = recursiveChmod($fbPath);
        echo "$count items.<br>";
        $totalFixed += $count;
    } else {
        echo "⚠️ FormBuilder directory not found at $fbPath<br>";
        // Intentar crearla si no existe
        @mkdir($fbPath, 0755, true);
        echo "Created FormBuilder directory.<br>";
    }

    echo "<p style='color: green; font-weight: bold;'>✅ Proceso finalizado. Total archivos procesados: $totalFixed</p>";
    echo "</div>";
}

// INTENTO DE CARGAR LARAVEL (Opcional y Seguro)
// Solo si se pide explícitamente para evitar error 500 por dependencias rotas
if (isset($_GET['with_laravel'])) {
    $bootstrap = $baseDir . 'bootstrap/app.php';
    if (file_exists($bootstrap)) {
        try {
            require $baseDir . 'vendor/autoload.php';
            $app = require_once $bootstrap;
            $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
            $kernel->bootstrap();
            echo "<div style='background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px;'>Laravel cargado exitosamente v" . app()->version() . "</div>";
            
            // Botones de Artisan
            echo "<p>
                <a href='?with_laravel=1&artisan=optimize'><button>Ejecutar optimize:clear</button></a>
                <a href='?with_laravel=1&artisan=storage'><button>Ejecutar storage:link</button></a>
            </p>";

            if (isset($_GET['artisan'])) {
                if ($_GET['artisan'] == 'optimize') {
                    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
                    echo "<pre>" . \Illuminate\Support\Facades\Artisan::output() . "</pre>";
                } elseif ($_GET['artisan'] == 'storage') {
                    \Illuminate\Support\Facades\Artisan::call('storage:link');
                    echo "<pre>" . \Illuminate\Support\Facades\Artisan::output() . "</pre>";
                }
            }

        } catch (Throwable $e) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 20px;'>⚠️ No se pudo cargar Laravel (probablemente por permisos o vendor corrupto): " . $e->getMessage() . "</div>";
        }
    }
}

// ESTADO ACTUAL
echo "<h3>📊 Estado Actual de Directorios</h3>";

$checkDirs = [
    'Storage Root' => $baseDir . 'storage',
    'Storage App' => $baseDir . 'storage/app',
    'FormBuilder Data' => $baseDir . 'storage/app/formbuilder',
    'Public Storage' => $publicStorage,
];

echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'><th>Directorio</th><th>Permisos</th><th>Estado</th></tr>";

foreach ($checkDirs as $name => $path) {
    if (file_exists($path)) {
        $perms = getPerms($path);
        $ok = ($perms == '0755' || $perms == '0775');
        $style = $ok ? "color: green;" : "color: red; font-weight: bold;";
        $status = $ok ? "✅ OK" : "⚠️ Riesgo (Debe ser 0755)";
        echo "<tr><td>$name</td><td style='$style'>$perms</td><td>$status</td></tr>";
    } else {
        echo "<tr><td>$name</td><td colspan='2'>❌ No existe</td></tr>";
    }
}
echo "</table>";

echo "<hr>";
echo "<h3>🛠️ Acciones</h3>";
echo "<p><a href='?fix_perms=1' style='background-color: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px;'>🔧 1. REPARAR PERMISOS (Seguro)</a></p>";
echo "<p>Si los permisos están bien y sigues con problemas, intenta cargar Laravel:</p>";
echo "<p><a href='?with_laravel=1' style='background-color: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🐘 2. Intentar Cargar Laravel</a></p>";

echo "</body>";
