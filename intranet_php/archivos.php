<?php
/**
 * Página pública: Todos los archivos por departamento con filtros.
 * - Búsqueda por nombre
 * - Filtro por departamento
 * - PDFs se abren en modal flotante (sin descargar)
 * - Iconos dinámicos por extensión
 */
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Filtros
$busqueda = trim($_GET['q'] ?? '');
$deptId   = $_GET['dept'] ?? 'all';
$tipoExt  = $_GET['tipo'] ?? 'all';
$pagina   = max(1, (int)($_GET['p'] ?? 1));
$perPage  = 24;

// Departamentos para el selector
$departamentos = $pdo->query("SELECT id, nombre FROM departamentos ORDER BY nombre ASC")->fetchAll();

// Query dinámica
$where = ["a.activo = 1"];
$params = [];
if ($busqueda !== '') { $where[] = "(a.nombre LIKE ? OR a.descripcion LIKE ?)"; $params[] = "%$busqueda%"; $params[] = "%$busqueda%"; }
if ($deptId !== 'all' && ctype_digit($deptId)) { $where[] = "a.departamento_id = ?"; $params[] = (int)$deptId; }
if ($tipoExt !== 'all' && preg_match('/^[a-z0-9,]+$/i', $tipoExt)) {
    $extList = explode(',', $tipoExt);
    $extPh   = implode(',', array_fill(0, count($extList), '?'));
    $where[] = "LOWER(SUBSTRING_INDEX(a.archivo, '.', -1)) IN ($extPh)";
    foreach ($extList as $e) { $params[] = strtolower($e); }
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

// Contar total
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM archivos_departamento a LEFT JOIN departamentos d ON a.departamento_id = d.id $whereSql");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();
$totalPaginas = max(1, ceil($total / $perPage));
$offset = ($pagina - 1) * $perPage;

// Obtener archivos
$sql = "SELECT a.*, d.nombre AS departamento_nombre, d.color AS dept_color
        FROM archivos_departamento a
        LEFT JOIN departamentos d ON a.departamento_id = d.id
        $whereSql
        ORDER BY a.fecha_creacion DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$archivos = $stmt->fetchAll();

// Helper para icono y color por extensión
function getFileMeta($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $map = [
        'pdf'  => ['fa-file-pdf',        '#e53935', 'pdf'],
        'doc'  => ['fa-file-word',       '#1976d2', 'doc'],
        'docx' => ['fa-file-word',       '#1976d2', 'doc'],
        'xls'  => ['fa-file-excel',      '#43a047', 'xls'],
        'xlsx' => ['fa-file-excel',      '#43a047', 'xls'],
        'csv'  => ['fa-file-csv',        '#43a047', 'xls'],
        'ppt'  => ['fa-file-powerpoint', '#f57c00', 'ppt'],
        'pptx' => ['fa-file-powerpoint', '#f57c00', 'ppt'],
        'jpg'  => ['fa-file-image',      '#9c27b0', 'img'],
        'jpeg' => ['fa-file-image',      '#9c27b0', 'img'],
        'png'  => ['fa-file-image',      '#9c27b0', 'img'],
        'gif'  => ['fa-file-image',      '#9c27b0', 'img'],
        'webp' => ['fa-file-image',      '#9c27b0', 'img'],
        'zip'  => ['fa-file-zipper',     '#607d8b', 'zip'],
        'rar'  => ['fa-file-zipper',     '#607d8b', 'zip'],
        '7z'   => ['fa-file-zipper',     '#607d8b', 'zip'],
        'txt'  => ['fa-file-lines',      '#455a64', 'txt'],
        'md'   => ['fa-file-lines',      '#455a64', 'txt'],
        'mp4'  => ['fa-file-video',      '#e91e63', 'vid'],
        'avi'  => ['fa-file-video',      '#e91e63', 'vid'],
        'mov'  => ['fa-file-video',      '#e91e63', 'vid'],
        'wmv'  => ['fa-file-video',      '#e91e63', 'vid'],
        'mp3'  => ['fa-file-audio',      '#00838f', 'aud'],
        'wav'  => ['fa-file-audio',      '#00838f', 'aud'],
        'ogg'  => ['fa-file-audio',      '#00838f', 'aud'],
    ];
    return $map[$ext] ?? ['fa-file', '#757575', 'gen'];
}

// Tamaño de archivo legible
function fileSizeHuman($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes/1024, 1) . ' KB';
    if ($bytes < 1073741824) return round($bytes/1048576, 1) . ' MB';
    return round($bytes/1073741824, 1) . ' GB';
}

