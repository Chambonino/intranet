<?php
/**
 * Gestión de Artículos/Noticias
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$flash = getFlashMessage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = sanitize($_POST['titulo']);
    $contenido = $_POST['contenido']; // Permitir HTML
    $autor = sanitize($_POST['autor']);
    $destacado = isset($_POST['destacado']) ? 1 : 0;
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    $imagen = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $result = uploadFile($_FILES['imagen'], 'articles', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        if ($result['success']) {
            $imagen = $result['filename'];
        }
    }
    
    if ($_POST['form_action'] === 'add') {
        $stmt = $pdo->prepare("INSERT INTO articulos (titulo, contenido, imagen, autor, destacado, activo) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$titulo, $contenido, $imagen, $autor, $destacado, $activo]);
        setFlashMessage('Artículo agregado correctamente', 'success');
    } elseif ($_POST['form_action'] === 'edit') {
        $update_imagen = $imagen ? ", imagen = '$imagen'" : "";
        $stmt = $pdo->prepare("UPDATE articulos SET titulo = ?, contenido = ?, autor = ?, destacado = ?, activo = ? $update_imagen WHERE id = ?");
        $stmt->execute([$titulo, $contenido, $autor, $destacado, $activo, $_POST['id']]);
        setFlashMessage('Artículo actualizado correctamente', 'success');
    }
    header('Location: articulos.php');
    exit;
}

if ($action === 'delete' && $id) {
    $pdo->prepare("DELETE FROM articulos WHERE id = ?")->execute([$id]);
    setFlashMessage('Artículo eliminado correctamente', 'success');
    header('Location: articulos.php');
    exit;
}

$editData = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM articulos WHERE id = ?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch();
}

$articulos = $pdo->query("SELECT * FROM articulos ORDER BY fecha_publicacion DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artículos - Admin</title>
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
                    <a href="videos.php"><i class="fas fa-video"></i> <span>Videos</span></a>
                    <a href="articulos.php" class="active"><i class="fas fa-newspaper"></i> <span>Artículos</span></a>
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
                <h1><i class="fas fa-newspaper"></i> Artículos / Noticias</h1>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </div>

            <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></div>
            <?php endif; ?>

            <?php if ($action === 'add' || $action === 'edit'): ?>
            <div class="content-card">
                <div class="card-header">
                    <h2><?php echo $action === 'add' ? 'Agregar Artículo' : 'Editar Artículo'; ?></h2>
                    <a href="articulos.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Volver</a>
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
                                <label>Autor</label>
                                <input type="text" name="autor" class="form-control" value="<?php echo htmlspecialchars($editData['autor'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Contenido *</label>
                            <textarea name="contenido" class="form-control" rows="10" required><?php echo htmlspecialchars($editData['contenido'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Imagen de portada</label>
                            <input type="file" name="imagen" accept="image/*" onchange="previewImage(this, 'preview')">
                            <?php if ($editData && $editData['imagen']): ?>
                            <img src="../assets/uploads/articles/<?php echo $editData['imagen']; ?>" class="preview-image" id="preview">
                            <?php else: ?>
                            <img src="" class="preview-image" id="preview" style="display: none;">
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label><input type="checkbox" name="destacado" <?php echo ($editData['destacado'] ?? 0) ? 'checked' : ''; ?>> Destacado</label>
                            <label style="margin-left: 20px;"><input type="checkbox" name="activo" <?php echo ($editData['activo'] ?? 1) ? 'checked' : ''; ?>> Activo</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> Lista de Artículos</h2>
                    <a href="articulos.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Agregar</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr><th>Imagen</th><th>Título</th><th>Autor</th><th>Fecha</th><th>Estado</th><th>Acciones</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($articulos as $art): ?>
                                <tr>
                                    <td>
                                        <?php if ($art['imagen']): ?>
                                        <img src="../assets/uploads/articles/<?php echo $art['imagen']; ?>" alt="">
                                        <?php else: ?>
                                        <i class="fas fa-newspaper" style="font-size: 2rem; color: #666;"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($art['titulo']); ?>
                                        <?php if ($art['destacado']): ?><span class="badge" style="background: #ff9800; color: white; margin-left: 5px;">Destacado</span><?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($art['autor'] ?: '-'); ?></td>
                                    <td><?php echo formatearFecha($art['fecha_publicacion']); ?></td>
                                    <td><span class="badge badge-<?php echo $art['activo'] ? 'active' : 'inactive'; ?>"><?php echo $art['activo'] ? 'Activo' : 'Inactivo'; ?></span></td>
                                    <td class="actions">
                                        <a href="articulos.php?action=edit&id=<?php echo $art['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                                        <button onclick="if(confirmDelete()) location.href='articulos.php?action=delete&id=<?php echo $art['id']; ?>'" class="btn-delete"><i class="fas fa-trash"></i></button>
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
