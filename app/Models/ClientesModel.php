<?php
namespace App\Models;
use CodeIgniter\Model;

class ClientesModel extends Model
{
    protected $table      = 'tbl_clientes';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'nombre','razon_social','rut','direccion','comuna','ciudad','giro',
        'contacto_nombre','contacto_apellido','email',
        'condiciones_pago_dias','linea_credito','lista_precios','fecha_vencimiento_linea',
        'telefono_empresa','telefono_contacto','pais','usuario_encargado',
    ];

    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';

    /**
     * Inserta o actualiza por RUT (upsert).
     * Retorna ['insertados'=>n, 'actualizados'=>n, 'errores'=>[]]
     */
    public function importar(array $rows): array
    {
        $insertados  = 0;
        $actualizados = 0;
        $errores     = [];

        foreach ($rows as $i => $row) {
            $rut = trim($row['rut'] ?? '');
            if (empty($rut)) { $errores[] = "Fila $i: RUT vacío"; continue; }

            $existing = $this->where('rut', $rut)->first();
            if ($existing) {
                $this->update($existing['id'], $row);
                $actualizados++;
            } else {
                $this->insert($row);
                $insertados++;
            }
        }

        return compact('insertados', 'actualizados', 'errores');
    }
}
