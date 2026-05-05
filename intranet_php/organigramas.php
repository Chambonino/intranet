<?php
/**
 * Vista pública de Organigramas (solo lectura)
 * Muestra todos los organigramas creados desde el panel admin (drag & drop).
 */
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Obtener todos los organigramas activos
try {
    $organigramas = $pdo->query("SELECT * FROM organigramas_custom WHERE activo = 1 ORDER BY departamento, titulo")->fetchAll();
} catch (Exception $e) {
    $organigramas = [];
}

// Si pide ver uno específico
$verId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$current = null;
if ($verId) {
    $stmt = $pdo->prepare("SELECT * FROM organigramas_custom WHERE id = ? AND activo = 1");
    $stmt->execute([$verId]);
    $current = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organigramas - Intranet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .org-page { max-width: 1300px; margin: 0 auto; padding: 25px 40px 60px; }
        .org-page h1 { font-size: 1.5rem; margin-bottom: 6px; }
        .org-page .subtitle { color: var(--text-muted); font-size: 0.85rem; margin-bottom: 25px; }

        /* Grid de organigramas */
        .pub-org-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 18px; }
        .pub-org-card { background: var(--bg-card); border-radius: 14px; padding: 22px; border-left: 5px solid; transition: all 0.3s; cursor: pointer; text-decoration: none; color: var(--text-primary); display: block; }
        .pub-org-card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.4); background: var(--bg-card-hover); }
        .pub-org-card h4 { font-size: 1.05rem; margin-bottom: 5px; color: var(--text-primary); }
        .pub-org-card .org-dept-tag { font-size: 0.75rem; color: var(--text-muted); background: rgba(255,255,255,0.05); padding: 3px 10px; border-radius: 12px; display: inline-block; margin-bottom: 10px; }
        .pub-org-card .org-info { font-size: 0.78rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px; margin-top: 10px; }
        .pub-org-card .org-cta { display: inline-flex; align-items: center; gap: 6px; font-size: 0.78rem; color: var(--accent-blue, #42a5f5); margin-top: 12px; font-weight: 600; }

        /* Viewer */
        .viewer-toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 18px; background: var(--bg-card); padding: 18px 22px; border-radius: 12px; }
        .viewer-toolbar h2 { font-size: 1.15rem; }
        .viewer-toolbar .vt-meta { color: var(--text-muted); font-size: 0.82rem; }
        .viewer-toolbar .vt-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .vt-btn { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); color: var(--text-primary); padding: 9px 14px; border-radius: 8px; cursor: pointer; font-size: 0.82rem; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .vt-btn:hover { background: rgba(255,255,255,0.12); border-color: rgba(255,255,255,0.2); }
        .vt-btn.primary { background: var(--accent-blue, #1976d2); border-color: var(--accent-blue, #1976d2); color: white; }

        .org-viewer-canvas {
            position: relative;
            min-height: 600px;
            background: #f6f8fa;
            border-radius: 14px;
            overflow: auto;
            border: 1px solid rgba(255,255,255,0.08);
        }
        .org-viewer-layer { position: absolute; top: 0; left: 0; width: 100%; height: 100%; transform-origin: 0 0; }

        .v-node-card {
            position: absolute;
            width: 180px;
            background: white;
            border-radius: 14px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.12);
            user-select: none;
            border-top: 4px solid #1976d2;
            z-index: 10;
            transition: transform 0.25s, box-shadow 0.25s;
        }
        .v-node-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.22); z-index: 20; }
        .v-node-card .vn-photo { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 3px solid #eee; margin: 15px auto 8px; display: block; }
        .v-node-card .vn-initials { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 15px auto 8px; color: white; font-weight: 800; font-size: 1.15rem; }
        .v-node-card .vn-name { text-align: center; font-weight: 700; font-size: 0.85rem; color: #333; padding: 0 10px; }
        .v-node-card .vn-puesto { text-align: center; font-size: 0.72rem; color: #888; padding: 2px 10px 12px; }

        .v-connections-svg { position: absolute; top: 0; left: 0; pointer-events: none; z-index: 5; }
        .v-connection-line { stroke: #90a4ae; stroke-width: 2; fill: none; }

        .empty-state { text-align: center; padding: 80px 20px; color: var(--text-muted); background: var(--bg-card); border-radius: 14px; }
        .empty-state i { font-size: 3rem; opacity: 0.3; display: block; margin-bottom: 15px; }

        .zoom-pill { position: absolute; bottom: 14px; right: 18px; background: rgba(25,118,210,0.9); color: white; padding: 7px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; z-index: 200; pointer-events: none; }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo-text"><img src="assets/img/logo.png" alt="Logo" style="height:45px;" onerror="this.style.display='none'"></div>
            <nav><a href="index.php" style="color: white; text-decoration: none; font-weight: 500;"><i class="fas fa-home"></i> Inicio</a></nav>
        </div>
    </header>

    <main class="org-page">
        <?php if ($current): ?>
            <!-- ====== VIEWER DE UN ORGANIGRAMA ESPECÍFICO ====== -->
            <div class="viewer-toolbar">
                <div>
                    <h2><i class="fas fa-project-diagram" style="color: var(--accent-blue, #42a5f5);"></i> <?php echo htmlspecialchars($current['titulo']); ?></h2>
                    <div class="vt-meta"><i class="fas fa-building"></i> <?php echo htmlspecialchars($current['departamento'] ?: 'General'); ?></div>
                </div>
                <div class="vt-actions">
                    <button class="vt-btn" onclick="vZoomIn()" data-testid="org-zoom-in"><i class="fas fa-search-plus"></i></button>
                    <button class="vt-btn" onclick="vZoomOut()" data-testid="org-zoom-out"><i class="fas fa-search-minus"></i></button>
                    <button class="vt-btn" onclick="vZoomReset()" data-testid="org-zoom-reset"><i class="fas fa-expand"></i> 100%</button>
                    <button class="vt-btn primary" onclick="vExportImage()" data-testid="org-export-image"><i class="fas fa-image"></i> Descargar imagen</button>
                    <a href="organigramas.php" class="vt-btn" data-testid="org-back-list"><i class="fas fa-arrow-left"></i> Volver</a>
                </div>
            </div>

            <div class="org-viewer-canvas" id="vCanvas">
                <div class="org-viewer-layer" id="vLayer">
                    <svg class="v-connections-svg" id="vSvg"></svg>
                </div>
                <div class="zoom-pill" id="vZoomPill">100%</div>
            </div>

            <script>
            var vNodes = <?php echo $current['datos_json'] ?: '[]'; ?>;
            var vZoom = 1;
            var V_NODE_W = 180, V_NODE_H = 145;

            function vRender() {
                var layer = document.getElementById('vLayer');
                layer.querySelectorAll('.v-node-card').forEach(function(n) { n.remove(); });
                vNodes.forEach(function(n) {
                    var el = document.createElement('div');
                    el.className = 'v-node-card';
                    el.style.left = (n.x || 0) + 'px';
                    el.style.top = (n.y || 0) + 'px';
                    el.style.borderTopColor = n.color || '#1976d2';

                    var photoHtml = '';
                    if (n.photo) {
                        photoHtml = '<img src="assets/uploads/company/' + n.photo + '" class="vn-photo">';
                    } else {
                        var ini = (n.name || 'NN').split(' ').map(function(s) { return s[0]; }).join('').substring(0,2).toUpperCase();
                        photoHtml = '<div class="vn-initials" style="background:' + (n.color||'#1976d2') + ';">' + ini + '</div>';
                    }

                    el.innerHTML = photoHtml +
                        '<div class="vn-name">' + (n.name || '') + '</div>' +
                        '<div class="vn-puesto">' + (n.puesto || '') + '</div>';
                    layer.appendChild(el);
                });
                vDrawConnections();
            }

            function vDrawConnections() {
                var svg = document.getElementById('vSvg');
                svg.innerHTML = '';
                var maxX = 1000, maxY = 600;
                vNodes.forEach(function(n) {
                    if ((n.x || 0) + V_NODE_W + 50 > maxX) maxX = (n.x || 0) + V_NODE_W + 50;
                    if ((n.y || 0) + V_NODE_H + 50 > maxY) maxY = (n.y || 0) + V_NODE_H + 50;
                });
                svg.setAttribute('width', maxX);
                svg.setAttribute('height', maxY);
                svg.style.width = maxX + 'px';
                svg.style.height = maxY + 'px';

                vNodes.forEach(function(n) {
                    if (!n.parent) return;
                    var p = vNodes.find(function(x) { return x.id === n.parent; });
                    if (!p) return;
                    var x1 = (p.x || 0) + V_NODE_W/2, y1 = (p.y || 0) + V_NODE_H;
                    var x2 = (n.x || 0) + V_NODE_W/2, y2 = (n.y || 0);
                    var midY = (y1 + y2) / 2;
                    var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    path.setAttribute('d', 'M' + x1 + ',' + y1 + ' C' + x1 + ',' + midY + ' ' + x2 + ',' + midY + ' ' + x2 + ',' + y2);
                    path.setAttribute('class', 'v-connection-line');
                    svg.appendChild(path);
                });
            }

            function vApplyZoom() {
                document.getElementById('vLayer').style.transform = 'scale(' + vZoom + ')';
                document.getElementById('vZoomPill').textContent = Math.round(vZoom * 100) + '%';
            }
            function vZoomIn() { vZoom = Math.min(2.5, vZoom + 0.1); vApplyZoom(); }
            function vZoomOut() { vZoom = Math.max(0.3, vZoom - 0.1); vApplyZoom(); }
            function vZoomReset() { vZoom = 1; vApplyZoom(); }

            document.getElementById('vCanvas').addEventListener('wheel', function(e) {
                if (e.ctrlKey || e.metaKey) {
                    e.preventDefault();
                    if (e.deltaY < 0) vZoomIn(); else vZoomOut();
                }
            }, { passive: false });

            function vExportImage() {
                var layer = document.getElementById('vLayer');
                var prev = layer.style.transform;
                layer.style.transform = 'scale(1)';
                if (typeof html2canvas !== 'undefined') {
                    html2canvas(layer, {backgroundColor: '#f6f8fa'}).then(function(c) {
                        layer.style.transform = prev;
                        var link = document.createElement('a');
                        link.download = 'organigrama_<?php echo preg_replace('/[^a-z0-9]/i','_', $current['titulo']); ?>.png';
                        link.href = c.toDataURL();
                        link.click();
                    });
                } else {
                    var s = document.createElement('script');
                    s.src = 'https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js';
                    s.onload = function() { layer.style.transform = prev; vExportImage(); };
                    document.head.appendChild(s);
                }
            }

            window.onload = vRender;
            </script>

        <?php else: ?>
            <!-- ====== LISTA DE ORGANIGRAMAS ====== -->
            <h1><i class="fas fa-project-diagram" style="color: var(--accent-blue, #42a5f5);"></i> Organigramas</h1>
            <p class="subtitle">Selecciona un organigrama para visualizarlo. Usa Ctrl + rueda del ratón para hacer zoom.</p>

            <?php if (count($organigramas) > 0): ?>
                <div class="pub-org-grid">
                    <?php
                    $colores = ['#1976D2','#E53935','#43A047','#FF9800','#9C27B0','#00BCD4','#E91E63','#3F51B5'];
                    foreach ($organigramas as $i => $org):
                        $datos = json_decode($org['datos_json'] ?: '[]', true);
                        $numNodos = is_array($datos) ? count($datos) : 0;
                        $color = $colores[$i % count($colores)];
                    ?>
                    <a href="organigramas.php?id=<?php echo $org['id']; ?>" class="pub-org-card" style="border-left-color: <?php echo $color; ?>;" data-testid="org-card-<?php echo $org['id']; ?>">
                        <h4><?php echo htmlspecialchars($org['titulo']); ?></h4>
                        <span class="org-dept-tag"><i class="fas fa-building"></i> <?php echo htmlspecialchars($org['departamento'] ?: 'General'); ?></span>
                        <div class="org-info"><i class="fas fa-users" style="color:<?php echo $color; ?>;"></i> <?php echo $numNodos; ?> posiciones</div>
                        <div class="org-cta">Ver organigrama <i class="fas fa-arrow-right"></i></div>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-project-diagram"></i>
                    <p>Aún no hay organigramas publicados.</p>
                    <p style="font-size:0.78rem;margin-top:8px;">Pídele al administrador que cree uno desde el panel.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Intranet Corporativa | <a href="index.php" style="color:var(--text-muted);text-decoration:none;">Inicio</a></p>
    </footer>
</body>
</html>
