<?php
/**
 * Gestión de Misión, Visión, Valores y Políticas
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin();

$flash = getFlashMessage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $titulo = sanitize($_POST['titulo']);
    $contenido = $_POST['contenido'];
    
    $archivo_pdf = null;
    if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] !== UPLOAD_ERR_NO_FILE) {
        $result = uploadFile($_FILES['archivo_pdf'], 'company', ['pdf']);
        if ($result['success']) { $archivo_pdf = $result['filename']; }
    }
    
    $sql = "UPDATE info_compania SET titulo = ?, contenido = ?";
    $params = [$titulo, $contenido];
    if ($archivo_pdf) { $sql .= ", archivo_pdf = ?"; $params[] = $archivo_pdf; }
    $sql .= " WHERE id = ?"; $params[] = $id;
    
    $pdo->prepare($sql)->execute($params);
    setFlashMessage('Información actualizada correctamente', 'success');
    header('Location: compania.php'); exit;
}

$secciones = $pdo->query("SELECT * FROM info_compania ORDER BY orden ASC")->fetchAll();
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
</head>
<body>
    <div class="admin-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header"><h2>Intranet Admin</h2></div>
            <nav class="sidebar-menu">
                <div class="menu-section"><a href="index.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></div>
                <div class="menu-section">
                    <a href="slider.php"><i class="fas fa-images"></i> <span>Slider</span></a>
                    <a href="eventos.php"><i class="fas fa-calendar-alt"></i> <span>Eventos</span></a>
                    <a href="cumpleanos.php"><i class="fas fa-birthday-cake"></i> <span>Cumpleaños</span></a>
                    <a href="galeria.php"><i class="fas fa-photo-video"></i> <span>Galería</span></a>
                    <a href="videos.php"><i class="fas fa-video"></i> <span>Videos</span></a>
                    <a href="articulos.php"><i class="fas fa-newspaper"></i> <span>Artículos</span></a>
                </div>
                <div class="menu-section">
                    <a href="kpis.php"><i class="fas fa-chart-line"></i> <span>KPIs</span></a>
                    <a href="archivos.php"><i class="fas fa-folder"></i> <span>Archivos</span></a>
                    <a href="portales.php"><i class="fas fa-external-link-alt"></i> <span>Portales</span></a>
                    <a href="countdown.php"><i class="fas fa-hourglass-half"></i> <span>Cuenta Regresiva</span></a>
                    <a href="avisos.php"><i class="fas fa-bullhorn"></i> <span>Avisos</span></a>
                    <a href="compania.php" class="active"><i class="fas fa-building"></i> <span>Compañía</span></a>
                    <a href="usuarios.php"><i class="fas fa-users-cog"></i> <span>Usuarios</span></a>
                </div>
            </nav>
        </aside>
        <main class="main-content">
            <div class="top-bar"><h1><i class="fas fa-building"></i> Misión, Visión, Valores y Políticas</h1><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Salir</a></div>
            <?php if ($flash): ?><div class="alert alert-<?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></div><?php endif; ?>
            
            <?php foreach ($secciones as $sec): ?>
            <div class="content-card" style="margin-bottom: 20px;">
                <div class="card-header"><h2><i class="fas fa-<?php echo $sec['seccion'] === 'mision' ? 'bullseye' : ($sec['seccion'] === 'vision' ? 'eye' : 'heart'); ?>"></i> <?php echo strtoupper($sec['seccion']); ?></h2></div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $sec['id']; ?>">
                        <div class="form-group"><label>Título</label><input type="text" name="titulo" class="form-control" value="<?php echo htmlspecialchars($sec['titulo']); ?>"></div>
                        <div class="form-group"><label>Contenido</label><textarea name="contenido" class="form-control sn-editor" rows="4"><?php echo htmlspecialchars($sec['contenido']); ?></textarea></div>
                        <div class="form-group"><label>Archivo PDF (Política)</label><input type="file" name="archivo_pdf" accept=".pdf">
                            <?php if (!empty($sec['archivo_pdf'])): ?><p style="margin-top:8px;color:#666;"><i class="fas fa-file-pdf" style="color:red;"></i> <?php echo $sec['archivo_pdf']; ?></p><?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </main>
    </div>
    <script>
    $('.sn-editor').summernote({height:150,toolbar:[['style',['bold','italic','underline']],['para',['ul','ol']],['insert',['link']]]});
    </script>
</body>
</html>
