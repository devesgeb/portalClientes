<?php

namespace App\Controllers;

use App\Models\UsuariosModel;

class UsuariosController extends BaseController
{
    protected UsuariosModel $model;

    public function __construct()
    {
        $this->model = new UsuariosModel();
        helper(['url']);
    }

    /** Verifica sesión activa */
    private function checkSesion(): bool
    {
        return (bool) session()->get('is_logued_in');
    }

    // ─── VISTA PRINCIPAL ──────────────────────────────────────────────────────

    public function index()
    {
        if (!$this->checkSesion()) {
            return redirect()->to(site_url('/'));
        }

        $perfiles = [
            1 => 'Admin',
            2 => 'Cliente',
            3 => 'Proveedor',
        ];

        return view('usuarios', [
            'activePage' => 'usuarios',
            'perfiles'   => $perfiles,
            'usuario'    => session()->get(),
        ]);
    }

    // ─── API JSON ─────────────────────────────────────────────────────────────

    /** GET /usuarios/lista?perfil_id=0 */
    public function lista()
    {
        if (!$this->checkSesion()) {
            return $this->jsonError('No autorizado', 401);
        }

        $perfil_id = (int) $this->request->getGet('perfil_id');
        $usuarios  = $this->model->getUsuarios($perfil_id);

        // Ocultar clave
        foreach ($usuarios as &$u) {
            unset($u['clave']);
        }

        return $this->response->setJSON(['ok' => true, 'data' => $usuarios]);
    }

    /** POST /usuarios/guardar — crear o actualizar */
    public function guardar()
    {
        if (!$this->checkSesion()) {
            return $this->jsonError('No autorizado', 401);
        }

        $body = $this->request->getJSON(true) ?? $this->request->getPost();
        $id   = (int) ($body['id'] ?? 0);

        // Validaciones básicas
        $nombre = trim($body['nombre'] ?? '');
        $email  = trim($body['email'] ?? '');
        $perfil = (int) ($body['perfil_id'] ?? 0);

        if (!$nombre || !$email || !$perfil) {
            return $this->jsonError('Nombre, email y perfil son requeridos.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonError('El email no es válido.');
        }

        if ($this->model->emailExiste($email, $id)) {
            return $this->jsonError('El email ya está registrado.');
        }

        $data = [
            'perfil_id' => $perfil,
            'nombre'    => $nombre,
            'apellidos' => trim($body['apellidos'] ?? ''),
            'rut'       => trim($body['rut'] ?? ''),
            'email'     => $email,
            'telefono'  => trim($body['telefono'] ?? ''),
            'estado'    => (int) ($body['estado'] ?? 1),
        ];

        // Clave: solo al crear o si se envía
        $clave = trim($body['clave'] ?? '');
        if ($id === 0 && !$clave) {
            return $this->jsonError('La clave es requerida al crear un usuario.');
        }
        if ($clave) {
            $data['clave'] = password_hash($clave, PASSWORD_DEFAULT);
        }

        if ($id > 0) {
            $this->model->update($id, $data);
            $msg = 'Usuario actualizado correctamente.';
        } else {
            $id  = $this->model->insert($data);
            $msg = 'Usuario creado correctamente.';
        }

        $usuario = $this->model->getUsuario((int) $id);
        unset($usuario['clave']);

        return $this->response->setJSON(['ok' => true, 'msg' => $msg, 'usuario' => $usuario]);
    }

    /** DELETE /usuarios/eliminar/:id */
    public function eliminar(int $id)
    {
        if (!$this->checkSesion()) {
            return $this->jsonError('No autorizado', 401);
        }

        $usuario = $this->model->find($id);
        if (!$usuario) {
            return $this->jsonError('Usuario no encontrado.');
        }

        $this->model->delete($id);
        return $this->response->setJSON(['ok' => true, 'msg' => 'Usuario eliminado.']);
    }

    /** PATCH /usuarios/toggle-estado/:id */
    public function toggleEstado(int $id)
    {
        if (!$this->checkSesion()) {
            return $this->jsonError('No autorizado', 401);
        }

        $usuario = $this->model->find($id);
        if (!$usuario) {
            return $this->jsonError('Usuario no encontrado.');
        }

        $nuevoEstado = $usuario['estado'] ? 0 : 1;
        $this->model->update($id, ['estado' => $nuevoEstado]);

        return $this->response->setJSON([
            'ok'     => true,
            'msg'    => $nuevoEstado ? 'Usuario activado.' : 'Usuario desactivado.',
            'estado' => $nuevoEstado,
        ]);
    }

    // ─── HELPERS ──────────────────────────────────────────────────────────────

    private function jsonError(string $msg, int $code = 422)
    {
        return $this->response->setStatusCode($code)->setJSON(['ok' => false, 'msg' => $msg]);
    }
}
