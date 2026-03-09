-- =========================================================================
-- ESTRUCTURA DE USUARIOS Y PERFILES (ROLES)
-- DBMS: MySQL / MariaDB
-- =========================================================================

-- EVALUACIÓN DE TABLA INTERMEDIA:
-- Depende de la regla de negocio: ¿Puede un mismo usuario ser "Cliente" 
-- y "Proveedor" al mismo tiempo con la misma cuenta de acceso?
-- 
-- 1. Si la respuesta es NO (un usuario tiene solo 1 rol específico): 
--    No se necesita tabla intermedia. La relación es 1 a N (1 Perfil -> N Usuarios).
--    Esta es la estructura que se implementa a continuación, ya que es el 
--    estándar más común y óptimo para este tipo de portales.
--
-- 2. Si la respuesta es SÍ:
--    Se quitaría `perfil_id` de `tbl_usuarios` y se crearía una tabla 
--    `tbl_usuario_perfiles` (usuario_id, perfil_id). 
-- -------------------------------------------------------------------------

-- 1. TABLA DE ROLES O PERFILES
-- Almacena los tipos de usuario (Admin, Clientes, Proveedores)
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

-- Insertar los roles por defecto solicitados
INSERT IGNORE INTO `tbl_perfiles` (`id`, `nombre`, `descripcion`) VALUES
(1, 'Admin', 'Administrador general del sistema con acceso total'),
(2, 'Cliente', 'Usuario con rol de cliente para cuentas por cobrar'),
(3, 'Proveedor', 'Usuario con rol de proveedor para cuentas por pagar');


-- 2. TABLA DE USUARIOS
-- Almacena los credenciales e información base de quienes acceden
CREATE TABLE IF NOT EXISTS `tbl_usuarios` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `perfil_id` INT(11) UNSIGNED NOT NULL,
    `nombres` VARCHAR(150) NOT NULL,
    `apellidos` VARCHAR(150) DEFAULT NULL,
    `rut` VARCHAR(20) DEFAULT NULL COMMENT 'Útil para vincular con tbl_clientes si aplica',
    `email` VARCHAR(150) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL COMMENT 'Contraseña encriptada (p. ej. bcrypt)',
    `telefono` VARCHAR(50) DEFAULT NULL,
    `estado` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '1: Activo, 0: Suspendido',
    `ultimo_acceso` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_email` (`email`),
    UNIQUE KEY `uk_rut` (`rut`),
    KEY `idx_perfil_id` (`perfil_id`),
    CONSTRAINT `fk_usuario_perfil` FOREIGN KEY (`perfil_id`) 
        REFERENCES `tbl_perfiles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- NOTAS DE IMPLEMENTACIÓN Y NORMALIZACIÓN:
-- 1. Se incluye FK fk_usuario_perfil con ON DELETE RESTRICT: 
--    Evita que accidentalmente se borre un perfil (ej. Admin) si hay usuarios asignados.
-- 2. Las contraseñas deben guardarse aplicando funciones como password_hash() en PHP.
-- 3. Si un "Cliente" que inicia sesión es una empresa (como en tbl_clientes), 
--    el campo `rut` servirá como clave natural para cruzar ambas tablas y 
--    saber qué documentos puede ver en su portal.
