<?php
/**
 * Detalle de sección de la Compañía (Misión, Visión, Valores, Historia, Objetivos, Política Integral, Reconocimientos)
 * Diseño profesional con hero, breadcrumb e imagen destacada.
 */
require_once 'includes/config.php';
require_once 'includes/functions.php';

$seccion = $_GET['s'] ?? null;
if (!$seccion) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM info_compania WHERE seccion = ? AND activo = 1");
$stmt->execute([$seccion]);
$info = $stmt->fetch();
if (!$info) { header('Location: index.php'); exit; }

// Mapas de iconos, colores y nombres
$iconos = [
    'mision' => 'bullseye', 'vision' => 'eye', 'valores' => 'heart',
    'historia' => 'landmark', 'objetivos' => 'flag-checkered',
    'politica_integral' => 'shield-halved', 'reconocimientos' => 'trophy'
];
$colores = [
    'mision' => '#E53935', 'vision' => '#43A047', 'valores' => '#FF9800',
    'historia' => '#E91E63', 'objetivos' => '#00BCD4',
    'politica_integral' => '#9C27B0', 'reconocimientos' => '#FFD700'
];
$labels = [
    'mision' => 'Nuestra Misión', 'vision' => 'Nuestra Visión', 'valores' => 'Nuestros Valores',
    'historia' => 'Nuestra Historia', 'objetivos' => 'Nuestros Objetivos',
    'politica_integral' => 'Política Integral', 'reconocimientos' => 'Reconocimientos'
];
$subtitulos = [
    'mision' => 'Lo que hacemos día a día',
    'vision' => 'Hacia dónde vamos',
    'valores' => 'En qué creemos',
    'historia' => 'Nuestro recorrido',
    'objetivos' => 'Lo que perseguimos',
    'politica_integral' => 'Nuestro compromiso',
    'reconocimientos' => 'Logros que nos definen'
];

$icono = $iconos[$info['seccion']] ?? 'info-circle';
$color = $colores[$info['seccion']] ?? '#1976D2';
$label = $labels[$info['seccion']] ?? $info['titulo'];
$subt  = $subtitulos[$info['seccion']] ?? '';

