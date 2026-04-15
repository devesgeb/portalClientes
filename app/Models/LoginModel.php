<?php

namespace App\Models; // Namespace correcto

use CodeIgniter\Model;

class LoginModel extends Model
{
    protected $table = 'tbl_users'; // Nombre de la tabla
    protected $primaryKey = 'id'; // Clave primaria

    protected $returnType = 'array'; // array|object
    protected $useSoftDeletes = false;

    // Campos que se pueden insertar/actualizar
    protected $allowedFields = [
        'nombre',
        'rut',
        'clave'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validaciones
    protected $validationRules = [
        'username' => 'required|min_length[3]|is_unique[usuarios.username]',
        'email' => 'required|valid_email|is_unique[usuarios.email]',
        'password' => 'required|min_length[6]'
    ];

    protected $validationMessages = [];
    protected $skipValidation = false;

    /**
     * Verificar credenciales de login
     */


    public function verificarLogin($username, $password)
    {

        $db = \Config\Database::connect();

        $builder = $db->table('tbl_users');
        $builder->select('id, nombre, clave');
        $builder->where('clave', $password);
        $builder->where('nombre', $username);
        $query = $builder->get();

        if ($query->getNumRows() > 0) {
            return $query->getResultArray();
        }

        return false;





    }

    /**
     * Obtener usuario completo por ID (para panel admin)
     */
    public function obtenerPorId(int $id): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('tbl_users');
        $builder->select('id, nombre, clave, id_perfil');
        $builder->where('id', $id);
        $query = $builder->get();

        if ($query->getNumRows() > 0) {
            $row = $query->getRowArray();
            // Normalizar al formato esperado por la vista
            return [
                'id' => $row['id'],
                'nombre' => $row['nombre'],
                'apellidos' => '',
                'email' => '',
                'rut' => '',
                'telefono' => '',
                'estado' => 1,
                'ultimo_acceso' => null,
                'perfil' => $row['id_perfil'] == 1 ? 'Administrador' : 'Usuario',
            ];
        }

        return [
            'id' => $id, 'nombre' => 'Usuario', 'apellidos' => '',
            'email' => '', 'rut' => '', 'telefono' => '',
            'estado' => 1, 'ultimo_acceso' => null, 'perfil' => 'Administrador',
        ];
    }

}