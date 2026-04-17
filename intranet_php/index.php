<?php
/**
 * Página Principal - Intranet Corporativa (Dark Theme)
 * Empresa Automotriz - Inyección, Cromado, Pintura
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';

// Obtener datos
$sliderNoticias = getSliderNoticias($pdo);
$eventos = getEventos($pdo);
$cumpleaneros = getCumpleanerosMes($pdo);
$cumpleanerosHoy = getCumpleanerosHoy($pdo);
$aplicaciones = getAplicaciones($pdo);
$departamentos = getDepartamentos($pdo);
$galeriaFotos = getGaleriaFotos($pdo);
$videos = getVideos($pdo);
$articulos = getArticulos($pdo, 5);
$portales = getPortalesClientes($pdo);
$cuentaRegresiva = getCuentaRegresiva($pdo);
$avisos = getAvisosActivos($pdo);
$infoCompania = getInfoCompania($pdo);
$archivos = getArchivosPorDepartamento($pdo);

// Fechas de eventos para el calendario JS
$eventDates = array_map(function($e) {
    return (int)date('d', strtotime($e['fecha_evento']));
}, array_filter($eventos, function($e) {
    return date('m', strtotime($e['fecha_evento'])) == date('m');
}));

// Meses en español
$mesesEsp = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
$mesActual = $mesesEsp[(int)date('m')];
$anioActual = date('Y');
$diaHoy = (int)date('d');
$primerDia = date('w', mktime(0,0,0,(int)date('m'),1,(int)date('Y')));
$diasEnMes = (int)date('t');
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

    <!-- HEADER -->
    <header class="header">
        <div class="header-content">
            <div class="logo-text">
                <h1>AUTOMOTRIZ CORP</h1>
                <span>INYECCI&Oacute;N &bull; CROMADO &bull; PINTURA</span>
            </div>
        </div>
    </header>

    <!-- AVISOS -->
    <?php foreach ($avisos as $aviso): ?>
    <div class="aviso-bar">
        <div class="aviso-content <?php echo $aviso['tipo']; ?>">
            <i class="fas fa-bullhorn"></i>
            <strong><?php echo htmlspecialchars($aviso['titulo']); ?></strong>
            <?php if ($aviso['contenido']): ?> &mdash; <?php echo htmlspecialchars($aviso['contenido']); ?><?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- SLIDER -->
    <section class="slider-section">
        <div class="slider-container">
            <?php if (count($sliderNoticias) > 0): ?>
                <?php foreach ($sliderNoticias as $i => $slide): ?>
                <div class="slide <?php echo $i === 0 ? 'active' : ''; ?>">
                    <img src="assets/uploads/slider/<?php echo $slide['imagen']; ?>" alt="<?php echo htmlspecialchars($slide['titulo']); ?>">
                    <div class="slide-content">
                        <h2><?php echo htmlspecialchars($slide['titulo']); ?></h2>
                        <p><?php echo htmlspecialchars($slide['descripcion']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="slide active" style="background: url('assets/img/fondo1.png') center/cover;">
                    <div class="slide-content">
                        <h2>Bienvenidos a la Intranet</h2>
                        <p>Configure el slider desde el panel de administraci&oacute;n</p>
                    </div>
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

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <!-- ROW 1: Calendario + Aplicaciones -->
        <div class="grid-2-col-left">
            <!-- Calendario -->
            <div class="section-card">
                <div class="section-header">
                    <i class="fas fa-calendar-alt"></i> Calendario de Eventos
                </div>
                <div class="calendar-wrapper">
                    <div class="cal-header">
                        <span><?php echo $mesActual . ' ' . $anioActual; ?></span>
                        <div class="cal-nav">
                            <button><i class="fas fa-chevron-left"></i></button>
                            <button><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                    <div class="calendar-grid">
                        <div class="cal-day-name">Do</div>
                        <div class="cal-day-name">Lu</div>
                        <div class="cal-day-name">Ma</div>
                        <div class="cal-day-name">Mi</div>
                        <div class="cal-day-name">Ju</div>
                        <div class="cal-day-name">Vi</div>
                        <div class="cal-day-name">Sa</div>
                        <?php
                        for ($i = 0; $i < $primerDia; $i++) {
                            echo '<div class="cal-day empty"></div>';
                        }
                        for ($d = 1; $d <= $diasEnMes; $d++) {
                            $classes = 'cal-day';
                            if ($d == $diaHoy) $classes .= ' today';
                            if (in_array($d, array_values($eventDates))) $classes .= ' has-event';
                            echo "<div class=\"{$classes}\">{$d}</div>";
                        }
                        ?>
                    </div>
                </div>
                <div class="proximos-eventos">
                    <h4>Pr&oacute;ximos Eventos</h4>
                    <?php if (count($eventos) > 0): ?>
                        <?php foreach (array_slice($eventos, 0, 4) as $ev): ?>
                        <div class="evento-item">
                            <div class="evento-fecha">
                                <span class="dia"><?php echo date('d', strtotime($ev['fecha_evento'])); ?></span>
                                <span class="mes"><?php echo strtoupper(substr($mesesEsp[(int)date('m', strtotime($ev['fecha_evento']))], 0, 3)); ?></span>
                            </div>
                            <div class="evento-info">
                                <h5><?php echo htmlspecialchars($ev['titulo']); ?></h5>
                                <p><?php echo $ev['hora_inicio'] ? date('H:i', strtotime($ev['hora_inicio'])) : ''; ?> <?php echo htmlspecialchars($ev['lugar'] ?? ''); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-events-msg">No hay eventos pr&oacute;ximos</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Aplicaciones Rápidas + Cumpleaños -->
            <div>
                <div class="section-card" style="margin-bottom: 25px;">
                    <div class="section-header">
                        <i class="fas fa-th"></i> Aplicaciones R&aacute;pidas
                    </div>
                    <div class="apps-grid">
                        <?php foreach ($aplicaciones as $app): ?>
                        <a href="<?php echo htmlspecialchars($app['url']); ?>" target="_blank" class="app-card" style="background: <?php echo $app['color']; ?>;">
                            <i class="fas <?php echo $app['icono']; ?>"></i>
                            <span><?php echo htmlspecialchars($app['nombre']); ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-header">
                        <i class="fas fa-birthday-cake"></i> Cumplea&ntilde;os
                    </div>
                    <div class="birthday-scroll">
                        <?php
                        $allBirthdays = array_merge($cumpleanerosHoy, array_filter($cumpleaneros, function($c) {
                            return date('d', strtotime($c['fecha_nacimiento'])) != date('d');
                        }));
                        if (count($allBirthdays) > 0):
                            foreach ($allBirthdays as $cumple):
                        ?>
                        <div class="birthday-item">
                            <div class="birthday-top">
                                <img src="assets/uploads/employees/<?php echo $cumple['foto'] ?: 'default.png'; ?>" class="birthday-photo" onerror="this.src='assets/img/default-avatar.svg'">
                                <div>
                                    <div class="birthday-name"><?php echo htmlspecialchars($cumple['nombre_completo']); ?></div>
                                    <div class="birthday-dept"><?php echo htmlspecialchars($cumple['departamento_nombre'] ?? ''); ?> - <?php echo htmlspecialchars($cumple['puesto'] ?? ''); ?></div>
                                </div>
                            </div>
                            <?php if (date('d', strtotime($cumple['fecha_nacimiento'])) == date('d')): ?>
                            <div class="birthday-msg">&iexcl;Feliz cumplea&ntilde;os <?php echo htmlspecialchars(explode(' ', $cumple['nombre_completo'])[0]); ?>! Que tengas un excelente d&iacute;a.</div>
                            <?php endif; ?>
                        </div>
                        <?php
                            endforeach;
                        else:
                        ?>
                        <p style="color: var(--text-muted); padding: 15px; font-size: 0.85rem;">No hay cumplea&ntilde;os este mes</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROW 2: Cuenta Regresiva + Videos preview -->
        <div class="grid-2-col">
            <!-- Cuenta Regresiva -->
            <?php if ($cuentaRegresiva): ?>
            <div class="countdown-card">
                <div class="countdown-header">
                    <i class="fas fa-clock"></i>
                    <h3><?php echo htmlspecialchars($cuentaRegresiva['titulo']); ?></h3>
                </div>
                <div class="countdown-timer" data-target="<?php echo $cuentaRegresiva['fecha_evento']; ?>">
                    <div class="countdown-box">
                        <span class="countdown-num" id="cd-days">00</span>
                        <span class="countdown-label">D&iacute;as</span>
                    </div>
                    <div class="countdown-box">
                        <span class="countdown-num" id="cd-hours">00</span>
                        <span class="countdown-label">Horas</span>
                    </div>
                    <div class="countdown-box">
                        <span class="countdown-num" id="cd-mins">00</span>
                        <span class="countdown-label">Min</span>
                    </div>
                    <div class="countdown-box">
                        <span class="countdown-num" id="cd-secs">00</span>
                        <span class="countdown-label">Seg</span>
                    </div>
                </div>
                <?php if ($cuentaRegresiva['descripcion']): ?>
                <p class="countdown-desc"><?php echo htmlspecialchars($cuentaRegresiva['descripcion']); ?></p>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div></div>
            <?php endif; ?>

            <!-- Videos Preview -->
            <div class="section-card">
                <div class="section-header">
                    <i class="fas fa-video"></i> Videos
                </div>
                <div class="video-items">
                    <?php if (count($videos) > 0): ?>
                        <?php foreach (array_slice($videos, 0, 2) as $vid): ?>
                        <div class="video-thumb">
                            <?php if ($vid['thumbnail']): ?>
                            <img src="assets/uploads/videos/<?php echo $vid['thumbnail']; ?>" alt="">
                            <?php else: ?>
                            <video><source src="assets/uploads/videos/<?php echo $vid['archivo_video']; ?>" type="video/mp4"></video>
                            <?php endif; ?>
                            <div class="video-play-icon"><i class="fas fa-play"></i></div>
                            <div class="video-overlay"><?php echo htmlspecialchars($vid['titulo']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: var(--text-muted); padding: 20px; font-size: 0.85rem; grid-column: span 2;">No hay videos disponibles</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ROW 3: Noticias y Artículos -->
        <div class="news-section">
            <div class="section-header" style="padding: 18px 20px;">
                <i class="fas fa-newspaper"></i> Noticias y Art&iacute;culos
            </div>
            <div class="news-grid">
                <!-- Featured -->
                <div class="news-featured">
                    <?php if (count($articulos) > 0): ?>
                    <?php $featured = $articulos[0]; ?>
                    <?php if ($featured['imagen']): ?>
                    <img src="assets/uploads/articles/<?php echo $featured['imagen']; ?>" alt="">
                    <?php else: ?>
                    <div style="background: #222; height: 100%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-newspaper" style="font-size: 3rem; color: #444;"></i>
                    </div>
                    <?php endif; ?>
                    <div class="news-featured-overlay">
                        <h3><?php echo htmlspecialchars($featured['titulo']); ?></h3>
                        <p><?php echo truncarTexto(strip_tags($featured['contenido']), 80); ?></p>
                    </div>
                    <?php else: ?>
                    <div style="background: #222; height: 100%; display: flex; align-items: center; justify-content: center;">
                        <p style="color: #666;">Sin noticias</p>
                    </div>
                    <?php endif; ?>
                </div>
                <!-- List -->
                <div class="news-list">
                    <?php foreach (array_slice($articulos, 1, 4) as $art): ?>
                    <a href="articulo.php?id=<?php echo $art['id']; ?>" class="news-list-item" style="text-decoration: none; color: inherit;">
                        <?php if ($art['imagen']): ?>
                        <img src="assets/uploads/articles/<?php echo $art['imagen']; ?>" class="news-thumb" alt="">
                        <?php else: ?>
                        <div class="news-thumb" style="background: #333; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-newspaper" style="color: #555;"></i>
                        </div>
                        <?php endif; ?>
                        <div class="news-item-info">
                            <div class="news-item-date"><?php echo formatearFecha($art['fecha_publicacion']); ?></div>
                            <div class="news-item-title"><?php echo htmlspecialchars($art['titulo']); ?></div>
                            <div class="news-item-desc"><?php echo truncarTexto(strip_tags($art['contenido']), 50); ?></div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ROW 4: Galería + Videos -->
        <div class="media-grid">
            <div class="section-card">
                <div class="section-header">
                    <i class="fas fa-images"></i> Galer&iacute;a de Fotos
                </div>
                <div class="gallery-items">
                    <?php if (count($galeriaFotos) > 0): ?>
                        <?php foreach (array_slice($galeriaFotos, 0, 6) as $foto): ?>
                        <div class="gallery-thumb">
                            <img src="assets/uploads/gallery/<?php echo $foto['imagen']; ?>" alt="<?php echo htmlspecialchars($foto['titulo']); ?>">
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: var(--text-muted); font-size: 0.85rem; grid-column: span 3; padding: 20px;">Sin fotos</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <i class="fas fa-play-circle"></i> Videos
                </div>
                <div class="video-items">
                    <?php if (count($videos) > 0): ?>
                        <?php foreach (array_slice($videos, 0, 2) as $vid): ?>
                        <div class="video-thumb">
                            <?php if ($vid['thumbnail']): ?>
                            <img src="assets/uploads/videos/<?php echo $vid['thumbnail']; ?>" alt="">
                            <?php else: ?>
                            <video><source src="assets/uploads/videos/<?php echo $vid['archivo_video']; ?>" type="video/mp4"></video>
                            <?php endif; ?>
                            <div class="video-play-icon"><i class="fas fa-play"></i></div>
                            <div class="video-overlay"><?php echo htmlspecialchars($vid['titulo']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: var(--text-muted); font-size: 0.85rem; grid-column: span 2; padding: 20px;">Sin videos</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ROW 5: Archivos por Departamento -->
        <div class="section-card files-card">
            <div class="files-header">
                <div class="files-header-left">
                    <i class="fas fa-folder-open"></i> Archivos por Departamento
                </div>
                <select class="dept-select" id="deptFilter" onchange="filterFiles(this.value)">
                    <option value="all">Todos</option>
                    <?php foreach ($departamentos as $dept): ?>
                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="files-grid" id="filesGrid">
                <?php if (count($archivos) > 0): ?>
                    <?php foreach (array_slice($archivos, 0, 6) as $archivo): ?>
                    <?php
                    $ext = strtolower(pathinfo($archivo['archivo'], PATHINFO_EXTENSION));
                    $iconClass = in_array($ext, ['pdf']) ? 'pdf' : (in_array($ext, ['doc', 'docx']) ? 'doc' : (in_array($ext, ['xls', 'xlsx']) ? 'xls' : ''));
                    ?>
                    <div class="file-card">
                        <div class="file-icon-box <?php echo $iconClass; ?>">
                            <i class="fas fa-file"></i>
                        </div>
                        <div class="file-details">
                            <div class="file-name"><?php echo htmlspecialchars($archivo['nombre']); ?></div>
                            <div class="file-meta"><?php echo htmlspecialchars($archivo['departamento_nombre']); ?> &bull; <?php echo strtoupper($ext); ?></div>
                        </div>
                        <a href="download.php?id=<?php echo $archivo['id']; ?>" class="file-download"><i class="fas fa-download"></i></a>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: var(--text-muted); padding: 15px; grid-column: span 3; font-size: 0.85rem;">No hay archivos disponibles</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ROW 6: Nuestra Compañía + Portales -->
        <div class="bottom-grid">
            <div class="section-card">
                <div class="section-header">
                    <i class="fas fa-building"></i> Nuestra Compa&ntilde;&iacute;a
                </div>
                <div class="company-body">
                    <p class="company-desc">Empresa automotriz dedicada a la inyecci&oacute;n, cromado y pintura de piezas pl&aacute;sticas automotrices.</p>
                    <?php
                    $iconos = ['mision' => 'fa-bullseye', 'vision' => 'fa-eye', 'valores' => 'fa-heart'];
                    $colores = ['mision' => '#E53935', 'vision' => '#43A047', 'valores' => '#FF9800'];
                    foreach ($infoCompania as $info):
                        $icono = $iconos[$info['seccion']] ?? 'fa-info-circle';
                        $color = $colores[$info['seccion']] ?? '#1976D2';
                    ?>
                    <div class="company-value">
                        <div class="company-value-header">
                            <i class="fas <?php echo $icono; ?>" style="color: <?php echo $color; ?>;"></i>
                            <span style="color: <?php echo $color; ?>;"><?php echo strtoupper($info['seccion']); ?></span>
                        </div>
                        <p><?php echo htmlspecialchars($info['contenido']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <i class="fas fa-globe"></i> Portales de Clientes
                </div>
                <div class="portal-list">
                    <?php if (count($portales) > 0): ?>
                        <?php foreach ($portales as $portal): ?>
                        <a href="<?php echo htmlspecialchars($portal['url']); ?>" target="_blank" class="portal-item">
                            <div class="portal-icon"><i class="fas fa-globe"></i></div>
                            <div class="portal-info">
                                <div class="portal-name"><?php echo htmlspecialchars($portal['nombre']); ?></div>
                                <div class="portal-desc">Acceso al portal de <?php echo htmlspecialchars($portal['nombre']); ?></div>
                            </div>
                            <span class="portal-arrow"><i class="fas fa-external-link-alt"></i></span>
                        </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: var(--text-muted); font-size: 0.85rem; padding: 15px;">No hay portales configurados</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </main>

    <!-- FOOTER -->
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Automotriz Corp. Todos los derechos reservados. | <a href="admin/login.php" style="color: var(--text-muted); text-decoration: none;">Administraci&oacute;n</a></p>
    </footer>

    <script>
    // ---- SLIDER ----
    (function() {
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.dot');
        if (slides.length <= 1) return;
        let cur = 0, timer;
        function show(i) {
            slides.forEach(s => s.classList.remove('active'));
            dots.forEach(d => d.classList.remove('active'));
            cur = (i + slides.length) % slides.length;
            slides[cur].classList.add('active');
            if(dots[cur]) dots[cur].classList.add('active');
        }
        function start() { timer = setInterval(() => show(cur + 1), 5000); }
        function reset() { clearInterval(timer); start(); }
        document.querySelector('.slider-nav-btn.next')?.addEventListener('click', () => { show(cur+1); reset(); });
        document.querySelector('.slider-nav-btn.prev')?.addEventListener('click', () => { show(cur-1); reset(); });
        dots.forEach((d,i) => d.addEventListener('click', () => { show(i); reset(); }));
        start();
    })();

    // ---- COUNTDOWN ----
    (function() {
        const el = document.querySelector('.countdown-timer');
        if (!el || !el.dataset.target) return;
        const target = new Date(el.dataset.target).getTime();
        function update() {
            const diff = target - Date.now();
            if (diff < 0) return;
            const d = Math.floor(diff / 86400000);
            const h = Math.floor((diff % 86400000) / 3600000);
            const m = Math.floor((diff % 3600000) / 60000);
            const s = Math.floor((diff % 60000) / 1000);
            document.getElementById('cd-days').textContent = d;
            document.getElementById('cd-hours').textContent = String(h).padStart(2, '0');
            document.getElementById('cd-mins').textContent = String(m).padStart(2, '0');
            document.getElementById('cd-secs').textContent = String(s).padStart(2, '0');
        }
        update();
        setInterval(update, 1000);
    })();

    // ---- FILES FILTER ----
    function filterFiles(deptId) {
        const grid = document.getElementById('filesGrid');
        grid.innerHTML = '<p style="color:#666;padding:20px;grid-column:span 3;">Cargando...</p>';
        fetch('api/get_files.php?dept=' + deptId)
            .then(r => r.json())
            .then(data => {
                if (!data.length) {
                    grid.innerHTML = '<p style="color:#666;padding:15px;grid-column:span 3;font-size:0.85rem;">No hay archivos</p>';
                    return;
                }
                grid.innerHTML = data.map(f => {
                    const ext = f.archivo.split('.').pop().toLowerCase();
                    const ic = ext === 'pdf' ? 'pdf' : (['doc','docx'].includes(ext) ? 'doc' : (['xls','xlsx'].includes(ext) ? 'xls' : ''));
                    return `<div class="file-card">
                        <div class="file-icon-box ${ic}"><i class="fas fa-file"></i></div>
                        <div class="file-details">
                            <div class="file-name">${f.nombre}</div>
                            <div class="file-meta">${f.departamento_nombre || ''} &bull; ${ext.toUpperCase()}</div>
                        </div>
                        <a href="download.php?id=${f.id}" class="file-download"><i class="fas fa-download"></i></a>
                    </div>`;
                }).join('');
            });
    }
    </script>
</body>
</html>
