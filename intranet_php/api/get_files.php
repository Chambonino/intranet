<?php
/**
 * API para obtener archivos por departamento
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$deptId = $_GET['dept'] ?? 'all';

if ($deptId === 'all') {
    $archivos = getArchivosPorDepartamento($pdo);
} else {
    $archivos = getArchivosPorDepartamento($pdo, (int)$deptId);
}

echo json_encode($archivos);
?>
