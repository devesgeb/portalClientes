<?php

namespace App\Controllers;

use App\Models\LoginModel;
use CodeIgniter\HTTP\ResponseInterface;

class ContabilidadController extends BaseController
{
    public function __construct()
    {
        helper(['url', 'form']);
    }

    public function historialBalances(): string
    {
        $session = session();
        $userId = $session->get('is_logued_in');
        $loginModel = new LoginModel();
        $usuario = $userId ? $loginModel->obtenerPorId($userId) : null;

        $data = [
            'title'      => 'Historial de Balances',
            'base_url'   => base_url(),
            'assets_url' => base_url('public/assets/'),
            'activePage' => 'historial-balances',
            'usuario'    => $usuario,
        ];

        return view('contabilidad/historial_balances', $data);
    }
}
