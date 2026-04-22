<?php
/**
 * Página Principal - Intranet Corporativa (v3)
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

// Cumpleañeros del mes (todos para slider)
$cumpleaneros = getCumpleanerosMes($pdo);
$cumpleanerosHoy = getCumpleanerosHoy($pdo);
$allBirthdays = array_merge($cumpleanerosHoy, array_filter($cumpleaneros, function($c) {
    return date('d', strtotime($c['fecha_nacimiento'])) != date('d');
}));

// Eventos con paginación
$evPage = max(1, (int)($_GET['ev_page'] ?? 1));
$evPerPage = 4;
$evOffset = ($evPage - 1) * $evPerPage;
$totalEventos = $pdo->query("SELECT COUNT(*) FROM eventos WHERE activo = 1")->fetchColumn();
$totalEvPages = ceil($totalEventos / $evPerPage);
$eventosPage = $pdo->prepare("SELECT e.*, d.nombre as dept_nombre, d.color as dept_color FROM eventos e LEFT JOIN departamentos d ON e.departamento_id = d.id WHERE e.activo = 1 ORDER BY e.fecha_evento DESC LIMIT ? OFFSET ?");
$eventosPage->execute([$evPerPage, $evOffset]);
$eventosPage = $eventosPage->fetchAll();

// Artículos con paginación
$artPage = max(1, (int)($_GET['art_page'] ?? 1));
$artPerPage = 4;
$artOffset = ($artPage - 1) * $artPerPage;
$totalArticulos = $pdo->query("SELECT COUNT(*) FROM articulos WHERE activo = 1")->fetchColumn();
$totalArtPages = ceil($totalArticulos / $artPerPage);
$articulosPage = $pdo->prepare("SELECT * FROM articulos WHERE activo = 1 ORDER BY fecha_publicacion DESC LIMIT ? OFFSET ?");
$articulosPage->execute([$artPerPage, $artOffset]);
$articulosPage = $articulosPage->fetchAll();

// Archivos con paginación
$filePage = max(1, (int)($_GET['file_page'] ?? 1));
$filePerPage = 6;
$fileOffset = ($filePage - 1) * $filePerPage;
$fileDept = $_GET['dept'] ?? 'all';
$fileWhere = "WHERE a.activo = 1";
$fileParams = [];
if ($fileDept !== 'all') {
    $fileWhere .= " AND a.departamento_id = ?";
    $fileParams[] = (int)$fileDept;
}
$totalFiles = $pdo->prepare("SELECT COUNT(*) FROM archivos_departamento a $fileWhere");
$totalFiles->execute($fileParams);
$totalFiles = $totalFiles->fetchColumn();
$totalFilePages = ceil($totalFiles / $filePerPage);
$filesPageQ = $pdo->prepare("SELECT a.*, d.nombre as departamento_nombre FROM archivos_departamento a LEFT JOIN departamentos d ON a.departamento_id = d.id $fileWhere ORDER BY a.fecha_creacion DESC LIMIT $filePerPage OFFSET $fileOffset");
$filesPageQ->execute($fileParams);
$archivosPage = $filesPageQ->fetchAll();

// Portales con paginación
$portalPage = max(1, (int)($_GET['p_page'] ?? 1));
$portalPerPage = 4;
$portalOffset = ($portalPage - 1) * $portalPerPage;
$totalPortales = $pdo->query("SELECT COUNT(*) FROM portales_clientes WHERE activo = 1")->fetchColumn();
$totalPortalPages = ceil($totalPortales / $portalPerPage);
$portalesPage = $pdo->prepare("SELECT * FROM portales_clientes WHERE activo = 1 ORDER BY orden ASC LIMIT ? OFFSET ?");
$portalesPage->execute([$portalPerPage, $portalOffset]);
$portalesPage = $portalesPage->fetchAll();

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
</head>
<body>

    <!-- HEADER con fondo, reloj, fecha y clima -->
    <header class="header" style="background: url('assets/img/fondo1.png') center/cover; position: relative;">
        <div style="position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);"></div>
        <div class="header-content" style="position:relative;z-index:1;">
            <div class="logo-text">
                <h1>AUTOMOTRIZ CORP</h1>
                <span>INYECCI&Oacute;N &bull; CROMADO &bull; PINTURA</span>
            </div>
            <div style="display:flex;align-items:center;gap:25px;">
                <div style="text-align:right;">
                    <div id="headerClock" style="font-size:2rem;font-weight:700;letter-spacing:2px;"></div>
                    <div id="headerDate" style="font-size:0.85rem;color:var(--text-secondary);"></div>
                </div>
                <div id="weatherWidget" style="display:flex;align-items:center;gap:10px;background:rgba(255,255,255,0.1);padding:10px 18px;border-radius:10px;">
                    <i class="fas fa-cloud-sun" style="font-size:1.8rem;color:#FFD54F;"></i>
                    <div>
                        <div id="weatherTemp" style="font-size:1.3rem;font-weight:700;">--&deg;C</div>
                        <div id="weatherDesc" style="font-size:0.7rem;color:var(--text-secondary);">Cargando...</div>
                    </div>
                </div>
                <a href="calendario.php" style="color:white;text-decoration:none;font-size:0.85rem;"><i class="fas fa-calendar-alt"></i> Calendario</a>
            </div>
        </div>
    </header>

    <!-- AVISOS - justo arriba del slider -->
    <?php if (count($avisos) > 0): ?>
    <?php foreach ($avisos as $aviso): ?>
    <div class="aviso-bar">
        <div class="aviso-content <?php echo $aviso['tipo']; ?>">
            <i class="fas fa-bullhorn"></i>
            <strong><?php echo htmlspecialchars($aviso['titulo']); ?></strong>
            <?php if ($aviso['contenido']): ?> &mdash; <?php echo htmlspecialchars($aviso['contenido']); ?><?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- SLIDER -->
    <section class="slider-section">
        <div class="slider-container">
            <?php if (count($sliderNoticias) > 0): ?>
                <?php foreach ($sliderNoticias as $i => $slide): ?>
                <div class="slide <?php echo $i === 0 ? 'active' : ''; ?>">
                    <img src="assets/uploads/slider/<?php echo $slide['imagen']; ?>" alt="">
                    <div class="slide-content">
                        <h2><?php echo htmlspecialchars($slide['titulo']); ?></h2>
                        <p><?php echo htmlspecialchars($slide['descripcion']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="slide active" style="background:url('assets/img/fondo1.png') center/cover;">
                    <div class="slide-content"><h2>Bienvenidos a la Intranet</h2><p>Configure el slider desde el panel de administraci&oacute;n</p></div>
                </div>
            <?php endif; ?>
            <button class="slider-nav-btn prev"><i class="fas fa-chevron-left"></i></button>
            <button class="slider-nav-btn next"><i class="fas fa-chevron-right"></i></button>
            <div class="slider-dots">
                <?php for ($i = 0; $i < max(count($sliderNoticias), 1); $i++): ?>
                <span class="dot <?php echo $i === 0 ? 'active' : ''; ?>"></span>
                <?php endfor; ?>
            </div>
        </div>
    </section>

    <main class="main-content">

        <!-- ROW 1: Eventos (con paginación) + Aplicaciones Rápidas -->
        <div class="grid-2-col-left">
            <div class="section-card">
                <div class="section-header" style="justify-content:space-between;">
                    <span><i class="fas fa-calendar-check"></i> Eventos</span>
                    <a href="calendario.php" style="font-size:0.75rem;color:var(--accent-blue);text-decoration:none;">Ver calendario completo <i class="fas fa-arrow-right"></i></a>
                </div>
                <div style="padding:15px 20px 20px;">
                    <?php if (count($eventosPage) > 0): ?>
                        <?php foreach ($eventosPage as $ev):
                            $isPast = strtotime($ev['fecha_evento']) < strtotime('today');
                        ?>
                        <a href="evento.php?id=<?php echo $ev['id']; ?>" class="evento-item" style="text-decoration:none;color:inherit;display:flex;gap:12px;padding:12px;background:var(--bg-input);border-radius:8px;margin-bottom:10px;border-left:4px solid <?php echo $ev['dept_color'] ?: $ev['color']; ?>;transition:background 0.3s;<?php echo $isPast ? 'opacity:0.6;' : ''; ?>">
                            <div class="evento-fecha">
                                <span class="dia"><?php echo date('d', strtotime($ev['fecha_evento'])); ?></span>
                                <span class="mes"><?php echo strtoupper(substr($mesesEsp[(int)date('m', strtotime($ev['fecha_evento']))], 0, 3)); ?></span>
                            </div>
                            <div class="evento-info" style="flex:1;">
                                <h5 style="font-size:0.85rem;font-weight:600;"><?php echo htmlspecialchars($ev['titulo']); ?></h5>
                                <p style="font-size:0.75rem;color:var(--text-muted);">
                                    <?php if ($ev['hora_inicio']): ?><i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($ev['hora_inicio'])); ?> <?php endif; ?>
                                    <?php if ($ev['lugar']): ?><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($ev['lugar']); ?><?php endif; ?>
                                    <?php if ($ev['dept_nombre']): ?> &bull; <?php echo htmlspecialchars($ev['dept_nombre']); ?><?php endif; ?>
                                </p>
                            </div>
                            <?php if ($isPast): ?><span style="font-size:0.65rem;color:var(--text-muted);align-self:center;">Pasado</span><?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                        <!-- Paginación Eventos -->
                        <?php if ($totalEvPages > 1): ?>
                        <div style="display:flex;justify-content:center;gap:5px;margin-top:10px;">
                            <?php for ($p = 1; $p <= $totalEvPages; $p++): ?>
                            <a href="?ev_page=<?php echo $p; ?>#eventos" style="padding:5px 12px;border-radius:6px;font-size:0.8rem;text-decoration:none;<?php echo $p == $evPage ? 'background:var(--accent-red);color:white;' : 'background:var(--bg-input);color:var(--text-secondary);'; ?>"><?php echo $p; ?></a>
                            <?php endfor; ?>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p style="color:var(--text-muted);font-size:0.85rem;">No hay eventos registrados</p>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <div class="section-card" style="margin-bottom:25px;">
                    <div class="section-header"><i class="fas fa-th"></i> Aplicaciones R&aacute;pidas</div>
                    <div class="apps-grid">
                        <?php foreach ($aplicaciones as $app): ?>
                        <a href="<?php echo htmlspecialchars($app['url']); ?>" target="_blank" class="app-card" style="background:<?php echo $app['color']; ?>;">
                            <i class="fas <?php echo $app['icono']; ?>"></i>
                            <span><?php echo htmlspecialchars($app['nombre']); ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- CUMPLEAÑOS SLIDER -->
                <div class="section-card">
                    <div class="section-header"><i class="fas fa-birthday-cake"></i> Cumplea&ntilde;os</div>
                    <div style="position:relative;overflow:hidden;padding:15px 20px 20px;">
                        <div class="slider-track" id="birthdayTrack" style="display:flex;gap:15px;transition:transform 0.5s ease;">
                            <?php if (count($allBirthdays) > 0): foreach ($allBirthdays as $cumple): ?>
                            <div style="min-width:200px;background:var(--bg-input);border-radius:10px;padding:15px;flex-shrink:0;">
                                <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                                    <img src="assets/uploads/employees/<?php echo $cumple['foto'] ?: 'default.png'; ?>" style="width:45px;height:45px;border-radius:50%;object-fit:cover;border:2px solid var(--accent-purple);" onerror="this.src='assets/img/default-avatar.svg'">
                                    <div>
                                        <div style="font-size:0.85rem;font-weight:600;"><?php echo htmlspecialchars($cumple['nombre_completo']); ?></div>
                                        <div style="font-size:0.7rem;color:var(--text-muted);"><?php echo htmlspecialchars($cumple['departamento_nombre'] ?? ''); ?> - <?php echo htmlspecialchars($cumple['puesto'] ?? ''); ?></div>
                                    </div>
                                </div>
                                <?php if (date('d', strtotime($cumple['fecha_nacimiento'])) == date('d')): ?>
                                <div style="font-size:0.75rem;color:var(--text-secondary);font-style:italic;padding:8px;background:rgba(255,255,255,0.05);border-radius:6px;">&iexcl;Feliz cumplea&ntilde;os <?php echo htmlspecialchars(explode(' ',$cumple['nombre_completo'])[0]); ?>!</div>
                                <?php else: ?>
                                <div style="font-size:0.7rem;color:var(--text-muted);"><?php echo date('d', strtotime($cumple['fecha_nacimiento'])); ?> de este mes</div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; else: ?>
                            <p style="color:var(--text-muted);font-size:0.85rem;">No hay cumplea&ntilde;os este mes</p>
                            <?php endif; ?>
                        </div>
                        <?php if (count($allBirthdays) > 2): ?>
                        <button onclick="slideTrack('birthdayTrack',-1)" style="position:absolute;left:5px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.7);border:none;color:white;width:28px;height:28px;border-radius:50%;cursor:pointer;z-index:5;"><i class="fas fa-chevron-left"></i></button>
                        <button onclick="slideTrack('birthdayTrack',1)" style="position:absolute;right:5px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.7);border:none;color:white;width:28px;height:28px;border-radius:50%;cursor:pointer;z-index:5;"><i class="fas fa-chevron-right"></i></button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROW 2: Cuenta Regresiva + Videos slider -->
        <div class="grid-2-col">
            <?php if ($cuentaRegresiva): ?>
            <div class="countdown-card">
                <div class="countdown-header"><i class="fas fa-clock"></i><h3><?php echo htmlspecialchars($cuentaRegresiva['titulo']); ?></h3></div>
                <div class="countdown-timer" data-target="<?php echo $cuentaRegresiva['fecha_evento']; ?>">
                    <div class="countdown-box"><span class="countdown-num" id="cd-days">00</span><span class="countdown-label">D&iacute;as</span></div>
                    <div class="countdown-box"><span class="countdown-num" id="cd-hours">00</span><span class="countdown-label">Horas</span></div>
                    <div class="countdown-box"><span class="countdown-num" id="cd-mins">00</span><span class="countdown-label">Min</span></div>
                    <div class="countdown-box"><span class="countdown-num" id="cd-secs">00</span><span class="countdown-label">Seg</span></div>
                </div>
                <?php if ($cuentaRegresiva['descripcion']): ?><p class="countdown-desc"><?php echo htmlspecialchars($cuentaRegresiva['descripcion']); ?></p><?php endif; ?>
            </div>
            <?php else: ?><div></div><?php endif; ?>

            <!-- Videos Slider -->
            <div class="section-card">
                <div class="section-header"><i class="fas fa-video"></i> Videos</div>
                <div style="position:relative;overflow:hidden;">
                    <div class="slider-track" id="videoTrack" style="display:flex;gap:10px;padding:15px 20px 20px;transition:transform 0.5s ease;">
                        <?php if (count($videos) > 0): foreach ($videos as $vid): ?>
                        <div style="min-width:220px;flex-shrink:0;border-radius:8px;overflow:hidden;position:relative;height:130px;background:#111;">
                            <?php if ($vid['thumbnail']): ?>
                            <img src="assets/uploads/videos/<?php echo $vid['thumbnail']; ?>" style="width:100%;height:100%;object-fit:cover;">
                            <?php else: ?>
                            <video style="width:100%;height:100%;object-fit:cover;"><source src="assets/uploads/videos/<?php echo $vid['archivo_video']; ?>" type="video/mp4"></video>
                            <?php endif; ?>
                            <div class="video-play-icon"><i class="fas fa-play"></i></div>
                            <div class="video-overlay"><?php echo htmlspecialchars($vid['titulo']); ?></div>
                        </div>
                        <?php endforeach; else: ?>
                        <p style="color:var(--text-muted);font-size:0.85rem;padding:20px;">Sin videos</p>
                        <?php endif; ?>
                    </div>
                    <?php if (count($videos) > 2): ?>
                    <button onclick="slideTrack('videoTrack',-1)" style="position:absolute;left:5px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.7);border:none;color:white;width:28px;height:28px;border-radius:50%;cursor:pointer;z-index:5;"><i class="fas fa-chevron-left"></i></button>
                    <button onclick="slideTrack('videoTrack',1)" style="position:absolute;right:5px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.7);border:none;color:white;width:28px;height:28px;border-radius:50%;cursor:pointer;z-index:5;"><i class="fas fa-chevron-right"></i></button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ROW 3: Noticias con paginación -->
        <div class="section-card" style="margin-bottom:25px;">
            <div class="section-header" style="padding:18px 20px;"><i class="fas fa-newspaper"></i> Noticias y Art&iacute;culos</div>
            <div style="padding:15px 20px 20px;">
                <?php if (count($articulosPage) > 0): ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:15px;">
                    <?php foreach ($articulosPage as $art): ?>
                    <a href="articulo.php?id=<?php echo $art['id']; ?>" style="text-decoration:none;color:inherit;background:var(--bg-input);border-radius:10px;overflow:hidden;transition:transform 0.3s;">
                        <?php if ($art['imagen']): ?>
                        <img src="assets/uploads/articles/<?php echo $art['imagen']; ?>" style="width:100%;height:140px;object-fit:cover;">
                        <?php else: ?>
                        <div style="height:140px;background:#222;display:flex;align-items:center;justify-content:center;"><i class="fas fa-newspaper" style="font-size:2rem;color:#444;"></i></div>
                        <?php endif; ?>
                        <div style="padding:14px;">
                            <div style="font-size:0.65rem;color:var(--text-muted);margin-bottom:5px;"><?php echo formatearFecha($art['fecha_publicacion']); ?></div>
                            <div style="font-size:0.9rem;font-weight:600;margin-bottom:5px;"><?php echo htmlspecialchars($art['titulo']); ?></div>
                            <div style="font-size:0.78rem;color:var(--text-muted);"><?php echo truncarTexto(strip_tags($art['contenido']), 80); ?></div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php if ($totalArtPages > 1): ?>
                <div style="display:flex;justify-content:center;gap:5px;margin-top:15px;">
                    <?php for ($p = 1; $p <= $totalArtPages; $p++): ?>
                    <a href="?art_page=<?php echo $p; ?>#noticias" style="padding:5px 12px;border-radius:6px;font-size:0.8rem;text-decoration:none;<?php echo $p == $artPage ? 'background:var(--accent-red);color:white;' : 'background:var(--bg-input);color:var(--text-secondary);'; ?>"><?php echo $p; ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <p style="color:var(--text-muted);font-size:0.85rem;">Sin noticias</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ROW 4: Galería slider + Fotos slider -->
        <div class="media-grid">
            <div class="section-card">
                <div class="section-header"><i class="fas fa-images"></i> Galer&iacute;a de Fotos</div>
                <div style="position:relative;overflow:hidden;">
                    <div class="slider-track" id="galleryTrack" style="display:flex;gap:8px;padding:15px 20px 20px;transition:transform 0.5s ease;">
                        <?php if (count($galeriaFotos) > 0): foreach ($galeriaFotos as $foto): ?>
                        <div style="min-width:160px;height:110px;flex-shrink:0;border-radius:8px;overflow:hidden;">
                            <img src="assets/uploads/gallery/<?php echo $foto['imagen']; ?>" style="width:100%;height:100%;object-fit:cover;transition:transform 0.3s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                        </div>
                        <?php endforeach; else: ?>
                        <p style="color:var(--text-muted);font-size:0.85rem;">Sin fotos</p>
                        <?php endif; ?>
                    </div>
                    <?php if (count($galeriaFotos) > 3): ?>
                    <button onclick="slideTrack('galleryTrack',-1)" style="position:absolute;left:5px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.7);border:none;color:white;width:28px;height:28px;border-radius:50%;cursor:pointer;z-index:5;"><i class="fas fa-chevron-left"></i></button>
                    <button onclick="slideTrack('galleryTrack',1)" style="position:absolute;right:5px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.7);border:none;color:white;width:28px;height:28px;border-radius:50%;cursor:pointer;z-index:5;"><i class="fas fa-chevron-right"></i></button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="section-card">
                <div class="section-header"><i class="fas fa-play-circle"></i> Videos</div>
                <div style="position:relative;overflow:hidden;">
                    <div class="slider-track" id="videoTrack2" style="display:flex;gap:10px;padding:15px 20px 20px;transition:transform 0.5s ease;">
                        <?php if (count($videos) > 0): foreach ($videos as $vid): ?>
                        <div style="min-width:200px;flex-shrink:0;border-radius:8px;overflow:hidden;position:relative;height:120px;background:#111;">
                            <?php if ($vid['thumbnail']): ?>
                            <img src="assets/uploads/videos/<?php echo $vid['thumbnail']; ?>" style="width:100%;height:100%;object-fit:cover;">
                            <?php endif; ?>
                            <div class="video-play-icon"><i class="fas fa-play"></i></div>
                            <div class="video-overlay"><?php echo htmlspecialchars($vid['titulo']); ?></div>
                        </div>
                        <?php endforeach; else: ?>
                        <p style="color:var(--text-muted);font-size:0.85rem;">Sin videos</p>
                        <?php endif; ?>
                    </div>
                    <?php if (count($videos) > 2): ?>
                    <button onclick="slideTrack('videoTrack2',-1)" style="position:absolute;left:5px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.7);border:none;color:white;width:28px;height:28px;border-radius:50%;cursor:pointer;z-index:5;"><i class="fas fa-chevron-left"></i></button>
                    <button onclick="slideTrack('videoTrack2',1)" style="position:absolute;right:5px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.7);border:none;color:white;width:28px;height:28px;border-radius:50%;cursor:pointer;z-index:5;"><i class="fas fa-chevron-right"></i></button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ROW 5: Archivos con paginación -->
        <div class="section-card files-card">
            <div class="files-header">
                <div class="files-header-left"><i class="fas fa-folder-open"></i> Archivos por Departamento</div>
                <form method="GET" style="display:flex;gap:8px;align-items:center;">
                    <select name="dept" class="dept-select" onchange="this.form.submit()">
                        <option value="all">Todos</option>
                        <?php foreach ($departamentos as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>" <?php echo $fileDept == $dept['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div class="files-grid">
                <?php if (count($archivosPage) > 0): foreach ($archivosPage as $archivo):
                    $ext = strtolower(pathinfo($archivo['archivo'], PATHINFO_EXTENSION));
                    $iconClass = in_array($ext, ['pdf']) ? 'pdf' : (in_array($ext, ['doc','docx']) ? 'doc' : (in_array($ext, ['xls','xlsx']) ? 'xls' : ''));
                ?>
                <div class="file-card">
                    <div class="file-icon-box <?php echo $iconClass; ?>"><i class="fas fa-file"></i></div>
                    <div class="file-details">
                        <div class="file-name"><?php echo htmlspecialchars($archivo['nombre']); ?></div>
                        <div class="file-meta"><?php echo htmlspecialchars($archivo['departamento_nombre']); ?> &bull; <?php echo strtoupper($ext); ?></div>
                    </div>
                    <a href="download.php?id=<?php echo $archivo['id']; ?>" class="file-download"><i class="fas fa-download"></i></a>
                </div>
                <?php endforeach; else: ?>
                <p style="color:var(--text-muted);padding:15px;grid-column:span 3;font-size:0.85rem;">No hay archivos</p>
                <?php endif; ?>
            </div>
            <?php if ($totalFilePages > 1): ?>
            <div style="display:flex;justify-content:center;gap:5px;padding:0 20px 20px;">
                <?php for ($p = 1; $p <= $totalFilePages; $p++): ?>
                <a href="?dept=<?php echo $fileDept; ?>&file_page=<?php echo $p; ?>" style="padding:5px 12px;border-radius:6px;font-size:0.8rem;text-decoration:none;<?php echo $p == $filePage ? 'background:var(--accent-red);color:white;' : 'background:var(--bg-input);color:var(--text-secondary);'; ?>"><?php echo $p; ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- ROW 6: Nuestra Compañía + Portales con paginación -->
        <div class="bottom-grid">
            <div class="section-card">
                <div class="section-header"><i class="fas fa-building"></i> Nuestra Compa&ntilde;&iacute;a</div>
                <div class="company-body">
                    <p class="company-desc">Empresa automotriz dedicada a la inyecci&oacute;n, cromado y pintura de piezas pl&aacute;sticas automotrices.</p>
                    <?php
                    $iconos = ['mision'=>'fa-bullseye','vision'=>'fa-eye','valores'=>'fa-heart'];
                    $colores = ['mision'=>'#E53935','vision'=>'#43A047','valores'=>'#FF9800'];
                    foreach ($infoCompania as $info):
                    ?>
                    <div class="company-value">
                        <div class="company-value-header">
                            <i class="fas <?php echo $iconos[$info['seccion']] ?? 'fa-info-circle'; ?>" style="color:<?php echo $colores[$info['seccion']] ?? '#1976D2'; ?>;"></i>
                            <span style="color:<?php echo $colores[$info['seccion']] ?? '#1976D2'; ?>;"><?php echo strtoupper($info['seccion']); ?></span>
                        </div>
                        <p><?php echo htmlspecialchars($info['contenido']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header"><i class="fas fa-globe"></i> Portales de Clientes</div>
                <div class="portal-list">
                    <?php if (count($portalesPage) > 0): foreach ($portalesPage as $portal): ?>
                    <a href="<?php echo htmlspecialchars($portal['url']); ?>" target="_blank" class="portal-item">
                        <div class="portal-icon"><i class="fas fa-globe"></i></div>
                        <div class="portal-info">
                            <div class="portal-name"><?php echo htmlspecialchars($portal['nombre']); ?></div>
                            <div class="portal-desc">Acceso al portal de <?php echo htmlspecialchars($portal['nombre']); ?></div>
                        </div>
                        <span class="portal-arrow"><i class="fas fa-external-link-alt"></i></span>
                    </a>
                    <?php endforeach; else: ?>
                    <p style="color:var(--text-muted);font-size:0.85rem;padding:15px;">Sin portales</p>
                    <?php endif; ?>
                    <?php if ($totalPortalPages > 1): ?>
                    <div style="display:flex;justify-content:center;gap:5px;margin-top:10px;">
                        <?php for ($p = 1; $p <= $totalPortalPages; $p++): ?>
                        <a href="?p_page=<?php echo $p; ?>" style="padding:5px 12px;border-radius:6px;font-size:0.8rem;text-decoration:none;<?php echo $p == $portalPage ? 'background:var(--accent-red);color:white;' : 'background:var(--bg-input);color:var(--text-secondary);'; ?>"><?php echo $p; ?></a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </main>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Automotriz Corp. | <a href="admin/login.php" style="color:var(--text-muted);text-decoration:none;">Administraci&oacute;n</a></p>
    </footer>

    <script>
    // SLIDER principal
    (function(){const s=document.querySelectorAll('.slide'),d=document.querySelectorAll('.dot');if(s.length<=1)return;let c=0,t;function show(i){s.forEach(x=>x.classList.remove('active'));d.forEach(x=>x.classList.remove('active'));c=(i+s.length)%s.length;s[c].classList.add('active');if(d[c])d[c].classList.add('active')}function go(){t=setInterval(()=>show(c+1),5000)}function r(){clearInterval(t);go()}document.querySelector('.slider-nav-btn.next')?.addEventListener('click',()=>{show(c+1);r()});document.querySelector('.slider-nav-btn.prev')?.addEventListener('click',()=>{show(c-1);r()});d.forEach((x,i)=>x.addEventListener('click',()=>{show(i);r()}));go()})();

    // COUNTDOWN
    (function(){const el=document.querySelector('.countdown-timer');if(!el||!el.dataset.target)return;const t=new Date(el.dataset.target).getTime();function u(){const d=t-Date.now();if(d<0)return;document.getElementById('cd-days').textContent=Math.floor(d/864e5);document.getElementById('cd-hours').textContent=String(Math.floor(d%864e5/36e5)).padStart(2,'0');document.getElementById('cd-mins').textContent=String(Math.floor(d%36e5/6e4)).padStart(2,'0');document.getElementById('cd-secs').textContent=String(Math.floor(d%6e4/1e3)).padStart(2,'0')}u();setInterval(u,1000)})();

    // SLIDER genérico para tracks
    const trackPositions = {};
    function slideTrack(id, dir) {
        const track = document.getElementById(id);
        if (!track) return;
        if (!trackPositions[id]) trackPositions[id] = 0;
        const itemW = track.firstElementChild ? track.firstElementChild.offsetWidth + 10 : 200;
        const maxScroll = -(track.scrollWidth - track.parentElement.offsetWidth + 40);
        trackPositions[id] += dir * -itemW * 2;
        if (trackPositions[id] > 0) trackPositions[id] = 0;
        if (trackPositions[id] < maxScroll) trackPositions[id] = maxScroll;
        track.style.transform = 'translateX(' + trackPositions[id] + 'px)';
    }

    // RELOJ Y FECHA
    function updateClock() {
        const now = new Date();
        const h = String(now.getHours()).padStart(2,'0');
        const m = String(now.getMinutes()).padStart(2,'0');
        const s = String(now.getSeconds()).padStart(2,'0');
        document.getElementById('headerClock').textContent = h + ':' + m + ':' + s;
        const dias = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
        const meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        document.getElementById('headerDate').textContent = dias[now.getDay()] + ', ' + now.getDate() + ' de ' + meses[now.getMonth()] + ' de ' + now.getFullYear();
    }
    updateClock();
    setInterval(updateClock, 1000);

    // CLIMA (usando API gratuita wttr.in)
    fetch('https://wttr.in/?format=%t|%C&lang=es')
        .then(r => r.text())
        .then(data => {
            const parts = data.split('|');
            if (parts.length >= 2) {
                document.getElementById('weatherTemp').textContent = parts[0].trim();
                document.getElementById('weatherDesc').textContent = parts[1].trim();
            }
        })
        .catch(() => {
            document.getElementById('weatherDesc').textContent = 'Sin conexión';
        });
    </script>
</body>
</html>
