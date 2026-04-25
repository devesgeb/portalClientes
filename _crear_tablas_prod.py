import pymysql

conn = pymysql.connect(
    host='190.107.177.237',
    user='cna48657_portal',
    password='fred1985#',
    database='cna48657_admin',
    port=3306,
    connect_timeout=15
)
cur = conn.cursor()

# Verificar tablas existentes
cur.execute("SHOW TABLES LIKE 'tbl_%'")
rows = cur.fetchall()
print('Tablas existentes en produccion:')
for r in rows:
    print(' -', r[0])

tiene_productos   = any('tbl_productos'   in r[0] for r in rows)
tiene_listaPrecios = any('tbl_listaPrecios' in r[0] for r in rows)

# ── Crear tbl_productos si no existe ────────────────────────────
if not tiene_productos:
    print('\nCreando tbl_productos...')
    cur.execute("""
        CREATE TABLE tbl_productos (
            sku               VARCHAR(20)    NOT NULL,
            categoria         VARCHAR(100)   DEFAULT NULL,
            nombre            VARCHAR(200)   NOT NULL,
            marca             VARCHAR(100)   DEFAULT NULL,
            modelo            VARCHAR(100)   DEFAULT NULL,
            unidad            ENUM('KG','CAJA','UN','LT','GR','MT','PAQ','OTR') DEFAULT 'UN',
            codigo_barras     VARCHAR(50)    DEFAULT NULL,
            tipo              ENUM('producto','servicio') DEFAULT 'producto',
            costo_neto        DECIMAL(12,2)  DEFAULT NULL,
            precio_venta_neto DECIMAL(12,2)  DEFAULT NULL,
            monto_iva         DECIMAL(12,2)  DEFAULT NULL,
            precio_venta_total DECIMAL(12,2) DEFAULT NULL,
            stock_minimo      DECIMAL(10,3)  DEFAULT 0.000,
            stock_bodega_ppral DECIMAL(10,3) DEFAULT 0.000,
            stock_bodega_sec  DECIMAL(10,3)  DEFAULT 0.000,
            stock_reservado   DECIMAL(10,3)  DEFAULT 0.000,
            comision_vendedor DECIMAL(5,2)   DEFAULT NULL,
            descripcion       TEXT,
            descripcion_ecommerce TEXT,
            activo            TINYINT(1)     DEFAULT 1,
            created_at        DATETIME       DEFAULT CURRENT_TIMESTAMP,
            updated_at        DATETIME       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (sku)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    """)
    print('  OK tbl_productos creada')
else:
    print('\n  tbl_productos ya existe, omitida.')

# ── Crear tbl_listaPrecios si no existe ────────────────────────
if not tiene_listaPrecios:
    print('Creando tbl_listaPrecios...')
    cur.execute("""
        CREATE TABLE tbl_listaPrecios (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    """)
    print('  OK tbl_listaPrecios creada')
else:
    print('  tbl_listaPrecios ya existe, omitida.')

conn.commit()
conn.close()
print('\nListo. Base de datos de produccion actualizada.')
