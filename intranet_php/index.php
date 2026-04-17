<?php
/**
 * Página Principal - Intranet Corporativa
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

// Preparar fechas de eventos para JavaScript
$eventDates = array_map(function($e) {
    return (int)date('d', strtotime($e['fecha_evento']));
}, array_filter($eventos, function($e) {
    return date('m', strtotime($e['fecha_evento'])) == date('m');
}));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Empresa Automotriz</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <img src="assets/img/logo.png" alt="Logo" onerror="this.style.display='none'">
                <div class="logo-text">
                    <h1>AUTOMOTRIZ CORP</h1>
                    <span>INYECCIÓN • CROMADO • PINTURA</span>
                </div>
            </div>
            <nav class="header-nav">
                <ul>
                    <li><a href="#inicio"><i class="fas fa-home"></i> Inicio</a></li>
                    <li><a href="#eventos"><i class="fas fa-calendar"></i> Eventos</a></li>
                    <li><a href="#aplicaciones"><i class="fas fa-th"></i> Aplicaciones</a></li>
                    <li><a href="#noticias"><i class="fas fa-newspaper"></i> Noticias</a></li>
                    <li><a href="#compania"><i class="fas fa-building"></i> Compañía</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Avisos Banner -->
    <?php foreach ($avisos as $aviso): ?>
    <div class="aviso-banner <?php echo $aviso['tipo']; ?>">
        <i class="fas fa-bullhorn"></i>
        <strong><?php echo htmlspecialchars($aviso['titulo']); ?></strong>
        <?php if ($aviso['contenido']): ?>
            - <?php echo htmlspecialchars($aviso['contenido']); ?>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <!-- Slider de Noticias -->
    <section class="slider-section" id="inicio">
        <div class="slider-container">
            <?php if (count($sliderNoticias) > 0): ?>
                <?php foreach ($sliderNoticias as $index => $slide): ?>
                <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>">
                    <img src="assets/uploads/slider/<?php echo $slide['imagen']; ?>" alt="<?php echo htmlspecialchars($slide['titulo']); ?>">
                    <div class="slide-content">
                        <h2><?php echo htmlspecialchars($slide['titulo']); ?></h2>
                        <p><?php echo htmlspecialchars($slide['descripcion']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="slide active">
                    <div style="background: linear-gradient(135deg, #1a1a1a, #333); height: 100%; display: flex; align-items: center; justify-content: center;">
                        <div style="text-align: center; color: white;">
                            <i class="fas fa-image" style="font-size: 4rem; margin-bottom: 20px; opacity: 0.5;"></i>
                            <h2>Bienvenidos a la Intranet</h2>
                            <p>Configure el slider desde el panel de administración</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="slider-controls">
                <button class="slider-btn prev"><i class="fas fa-chevron-left"></i></button>
                <button class="slider-btn next"><i class="fas fa-chevron-right"></i></button>
            </div>
            
            <div class="slider-dots">
                <?php for ($i = 0; $i < max(count($sliderNoticias), 1); $i++): ?>
                <span class="dot <?php echo $i === 0 ? 'active' : ''; ?>"></span>
                <?php endfor; ?>
            </div>
        </div>
    </section>

    <!-- Cuenta Regresiva -->
    <?php if ($cuentaRegresiva): ?>
    <section class="countdown-section" style="background: linear-gradient(135deg, <?php echo $cuentaRegresiva['color']; ?>, #333);">
        <h2 class="countdown-title"><?php echo htmlspecialchars($cuentaRegresiva['titulo']); ?></h2>
        <?php if ($cuentaRegresiva['descripcion']): ?>
            <p style="margin-bottom: 20px; opacity: 0.9;"><?php echo htmlspecialchars($cuentaRegresiva['descripcion']); ?></p>
        <?php endif; ?>
        <div class="countdown-timer" data-target="<?php echo $cuentaRegresiva['fecha_evento']; ?>">
            <div class="countdown-item">
                <span class="countdown-number" id="countdown-days">00</span>
                <span class="countdown-label">Días</span>
            </div>
            <div class="countdown-item">
                <span class="countdown-number" id="countdown-hours">00</span>
                <span class="countdown-label">Horas</span>
            </div>
            <div class="countdown-item">
                <span class="countdown-number" id="countdown-minutes">00</span>
                <span class="countdown-label">Minutos</span>
            </div>
            <div class="countdown-item">
                <span class="countdown-number" id="countdown-seconds">00</span>
                <span class="countdown-label">Segundos</span>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Contenido Principal -->
    <main class="main-content">
        
        <!-- Grid: Calendario y Cumpleaños -->
        <div class="grid-2" id="eventos">
            <!-- Calendario de Eventos -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-calendar-alt"></i> Calendario de Eventos
                </div>
                <div class="calendar-widget">
                    <div class="calendar-header"></div>
                    <div class="calendar-grid"></div>
                </div>
                <div class="events-scroll">
                    <?php if (count($eventos) > 0): ?>
                        <?php foreach ($eventos as $evento): ?>
                        <div class="event-item" style="border-left-color: <?php echo $evento['color']; ?>;">
                            <div class="event-date">
                                <span class="day"><?php echo date('d', strtotime($evento['fecha_evento'])); ?></span>
                                <span class="month"><?php echo strtoupper(substr(formatearFecha($evento['fecha_evento'], 'completo'), strpos(formatearFecha($evento['fecha_evento'], 'completo'), ' de ') + 4, 3)); ?></span>
                            </div>
                            <div class="event-info">
                                <h4><?php echo htmlspecialchars($evento['titulo']); ?></h4>
                                <p>
                                    <?php if ($evento['hora_inicio']): ?>
                                        <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($evento['hora_inicio'])); ?>
                                    <?php endif; ?>
                                    <?php if ($evento['lugar']): ?>
                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($evento['lugar']); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; padding: 30px; color: #666;">No hay eventos próximos</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Cumpleaños -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-birthday-cake"></i> Cumpleaños del Mes
                </div>
                <div class="card-body">
                    <?php if (count($cumpleanerosHoy) > 0): ?>
                        <h4 style="color: #e53935; margin-bottom: 15px;">🎉 ¡Feliz Cumpleaños Hoy!</h4>
                        <?php foreach ($cumpleanerosHoy as $cumple): ?>
                        <div class="birthday-card" style="margin-bottom: 15px;">
                            <img src="assets/uploads/employees/<?php echo $cumple['foto'] ?: 'default.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($cumple['nombre_completo']); ?>" 
                                 class="birthday-photo"
                                 onerror="this.src='assets/img/default-avatar.png'">
                            <div class="birthday-name"><?php echo htmlspecialchars($cumple['nombre_completo']); ?></div>
                            <div class="birthday-info">
                                <?php echo htmlspecialchars($cumple['puesto']); ?><br>
                                <strong><?php echo htmlspecialchars($cumple['departamento_nombre']); ?></strong>
                            </div>
                            <div class="birthday-message">
                                ¡Te deseamos un excelente día lleno de alegría y éxitos!
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if (count($cumpleaneros) > 0): ?>
                        <div style="max-height: 300px; overflow-y: auto;">
                        <?php foreach ($cumpleaneros as $cumple): ?>
                            <?php if (date('d', strtotime($cumple['fecha_nacimiento'])) != date('d')): ?>
                            <div style="display: flex; align-items: center; gap: 15px; padding: 10px; border-bottom: 1px solid #eee;">
                                <img src="assets/uploads/employees/<?php echo $cumple['foto'] ?: 'default.png'; ?>" 
                                     style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;"
                                     onerror="this.src='assets/img/default-avatar.png'">
                                <div>
                                    <strong><?php echo htmlspecialchars($cumple['nombre_completo']); ?></strong>
                                    <p style="font-size: 0.85rem; color: #666;">
                                        <?php echo date('d', strtotime($cumple['fecha_nacimiento'])); ?> de este mes • 
                                        <?php echo htmlspecialchars($cumple['departamento_nombre']); ?>
                                    </p>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; padding: 30px; color: #666;">No hay cumpleaños este mes</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Aplicaciones -->
        <section id="aplicaciones">
            <h2 class="section-title"><i class="fas fa-th"></i> Aplicaciones del Departamento</h2>
            <div class="apps-grid">
                <?php foreach ($aplicaciones as $app): ?>
                <a href="<?php echo htmlspecialchars($app['url']); ?>" 
                   target="_blank" 
                   class="app-card" 
                   style="background: <?php echo $app['color']; ?>;">
                    <i class="fas <?php echo $app['icono']; ?>"></i>
                    <span><?php echo htmlspecialchars($app['nombre']); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Archivos por Departamento -->
        <section>
            <h2 class="section-title"><i class="fas fa-folder-open"></i> Archivos por Departamento</h2>
            <div class="files-section">
                <div class="department-tabs">
                    <button class="dept-tab active" data-dept="all">Todos</button>
                    <?php foreach ($departamentos as $dept): ?>
                    <button class="dept-tab" data-dept="<?php echo $dept['id']; ?>">
                        <?php echo htmlspecialchars($dept['nombre']); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <div class="files-list">
                    <?php 
                    $archivos = getArchivosPorDepartamento($pdo);
                    if (count($archivos) > 0):
                        foreach ($archivos as $archivo):
                            $ext = pathinfo($archivo['archivo'], PATHINFO_EXTENSION);
                            $iconClass = in_array($ext, ['pdf']) ? 'pdf' : (in_array($ext, ['doc', 'docx']) ? 'doc' : (in_array($ext, ['xls', 'xlsx']) ? 'xls' : ''));
                    ?>
                    <div class="file-item">
                        <div class="file-info">
                            <div class="file-icon <?php echo $iconClass; ?>">
                                <i class="fas fa-file"></i>
                            </div>
                            <div>
                                <strong><?php echo htmlspecialchars($archivo['nombre']); ?></strong>
                                <p style="font-size: 0.85rem; color: #666;">
                                    <?php echo htmlspecialchars($archivo['departamento_nombre']); ?>
                                    <?php if ($archivo['descripcion']): ?> - <?php echo truncarTexto($archivo['descripcion'], 50); ?><?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <a href="download.php?id=<?php echo $archivo['id']; ?>" class="download-btn">
                            <i class="fas fa-download"></i> Descargar
                        </a>
                    </div>
                    <?php 
                        endforeach;
                    else:
                    ?>
                    <p style="text-align: center; padding: 30px; color: #666;">No hay archivos disponibles</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Galería de Fotos -->
        <section>
            <h2 class="section-title"><i class="fas fa-images"></i> Galería de Fotos</h2>
            <div class="gallery-slider">
                <div class="gallery-track">
                    <?php if (count($galeriaFotos) > 0): ?>
                        <?php foreach ($galeriaFotos as $foto): ?>
                        <div class="gallery-item">
                            <img src="assets/uploads/gallery/<?php echo $foto['imagen']; ?>" alt="<?php echo htmlspecialchars($foto['titulo']); ?>">
                            <?php if ($foto['titulo']): ?>
                            <div class="caption"><?php echo htmlspecialchars($foto['titulo']); ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="width: 100%; padding: 50px; text-align: center; color: #666;">
                            <i class="fas fa-images" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                            <p>No hay fotos en la galería</p>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (count($galeriaFotos) > 4): ?>
                <button class="slider-btn gallery-prev" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%);"><i class="fas fa-chevron-left"></i></button>
                <button class="slider-btn gallery-next" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%);"><i class="fas fa-chevron-right"></i></button>
                <?php endif; ?>
            </div>
        </section>

        <!-- Videos -->
        <section>
            <h2 class="section-title"><i class="fas fa-video"></i> Videos</h2>
            <div class="video-slider">
                <div class="video-track">
                    <?php if (count($videos) > 0): ?>
                        <?php foreach ($videos as $video): ?>
                        <div class="video-item">
                            <video controls poster="assets/uploads/videos/<?php echo $video['thumbnail']; ?>">
                                <source src="assets/uploads/videos/<?php echo $video['archivo_video']; ?>" type="video/mp4">
                                Tu navegador no soporta video HTML5.
                            </video>
                            <div class="video-title"><?php echo htmlspecialchars($video['titulo']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="width: 100%; padding: 50px; text-align: center; color: #666; background: #1a1a1a; border-radius: 10px;">
                            <i class="fas fa-video" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3; color: #fff;"></i>
                            <p style="color: #fff;">No hay videos disponibles</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Artículos / Noticias -->
        <section id="noticias">
            <h2 class="section-title"><i class="fas fa-newspaper"></i> Noticias de la Empresa</h2>
            <div class="grid-3">
                <?php if (count($articulos) > 0): ?>
                    <?php foreach ($articulos as $articulo): ?>
                    <article class="article-card">
                        <?php if ($articulo['imagen']): ?>
                        <div class="article-image">
                            <img src="assets/uploads/articles/<?php echo $articulo['imagen']; ?>" alt="<?php echo htmlspecialchars($articulo['titulo']); ?>">
                        </div>
                        <?php endif; ?>
                        <div class="article-content">
                            <h3><?php echo htmlspecialchars($articulo['titulo']); ?></h3>
                            <p><?php echo truncarTexto(strip_tags($articulo['contenido']), 120); ?></p>
                            <div class="article-meta">
                                <span><i class="fas fa-calendar"></i> <?php echo formatearFecha($articulo['fecha_publicacion']); ?></span>
                                <a href="articulo.php?id=<?php echo $articulo['id']; ?>" class="read-more">
                                    Leer más <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: span 3; text-align: center; padding: 50px; color: #666;">
                        <i class="fas fa-newspaper" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                        <p>No hay noticias publicadas</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Nuestra Compañía -->
        <section class="company-section" id="compania">
            <h2 style="text-align: center; margin-bottom: 40px; font-size: 2rem;">Nuestra Compañía</h2>
            <div class="company-grid">
                <?php 
                $iconos = ['mision' => 'fa-bullseye', 'vision' => 'fa-eye', 'valores' => 'fa-heart'];
                foreach ($infoCompania as $info): 
                    $icono = $iconos[$info['seccion']] ?? 'fa-info-circle';
                ?>
                <div class="company-item">
                    <i class="fas <?php echo $icono; ?>"></i>
                    <h3><?php echo htmlspecialchars($info['titulo']); ?></h3>
                    <p><?php echo htmlspecialchars($info['contenido']); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Portales de Clientes -->
        <section>
            <h2 class="section-title"><i class="fas fa-external-link-alt"></i> Acceso a Portales de Clientes</h2>
            <div class="portals-grid">
                <?php if (count($portales) > 0): ?>
                    <?php foreach ($portales as $portal): ?>
                    <a href="<?php echo htmlspecialchars($portal['url']); ?>" target="_blank" class="portal-card">
                        <?php if ($portal['logo']): ?>
                        <img src="assets/uploads/portals/<?php echo $portal['logo']; ?>" alt="<?php echo htmlspecialchars($portal['nombre']); ?>">
                        <?php else: ?>
                        <i class="fas fa-building" style="font-size: 2.5rem; color: #1976d2; margin-bottom: 10px;"></i>
                        <?php endif; ?>
                        <h4><?php echo htmlspecialchars($portal['nombre']); ?></h4>
                    </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: span 4; text-align: center; padding: 30px; color: #666;">
                        No hay portales configurados
                    </div>
                <?php endif; ?>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>Automotriz Corp</h3>
                <p>Empresa líder en inyección, cromado y pintura de piezas plásticas automotrices.</p>
            </div>
            <div class="footer-section">
                <h3>Departamentos</h3>
                <ul>
                    <?php foreach ($departamentos as $dept): ?>
                    <li><a href="#"><?php echo htmlspecialchars($dept['nombre']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Enlaces Rápidos</h3>
                <ul>
                    <li><a href="#inicio">Inicio</a></li>
                    <li><a href="#eventos">Eventos</a></li>
                    <li><a href="#aplicaciones">Aplicaciones</a></li>
                    <li><a href="#noticias">Noticias</a></li>
                    <li><a href="admin/login.php">Administración</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Automotriz Corp. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script>
        // Pasar fechas de eventos a JavaScript
        let eventDates = <?php echo json_encode(array_values($eventDates)); ?>;
    </script>
    <script src="assets/js/main.js"></script>
</body>
</html>
