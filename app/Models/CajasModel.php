<?php

namespace App\Models;

use CodeIgniter\Model;

class CajasModel extends Model
{
    protected $table            = 'tbl_cajas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'nombre',
        'tipo',
        'saldo_inicial',
        'saldo_actual',
        'estado',
        'observaciones',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'nombre' => 'required|min_length[2]|max_length[150]',
        'tipo'   => 'required|in_list[manual,fisica,bancaria]',
    ];

    /**
     * Asegura la existencia de la tabla tbl_cajas por resiliencia.
     */
    public function autoCrearTabla(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `tbl_cajas` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `nombre` VARCHAR(150) NOT NULL,
          `tipo` ENUM('manual', 'fisica', 'bancaria') NOT NULL DEFAULT 'manual',
          `saldo_inicial` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `saldo_actual` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `estado` ENUM('activa', 'inactiva') NOT NULL DEFAULT 'activa',
          `observaciones` TEXT NULL,
          `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->db->query($sql);

        // Modificar columna por si la tabla ya existía previamente con ENUM('manual','fisica')
        try {
            $this->db->query("ALTER TABLE `tbl_cajas` MODIFY COLUMN `tipo` ENUM('manual', 'fisica', 'bancaria') NOT NULL DEFAULT 'manual';");
        } catch (\Throwable $e) {
            // Ignorar si no requiere modificación o si falla silenciosamente
        }
    }
}
