<?php
/**
 * Gestión de Departamentos
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$flash = getFlashMessage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre      = sanitize($_POST['nombre'] ?? '');
    $descripcion = sanitize($_POST['descripcion'] ?? '');
    $color       = preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['color'] ?? '') ? $_POST['color'] : '#333333';
    $orden       = (int)($_POST['orden'] ?? 0);
    $activo      = isset($_POST['activo']) ? 1 : 0;

    if ($nombre === '') {
        setFlashMessage('El nombre es obligatorio', 'danger');
    } elseif (($_POST['form_action'] ?? '') === 'add') {
        $check = $pdo->prepare("SELECT id FROM departamentos WHERE nombre = ?");
        $check->execute([$nombre]);
        if ($check->fetch()) {
            setFlashMessage('Ya existe un departamento con ese nombre', 'danger');
        } else {
            $stmt = $pdo->prepare("INSERT INTO departamentos (nombre, descripcion, color, orden, activo) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $descripcion, $color, $orden, $activo]);
            setFlashMessage('Departamento agregado correctamente', 'success');
        }
    } elseif (($_POST['form_action'] ?? '') === 'edit') {
        $check = $pdo->prepare("SELECT id FROM departamentos WHERE nombre = ? AND id != ?");
        $check->execute([$nombre, $_POST['id']]);
        if ($check->fetch()) {
            setFlashMessage('Ya existe un departamento con ese nombre', 'danger');
        } else {
            $stmt = $pdo->prepare("UPDATE departamentos SET nombre = ?, descripcion = ?, color = ?, orden = ?, activo = ? WHERE id = ?");
            $stmt->execute([$nombre, $descripcion, $color, $orden, $activo, $_POST['id']]);
            setFlashMessage('Departamento actualizado correctamente', 'success');
        }
    }
    header('Location: departamentos.php');
    exit;
}

if ($action === 'delete' && $id) {
    // Verificar dependencias
    $deps = 0;
    foreach (['archivos_departamento','empleados_cumpleanos','kpis_departamento'] as $tabla) {
        try {
            $s = $pdo->prepare("SELECT COUNT(*) FROM `$tabla` WHERE departamento_id = ?");
            $s->execute([$id]);
            $deps += (int)$s->fetchColumn();
        } catch (Exception $e) { /* tabla puede no existir */ }
    }
    if ($deps > 0) {
        setFlashMessage("No se puede eliminar: tiene $deps registros asociados. Desactívelo en su lugar.", 'danger');
    } else {
        $pdo->prepare("DELETE FROM departamentos WHERE id = ?")->execute([$id]);
        setFlashMessage('Departamento eliminado correctamente', 'success');
    }
    header('Location: departamentos.php');
    exit;
}

$editData = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM departamentos WHERE id = ?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch();
}

