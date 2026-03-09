-- =========================================================================
-- SCRIPT DE MIGRACIÓN: CUENTAS POR COBRAR (1 Tabla -> 2 Tablas)
-- DBMS: MySQL / MariaDB
-- =========================================================================

-- 1. CREACIÓN DE TABLAS NUEVAS
-- -------------------------------------------------------------------------

-- Tabla de Clientes
CREATE TABLE IF NOT EXISTS `tbl_clientes` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(200) NOT NULL,
    `rut` VARCHAR(20) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_rut` (`rut`) -- Mantiene la unicidad del cliente
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Tabla de Documentos por Cobrar
CREATE TABLE IF NOT EXISTS `tbl_documentos_cobrar` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `cliente_id` INT(11) UNSIGNED NOT NULL,
    `tipo_documento` VARCHAR(120) NOT NULL,
    `numero` VARCHAR(50) NOT NULL,
    `fecha` DATE NOT NULL,
    `total` DECIMAL(12,2) NOT NULL DEFAULT '0.00',
    `pagado` DECIMAL(12,2) NOT NULL DEFAULT '0.00',
    `impago` DECIMAL(12,2) NOT NULL DEFAULT '0.00',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_cliente_id` (`cliente_id`),
    CONSTRAINT `fk_documentos_cliente` FOREIGN KEY (`cliente_id`) 
        REFERENCES `tbl_clientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 2. MIGRACIÓN DE DATOS DESDE LA TABLA ANTIGUA
-- -------------------------------------------------------------------------
-- NOTA: Se asume que la tabla actual se llama `tbl_cuentasCobrar`

-- A. Insertar Clientes Únicos
-- Agrupamos por RUT para evitar duplicados. Si un cliente no tiene RUT, lo ignoramos o manejas la limpieza de datos antes.
INSERT IGNORE INTO `tbl_clientes` (`nombre`, `rut`, `created_at`, `updated_at`)
SELECT 
    MAX(`emisor_receptor`), -- Tomamos un nombre cualquiera si hay variación
    `rut`,
    MIN(`created_at`), -- Preservamos la fecha de creación más antigua
    MAX(`updated_at`)  -- Preservamos la última actualización
FROM `tbl_cuentasCobrar`
WHERE `rut` IS NOT NULL AND `rut` != ''
GROUP BY `rut`;

-- B. Insertar Documentos vinculados al cliente
INSERT INTO `tbl_documentos_cobrar` (
    `cliente_id`, `tipo_documento`, `numero`, `fecha`, `total`, `pagado`, `impago`, `created_at`, `updated_at`
)
SELECT 
    c.`id`,
    d.`tipo_documento`,
    d.`numero`,
    d.`fecha`,
    d.`total`,
    d.`pagado`,
    d.`impago`,
    d.`created_at`,
    d.`updated_at`
FROM `tbl_cuentasCobrar` d
JOIN `tbl_clientes` c ON d.`rut` = c.`rut`;


-- 3. (OPCIONAL) RENOMBRAR / ELIMINAR TABLA ANTIGUA
-- -------------------------------------------------------------------------
-- Descomenta esta línea si quieres respaldar la tabla antigua en lugar de borrarla
-- RENAME TABLE `tbl_cuentasCobrar` TO `tbl_cuentasCobrar_old`;

-- O elimínala directamente si estás seguro de que la migración fue exitosa
-- DROP TABLE IF EXISTS `tbl_cuentasCobrar`;
