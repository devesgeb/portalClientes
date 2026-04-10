<?php
namespace App\Controllers;

use App\Models\ClientesModel;
use App\Models\ProveedoresModel;
use App\Models\LoginModel;

class BuscarEntidadController extends BaseController
{
    /** GET /buscar-entidad — Vista principal */
    public function index()
    {
        $session = session();
        $userId = (int)$session->get('is_logued_in');

        // Redirigir si no hay sesión activa
        if (!$userId) {
            return redirect()->to(site_url('/'));
        }

        $loginModel = new LoginModel();
        $usuario = $loginModel->obtenerPorId($userId);

        return view('buscar_entidad', [
            'title' => 'Buscar Cliente / Proveedor',
            'activePage' => 'buscar-entidad',
            'usuario' => $usuario ?? [],
        ]);
    }

    /** GET /buscar-entidad/buscar — API JSON de búsqueda */
    public function buscar()
    {
        $nombre = trim($this->request->getGet('nombre') ?? '');
        $rut = trim($this->request->getGet('rut') ?? '');
        $razonSocial = trim($this->request->getGet('razon_social') ?? '');
        $giro = trim($this->request->getGet('giro') ?? '');
        $tipo = $this->request->getGet('tipo') ?? 'ambos';

        $result = [];

        if (in_array($tipo, ['cliente', 'ambos'])) {
            $m = new ClientesModel();
            $q = $m->select('id, nombre, razon_social, rut, email, telefono_empresa, giro, ciudad, contacto_nombre');
            if ($nombre)
                $q = $q->groupStart()->like('nombre', $nombre)->orLike('razon_social', $nombre)->groupEnd();
            if ($rut)
                $q = $q->like('rut', $rut);
            if ($razonSocial)
                $q = $q->like('razon_social', $razonSocial);
            if ($giro)
                $q = $q->like('giro', $giro);
            $result['clientes'] = $q->orderBy('nombre', 'ASC')->findAll(100);
        }

        if (in_array($tipo, ['proveedor', 'ambos'])) {
            $m = new ProveedoresModel();
            $q = $m->select('id, nombre, razon_social, rut, email, telefono_empresa, giro, ciudad, contacto_nombre');
            if ($nombre)
                $q = $q->groupStart()->like('nombre', $nombre)->orLike('razon_social', $nombre)->groupEnd();
            if ($rut)
                $q = $q->like('rut', $rut);
            if ($razonSocial)
                $q = $q->like('razon_social', $razonSocial);
            if ($giro)
                $q = $q->like('giro', $giro);
            $result['proveedores'] = $q->orderBy('nombre', 'ASC')->findAll(100);
        }

        return $this->response->setJSON($result);
    }

    /** GET /buscar-entidad/detalle?tipo=cliente&id=X */
    public function detalle()
    {
        $id = (int)($this->request->getGet('id') ?? 0);
        $tipo = $this->request->getGet('tipo') ?? 'cliente';

        $model = $tipo === 'proveedor' ? new ProveedoresModel() : new ClientesModel();
        $record = $model->find($id);

        if (!$record) {
            return $this->response->setJSON(['error' => 'No encontrado'])->setStatusCode(404);
        }

        return $this->response->setJSON($record);
    }

    /** PUT /buscar-entidad/actualizar */
    public function actualizar()
    {
        $data = $this->request->getJSON(true);
        $id = (int)($data['id'] ?? 0);
        $tipo = $data['tipo'] ?? 'cliente';

        if (!$id) {
            return $this->response->setJSON(['error' => 'ID requerido'])->setStatusCode(400);
        }

        unset($data['id'], $data['tipo']);

        $model = $tipo === 'proveedor' ? new ProveedoresModel() : new ClientesModel();

        if (!$model->find($id)) {
            return $this->response->setJSON(['error' => 'No encontrado'])->setStatusCode(404);
        }

        $model->update($id, $data);
        return $this->response->setJSON(['ok' => true]);
    }

    /** DELETE /buscar-entidad/eliminar?tipo=cliente&id=X */
    public function eliminar()
    {
        $id = (int)($this->request->getGet('id') ?? 0);
        $tipo = $this->request->getGet('tipo') ?? 'cliente';

        if (!$id) {
            return $this->response->setJSON(['error' => 'ID requerido'])->setStatusCode(400);
        }

        $model = $tipo === 'proveedor' ? new ProveedoresModel() : new ClientesModel();

        if (!$model->find($id)) {
            return $this->response->setJSON(['error' => 'No encontrado'])->setStatusCode(404);
        }

        $model->delete($id);
        return $this->response->setJSON(['ok' => true]);
    }
}
