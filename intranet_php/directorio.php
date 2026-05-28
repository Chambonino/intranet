<?php
/**
 * Directorio Telefónico Corporativo - Active Directory
 * Tarjetas compactas con color por departamento.
 * QR contiene vCard COMPLETA embebida (escaneable desde cualquier red).
 */
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Configuración LDAP / Active Directory
$ldap_host = "ldap://192.168.10.100";
$ldap_port = 389;
$ldap_dn = "OU=Usre,DC=chinga,DC=tumadre,DC=com,DC=mx";
$ldap_user = "empresa\\chuvidubi";
$ldap_pass = "tupumpummami";

$usuarios = [];
$error = '';
$busqueda = $_GET['q'] ?? '';
$deptFilter = $_GET['dept'] ?? '';
$departamentos_ad = [];

// Conectar a Active Directory
$ldap = @ldap_connect($ldap_host, $ldap_port);
if ($ldap) {
    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

    $bind = @ldap_bind($ldap, $ldap_user, $ldap_pass);
    if ($bind) {
        $filter = "(&(Company=Nicrobolta)(sn=*)(Useraccountcontrol=512)";
        if ($busqueda) {
            $filter .= "(|(givenName=*$busqueda*)(sn=*$busqueda*)(displayName=*$busqueda*)(mail=*$busqueda*))";
        }
        if ($deptFilter) { $filter .= "(department=$deptFilter)"; }
        $filter .= ")";

        $attrs = ['displayname','givenname','sn','title','department','telephonenumber','mobile','mail','thumbnailphoto','physicaldeliveryofficename','company','description'];
        $result = @ldap_search($ldap, $ldap_dn, $filter, $attrs);
        if ($result) {
            $entries = ldap_get_entries($ldap, $result);
            for ($i = 0; $i < $entries['count']; $i++) {
                $entry = $entries[$i];
                $foto = '';
                if (isset($entry['thumbnailphoto'][0])) {
                    $foto = 'data:image/jpeg;base64,' . base64_encode($entry['thumbnailphoto'][0]);
                }
                $dept = $entry['department'][0] ?? '';
                if ($dept && !in_array($dept, $departamentos_ad)) { $departamentos_ad[] = $dept; }
                $usuarios[] = [
                    'nombre' => $entry['displayname'][0] ?? '',
                    'nombre_pila' => $entry['givenname'][0] ?? '',
                    'apellido' => $entry['sn'][0] ?? '',
                    'puesto' => $entry['title'][0] ?? '',
                    'departamento' => $dept,
                    'telefono' => $entry['telephonenumber'][0] ?? '',
                    'celular' => $entry['mobile'][0] ?? '',
                    'email' => $entry['mail'][0] ?? '',
                    'oficina' => $entry['physicaldeliveryofficename'][0] ?? '',
                    'foto' => $foto
                ];
            }
            usort($usuarios, function($a, $b) { return strcmp($a['nombre'], $b['nombre']); });
            sort($departamentos_ad);
        }
        ldap_close($ldap);
    } else { $error = 'No se pudo autenticar con Active Directory. Verifique credenciales.'; }
} else { $error = 'No se pudo conectar al servidor LDAP (192.168.10.100).'; }

/**
 * Devuelve un par [color, gradiente] basado en una semilla (depto o nombre).
 * Paleta de 14 combinaciones vistosas en modo oscuro.
 */
