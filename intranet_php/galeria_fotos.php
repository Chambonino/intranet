<?php
/**
 * Galería de Fotos por Departamento y Tipo de Evento
 */
require_once 'includes/config.php';
require_once 'includes/functions.php';

$departamentos = getDepartamentos($pdo);
$deptId = $_GET['dept'] ?? null;
$tipoEvento = $_GET['tipo'] ?? null;

// Obtener tipos de evento disponibles
$tipos = $pdo->query("SELECT DISTINCT tipo_evento FROM galeria_fotos WHERE tipo_evento IS NOT NULL AND tipo_evento != '' AND activo = 1 ORDER BY tipo_evento")->fetchAll(PDO::FETCH_COLUMN);

// Construir query
$where = "WHERE g.activo = 1";
$params = [];
if ($deptId) { $where .= " AND g.departamento_id = ?"; $params[] = (int)$deptId; }
if ($tipoEvento) { $where .= " AND g.tipo_evento = ?"; $params[] = $tipoEvento; }

$stmt = $pdo->prepare("SELECT g.*, d.nombre as dept_nombre FROM galeria_fotos g LEFT JOIN departamentos d ON g.departamento_id = d.id $where ORDER BY g.id DESC");
$stmt->execute($params);
$fotos = $stmt->fetchAll();

