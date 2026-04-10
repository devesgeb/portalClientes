<?php

namespace App\Models;

use CodeIgniter\Model;

class CuentasCobrarModel extends Model
{
    protected $table            = 'tbl_documentos_cobrar';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'rut_cliente', 'tipo_documento', 'numero',
        'fecha', 'total', 'pagado', 'impago',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'rut_cliente'    => 'required|max_length[20]',
        'tipo_documento' => 'required|max_length[120]',
        'numero'         => 'required|max_length[50]',
        'fecha'          => 'required|valid_date[Y-m-d]',
        'total'          => 'required|decimal',
        'pagado'         => 'required|decimal',
        'impago'         => 'required|decimal',
    ];

    public function insertarRegistros(array $registros): int
    {
        if (empty($registros)) return 0;
        $r = $this->insertBatch($registros);
        if ($r === false) throw new \RuntimeException('Error al insertar: ' . implode(', ', $this->errors()));
        return $r;
    }

    /**
     * Retorna todos los documentos con nombre del cliente via JOIN.
     * Alias nombre_cliente compatible con cargarCobrarDesdeBD() del JS.
     */
    public function obtenerPendientes(): array
    {
        $query = $this->db->query("
            SELECT d.*,
                COALESCE(c.razon_social, c.nombre, d.rut_cliente) AS nombre_cliente
            FROM `tbl_documentos_cobrar` d
            LEFT JOIN `tbl_clientes` c ON c.rut = d.rut_cliente
            WHERE d.impago > 0
        ORDER BY COALESCE(c.razon_social, c.nombre, d.rut_cliente) ASC, d.fecha DESC
        ");
        return $query ? $query->getResultArray() : [];
    }

    public function totalImpago(): float
    {
        $row = $this->selectSum('impago')->get()->getRowArray();
        return (float)($row['impago'] ?? 0);
    }

    public function contarPorCliente(string $rut): int
    {
        return (int)$this->where('rut_cliente', $rut)->countAllResults();
    }

    public function eliminarPorCliente(string $rut): int
    {
        $this->where('rut_cliente', $rut)->delete();
        return $this->db->affectedRows();
    }

    /**
     * Sincronización completa por rut_cliente.
     * Si el cliente no existe en tbl_clientes, lo crea automáticamente.
     *
     * Cada elemento de $clientesActuales debe tener:
     *   - rut_cliente   (string, requerido)
     *   - nombre_cliente (string, opcional — para auto-crear)
     *   - docs          (array)
     */
    public function sincronizar(array $clientesActuales): array
    {
        $db = $this->db;
        $db->transStart();

        $rutsActuales = array_column($clientesActuales, 'rut_cliente');
        $queryBD      = $db->query("SELECT DISTINCT rut_cliente FROM `{$this->table}`");
        $rutsBD       = $queryBD ? array_column($queryBD->getResultArray(), 'rut_cliente') : [];

        $eliminadosClientes = 0;
        foreach ($rutsBD as $rut) {
            if (!in_array($rut, $rutsActuales, true)) {
                $db->table($this->table)->where('rut_cliente', $rut)->delete();
                $eliminadosClientes++;
            }
        }

        $totalInsertados       = 0;
        $clientesSincronizados = 0;

        foreach ($clientesActuales as $cliente) {
            $rut    = trim($cliente['rut_cliente'] ?? '');
            $nombre = trim($cliente['nombre_cliente'] ?? $rut);
            $docs   = $cliente['docs'] ?? [];

            if (empty($rut) || empty($docs)) continue;

            // ── Auto-crear cliente en tbl_clientes si no existe (para respetar la FK) ──
            $existe = $db->table('tbl_clientes')->where('rut', $rut)->countAllResults();
            if (!$existe) {
                $db->table('tbl_clientes')->insert([
                    'rut'    => $rut,
                    'nombre' => $nombre ?: $rut,
                ]);
            }

            // Reemplazar documentos del cliente
            $db->table($this->table)->where('rut_cliente', $rut)->delete();

            $filas = [];
            foreach ($docs as $doc) {
                $total  = (float)($doc['total']  ?? 0);
                $pagado = (float)($doc['pagado'] ?? 0);
                $impago = isset($doc['impago']) ? (float)$doc['impago'] : max(0, $total - $pagado);

                $filas[] = [
                    'rut_cliente'    => trim(substr($rut, 0, 20)),
                    'tipo_documento' => trim(substr($doc['tipo_documento'] ?? 'Sin tipo', 0, 120)),
                    'fecha'          => $doc['fecha'] ?? date('Y-m-d'),
                    'numero'         => trim(substr((string)($doc['numero'] ?? ''), 0, 50)),
                    'total'          => $total,
                    'pagado'         => $pagado,
                    'impago'         => $impago,
                ];
            }

            if (!empty($filas)) {
                $db->table($this->table)->insertBatch($filas);
                $totalInsertados      += count($filas);
                $clientesSincronizados++;
            }
        }

        $db->transComplete();

        if (!$db->transStatus()) {
            throw new \RuntimeException('La transacción de sincronización falló.');
        }

        return [
            'insertados'             => $totalInsertados,
            'eliminados_clientes'    => $eliminadosClientes,
            'clientes_sincronizados' => $clientesSincronizados,
        ];
    }
}