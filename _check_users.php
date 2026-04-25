<?php
$host = '190.107.177.237';
$db   = 'cna48657_admin';
$user = 'cna48657_portal';
$pass = 'fred1985#';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT id, nombre, email, clave, perfil_id, estado FROM tbl_usuarios ORDER BY id");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<pre>\n";
    foreach ($rows as $r) {
        $esHash = strlen($r['clave']) > 40;
        echo "ID: {$r['id']} | nombre: {$r['nombre']} | email: {$r['email']} | estado: {$r['estado']}\n";
        echo "  clave: {$r['clave']}\n";
        echo "  tipo_clave: " . ($esHash ? 'HASH (password_hash)' : 'TEXTO PLANO') . "\n\n";
    }
    echo "</pre>";
} catch (PDOException $e) {
    echo "<pre>ERROR: " . $e->getMessage() . "</pre>";
}
