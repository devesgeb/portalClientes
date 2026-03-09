-- =========================================================================
-- SCRIPT DE ESTRUCTURA COMPLETA V2 (USUARIOS + DOCUMENTOS UNIFICADOS)
-- Ideal para generar Diagrama Entidad-Relación (ER) en Navicat
-- DBMS: MySQL / MariaDB
-- =========================================================================

-- =====================================================
-- MÓDULO 1: ACCESOS Y USUARIOS
-- =====================================================

-- -----------------------------------------------------
-- 1.1 TABLA: tbl_perfiles (Roles del sistema)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `tbl_perfiles` (`id`, `nombre`) VALUES 
(1, 'Admin'), (2, 'Cliente'), (3, 'Proveedor');

-- -----------------------------------------------------
-- 1.2 TABLA: tbl_usuarios (Logins y accesos)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbl_usuarios` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `perfil_id` INT(11) UNSIGNED NOT NULL,
    `nombre` VARCHAR(150) NOT NULL,
    `apellidos` VARCHAR(150) DEFAULT NULL,
    `rut` VARCHAR(20) DEFAULT NULL COMMENT 'RUT para match lógico con entidades',
    `email` VARCHAR(150) NOT NULL,
    `clave` VARCHAR(255) NOT NULL,
    `telefono` VARCHAR(50) DEFAULT NULL,
    `estado` TINYINT(1) NOT NULL DEFAULT '1',
    `ultimo_acceso` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_email` (`email`),
    KEY `idx_perfil_id` (`perfil_id`),
    CONSTRAINT `fk_usuario_perfil` FOREIGN KEY (`perfil_id`) REFERENCES `tbl_perfiles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =====================================================
-- MÓDULO 2: ENTIDADES COMERCIALES (MAESTROS)
-- =====================================================

-- -----------------------------------------------------
-- 2.1 TABLA: tbl_clientes
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbl_clientes` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `rut` VARCHAR(20) NOT NULL,
    `nombre_razon_social` VARCHAR(200) NOT NULL,
    `direccion` VARCHAR(255) DEFAULT NULL,
    `giro` VARCHAR(150) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_rut_cliente` (`rut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 2.2 TABLA: tbl_proveedores
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbl_proveedores` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `rut` VARCHAR(20) NOT NULL,
    `nombre_razon_social` VARCHAR(200) NOT NULL,
    `direccion` VARCHAR(255) DEFAULT NULL,
    `giro` VARCHAR(150) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_rut_proveedor` (`rut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =====================================================
-- MÓDULO 3: DOCUMENTOS TRIBUTARIOS UNIFICADOS
-- =====================================================

-- -----------------------------------------------------
-- 3.1 TABLA: tbl_tipoDocumentos (Catálogo SII)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbl_tipoDocumentos` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `codigo_sii` INT(11) NOT NULL COMMENT 'Ej: 33, 39, 61',
    `nombre` VARCHAR(100) NOT NULL,
    `signo_contable` TINYINT(2) NOT NULL DEFAULT '1' COMMENT '1 Suma deuda, -1 Resta deuda',
    `estado` TINYINT(1) NOT NULL DEFAULT '1',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_codigo_sii` (`codigo_sii`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `tbl_tipoDocumentos` (`codigo_sii`, `nombre`, `signo_contable`) VALUES 
(33, 'Factura Electrónica', 1), 
(34, 'Factura Exenta', 1), 
(39, 'Boleta Electrónica', 1), 
(61, 'Nota de Crédito', -1), 
(56, 'Nota de Débito', 1);

-- -----------------------------------------------------
-- 3.2 TABLA: tbl_estados (Estado del documento)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbl_estados` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(50) NOT NULL,
    `color_hex` VARCHAR(10) DEFAULT '#000000',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `tbl_estados` (`id`, `nombre`, `color_hex`) VALUES 
(1, 'Pendiente', '#f59e0b'), 
(2, 'Pagado Parcial', '#3b82f6'), 
(3, 'Pagado Total', '#10b981'), 
(4, 'Anulado', '#ef4444');

-- -----------------------------------------------------
-- 3.3 TABLA CENTRAL: tbl_docTributarios
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbl_docTributarios` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `tipo_movimiento` ENUM('VENTA', 'COMPRA') NOT NULL COMMENT 'Venta=Cliente debe pagar, Compra=Nosotros pagamos',
    `rut_entidad` VARCHAR(20) NOT NULL COMMENT 'RUT Cliente o Proveedor (Llave Lógica)',
    
    `tipo_doc_id` INT(11) UNSIGNED NOT NULL COMMENT 'Catalogo SII',
    `estado_id` INT(11) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Catálogo Estados',
    
    `numero_documento` VARCHAR(50) NOT NULL,
    `fecha_emision` DATE NOT NULL,
    `fecha_vencimiento` DATE DEFAULT NULL,
    
    `monto_neto` DECIMAL(12,2) NOT NULL DEFAULT '0.00',
    `monto_iva` DECIMAL(12,2) NOT NULL DEFAULT '0.00',
    `monto_total` DECIMAL(12,2) NOT NULL DEFAULT '0.00',
    
    `monto_pagado` DECIMAL(12,2) NOT NULL DEFAULT '0.00',
    `saldo_impago` DECIMAL(12,2) GENERATED ALWAYS AS (`monto_total` - `monto_pagado`) VIRTUAL COMMENT 'Campo Autocalculado',
    
    `notas` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    KEY `idx_rut_movimiento` (`rut_entidad`, `tipo_movimiento`),
    KEY `idx_tipo_doc_id` (`tipo_doc_id`),
    KEY `idx_estado_id` (`estado_id`),
    
    CONSTRAINT `fk_doc_tipo` FOREIGN KEY (`tipo_doc_id`) REFERENCES `tbl_tipoDocumentos` (`id`),
    CONSTRAINT `fk_doc_estado` FOREIGN KEY (`estado_id`) REFERENCES `tbl_estados` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- -----------------------------------------------------
-- VISTAS LÓGICAS (Soft-Links para Visibilidad en Navicat)
-- Ayudan al Motor DB a entender la relación Polimórfica del RUT
-- -----------------------------------------------------

-- Vista de Ventas (Conecta Documentos de Venta con tbl_clientes)
CREATE OR REPLACE VIEW `vw_documentos_ventas` AS
SELECT 
    d.*, 
    c.id AS cliente_id, 
    c.nombre_razon_social AS cliente_nombre 
FROM `tbl_docTributarios` d
INNER JOIN `tbl_clientes` c ON d.rut_entidad = c.rut
WHERE d.tipo_movimiento = 'VENTA';

-- Vista de Compras (Conecta Documentos de Compra con tbl_proveedores)
CREATE OR REPLACE VIEW `vw_documentos_compras` AS
SELECT 
    d.*, 
    p.id AS proveedor_id, 
    p.nombre_razon_social AS proveedor_nombre 
FROM `tbl_docTributarios` d
INNER JOIN `tbl_proveedores` p ON d.rut_entidad = p.rut
WHERE d.tipo_movimiento = 'COMPRA';
