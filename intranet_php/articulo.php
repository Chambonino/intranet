<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$id = $_GET['id'] ?? null;
if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM articulos WHERE id = ? AND activo = 1");
$stmt->execute([$id]);
$articulo = $stmt->fetch();
if (!$articulo) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($articulo['titulo']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .article-page { max-width: 800px; margin: 0 auto; padding: 40px 20px; }
        .article-page h1 { font-size: 2rem; margin-bottom: 10px; }
        .article-page .meta { color: var(--text-muted); font-size: 0.85rem; margin-bottom: 25px; display: flex; gap: 20px; }
        .article-page .feat-img { width: 100%; max-height: 400px; object-fit: cover; border-radius: 12px; margin-bottom: 25px; }
        .article-page .body { font-size: 1rem; line-height: 1.8; color: var(--text-secondary); }
        .article-page .body p { margin-bottom: 15px; }
        .back-btn { display: inline-flex; align-items: center; gap: 8px; color: var(--accent-red); text-decoration: none; font-weight: 500; margin-bottom: 25px; }
        .back-btn:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo-text">
                <h1>AUTOMOTRIZ CORP</h1>
                <span>INYECCI&Oacute;N &bull; CROMADO &bull; PINTURA</span>
            </div>
        </div>
    </header>
    <main class="article-page">
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Volver</a>
        <h1><?php echo htmlspecialchars($articulo['titulo']); ?></h1>
        <div class="meta">
            <span><i class="fas fa-calendar"></i> <?php echo formatearFecha($articulo['fecha_publicacion'], 'completo'); ?></span>
            <?php if ($articulo['autor']): ?><span><i class="fas fa-user"></i> <?php echo htmlspecialchars($articulo['autor']); ?></span><?php endif; ?>
        </div>
        <?php if ($articulo['imagen']): ?>
        <img src="assets/uploads/articles/<?php echo $articulo['imagen']; ?>" class="feat-img" alt="">
        <?php endif; ?>
        <div class="body"><?php echo nl2br(htmlspecialchars($articulo['contenido'])); ?></div>
    </main>
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Automotriz Corp.</p>
    </footer>
</body>
</html>
