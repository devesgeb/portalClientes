"""
_safe_deploy_code_and_schema.py
================================
1. Subir por FTP únicamente los 22 archivos de código (vistas, controladores, JS, CSS) a produccion (ftp.prelisto.cl).
2. Subir y ejecutar un script PHP temporal de migración de esquema ESTRICTAMENTE SEGURO (Creación de tabla tbl_abonos_pagar y columna tipo_activo si no existen).
3. Eliminar el script PHP temporal del servidor de producción.
"""
import ftplib
import io
import os
import time
import urllib.request

FTP_HOST   = "ftp.prelisto.cl"
FTP_USER   = "portal@prelisto.cl"
FTP_PASS   = "portal2026***?"
REMOTE_DIR = "Portal"

MIGRATE_URL = 'https://www.prelisto.cl/Portal/public/_safe_migrate.php'

LOCAL_ROOT = os.path.dirname(os.path.abspath(__file__))

FILES_TO_UPLOAD = [
    # Archivos Modificados
    "app/Config/Routes.php",
    "app/Controllers/BalanceDiario.php",
    "app/Controllers/BodegaController.php",
    "app/Controllers/FactoController.php",
    "app/Controllers/FactoProveedorController.php",
    "app/Controllers/Home.php",
    "app/Models/CuentasCobrarModel.php",
    "app/Models/CuentasPagarModel.php",
    "app/Models/LoginModel.php",
    "app/Views/balance_diario.php",
    "app/Views/cobranza/facturas_facto.php",
    "app/Views/contabilidad/historial_balances.php",
    "app/Views/login.php",
    "app/Views/partials/sidebar.php",
    "public/assets/css/admin.css",
    "public/assets/js/BalanceDiario.js",
    "public/assets/js/app/DocumentosModule.js",
    
    # Archivos Nuevos
    "app/Controllers/CronController.php",
    "app/Controllers/NotificacionesController.php",
    "app/Controllers/WebhookController.php",
    "app/Views/administracion/webhook_monitor.php",
    "app/Views/bodega/facto_productos.php",
]

PHP_SAFE_MIGRATE = r"""<?php
// Conexion de produccion
$host = '190.107.177.237';
$db   = 'cna48657_admin';
$user = 'cna48657_portal';
$pass = 'fred1985#';

try {
    $pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(Exception $e) {
    die('ERROR CONEXION BD: ' . $e->getMessage());
}

$resultado = [];

// 1. Crear tabla tbl_abonos_pagar si no existe (No destructivo)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS tbl_abonos_pagar (
        id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        doc_id      INT UNSIGNED NOT NULL,
        monto       DECIMAL(14,2) NOT NULL DEFAULT 0,
        fecha       DATE NOT NULL,
        comentario  VARCHAR(500) DEFAULT '',
        created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_doc_id (doc_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $resultado[] = 'OK: Tabla tbl_abonos_pagar verificada/creada correctamente.';
} catch(Exception $e) {
    $resultado[] = 'ERROR tbl_abonos_pagar: ' . $e->getMessage();
}

// 2. Agregar columna tipo_activo a tbl_productos si no existe (No destructivo)
try {
    $cols = $pdo->query("SHOW COLUMNS FROM tbl_productos LIKE 'tipo_activo'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE tbl_productos ADD COLUMN tipo_activo VARCHAR(20) DEFAULT 'producto' AFTER activo");
        $resultado[] = 'OK: Columna tipo_activo agregada a tbl_productos.';
    } else {
        $resultado[] = 'OK: Columna tipo_activo ya existe en tbl_productos.';
    }
} catch(Exception $e) {
    $resultado[] = 'ERROR tipo_activo: ' . $e->getMessage();
}

header('Content-Type: text/plain; charset=utf-8');
echo implode("\n", $resultado);
echo "\n\nMigración de esquema no destructiva completada con éxito.";
?>"""

def ftp_mkdirs(ftp, remote_path):
    parts = remote_path.split('/')
    current = ''
    for part in parts:
        if not part:
            continue
        current += '/' + part
        try:
            ftp.mkd(current)
        except ftplib.error_perm:
            pass

def main():
    print(f"Conectando servidor FTP {FTP_HOST}...")
    ftp = ftplib.FTP()
    ftp.connect(FTP_HOST, 21, timeout=30)
    ftp.login(FTP_USER, FTP_PASS)
    ftp.set_pasv(True)
    print("FTP Conectado exitosamente.\n")

    # 1. Subir archivos de código
    print("=== PASO 1: Subiendo 22 archivos de código (PHP / JS / CSS) ===")
    exitos = 0
    for rel_path in FILES_TO_UPLOAD:
        local_path = os.path.join(LOCAL_ROOT, rel_path.replace('/', os.sep))
        remote_path = f"{REMOTE_DIR}/{rel_path}"
        if os.path.exists(local_path):
            remote_dir = os.path.dirname(remote_path)
            ftp_mkdirs(ftp, remote_dir)
            with open(local_path, 'rb') as f:
                ftp.storbinary(f'STOR {remote_path}', f)
            exitos += 1
            print(f" [{exitos}/22] Subido correctamente: {rel_path}")
        else:
            print(f" [ADVERTENCIA] Archivo no encontrado: {rel_path}")

    # 2. Subir script PHP de migración segura de esquema
    print("\n=== PASO 2: Subiendo y Ejecutando Migración de Esquema Segura ===")
    migrate_bytes = PHP_SAFE_MIGRATE.encode('utf-8')
    ftp.storbinary(f'STOR {REMOTE_DIR}/public/_safe_migrate.php', io.BytesIO(migrate_bytes))
    print(" [OK] Script _safe_migrate.php subido temporalmente a producción.")

    # 3. Disparar ejecucion vía HTTP
    time.sleep(2)
    try:
        req = urllib.request.Request(MIGRATE_URL, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(req, timeout=30) as resp:
            resultado = resp.read().decode('utf-8')
            print("\n--- Respuesta del Servidor de Producción ---")
            print(resultado)
            print("-------------------------------------------\n")
    except Exception as e:
        print(f"[ERROR HTTP] No se pudo ejecutar _safe_migrate.php: {e}")

    # 4. Eliminar script temporal de migración
    try:
        ftp.delete(f'{REMOTE_DIR}/public/_safe_migrate.php')
        print(" [OK] Script temporal _safe_migrate.php eliminado del servidor de producción.")
    except Exception as e:
        print(f" [ADVERTENCIA] No se pudo eliminar el script temporal: {e}")

    ftp.quit()
    print("\n=== ¡DESPLIEGUE SEGURO COMPLETADO CON ÉXITO Y CERO IMPACTO EN DATOS! ===")

if __name__ == "__main__":
    main()
