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
        echo "❌ La carpeta no existe en: $path<br>";
        return;
    }

    $perms = getPerms($path);
    echo "Ruta: $path<br>";
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
echo "<h1>Diagnóstico Avanzado y Reparación (Modo Seguro v4)</h1>";

$baseDir = __DIR__ . '/../'; // Root del proyecto
$publicDir = __DIR__; // Carpeta public actual
$storageApp = $baseDir . 'storage/app';
$publicStorage = $baseDir . 'storage/app/public';
$symlinkPath = $publicDir . '/storage';

// ACCIÓN: REPARAR SYMLINK (Nativo PHP)
if (isset($_GET['fix_symlink'])) {
    echo "<div style='background: #eef; padding: 15px; border: 1px solid #ccf; margin-bottom: 20px;'>";
    echo "<h3>🔗 Reparando Enlace Simbólico (Storage Link)...</h3>";
    
    // 1. Manejar enlace/carpeta existente
    if (file_exists($symlinkPath) || is_link($symlinkPath)) {
        if (is_link($symlinkPath)) {
            echo "Enlace simbólico existente encontrado. Eliminando... ";
            if (@unlink($symlinkPath)) {
                echo "✅ Eliminado.<br>";
            } else {
                echo "❌ No se pudo eliminar el enlace existente.<br>";
            }
        } elseif (is_dir($symlinkPath)) {
            // Es un directorio físico (el problema actual)
            $backupName = 'storage_backup_' . date('Ymd_His');
            $backupPath = $publicDir . '/' . $backupName;
            echo "⚠️ Se detectó una CARPETA FÍSICA en lugar de un enlace.<br>";
            echo "Intentando renombrarla a: <strong>$backupName</strong>... ";
            
            if (@rename($symlinkPath, $backupPath)) {
                echo "✅ Renombrada exitosamente.<br>";
            } else {
                echo "❌ No se pudo renombrar. Intentando eliminarla (si está vacía)... ";
                if (@rmdir($symlinkPath)) {
                    echo "✅ Eliminada (estaba vacía).<br>";
                } else {
                    echo "❌ <span style='color: red; font-weight: bold;'>Error Crítico:</span> No se puede quitar la carpeta 'storage' existente porque contiene archivos y no se pudo renombrar.<br>";
                    echo "Solución manual requerida: Entra por FTP y renombra la carpeta 'public/storage' a 'public/storage_old'.<br>";
                    // Detener aquí para evitar errores
                }
            }
        }
    } else {
        echo "No existía enlace previo.<br>";
    }
    
    // 2. Crear nuevo enlace
    $target = realpath($publicStorage);
    
    // Si la carpeta destino no existe, intentar crearla
    if (!$target && !file_exists($publicStorage)) {
        echo "La carpeta destino ($publicStorage) no existe. Intentando crearla... ";
        @mkdir($publicStorage, 0755, true);
        $target = realpath($publicStorage);
        echo ($target ? "✅ Creada." : "❌ Falló.") . "<br>";
    }

    // Verificar de nuevo si el path está libre antes de crear
    if (!file_exists($symlinkPath)) {
        if ($target) {
            echo "Creando enlace de <strong>$symlinkPath</strong> a <strong>$target</strong>...<br>";
            if (@symlink($target, $symlinkPath)) {
                echo "<span style='color: green; font-weight: bold;'>✅ Enlace simbólico creado correctamente.</span><br>";
                echo "Ahora las imágenes deberían funcionar.<br>";
            } else {
                echo "<span style='color: red; font-weight: bold;'>❌ Falló la creación del enlace simbólico con symlink().</span><br>";
                echo "Posible causa: Permisos desactivados en hosting o función deshabilitada.<br>";
            }
        } else {
            echo "❌ No se puede crear el enlace porque la carpeta destino no existe.<br>";
        }
    } else {
        echo "⚠️ No se pudo crear el enlace porque la ruta 'public/storage' sigue ocupada.<br>";
    }
    echo "</div>";
}

