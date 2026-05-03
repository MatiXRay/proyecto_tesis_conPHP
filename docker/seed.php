<?php
/**
 * Genera usuarios de prueba con contraseñas hasheadas correctamente.
 * Ejecutar UNA SOLA VEZ después de docker compose up:
 *
 *   docker compose exec web php /var/www/html/bialy/docker/seed.php
 */

require_once __DIR__ . '/../conexion.php';

$usuarios = [
    [
        'nombre'   => 'Admin',
        'apellido' => 'Bialystok',
        'mail'     => 'admin@bialy.local',
        'telefono' => '2215550001',
        'rol_id'   => 1,
        'username' => 'admin',
        'password' => 'admin1234',
    ],
    [
        'nombre'   => 'Juan',
        'apellido' => 'Cervecero',
        'mail'     => 'elaborador@bialy.local',
        'telefono' => '2215550002',
        'rol_id'   => 2,
        'username' => 'elaborador',
        'password' => 'bialy2024',
    ],
    [
        'nombre'   => 'Maria',
        'apellido' => 'Taster',
        'mail'     => 'taster@bialy.local',
        'telefono' => '2215550003',
        'rol_id'   => 3,
        'username' => 'taster',
        'password' => 'cata1234',
    ],
];

try {
    $pdo  = getPDO();
    $stmt = $pdo->prepare(
        "INSERT INTO users (nombre, apellido, mail, telefono, rol_id, username, password)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE password = VALUES(password)"
    );

    foreach ($usuarios as $u) {
        $hash = password_hash($u['password'], PASSWORD_DEFAULT);
        $stmt->execute([$u['nombre'], $u['apellido'], $u['mail'], $u['telefono'], $u['rol_id'], $u['username'], $hash]);
        echo "✓ Usuario '{$u['username']}' (password: {$u['password']}) — OK\n";
    }

    echo "\nUsuarios listos. Accedé en: http://localhost:8080/bialy/login\n";

} catch (PDOException $ex) {
    echo "Error: " . $ex->getMessage() . "\n";
    exit(1);
}