function dirColorFor($semilla) {
    $palette = [
        ['#42A5F5', 'linear-gradient(135deg,#1976D2,#42A5F5)'],   // Azul
        ['#26C6DA', 'linear-gradient(135deg,#00ACC1,#26C6DA)'],   // Cyan
        ['#66BB6A', 'linear-gradient(135deg,#43A047,#66BB6A)'],   // Verde
        ['#9CCC65', 'linear-gradient(135deg,#7CB342,#9CCC65)'],   // Lima
        ['#FFCA28', 'linear-gradient(135deg,#FFB300,#FFCA28)'],   // Ámbar
        ['#FFA726', 'linear-gradient(135deg,#FB8C00,#FFA726)'],   // Naranja
        ['#FF7043', 'linear-gradient(135deg,#F4511E,#FF7043)'],   // Coral
        ['#EF5350', 'linear-gradient(135deg,#E53935,#EF5350)'],   // Rojo
        ['#EC407A', 'linear-gradient(135deg,#D81B60,#EC407A)'],   // Rosa
        ['#AB47BC', 'linear-gradient(135deg,#8E24AA,#AB47BC)'],   // Púrpura
        ['#7E57C2', 'linear-gradient(135deg,#5E35B1,#7E57C2)'],   // Violeta
        ['#5C6BC0', 'linear-gradient(135deg,#3949AB,#5C6BC0)'],   // Índigo
        ['#26A69A', 'linear-gradient(135deg,#00897B,#26A69A)'],   // Teal
        ['#8D6E63', 'linear-gradient(135deg,#6D4C41,#8D6E63)'],   // Café
    ];
    $hash = abs(crc32($semilla ?: 'default'));
    return $palette[$hash % count($palette)];
}

/**
 * Construye el texto completo de una vCard 3.0 (compatible iOS/Android).
 * NO incluye foto para mantener el QR escaneable.
 */
function buildVCardText($u) {
    $lines = ["BEGIN:VCARD", "VERSION:3.0"];
    $fn = trim($u['nombre']); if ($fn === '') $fn = trim($u['nombre_pila'] . ' ' . $u['apellido']);
    $lines[] = "FN:" . $fn;
    $lines[] = "N:" . $u['apellido'] . ";" . $u['nombre_pila'] . ";;;";
    if (!empty($u['puesto']))       $lines[] = "TITLE:" . $u['puesto'];
    if (!empty($u['departamento'])) $lines[] = "ORG:Empresa;" . $u['departamento'];
    if (!empty($u['telefono']))     $lines[] = "TEL;TYPE=WORK,VOICE:" . preg_replace('/\s+/', '', $u['telefono']);
    if (!empty($u['celular']))      $lines[] = "TEL;TYPE=CELL,VOICE:" . preg_replace('/\s+/', '', $u['celular']);
    if (!empty($u['email']))        $lines[] = "EMAIL;TYPE=INTERNET:" . $u['email'];
    if (!empty($u['oficina']))      $lines[] = "ADR;TYPE=WORK:;;" . $u['oficina'] . ";;;;";
    $lines[] = "END:VCARD";
    return implode("\r\n", $lines);
}

