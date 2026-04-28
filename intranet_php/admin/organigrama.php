<?php
/**
 * Gestión de Organigrama Corporativo
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin();

$flash = getFlashMessage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = sanitize($_POST['titulo']);
    
    $imagen = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
        $result = uploadFile($_FILES['imagen'], 'company', ['jpg','jpeg','png','gif','webp']);
        if ($result['success']) { $imagen = $result['filename']; }
        else { setFlashMessage($result['message'], 'danger'); header('Location: organigrama.php'); exit; }
    }
    
    if ($_POST['form_action'] === 'add' && $imagen) {
        $pdo->prepare("INSERT INTO organigrama (titulo, imagen) VALUES (?, ?)")->execute([$titulo, $imagen]);
        setFlashMessage('Organigrama agregado', 'success');
    } elseif ($_POST['form_action'] === 'edit') {
        $sql = "UPDATE organigrama SET titulo = ?"; $params = [$titulo];
        if ($imagen) { $sql .= ", imagen = ?"; $params[] = $imagen; }
        $sql .= " WHERE id = ?"; $params[] = $_POST['id'];
        $pdo->prepare($sql)->execute($params);
        setFlashMessage('Organigrama actualizado', 'success');
    }
    header('Location: organigrama.php'); exit;
}

$action = $_GET['action'] ?? '';
if ($action === 'delete' && isset($_GET['id'])) {
    $pdo->prepare("DELETE FROM organigrama WHERE id = ?")->execute([$_GET['id']]);
    setFlashMessage('Organigrama eliminado', 'success');
    header('Location: organigrama.php'); exit;
}

if ($action === 'activate' && isset($_GET['id'])) {
    $pdo->exec("UPDATE organigrama SET activo = 0");
    $pdo->prepare("UPDATE organigrama SET activo = 1 WHERE id = ?")->execute([$_GET['id']]);
    setFlashMessage('Organigrama activado', 'success');
    header('Location: organigrama.php'); exit;
}

$organigramas = $pdo->query("SELECT * FROM organigrama ORDER BY activo DESC, id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organigrama - Admin</title>
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
                    <a href="galeria.php"><i class="fas fa-photo-video"></i> <span>Galería</span></a>
                    <a href="videos.php"><i class="fas fa-video"></i> <span>Videos</span></a>
                    <a href="articulos.php"><i class="fas fa-newspaper"></i> <span>Artículos</span></a>
                </div>
                <div class="menu-section">
                    <a href="kpis.php"><i class="fas fa-chart-line"></i> <span>KPIs</span></a>
                    <a href="organigrama.php" class="active"><i class="fas fa-sitemap"></i> <span>Organigrama</span></a>
                    <a href="archivos.php"><i class="fas fa-folder"></i> <span>Archivos</span></a>
                    <a href="portales.php"><i class="fas fa-external-link-alt"></i> <span>Portales</span></a>
                    <a href="compania.php"><i class="fas fa-building"></i> <span>Compañía</span></a>
                    <a href="avisos.php"><i class="fas fa-bullhorn"></i> <span>Avisos</span></a>
                    <a href="usuarios.php"><i class="fas fa-users-cog"></i> <span>Usuarios</span></a>
                </div>
            </nav>
        </aside>
        <main class="main-content">
            <div class="top-bar"><h1><i class="fas fa-sitemap"></i> Organigrama Corporativo</h1><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Salir</a></div>
            <?php if ($flash): ?><div class="alert alert-<?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></div><?php endif; ?>

            <!-- Formulario subir -->
            <div class="content-card" style="margin-bottom:20px;">
                <div class="card-header"><h2>Subir Organigrama</h2></div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="form_action" value="add">
                        <div class="form-row">
                            <div class="form-group"><label>Título</label><input type="text" name="titulo" class="form-control" value="Organigrama Corporativo" required></div>
                            <div class="form-group"><label>Imagen del organigrama *</label><input type="file" name="imagen" accept="image/*" required></div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Subir</button>
                    </form>
                </div>
            </div>

            <!-- Lista -->
            <div class="content-card">
                <div class="card-header"><h2>Organigramas</h2></div>
                <div class="card-body">
                    <?php if (count($organigramas) > 0): ?>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:15px;">
                        <?php foreach ($organigramas as $org): ?>
                        <div style="background:#f5f5f5;border-radius:10px;overflow:hidden;border:<?php echo $org['activo'] ? '3px solid #43A047' : '1px solid #ddd'; ?>;">
                            <img src="../assets/uploads/company/<?php echo $org['imagen']; ?>" style="width:100%;height:160px;object-fit:cover;cursor:pointer;" onclick="window.open(this.src)">
                            <div style="padding:12px;">
                                <strong><?php echo htmlspecialchars($org['titulo']); ?></strong>
                                <?php if ($org['activo']): ?><span style="display:inline-block;margin-left:8px;padding:2px 8px;background:#43A047;color:white;border-radius:10px;font-size:0.7rem;">Activo</span><?php endif; ?>
                                <div style="margin-top:10px;display:flex;gap:8px;">
                                    <?php if (!$org['activo']): ?><a href="organigrama.php?action=activate&id=<?php echo $org['id']; ?>" class="btn btn-success btn-sm" style="text-decoration:none;"><i class="fas fa-check"></i> Activar</a><?php endif; ?>
                                    <button onclick="if(confirmDelete()) location.href='organigrama.php?action=delete&id=<?php echo $org['id']; ?>'" class="btn-delete" style="padding:5px 10px;"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?><p style="color:#666;">No hay organigramas. Sube uno arriba.</p><?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
