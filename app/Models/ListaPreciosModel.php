<?php
namespace App\Models;

use CodeIgniter\Model;

class ListaPreciosModel extends Model
{
    protected $table      = 'tbl_listaPrecios';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'sku', 'lista', 'precio_neto', 'condicion_iva',
        'monto_iva', 'precio_total', 'activo',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Nombres de listas conocidas (para validación heurística)
    private const LISTAS_VALIDAS = [
        'Precios base', 'Mayorista', 'Horeca',
        'Distribucion detallista', 'Distribucion Mayorista',
        'Lista especial 1', 'lista especial 2',
    ];

    /**
     * Importación masiva de listas de precio desde JSON (SheetJS).
     * Clave única: (sku + lista)  →  ON DUPLICATE UPDATE.
     *
     * @param  array $rows   Filas parseadas por JS desde el Excel
     * @return array         ['insertados','actualizados','errores','skuNoExiste']
     */
    public function importar(array $rows): array
    {
        $db = \Config\Database::connect();

        $insertados   = 0;
        $actualizados = 0;
        $errores      = [];
        $skuNoExiste  = [];

        // Mapa cabeceras Excel (lower sin acento) → campo BD
        $map = [
            'sku'               => 'sku',
            'lista de precios'  => 'lista',
            'lista'             => 'lista',
            'precio neto'       => 'precio_neto',
            'afecto/exento iva' => 'condicion_iva',
            'afecto/exento'     => 'condicion_iva',
            'condicion iva'     => 'condicion_iva',
            'condición iva'     => 'condicion_iva',
            'monto iva'         => 'monto_iva',
            'precio total'      => 'precio_total',
        ];

        // Cache de SKUs existentes para validar FK sin query por fila
        $skusExistentes = array_column(
            $db->table('tbl_productos')->select('sku')->get()->getResultArray(),
            'sku'
        );
        $skusSet = array_flip($skusExistentes);

        foreach ($rows as $i => $row) {
            // Normalizar claves
            $nr = [];
            foreach ($row as $k => $v) {
                $kn = strtolower(trim($k));
                $kn = str_replace(
                    ['á','é','í','ó','ú','ü'],
                    ['a','e','i','o','u','u'], $kn
                );
                if (isset($map[$kn])) {
                    $nr[$map[$kn]] = ($v === '' || $v === null) ? null : $v;
                }
            }

            $sku   = trim($nr['sku']   ?? '');
            $lista = trim($nr['lista'] ?? '');

            if ($sku === '') {
                $errores[] = "Fila " . ($i + 2) . ": SKU vacío, omitida.";
                continue;
            }
            if ($lista === '') {
                $errores[] = "Fila " . ($i + 2) . " (SKU=$sku): nombre de lista vacío, omitida.";
                continue;
            }

            // Validar que el SKU exista en tbl_productos
            if (!isset($skusSet[$sku])) {
                $skuNoExiste[] = $sku;
                $errores[] = "Fila " . ($i + 2) . ": SKU '$sku' no existe en el maestro de productos.";
                continue;
            }

            // Normalizar condicion_iva
            $rawIva = strtolower(trim($nr['condicion_iva'] ?? 'afecto'));
            $nr['condicion_iva'] = str_contains($rawIva, 'exento') ? 'exento' : 'afecto';

            // Limpiar numéricos
            foreach (['precio_neto', 'monto_iva', 'precio_total'] as $f) {
                $nr[$f] = $this->toDecimal($nr[$f] ?? null);
            }
            $nr['sku']   = $sku;
            $nr['lista'] = $lista;

            // ── Upsert por (sku + lista) ──────────────────────────────
            try {
                $existe = $db->table('tbl_listaPrecios')
                             ->where('sku', $sku)
                             ->where('lista', $lista)
                             ->countAllResults();

                if ($existe) {
                    $upd = $nr;
                    unset($upd['sku'], $upd['lista']);
                    $db->table('tbl_listaPrecios')
                       ->where('sku', $sku)
                       ->where('lista', $lista)
                       ->update($upd);
                    $actualizados++;
                } else {
                    $db->table('tbl_listaPrecios')->insert($nr);
                    $insertados++;
                }

                // Sincronizar también en tbl_facto_productos y tbl_facto_listas_precios
                if ($db->tableExists('tbl_facto_productos')) {
                    $mapListasId = [
                        'precios base' => 1, 'base' => 1,
                        'horeca' => 11,
                        'mayorista' => 12,
                        'distribucion detallista' => 14, 'distribución detallista' => 14,
                        'distribucion mayorista' => 13, 'distribución mayorista' => 13,
                    ];

                    $listaLower = strtolower($lista);
                    $factoListaId = $mapListasId[$listaLower] ?? (abs(crc32($listaLower)) % 90 + 20);

                    if ($db->tableExists('tbl_facto_listas_precios')) {
                        $db->query("INSERT INTO tbl_facto_listas_precios (facto_lista_id, nombre) VALUES (?, ?) ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)", [$factoListaId, $lista]);
                    }

                    $baseProd = $db->table('tbl_productos')->where('sku', $sku)->get()->getRowArray();
                    $factoProd = $db->table('tbl_facto_productos')->where('sku', $sku)->get()->getRowArray();

                    $factoId = $factoProd['facto_product_id'] ?? (abs(crc32($sku)) % 80000000 + 1000);
                    $nombre  = $factoProd['nombre'] ?? $baseProd['nombre'] ?? $sku;
                    $desc    = $factoProd['descripcion'] ?? $baseProd['descripcion'] ?? '';
                    $marca   = $factoProd['marca'] ?? $baseProd['marca'] ?? '';
                    $modelo  = $factoProd['modelo'] ?? $baseProd['modelo'] ?? '';
                    $unidad  = $factoProd['unidad_medida'] ?? $baseProd['unidad'] ?? 'UN';
                    $stock   = $factoProd['stock'] ?? $baseProd['stock_bodega_ppral'] ?? 0;
                    $reser   = $factoProd['stock_reservado'] ?? $baseProd['stock_reservado'] ?? 0;
                    $cNeto   = $factoProd['costo_neto'] ?? $baseProd['costo_neto'] ?? 0;
                    $cTotal  = round($cNeto * 1.19, 2);

                    $pNeto   = $nr['precio_neto'] ?? 0;
                    $pTotal  = $nr['precio_total'] ?? round($pNeto * 1.19, 2);
                    $pImp    = $nr['monto_iva'] ?? round($pNeto * 0.19, 2);

                    $sqlFactoLP = "INSERT INTO tbl_facto_productos
                        (facto_product_id, sku, nombre, descripcion, marca, modelo, unidad_medida, stock, stock_reservado, costo_neto, costo_total, facto_lista_id, nombre_lista, precio_neto, precio_impuesto, precio_total)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                          nombre = VALUES(nombre),
                          nombre_lista = VALUES(nombre_lista),
                          precio_neto = VALUES(precio_neto),
                          precio_impuesto = VALUES(precio_impuesto),
                          precio_total = VALUES(precio_total),
                          updated_at = CURRENT_TIMESTAMP";

                    $db->query($sqlFactoLP, [
                        $factoId, $sku, $nombre, $desc, $marca, $modelo, $unidad, $stock, $reser, $cNeto, $cTotal, $factoListaId, $lista, $pNeto, $pImp, $pTotal
                    ]);
                }
            } catch (\Exception $e) {
                $errores[] = "Fila " . ($i + 2) . " ($sku / $lista): " . $e->getMessage();
            }
        }

        return compact('insertados', 'actualizados', 'errores', 'skuNoExiste');
    }

    // ── Helper ────────────────────────────────────────────────────
    private function toDecimal($v): ?float
    {
        if ($v === null || $v === '') return null;
        if (is_float($v) || is_int($v)) return round((float)$v, 2);
        
        $v = trim((string)$v);
        $v = str_replace(['$', ' '], '', $v);
        
        if (strpos($v, ',') !== false && strpos($v, '.') !== false) {
            if (strrpos($v, ',') > strrpos($v, '.')) {
                $v = str_replace('.', '', $v);
                $v = str_replace(',', '.', $v);
            } else {
                $v = str_replace(',', '', $v);
            }
        } elseif (strpos($v, ',') !== false) {
            if (substr_count($v, ',') > 1) {
                $v = str_replace(',', '', $v);
            } else {
                $v = str_replace(',', '.', $v);
            }
        } elseif (strpos($v, '.') !== false) {
            if (substr_count($v, '.') > 1) {
                $v = str_replace('.', '', $v);
            }
        }
        
        return is_numeric($v) ? round((float)$v, 2) : null;
    }
}
