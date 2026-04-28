<?php
/**
 * Gestión de Aplicaciones Rápidas
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$flash = getFlashMessage();

// Lista de iconos FontAwesome disponibles
$iconos_disponibles = [
    'fa-sitemap'=>'Organigrama','fa-desktop'=>'Monitor','fa-database'=>'Base datos','fa-chart-bar'=>'Gráfica',
    'fa-users'=>'Usuarios','fa-file-alt'=>'Documento','fa-cogs'=>'Configuración','fa-truck'=>'Camión',
    'fa-clipboard'=>'Portapapeles','fa-calculator'=>'Calculadora','fa-calendar'=>'Calendario','fa-envelope'=>'Correo',
    'fa-print'=>'Impresora','fa-shield-alt'=>'Seguridad','fa-tools'=>'Herramientas','fa-warehouse'=>'Almacén',
    'fa-industry'=>'Industria','fa-box'=>'Caja','fa-shopping-cart'=>'Compras','fa-money-bill'=>'Dinero',
    'fa-user-tie'=>'Ejecutivo','fa-headset'=>'Soporte','fa-server'=>'Servidor','fa-wifi'=>'Red',
    'fa-folder-open'=>'Carpeta','fa-key'=>'Llave','fa-globe'=>'Web','fa-phone'=>'Teléfono',
    'fa-video'=>'Video','fa-camera'=>'Cámara','fa-wrench'=>'Llave inglesa','fa-bolt'=>'Rayo'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitize($_POST['nombre']);
    $icono = $_POST['icono'];
    $url = sanitize($_POST['url']);
    $color = $_POST['color'];
    $orden = (int)$_POST['orden'];
    $activo = isset($_POST['activo']) ? 1 : 0;

    if ($_POST['form_action'] === 'edit') {
        $pdo->prepare("UPDATE aplicaciones SET nombre=?, icono=?, url=?, color=?, orden=?, activo=? WHERE id=?")->execute([$nombre, $icono, $url, $color, $orden, $activo, $_POST['id']]);
        setFlashMessage('Aplicación actualizada', 'success');
    } elseif ($_POST['form_action'] === 'add') {
        $pdo->prepare("INSERT INTO aplicaciones (nombre, icono, url, color, orden, activo) VALUES (?,?,?,?,?,?)")->execute([$nombre, $icono, $url, $color, $orden, $activo]);
        setFlashMessage('Aplicación agregada', 'success');
    }
    header('Location: aplicaciones.php'); exit;
}

if ($action === 'delete' && $id) {
    $pdo->prepare("DELETE FROM aplicaciones WHERE id=?")->execute([$id]);
    setFlashMessage('Aplicación eliminada', 'success');
    header('Location: aplicaciones.php'); exit;
}

$editData = null;
if ($action === 'edit' && $id) { $s=$pdo->prepare("SELECT * FROM aplicaciones WHERE id=?"); $s->execute([$id]); $editData=$s->fetch(); }

$apps = $pdo->query("SELECT * FROM aplicaciones ORDER BY orden ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplicaciones Rápidas - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>.icon-grid{display:grid;grid-template-columns:repeat(8,1fr);gap:6px;margin-top:8px;}.icon-opt{padding:10px;text-align:center;border-radius:8px;cursor:pointer;border:2px solid transparent;transition:all 0.3s;background:#f5f5f5;}.icon-opt:hover{background:#e0e0e0;}.icon-opt.selected{border-color:#1976d2;background:#e3f2fd;}.icon-opt i{font-size:1.3rem;display:block;margin-bottom:4px;}.icon-opt span{font-size:0.6rem;color:#666;}</style>
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
                    <a href="galeria.php"><i class="fas fa-photo-video"></i> <span>Galería</span></a>
                    <a href="videos.php"><i class="fas fa-video"></i> <span>Videos</span></a>
                    <a href="articulos.php"><i class="fas fa-newspaper"></i> <span>Artículos</span></a>
                </div>
                <div class="menu-section">
                    <a href="aplicaciones.php" class="active"><i class="fas fa-th"></i> <span>Aplicaciones</span></a>
                    <a href="kpis.php"><i class="fas fa-chart-line"></i> <span>KPIs</span></a>
                    <a href="organigrama.php"><i class="fas fa-sitemap"></i> <span>Organigrama</span></a>
                    <a href="archivos.php"><i class="fas fa-folder"></i> <span>Archivos</span></a>
                    <a href="portales.php"><i class="fas fa-external-link-alt"></i> <span>Portales</span></a>
                    <a href="compania.php"><i class="fas fa-building"></i> <span>Compañía</span></a>
                    <a href="avisos.php"><i class="fas fa-bullhorn"></i> <span>Avisos</span></a>
                    <a href="usuarios.php"><i class="fas fa-users-cog"></i> <span>Usuarios</span></a>
                </div>
            </nav>
        </aside>
        <main class="main-content">
            <div class="top-bar"><h1><i class="fas fa-th"></i> Aplicaciones Rápidas</h1><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Salir</a></div>
            <?php if ($flash): ?><div class="alert alert-<?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></div><?php endif; ?>

            <?php if ($action === 'add' || $action === 'edit'): ?>
            <div class="content-card">
                <div class="card-header"><h2><?php echo $action==='add'?'Agregar':'Editar'; ?> Aplicación</h2><a href="aplicaciones.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Volver</a></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="form_action" value="<?php echo $action; ?>">
                        <?php if ($editData): ?><input type="hidden" name="id" value="<?php echo $editData['id']; ?>"><?php endif; ?>
                        <div class="form-row">
                            <div class="form-group"><label>Nombre *</label><input type="text" name="nombre" class="form-control" required value="<?php echo htmlspecialchars($editData['nombre'] ?? ''); ?>"></div>
                            <div class="form-group"><label>URL / Enlace *</label><input type="text" name="url" class="form-control" required value="<?php echo htmlspecialchars($editData['url'] ?? ''); ?>" placeholder="http://..."></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label>Color de fondo *</label><input type="color" name="color" value="<?php echo $editData['color'] ?? '#F44336'; ?>" style="width:80px;height:45px;"></div>
                            <div class="form-group"><label>Orden</label><input type="number" name="orden" class="form-control" min="0" value="<?php echo $editData['orden'] ?? 0; ?>"></div>
                        </div>
                        <div class="form-group">
                            <label>Icono *</label>
                            <input type="hidden" name="icono" id="selectedIcon" value="<?php echo $editData['icono'] ?? 'fa-desktop'; ?>">
                            <div class="icon-grid">
                                <?php foreach ($iconos_disponibles as $clase => $label): ?>
                                <div class="icon-opt <?php echo ($editData['icono'] ?? 'fa-desktop') === $clase ? 'selected' : ''; ?>" onclick="selectIcon('<?php echo $clase; ?>', this)">
                                    <i class="fas <?php echo $clase; ?>"></i>
                                    <span><?php echo $label; ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="form-group"><label><input type="checkbox" name="activo" <?php echo ($editData['activo'] ?? 1) ? 'checked' : ''; ?>> Activo</label></div>
                        <div style="margin-top:15px;padding:20px;background:#f5f5f5;border-radius:10px;text-align:center;">
                            <p style="font-size:0.8rem;color:#666;margin-bottom:8px;">Vista previa:</p>
                            <div id="previewApp" style="display:inline-flex;flex-direction:column;align-items:center;padding:20px 25px;border-radius:12px;color:white;background:<?php echo $editData['color'] ?? '#F44336'; ?>;">
                                <i id="previewIcon" class="fas <?php echo $editData['icono'] ?? 'fa-desktop'; ?>" style="font-size:2rem;margin-bottom:8px;"></i>
                                <span id="previewName" style="font-size:0.8rem;font-weight:600;"><?php echo htmlspecialchars($editData['nombre'] ?? 'Nombre'); ?></span>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-top:15px;"><i class="fas fa-save"></i> Guardar</button>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="content-card">
                <div class="card-header"><h2>Aplicaciones (<?php echo count($apps); ?>)</h2><a href="aplicaciones.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Agregar</a></div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:20px;">
                        <?php foreach ($apps as $a): ?>
                        <div style="background:<?php echo $a['color']; ?>;border-radius:12px;padding:18px 10px;text-align:center;color:white;position:relative;">
                            <i class="fas <?php echo $a['icono']; ?>" style="font-size:1.8rem;display:block;margin-bottom:8px;"></i>
                            <span style="font-size:0.75rem;font-weight:600;"><?php echo htmlspecialchars($a['nombre']); ?></span>
                            <div style="margin-top:10px;display:flex;justify-content:center;gap:5px;">
                                <a href="aplicaciones.php?action=edit&id=<?php echo $a['id']; ?>" style="background:rgba(255,255,255,0.3);padding:4px 8px;border-radius:4px;color:white;text-decoration:none;font-size:0.7rem;"><i class="fas fa-edit"></i></a>
                                <button onclick="if(confirmDelete()) location.href='aplicaciones.php?action=delete&id=<?php echo $a['id']; ?>'" style="background:rgba(255,255,255,0.3);padding:4px 8px;border-radius:4px;color:white;border:none;cursor:pointer;font-size:0.7rem;"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
    <script>
    function selectIcon(cls, el) {
        document.querySelectorAll('.icon-opt').forEach(e => e.classList.remove('selected'));
        el.classList.add('selected');
        document.getElementById('selectedIcon').value = cls;
        document.getElementById('previewIcon').className = 'fas ' + cls;
    }
    // Live preview
    document.querySelector('input[name="nombre"]')?.addEventListener('input', function(){ document.getElementById('previewName').textContent = this.value || 'Nombre'; });
    document.querySelector('input[name="color"]')?.addEventListener('input', function(){ document.getElementById('previewApp').style.background = this.value; });
    </script>
</body>
</html>
