<?php
/**
 * Gestión de Misión, Visión, Valores, Historia, Objetivos, Política Integral, Reconocimientos
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin();

$flash = getFlashMessage();

// Iconos por sección
$iconosSeccion = [
    'mision'            => 'bullseye',
    'vision'            => 'eye',
    'valores'           => 'heart',
    'historia'          => 'landmark',
    'objetivos'         => 'flag-checkered',
    'politica_integral' => 'shield-halved',
    'reconocimientos'   => 'trophy'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $titulo = sanitize($_POST['titulo']);
    $contenido = $_POST['contenido'];

    $archivo_pdf = null;
    if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] !== UPLOAD_ERR_NO_FILE) {
        $result = uploadFile($_FILES['archivo_pdf'], 'company', ['pdf','doc','docx']);
        if ($result['success']) { $archivo_pdf = $result['filename']; }
    }

    $imagen = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
        $resultImg = uploadFile($_FILES['imagen'], 'company', ['jpg','jpeg','png','gif','webp']);
        if ($resultImg['success']) { $imagen = $resultImg['filename']; }
    }

    $sql = "UPDATE info_compania SET titulo = ?, contenido = ?";
    $params = [$titulo, $contenido];
    if ($archivo_pdf) { $sql .= ", archivo_pdf = ?"; $params[] = $archivo_pdf; }
    if ($imagen)      { $sql .= ", imagen = ?";      $params[] = $imagen; }
    $sql .= " WHERE id = ?"; $params[] = $id;

    $pdo->prepare($sql)->execute($params);
    setFlashMessage('Información actualizada correctamente', 'success');
    header('Location: compania.php'); exit;
}

$secciones = $pdo->query("SELECT * FROM info_compania WHERE activo = 1 ORDER BY orden ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuestra Compañía - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/lang/summernote-es-ES.min.js"></script>
</head>
<body>
    <div class="admin-wrapper">
        <?php renderAdminSidebar('compania'); ?>
        <main class="main-content">
            <div class="top-bar"><h1><i class="fas fa-building"></i> Misión, Visión, Valores y Políticas</h1><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Salir</a></div>
            <?php if ($flash): ?><div class="alert alert-<?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></div><?php endif; ?>
            
            <?php foreach ($secciones as $sec):
                $secKey = $sec['seccion'];
                $secIcon = $iconosSeccion[$secKey] ?? 'info-circle';
                $secLabel = ucfirst(str_replace('_', ' ', $secKey));
            ?>
            <div class="content-card" style="margin-bottom: 20px;">
                <div class="card-header"><h2><i class="fas fa-<?php echo $secIcon; ?>"></i> <?php echo strtoupper($secLabel); ?></h2></div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $sec['id']; ?>">
                        <div class="form-group"><label>Título</label><input type="text" name="titulo" class="form-control" value="<?php echo htmlspecialchars($sec['titulo']); ?>"></div>
                        <div class="form-group"><label>Contenido</label><textarea name="contenido" class="form-control sn-editor" rows="4"><?php echo htmlspecialchars($sec['contenido']); ?></textarea></div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Imagen (opcional - jpg, png, gif, webp)</label>
                                <input type="file" name="imagen" accept="image/*">
                                <?php if (!empty($sec['imagen'])): ?>
                                <p style="margin-top:8px;"><img src="../assets/uploads/company/<?php echo $sec['imagen']; ?>" style="max-height:80px;border-radius:8px;"></p>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label>Archivo adjunto (PDF/Word)</label>
                                <input type="file" name="archivo_pdf" accept=".pdf,.doc,.docx">
                                <?php if (!empty($sec['archivo_pdf'])): ?>
                                <p style="margin-top:8px;color:#666;"><i class="fas fa-file-pdf" style="color:red;"></i> <?php echo $sec['archivo_pdf']; ?> <a href="../assets/uploads/company/<?php echo $sec['archivo_pdf']; ?>" target="_blank" style="margin-left:8px;font-size:0.8rem;">Ver</a></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </main>
    </div>
    <script>
    $('.sn-editor').summernote({
        height: 250,
        lang: 'es-ES',
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
            ['fontname', ['fontname']],
            ['fontsize', ['fontsize']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['height', ['height']],
            ['insert', ['link', 'picture', 'video', 'table', 'hr']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ],
        fontNames: ['Arial', 'Calibri', 'Cambria', 'Comic Sans MS', 'Courier New', 'Georgia', 'Helvetica', 'Impact', 'Lucida Console', 'Roboto', 'Segoe UI', 'Tahoma', 'Times New Roman', 'Trebuchet MS', 'Verdana'],
        fontNamesIgnoreCheck: ['Roboto', 'Segoe UI'],
        fontSizes: ['8', '9', '10', '11', '12', '14', '16', '18', '20', '24', '28', '32', '36', '48', '64'],
        callbacks: {
            onImageUpload: function(files) {
                for (var i = 0; i < files.length; i++) {
                    uploadCompanyImage(files[i], this);
                }
            }
        }
    });
    // Cargar idioma español de Summernote
    $.fn.summernote.lang['es-ES'] = $.fn.summernote.lang['es-ES'] || $.fn.summernote.lang['en-US'];

    function uploadCompanyImage(file, editor) {
        var data = new FormData();
        data.append('file', file);
        data.append('folder', 'company');
        $.ajax({
            url: '../api/upload_image.php',
            method: 'POST',
            data: data,
            processData: false,
            contentType: false,
            success: function(res) {
                try {
                    var r = typeof res === 'string' ? JSON.parse(res) : res;
                    if (r.url) {
                        // Guardar URL relativa al sitio (sin ../), funciona en compania_detalle.php raíz
                        var imgUrl = 'assets/uploads/company/' + r.filename;
                        $(editor).summernote('insertImage', imgUrl);
                    } else {
                        alert('Error al subir: ' + (r.error || 'desconocido'));
                    }
                } catch (e) { alert('Error procesando respuesta'); }
            },
            error: function() { alert('Error subiendo imagen'); }
        });
    }
    </script>
</body>
</html>
