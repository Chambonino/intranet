<?php
/**
 * Galería de Fotos por Departamento
 */
require_once 'includes/config.php';
require_once 'includes/functions.php';

$departamentos = getDepartamentos($pdo);
$deptId = $_GET['dept'] ?? null;
$deptActual = null;

if ($deptId) {
    $stmt = $pdo->prepare("SELECT * FROM departamentos WHERE id = ?");
    $stmt->execute([$deptId]);
    $deptActual = $stmt->fetch();
    $stmt = $pdo->prepare("SELECT * FROM galeria_fotos WHERE activo = 1 AND departamento_id = ? ORDER BY orden ASC, id DESC");
    $stmt->execute([$deptId]);
} else {
    $stmt = $pdo->query("SELECT g.*, d.nombre as dept_nombre FROM galeria_fotos g LEFT JOIN departamentos d ON g.departamento_id = d.id WHERE g.activo = 1 ORDER BY g.id DESC");
}
$fotos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galería de Fotos<?php echo $deptActual ? ' - ' . $deptActual['nombre'] : ''; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .gallery-page { max-width: 1200px; margin: 0 auto; padding: 20px 40px 40px; }
        .dept-pills { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 25px; }
        .dept-pill { padding: 8px 18px; border-radius: 20px; text-decoration: none; font-size: 0.85rem; font-weight: 500; transition: all 0.3s; }
        .dept-pill.active { color: white; }
        .dept-pill:not(.active) { background: var(--bg-card); color: var(--text-secondary); }
        .dept-pill:not(.active):hover { background: var(--bg-card-hover); }
        .photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; }
        .photo-card { border-radius: 12px; overflow: hidden; cursor: pointer; position: relative; height: 200px; }
        .photo-card img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s; }
        .photo-card:hover img { transform: scale(1.08); }
        .photo-card .caption { position: absolute; bottom: 0; left: 0; right: 0; padding: 10px; background: linear-gradient(to top, rgba(0,0,0,0.8), transparent); color: white; font-size: 0.8rem; }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo-text"><h1>AUTOMOTRIZ CORP</h1><span>INYECCI&Oacute;N &bull; CROMADO &bull; PINTURA</span></div>
            <a href="index.php" style="color:white;text-decoration:none;font-weight:500;"><i class="fas fa-home"></i> Inicio</a>
        </div>
    </header>
    <main class="gallery-page">
        <h2 style="margin-bottom:20px;"><i class="fas fa-images" style="color:var(--accent-blue);"></i> Galería de Fotos <?php echo $deptActual ? '- ' . htmlspecialchars($deptActual['nombre']) : ''; ?></h2>
        <div class="dept-pills">
            <a href="galeria_fotos.php" class="dept-pill <?php echo !$deptId ? 'active' : ''; ?>" style="<?php echo !$deptId ? 'background:var(--accent-blue);' : ''; ?>">Todas</a>
            <?php foreach ($departamentos as $dept): ?>
            <a href="galeria_fotos.php?dept=<?php echo $dept['id']; ?>" class="dept-pill <?php echo $deptId == $dept['id'] ? 'active' : ''; ?>" style="<?php echo $deptId == $dept['id'] ? 'background:' . $dept['color'] . ';' : ''; ?>"><?php echo htmlspecialchars($dept['nombre']); ?></a>
            <?php endforeach; ?>
        </div>
        <?php if (count($fotos) > 0): ?>
        <div class="photo-grid">
            <?php foreach ($fotos as $foto): ?>
            <div class="photo-card" onclick="openLightbox('assets/uploads/gallery/<?php echo $foto['imagen']; ?>','<?php echo htmlspecialchars($foto['titulo'] ?? '', ENT_QUOTES); ?>')">
                <img src="assets/uploads/gallery/<?php echo $foto['imagen']; ?>" alt="">
                <?php if ($foto['titulo']): ?><div class="caption"><?php echo htmlspecialchars($foto['titulo']); ?></div><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:60px;color:var(--text-muted);"><i class="fas fa-images" style="font-size:3rem;opacity:0.3;margin-bottom:15px;display:block;"></i>No hay fotos en esta galería</div>
        <?php endif; ?>
    </main>
    <footer class="footer"><p>&copy; <?php echo date('Y'); ?> Automotriz Corp.</p></footer>
    <script>
    function openLightbox(src, title) {
        let m = document.getElementById('lb');
        if (!m) {
            m = document.createElement('div'); m.id = 'lb';
            m.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.95);z-index:9999;display:flex;align-items:center;justify-content:center;flex-direction:column;cursor:pointer;';
            m.innerHTML = '<button onclick="this.parentElement.style.display=\'none\'" style="position:absolute;top:20px;right:20px;background:none;border:none;color:white;font-size:2rem;cursor:pointer;"><i class="fas fa-times"></i></button><img id="lbImg" style="max-width:90%;max-height:80%;object-fit:contain;border-radius:10px;"><p id="lbCap" style="color:white;margin-top:15px;font-size:1.1rem;"></p>';
            m.addEventListener('click', function(e) { if(e.target===m) m.style.display='none'; });
            document.body.appendChild(m);
        }
        document.getElementById('lbImg').src = src;
        document.getElementById('lbCap').textContent = title;
        m.style.display = 'flex';
    }
    document.addEventListener('keydown', function(e) { if(e.key==='Escape'){var m=document.getElementById('lb');if(m)m.style.display='none';} });
    </script>
</body>
</html>
