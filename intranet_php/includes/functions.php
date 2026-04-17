<?php
/**
 * Funciones auxiliares para la Intranet
 */

/**
 * Obtener slider de noticias activo
 */
function getSliderNoticias($pdo, $limit = 10) {
    $stmt = $pdo->prepare("SELECT * FROM slider_noticias WHERE activo = 1 ORDER BY orden ASC, id DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Obtener eventos del calendario
 */
function getEventos($pdo, $limit = null) {
    $sql = "SELECT * FROM eventos WHERE activo = 1 AND fecha_evento >= CURDATE() ORDER BY fecha_evento ASC";
    if ($limit) {
        $sql .= " LIMIT " . (int)$limit;
    }
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

/**
 * Obtener cumpleaños del mes actual
 */
function getCumpleanerosMes($pdo) {
    $stmt = $pdo->query("
        SELECT e.*, d.nombre as departamento_nombre 
        FROM empleados_cumpleanos e 
        LEFT JOIN departamentos d ON e.departamento_id = d.id 
        WHERE e.activo = 1 
        AND MONTH(e.fecha_nacimiento) = MONTH(CURDATE())
        ORDER BY DAY(e.fecha_nacimiento) ASC
    ");
    return $stmt->fetchAll();
}

/**
 * Obtener cumpleañeros de hoy
 */
function getCumpleanerosHoy($pdo) {
    $stmt = $pdo->query("
        SELECT e.*, d.nombre as departamento_nombre 
        FROM empleados_cumpleanos e 
        LEFT JOIN departamentos d ON e.departamento_id = d.id 
        WHERE e.activo = 1 
        AND MONTH(e.fecha_nacimiento) = MONTH(CURDATE())
        AND DAY(e.fecha_nacimiento) = DAY(CURDATE())
    ");
    return $stmt->fetchAll();
}

/**
 * Obtener aplicaciones
 */
function getAplicaciones($pdo) {
    $stmt = $pdo->query("SELECT * FROM aplicaciones WHERE activo = 1 ORDER BY orden ASC LIMIT 12");
    return $stmt->fetchAll();
}

/**
 * Obtener departamentos
 */
function getDepartamentos($pdo) {
    $stmt = $pdo->query("SELECT * FROM departamentos WHERE activo = 1 ORDER BY nombre ASC");
    return $stmt->fetchAll();
}

/**
 * Obtener archivos por departamento
 */
function getArchivosPorDepartamento($pdo, $departamento_id = null) {
    if ($departamento_id) {
        $stmt = $pdo->prepare("
            SELECT a.*, d.nombre as departamento_nombre 
            FROM archivos_departamento a 
            LEFT JOIN departamentos d ON a.departamento_id = d.id 
            WHERE a.activo = 1 AND a.departamento_id = ?
            ORDER BY a.fecha_creacion DESC
        ");
        $stmt->execute([$departamento_id]);
    } else {
        $stmt = $pdo->query("
            SELECT a.*, d.nombre as departamento_nombre 
            FROM archivos_departamento a 
            LEFT JOIN departamentos d ON a.departamento_id = d.id 
            WHERE a.activo = 1
            ORDER BY d.nombre ASC, a.fecha_creacion DESC
        ");
    }
    return $stmt->fetchAll();
}

/**
 * Obtener galería de fotos
 */
function getGaleriaFotos($pdo, $limit = 20) {
    $stmt = $pdo->prepare("SELECT * FROM galeria_fotos WHERE activo = 1 ORDER BY orden ASC, id DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Obtener videos
 */
function getVideos($pdo, $limit = 10) {
    $stmt = $pdo->prepare("SELECT * FROM videos WHERE activo = 1 ORDER BY orden ASC, id DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Obtener artículos/noticias
 */
function getArticulos($pdo, $limit = 5) {
    $stmt = $pdo->prepare("SELECT * FROM articulos WHERE activo = 1 ORDER BY fecha_publicacion DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Obtener portales de clientes
 */
function getPortalesClientes($pdo) {
    $stmt = $pdo->query("SELECT * FROM portales_clientes WHERE activo = 1 ORDER BY orden ASC");
    return $stmt->fetchAll();
}

/**
 * Obtener cuenta regresiva activa
 */
function getCuentaRegresiva($pdo) {
    $stmt = $pdo->query("
        SELECT * FROM cuenta_regresiva 
        WHERE activo = 1 AND fecha_evento > NOW() 
        ORDER BY fecha_evento ASC 
        LIMIT 1
    ");
    return $stmt->fetch();
}

/**
 * Obtener avisos activos
 */
function getAvisosActivos($pdo) {
    $stmt = $pdo->query("
        SELECT * FROM avisos 
        WHERE activo = 1 
        AND (fecha_inicio IS NULL OR fecha_inicio <= CURDATE())
        AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())
        ORDER BY fecha_creacion DESC
    ");
    return $stmt->fetchAll();
}

/**
 * Obtener información de la compañía
 */
function getInfoCompania($pdo) {
    $stmt = $pdo->query("SELECT * FROM info_compania WHERE activo = 1 ORDER BY orden ASC");
    return $stmt->fetchAll();
}

/**
 * Formatear fecha en español
 */
function formatearFecha($fecha, $formato = 'd/m/Y') {
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    
    $timestamp = strtotime($fecha);
    
    if ($formato === 'completo') {
        $dia = date('d', $timestamp);
        $mes = $meses[(int)date('m', $timestamp)];
        $anio = date('Y', $timestamp);
        return "$dia de $mes de $anio";
    }
    
    return date($formato, $timestamp);
}

/**
 * Calcular edad
 */
function calcularEdad($fechaNacimiento) {
    $nacimiento = new DateTime($fechaNacimiento);
    $hoy = new DateTime();
    return $hoy->diff($nacimiento)->y;
}

/**
 * Truncar texto
 */
function truncarTexto($texto, $longitud = 100) {
    if (strlen($texto) <= $longitud) {
        return $texto;
    }
    return substr($texto, 0, $longitud) . '...';
}
?>
