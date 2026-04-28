<?php
/**
 * Gestión de KPIs por Departamento - con imagen, mes y año
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$flash = getFlashMessage();
$departamentos = getDepartamentos($pdo);
$meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitize($_POST['nombre']);
    $descripcion = sanitize($_POST['descripcion']);
    $departamento_id = $_POST['departamento_id'] ?: null;
    $mes = (int)$_POST['mes'] ?: null;
    $anio = (int)$_POST['anio'] ?: null;
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    $archivo = null;
    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $result = uploadFile($_FILES['archivo'], 'kpis', ['pdf','xls','xlsx','doc','docx','ppt','pptx']);
        if ($result['success']) { $archivo = $result['filename']; }
        else { setFlashMessage($result['message'], 'danger'); header('Location: kpis.php'); exit; }
    }
    
    $imagen = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
        $result = uploadFile($_FILES['imagen'], 'kpis', ['jpg','jpeg','png','gif','webp']);
        if ($result['success']) { $imagen = $result['filename']; }
    }
    
    if ($_POST['form_action'] === 'add') {
        if (!$archivo && !$imagen) { setFlashMessage('Debe subir al menos un archivo o imagen', 'danger'); }
        else {
            $stmt = $pdo->prepare("INSERT INTO kpis_departamento (nombre, descripcion, archivo, mes, anio, imagen, departamento_id, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $descripcion, $archivo ?? '', $mes, $anio, $imagen, $departamento_id, $activo]);
            setFlashMessage('KPI agregado correctamente', 'success');
        }
    } elseif ($_POST['form_action'] === 'edit') {
        $sql = "UPDATE kpis_departamento SET nombre = ?, descripcion = ?, departamento_id = ?, mes = ?, anio = ?, activo = ?";
        $params = [$nombre, $descripcion, $departamento_id, $mes, $anio, $activo];
        if ($archivo) { $sql .= ", archivo = ?"; $params[] = $archivo; }
        if ($imagen) { $sql .= ", imagen = ?"; $params[] = $imagen; }
        $sql .= " WHERE id = ?"; $params[] = $_POST['id'];
        $pdo->prepare($sql)->execute($params);
        setFlashMessage('KPI actualizado', 'success');
    }
    header('Location: kpis.php'); exit;
}

if ($action === 'delete' && $id) {
    $pdo->prepare("DELETE FROM kpis_departamento WHERE id = ?")->execute([$id]);
    setFlashMessage('KPI eliminado', 'success');
    header('Location: kpis.php'); exit;
}

$editData = null;
if ($action === 'edit' && $id) { $stmt = $pdo->prepare("SELECT * FROM kpis_departamento WHERE id = ?"); $stmt->execute([$id]); $editData = $stmt->fetch(); }

$kpis = $pdo->query("SELECT k.*, d.nombre as dept_nombre FROM kpis_departamento k LEFT JOIN departamentos d ON k.departamento_id = d.id ORDER BY k.anio DESC, k.mes DESC, d.nombre")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPIs - Admin</title>
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
                    <a href="kpis.php" class="active"><i class="fas fa-chart-line"></i> <span>KPIs</span></a>
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
            <div class="top-bar"><h1><i class="fas fa-chart-line"></i> Indicadores KPIs</h1><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Salir</a></div>
            <?php if ($flash): ?><div class="alert alert-<?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></div><?php endif; ?>

            <?php if ($action === 'add' || $action === 'edit'): ?>
            <div class="content-card">
                <div class="card-header"><h2><?php echo $action === 'add' ? 'Agregar KPI' : 'Editar KPI'; ?></h2><a href="kpis.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Volver</a></div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="form_action" value="<?php echo $action; ?>">
                        <?php if ($editData): ?><input type="hidden" name="id" value="<?php echo $editData['id']; ?>"><?php endif; ?>
                        <div class="form-row">
                            <div class="form-group"><label>Nombre *</label><input type="text" name="nombre" class="form-control" required value="<?php echo htmlspecialchars($editData['nombre'] ?? ''); ?>"></div>
                            <div class="form-group"><label>Departamento *</label>
                                <select name="departamento_id" class="form-control" required><option value="">Seleccionar...</option>
                                <?php foreach ($departamentos as $d): ?><option value="<?php echo $d['id']; ?>" <?php echo ($editData['departamento_id'] ?? '') == $d['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['nombre']); ?></option><?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label>Mes *</label>
                                <select name="mes" class="form-control" required><option value="">Seleccionar...</option>
                                <?php foreach ($meses as $num => $nom): ?><option value="<?php echo $num; ?>" <?php echo ($editData['mes'] ?? '') == $num ? 'selected' : ''; ?>><?php echo $nom; ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group"><label>Año *</label>
                                <select name="anio" class="form-control" required>
                                <?php for ($y = date('Y') + 1; $y >= date('Y') - 5; $y--): ?><option value="<?php echo $y; ?>" <?php echo ($editData['anio'] ?? date('Y')) == $y ? 'selected' : ''; ?>><?php echo $y; ?></option><?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group"><label>Descripción</label><textarea name="descripcion" class="form-control" rows="2"><?php echo htmlspecialchars($editData['descripcion'] ?? ''); ?></textarea></div>
                        <div class="form-row">
                            <div class="form-group"><label>Imagen del KPI (captura/gráfica) *</label><input type="file" name="imagen" accept="image/*" <?php echo $action === 'add' ? '' : ''; ?>>
                                <?php if ($editData && $editData['imagen']): ?><img src="../assets/uploads/kpis/<?php echo $editData['imagen']; ?>" style="max-width:200px;margin-top:8px;border-radius:8px;"><?php endif; ?>
                            </div>
                            <div class="form-group"><label>Archivo adjunto (PDF, Excel, PPT)</label><input type="file" name="archivo" accept=".pdf,.xls,.xlsx,.doc,.docx,.ppt,.pptx">
                                <?php if ($editData && $editData['archivo']): ?><p style="margin-top:8px;color:#666;"><i class="fas fa-paperclip"></i> <?php echo $editData['archivo']; ?></p><?php endif; ?>
                            </div>
                        </div>
                        <div class="form-group"><label><input type="checkbox" name="activo" <?php echo ($editData['activo'] ?? 1) ? 'checked' : ''; ?>> Activo</label></div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="content-card">
                <div class="card-header"><h2>Lista de KPIs</h2><a href="kpis.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Agregar</a></div>
                <div class="card-body"><div class="table-responsive"><table class="data-table">
                    <thead><tr><th>Imagen</th><th>Nombre</th><th>Departamento</th><th>Periodo</th><th>Archivo</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($kpis as $k): ?>
                    <tr>
                        <td><?php if ($k['imagen']): ?><img src="../assets/uploads/kpis/<?php echo $k['imagen']; ?>" style="width:60px;height:40px;object-fit:cover;border-radius:4px;cursor:pointer;" onclick="window.open(this.src)"><?php else: ?>-<?php endif; ?></td>
                        <td><?php echo htmlspecialchars($k['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($k['dept_nombre'] ?? '-'); ?></td>
                        <td><?php echo $k['mes'] ? $meses[$k['mes']] . ' ' . $k['anio'] : '-'; ?></td>
                        <td><?php echo $k['archivo'] ? '<i class="fas fa-file" style="color:#1976d2;"></i>' : '-'; ?></td>
                        <td><span class="badge badge-<?php echo $k['activo']?'active':'inactive'; ?>"><?php echo $k['activo']?'Activo':'Inactivo'; ?></span></td>
                        <td class="actions">
                            <a href="kpis.php?action=edit&id=<?php echo $k['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                            <button onclick="if(confirmDelete()) location.href='kpis.php?action=delete&id=<?php echo $k['id']; ?>'" class="btn-delete"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div></div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
