<?php
/**
 * Directorio Telefónico Corporativo - Active Directory
 * Consulta usuarios de AD con foto, teléfono, email
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
        // Filtro LDAP
        $filter = "(&(Company=Nicrobolta)(sn=*)(Useraccountcontrol=512)";
        if ($busqueda) {
            $filter .= "(|(givenName=*$busqueda*)(sn=*$busqueda*)(displayName=*$busqueda*)(mail=*$busqueda*))";
        }
        if ($deptFilter) {
            $filter .= "(department=$deptFilter)";
        }
        $filter .= ")";
        
        $attrs = ['displayname','givenname','sn','title','department','telephonenumber','mobile','mail','thumbnailphoto','physicaldeliveryofficename','company','description'];
        
        $result = @ldap_search($ldap, $ldap_dn, $filter, $attrs);
        if ($result) {
            $entries = ldap_get_entries($ldap, $result);
            
            for ($i = 0; $i < $entries['count']; $i++) {
                $entry = $entries[$i];
                
                // Foto del AD (thumbnailPhoto es binario)
                $foto = '';
                if (isset($entry['thumbnailphoto'][0])) {
                    $foto = 'data:image/jpeg;base64,' . base64_encode($entry['thumbnailphoto'][0]);
                }
                
                $dept = $entry['department'][0] ?? '';
                if ($dept && !in_array($dept, $departamentos_ad)) {
                    $departamentos_ad[] = $dept;
                }
                
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
            
            // Ordenar por nombre
            usort($usuarios, function($a, $b) { return strcmp($a['nombre'], $b['nombre']); });
            sort($departamentos_ad);
        }
        ldap_close($ldap);
    } else {
        $error = 'No se pudo autenticar con Active Directory. Verifique credenciales.';
    }
} else {
    $error = 'No se pudo conectar al servidor LDAP (192.168.10.100).';
}

// Generar vCard
if (isset($_GET['vcard'])) {
    $idx = (int)$_GET['vcard'];
    if (isset($usuarios[$idx])) {
        $u = $usuarios[$idx];
        header('Content-Type: text/vcard; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-zA-Z0-9]/', '_', $u['nombre']) . '.vcf"');
        echo "BEGIN:VCARD\r\n";
        echo "VERSION:3.0\r\n";
        echo "FN:" . $u['nombre'] . "\r\n";
        echo "N:" . $u['apellido'] . ";" . $u['nombre_pila'] . ";;;\r\n";
        echo "TITLE:" . $u['puesto'] . "\r\n";
        echo "ORG:;" . $u['departamento'] . "\r\n";
        if ($u['telefono']) echo "TEL;TYPE=WORK,VOICE:" . $u['telefono'] . "\r\n";
        if ($u['celular']) echo "TEL;TYPE=CELL,VOICE:" . $u['celular'] . "\r\n";
        if ($u['email']) echo "EMAIL;TYPE=INTERNET:" . $u['email'] . "\r\n";
        echo "END:VCARD\r\n";
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
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <style>
        .dir-page { max-width: 1400px; margin: 0 auto; padding: 20px 40px 40px; }
        
        /* Filtros */
        .dir-filters { background: var(--bg-card); border-radius: 14px; padding: 22px 25px; margin-bottom: 28px; display: flex; gap: 12px; align-items: end; flex-wrap: wrap; }
        .dir-field { flex: 1; min-width: 180px; }
        .dir-field label { display: block; font-size: 0.72rem; color: var(--text-muted); margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; }
        .dir-field input, .dir-field select { width: 100%; padding: 11px 15px; background: var(--bg-input); border: 1px solid var(--border-color); border-radius: 10px; color: white; font-size: 0.9rem; transition: border-color 0.3s; }
        .dir-field input:focus, .dir-field select:focus { outline: none; border-color: var(--accent-blue); }
        .dir-btn { padding: 11px 24px; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; }
        .dir-btn:hover { transform: translateY(-2px); }
        .dir-btn-blue { background: var(--accent-blue); color: white; }
        .dir-btn-clear { background: var(--bg-input); color: var(--text-secondary); }
        
        /* Stats */
        .dir-stats { color: var(--text-muted); font-size: 0.82rem; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        
        /* Grid de tarjetas */
        .dir-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
        
        /* Tarjeta de contacto */
        .contact-card {
            background: var(--bg-card);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            border: 1px solid transparent;
        }
        .contact-card:hover {
            transform: translateY(-8px) scale(1.02);
            border-color: var(--accent-blue);
            box-shadow: 0 20px 40px rgba(25, 118, 210, 0.15), 0 0 0 1px rgba(25, 118, 210, 0.1);
        }
        .contact-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-blue), var(--accent-teal));
            opacity: 0;
            transition: opacity 0.3s;
        }
        .contact-card:hover::before { opacity: 1; }
        
        .card-top {
            display: flex;
            gap: 16px;
            padding: 22px 22px 0;
            align-items: flex-start;
        }
        
        .card-avatar {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--bg-input);
            flex-shrink: 0;
            transition: border-color 0.3s;
        }
        .contact-card:hover .card-avatar {
            border-color: var(--accent-blue);
        }
        
        .card-avatar-placeholder {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-teal));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            flex-shrink: 0;
            transition: transform 0.3s;
        }
        .contact-card:hover .card-avatar-placeholder { transform: scale(1.05); }
        
        .card-info { flex: 1; min-width: 0; }
        .card-name { font-size: 1.05rem; font-weight: 700; color: var(--text-primary); margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .card-title { font-size: 0.78rem; color: var(--accent-blue); font-weight: 500; margin-bottom: 4px; }
        .card-dept { font-size: 0.72rem; color: var(--text-muted); display: flex; align-items: center; gap: 5px; }
        
        .card-contacts {
            padding: 15px 22px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .card-contact-row {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.82rem;
            color: var(--text-secondary);
        }
        .card-contact-row i { width: 16px; color: var(--text-muted); font-size: 0.8rem; }
        .card-contact-row a { color: var(--accent-blue); text-decoration: none; transition: color 0.3s; }
        .card-contact-row a:hover { color: var(--accent-teal); text-decoration: underline; }
        
        /* Botones de acción */
        .card-actions {
            display: flex;
            gap: 0;
            border-top: 1px solid var(--border-color);
        }
        .card-action {
            flex: 1;
            padding: 12px;
            text-align: center;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.75rem;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            cursor: pointer;
            border: none;
            background: none;
            border-right: 1px solid var(--border-color);
        }
        .card-action:last-child { border-right: none; }
        .card-action:hover { background: rgba(25,118,210,0.08); color: var(--accent-blue); }
        .card-action i { font-size: 1rem; }
        .card-action.teams:hover { color: #6264A7; }
        .card-action.outlook:hover { color: #0078D4; }
        .card-action.phone:hover { color: var(--accent-green); }
        .card-action.vcard:hover { color: var(--accent-orange); }
        
        /* QR Modal */
        .qr-modal {
            position: fixed; top:0; left:0; right:0; bottom:0;
            background: rgba(0,0,0,0.9); z-index: 9999;
            display: none; align-items: center; justify-content: center;
        }
        .qr-modal-content {
            background: var(--bg-card); border-radius: 20px; padding: 35px;
            text-align: center; max-width: 380px; width: 90%;
        }
        .qr-modal-content h3 { margin-bottom: 5px; font-size: 1.1rem; }
        .qr-modal-content p { color: var(--text-muted); font-size: 0.8rem; margin-bottom: 20px; }
        .qr-canvas { background: white; padding: 15px; border-radius: 12px; display: inline-block; margin-bottom: 15px; }
        
        .conn-error { background: rgba(229,57,53,0.1); border: 1px solid rgba(229,57,53,0.3); border-radius: 12px; padding: 22px; color: #ef5350; display: flex; align-items: center; gap: 15px; margin-bottom: 25px; }
        .dir-empty { text-align: center; padding: 80px 20px; color: var(--text-muted); }
        .dir-empty i { font-size: 4rem; opacity: 0.2; margin-bottom: 20px; display: block; }
        
        @media (max-width: 768px) {
            .dir-grid { grid-template-columns: 1fr; }
            .dir-filters { flex-direction: column; }
        }
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

    <main class="dir-page">
        <h2 style="margin-bottom:22px;display:flex;align-items:center;gap:12px;">
            <i class="fas fa-address-book" style="color:var(--accent-blue);"></i> Directorio Telef&oacute;nico
        </h2>

        <?php if ($error): ?>
        <div class="conn-error">
            <i class="fas fa-exclamation-triangle" style="font-size:1.8rem;"></i>
            <div><strong>Error de conexi&oacute;n</strong><p style="font-size:0.85rem;margin-top:5px;"><?php echo $error; ?></p></div>
        </div>
        <?php endif; ?>

        <!-- Filtros -->
        <form method="GET" class="dir-filters">
            <div class="dir-field" style="flex:2;">
                <label><i class="fas fa-search"></i> Buscar</label>
                <input type="text" name="q" placeholder="Nombre, apellido o correo..." value="<?php echo htmlspecialchars($busqueda); ?>">
            </div>
            <div class="dir-field">
                <label><i class="fas fa-building"></i> Departamento</label>
                <select name="dept">
                    <option value="">Todos</option>
                    <?php foreach ($departamentos_ad as $d): ?>
                    <option value="<?php echo htmlspecialchars($d); ?>" <?php echo $deptFilter === $d ? 'selected' : ''; ?>><?php echo htmlspecialchars($d); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="dir-btn dir-btn-blue"><i class="fas fa-search"></i> Buscar</button>
            <?php if ($busqueda || $deptFilter): ?>
            <a href="directorio.php" class="dir-btn dir-btn-clear"><i class="fas fa-times"></i> Limpiar</a>
            <?php endif; ?>
        </form>

        <div class="dir-stats">
            <i class="fas fa-users"></i> <?php echo count($usuarios); ?> contacto<?php echo count($usuarios) != 1 ? 's' : ''; ?> encontrado<?php echo count($usuarios) != 1 ? 's' : ''; ?>
            <?php if ($busqueda): ?> para &quot;<?php echo htmlspecialchars($busqueda); ?>&quot;<?php endif; ?>
            <?php if ($deptFilter): ?> en <strong><?php echo htmlspecialchars($deptFilter); ?></strong><?php endif; ?>
        </div>

        <?php if (count($usuarios) > 0): ?>
        <div class="dir-grid">
            <?php foreach ($usuarios as $idx => $u):
                $iniciales = strtoupper(substr($u['nombre_pila'] ?: $u['nombre'], 0, 1) . substr($u['apellido'], 0, 1));
                $telClean = preg_replace('/[^0-9+]/', '', $u['telefono']);
                $celClean = preg_replace('/[^0-9+]/', '', $u['celular']);
            ?>
            <div class="contact-card">
                <div class="card-top">
                    <?php if ($u['foto']): ?>
                    <img src="<?php echo $u['foto']; ?>" class="card-avatar" alt="">
                    <?php else: ?>
                    <div class="card-avatar-placeholder"><?php echo $iniciales; ?></div>
                    <?php endif; ?>
                    <div class="card-info">
                        <div class="card-name"><?php echo htmlspecialchars($u['nombre']); ?></div>
                        <div class="card-title"><?php echo htmlspecialchars($u['puesto']); ?></div>
                        <div class="card-dept"><i class="fas fa-building"></i> <?php echo htmlspecialchars($u['departamento']); ?></div>
                    </div>
                </div>
                <div class="card-contacts">
                    <?php if ($u['telefono']): ?>
                    <div class="card-contact-row">
                        <i class="fas fa-phone"></i>
                        <a href="tel:<?php echo $telClean; ?>" title="Llamar por Teams"><?php echo htmlspecialchars($u['telefono']); ?></a>
                    </div>
                    <?php endif; ?>
                    <?php if ($u['celular']): ?>
                    <div class="card-contact-row">
                        <i class="fas fa-mobile-alt"></i>
                        <a href="tel:<?php echo $celClean; ?>"><?php echo htmlspecialchars($u['celular']); ?></a>
                    </div>
                    <?php endif; ?>
                    <?php if ($u['email']): ?>
                    <div class="card-contact-row">
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:<?php echo htmlspecialchars($u['email']); ?>" title="Enviar correo en Outlook"><?php echo htmlspecialchars($u['email']); ?></a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-actions">
                    <?php if ($u['telefono']): ?>
                    <a href="https://teams.microsoft.com/l/call/0/0?users=<?php echo urlencode($u['email']); ?>" target="_blank" class="card-action phone" title="Llamar por Teams">
                        <i class="fas fa-phone-alt"></i><span>Llamar</span>
                    </a>
                    <?php endif; ?>
                    <?php if ($u['email']): ?>
                    <a href="mailto:<?php echo htmlspecialchars($u['email']); ?>" class="card-action outlook" title="Enviar correo por Outlook">
                        <i class="fas fa-envelope"></i><span>Correo</span>
                    </a>
                    <a href="https://teams.microsoft.com/l/chat/0/0?users=<?php echo urlencode($u['email']); ?>" target="_blank" class="card-action teams" title="Chat en Teams">
                        <i class="fab fa-microsoft"></i><span>Teams</span>
                    </a>
                    <?php endif; ?>
                    <button class="card-action vcard" onclick="showQR(<?php echo $idx; ?>, '<?php echo htmlspecialchars($u['nombre'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($u['puesto'], ENT_QUOTES); ?>')" title="vCard + QR">
                        <i class="fas fa-qrcode"></i><span>QR</span>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="dir-empty">
            <i class="fas fa-address-book"></i>
            <p style="font-size:1.1rem;margin-bottom:8px;">No se encontraron contactos</p>
            <p style="font-size:0.85rem;">Intente con otros criterios de b&uacute;squeda</p>
        </div>
        <?php endif; ?>
    </main>

    <footer class="footer"><p>&copy; <?php echo date('Y'); ?> Automotriz Corp.</p></footer>

    <!-- QR Modal -->
    <div class="qr-modal" id="qrModal" onclick="if(event.target===this)this.style.display='none'">
        <div class="qr-modal-content">
            <button onclick="document.getElementById('qrModal').style.display='none'" style="position:absolute;top:15px;right:18px;background:none;border:none;color:var(--text-muted);font-size:1.3rem;cursor:pointer;"><i class="fas fa-times"></i></button>
            <h3 id="qrName"></h3>
            <p id="qrTitle"></p>
            <div class="qr-canvas" id="qrCanvas"></div>
            <br>
            <a id="qrDownload" href="#" class="dir-btn dir-btn-blue" style="text-decoration:none;display:inline-flex;margin-top:10px;">
                <i class="fas fa-download"></i> Descargar vCard
            </a>
        </div>
    </div>

    <script>
    function showQR(idx, name, title) {
        document.getElementById('qrName').textContent = name;
        document.getElementById('qrTitle').textContent = title;
        document.getElementById('qrDownload').href = 'directorio.php?<?php echo http_build_query(array_filter(['q'=>$busqueda,'dept'=>$deptFilter])); ?>&vcard=' + idx;
        
        // Generar QR con vCard URL
        var vcardUrl = window.location.origin + window.location.pathname + '?vcard=' + idx + '&<?php echo http_build_query(array_filter(['q'=>$busqueda,'dept'=>$deptFilter])); ?>';
        var qr = qrcode(0, 'M');
        qr.addData(vcardUrl);
        qr.make();
        document.getElementById('qrCanvas').innerHTML = qr.createSvgTag(5, 0);
        
        document.getElementById('qrModal').style.display = 'flex';
    }
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') document.getElementById('qrModal').style.display = 'none';
    });
    </script>
</body>
</html>
