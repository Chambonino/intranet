<?php
/**
 * Gestión de Portales de Clientes
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$flash = getFlashMessage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitize($_POST['nombre']);
    $url = sanitize($_POST['url']);
    $descripcion = sanitize($_POST['descripcion']);
    $orden = (int)$_POST['orden'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    $logo = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $result = uploadFile($_FILES['logo'], 'portals', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
        if ($result['success']) {
            $logo = $result['filename'];
        } else {
            setFlashMessage($result['message'], 'danger');
            header('Location: portales.php');
            exit;
        }
    }
    
    if ($_POST['form_action'] === 'add') {
        $stmt = $pdo->prepare("INSERT INTO portales_clientes (nombre, logo, url, descripcion, orden, activo) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $logo, $url, $descripcion, $orden, $activo]);
        setFlashMessage('Portal agregado correctamente', 'success');
    } elseif ($_POST['form_action'] === 'edit') {
        $update_logo = $logo ? ", logo = '$logo'" : "";
        $stmt = $pdo->prepare("UPDATE portales_clientes SET nombre = ?, url = ?, descripcion = ?, orden = ?, activo = ? $update_logo WHERE id = ?");
        $stmt->execute([$nombre, $url, $descripcion, $orden, $activo, $_POST['id']]);
        setFlashMessage('Portal actualizado correctamente', 'success');
    }
    header('Location: portales.php');
    exit;
}

if ($action === 'delete' && $id) {
    $pdo->prepare("DELETE FROM portales_clientes WHERE id = ?")->execute([$id]);
    setFlashMessage('Portal eliminado correctamente', 'success');
    header('Location: portales.php');
    exit;
}

$editData = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM portales_clientes WHERE id = ?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch();
}

$portales = $pdo->query("SELECT * FROM portales_clientes ORDER BY orden ASC, nombre ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portales de Clientes - Admin</title>
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
                    <a href="articulos.php"><i class="fas fa-newspaper"></i> <span>Artículos</span></a>
                </div>
                <div class="menu-section">
                    <a href="archivos.php"><i class="fas fa-folder"></i> <span>Archivos Depto.</span></a>
                    <a href="portales.php" class="active"><i class="fas fa-external-link-alt"></i> <span>Portales Clientes</span></a>
                    <a href="countdown.php"><i class="fas fa-hourglass-half"></i> <span>Cuenta Regresiva</span></a>
                    <a href="avisos.php"><i class="fas fa-bullhorn"></i> <span>Avisos</span></a>
                    <a href="usuarios.php"><i class="fas fa-users-cog"></i> <span>Usuarios</span></a>
                </div>
            </nav>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-external-link-alt"></i> Portales de Clientes</h1>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </div>

            <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></div>
            <?php endif; ?>

            <?php if ($action === 'add' || $action === 'edit'): ?>
            <div class="content-card">
                <div class="card-header">
                    <h2><?php echo $action === 'add' ? 'Agregar Portal' : 'Editar Portal'; ?></h2>
                    <a href="portales.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Volver</a>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="form_action" value="<?php echo $action; ?>">
                        <?php if ($editData): ?><input type="hidden" name="id" value="<?php echo $editData['id']; ?>"><?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nombre del Cliente *</label>
                                <input type="text" name="nombre" class="form-control" required value="<?php echo htmlspecialchars($editData['nombre'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>URL del Portal *</label>
                                <input type="url" name="url" class="form-control" required value="<?php echo htmlspecialchars($editData['url'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Logo</label>
                                <input type="file" name="logo" accept="image/*" onchange="previewImage(this, 'preview')">
                                <?php if ($editData && $editData['logo']): ?>
                                <img src="../assets/uploads/portals/<?php echo $editData['logo']; ?>" class="preview-image" id="preview">
                                <?php else: ?>
                                <img src="" class="preview-image" id="preview" style="display: none;">
                                <?php endif; ?>
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
                    <h2><i class="fas fa-list"></i> Lista de Portales</h2>
                    <a href="portales.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Agregar</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr><th>Logo</th><th>Nombre</th><th>URL</th><th>Orden</th><th>Estado</th><th>Acciones</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($portales as $portal): ?>
                                <tr>
                                    <td>
                                        <?php if ($portal['logo']): ?>
                                        <img src="../assets/uploads/portals/<?php echo $portal['logo']; ?>" alt="">
                                        <?php else: ?>
                                        <i class="fas fa-building" style="font-size: 2rem; color: #666;"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($portal['nombre']); ?></td>
                                    <td><a href="<?php echo htmlspecialchars($portal['url']); ?>" target="_blank"><?php echo truncarTexto($portal['url'], 40); ?></a></td>
                                    <td><?php echo $portal['orden']; ?></td>
                                    <td><span class="badge badge-<?php echo $portal['activo'] ? 'active' : 'inactive'; ?>"><?php echo $portal['activo'] ? 'Activo' : 'Inactivo'; ?></span></td>
                                    <td class="actions">
                                        <a href="portales.php?action=edit&id=<?php echo $portal['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                                        <button onclick="if(confirmDelete()) location.href='portales.php?action=delete&id=<?php echo $portal['id']; ?>'" class="btn-delete"><i class="fas fa-trash"></i></button>
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
