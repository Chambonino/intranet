<?php
/**
 * BOM (Bill of Materials) - Consulta de materiales
 * Conecta a SQL Server y muestra resultados agrupados
 */
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Conexión a SQL Server
$serverName = "192.102.158.2";
$connectionInfo = array("Database" => "rtg", "UID" => "ryty", "PWD" => "yryryrujjg");
$conn = sqlsrv_connect($serverName, $connectionInfo);
$connError = ($conn === false);

// Obtener lista de materiales para el select
$materiales = [];
if (!$connError) {
    $sql = "SELECT DISTINCT M.MATNR, T.MAKTX FROM prd.MARA M INNER JOIN prd.MAST MA ON M.MATNR=MA.MATNR INNER JOIN prd.STPO S ON S.STLNR=MA.STLNR INNER JOIN prd.MAKT T ON M.MATNR=T.MATNR AND T.MANDT=300 AND T.SPRAS='S' WHERE M.MTART='FERT' AND M.MANDT=300 ORDER BY M.MATNR";
    $cursorType = array("Scrollable" => SQLSRV_CURSOR_KEYSET);
    $getProducts = sqlsrv_query($conn, $sql, array(), $cursorType);
    if ($getProducts) {
        while ($row = sqlsrv_fetch_array($getProducts, SQLSRV_FETCH_ASSOC)) {
            $materiales[] = $row;
        }
    }
}

// Procesar búsqueda
$resultados = [];
$materialBuscado = '';
$componenteBuscado = $_GET['componente'] ?? '';

if (isset($_GET['codigo']) && $_GET['codigo'] !== '') {
    $materialBuscado = $_GET['codigo'];
    
    if (!$connError) {
        set_time_limit(0);
        $tsql_callSP = "{call Z_B (?, ?, ?, ?, ?, ?, ?, ?, ?)}";
        $codigo = $materialBuscado;
        $material = ''; $submaterial = ''; $nombre = ''; $grupo = ''; $componente = ''; $maktx = ''; $cantidad = ''; $unidad = '';
        
        $params = array(
            array(&$codigo, SQLSRV_PARAM_IN),
            array(&$material, SQLSRV_PARAM_OUT),
            array(&$submaterial, SQLSRV_PARAM_OUT),
            array(&$nombre, SQLSRV_PARAM_OUT),
            array(&$grupo, SQLSRV_PARAM_OUT),
            array(&$componente, SQLSRV_PARAM_OUT),
            array(&$maktx, SQLSRV_PARAM_OUT),
            array(&$cantidad, SQLSRV_PARAM_OUT),
            array(&$unidad, SQLSRV_PARAM_OUT)
        );
        
        $stmt = sqlsrv_query($conn, $tsql_callSP, $params);
        if ($stmt) {
            while ($row = sqlsrv_fetch_object($stmt)) {
                $resultados[] = [
                    'material' => $row->MATERIAL,
                    'submaterial' => $row->MATERIAL2,
                    'nombre' => $row->NOMBREMAT,
                    'grupo' => $row->GRUPO,
                    'componente' => $row->COMPONENTE,
                    'nom_componente' => $row->MAKTX,
                    'cantidad' => $row->CANTIDAD,
                    'unidad' => $row->UNIDAD
                ];
            }
        }
    }
    
    // Filtrar por componente si se especificó
    if ($componenteBuscado) {
        $resultados = array_filter($resultados, function($r) use ($componenteBuscado) {
            return stripos($r['componente'], $componenteBuscado) !== false || stripos($r['nom_componente'], $componenteBuscado) !== false;
        });
        $resultados = array_values($resultados);
    }
}

// Exportar a Excel
if (isset($_GET['export']) && $_GET['export'] === 'excel' && count($resultados) > 0) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="BOM_' . $materialBuscado . '_' . date('Ymd_His') . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo '<html><head><meta charset="UTF-8"></head><body>';
    echo '<table border="1">';
    echo '<tr><th>Material</th><th>Submaterial</th><th>Nombre Material</th><th>Grupo</th><th>Componente</th><th>Nom. Componente</th><th>Cantidad</th><th>Unidad</th></tr>';
    foreach ($resultados as $r) {
        echo '<tr>';
        echo '<td>' . $r['material'] . '</td>';
        echo '<td>' . $r['submaterial'] . '</td>';
        echo '<td>' . $r['nombre'] . '</td>';
        echo '<td>' . $r['grupo'] . '</td>';
        echo '<td>' . $r['componente'] . '</td>';
        echo '<td>' . $r['nom_componente'] . '</td>';
        echo '<td>' . $r['cantidad'] . '</td>';
        echo '<td>' . $r['unidad'] . '</td>';
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit;
}

