<?php
/**
 * _setup_tablas.php
 * Ejecutar UNA SOLA VEZ en produccion para crear tbl_productos y tbl_listaPrecios.
 * Acceder via: https://www.prelisto.cl/Portal/_setup_tablas.php?token=setup2026
 * ELIMINAR después de ejecutar.
 */

if (($_GET['token'] ?? '') !== 'setup2026') {
    http_response_code(403); die('Acceso denegado.');
}

// Leer config desde .env de CI4
$env = parse_ini_file(__DIR__ . '/.env');
$host = $env['database.default.hostname'] ?? '127.0.0.1';
$db   = $env['database.default.database'] ?? '';
$user = $env['database.default.username'] ?? '';
$pass = $env['database.default.password'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (Exception $e) {
    die('<pre>Error conexión: ' . $e->getMessage() . '</pre>');
}

$resultados = [];

// ── tbl_productos ─────────────────────────────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS tbl_productos (
    sku                VARCHAR(20)    NOT NULL,
    categoria          VARCHAR(100)   DEFAULT NULL,
    nombre             VARCHAR(200)   NOT NULL,
    marca              VARCHAR(100)   DEFAULT NULL,
    modelo             VARCHAR(100)   DEFAULT NULL,
    unidad             ENUM('KG','CAJA','UN','LT','GR','MT','PAQ','OTR') DEFAULT 'UN',
    codigo_barras      VARCHAR(50)    DEFAULT NULL,
    tipo               ENUM('producto','servicio') DEFAULT 'producto',
    costo_neto         DECIMAL(12,2)  DEFAULT NULL,
    precio_venta_neto  DECIMAL(12,2)  DEFAULT NULL,
    monto_iva          DECIMAL(12,2)  DEFAULT NULL,
    precio_venta_total DECIMAL(12,2)  DEFAULT NULL,
    stock_minimo       DECIMAL(10,3)  DEFAULT 0.000,
    stock_bodega_ppral DECIMAL(10,3)  DEFAULT 0.000,
    stock_bodega_sec   DECIMAL(10,3)  DEFAULT 0.000,
    stock_reservado    DECIMAL(10,3)  DEFAULT 0.000,
    comision_vendedor  DECIMAL(5,2)   DEFAULT NULL,
    descripcion        TEXT,
    descripcion_ecommerce TEXT,
    activo             TINYINT(1)     DEFAULT 1,
    created_at         DATETIME       DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (sku)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$resultados[] = '✅ tbl_productos: OK (creada o ya existía)';

// ── tbl_listaPrecios ──────────────────────────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS tbl_listaPrecios (
    id            INT            NOT NULL AUTO_INCREMENT,
    sku           VARCHAR(20)    NOT NULL,
    lista         VARCHAR(100)   NOT NULL,
    precio_neto   DECIMAL(12,2)  DEFAULT NULL,
    condicion_iva ENUM('afecto','exento') DEFAULT 'afecto',
    monto_iva     DECIMAL(12,2)  DEFAULT NULL,
    precio_total  DECIMAL(12,2)  DEFAULT NULL,
    activo        TINYINT(1)     DEFAULT 1,
    created_at    DATETIME       DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_sku_lista (sku, lista),
    CONSTRAINT fk_lp_sku FOREIGN KEY (sku)
        REFERENCES tbl_productos(sku)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$resultados[] = '✅ tbl_listaPrecios: OK (creada o ya existía)';

// ── Verificar conteos ─────────────────────────────────────────
$nProd  = $pdo->query("SELECT COUNT(*) FROM tbl_productos")->fetchColumn();
$nLista = $pdo->query("SELECT COUNT(*) FROM tbl_listaPrecios")->fetchColumn();
$resultados[] = "📦 tbl_productos: $nProd registros";
$resultados[] = "💰 tbl_listaPrecios: $nLista registros";
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Setup Tablas – Portal</title>
<style>body{font-family:monospace;padding:30px;background:#0f172a;color:#e2e8f0}
h2{color:#a78bfa} .ok{color:#4ade80} .info{color:#60a5fa}</style>
</head>
<body>
<h2>🔧 Setup tablas – Producción</h2>
<?php foreach($resultados as $r) echo "<p class='ok'>$r</p>"; ?>
<p class="info" style="margin-top:20px;font-size:.85em">
⚠️ Elimina este archivo del servidor una vez completada la configuración.
</p>
</body>
</html>
