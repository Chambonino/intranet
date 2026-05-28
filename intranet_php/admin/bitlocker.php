<?php
/**
 * BitLocker Recovery - Consulta de claves desde Active Directory.
 * Acceso restringido a SUPER ADMINISTRADORES.
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin();

// Doble verificación: aunque requireLogin ya valida permisos, blindamos por seguridad
if (!esSuperAdmin()) {
    setFlashMessage('Acceso restringido. Solo super administradores pueden consultar claves BitLocker.', 'danger');
    header('Location: index.php');
    exit;
}

// Configuración LDAP (misma que directorio.php)
$CONFIG = [
    'ldap_host' => '192.168.10.100',
    'ldap_port' => 389,
    'bind_rdn'  => 'empresa\\chuvidubi',
    'bind_pass' => 'tupumpummami',
    'base_dn'   => 'DC=chinga,DC=tumadre,DC=com,DC=mx',
];

// ------------------ FUNCIONES ------------------
function ad_connect($CONFIG) {
    $ldap = @ldap_connect($CONFIG['ldap_host'], $CONFIG['ldap_port']);
    if (!$ldap) return false;
    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
    $bind = @ldap_bind($ldap, $CONFIG['bind_rdn'], $CONFIG['bind_pass']);
    if (!$bind) return false;
    return $ldap;
}
function ad_search($ldap, $base_dn, $filter, $attrs = []) {
    $res = @ldap_search($ldap, $base_dn, $filter, $attrs);
    if (!$res) return [];
    $entries = @ldap_get_entries($ldap, $res);
    $out = [];
    for ($i = 0; $i < ($entries['count'] ?? 0); $i++) $out[] = $entries[$i];
    return $out;
}

// ------------------ CONEXION + BÚSQUEDA ------------------
$computer = trim($_GET['computer'] ?? '');
$entries = [];
$ldapError = '';
$ldapconn = ad_connect($CONFIG);

if (!$ldapconn) {
    $ldapError = 'No se pudo conectar al servidor LDAP (' . htmlspecialchars($CONFIG['ldap_host']) . ').';
} elseif ($computer !== '') {
    // Sanitizar input para LDAP (sin caracteres de inyección)
    $cleanComp = preg_replace('/[^a-zA-Z0-9\-_.]/', '', $computer);
    $computers = ad_search($ldapconn, $CONFIG['base_dn'], "(cn=$cleanComp)", ['distinguishedName']);
    if (count($computers) > 0) {
        $computerDN = $computers[0]['distinguishedname'][0];
        $entries = ad_search($ldapconn, $computerDN, "(objectClass=msFVE-RecoveryInformation)", [
            'msFVE-RecoveryGuid','msFVE-RecoveryPassword','whenCreated','distinguishedName'
        ]);
    }
}
if ($ldapconn) @ldap_close($ldapconn);

$activeMenu = 'bitlocker';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BitLocker Recovery - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .bl-warning-banner {
            background: linear-gradient(135deg, rgba(255,193,7,0.18), rgba(255,152,0,0.12));
            border: 1px solid rgba(255,193,7,0.4);
            border-radius: 12px;
            padding: 14px 20px;
            color: #ffc107;
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 22px;
            font-size: 0.88rem;
        }
        .bl-warning-banner i { font-size: 1.6rem; }
        .bl-warning-banner strong { color: #fff; }

        .bl-search-form { display: flex; gap: 10px; align-items: flex-end; margin-bottom: 22px; }
        .bl-search-form .form-group { flex: 1; margin: 0; }
        .bl-search-form input { font-family: 'Consolas','Courier New',monospace; letter-spacing: 0.5px; }

        .bl-result-card { background: var(--bg-card, #1a1f2e); border-radius: 12px; padding: 0; overflow: hidden; border: 1px solid rgba(255,255,255,0.08); margin-bottom: 20px; }
        .bl-result-header { padding: 14px 20px; background: linear-gradient(135deg, #1565c0, #1976d2); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .bl-result-header h3 { color: white; margin: 0; font-size: 1rem; display: flex; align-items: center; gap: 10px; }
        .bl-result-header .badge-cnt { background: rgba(255,255,255,0.2); color: #fff; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; }

        .bl-table { width: 100%; border-collapse: collapse; }
        .bl-table th { background: rgba(255,255,255,0.04); color: var(--text-muted, #90a4ae); text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.8px; padding: 12px 16px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .bl-table td { padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,0.04); font-size: 0.85rem; color: #ddd; vertical-align: top; }
        .bl-table tr:last-child td { border-bottom: none; }
        .bl-table tr:hover td { background: rgba(255,255,255,0.02); }
        .bl-recovery-key { font-family: 'Consolas','Courier New',monospace; font-size: 0.92rem; color: #4fc3f7; letter-spacing: 1px; display: inline-block; padding: 4px 10px; background: rgba(79,195,247,0.1); border-radius: 6px; border: 1px solid rgba(79,195,247,0.2); }
        .bl-dn { font-family: 'Consolas','Courier New',monospace; font-size: 0.72rem; color: var(--text-muted, #90a4ae); word-break: break-all; }
        .bl-copy-btn { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); color: #cfd8dc; padding: 4px 10px; border-radius: 6px; cursor: pointer; font-size: 0.7rem; margin-left: 8px; transition: all 0.2s; }
        .bl-copy-btn:hover { background: rgba(79,195,247,0.2); border-color: #4fc3f7; color: #4fc3f7; }
        .bl-copy-btn.copied { background: rgba(76,175,80,0.25); border-color: #4caf50; color: #81c784; }

        .bl-empty { background: var(--bg-card, #1a1f2e); border: 1px dashed rgba(255,255,255,0.1); border-radius: 12px; padding: 50px 20px; text-align: center; color: var(--text-muted, #90a4ae); }
        .bl-empty i { font-size: 3rem; opacity: 0.3; display: block; margin-bottom: 16px; }
        .bl-empty h3 { color: #fff; margin-bottom: 8px; font-size: 1.05rem; }
        .bl-empty p { font-size: 0.85rem; }

        .bl-error { background: rgba(244,67,54,0.12); border: 1px solid rgba(244,67,54,0.35); border-radius: 10px; padding: 14px 18px; color: #ef5350; margin-bottom: 18px; display: flex; align-items: center; gap: 10px; font-size: 0.88rem; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php renderAdminSidebar('bitlocker'); ?>

        <main class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-key"></i> BitLocker Recovery</h1>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </div>

            <div class="bl-warning-banner">
                <i class="fas fa-shield-alt"></i>
                <div>
                    <strong>Sección de máxima seguridad</strong> — Esta página permite consultar claves de recuperación BitLocker desde Active Directory. Su uso queda registrado y solo está disponible para super administradores.
                </div>
            </div>

            <?php if ($ldapError): ?>
            <div class="bl-error"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($ldapError); ?></div>
            <?php endif; ?>

            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-search"></i> Consultar clave BitLocker</h2>
                </div>
                <div class="card-body">
                    <form method="GET" class="bl-search-form" data-testid="bitlocker-form">
                        <div class="form-group">
                            <label>Nombre del equipo (Computer Name)</label>
                            <input type="text" name="computer" class="form-control" placeholder="Ej: PC-VENTAS-01, LAPTOP-JPEREZ..." value="<?php echo htmlspecialchars($computer); ?>" autofocus autocomplete="off" data-testid="bitlocker-computer-input">
                        </div>
                        <button type="submit" class="btn btn-primary" data-testid="bitlocker-submit"><i class="fas fa-search"></i> Buscar</button>
                        <?php if ($computer !== ''): ?>
                        <a href="bitlocker.php" class="btn btn-secondary"><i class="fas fa-times"></i> Limpiar</a>
                        <?php endif; ?>
                    </form>

                    <?php if ($computer === ''): ?>
                    <div class="bl-empty">
                        <i class="fas fa-search"></i>
                        <h3>Ingrese el nombre del equipo</h3>
                        <p>Escriba el Computer Name del equipo en Active Directory para consultar sus claves BitLocker.</p>
                    </div>
                    <?php elseif (count($entries) === 0): ?>
                    <div class="bl-empty">
                        <i class="fas fa-info-circle" style="color:#ff9800;opacity:0.55;"></i>
                        <h3>Sin resultados</h3>
                        <p>No se encontraron objetos de recuperación BitLocker para <strong style="color:#fff;"><?php echo htmlspecialchars($computer); ?></strong>.</p>
                        <p style="margin-top:8px;font-size:0.75rem;opacity:0.8;">Verifique que el nombre del equipo sea exacto y que esté unido al dominio.</p>
                    </div>
                    <?php else: ?>
                    <div class="bl-result-card">
                        <div class="bl-result-header">
                            <h3><i class="fas fa-laptop"></i> <?php echo htmlspecialchars($computer); ?></h3>
                            <span class="badge-cnt"><?php echo count($entries); ?> clave<?php echo count($entries) > 1 ? 's' : ''; ?> encontrada<?php echo count($entries) > 1 ? 's' : ''; ?></span>
                        </div>
                        <div class="table-responsive">
                            <table class="bl-table">
                                <thead>
                                    <tr>
                                        <th>Recovery Password</th>
                                        <th style="width:170px;">Date Added</th>
                                        <th>Distinguished Name</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($entries as $i => $e):
                                        $pwd = $e['msfve-recoverypassword'][0] ?? '';
                                        $when = $e['whencreated'][0] ?? '';
                                        // Formatear fecha LDAP (yyyyMMddHHmmss.0Z)
                                        $whenFmt = '';
                                        if ($when) {
                                            $ts = strtotime(preg_replace('/\.0?Z$/', 'Z', $when));
                                            $whenFmt = $ts ? date('Y-m-d H:i:s', $ts) : $when;
                                        }
                                        $dn = $e['distinguishedname'][0] ?? '';
                                    ?>
                                    <tr data-testid="bitlocker-row-<?php echo $i; ?>">
                                        <td>
                                            <span class="bl-recovery-key" id="bl-key-<?php echo $i; ?>"><?php echo htmlspecialchars($pwd); ?></span>
                                            <?php if ($pwd): ?>
                                            <button class="bl-copy-btn" onclick="copyKey('bl-key-<?php echo $i; ?>', this)" data-testid="bitlocker-copy-<?php echo $i; ?>"><i class="fas fa-copy"></i> Copiar</button>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($whenFmt); ?></td>
                                        <td><span class="bl-dn"><?php echo htmlspecialchars($dn); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
    <script>
        function copyKey(elId, btn) {
            var txt = document.getElementById(elId).textContent.trim();
            if (!txt) return;
            navigator.clipboard.writeText(txt).then(function() {
                var orig = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copiado';
                btn.classList.add('copied');
                setTimeout(function() { btn.innerHTML = orig; btn.classList.remove('copied'); }, 1800);
            });
        }
    </script>
</body>
</html>
