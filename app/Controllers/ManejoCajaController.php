<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CajasModel;
use App\Models\CajaMovimientosModel;
use CodeIgniter\HTTP\ResponseInterface;

class ManejoCajaController extends BaseController
{
    protected CajasModel $cajasModel;
    protected CajaMovimientosModel $movimientosModel;

    public function __construct()
    {
        $this->cajasModel       = new CajasModel();
        $this->movimientosModel = new CajaMovimientosModel();
    }

    /**
     * GET /cobranza/manejo-caja
     * Renderiza la interfaz de gestión de cajas y movimientos.
     */
    public function index()
    {
        $this->cajasModel->autoCrearTabla();
        $this->movimientosModel->autoCrearTabla();

        return view('cobranza/manejo_caja', [
            'title'      => 'Manejo de caja – Portal',
            'activePage' => 'manejo-caja',
            'usuario'    => session()->get() ? [
                'nombre'    => session()->get('Nombre') ?? 'Administrador',
                'apellidos' => session()->get('Apellidos') ?? '',
                'email'     => session()->get('Email') ?? '',
                'rut'       => session()->get('Rut') ?? '',
                'perfil'    => session()->get('Perfil') ?? 'Administrador',
            ] : null,
        ]);
    }

    /**
     * GET /cobranza/manejo-caja/listar
     * Retorna movimientos y resumen de cajas en JSON.
     */
    public function listar(): ResponseInterface
    {
        try {
            $this->cajasModel->autoCrearTabla();
            $this->movimientosModel->autoCrearTabla();

            $cajaId      = $this->request->getGet('caja_id') ? (int)$this->request->getGet('caja_id') : null;
            $fechaInicio = $this->request->getGet('fecha_inicio') ? trim($this->request->getGet('fecha_inicio')) : null;
            $fechaFin    = $this->request->getGet('fecha_fin') ? trim($this->request->getGet('fecha_fin')) : null;

            $cajas = $this->cajasModel->orderBy('nombre', 'ASC')->findAll();
            $movimientos = $this->movimientosModel->obtenerMovimientos($cajaId, $fechaInicio, $fechaFin);

            // Calcular totales del día actual
            $today = date('Y-m-d');
            $movsToday = $this->movimientosModel->where('fecha', $today)->findAll();

            $ingresosHoy = 0.00;
            $egresosHoy  = 0.00;

            foreach ($movsToday as $m) {
                if ($m['tipo'] === 'ingreso') {
                    $ingresosHoy += (float)$m['monto'];
                } else if ($m['tipo'] === 'egreso') {
                    $egresosHoy += (float)$m['monto'];
                }
            }

            $totalSaldoActual = 0.00;
            foreach ($cajas as $c) {
                if ($c['estado'] === 'activa') {
                    $totalSaldoActual += (float)$c['saldo_actual'];
                }
            }

            return $this->response->setJSON([
                'success'     => true,
                'cajas'       => $cajas,
                'movimientos' => $movimientos,
                'resumen'     => [
                    'total_saldo'  => $totalSaldoActual,
                    'ingresos_hoy' => $ingresosHoy,
                    'egresos_hoy'  => $egresosHoy,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Error al cargar los datos de caja: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * POST /cobranza/manejo-caja/guardar
     * Registra un movimiento e incrementa/decrementa el saldo de la caja.
     */
    public function guardar(): ResponseInterface
    {
        $body = $this->request->getJSON(true) ?? $this->request->getPost();

        $cajaId     = !empty($body['caja_id']) ? (int)$body['caja_id'] : null;
        $tipo       = trim(strtolower($body['tipo'] ?? 'ingreso'));
        $monto      = isset($body['monto']) ? (float)$body['monto'] : 0.00;
        $fecha      = !empty($body['fecha']) ? trim($body['fecha']) : date('Y-m-d');
        $comentario = trim($body['comentario'] ?? '');

        if (!$cajaId) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Debe seleccionar una caja válida.',
            ]);
        }

        if (!in_array($tipo, ['ingreso', 'egreso'], true)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'El tipo de movimiento debe ser "ingreso" o "egreso".',
            ]);
        }

        if ($monto <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'El monto del movimiento debe ser mayor a 0.',
            ]);
        }

        // Obtener usuario ejecutor desde sesión
        $nombreUser = session()->get('Nombre') ?? 'Administrador';
        if (session()->get('Apellidos')) {
            $nombreUser .= ' ' . session()->get('Apellidos');
        }
        $userId = session()->get('is_logued_in') ? (int)session()->get('is_logued_in') : null;

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $caja = $this->cajasModel->find($cajaId);
            if (!$caja) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'La caja seleccionada no existe.',
                ]);
            }

            $saldoAnterior = (float)$caja['saldo_actual'];
            $saldoNuevo    = $tipo === 'ingreso' ? ($saldoAnterior + $monto) : ($saldoAnterior - $monto);

            // 1. Actualizar saldo_actual en tbl_cajas
            $this->cajasModel->update($cajaId, [
                'saldo_actual' => $saldoNuevo,
            ]);

            // 2. Registrar movimiento en tbl_caja_movimientos
            $this->movimientosModel->insert([
                'caja_id'        => $cajaId,
                'tipo'           => $tipo,
                'monto'          => $monto,
                'saldo_anterior' => $saldoAnterior,
                'saldo_nuevo'    => $saldoNuevo,
                'fecha'          => $fecha,
                'comentario'     => $comentario,
                'usuario_nombre' => $nombreUser,
                'usuario_id'     => $userId,
            ]);

            $db->transComplete();

            if (!$db->transStatus()) {
                throw new \RuntimeException('La transacción SQL ha fallado.');
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Movimiento registrado con éxito.',
            ]);
        } catch (\Exception $e) {
            $db->transRollback();
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Error al guardar movimiento: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * POST /cobranza/manejo-caja/ajustar-saldo
     * Ajusta/modifica directamente el saldo de una caja para cuadraturas diarias.
     */
    public function ajustarSaldo(): ResponseInterface
    {
        $body = $this->request->getJSON(true) ?? $this->request->getPost();

        $cajaId      = !empty($body['caja_id']) ? (int)$body['caja_id'] : null;
        $nuevoSaldo  = isset($body['nuevo_saldo']) ? (float)$body['nuevo_saldo'] : null;
        $fecha       = !empty($body['fecha']) ? trim($body['fecha']) : date('Y-m-d');
        $comentario  = trim($body['comentario'] ?? 'Cuadratura / Ajuste directo de saldo');

        if (!$cajaId) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Debe seleccionar una caja válida.',
            ]);
        }

        if ($nuevoSaldo === null || $nuevoSaldo < 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Debe ingresar un saldo válido (igual o mayor a 0).',
            ]);
        }

        // Obtener usuario ejecutor desde sesión
        $nombreUser = session()->get('Nombre') ?? 'Administrador';
        if (session()->get('Apellidos')) {
            $nombreUser .= ' ' . session()->get('Apellidos');
        }
        $userId = session()->get('is_logued_in') ? (int)session()->get('is_logued_in') : null;

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $caja = $this->cajasModel->find($cajaId);
            if (!$caja) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'La caja seleccionada no existe.',
                ]);
            }

            $saldoAnterior = (float)$caja['saldo_actual'];
            $diferencia    = $nuevoSaldo - $saldoAnterior;

            // 1. Actualizar saldo_actual en tbl_cajas
            $this->cajasModel->update($cajaId, [
                'saldo_actual' => $nuevoSaldo,
            ]);

            // 2. Registrar movimiento de tipo "ajuste" en tbl_caja_movimientos
            $diffStr = ($diferencia >= 0 ? '+' : '-') . '$' . number_format(abs($diferencia), 0, ',', '.');
            $this->movimientosModel->insert([
                'caja_id'        => $cajaId,
                'tipo'           => 'ajuste',
                'monto'          => abs($diferencia),
                'saldo_anterior' => $saldoAnterior,
                'saldo_nuevo'    => $nuevoSaldo,
                'fecha'          => $fecha,
                'comentario'     => $comentario . " [Diferencia: {$diffStr}]",
                'usuario_nombre' => $nombreUser,
                'usuario_id'     => $userId,
            ]);

            $db->transComplete();

            if (!$db->transStatus()) {
                throw new \RuntimeException('La transacción SQL ha fallado.');
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Saldo de caja ajustado con éxito para cuadratura.',
            ]);
        } catch (\Exception $e) {
            $db->transRollback();
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Error al ajustar saldo: ' . $e->getMessage(),
            ]);
        }
    }
}
