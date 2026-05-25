<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$id = $_GET['id'] ?? null;
if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM articulos WHERE id = ? AND activo = 1");
$stmt->execute([$id]);
$articulo = $stmt->fetch();
if (!$articulo) { header('Location: index.php'); exit; }

// Tiempo estimado de lectura
$palabras = str_word_count(strip_tags($articulo['contenido']));
$minutosLectura = max(1, ceil($palabras / 200));

// Artículos relacionados (los 3 más recientes que no sean este)
$relStmt = $pdo->prepare("SELECT id, titulo, imagen, fecha_publicacion FROM articulos WHERE id != ? AND activo = 1 ORDER BY fecha_publicacion DESC LIMIT 3");
$relStmt->execute([$id]);
$relacionados = $relStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($articulo['titulo']); ?> - Automotriz Corp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700;800;900&family=Source+Serif+Pro:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo date('YmdHis'); ?>">
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }

        /* ====== HERO ====== */
        .art-hero {
            position: relative;
            min-height: 480px;
            display: flex;
            align-items: flex-end;
            padding: 60px 40px 70px;
            overflow: hidden;
            background: #0a0a0a;
        }
        .art-hero-bg {
            position: absolute; inset: 0;
            background-size: cover; background-position: center;
            filter: brightness(0.55);
            transform: scale(1.05);
            transition: transform 8s ease;
        }
        .art-hero:hover .art-hero-bg { transform: scale(1); }
        .art-hero-overlay {
            position: absolute; inset: 0;
            background: linear-gradient(180deg, transparent 0%, transparent 40%, rgba(10,10,10,0.85) 80%, rgba(10,10,10,0.98) 100%);
        }
        .art-hero-content {
            position: relative; z-index: 2;
            max-width: 1100px; margin: 0 auto; width: 100%;
            color: white;
        }
        .art-breadcrumb {
            display: inline-flex; align-items: center; gap: 10px;
            font-size: 0.75rem; color: rgba(255,255,255,0.7);
            text-transform: uppercase; letter-spacing: 2px;
            margin-bottom: 22px; font-weight: 500;
        }
        .art-breadcrumb a { color: rgba(255,255,255,0.85); text-decoration: none; }
        .art-breadcrumb a:hover { color: white; }
        .art-breadcrumb i { font-size: 0.55rem; }
        .art-category-tag {
            display: inline-block;
            padding: 6px 16px;
            background: #e53935;
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            border-radius: 20px;
            margin-bottom: 18px;
        }
        .art-hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 4.5vw, 3.5rem);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -1px;
            margin-bottom: 24px;
            max-width: 950px;
            text-shadow: 0 2px 20px rgba(0,0,0,0.4);
        }
        .art-meta-row {
            display: flex; flex-wrap: wrap; gap: 25px;
            font-size: 0.85rem; color: rgba(255,255,255,0.9);
            padding-top: 18px;
            border-top: 1px solid rgba(255,255,255,0.18);
            max-width: 700px;
        }
        .art-meta-row .meta-item { display: inline-flex; align-items: center; gap: 8px; }
        .art-meta-row .meta-item i { color: #e53935; }
        .art-meta-row .meta-item strong { color: white; font-weight: 600; }

        /* ====== BODY ====== */
        .art-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 50px 30px 80px;
            font-family: 'Source Serif Pro', Georgia, serif;
        }
        .art-lead {
            font-size: 1.3rem;
            line-height: 1.55;
            color: var(--text-secondary);
            font-style: italic;
            margin-bottom: 32px;
            padding-left: 24px;
            border-left: 4px solid #e53935;
        }
        .art-body {
            font-size: 1.1rem;
            line-height: 1.85;
            color: var(--text-secondary);
        }
        .art-body p { margin-bottom: 1.4em; }
        .art-body p:first-of-type::first-letter {
            font-family: 'Playfair Display', serif;
            font-size: 4.5rem;
            font-weight: 800;
            float: left;
            line-height: 0.85;
            padding: 8px 12px 0 0;
            color: #e53935;
        }
        .art-body h1, .art-body h2, .art-body h3, .art-body h4 {
            font-family: 'Playfair Display', serif;
            color: var(--text-primary);
            margin: 1.8em 0 0.6em;
            line-height: 1.25;
            font-weight: 700;
        }
        .art-body h1 { font-size: 2rem; }
        .art-body h2 { font-size: 1.6rem; padding-bottom: 10px; border-bottom: 2px solid rgba(229,57,53,0.4); }
        .art-body h3 { font-size: 1.35rem; }
        .art-body img {
            max-width: 100%; height: auto;
            border-radius: 12px;
            margin: 24px 0;
            box-shadow: 0 16px 40px rgba(0,0,0,0.5);
        }
        .art-body figure { margin: 30px 0; }
        .art-body figcaption { font-size: 0.82rem; color: var(--text-muted); text-align: center; margin-top: 10px; font-style: italic; }
        .art-body ul, .art-body ol { padding-left: 1.8em; margin-bottom: 1.4em; }
        .art-body li { margin-bottom: 0.5em; }
        .art-body a { color: #42a5f5; text-decoration: none; border-bottom: 1px dashed rgba(66,165,245,0.5); }
        .art-body a:hover { border-bottom-style: solid; color: #64b5f6; }
        .art-body blockquote {
            border-left: 4px solid #e53935;
            background: rgba(229,57,53,0.08);
            padding: 22px 30px;
            margin: 30px 0;
            border-radius: 0 14px 14px 0;
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            font-style: italic;
            color: var(--text-primary);
            position: relative;
        }
        .art-body blockquote::before {
            content: '"';
            position: absolute;
            top: -10px; left: 16px;
            font-size: 5rem;
            color: rgba(229,57,53,0.3);
            font-family: Georgia, serif;
            line-height: 1;
        }
        .art-body table {
            width: 100%;
            border-collapse: collapse;
            margin: 24px 0;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
        }
        .art-body th { background: rgba(229,57,53,0.15); padding: 12px 16px; text-align: left; font-weight: 700; color: var(--text-primary); border-bottom: 2px solid #e53935; }
        .art-body td { padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .art-body hr { border: none; height: 1px; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent); margin: 40px 0; }

        /* Compartir + acciones */
        .art-footer-actions {
            margin-top: 50px; padding-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.1);
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;
        }
        .art-share { display: flex; gap: 10px; align-items: center; font-family: 'Inter', sans-serif; }
        .art-share span { font-size: 0.78rem; color: var(--text-muted); margin-right: 5px; }
        .art-share-btn {
            width: 38px; height: 38px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,0.08);
            color: var(--text-secondary);
            cursor: pointer; border: none;
            transition: all 0.25s;
            text-decoration: none;
        }
        .art-share-btn:hover { background: #e53935; color: white; transform: translateY(-2px); }
        .art-back-link {
            display: inline-flex; align-items: center; gap: 8px;
            color: var(--accent-blue); text-decoration: none;
            font-family: 'Inter', sans-serif; font-weight: 600; font-size: 0.85rem;
            padding: 10px 18px;
            background: rgba(25,118,210,0.15);
            border-radius: 8px;
            transition: background 0.2s;
        }
        .art-back-link:hover { background: rgba(25,118,210,0.3); }

        /* ====== RELACIONADOS ====== */
        .art-related {
            background: var(--bg-card);
            padding: 60px 30px;
            border-top: 1px solid rgba(255,255,255,0.08);
        }
        .art-related-inner { max-width: 1100px; margin: 0 auto; }
        .art-related h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.7rem; font-weight: 700;
            text-align: center; margin-bottom: 35px;
            color: var(--text-primary);
        }
        .art-related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 22px;
        }
        .art-rel-card {
            background: var(--bg-input);
            border-radius: 14px;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .art-rel-card:hover { transform: translateY(-6px); box-shadow: 0 16px 40px rgba(0,0,0,0.5); }
        .art-rel-card img, .art-rel-card .art-rel-placeholder {
            width: 100%; height: 160px; object-fit: cover; display: block;
            background: linear-gradient(135deg,#1a1a1a,#333);
        }
        .art-rel-card .art-rel-placeholder { display: flex; align-items: center; justify-content: center; }
        .art-rel-card .art-rel-placeholder i { font-size: 2.5rem; color: #555; }
        .art-rel-card .art-rel-body { padding: 18px 20px; font-family: 'Inter', sans-serif; }
        .art-rel-card .art-rel-date { font-size: 0.7rem; color: var(--accent-blue); font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
        .art-rel-card .art-rel-title { font-size: 1.02rem; font-weight: 700; color: var(--text-primary); line-height: 1.35; }

        @media (max-width: 768px) {
            .art-hero { padding: 40px 25px 50px; min-height: 360px; }
            .art-hero h1 { font-size: 1.9rem; }
            .art-container { padding: 35px 22px 55px; }
            .art-lead { font-size: 1.1rem; }
            .art-body { font-size: 1rem; }
            .art-body p:first-of-type::first-letter { font-size: 3.5rem; }
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
    <section class="art-hero">
        <?php if ($articulo['imagen']): ?>
        <div class="art-hero-bg" style="background-image:url('assets/uploads/articles/<?php echo htmlspecialchars($articulo['imagen']); ?>');"></div>
        <?php else: ?>
        <div class="art-hero-bg" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0a0a0a 100%);"></div>
        <?php endif; ?>
        <div class="art-hero-overlay"></div>
        <div class="art-hero-content">
            <div class="art-breadcrumb">
                <a href="index.php"><i class="fas fa-home"></i> Inicio</a>
                <i class="fas fa-chevron-right"></i>
                <a href="noticias.php">Noticias</a>
                <i class="fas fa-chevron-right"></i>
                <span style="color:white;">Artículo</span>
            </div>
            <span class="art-category-tag"><i class="fas fa-newspaper"></i> Artículo</span>
            <h1><?php echo htmlspecialchars($articulo['titulo']); ?></h1>
            <div class="art-meta-row">
                <div class="meta-item"><i class="far fa-calendar"></i> <strong><?php echo formatearFecha($articulo['fecha_publicacion'], 'completo'); ?></strong></div>
                <?php if (!empty($articulo['autor'])): ?>
                <div class="meta-item"><i class="far fa-user"></i> Por <strong><?php echo htmlspecialchars($articulo['autor']); ?></strong></div>
                <?php endif; ?>
                <div class="meta-item"><i class="far fa-clock"></i> <strong><?php echo $minutosLectura; ?> min</strong> de lectura</div>
                <div class="meta-item"><i class="far fa-eye"></i> <strong><?php echo number_format($palabras); ?></strong> palabras</div>
            </div>
        </div>
    </section>

    <!-- ARTÍCULO -->
    <article class="art-container">
        <?php
        // Extraer primer párrafo como "lead" (entrada destacada)
        $contenido = $articulo['contenido'];
        if (strip_tags($contenido) === $contenido) {
            // Texto plano - convertir a párrafos
            $contenido = '<p>' . nl2br(htmlspecialchars($contenido)) . '</p>';
        }
        ?>
        <div class="art-body">
            <?php echo $contenido; ?>
        </div>

        <div class="art-footer-actions">
            <a href="index.php" class="art-back-link"><i class="fas fa-arrow-left"></i> Volver al inicio</a>
            <div class="art-share">
                <span>Compartir:</span>
                <button class="art-share-btn" onclick="copyArtLink(this)" title="Copiar enlace"><i class="fas fa-link"></i></button>
                <a class="art-share-btn" href="mailto:?subject=<?php echo urlencode($articulo['titulo']); ?>&body=<?php echo urlencode('Mira este artículo: '); ?>" title="Email"><i class="fas fa-envelope"></i></a>
                <button class="art-share-btn" onclick="window.print()" title="Imprimir"><i class="fas fa-print"></i></button>
            </div>
        </div>
    </article>

    <!-- RELACIONADOS -->
    <?php if (count($relacionados) > 0): ?>
    <section class="art-related">
        <div class="art-related-inner">
            <h3>Otros artículos que te pueden interesar</h3>
            <div class="art-related-grid">
                <?php foreach ($relacionados as $rel): ?>
                <a href="articulo.php?id=<?php echo $rel['id']; ?>" class="art-rel-card">
                    <?php if ($rel['imagen']): ?>
                    <img src="assets/uploads/articles/<?php echo htmlspecialchars($rel['imagen']); ?>" alt="">
                    <?php else: ?>
                    <div class="art-rel-placeholder"><i class="fas fa-newspaper"></i></div>
                    <?php endif; ?>
                    <div class="art-rel-body">
                        <div class="art-rel-date"><?php echo formatearFecha($rel['fecha_publicacion']); ?></div>
                        <div class="art-rel-title"><?php echo htmlspecialchars($rel['titulo']); ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Automotriz Corp. | <a href="index.php" style="color:var(--text-muted);text-decoration:none;">Volver al inicio</a></p>
    </footer>

    <script>
    function copyArtLink(btn) {
        var url = window.location.href;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function() {
                var prev = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i>';
                btn.style.background = '#43a047'; btn.style.color = 'white';
                setTimeout(function() { btn.innerHTML = prev; btn.style.background = ''; btn.style.color = ''; }, 1500);
            });
        } else { prompt('Copia este enlace:', url); }
    }
    </script>
</body>
</html>
