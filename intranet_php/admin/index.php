<?php
/**
 * Dashboard Principal de Administración
 * Intranet Corporativa
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin();

// Obtener estadísticas
$stats = [
    'usuarios' => $pdo->query("SELECT COUNT(*) FROM administradores WHERE activo = 1")->fetchColumn(),
    'noticias' => $pdo->query("SELECT COUNT(*) FROM slider_noticias WHERE activo = 1")->fetchColumn(),
    'eventos' => $pdo->query("SELECT COUNT(*) FROM eventos WHERE activo = 1")->fetchColumn(),
    'articulos' => $pdo->query("SELECT COUNT(*) FROM articulos WHERE activo = 1")->fetchColumn(),
];

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Panel de Administración</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/img/logo.png" alt="Logo" onerror="this.style.display='none'">
                <h2>Intranet Admin</h2>
            </div>
            <nav class="sidebar-menu">
                <div class="menu-section">
                    <span class="menu-section-title">Principal</span>
                    <a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
                </div>
                <div class="menu-section">
                    <span class="menu-section-title">Contenido</span>
                    <a href="slider.php"><i class="fas fa-images"></i> <span>Slider Noticias</span></a>
                    <a href="eventos.php"><i class="fas fa-calendar-alt"></i> <span>Eventos</span></a>
                    <a href="cumpleanos.php"><i class="fas fa-birthday-cake"></i> <span>Cumpleaños</span></a>
                    <a href="galeria.php"><i class="fas fa-photo-video"></i> <span>Galería Fotos</span></a>
                    <a href="videos.php"><i class="fas fa-video"></i> <span>Videos</span></a>
                    <a href="articulos.php"><i class="fas fa-newspaper"></i> <span>Artículos</span></a>
                </div>
                <div class="menu-section">
                    <span class="menu-section-title">Configuración</span>
                    <a href="archivos.php"><i class="fas fa-folder"></i> <span>Archivos Depto.</span></a>
                    <a href="portales.php"><i class="fas fa-external-link-alt"></i> <span>Portales Clientes</span></a>
                    <a href="countdown.php"><i class="fas fa-hourglass-half"></i> <span>Cuenta Regresiva</span></a>
                    <a href="avisos.php"><i class="fas fa-bullhorn"></i> <span>Avisos</span></a>
                    <a href="kpis.php"><i class="fas fa-chart-line"></i> <span>KPIs</span></a>
                    <a href="aplicaciones.php"><i class="fas fa-th"></i> <span>Aplicaciones</span></a>
                    <a href="organigrama.php"><i class="fas fa-sitemap"></i> <span>Organigrama</span></a>
                    <a href="compania.php"><i class="fas fa-building"></i> <span>Compañía</span></a>
                    <a href="usuarios.php"><i class="fas fa-users-cog"></i> <span>Usuarios</span></a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                <div class="user-menu">
                    <span>Hola, <?php echo htmlspecialchars($_SESSION['admin_nombre']); ?></span>
                    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Salir</a>
                </div>
            </div>

            <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $flash['message']; ?>
            </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon red"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['usuarios']; ?></h3>
                        <p>Administradores</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-images"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['noticias']; ?></h3>
                        <p>Slides Activos</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-calendar"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['eventos']; ?></h3>
                        <p>Eventos</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fas fa-newspaper"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['articulos']; ?></h3>
                        <p>Artículos</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-bolt"></i> Acciones Rápidas</h2>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
                        <a href="slider.php?action=add" class="btn btn-primary" style="text-decoration: none;">
                            <i class="fas fa-plus"></i> Nuevo Slide
                        </a>
                        <a href="eventos.php?action=add" class="btn btn-secondary" style="text-decoration: none;">
                            <i class="fas fa-plus"></i> Nuevo Evento
                        </a>
                        <a href="articulos.php?action=add" class="btn btn-success" style="text-decoration: none;">
                            <i class="fas fa-plus"></i> Nuevo Artículo
                        </a>
                        <a href="avisos.php?action=add" class="btn" style="text-decoration: none; background: #ff9800; color: white;">
                            <i class="fas fa-plus"></i> Nuevo Aviso
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                <div class="content-card">
                    <div class="card-header">
                        <h2><i class="fas fa-calendar"></i> Próximos Eventos</h2>
                    </div>
                    <div class="card-body">
                        <?php
                        $proximosEventos = getEventos($pdo, 5);
                        if (count($proximosEventos) > 0):
                            foreach ($proximosEventos as $evento):
                        ?>
                        <div style="display: flex; align-items: center; gap: 15px; padding: 10px 0; border-bottom: 1px solid #eee;">
                            <div style="min-width: 50px; text-align: center; background: #f5f5f5; padding: 8px; border-radius: 8px;">
                                <strong style="font-size: 1.2rem; color: #e53935;"><?php echo date('d', strtotime($evento['fecha_evento'])); ?></strong><br>
                                <small><?php echo strtoupper(substr(date('M', strtotime($evento['fecha_evento'])), 0, 3)); ?></small>
                            </div>
                            <div>
                                <strong><?php echo htmlspecialchars($evento['titulo']); ?></strong>
                                <p style="font-size: 0.85rem; color: #666;"><?php echo htmlspecialchars($evento['lugar'] ?? 'Sin ubicación'); ?></p>
                            </div>
                        </div>
                        <?php 
                            endforeach;
                        else:
                        ?>
                        <p style="text-align: center; color: #666; padding: 20px;">No hay eventos próximos</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header">
                        <h2><i class="fas fa-birthday-cake"></i> Cumpleaños del Mes</h2>
                    </div>
                    <div class="card-body">
                        <?php
                        $cumpleaneros = getCumpleanerosMes($pdo);
                        if (count($cumpleaneros) > 0):
                            foreach (array_slice($cumpleaneros, 0, 5) as $cumple):
                        ?>
                        <div style="display: flex; align-items: center; gap: 15px; padding: 10px 0; border-bottom: 1px solid #eee;">
                            <img src="../assets/uploads/employees/<?php echo $cumple['foto'] ?: 'default.png'; ?>" 
                                 style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;"
                                 onerror="this.src='../assets/img/default-avatar.png'">
                            <div>
                                <strong><?php echo htmlspecialchars($cumple['nombre_completo']); ?></strong>
                                <p style="font-size: 0.85rem; color: #666;">
                                    <?php echo date('d', strtotime($cumple['fecha_nacimiento'])); ?> de este mes
                                </p>
                            </div>
                        </div>
                        <?php 
                            endforeach;
                        else:
                        ?>
                        <p style="text-align: center; color: #666; padding: 20px;">No hay cumpleaños este mes</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
