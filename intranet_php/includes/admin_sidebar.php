<?php
/**
 * Sidebar central del panel admin.
 * Uso: definir $activeMenu = 'slider' (o la clave correspondiente) ANTES del include.
 *      Luego: include __DIR__ . '/../includes/admin_sidebar.php';
 */
if (!function_exists('getSeccionesAdminPanel')) {
    require_once __DIR__ . '/config.php';
}
$activeMenu = $activeMenu ?? '';
$secciones  = getSeccionesAdminPanel();
$grupos     = [];
foreach ($secciones as $key => $meta) {
    $grupos[$meta[2]][$key] = $meta;
}
$ordenGrupos = ['Contenido','Configuración','Administración'];
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <img src="../assets/img/logo.png" alt="Logo" onerror="this.style.display='none'">
        <h2>Intranet Admin</h2>
    </div>
    <nav class="sidebar-menu">
        <div class="menu-section">
            <span class="menu-section-title">Principal</span>
            <a href="index.php" class="<?php echo $activeMenu === 'index' ? 'active' : ''; ?>" data-testid="menu-dashboard"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        </div>
        <?php foreach ($ordenGrupos as $grupo): if (empty($grupos[$grupo])) continue; ?>
        <?php
            // Calcular si hay al menos un item visible para este grupo
            $itemsVisibles = [];
            foreach ($grupos[$grupo] as $k => $m) {
                if (tienePermisoSeccion($k)) $itemsVisibles[$k] = $m;
            }
            if (empty($itemsVisibles)) continue;
        ?>
        <div class="menu-section">
            <span class="menu-section-title"><?php echo htmlspecialchars($grupo); ?></span>
            <?php foreach ($itemsVisibles as $k => $m): ?>
            <a href="<?php echo $k; ?>.php" class="<?php echo $activeMenu === $k ? 'active' : ''; ?>" data-testid="menu-<?php echo $k; ?>"><i class="fas <?php echo $m[1]; ?>"></i> <span><?php echo htmlspecialchars($m[0]); ?></span></a>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </nav>
</aside>
