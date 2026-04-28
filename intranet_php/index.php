<?php
/**
 * Página Principal - Intranet Corporativa (v4)
 */
require_once 'includes/config.php';
require_once 'includes/functions.php';

$sliderNoticias = getSliderNoticias($pdo);
$aplicaciones = getAplicaciones($pdo);
$departamentos = getDepartamentos($pdo);
$galeriaFotos = getGaleriaFotos($pdo, 30);
$videos = getVideos($pdo, 20);
$cuentaRegresiva = getCuentaRegresiva($pdo);
$avisos = getAvisosActivos($pdo);
$infoCompania = getInfoCompania($pdo);

// Cumpleañeros
$cumpleaneros = getCumpleanerosMes($pdo);
$cumpleanerosHoy = getCumpleanerosHoy($pdo);
$allBirthdays = array_merge($cumpleanerosHoy, array_filter($cumpleaneros, function($c) {
    return date('d', strtotime($c['fecha_nacimiento'])) != date('d');
}));

// Aniversarios laborales del mes
$aniversarios = $pdo->query("SELECT e.*, d.nombre as departamento_nombre FROM empleados_cumpleanos e LEFT JOIN departamentos d ON e.departamento_id = d.id WHERE e.activo = 1 AND e.fecha_ingreso IS NOT NULL AND MONTH(e.fecha_ingreso) = MONTH(CURDATE()) ORDER BY DAY(e.fecha_ingreso) ASC")->fetchAll();

// Eventos paginados
$evPage = max(1, (int)($_GET['ev_page'] ?? 1));
$evPerPage = 4; $evOffset = ($evPage - 1) * $evPerPage;
$totalEventos = $pdo->query("SELECT COUNT(*) FROM eventos WHERE activo = 1")->fetchColumn();
$totalEvPages = max(1, ceil($totalEventos / $evPerPage));
$stmtEv = $pdo->prepare("SELECT e.*, d.nombre as dept_nombre, d.color as dept_color FROM eventos e LEFT JOIN departamentos d ON e.departamento_id = d.id WHERE e.activo = 1 ORDER BY e.fecha_evento DESC LIMIT ? OFFSET ?");
$stmtEv->execute([$evPerPage, $evOffset]);
$eventosPage = $stmtEv->fetchAll();

// Artículos paginados
$artPage = max(1, (int)($_GET['art_page'] ?? 1));
$artPerPage = 4; $artOffset = ($artPage - 1) * $artPerPage;
$totalArt = $pdo->query("SELECT COUNT(*) FROM articulos WHERE activo = 1")->fetchColumn();
$totalArtPages = max(1, ceil($totalArt / $artPerPage));
$stmtArt = $pdo->prepare("SELECT * FROM articulos WHERE activo = 1 ORDER BY fecha_publicacion DESC LIMIT ? OFFSET ?");
$stmtArt->execute([$artPerPage, $artOffset]);
$articulosPage = $stmtArt->fetchAll();

// Archivos paginados
$filePage = max(1, (int)($_GET['file_page'] ?? 1));
$filePerPage = 6; $fileOffset = ($filePage - 1) * $filePerPage;
$fileDept = $_GET['dept'] ?? 'all';
$fw = "WHERE a.activo = 1"; $fp = [];
if ($fileDept !== 'all') { $fw .= " AND a.departamento_id = ?"; $fp[] = (int)$fileDept; }
$stmtFC = $pdo->prepare("SELECT COUNT(*) FROM archivos_departamento a $fw"); $stmtFC->execute($fp);
$totalFiles = $stmtFC->fetchColumn(); $totalFilePages = max(1, ceil($totalFiles / $filePerPage));
$stmtF = $pdo->prepare("SELECT a.*, d.nombre as departamento_nombre FROM archivos_departamento a LEFT JOIN departamentos d ON a.departamento_id = d.id $fw ORDER BY a.fecha_creacion DESC LIMIT $filePerPage OFFSET $fileOffset");
$stmtF->execute($fp); $archivosPage = $stmtF->fetchAll();

// Portales paginados
$pPage = max(1, (int)($_GET['p_page'] ?? 1));
$pPerPage = 4; $pOffset = ($pPage - 1) * $pPerPage;
$totalP = $pdo->query("SELECT COUNT(*) FROM portales_clientes WHERE activo = 1")->fetchColumn();
$totalPPages = max(1, ceil($totalP / $pPerPage));
$stmtP = $pdo->prepare("SELECT * FROM portales_clientes WHERE activo = 1 ORDER BY orden ASC LIMIT ? OFFSET ?");
$stmtP->execute([$pPerPage, $pOffset]); $portalesPage = $stmtP->fetchAll();

// KPIs con filtro mes/año
$kpiMes = $_GET['kpi_mes'] ?? date('m');
$kpiAnio = $_GET['kpi_anio'] ?? date('Y');
$stmtKpi = $pdo->prepare("SELECT k.*, d.nombre as dept_nombre FROM kpis_departamento k LEFT JOIN departamentos d ON k.departamento_id = d.id WHERE k.activo = 1 AND k.mes = ? AND k.anio = ? ORDER BY d.nombre, k.nombre");
$stmtKpi->execute([(int)$kpiMes, (int)$kpiAnio]);
$kpis = $stmtKpi->fetchAll();

// Organigrama activo
$organigrama = $pdo->query("SELECT * FROM organigrama WHERE activo = 1 ORDER BY id DESC LIMIT 1")->fetch();

