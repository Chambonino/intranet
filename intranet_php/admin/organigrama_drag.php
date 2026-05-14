<?php
/**
 * Constructor de Organigramas - Drag & Drop Interactivo
 * Permite crear múltiples organigramas por departamento
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin();

$flash = getFlashMessage();
$departamentos = getDepartamentos($pdo);

// CRUD de organigramas
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$orgId = $_GET['org'] ?? null;

// Guardar nuevo organigrama
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_org'])) {
    $titulo = sanitize($_POST['titulo']);
    $departamento = sanitize($_POST['departamento'] ?? '');
    
    if ($_POST['save_org'] === 'new') {
        $stmt = $pdo->prepare("INSERT INTO organigramas_custom (titulo, departamento, datos_json, activo) VALUES (?, ?, '[]', 1)");
        $stmt->execute([$titulo, $departamento]);
        $newId = $pdo->lastInsertId();
        setFlashMessage('Organigrama creado. Ahora agrega posiciones.', 'success');
        header('Location: organigrama_drag.php?action=edit&org=' . $newId);
    } else {
        $pdo->prepare("UPDATE organigramas_custom SET titulo=?, departamento=? WHERE id=?")->execute([$titulo, $departamento, $_POST['org_id']]);
        setFlashMessage('Organigrama actualizado', 'success');
        header('Location: organigrama_drag.php');
    }
    exit;
}

// Guardar nodos via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_nodes'])) {
    header('Content-Type: application/json');
    $orgId = (int)$_POST['org_id'];
    $nodesJson = $_POST['nodes_json'];
    $pdo->prepare("UPDATE organigramas_custom SET datos_json=? WHERE id=?")->execute([$nodesJson, $orgId]);
    echo json_encode(['success' => true]);
    exit;
}

// Subir foto de nodo via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['node_photo'])) {
    header('Content-Type: application/json');
    $result = uploadFile($_FILES['node_photo'], 'company', ['jpg','jpeg','png','gif','webp']);
    if ($result['success']) {
        echo json_encode(['success' => true, 'filename' => $result['filename']]);
    } else {
        echo json_encode(['success' => false, 'error' => $result['message']]);
    }
    exit;
}

// Eliminar organigrama
if ($action === 'delete' && $id) {
    $pdo->prepare("DELETE FROM organigramas_custom WHERE id=?")->execute([$id]);
    setFlashMessage('Organigrama eliminado', 'success');
    header('Location: organigrama_drag.php'); exit;
}

// Obtener organigramas
$organigramas = $pdo->query("SELECT * FROM organigramas_custom ORDER BY departamento, titulo")->fetchAll();

// Editar organigrama específico
$currentOrg = null;
if ($action === 'edit' && $orgId) {
    $stmt = $pdo->prepare("SELECT * FROM organigramas_custom WHERE id=?");
    $stmt->execute([$orgId]);
    $currentOrg = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organigramas - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /* Canvas del organigrama */
        .org-canvas { position: relative; min-height: 600px; background: #ffffff; border-radius: 12px; overflow: auto; border: 2px dashed #ddd; }
        .org-canvas.drag-over { border-color: #1976d2; background: #f5f9ff; }
        
        /* === Nodo estilo Visio corporativo === */
        .org-node-card {
            position: absolute;
            width: 200px;
            cursor: move;
            user-select: none;
            transition: filter 0.25s, transform 0.25s;
            z-index: 10;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.15));
        }
        .org-node-card:hover { filter: drop-shadow(0 6px 14px rgba(25,118,210,0.35)); z-index: 20; transform: translateY(-2px); }
        .org-node-card.dragging { opacity: 0.85; z-index: 100; filter: drop-shadow(0 12px 24px rgba(0,0,0,0.4)); }

        /* Cabecera (gris con foto + puesto) */
        .org-node-card .node-header {
            display: flex;
            align-items: stretch;
            background: #d9d9d9;
            border: 1px solid #b0b0b0;
            border-bottom: none;
            min-height: 70px;
        }
        .org-node-card .node-photo-wrap {
            width: 60px;
            background: #e8e8e8;
            display: flex;
            align-items: center;
            justify-content: center;
            border-right: 1px solid #b0b0b0;
        }
        .org-node-card .node-photo { width: 44px; height: 44px; border-radius: 4px; object-fit: cover; }
        .org-node-card .node-initials { width: 44px; height: 44px; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.95rem; }
        .org-node-card .node-puesto-block {
            flex: 1;
            background: #2e75b6;
            color: white;
            padding: 8px 10px;
            font-size: 0.78rem;
            font-weight: 500;
            line-height: 1.25;
            display: flex;
            align-items: center;
            text-align: left;
        }

        /* Cuerpo (nombre) */
        .org-node-card .node-name-block {
            background: white;
            border: 1px solid #b0b0b0;
            border-top: none;
            padding: 8px 12px;
            text-align: center;
            font-size: 0.82rem;
            color: #1a1a1a;
            font-weight: 500;
        }

        /* Pestaña inferior decorativa */
        .org-node-card .node-ribbon {
            background: #2e75b6;
            height: 10px;
            margin: 0 18px;
            border-radius: 0 0 4px 4px;
            border: 1px solid #1c5a90;
            border-top: none;
        }

        /* Toolbar flotante */
        .org-node-card .node-toolbar { display: none; position: absolute; top: -14px; right: -10px; background: white; border-radius: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.15); padding: 4px; z-index: 30; }
        .org-node-card:hover .node-toolbar { display: flex; gap: 2px; }
        .node-tool-btn { width: 26px; height: 26px; border-radius: 50%; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; transition: all 0.2s; }
        .node-tool-btn.edit { background: #e3f2fd; color: #1976d2; }
        .node-tool-btn.delete { background: #ffebee; color: #e53935; }
        .node-tool-btn.connect { background: #e8f5e9; color: #43a047; }
        .node-tool-btn.lateral { background: #fff3e0; color: #f57c00; }
        .node-tool-btn:hover { transform: scale(1.15); }

        /* Indicador "lateral" */
        .org-node-card.is-lateral .node-puesto-block::before {
            content: '\f061';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-right: 6px;
            opacity: 0.7;
            font-size: 0.7rem;
        }
        
        /* === Conexiones SVG === */
        .connections-svg { position: absolute; top: 0; left: 0; pointer-events: none; z-index: 5; }
        .connection-line { stroke: #2e75b6; stroke-width: 1.6; fill: none; }
        .connection-line.lateral-line { stroke: #1c5a90; stroke-dasharray: 0; }

        /* Separadores de nivel */
        .level-separator-svg { position: absolute; top: 0; left: 0; pointer-events: none; z-index: 4; }
        .level-line { stroke: #333; stroke-width: 1.2; stroke-dasharray: 8 6; fill: none; }
        .level-label { font: 600 12px sans-serif; fill: #333; }
        .level-handle { position: absolute; right: 8px; transform: translateY(-50%); background: #fff; border: 1px solid #ccc; border-radius: 6px; padding: 4px 8px; font-size: 0.7rem; cursor: pointer; z-index: 6; display: none; }
        .level-handle:hover { background: #fee; color: #c00; }

        /* Toolbar lateral */
        .org-toolbar { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 3px 12px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .org-toolbar-title { font-size: 0.85rem; font-weight: 700; color: #333; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .org-toolbar-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .org-tool-btn { padding: 10px 18px; border-radius: 8px; border: 1px solid #ddd; background: white; cursor: pointer; font-size: 0.82rem; font-weight: 500; transition: all 0.3s; display: inline-flex; align-items: center; gap: 6px; }
        .org-tool-btn:hover { border-color: #1976d2; color: #1976d2; background: #e3f2fd; }
        .org-tool-btn.primary { background: #1976d2; color: white; border-color: #1976d2; }
        .org-tool-btn.primary:hover { background: #1565c0; }
        .org-tool-btn.success { background: #43a047; color: white; border-color: #43a047; }
        .org-tool-btn.danger { background: #e53935; color: white; border-color: #e53935; }
        
        /* Modal edición nodo */
        .node-modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 9999; display: none; align-items: center; justify-content: center; }
        .node-modal-content { background: white; border-radius: 16px; padding: 30px; max-width: 420px; width: 90%; }
        .node-modal-content h3 { margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .node-form-group { margin-bottom: 15px; }
        .node-form-group label { display: block; font-size: 0.8rem; color: #666; margin-bottom: 5px; font-weight: 500; }
        .node-form-group input, .node-form-group select { width: 100%; padding: 10px 14px; border: 1.5px solid #e0e0e0; border-radius: 8px; font-size: 0.9rem; }
        .node-form-group input:focus { outline: none; border-color: #1976d2; }
        .node-form-group label.cb { display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .node-form-group label.cb input { width: auto; }
        
        /* Grid de organigramas */
        .org-list-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 18px; }
        .org-list-card { background: white; border-radius: 14px; padding: 22px; box-shadow: 0 3px 12px rgba(0,0,0,0.06); border-left: 5px solid; transition: all 0.3s; }
        .org-list-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.12); transform: translateY(-3px); }
        .org-list-card h4 { font-size: 1.05rem; margin-bottom: 5px; }
        .org-list-card .org-dept { font-size: 0.8rem; color: #888; margin-bottom: 12px; }
        .org-list-card .org-count { font-size: 0.75rem; color: #1976d2; background: #e3f2fd; padding: 3px 10px; border-radius: 12px; display: inline-block; }
        .org-list-card .org-card-actions { display: flex; gap: 8px; margin-top: 15px; }
        .org-list-card .org-card-actions a { padding: 7px 14px; border-radius: 6px; font-size: 0.78rem; text-decoration: none; font-weight: 500; transition: all 0.2s; }
        .org-list-card .org-card-actions .btn-edit-org { background: #e3f2fd; color: #1976d2; }
        .org-list-card .org-card-actions .btn-del-org { background: #ffebee; color: #e53935; }
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
                    <a href="encuestas.php"><i class="fas fa-poll"></i> <span>Encuestas</span></a>
                </div>
                <div class="menu-section">
                    <a href="aplicaciones.php"><i class="fas fa-th"></i> <span>Aplicaciones</span></a>
                    <a href="kpis.php"><i class="fas fa-chart-line"></i> <span>KPIs</span></a>
                    <a href="organigrama_drag.php" class="active"><i class="fas fa-project-diagram"></i> <span>Organigramas (Drag&Drop)</span></a>
                    <a href="organigrama_builder.php"><i class="fas fa-sitemap"></i> <span>Organigrama Jerárquico</span></a>
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
            <div class="top-bar"><h1><i class="fas fa-project-diagram"></i> Organigramas</h1><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Salir</a></div>
            <?php if ($flash): ?><div class="alert alert-<?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></div><?php endif; ?>

            <?php if ($action === 'edit' && $currentOrg): ?>
            <!-- ====== EDITOR DRAG & DROP ====== -->
            <div class="org-toolbar">
                <div class="org-toolbar-title"><i class="fas fa-project-diagram" style="color:#1976d2;"></i> <?php echo htmlspecialchars($currentOrg['titulo']); ?> <span style="font-weight:400;color:#888;font-size:0.8rem;">— <?php echo htmlspecialchars($currentOrg['departamento'] ?: 'General'); ?></span></div>
                <div class="org-toolbar-actions">
                    <button class="org-tool-btn primary" onclick="addNode()"><i class="fas fa-plus"></i> Agregar Posición</button>
                    <button class="org-tool-btn" onclick="addLevel()"><i class="fas fa-grip-lines"></i> Agregar Separador</button>
                    <button class="org-tool-btn" onclick="connectMode()"><i class="fas fa-link"></i> Conectar</button>
                    <button class="org-tool-btn" onclick="autoLayout()"><i class="fas fa-magic"></i> Auto-organizar</button>
                    <button class="org-tool-btn" onclick="zoomIn()"><i class="fas fa-search-plus"></i></button>
                    <button class="org-tool-btn" onclick="zoomOut()"><i class="fas fa-search-minus"></i></button>
                    <button class="org-tool-btn" onclick="zoomReset()"><i class="fas fa-expand"></i> 100%</button>
                    <button class="org-tool-btn success" onclick="saveOrg()"><i class="fas fa-save"></i> Guardar</button>
                    <button class="org-tool-btn" onclick="exportImage()"><i class="fas fa-image"></i> Exportar Imagen</button>
                    <a href="organigrama_drag.php" class="org-tool-btn"><i class="fas fa-arrow-left"></i> Volver</a>
                </div>
            </div>
            <div class="org-canvas" id="orgCanvas">
                <div class="org-zoom-layer" id="orgZoomLayer" style="position:absolute;top:0;left:0;width:100%;height:100%;transform-origin:0 0;">
                    <svg class="level-separator-svg" id="levelsSvg"></svg>
                    <svg class="connections-svg" id="connectionsSvg"></svg>
                </div>
                <div id="zoomIndicator" style="position:absolute;bottom:10px;right:14px;background:rgba(25,118,210,0.85);color:white;padding:6px 12px;border-radius:20px;font-size:0.75rem;font-weight:600;z-index:200;pointer-events:none;">100%</div>
            </div>

            <!-- Modal para editar nodo -->
            <div class="node-modal" id="nodeModal">
                <div class="node-modal-content">
                    <h3><i class="fas fa-user-edit" style="color:#1976d2;"></i> <span id="modalTitle">Agregar Posición</span></h3>
                    <div class="node-form-group"><label>Nombre completo *</label><input type="text" id="nodeName" placeholder="Juan Pérez"></div>
                    <div class="node-form-group"><label>Puesto *</label><input type="text" id="nodePuesto" placeholder="Director General"></div>
                    <div class="node-form-group"><label>Color</label><input type="color" id="nodeColor" value="#1976D2" style="width:60px;height:38px;padding:2px;"></div>
                    <div class="node-form-group"><label>Foto</label><input type="file" id="nodePhoto" accept="image/*"><img id="nodePhotoPreview" style="width:50px;height:50px;border-radius:50%;object-fit:cover;margin-top:8px;display:none;"></div>
                    <div class="node-form-group"><label class="cb"><input type="checkbox" id="nodeLateral"> Posición lateral (asistente al lado del jefe, no debajo)</label></div>
                    <input type="hidden" id="nodeEditId" value="">
                    <input type="hidden" id="nodePhotoFile" value="">
                    <div style="display:flex;gap:10px;margin-top:20px;">
                        <button class="org-tool-btn primary" onclick="saveNode()" style="flex:1;justify-content:center;"><i class="fas fa-check"></i> Guardar</button>
                        <button class="org-tool-btn" onclick="closeModal()" style="flex:1;justify-content:center;"><i class="fas fa-times"></i> Cancelar</button>
                    </div>
                </div>
            </div>

            <script>
            // Datos del organigrama
            var orgId = <?php echo $currentOrg['id']; ?>;
            var rawData = <?php echo $currentOrg['datos_json'] ?: '[]'; ?>;
            // Compatibilidad: si es array antiguo => migrar a formato {nodes, levels}
            var nodes, levels;
            if (Array.isArray(rawData)) { nodes = rawData; levels = []; }
            else { nodes = rawData.nodes || []; levels = rawData.levels || []; }
            var connections = [];
            var isConnecting = false;
            var connectFrom = null;
            var dragNode = null;
            var dragLevel = null;
            var dragOffset = {x:0, y:0};
            var zoomLevel = 1;
            var NODE_W = 200, NODE_H = 132, GAP_X = 30, GAP_Y = 70, LATERAL_GAP = 30;

            window.onload = function() {
                var unpositioned = nodes.filter(function(n) { return n.x === undefined || n.x === null; });
                if (unpositioned.length > 0 && nodes.length > 0) autoLayout(true);
                renderAll();
            };

            function getZoomLayer() { return document.getElementById('orgZoomLayer'); }

            function renderAll() {
                var layer = getZoomLayer();
                layer.querySelectorAll('.org-node-card, .level-handle').forEach(function(n) { n.remove(); });

                connections = [];
                nodes.forEach(function(n) { if (n.parent) connections.push({from: n.parent, to: n.id, lateral: !!n.lateral}); });

                nodes.forEach(function(n) {
                    var el = document.createElement('div');
                    el.className = 'org-node-card' + (n.lateral ? ' is-lateral' : '');
                    el.dataset.id = n.id;
                    el.style.left = (n.x || 50) + 'px';
                    el.style.top = (n.y || 50) + 'px';
                    var accent = n.color || '#2e75b6';

                    var photoHtml = '';
                    if (n.photo) {
                        photoHtml = '<img src="../assets/uploads/company/' + n.photo + '" class="node-photo">';
                    } else {
                        var ini = (n.name || 'NN').split(' ').map(function(s) { return s[0]; }).join('').substring(0,2).toUpperCase();
                        photoHtml = '<div class="node-initials" style="background:' + accent + ';">' + ini + '</div>';
                    }

                    el.innerHTML =
                        '<div class="node-toolbar">' +
                            '<button class="node-tool-btn connect" onclick="startConnect(\'' + n.id + '\')" title="Conectar"><i class="fas fa-link"></i></button>' +
                            '<button class="node-tool-btn lateral" onclick="toggleLateral(\'' + n.id + '\')" title="Marcar como lateral"><i class="fas fa-arrow-right"></i></button>' +
                            '<button class="node-tool-btn edit" onclick="editNode(\'' + n.id + '\')" title="Editar"><i class="fas fa-edit"></i></button>' +
                            '<button class="node-tool-btn delete" onclick="deleteNode(\'' + n.id + '\')" title="Eliminar"><i class="fas fa-trash"></i></button>' +
                        '</div>' +
                        '<div class="node-header">' +
                            '<div class="node-photo-wrap">' + photoHtml + '</div>' +
                            '<div class="node-puesto-block" style="background:' + accent + ';">' + (n.puesto || '') + '</div>' +
                        '</div>' +
                        '<div class="node-name-block">' + (n.name || '') + '</div>' +
                        '<div class="node-ribbon" style="background:' + accent + ';"></div>';

                    el.addEventListener('mousedown', function(e) {
                        if (e.target.closest('.node-toolbar')) return;
                        if (isConnecting) { finishConnect(n.id); return; }
                        dragNode = n;
                        dragOffset.x = e.clientX / zoomLevel - (n.x || 0);
                        dragOffset.y = e.clientY / zoomLevel - (n.y || 0);
                        el.classList.add('dragging');
                        e.preventDefault();
                    });

                    layer.appendChild(el);
                });

                // Renderizar handles de niveles
                levels.forEach(function(lv, idx) {
                    var handle = document.createElement('div');
                    handle.className = 'level-handle';
                    handle.style.top = lv.y + 'px';
                    handle.style.display = 'block';
                    handle.innerHTML = '<i class="fas fa-times"></i> Quitar';
                    handle.onclick = function() { if (confirm('¿Eliminar el separador "' + lv.label + '"?')) { levels.splice(idx, 1); renderAll(); } };
                    layer.appendChild(handle);
                });

                drawConnections();
                drawLevels();
            }

            document.addEventListener('mousemove', function(e) {
                if (!dragNode) return;
                dragNode.x = Math.max(0, e.clientX / zoomLevel - dragOffset.x);
                dragNode.y = Math.max(0, e.clientY / zoomLevel - dragOffset.y);
                var el = getZoomLayer().querySelector('[data-id="' + dragNode.id + '"]');
                if (el) { el.style.left = dragNode.x + 'px'; el.style.top = dragNode.y + 'px'; }
                drawConnections();
            });

            document.addEventListener('mouseup', function() {
                if (dragNode) {
                    var el = getZoomLayer().querySelector('[data-id="' + dragNode.id + '"]');
                    if (el) el.classList.remove('dragging');
                    dragNode = null;
                }
            });

            function drawConnections() {
                var svg = document.getElementById('connectionsSvg');
                svg.innerHTML = '';
                // marker arrow
                var defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
                defs.innerHTML = '<marker id="arrow" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse"><path d="M 0 0 L 10 5 L 0 10 z" fill="#2e75b6"/></marker>';
                svg.appendChild(defs);

                var maxX = 1200, maxY = 700;
                nodes.forEach(function(n) {
                    if ((n.x || 0) + NODE_W + 80 > maxX) maxX = (n.x || 0) + NODE_W + 80;
                    if ((n.y || 0) + NODE_H + 80 > maxY) maxY = (n.y || 0) + NODE_H + 80;
                });
                levels.forEach(function(l) { if (l.y + 50 > maxY) maxY = l.y + 50; });
                svg.setAttribute('width', maxX);
                svg.setAttribute('height', maxY);
                svg.style.width = maxX + 'px';
                svg.style.height = maxY + 'px';

                connections.forEach(function(c) {
                    var p = nodes.find(function(n) { return n.id === c.from; });
                    var ch = nodes.find(function(n) { return n.id === c.to; });
                    if (!p || !ch) return;
                    var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    var d;
                    if (c.lateral) {
                        // Conexión lateral: del lado derecho del padre al lado izquierdo del hijo
                        var x1 = (p.x || 0) + NODE_W;
                        var y1 = (p.y || 0) + NODE_H/2;
                        var x2 = (ch.x || 0);
                        var y2 = (ch.y || 0) + NODE_H/2;
                        var midX = (x1 + x2) / 2;
                        d = 'M' + x1 + ',' + y1 + ' L' + midX + ',' + y1 + ' L' + midX + ',' + y2 + ' L' + x2 + ',' + y2;
                        path.setAttribute('class', 'connection-line lateral-line');
                    } else {
                        // Conexión vertical ortogonal: del centro inferior del padre al centro superior del hijo
                        var px = (p.x || 0) + NODE_W/2;
                        var py = (p.y || 0) + NODE_H;
                        var cx = (ch.x || 0) + NODE_W/2;
                        var cy = (ch.y || 0);
                        var midY = (py + cy) / 2;
                        d = 'M' + px + ',' + py + ' L' + px + ',' + midY + ' L' + cx + ',' + midY + ' L' + cx + ',' + cy;
                        path.setAttribute('class', 'connection-line');
                    }
                    path.setAttribute('d', d);
                    path.setAttribute('marker-end', 'url(#arrow)');
                    svg.appendChild(path);
                });
            }

            function drawLevels() {
                var svg = document.getElementById('levelsSvg');
                svg.innerHTML = '';
                var maxX = 1200;
                nodes.forEach(function(n) { if ((n.x || 0) + NODE_W + 80 > maxX) maxX = (n.x || 0) + NODE_W + 80; });
                svg.setAttribute('width', maxX);
                svg.setAttribute('height', 2000);
                svg.style.width = maxX + 'px';
                levels.forEach(function(lv) {
                    var line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                    line.setAttribute('x1', 0); line.setAttribute('x2', maxX);
                    line.setAttribute('y1', lv.y); line.setAttribute('y2', lv.y);
                    line.setAttribute('class', 'level-line');
                    svg.appendChild(line);

                    var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                    text.setAttribute('x', maxX/2); text.setAttribute('y', lv.y - 6);
                    text.setAttribute('text-anchor', 'middle');
                    text.setAttribute('class', 'level-label');
                    text.textContent = lv.label;
                    svg.appendChild(text);
                });
            }

            // ====== ZOOM ======
            function applyZoom() {
                getZoomLayer().style.transform = 'scale(' + zoomLevel + ')';
                document.getElementById('zoomIndicator').textContent = Math.round(zoomLevel * 100) + '%';
            }
            function zoomIn() { zoomLevel = Math.min(2.5, zoomLevel + 0.1); applyZoom(); }
            function zoomOut() { zoomLevel = Math.max(0.3, zoomLevel - 0.1); applyZoom(); }
            function zoomReset() { zoomLevel = 1; applyZoom(); }

            document.getElementById('orgCanvas').addEventListener('wheel', function(e) {
                if (e.ctrlKey || e.metaKey) { e.preventDefault(); if (e.deltaY < 0) zoomIn(); else zoomOut(); }
            }, { passive: false });

            // ====== AUTO-LAYOUT JERÁRQUICO ======
            function autoLayout(silent) {
                if (nodes.length === 0) { if (!silent) alert('No hay nodos para organizar'); return; }
                var idSet = {}; nodes.forEach(function(n) { idSet[n.id] = true; });
                var roots = nodes.filter(function(n) { return !n.parent || !idSet[n.parent]; });
                if (roots.length === 0) roots = [nodes[0]];

                function children(node) { return nodes.filter(function(c) { return c.parent === node.id && !c.lateral; }); }
                function laterals(node) { return nodes.filter(function(c) { return c.parent === node.id && c.lateral; }); }

                function subtreeWidth(node) {
                    var ch = children(node);
                    var lat = laterals(node);
                    if (ch.length === 0) return NODE_W + GAP_X + lat.length * (NODE_W + LATERAL_GAP);
                    var w = 0;
                    ch.forEach(function(c) { w += subtreeWidth(c); });
                    return Math.max(NODE_W + GAP_X + lat.length * (NODE_W + LATERAL_GAP), w);
                }

                function placeNode(node, xStart, depth) {
                    var ch = children(node);
                    var lat = laterals(node);
                    var totalW = subtreeWidth(node);
                    node.x = xStart + (totalW - NODE_W - lat.length * (NODE_W + LATERAL_GAP)) / 2;
                    node.y = 50 + depth * (NODE_H + GAP_Y);
                    // colocar laterales a la derecha del nodo
                    lat.forEach(function(l, i) {
                        l.x = node.x + NODE_W + LATERAL_GAP + i * (NODE_W + LATERAL_GAP);
                        l.y = node.y;
                    });
                    var cx = xStart;
                    ch.forEach(function(c) {
                        var cw = subtreeWidth(c);
                        placeNode(c, cx, depth + 1);
                        cx += cw;
                    });
                }

                var x = 30;
                roots.forEach(function(r) { var w = subtreeWidth(r); placeNode(r, x, 0); x += w + GAP_X * 2; });

                renderAll();
                if (!silent) alert('Organigrama auto-organizado. Recuerda Guardar.');
            }

            // ====== CRUD NODOS ======
            function addNode() {
                document.getElementById('modalTitle').textContent = 'Agregar Posición';
                document.getElementById('nodeName').value = '';
                document.getElementById('nodePuesto').value = '';
                document.getElementById('nodeColor').value = '#2E75B6';
                document.getElementById('nodeEditId').value = '';
                document.getElementById('nodePhotoFile').value = '';
                document.getElementById('nodePhoto').value = '';
                document.getElementById('nodeLateral').checked = false;
                document.getElementById('nodePhotoPreview').style.display = 'none';
                document.getElementById('nodeModal').style.display = 'flex';
            }

            function editNode(id) {
                var n = nodes.find(function(x) { return x.id === id; });
                if (!n) return;
                document.getElementById('modalTitle').textContent = 'Editar Posición';
                document.getElementById('nodeName').value = n.name;
                document.getElementById('nodePuesto').value = n.puesto;
                document.getElementById('nodeColor').value = n.color || '#2E75B6';
                document.getElementById('nodeEditId').value = n.id;
                document.getElementById('nodePhotoFile').value = n.photo || '';
                document.getElementById('nodePhoto').value = '';
                document.getElementById('nodeLateral').checked = !!n.lateral;
                if (n.photo) {
                    document.getElementById('nodePhotoPreview').src = '../assets/uploads/company/' + n.photo;
                    document.getElementById('nodePhotoPreview').style.display = 'block';
                } else {
                    document.getElementById('nodePhotoPreview').style.display = 'none';
                }
                document.getElementById('nodeModal').style.display = 'flex';
            }

            function closeModal() { document.getElementById('nodeModal').style.display = 'none'; }

            function saveNode() {
                var name = document.getElementById('nodeName').value.trim();
                var puesto = document.getElementById('nodePuesto').value.trim();
                var color = document.getElementById('nodeColor').value;
                var editId = document.getElementById('nodeEditId').value;
                var photoFile = document.getElementById('nodePhotoFile').value;
                var lateral = document.getElementById('nodeLateral').checked;

                if (!name || !puesto) { alert('Nombre y puesto son obligatorios'); return; }

                var fileInput = document.getElementById('nodePhoto');
                if (fileInput.files.length > 0) {
                    var formData = new FormData();
                    formData.append('node_photo', fileInput.files[0]);
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'organigrama_drag.php', false);
                    xhr.send(formData);
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) photoFile = res.filename;
                        else alert('Error subiendo foto: ' + (res.error || 'desconocido'));
                    } catch(e) { alert('Error subiendo foto'); }
                }

                if (editId) {
                    var n = nodes.find(function(x) { return x.id === editId; });
                    if (n) { n.name = name; n.puesto = puesto; n.color = color; n.lateral = lateral; if (photoFile) n.photo = photoFile; }
                } else {
                    nodes.push({
                        id: 'n' + Date.now(),
                        name: name, puesto: puesto, color: color, photo: photoFile,
                        lateral: lateral,
                        x: 50 + Math.random() * 400, y: 50 + Math.random() * 300, parent: null
                    });
                }
                closeModal();
                fileInput.value = '';
                renderAll();
            }

            function deleteNode(id) {
                if (!confirm('¿Eliminar esta posición?')) return;
                nodes = nodes.filter(function(n) { return n.id !== id; });
                nodes.forEach(function(n) { if (n.parent === id) n.parent = null; });
                renderAll();
            }

            function toggleLateral(id) {
                var n = nodes.find(function(x) { return x.id === id; });
                if (!n) return;
                n.lateral = !n.lateral;
                renderAll();
            }

            // ====== CONEXIONES ======
            function connectMode() {
                isConnecting = !isConnecting; connectFrom = null;
                document.getElementById('orgCanvas').style.cursor = isConnecting ? 'crosshair' : 'default';
                if (isConnecting) alert('Modo conexión activado. Haz clic en el nodo PADRE, luego en el HIJO.');
            }
            function startConnect(id) { if (!isConnecting) isConnecting = true; connectFrom = id; document.getElementById('orgCanvas').style.cursor = 'crosshair'; }
            function finishConnect(id) {
                if (connectFrom && connectFrom !== id) {
                    var n = nodes.find(function(x) { return x.id === id; });
                    if (n) n.parent = connectFrom;
                }
                connectFrom = null; isConnecting = false;
                document.getElementById('orgCanvas').style.cursor = 'default';
                renderAll();
            }

            // ====== SEPARADORES DE NIVEL ======
            function addLevel() {
                var label = prompt('Nombre del nivel (ej: "Upper Management Level", "Operational Level"):', 'Upper Management Level');
                if (!label) return;
                var y = parseInt(prompt('Posición vertical en pixeles (Y):', '300'), 10);
                if (isNaN(y)) return;
                levels.push({ id: 'l' + Date.now(), label: label, y: y });
                renderAll();
            }

            // ====== GUARDAR / EXPORTAR ======
            function saveOrg() {
                var formData = new FormData();
                formData.append('save_nodes', '1');
                formData.append('org_id', orgId);
                formData.append('nodes_json', JSON.stringify({ nodes: nodes, levels: levels }));
                fetch('organigrama_drag.php', { method: 'POST', body: formData })
                    .then(function(r) { return r.json(); })
                    .then(function(d) { if (d.success) alert('Organigrama guardado correctamente'); });
            }

            function exportImage() {
                var layer = getZoomLayer();
                var prevTransform = layer.style.transform;
                var prevWidth = layer.style.width;
                var prevHeight = layer.style.height;

                // Calcular bounding box real de todo el contenido
                var maxX = 800, maxY = 600;
                nodes.forEach(function(n) {
                    if ((n.x || 0) + NODE_W + 40 > maxX) maxX = (n.x || 0) + NODE_W + 40;
                    if ((n.y || 0) + NODE_H + 40 > maxY) maxY = (n.y || 0) + NODE_H + 40;
                });
                levels.forEach(function(l) { if (l.y + 40 > maxY) maxY = l.y + 40; });

                // Reset zoom + fijar tamaño absoluto del layer para que html2canvas capture todo
                layer.style.transform = 'scale(1)';
                layer.style.width = maxX + 'px';
                layer.style.height = maxY + 'px';
                layer.querySelectorAll('.level-handle').forEach(function(h) { h.style.visibility = 'hidden'; });

                function restore() {
                    layer.style.transform = prevTransform;
                    layer.style.width = prevWidth;
                    layer.style.height = prevHeight;
                    layer.querySelectorAll('.level-handle').forEach(function(h) { h.style.visibility = ''; });
                }

                function run() {
                    html2canvas(layer, {
                        backgroundColor: '#ffffff',
                        width: maxX,
                        height: maxY,
                        windowWidth: maxX,
                        windowHeight: maxY,
                        scale: 2,
                        useCORS: true
                    }).then(function(c) {
                        restore();
                        var link = document.createElement('a');
                        link.download = 'organigrama.png';
                        link.href = c.toDataURL('image/png');
                        link.click();
                    }).catch(function(err) {
                        restore();
                        alert('Error exportando imagen: ' + err);
                    });
                }

                if (typeof html2canvas !== 'undefined') {
                    run();
                } else {
                    var script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js';
                    script.onload = run;
                    document.head.appendChild(script);
                }
            }

            document.getElementById('nodePhoto').addEventListener('change', function(e) {
                if (e.target.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(ev) { document.getElementById('nodePhotoPreview').src = ev.target.result; document.getElementById('nodePhotoPreview').style.display = 'block'; };
                    reader.readAsDataURL(e.target.files[0]);
                }
            });
            </script>

            <?php else: ?>
            <!-- ====== LISTA DE ORGANIGRAMAS ====== -->
            <div class="content-card" style="margin-bottom:25px;">
                <div class="card-header"><h2><i class="fas fa-plus-circle"></i> Crear Nuevo Organigrama</h2></div>
                <div class="card-body">
                    <form method="POST" style="display:flex;gap:15px;align-items:end;flex-wrap:wrap;">
                        <input type="hidden" name="save_org" value="new">
                        <div class="form-group" style="flex:1;min-width:200px;margin:0;"><label>Título *</label><input type="text" name="titulo" class="form-control" required placeholder="Ej: Organigrama Dirección"></div>
                        <div class="form-group" style="flex:1;min-width:200px;margin:0;"><label>Departamento</label>
                            <select name="departamento" class="form-control"><option value="">General</option>
                            <?php foreach ($departamentos as $d): ?><option value="<?php echo htmlspecialchars($d['nombre']); ?>"><?php echo htmlspecialchars($d['nombre']); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Crear</button>
                    </form>
                </div>
            </div>

            <?php if (count($organigramas) > 0): ?>
            <div class="org-list-grid">
                <?php 
                $colores = ['#1976D2','#E53935','#43A047','#FF9800','#9C27B0','#00BCD4','#E91E63','#3F51B5'];
                foreach ($organigramas as $i => $org):
                    $datos = json_decode($org['datos_json'] ?: '[]', true);
                    // Compat: nuevo formato {nodes, levels} o array antiguo
                    $numNodos = is_array($datos) ? (isset($datos['nodes']) ? count($datos['nodes']) : count($datos)) : 0;
                    $color = $colores[$i % count($colores)];
                ?>
                <div class="org-list-card" style="border-left-color: <?php echo $color; ?>;">
                    <h4><?php echo htmlspecialchars($org['titulo']); ?></h4>
                    <div class="org-dept"><?php echo htmlspecialchars($org['departamento'] ?: 'General'); ?></div>
                    <span class="org-count"><i class="fas fa-users"></i> <?php echo $numNodos; ?> posiciones</span>
                    <div class="org-card-actions">
                        <a href="organigrama_drag.php?action=edit&org=<?php echo $org['id']; ?>" class="btn-edit-org"><i class="fas fa-edit"></i> Editar</a>
                        <a href="organigrama_drag.php?action=delete&id=<?php echo $org['id']; ?>" onclick="return confirmDelete()" class="btn-del-org"><i class="fas fa-trash"></i> Eliminar</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div style="text-align:center;padding:60px;color:#999;background:white;border-radius:12px;"><i class="fas fa-project-diagram" style="font-size:3rem;opacity:0.3;display:block;margin-bottom:15px;"></i><p>No hay organigramas. Crea uno arriba para empezar.</p></div>
            <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
