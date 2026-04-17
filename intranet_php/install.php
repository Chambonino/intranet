<?php
/**
 * Script de Instalación - Intranet Corporativa
 * Ejecutar UNA SOLA VEZ después de importar database.sql
 * Luego ELIMINAR este archivo por seguridad.
 */

require_once 'includes/config.php';

// Credenciales del administrador
$usuario = 'mbonilla';
$password = 'Puebla2007';
$nombre = 'Administrador';
$email = 'admin@empresa.com';

// Generar hash seguro
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
    // Eliminar admin anterior si existe
    $pdo->prepare("DELETE FROM administradores WHERE usuario = ? OR usuario = 'admin'")->execute([$usuario]);
    
    // Insertar nuevo administrador
    $stmt = $pdo->prepare("INSERT INTO administradores (usuario, password, nombre_completo, email, activo) VALUES (?, ?, ?, ?, 1)");
    $stmt->execute([$usuario, $passwordHash, $nombre, $email]);
    
    echo "<div style='font-family: Arial; max-width: 500px; margin: 100px auto; padding: 30px; background: #e8f5e9; border-radius: 10px; text-align: center;'>";
    echo "<h2 style='color: #2e7d32;'>&#10004; Instalación Exitosa</h2>";
    echo "<p><strong>Usuario:</strong> {$usuario}</p>";
    echo "<p><strong>Contraseña:</strong> (la que configuraste)</p>";
    echo "<p style='margin-top: 20px;'><a href='admin/login.php' style='background: #e53935; color: white; padding: 10px 25px; border-radius: 5px; text-decoration: none;'>Ir al Login</a></p>";
    echo "<p style='color: #c62828; margin-top: 20px; font-size: 0.9rem;'><strong>IMPORTANTE:</strong> Elimina este archivo (install.php) por seguridad.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='font-family: Arial; max-width: 500px; margin: 100px auto; padding: 30px; background: #ffebee; border-radius: 10px; text-align: center;'>";
    echo "<h2 style='color: #c62828;'>&#10008; Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