// ¿Es archivo nuevo (últimos 7 días)?
function esArchivoNuevo($fecha) {
    if (!$fecha) return false;
    try {
        $t = strtotime($fecha);
        return $t !== false && (time() - $t) < (7 * 24 * 60 * 60);
    } catch (Exception $e) { return false; }
}

// URL absoluta para Office Online Viewer
function urlAbsoluta($relativa) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir  = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\');
    return $scheme . '://' . $host . $dir . '/' . ltrim($relativa, '/');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repositorio de Archivos - Intranet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo date('YmdHis'); ?>">
    <style>
        body { font-family: 'Inter', sans-serif; }

        /* ====== HERO ====== */
        .files-hero {
            position: relative;
            padding: 50px 40px 60px;
            background: linear-gradient(135deg, #1565c0 0%, #1976d2 50%, #42a5f5 100%);
            overflow: hidden;
        }
        .files-hero::before { content: ''; position: absolute; top: -100px; right: -80px; width: 350px; height: 350px; border: 2px solid rgba(255,255,255,0.07); border-radius: 50%; }
        .files-hero::after { content: ''; position: absolute; bottom: -120px; left: -120px; width: 300px; height: 300px; background: radial-gradient(circle, rgba(255,255,255,0.1), transparent 60%); border-radius: 50%; }
        .files-hero-inner { max-width: 1400px; margin: 0 auto; position: relative; z-index: 2; }
        .files-breadcrumb { font-size: 0.75rem; color: rgba(255,255,255,0.85); letter-spacing: 2px; text-transform: uppercase; margin-bottom: 18px; }
        .files-breadcrumb a { color: rgba(255,255,255,0.9); text-decoration: none; }
        .files-breadcrumb a:hover { color: white; }
        .files-hero h1 { font-family: 'Playfair Display', serif; font-size: clamp(2rem, 4vw, 3rem); font-weight: 800; color: white; margin-bottom: 10px; letter-spacing: -0.5px; }
        .files-hero p { color: rgba(255,255,255,0.9); font-size: 1rem; max-width: 600px; line-height: 1.55; }
        .files-stats { display: flex; gap: 22px; margin-top: 22px; flex-wrap: wrap; }
        .files-stat { background: rgba(255,255,255,0.18); backdrop-filter: blur(10px); padding: 10px 18px; border-radius: 10px; color: white; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 8px; }
        .files-stat strong { font-size: 1.05rem; }

        /* ====== FILTROS ====== */
        .filters-bar {
            max-width: 1400px;
            margin: -35px auto 0;
            padding: 0 30px;
            position: relative; z-index: 5;
        }
        .filters-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 16px 40px rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.08);
            display: grid;
            grid-template-columns: 1.7fr 1fr 1fr auto;
            gap: 12px;
            align-items: end;
        }
        .filter-group label { display: block; font-size: 0.7rem; color: var(--text-muted); font-weight: 600; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 1px; }
        .filter-input, .filter-select {
            width: 100%;
            padding: 11px 14px;
            background: var(--bg-input);
            border: 1.5px solid rgba(255,255,255,0.1);
            border-radius: 9px;
            color: var(--text-primary);
            font-size: 0.92rem;
            transition: border-color 0.2s;
        }
        .filter-input:focus, .filter-select:focus { outline: none; border-color: var(--accent-blue); }
        .filter-input { padding-left: 38px; }
        .filter-search-wrap { position: relative; }
        .filter-search-wrap i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; }
        .filter-btn { background: var(--accent-blue); color: white; border: none; padding: 11px 22px; border-radius: 9px; font-weight: 600; cursor: pointer; font-size: 0.88rem; transition: background 0.2s; display: inline-flex; align-items: center; gap: 8px; }
        .filter-btn:hover { background: #1565c0; }
        .filter-clear { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; background: rgba(255,255,255,0.08); border-radius: 20px; font-size: 0.75rem; color: var(--text-secondary); text-decoration: none; margin-left: 8px; }
        .filter-clear:hover { background: rgba(255,255,255,0.15); color: white; }

        @media (max-width: 880px) {
            .filters-card { grid-template-columns: 1fr; }
        }

        /* ====== GRID DE ARCHIVOS ====== */
        .files-container { max-width: 1400px; margin: 32px auto 50px; padding: 0 30px; }
        .files-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px; }
        .files-toolbar-info { color: var(--text-muted); font-size: 0.88rem; }
        .files-toolbar-info strong { color: var(--text-primary); }
        .files-quick-filters { display: flex; gap: 8px; flex-wrap: wrap; }
        .files-quick-filter { padding: 6px 14px; background: var(--bg-card); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; font-size: 0.75rem; color: var(--text-secondary); text-decoration: none; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .files-quick-filter:hover, .files-quick-filter.active { border-color: var(--accent-blue); background: rgba(25,118,210,0.15); color: var(--accent-blue); }

        .files-grid-pub { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 18px; }
        .file-card-pub {
            background: var(--bg-card);
            border-radius: 14px;
            padding: 20px 18px;
            transition: transform 0.3s, box-shadow 0.3s, border-color 0.3s;
            border: 1px solid rgba(255,255,255,0.06);
            display: flex; flex-direction: column;
            position: relative;
            overflow: hidden;
        }
        .file-card-pub::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 4px;
            background: var(--accent-color, var(--accent-blue));
        }
        .file-card-pub:hover {
            transform: translateY(-5px);
            box-shadow: 0 18px 40px rgba(0,0,0,0.45);
            border-color: rgba(255,255,255,0.18);
        }
        .file-card-pub .new-badge {
            position: absolute;
            top: 12px; right: 12px;
            background: linear-gradient(135deg, #FF1744, #D50000);
            color: white;
            font-size: 0.58rem;
            font-weight: 800;
            letter-spacing: 1.5px;
            padding: 4px 9px;
            border-radius: 20px;
            text-transform: uppercase;
            box-shadow: 0 4px 12px rgba(213,0,0,0.5);
            animation: newPulse 2s ease-in-out infinite;
            z-index: 3;
        }
        .file-card-pub .new-badge i { font-size: 0.55rem; margin-right: 2px; }
        @keyframes newPulse {
            0%, 100% { transform: scale(1); box-shadow: 0 4px 12px rgba(213,0,0,0.5); }
            50% { transform: scale(1.06); box-shadow: 0 6px 18px rgba(213,0,0,0.75); }
        }
        .file-card-icon {
            width: 60px; height: 60px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.7rem;
            margin-bottom: 14px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.25);
        }
        .file-card-name {
            font-size: 0.92rem; font-weight: 600; color: var(--text-primary);
            margin-bottom: 6px; line-height: 1.35;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
            overflow: hidden; min-height: 2.5em;
        }
        .file-card-dept { display: inline-flex; align-items: center; gap: 5px; font-size: 0.7rem; color: var(--text-muted); margin-bottom: 4px; }
        .file-card-ext-tag {
            font-size: 0.62rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;
            padding: 2px 8px; border-radius: 4px; background: rgba(255,255,255,0.07); color: var(--text-secondary);
            display: inline-block; margin-bottom: 10px;
        }
        .file-card-desc { font-size: 0.76rem; color: var(--text-muted); line-height: 1.45; margin-bottom: 14px; flex: 1; }
        .file-card-actions { display: flex; gap: 6px; margin-top: auto; }
        .file-card-btn {
            flex: 1;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }
        .file-card-btn.view { background: rgba(25,118,210,0.18); color: #64b5f6; border: 1px solid rgba(25,118,210,0.4); }
        .file-card-btn.view:hover { background: rgba(25,118,210,0.3); }
        .file-card-btn.download { background: rgba(255,255,255,0.08); color: var(--text-secondary); border: 1px solid rgba(255,255,255,0.1); }
        .file-card-btn.download:hover { background: rgba(255,255,255,0.15); color: white; }

        /* ====== EMPTY STATE ====== */
        .empty-state-files { text-align: center; padding: 70px 20px; color: var(--text-muted); }
        .empty-state-files i { font-size: 4rem; opacity: 0.25; display: block; margin-bottom: 18px; }
        .empty-state-files h3 { font-size: 1.1rem; color: var(--text-secondary); margin-bottom: 8px; }

        /* ====== PAGINATION ====== */
        .pagination-files { display: flex; justify-content: center; gap: 6px; margin-top: 40px; flex-wrap: wrap; }
        .pagination-files a, .pagination-files span {
            padding: 8px 14px;
            background: var(--bg-card);
            color: var(--text-secondary);
            border-radius: 8px;
            font-size: 0.85rem;
            text-decoration: none;
            border: 1px solid rgba(255,255,255,0.06);
            transition: all 0.2s;
        }
        .pagination-files a:hover { background: var(--bg-card-hover); border-color: var(--accent-blue); color: white; }
        .pagination-files .active { background: var(--accent-blue); color: white; border-color: var(--accent-blue); font-weight: 700; }
    </style>
</head>
<body>
    <!-- HEADER del index -->
    <header class="header">
        <div class="header-content">
            <div class="logo-text"><img src="assets/img/logo.png" alt="Logo" style="height:45px;" onerror="this.style.display='none'"></div>
            <nav><a href="index.php" style="color: white; text-decoration: none; font-weight: 500;"><i class="fas fa-home"></i> Inicio</a></nav>
        </div>
    </header>

    <!-- HERO -->
    <section class="files-hero">
        <div class="files-hero-inner">
            <div class="files-breadcrumb"><a href="index.php"><i class="fas fa-home"></i> Inicio</a> &nbsp;&rsaquo;&nbsp; <span style="color:white;">Repositorio de Archivos</span></div>
            <h1>Repositorio de Archivos</h1>
            <p>Consulta, visualiza y descarga todos los documentos corporativos organizados por departamento.</p>
            <div class="files-stats">
                <div class="files-stat"><i class="fas fa-folder-open"></i> <strong><?php echo $total; ?></strong> archivos disponibles</div>
                <div class="files-stat"><i class="fas fa-building"></i> <strong><?php echo count($departamentos); ?></strong> departamentos</div>
            </div>
        </div>
    </section>

    <!-- FILTROS -->
    <div class="filters-bar">
        <form class="filters-card" method="GET" id="filtersForm">
            <div class="filter-group">
                <label><i class="fas fa-search"></i> Buscar por nombre o descripción</label>
                <div class="filter-search-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" name="q" class="filter-input" placeholder="Ej: Manual, Procedimiento, Reporte..." value="<?php echo htmlspecialchars($busqueda); ?>" data-testid="filter-search">
                </div>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-building"></i> Departamento</label>
                <select name="dept" class="filter-select" data-testid="filter-dept">
                    <option value="all">Todos los departamentos</option>
                    <?php foreach ($departamentos as $d): ?>
                    <option value="<?php echo $d['id']; ?>" <?php echo $deptId == $d['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-filter"></i> Tipo de archivo</label>
                <select name="tipo" class="filter-select" data-testid="filter-tipo">
                    <option value="all">Todos los tipos</option>
                    <option value="pdf" <?php echo $tipoExt=='pdf'?'selected':''; ?>>📕 PDF</option>
                    <option value="doc,docx" <?php echo $tipoExt=='doc,docx'?'selected':''; ?>>📘 Word</option>
                    <option value="xls,xlsx,csv" <?php echo $tipoExt=='xls,xlsx,csv'?'selected':''; ?>>📗 Excel</option>
                    <option value="ppt,pptx" <?php echo $tipoExt=='ppt,pptx'?'selected':''; ?>>📙 PowerPoint</option>
                    <option value="jpg,jpeg,png,gif,webp" <?php echo $tipoExt=='jpg,jpeg,png,gif,webp'?'selected':''; ?>>🖼️ Imágenes</option>
                    <option value="zip,rar,7z" <?php echo $tipoExt=='zip,rar,7z'?'selected':''; ?>>🗜️ Comprimidos</option>
                </select>
            </div>
            <button type="submit" class="filter-btn" data-testid="filter-submit"><i class="fas fa-filter"></i> Filtrar</button>
        </form>
    </div>

    <!-- CONTENIDO -->
    <main class="files-container">
        <div class="files-toolbar">
            <div class="files-toolbar-info">
                <strong><?php echo $total; ?></strong> archivo<?php echo $total != 1 ? 's' : ''; ?> encontrado<?php echo $total != 1 ? 's' : ''; ?>
                <?php if ($busqueda !== '' || $deptId !== 'all' || $tipoExt !== 'all'): ?>
                    <a href="archivos.php" class="filter-clear"><i class="fas fa-times"></i> Limpiar filtros</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (count($archivos) > 0): ?>
        <div class="files-grid-pub">
            <?php foreach ($archivos as $a):
                [$icon, $color, $cls] = getFileMeta($a['archivo']);
                $ext = strtolower(pathinfo($a['archivo'], PATHINFO_EXTENSION));
                $fileUrl = 'assets/uploads/files/' . $a['archivo'];
                $absPath = __DIR__ . '/assets/uploads/files/' . $a['archivo'];
                $fileSize = (file_exists($absPath) ? filesize($absPath) : 0);
                $esNuevo = esArchivoNuevo($a['fecha_creacion'] ?? null);
                $esOffice = in_array($ext, ['doc','docx','xls','xlsx','ppt','pptx']);
                $urlAbs = $esOffice ? urlAbsoluta($fileUrl) : '';
            ?>
            <div class="file-card-pub" style="--accent-color: <?php echo $color; ?>;" data-testid="file-card-<?php echo $a['id']; ?>">
                <?php if ($esNuevo): ?><span class="new-badge" data-testid="badge-new-<?php echo $a['id']; ?>"><i class="fas fa-bolt"></i> Nuevo</span><?php endif; ?>
                <div class="file-card-icon" style="background: <?php echo $color; ?>;"><i class="fas <?php echo $icon; ?>"></i></div>
                <div class="file-card-name" title="<?php echo htmlspecialchars($a['nombre']); ?>"><?php echo htmlspecialchars($a['nombre']); ?></div>
                <?php if (!empty($a['departamento_nombre'])): ?>
                <div class="file-card-dept"><i class="fas fa-folder"></i> <?php echo htmlspecialchars($a['departamento_nombre']); ?></div>
                <?php endif; ?>
                <span class="file-card-ext-tag" style="color: <?php echo $color; ?>; background: rgba(<?php echo implode(',', sscanf($color, '#%02x%02x%02x')); ?>, 0.15);"><?php echo strtoupper($ext); ?> <?php if ($fileSize > 0): ?>&middot; <?php echo fileSizeHuman($fileSize); ?><?php endif; ?></span>
                <?php if (!empty($a['descripcion'])): ?>
                <div class="file-card-desc"><?php echo htmlspecialchars(mb_substr($a['descripcion'], 0, 80)) . (mb_strlen($a['descripcion']) > 80 ? '...' : ''); ?></div>
                <?php endif; ?>
                <div class="file-card-actions">
                    <?php if ($ext === 'pdf'): ?>
                    <button class="file-card-btn view" onclick="openPdfModal('<?php echo $fileUrl; ?>','<?php echo htmlspecialchars($a['nombre'], ENT_QUOTES); ?>')" data-testid="file-view-pdf-<?php echo $a['id']; ?>"><i class="fas fa-eye"></i> Ver</button>
                    <?php elseif (in_array($ext, ['jpg','jpeg','png','gif','webp'])): ?>
                    <button class="file-card-btn view" onclick="openImageModalPub('<?php echo $fileUrl; ?>','<?php echo htmlspecialchars($a['nombre'], ENT_QUOTES); ?>')" data-testid="file-view-img-<?php echo $a['id']; ?>"><i class="fas fa-eye"></i> Ver</button>
                    <?php elseif ($esOffice): ?>
                    <button class="file-card-btn view" onclick="openOfficeModal('<?php echo addslashes($urlAbs); ?>','<?php echo htmlspecialchars($a['nombre'], ENT_QUOTES); ?>','<?php echo $fileUrl; ?>')" data-testid="file-view-office-<?php echo $a['id']; ?>"><i class="fas fa-eye"></i> Vista previa</button>
                    <?php else: ?>
                    <a href="<?php echo $fileUrl; ?>" target="_blank" rel="noopener" class="file-card-btn view" data-testid="file-view-other-<?php echo $a['id']; ?>"><i class="fas fa-external-link-alt"></i> Abrir</a>
                    <?php endif; ?>
                    <a href="download.php?id=<?php echo $a['id']; ?>" class="file-card-btn download" title="Descargar" data-testid="file-download-<?php echo $a['id']; ?>"><i class="fas fa-download"></i></a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Paginación -->
        <?php if ($totalPaginas > 1):
            $qsBase = http_build_query(array_filter(['q'=>$busqueda, 'dept'=>$deptId!='all'?$deptId:null, 'tipo'=>$tipoExt!='all'?$tipoExt:null]));
            $qsPrefix = $qsBase ? $qsBase . '&' : '';
        ?>
        <div class="pagination-files">
            <?php if ($pagina > 1): ?>
            <a href="?<?php echo $qsPrefix; ?>p=<?php echo $pagina - 1; ?>"><i class="fas fa-chevron-left"></i></a>
            <?php endif; ?>
            <?php
            $start = max(1, $pagina - 2);
            $end = min($totalPaginas, $pagina + 2);
            if ($start > 1) { echo '<a href="?' . $qsPrefix . 'p=1">1</a>'; if ($start > 2) echo '<span>...</span>'; }
            for ($p = $start; $p <= $end; $p++):
            ?>
            <?php if ($p == $pagina): ?><span class="active"><?php echo $p; ?></span><?php else: ?><a href="?<?php echo $qsPrefix; ?>p=<?php echo $p; ?>"><?php echo $p; ?></a><?php endif; ?>
            <?php endfor;
            if ($end < $totalPaginas) { if ($end < $totalPaginas - 1) echo '<span>...</span>'; echo '<a href="?' . $qsPrefix . 'p=' . $totalPaginas . '">' . $totalPaginas . '</a>'; }
            ?>
            <?php if ($pagina < $totalPaginas): ?>
            <a href="?<?php echo $qsPrefix; ?>p=<?php echo $pagina + 1; ?>"><i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="empty-state-files">
            <i class="fas fa-folder-open"></i>
            <h3>No se encontraron archivos</h3>
            <p>Intenta cambiar los filtros o limpiar la búsqueda.</p>
            <?php if ($busqueda !== '' || $deptId !== 'all' || $tipoExt !== 'all'): ?>
            <p><a href="archivos.php" class="filter-clear" style="margin-top:18px;display:inline-flex;"><i class="fas fa-times"></i> Limpiar filtros</a></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </main>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Automotriz Corp. | <a href="index.php" style="color:var(--text-muted);text-decoration:none;">Volver al inicio</a></p>
    </footer>

    <script>
    // ====== MODAL PDF (sin descarga) ======
    function openPdfModal(url, title) {
        var m = document.getElementById('pdfModal');
        if (!m) {
            m = document.createElement('div');
            m.id = 'pdfModal';
            m.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.92);z-index:9999;display:flex;flex-direction:column;';
            document.body.appendChild(m);
        }
        m.innerHTML =
            '<div style="display:flex;justify-content:space-between;align-items:center;padding:14px 22px;background:#1a1a1a;color:white;border-bottom:1px solid #333;flex-wrap:wrap;gap:10px;">' +
                '<div style="display:flex;align-items:center;gap:12px;font-weight:600;font-size:0.95rem;"><i class="fas fa-file-pdf" style="color:#e53935;font-size:1.4rem;"></i> ' + title + '</div>' +
                '<div style="display:flex;gap:10px;">' +
                    '<a href="' + url + '" target="_blank" rel="noopener" style="background:rgba(255,255,255,0.1);color:white;padding:8px 14px;border-radius:8px;text-decoration:none;font-size:0.8rem;"><i class="fas fa-external-link-alt"></i> Pestaña nueva</a>' +
                    '<a href="' + url + '" download style="background:rgba(255,255,255,0.1);color:white;padding:8px 14px;border-radius:8px;text-decoration:none;font-size:0.8rem;"><i class="fas fa-download"></i> Descargar</a>' +
                    '<button onclick="closePdfModal()" style="background:#e53935;color:white;border:none;padding:8px 16px;border-radius:8px;cursor:pointer;font-size:0.8rem;"><i class="fas fa-times"></i> Cerrar</button>' +
                '</div>' +
            '</div>' +
            '<iframe src="' + url + '#toolbar=1&navpanes=0" style="flex:1;width:100%;border:none;background:#525659;" data-testid="pdf-iframe"></iframe>';
        m.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closePdfModal() {
        var m = document.getElementById('pdfModal');
        if (m) { m.style.display = 'none'; m.innerHTML = ''; }
        document.body.style.overflow = '';
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            var m = document.getElementById('pdfModal');
            if (m && m.style.display === 'flex') closePdfModal();
            var mi = document.getElementById('imgModalPub');
            if (mi && mi.style.display === 'flex') closeImageModalPub();
            var mo = document.getElementById('officeModal');
            if (mo && mo.style.display === 'flex') closeOfficeModal();
        }
    });

    // Modal de imagen
    function openImageModalPub(url, title) {
        var m = document.getElementById('imgModalPub');
        if (!m) {
            m = document.createElement('div');
            m.id = 'imgModalPub';
            m.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.92);z-index:9999;display:flex;align-items:center;justify-content:center;padding:30px;flex-direction:column;';
            m.addEventListener('click', function(e) { if (e.target === m) closeImageModalPub(); });
            document.body.appendChild(m);
        }
        m.innerHTML =
            '<div style="position:absolute;top:20px;right:30px;display:flex;gap:10px;z-index:2;">' +
                '<a href="' + url + '" download style="background:rgba(255,255,255,0.15);color:white;padding:8px 14px;border-radius:8px;text-decoration:none;font-size:0.8rem;"><i class="fas fa-download"></i></a>' +
                '<button onclick="closeImageModalPub()" style="background:#e53935;color:white;border:none;padding:8px 14px;border-radius:8px;cursor:pointer;font-size:0.85rem;"><i class="fas fa-times"></i></button>' +
            '</div>' +
            '<img src="' + url + '" style="max-width:100%;max-height:85vh;border-radius:10px;box-shadow:0 20px 60px rgba(0,0,0,0.7);">' +
            '<div style="color:white;margin-top:18px;font-size:0.95rem;">' + title + '</div>';
        m.style.display = 'flex';
    }
    function closeImageModalPub() { var m = document.getElementById('imgModalPub'); if (m) m.style.display = 'none'; }

    // ====== MODAL OFFICE ONLINE (Word / Excel / PowerPoint) ======
    function openOfficeModal(absoluteUrl, title, localUrl) {
        var m = document.getElementById('officeModal');
        if (!m) {
            m = document.createElement('div');
            m.id = 'officeModal';
            m.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.92);z-index:9999;display:flex;flex-direction:column;';
            document.body.appendChild(m);
        }
        var isLocalHost = /^(http:\/\/)?(localhost|127\.0\.0\.1|192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/i.test(absoluteUrl);
        var officeUrl = 'https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(absoluteUrl);
        var warning = isLocalHost
            ? '<div style="background:#fff3cd;color:#664d03;padding:12px 22px;font-size:0.82rem;border-bottom:1px solid #ffeeba;display:flex;align-items:center;gap:10px;"><i class="fas fa-exclamation-triangle"></i> El servidor parece privado/local. Office Online Viewer necesita acceder al archivo desde Internet. Si no carga, usa "Descargar" o "Abrir externamente".</div>'
            : '';
        m.innerHTML =
            '<div style="display:flex;justify-content:space-between;align-items:center;padding:14px 22px;background:#1a1a1a;color:white;border-bottom:1px solid #333;flex-wrap:wrap;gap:10px;">' +
                '<div style="display:flex;align-items:center;gap:12px;font-weight:600;font-size:0.95rem;"><i class="fas fa-file-word" style="color:#1976d2;font-size:1.4rem;"></i> ' + title + '</div>' +
                '<div style="display:flex;gap:10px;flex-wrap:wrap;">' +
                    '<a href="' + localUrl + '" target="_blank" rel="noopener" style="background:rgba(255,255,255,0.1);color:white;padding:8px 14px;border-radius:8px;text-decoration:none;font-size:0.8rem;"><i class="fas fa-external-link-alt"></i> Abrir externamente</a>' +
                    '<a href="' + localUrl + '" download style="background:rgba(255,255,255,0.1);color:white;padding:8px 14px;border-radius:8px;text-decoration:none;font-size:0.8rem;"><i class="fas fa-download"></i> Descargar</a>' +
                    '<button onclick="closeOfficeModal()" style="background:#e53935;color:white;border:none;padding:8px 16px;border-radius:8px;cursor:pointer;font-size:0.8rem;"><i class="fas fa-times"></i> Cerrar</button>' +
                '</div>' +
            '</div>' +
            warning +
            '<iframe src="' + officeUrl + '" style="flex:1;width:100%;border:none;background:#fff;" data-testid="office-iframe"></iframe>';
        m.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closeOfficeModal() {
        var m = document.getElementById('officeModal');
        if (m) { m.style.display = 'none'; m.innerHTML = ''; }
        document.body.style.overflow = '';
    }
    </script>
</body>
</html>
