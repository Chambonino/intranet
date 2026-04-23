<?php
/**
 * API para subir imágenes desde Summernote
 */
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $result = uploadFile($_FILES['file'], 'articles', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    if ($result['success']) {
        echo json_encode(['url' => '../assets/uploads/articles/' . $result['filename']]);
    } else {
        echo json_encode(['error' => $result['message']]);
    }
} else {
    echo json_encode(['error' => 'No se recibió archivo']);
}
