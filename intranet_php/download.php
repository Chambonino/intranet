<?php
/**
 * Descarga de archivos
 */
require_once 'includes/config.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die('Archivo no especificado');
}

$stmt = $pdo->prepare("SELECT * FROM archivos_departamento WHERE id = ? AND activo = 1");
$stmt->execute([$id]);
$archivo = $stmt->fetch();

if (!$archivo) {
    die('Archivo no encontrado');
}

$filePath = UPLOAD_PATH . 'files/' . $archivo['archivo'];

if (!file_exists($filePath)) {
    die('El archivo no existe en el servidor');
}

// Incrementar contador de descargas
$pdo->prepare("UPDATE archivos_departamento SET descargas = descargas + 1 WHERE id = ?")->execute([$id]);

// Preparar descarga
$fileInfo = pathinfo($filePath);
$mimeTypes = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'ppt' => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'txt' => 'text/plain',
    'zip' => 'application/zip',
    'rar' => 'application/x-rar-compressed'
];

$ext = strtolower($fileInfo['extension']);
$mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $archivo['nombre'] . '.' . $ext . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache, must-revalidate');

readfile($filePath);
exit;
?>
