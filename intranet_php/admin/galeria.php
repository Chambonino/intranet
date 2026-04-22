<?php
/**
 * Gestión de Galería de Fotos
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
    $departamento_id = $_POST['departamento_id'] ?: null;
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    $imagen = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
        $result = uploadFile($_FILES['imagen'], 'gallery', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        if ($result['success']) {
            $imagen = $result['filename'];
        } else {
            setFlashMessage($result['message'], 'danger');
            header('Location: galeria.php');
            exit;
        }
    }
    
    if ($_POST['form_action'] === 'add') {
        if (!$imagen) {
            setFlashMessage('Debe seleccionar una imagen', 'danger');
        } else {
            $stmt = $pdo->prepare("INSERT INTO galeria_fotos (titulo, descripcion, imagen, orden, departamento_id, activo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$titulo, $descripcion, $imagen, $orden, $departamento_id, $activo]);
            setFlashMessage('Foto agregada correctamente', 'success');
        }
    } elseif ($_POST['form_action'] === 'edit') {
        $update_imagen = $imagen ? ", imagen = '$imagen'" : "";
        $stmt = $pdo->prepare("UPDATE galeria_fotos SET titulo = ?, descripcion = ?, orden = ?, departamento_id = ?, activo = ? $update_imagen WHERE id = ?");
        $stmt->execute([$titulo, $descripcion, $orden, $departamento_id, $activo, $_POST['id']]);
        setFlashMessage('Foto actualizada correctamente', 'success');
    }
    header('Location: galeria.php');
    exit;
}

if ($action === 'delete' && $id) {
    $pdo->prepare("DELETE FROM galeria_fotos WHERE id = ?")->execute([$id]);
    setFlashMessage('Foto eliminada correctamente', 'success');
    header('Location: galeria.php');
    exit;
}

$editData = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM galeria_fotos WHERE id = ?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch();
}

$fotos = $pdo->query("SELECT * FROM galeria_fotos ORDER BY orden ASC, id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galería de Fotos - Admin</title>
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
                    <a href="galeria.php" class="active"><i class="fas fa-photo-video"></i> <span>Galería Fotos</span></a>
                    <a href="videos.php"><i class="fas fa-video"></i> <span>Videos</span></a>
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
                <h1><i class="fas fa-photo-video"></i> Galería de Fotos</h1>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </div>

            <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></div>
            <?php endif; ?>

            <?php if ($action === 'add' || $action === 'edit'): ?>
            <div class="content-card">
                <div class="card-header">
                    <h2><?php echo $action === 'add' ? 'Agregar Foto' : 'Editar Foto'; ?></h2>
                    <a href="galeria.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Volver</a>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="form_action" value="<?php echo $action; ?>">
                        <?php if ($editData): ?><input type="hidden" name="id" value="<?php echo $editData['id']; ?>"><?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Título</label>
                                <input type="text" name="titulo" class="form-control" value="<?php echo htmlspecialchars($editData['titulo'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Orden</label>
                                <input type="number" name="orden" class="form-control" min="0" value="<?php echo $editData['orden'] ?? 0; ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Departamento</label>
                            <select name="departamento_id" class="form-control">
                                <option value="">General</option>
                                <?php $depts = getDepartamentos($pdo); foreach ($depts as $d): ?>
                                <option value="<?php echo $d['id']; ?>" <?php echo ($editData['departamento_id'] ?? '') == $d['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="2"><?php echo htmlspecialchars($editData['descripcion'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Imagen <?php echo $action === 'add' ? '*' : ''; ?></label>
                            <input type="file" name="imagen" accept="image/*" <?php echo $action === 'add' ? 'required' : ''; ?> onchange="previewImage(this, 'preview')">
                            <?php if ($editData && $editData['imagen']): ?>
                            <img src="../assets/uploads/gallery/<?php echo $editData['imagen']; ?>" class="preview-image" id="preview">
                            <?php else: ?>
                            <img src="" class="preview-image" id="preview" style="display: none;">
                            <?php endif; ?>
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
                    <h2><i class="fas fa-list"></i> Lista de Fotos</h2>
                    <a href="galeria.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Agregar</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr><th>Imagen</th><th>Título</th><th>Orden</th><th>Estado</th><th>Acciones</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fotos as $foto): ?>
                                <tr>
                                    <td><img src="../assets/uploads/gallery/<?php echo $foto['imagen']; ?>" alt=""></td>
                                    <td><?php echo htmlspecialchars($foto['titulo'] ?: '-'); ?></td>
                                    <td><?php echo $foto['orden']; ?></td>
                                    <td><span class="badge badge-<?php echo $foto['activo'] ? 'active' : 'inactive'; ?>"><?php echo $foto['activo'] ? 'Activo' : 'Inactivo'; ?></span></td>
                                    <td class="actions">
                                        <a href="galeria.php?action=edit&id=<?php echo $foto['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                                        <button onclick="if(confirmDelete()) location.href='galeria.php?action=delete&id=<?php echo $foto['id']; ?>'" class="btn-delete"><i class="fas fa-trash"></i></button>
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
