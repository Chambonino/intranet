<?php
/**
 * Gestión de Eventos del Calendario
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$flash = getFlashMessage();

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = sanitize($_POST['titulo']);
    $descripcion = sanitize($_POST['descripcion']);
    $fecha_evento = $_POST['fecha_evento'];
    $hora_inicio = $_POST['hora_inicio'] ?: null;
    $hora_fin = $_POST['hora_fin'] ?: null;
    $lugar = sanitize($_POST['lugar']);
    $color = $_POST['color'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    if ($_POST['form_action'] === 'add') {
        $stmt = $pdo->prepare("INSERT INTO eventos (titulo, descripcion, fecha_evento, hora_inicio, hora_fin, lugar, color, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$titulo, $descripcion, $fecha_evento, $hora_inicio, $hora_fin, $lugar, $color, $activo]);
        setFlashMessage('Evento agregado correctamente', 'success');
    } elseif ($_POST['form_action'] === 'edit') {
        $stmt = $pdo->prepare("UPDATE eventos SET titulo = ?, descripcion = ?, fecha_evento = ?, hora_inicio = ?, hora_fin = ?, lugar = ?, color = ?, activo = ? WHERE id = ?");
        $stmt->execute([$titulo, $descripcion, $fecha_evento, $hora_inicio, $hora_fin, $lugar, $color, $activo, $_POST['id']]);
        setFlashMessage('Evento actualizado correctamente', 'success');
    }
    header('Location: eventos.php');
    exit;
}

// Eliminar
if ($action === 'delete' && $id) {
    $pdo->prepare("DELETE FROM eventos WHERE id = ?")->execute([$id]);
    setFlashMessage('Evento eliminado correctamente', 'success');
    header('Location: eventos.php');
    exit;
}

$editData = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM eventos WHERE id = ?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch();
}

$eventos = $pdo->query("SELECT * FROM eventos ORDER BY fecha_evento DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventos - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/img/logo.png" alt="Logo" onerror="this.style.display='none'">
                <h2>Intranet Admin</h2>
            </div>
            <nav class="sidebar-menu">
                <div class="menu-section">
                    <span class="menu-section-title">Principal</span>
                    <a href="index.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
                </div>
                <div class="menu-section">
                    <span class="menu-section-title">Contenido</span>
                    <a href="slider.php"><i class="fas fa-images"></i> <span>Slider Noticias</span></a>
                    <a href="eventos.php" class="active"><i class="fas fa-calendar-alt"></i> <span>Eventos</span></a>
                    <a href="cumpleanos.php"><i class="fas fa-birthday-cake"></i> <span>Cumpleaños</span></a>
                    <a href="galeria.php"><i class="fas fa-photo-video"></i> <span>Galería Fotos</span></a>
                    <a href="videos.php"><i class="fas fa-video"></i> <span>Videos</span></a>
                    <a href="articulos.php"><i class="fas fa-newspaper"></i> <span>Artículos</span></a>
                </div>
                <div class="menu-section">
                    <span class="menu-section-title">Configuración</span>
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
                <h1><i class="fas fa-calendar-alt"></i> Eventos del Calendario</h1>
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
                    <h2><i class="fas fa-<?php echo $action === 'add' ? 'plus' : 'edit'; ?>"></i> 
                        <?php echo $action === 'add' ? 'Agregar Evento' : 'Editar Evento'; ?>
                    </h2>
                    <a href="eventos.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Volver</a>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="form_action" value="<?php echo $action; ?>">
                        <?php if ($editData): ?>
                        <input type="hidden" name="id" value="<?php echo $editData['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Título *</label>
                                <input type="text" name="titulo" class="form-control" required
                                       value="<?php echo htmlspecialchars($editData['titulo'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Fecha del Evento *</label>
                                <input type="date" name="fecha_evento" class="form-control" required
                                       value="<?php echo $editData['fecha_evento'] ?? ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="3"><?php echo htmlspecialchars($editData['descripcion'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Hora Inicio</label>
                                <input type="time" name="hora_inicio" class="form-control"
                                       value="<?php echo $editData['hora_inicio'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Hora Fin</label>
                                <input type="time" name="hora_fin" class="form-control"
                                       value="<?php echo $editData['hora_fin'] ?? ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Lugar</label>
                                <input type="text" name="lugar" class="form-control"
                                       value="<?php echo htmlspecialchars($editData['lugar'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Color</label>
                                <input type="color" name="color" value="<?php echo $editData['color'] ?? '#1976D2'; ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="activo" <?php echo ($editData['activo'] ?? 1) ? 'checked' : ''; ?>>
                                Activo
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> Lista de Eventos</h2>
                    <a href="eventos.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Agregar</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Color</th>
                                    <th>Título</th>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th>Lugar</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($eventos as $evento): ?>
                                <tr>
                                    <td><span style="display: inline-block; width: 20px; height: 20px; background: <?php echo $evento['color']; ?>; border-radius: 4px;"></span></td>
                                    <td><?php echo htmlspecialchars($evento['titulo']); ?></td>
                                    <td><?php echo formatearFecha($evento['fecha_evento']); ?></td>
                                    <td><?php echo $evento['hora_inicio'] ? date('H:i', strtotime($evento['hora_inicio'])) : '-'; ?></td>
                                    <td><?php echo htmlspecialchars($evento['lugar'] ?: '-'); ?></td>
                                    <td><span class="badge badge-<?php echo $evento['activo'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $evento['activo'] ? 'Activo' : 'Inactivo'; ?>
                                    </span></td>
                                    <td class="actions">
                                        <a href="eventos.php?action=edit&id=<?php echo $evento['id']; ?>" class="btn-edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="if(confirmDelete()) location.href='eventos.php?action=delete&id=<?php echo $evento['id']; ?>'" class="btn-delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
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
