<?php

namespace App\Controllers;
use App\Models\LoginModel;

class Home extends BaseController
{



    public function __construct()
    {
        $this->loginModel = new loginModel();
        helper(['url', 'form', 'html', 'assets']); // ← assets_helper.php


    }


    public function index()
    {

        $assets_path = $this->assets_url();
        $data = [
            'title' => ['Página Principal'],
            'css' => [$assets_path['css_path'][0] . 'styles.css'],
            'js' => [$assets_path['js_path'][0] . 'bootstrap.bundle.min.js'],
            'img' => [$assets_path['img_path'][0]]
        ];
        return view('login', $data);
    }


    public function validaUser()
    {
        $username = trim($this->request->getPost('Usuario') ?? '');
        $password = trim($this->request->getPost('Clave')   ?? '');

        if (!$username || !$password) {
            session()->setFlashdata('login_error', 'Por favor ingresa usuario y contraseña.');
            return redirect()->to(site_url('/'));
        }

        $check_user = $this->loginModel->verificarLogin($username, $password);

        if ($check_user !== false) {
            $u = $check_user[0];
            $session = session();
            $session->set([
                'is_logued_in' => $u['id'],
                'user_id'      => $u['id'],
                'Nombre'       => $u['nombre'],
                'Apellidos'    => $u['apellidos']     ?? '',
                'Email'        => $u['email']         ?? '',
                'Rut'          => $u['rut']           ?? '',
                'Telefono'     => $u['telefono']      ?? '',
                'Perfil'       => $u['perfil_nombre'] ?? 'Administrador',
                'perfil_id'    => $u['perfil_id']     ?? 1,
            ]);

            return redirect()->to(site_url('admin'));
        }

        // Credenciales inválidas → volver al login con mensaje
        session()->setFlashdata('login_error', 'Usuario o contraseña incorrectos. Intenta nuevamente.');
        return redirect()->to(site_url('/'));
    }

    public function carga($session, $tipouser)
    {
        $assets_path = $this->assets_url();
        $data = [
            'title' => ['Página Principal'],
            'css' => ['../' . $assets_path['css_path'][0] . 'styles.css'],
            'js' => ['../' . $assets_path['js_path'][0] . 'chart-area-demo.js', '../' . $assets_path['js_path'][0] . 'chart-bar-demo.js', '../' . $assets_path['js_path'][0] . 'scripts.js', '../' . $assets_path['js_path'][0] . 'datatables-simple-demo.js'],
            'img' => ['../' . $assets_path['img_path'][0]],
            'session' => $session
        ];
        return $data;
    }
    public function token()
    {
        $token = md5(uniqid(rand(), true));
        $this->session->set_userdata('token', $token);
        return $token;
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to(site_url('/'));
    }

    public function logout_ci()
    {
        return $this->logout();
    }





}


?>