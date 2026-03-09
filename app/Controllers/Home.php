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

        // $this->form_validation->set_rules('Usuario', 'nombre de usuario', 'required|trim|min_length[2]|max_length[30]|xss_clean');
        //  $this->form_validation->set_rules('Clave', 'Clave', 'required|trim|min_length[5]|max_length[30]|xss_clean');
        $username = $_REQUEST['Usuario'];
        $password = $_REQUEST['Clave'];

        $check_user = $this->loginModel->verificarLogin($username, $password);


        if ($check_user == TRUE) {

            $data = array(
                'is_logued_in' => $check_user[0]['id'],
                'Nombre' => $check_user[0]['nombre'],
            );
            $session = session();
            $session->set($data);
            $carga = $this->carga($data, $data['is_logued_in']);

            return view('balance_diario', $carga);
        }
        else {

            $assets_path = $this->assets_url();
            $data = [
                'title' => ['Página Principal'],
                'css' => ['../' . $assets_path['css_path'][0] . 'styles.css'],
                'js' => ['../' . $assets_path['js_path'][0] . 'bootstrap.bundle.min.js'],
                'img' => ['../' . $assets_path['img_path'][0]]
            ];


            return view('login', $data);

        }



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
        $this->session->sess_destroy();
        header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
        header("Location: http://localhost/portal/"); /* Redirección del navegador */
    }





}


?>