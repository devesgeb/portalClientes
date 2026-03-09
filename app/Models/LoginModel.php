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


  public function verificarLogin($username, $password) {

   $db = \Config\Database::connect();

        $builder = $db->table('tbl_users');
        $builder->select('id, nombre, clave');
        $builder->where('clave', $password);
        $builder->where('nombre', $username);
        $query = $builder->get();

        if ($query->getNumRows() > 0) {
            return $query->getResultArray();
        } else
         return  $this->db->showLastQuery();

         



    }
    

}