// Descarga vCard (fallback)
if (isset($_GET['vcard'])) {
    $idx = (int)$_GET['vcard'];
    if (isset($usuarios[$idx])) {
        $u = $usuarios[$idx];
        header('Content-Type: text/vcard; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-zA-Z0-9]/', '_', $u['nombre']) . '.vcf"');
        echo buildVCardText($u);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Directorio Telef&oacute;nico</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo date('YmdHis'); ?>">
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <style>
        .dir-page { max-width: 1500px; margin: 0 auto; padding: 18px 30px 40px; }

        /* Filtros */
        .dir-filters { background: var(--bg-card); border-radius: 12px; padding: 16px 20px; margin-bottom: 22px; display: flex; gap: 10px; align-items: end; flex-wrap: wrap; }
        .dir-field { flex: 1; min-width: 170px; }
        .dir-field label { display: block; font-size: 0.68rem; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px; }
        .dir-field input, .dir-field select { width: 100%; padding: 9px 13px; background: var(--bg-input); border: 1px solid var(--border-color); border-radius: 8px; color: white; font-size: 0.85rem; }
        .dir-field input:focus, .dir-field select:focus { outline: none; border-color: var(--accent-blue); }
        .dir-btn { padding: 9px 18px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 6px; transition: all 0.25s; }
        .dir-btn:hover { transform: translateY(-2px); }
        .dir-btn-blue { background: var(--accent-blue); color: white; }
        .dir-btn-clear { background: var(--bg-input); color: var(--text-secondary); }

        .dir-stats { color: var(--text-muted); font-size: 0.78rem; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }

        /* Grid de tarjetas compactas */
        .dir-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 14px; }

        /* Tarjeta compacta con color y borde por departamento */
        .contact-card {
            --accent: #42A5F5;
            --accent-grad: linear-gradient(135deg,#1976D2,#42A5F5);
            background: #1a1f2e;
            border-radius: 14px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            border: 1.5px solid var(--accent);
            box-shadow: 0 6px 18px rgba(0,0,0,0.25);
        }
        .contact-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 14px 30px rgba(0,0,0,0.4), 0 0 0 1px var(--accent);
        }
        /* Encabezado coloreado */
        .card-header {
            background: var(--accent-grad);
            padding: 14px 14px 30px;
            position: relative;
            text-align: center;
        }
        .card-avatar-wrap {
            width: 64px; height: 64px;
            border-radius: 50%;
            margin: 0 auto;
            border: 3px solid #1a1f2e;
            overflow: hidden;
            background: #1a1f2e;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        .card-avatar-wrap img { width: 100%; height: 100%; object-fit: cover; }
        .card-initials {
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.4rem; font-weight: 800; letter-spacing: 1px;
            background: rgba(0,0,0,0.25);
        }

        /* Cuerpo */
        .card-body { padding: 22px 14px 14px; margin-top: -18px; }
        .card-name {
            font-size: 0.92rem; font-weight: 700; color: #fff;
            text-align: center; line-height: 1.2;
            display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden;
        }
        .card-title {
            font-size: 0.7rem; color: var(--accent); font-weight: 600;
            text-align: center; margin-top: 3px;
            display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden;
        }
        .card-dept {
            display: block; text-align: center;
            font-size: 0.6rem; color: var(--text-muted);
            margin-top: 6px; text-transform: uppercase; letter-spacing: 0.8px;
        }

        /* Filas de contacto */
        .card-contacts {
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px solid rgba(255,255,255,0.06);
            display: flex; flex-direction: column; gap: 5px;
        }
        .card-row {
            display: flex; align-items: center; gap: 8px;
            font-size: 0.72rem; color: #cfd8dc;
            padding: 3px 0;
        }
        .card-row i { width: 14px; text-align: center; color: var(--accent); font-size: 0.72rem; }
        .card-row a { color: #cfd8dc; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .card-row a:hover { color: #fff; }

        /* Acciones compactas */
        .card-actions {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 4px;
            padding: 10px 0 0;
            margin-top: 8px;
            border-top: 1px solid rgba(255,255,255,0.06);
        }
        .card-act-btn {
            padding: 7px 0;
            border-radius: 7px;
            font-size: 0.65rem; font-weight: 600;
            cursor: pointer; transition: all 0.2s;
            text-decoration: none;
            display: flex; align-items: center; justify-content: center;
            gap: 3px;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.04);
            color: #cfd8dc;
        }
        .card-act-btn:hover { background: var(--accent); border-color: var(--accent); color: #fff; transform: translateY(-1px); }
        .card-act-btn i { font-size: 0.75rem; }
        .card-act-btn.btn-qr { background: var(--accent-grad); border-color: transparent; color: #fff; }
        .card-act-btn.btn-qr:hover { filter: brightness(1.15); }

        /* QR Modal */
        .qr-modal {
            position: fixed; top:0; left:0; right:0; bottom:0;
            background: rgba(0,0,0,0.92); z-index: 9999;
            display: none; align-items: center; justify-content: center;
            padding: 20px;
        }
        .qr-modal-content {
            background: #1a1f2e; border-radius: 20px; padding: 30px 28px 25px;
            text-align: center; max-width: 380px; width: 100%;
            border: 2px solid var(--qr-color, #42A5F5);
            box-shadow: 0 25px 60px rgba(0,0,0,0.5);
            position: relative;
        }
        .qr-modal-content .qr-close { position: absolute; top: 12px; right: 14px; background: rgba(255,255,255,0.1); border: none; color: #fff; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; }
        .qr-modal-content .qr-close:hover { background: rgba(229,57,53,0.8); }
        .qr-modal-content h3 { margin: 0 0 4px; font-size: 1.05rem; color: #fff; }
        .qr-modal-content .qr-subtitle { color: var(--text-muted); font-size: 0.78rem; margin: 0 0 18px; }
        .qr-canvas { background: white; padding: 16px; border-radius: 14px; display: inline-block; margin-bottom: 14px; box-shadow: 0 6px 20px rgba(0,0,0,0.3); }
        .qr-canvas svg { display: block; width: 240px; height: 240px; }
        .qr-instructions { background: rgba(66,165,245,0.1); border: 1px solid rgba(66,165,245,0.25); border-radius: 10px; padding: 10px 14px; font-size: 0.72rem; color: #b0bec5; line-height: 1.45; margin-bottom: 14px; text-align: left; }
        .qr-instructions strong { color: #64b5f6; }
        .qr-instructions i { color: #64b5f6; margin-right: 4px; }
        .qr-modal-content .qr-download {
            display: inline-flex; align-items: center; gap: 6px;
            background: var(--qr-color, #42A5F5); color: white;
            padding: 9px 18px; border-radius: 9px; font-size: 0.8rem;
            font-weight: 600; text-decoration: none; transition: filter 0.2s;
        }
        .qr-modal-content .qr-download:hover { filter: brightness(1.15); }

        .conn-error { background: rgba(229,57,53,0.1); border: 1px solid rgba(229,57,53,0.3); border-radius: 10px; padding: 16px 20px; color: #ef5350; display: flex; align-items: center; gap: 12px; margin-bottom: 20px; font-size: 0.85rem; }
        .dir-empty { text-align: center; padding: 60px 20px; color: var(--text-muted); }
        .dir-empty i { font-size: 3.5rem; opacity: 0.2; margin-bottom: 16px; display: block; }

        @media (max-width: 600px) {
            .dir-grid { grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 10px; }
            .card-body { padding: 18px 10px 10px; }
            .qr-canvas svg { width: 200px; height: 200px; }
        }
    </style>
</head>
<body>
    <header class="header" style="background: url('assets/img/fondo1.png') center/cover; position:fixed;top:0;left:0;right:0;z-index:1000;">
        <div style="position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(27,32,40,0.85);"></div>
        <div class="header-content" style="position:relative;z-index:1;">
            <div style="display:flex;align-items:center;gap:15px;"><img src="assets/img/logo.png" alt="NB" style="height:55px;"></div>
            <a href="index.php" style="color:white;text-decoration:none;font-weight:500;"><i class="fas fa-home"></i> Inicio</a>
        </div>
    </header>
    <div style="height:110px;"></div>

    <main class="dir-page">
        <h2 style="margin-bottom:18px;display:flex;align-items:center;gap:12px;font-size:1.5rem;">
            <i class="fas fa-address-book" style="color:var(--accent-blue);"></i> Directorio Telef&oacute;nico
        </h2>

        <?php if ($error): ?>
        <div class="conn-error">
            <i class="fas fa-exclamation-triangle" style="font-size:1.5rem;"></i>
            <div><strong>Error de conexi&oacute;n</strong><p style="font-size:0.8rem;margin-top:3px;"><?php echo $error; ?></p></div>
        </div>
        <?php endif; ?>

        <form method="GET" class="dir-filters" id="dirFiltersForm" onsubmit="event.preventDefault();">
            <div class="dir-field" style="flex:2;position:relative;">
                <label><i class="fas fa-search"></i> Buscar en tiempo real</label>
                <input type="text" name="q" id="dirSearchInput" placeholder="Empiece a escribir... nombre, puesto, correo, teléfono..." value="<?php echo htmlspecialchars($busqueda); ?>" autocomplete="off" data-testid="dir-search">
                <button type="button" id="dirClearBtn" onclick="clearSearch()" style="position:absolute;right:10px;top:32px;background:rgba(255,255,255,0.1);border:none;color:#cfd8dc;width:26px;height:26px;border-radius:50%;cursor:pointer;display:none;align-items:center;justify-content:center;font-size:0.7rem;" title="Limpiar"><i class="fas fa-times"></i></button>
            </div>
            <div class="dir-field">
                <label><i class="fas fa-building"></i> Departamento</label>
                <select name="dept" id="dirDeptSelect" data-testid="dir-dept">
                    <option value="">Todos</option>
                    <?php foreach ($departamentos_ad as $d): ?>
                    <option value="<?php echo htmlspecialchars($d); ?>" <?php echo $deptFilter === $d ? 'selected' : ''; ?>><?php echo htmlspecialchars($d); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($busqueda || $deptFilter): ?>
            <a href="directorio.php" class="dir-btn dir-btn-clear" title="Recargar desde el servidor"><i class="fas fa-sync-alt"></i> Recargar</a>
            <?php endif; ?>
        </form>

        <div class="dir-stats" id="dirStats">
            <i class="fas fa-users"></i> <span id="dirCountVisible"><?php echo count($usuarios); ?></span> de <strong><?php echo count($usuarios); ?></strong> contacto<?php echo count($usuarios) != 1 ? 's' : ''; ?>
        </div>

        <?php if (count($usuarios) > 0): ?>
        <div class="dir-grid">
            <?php foreach ($usuarios as $idx => $u):
                $iniciales = strtoupper(substr($u['nombre_pila'] ?: $u['nombre'], 0, 1) . substr($u['apellido'], 0, 1));
                $celClean = preg_replace('/[^0-9+]/', '', $u['celular']);
                $telClean = preg_replace('/[^0-9+]/', '', $u['telefono']);
                [$color, $grad] = dirColorFor($u['departamento'] ?: $u['nombre']);
                $vcardText = buildVCardText($u);
            ?>
            <div class="contact-card" style="--accent: <?php echo $color; ?>; --accent-grad: <?php echo $grad; ?>;"
                data-testid="contact-card-<?php echo $idx; ?>"
                data-search="<?php echo htmlspecialchars(strtolower(($u['nombre'] ?? '') . ' ' . ($u['nombre_pila'] ?? '') . ' ' . ($u['apellido'] ?? '') . ' ' . ($u['puesto'] ?? '') . ' ' . ($u['email'] ?? '') . ' ' . ($u['telefono'] ?? '') . ' ' . ($u['celular'] ?? '') . ' ' . ($u['oficina'] ?? '')), ENT_QUOTES); ?>"
                data-dept="<?php echo htmlspecialchars($u['departamento'] ?? '', ENT_QUOTES); ?>">
                <div class="card-header">
                    <div class="card-avatar-wrap">
                        <?php if ($u['foto']): ?>
                        <img src="<?php echo $u['foto']; ?>" alt="">
                        <?php else: ?>
                        <div class="card-initials"><?php echo $iniciales; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card-name" title="<?php echo htmlspecialchars($u['nombre']); ?>"><?php echo htmlspecialchars($u['nombre']); ?></div>
                    <?php if ($u['puesto']): ?><div class="card-title" title="<?php echo htmlspecialchars($u['puesto']); ?>"><?php echo htmlspecialchars($u['puesto']); ?></div><?php endif; ?>
                    <?php if ($u['departamento']): ?><span class="card-dept"><?php echo htmlspecialchars($u['departamento']); ?></span><?php endif; ?>

                    <div class="card-contacts">
                        <?php if ($u['telefono']): ?>
                        <div class="card-row"><i class="fas fa-phone"></i><a href="tel:<?php echo $telClean; ?>"><?php echo htmlspecialchars($u['telefono']); ?></a></div>
                        <?php endif; ?>
                        <?php if ($u['celular']): ?>
                        <div class="card-row"><i class="fas fa-mobile-alt"></i><a href="tel:<?php echo $celClean; ?>"><?php echo htmlspecialchars($u['celular']); ?></a></div>
                        <?php endif; ?>
                        <?php if ($u['email']): ?>
                        <div class="card-row" title="<?php echo htmlspecialchars($u['email']); ?>"><i class="fas fa-envelope"></i><a href="mailto:<?php echo htmlspecialchars($u['email']); ?>"><?php echo htmlspecialchars($u['email']); ?></a></div>
                        <?php endif; ?>
                    </div>

                    <div class="card-actions">
                        <?php if ($u['email']): ?>
                        <a href="https://teams.microsoft.com/l/call/0/0?users=<?php echo urlencode($u['email']); ?>" target="_blank" class="card-act-btn" title="Llamar por Teams"><i class="fas fa-phone-alt"></i></a>
                        <a href="https://teams.microsoft.com/l/chat/0/0?users=<?php echo urlencode($u['email']); ?>" target="_blank" class="card-act-btn" title="Chat en Teams"><i class="fab fa-microsoft"></i></a>
                        <a href="mailto:<?php echo htmlspecialchars($u['email']); ?>" class="card-act-btn" title="Enviar correo"><i class="fas fa-envelope"></i></a>
                        <?php endif; ?>
                        <button class="card-act-btn btn-qr"
                            data-vcard="<?php echo htmlspecialchars($vcardText, ENT_QUOTES); ?>"
                            data-name="<?php echo htmlspecialchars($u['nombre'], ENT_QUOTES); ?>"
                            data-title="<?php echo htmlspecialchars($u['puesto'], ENT_QUOTES); ?>"
                            data-color="<?php echo $color; ?>"
                            data-idx="<?php echo $idx; ?>"
                            onclick="showQR(this)"
                            title="Mostrar QR para guardar contacto" data-testid="qr-btn-<?php echo $idx; ?>"><i class="fas fa-qrcode"></i></button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="dir-empty">
            <i class="fas fa-address-book"></i>
            <p style="font-size:1rem;margin-bottom:6px;">No se encontraron contactos</p>
            <p style="font-size:0.8rem;">Intente con otros criterios de b&uacute;squeda</p>
        </div>
        <?php endif; ?>

        <!-- Empty state dinámico (cuando el filtro client-side no encuentra nada) -->
        <div class="dir-empty" id="dirEmptyDynamic" style="display:none;">
            <i class="fas fa-search"></i>
            <p style="font-size:1rem;margin-bottom:6px;">Sin coincidencias</p>
            <p style="font-size:0.8rem;">No hay contactos que coincidan con su búsqueda actual</p>
        </div>
    </main>

    <footer class="footer"><p>&copy; <?php echo date('Y'); ?> Automotriz Corp.</p></footer>

    <!-- QR Modal -->
    <div class="qr-modal" id="qrModal" onclick="if(event.target===this)closeQR()" data-testid="qr-modal">
        <div class="qr-modal-content" id="qrModalContent">
            <button class="qr-close" onclick="closeQR()"><i class="fas fa-times"></i></button>
            <h3 id="qrName"></h3>
            <p class="qr-subtitle" id="qrTitle"></p>
            <div class="qr-canvas" id="qrCanvas"></div>
            <div class="qr-instructions">
                <strong><i class="fas fa-mobile-alt"></i> ¿Cómo guardar el contacto?</strong><br>
                <span style="color:#ddd;">Apunte la cámara del celular al código QR. Toque la notificación que aparece para agregar el contacto a su agenda.</span>
                <div style="margin-top:6px;font-size:0.66rem;color:#90a4ae;">Funciona sin WiFi: los datos están dentro del propio QR.</div>
            </div>
            <a id="qrDownload" href="#" class="qr-download"><i class="fas fa-download"></i> Descargar vCard (.vcf)</a>
        </div>
    </div>

    <script>
    function showQR(btn) {
        var vcardText = btn.getAttribute('data-vcard');
        var name = btn.getAttribute('data-name');
        var title = btn.getAttribute('data-title');
        var color = btn.getAttribute('data-color') || '#42A5F5';
        var idx = btn.getAttribute('data-idx');

        document.getElementById('qrName').textContent = name;
        document.getElementById('qrTitle').textContent = title;
        document.getElementById('qrModalContent').style.setProperty('--qr-color', color);
        document.getElementById('qrDownload').href = 'directorio.php?vcard=' + idx + '&<?php echo http_build_query(array_filter(['q'=>$busqueda,'dept'=>$deptFilter])); ?>';

        // Generar QR con la vCard COMPLETA embebida (escaneable sin red corporativa)
        // Usamos typeNumber=0 (auto) y correction L para maximizar capacidad
        var qr = qrcode(0, 'L');
        qr.addData(vcardText);
        qr.make();
        document.getElementById('qrCanvas').innerHTML = qr.createSvgTag({ cellSize: 5, margin: 0, scalable: true });

        document.getElementById('qrModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closeQR() {
        document.getElementById('qrModal').style.display = 'none';
        document.body.style.overflow = '';
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeQR();
    });

    // ====== FILTRADO EN VIVO (client-side) ======
    (function() {
        var input = document.getElementById('dirSearchInput');
        var deptSel = document.getElementById('dirDeptSelect');
        var clearBtn = document.getElementById('dirClearBtn');
        var cards = document.querySelectorAll('.contact-card');
        var countSpan = document.getElementById('dirCountVisible');
        var emptyDynamic = document.getElementById('dirEmptyDynamic');
        var grid = document.querySelector('.dir-grid');
        if (!input) return;

        // Normalizar texto: quitar tildes y minúsculas
        function normalize(s) {
            return (s || '').toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }

        function applyFilter() {
            var q = normalize(input.value.trim());
            var dept = deptSel.value;
            var tokens = q.split(/\s+/).filter(Boolean);
            var visible = 0;

            cards.forEach(function(card) {
                var haystack = normalize(card.getAttribute('data-search') || '');
                var cardDept = card.getAttribute('data-dept') || '';
                var matchDept = !dept || cardDept === dept;
                // Todos los tokens deben aparecer (AND)
                var matchSearch = tokens.length === 0 || tokens.every(function(t) { return haystack.indexOf(t) !== -1; });
                var show = matchDept && matchSearch;
                card.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            if (countSpan) countSpan.textContent = visible;
            // Mostrar/ocultar empty state dinámico
            if (emptyDynamic) {
                if (visible === 0 && cards.length > 0) {
                    emptyDynamic.style.display = 'block';
                    if (grid) grid.style.display = 'none';
                } else {
                    emptyDynamic.style.display = 'none';
                    if (grid) grid.style.display = '';
                }
            }
            // Botón limpiar
            if (clearBtn) clearBtn.style.display = (input.value.length > 0) ? 'flex' : 'none';
        }

        // Debounce muy ligero (50ms) para experiencia fluida
        var t;
        input.addEventListener('input', function() {
            clearTimeout(t);
            t = setTimeout(applyFilter, 50);
        });
        deptSel.addEventListener('change', applyFilter);

        // Auto-focus al cargar (opcional, mejora UX)
        setTimeout(function() { try { input.focus(); } catch(e){} }, 100);

        // Aplicar al cargar (por si vino con valor previo)
        applyFilter();

        // Función global para botón "X"
        window.clearSearch = function() {
            input.value = '';
            applyFilter();
            input.focus();
        };
    })();
    </script>
</body>
</html>
