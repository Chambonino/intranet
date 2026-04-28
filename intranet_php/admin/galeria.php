<?php
/**
 * Gestión de Galería de Fotos - Multi-upload
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$flash = getFlashMessage();
$departamentos = getDepartamentos($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = sanitize($_POST['titulo']);
    $descripcion = sanitize($_POST['descripcion']);
    $orden = (int)$_POST['orden'];
    $departamento_id = $_POST['departamento_id'] ?: null;
    $tipo_evento = sanitize($_POST['tipo_evento'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0;

    if ($_POST['form_action'] === 'add') {
        // MULTI-UPLOAD
        $count = 0;
        if (isset($_FILES['imagenes']) && is_array($_FILES['imagenes']['name'])) {
            $total = count($_FILES['imagenes']['name']);
            for ($i = 0; $i < $total; $i++) {
                if ($_FILES['imagenes']['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['imagenes']['name'][$i],
                        'type' => $_FILES['imagenes']['type'][$i],
                        'tmp_name' => $_FILES['imagenes']['tmp_name'][$i],
                        'error' => $_FILES['imagenes']['error'][$i],
                        'size' => $_FILES['imagenes']['size'][$i],
                    ];
                    $result = uploadFile($file, 'gallery', ['jpg','jpeg','png','gif','webp']);
                    if ($result['success']) {
                        $imgTitle = $total > 1 ? ($titulo ? $titulo . ' (' . ($i+1) . ')' : '') : $titulo;
                        $stmt = $pdo->prepare("INSERT INTO galeria_fotos (titulo, descripcion, imagen, orden, departamento_id, tipo_evento, activo) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$imgTitle, $descripcion, $result['filename'], $orden + $i, $departamento_id, $tipo_evento, $activo]);
                        $count++;
                    }
                }
            }
        }
        if ($count > 0) {
            setFlashMessage($count . ' foto(s) agregada(s) correctamente', 'success');
        } else {
            setFlashMessage('Debe seleccionar al menos una imagen', 'danger');
        }
        header('Location: galeria.php'); exit;

    } elseif ($_POST['form_action'] === 'edit') {
        $imagen = null;
        if (isset($_FILES['imagen_edit']) && $_FILES['imagen_edit']['error'] !== UPLOAD_ERR_NO_FILE) {
            $result = uploadFile($_FILES['imagen_edit'], 'gallery', ['jpg','jpeg','png','gif','webp']);
            if ($result['success']) { $imagen = $result['filename']; }
        }
        $update_imagen = $imagen ? ", imagen = '$imagen'" : "";
        $stmt = $pdo->prepare("UPDATE galeria_fotos SET titulo = ?, descripcion = ?, orden = ?, departamento_id = ?, tipo_evento = ?, activo = ? $update_imagen WHERE id = ?");
        $stmt->execute([$titulo, $descripcion, $orden, $departamento_id, $tipo_evento, $activo, $_POST['id']]);
        setFlashMessage('Foto actualizada correctamente', 'success');
        header('Location: galeria.php'); exit;
    }
}

if ($action === 'delete' && $id) {
    $pdo->prepare("DELETE FROM galeria_fotos WHERE id = ?")->execute([$id]);
    setFlashMessage('Foto eliminada', 'success');
    header('Location: galeria.php'); exit;
}

$editData = null;
if ($action === 'edit' && $id) { $stmt = $pdo->prepare("SELECT * FROM galeria_fotos WHERE id = ?"); $stmt->execute([$id]); $editData = $stmt->fetch(); }

$fotos = $pdo->query("SELECT g.*, d.nombre as dept_nombre FROM galeria_fotos g LEFT JOIN departamentos d ON g.departamento_id = d.id ORDER BY g.orden ASC, g.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galería de Fotos - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
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
                    <a href="galeria.php" class="active"><i class="fas fa-photo-video"></i> <span>Galería</span></a>
                    <a href="videos.php"><i class="fas fa-video"></i> <span>Videos</span></a>
                    <a href="articulos.php"><i class="fas fa-newspaper"></i> <span>Artículos</span></a>
                </div>
                <div class="menu-section">
                    <a href="kpis.php"><i class="fas fa-chart-line"></i> <span>KPIs</span></a>
                    <a href="archivos.php"><i class="fas fa-folder"></i> <span>Archivos</span></a>
                    <a href="portales.php"><i class="fas fa-external-link-alt"></i> <span>Portales</span></a>
                    <a href="countdown.php"><i class="fas fa-hourglass-half"></i> <span>Cuenta Regresiva</span></a>
                    <a href="avisos.php"><i class="fas fa-bullhorn"></i> <span>Avisos</span></a>
                    <a href="compania.php"><i class="fas fa-building"></i> <span>Compañía</span></a>
                    <a href="usuarios.php"><i class="fas fa-users-cog"></i> <span>Usuarios</span></a>
                </div>
            </nav>
        </aside>
        <main class="main-content">
            <div class="top-bar"><h1><i class="fas fa-photo-video"></i> Galería de Fotos</h1><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Salir</a></div>

            <?php if ($flash): ?><div class="alert alert-<?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></div><?php endif; ?>

            <?php if ($action === 'add' || $action === 'edit'): ?>
            <div class="content-card">
                <div class="card-header"><h2><?php echo $action === 'add' ? 'Subir Fotos' : 'Editar Foto'; ?></h2><a href="galeria.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Volver</a></div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="form_action" value="<?php echo $action; ?>">
                        <?php if ($editData): ?><input type="hidden" name="id" value="<?php echo $editData['id']; ?>"><?php endif; ?>

                        <div class="form-row">
                            <div class="form-group"><label>Título</label><input type="text" name="titulo" class="form-control" value="<?php echo htmlspecialchars($editData['titulo'] ?? ''); ?>"></div>
                            <div class="form-group"><label>Orden</label><input type="number" name="orden" class="form-control" min="0" value="<?php echo $editData['orden'] ?? 0; ?>"></div>
                        </div>
                        <div class="form-group"><label>Descripción</label><textarea name="descripcion" class="form-control" rows="2"><?php echo htmlspecialchars($editData['descripcion'] ?? ''); ?></textarea></div>
                        <div class="form-row">
                            <div class="form-group"><label>Departamento</label>
                                <select name="departamento_id" class="form-control"><option value="">General</option>
                                <?php foreach ($departamentos as $d): ?><option value="<?php echo $d['id']; ?>" <?php echo ($editData['departamento_id'] ?? '') == $d['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['nombre']); ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group"><label>Tipo de Evento</label><input type="text" name="tipo_evento" class="form-control" placeholder="Ej: Capacitación, Fiesta..." value="<?php echo htmlspecialchars($editData['tipo_evento'] ?? ''); ?>"></div>
                        </div>

                        <?php if ($action === 'add'): ?>
                        <div class="form-group">
                            <label>Imágenes * (puede seleccionar varias)</label>
                            <input type="file" name="imagenes[]" accept="image/*" multiple required style="padding:15px;border:2px dashed #555;border-radius:10px;width:100%;cursor:pointer;">
                            <small style="color:#999;">Mantenga presionado Ctrl para seleccionar múltiples archivos</small>
                            <div id="previewGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:8px;margin-top:10px;"></div>
                        </div>
                        <?php else: ?>
                        <div class="form-group">
                            <label>Imagen (dejar vacío para mantener)</label>
                            <input type="file" name="imagen_edit" accept="image/*">
                            <?php if ($editData && $editData['imagen']): ?><img src="../assets/uploads/gallery/<?php echo $editData['imagen']; ?>" class="preview-image"><?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <div class="form-group"><label><input type="checkbox" name="activo" <?php echo ($editData['activo'] ?? 1) ? 'checked' : ''; ?>> Activo</label></div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $action === 'add' ? 'Subir Fotos' : 'Guardar'; ?></button>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="content-card">
                <div class="card-header"><h2>Fotos (<?php echo count($fotos); ?>)</h2><a href="galeria.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Subir Fotos</a></div>
                <div class="card-body">
                    <!-- Vista de galería con preview -->
                    <?php
                    $grouped = [];
                    foreach ($fotos as $f) {
                        $key = ($f['dept_nombre'] ?? 'General') . ($f['tipo_evento'] ? ' - ' . $f['tipo_evento'] : '');
                        $grouped[$key][] = $f;
                    }
                    foreach ($grouped as $group => $items):
                    ?>
                    <h4 style="margin:20px 0 12px;padding-bottom:8px;border-bottom:1px solid #eee;color:#333;font-size:0.95rem;">
                        <i class="fas fa-folder" style="color:#1976d2;"></i> <?php echo htmlspecialchars($group); ?>
                        <span style="font-size:0.75rem;color:#999;font-weight:normal;">(<?php echo count($items); ?> fotos)</span>
                    </h4>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:10px;margin-bottom:15px;">
                        <?php foreach ($items as $f): ?>
                        <div style="position:relative;border-radius:8px;overflow:hidden;height:90px;border:1px solid #eee;">
                            <img src="../assets/uploads/gallery/<?php echo $f['imagen']; ?>" style="width:100%;height:100%;object-fit:cover;cursor:pointer;" onclick="window.open(this.src)" title="<?php echo htmlspecialchars($f['titulo'] ?: 'Sin título'); ?>">
                            <div style="position:absolute;top:4px;right:4px;display:flex;gap:3px;">
                                <a href="galeria.php?action=edit&id=<?php echo $f['id']; ?>" style="width:22px;height:22px;background:#1976d2;color:white;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:0.6rem;"><i class="fas fa-edit"></i></a>
                                <button onclick="if(confirmDelete()) location.href='galeria.php?action=delete&id=<?php echo $f['id']; ?>'" style="width:22px;height:22px;background:#e53935;color:white;border:none;border-radius:4px;cursor:pointer;font-size:0.6rem;"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
    <script>
    // Preview múltiples imágenes
    document.querySelector('input[name="imagenes[]"]')?.addEventListener('change', function(e) {
        const grid = document.getElementById('previewGrid');
        grid.innerHTML = '';
        Array.from(e.target.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(ev) {
                const img = document.createElement('img');
                img.src = ev.target.result;
                img.style.cssText = 'width:100%;height:80px;object-fit:cover;border-radius:6px;';
                grid.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    });
    </script>
</body>
</html>
