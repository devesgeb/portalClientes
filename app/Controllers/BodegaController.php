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

    // ── GET /bodega/producto/{sku} — Obtener detalle de un producto ─
    public function getProducto(string $sku): ResponseInterface
    {
        $db  = \Config\Database::connect();
        $row = $db->table('tbl_productos')->where('sku', $sku)->get()->getRowArray();

        if (!$row && $db->tableExists('tbl_facto_productos')) {
            $fRow = $db->table('tbl_facto_productos')->where('sku', $sku)->get()->getRowArray();
            if ($fRow) {
                $row = [
                    'id' => $fRow['id'],
                    'sku' => $fRow['sku'],
                    'nombre' => $fRow['nombre'],
                    'categoria' => 'Facto API',
                    'marca' => $fRow['marca'],
                    'modelo' => $fRow['modelo'],
                    'unidad' => $fRow['unidad_medida'],
                    'codigo_barras' => null,
                    'tipo' => 'Producto',
                    'costo_neto' => $fRow['costo_neto'],
                    'precio_venta_neto' => $fRow['precio_neto'],
                    'monto_iva' => $fRow['precio_impuesto'],
                    'precio_venta_total' => $fRow['precio_total'],
                    'stock_bodega_ppral' => $fRow['stock'],
                    'stock_reservado' => $fRow['stock_reservado'],
                    'stock_bodega_sec' => 0,
                    'stock_minimo' => 0,
                    'comision_vendedor' => null,
                    'descripcion' => $fRow['descripcion']
                ];
            }
        }

        if (!$row) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Producto no encontrado.']);
        }

        // Listas de precio asociadas
        $listas = [];
        if ($db->tableExists('tbl_listaPrecios')) {
            $listas = $db->table('tbl_listaPrecios')
                         ->where('sku', $sku)
                         ->where('activo', 1)
                         ->orderBy('lista', 'ASC')
                         ->get()->getResultArray();
        }

        if (empty($listas) && $db->tableExists('tbl_facto_productos')) {
            $fListas = $db->table('tbl_facto_productos')
                          ->where('sku', $sku)
                          ->get()->getResultArray();
            foreach ($fListas as $fl) {
                $listas[] = [
                    'sku' => $fl['sku'],
                    'lista' => $fl['nombre_lista'],
                    'precio_neto' => $fl['precio_neto'],
                    'precio_total' => $fl['precio_total'],
                    'activo' => 1
                ];
            }
        }

        return $this->response->setJSON(['success' => true, 'producto' => $row, 'listas' => $listas]);
    }

    // ── PATCH /bodega/producto/{sku} — Editar nombre y/o cantidad ──
    public function actualizarProducto(string $sku): ResponseInterface
    {
        $body = $this->request->getJSON(true);
        $db   = \Config\Database::connect();

        $existeProd = $db->table('tbl_productos')->where('sku', $sku)->countAllResults();
        $existeFacto = $db->tableExists('tbl_facto_productos') ? $db->table('tbl_facto_productos')->where('sku', $sku)->countAllResults() : 0;

        if (!$existeProd && !$existeFacto) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Producto no encontrado.']);
        }

        $datos = [];
        if (isset($body['nombre'])           && trim($body['nombre']) !== '') $datos['nombre'] = trim($body['nombre']);
        if (isset($body['stock_bodega_ppral']))                               $datos['stock_bodega_ppral'] = (float) $body['stock_bodega_ppral'];
        if (isset($body['stock_reservado']))                                  $datos['stock_reservado'] = (float) $body['stock_reservado'];
        if (isset($body['costo_neto']))                                       $datos['costo_neto'] = (float) $body['costo_neto'];

        if ($existeProd && !empty($datos)) {
            $db->table('tbl_productos')->where('sku', $sku)->update($datos);
        }

        if ($existeFacto) {
            $datosFacto = [];
            if (isset($body['nombre']) && trim($body['nombre']) !== '') $datosFacto['nombre'] = trim($body['nombre']);
            if (isset($body['stock_bodega_ppral']))                      $datosFacto['stock'] = (float) $body['stock_bodega_ppral'];
            if (isset($body['stock_reservado']))                         $datosFacto['stock_reservado'] = (float) $body['stock_reservado'];
            if (isset($body['costo_neto'])) {
                $costoNeto = (float)$body['costo_neto'];
                $datosFacto['costo_neto'] = $costoNeto;
                $datosFacto['costo_total'] = round($costoNeto * 1.19, 2);
            }
            if (isset($body['precio_venta_neto'])) {
                $precioNeto = (float)$body['precio_venta_neto'];
                $datosFacto['precio_neto'] = $precioNeto;
                $datosFacto['precio_impuesto'] = round($precioNeto * 0.19, 2);
                $datosFacto['precio_total'] = $precioNeto + $datosFacto['precio_impuesto'];
            }
            if (!empty($datosFacto)) {
                $db->table('tbl_facto_productos')->where('sku', $sku)->update($datosFacto);
            }
        }

        // Actualizar o insertar lista de precio 'Precios base'
        if (isset($body['precio_venta_neto']) && $existeProd) {
            $precioNeto = (float) $body['precio_venta_neto'];
            $precioTotal = round($precioNeto * 1.19, 0);

            $existeLista = $db->table('tbl_listaPrecios')
                              ->where('sku', $sku)
                              ->where('lista', 'Precios base')
                              ->countAllResults();

            if ($existeLista) {
                $db->table('tbl_listaPrecios')
                   ->where('sku', $sku)
                   ->where('lista', 'Precios base')
                   ->update([
                       'precio_neto' => $precioNeto,
                       'precio_total' => $precioTotal,
                       'activo' => 1
                   ]);
            } else {
                $db->table('tbl_listaPrecios')->insert([
                    'sku' => $sku,
                    'lista' => 'Precios base',
                    'precio_neto' => $precioNeto,
                    'precio_total' => $precioTotal,
                    'activo' => 1
                ]);
            }
        }

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

    // ── GET /bodega/reservas/{sku} — Obtener reservas por SKU ──────────
    public function getReservas(string $sku): ResponseInterface
    {
        $db = \Config\Database::connect();
        $reservas = $db->table('tbl_productos_reservas r')
                       ->select('r.id, r.sku, r.rut_cliente, r.cantidad, c.nombre AS nombre_cliente')
                       ->join('tbl_clientes c', 'c.rut = r.rut_cliente', 'left')
                       ->where('r.sku', $sku)
                       ->orderBy('c.nombre', 'ASC')
                       ->get()->getResultArray();

        return $this->response->setJSON(['success' => true, 'reservas' => $reservas]);
    }

    // ── POST /bodega/reservas — Guardar/Crear reserva para un cliente ──
    public function guardarReserva(): ResponseInterface
    {
        $body = $this->request->getJSON(true);
        $sku = $body['sku'] ?? '';
        $rut = trim($body['rut_cliente'] ?? '');
        $cantidad = (float) ($body['cantidad'] ?? 0);

        if (empty($sku) || empty($rut) || $cantidad < 0) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Parámetros inválidos.']);
        }

        $db = \Config\Database::connect();
        $p = $db->table('tbl_productos')->where('sku', $sku)->get()->getRowArray();
        if (!$p) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Producto no encontrado.']);
        }

        $c = $db->table('tbl_clientes')->where('rut', $rut)->get()->getRowArray();
        if (!$c) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Cliente no registrado en el sistema.']);
        }

        $existe = $db->table('tbl_productos_reservas')
                     ->where('sku', $sku)
                     ->where('rut_cliente', $rut)
                     ->get()->getRowArray();

        if ($existe) {
            $db->table('tbl_productos_reservas')
               ->where('id', $existe['id'])
               ->update(['cantidad' => $cantidad]);
        } else {
            $db->table('tbl_productos_reservas')->insert([
                'sku' => $sku,
                'rut_cliente' => $rut,
                'cantidad' => $cantidad
            ]);
        }

        $total = $db->table('tbl_productos_reservas')
                    ->where('sku', $sku)
                    ->selectSum('cantidad')
                    ->get()->getRowArray()['cantidad'] ?? 0;

        $db->table('tbl_productos')->where('sku', $sku)->update(['stock_reservado' => $total]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Reserva guardada correctamente.',
            'stock_reservado' => $total
        ]);
    }

    // ── POST /bodega/reservas/descontar — Descontar stock de una reserva ─
    public function descontarReserva(): ResponseInterface
    {
        $body = $this->request->getJSON(true);
        $id = (int) ($body['id_reserva'] ?? 0);
        $cantidadRestar = (float) ($body['cantidad'] ?? 0);

        if ($id <= 0 || $cantidadRestar <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Parámetros inválidos.']);
        }

        $db = \Config\Database::connect();
        $reserva = $db->table('tbl_productos_reservas')->where('id', $id)->get()->getRowArray();
        if (!$reserva) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Reserva no encontrada.']);
        }

        $sku = $reserva['sku'];
        $nuevaCantidad = max(0.000, ((float) $reserva['cantidad']) - $cantidadRestar);

        if ($nuevaCantidad <= 0) {
            $db->table('tbl_productos_reservas')->where('id', $id)->delete();
        } else {
            $db->table('tbl_productos_reservas')->where('id', $id)->update(['cantidad' => $nuevaCantidad]);
        }

        $total = $db->table('tbl_productos_reservas')
                    ->where('sku', $sku)
                    ->selectSum('cantidad')
                    ->get()->getRowArray()['cantidad'] ?? 0;

        $db->table('tbl_productos')->where('sku', $sku)->update(['stock_reservado' => $total]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Stock descontado correctamente.',
            'stock_reservado' => $total
        ]);
    }

    // ── DELETE /bodega/reservas/{id} — Eliminar reserva ──────────────────
    public function eliminarReserva(int $id): ResponseInterface
    {
        $db = \Config\Database::connect();
        $reserva = $db->table('tbl_productos_reservas')->where('id', $id)->get()->getRowArray();
        if (!$reserva) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Reserva no encontrada.']);
        }

        $sku = $reserva['sku'];
        $db->table('tbl_productos_reservas')->where('id', $id)->delete();

        $total = $db->table('tbl_productos_reservas')
                    ->where('sku', $sku)
                    ->selectSum('cantidad')
                    ->get()->getRowArray()['cantidad'] ?? 0;

        $db->table('tbl_productos')->where('sku', $sku)->update(['stock_reservado' => $total]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Reserva eliminada correctamente.',
            'stock_reservado' => $total
        ]);
    }

    // ── Vista: Prototipo Productos Facto API ────────────────────────
    public function factoProductos()
    {
        $session    = session();
        $userId     = $session->get('is_logued_in');
        $loginModel = new LoginModel();
        $usuario    = $userId ? $loginModel->obtenerPorId($userId) : null;

        $db = \Config\Database::connect();
        $listas = [];
        if ($db->tableExists('tbl_facto_listas_precios')) {
            $listas = $db->table('tbl_facto_listas_precios')
                         ->get()->getResultArray();
        }

        // Si tbl_facto_listas_precios está vacía, fallback a distintas listas registradas en tbl_facto_productos
        if (empty($listas) && $db->tableExists('tbl_facto_productos')) {
            $listasRaw = $db->query(
                "SELECT DISTINCT facto_lista_id, 
                        COALESCE(NULLIF(nombre_lista, ''), CONCAT('Lista #', facto_lista_id)) AS nombre 
                 FROM tbl_facto_productos 
                 WHERE facto_lista_id IS NOT NULL AND facto_lista_id > 0"
            )->getResultArray();

            foreach ($listasRaw as $lr) {
                $listas[] = [
                    'facto_lista_id' => $lr['facto_lista_id'],
                    'nombre'         => $lr['nombre']
                ];
            }
        }

        // Orden específico deseado: 1: Precios base, 11: Horeca, 12: Mayorista, 13: Distribución detallista, 14: Distribución Mayorista
        $ordenDeseado = [1 => 1, 11 => 2, 12 => 3, 13 => 4, 14 => 5];
        usort($listas, function($a, $b) use ($ordenDeseado) {
            $posA = $ordenDeseado[(int)$a['facto_lista_id']] ?? 99;
            $posB = $ordenDeseado[(int)$b['facto_lista_id']] ?? 99;
            return $posA <=> $posB;
        });

        return view('bodega/facto_productos', [
            'title'      => 'Productos Facto API – Bodega',
            'base_url'   => base_url(),
            'assets_url' => base_url('public/assets/'),
            'activePage' => 'facto_productos',
            'usuario'    => $usuario,
            'listas'     => $listas,
        ]);
    }

    // ── GET /bodega/facto-productos/exportar-pdf — Catálogo HTML imprimible ──
    public function exportarCatalogoPdf()
    {
        $db = \Config\Database::connect();

        if (!$db->tableExists('tbl_facto_productos')) {
            return $this->response->setBody('<p>No hay productos sincronizados.</p>');
        }

        // ── Listas permitidas (etiqueta, color y variaciones de nombres) ──
        $listasConfig = [
            'precio_base' => [
                'label'   => 'PRECIO BASE',
                'color'   => '#16a34a',
                'aliases' => ['Precios base', 'Precio base', 'Lista #1', '1']
            ],
            'horeca' => [
                'label'   => 'HORECA',
                'color'   => '#0369a1',
                'aliases' => ['Horeca', 'Lista #11', '11']
            ],
            'mayorista' => [
                'label'   => 'MAYORISTA',
                'color'   => '#7c3aed',
                'aliases' => ['Mayorista', 'Lista #12', '12']
            ],
            'distribucion_detallista' => [
                'label'   => 'DIST. DETALLE',
                'color'   => '#b45309',
                'aliases' => ['Distribución detallista', 'Distribucion detallista', 'Distribución Detallista', 'Distribucion Detallista', 'Dist. Detallista', 'Lista #13', '13']
            ],
            'distribucion_mayorista' => [
                'label'   => 'DIST. MAYOR',
                'color'   => '#be123c',
                'aliases' => ['Distribución Mayorista', 'Distribucion Mayorista', 'Distribución mayorista', 'Distribucion mayorista', 'Dist. Mayorista', 'Lista #14', '14']
            ],
        ];

        // ── Un SKU por fila con GROUP BY ──
        $skusRaw = $db->query(
            "SELECT sku,
                    MIN(nombre)        AS nombre,
                    MIN(descripcion)   AS descripcion,
                    MIN(unidad_medida) AS unidad_medida,
                    MIN(marca)         AS marca,
                    MIN(modelo)        AS modelo
               FROM tbl_facto_productos
              GROUP BY sku
              ORDER BY sku ASC"
        )->getResultArray();

        // ── Precios por SKU e identificadores/nombres ──
        $preciosPorSku = [];
        $queryPrecios = "SELECT p.sku, 
                                COALESCE(l.nombre, p.nombre_lista) AS nombre_lista, 
                                p.nombre_lista AS raw_nombre_lista,
                                p.facto_lista_id, 
                                p.precio_neto, 
                                p.precio_total 
                         FROM tbl_facto_productos p
                         LEFT JOIN tbl_facto_listas_precios l ON p.facto_lista_id = l.facto_lista_id";
        foreach ($db->query($queryPrecios)->getResultArray() as $pr) {
            $sku = $pr['sku'];
            $val = [
                'neto'  => $pr['precio_neto'],
                'total' => $pr['precio_total'],
            ];
            if (!empty($pr['nombre_lista'])) {
                $preciosPorSku[$sku][$pr['nombre_lista']] = $val;
            }
            if (!empty($pr['raw_nombre_lista'])) {
                $preciosPorSku[$sku][$pr['raw_nombre_lista']] = $val;
            }
            if (!empty($pr['facto_lista_id'])) {
                $preciosPorSku[$sku][(string)$pr['facto_lista_id']] = $val;
            }
        }

        // ── Categorías derivadas del prefijo del SKU ──
        $mapaCategoria = [
            'PROCE' => 'Preelaborados',
            'CONG'  => 'Congelados',
            'ACE'   => 'Aderezos y Aceites',
            'COC'   => 'Cocidos',
            'MP'    => 'Materias Primas',
            'ACC'   => 'Accesorios',
        ];
        $porCategoria = [];
        foreach ($skusRaw as $p) {
            // Excepción solicitada: no mostrar MP-001
            if (strtoupper(trim($p['sku'])) === 'MP-001') {
                continue;
            }
            $prefijo = strtoupper(preg_replace('/[-_\d].*/', '', $p['sku']));
            $cat = 'Otros';
            foreach ($mapaCategoria as $key => $nombre) {
                if (str_starts_with($prefijo, $key)) { $cat = $nombre; break; }
            }
            $porCategoria[$cat][] = $p;
        }
        krsort($porCategoria); // Orden inverso (Z-A)

        $totalSkus   = count($skusRaw);
        $totalCats   = count($porCategoria);
        $totalListas = count($listasConfig);
        $fechaGen    = date('d/m/Y H:i');
        $empresa     = 'F Alimentos SpA';

        ob_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Catálogo y Lista de Precios por SKU</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
*{box-sizing:border-box;margin:0;padding:0; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important;}
body{font-family:'Inter',Arial,sans-serif;font-size:8.5pt;color:#111;background:#fff;padding:12mm 10mm;}

/* ── Acción print ── */
/* Ocultar barra de depuración de CodeIgniter 4 */
#debug-icon, #debug-bar, #toolbarContainer { display: none !important; }

.no-print{position:fixed;top:14px;right:14px;z-index:9999;display:flex;gap:8px;}
.btn-pdf{background:#dc2626;color:#fff;border:none;border-radius:8px;padding:9px 20px;font-size:9.5pt;font-weight:700;cursor:pointer;box-shadow:0 4px 14px rgba(220,38,38,.3);}
.btn-close{background:#475569;color:#fff;border:none;border-radius:8px;padding:9px 16px;font-size:9.5pt;font-weight:600;cursor:pointer;}
@media print{
  .no-print{display:none!important;}
  body{padding:0;}
  @page{size:A4 landscape; margin: 0;}
  table { page-break-inside: auto; break-inside: auto; margin-bottom: 20px; }
  .avoid-break { break-inside: avoid; page-break-inside: avoid; }
  thead { break-inside: avoid; page-break-inside: avoid; break-after: avoid; page-break-after: avoid; }
  tr { page-break-inside: avoid; break-inside: avoid; }
}

/* ── Top bar ── */
.top-bar{display:flex;justify-content:space-between;align-items:center;border-bottom:2.5px solid #16a34a;padding-bottom:6px;margin-bottom:10px;}
.top-bar .breadcrumb{font-size:7pt;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.5px;}
.top-bar .company{font-size:7.5pt;color:#374151;font-weight:700;text-transform:uppercase;}

/* ── Encabezado principal ── */
.main-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;}
.main-header h1{font-size:22pt;font-weight:900;color:#0f172a;text-transform:uppercase;letter-spacing:-0.5px;line-height:1.05;}
.main-header .subtitle{font-size:8pt;color:#64748b;margin-top:4px;font-weight:400;}
.logo-box{display:inline-flex;align-items:center;height:45px;}
.logo-box img{max-height:100%;object-fit:contain;}

/* ── Stats cards ── */
.stats-row{display:flex;gap:8px;margin-bottom:14px;}
.stat-card{flex:1;border:1.5px solid #e2e8f0;border-radius:10px;padding:9px 14px;}
.stat-card.green{border-color:#bbf7d0;background:#f0fdf4;}
.stat-card.blue{border-color:#bfdbfe;background:#eff6ff;}
.stat-card.purple{border-color:#ddd6fe;background:#f5f3ff;}
.stat-card.gold{border-color:#fde68a;background:#fffbeb;}
.stat-card .slabel{font-size:6.5pt;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;}
.stat-card .svalue{font-size:14pt;font-weight:800;color:#0f172a;}
.stat-card.green .svalue{color:#16a34a;}
.stat-card.blue .svalue{color:#0369a1;}
.stat-card.purple .svalue{color:#7c3aed;}
.stat-card.gold .svalue{color:#b45309;}

/* ── Categoría header integrado ── */
.cat-header-cell{background:#008e4d !important;color:#fff !important;padding:8px 14px !important;font-size:8.5pt;font-weight:800;text-transform:uppercase;letter-spacing:.6px;border:none !important;text-align:left;}
.cat-count{font-size:7.5pt;font-weight:500;opacity:.8;float:right;}

/* ── Tabla ── */
table{width:100%;border-collapse:collapse;margin-bottom:24px;}
.col-header-row th{padding:8px 9px;font-size:7pt;font-weight:700;text-transform:uppercase;letter-spacing:.4px;border:none;background:#0f172a !important;color:#fff !important;vertical-align:middle;text-align:center;}
th.th-sku{text-align:left;width:75px;}
th.th-desc{text-align:left;}

/* Spacer rows para print margin 0 (superior e inferior) */
tr.print-spacer { height: 10mm; background: #fff !important; border: none !important; }
tr.print-spacer td { border: none !important; padding: 0 !important; background: #fff !important; }

/* Subheader neto/total */
.th-sub{font-weight:400;font-size:6.5pt;opacity:.85;display:block;margin-top:2px;}

/* Filas */
tbody tr:nth-child(odd) td{background:#fff;}
tbody tr:nth-child(even) td{background:#f8fafc;}
tbody tr:hover td{background:#f0fdf4;}
tbody td{padding:7px 9px;font-size:8pt;color:#1e293b;border:1px solid #e2e8f0;vertical-align:middle;}

td.td-sku{font-weight:800;color:#111827;font-size:8pt;white-space:nowrap;text-align:left;}
td.td-desc .prod-name{font-weight:700;font-size:8.5pt;color:#0f172a;}
td.td-desc .prod-model{font-size:7pt;color:#64748b;margin-top:1px;font-style:italic;}
td.td-precio{text-align:right;}
td.td-precio .p-total{font-weight:800;font-size:9.5pt;color:#111827;}
td.td-precio .p-neto{font-size:7pt;color:#64748b;margin-top:1px;}
td.td-precio.no-precio{text-align:center;color:#cbd5e1;font-size:9pt;}

/* ── Pie ── */
.footer{margin-top:14px;padding-top:8px;border-top:1px solid #e2e8f0;display:flex;justify-content:space-between;font-size:6.5pt;color:#94a3b8;}
</style>
</head>
<body>

<!-- Wrapper principal con padding para simular margenes de @page -->
<div style="padding: 12mm 10mm;">

<div class="no-print">
  <button class="btn-pdf" onclick="window.print()">🖨 Imprimir / Guardar PDF</button>
  <button class="btn-close" onclick="window.close()">✕ Cerrar</button>
</div>

<!-- Top bar -->
<div class="top-bar">
  <span class="breadcrumb">Catálogo de Productos y Lista de Precios por SKU</span>
  <span class="company">Prelisto / <?= esc($empresa) ?></span>
</div>

<!-- Encabezado -->
<div class="main-header">
  <div>
    <h1>Catálogo y Lista de Precios por SKU</h1>
    <p class="subtitle">Portafolio Institucional y Matriz de Precios Diferenciados | <?= esc($empresa) ?></p>
  </div>
  <div class="logo-box">
    <img src="<?= base_url('public/assets/img/logo_empresa.png') ?>" alt="Logo">
  </div>
</div>

<!-- Stats -->
<div class="stats-row">
  <div class="stat-card green">
    <div class="slabel">Total Productos</div>
    <div class="svalue"><?= $totalSkus ?> SKUs</div>
  </div>
  <div class="stat-card blue">
    <div class="slabel">Categorías</div>
    <div class="svalue"><?= $totalCats ?> Líneas</div>
  </div>
  <div class="stat-card purple">
    <div class="slabel">Listas de Precios</div>
    <div class="svalue"><?= $totalListas ?> Listas</div>
  </div>
  <div class="stat-card gold">
    <div class="slabel">Moneda &amp; Impuestos</div>
    <div class="svalue">CLP (Neto / Total)</div>
  </div>
</div>

  <?php $cIdx = 0; foreach ($porCategoria as $categoria => $productos): ?>
  <?php $numProds = count($productos); ?>
  <table class="<?= $numProds <= 12 ? 'avoid-break' : '' ?>">
    <thead>
      <!-- Spacer invisible superior para cuando la tabla salta de página o inicia -->
      <tr class="print-spacer"><th colspan="<?= 2 + count($listasConfig) ?>" style="border:none !important; background:#fff !important; padding:0 !important;"></th></tr>
      <!-- Fila de Categoría integrada en el thead -->
      <tr>
        <th colspan="<?= 2 + count($listasConfig) ?>" class="cat-header-cell">
          CATEGORÍA: <?= esc(strtoupper($categoria)) ?> 
          <span class="cat-count">(<?= count($productos) ?> Productos)</span>
        </th>
      </tr>
      <!-- Fila de Cabeceras de Columnas -->
      <tr class="col-header-row">
        <th class="th-sku">SKU</th>
        <th class="th-desc">Descripción del Producto</th>
        <?php foreach ($listasConfig as $keyLista => $cfg): ?>
        <th>
          <?= esc($cfg['label']) ?>
          <span class="th-sub">(Neto / Total)</span>
        </th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($productos as $p): ?>
      <tr>
        <td class="td-sku"><?= esc($p['sku']) ?></td>
        <td class="td-desc">
          <div class="prod-name"><?= esc($p['nombre']) ?></div>
          <?php if (!empty($p['modelo'])): ?>
          <div class="prod-model">Modelo: <?= esc($p['modelo']) ?></div>
          <?php elseif (!empty($p['descripcion'])): ?>
          <div class="prod-model"><?= esc(mb_substr($p['descripcion'], 0, 60)) ?></div>
          <?php endif; ?>
        </td>
        
        <?php foreach ($listasConfig as $keyLista => $cfg):
          $pr = null;
          if (isset($preciosPorSku[$p['sku']])) {
              foreach ($cfg['aliases'] as $alias) {
                  if (isset($preciosPorSku[$p['sku']][$alias])) {
                      $pr = $preciosPorSku[$p['sku']][$alias];
                      break;
                  }
              }
          }
        ?>
        <?php if ($pr && ((float)$pr['total'] > 0 || (float)$pr['neto'] > 0)): ?>
        <td class="td-precio">
          <div class="p-total">$<?= number_format((float)$pr['total'], 0, ',', '.') ?></div>
          <div class="p-neto">N: $<?= number_format((float)$pr['neto'], 0, ',', '.') ?></div>
        </td>
        <?php else: ?>
        <td class="td-precio no-precio">—</td>
        <?php endif; ?>
        <?php endforeach; ?>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <!-- Spacer invisible inferior -->
      <tr class="print-spacer"><td colspan="<?= 2 + count($listasConfig) ?>"></td></tr>
    </tfoot>
  </table>
  <?php $cIdx++; endforeach; ?>

<div class="footer">
  <span>Documento generado el <?= $fechaGen ?> · Precios sujetos a modificación sin previo aviso</span>
  <span><?= esc($empresa) ?></span>
</div>

</div> <!-- end wrapper -->

</body>
</html>
<?php
        $html = ob_get_clean();
        return $this->response
            ->setHeader('Content-Type', 'text/html; charset=utf-8')
            ->setBody($html);
    }


    // ── GET /bodega/facto-productos/listar (API JSON) ──────────────
    public function listarFactoProductos(): ResponseInterface
    {
        $db = \Config\Database::connect();

        if (!$db->tableExists('tbl_facto_productos')) {
            return $this->response->setJSON([
                'success' => true,
                'data' => [],
                'stats' => ['total_skus' => 0, 'total_listas' => 0, 'stock_total' => 0, 'promedio_neto' => 0]
            ]);
        }

        $listaId = $this->request->getGet('lista_id');
        $q       = trim((string)$this->request->getGet('q'));

        $builder = $db->table('tbl_facto_productos');

        if ($listaId === 'all' || $listaId === 'todas') {
            // No filtrar por facto_lista_id para exportaciones completas
        } elseif (!empty($listaId) && is_numeric($listaId)) {
            $builder->where('facto_lista_id', (int)$listaId);
        } else {
            // Por defecto si viene vacío o no especificado: Precios base (facto_lista_id = 1)
            $builder->where('facto_lista_id', 1);
        }

        if (!empty($q)) {
            $builder->groupStart()
                    ->like('sku', $q)
                    ->orLike('nombre', $q)
                    ->orLike('descripcion', $q)
                    ->orLike('marca', $q)
                    ->groupEnd();
        }

        $productos = $builder->orderBy('nombre', 'ASC')
                             ->orderBy('nombre_lista', 'ASC')
                             ->get()->getResultArray();

        // Calcular Estadísticas
        $statsRaw = $db->query("SELECT 
            COUNT(DISTINCT sku) as total_skus,
            COUNT(DISTINCT facto_lista_id) as total_listas,
            SUM(DISTINCT stock) as stock_total,
            AVG(precio_neto) as promedio_neto
            FROM tbl_facto_productos")->getRowArray();

        return $this->response->setJSON([
            'success' => true,
            'data'    => $productos,
            'stats'   => [
                'total_skus'    => (int)($statsRaw['total_skus'] ?? 0),
                'total_listas'  => (int)($statsRaw['total_listas'] ?? 0),
                'stock_total'   => (float)($statsRaw['stock_total'] ?? 0),
                'promedio_neto' => (float)($statsRaw['promedio_neto'] ?? 0),
            ]
        ]);
    }
}
