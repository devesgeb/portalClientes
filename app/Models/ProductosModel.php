<?php
namespace App\Models;

use CodeIgniter\Model;

class ProductosModel extends Model
{
    protected $table      = 'tbl_productos';
    protected $primaryKey = 'sku';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'sku', 'categoria', 'nombre', 'marca', 'modelo', 'unidad',
        'codigo_barras', 'tipo', 'costo_neto', 'precio_venta_neto',
        'monto_iva', 'precio_venta_total', 'stock_minimo',
        'stock_bodega_ppral', 'stock_bodega_sec', 'stock_reservado',
        'comision_vendedor', 'descripcion', 'descripcion_ecommerce', 'activo',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Enum válidos
    private const UNIDADES = ['KG','CAJA','UN','LT','GR','MT','PAQ','OTR'];
    private const UNIDAD_MAP = [
        'KG'=>'KG','KILO'=>'KG','KILOS'=>'KG',
        'CAJA'=>'CAJA','CAJAS'=>'CAJA',
        'UN'=>'UN','UNI'=>'UN','UNIDAD'=>'UN','UNIDADES'=>'UN',
        'LT'=>'LT','LITRO'=>'LT','LITROS'=>'LT',
        'GR'=>'GR','GRAMO'=>'GR','GRAMOS'=>'GR',
        'MT'=>'MT','METRO'=>'MT','METROS'=>'MT',
        'PAQ'=>'PAQ','PAQUETE'=>'PAQ',
    ];

