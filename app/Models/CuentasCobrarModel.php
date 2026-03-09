<?php

namespace App\Models;

use CodeIgniter\Model;

class CuentasCobrarModel extends Model
{
    protected $table            = 'tbl_cuentasCobrar';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'tipo_documento', 'fecha', 'numero',
        'emisor_receptor', 'rut',
        'total', 'pagado', 'impago',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'tipo_documento'  => 'required|max_length[120]',
        'fecha'           => 'required|valid_date[Y-m-d]',
        'numero'          => 'required|max_length[50]',
        'emisor_receptor' => 'required|max_length[200]',
        'rut'             => 'permit_empty|max_length[20]',
        'total'           => 'required|decimal',
        'pagado'          => 'required|decimal',
        'impago'          => 'required|decimal',
    ];

    public function insertarRegistros(array $registros): int
    {
        if (empty($registros)) return 0;
        $resultado = $this->insertBatch($registros);
        if ($resultado === false) {
            throw new \RuntimeException('Error al insertar registros: ' . implode(', ', $this->errors()));
        }
        return $resultado;
    }

    /** Retorna TODOS los registros (sin filtro de impago) */
    public function obtenerPendientes(): array
    {
        return $this->orderBy('emisor_receptor', 'ASC')
                    ->orderBy('fecha', 'DESC')
                    ->findAll();
    }

    public function contarPorCliente(string $emisorReceptor): int
    {
        return (int) $this->where('emisor_receptor', $emisorReceptor)->countAllResults();
    }

    public function eliminarPorCliente(string $emisorReceptor): int
    {
        $this->where('emisor_receptor', $emisorReceptor)->delete();
        return $this->db->affectedRows();
    }

    public function totalImpago(): float
    {
        $row = $this->selectSum('impago')->get()->getRowArray();
        return (float) ($row['impago'] ?? 0);
    }

    /**
     * Sincronizacion completa: compara pantalla vs BD.
     * 1. Borra clientes que ya no estan en la pantalla.
     * 2. Para cada cliente: borra sus docs y reinserta los actuales.
     */
    public function sincronizar(array $clientesActuales): array
    {
        $db = $this->db;
        $db->transStart();

        $nombresActuales = array_column($clientesActuales, 'emisor_receptor');

        $query     = $db->query("SELECT DISTINCT emisor_receptor FROM `{$this->table}`");
        $nombresBD = $query ? array_column($query->getResultArray(), 'emisor_receptor') : [];

        $eliminadosClientes = 0;
        foreach ($nombresBD as $nombre) {
            if (!in_array($nombre, $nombresActuales, true)) {
                $db->table($this->table)->where('emisor_receptor', $nombre)->delete();
                $eliminadosClientes++;
            }
        }

        $totalInsertados       = 0;
        $clientesSincronizados = 0;

        foreach ($clientesActuales as $cliente) {
            $emisor = $cliente['emisor_receptor'] ?? '';
            $docs   = $cliente['docs'] ?? [];

            if (empty($emisor) || empty($docs)) continue;

            $db->table($this->table)->where('emisor_receptor', $emisor)->delete();

            $filas = [];
            foreach ($docs as $doc) {
                $total   = (float)($doc['total']  ?? 0);
                $pagado  = (float)($doc['pagado'] ?? 0);
                $impago  = isset($doc['impago']) ? (float)$doc['impago'] : max(0, $total - $pagado);

                $filas[] = [
                    'tipo_documento'  => trim(substr($doc['tipo_documento'] ?? 'Sin tipo', 0, 120)),
                    'fecha'           => $doc['fecha'] ?? date('Y-m-d'),
                    'numero'          => trim(substr((string)($doc['numero'] ?? ''), 0, 50)),
                    'emisor_receptor' => trim(substr($emisor, 0, 200)),
                    'rut'             => trim(substr($doc['rut'] ?? '', 0, 20)),
                    'total'           => $total,
                    'pagado'          => $pagado,
                    'impago'          => $impago,
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
            throw new \RuntimeException('La transaccion de sincronizacion fallo.');
        }

        return [
            'insertados'             => $totalInsertados,
            'eliminados_clientes'    => $eliminadosClientes,
            'clientes_sincronizados' => $clientesSincronizados,
        ];
    }
}