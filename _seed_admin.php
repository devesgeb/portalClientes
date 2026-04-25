<?php
// Script de seed temporal — ELIMINAR después de usarlo
$host = '190.107.177.237';
$db   = 'cna48657_admin';
$user = 'cna48657_portal';
$pass = 'fred1985#';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ver usuarios actuales
    $stmt = $pdo->query("SELECT id, nombre, email, perfil_id, estado FROM tbl_usuarios");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>Usuarios actuales:\n";
    print_r($usuarios);

    // Insertar admin si no existe
    $check = $pdo->prepare("SELECT id FROM tbl_usuarios WHERE nombre = 'admin'");
    $check->execute();

    if (!$check->fetch()) {
        $insert = $pdo->prepare("
            INSERT INTO tbl_usuarios (perfil_id, nombre, apellidos, email, clave, estado)
            VALUES (1, 'admin', 'Administrador', 'admin@prelisto.cl', 'admin123', 1)
        ");
        $insert->execute();
        echo "\nUsuario admin creado. ID: " . $pdo->lastInsertId() . "\n";
    } else {
        echo "\nUsuario admin ya existe.\n";
    }

    // Verificar perfiles
    $perfiles = $pdo->query("SELECT * FROM tbl_perfiles")->fetchAll(PDO::FETCH_ASSOC);
    echo "\nPerfiles:\n";
    print_r($perfiles);
    echo "</pre>";

} catch (PDOException $e) {
    echo "<pre>ERROR: " . $e->getMessage() . "</pre>";
}
