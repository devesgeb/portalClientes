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
        $v = str_replace(['$', ' ', '.'], '', (string)$v);
        $v = str_replace(',', '.', $v);
        return is_numeric($v) ? round((float)$v, 2) : null;
    }
}