$mesesEsp = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
    /* Countdown animation */
    @keyframes glow { 0%,100%{opacity:1;text-shadow:0 0 10px rgba(229,57,53,0.8);} 50%{opacity:0.7;text-shadow:0 0 25px rgba(229,57,53,1);} }
    .cd-num-glow { animation: glow 2s ease-in-out infinite; }
    .cd-box-red { background: var(--accent-red) !important; }
    /* Aviso outline */
    .aviso-outline { border: 2px solid; border-radius: 8px; padding: 10px 18px; display: flex; align-items: center; gap: 12px; margin-bottom: 8px; background: transparent !important; }
    .aviso-outline.info { border-color: #1976D2; color: #1976D2; }
    .aviso-outline.warning { border-color: #FF9800; color: #FF9800; }
    .aviso-outline.danger { border-color: #E53935; color: #E53935; }
    .aviso-outline.success { border-color: #43A047; color: #43A047; }
    .aviso-outline strong { color: var(--text-primary); }
    .aviso-outline p { color: var(--text-secondary); }
    /* Anniversary card */
    .aniv-card { min-width: 220px; background: linear-gradient(135deg, #1a237e, #283593); border-radius: 12px; padding: 18px; flex-shrink: 0; text-align: center; color: white; cursor: pointer; transition: transform 0.3s; }
    .aniv-card:hover { transform: scale(1.03); }
    </style>
</head>
<body>

    <!-- HEADER FIXED -->
    <header class="header" style="background: url('assets/img/fondo1.png') center/cover; position:fixed;top:0;left:0;right:0;z-index:1000;">
        <div style="position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.65);"></div>
        <div class="header-content" style="position:relative;z-index:1;">
            <div class="logo-text">
                <h1>AUTOMOTRIZ CORP</h1>
                <span>INYECCI&Oacute;N &bull; CROMADO &bull; PINTURA</span>
            </div>
            <div style="display:flex;align-items:center;gap:25px;">
                <div style="text-align:right;">
                    <div id="headerClock" style="font-size:2rem;font-weight:700;letter-spacing:2px;"></div>
                    <div id="headerDate" style="font-size:0.8rem;color:var(--text-secondary);"></div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;background:rgba(255,255,255,0.1);padding:10px 16px;border-radius:10px;">
                    <i class="fas fa-cloud-sun" style="font-size:1.5rem;color:#FFD54F;"></i>
                    <div>
                        <div id="weatherTemp" style="font-size:1.2rem;font-weight:700;">--&deg;C</div>
                        <div id="weatherDesc" style="font-size:0.65rem;color:var(--text-secondary);">Clima</div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;background:rgba(255,255,255,0.1);padding:10px 16px;border-radius:10px;">
                    <i class="fas fa-dollar-sign" style="font-size:1.3rem;color:#4CAF50;"></i>
                    <div>
                        <div id="exchangeRate" style="font-size:1.1rem;font-weight:700;">--</div>
                        <div style="font-size:0.6rem;color:var(--text-secondary);">USD/MXN</div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <div style="height:90px;"></div>

    <!-- AVISOS con fade automático -->
    <?php if (count($avisos) > 0): ?>
    <div style="max-width:1200px;margin:0 auto;padding:8px 40px 0;">
        <div style="position:relative;min-height:50px;">
            <?php foreach ($avisos as $i => $aviso):
                $tipoColors = ['info'=>'#1976D2','warning'=>'#FF9800','danger'=>'#E53935','success'=>'#43A047'];
                $tipoColor = $tipoColors[$aviso['tipo']] ?? '#1976D2';
            ?>
            <div class="aviso-fade-item" style="<?php echo $i > 0 ? 'opacity:0;position:absolute;top:0;left:0;right:0;' : 'opacity:1;'; ?>transition:opacity 0.8s ease;border:2px solid <?php echo $tipoColor; ?>;border-radius:8px;padding:10px 18px;display:flex;align-items:center;gap:12px;background:transparent;color:<?php echo $tipoColor; ?>;">
                <i class="fas fa-bullhorn"></i>
                <div style="flex:1;"><strong style="color:var(--text-primary);"><?php echo htmlspecialchars($aviso['titulo']); ?></strong>
                <?php if ($aviso['contenido']): ?><p style="font-size:0.8rem;margin-top:2px;color:var(--text-secondary);"><?php echo htmlspecialchars($aviso['contenido']); ?></p><?php endif; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- SLIDER -->
    <section class="slider-section">
        <div class="slider-container">
            <?php if (count($sliderNoticias) > 0): foreach ($sliderNoticias as $i => $slide): ?>
            <div class="slide <?php echo $i === 0 ? 'active' : ''; ?>"><img src="assets/uploads/slider/<?php echo $slide['imagen']; ?>" alt=""><div class="slide-content"><h2><?php echo htmlspecialchars($slide['titulo']); ?></h2><p><?php echo htmlspecialchars($slide['descripcion']); ?></p></div></div>
            <?php endforeach; else: ?>
            <div class="slide active" style="background:url('assets/img/fondo1.png') center/cover;"><div class="slide-content"><h2>Bienvenidos</h2><p>Configure desde administraci&oacute;n</p></div></div>
            <?php endif; ?>
            <button class="slider-nav-btn prev"><i class="fas fa-chevron-left"></i></button>
            <button class="slider-nav-btn next"><i class="fas fa-chevron-right"></i></button>
            <div class="slider-dots"><?php for ($i = 0; $i < max(count($sliderNoticias), 1); $i++): ?><span class="dot <?php echo $i === 0 ? 'active' : ''; ?>"></span><?php endfor; ?></div>
        </div>
    </section>

    <main class="main-content">

        <!-- ROW 1: Eventos + Apps + Countdown -->
        <div class="grid-2-col-left">
            <div class="section-card">
                <div class="section-header" style="justify-content:space-between;"><span><i class="fas fa-calendar-check"></i> Eventos</span><a href="calendario.php" style="font-size:0.75rem;color:var(--accent-blue);text-decoration:none;">Ver calendario completo <i class="fas fa-arrow-right"></i></a></div>
                <div style="padding:15px 20px 20px;">
                    <?php foreach ($eventosPage as $ev): $isPast = strtotime($ev['fecha_evento']) < strtotime('today'); ?>
                    <a href="evento.php?id=<?php echo $ev['id']; ?>" style="text-decoration:none;color:inherit;display:flex;gap:12px;padding:12px;background:var(--bg-input);border-radius:8px;margin-bottom:10px;border-left:4px solid <?php echo $ev['dept_color'] ?: $ev['color']; ?>;<?php echo $isPast ? 'opacity:0.6;' : ''; ?>">
                        <div class="evento-fecha"><span class="dia"><?php echo date('d', strtotime($ev['fecha_evento'])); ?></span><span class="mes"><?php echo strtoupper(substr($mesesEsp[(int)date('m', strtotime($ev['fecha_evento']))], 0, 3)); ?></span></div>
                        <div style="flex:1;"><h5 style="font-size:0.85rem;font-weight:600;"><?php echo htmlspecialchars($ev['titulo']); ?></h5><p style="font-size:0.72rem;color:var(--text-muted);"><?php if ($ev['hora_inicio']): ?><i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($ev['hora_inicio'])); ?> <?php endif; ?><?php if ($ev['lugar']): ?><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($ev['lugar']); ?><?php endif; ?> <?php if ($ev['dept_nombre']): ?>&bull; <?php echo htmlspecialchars($ev['dept_nombre']); ?><?php endif; ?></p></div>
                        <?php if ($isPast): ?><span style="font-size:0.65rem;color:var(--text-muted);align-self:center;">Pasado</span><?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                    <?php if ($totalEvPages > 1): ?><div style="display:flex;justify-content:center;gap:5px;margin-top:10px;"><?php for ($p = 1; $p <= $totalEvPages; $p++): ?><a href="?ev_page=<?php echo $p; ?>" style="padding:5px 12px;border-radius:6px;font-size:0.8rem;text-decoration:none;<?php echo $p == $evPage ? 'background:var(--accent-red);color:white;' : 'background:var(--bg-input);color:var(--text-secondary);'; ?>"><?php echo $p; ?></a><?php endfor; ?></div><?php endif; ?>
                    <?php if (count($eventosPage) === 0): ?><p style="color:var(--text-muted);font-size:0.85rem;">Sin eventos</p><?php endif; ?>
                </div>
            </div>
            <div>
                <!-- Apps -->
                <div class="section-card" style="margin-bottom:20px;">
                    <div class="section-header"><i class="fas fa-th"></i> Aplicaciones R&aacute;pidas</div>
                    <div class="apps-grid"><?php foreach ($aplicaciones as $app): ?>
                        <?php if ($app['url'] === 'http://192.168.1.2/a' && $organigrama): ?>
                        <a href="javascript:void(0)" onclick="openImageModal('assets/uploads/company/<?php echo $organigrama['imagen']; ?>','Organigrama Corporativo')" class="app-card" style="background:<?php echo $app['color']; ?>;"><i class="fas <?php echo $app['icono']; ?>"></i><span><?php echo htmlspecialchars($app['nombre']); ?></span></a>
                        <?php else: ?>
                        <a href="<?php echo htmlspecialchars($app['url']); ?>" target="_blank" class="app-card" style="background:<?php echo $app['color']; ?>;"><i class="fas <?php echo $app['icono']; ?>"></i><span><?php echo htmlspecialchars($app['nombre']); ?></span></a>
                        <?php endif; ?>
                    <?php endforeach; ?></div>
                </div>
                <!-- Countdown con efecto -->
                <?php if ($cuentaRegresiva): ?>
                <div class="section-card" style="padding:20px;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:15px;"><i class="fas fa-clock" style="color:var(--accent-green);"></i><strong style="font-size:0.95rem;"><?php echo htmlspecialchars($cuentaRegresiva['titulo']); ?></strong></div>
                    <div class="countdown-timer" data-target="<?php echo $cuentaRegresiva['fecha_evento']; ?>" style="display:flex;gap:12px;">
                        <div class="countdown-box cd-box-red" style="flex:1;text-align:center;padding:12px;border-radius:8px;"><span class="countdown-num cd-num-glow" id="cd-days" style="font-size:1.8rem;font-weight:800;">0</span><span class="countdown-label" style="font-size:0.65rem;color:rgba(255,255,255,0.8);">D&iacute;as</span></div>
                        <div class="countdown-box cd-box-red" style="flex:1;text-align:center;padding:12px;border-radius:8px;"><span class="countdown-num cd-num-glow" id="cd-hours" style="font-size:1.8rem;font-weight:800;">00</span><span class="countdown-label" style="font-size:0.65rem;color:rgba(255,255,255,0.8);">Horas</span></div>
                        <div class="countdown-box cd-box-red" style="flex:1;text-align:center;padding:12px;border-radius:8px;"><span class="countdown-num cd-num-glow" id="cd-mins" style="font-size:1.8rem;font-weight:800;">00</span><span class="countdown-label" style="font-size:0.65rem;color:rgba(255,255,255,0.8);">Min</span></div>
                        <div class="countdown-box cd-box-red" style="flex:1;text-align:center;padding:12px;border-radius:8px;"><span class="countdown-num cd-num-glow" id="cd-secs" style="font-size:1.8rem;font-weight:800;">00</span><span class="countdown-label" style="font-size:0.65rem;color:rgba(255,255,255,0.8);">Seg</span></div>
                    </div>
                    <?php if ($cuentaRegresiva['descripcion']): ?><p style="text-align:center;font-size:0.78rem;color:var(--text-muted);margin-top:10px;"><?php echo htmlspecialchars($cuentaRegresiva['descripcion']); ?></p><?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ROW 2: Cumpleaños slider + Aniversarios slider -->
        <div class="grid-2-col">
            <div class="section-card">
                <div class="section-header"><i class="fas fa-birthday-cake"></i> Cumplea&ntilde;os</div>
                <div style="position:relative;overflow:hidden;padding:15px 20px 20px;">
                    <div id="bdTrack" style="display:flex;gap:15px;transition:transform 0.5s;"><?php if (count($allBirthdays) > 0): foreach ($allBirthdays as $c): ?>
                    <div style="min-width:220px;background:linear-gradient(135deg,#e65100,#ff9800);border-radius:12px;padding:18px;flex-shrink:0;text-align:center;color:white;cursor:pointer;transition:transform 0.3s;" onclick="openBirthdayCard('<?php echo htmlspecialchars($c['nombre_completo'], ENT_QUOTES); ?>','<?php echo htmlspecialchars($c['departamento_nombre'] ?? '', ENT_QUOTES); ?>','<?php echo htmlspecialchars($c['puesto'] ?? '', ENT_QUOTES); ?>','assets/uploads/employees/<?php echo $c['foto'] ?: 'default.png'; ?>')" onmouseover="this.style.transform='scale(1.03)'" onmouseout="this.style.transform='scale(1)'">
                        <img src="assets/uploads/employees/<?php echo $c['foto'] ?: 'default.png'; ?>" style="width:60px;height:60px;border-radius:50%;object-fit:cover;border:3px solid white;margin-bottom:10px;" onerror="this.src='assets/img/default-avatar.svg'">
                        <div style="font-weight:600;"><?php echo htmlspecialchars($c['nombre_completo']); ?></div>
                        <div style="font-size:0.75rem;opacity:0.9;"><?php echo htmlspecialchars($c['departamento_nombre'] ?? ''); ?></div>
                        <div style="font-size:0.7rem;opacity:0.8;margin-top:5px;"><?php echo date('d', strtotime($c['fecha_nacimiento'])); ?> de este mes</div>
                    </div>
                    <?php endforeach; else: ?><p style="color:var(--text-muted);font-size:0.85rem;">Sin cumplea&ntilde;os</p><?php endif; ?></div>
                    <?php if (count($allBirthdays) > 2): ?><button onclick="slideTrack('bdTrack',-1)" style="position:absolute;left:5px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.7);border:none;color:white;width:28px;height:28px;border-radius:50%;cursor:pointer;z-index:5;"><i class="fas fa-chevron-left"></i></button><button onclick="slideTrack('bdTrack',1)" style="position:absolute;right:5px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.7);border:none;color:white;width:28px;height:28px;border-radius:50%;cursor:pointer;z-index:5;"><i class="fas fa-chevron-right"></i></button><?php endif; ?>
                </div>
            </div>
            <div class="section-card">
                <div class="section-header"><i class="fas fa-award"></i> Aniversarios Laborales</div>
                <div style="position:relative;overflow:hidden;padding:15px 20px 20px;">
                    <div id="anivTrack" style="display:flex;gap:15px;transition:transform 0.5s;"><?php if (count($aniversarios) > 0): foreach ($aniversarios as $a): $anos = date('Y') - date('Y', strtotime($a['fecha_ingreso'])); ?>
                    <div class="aniv-card" onclick="openAnivCard('<?php echo htmlspecialchars($a['nombre_completo'], ENT_QUOTES); ?>','<?php echo htmlspecialchars($a['departamento_nombre'] ?? '', ENT_QUOTES); ?>','<?php echo htmlspecialchars($a['puesto'] ?? '', ENT_QUOTES); ?>','assets/uploads/employees/<?php echo $a['foto'] ?: 'default.png'; ?>',<?php echo $anos; ?>)">
                        <img src="assets/uploads/employees/<?php echo $a['foto'] ?: 'default.png'; ?>" style="width:60px;height:60px;border-radius:50%;object-fit:cover;border:3px solid gold;margin-bottom:10px;" onerror="this.src='assets/img/default-avatar.svg'">
                        <div style="font-weight:600;"><?php echo htmlspecialchars($a['nombre_completo']); ?></div>
                        <div style="font-size:0.75rem;opacity:0.8;"><?php echo $anos; ?> a&ntilde;o<?php echo $anos != 1 ? 's' : ''; ?> en la empresa</div>
                        <div style="font-size:0.7rem;opacity:0.7;"><?php echo date('d', strtotime($a['fecha_ingreso'])); ?> de este mes</div>
                    </div>
                    <?php endforeach; else: ?><p style="color:var(--text-muted);font-size:0.85rem;">Sin aniversarios este mes</p><?php endif; ?></div>
                    <?php if (count($aniversarios) > 2): ?><button onclick="slideTrack('anivTrack',-1)" style="position:absolute;left:5px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.7);border:none;color:white;width:28px;height:28px;border-radius:50%;cursor:pointer;z-index:5;"><i class="fas fa-chevron-left"></i></button><button onclick="slideTrack('anivTrack',1)" style="position:absolute;right:5px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.7);border:none;color:white;width:28px;height:28px;border-radius:50%;cursor:pointer;z-index:5;"><i class="fas fa-chevron-right"></i></button><?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ROW 3: Noticias paginadas -->
        <div class="section-card" style="margin-bottom:25px;">
            <div class="section-header" style="padding:18px 20px;"><i class="fas fa-newspaper"></i> Noticias y Art&iacute;culos</div>
            <div style="padding:15px 20px 20px;">
                <?php if (count($articulosPage) > 0): ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:15px;">
                    <?php foreach ($articulosPage as $art): ?>
                    <a href="articulo.php?id=<?php echo $art['id']; ?>" style="text-decoration:none;color:inherit;background:var(--bg-input);border-radius:10px;overflow:hidden;">
                        <?php if ($art['imagen']): ?><img src="assets/uploads/articles/<?php echo $art['imagen']; ?>" style="width:100%;height:140px;object-fit:cover;"><?php else: ?><div style="height:140px;background:#222;display:flex;align-items:center;justify-content:center;"><i class="fas fa-newspaper" style="font-size:2rem;color:#444;"></i></div><?php endif; ?>
                        <div style="padding:14px;"><div style="font-size:0.65rem;color:var(--text-muted);margin-bottom:5px;"><?php echo formatearFecha($art['fecha_publicacion']); ?></div><div style="font-size:0.9rem;font-weight:600;margin-bottom:5px;"><?php echo htmlspecialchars($art['titulo']); ?></div><div style="font-size:0.78rem;color:var(--text-muted);"><?php echo truncarTexto(strip_tags($art['contenido']), 80); ?></div></div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php if ($totalArtPages > 1): ?><div style="display:flex;justify-content:center;gap:5px;margin-top:15px;"><?php for ($p = 1; $p <= $totalArtPages; $p++): ?><a href="?art_page=<?php echo $p; ?>" style="padding:5px 12px;border-radius:6px;font-size:0.8rem;text-decoration:none;<?php echo $p == $artPage ? 'background:var(--accent-red);color:white;' : 'background:var(--bg-input);color:var(--text-secondary);'; ?>"><?php echo $p; ?></a><?php endfor; ?></div><?php endif; ?>
                <?php else: ?><p style="color:var(--text-muted);">Sin noticias</p><?php endif; ?>
            </div>
        </div>

        <!-- ROW 4: Galería slider + Videos slider -->
        <div class="media-grid">
            <div class="section-card">
                <div class="section-header" style="justify-content:space-between;"><span><i class="fas fa-images"></i> Galer&iacute;a de Fotos</span><a href="galeria_fotos.php" style="font-size:0.75rem;color:var(--accent-blue);text-decoration:none;">Ver por departamento <i class="fas fa-arrow-right"></i></a></div>
                <div style="position:relative;overflow:hidden;">
                    <div id="galleryTrack" style="display:flex;gap:8px;padding:15px 20px 20px;transition:transform 0.5s;">
                        <?php if (count($galeriaFotos) > 0): foreach ($galeriaFotos as $foto): ?>
                        <div style="min-width:160px;height:110px;flex-shrink:0;border-radius:8px;overflow:hidden;cursor:pointer;" onclick="openImageModal('assets/uploads/gallery/<?php echo $foto['imagen']; ?>','<?php echo htmlspecialchars($foto['titulo'] ?? '', ENT_QUOTES); ?>')"><img src="assets/uploads/gallery/<?php echo $foto['imagen']; ?>" style="width:100%;height:100%;object-fit:cover;transition:transform 0.3s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'"></div>
                        <?php endforeach; else: ?><p style="color:var(--text-muted);font-size:0.85rem;">Sin fotos</p><?php endif; ?>
                    </div>
                    <?php if (count($galeriaFotos) > 3): ?><button onclick="slideTrack('galleryTrack',-1)" style="position:absolute;left:5px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.7);border:none;color:white;width:28px;height:28px;border-radius:50%;cursor:pointer;z-index:5;"><i class="fas fa-chevron-left"></i></button><button onclick="slideTrack('galleryTrack',1)" style="position:absolute;right:5px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.7);border:none;color:white;width:28px;height:28px;border-radius:50%;cursor:pointer;z-index:5;"><i class="fas fa-chevron-right"></i></button><?php endif; ?>
                </div>
            </div>
            <div class="section-card">
                <div class="section-header"><i class="fas fa-video"></i> Videos</div>
                <div style="position:relative;overflow:hidden;">
                    <div id="videoTrack" style="display:flex;gap:10px;padding:15px 20px 20px;transition:transform 0.5s;">
                        <?php if (count($videos) > 0): foreach ($videos as $vid): ?>
                        <div style="min-width:200px;flex-shrink:0;border-radius:8px;overflow:hidden;position:relative;height:120px;background:#111;cursor:pointer;" onclick="openVideoModal('assets/uploads/videos/<?php echo $vid['archivo_video']; ?>','<?php echo htmlspecialchars($vid['titulo'], ENT_QUOTES); ?>')">
                            <?php if ($vid['thumbnail']): ?><img src="assets/uploads/videos/<?php echo $vid['thumbnail']; ?>" style="width:100%;height:100%;object-fit:cover;"><?php else: ?><div style="width:100%;height:100%;background:#222;display:flex;align-items:center;justify-content:center;"><i class="fas fa-film" style="font-size:2rem;color:#555;"></i></div><?php endif; ?>
                            <div class="video-play-icon"><i class="fas fa-play"></i></div><div class="video-overlay"><?php echo htmlspecialchars($vid['titulo']); ?></div>
                        </div>
                        <?php endforeach; else: ?><p style="color:var(--text-muted);font-size:0.85rem;">Sin videos</p><?php endif; ?>
                    </div>
                    <?php if (count($videos) > 2): ?><button onclick="slideTrack('videoTrack',-1)" style="position:absolute;left:5px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.7);border:none;color:white;width:28px;height:28px;border-radius:50%;cursor:pointer;z-index:5;"><i class="fas fa-chevron-left"></i></button><button onclick="slideTrack('videoTrack',1)" style="position:absolute;right:5px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.7);border:none;color:white;width:28px;height:28px;border-radius:50%;cursor:pointer;z-index:5;"><i class="fas fa-chevron-right"></i></button><?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ROW 6: Archivos paginados -->
        <div class="section-card files-card">
            <div class="files-header"><div class="files-header-left"><i class="fas fa-folder-open"></i> Archivos por Departamento</div>
                <form method="GET"><select name="dept" class="dept-select" onchange="this.form.submit()"><option value="all">Todos</option><?php foreach ($departamentos as $d): ?><option value="<?php echo $d['id']; ?>" <?php echo $fileDept==$d['id']?'selected':''; ?>><?php echo htmlspecialchars($d['nombre']); ?></option><?php endforeach; ?></select></form>
            </div>
            <div class="files-grid">
                <?php if (count($archivosPage) > 0): foreach ($archivosPage as $a): $ext = strtolower(pathinfo($a['archivo'], PATHINFO_EXTENSION)); $ic = in_array($ext,['pdf'])?'pdf':(in_array($ext,['doc','docx'])?'doc':(in_array($ext,['xls','xlsx'])?'xls':'')); ?>
                <div class="file-card"><div class="file-icon-box <?php echo $ic; ?>"><i class="fas fa-file"></i></div><div class="file-details"><div class="file-name"><?php echo htmlspecialchars($a['nombre']); ?></div><div class="file-meta"><?php echo htmlspecialchars($a['departamento_nombre']); ?> &bull; <?php echo strtoupper($ext); ?></div></div><a href="download.php?id=<?php echo $a['id']; ?>" class="file-download"><i class="fas fa-download"></i></a></div>
                <?php endforeach; else: ?><p style="color:var(--text-muted);padding:15px;grid-column:span 3;font-size:0.85rem;">No hay archivos</p><?php endif; ?>
            </div>
            <?php if ($totalFilePages > 1): ?><div style="display:flex;justify-content:center;gap:5px;padding:0 20px 20px;"><?php for ($p = 1; $p <= $totalFilePages; $p++): ?><a href="?dept=<?php echo $fileDept; ?>&file_page=<?php echo $p; ?>" style="padding:5px 12px;border-radius:6px;font-size:0.8rem;text-decoration:none;<?php echo $p==$filePage?'background:var(--accent-red);color:white;':'background:var(--bg-input);color:var(--text-secondary);'; ?>"><?php echo $p; ?></a><?php endfor; ?></div><?php endif; ?>
        </div>

        <!-- ROW 7: Compañía + Portales -->
        <div class="bottom-grid">
            <div class="section-card">
                <div class="section-header"><i class="fas fa-building"></i> Nuestra Compa&ntilde;&iacute;a</div>
                <div class="company-body">
                    <p class="company-desc">Empresa automotriz dedicada a la inyecci&oacute;n, cromado y pintura de piezas pl&aacute;sticas automotrices.</p>
                    <?php $iconos=['mision'=>'fa-bullseye','vision'=>'fa-eye','valores'=>'fa-heart']; $colores=['mision'=>'#E53935','vision'=>'#43A047','valores'=>'#FF9800'];
                    foreach ($infoCompania as $info): ?>
                    <a href="compania_detalle.php?s=<?php echo $info['seccion']; ?>" style="text-decoration:none;color:inherit;display:block;" class="company-value">
                        <div class="company-value-header"><i class="fas <?php echo $iconos[$info['seccion']] ?? 'fa-info-circle'; ?>" style="color:<?php echo $colores[$info['seccion']] ?? '#1976D2'; ?>;"></i><span style="color:<?php echo $colores[$info['seccion']] ?? '#1976D2'; ?>;"><?php echo strtoupper($info['seccion']); ?></span>
                        <?php if (!empty($info['archivo_pdf'])): ?><span style="margin-left:auto;font-size:0.7rem;color:var(--accent-blue);"><i class="fas fa-file-pdf"></i> PDF</span><?php endif; ?>
                        <i class="fas fa-chevron-right" style="margin-left:auto;font-size:0.7rem;color:var(--text-muted);"></i>
                        </div>
                        <p><?php echo truncarTexto(strip_tags($info['contenido']), 150); ?></p>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="section-card">
                <div class="section-header"><i class="fas fa-globe"></i> Portales de Clientes</div>
                <div class="portal-list">
                    <?php foreach ($portalesPage as $portal): ?>
                    <a href="<?php echo htmlspecialchars($portal['url']); ?>" target="_blank" class="portal-item"><div class="portal-icon"><i class="fas fa-globe"></i></div><div class="portal-info"><div class="portal-name"><?php echo htmlspecialchars($portal['nombre']); ?></div><div class="portal-desc">Acceso al portal</div></div><span class="portal-arrow"><i class="fas fa-external-link-alt"></i></span></a>
                    <?php endforeach; ?>
                    <?php if ($totalPPages > 1): ?><div style="display:flex;justify-content:center;gap:5px;margin-top:10px;"><?php for ($p = 1; $p <= $totalPPages; $p++): ?><a href="?p_page=<?php echo $p; ?>" style="padding:5px 12px;border-radius:6px;font-size:0.8rem;text-decoration:none;<?php echo $p==$pPage?'background:var(--accent-red);color:white;':'background:var(--bg-input);color:var(--text-secondary);'; ?>"><?php echo $p; ?></a><?php endfor; ?></div><?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ROW 8: Conversor de divisas + KPIs paginados por departamento -->
        <div class="grid-2-col">
            <div class="section-card">
                <div class="section-header"><i class="fas fa-exchange-alt"></i> Conversor de Divisas</div>
                <div style="padding:20px;">
                    <div style="display:flex;gap:15px;align-items:end;flex-wrap:wrap;">
                        <div style="flex:1;min-width:120px;"><label style="font-size:0.75rem;color:var(--text-muted);display:block;margin-bottom:5px;">Cantidad</label><input type="number" id="convAmount" value="1" min="0" step="0.01" style="width:100%;padding:10px;background:var(--bg-input);border:1px solid var(--border-color);border-radius:8px;color:white;font-size:1rem;"></div>
                        <div style="flex:1;min-width:100px;"><label style="font-size:0.75rem;color:var(--text-muted);display:block;margin-bottom:5px;">De</label><select id="convFrom" style="width:100%;padding:10px;background:var(--bg-input);border:1px solid var(--border-color);border-radius:8px;color:white;"><option value="USD">USD</option><option value="MXN">MXN</option><option value="EUR">EUR</option></select></div>
                        <div style="flex:1;min-width:100px;"><label style="font-size:0.75rem;color:var(--text-muted);display:block;margin-bottom:5px;">A</label><select id="convTo" style="width:100%;padding:10px;background:var(--bg-input);border:1px solid var(--border-color);border-radius:8px;color:white;"><option value="MXN">MXN</option><option value="USD">USD</option><option value="EUR">EUR</option></select></div>
                        <button onclick="convertCurrency()" style="padding:10px 20px;background:var(--accent-blue);color:white;border:none;border-radius:8px;cursor:pointer;font-weight:600;">Convertir</button>
                    </div>
                    <div id="convResult" style="margin-top:15px;font-size:1.3rem;font-weight:700;text-align:center;color:var(--accent-green);"></div>
                </div>
            </div>
            <div class="section-card">
                <div class="section-header" style="justify-content:space-between;flex-wrap:wrap;gap:10px;">
                    <span><i class="fas fa-chart-line"></i> KPI's por Departamento</span>
                    <form method="GET" style="display:flex;gap:8px;align-items:center;">
                        <select name="kpi_mes" class="dept-select" style="font-size:0.75rem;" onchange="this.form.submit()">
                            <?php foreach ($mesesEsp as $num => $nom): ?><option value="<?php echo $num; ?>" <?php echo $kpiMes == $num ? 'selected' : ''; ?>><?php echo $nom; ?></option><?php endforeach; ?>
                        </select>
                        <select name="kpi_anio" class="dept-select" style="font-size:0.75rem;" onchange="this.form.submit()">
                            <?php for ($y = date('Y') + 1; $y >= date('Y') - 3; $y--): ?><option value="<?php echo $y; ?>" <?php echo $kpiAnio == $y ? 'selected' : ''; ?>><?php echo $y; ?></option><?php endfor; ?>
                        </select>
                    </form>
                </div>
                <div style="padding:15px 20px 20px;max-height:350px;overflow-y:auto;">
                    <?php if (count($kpis) > 0):
                        $currentDept = '';
                        foreach ($kpis as $k):
                            if ($k['dept_nombre'] !== $currentDept):
                                $currentDept = $k['dept_nombre'];
                    ?>
                    <div style="font-size:0.75rem;font-weight:700;color:var(--accent-blue);text-transform:uppercase;letter-spacing:1px;margin:12px 0 8px;padding-top:8px;border-top:1px solid var(--border-color);"><?php echo htmlspecialchars($currentDept ?? 'General'); ?></div>
                    <?php endif; ?>
                    <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;background:var(--bg-input);border-radius:8px;margin-bottom:6px;">
                        <?php if ($k['imagen']): ?>
                        <img src="assets/uploads/kpis/<?php echo $k['imagen']; ?>" style="width:40px;height:40px;object-fit:cover;border-radius:6px;cursor:pointer;flex-shrink:0;" onclick="openImageModal('assets/uploads/kpis/<?php echo $k['imagen']; ?>','<?php echo htmlspecialchars($k['nombre'], ENT_QUOTES); ?>')">
                        <?php else: ?>
                        <div style="width:40px;height:40px;background:var(--accent-green);border-radius:6px;display:flex;align-items:center;justify-content:center;color:white;font-size:0.75rem;flex-shrink:0;"><i class="fas fa-chart-bar"></i></div>
                        <?php endif; ?>
                        <div style="flex:1;min-width:0;"><div style="font-size:0.8rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($k['nombre']); ?></div><div style="font-size:0.6rem;color:var(--text-muted);"><?php echo $mesesEsp[$k['mes']] ?? ''; ?> <?php echo $k['anio']; ?></div></div>
                        <?php if ($k['imagen']): ?><span onclick="openImageModal('assets/uploads/kpis/<?php echo $k['imagen']; ?>','<?php echo htmlspecialchars($k['nombre'], ENT_QUOTES); ?>')" style="color:var(--accent-blue);font-size:0.8rem;cursor:pointer;" title="Ver imagen"><i class="fas fa-search-plus"></i></span><?php endif; ?>
                        <?php if ($k['archivo']): ?><a href="assets/uploads/kpis/<?php echo $k['archivo']; ?>" download style="color:var(--text-muted);font-size:0.8rem;" title="Descargar"><i class="fas fa-download"></i></a><?php endif; ?>
                    </div>
                    <?php endforeach; else: ?>
                    <p style="color:var(--text-muted);font-size:0.85rem;">Sin KPIs para <?php echo $mesesEsp[(int)$kpiMes]; ?> <?php echo $kpiAnio; ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </main>

    <footer class="footer"><p>&copy; <?php echo date('Y'); ?> Automotriz Corp. | <a href="admin/login.php" style="color:var(--text-muted);text-decoration:none;">Administraci&oacute;n</a></p></footer>

    <script>
    // SLIDER
    (function(){const s=document.querySelectorAll('.slide'),d=document.querySelectorAll('.dot');if(s.length<=1)return;let c=0,t;function show(i){s.forEach(x=>x.classList.remove('active'));d.forEach(x=>x.classList.remove('active'));c=(i+s.length)%s.length;s[c].classList.add('active');if(d[c])d[c].classList.add('active')}function go(){t=setInterval(()=>show(c+1),5000)}function r(){clearInterval(t);go()}document.querySelector('.slider-nav-btn.next')?.addEventListener('click',()=>{show(c+1);r()});document.querySelector('.slider-nav-btn.prev')?.addEventListener('click',()=>{show(c-1);r()});d.forEach((x,i)=>x.addEventListener('click',()=>{show(i);r()}));go()})();

    // COUNTDOWN
    (function(){const el=document.querySelector('.countdown-timer');if(!el||!el.dataset.target)return;const t=new Date(el.dataset.target).getTime();function u(){const d=t-Date.now();if(d<0)return;document.getElementById('cd-days').textContent=Math.floor(d/864e5);document.getElementById('cd-hours').textContent=String(Math.floor(d%864e5/36e5)).padStart(2,'0');document.getElementById('cd-mins').textContent=String(Math.floor(d%36e5/6e4)).padStart(2,'0');document.getElementById('cd-secs').textContent=String(Math.floor(d%6e4/1e3)).padStart(2,'0')}u();setInterval(u,1000)})();

    // SLIDER TRACKS
    const tp={};function slideTrack(id,dir){const t=document.getElementById(id);if(!t)return;if(!tp[id])tp[id]=0;const w=t.firstElementChild?t.firstElementChild.offsetWidth+15:200;const mx=-(t.scrollWidth-t.parentElement.offsetWidth+40);tp[id]+=dir*-w*2;if(tp[id]>0)tp[id]=0;if(tp[id]<mx)tp[id]=mx;t.style.transform='translateX('+tp[id]+'px)';}

    // CLOCK
    function uc(){const n=new Date();document.getElementById('headerClock').textContent=String(n.getHours()).padStart(2,'0')+':'+String(n.getMinutes()).padStart(2,'0')+':'+String(n.getSeconds()).padStart(2,'0');const d=['Domingo','Lunes','Martes','Mi\u00e9rcoles','Jueves','Viernes','S\u00e1bado'],m=['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];document.getElementById('headerDate').textContent=d[n.getDay()]+', '+n.getDate()+' de '+m[n.getMonth()]+' de '+n.getFullYear();}uc();setInterval(uc,1000);

    // WEATHER (JSON)
    fetch('https://wttr.in/?format=j1').then(r=>r.json()).then(d=>{try{document.getElementById('weatherTemp').textContent=d.current_condition[0].temp_C+'\u00b0C';document.getElementById('weatherDesc').textContent=d.current_condition[0].lang_es?d.current_condition[0].lang_es[0].value:d.current_condition[0].weatherDesc[0].value;}catch(e){}}).catch(()=>{});

    // EXCHANGE RATE
    fetch('https://open.er-api.com/v6/latest/USD').then(r=>r.json()).then(d=>{if(d.rates&&d.rates.MXN){document.getElementById('exchangeRate').textContent='$'+d.rates.MXN.toFixed(2);window._rates=d.rates;}}).catch(()=>{document.getElementById('exchangeRate').textContent='--';});

    // CURRENCY CONVERTER
    function convertCurrency(){if(!window._rates)return;const a=parseFloat(document.getElementById('convAmount').value)||0;const f=document.getElementById('convFrom').value;const t=document.getElementById('convTo').value;const inUSD=f==='USD'?a:a/window._rates[f];const result=inUSD*window._rates[t];document.getElementById('convResult').textContent=a.toFixed(2)+' '+f+' = '+result.toFixed(2)+' '+t;}

    // MODALS
    function openImageModal(s,t){let m=document.getElementById('imgM');if(!m){m=document.createElement('div');m.id='imgM';m.style.cssText='position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.95);z-index:9999;display:flex;align-items:center;justify-content:center;flex-direction:column;cursor:pointer;';m.innerHTML='<button onclick="this.parentElement.style.display=\'none\'" style="position:absolute;top:20px;right:20px;background:none;border:none;color:white;font-size:2rem;cursor:pointer;"><i class="fas fa-times"></i></button><img id="mImg" style="max-width:90%;max-height:80%;border-radius:10px;"><p id="mCap" style="color:white;margin-top:15px;"></p>';m.addEventListener('click',function(e){if(e.target===m)m.style.display='none';});document.body.appendChild(m);}document.getElementById('mImg').src=s;document.getElementById('mCap').textContent=t;m.style.display='flex';}

    function openVideoModal(s,t){let m=document.getElementById('vidM');if(!m){m=document.createElement('div');m.id='vidM';m.style.cssText='position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.95);z-index:9999;display:flex;align-items:center;justify-content:center;flex-direction:column;';m.innerHTML='<button onclick="closeVM()" style="position:absolute;top:20px;right:20px;background:none;border:none;color:white;font-size:2rem;cursor:pointer;"><i class="fas fa-times"></i></button><video id="mVid" controls autoplay style="max-width:90%;max-height:75%;border-radius:10px;"></video><p id="mVT" style="color:white;margin-top:15px;"></p>';m.addEventListener('click',function(e){if(e.target===m)closeVM();});document.body.appendChild(m);}document.getElementById('mVid').src=s;document.getElementById('mVT').textContent=t;m.style.display='flex';}
    function closeVM(){const m=document.getElementById('vidM'),v=document.getElementById('mVid');if(v){v.pause();v.src='';}if(m)m.style.display='none';}

    // Birthday / Anniversary card modal
    function openBirthdayCard(name,dept,puesto,foto){let m=document.getElementById('bdM');if(!m){m=document.createElement('div');m.id='bdM';m.style.cssText='position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.9);z-index:9999;display:flex;align-items:center;justify-content:center;';m.addEventListener('click',function(e){if(e.target===m)m.style.display='none';});document.body.appendChild(m);}
    m.innerHTML='<div style="background:linear-gradient(135deg,#667eea,#764ba2);border-radius:20px;padding:40px;text-align:center;max-width:400px;color:white;position:relative;"><button onclick="document.getElementById(\'bdM\').style.display=\'none\'" style="position:absolute;top:10px;right:15px;background:none;border:none;color:white;font-size:1.5rem;cursor:pointer;"><i class="fas fa-times"></i></button><div style="font-size:3rem;margin-bottom:15px;">&#127874;</div><img src="'+foto+'" style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:4px solid white;margin-bottom:15px;" onerror="this.src=\'assets/img/default-avatar.svg\'"><h2 style="margin-bottom:5px;">'+name+'</h2><p style="opacity:0.9;">'+puesto+'</p><p style="opacity:0.8;font-size:0.9rem;">'+dept+'</p><div style="margin-top:20px;padding:15px;background:rgba(255,255,255,0.2);border-radius:10px;font-style:italic;">\u00a1Feliz cumplea\u00f1os '+name.split(' ')[0]+'! Que tengas un excelente d\u00eda lleno de alegr\u00eda.</div></div>';m.style.display='flex';}

    function openAnivCard(name,dept,puesto,foto,anos){let m=document.getElementById('anivM');if(!m){m=document.createElement('div');m.id='anivM';m.style.cssText='position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.9);z-index:9999;display:flex;align-items:center;justify-content:center;';m.addEventListener('click',function(e){if(e.target===m)m.style.display='none';});document.body.appendChild(m);}
    m.innerHTML='<div style="background:linear-gradient(135deg,#1a237e,#283593);border-radius:20px;padding:40px;text-align:center;max-width:400px;color:white;position:relative;"><button onclick="document.getElementById(\'anivM\').style.display=\'none\'" style="position:absolute;top:10px;right:15px;background:none;border:none;color:white;font-size:1.5rem;cursor:pointer;"><i class="fas fa-times"></i></button><div style="font-size:3rem;margin-bottom:15px;">&#127942;</div><img src="'+foto+'" style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:4px solid gold;margin-bottom:15px;" onerror="this.src=\'assets/img/default-avatar.svg\'"><h2 style="margin-bottom:5px;">'+name+'</h2><p style="opacity:0.9;">'+puesto+'</p><p style="opacity:0.8;font-size:0.9rem;">'+dept+'</p><div style="font-size:2.5rem;font-weight:800;margin:15px 0;color:gold;">'+anos+' A\u00f1o'+(anos!=1?'s':'')+'</div><div style="padding:15px;background:rgba(255,255,255,0.2);border-radius:10px;font-style:italic;">\u00a1Felicidades '+name.split(' ')[0]+' por tu aniversario en la empresa!</div></div>';m.style.display='flex';}

    // AUTO-SLIDER para galerías y videos
    function autoSlide(trackId, intervalMs) {
        const track = document.getElementById(trackId);
        if (!track || !track.firstElementChild) return;
        if (!tp[trackId]) tp[trackId] = 0;
        const itemW = track.firstElementChild.offsetWidth + 15;
        const maxScroll = -(track.scrollWidth - track.parentElement.offsetWidth + 40);
        setInterval(() => {
            tp[trackId] -= itemW;
            if (tp[trackId] < maxScroll) tp[trackId] = 0;
            track.style.transform = 'translateX(' + tp[trackId] + 'px)';
        }, intervalMs);
    }
    autoSlide('galleryTrack', 3000);
    autoSlide('videoTrack', 4000);
    autoSlide('bdTrack', 3500);
    autoSlide('anivTrack', 4000);

    // AVISOS FADE
    (function(){
        const items = document.querySelectorAll('.aviso-fade-item');
        if (items.length <= 1) return;
        let cur = 0;
        setInterval(() => {
            items[cur].style.opacity = '0';
            items[cur].style.position = 'absolute';
            cur = (cur + 1) % items.length;
            items[cur].style.position = 'relative';
            items[cur].style.opacity = '1';
        }, 4000);
    })();

    document.addEventListener('keydown',function(e){if(e.key==='Escape'){['imgM','vidM','bdM','anivM'].forEach(id=>{const m=document.getElementById(id);if(m)m.style.display='none';});closeVM();}});
    </script>
</body>
</html>
