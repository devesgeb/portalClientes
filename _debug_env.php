<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
define("FCPATH", __DIR__ . DIRECTORY_SEPARATOR);
chdir(__DIR__);
require_once __DIR__ . "/app/Config/Paths.php";
$paths = new Config\Paths();
$envDir  = rtrim($paths->envDirectory, "/\\") . DIRECTORY_SEPARATOR;
$envFile = $envDir . ".env";
echo "<pre>\n";
echo "FCPATH: " . FCPATH . "\n";
echo "envDirectory: " . $envDir . "\n";
echo "Archivo .env: " . $envFile . "\n";
echo "Existe: " . (is_file($envFile) ? "SI" : "NO") . "\n\n";
if (is_file($envFile)) {
    echo "Contenido del .env:\n";
    echo htmlspecialchars(file_get_contents($envFile));
}
require_once $paths->systemDirectory . "/Config/DotEnv.php";
(new CodeIgniter\Config\DotEnv($envDir))->load();
echo "\n\napp.baseURL cargado: " . ($_ENV["app.baseURL"] ?? "NO ENCONTRADO") . "\n";
echo "CI_ENVIRONMENT: " . ($_ENV["CI_ENVIRONMENT"] ?? "NO ENCONTRADO") . "\n";
echo "HTTP_HOST: " . ($_SERVER["HTTP_HOST"] ?? "NO ENCONTRADO") . "\n";
echo "</pre>";
