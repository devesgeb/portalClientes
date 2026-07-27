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
                dp.impago,
                dp.comentario
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
     * Elimina abonos huerfanos al re-sincronizar.
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
                        'rut'          => $rut,
                        'nombre'       => $nombre,
                        'razon_social' => $nombre,
                        'created_at'   => date('Y-m-d H:i:s'),
                    ]);
                }

                // Eliminar abonos huerfanos de docs de este proveedor
                $docsExistentes = $this->db->table('tbl_documentos_pagar')
                    ->where('rut_proveedor', $rut)->get()->getResultArray();
                $idsExistentes = array_column($docsExistentes, 'id');
                if (!empty($idsExistentes)) {
                    $this->db->table('tbl_abonos_pagar')
                        ->whereIn('doc_id', $idsExistentes)->delete();
                }

                // Eliminar documentos anteriores para re-insertar
                $this->db->table('tbl_documentos_pagar')
                    ->where('rut_proveedor', $rut)->delete();

                foreach ($docs as $doc) {
                    $total      = (float)($doc['total']  ?? 0);
                    $pagado     = (float)($doc['pagado'] ?? 0);
                    $impago     = isset($doc['impago']) ? (float)$doc['impago'] : max(0, $total - $pagado);
                    $comentario = trim($doc['comentario'] ?? '');

                    $this->db->table('tbl_documentos_pagar')->insert([
                        'rut_proveedor'  => $rut,
                        'tipo_documento' => $doc['tipo_documento'] ?? $doc['tipo'] ?? 'Sin tipo',
                        'numero'         => (string)($doc['numero'] ?? $doc['nro'] ?? ''),
                        'fecha'          => $this->parsearFecha($doc['fecha'] ?? ''),
                        'total'          => $total,
                        'pagado'         => $pagado,
                        'impago'         => $impago,
                        'comentario'     => $comentario,
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
     * Tambien elimina los abonos asociados.
     */
    public function eliminarPorProveedor(string $rut): int
    {
        $docs = $this->db->table('tbl_documentos_pagar')
            ->where('rut_proveedor', $rut)->get()->getResultArray();
        $ids = array_column($docs, 'id');
        if (!empty($ids)) {
            $this->db->table('tbl_abonos_pagar')->whereIn('doc_id', $ids)->delete();
        }
        $this->db->table('tbl_documentos_pagar')->where('rut_proveedor', $rut)->delete();
        return $this->db->affectedRows();
    }

    // ──────────────────────────────────────────────────────────────
    //  ABONOS
    // ──────────────────────────────────────────────────────────────

    /**
     * Retorna los abonos de un documento ordenados por fecha.
     */
    public function obtenerAbonos(int $docId): array
    {
        $query = $this->db->query(
            "SELECT id, doc_id, monto, fecha, comentario, created_at
             FROM tbl_abonos_pagar
             WHERE doc_id = ?
             ORDER BY fecha ASC, id ASC",
            [$docId]
        );
        return $query ? $query->getResultArray() : [];
    }

    /**
     * Registra un abono y recalcula pagado/impago en el documento.
     * Retorna el id del abono insertado.
     */
    public function registrarAbono(int $docId, float $monto, string $fecha, string $comentario = ''): int
    {
        $this->db->transStart();

        $this->db->table('tbl_abonos_pagar')->insert([
            'doc_id'     => $docId,
            'monto'      => $monto,
            'fecha'      => $fecha,
            'comentario' => trim($comentario),
        ]);
        $abonoId = $this->db->insertID();

        $this->_recalcularPagado($docId);
        $this->db->transComplete();

        return $abonoId;
    }

    /**
     * Elimina un abono y recalcula pagado/impago en el documento.
     */
    public function eliminarAbono(int $abonoId): bool
    {
        $abono = $this->db->table('tbl_abonos_pagar')
            ->where('id', $abonoId)->get()->getRowArray();
        if (!$abono) return false;

        $docId = (int)$abono['doc_id'];

        $this->db->transStart();
        $this->db->table('tbl_abonos_pagar')->where('id', $abonoId)->delete();
        $this->_recalcularPagado($docId);
        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * Actualiza el comentario de un documento.
     */
    public function actualizarComentarioDoc(int $docId, string $comentario): bool
    {
        $this->db->table('tbl_documentos_pagar')
            ->where('id', $docId)
            ->update(['comentario' => trim($comentario)]);
        return $this->db->affectedRows() >= 0;
    }

    /**
     * Recalcula pagado e impago de un documento sumando sus abonos.
     */
    private function _recalcularPagado(int $docId): void
    {
        $this->db->query(
            "UPDATE tbl_documentos_pagar
             SET pagado = COALESCE((SELECT SUM(monto) FROM tbl_abonos_pagar WHERE doc_id = ?), 0),
                 impago = GREATEST(0, total - COALESCE((SELECT SUM(monto) FROM tbl_abonos_pagar WHERE doc_id = ?), 0))
             WHERE id = ?",
            [$docId, $docId, $docId]
        );
    }

    private function parsearFecha(string $rawFecha): string
    {
        if (empty($rawFecha) || $rawFecha === '-' || $rawFecha === "\xe2\x80\x94") {
            return date('Y-m-d');
        }
        foreach (['d-m-Y', 'd/m/Y', 'Y-m-d', 'Y/m/d', 'm/d/Y'] as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, trim($rawFecha));
            if ($dt !== false) return $dt->format('Y-m-d');
        }
        return date('Y-m-d');
    }
}