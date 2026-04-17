<?php
/**
 * Gestión de Cuenta Regresiva
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
    $fecha_evento = $_POST['fecha_evento'] . ' ' . $_POST['hora_evento'];
    $color = $_POST['color'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    if ($_POST['form_action'] === 'add') {
        $stmt = $pdo->prepare("INSERT INTO cuenta_regresiva (titulo, descripcion, fecha_evento, color, activo) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$titulo, $descripcion, $fecha_evento, $color, $activo]);
        setFlashMessage('Cuenta regresiva agregada correctamente', 'success');
    } elseif ($_POST['form_action'] === 'edit') {
        $stmt = $pdo->prepare("UPDATE cuenta_regresiva SET titulo = ?, descripcion = ?, fecha_evento = ?, color = ?, activo = ? WHERE id = ?");
        $stmt->execute([$titulo, $descripcion, $fecha_evento, $color, $activo, $_POST['id']]);
        setFlashMessage('Cuenta regresiva actualizada correctamente', 'success');
    }
    header('Location: countdown.php');
    exit;
}

if ($action === 'delete' && $id) {
    $pdo->prepare("DELETE FROM cuenta_regresiva WHERE id = ?")->execute([$id]);
    setFlashMessage('Cuenta regresiva eliminada correctamente', 'success');
    header('Location: countdown.php');
    exit;
}

$editData = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM cuenta_regresiva WHERE id = ?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch();
}

$countdowns = $pdo->query("SELECT * FROM cuenta_regresiva ORDER BY fecha_evento ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuenta Regresiva - Admin</title>
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
                    <a href="countdown.php" class="active"><i class="fas fa-hourglass-half"></i> <span>Cuenta Regresiva</span></a>
                    <a href="avisos.php"><i class="fas fa-bullhorn"></i> <span>Avisos</span></a>
                    <a href="usuarios.php"><i class="fas fa-users-cog"></i> <span>Usuarios</span></a>
                </div>
            </nav>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-hourglass-half"></i> Cuenta Regresiva</h1>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </div>

            <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></div>
            <?php endif; ?>

            <?php if ($action === 'add' || $action === 'edit'): ?>
            <div class="content-card">
                <div class="card-header">
                    <h2><?php echo $action === 'add' ? 'Agregar Cuenta Regresiva' : 'Editar Cuenta Regresiva'; ?></h2>
                    <a href="countdown.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Volver</a>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="form_action" value="<?php echo $action; ?>">
                        <?php if ($editData): ?><input type="hidden" name="id" value="<?php echo $editData['id']; ?>"><?php endif; ?>
                        
                        <div class="form-group">
                            <label>Título del Evento *</label>
                            <input type="text" name="titulo" class="form-control" required value="<?php echo htmlspecialchars($editData['titulo'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="2"><?php echo htmlspecialchars($editData['descripcion'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Fecha del Evento *</label>
                                <input type="date" name="fecha_evento" class="form-control" required 
                                       value="<?php echo $editData ? date('Y-m-d', strtotime($editData['fecha_evento'])) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Hora del Evento *</label>
                                <input type="time" name="hora_evento" class="form-control" required
                                       value="<?php echo $editData ? date('H:i', strtotime($editData['fecha_evento'])) : '09:00'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Color</label>
                                <input type="color" name="color" value="<?php echo $editData['color'] ?? '#F44336'; ?>">
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
                    <h2><i class="fas fa-list"></i> Lista de Cuentas Regresivas</h2>
                    <a href="countdown.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Agregar</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr><th>Color</th><th>Título</th><th>Fecha/Hora</th><th>Estado</th><th>Acciones</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($countdowns as $cd): ?>
                                <tr>
                                    <td><span style="display: inline-block; width: 25px; height: 25px; background: <?php echo $cd['color']; ?>; border-radius: 4px;"></span></td>
                                    <td><?php echo htmlspecialchars($cd['titulo']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($cd['fecha_evento'])); ?></td>
                                    <td>
                                        <?php if (strtotime($cd['fecha_evento']) < time()): ?>
                                        <span class="badge" style="background: #666; color: white;">Pasado</span>
                                        <?php else: ?>
                                        <span class="badge badge-<?php echo $cd['activo'] ? 'active' : 'inactive'; ?>"><?php echo $cd['activo'] ? 'Activo' : 'Inactivo'; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions">
                                        <a href="countdown.php?action=edit&id=<?php echo $cd['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                                        <button onclick="if(confirmDelete()) location.href='countdown.php?action=delete&id=<?php echo $cd['id']; ?>'" class="btn-delete"><i class="fas fa-trash"></i></button>
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