// Agrupar resultados por material y submaterial
$agrupados = [];
foreach ($resultados as $r) {
    $key = $r['material'] . '|' . $r['submaterial'];
    if (!isset($agrupados[$key])) {
        $agrupados[$key] = [
            'material' => $r['material'],
            'submaterial' => $r['submaterial'],
            'nombre' => $r['nombre'],
            'componentes' => []
        ];
    }
    $agrupados[$key]['componentes'][] = $r;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOM - Lista de Materiales</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .bom-page { max-width: 1400px; margin: 0 auto; padding: 20px 40px 40px; }
        .bom-filters { background: var(--bg-card); border-radius: 12px; padding: 25px; margin-bottom: 25px; }
        .bom-filters-row { display: flex; gap: 15px; align-items: end; flex-wrap: wrap; }
        .bom-field { flex: 1; min-width: 200px; }
        .bom-field label { display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 6px; }
        .bom-field select, .bom-field input { width: 100%; padding: 10px 14px; background: var(--bg-input); border: 1px solid var(--border-color); border-radius: 8px; color: white; font-size: 0.9rem; }
        .bom-btn { padding: 10px 22px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; }
        .bom-btn-primary { background: var(--accent-blue); color: white; }
        .bom-btn-success { background: var(--accent-green); color: white; }
        .bom-btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .bom-stats { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .bom-stat { background: var(--bg-card); border-radius: 10px; padding: 15px 20px; display: flex; align-items: center; gap: 12px; }
        .bom-stat-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1rem; }
        .bom-stat-num { font-size: 1.4rem; font-weight: 800; }
        .bom-stat-label { font-size: 0.7rem; color: var(--text-muted); }
        .bom-group { background: var(--bg-card); border-radius: 12px; margin-bottom: 20px; overflow: hidden; }
        .bom-group-header { padding: 15px 20px; background: var(--bg-input); display: flex; justify-content: space-between; align-items: center; cursor: pointer; border-left: 4px solid var(--accent-blue); }
        .bom-group-header:hover { background: var(--bg-card-hover); }
        .bom-group-title { font-size: 0.9rem; font-weight: 700; }
        .bom-group-sub { font-size: 0.75rem; color: var(--text-muted); margin-top: 3px; }
        .bom-group-badge { background: var(--accent-blue); color: white; padding: 3px 10px; border-radius: 12px; font-size: 0.7rem; font-weight: 600; }
        .bom-group-body { padding: 0; }
        .bom-table { width: 100%; border-collapse: collapse; }
        .bom-table th { background: rgba(25,118,210,0.1); padding: 10px 15px; text-align: left; font-size: 0.75rem; color: var(--accent-blue); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-color); }
        .bom-table td { padding: 10px 15px; border-bottom: 1px solid var(--border-color); font-size: 0.82rem; color: var(--text-secondary); }
        .bom-table tr:hover td { background: rgba(255,255,255,0.02); }
        .bom-table tr:last-child td { border-bottom: none; }
        .bom-empty { text-align: center; padding: 60px; color: var(--text-muted); }
        .bom-empty i { font-size: 3rem; opacity: 0.3; margin-bottom: 15px; display: block; }
        .conn-error { background: rgba(229,57,53,0.1); border: 1px solid rgba(229,57,53,0.3); border-radius: 10px; padding: 20px; color: #ef5350; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; }
    </style>
</head>
<body>
    <header class="header" style="background: url('assets/img/fondo1.png') center/cover; position:fixed;top:0;left:0;right:0;z-index:1000;">
        <div style="position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(27,32,40,0.85);"></div>
        <div class="header-content" style="position:relative;z-index:1;">
            <div style="display:flex;align-items:center;gap:15px;">
                <img src="assets/img/logo.png" alt="NB" style="height:55px;">
            </div>
            <a href="index.php" style="color:white;text-decoration:none;font-weight:500;"><i class="fas fa-home"></i> Inicio</a>
        </div>
    </header>
    <div style="height:110px;"></div>

    <main class="bom-page">
        <h2 style="margin-bottom:20px;display:flex;align-items:center;gap:10px;">
            <i class="fas fa-cubes" style="color:var(--accent-blue);"></i> BOM - Lista de Materiales
        </h2>

        <?php if ($connError): ?>
        <div class="conn-error">
            <i class="fas fa-exclamation-triangle" style="font-size:1.5rem;"></i>
            <div><strong>Error de conexi&oacute;n</strong><p style="font-size:0.85rem;margin-top:5px;">No se pudo conectar al servidor SQL Server. Verifique la configuraci&oacute;n.</p></div>
        </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="bom-filters">
            <form method="GET">
                <div class="bom-filters-row">
                    <div class="bom-field" style="flex:2;">
                        <label><i class="fas fa-box"></i> Material</label>
                        <select name="codigo">
                            <option value="0000000000">-- Todos los materiales --</option>
                            <?php foreach ($materiales as $mat): ?>
                            <option value="<?php echo $mat['MATNR']; ?>" <?php echo $materialBuscado === $mat['MATNR'] ? 'selected' : ''; ?>>
                                <?php echo $mat['MATNR']; ?>: <?php echo $mat['MAKTX']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="bom-field">
                        <label><i class="fas fa-search"></i> Buscar Componente</label>
                        <input type="text" name="componente" placeholder="Filtrar por componente..." value="<?php echo htmlspecialchars($componenteBuscado); ?>">
                    </div>
                    <button type="submit" class="bom-btn bom-btn-primary"><i class="fas fa-search"></i> Buscar</button>
                    <?php if (count($resultados) > 0): ?>
                    <a href="?codigo=<?php echo urlencode($materialBuscado); ?>&componente=<?php echo urlencode($componenteBuscado); ?>&export=excel" class="bom-btn bom-btn-success"><i class="fas fa-file-excel"></i> Exportar Excel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Estadísticas -->
        <?php if (count($resultados) > 0): ?>
        <div class="bom-stats">
            <div class="bom-stat">
                <div class="bom-stat-icon" style="background:var(--accent-blue);"><i class="fas fa-cubes"></i></div>
                <div><div class="bom-stat-num"><?php echo count($agrupados); ?></div><div class="bom-stat-label">Materiales</div></div>
            </div>
            <div class="bom-stat">
                <div class="bom-stat-icon" style="background:var(--accent-green);"><i class="fas fa-puzzle-piece"></i></div>
                <div><div class="bom-stat-num"><?php echo count($resultados); ?></div><div class="bom-stat-label">Componentes</div></div>
            </div>
            <div class="bom-stat">
                <div class="bom-stat-icon" style="background:var(--accent-orange);"><i class="fas fa-layer-group"></i></div>
                <div><div class="bom-stat-num"><?php echo count(array_unique(array_column($resultados, 'grupo'))); ?></div><div class="bom-stat-label">Grupos</div></div>
            </div>
        </div>

        <!-- Resultados agrupados -->
        <?php foreach ($agrupados as $grupo): ?>
        <div class="bom-group">
            <div class="bom-group-header" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? 'block' : 'none'">
                <div>
                    <div class="bom-group-title"><i class="fas fa-box" style="color:var(--accent-blue);margin-right:8px;"></i><?php echo htmlspecialchars($grupo['nombre']); ?></div>
                    <div class="bom-group-sub">Material: <?php echo $grupo['material']; ?> &bull; Submaterial: <?php echo $grupo['submaterial']; ?></div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <span class="bom-group-badge"><?php echo count($grupo['componentes']); ?> componentes</span>
                    <i class="fas fa-chevron-down" style="color:var(--text-muted);"></i>
                </div>
            </div>
            <div class="bom-group-body">
                <table class="bom-table">
                    <thead>
                        <tr>
                            <th>Grupo</th>
                            <th>Componente</th>
                            <th>Nombre Componente</th>
                            <th style="text-align:right;">Cantidad</th>
                            <th>Unidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grupo['componentes'] as $comp): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($comp['grupo']); ?></td>
                            <td style="font-weight:600;color:var(--text-primary);"><?php echo htmlspecialchars($comp['componente']); ?></td>
                            <td><?php echo htmlspecialchars($comp['nom_componente']); ?></td>
                            <td style="text-align:right;font-weight:600;"><?php echo $comp['cantidad']; ?></td>
                            <td><span style="background:rgba(25,118,210,0.1);padding:2px 8px;border-radius:4px;font-size:0.75rem;color:var(--accent-blue);"><?php echo htmlspecialchars($comp['unidad']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>

        <?php elseif ($materialBuscado): ?>
        <div class="bom-empty">
            <i class="fas fa-search"></i>
            <p>No se encontraron resultados<?php echo $componenteBuscado ? ' para el componente "' . htmlspecialchars($componenteBuscado) . '"' : ''; ?></p>
        </div>
        <?php else: ?>
        <div class="bom-empty">
            <i class="fas fa-cubes"></i>
            <p>Seleccione un material para consultar su lista de componentes (BOM)</p>
        </div>
        <?php endif; ?>
    </main>

    <footer class="footer"><p>&copy; <?php echo date('Y'); ?> Automotriz Corp.</p></footer>
</body>
</html>
