<?php
/**
 * Login de Administración
 * Intranet Corporativa
 */

require_once '../includes/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = sanitize($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($usuario) || empty($password)) {
        $error = 'Por favor complete todos los campos';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM administradores WHERE usuario = ? AND activo = 1");
        $stmt->execute([$usuario]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_nombre'] = $admin['nombre_completo'];
            $_SESSION['admin_usuario'] = $admin['usuario'];
            
            // Actualizar último acceso
            $pdo->prepare("UPDATE administradores SET ultimo_acceso = NOW() WHERE id = ?")->execute([$admin['id']]);
            
            header('Location: index.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos';
        }
    }
}

// Si ya está logueado, redirigir
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Panel de Administración</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-logo">
                <img src="../assets/img/logo.png" alt="Logo" onerror="this.style.display='none'">
                <h1>Panel de Administración</h1>
                <p>Intranet Corporativa</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="usuario"><i class="fas fa-user"></i> Usuario</label>
                    <input type="text" id="usuario" name="usuario" class="form-control" 
                           placeholder="Ingrese su usuario" required
                           value="<?php echo htmlspecialchars($_POST['usuario'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Contraseña</label>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Ingrese su contraseña" required>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </button>
            </form>
            
            <p style="text-align: center; margin-top: 20px; color: #666; font-size: 0.9rem;">
                <a href="../index.php" style="color: #e53935; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Volver a la Intranet
                </a>
            </p>
        </div>
    </div>
</body>
</html>
