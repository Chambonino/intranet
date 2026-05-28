<?php
/**
 * Gestión de Cumpleaños de Empleados
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$flash = getFlashMessage();
$departamentos = getDepartamentos($pdo);

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_completo = sanitize($_POST['nombre_completo']);
    $departamento_id = $_POST['departamento_id'] ?: null;
    $puesto = sanitize($_POST['puesto']);
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $fecha_ingreso = $_POST['fecha_ingreso'] ?: null;
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $result = uploadFile($_FILES['foto'], 'employees', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        if ($result['success']) {
            $foto = $result['filename'];
        } else {
            setFlashMessage($result['message'], 'danger');
            header('Location: cumpleanos.php');
            exit;
        }
    }
    
    if ($_POST['form_action'] === 'add') {
        $stmt = $pdo->prepare("INSERT INTO empleados_cumpleanos (nombre_completo, foto, departamento_id, puesto, fecha_nacimiento, fecha_ingreso, activo) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nombre_completo, $foto, $departamento_id, $puesto, $fecha_nacimiento, $fecha_ingreso, $activo]);
        setFlashMessage('Empleado agregado correctamente', 'success');
    } elseif ($_POST['form_action'] === 'edit') {
        $update_foto = $foto ? ", foto = '$foto'" : "";
        $stmt = $pdo->prepare("UPDATE empleados_cumpleanos SET nombre_completo = ?, departamento_id = ?, puesto = ?, fecha_nacimiento = ?, fecha_ingreso = ?, activo = ? $update_foto WHERE id = ?");
        $stmt->execute([$nombre_completo, $departamento_id, $puesto, $fecha_nacimiento, $fecha_ingreso, $activo, $_POST['id']]);
        setFlashMessage('Empleado actualizado correctamente', 'success');
    }
    header('Location: cumpleanos.php');
    exit;
}

// Eliminar
if ($action === 'delete' && $id) {
    $pdo->prepare("DELETE FROM empleados_cumpleanos WHERE id = ?")->execute([$id]);
    setFlashMessage('Registro eliminado correctamente', 'success');
    header('Location: cumpleanos.php');
    exit;
}

$editData = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM empleados_cumpleanos WHERE id = ?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch();
}

$empleados = $pdo->query("SELECT e.*, d.nombre as departamento_nombre FROM empleados_cumpleanos e LEFT JOIN departamentos d ON e.departamento_id = d.id ORDER BY e.nombre_completo ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cumpleaños - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php renderAdminSidebar('cumpleanos'); ?>

        <main class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-birthday-cake"></i> Cumpleaños de Empleados</h1>
                <div class="user-menu">
                    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Salir</a>
                </div>
            </div>

            <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $flash['message']; ?>
            </div>
            <?php endif; ?>

            <?php if ($action === 'add' || $action === 'edit'): ?>
            <div class="content-card">
                <div class="card-header">
                    <h2><?php echo $action === 'add' ? 'Agregar Empleado' : 'Editar Empleado'; ?></h2>
                    <a href="cumpleanos.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Volver</a>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="form_action" value="<?php echo $action; ?>">
                        <?php if ($editData): ?>
                        <input type="hidden" name="id" value="<?php echo $editData['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nombre Completo *</label>
                                <input type="text" name="nombre_completo" class="form-control" required
                                       value="<?php echo htmlspecialchars($editData['nombre_completo'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Fecha de Nacimiento *</label>
                                <input type="date" name="fecha_nacimiento" class="form-control" required
                                       value="<?php echo $editData['fecha_nacimiento'] ?? ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Fecha de Ingreso (para aniversarios)</label>
                                <input type="date" name="fecha_ingreso" class="form-control"
                                       value="<?php echo $editData['fecha_ingreso'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Departamento</label>
                                <select name="departamento_id" class="form-control">
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($departamentos as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo ($editData['departamento_id'] ?? '') == $dept['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Puesto</label>
                                <input type="text" name="puesto" class="form-control"
                                       value="<?php echo htmlspecialchars($editData['puesto'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Foto</label>
                            <input type="file" name="foto" accept="image/*" onchange="previewImage(this, 'preview')">
                            <?php if ($editData && $editData['foto']): ?>
                            <img src="../assets/uploads/employees/<?php echo $editData['foto']; ?>" class="preview-image" id="preview">
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
                    <h2><i class="fas fa-list"></i> Lista de Empleados</h2>
                    <a href="cumpleanos.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Agregar</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Foto</th>
                                    <th>Nombre</th>
                                    <th>Departamento</th>
                                    <th>Puesto</th>
                                    <th>Cumpleaños</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($empleados as $emp): ?>
                                <tr>
                                    <td><img src="../assets/uploads/employees/<?php echo $emp['foto'] ?: 'default.png'; ?>" alt="" onerror="this.src='../assets/img/default-avatar.png'"></td>
                                    <td><?php echo htmlspecialchars($emp['nombre_completo']); ?></td>
                                    <td><?php echo htmlspecialchars($emp['departamento_nombre'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($emp['puesto'] ?: '-'); ?></td>
                                    <td><?php echo date('d/m', strtotime($emp['fecha_nacimiento'])); ?></td>
                                    <td><span class="badge badge-<?php echo $emp['activo'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $emp['activo'] ? 'Activo' : 'Inactivo'; ?>
                                    </span></td>
                                    <td class="actions">
                                        <a href="cumpleanos.php?action=edit&id=<?php echo $emp['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                                        <button onclick="if(confirmDelete()) location.href='cumpleanos.php?action=delete&id=<?php echo $emp['id']; ?>'" class="btn-delete"><i class="fas fa-trash"></i></button>
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
