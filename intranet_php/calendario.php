<?php
/**
 * Calendario Completo - Vista Mensual
 */
require_once 'includes/config.php';
require_once 'includes/functions.php';

$month = isset($_GET['m']) ? (int)$_GET['m'] : (int)date('m');
$year = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');

if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }

$mesesEsp = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
$diasSemana = ['LUNES','MARTES','MIÉRCOLES','JUEVES','VIERNES','SÁBADO','DOMINGO'];

$primerDia = date('N', mktime(0,0,0,$month,1,$year)); // 1=Lun, 7=Dom
$diasEnMes = (int)date('t', mktime(0,0,0,$month,1,$year));

// Obtener eventos del mes
$stmt = $pdo->prepare("SELECT e.*, d.nombre as departamento_nombre, d.color as dept_color FROM eventos e LEFT JOIN departamentos d ON e.departamento_id = d.id WHERE e.activo = 1 AND MONTH(e.fecha_evento) = ? AND YEAR(e.fecha_evento) = ? ORDER BY e.fecha_evento ASC");
$stmt->execute([$month, $year]);
$eventosDelMes = $stmt->fetchAll();

// Agrupar eventos por día
$eventosPorDia = [];
foreach ($eventosDelMes as $ev) {
    $dia = (int)date('d', strtotime($ev['fecha_evento']));
    $eventosPorDia[$dia][] = $ev;
}

// Obtener departamentos para leyenda
$departamentos = getDepartamentos($pdo);