    /**
     * Importación masiva desde JSON (SheetJS).
     * Recibe array de filas con cabeceras del Excel y las normaliza.
     * Usa INSERT … ON DUPLICATE KEY UPDATE para upsert por SKU.
     *
     * @param  array $rows  Filas del Excel ya parseadas por JS
     * @return array        ['insertados'=>int,'actualizados'=>int,'errores'=>array]
     */
    public function importar(array $rows): array
    {
        $db = \Config\Database::connect();

        $insertados  = 0;
        $actualizados = 0;
        $errores     = [];

        // Mapa de cabeceras Excel (minúsculas sin acento) → campo BD
        $map = [
            'sku'                                   => 'sku',
            'categoria'                             => 'categoria',
            'nombre'                                => 'nombre',
            'marca'                                 => 'marca',
            'modelo'                                => 'modelo',
            'unidad'                                => 'unidad',
            'código de barras'                      => 'codigo_barras',
            'codigo de barras'                      => 'codigo_barras',
            'producto / servicio'                   => 'tipo',
            'costo neto'                            => 'costo_neto',
            'venta: precio neto'                    => 'precio_venta_neto',
            'venta: monto iva'                      => 'monto_iva',
            'venta: precio total'                   => 'precio_venta_total',
            'stock mínimo'                          => 'stock_minimo',
            'stock minimo'                          => 'stock_minimo',
            'descripción'                           => 'descripcion',
            'descripcion'                           => 'descripcion',
            'comisión vendedor'                     => 'comision_vendedor',
            'comision vendedor'                     => 'comision_vendedor',
            'descripción ecommerce'                 => 'descripcion_ecommerce',
            'descripcion ecommerce'                 => 'descripcion_ecommerce',
            'disponibilidad en: bodega independencia' => 'stock_bodega_ppral',
            'disponibilidad en: ditron'             => 'stock_bodega_sec',
            'disponibilidad en: ditron '            => 'stock_bodega_sec',
            'disponibilidad en: stock reservados'   => 'stock_reservado',
        ];

        foreach ($rows as $i => $row) {
            // Normalizar claves de la fila
            $nr = [];
            foreach ($row as $k => $v) {
                $kn = strtolower(trim($k));
                // Quitar acentos básicos para mayor tolerancia
                $kn = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $kn);
                if (isset($map[$kn])) {
                    $nr[$map[$kn]] = ($v === '' || $v === null) ? null : $v;
                }
            }

            $sku = trim($nr['sku'] ?? '');
            if ($sku === '') {
                $errores[] = "Fila " . ($i + 2) . ": SKU vacío, omitida.";
                continue;
            }

            // Normalizar unidad al ENUM
            $rawU = strtoupper(trim($nr['unidad'] ?? 'UN'));
            $nr['unidad'] = self::UNIDAD_MAP[$rawU] ?? 'OTR';

            // Normalizar tipo al ENUM
            $rawT = strtolower(trim($nr['tipo'] ?? 'producto'));
            $nr['tipo'] = (strpos($rawT, 'serv') !== false) ? 'servicio' : 'producto';

            // Limpiar/convertir numéricos
            foreach (['costo_neto','precio_venta_neto','monto_iva','precio_venta_total','comision_vendedor'] as $f) {
                if (isset($nr[$f])) $nr[$f] = $this->toDecimal($nr[$f]);
            }
            foreach (['stock_minimo','stock_bodega_ppral','stock_bodega_sec','stock_reservado'] as $f) {
                $nr[$f] = $this->toDecimal($nr[$f] ?? 0) ?? 0;
            }

            // ── Upsert ───────────────────────────────────────────────
            try {
                $existe = $db->table('tbl_productos')->where('sku', $sku)->countAllResults();

                if ($existe) {
                    $upd = $nr;
                    unset($upd['sku']); // no actualizar PK
                    $db->table('tbl_productos')->where('sku', $sku)->update($upd);
                    $actualizados++;
                } else {
                    $nr['sku'] = $sku;
                    $db->table('tbl_productos')->insert($nr);
                    $insertados++;
                }

                // Sincronizar también en tbl_facto_productos para respaldar la vista principal y Balance Diario si la API no responde
                if ($db->tableExists('tbl_facto_productos')) {
                    $factoSql = "INSERT INTO tbl_facto_productos 
                        (facto_product_id, sku, nombre, descripcion, marca, modelo, unidad_medida, stock, stock_reservado, costo_neto, costo_total, facto_lista_id, nombre_lista, precio_neto, precio_impuesto, precio_total)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'Precios base', ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                          nombre = VALUES(nombre),
                          descripcion = VALUES(descripcion),
                          marca = VALUES(marca),
                          modelo = VALUES(modelo),
                          unidad_medida = VALUES(unidad_medida),
                          stock = VALUES(stock),
                          stock_reservado = VALUES(stock_reservado),
                          costo_neto = VALUES(costo_neto),
                          costo_total = VALUES(costo_total),
                          precio_neto = VALUES(precio_neto),
                          precio_impuesto = VALUES(precio_impuesto),
                          precio_total = VALUES(precio_total),
                          updated_at = CURRENT_TIMESTAMP";
                    
                    $factoId    = abs(crc32($sku)) % 80000000 + 1000;
                    $costoNeto  = $nr['costo_neto'] ?? 0;
                    $costoTotal = round($costoNeto * 1.19, 2);
                    $precioNeto = $nr['precio_venta_neto'] ?? 0;
                    $precioTotal= $nr['precio_venta_total'] ?? round($precioNeto * 1.19, 2);
                    $precioImp  = $nr['monto_iva'] ?? round($precioNeto * 0.19, 2);

                    $db->query($factoSql, [
                        $factoId,
                        $sku,
                        $nr['nombre'] ?? $sku,
                        $nr['descripcion'] ?? '',
                        $nr['marca'] ?? '',
                        $nr['modelo'] ?? '',
                        $nr['unidad'] ?? 'UN',
                        $nr['stock_bodega_ppral'] ?? 0,
                        $nr['stock_reservado'] ?? 0,
                        $costoNeto,
                        $costoTotal,
                        $precioNeto,
                        $precioImp,
                        $precioTotal
                    ]);
                }
            } catch (\Exception $e) {
                $errores[] = "Fila " . ($i + 2) . " (SKU=$sku): " . $e->getMessage();
            }
        }

        return compact('insertados', 'actualizados', 'errores');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
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

    /** Obtener todos los productos activos para listado */
    public function listar(): array
    {
        return $this->where('activo', 1)->orderBy('categoria', 'ASC')->orderBy('nombre', 'ASC')->findAll();
    }
}
