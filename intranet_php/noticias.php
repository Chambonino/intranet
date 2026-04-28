<?php
/**
 * Todas las Noticias y Artículos - con paginación, filtros y búsqueda
 */
require_once 'includes/config.php';
require_once 'includes/functions.php';

$departamentos = getDepartamentos($pdo);

// Filtros
$busqueda = $_GET['q'] ?? '';
$deptFilter = $_GET['dept'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Construir query
$where = "WHERE a.activo = 1";
$params = [];

if ($busqueda) {
    $where .= " AND (a.titulo LIKE ? OR a.contenido LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}
if ($deptFilter) {
    $where .= " AND a.autor = ?";
    $params[] = $deptFilter;
}

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM articulos a $where");
$stmtCount->execute($params);
$total = $stmtCount->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

$stmt = $pdo->prepare("SELECT a.* FROM articulos a $where ORDER BY a.fecha_publicacion DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$articulos = $stmt->fetchAll();

$mesesEsp = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Noticias y Artículos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .news-page { max-width: 1200px; margin: 0 auto; padding: 20px 40px 40px; }
        .search-bar { display: flex; gap: 12px; margin-bottom: 25px; flex-wrap: wrap; }
        .search-input { flex: 1; min-width: 250px; padding: 12px 18px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; color: white; font-size: 0.95rem; }
        .search-input::placeholder { color: var(--text-muted); }
        .filter-select { padding: 12px 18px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-secondary); font-size: 0.9rem; min-width: 160px; }
        .search-btn { padding: 12px 25px; background: var(--accent-red); color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 0.9rem; }
        .search-btn:hover { background: #c62828; }
        .news-card { display: flex; gap: 20px; background: var(--bg-card); border-radius: 12px; overflow: hidden; margin-bottom: 15px; transition: transform 0.3s; text-decoration: none; color: inherit; }
        .news-card:hover { transform: translateX(5px); }
        .news-card-img { width: 220px; min-height: 160px; flex-shrink: 0; overflow: hidden; }
        .news-card-img img { width: 100%; height: 100%; object-fit: cover; }
        .news-card-img .no-img { width: 100%; height: 100%; background: #222; display: flex; align-items: center; justify-content: center; }
        .news-card-body { padding: 20px; flex: 1; display: flex; flex-direction: column; }
        .news-card-date { font-size: 0.75rem; color: var(--text-muted); margin-bottom: 8px; }
        .news-card-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 8px; }
        .news-card-excerpt { font-size: 0.85rem; color: var(--text-secondary); line-height: 1.6; flex: 1; }
        .news-card-read { font-size: 0.8rem; color: var(--accent-red); font-weight: 600; margin-top: 10px; }
        .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 25px; }
        .pagination a { padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 0.85rem; font-weight: 500; }
        .pagination a.active { background: var(--accent-red); color: white; }
        .pagination a:not(.active) { background: var(--bg-card); color: var(--text-secondary); }
        .pagination a:not(.active):hover { background: var(--bg-card-hover); }
        .results-info { color: var(--text-muted); font-size: 0.85rem; margin-bottom: 15px; }
        @media (max-width: 768px) { .news-card { flex-direction: column; } .news-card-img { width: 100%; height: 180px; } }
    </style>
</head>
<body>
    <header class="header" style="background: url('assets/img/fondo1.png') center/cover; position:fixed;top:0;left:0;right:0;z-index:1000;">
        <div style="position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.65);"></div>
        <div class="header-content" style="position:relative;z-index:1;">
            <div class="logo-text"><h1>AUTOMOTRIZ CORP</h1><span>INYECCI&Oacute;N &bull; CROMADO &bull; PINTURA</span></div>
            <a href="index.php" style="color:white;text-decoration:none;font-weight:500;"><i class="fas fa-home"></i> Inicio</a>
        </div>
    </header>
    <div style="height:100px;"></div>

    <main class="news-page">
        <h2 style="margin-bottom: 20px;"><i class="fas fa-newspaper" style="color: var(--accent-red);"></i> Noticias y Art&iacute;culos</h2>

        <!-- Búsqueda y filtros -->
        <form method="GET" class="search-bar">
            <input type="text" name="q" class="search-input" placeholder="Buscar por título o contenido..." value="<?php echo htmlspecialchars($busqueda); ?>">
            <select name="dept" class="filter-select">
                <option value="">Todos los departamentos</option>
                <?php foreach ($departamentos as $d): ?>
                <option value="<?php echo htmlspecialchars($d['nombre']); ?>" <?php echo $deptFilter === $d['nombre'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="search-btn"><i class="fas fa-search"></i> Buscar</button>
            <?php if ($busqueda || $deptFilter): ?>
            <a href="noticias.php" style="padding:12px 18px;background:var(--bg-card);border-radius:10px;color:var(--text-secondary);text-decoration:none;font-size:0.9rem;display:flex;align-items:center;gap:5px;"><i class="fas fa-times"></i> Limpiar</a>
            <?php endif; ?>
        </form>

        <p class="results-info">
            Mostrando <?php echo count($articulos); ?> de <?php echo $total; ?> resultado<?php echo $total != 1 ? 's' : ''; ?>
            <?php if ($busqueda): ?> para "<strong><?php echo htmlspecialchars($busqueda); ?></strong>"<?php endif; ?>
            <?php if ($deptFilter): ?> en <strong><?php echo htmlspecialchars($deptFilter); ?></strong><?php endif; ?>
        </p>

        <!-- Lista de noticias -->
        <?php if (count($articulos) > 0): ?>
            <?php foreach ($articulos as $art): ?>
            <a href="articulo.php?id=<?php echo $art['id']; ?>" class="news-card">
                <div class="news-card-img">
                    <?php if ($art['imagen']): ?>
                    <img src="assets/uploads/articles/<?php echo $art['imagen']; ?>" alt="" loading="lazy">
                    <?php else: ?>
                    <div class="no-img"><i class="fas fa-newspaper" style="font-size:2.5rem;color:#444;"></i></div>
                    <?php endif; ?>
                </div>
                <div class="news-card-body">
                    <div class="news-card-date">
                        <i class="fas fa-calendar"></i> <?php echo formatearFecha($art['fecha_publicacion'], 'completo'); ?>
                        <?php if ($art['autor']): ?> &bull; <i class="fas fa-user"></i> <?php echo htmlspecialchars($art['autor']); ?><?php endif; ?>
                    </div>
                    <div class="news-card-title"><?php echo htmlspecialchars($art['titulo']); ?></div>
                    <div class="news-card-excerpt"><?php echo truncarTexto(strip_tags($art['contenido']), 200); ?></div>
                    <div class="news-card-read">Leer m&aacute;s <i class="fas fa-arrow-right"></i></div>
                </div>
            </a>
            <?php endforeach; ?>

            <!-- Paginación -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&q=<?php echo urlencode($busqueda); ?>&dept=<?php echo urlencode($deptFilter); ?>"><i class="fas fa-chevron-left"></i></a>
                <?php endif; ?>
                <?php
                $start = max(1, $page - 3);
                $end = min($totalPages, $page + 3);
                for ($p = $start; $p <= $end; $p++):
                ?>
                <a href="?page=<?php echo $p; ?>&q=<?php echo urlencode($busqueda); ?>&dept=<?php echo urlencode($deptFilter); ?>" class="<?php echo $p == $page ? 'active' : ''; ?>"><?php echo $p; ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page+1; ?>&q=<?php echo urlencode($busqueda); ?>&dept=<?php echo urlencode($deptFilter); ?>"><i class="fas fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <div style="text-align:center;padding:60px;color:var(--text-muted);">
                <i class="fas fa-search" style="font-size:3rem;opacity:0.3;margin-bottom:15px;display:block;"></i>
                <p>No se encontraron noticias<?php echo $busqueda ? ' para "' . htmlspecialchars($busqueda) . '"' : ''; ?></p>
            </div>
        <?php endif; ?>
    </main>

    <footer class="footer"><p>&copy; <?php echo date('Y'); ?> Automotriz Corp.</p></footer>
</body>
</html>