$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario - <?php echo $mesesEsp[$month] . ' ' . $year; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .cal-page { max-width: 1200px; margin: 0 auto; padding: 20px 40px 40px; }
        .cal-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .cal-top h1 { font-size: 1.5rem; }
        .cal-nav-btns { display: flex; gap: 10px; }
        .cal-nav-btns a { background: var(--bg-card); color: var(--text-primary); padding: 8px 15px; border-radius: 8px; text-decoration: none; transition: background 0.3s; }
        .cal-nav-btns a:hover { background: var(--bg-card-hover); }
        .cal-layout { display: grid; grid-template-columns: 200px 1fr; gap: 25px; }
        .cal-sidebar {}
        .cal-mini-months { background: var(--bg-card); border-radius: 12px; padding: 15px; margin-bottom: 20px; }
        .cal-mini-months h4 { text-align: center; margin-bottom: 10px; font-size: 0.9rem; }
        .mini-months-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 5px; }
        .mini-month { padding: 6px; text-align: center; border-radius: 6px; font-size: 0.75rem; color: var(--text-secondary); cursor: pointer; text-decoration: none; transition: background 0.3s; }
        .mini-month:hover, .mini-month.active { background: var(--accent-blue); color: white; }
        .cal-legend { background: var(--bg-card); border-radius: 12px; padding: 15px; }
        .cal-legend h4 { margin-bottom: 12px; font-size: 0.85rem; }
        .legend-item { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 0.8rem; color: var(--text-secondary); }
        .legend-dot { width: 24px; height: 8px; border-radius: 4px; flex-shrink: 0; }
        .cal-table { background: var(--bg-card); border-radius: 12px; overflow: hidden; }
        .cal-table table { width: 100%; border-collapse: collapse; }
        .cal-table th { background: var(--bg-input); padding: 12px; text-align: center; font-size: 0.85rem; font-weight: 600; color: #ffffff; letter-spacing: 1px; border-bottom: 1px solid var(--border-color); }
        .cal-table td { border: 1px solid var(--border-color); padding: 8px; vertical-align: top; height: 110px; width: 14.28%; }
        .cal-table td .day-num { font-size: 1rem; color: #e0e0e0; margin-bottom: 5px; font-weight: 500; }
        .cal-table td.today .day-num { color: #42a5f5; font-weight: 700; font-size: 1.1rem; }
        .cal-event-bar { display: block; padding: 3px 8px; border-radius: 4px; font-size: 0.7rem; color: white; margin-bottom: 3px; text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: opacity 0.3s; }
        .cal-event-bar:hover { opacity: 0.8; }
        .cal-table td.empty { background: rgba(0,0,0,0.2); }
        @media (max-width: 992px) {
            .cal-layout { grid-template-columns: 1fr; }
            .cal-sidebar { display: flex; gap: 15px; }
            .cal-mini-months, .cal-legend { flex: 1; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo-text"><img src="assets/img/logo.png" alt="NB" style="height:45px;"></div>
            <nav><a href="index.php" style="color: white; text-decoration: none; font-weight: 500;"><i class="fas fa-home"></i> Inicio</a></nav>
        </div>
    </header>
    <main class="cal-page">
        <div class="cal-top">
            <h1><i class="fas fa-calendar-alt" style="color: var(--accent-blue);"></i> <?php echo $mesesEsp[$month] . ' de ' . $year; ?></h1>
            <div class="cal-nav-btns">
                <a href="calendario.php?m=<?php echo $prevMonth; ?>&y=<?php echo $prevYear; ?>"><i class="fas fa-chevron-left"></i></a>
                <a href="calendario.php">Hoy</a>
                <a href="calendario.php?m=<?php echo $nextMonth; ?>&y=<?php echo $nextYear; ?>"><i class="fas fa-chevron-right"></i></a>
            </div>
        </div>
        <div class="cal-layout">
            <div class="cal-sidebar">
                <div class="cal-mini-months">
                    <h4><a href="calendario.php?m=<?php echo $month; ?>&y=<?php echo $year-1; ?>" style="color:var(--text-muted);text-decoration:none;">&laquo;</a> <?php echo $year; ?> <a href="calendario.php?m=<?php echo $month; ?>&y=<?php echo $year+1; ?>" style="color:var(--text-muted);text-decoration:none;">&raquo;</a></h4>
                    <div class="mini-months-grid">
                        <?php $mesesCortos = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
                        for ($i = 1; $i <= 12; $i++): ?>
                        <a href="calendario.php?m=<?php echo $i; ?>&y=<?php echo $year; ?>" class="mini-month <?php echo $i == $month ? 'active' : ''; ?>"><?php echo $mesesCortos[$i-1]; ?></a>
                        <?php endfor; ?>
                    </div>
                    <p style="font-size:0.75rem;color:var(--text-muted);margin-top:10px;">Hoy es <?php echo date('d') . ' de ' . $mesesEsp[(int)date('m')] . ' de ' . date('Y'); ?></p>
                </div>
                <div class="cal-legend">
                    <h4><i class="fas fa-palette"></i> Departamentos</h4>
                    <?php foreach ($departamentos as $dept): ?>
                    <div class="legend-item">
                        <span class="legend-dot" style="background: <?php echo $dept['color']; ?>;"></span>
                        <?php echo htmlspecialchars($dept['nombre']); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="cal-table">
                <table>
                    <thead>
                        <tr>
                            <?php foreach ($diasSemana as $dia): ?>
                            <th><?php echo $dia; ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $celda = 1;
                        $diaActual = 1;
                        $hoy = (int)date('d');
                        $mesHoy = (int)date('m');
                        $anioHoy = (int)date('Y');
                        
                        while ($diaActual <= $diasEnMes):
                            echo '<tr>';
                            for ($col = 1; $col <= 7; $col++):
                                if (($celda < $primerDia && $diaActual === 1) || $diaActual > $diasEnMes):
                                    echo '<td class="empty"></td>';
                                else:
                                    $isToday = ($diaActual == $hoy && $month == $mesHoy && $year == $anioHoy);
                                    echo '<td class="' . ($isToday ? 'today' : '') . '">';
                                    echo '<div class="day-num">' . $diaActual . '</div>';
                                    if (isset($eventosPorDia[$diaActual])):
                                        foreach ($eventosPorDia[$diaActual] as $ev):
                                            $evColor = $ev['dept_color'] ?: $ev['color'];
                                            $evTime = $ev['hora_inicio'] ? date('H:i', strtotime($ev['hora_inicio'])) . ' ' : '';
                                            echo '<a href="evento.php?id=' . $ev['id'] . '" class="cal-event-bar" style="background:' . $evColor . ';">' . $evTime . htmlspecialchars($ev['titulo']) . '</a>';
                                        endforeach;
                                    endif;
                                    echo '</td>';
                                    $diaActual++;
                                endif;
                                $celda++;
                            endfor;
                            echo '</tr>';
                        endwhile;
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Automotriz Corp. | <a href="index.php" style="color:var(--text-muted);text-decoration:none;">Inicio</a></p>
    </footer>
</body>
</html>
