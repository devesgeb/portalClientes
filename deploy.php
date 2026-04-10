<?php
/**
 * deploy.php — Auto-deploy via GitHub Webhook
 * Coloca este archivo en la raíz del proyecto en el hosting.
 * Configura el webhook en GitHub → Settings → Webhooks → Add webhook
 *   Payload URL : https://tudominio.com/Portal/deploy.php
 *   Content type: application/json
 *   Secret      : (mismo valor que DEPLOY_SECRET abajo)
 *   Events      : Just the push event
 */

// ─── CONFIGURACIÓN ────────────────────────────────────────────────────────────
define('DEPLOY_SECRET', 'CAMBIA_ESTE_SECRET_SEGURO');   // ← cambiar esto
define('DEPLOY_BRANCH', 'refs/heads/main');              // rama a desplegar
define('LOG_FILE',      __DIR__ . '/storage/logs/deploy.log');

// ─── 1. Solo POST ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// ─── 2. Validar firma HMAC del webhook ────────────────────────────────────────
$payload   = file_get_contents('php://input');
$signature = 'sha256=' . hash_hmac('sha256', $payload, DEPLOY_SECRET);
$received  = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if (!hash_equals($signature, $received)) {
    http_response_code(403);
    log_deploy('ERROR: Firma inválida. Recibida: ' . $received);
    exit('Forbidden');
}

// ─── 3. Verificar rama ────────────────────────────────────────────────────────
$data = json_decode($payload, true);
$ref  = $data['ref'] ?? '';

if ($ref !== DEPLOY_BRANCH) {
    http_response_code(200);
    exit("Ignorado: push a '$ref' (solo se despliega '" . DEPLOY_BRANCH . "')");
}

// ─── 4. Ejecutar git pull ─────────────────────────────────────────────────────
$repo_dir = __DIR__;
$commands = [
    "cd " . escapeshellarg($repo_dir),
    "git fetch origin",
    "git reset --hard origin/main",
];

$output  = [];
$success = true;

foreach ($commands as $cmd) {
    $result = shell_exec($cmd . ' 2>&1');
    $output[] = "$ $cmd\n" . ($result ?? '(sin salida)');
    if ($result === null) {
        $success = false;
    }
}

// ─── 5. Log y respuesta ───────────────────────────────────────────────────────
$log_entry = date('[Y-m-d H:i:s]') . " Push desde: " . ($data['pusher']['name'] ?? 'unknown')
    . " | Commit: " . substr($data['after'] ?? '', 0, 7)
    . " | " . ($success ? 'OK' : 'ERROR') . "\n"
    . implode("\n", $output) . "\n" . str_repeat('-', 60) . "\n";

log_deploy($log_entry);

http_response_code(200);
header('Content-Type: application/json');
echo json_encode([
    'success' => $success,
    'branch'  => $ref,
    'commit'  => substr($data['after'] ?? '', 0, 7),
    'message' => $success ? 'Deploy completado' : 'Deploy con errores — revisar log',
]);

function log_deploy(string $text): void
{
    $dir = dirname(LOG_FILE);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    file_put_contents(LOG_FILE, $text, FILE_APPEND | LOCK_EX);
}
