-- =========================================================================
-- SCRIPT DE ESTRUCTURA COMPLETA (USUARIOS, CLIENTES, PROVEEDORES)
-- RELACIONADOS POR RUT (CARGA MASIVA EXCEL)
-- Ideal para generar Diagrama Entidad-Relación (ER) en Navicat
-- DBMS: MySQL / MariaDB
-- =========================================================================

-- Dando de baja las tablas existentes en orden inverso para evitar constraint FK
DROP TABLE IF EXISTS `tbl_documentos_pagar`;
DROP TABLE IF EXISTS `tbl_documentos_cobrar`;
DROP TABLE IF EXISTS `tbl_proveedores`;
DROP TABLE IF EXISTS `tbl_clientes`;
DROP TABLE IF EXISTS `tbl_usuarios`;
DROP TABLE IF EXISTS `tbl_perfiles`;

-- -----------------------------------------------------
-- 1. TABLA: tbl_perfiles (Roles del sistema)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbl_perfiles` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(50) NOT NULL,
    `descripcion` VARCHAR(255) DEFAULT NULL,
    `estado` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '1: Activo, 0: Inactivo',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_nombre_perfil` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tbl_perfiles` (`id`, `nombre`, `descripcion`) VALUES
(1, 'Admin', 'Administrador general del sistema con acceso total'),
(2, 'Cliente', 'Usuario con rol de cliente para cuentas por cobrar'),
(3, 'Proveedor', 'Usuario con rol de proveedor para cuentas por pagar');


-- -----------------------------------------------------
-- 2. TABLA: tbl_usuarios (Logins y accesos)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbl_usuarios` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `perfil_id` INT(11) UNSIGNED NOT NULL,
    `nombre` VARCHAR(150) NOT NULL,
    `apellidos` VARCHAR(150) DEFAULT NULL,
    `rut` VARCHAR(20) DEFAULT NULL COMMENT 'RUT para match lógico con clientes/proveedores',
    `email` VARCHAR(150) NOT NULL,
    `clave` VARCHAR(255) NOT NULL COMMENT 'Contraseña encriptada',
    `telefono` VARCHAR(50) DEFAULT NULL,
    `estado` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '1: Activo, 0: Suspendido',
    `ultimo_acceso` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_email` (`email`),
    UNIQUE KEY `uk_rut_usuario` (`rut`),
    KEY `idx_perfil_id` (`perfil_id`),
    CONSTRAINT `fk_usuario_perfil` FOREIGN KEY (`perfil_id`) 
        REFERENCES `tbl_perfiles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- MÓDULO: CUENTAS POR COBRAR (CLIENTES)
-- =====================================================

-- -----------------------------------------------------
-- 3. TABLA: tbl_clientes
-- (Estructura adaptada para Carga Masiva desde Excel Emisor: Facto)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbl_clientes` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre_cliente` VARCHAR(200) DEFAULT NULL,
    `razon_social` VARCHAR(255) DEFAULT NULL,
    `rut` VARCHAR(20) NOT NULL COMMENT 'Llave lógica y foránea',
    `direccion` VARCHAR(255) DEFAULT NULL,
    `comuna` VARCHAR(100) DEFAULT NULL,
    `ciudad` VARCHAR(100) DEFAULT NULL,
    `pais` VARCHAR(100) DEFAULT 'Chile',
    `giro` VARCHAR(255) DEFAULT NULL,
    `telefono_empresa` VARCHAR(50) DEFAULT NULL,
    `nombre_contacto` VARCHAR(100) DEFAULT NULL,
    `apellido_contacto` VARCHAR(100) DEFAULT NULL,
    `telefono_contacto` VARCHAR(50) DEFAULT NULL,
    `email` VARCHAR(150) DEFAULT NULL,
    `condicion_pago_dias` INT(11) DEFAULT '0',
    `linea_credito_aprobada` DECIMAL(15,2) DEFAULT '0.00',
    `fecha_venc_linea` DATE DEFAULT NULL,
    `lista_precios` VARCHAR(100) DEFAULT NULL,
    `usuario_encargado` VARCHAR(150) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_rut_cliente_maestro` (`rut`) -- Debe ser UNIQUE para ser usada como FK
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 4. TABLA: tbl_documentos_cobrar (Facturas, Boletas, etc)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbl_documentos_cobrar` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `tipo_documento` VARCHAR(120) NOT NULL,
    `fecha` DATE NOT NULL,
    `numero` VARCHAR(50) NOT NULL,
    `emisor_receptor` VARCHAR(255) DEFAULT NULL COMMENT 'Nombre del cliente extraído del Excel',
    `rut` VARCHAR(20) NOT NULL COMMENT 'Llave hacia clientes',
    `total` DECIMAL(12,2) NOT NULL DEFAULT '0.00',
    `pagado` DECIMAL(12,2) NOT NULL DEFAULT '0.00',
    `impago` DECIMAL(12,2) NOT NULL DEFAULT '0.00',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_rut_cliente_doc` (`rut`),
    -- Relación formal por RUT en lugar de ID
    CONSTRAINT `fk_documentos_rut_cliente` FOREIGN KEY (`rut`) 
        REFERENCES `tbl_clientes` (`rut`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- MÓDULO: CUENTAS POR PAGAR (PROVEEDORES)
-- =====================================================

-- -----------------------------------------------------
-- 5. TABLA: tbl_proveedores
-- (Estructura adaptada para Carga Masiva desde Excel Receptor: Facto)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbl_proveedores` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre_proveedor` VARCHAR(200) DEFAULT NULL,
    `razon_social` VARCHAR(255) DEFAULT NULL,
    `rut` VARCHAR(20) NOT NULL COMMENT 'Llave lógica y foránea',
    `direccion` VARCHAR(255) DEFAULT NULL,
    `comuna` VARCHAR(100) DEFAULT NULL,
    `ciudad` VARCHAR(100) DEFAULT NULL,
    `pais` VARCHAR(100) DEFAULT 'Chile',
    `giro` VARCHAR(255) DEFAULT NULL,
    `telefono_empresa` VARCHAR(50) DEFAULT NULL,
    `nombre_contacto` VARCHAR(100) DEFAULT NULL,
    `apellido_contacto` VARCHAR(100) DEFAULT NULL,
    `telefono_contacto` VARCHAR(50) DEFAULT NULL,
    `email` VARCHAR(150) DEFAULT NULL,
    `usuario_encargado` VARCHAR(150) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_rut_proveedor_maestro` (`rut`) -- Debe ser UNIQUE para ser usada como FK
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 6. TABLA: tbl_documentos_pagar (Facturas/Gastos Recibidos)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbl_documentos_pagar` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `tipo_documento` VARCHAR(120) NOT NULL,
    `fecha` DATE NOT NULL,
    `numero` VARCHAR(50) NOT NULL,
    `emisor_receptor` VARCHAR(255) DEFAULT NULL COMMENT 'Nombre del proveedor extraído del Excel',
    `rut` VARCHAR(20) NOT NULL COMMENT 'Llave hacia proveedores',
    `total` DECIMAL(12,2) NOT NULL DEFAULT '0.00',
    `pagado` DECIMAL(12,2) NOT NULL DEFAULT '0.00',
    `impago` DECIMAL(12,2) NOT NULL DEFAULT '0.00',
    `notas` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_rut_proveedor_doc` (`rut`),
    -- Relación formal por RUT en lugar de ID
    CONSTRAINT `fk_documentos_rut_proveedor` FOREIGN KEY (`rut`) 
        REFERENCES `tbl_proveedores` (`rut`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