$departamentos = $pdo->query("SELECT d.*, 
    (SELECT COUNT(*) FROM archivos_departamento a WHERE a.departamento_id = d.id) AS total_archivos
    FROM departamentos d ORDER BY d.orden ASC, d.nombre ASC")->fetchAll();

$activeMenu = 'departamentos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departamentos - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .dept-color-dot { display:inline-block; width:18px; height:18px; border-radius:50%; vertical-align:middle; margin-right:8px; border:2px solid rgba(255,255,255,0.15); }
        .color-picker-wrap { display:flex; align-items:center; gap:10px; }
        .color-picker-wrap input[type="color"] { width:60px; height:40px; padding:0; border:1px solid var(--border, rgba(255,255,255,0.1)); border-radius:6px; cursor:pointer; background:transparent; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php renderAdminSidebar('departamentos'); ?>

        <main class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-building-user"></i> Departamentos</h1>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </div>

            <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>" data-testid="flash-msg"><?php echo $flash['message']; ?></div>
            <?php endif; ?>

            <?php if ($action === 'add' || $action === 'edit'): ?>
            <div class="content-card">
                <div class="card-header">
                    <h2><?php echo $action === 'add' ? 'Agregar Departamento' : 'Editar Departamento'; ?></h2>
                    <a href="departamentos.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Volver</a>
                </div>
                <div class="card-body">
                    <form method="POST" data-testid="dept-form">
                        <input type="hidden" name="form_action" value="<?php echo $action; ?>">
                        <?php if ($editData): ?><input type="hidden" name="id" value="<?php echo $editData['id']; ?>"><?php endif; ?>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Nombre *</label>
                                <input type="text" name="nombre" class="form-control" required maxlength="100" value="<?php echo htmlspecialchars($editData['nombre'] ?? ''); ?>" data-testid="dept-nombre">
                            </div>
                            <div class="form-group">
                                <label>Color</label>
                                <div class="color-picker-wrap">
                                    <input type="color" name="color" value="<?php echo htmlspecialchars($editData['color'] ?? '#1976d2'); ?>" data-testid="dept-color">
                                    <small style="color:var(--text-muted, #888);">Color identificador del departamento</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="3" data-testid="dept-descripcion"><?php echo htmlspecialchars($editData['descripcion'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Orden de visualización</label>
                                <input type="number" name="orden" class="form-control" value="<?php echo (int)($editData['orden'] ?? 0); ?>" min="0" data-testid="dept-orden">
                            </div>
                            <div class="form-group">
                                <label style="margin-top:32px;display:block;">
                                    <input type="checkbox" name="activo" <?php echo (!isset($editData['activo']) || $editData['activo']) ? 'checked' : ''; ?> data-testid="dept-activo"> Activo
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" data-testid="dept-submit"><i class="fas fa-save"></i> Guardar</button>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> Lista de Departamentos (<?php echo count($departamentos); ?>)</h2>
                    <a href="departamentos.php?action=add" class="btn btn-primary btn-sm" data-testid="dept-add-btn"><i class="fas fa-plus"></i> Agregar</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr><th>#</th><th>Nombre</th><th>Descripción</th><th>Archivos</th><th>Orden</th><th>Estado</th><th>Acciones</th></tr>
                            </thead>
                            <tbody>
                                <?php if (count($departamentos) === 0): ?>
                                <tr><td colspan="7" style="text-align:center;color:var(--text-muted, #888);padding:30px;">No hay departamentos registrados</td></tr>
                                <?php else: foreach ($departamentos as $d): ?>
                                <tr data-testid="dept-row-<?php echo $d['id']; ?>">
                                    <td><?php echo $d['id']; ?></td>
                                    <td><span class="dept-color-dot" style="background:<?php echo htmlspecialchars($d['color']); ?>"></span><strong><?php echo htmlspecialchars($d['nombre']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(mb_substr($d['descripcion'] ?? '', 0, 70)) . (mb_strlen($d['descripcion'] ?? '') > 70 ? '...' : ''); ?></td>
                                    <td><span class="badge badge-active"><?php echo (int)$d['total_archivos']; ?></span></td>
                                    <td><?php echo (int)($d['orden'] ?? 0); ?></td>
                                    <td><span class="badge badge-<?php echo $d['activo'] ? 'active' : 'inactive'; ?>"><?php echo $d['activo'] ? 'Activo' : 'Inactivo'; ?></span></td>
                                    <td class="actions">
                                        <a href="departamentos.php?action=edit&id=<?php echo $d['id']; ?>" class="btn-edit" data-testid="dept-edit-<?php echo $d['id']; ?>"><i class="fas fa-edit"></i></a>
                                        <button onclick="if(confirm('¿Está seguro de eliminar este departamento? Solo es posible si no tiene registros asociados.')) location.href='departamentos.php?action=delete&id=<?php echo $d['id']; ?>'" class="btn-delete" data-testid="dept-del-<?php echo $d['id']; ?>"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
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
