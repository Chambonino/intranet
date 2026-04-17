<?php
/**
 * Gestión de Archivos por Departamento
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$flash = getFlashMessage();
$departamentos = getDepartamentos($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitize($_POST['nombre']);
    $descripcion = sanitize($_POST['descripcion']);
    $departamento_id = $_POST['departamento_id'] ?: null;
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    $archivo = null;
    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $result = uploadFile($_FILES['archivo'], 'files', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar']);
        if ($result['success']) {
            $archivo = $result['filename'];
        } else {
            setFlashMessage($result['message'], 'danger');
            header('Location: archivos.php');
            exit;
        }
    }
    
    if ($_POST['form_action'] === 'add') {
        if (!$archivo) {
            setFlashMessage('Debe seleccionar un archivo', 'danger');
        } else {
            $stmt = $pdo->prepare("INSERT INTO archivos_departamento (nombre, descripcion, archivo, departamento_id, activo) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $descripcion, $archivo, $departamento_id, $activo]);
            setFlashMessage('Archivo agregado correctamente', 'success');
        }
    } elseif ($_POST['form_action'] === 'edit') {
        $update_archivo = $archivo ? ", archivo = '$archivo'" : "";
        $stmt = $pdo->prepare("UPDATE archivos_departamento SET nombre = ?, descripcion = ?, departamento_id = ?, activo = ? $update_archivo WHERE id = ?");
        $stmt->execute([$nombre, $descripcion, $departamento_id, $activo, $_POST['id']]);
        setFlashMessage('Archivo actualizado correctamente', 'success');
    }
    header('Location: archivos.php');
    exit;
}

if ($action === 'delete' && $id) {
    $pdo->prepare("DELETE FROM archivos_departamento WHERE id = ?")->execute([$id]);
    setFlashMessage('Archivo eliminado correctamente', 'success');
    header('Location: archivos.php');
    exit;
}

$editData = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM archivos_departamento WHERE id = ?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch();
}

$archivos = $pdo->query("SELECT a.*, d.nombre as departamento_nombre FROM archivos_departamento a LEFT JOIN departamentos d ON a.departamento_id = d.id ORDER BY d.nombre ASC, a.nombre ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archivos - Admin</title>
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
                    <a href="archivos.php" class="active"><i class="fas fa-folder"></i> <span>Archivos Depto.</span></a>
                    <a href="portales.php"><i class="fas fa-external-link-alt"></i> <span>Portales Clientes</span></a>
                    <a href="countdown.php"><i class="fas fa-hourglass-half"></i> <span>Cuenta Regresiva</span></a>
                    <a href="avisos.php"><i class="fas fa-bullhorn"></i> <span>Avisos</span></a>
                    <a href="usuarios.php"><i class="fas fa-users-cog"></i> <span>Usuarios</span></a>
                </div>
            </nav>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-folder"></i> Archivos por Departamento</h1>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </div>

            <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></div>
            <?php endif; ?>

            <?php if ($action === 'add' || $action === 'edit'): ?>
            <div class="content-card">
                <div class="card-header">
                    <h2><?php echo $action === 'add' ? 'Agregar Archivo' : 'Editar Archivo'; ?></h2>
                    <a href="archivos.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Volver</a>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="form_action" value="<?php echo $action; ?>">
                        <?php if ($editData): ?><input type="hidden" name="id" value="<?php echo $editData['id']; ?>"><?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nombre *</label>
                                <input type="text" name="nombre" class="form-control" required value="<?php echo htmlspecialchars($editData['nombre'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Departamento *</label>
                                <select name="departamento_id" class="form-control" required>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($departamentos as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo ($editData['departamento_id'] ?? '') == $dept['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="2"><?php echo htmlspecialchars($editData['descripcion'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Archivo <?php echo $action === 'add' ? '*' : ''; ?> (PDF, DOC, XLS, PPT, TXT, ZIP)</label>
                            <input type="file" name="archivo" <?php echo $action === 'add' ? 'required' : ''; ?>>
                            <?php if ($editData && $editData['archivo']): ?>
                            <p style="margin-top: 10px; color: #666;"><i class="fas fa-file"></i> Archivo actual: <?php echo $editData['archivo']; ?></p>
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
                    <h2><i class="fas fa-list"></i> Lista de Archivos</h2>
                    <a href="archivos.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Agregar</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr><th>Nombre</th><th>Departamento</th><th>Archivo</th><th>Descargas</th><th>Estado</th><th>Acciones</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($archivos as $arch): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($arch['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($arch['departamento_nombre'] ?? '-'); ?></td>
                                    <td><?php echo $arch['archivo']; ?></td>
                                    <td><?php echo $arch['descargas']; ?></td>
                                    <td><span class="badge badge-<?php echo $arch['activo'] ? 'active' : 'inactive'; ?>"><?php echo $arch['activo'] ? 'Activo' : 'Inactivo'; ?></span></td>
                                    <td class="actions">
                                        <a href="archivos.php?action=edit&id=<?php echo $arch['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                                        <button onclick="if(confirmDelete()) location.href='archivos.php?action=delete&id=<?php echo $arch['id']; ?>'" class="btn-delete"><i class="fas fa-trash"></i></button>
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
