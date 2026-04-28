<?php
/**
 * API para subir archivos desde Summernote (imágenes y documentos)
 */
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    
    // Determinar carpeta según tipo
    $imgTypes = ['jpg','jpeg','png','gif','webp'];
    $docTypes = ['pdf','doc','docx','xls','xlsx','ppt','pptx'];
    
    if (in_array($ext, $imgTypes)) {
        $result = uploadFile($_FILES['file'], 'articles', $imgTypes);
        $folder = 'articles';
    } elseif (in_array($ext, $docTypes)) {
        $result = uploadFile($_FILES['file'], 'articles', $docTypes);
        $folder = 'articles';
    } else {
        echo json_encode(['error' => 'Tipo de archivo no permitido. Use: ' . implode(', ', array_merge($imgTypes, $docTypes))]);
        exit;
    }
    
    if ($result['success']) {
        $url = '../assets/uploads/' . $folder . '/' . $result['filename'];
        $originalName = pathinfo($_FILES['file']['name'], PATHINFO_FILENAME);
        echo json_encode([
            'url' => $url,
            'filename' => $result['filename'],
            'originalName' => $originalName . '.' . $ext,
            'extension' => $ext,
            'isDocument' => in_array($ext, $docTypes)
        ]);
    } else {
        echo json_encode(['error' => $result['message']]);
    }
} else {
    echo json_encode(['error' => 'No se recibió archivo']);
}
