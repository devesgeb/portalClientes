-- ══════════════════════════════════════════════
--  Script: Tabla tbl_inventario
--  Base de datos: admin
--  Ejecutar en Navicat / phpMyAdmin / MySQL CLI
-- ══════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `tbl_inventario` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `sku`         VARCHAR(200) NOT NULL,
  `descripcion` VARCHAR(500) DEFAULT NULL,
  `precio`      DECIMAL(14,2) NOT NULL DEFAULT 0,
  `stock`       INT NOT NULL DEFAULT 0,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales (migrados desde el JS hardcodeado)
INSERT INTO `tbl_inventario` (`sku`, `precio`, `stock`) VALUES
  ('Cemento 25 Kg',    8500,  200),
  ('Arena m3',         45000,  30),
  ('Fierro 10mm x 6m', 12800, 150),
  ('Pintura latex 4L', 18900,  80);