// Conversión hex → rgb (para gradientes con opacidad)
$hex = ltrim($color, '#');
$r = hexdec(substr($hex, 0, 2));
$g = hexdec(substr($hex, 2, 2));
$b = hexdec(substr($hex, 4, 2));
$rgb = "$r,$g,$b";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($info['titulo']); ?> - Automotriz Corp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo date('YmdHis'); ?>">
    <style>
        :root { --acc: <?php echo $color; ?>; --acc-rgb: <?php echo $rgb; ?>; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }

        /* ======= HERO ======= */
        .cd-hero {
            position: relative;
            padding: 70px 40px 90px;
            background: linear-gradient(135deg, rgba(var(--acc-rgb), 0.95), rgba(var(--acc-rgb), 0.55)),
                        radial-gradient(ellipse at top right, rgba(255,255,255,0.15), transparent 70%);
            overflow: hidden;
        }
        .cd-hero::before {
            content: '';
            position: absolute;
            top: -100px; right: -100px;
            width: 400px; height: 400px;
            border: 2px solid rgba(255,255,255,0.08);
            border-radius: 50%;
        }
        .cd-hero::after {
            content: '';
            position: absolute;
            bottom: -150px; left: -150px;
            width: 350px; height: 350px;
            background: radial-gradient(circle, rgba(255,255,255,0.1), transparent 60%);
            border-radius: 50%;
        }
        .cd-hero-inner { max-width: 1200px; margin: 0 auto; position: relative; z-index: 2; }
        .cd-breadcrumb {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 0.78rem; color: rgba(255,255,255,0.8);
            margin-bottom: 28px;
            text-transform: uppercase; letter-spacing: 1.5px;
        }
        .cd-breadcrumb a { color: rgba(255,255,255,0.85); text-decoration: none; transition: opacity 0.2s; }
        .cd-breadcrumb a:hover { opacity: 1; color: white; }
        .cd-breadcrumb i { font-size: 0.6rem; }

        .cd-hero-icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: 76px; height: 76px;
            background: rgba(255,255,255,0.18);
            border-radius: 22px;
            backdrop-filter: blur(10px);
            font-size: 2.3rem; color: white;
            margin-bottom: 22px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.25);
        }
        .cd-hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2.4rem, 5vw, 3.8rem);
            font-weight: 800; color: white;
            margin-bottom: 12px; line-height: 1.1;
            letter-spacing: -1px;
        }
        .cd-hero .cd-subtitle {
            font-size: 1.05rem;
            color: rgba(255,255,255,0.92);
            font-weight: 400; max-width: 560px;
            line-height: 1.55;
        }
        .cd-hero .cd-divider {
            width: 70px; height: 4px;
            background: white;
            border-radius: 4px;
            margin: 18px 0 22px;
        }

        /* ======= CONTENT ======= */
        .cd-body {
            max-width: 1200px;
            margin: -50px auto 60px;
            padding: 0 30px;
            position: relative; z-index: 5;
        }
        .cd-content-card {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 50px 60px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.08);
        }
        .cd-content {
            font-family: 'Inter', sans-serif;
            font-size: 1.04rem;
            line-height: 1.85;
            color: var(--text-secondary);
        }
        .cd-content p { margin-bottom: 1.2em; }
        .cd-content h1, .cd-content h2, .cd-content h3, .cd-content h4 {
            color: var(--text-primary);
            margin: 1.5em 0 0.6em;
            font-family: 'Playfair Display', serif;
            line-height: 1.25;
        }
        .cd-content h1 { font-size: 1.8rem; }
        .cd-content h2 { font-size: 1.5rem; }
        .cd-content h3 { font-size: 1.25rem; }
        .cd-content img {
            max-width: 100%; height: auto;
            border-radius: 14px;
            margin: 18px 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .cd-content ul, .cd-content ol { padding-left: 1.6em; margin-bottom: 1.2em; }
        .cd-content li { margin-bottom: 0.45em; }
        .cd-content a { color: var(--acc); text-decoration: none; border-bottom: 1px dashed rgba(var(--acc-rgb), 0.5); }
        .cd-content a:hover { border-bottom-style: solid; }
        .cd-content blockquote {
            border-left: 4px solid var(--acc);
            padding: 14px 22px;
            background: rgba(var(--acc-rgb), 0.08);
            border-radius: 0 12px 12px 0;
            margin: 18px 0;
            font-style: italic;
        }
        .cd-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 18px 0;
            font-size: 0.94rem;
        }
        .cd-content table th { background: rgba(var(--acc-rgb), 0.15); padding: 10px 14px; text-align: left; font-weight: 600; }
        .cd-content table td { padding: 10px 14px; border-top: 1px solid rgba(255,255,255,0.08); }

        /* Imagen destacada */
        .cd-featured-image {
            margin: 0 0 35px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 16px 40px rgba(0,0,0,0.4);
            position: relative;
        }
        .cd-featured-image img { width: 100%; display: block; }
        .cd-featured-image .cd-image-tag {
            position: absolute;
            top: 18px; left: 18px;
            background: rgba(var(--acc-rgb), 0.95);
            color: white;
            padding: 6px 14px;
            border-radius: 18px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            backdrop-filter: blur(8px);
        }

        /* Adjunto destacado */
        .cd-attachment {
            margin-top: 40px;
            padding: 24px 28px;
            background: linear-gradient(135deg, rgba(var(--acc-rgb), 0.15), rgba(var(--acc-rgb), 0.05));
            border: 1px solid rgba(var(--acc-rgb), 0.3);
            border-radius: 16px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .cd-attachment-icon {
            width: 60px; height: 60px;
            background: var(--acc);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.7rem;
            flex-shrink: 0;
            box-shadow: 0 8px 22px rgba(var(--acc-rgb), 0.4);
        }
        .cd-attachment-info { flex: 1; }
        .cd-attachment-info h4 { font-size: 0.78rem; font-weight: 600; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase; letter-spacing: 1px; }
        .cd-attachment-info .cd-att-name { font-size: 1.05rem; font-weight: 600; color: var(--text-primary); }
        .cd-attachment .cd-att-btn {
            padding: 10px 20px;
            background: var(--acc);
            color: white;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .cd-attachment .cd-att-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(var(--acc-rgb), 0.4); }

        /* ======= NAVEGACIÓN A OTRAS SECCIONES ======= */
        .cd-other-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.45rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 60px 0 20px;
            text-align: center;
        }
        .cd-other-sections {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 14px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 30px 60px;
        }
        .cd-other-link {
            position: relative;
            padding: 22px 16px;
            background: var(--bg-card);
            border-radius: 14px;
            text-decoration: none;
            color: var(--text-primary);
            border: 1px solid rgba(255,255,255,0.06);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 8px;
        }
        .cd-other-link:hover { transform: translateY(-4px); border-color: rgba(255,255,255,0.18); box-shadow: 0 12px 30px rgba(0,0,0,0.4); }
        .cd-other-link .cd-other-icon {
            width: 46px; height: 46px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; color: white;
        }
        .cd-other-link span { font-size: 0.82rem; font-weight: 600; line-height: 1.2; }

        @media (max-width: 768px) {
            .cd-hero { padding: 50px 25px 70px; }
            .cd-body { padding: 0 18px; margin-top: -40px; }
            .cd-content-card { padding: 32px 24px; }
            .cd-attachment { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo-text"><img src="assets/img/logo.png" alt="NB" style="height:45px;" onerror="this.style.display='none'"></div>
            <nav><a href="index.php" style="color:white;text-decoration:none;font-weight:500;"><i class="fas fa-home"></i> Inicio</a></nav>
        </div>
    </header>

    <!-- HERO -->
    <section class="cd-hero">
        <div class="cd-hero-inner">
            <div class="cd-breadcrumb">
                <a href="index.php"><i class="fas fa-home"></i> Inicio</a>
                <i class="fas fa-chevron-right"></i>
                <span>Nuestra Compañía</span>
                <i class="fas fa-chevron-right"></i>
                <span style="color:white;font-weight:600;"><?php echo $label; ?></span>
            </div>
            <div class="cd-hero-icon"><i class="fas fa-<?php echo $icono; ?>"></i></div>
            <h1><?php echo htmlspecialchars($info['titulo']); ?></h1>
            <div class="cd-divider"></div>
            <?php if ($subt): ?><p class="cd-subtitle"><?php echo $subt; ?></p><?php endif; ?>
        </div>
    </section>

    <!-- CONTENIDO -->
    <main class="cd-body">
        <article class="cd-content-card">
            <?php if (!empty($info['imagen'])): ?>
            <div class="cd-featured-image">
                <span class="cd-image-tag"><i class="fas fa-image"></i> Imagen destacada</span>
                <img src="assets/uploads/company/<?php echo htmlspecialchars($info['imagen']); ?>" alt="<?php echo htmlspecialchars($info['titulo']); ?>" onerror="this.parentElement.style.display='none'">
            </div>
            <?php endif; ?>

            <div class="cd-content">
                <?php
                $contenido = $info['contenido'];
                // Si no contiene tags HTML, aplicar nl2br para preservar saltos de línea
                if (strip_tags($contenido) === $contenido) {
                    echo nl2br(htmlspecialchars($contenido));
                } else {
                    echo $contenido; // Ya viene con HTML del editor Summernote
                }
                ?>
            </div>

            <?php if (!empty($info['archivo_pdf'])):
                $ext = strtolower(pathinfo($info['archivo_pdf'], PATHINFO_EXTENSION));
                $iconFile = 'fa-file';
                if ($ext === 'pdf')               $iconFile = 'fa-file-pdf';
                elseif (in_array($ext,['doc','docx'])) $iconFile = 'fa-file-word';
                elseif (in_array($ext,['xls','xlsx'])) $iconFile = 'fa-file-excel';
            ?>
            <div class="cd-attachment">
                <div class="cd-attachment-icon"><i class="fas <?php echo $iconFile; ?>"></i></div>
                <div class="cd-attachment-info">
                    <h4>Documento adjunto</h4>
                    <div class="cd-att-name"><?php echo htmlspecialchars($info['archivo_pdf']); ?></div>
                </div>
                <a href="assets/uploads/company/<?php echo htmlspecialchars($info['archivo_pdf']); ?>" target="_blank" class="cd-att-btn">
                    <i class="fas fa-download"></i> Ver / Descargar
                </a>
            </div>
            <?php endif; ?>
        </article>

        <!-- Navegación a otras secciones -->
        <h3 class="cd-other-title">Explora otras secciones</h3>
    </main>

    <nav class="cd-other-sections">
        <?php foreach ($labels as $key => $lab):
            if ($key === $info['seccion']) continue;
            $col = $colores[$key] ?? '#1976D2';
            $ic = $iconos[$key] ?? 'info-circle';
        ?>
        <a href="compania_detalle.php?s=<?php echo $key; ?>" class="cd-other-link">
            <div class="cd-other-icon" style="background:<?php echo $col; ?>;"><i class="fas fa-<?php echo $ic; ?>"></i></div>
            <span><?php echo $lab; ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Automotriz Corp. | <a href="index.php" style="color:var(--text-muted);text-decoration:none;">Volver al inicio</a></p>
    </footer>
</body>
</html>
