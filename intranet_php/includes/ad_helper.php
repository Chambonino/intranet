<?php
/**
 * AD Helper — Obtiene Nuevos Ingresos y Aniversarios Laborales desde Active Directory
 *
 * ============================================================
 * INSTRUCCIONES — Cambia estos valores ficticios por los reales
 * de tu Active Directory antes de subir a producción.
 * ============================================================
 *
 * Atributo de fecha de ingreso:
 *  - Por defecto se usa `whenCreated` (cuando se creó la cuenta de AD).
 *  - Si tu empresa guarda la fecha de contratación en otro atributo
 *    (p.ej. `extensionAttribute1`), cambia la constante AD_HIRE_ATTR.
 */

// ============ CONFIGURACIÓN AD (CAMBIAR POR LAS REALES) ============
if (!defined('AD_HOST'))      define('AD_HOST',      'ldap://192.168.10.100');   // IP/host del Domain Controller
if (!defined('AD_PORT'))      define('AD_PORT',      389);                       // 389 normal, 636 SSL
if (!defined('AD_BASE_DN'))   define('AD_BASE_DN',   'OU=Usuarios,DC=miempresa,DC=com,DC=mx');
if (!defined('AD_USER'))      define('AD_USER',      'MIEMPRESA\\usuario_consulta');
if (!defined('AD_PASS'))      define('AD_PASS',      'TuPasswordAqui');
if (!defined('AD_COMPANY'))   define('AD_COMPANY',   'Nicrobolta');             // valor del atributo Company para filtrar
if (!defined('AD_HIRE_ATTR')) define('AD_HIRE_ATTR', 'whencreated');             // atributo de fecha de ingreso
// ====================================================================

/**
 * Conecta y consulta AD devolviendo array de empleados con fecha de ingreso parseada.
 * Cachea el resultado para no golpear AD múltiples veces en la misma carga.
 */
function _ad_fetch_empleados() {
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = [];

    if (!function_exists('ldap_connect')) return $cache; // extensión php_ldap no instalada

    $ldap = @ldap_connect(AD_HOST, AD_PORT);
    if (!$ldap) return $cache;
    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

    if (!@ldap_bind($ldap, AD_USER, AD_PASS)) {
        @ldap_close($ldap);
        return $cache;
    }

    // Sólo cuentas activas con Company configurado
    $filter = '(&(objectClass=user)(Company=' . AD_COMPANY . ')(sn=*)(Useraccountcontrol=512))';
    $attrs  = ['displayname', 'givenname', 'sn', 'title', 'department', 'mail', 'telephonenumber', 'thumbnailphoto', AD_HIRE_ATTR];

    $result = @ldap_search($ldap, AD_BASE_DN, $filter, $attrs);
    if (!$result) { @ldap_close($ldap); return $cache; }

    $entries = ldap_get_entries($ldap, $result);
    for ($i = 0; $i < $entries['count']; $i++) {
        $e = $entries[$i];
        $hireRaw = $e[AD_HIRE_ATTR][0] ?? '';
        $ts = _ad_parse_generalized_time($hireRaw);
        if (!$ts) continue;

        $foto = '';
        if (isset($e['thumbnailphoto'][0])) {
            $foto = 'data:image/jpeg;base64,' . base64_encode($e['thumbnailphoto'][0]);
        }

        $cache[] = [
            'nombre'       => $e['displayname'][0] ?? trim(($e['givenname'][0] ?? '') . ' ' . ($e['sn'][0] ?? '')),
            'puesto'       => $e['title'][0] ?? '',
            'departamento' => $e['department'][0] ?? '',
            'email'        => $e['mail'][0] ?? '',
            'telefono'     => $e['telephonenumber'][0] ?? '',
            'foto'         => $foto,
            'fecha_ingreso_ts' => $ts,
            'fecha_ingreso'    => date('Y-m-d', $ts),
        ];
    }
    @ldap_close($ldap);
    return $cache;
}

/**
 * Convierte el formato GeneralizedTime de AD (ej: 20230515103045.0Z) a timestamp.
 */
function _ad_parse_generalized_time($s) {
    if (!$s) return null;
    if (preg_match('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', $s, $m)) {
        return mktime((int)$m[4], (int)$m[5], (int)$m[6], (int)$m[2], (int)$m[3], (int)$m[1]);
    }
    $ts = strtotime($s);
    return $ts ?: null;
}

/**
 * Nuevos Ingresos: empleados creados en los últimos N días (default 60).
 */
function getNuevosIngresosAD($dias = 60) {
    $emp = _ad_fetch_empleados();
    $limite = time() - ($dias * 86400);
    $nuevos = array_values(array_filter($emp, function($x) use ($limite) {
        return $x['fecha_ingreso_ts'] >= $limite;
    }));
    usort($nuevos, function($a, $b) { return $b['fecha_ingreso_ts'] - $a['fecha_ingreso_ts']; });
    return $nuevos;
}

/**
 * Aniversarios Laborales: empleados cuyo aniversario cae en el mes actual.
 */
function getAniversariosAD() {
    $emp = _ad_fetch_empleados();
    $mesActual = (int)date('m');
    $anioActual = (int)date('Y');
    $aniv = [];
    foreach ($emp as $x) {
        $mes = (int)date('m', $x['fecha_ingreso_ts']);
        $anioIngreso = (int)date('Y', $x['fecha_ingreso_ts']);
        $anos = $anioActual - $anioIngreso;
        if ($mes === $mesActual && $anos >= 1) {
            $x['anos'] = $anos;
            $aniv[] = $x;
        }
    }
    usort($aniv, function($a, $b) { return (int)date('d', $a['fecha_ingreso_ts']) - (int)date('d', $b['fecha_ingreso_ts']); });
    return $aniv;
}
