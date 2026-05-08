<?php

namespace App\Controllers;

use App\Models\CuentasCobrarModel;
use App\Models\ClientesModel;
use App\Models\LoginModel;
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
        $session = session();
        $userId = $session->get('is_logued_in');
        $loginModel = new LoginModel();
        $usuario = $userId ? $loginModel->obtenerPorId($userId) : null;
        $data = [
            'title' => 'Balance Diario',
            'base_url' => base_url(),
            'assets_url' => base_url('public/assets/'),
            'activePage' => 'balance-diario',
            'usuario' => $usuario,
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
                'tipo_documento' => trim(substr($reg['tipo_documento'] ?? 'Sin tipo', 0, 120)),
                'fecha' => $fecha,
                'numero' => trim(substr((string) ($reg['numero'] ?? ''), 0, 50)),
                'emisor_receptor' => trim(substr($reg['emisor_receptor'], 0, 200)),
                'rut' => trim(substr($reg['rut'] ?? '', 0, 20)),
                'total' => (float) ($reg['total'] ?? 0),
                'pagado' => (float) ($reg['pagado'] ?? 0),
                'impago' => (float) ($reg['impago'] ?? 0),
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
            'success' => true,
            'insertados' => $insertados,
            'message' => "{$insertados} registro(s) guardados correctamente.",
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
    public function sincronizarCobrar(): ResponseInterface
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
            $rut = trim($cliente['rut_cliente'] ?? $cliente['rut'] ?? '');
            if (empty($rut))
                continue;

            $docsNormalizados = [];
            foreach (($cliente['docs'] ?? []) as $doc) {
                $fecha = $this->parsearFecha($doc['fecha'] ?? '');
                $docsNormalizados[] = [
                    'tipo_documento' => $doc['tipo_documento'] ?? 'Sin tipo',
                    'fecha' => $fecha ?: date('Y-m-d'),
                    'numero' => (string) ($doc['numero'] ?? ''),
                    'rut' => $doc['rut'] ?? ($cliente['rut'] ?? ''),
                    'total' => (float) ($doc['total'] ?? 0),
                    'pagado' => (float) ($doc['pagado'] ?? 0),
                    'impago' => (float) ($doc['impago'] ?? 0),
                ];
            }

            $clientesNormalizados[] = [
                'rut_cliente' => $rut,
                'nombre_cliente' => trim($cliente['emisor_receptor'] ?? $cliente['nombre_cliente'] ?? ''),
                'docs' => $docsNormalizados,
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
        $eliminados = $resultado['eliminados_clientes'];
        $insertados = $resultado['insertados'];

        $msg = "Sincronizacion completada: {$sincronizados} cliente(s) actualizados, {$insertados} documento(s) guardados.";
        if ($eliminados > 0) {
            $msg .= " Se eliminaron {$eliminados} cliente(s) que ya no estaban en pantalla.";
        }

        return $this->response->setJSON([
            'success' => true,
            'clientes_sincronizados' => $sincronizados,
            'eliminados_clientes' => $eliminados,
            'insertados' => $insertados,
            'message' => $msg,
        ]);
    }

    // ── DELETE /cuentas-cobrar/eliminar
    public function eliminarCobrar(): ResponseInterface
    {
        $body = $this->request->getJSON(true);
        // Aceptar 'rut' (nuevo esquema) o 'cliente' como fallback de compatibilidad
        $rut = trim($body['rut'] ?? $body['cliente'] ?? '');

        if (empty($rut)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Se requiere el campo "rut" del cliente.',
            ]);
        }

        try {
            $eliminados = $this->cobrarModel->eliminarPorCliente($rut);
        } catch (\Exception $e) {
            log_message('error', '[BalanceDiario::eliminarCobrar] ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Error al eliminar en BD: ' . $e->getMessage(),
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'eliminados' => $eliminados,
            'message' => $eliminados > 0
                ? "{$eliminados} documento(s) del cliente RUT {$rut} eliminados de la BD."
                : "El cliente RUT {$rut} no tenia registros en la BD.",
        ]);
    }

    public function pendientesCobrar(): ResponseInterface
    {
        return $this->response->setJSON([
            'success' => true,
            'total_impago' => $this->cobrarModel->totalImpago(),
            'registros' => $this->cobrarModel->obtenerPendientes(),
        ]);
    }

    // Metodo temporal para corregir impago = total - pagado en registros existentes
    public function corregirImpago(): ResponseInterface
    {
        $db = \Config\Database::connect();
        $affected = $db->query("UPDATE tbl_documentos_cobrar SET impago = total - pagado WHERE impago = 0 AND total > 0");
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
            if ($dt !== false)
                return $dt->format('Y-m-d');
        }
        return null;
    }

    /**
     * GET /clientes/buscar?q=texto
     * Devuelve lista JSON de clientes que coincidan con nombre, razón social o RUT.
     */
    public function buscarClientes(): ResponseInterface
    {
        $q = trim($this->request->getGet('q') ?? '');
        if (strlen($q) < 2) {
            return $this->response->setJSON([]);
        }

        $model = new ClientesModel();
        $results = $model->groupStart()
            ->like('nombre', $q)
            ->orLike('razon_social', $q)
            ->orLike('rut', $q)
            ->groupEnd()
            ->select('id, nombre, razon_social, rut')
            ->orderBy('nombre', 'ASC')
            ->findAll(10);

        return $this->response->setJSON($results);
    }

    // ── GET /cuentas-pagar/pendientes
    public function pendientesPagar(): ResponseInterface
    {
        $model = new \App\Models\CuentasPagarModel();
        return $this->response->setJSON([
            'success' => true,
            'total_impago' => $model->totalImpago(),
            'registros' => $model->obtenerPendientes(),
        ]);
    }

    // ── POST /cuentas-pagar/sincronizar
    public function sincronizarPagar(): ResponseInterface
    {
        $body = $this->request->getJSON(true);
        $proveedores = $body['proveedores'] ?? [];

        if (empty($proveedores)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'No se recibieron proveedores para sincronizar.',
            ]);
        }

        $model = new \App\Models\CuentasPagarModel();
        $result = $model->sincronizar($proveedores);

        return $this->response->setJSON([
            'success' => empty($result['errores']),
            'insertados' => $result['insertados'],
            'actualizados' => $result['actualizados'],
            'errores' => $result['errores'],
            'message' => "SincronizaciÃ³n completada: {$result['insertados']} documento(s) guardados.",
        ]);
    }

    // ── DELETE /cuentas-pagar/eliminar
    public function eliminarPagar(): ResponseInterface
    {
        $body = $this->request->getJSON(true);
        $rut = trim($body['rut'] ?? '');

        if (empty($rut)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'RUT requerido para eliminar.',
            ]);
        }

        $model = new \App\Models\CuentasPagarModel();
        $eliminados = $model->eliminarPorProveedor($rut);

        return $this->response->setJSON([
            'success' => true,
            'message' => $eliminados > 0
                ? "{$eliminados} documento(s) del proveedor eliminados de la BD."
                : "El proveedor no tenÃ­a registros en la BD.",
        ]);
    }

    /**
     * GET /cuentas-cobrar/verificar-documento?numero=xxx
     * Verifica si un número de documento ya existe en tbl_documentos_cobrar.
     */
    public function verificarDocumento(): ResponseInterface
    {
        $numero = trim($this->request->getGet('numero') ?? '');

        if (empty($numero) || $numero === 'N/A') {
            return $this->response->setJSON(['existe' => false, 'count' => 0]);
        }

        $db = \Config\Database::connect();
        $query = $db->query(
            "SELECT COUNT(*) AS total, GROUP_CONCAT(DISTINCT COALESCE(c.razon_social, c.nombre, d.rut_cliente) ORDER BY 1 SEPARATOR ', ') AS clientes
             FROM tbl_documentos_cobrar d
             LEFT JOIN tbl_clientes c ON c.rut = d.rut_cliente
             WHERE d.numero = ?",
            [$numero]
        );
        $row = $query ? $query->getRowArray() : ['total' => 0, 'clientes' => ''];
        $count = (int) ($row['total'] ?? 0);

        return $this->response->setJSON([
            'existe' => $count > 0,
            'count' => $count,
            'clientes' => $row['clientes'] ?? '',
        ]);
    }

    /**
     * GET /cuentas-pagar/verificar-documento?numero=xxx
     * Verifica si un número de documento ya existe en tbl_documentos_pagar.
     */
    public function verificarDocumentoPagar(): ResponseInterface
    {
        $numero = trim($this->request->getGet('numero') ?? '');

        if (empty($numero) || $numero === 'N/A') {
            return $this->response->setJSON(['existe' => false, 'count' => 0]);
        }

        $db = \Config\Database::connect();
        $query = $db->query(
            "SELECT COUNT(*) AS total, GROUP_CONCAT(DISTINCT COALESCE(p.razon_social, p.nombre, d.rut_proveedor) ORDER BY 1 SEPARATOR ', ') AS proveedores
             FROM tbl_documentos_pagar d
             LEFT JOIN tbl_proveedores p ON p.rut = d.rut_proveedor
             WHERE d.numero = ?",
            [$numero]
        );
        $row = $query ? $query->getRowArray() : ['total' => 0, 'proveedores' => ''];
        $count = (int) ($row['total'] ?? 0);

        return $this->response->setJSON([
            'existe' => $count > 0,
            'count' => $count,
            'clientes' => $row['proveedores'] ?? '',
        ]);
    }

    /**
     * GET /inventario/productos
     * Devuelve todos los productos activos con precio_con_iva = costo_neto * 1.19
     */
    public function inventarioProductos(): ResponseInterface
    {
        $db = \Config\Database::connect();
        $rows = $db->query("
            SELECT
                p.sku,
                p.nombre,
                p.categoria,
                p.marca,
                p.costo_neto,
                ROUND(p.costo_neto * 1.19, 0)          AS precio_con_iva,
                ROUND(p.costo_neto * 0.19, 0)           AS monto_iva,
                p.stock_bodega_ppral                    AS stock,
                p.stock_reservado,
                ROUND(p.costo_neto * 1.19 * p.stock_bodega_ppral, 0) AS total
            FROM tbl_productos p
            WHERE p.activo = 1
            ORDER BY p.nombre ASC
        ")->getResultArray();

        return $this->response->setJSON(['success' => true, 'data' => $rows]);
    }

    /**
     * GET /proveedores/buscar?q=texto
     */
    public function buscarProveedores(): ResponseInterface
    {
        $q = trim($this->request->getGet('q') ?? '');
        if (strlen($q) < 2) {
            return $this->response->setJSON([]);
        }

        $db = \Config\Database::connect();
        $results = $db->table('tbl_proveedores')
            ->groupStart()
            ->like('nombre', $q)
            ->orLike('razon_social', $q)
            ->orLike('rut', $q)
            ->groupEnd()
            ->select('id, nombre, razon_social, rut')
            ->orderBy('nombre', 'ASC')
            ->limit(10)
            ->get()
            ->getResultArray();

        return $this->response->setJSON($results);
    }
}
