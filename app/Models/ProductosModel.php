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
                    unset($nr['sku']); // no actualizar PK
                    $db->table('tbl_productos')->where('sku', $sku)->update($nr);
                    $actualizados++;
                } else {
                    $nr['sku'] = $sku;
                    $db->table('tbl_productos')->insert($nr);
                    $insertados++;
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
        // Limpiar separadores de miles (punto o coma) y normalizar decimales
        $v = str_replace(['$', ' ', '.'], ['', '', ''], (string)$v);
        $v = str_replace(',', '.', $v);
        return is_numeric($v) ? round((float)$v, 2) : null;
    }

    /** Obtener todos los productos activos para listado */
    public function listar(): array
    {
        return $this->where('activo', 1)->orderBy('categoria', 'ASC')->orderBy('nombre', 'ASC')->findAll();
    }
}
