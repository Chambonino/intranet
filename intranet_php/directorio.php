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
        .dir-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 22px; }
        
        /* Tarjeta de contacto - Estilo nuevo */
        .contact-card {
            background: #1a1f2e;
            border-radius: 18px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            border: 1px solid rgba(255,255,255,0.05);
            padding: 28px 25px 0;
        }
        .contact-card:hover {
            transform: translateY(-8px) scale(1.02);
            border-color: rgba(255,180,50,0.4);
            box-shadow: 0 20px 50px rgba(255,180,50,0.1), 0 0 0 1px rgba(255,180,50,0.15);
        }
        .contact-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, #f48fb1, #ffb74d);
            opacity: 0;
            transition: opacity 0.4s;
        }
        .contact-card:hover::before { opacity: 1; }
        
        /* Avatar cuadrado redondeado grande */
        .card-avatar-box {
            width: 80px;
            height: 80px;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 18px;
            flex-shrink: 0;
            transition: transform 0.3s;
        }
        .contact-card:hover .card-avatar-box { transform: scale(1.05); }
        .card-avatar-box img {
            width: 100%; height: 100%;
            object-fit: cover;
        }
        .card-avatar-initials {
            width: 80px; height: 80px;
            border-radius: 16px;
            background: linear-gradient(135deg, #f48fb1, #f06292);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: 1px;
            margin-bottom: 18px;
            transition: transform 0.3s;
        }
        .contact-card:hover .card-avatar-initials { transform: scale(1.05); }
        
        /* Info */
        .card-name { font-size: 1.25rem; font-weight: 800; color: #ffffff; margin-bottom: 4px; }
        .card-title { font-size: 0.88rem; color: #f48fb1; font-weight: 600; margin-bottom: 8px; }
        .card-dept-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(255,255,255,0.08);
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-bottom: 18px;
        }
        .card-dept-badge i { font-size: 0.7rem; }
        
        /* Contactos */
        .card-contacts {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding-bottom: 20px;
        }
        .card-contact-row {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
            color: #cfd8dc;
        }
        .card-contact-row .ci { width: 20px; text-align: center; }
        .card-contact-row .ci-phone { color: #a5d6a7; }
        .card-contact-row .ci-mobile { color: #ffb74d; }
        .card-contact-row .ci-email { color: #ffb74d; }
        .card-contact-row a { color: #cfd8dc; text-decoration: none; transition: color 0.3s; }
        .card-contact-row a:hover { color: #ffffff; }
        
        /* Botones de acción - estilo con bordes */
        .card-actions {
            display: flex;
            gap: 10px;
            padding: 18px 0;
            border-top: 1px solid rgba(255,255,255,0.06);
        }
        .card-act-btn {
            flex: 1;
            padding: 10px 0;
            text-align: center;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: none;
        }
        .card-act-btn.btn-call {
            background: transparent;
            border: 1.5px solid rgba(255,255,255,0.15);
            color: #cfd8dc;
        }
        .card-act-btn.btn-call:hover { border-color: #a5d6a7; color: #a5d6a7; background: rgba(165,214,167,0.08); }
        .card-act-btn.btn-teams {
            background: transparent;
            border: 1.5px solid rgba(255,255,255,0.15);
            color: #cfd8dc;
        }
        .card-act-btn.btn-teams:hover { border-color: #7986cb; color: #7986cb; background: rgba(121,134,203,0.08); }
        .card-act-btn.btn-correo {
            background: #ffb74d;
            color: #1a1f2e;
            border: 1.5px solid #ffb74d;
        }
        .card-act-btn.btn-correo:hover { background: #ffa726; border-color: #ffa726; }
        .card-act-btn.btn-qr {
            background: transparent;
            border: 1.5px solid rgba(255,255,255,0.15);
            color: #cfd8dc;
            max-width: 44px;
        }
        .card-act-btn.btn-qr:hover { border-color: #ffb74d; color: #ffb74d; }
        
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
                <?php if ($u['foto']): ?>
                <div class="card-avatar-box"><img src="<?php echo $u['foto']; ?>" alt=""></div>
                <?php else: ?>
                <div class="card-avatar-initials"><?php echo $iniciales; ?></div>
                <?php endif; ?>

                <div class="card-name"><?php echo htmlspecialchars($u['nombre']); ?></div>
                <div class="card-title"><?php echo htmlspecialchars($u['puesto']); ?></div>
                <span class="card-dept-badge"><i class="fas fa-building"></i> <?php echo htmlspecialchars($u['departamento']); ?></span>

                <div class="card-contacts">
                    <?php if ($u['telefono']): ?>
                    <div class="card-contact-row">
                        <i class="fas fa-phone ci ci-phone"></i>
                        <a href="https://teams.microsoft.com/l/call/0/0?users=<?php echo urlencode($u['email']); ?>" target="_blank"><?php echo htmlspecialchars($u['telefono']); ?></a>
                    </div>
                    <?php endif; ?>
                    <?php if ($u['celular']): ?>
                    <div class="card-contact-row">
                        <i class="fas fa-mobile-alt ci ci-mobile"></i>
                        <a href="tel:<?php echo $celClean; ?>"><?php echo htmlspecialchars($u['celular']); ?></a>
                    </div>
                    <?php endif; ?>
                    <?php if ($u['email']): ?>
                    <div class="card-contact-row">
                        <i class="fas fa-envelope ci ci-email"></i>
                        <a href="mailto:<?php echo htmlspecialchars($u['email']); ?>"><?php echo htmlspecialchars($u['email']); ?></a>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="card-actions">
                    <?php if ($u['telefono']): ?>
                    <a href="https://teams.microsoft.com/l/call/0/0?users=<?php echo urlencode($u['email']); ?>" target="_blank" class="card-act-btn btn-call"><i class="fas fa-phone-alt"></i> Llamar</a>
                    <?php endif; ?>
                    <?php if ($u['email']): ?>
                    <a href="https://teams.microsoft.com/l/chat/0/0?users=<?php echo urlencode($u['email']); ?>" target="_blank" class="card-act-btn btn-teams"><i class="fab fa-microsoft"></i> Teams</a>
                    <a href="mailto:<?php echo htmlspecialchars($u['email']); ?>" class="card-act-btn btn-correo"><i class="fas fa-envelope"></i> Correo</a>
                    <?php endif; ?>
                    <button class="card-act-btn btn-qr" onclick="showQR(<?php echo $idx; ?>, '<?php echo htmlspecialchars($u['nombre'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($u['puesto'], ENT_QUOTES); ?>')" title="QR + vCard"><i class="fas fa-qrcode"></i></button>
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
