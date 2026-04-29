<?php
/**
 * Detalle de Evento
 */
require_once 'includes/config.php';
require_once 'includes/functions.php';

$id = $_GET['id'] ?? null;
if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT e.*, d.nombre as departamento_nombre, d.color as dept_color FROM eventos e LEFT JOIN departamentos d ON e.departamento_id = d.id WHERE e.id = ? AND e.activo = 1");
$stmt->execute([$id]);
$evento = $stmt->fetch();
if (!$evento) { header('Location: index.php'); exit; }

$mesesEsp = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($evento['titulo']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .event-detail { max-width: 800px; margin: 0 auto; padding: 40px 20px; }
        .event-badge { display: inline-block; padding: 4px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; color: white; margin-bottom: 15px; }
        .event-detail h1 { font-size: 2rem; margin-bottom: 10px; }
        .event-meta { display: flex; flex-wrap: wrap; gap: 20px; color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color); }
        .event-meta i { color: var(--accent-blue); margin-right: 5px; }
        .event-body { font-size: 1rem; line-height: 1.8; color: var(--text-secondary); }
        .event-body p { margin-bottom: 15px; }
        .event-file { margin-top: 25px; padding: 20px; background: var(--bg-card); border-radius: 12px; }
        .event-file a { color: var(--accent-blue); text-decoration: none; display: flex; align-items: center; gap: 10px; font-weight: 500; }
        .event-file a:hover { text-decoration: underline; }
        .back-btn { display: inline-flex; align-items: center; gap: 8px; color: var(--accent-red); text-decoration: none; font-weight: 500; margin-bottom: 25px; }
        .back-btn:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <img src="assets/img/logo.png" alt="NB" style="height:45px;">
        </div>
    </header>
    <main class="event-detail">
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Volver</a>
        <?php if ($evento['departamento_nombre']): ?>
        <span class="event-badge" style="background: <?php echo $evento['dept_color'] ?: $evento['color']; ?>;"><?php echo htmlspecialchars($evento['departamento_nombre']); ?></span>
        <?php endif; ?>
        <h1><?php echo htmlspecialchars($evento['titulo']); ?></h1>
        <div class="event-meta">
            <span><i class="fas fa-calendar"></i> <?php echo date('d', strtotime($evento['fecha_evento'])) . ' de ' . $mesesEsp[(int)date('m', strtotime($evento['fecha_evento']))] . ' de ' . date('Y', strtotime($evento['fecha_evento'])); ?></span>
            <?php if ($evento['hora_inicio']): ?><span><i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($evento['hora_inicio'])); ?><?php if ($evento['hora_fin']): ?> - <?php echo date('H:i', strtotime($evento['hora_fin'])); ?><?php endif; ?></span><?php endif; ?>
            <?php if ($evento['lugar']): ?><span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($evento['lugar']); ?></span><?php endif; ?>
        </div>
        <div class="event-body">
            <?php echo nl2br(htmlspecialchars($evento['descripcion'] ?? 'Sin descripción disponible.')); ?>
        </div>
        <?php if ($evento['archivo']): ?>
        <div class="event-file">
            <h4 style="margin-bottom: 10px;"><i class="fas fa-paperclip"></i> Archivo adjunto</h4>
            <a href="assets/uploads/events/<?php echo $evento['archivo']; ?>" download>
                <i class="fas fa-file-download"></i> Descargar <?php echo pathinfo($evento['archivo'], PATHINFO_EXTENSION); ?>
            </a>
        </div>
        <?php endif; ?>
    </main>
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Automotriz Corp.</p>
    </footer>
</body>
</html>
