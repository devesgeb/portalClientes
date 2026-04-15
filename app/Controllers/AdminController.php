<?php

namespace App\Controllers;

use App\Models\LoginModel;

class AdminController extends BaseController
{
    protected $loginModel;

    public function __construct()
    {
        $this->loginModel = new LoginModel();
        helper(['url', 'form']);
    }

    /**
     * GET /admin
     * Muestra el panel de administración.
     * Inyecta los datos del usuario logueado vía variable JS en la vista.
     */
    public function index()
    {
        // Verificar sesión activa
        $session = session();
        $userId = $session->get('is_logued_in');

        if (!$userId) {
            return redirect()->to(site_url('/'));
        }

        // Leer datos del usuario desde la BD
        $usuario = $this->loginModel->obtenerPorId($userId);

        // Preparar datos para inyectar en la vista
        $data = [
            'title' => 'Panel de Administración',
            'base_url' => base_url(),
            'assets_url' => base_url('public/assets/'),
            'usuario' => $usuario,
            'activePage' => 'admin',
        ];

        return view('admin', $data);
    }
}
