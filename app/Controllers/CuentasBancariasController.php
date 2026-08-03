<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CajasModel;
use CodeIgniter\HTTP\ResponseInterface;

class CuentasBancariasController extends BaseController
{
    protected CajasModel $cajasModel;

    public function __construct()
    {
        $this->cajasModel = new CajasModel();
    }

    /**
     * GET /administracion/cuentas-bancarias
     * Vista principal del módulo Cuenta bancaria / Efectivo
     */
    public function index()
    {
        $this->cajasModel->autoCrearTabla();

        return view('administracion/cuentas_bancarias', [
            'title'      => 'Cuenta bancaria / Efectivo – Portal',
            'activePage' => 'cuentas-bancarias',
            'usuario'    => session()->get() ? [
                'nombre'    => session()->get('Nombre') ?? 'Administrador',
                'apellidos' => session()->get('Apellidos') ?? '',
                'email'     => session()->get('Email') ?? '',
                'rut'       => session()->get('Rut') ?? '',
                'telefono'  => session()->get('Telefono') ?? '',
                'perfil'    => session()->get('Perfil') ?? 'Administrador',
            ] : null,
        ]);
    }

    /**
     * GET /administracion/cuentas-bancarias/listar
     * Retorna el listado de cajas en JSON
     */
    public function listar(): ResponseInterface
    {
        try {
            $this->cajasModel->autoCrearTabla();
            $cajas = $this->cajasModel->orderBy('id', 'DESC')->findAll();

            return $this->response->setJSON([
                'success' => true,
                'data'    => $cajas,
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Error al obtener cajas: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * GET /administracion/cuentas-bancarias/obtener/{id}
     * Retorna los detalles de una caja específica
     */
    public function obtener($id = null): ResponseInterface
    {
        if (!$id) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'ID de caja no proporcionado.',
            ]);
        }

        $caja = $this->cajasModel->find($id);

        if (!$caja) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'La caja especificada no existe.',
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'data'    => $caja,
        ]);
    }

    /**
     * POST /administracion/cuentas-bancarias/guardar
     * Guarda o actualiza una caja (manual o física)
     */
    public function guardar(): ResponseInterface
    {
        $body = $this->request->getJSON(true) ?? $this->request->getPost();

        $id           = !empty($body['id']) ? (int)$body['id'] : null;
        $nombre       = trim($body['nombre'] ?? '');
        $tipo         = trim(strtolower($body['tipo'] ?? 'manual'));
        $saldoInicial = isset($body['saldo_inicial']) ? (float)$body['saldo_inicial'] : 0.00;
        $estado       = trim(strtolower($body['estado'] ?? 'activa'));
        $obs          = trim($body['observaciones'] ?? '');

        // Validaciones básicas
        if (empty($nombre)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'El nombre de la caja es obligatorio.',
            ]);
        }

        if (!in_array($tipo, ['manual', 'fisica', 'bancaria'], true)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'El tipo de caja debe ser "caja manual", "caja física" o "caja bancaria".',
            ]);
        }

        try {
            if ($id) {
                // Editar existente
                $cajaExistente = $this->cajasModel->find($id);
                if (!$cajaExistente) {
                    return $this->response->setStatusCode(404)->setJSON([
                        'success' => false,
                        'message' => 'Caja a editar no encontrada.',
                    ]);
                }

                $dataUpdate = [
                    'nombre'        => $nombre,
                    'tipo'          => $tipo,
                    'estado'        => in_array($estado, ['activa', 'inactiva'], true) ? $estado : 'activa',
                    'observaciones' => $obs,
                ];

                $this->cajasModel->update($id, $dataUpdate);

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Caja actualizada exitosamente.',
                    'id'      => $id,
                ]);
            } else {
                // Crear nueva caja
                $dataInsert = [
                    'nombre'        => $nombre,
                    'tipo'          => $tipo,
                    'saldo_inicial' => $saldoInicial,
                    'saldo_actual'  => $saldoInicial,
                    'estado'        => 'activa',
                    'observaciones' => $obs,
                ];

                $newId = $this->cajasModel->insert($dataInsert);

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Caja registrada exitosamente.',
                    'id'      => $newId,
                ]);
            }
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Error al procesar la caja: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * POST /administracion/cuentas-bancarias/eliminar/{id}
     * Elimina una caja por ID
     */
    public function eliminar($id = null): ResponseInterface
    {
        if (!$id) {
            $body = $this->request->getJSON(true);
            $id = $body['id'] ?? null;
        }

        if (!$id) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'ID de caja no especificado.',
            ]);
        }

        try {
            $caja = $this->cajasModel->find($id);
            if (!$caja) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'La caja no existe o ya fue eliminada.',
                ]);
            }

            $this->cajasModel->delete($id);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Caja "' . $caja['nombre'] . '" eliminada con éxito.',
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Error al eliminar la caja: ' . $e->getMessage(),
            ]);
        }
    }
}
