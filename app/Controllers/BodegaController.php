<?php
namespace App\Controllers;

use App\Models\ProductosModel;
use App\Models\ListaPreciosModel;
use App\Models\LoginModel;
use CodeIgniter\HTTP\ResponseInterface;

class BodegaController extends BaseController
{
    // ── Vista: Listado de productos ────────────────────────────────
    public function productos()
    {
        $session    = session();
        $userId     = $session->get('is_logued_in');
        $loginModel = new LoginModel();
        $usuario    = $userId ? $loginModel->obtenerPorId($userId) : null;

        return view('bodega/productos', [
            'title'      => 'Productos – Bodega',
            'base_url'   => base_url(),
            'assets_url' => base_url('public/assets/'),
            'activePage' => 'productos',
            'usuario'    => $usuario,
        ]);
    }

    // ── GET /bodega/lista-productos — Todos los productos (JSON) ──
    public function listarProductos(): ResponseInterface
    {
        $db = \Config\Database::connect();

        $productos = $db->table('tbl_productos')
                        ->where('activo', 1)
                        ->orderBy('categoria', 'ASC')
                        ->orderBy('nombre',    'ASC')
                        ->get()->getResultArray();

        // Traer precios de tbl_listaPrecios agrupados por SKU
        $listas = $db->query(
            "SELECT sku, lista, precio_neto, precio_total
               FROM tbl_listaPrecios
              WHERE activo = 1
              ORDER BY sku, lista"
        )->getResultArray();

        // Indexar: [ sku => [ lista => { precio_neto, precio_total } ] ]
        $listasIdx = [];
        foreach ($listas as $l) {
            $listasIdx[$l['sku']][$l['lista']] = [
                'precio_neto'   => $l['precio_neto'],
                'precio_total'  => $l['precio_total'],
            ];
        }

        foreach ($productos as &$p) {
            $p['precios_lista'] = $listasIdx[$p['sku']] ?? [];          // dict lista→precios
            $p['listas']        = array_keys($listasIdx[$p['sku']] ?? []); // solo nombres
        }
        unset($p);

        return $this->response->setJSON(['success' => true, 'productos' => $productos]);
    }

    // ── GET /bodega/producto/{sku} — Detalle completo (JSON) ───────
    public function getProducto(string $sku): ResponseInterface
    {
        $db  = \Config\Database::connect();
        $row = $db->table('tbl_productos')->where('sku', $sku)->get()->getRowArray();

        if (!$row) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Producto no encontrado.']);
        }

        // Listas de precio asociadas
        $listas = $db->table('tbl_listaPrecios')
                     ->where('sku', $sku)
                     ->where('activo', 1)
                     ->orderBy('lista', 'ASC')
                     ->get()->getResultArray();

