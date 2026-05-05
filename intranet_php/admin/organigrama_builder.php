<?php
/**
 * Gestión de Organigrama Dinámico
 * CRUD de nodos con jerarquía padre-hijo
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin();

$flash = getFlashMessage();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Obtener todos los nodos para el select de padre
$todosNodos = $pdo->query("SELECT id, nombre, puesto, parent_id FROM organigrama_nodos WHERE activo = 1 ORDER BY orden ASC, nombre ASC")->fetchAll();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitize($_POST['nombre']);
    $puesto = sanitize($_POST['puesto']);
    $departamento = sanitize($_POST['departamento'] ?? '');
    $parent_id = $_POST['parent_id'] ?: null;
    $color = $_POST['color'] ?? '#1976D2';
    $orden = (int)($_POST['orden'] ?? 0);
    $activo = isset($_POST['activo']) ? 1 : 0;

    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $result = uploadFile($_FILES['foto'], 'company', ['jpg','jpeg','png','gif','webp']);
        if ($result['success']) { $foto = $result['filename']; }
    }

    if ($_POST['form_action'] === 'add') {
        $stmt = $pdo->prepare("INSERT INTO organigrama_nodos (nombre, puesto, departamento, foto, parent_id, color, orden, activo) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$nombre, $puesto, $departamento, $foto, $parent_id, $color, $orden, $activo]);
        setFlashMessage('Posición agregada al organigrama', 'success');
    } elseif ($_POST['form_action'] === 'edit') {
        $sql = "UPDATE organigrama_nodos SET nombre=?, puesto=?, departamento=?, parent_id=?, color=?, orden=?, activo=?";
        $params = [$nombre, $puesto, $departamento, $parent_id, $color, $orden, $activo];
        if ($foto) { $sql .= ", foto=?"; $params[] = $foto; }
        $sql .= " WHERE id=?"; $params[] = $_POST['id'];
        $pdo->prepare($sql)->execute($params);
        setFlashMessage('Posición actualizada', 'success');
    }
    header('Location: organigrama_builder.php'); exit;
}

// Eliminar
if ($action === 'delete' && $id) {
    // Reasignar hijos al padre del nodo eliminado
    $nodo = $pdo->prepare("SELECT parent_id FROM organigrama_nodos WHERE id=?"); $nodo->execute([$id]); $n = $nodo->fetch();
    $pdo->prepare("UPDATE organigrama_nodos SET parent_id=? WHERE parent_id=?")->execute([$n['parent_id'], $id]);
    $pdo->prepare("DELETE FROM organigrama_nodos WHERE id=?")->execute([$id]);
    setFlashMessage('Posición eliminada', 'success');
    header('Location: organigrama_builder.php'); exit;
}

$editData = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM organigrama_nodos WHERE id=?"); $stmt->execute([$id]); $editData = $stmt->fetch();
}

// Construir árbol para visualización
function buildTree($nodes, $parentId = null) {
    $tree = [];
    foreach ($nodes as $node) {
        if ($node['parent_id'] == $parentId) {
            $node['children'] = buildTree($nodes, $node['id']);
            $tree[] = $node;
        }
    }
    usort($tree, function($a, $b) { return $a['orden'] - $b['orden']; });
    return $tree;
}

$allNodes = $pdo->query("SELECT * FROM organigrama_nodos WHERE activo = 1 ORDER BY orden ASC")->fetchAll();
$tree = buildTree($allNodes);
$totalNodos = count($allNodes);

// Función para renderizar el árbol HTML
function renderTree($nodes, $level = 0) {
    if (empty($nodes)) return;
    echo '<ul class="org-tree-list">';
    foreach ($nodes as $node) {
        echo '<li class="org-tree-item">';
        echo '<div class="org-node" style="border-left: 4px solid ' . $node['color'] . ';">';
        if ($node['foto']) {
            echo '<img src="../assets/uploads/company/' . $node['foto'] . '" class="org-node-avatar">';
        } else {
            $ini = strtoupper(substr($node['nombre'], 0, 1) . substr(explode(' ', $node['nombre'])[1] ?? '', 0, 1));
            echo '<div class="org-node-avatar-ph" style="background:' . $node['color'] . ';">' . $ini . '</div>';
        }
        echo '<div class="org-node-info">';
        echo '<strong>' . htmlspecialchars($node['nombre']) . '</strong>';
        echo '<span class="org-node-puesto">' . htmlspecialchars($node['puesto']) . '</span>';
        if ($node['departamento']) echo '<span class="org-node-dept">' . htmlspecialchars($node['departamento']) . '</span>';
        echo '</div>';
        echo '<div class="org-node-actions">';
        echo '<a href="organigrama_builder.php?action=edit&id=' . $node['id'] . '" title="Editar"><i class="fas fa-edit"></i></a>';
        echo '<a href="organigrama_builder.php?action=delete&id=' . $node['id'] . '" onclick="return confirmDelete()" title="Eliminar"><i class="fas fa-trash"></i></a>';
        echo '</div>';
        echo '</div>';
        if (!empty($node['children'])) {
            renderTree($node['children'], $level + 1);
        }
        echo '</li>';
    }
    echo '</ul>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Constructor de Organigrama - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /* Árbol del organigrama */
        .org-tree-list { list-style: none; padding-left: 30px; margin: 0; }
        .org-tree-list:first-child { padding-left: 0; }
        .org-tree-item { position: relative; margin: 8px 0; }
        .org-tree-item::before { content: ''; position: absolute; left: -20px; top: 22px; width: 18px; height: 1px; background: #555; }
        .org-tree-list:first-child > .org-tree-item::before { display: none; }
        .org-tree-item::after { content: ''; position: absolute; left: -20px; top: 0; bottom: 0; width: 1px; background: #444; }
        .org-tree-item:last-child::after { height: 22px; }
        .org-tree-list:first-child > .org-tree-item::after { display: none; }
        
        .org-node {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 10px 15px;
            transition: all 0.3s;
        }
        .org-node:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); transform: translateX(3px); }
        
        .org-node-avatar { width: 42px; height: 42px; border-radius: 10px; object-fit: cover; }
        .org-node-avatar-ph { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.85rem; font-weight: 700; flex-shrink: 0; }
        
        .org-node-info { display: flex; flex-direction: column; }
        .org-node-info strong { font-size: 0.9rem; color: #333; }
        .org-node-puesto { font-size: 0.75rem; color: #666; }
        .org-node-dept { font-size: 0.68rem; color: #999; background: #eee; padding: 1px 8px; border-radius: 10px; display: inline-block; margin-top: 2px; }
        
        .org-node-actions { display: flex; gap: 6px; margin-left: 10px; }
        .org-node-actions a { color: #999; font-size: 0.8rem; transition: color 0.3s; }
        .org-node-actions a:hover { color: #1976d2; }
        .org-node-actions a:last-child:hover { color: #e53935; }

        /* Organigrama visual (chart) */
        .org-chart { overflow-x: auto; padding: 20px 0; }
        .org-chart ul { display: flex; justify-content: center; padding-top: 20px; position: relative; }
        .org-chart ul::before { content: ''; position: absolute; top: 0; left: 50%; border-left: 2px solid #555; height: 20px; }
        .org-chart ul:first-child::before { display: none; }
        .org-chart li { display: flex; flex-direction: column; align-items: center; position: relative; padding: 20px 8px 0; }
        .org-chart li::before, .org-chart li::after { content: ''; position: absolute; top: 0; width: 50%; height: 20px; border-top: 2px solid #555; }
        .org-chart li::before { right: 50%; border-right: 2px solid #555; }
        .org-chart li::after { left: 50%; border-left: 2px solid #555; }
        .org-chart li:first-child::before { border: none; }
        .org-chart li:last-child::after { border: none; }
        .org-chart li:only-child::before, .org-chart li:only-child::after { border: none; }
        
        .org-card-visual {
            background: #fff;
            border-radius: 12px;
            padding: 18px 15px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            min-width: 140px;
            max-width: 180px;
            transition: all 0.3s;
            border-top: 4px solid;
        }
        .org-card-visual:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
        .org-card-visual img { width: 55px; height: 55px; border-radius: 50%; object-fit: cover; margin-bottom: 8px; border: 3px solid #eee; }
        .org-card-visual .v-initials { width: 55px; height: 55px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.1rem; margin: 0 auto 8px; }
        .org-card-visual .v-name { font-size: 0.82rem; font-weight: 700; color: #333; }
        .org-card-visual .v-puesto { font-size: 0.7rem; color: #888; margin-top: 2px; }
        .org-card-visual .v-dept { font-size: 0.6rem; color: #aaa; margin-top: 4px; }

        .tab-btns { display: flex; gap: 0; margin-bottom: 20px; background: #eee; border-radius: 10px; overflow: hidden; display: inline-flex; }
        .tab-btn { padding: 10px 25px; border: none; cursor: pointer; font-weight: 600; font-size: 0.85rem; background: transparent; color: #666; transition: all 0.3s; }
        .tab-btn.active { background: #1976d2; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
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
                    <a href="aplicaciones.php"><i class="fas fa-th"></i> <span>Aplicaciones</span></a>
                    <a href="kpis.php"><i class="fas fa-chart-line"></i> <span>KPIs</span></a>
                    <a href="organigrama_drag.php"><i class="fas fa-project-diagram"></i> <span>Organigramas (Drag&Drop)</span></a>
                    <a href="organigrama_builder.php" class="active"><i class="fas fa-sitemap"></i> <span>Organigrama Jerárquico</span></a>
                    <a href="organigrama.php"><i class="fas fa-image"></i> <span>Organigrama Imagen</span></a>
                    <a href="archivos.php"><i class="fas fa-folder"></i> <span>Archivos</span></a>
                    <a href="portales.php"><i class="fas fa-external-link-alt"></i> <span>Portales</span></a>
                    <a href="compania.php"><i class="fas fa-building"></i> <span>Compañía</span></a>
                    <a href="avisos.php"><i class="fas fa-bullhorn"></i> <span>Avisos</span></a>
                    <a href="usuarios.php"><i class="fas fa-users-cog"></i> <span>Usuarios</span></a>
                </div>
            </nav>
        </aside>
        <main class="main-content">
            <div class="top-bar"><h1><i class="fas fa-project-diagram"></i> Constructor de Organigrama</h1><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Salir</a></div>
            <?php if ($flash): ?><div class="alert alert-<?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></div><?php endif; ?>

            <?php if ($action === 'add' || $action === 'edit'): ?>
            <!-- FORMULARIO -->
            <div class="content-card">
                <div class="card-header"><h2><i class="fas fa-<?php echo $action === 'add' ? 'plus' : 'edit'; ?>"></i> <?php echo $action === 'add' ? 'Agregar Posición' : 'Editar Posición'; ?></h2><a href="organigrama_builder.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Volver</a></div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="form_action" value="<?php echo $action; ?>">
                        <?php if ($editData): ?><input type="hidden" name="id" value="<?php echo $editData['id']; ?>"><?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group"><label>Nombre completo *</label><input type="text" name="nombre" class="form-control" required value="<?php echo htmlspecialchars($editData['nombre'] ?? ''); ?>" placeholder="Ej: Juan Pérez"></div>
                            <div class="form-group"><label>Puesto *</label><input type="text" name="puesto" class="form-control" required value="<?php echo htmlspecialchars($editData['puesto'] ?? ''); ?>" placeholder="Ej: Director General"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label>Departamento</label><input type="text" name="departamento" class="form-control" value="<?php echo htmlspecialchars($editData['departamento'] ?? ''); ?>" placeholder="Ej: Dirección, IT, RH..."></div>
                            <div class="form-group"><label>Reporta a (superior)</label>
                                <select name="parent_id" class="form-control">
                                    <option value="">-- Ninguno (nivel más alto) --</option>
                                    <?php foreach ($todosNodos as $n): if ($editData && $n['id'] == $editData['id']) continue; ?>
                                    <option value="<?php echo $n['id']; ?>" <?php echo ($editData['parent_id'] ?? '') == $n['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($n['nombre'] . ' - ' . $n['puesto']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label>Foto</label><input type="file" name="foto" accept="image/*">
                                <?php if ($editData && $editData['foto']): ?><img src="../assets/uploads/company/<?php echo $editData['foto']; ?>" style="width:60px;height:60px;border-radius:10px;object-fit:cover;margin-top:8px;"><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label>Color</label><input type="color" name="color" value="<?php echo $editData['color'] ?? '#1976D2'; ?>" style="width:60px;height:40px;">
                            </div>
                            <div class="form-group"><label>Orden</label><input type="number" name="orden" class="form-control" min="0" value="<?php echo $editData['orden'] ?? 0; ?>"></div>
                        </div>
                        <div class="form-group"><label><input type="checkbox" name="activo" <?php echo ($editData['activo'] ?? 1) ? 'checked' : ''; ?>> Activo</label></div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                    </form>
                </div>
            </div>
            <?php else: ?>

            <!-- VISTA -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <div class="tab-btns">
                    <button class="tab-btn active" onclick="showTab('chart')"><i class="fas fa-project-diagram"></i> Visual</button>
                    <button class="tab-btn" onclick="showTab('tree')"><i class="fas fa-list-ul"></i> Árbol</button>
                </div>
                <div style="display:flex;gap:10px;">
                    <span style="color:#666;font-size:0.85rem;padding:10px;"><?php echo $totalNodos; ?> posiciones</span>
                    <a href="organigrama_builder.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Agregar Posición</a>
                </div>
            </div>

            <!-- Vista Chart (visual) -->
            <div class="content-card tab-content active" id="tab-chart">
                <div class="card-header"><h2><i class="fas fa-project-diagram"></i> Organigrama Visual</h2></div>
                <div class="card-body" style="overflow-x:auto;">
                    <?php if ($totalNodos > 0): ?>
                    <div class="org-chart">
                        <?php
                        function renderChart($nodes) {
                            if (empty($nodes)) return;
                            echo '<ul>';
                            foreach ($nodes as $n) {
                                echo '<li>';
                                echo '<div class="org-card-visual" style="border-top-color:' . $n['color'] . ';">';
                                if ($n['foto']) {
                                    echo '<img src="../assets/uploads/company/' . $n['foto'] . '">';
                                } else {
                                    $ini = strtoupper(substr($n['nombre'],0,1) . substr(explode(' ',$n['nombre'])[1] ?? '',0,1));
                                    echo '<div class="v-initials" style="background:' . $n['color'] . ';">' . $ini . '</div>';
                                }
                                echo '<div class="v-name">' . htmlspecialchars($n['nombre']) . '</div>';
                                echo '<div class="v-puesto">' . htmlspecialchars($n['puesto']) . '</div>';
                                if ($n['departamento']) echo '<div class="v-dept">' . htmlspecialchars($n['departamento']) . '</div>';
                                echo '</div>';
                                if (!empty($n['children'])) renderChart($n['children']);
                                echo '</li>';
                            }
                            echo '</ul>';
                        }
                        renderChart($tree);
                        ?>
                    </div>
                    <?php else: ?>
                    <div style="text-align:center;padding:60px;color:#999;"><i class="fas fa-project-diagram" style="font-size:3rem;opacity:0.3;display:block;margin-bottom:15px;"></i><p>No hay posiciones. Haz clic en "Agregar Posición" para comenzar.</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Vista Árbol -->
            <div class="content-card tab-content" id="tab-tree">
                <div class="card-header"><h2><i class="fas fa-list-ul"></i> Vista de Árbol</h2></div>
                <div class="card-body">
                    <?php if ($totalNodos > 0): renderTree($tree); else: ?>
                    <p style="text-align:center;padding:40px;color:#999;">No hay posiciones registradas.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
    <script>
    function showTab(tab) {
        document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
        event.target.closest('.tab-btn').classList.add('active');
    }
    </script>
</body>
</html>
