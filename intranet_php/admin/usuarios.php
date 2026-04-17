<?php
/**
 * Gestión de Usuarios Administradores
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$flash = getFlashMessage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = sanitize($_POST['usuario']);
    $nombre_completo = sanitize($_POST['nombre_completo']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    if ($_POST['form_action'] === 'add') {
        // Verificar que el usuario no exista
        $check = $pdo->prepare("SELECT id FROM administradores WHERE usuario = ?");
        $check->execute([$usuario]);
        if ($check->fetch()) {
            setFlashMessage('El usuario ya existe', 'danger');
        } elseif (empty($password)) {
            setFlashMessage('Debe ingresar una contraseña', 'danger');
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO administradores (usuario, password, nombre_completo, email, activo) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$usuario, $passwordHash, $nombre_completo, $email, $activo]);
            setFlashMessage('Usuario agregado correctamente', 'success');
        }
    } elseif ($_POST['form_action'] === 'edit') {
        // Verificar que el usuario no esté duplicado
        $check = $pdo->prepare("SELECT id FROM administradores WHERE usuario = ? AND id != ?");
        $check->execute([$usuario, $_POST['id']]);
        if ($check->fetch()) {
            setFlashMessage('El usuario ya existe', 'danger');
        } else {
            if (!empty($password)) {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE administradores SET usuario = ?, password = ?, nombre_completo = ?, email = ?, activo = ? WHERE id = ?");
                $stmt->execute([$usuario, $passwordHash, $nombre_completo, $email, $activo, $_POST['id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE administradores SET usuario = ?, nombre_completo = ?, email = ?, activo = ? WHERE id = ?");
                $stmt->execute([$usuario, $nombre_completo, $email, $activo, $_POST['id']]);
            }
            setFlashMessage('Usuario actualizado correctamente', 'success');
        }
    }
    header('Location: usuarios.php');
    exit;
}

if ($action === 'delete' && $id) {
    // No permitir eliminar al usuario actual
    if ($id == $_SESSION['admin_id']) {
        setFlashMessage('No puede eliminar su propio usuario', 'danger');
    } else {
        $pdo->prepare("DELETE FROM administradores WHERE id = ?")->execute([$id]);
        setFlashMessage('Usuario eliminado correctamente', 'success');
    }
    header('Location: usuarios.php');
    exit;
}

$editData = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM administradores WHERE id = ?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch();
}

$usuarios = $pdo->query("SELECT * FROM administradores ORDER BY nombre_completo ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - Admin</title>
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
                    <a href="portales.php"><i class="fas fa-external-link-alt"></i> <span>Portales Clientes</span></a>
                    <a href="countdown.php"><i class="fas fa-hourglass-half"></i> <span>Cuenta Regresiva</span></a>
                    <a href="avisos.php"><i class="fas fa-bullhorn"></i> <span>Avisos</span></a>
                    <a href="usuarios.php" class="active"><i class="fas fa-users-cog"></i> <span>Usuarios</span></a>
                </div>
            </nav>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-users-cog"></i> Usuarios Administradores</h1>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </div>

            <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></div>
            <?php endif; ?>

            <?php if ($action === 'add' || $action === 'edit'): ?>
            <div class="content-card">
                <div class="card-header">
                    <h2><?php echo $action === 'add' ? 'Agregar Usuario' : 'Editar Usuario'; ?></h2>
                    <a href="usuarios.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Volver</a>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="form_action" value="<?php echo $action; ?>">
                        <?php if ($editData): ?><input type="hidden" name="id" value="<?php echo $editData['id']; ?>"><?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Usuario *</label>
                                <input type="text" name="usuario" class="form-control" required value="<?php echo htmlspecialchars($editData['usuario'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Contraseña <?php echo $action === 'add' ? '*' : '(dejar vacío para mantener)'; ?></label>
                                <input type="password" name="password" class="form-control" <?php echo $action === 'add' ? 'required' : ''; ?>>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nombre Completo *</label>
                                <input type="text" name="nombre_completo" class="form-control" required value="<?php echo htmlspecialchars($editData['nombre_completo'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($editData['email'] ?? ''); ?>">
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
                    <h2><i class="fas fa-list"></i> Lista de Usuarios</h2>
                    <a href="usuarios.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Agregar</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr><th>Usuario</th><th>Nombre</th><th>Email</th><th>Último Acceso</th><th>Estado</th><th>Acciones</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $user): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($user['usuario']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['nombre_completo']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email'] ?: '-'); ?></td>
                                    <td><?php echo $user['ultimo_acceso'] ? formatearFecha($user['ultimo_acceso'], 'd/m/Y H:i') : 'Nunca'; ?></td>
                                    <td><span class="badge badge-<?php echo $user['activo'] ? 'active' : 'inactive'; ?>"><?php echo $user['activo'] ? 'Activo' : 'Inactivo'; ?></span></td>
                                    <td class="actions">
                                        <a href="usuarios.php?action=edit&id=<?php echo $user['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                                        <?php if ($user['id'] != $_SESSION['admin_id']): ?>
                                        <button onclick="if(confirmDelete('¿Está seguro de eliminar este usuario?')) location.href='usuarios.php?action=delete&id=<?php echo $user['id']; ?>'" class="btn-delete"><i class="fas fa-trash"></i></button>
                                        <?php endif; ?>
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
