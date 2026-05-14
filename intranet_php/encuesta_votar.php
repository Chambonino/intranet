<?php
/**
 * Endpoint AJAX: Registrar voto de encuesta
 * Recibe: encuesta_id, opcion_id[] (puede ser uno o varios)
 * Responde JSON con resultados actualizados
 */
require_once 'includes/config.php';
require_once 'includes/functions.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']); exit;
}

$encuestaId = (int)($_POST['encuesta_id'] ?? 0);
$opciones   = $_POST['opcion_id'] ?? [];
if (!is_array($opciones)) $opciones = [$opciones];
$opciones = array_filter(array_map('intval', $opciones));

if (!$encuestaId || empty($opciones)) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']); exit;
}

// Validar encuesta activa y dentro del rango de fechas
$stmt = $pdo->prepare("SELECT * FROM encuestas WHERE id = ? AND activa = 1 AND (fecha_inicio IS NULL OR fecha_inicio <= NOW()) AND (fecha_fin IS NULL OR fecha_fin >= NOW())");
$stmt->execute([$encuestaId]);
$encuesta = $stmt->fetch();
if (!$encuesta) {
    echo json_encode(['success' => false, 'error' => 'Encuesta no disponible']); exit;
}

// Validar única opción si no permite múltiple
if (!$encuesta['permite_multiple'] && count($opciones) > 1) {
    $opciones = [$opciones[0]];
}

// Anti-doble-voto por IP (solo si es anónima — usamos IP)
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250);
$check = $pdo->prepare("SELECT COUNT(*) FROM encuestas_respuestas WHERE encuesta_id = ? AND ip = ?");
$check->execute([$encuestaId, $ip]);
if ($check->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'error' => 'Ya votaste en esta encuesta', 'already_voted' => true]); exit;
}

// Validar que las opciones pertenezcan a la encuesta
$placeholders = implode(',', array_fill(0, count($opciones), '?'));
$valStmt = $pdo->prepare("SELECT id FROM encuestas_opciones WHERE encuesta_id = ? AND id IN ($placeholders)");
$valStmt->execute(array_merge([$encuestaId], $opciones));
$validIds = array_column($valStmt->fetchAll(), 'id');

if (empty($validIds)) {
    echo json_encode(['success' => false, 'error' => 'Opciones inválidas']); exit;
}

// Registrar votos
$insert = $pdo->prepare("INSERT INTO encuestas_respuestas (encuesta_id, opcion_id, ip, user_agent) VALUES (?,?,?,?)");
foreach ($validIds as $oid) {
    $insert->execute([$encuestaId, $oid, $ip, $ua]);
}

// Obtener resultados actualizados
$resStmt = $pdo->prepare("SELECT o.id, o.texto, COUNT(r.id) as votos FROM encuestas_opciones o LEFT JOIN encuestas_respuestas r ON r.opcion_id = o.id WHERE o.encuesta_id = ? GROUP BY o.id, o.texto, o.orden ORDER BY o.orden ASC");
$resStmt->execute([$encuestaId]);
$resultados = $resStmt->fetchAll(PDO::FETCH_ASSOC);
$total = array_sum(array_column($resultados, 'votos'));

echo json_encode([
    'success' => true,
    'total' => $total,
    'resultados' => array_map(function($r) use ($total) {
        return [
            'id'    => (int)$r['id'],
            'texto' => $r['texto'],
            'votos' => (int)$r['votos'],
            'pct'   => $total > 0 ? round(($r['votos'] / $total) * 100, 1) : 0
        ];
    }, $resultados)
]);
