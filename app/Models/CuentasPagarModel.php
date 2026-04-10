<?php
namespace App\Models;

use CodeIgniter\Model;

class CuentasPagarModel extends Model
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    /**
     * Retorna todos los documentos con impago > 0 de tbl_documentos_pagar
     * JOIN con tbl_proveedores para obtener nombre y razon_social.
     */
    public function obtenerPendientes(): array
    {
        $query = $this->db->query("
            SELECT
                dp.id,
                dp.rut_proveedor,
                COALESCE(p.razon_social, p.nombre, dp.rut_proveedor) AS nombre_proveedor,
                dp.tipo_documento,
                dp.numero,
                dp.fecha,
                dp.total,
                dp.pagado,
                dp.impago
            FROM tbl_documentos_pagar dp
            LEFT JOIN tbl_proveedores p ON p.rut = dp.rut_proveedor
            WHERE dp.impago > 0
            ORDER BY nombre_proveedor ASC, dp.fecha ASC
        ");
        return $query ? $query->getResultArray() : [];
    }

    /**
     * Total consolidado de impago en tbl_documentos_pagar.
     */
    public function totalImpago(): float
    {
        $row = $this->db->query("SELECT COALESCE(SUM(impago),0) AS total FROM tbl_documentos_pagar WHERE impago > 0")->getRowArray();
        return (float)($row['total'] ?? 0);
    }

    /**
     * Sincroniza (reemplaza) los documentos de pagar para una lista de proveedores.
     * Crea el proveedor en tbl_proveedores si no existe.
     */
    public function sincronizar(array $proveedores): array
    {
        $insertados   = 0;
        $actualizados = 0;
        $errores      = [];

        $this->db->transStart();
        try {
            foreach ($proveedores as $proveedor) {
                $rut    = trim($proveedor['rut'] ?? '');
                $nombre = trim($proveedor['emisor_receptor'] ?? $proveedor['nombre'] ?? '');
                $docs   = $proveedor['docs'] ?? [];

                if (empty($rut) || empty($docs)) continue;

                // Crear proveedor si no existe
                $existeProv = $this->db->table('tbl_proveedores')
                    ->where('rut', $rut)->countAllResults();
                if (!$existeProv) {
                    $this->db->table('tbl_proveedores')->insert([
                        'rut'         => $rut,
                        'nombre'      => $nombre,
                        'razon_social'=> $nombre,
                        'created_at'  => date('Y-m-d H:i:s'),
                    ]);
                }

                // Eliminar documentos anteriores de este proveedor para re-insertar
                $this->db->table('tbl_documentos_pagar')
                    ->where('rut_proveedor', $rut)->delete();

                foreach ($docs as $doc) {
                    $total  = (float)($doc['total']  ?? 0);
                    $pagado = (float)($doc['pagado'] ?? 0);
                    $impago = isset($doc['impago']) ? (float)$doc['impago'] : max(0, $total - $pagado);

                    $this->db->table('tbl_documentos_pagar')->insert([
                        'rut_proveedor'  => $rut,
                        'tipo_documento' => $doc['tipo_documento'] ?? $doc['tipo'] ?? 'Sin tipo',
                        'numero'         => (string)($doc['numero'] ?? $doc['nro'] ?? ''),
                        'fecha'          => $this->parsearFecha($doc['fecha'] ?? ''),
                        'total'          => $total,
                        'pagado'         => $pagado,
                        'impago'         => $impago,
                        'created_at'     => date('Y-m-d H:i:s'),
                    ]);
                    $insertados++;
                }
                $actualizados++;
            }
            $this->db->transComplete();
        } catch (\Exception $e) {
            $this->db->transRollback();
            $errores[] = $e->getMessage();
        }

        return ['insertados' => $insertados, 'actualizados' => $actualizados, 'errores' => $errores];
    }

    /**
     * Elimina todos los documentos de pagar de un proveedor por RUT.
     */
    public function eliminarPorProveedor(string $rut): int
    {
        $this->db->table('tbl_documentos_pagar')->where('rut_proveedor', $rut)->delete();
        return $this->db->affectedRows();
    }

    private function parsearFecha(string $rawFecha): string
    {
        if (empty($rawFecha) || $rawFecha === '-' || $rawFecha === 'â€”') {
            return date('Y-m-d');
        }
        foreach (['d-m-Y', 'd/m/Y', 'Y-m-d', 'Y/m/d', 'm/d/Y'] as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, trim($rawFecha));
            if ($dt !== false) return $dt->format('Y-m-d');
        }
        return date('Y-m-d');
    }
}