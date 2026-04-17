<?php
/**
 * Gestión de Videos
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$flash = getFlashMessage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = sanitize($_POST['titulo']);
    $descripcion = sanitize($_POST['descripcion']);
    $orden = (int)$_POST['orden'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    $archivo_video = null;
    $thumbnail = null;
    
    if (isset($_FILES['archivo_video']) && $_FILES['archivo_video']['error'] !== UPLOAD_ERR_NO_FILE) {
        $result = uploadFile($_FILES['archivo_video'], 'videos', ['mp4', 'webm', 'ogg', 'mov', 'avi']);
        if ($result['success']) {
            $archivo_video = $result['filename'];
        } else {
            setFlashMessage($result['message'], 'danger');
            header('Location: videos.php');
            exit;
        }
    }
    
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] !== UPLOAD_ERR_NO_FILE) {
        $result = uploadFile($_FILES['thumbnail'], 'videos', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        if ($result['success']) {
            $thumbnail = $result['filename'];
        }
    }
    
    if ($_POST['form_action'] === 'add') {
        if (!$archivo_video) {
            setFlashMessage('Debe seleccionar un video', 'danger');
        } else {
            $stmt = $pdo->prepare("INSERT INTO videos (titulo, descripcion, archivo_video, thumbnail, orden, activo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$titulo, $descripcion, $archivo_video, $thumbnail, $orden, $activo]);
            setFlashMessage('Video agregado correctamente', 'success');
        }
    } elseif ($_POST['form_action'] === 'edit') {
        $updates = [];
        $params = [$titulo, $descripcion, $orden, $activo];
        
        if ($archivo_video) {
            $updates[] = "archivo_video = ?";
            $params[] = $archivo_video;
        }
        if ($thumbnail) {
            $updates[] = "thumbnail = ?";
            $params[] = $thumbnail;
        }
        
        $params[] = $_POST['id'];
        $extra = count($updates) > 0 ? ", " . implode(", ", $updates) : "";
        
        $stmt = $pdo->prepare("UPDATE videos SET titulo = ?, descripcion = ?, orden = ?, activo = ? $extra WHERE id = ?");
        $stmt->execute($params);
        setFlashMessage('Video actualizado correctamente', 'success');
    }
    header('Location: videos.php');
    exit;
}

if ($action === 'delete' && $id) {
    $pdo->prepare("DELETE FROM videos WHERE id = ?")->execute([$id]);
    setFlashMessage('Video eliminado correctamente', 'success');
    header('Location: videos.php');
    exit;
}

$editData = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM videos WHERE id = ?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch();
}

$videos = $pdo->query("SELECT * FROM videos ORDER BY orden ASC, id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Videos - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header"><h2>Intranet Admin</h2></div>
            <nav class="sidebar-menu">
                <div class="menu-section">
                    <a href="index.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
                </div>
                <div class="menu-section">
                    <a href="slider.php"><i class="fas fa-images"></i> <span>Slider Noticias</span></a>
                    <a href="eventos.php"><i class="fas fa-calendar-alt"></i> <span>Eventos</span></a>
                    <a href="cumpleanos.php"><i class="fas fa-birthday-cake"></i> <span>Cumpleaños</span></a>
                    <a href="galeria.php"><i class="fas fa-photo-video"></i> <span>Galería Fotos</span></a>
                    <a href="videos.php" class="active"><i class="fas fa-video"></i> <span>Videos</span></a>
                    <a href="articulos.php"><i class="fas fa-newspaper"></i> <span>Artículos</span></a>
                </div>
                <div class="menu-section">
                    <a href="archivos.php"><i class="fas fa-folder"></i> <span>Archivos Depto.</span></a>
                    <a href="portales.php"><i class="fas fa-external-link-alt"></i> <span>Portales Clientes</span></a>
                    <a href="countdown.php"><i class="fas fa-hourglass-half"></i> <span>Cuenta Regresiva</span></a>
                    <a href="avisos.php"><i class="fas fa-bullhorn"></i> <span>Avisos</span></a>
                    <a href="usuarios.php"><i class="fas fa-users-cog"></i> <span>Usuarios</span></a>
                </div>
            </nav>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-video"></i> Videos</h1>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </div>

            <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></div>
            <?php endif; ?>

            <?php if ($action === 'add' || $action === 'edit'): ?>
            <div class="content-card">
                <div class="card-header">
                    <h2><?php echo $action === 'add' ? 'Agregar Video' : 'Editar Video'; ?></h2>
                    <a href="videos.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Volver</a>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="form_action" value="<?php echo $action; ?>">
                        <?php if ($editData): ?><input type="hidden" name="id" value="<?php echo $editData['id']; ?>"><?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Título *</label>
                                <input type="text" name="titulo" class="form-control" required value="<?php echo htmlspecialchars($editData['titulo'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Orden</label>
                                <input type="number" name="orden" class="form-control" min="0" value="<?php echo $editData['orden'] ?? 0; ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="2"><?php echo htmlspecialchars($editData['descripcion'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Archivo de Video <?php echo $action === 'add' ? '*' : ''; ?> (mp4, webm, ogg)</label>
                                <input type="file" name="archivo_video" accept="video/*" <?php echo $action === 'add' ? 'required' : ''; ?>>
                                <?php if ($editData && $editData['archivo_video']): ?>
                                <p style="margin-top: 10px; color: #666;"><i class="fas fa-file-video"></i> Archivo actual: <?php echo $editData['archivo_video']; ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label>Thumbnail (imagen de portada)</label>
                                <input type="file" name="thumbnail" accept="image/*" onchange="previewImage(this, 'preview')">
                                <?php if ($editData && $editData['thumbnail']): ?>
                                <img src="../assets/uploads/videos/<?php echo $editData['thumbnail']; ?>" class="preview-image" id="preview">
                                <?php else: ?>
                                <img src="" class="preview-image" id="preview" style="display: none;">
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><input type="checkbox" name="activo" <?php echo ($editData['activo'] ?? 1) ? 'checked' : ''; ?>> Activo</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> Lista de Videos</h2>
                    <a href="videos.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Agregar</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr><th>Thumbnail</th><th>Título</th><th>Archivo</th><th>Orden</th><th>Estado</th><th>Acciones</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($videos as $video): ?>
                                <tr>
                                    <td>
                                        <?php if ($video['thumbnail']): ?>
                                        <img src="../assets/uploads/videos/<?php echo $video['thumbnail']; ?>" alt="">
                                        <?php else: ?>
                                        <i class="fas fa-video" style="font-size: 2rem; color: #666;"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($video['titulo']); ?></td>
                                    <td><?php echo $video['archivo_video']; ?></td>
                                    <td><?php echo $video['orden']; ?></td>
                                    <td><span class="badge badge-<?php echo $video['activo'] ? 'active' : 'inactive'; ?>"><?php echo $video['activo'] ? 'Activo' : 'Inactivo'; ?></span></td>
                                    <td class="actions">
                                        <a href="videos.php?action=edit&id=<?php echo $video['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                                        <button onclick="if(confirmDelete()) location.href='videos.php?action=delete&id=<?php echo $video['id']; ?>'" class="btn-delete"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
