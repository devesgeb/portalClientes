<?php

namespace App\Models;

use CodeIgniter\Model;

class CajaMovimientosModel extends Model
{
    protected $table            = 'tbl_caja_movimientos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'caja_id',
        'tipo',
        'monto',
        'saldo_anterior',
        'saldo_nuevo',
        'fecha',
        'comentario',
        'usuario_nombre',
        'usuario_id',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'caja_id' => 'required|numeric',
        'tipo'    => 'required|in_list[ingreso,egreso,ajuste]',
        'monto'   => 'required|numeric',
        'fecha'   => 'required|valid_date[Y-m-d]',
    ];

    /**
     * Autocrea la tabla tbl_caja_movimientos por resiliencia.
     */
    public function autoCrearTabla(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `tbl_caja_movimientos` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `caja_id` INT NOT NULL,
          `tipo` ENUM('ingreso', 'egreso', 'ajuste') NOT NULL DEFAULT 'ingreso',
          `monto` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `saldo_anterior` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `saldo_nuevo` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `fecha` DATE NOT NULL,
          `comentario` TEXT NULL,
          `usuario_nombre` VARCHAR(150) NOT NULL DEFAULT 'Administrador',
          `usuario_id` INT NULL,
          `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          KEY `idx_caja_id` (`caja_id`),
          KEY `idx_fecha` (`fecha`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->db->query($sql);

        // Modificar columna por si la tabla ya existía previamente con ENUM('ingreso','egreso')
        try {
            $this->db->query("ALTER TABLE `tbl_caja_movimientos` MODIFY COLUMN `tipo` ENUM('ingreso', 'egreso', 'ajuste') NOT NULL DEFAULT 'ingreso';");
        } catch (\Throwable $e) {
            // Ignorar si no requiere modificación
        }
    }

    /**
     * Obtiene los movimientos con JOIN a tbl_cajas
     */
    public function obtenerMovimientos(?int $cajaId = null, ?string $fechaInicio = null, ?string $fechaFin = null): array
    {
        $builder = $this->db->table('tbl_caja_movimientos m')
            ->select('m.*, c.nombre as caja_nombre, c.tipo as caja_tipo')
            ->join('tbl_cajas c', 'c.id = m.caja_id', 'left')
            ->orderBy('m.id', 'DESC');

        if ($cajaId) {
            $builder->where('m.caja_id', $cajaId);
        }

        if ($fechaInicio) {
            $builder->where('m.fecha >=', $fechaInicio);
        }

        if ($fechaFin) {
            $builder->where('m.fecha <=', $fechaFin);
        }

        return $builder->get()->getResultArray();
    }
}
