<?php
/**
 * Detalle de sección de la Compañía (Misión, Visión, Valores)
 */
require_once 'includes/config.php';
require_once 'includes/functions.php';

$seccion = $_GET['s'] ?? null;
if (!$seccion) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM info_compania WHERE seccion = ? AND activo = 1");
$stmt->execute([$seccion]);
$info = $stmt->fetch();
if (!$info) { header('Location: index.php'); exit; }

$iconos = ['mision'=>'fa-bullseye','vision'=>'fa-eye','valores'=>'fa-heart'];
$colores = ['mision'=>'#E53935','vision'=>'#43A047','valores'=>'#FF9800'];
$icono = $iconos[$info['seccion']] ?? 'fa-info-circle';
$color = $colores[$info['seccion']] ?? '#1976D2';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($info['titulo']); ?> - Automotriz Corp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .detail-page { max-width: 800px; margin: 0 auto; padding: 40px 20px; }
        .detail-card { background: var(--bg-card); border-radius: 16px; padding: 40px; border-left: 5px solid <?php echo $color; ?>; }
        .detail-icon { font-size: 3rem; color: <?php echo $color; ?>; margin-bottom: 20px; }
        .detail-title { font-size: 2rem; font-weight: 800; color: <?php echo $color; ?>; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 2px; }
        .detail-content { font-size: 1.1rem; line-height: 2; color: var(--text-secondary); }
        .detail-content p { margin-bottom: 15px; }
        .detail-pdf { margin-top: 30px; padding: 20px; background: var(--bg-input); border-radius: 12px; }
        .detail-pdf a { color: var(--accent-blue); text-decoration: none; display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 1rem; }
        .detail-pdf a:hover { text-decoration: underline; }
        .back-btn { display: inline-flex; align-items: center; gap: 8px; color: var(--accent-red); text-decoration: none; font-weight: 500; margin-bottom: 25px; }
        .back-btn:hover { text-decoration: underline; }
        .other-sections { display: flex; gap: 15px; margin-top: 30px; }
        .other-link { flex: 1; padding: 18px; background: var(--bg-input); border-radius: 12px; text-decoration: none; color: var(--text-primary); text-align: center; transition: all 0.3s; font-weight: 600; }
        .other-link:hover { background: var(--bg-card-hover); transform: translateY(-2px); }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo-text"><h1>AUTOMOTRIZ CORP</h1><span>INYECCI&Oacute;N &bull; CROMADO &bull; PINTURA</span></div>
        </div>
    </header>
    <main class="detail-page">
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Volver al inicio</a>
        <div class="detail-card">
            <div class="detail-icon"><i class="fas <?php echo $icono; ?>"></i></div>
            <h1 class="detail-title"><?php echo htmlspecialchars($info['titulo']); ?></h1>
            <div class="detail-content">
                <?php echo nl2br($info['contenido']); ?>
            </div>
            <?php if (!empty($info['archivo_pdf'])): ?>
            <div class="detail-pdf">
                <h4 style="margin-bottom: 10px; color: var(--text-primary);"><i class="fas fa-file-pdf" style="color: #E53935;"></i> Documento adjunto</h4>
                <a href="assets/uploads/company/<?php echo $info['archivo_pdf']; ?>" target="_blank">
                    <i class="fas fa-download"></i> Ver / Descargar PDF
                </a>
            </div>
            <?php endif; ?>
        </div>
        <div class="other-sections">
            <?php
            $secciones = ['mision'=>'Misión','vision'=>'Visión','valores'=>'Valores'];
            foreach ($secciones as $key => $label):
                if ($key !== $info['seccion']):
            ?>
            <a href="compania_detalle.php?s=<?php echo $key; ?>" class="other-link" style="border-bottom: 3px solid <?php echo $colores[$key]; ?>;">
                <i class="fas <?php echo $iconos[$key]; ?>" style="color: <?php echo $colores[$key]; ?>; margin-right: 5px;"></i> <?php echo $label; ?>
            </a>
            <?php endif; endforeach; ?>
        </div>
    </main>
    <footer class="footer"><p>&copy; <?php echo date('Y'); ?> Automotriz Corp.</p></footer>
</body>
</html>
