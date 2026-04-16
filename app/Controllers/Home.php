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
        $username = $this->request->getPost('Usuario') ?? '';
        $password = $this->request->getPost('Clave')   ?? '';

        if (!$username || !$password) {
            session()->setFlashdata('login_error', 'Por favor ingresa usuario y contraseña.');
            return redirect()->to(site_url('/'));
        }

        $check_user = $this->loginModel->verificarLogin($username, $password);

        if ($check_user !== false) {
            $session = session();
            $session->set([
                'is_logued_in' => $check_user[0]['id'],
                'Nombre'       => $check_user[0]['nombre'],
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

    public function logout_ci()
    {
        session()->destroy();
        return redirect()->to(site_url('/'));
    }





}


?>