// ACCIÓN: REPARAR PERMISOS
if (isset($_GET['fix_perms']) || isset($_GET['force_regen_avatar'])) {
    echo "<div style='background: #eef; padding: 15px; border: 1px solid #ccf; margin-bottom: 20px;'>";
    echo "<h3>🚀 Ejecutando Reparación de Permisos...</h3>";
    
    $totalFixed = 0;
    $forceRegen = isset($_GET['force_regen_avatar']);
    
    // 1. Directorios Base
    $parents = [
        $baseDir . 'storage',
        $baseDir . 'storage/app',
        $baseDir . 'storage/app/public',
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
    $publicDirs = ['avatars', 'attachments', 'livewire-tmp', 'groups/avatars']; 
    foreach ($publicDirs as $dir) {
        $path = $publicStorage . '/' . $dir;
        if (!file_exists($path)) {
             @mkdir($path, 0755, true);
             echo "Created missing dir: $dir<br>";
        }
        if (file_exists($path)) {
            echo "Fixing public dir: $dir... ";
            $count = recursiveChmod($path);
            echo "$count items.<br>";
            $totalFixed += $count;
        }
    }

    // 3. Reparar Avatar de Grupo "General" (CRÍTICO)
    $groupAvatarDir = $publicStorage . '/groups/avatars';
    $groupAvatarFile = $groupAvatarDir . '/01KECPXESFJ5SEA60BCHRZ8B25.jpg';
    
    if (!file_exists($groupAvatarDir)) {
        @mkdir($groupAvatarDir, 0755, true);
    }
    
    // Si se fuerza regeneración, eliminar archivo existente
    if ($forceRegen && file_exists($groupAvatarFile)) {
        echo "Forzando regeneración: Eliminando archivo existente... ";
        if (@unlink($groupAvatarFile)) {
            echo "✅ Eliminado.<br>";
        } else {
            echo "❌ No se pudo eliminar.<br>";
        }
    }
    
    if (!file_exists($groupAvatarFile)) {
        echo "<h3>🛠️ Reparando Avatar del Grupo 'General'...</h3>";
        // Intentar descargar una imagen por defecto de UI Avatars
        $imageUrl = 'https://ui-avatars.com/api/?name=General&background=0D8ABC&color=fff&size=512&font-size=0.33';
        $imageData = @file_get_contents($imageUrl);
        
        if ($imageData) {
            if (@file_put_contents($groupAvatarFile, $imageData)) {
                @chmod($groupAvatarFile, 0644);
                echo "<span style='color: green; font-weight: bold;'>✅ Avatar de grupo 'General' creado exitosamente.</span><br>";
                $totalFixed++;
            } else {
                echo "<span style='color: red; font-weight: bold;'>❌ No se pudo guardar la imagen del avatar. Verifica permisos de escritura.</span><br>";
            }
        } else {
            echo "<span style='color: orange; font-weight: bold;'>⚠️ No se pudo descargar la imagen de UI Avatars. (Quizás no hay internet en el servidor)</span><br>";
            // Intentar crear una imagen vacía o copiar un placeholder local si existe?
            // Por ahora solo avisar.
        }
    } else {
        echo "Avatar de grupo 'General' ya existe.<br>";
    }

    echo "<p style='color: green; font-weight: bold;'>✅ Proceso finalizado. Total archivos procesados: $totalFixed</p>";
    echo "</div>";
}

// INTENTO DE CARGAR LARAVEL
$laravelLoaded = false;
if (isset($_GET['with_laravel']) || isset($_GET['fix_db_url'])) {
    $bootstrap = $baseDir . 'bootstrap/app.php';
    if (file_exists($bootstrap)) {
        try {
            require $baseDir . 'vendor/autoload.php';
            $app = require_once $bootstrap;
            $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
            $kernel->bootstrap();
            $laravelLoaded = true;
            echo "<div style='background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px;'>Laravel cargado exitosamente v" . app()->version() . "</div>";
            
            // ACCIÓN: CORREGIR URL EN BD
            if (isset($_GET['fix_db_url'])) {
                try {
                    $affected = \Illuminate\Support\Facades\DB::table('wirechat_groups')
                        ->where('name', 'General')
                        ->update(['avatar_url' => '/storage/groups/avatars/01KECPXESFJ5SEA60BCHRZ8B25.jpg']);
                    
                    echo "<div style='background: #c3e6cb; padding: 10px; margin-bottom: 20px; border: 1px solid #155724; color: #155724;'>";
                    echo "<h3>✅ URL de Base de Datos Corregida</h3>";
                    echo "Registros actualizados: $affected<br>";
                    echo "Nueva URL: <strong>/storage/groups/avatars/01KECPXESFJ5SEA60BCHRZ8B25.jpg</strong>";
                    echo "</div>";
                } catch (\Exception $e) {
                    echo "<div style='background: #f8d7da; padding: 10px; margin-bottom: 20px; border: 1px solid #721c24; color: #721c24;'>";
                    echo "❌ Error al actualizar BD: " . $e->getMessage();
                    echo "</div>";
                }
            }

            // Botones de Artisan
            echo "<p>
                <a href='?with_laravel=1&artisan=optimize'><button>Ejecutar optimize:clear</button></a>
                <a href='?with_laravel=1&artisan=storage'><button>Ejecutar storage:link (Artisan)</button></a>
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
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 20px;'>⚠️ No se pudo cargar Laravel: " . $e->getMessage() . "</div>";
        }
    }
}

// ESTADO ACTUAL
echo "<h3>📊 Estado Actual de Directorios y Enlaces</h3>";

// Check Symlink
$symlinkStatus = "❌ No existe";
$symlinkTarget = "N/A";
if (file_exists($symlinkPath) || is_link($symlinkPath)) {
    if (is_link($symlinkPath)) {
        $target = readlink($symlinkPath);
        $symlinkStatus = "✅ Es un enlace simbólico";
        $symlinkTarget = $target;
        if (!file_exists($target) && !file_exists($publicDir . '/' . $target)) {
            $symlinkStatus = "⚠️ Enlace roto (apunta a destino inexistente)";
        }
    } elseif (is_dir($symlinkPath)) {
        $symlinkStatus = "<span style='color: red; font-weight: bold;'>⚠️ Es un directorio real (No un enlace) - ESTO ES EL PROBLEMA</span>";
        $symlinkTarget = "Directorio físico (Debe ser eliminado/renombrado)";
    }
}

echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
echo "<tr style='background: #f0f0f0;'><th>Elemento</th><th>Estado</th><th>Detalle/Ruta</th></tr>";
echo "<tr><td>Enlace 'public/storage'</td><td>$symlinkStatus</td><td>$symlinkTarget</td></tr>";
echo "</table>";

// Check Dirs
$checkDirs = [
    'Storage Root' => $baseDir . 'storage',
    'Storage App Public' => $publicStorage,
    'Avatars Folder' => $publicStorage . '/avatars',
    'Groups Avatars' => $publicStorage . '/groups/avatars',
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

// DIAGNOSTICO DE BASE DE DATOS
echo "<hr>";
echo "<h3>🗄️ Diagnóstico de Base de Datos (Avatar General)</h3>";

if ($laravelLoaded) {
    try {
        $group = \Illuminate\Support\Facades\DB::table('wirechat_groups')->where('name', 'General')->first();
        if ($group) {
            $currentUrl = $group->avatar_url;
            echo "URL actual en BD: <code>$currentUrl</code><br>";
            
            $isWrong = (strpos($currentUrl, 'http') !== false || strpos($currentUrl, 'localhost') !== false || strpos($currentUrl, '.test') !== false);
            
            if ($isWrong) {
                echo "<p style='color: red; font-weight: bold;'>⚠️ ALERTA: La URL contiene un dominio (http/https). Esto suele causar errores al cambiar de local a producción.</p>";
                echo "<p><a href='?fix_db_url=1' style='background-color: #e0a800; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🛠️ CORREGIR URL EN BD (Usar ruta relativa)</a></p>";
            } else {
                echo "<p style='color: green;'>✅ La URL parece correcta (ruta relativa).</p>";
                echo "<p>Si aún así no carga, puedes intentar forzar la reescritura:</p>";
                echo "<p><a href='?fix_db_url=1' style='background-color: #6c757d; color: white; padding: 5px 10px; text-decoration: none; border-radius: 5px; font-size: 12px;'>Forzar reescritura de URL</a></p>";
            }
        } else {
            echo "❌ No se encontró el grupo 'General' en la tabla 'wirechat_groups'.";
        }
    } catch (\Exception $e) {
        echo "❌ Error al consultar BD: " . $e->getMessage();
    }
} else {
    echo "<p>Para ver el estado de la base de datos, necesitamos cargar Laravel.</p>";
    echo "<p><a href='?with_laravel=1' style='background-color: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔍 Cargar Laravel y Diagnosticar BD</a></p>";
}

// DIAGNOSTICO ESPECIFICO AVATAR GENERAL
echo "<hr>";
echo "<h3>🔍 Diagnóstico Visual: Avatar 'General'</h3>";
$generalAvatarPath = $publicStorage . '/groups/avatars/01KECPXESFJ5SEA60BCHRZ8B25.jpg';
$generalAvatarUrl = '/storage/groups/avatars/01KECPXESFJ5SEA60BCHRZ8B25.jpg';

if (file_exists($generalAvatarPath)) {
    $fileSize = filesize($generalAvatarPath);
    $filePerms = getPerms($generalAvatarPath);
    echo "Ruta física: $generalAvatarPath<br>";
    echo "Tamaño: <strong>$fileSize bytes</strong> " . ($fileSize < 100 ? "<span style='color:red'>(⚠️ Muy pequeño/corrupto)</span>" : "") . "<br>";
    echo "Permisos: <strong>$filePerms</strong> (Ideal: 0644)<br>";
    
    // Mostrar lectura directa
    $base64 = base64_encode(file_get_contents($generalAvatarPath));
    $mime = mime_content_type($generalAvatarPath);
    echo "<div style='display:flex; gap: 20px; margin-top: 10px;'>";
    echo "<div style='border:1px solid #ccc; padding:10px;'><strong>Lectura Directa (PHP):</strong><br><img src='data:$mime;base64,$base64' style='max-width: 100px; margin-top:5px;'></div>";
    echo "<div style='border:1px solid #ccc; padding:10px;'><strong>Vía Servidor Web (/storage/...):</strong><br><img src='$generalAvatarUrl' style='max-width: 100px; margin-top:5px;' alt='Si ves esto, la imagen no carga vía URL'></div>";
    echo "</div>";
    
    echo "<p>Si ves la imagen de la izquierda pero NO la de la derecha, el problema es el enlace simbólico (repara con botón verde).</p>";
    echo "<p>Si NO ves ninguna imagen o el archivo es muy pequeño, usa el botón de regenerar.</p>";
    
} else {
    echo "❌ El archivo no existe físicamente en: $generalAvatarPath<br>";
}


echo "<hr>";
echo "<h3>🛠️ Acciones Recomendadas</h3>";
echo "<p>1. Verifica primero el 'Diagnóstico de Base de Datos' (botón azul cian arriba).</p>";
echo "<p>2. Si la URL en BD está bien, pero la imagen web falla, usa 'Reparar Symlink'.</p>";

echo "<p><a href='?fix_symlink=1' style='background-color: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px; margin-right: 10px;'>🔗 1. REPARAR SYMLINK (Recomendado)</a></p>";
echo "<p><a href='?fix_perms=1' style='background-color: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px; margin-right: 10px;'>🔧 2. REPARAR PERMISOS</a></p>";
echo "<p><a href='?force_regen_avatar=1' style='background-color: #dc3545; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px;'>🔄 3. FORZAR REGENERACIÓN AVATAR</a></p>";

echo "</body>";
?>
