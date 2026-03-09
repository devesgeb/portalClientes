<?php

namespace App\Controllers;

use App\Models\CuentasCobrarModel;
use CodeIgniter\HTTP\ResponseInterface;

class BalanceDiario extends BaseController
{
    protected $cobrarModel;

    public function __construct()
    {
        $this->cobrarModel = new CuentasCobrarModel();
        helper(['url', 'form']);
    }

    public function index(): string
    {
        $data = [
            'title'      => 'Balance Diario',
            'base_url'   => base_url(),
            'assets_url' => base_url('public/assets/'),
        ];
        return view('balance_diario', $data);
    }

    // ── POST /cuentas-cobrar/guardar (insert simple, sin sync)
    public function guardar(): ResponseInterface
    {
        $body = $this->request->getJSON(true);

        if (empty($body['registros']) || !is_array($body['registros'])) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Se requiere un array "registros" en el body.',
            ]);
        }

        $filas = [];
        foreach ($body['registros'] as $idx => $reg) {
            if (empty($reg['emisor_receptor']) || empty($reg['numero'])) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => "El registro #{$idx} no tiene emisor_receptor o numero.",
                ]);
            }

            $fecha = $this->parsearFecha($reg['fecha'] ?? '');
            if (!$fecha) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => "Fecha invalida en el registro #{$idx}: \"{$reg['fecha']}\".",
                ]);
            }

            $filas[] = [
                'tipo_documento'  => trim(substr($reg['tipo_documento']  ?? 'Sin tipo', 0, 120)),
                'fecha'           => $fecha,
                'numero'          => trim(substr((string)($reg['numero'] ?? ''), 0, 50)),
                'emisor_receptor' => trim(substr($reg['emisor_receptor'], 0, 200)),
                'rut'             => trim(substr($reg['rut']   ?? '', 0, 20)),
                'total'           => (float)($reg['total']  ?? 0),
                'pagado'          => (float)($reg['pagado'] ?? 0),
                'impago'          => (float)($reg['impago'] ?? 0),
            ];
        }

        try {
            $insertados = $this->cobrarModel->insertarRegistros($filas);
        } catch (\RuntimeException $e) {
            log_message('error', '[BalanceDiario::guardar] ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Error al guardar en BD: ' . $e->getMessage(),
            ]);
        }

        return $this->response->setStatusCode(201)->setJSON([
            'success'    => true,
            'insertados' => $insertados,
            'message'    => "{$insertados} registro(s) guardados correctamente.",
        ]);
    }

    /**
     * POST /cuentas-cobrar/sincronizar
     *
     * Recibe el estado completo de pantalla y sincroniza con la BD:
     * - Elimina clientes que ya no aparecen
     * - Reemplaza los docs de cada cliente visible (delete + insert)
     *
     * Body JSON:
     * {
     *   "clientes": [
     *     { "emisor_receptor": "...", "rut": "...", "docs": [...] }
     *   ]
     * }
     */
    public function sincronizar(): ResponseInterface
    {
        $body = $this->request->getJSON(true);

        if (!isset($body['clientes']) || !is_array($body['clientes'])) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Se requiere un array "clientes" en el body.',
            ]);
        }

        // Normalizar fechas de los docs
        $clientesNormalizados = [];
        foreach ($body['clientes'] as $idx => $cliente) {
            $emisor = trim($cliente['emisor_receptor'] ?? '');
            if (empty($emisor)) continue;

            $docsNormalizados = [];
            foreach (($cliente['docs'] ?? []) as $doc) {
                $fecha = $this->parsearFecha($doc['fecha'] ?? '');
                $docsNormalizados[] = [
                    'tipo_documento'  => $doc['tipo_documento'] ?? 'Sin tipo',
                    'fecha'           => $fecha ?: date('Y-m-d'),
                    'numero'          => (string)($doc['numero'] ?? ''),
                    'rut'             => $doc['rut'] ?? ($cliente['rut'] ?? ''),
                    'total'           => (float)($doc['total']  ?? 0),
                    'pagado'          => (float)($doc['pagado'] ?? 0),
                    'impago'          => (float)($doc['impago'] ?? 0),
                ];
            }

            $clientesNormalizados[] = [
                'emisor_receptor' => $emisor,
                'docs'            => $docsNormalizados,
            ];
        }

        try {
            $resultado = $this->cobrarModel->sincronizar($clientesNormalizados);
        } catch (\Exception $e) {
            log_message('error', '[BalanceDiario::sincronizar] ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Error al sincronizar: ' . $e->getMessage(),
            ]);
        }

        $sincronizados = $resultado['clientes_sincronizados'];
        $eliminados    = $resultado['eliminados_clientes'];
        $insertados    = $resultado['insertados'];

        $msg = "Sincronizacion completada: {$sincronizados} cliente(s) actualizados, {$insertados} documento(s) guardados.";
        if ($eliminados > 0) {
            $msg .= " Se eliminaron {$eliminados} cliente(s) que ya no estaban en pantalla.";
        }

        return $this->response->setJSON([
            'success'                => true,
            'clientes_sincronizados' => $sincronizados,
            'eliminados_clientes'    => $eliminados,
            'insertados'             => $insertados,
            'message'                => $msg,
        ]);
    }

    // ── DELETE /cuentas-cobrar/eliminar
    public function eliminarCobrar(): ResponseInterface
    {
        $body = $this->request->getJSON(true);
        $cliente = trim($body['cliente'] ?? '');

        if (empty($cliente)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Se requiere el campo "cliente".',
            ]);
        }

        try {
            $eliminados = $this->cobrarModel->eliminarPorCliente($cliente);
        } catch (\Exception $e) {
            log_message('error', '[BalanceDiario::eliminarCobrar] ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Error al eliminar en BD: ' . $e->getMessage(),
            ]);
        }

        return $this->response->setJSON([
            'success'    => true,
            'eliminados' => $eliminados,
            'message'    => $eliminados > 0
                ? "{$eliminados} documento(s) de \"{$cliente}\" eliminados de la BD."
                : "El cliente \"{$cliente}\" no tenia registros en la BD.",
        ]);
    }

    public function pendientes(): ResponseInterface
    {
        return $this->response->setJSON([
            'success'      => true,
            'total_impago' => $this->cobrarModel->totalImpago(),
            'registros'    => $this->cobrarModel->obtenerPendientes(),
        ]);
    }

    // Metodo temporal para corregir impago = total - pagado en registros existentes
    public function corregirImpago(): ResponseInterface
    {
        $db = \Config\Database::connect();
        $affected = $db->query("UPDATE tbl_cuentasCobrar SET impago = total - pagado WHERE impago = 0 AND total > 0");
        $rows = $db->affectedRows();
        return $this->response->setJSON(['success' => true, 'filas_corregidas' => $rows, 'message' => "$rows fila(s) corregidas."]);
    }

    private function parsearFecha(string $rawFecha): ?string
    {
        if (empty($rawFecha) || $rawFecha === '-') {
            return date('Y-m-d');
        }
        foreach (['d-m-Y', 'd/m/Y', 'Y-m-d', 'Y/m/d', 'm/d/Y'] as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, trim($rawFecha));
            if ($dt !== false) return $dt->format('Y-m-d');
        }
        return null;
    }
}