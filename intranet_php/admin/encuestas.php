<?php
/**
 * Gestión de Encuestas - Admin
 * CRUD de encuestas + visualización de resultados
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin();

$flash = getFlashMessage();
$action = $_GET['action'] ?? 'list';
$id     = $_GET['id'] ?? null;

// === GUARDAR (crear/editar) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action'])) {
    $titulo   = sanitize($_POST['titulo']);
    $pregunta = sanitize($_POST['pregunta']);
    $multi    = isset($_POST['permite_multiple']) ? 1 : 0;
    $anonima  = isset($_POST['anonima']) ? 1 : 0;
    $activa   = isset($_POST['activa']) ? 1 : 0;
    $fIni     = $_POST['fecha_inicio'] ?: date('Y-m-d H:i:s');
    $fFin     = $_POST['fecha_fin'] ?: null;
    $opciones = array_values(array_filter(array_map('trim', $_POST['opciones'] ?? []), function($o) { return $o !== ''; }));

    if (count($opciones) < 2) {
        setFlashMessage('Debes agregar al menos 2 opciones de respuesta', 'danger');
        header('Location: encuestas.php?action=' . ($_POST['form_action'] === 'edit' ? 'edit&id=' . (int)$_POST['id'] : 'add'));
        exit;
    }

    if ($_POST['form_action'] === 'add') {
        $stmt = $pdo->prepare("INSERT INTO encuestas (titulo, pregunta, permite_multiple, anonima, fecha_inicio, fecha_fin, activa) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$titulo, $pregunta, $multi, $anonima, $fIni, $fFin, $activa]);
        $newId = $pdo->lastInsertId();
        $opStmt = $pdo->prepare("INSERT INTO encuestas_opciones (encuesta_id, texto, orden) VALUES (?,?,?)");
        foreach ($opciones as $i => $op) { $opStmt->execute([$newId, sanitize($op), $i]); }
        setFlashMessage('Encuesta creada correctamente', 'success');
    } else {
        $eid = (int)$_POST['id'];
        $pdo->prepare("UPDATE encuestas SET titulo=?, pregunta=?, permite_multiple=?, anonima=?, fecha_inicio=?, fecha_fin=?, activa=? WHERE id=?")
            ->execute([$titulo, $pregunta, $multi, $anonima, $fIni, $fFin, $activa, $eid]);
        // Reemplazar opciones
        $pdo->prepare("DELETE FROM encuestas_opciones WHERE encuesta_id=?")->execute([$eid]);
        $opStmt = $pdo->prepare("INSERT INTO encuestas_opciones (encuesta_id, texto, orden) VALUES (?,?,?)");
        foreach ($opciones as $i => $op) { $opStmt->execute([$eid, sanitize($op), $i]); }
        setFlashMessage('Encuesta actualizada', 'success');
    }
    header('Location: encuestas.php');
    exit;
}

// === ELIMINAR ===
if ($action === 'delete' && $id) {
    $pdo->prepare("DELETE FROM encuestas WHERE id=?")->execute([$id]);
    setFlashMessage('Encuesta eliminada', 'success');
    header('Location: encuestas.php'); exit;
}

// === DATOS ===
$encuestas = $pdo->query("SELECT e.*, (SELECT COUNT(*) FROM encuestas_respuestas r WHERE r.encuesta_id = e.id) as total_votos FROM encuestas e ORDER BY activa DESC, fecha_creacion DESC")->fetchAll();

$editData = null; $editOpciones = [];
if (($action === 'edit' || $action === 'results') && $id) {
    $stmt = $pdo->prepare("SELECT * FROM encuestas WHERE id=?"); $stmt->execute([$id]); $editData = $stmt->fetch();
    if ($editData) {
        $stmt2 = $pdo->prepare("SELECT * FROM encuestas_opciones WHERE encuesta_id=? ORDER BY orden ASC");
        $stmt2->execute([$id]);
        $editOpciones = $stmt2->fetchAll();
    }
}

// Resultados
$resultados = []; $totalVotos = 0;
if ($action === 'results' && $editData) {
    $stmt = $pdo->prepare("SELECT o.id, o.texto, COUNT(r.id) as votos FROM encuestas_opciones o LEFT JOIN encuestas_respuestas r ON r.opcion_id = o.id WHERE o.encuesta_id = ? GROUP BY o.id, o.texto, o.orden ORDER BY o.orden ASC");
    $stmt->execute([$id]);
    $resultados = $stmt->fetchAll();
    foreach ($resultados as $r) { $totalVotos += $r['votos']; }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encuestas - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .opcion-row { display: flex; gap: 10px; margin-bottom: 8px; align-items: center; }
        .opcion-row input { flex: 1; padding: 9px 12px; border: 1.5px solid #e0e0e0; border-radius: 8px; font-size: 0.9rem; }
        .opcion-row .del-op { background: #ffebee; color: #e53935; border: none; width: 36px; height: 36px; border-radius: 8px; cursor: pointer; }
        .opcion-row .del-op:hover { background: #ffcdd2; }
        .add-op { background: #e3f2fd; color: #1976d2; border: 1px dashed #1976d2; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 0.82rem; margin-top: 5px; }

        .enc-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 18px; }
        .enc-card { background: white; border-radius: 14px; padding: 22px; box-shadow: 0 3px 12px rgba(0,0,0,0.06); border-left: 5px solid #1976d2; transition: all 0.3s; }
        .enc-card.inactive { border-left-color: #999; opacity: 0.7; }
        .enc-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.12); transform: translateY(-3px); }
        .enc-card h4 { font-size: 1.05rem; margin-bottom: 5px; color: #1a1a1a; }
        .enc-card .q { font-size: 0.85rem; color: #555; margin: 8px 0 12px; line-height: 1.4; }
        .enc-card .meta { display: flex; gap: 12px; font-size: 0.72rem; color: #999; margin-bottom: 12px; }
        .enc-card .meta .badge { background: #e3f2fd; color: #1976d2; padding: 3px 10px; border-radius: 12px; font-weight: 600; }
        .enc-card .meta .badge.success { background: #e8f5e9; color: #2e7d32; }
        .enc-card .actions { display: flex; gap: 8px; }
        .enc-card .actions a, .enc-card .actions button { padding: 7px 12px; border-radius: 6px; font-size: 0.76rem; text-decoration: none; font-weight: 600; transition: all 0.2s; border: none; cursor: pointer; }
        .enc-card .a-view { background: #e3f2fd; color: #1976d2; }
        .enc-card .a-edit { background: #fff3e0; color: #f57c00; }
        .enc-card .a-del  { background: #ffebee; color: #e53935; }

        .res-bar { background: #f5f5f5; border-radius: 8px; height: 32px; overflow: hidden; position: relative; margin: 6px 0 14px; }
        .res-bar-fill { background: linear-gradient(90deg, #1976d2, #42a5f5); height: 100%; display: flex; align-items: center; padding-left: 12px; color: white; font-weight: 700; font-size: 0.82rem; transition: width 0.6s; min-width: 40px; }
        .res-bar-label { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 0.82rem; color: #333; font-weight: 500; mix-blend-mode: difference; color: white; }
        .res-bar-pct { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 0.78rem; color: #666; font-weight: 600; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php renderAdminSidebar('encuestas'); ?>
        <main class="main-content">
            <div class="top-bar"><h1><i class="fas fa-poll"></i> Encuestas</h1><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Salir</a></div>
            <?php if ($flash): ?><div class="alert alert-<?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></div><?php endif; ?>

            <?php if ($action === 'add' || $action === 'edit'): ?>
            <!-- ====== FORMULARIO ====== -->
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-<?php echo $action === 'add' ? 'plus' : 'edit'; ?>"></i> <?php echo $action === 'add' ? 'Nueva Encuesta' : 'Editar Encuesta'; ?></h2>
                    <a href="encuestas.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Volver</a>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="form_action" value="<?php echo $action; ?>">
                        <?php if ($editData): ?><input type="hidden" name="id" value="<?php echo $editData['id']; ?>"><?php endif; ?>

                        <div class="form-group"><label>Título *</label><input type="text" name="titulo" class="form-control" required value="<?php echo htmlspecialchars($editData['titulo'] ?? ''); ?>" placeholder="Ej: ¿Qué actividad prefieres para el próximo evento?"></div>

                        <div class="form-group"><label>Pregunta *</label><textarea name="pregunta" class="form-control" rows="2" required placeholder="Texto completo de la pregunta"><?php echo htmlspecialchars($editData['pregunta'] ?? ''); ?></textarea></div>

                        <div class="form-group">
                            <label>Opciones de respuesta * (mínimo 2)</label>
                            <div id="opcionesContainer">
                                <?php
                                $ops = $editOpciones ?: [['texto'=>''],['texto'=>'']];
                                foreach ($ops as $op): ?>
                                <div class="opcion-row">
                                    <input type="text" name="opciones[]" value="<?php echo htmlspecialchars($op['texto']); ?>" placeholder="Opción de respuesta" required>
                                    <button type="button" class="del-op" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="add-op" onclick="addOpcion()"><i class="fas fa-plus"></i> Agregar opción</button>
                        </div>

                        <div class="form-row">
                            <div class="form-group"><label>Fecha de inicio</label><input type="datetime-local" name="fecha_inicio" class="form-control" value="<?php echo $editData ? date('Y-m-d\TH:i', strtotime($editData['fecha_inicio'])) : date('Y-m-d\TH:i'); ?>"></div>
                            <div class="form-group"><label>Fecha de fin (opcional)</label><input type="datetime-local" name="fecha_fin" class="form-control" value="<?php echo $editData && $editData['fecha_fin'] ? date('Y-m-d\TH:i', strtotime($editData['fecha_fin'])) : ''; ?>"></div>
                        </div>

                        <div class="form-row">
                            <div class="form-group"><label><input type="checkbox" name="permite_multiple" <?php echo ($editData && $editData['permite_multiple']) ? 'checked' : ''; ?>> Permite seleccionar varias opciones</label></div>
                            <div class="form-group"><label><input type="checkbox" name="anonima" <?php echo (!$editData || $editData['anonima']) ? 'checked' : ''; ?>> Anónima (no registra identidad)</label></div>
                            <div class="form-group"><label><input type="checkbox" name="activa" <?php echo (!$editData || $editData['activa']) ? 'checked' : ''; ?>> Activa</label></div>
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                    </form>
                </div>
            </div>

            <script>
            function addOpcion() {
                var div = document.createElement('div');
                div.className = 'opcion-row';
                div.innerHTML = '<input type="text" name="opciones[]" placeholder="Opción de respuesta" required><button type="button" class="del-op" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>';
                document.getElementById('opcionesContainer').appendChild(div);
            }
            </script>

            <?php elseif ($action === 'results' && $editData): ?>
            <!-- ====== RESULTADOS ====== -->
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-chart-bar"></i> Resultados: <?php echo htmlspecialchars($editData['titulo']); ?></h2>
                    <a href="encuestas.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Volver</a>
                </div>
                <div class="card-body">
                    <p style="font-size:0.95rem;color:#555;margin-bottom:8px;"><strong>Pregunta:</strong> <?php echo htmlspecialchars($editData['pregunta']); ?></p>
                    <p style="font-size:0.82rem;color:#888;margin-bottom:20px;"><i class="fas fa-users"></i> <?php echo $totalVotos; ?> respuesta<?php echo $totalVotos != 1 ? 's' : ''; ?> en total</p>

                    <?php foreach ($resultados as $r):
                        $pct = $totalVotos > 0 ? round(($r['votos'] / $totalVotos) * 100, 1) : 0;
                    ?>
                    <div>
                        <div style="display:flex;justify-content:space-between;font-size:0.85rem;font-weight:500;margin-bottom:3px;">
                            <span><?php echo htmlspecialchars($r['texto']); ?></span>
                            <span style="color:#666;"><?php echo $r['votos']; ?> voto<?php echo $r['votos'] != 1 ? 's' : ''; ?> · <?php echo $pct; ?>%</span>
                        </div>
                        <div class="res-bar">
                            <div class="res-bar-fill" style="width: <?php echo max($pct, 2); ?>%;"><?php echo $pct; ?>%</div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php if ($totalVotos === 0): ?>
                    <p style="color:#999;text-align:center;padding:30px;"><i class="fas fa-info-circle"></i> Aún no hay respuestas para esta encuesta.</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php else: ?>
            <!-- ====== LISTA ====== -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <p style="color:#666;"><?php echo count($encuestas); ?> encuesta<?php echo count($encuestas) != 1 ? 's' : ''; ?> creada<?php echo count($encuestas) != 1 ? 's' : ''; ?></p>
                <a href="encuestas.php?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Nueva Encuesta</a>
            </div>

            <?php if (count($encuestas) > 0): ?>
            <div class="enc-list">
                <?php foreach ($encuestas as $enc): ?>
                <div class="enc-card <?php echo $enc['activa'] ? '' : 'inactive'; ?>">
                    <h4><?php echo htmlspecialchars($enc['titulo']); ?></h4>
                    <div class="meta">
                        <span class="badge <?php echo $enc['activa'] ? 'success' : ''; ?>"><i class="fas fa-circle"></i> <?php echo $enc['activa'] ? 'Activa' : 'Inactiva'; ?></span>
                        <span><i class="fas fa-users"></i> <?php echo $enc['total_votos']; ?> votos</span>
                    </div>
                    <p class="q"><?php echo htmlspecialchars(mb_substr($enc['pregunta'], 0, 120)) . (mb_strlen($enc['pregunta']) > 120 ? '...' : ''); ?></p>
                    <div class="actions">
                        <a href="encuestas.php?action=results&id=<?php echo $enc['id']; ?>" class="a-view"><i class="fas fa-chart-bar"></i> Resultados</a>
                        <a href="encuestas.php?action=edit&id=<?php echo $enc['id']; ?>" class="a-edit"><i class="fas fa-edit"></i> Editar</a>
                        <button class="a-del" onclick="if(confirm('¿Eliminar esta encuesta y todas sus respuestas?')) location.href='encuestas.php?action=delete&id=<?php echo $enc['id']; ?>'"><i class="fas fa-trash"></i> Eliminar</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div style="text-align:center;padding:60px;color:#999;background:white;border-radius:12px;">
                <i class="fas fa-poll" style="font-size:3rem;opacity:0.3;display:block;margin-bottom:15px;"></i>
                <p>No hay encuestas. Crea una para empezar a recoger opiniones.</p>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
