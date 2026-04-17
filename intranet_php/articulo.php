<?php
/**
 * Ver artículo completo
 */
require_once 'includes/config.php';
require_once 'includes/functions.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM articulos WHERE id = ? AND activo = 1");
$stmt->execute([$id]);
$articulo = $stmt->fetch();

if (!$articulo) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($articulo['titulo']); ?> - Intranet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .article-full {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .article-full-header {
            margin-bottom: 30px;
        }
        .article-full-header h1 {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #1a1a1a;
        }
        .article-meta-full {
            display: flex;
            gap: 20px;
            color: #666;
            font-size: 0.95rem;
        }
        .article-full-image {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        .article-content-full {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #333;
        }
        .article-content-full p {
            margin-bottom: 20px;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #e53935;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 30px;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
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
                    <li><a href="index.php"><i class="fas fa-home"></i> Inicio</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="article-full">
        <a href="index.php#noticias" class="back-link">
            <i class="fas fa-arrow-left"></i> Volver a noticias
        </a>
        
        <article>
            <header class="article-full-header">
                <h1><?php echo htmlspecialchars($articulo['titulo']); ?></h1>
                <div class="article-meta-full">
                    <span><i class="fas fa-calendar"></i> <?php echo formatearFecha($articulo['fecha_publicacion'], 'completo'); ?></span>
                    <?php if ($articulo['autor']): ?>
                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($articulo['autor']); ?></span>
                    <?php endif; ?>
                </div>
            </header>
            
            <?php if ($articulo['imagen']): ?>
            <img src="assets/uploads/articles/<?php echo $articulo['imagen']; ?>" 
                 alt="<?php echo htmlspecialchars($articulo['titulo']); ?>" 
                 class="article-full-image">
            <?php endif; ?>
            
            <div class="article-content-full">
                <?php echo nl2br(htmlspecialchars($articulo['contenido'])); ?>
            </div>
        </article>
    </main>

    <footer class="footer">
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Automotriz Corp. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html>
