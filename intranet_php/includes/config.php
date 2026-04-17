<?php
/**
 * Configuración de la Base de Datos
 * Intranet Corporativa - Empresa Automotriz
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Cambiar por tu contraseña de MySQL
define('DB_NAME', 'intranet_db');

// Configuración del sitio
define('SITE_NAME', 'Intranet Corporativa');
define('SITE_URL', 'http://localhost/intranet_php'); // Cambiar según tu configuración
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');

// Zona horaria
date_default_timezone_set('America/Mexico_City');

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conexión a la base de datos
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

/**
 * Función para sanitizar entradas
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Función para subir archivos
 */
function uploadFile($file, $destination, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar']) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por PHP (upload_max_filesize)',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo del formulario',
            UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
            UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal del servidor',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en disco',
        ];
        $msg = $errors[$file['error']] ?? 'Error desconocido al subir el archivo (código: ' . $file['error'] . ')';
        return ['success' => false, 'message' => $msg];
    }
    
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension'] ?? '');
    
    if (!in_array($extension, $allowedTypes)) {
        return ['success' => false, 'message' => 'Tipo de archivo no permitido: .' . $extension];
    }
    
    // Crear carpeta de destino si no existe
    $targetDir = UPLOAD_PATH . $destination;
    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0777, true)) {
            return ['success' => false, 'message' => 'No se pudo crear la carpeta: ' . $targetDir];
        }
    }
    
    $newName = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = $targetDir . '/' . $newName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $newName];
    }
    
    return ['success' => false, 'message' => 'Error al mover el archivo. Verifique permisos de la carpeta: ' . $targetDir];
}

/**
 * Verificar si el usuario está autenticado
 */
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Redirigir si no está autenticado
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Obtener mensaje flash
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Establecer mensaje flash
 */
function setFlashMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = ['message' => $message, 'type' => $type];
}
?>