        return $this->response->setJSON(['success' => true, 'producto' => $row, 'listas' => $listas]);
    }

    // ── PATCH /bodega/producto/{sku} — Editar nombre y/o cantidad ──
    public function actualizarProducto(string $sku): ResponseInterface
    {
        $body = $this->request->getJSON(true);
        $db   = \Config\Database::connect();

        $existe = $db->table('tbl_productos')->where('sku', $sku)->countAllResults();
        if (!$existe) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Producto no encontrado.']);
        }

        $datos = [];
        if (isset($body['nombre'])           && trim($body['nombre']) !== '') $datos['nombre'] = trim($body['nombre']);
        if (isset($body['stock_bodega_ppral']))                               $datos['stock_bodega_ppral'] = (float) $body['stock_bodega_ppral'];

        if (empty($datos)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Nada que actualizar.']);
        }

        $db->table('tbl_productos')->where('sku', $sku)->update($datos);

        return $this->response->setJSON(['success' => true, 'message' => 'Producto actualizado correctamente.']);
    }

    // ── DELETE /bodega/producto/{sku} — Eliminar producto ──────────
    public function eliminarProducto(string $sku): ResponseInterface
    {
        $db = \Config\Database::connect();

        $existe = $db->table('tbl_productos')->where('sku', $sku)->countAllResults();
        if (!$existe) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Producto no encontrado.']);
        }

        // Primero eliminar listas de precio asociadas (respeta FK)
        $db->table('tbl_listaPrecios')->where('sku', $sku)->delete();
        $db->table('tbl_productos')->where('sku', $sku)->delete();

        return $this->response->setJSON(['success' => true, 'message' => "Producto $sku eliminado correctamente."]);
    }

    // ── Vista principal: Carga masiva (tabs Productos + Lista Precios) ─
    public function cargaMasiva()
    {
        $session    = session();
        $userId     = $session->get('is_logued_in');
        $loginModel = new LoginModel();
        $usuario    = $userId ? $loginModel->obtenerPorId($userId) : null;

        return view('bodega/carga_masiva_productos', [
            'title'      => 'Carga masiva – Bodega',
            'base_url'   => base_url(),
            'assets_url' => base_url('public/assets/'),
            'activePage' => 'carga-masiva-productos',
            'usuario'    => $usuario,
        ]);
    }

    // ── POST /bodega/importar-productos ────────────────────────────────
    public function importarProductos(): ResponseInterface
    {
        $body = $this->request->getJSON(true);
        $rows = $body['rows'] ?? [];

        if (empty($rows) || !is_array($rows)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'No se recibieron filas para importar.',
            ]);
        }

        try {
            $model  = new ProductosModel();
            $result = $model->importar($rows);
        } catch (\Exception $e) {
            log_message('error', '[BodegaController::importarProductos] ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
            ]);
        }

        return $this->response->setJSON([
            'success'      => true,
            'insertados'   => $result['insertados'],
            'actualizados' => $result['actualizados'],
            'errores'      => $result['errores'],
            'message'      => "Importación completada: {$result['insertados']} nuevos, {$result['actualizados']} actualizados."
                . (count($result['errores']) > 0 ? ' Con ' . count($result['errores']) . ' error(es).' : ''),
        ]);
    }

    // ── POST /bodega/importar-lista-precios ────────────────────────────
    public function importarListaPrecios(): ResponseInterface
    {
        $body = $this->request->getJSON(true);
        $rows = $body['rows'] ?? [];

        if (empty($rows) || !is_array($rows)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'No se recibieron filas para importar.',
            ]);
        }

        try {
            $model  = new ListaPreciosModel();
            $result = $model->importar($rows);
        } catch (\Exception $e) {
            log_message('error', '[BodegaController::importarListaPrecios] ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
            ]);
        }

        $skuMsg = '';
        if (!empty($result['skuNoExiste'])) {
            $unicos = array_unique($result['skuNoExiste']);
            $skuMsg = ' SKUs no encontrados: ' . implode(', ', $unicos) . '.';
        }

        return $this->response->setJSON([
            'success'      => true,
            'insertados'   => $result['insertados'],
            'actualizados' => $result['actualizados'],
            'errores'      => $result['errores'],
            'skuNoExiste'  => array_unique($result['skuNoExiste'] ?? []),
            'message'      => "Importación completada: {$result['insertados']} nuevas, {$result['actualizados']} actualizadas."
                . (count($result['errores']) > 0 ? ' Con ' . count($result['errores']) . ' error(es).' : '')
                . $skuMsg,
        ]);
    }

    // ── GET /bodega/stats ──────────────────────────────────────────────
    public function stats(): ResponseInterface
    {
        $db = \Config\Database::connect();

        $totalProductos = $db->table('tbl_productos')->where('activo', 1)->countAllResults();
        $totalListas    = $db->table('tbl_listaPrecios')->where('activo', 1)->countAllResults();

        $categorias = $db->query(
            "SELECT categoria, COUNT(*) as total
               FROM tbl_productos
              WHERE activo = 1
              GROUP BY categoria
              ORDER BY total DESC"
        )->getResultArray();

        $ultimaRow = $db->query(
            "SELECT DATE_FORMAT(MAX(updated_at), '%d/%m/%Y') AS fecha FROM tbl_productos"
        )->getRowArray();

        $listasResumen = $db->query(
            "SELECT lista, COUNT(*) as total
               FROM tbl_listaPrecios
              WHERE activo = 1
              GROUP BY lista
              ORDER BY total DESC"
        )->getResultArray();

        $ultimaLista = $db->query(
            "SELECT DATE_FORMAT(MAX(updated_at), '%d/%m/%Y') AS fecha FROM tbl_listaPrecios"
        )->getRowArray();

        return $this->response->setJSON([
            'success' => true,
            'data' => [
                'total_productos'         => $totalProductos,
                'total_listas'            => $totalListas,
                'categorias'              => $categorias,
                'listas_resumen'          => $listasResumen,
                'ultima_actualizacion'    => $ultimaRow['fecha']   ?? '--',
                'ultima_actualizacion_lp' => $ultimaLista['fecha'] ?? '--',
            ],
        ]);
    }

    // ── GET /bodega/plantilla-productos ───────────────────────────────
    public function plantillaProductos()
    {
        $file = WRITEPATH . 'uploads/productos.xlsx';
        if (!file_exists($file)) {
            return $this->response->setStatusCode(404)->setBody('Plantilla no encontrada.');
        }
        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="plantilla_productos.xlsx"')
            ->setBody(file_get_contents($file));
    }

    // ── GET /bodega/plantilla-lista-precios ───────────────────────────
    public function plantillaListaPrecios()
    {
        $file = WRITEPATH . 'uploads/lista_de_precios.xlsx';
        if (!file_exists($file)) {
            return $this->response->setStatusCode(404)->setBody('Plantilla no encontrada.');
        }
        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="plantilla_lista_de_precios.xlsx"')
            ->setBody(file_get_contents($file));
    }
}