$deptActual = null;
if ($deptId) { $s = $pdo->prepare("SELECT * FROM departamentos WHERE id = ?"); $s->execute([$deptId]); $deptActual = $s->fetch(); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galería de Fotos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .gallery-page { max-width: 1200px; margin: 0 auto; padding: 20px 40px 40px; }
        .filter-row { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
        .filter-label { font-size: 0.8rem; color: var(--text-muted); margin-bottom: 5px; }
        .pill-group { display: flex; flex-wrap: wrap; gap: 6px; }
        .pill { padding: 6px 16px; border-radius: 20px; text-decoration: none; font-size: 0.8rem; font-weight: 500; transition: all 0.3s; }
        .pill.active { color: white; }
        .pill:not(.active) { background: var(--bg-card); color: var(--text-secondary); }
        .pill:not(.active):hover { background: var(--bg-card-hover); }
        .photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; }
        .photo-card { border-radius: 12px; overflow: hidden; cursor: pointer; position: relative; height: 200px; }
        .photo-card img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s; }
        .photo-card:hover img { transform: scale(1.08); }
        .photo-card .caption { position: absolute; bottom: 0; left: 0; right: 0; padding: 10px; background: linear-gradient(to top, rgba(0,0,0,0.8), transparent); color: white; font-size: 0.8rem; }
        .photo-card .badge-tipo { position: absolute; top: 10px; right: 10px; padding: 3px 10px; background: rgba(0,0,0,0.7); color: white; border-radius: 12px; font-size: 0.65rem; }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo-text"><img src="assets/img/logo.png" alt="NB" style="height:45px;"></div>
            <a href="index.php" style="color:white;text-decoration:none;font-weight:500;"><i class="fas fa-home"></i> Inicio</a>
        </div>
    </header>
    <main class="gallery-page">
        <h2 style="margin-bottom: 20px;"><i class="fas fa-images" style="color: var(--accent-blue);"></i> Galería de Fotos</h2>
        
        <!-- Filtros -->
        <div class="section-card" style="padding: 18px; margin-bottom: 20px;">
            <div class="filter-row">
                <div style="flex: 1;">
                    <div class="filter-label"><i class="fas fa-building"></i> Departamento</div>
                    <div class="pill-group">
                        <a href="galeria_fotos.php<?php echo $tipoEvento ? '?tipo=' . urlencode($tipoEvento) : ''; ?>" class="pill <?php echo !$deptId ? 'active' : ''; ?>" style="<?php echo !$deptId ? 'background:var(--accent-blue);' : ''; ?>">Todos</a>
                        <?php foreach ($departamentos as $d): ?>
                        <a href="galeria_fotos.php?dept=<?php echo $d['id']; ?><?php echo $tipoEvento ? '&tipo=' . urlencode($tipoEvento) : ''; ?>" class="pill <?php echo $deptId == $d['id'] ? 'active' : ''; ?>" style="<?php echo $deptId == $d['id'] ? 'background:' . $d['color'] . ';' : ''; ?>"><?php echo htmlspecialchars($d['nombre']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php if (count($tipos) > 0): ?>
            <div class="filter-row" style="margin-top: 10px;">
                <div style="flex: 1;">
                    <div class="filter-label"><i class="fas fa-tag"></i> Tipo de Evento</div>
                    <div class="pill-group">
                        <a href="galeria_fotos.php<?php echo $deptId ? '?dept=' . $deptId : ''; ?>" class="pill <?php echo !$tipoEvento ? 'active' : ''; ?>" style="<?php echo !$tipoEvento ? 'background:var(--accent-purple);' : ''; ?>">Todos</a>
                        <?php foreach ($tipos as $t): ?>
                        <a href="galeria_fotos.php?<?php echo $deptId ? 'dept=' . $deptId . '&' : ''; ?>tipo=<?php echo urlencode($t); ?>" class="pill <?php echo $tipoEvento === $t ? 'active' : ''; ?>" style="<?php echo $tipoEvento === $t ? 'background:var(--accent-orange);' : ''; ?>"><?php echo htmlspecialchars($t); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 15px;"><?php echo count($fotos); ?> foto<?php echo count($fotos) != 1 ? 's' : ''; ?> encontrada<?php echo count($fotos) != 1 ? 's' : ''; ?></p>

        <?php if (count($fotos) > 0): ?>
        <div class="photo-grid">
            <?php foreach ($fotos as $foto): ?>
            <div class="photo-card" onclick="openLB('assets/uploads/gallery/<?php echo $foto['imagen']; ?>','<?php echo htmlspecialchars($foto['titulo'] ?? '', ENT_QUOTES); ?>')">
                <img src="assets/uploads/gallery/<?php echo $foto['imagen']; ?>" alt="" loading="lazy">
                <?php if ($foto['tipo_evento']): ?><span class="badge-tipo"><?php echo htmlspecialchars($foto['tipo_evento']); ?></span><?php endif; ?>
                <div class="caption">
                    <?php if ($foto['titulo']): ?><strong><?php echo htmlspecialchars($foto['titulo']); ?></strong><br><?php endif; ?>
                    <?php if ($foto['dept_nombre']): ?><span style="font-size:0.7rem;opacity:0.8;"><?php echo htmlspecialchars($foto['dept_nombre']); ?></span><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:60px;color:var(--text-muted);"><i class="fas fa-images" style="font-size:3rem;opacity:0.3;margin-bottom:15px;display:block;"></i>No hay fotos con estos filtros</div>
        <?php endif; ?>
    </main>
    <footer class="footer"><p>&copy; <?php echo date('Y'); ?> Automotriz Corp.</p></footer>
    <script>
    function openLB(src,title){let m=document.getElementById('lb');if(!m){m=document.createElement('div');m.id='lb';m.style.cssText='position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.95);z-index:9999;display:flex;align-items:center;justify-content:center;flex-direction:column;cursor:pointer;';m.innerHTML='<button onclick="this.parentElement.style.display=\'none\'" style="position:absolute;top:20px;right:20px;background:none;border:none;color:white;font-size:2rem;cursor:pointer;"><i class="fas fa-times"></i></button><img id="lbI" style="max-width:90%;max-height:80%;object-fit:contain;border-radius:10px;"><p id="lbC" style="color:white;margin-top:15px;font-size:1rem;"></p>';m.addEventListener('click',function(e){if(e.target===m)m.style.display='none';});document.body.appendChild(m);}document.getElementById('lbI').src=src;document.getElementById('lbC').textContent=title;m.style.display='flex';}
    document.addEventListener('keydown',function(e){if(e.key==='Escape'){var m=document.getElementById('lb');if(m)m.style.display='none';}});
    </script>
</body>
</html>
