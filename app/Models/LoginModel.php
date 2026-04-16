<?php

namespace App\Models;

use CodeIgniter\Model;

class LoginModel extends Model
{
    protected $table      = 'tbl_usuarios';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'perfil_id', 'nombre', 'apellidos', 'rut',
        'email', 'clave', 'telefono', 'estado'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Verificar credenciales de login.
     * Busca por nombre de usuario (campo 'nombre') y verifica la clave.
     * Soporta claves hasheadas (password_hash) y texto plano (legacy).
     */
    public function verificarLogin(string $username, string $password)
    {
        $db      = \Config\Database::connect();
        $builder = $db->table('tbl_usuarios');
        $builder->select('tbl_usuarios.id, tbl_usuarios.nombre, tbl_usuarios.apellidos,
                          tbl_usuarios.email, tbl_usuarios.rut, tbl_usuarios.telefono,
                          tbl_usuarios.clave, tbl_usuarios.estado, tbl_usuarios.perfil_id,
                          tbl_perfiles.nombre AS perfil_nombre')
                ->join('tbl_perfiles', 'tbl_perfiles.id = tbl_usuarios.perfil_id', 'left')
                ->groupStart()
                    ->where('tbl_usuarios.nombre', $username)
                    ->orWhere('tbl_usuarios.email', $username)
                ->groupEnd()
                ->where('tbl_usuarios.estado', 1);

        $query = $builder->get();

        if ($query->getNumRows() === 0) {
            return false;
        }

        $user = $query->getRowArray();

        // Verificar clave: primero texto plano (legacy), luego hash
        $claveAlmacenada = $user['clave'];
        $claveValida = ($claveAlmacenada === $password)
                    || password_verify($password, $claveAlmacenada);

        if (!$claveValida) {
            return false;
        }

        // Actualizar último acceso
        $db->table('tbl_usuarios')
           ->where('id', $user['id'])
           ->update(['ultimo_acceso' => date('Y-m-d H:i:s')]);

        return [$user];
    }

    /**
     * Obtener usuario completo por ID (para panel admin / topbar).
     */
    public function obtenerPorId(int $id): array
    {
        $db      = \Config\Database::connect();
        $builder = $db->table('tbl_usuarios');
        $builder->select('tbl_usuarios.*, tbl_perfiles.nombre AS perfil_nombre')
                ->join('tbl_perfiles', 'tbl_perfiles.id = tbl_usuarios.perfil_id', 'left')
                ->where('tbl_usuarios.id', $id);

        $query = $builder->get();

        if ($query->getNumRows() > 0) {
            $row = $query->getRowArray();
            return [
                'id'            => $row['id'],
                'nombre'        => $row['nombre'],
                'apellidos'     => $row['apellidos'] ?? '',
                'email'         => $row['email']     ?? '',
                'rut'           => $row['rut']        ?? '',
                'telefono'      => $row['telefono']   ?? '',
                'estado'        => $row['estado'],
                'ultimo_acceso' => $row['ultimo_acceso'] ?? null,
                'perfil'        => $row['perfil_nombre'] ?? 'Administrador',
            ];
        }

        return [
            'id' => $id, 'nombre' => 'Usuario', 'apellidos' => '',
            'email' => '', 'rut' => '', 'telefono' => '',
            'estado' => 1, 'ultimo_acceso' => null, 'perfil' => 'Administrador',
        ];
    }
}