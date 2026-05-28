<?php
/**
 * Gestión de Usuarios Administradores (con permisos por sección)
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$flash = getFlashMessage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario        = sanitize($_POST['usuario']);
    $nombre_completo= sanitize($_POST['nombre_completo']);
    $email          = sanitize($_POST['email']);
    $password       = $_POST['password'];
    $activo         = isset($_POST['activo']) ? 1 : 0;
    $esSuper        = isset($_POST['es_super_admin']) ? 1 : 0;

    // Permisos: array de claves de secciones marcadas
    $seccionesValidas = array_keys(getSeccionesAdminPanel());
    $permisosEnviados = isset($_POST['permisos']) && is_array($_POST['permisos']) ? array_values(array_intersect($_POST['permisos'], $seccionesValidas)) : [];
    // Si es super admin, ignoramos permisos individuales (tendrá acceso total)
    $permisosJson = $esSuper ? null : json_encode($permisosEnviados, JSON_UNESCAPED_UNICODE);

    if (($_POST['form_action'] ?? '') === 'add') {
        $check = $pdo->prepare("SELECT id FROM administradores WHERE usuario = ?");
        $check->execute([$usuario]);
        if ($check->fetch()) {
            setFlashMessage('El usuario ya existe', 'danger');
        } elseif (empty($password)) {
            setFlashMessage('Debe ingresar una contraseña', 'danger');
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO administradores (usuario, password, nombre_completo, email, activo, permisos, es_super_admin) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$usuario, $passwordHash, $nombre_completo, $email, $activo, $permisosJson, $esSuper]);
            setFlashMessage('Usuario agregado correctamente', 'success');
        }
    } elseif (($_POST['form_action'] ?? '') === 'edit') {
        $check = $pdo->prepare("SELECT id FROM administradores WHERE usuario = ? AND id != ?");
        $check->execute([$usuario, $_POST['id']]);
        if ($check->fetch()) {
            setFlashMessage('El usuario ya existe', 'danger');
        } else {
            // Protección: no permitir que el usuario actual se quite a sí mismo el super-admin si es el último
            if ($_POST['id'] == $_SESSION['admin_id'] && !$esSuper) {
                $cntSupers = $pdo->query("SELECT COUNT(*) FROM administradores WHERE es_super_admin = 1 AND activo = 1")->fetchColumn();
                if ($cntSupers <= 1) {
                    $esSuper = 1; $permisosJson = null;
                    setFlashMessage('Debe existir al menos un super-admin. Tu rol fue conservado.', 'warning');
                }
            }
            if (!empty($password)) {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE administradores SET usuario = ?, password = ?, nombre_completo = ?, email = ?, activo = ?, permisos = ?, es_super_admin = ? WHERE id = ?");
                $stmt->execute([$usuario, $passwordHash, $nombre_completo, $email, $activo, $permisosJson, $esSuper, $_POST['id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE administradores SET usuario = ?, nombre_completo = ?, email = ?, activo = ?, permisos = ?, es_super_admin = ? WHERE id = ?");
                $stmt->execute([$usuario, $nombre_completo, $email, $activo, $permisosJson, $esSuper, $_POST['id']]);
            }
            if (empty($_SESSION['_flash_warn'])) {
                setFlashMessage('Usuario actualizado correctamente', 'success');
            }
        }
    }
    header('Location: usuarios.php');
    exit;
}

if ($action === 'delete' && $id) {
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
$permisosActuales = [];
$esSuperActual = false;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM administradores WHERE id = ?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch();
    if ($editData) {
        $esSuperActual = !empty($editData['es_super_admin']);
        $permisosActuales = $editData['permisos'] ? (json_decode($editData['permisos'], true) ?: []) : [];
    }
}

$usuarios = $pdo->query("SELECT * FROM administradores ORDER BY nombre_completo ASC")->fetchAll();
$secciones = getSeccionesAdminPanel();
// Agrupar por grupo
$grupos = [];
foreach ($secciones as $k => $m) { $grupos[$m[2]][$k] = $m; }
$activeMenu = 'usuarios';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .perm-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:8px 16px; }
        .perm-group { background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); border-radius:10px; padding:14px 16px; margin-bottom:14px; }
        .perm-group-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; padding-bottom:8px; border-bottom:1px solid rgba(255,255,255,0.08); }
        .perm-group-header h4 { margin:0; font-size:0.9rem; color:#fff; letter-spacing:0.5px; text-transform:uppercase; }
        .perm-group-toggle { background:rgba(25,118,210,0.18); color:#64b5f6; border:1px solid rgba(25,118,210,0.4); padding:4px 10px; border-radius:6px; font-size:0.7rem; cursor:pointer; font-weight:600; }
        .perm-group-toggle:hover { background:rgba(25,118,210,0.3); }
        .perm-item { display:flex; align-items:center; gap:8px; padding:6px 4px; border-radius:6px; transition:background 0.15s; cursor:pointer; }
        .perm-item:hover { background:rgba(255,255,255,0.05); }
        .perm-item input[type="checkbox"] { width:16px; height:16px; cursor:pointer; }
        .perm-item span { font-size:0.85rem; color:#ddd; }
        .perm-item i { color:#64b5f6; font-size:0.85rem; width:18px; text-align:center; }
        .super-admin-banner { background:linear-gradient(135deg,rgba(255,193,7,0.15),rgba(255,152,0,0.15)); border:1px solid rgba(255,193,7,0.35); border-radius:10px; padding:14px 18px; margin-bottom:18px; display:none; align-items:center; gap:12px; color:#ffc107; }
        .super-admin-banner.active { display:flex; }
        .super-admin-banner i { font-size:1.4rem; }
        .toolbar-actions { display:flex; gap:8px; margin-bottom:14px; flex-wrap:wrap; }
        .toolbar-actions button { background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); color:#ddd; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:0.78rem; }
        .toolbar-actions button:hover { background:rgba(255,255,255,0.12); }
        .badge-super { background:linear-gradient(135deg,#ff9800,#f57c00); color:white; padding:2px 8px; border-radius:10px; font-size:0.65rem; font-weight:700; letter-spacing:1px; text-transform:uppercase; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php renderAdminSidebar('usuarios'); ?>

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
                    <form method="POST" data-testid="user-form">
                        <input type="hidden" name="form_action" value="<?php echo $action; ?>">
                        <?php if ($editData): ?><input type="hidden" name="id" value="<?php echo $editData['id']; ?>"><?php endif; ?>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Usuario *</label>
                                <input type="text" name="usuario" class="form-control" required value="<?php echo htmlspecialchars($editData['usuario'] ?? ''); ?>" data-testid="user-usuario">
                            </div>
                            <div class="form-group">
                                <label>Contraseña <?php echo $action === 'add' ? '*' : '(dejar vacío para mantener)'; ?></label>
                                <input type="password" name="password" class="form-control" <?php echo $action === 'add' ? 'required' : ''; ?> data-testid="user-password">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Nombre Completo *</label>
                                <input type="text" name="nombre_completo" class="form-control" required value="<?php echo htmlspecialchars($editData['nombre_completo'] ?? ''); ?>" data-testid="user-nombre">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($editData['email'] ?? ''); ?>" data-testid="user-email">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label style="display:block;"><input type="checkbox" name="activo" <?php echo (!isset($editData['activo']) || $editData['activo']) ? 'checked' : ''; ?> data-testid="user-activo"> Activo</label>
                            </div>
                            <div class="form-group">
                                <label style="display:block;">
                                    <input type="checkbox" name="es_super_admin" id="es_super_admin" <?php echo $esSuperActual ? 'checked' : ''; ?> data-testid="user-superadmin">
                                    <strong style="color:#ffc107;"><i class="fas fa-crown"></i> Super Administrador</strong>
                                    <small style="display:block;color:var(--text-muted,#888);margin-top:4px;font-weight:normal;">Acceso total a todas las secciones (anula la lista de permisos).</small>
                                </label>
                            </div>
                        </div>

                        <div class="super-admin-banner" id="superAdminBanner">
                            <i class="fas fa-crown"></i>
                            <div><strong>Este usuario es Super Administrador.</strong> Tendrá acceso a todas las secciones del panel. Los permisos individuales abajo se ignoran.</div>
                        </div>

                        <hr style="margin:22px 0;border:none;border-top:1px solid rgba(255,255,255,0.08);">

                        <div id="permsBlock">
                            <h3 style="margin-bottom:6px;display:flex;align-items:center;gap:8px;"><i class="fas fa-shield-alt" style="color:#64b5f6;"></i> Permisos de acceso al panel</h3>
                            <p style="color:var(--text-muted,#888);font-size:0.85rem;margin-bottom:14px;">Marque las secciones a las que este usuario podrá acceder. Si no marca ninguna, el usuario solo verá el Dashboard.</p>

                            <div class="toolbar-actions">
                                <button type="button" onclick="togglePerms(true)" data-testid="perm-select-all"><i class="fas fa-check-double"></i> Seleccionar todos</button>
                                <button type="button" onclick="togglePerms(false)" data-testid="perm-clear-all"><i class="fas fa-eraser"></i> Limpiar todos</button>
                            </div>

                            <?php foreach ($grupos as $grupo => $items): ?>
                            <div class="perm-group" data-group="<?php echo htmlspecialchars($grupo); ?>">
                                <div class="perm-group-header">
                                    <h4><?php echo htmlspecialchars($grupo); ?></h4>
                                    <button type="button" class="perm-group-toggle" onclick="toggleGroup(this)" data-testid="perm-group-toggle-<?php echo strtolower($grupo); ?>"><i class="fas fa-check-double"></i> Todo el grupo</button>
                                </div>
                                <div class="perm-grid">
                                    <?php foreach ($items as $key => $meta):
                                        // Saltar secciones que son solo-super-admin
                                        if (isset($meta[3]) && $meta[3] === true) continue;
                                        $checked = in_array($key, $permisosActuales) ? 'checked' : '';
                                    ?>
                                    <label class="perm-item">
                                        <input type="checkbox" name="permisos[]" value="<?php echo $key; ?>" class="perm-cb" <?php echo $checked; ?> data-testid="perm-<?php echo $key; ?>">
                                        <i class="fas <?php echo $meta[1]; ?>"></i>
                                        <span><?php echo htmlspecialchars($meta[0]); ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="submit" class="btn btn-primary" data-testid="user-submit"><i class="fas fa-save"></i> Guardar</button>
                    </form>
                </div>
            </div>

            <script>
                function togglePerms(state) {
                    document.querySelectorAll('.perm-cb').forEach(cb => cb.checked = state);
                }
                function toggleGroup(btn) {
                    var group = btn.closest('.perm-group');
                    var cbs = group.querySelectorAll('.perm-cb');
                    var anyUnchecked = Array.from(cbs).some(cb => !cb.checked);
                    cbs.forEach(cb => cb.checked = anyUnchecked);
                }
                // Banner super-admin
                var superCb = document.getElementById('es_super_admin');
                var banner  = document.getElementById('superAdminBanner');
                var permsBlock = document.getElementById('permsBlock');
                function refreshSuperUI() {
                    if (superCb.checked) {
                        banner.classList.add('active');
                        permsBlock.style.opacity = '0.45';
                        permsBlock.style.pointerEvents = 'none';
                    } else {
                        banner.classList.remove('active');
                        permsBlock.style.opacity = '1';
                        permsBlock.style.pointerEvents = 'auto';
                    }
                }
                if (superCb) { superCb.addEventListener('change', refreshSuperUI); refreshSuperUI(); }
            </script>

            <?php else: ?>
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> Lista de Usuarios</h2>
                    <a href="usuarios.php?action=add" class="btn btn-primary btn-sm" data-testid="user-add-btn"><i class="fas fa-plus"></i> Agregar</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr><th>Usuario</th><th>Nombre</th><th>Email</th><th>Rol / Permisos</th><th>Último Acceso</th><th>Estado</th><th>Acciones</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $user):
                                    $perms = $user['permisos'] ? (json_decode($user['permisos'], true) ?: []) : [];
                                    $nPerms = count($perms);
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($user['usuario']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['nombre_completo']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email'] ?: '-'); ?></td>
                                    <td>
                                        <?php if (!empty($user['es_super_admin'])): ?>
                                            <span class="badge-super"><i class="fas fa-crown"></i> Super Admin</span>
                                        <?php elseif ($nPerms === 0): ?>
                                            <span style="color:var(--text-muted,#888);font-size:0.78rem;"><i class="fas fa-lock"></i> Sin acceso</span>
                                        <?php else: ?>
                                            <span class="badge badge-active"><?php echo $nPerms; ?> sección<?php echo $nPerms === 1 ? '' : 'es'; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $user['ultimo_acceso'] ? formatearFecha($user['ultimo_acceso'], 'd/m/Y H:i') : 'Nunca'; ?></td>
                                    <td><span class="badge badge-<?php echo $user['activo'] ? 'active' : 'inactive'; ?>"><?php echo $user['activo'] ? 'Activo' : 'Inactivo'; ?></span></td>
                                    <td class="actions">
                                        <a href="usuarios.php?action=edit&id=<?php echo $user['id']; ?>" class="btn-edit" data-testid="user-edit-<?php echo $user['id']; ?>"><i class="fas fa-edit"></i></a>
                                        <?php if ($user['id'] != $_SESSION['admin_id']): ?>
                                        <button onclick="if(confirm('¿Está seguro de eliminar este usuario?')) location.href='usuarios.php?action=delete&id=<?php echo $user['id']; ?>'" class="btn-delete" data-testid="user-del-<?php echo $user['id']; ?>"><i class="fas fa-trash"></i></button>
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
