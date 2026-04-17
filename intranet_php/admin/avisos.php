<?php
/**
 * Gestión de Avisos
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$flash = getFlashMessage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = sanitize($_POST['titulo']);
    $contenido = sanitize($_POST['contenido']);
    $tipo = $_POST['tipo'];
    $fecha_inicio = $_POST['fecha_inicio'] ?: null;
    $fecha_fin = $_POST['fecha_fin'] ?: null;
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    if ($_POST['form_action'] === 'add') {
        $stmt = $pdo->prepare("INSERT INTO avisos (titulo, contenido, tipo, fecha_inicio, fecha_fin, activo) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$titulo, $contenido, $tipo, $fecha_inicio, $fecha_fin, $activo]);
        setFlashMessage('Aviso agregado correctamente', 'success');
    } elseif ($_POST['form_action'] === 'edit') {
        $stmt = $pdo->prepare("UPDATE avisos SET titulo = ?, contenido = ?, tipo = ?, fecha_inicio = ?, fecha_fin = ?, activo = ? WHERE id = ?");
        $stmt->execute([$titulo, $contenido, $tipo, $fecha_inicio, $fecha_fin, $activo, $_POST['id']]);
        setFlashMessage('Aviso actualizado correctamente', 'success');
    }
    header('Location: avisos.php');
    exit;
}

if ($action === 'delete' && $id) {
    $pdo->prepare("DELETE FROM avisos WHERE id = ?")->execute([$id]);
    setFlashMessage('Aviso eliminado correctamente', 'success');
    header('Location: avisos.php');
    exit;
}

$editData = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM avisos WHERE id = ?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch();
}

$avisos = $pdo->query("SELECT * FROM avisos ORDER BY fecha_creacion DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avisos - Admin</title>
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
                    <a href="avisos.php" class="active"><i class="fas fa-bullhorn"></i> <span>Avisos</span></a>
                    <a href="usuarios.php"><i class="fas fa-users-cog"></i> <span>Usuarios</span></a>
                </div>
            </nav>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-bullhorn"></i> Avisos</h1>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </div>

            <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></div>
            <?php endif; ?>

            <?php if ($action === 'add' || $action === 'edit'): ?>
            <div class="content-card">
                <div class="card-header">
                    <h2><?php echo $action === 'add' ? 'Agregar Aviso' : 'Editar Aviso'; ?></h2>
                    <a href="avisos.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Volver</a>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="form_action" value="<?php echo $action; ?>">
                        <?php if ($editData): ?><input type="hidden" name="id" value="<?php echo $editData['id']; ?>"><?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Título *</label>
                                <input type="text" name="titulo" class="form-control" required value="<?php echo htmlspecialchars($editData['titulo'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Tipo</label>
                                <select name="tipo" class="form-control">
                                    <option value="info" <?php echo ($editData['tipo'] ?? '') === 'info' ? 'selected' : ''; ?>>Información (Azul)</option>
                                    <option value="warning" <?php echo ($editData['tipo'] ?? '') === 'warning' ? 'selected' : ''; ?>>Advertencia (Naranja)</option>
                                    <option value="danger" <?php echo ($editData['tipo'] ?? '') === 'danger' ? 'selected' : ''; ?>>Importante (Rojo)</option>
                                    <option value="success" <?php echo ($editData['tipo'] ?? '') === 'success' ? 'selected' : ''; ?>>Éxito (Verde)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Contenido</label>
                            <textarea name="contenido" class="form-control" rows="3"><?php echo htmlspecialchars($editData['contenido'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Fecha Inicio (opcional)</label>
                                <input type="date" name="fecha_inicio" class="form-control" value="<?php echo $editData['fecha_inicio'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Fecha Fin (opcional)</label>
                                <input type="date" name="fecha_fin" class="form-control" value="<?php echo $editData['fecha_fin'] ?? ''; ?>">
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
                    <h2><i class="fas fa-list"></i> Lista de Avisos</h2>
                    <a href="avisos.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Agregar</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr><th>Tipo</th><th>Título</th><th>Vigencia</th><th>Estado</th><th>Acciones</th></tr>
                            </thead>
                            <tbody>
                                <?php 
                                $tipos = ['info' => '#1976d2', 'warning' => '#ff9800', 'danger' => '#e53935', 'success' => '#43a047'];
                                foreach ($avisos as $aviso): 
                                ?>
                                <tr>
                                    <td><span style="display: inline-block; padding: 3px 10px; background: <?php echo $tipos[$aviso['tipo']]; ?>; color: white; border-radius: 4px; font-size: 0.8rem;"><?php echo ucfirst($aviso['tipo']); ?></span></td>
                                    <td><?php echo htmlspecialchars($aviso['titulo']); ?></td>
                                    <td>
                                        <?php 
                                        if ($aviso['fecha_inicio'] || $aviso['fecha_fin']) {
                                            echo ($aviso['fecha_inicio'] ? formatearFecha($aviso['fecha_inicio']) : 'Siempre') . ' - ' . ($aviso['fecha_fin'] ? formatearFecha($aviso['fecha_fin']) : 'Siempre');
                                        } else {
                                            echo 'Sin límite';
                                        }
                                        ?>
                                    </td>
                                    <td><span class="badge badge-<?php echo $aviso['activo'] ? 'active' : 'inactive'; ?>"><?php echo $aviso['activo'] ? 'Activo' : 'Inactivo'; ?></span></td>
                                    <td class="actions">
                                        <a href="avisos.php?action=edit&id=<?php echo $aviso['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                                        <button onclick="if(confirmDelete()) location.href='avisos.php?action=delete&id=<?php echo $aviso['id']; ?>'" class="btn-delete"><i class="fas fa-trash"></i></button>
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
