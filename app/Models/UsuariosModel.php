<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuariosModel extends Model
{
    protected $table = 'tbl_usuarios';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'perfil_id',
        'nombre',
        'apellidos',
        'rut',
        'email',
        'clave',
        'telefono',
        'estado'
    ];
    protected $useTimestamps = true;

    /**
     * Retorna todos los usuarios con el nombre del perfil.
     * Si $perfil_id > 0, filtra por ese perfil.
     */
    public function getUsuarios(int $perfil_id = 0): array
    {
        $this->select('tbl_usuarios.*, tbl_perfiles.nombre AS perfil_nombre')
            ->join('tbl_perfiles', 'tbl_perfiles.id = tbl_usuarios.perfil_id', 'left');

        if ($perfil_id > 0) {
            $this->where('tbl_usuarios.perfil_id', $perfil_id);
        }

        return $this->orderBy('tbl_usuarios.nombre', 'ASC')->findAll();
    }

    /**
     * Retorna un usuario con nombre de perfil.
     */
    public function getUsuario(int $id): ?array
    {
        return $this->select('tbl_usuarios.*, tbl_perfiles.nombre AS perfil_nombre')
            ->join('tbl_perfiles', 'tbl_perfiles.id = tbl_usuarios.perfil_id', 'left')
            ->where('tbl_usuarios.id', $id)
            ->first();
    }

    /**
     * Verifica si el email ya existe (excluyendo un id dado).
     */
    public function emailExiste(string $email, int $excludeId = 0): bool
    {
        $q = $this->where('email', $email);
        if ($excludeId > 0) {
            $q->where('id !=', $excludeId);
        }
        return $q->countAllResults() > 0;
    }
}
