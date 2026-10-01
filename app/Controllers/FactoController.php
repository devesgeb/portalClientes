<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class FactoController extends BaseController
{
    public function __construct()
    {
        $this->ensureTableExists();
    }

    /**
     * Asegura la existencia de la tabla local tbl_facto_pagos
     */
    private function ensureTableExists(): void
    {
        $db = \Config\Database::connect();
        $sql = "CREATE TABLE IF NOT EXISTS `tbl_facto_pagos` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `folio` VARCHAR(50) NOT NULL,
          `codigo_sii` INT NOT NULL DEFAULT 33,
          `rut_cliente` VARCHAR(20) NULL,
          `razon_social` VARCHAR(255) NULL,
          `monto_neto` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `monto_iva` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `monto_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `fecha_emision` DATE NULL,
          `estado_sii` VARCHAR(50) NULL,
          `estado_pago` ENUM('pendiente', 'pagada', 'parcial') NOT NULL DEFAULT 'pendiente',
          `monto_pagado` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `observacion` VARCHAR(255) NULL,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          UNIQUE KEY `uniq_facto_doc` (`folio`, `codigo_sii`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $db->query($sql);

        // Migración de columnas adicionales si no existen
        $cols = $db->getFieldNames('tbl_facto_pagos');
        if (!in_array('rut_cliente', $cols, true)) {
            $db->query("ALTER TABLE `tbl_facto_pagos` ADD COLUMN `rut_cliente` VARCHAR(20) NULL AFTER `codigo_sii`;");
        }
        if (!in_array('razon_social', $cols, true)) {
            $db->query("ALTER TABLE `tbl_facto_pagos` ADD COLUMN `razon_social` VARCHAR(255) NULL AFTER `rut_cliente`;");
        }
        if (!in_array('monto_neto', $cols, true)) {
            $db->query("ALTER TABLE `tbl_facto_pagos` ADD COLUMN `monto_neto` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `razon_social`;");
        }
        if (!in_array('monto_iva', $cols, true)) {
            $db->query("ALTER TABLE `tbl_facto_pagos` ADD COLUMN `monto_iva` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `monto_neto`;");
        }
        if (!in_array('monto_total', $cols, true)) {
            $db->query("ALTER TABLE `tbl_facto_pagos` ADD COLUMN `monto_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `monto_iva`;");
        }
        if (!in_array('fecha_emision', $cols, true)) {
            $db->query("ALTER TABLE `tbl_facto_pagos` ADD COLUMN `fecha_emision` DATE NULL AFTER `monto_total`;");
        }
        if (!in_array('estado_sii', $cols, true)) {
            $db->query("ALTER TABLE `tbl_facto_pagos` ADD COLUMN `estado_sii` VARCHAR(50) NULL AFTER `fecha_emision`;");
        }
    }

    /**
     * GET /cobranza/facturas-facto
     */
    public function facturasFacto()
    {
        return view('cobranza/facturas_facto', [
            'title' => 'Facturas y Guías Facto',
            'activePage' => 'facturas-facto',
            'base_url' => base_url(),
            'assets_url' => base_url('public/assets/'),
        ]);
    }

    /**
     * GET /cobranza/facto/buscar-dtes
     * Consulta directamente la tabla local tbl_facto_pagos (cargada/sincronizada por el Cron).
     */
    public function buscarDtes(): ResponseInterface
    {
        $db = \Config\Database::connect();
        $this->ensureTableExists();

        $fechaInicio   = $this->request->getGet('fecha_inicio');
        $fechaFin      = $this->request->getGet('fecha_fin');
        $numero        = trim((string) ($this->request->getGet('numero') ?? ''));
        $cliente       = trim((string) ($this->request->getGet('cliente') ?? ''));
        $tipoDte       = trim((string) ($this->request->getGet('tipo_dte') ?? ''));
        $estadoFiltro  = trim((string) ($this->request->getGet('estado_pago') ?? ''));

        // Si el usuario no especificó filtro de estado explícito, por defecto consulta 'todos' los documentos
        $targetEstado = ($estadoFiltro !== '') ? $estadoFiltro : 'todos';

        $builder = $db->table('tbl_facto_pagos');

        if ($numero !== '') {
            $builder->like('folio', $numero);
        }
        if ($cliente !== '') {
            $builder->groupStart()
                    ->like('rut_cliente', $cliente)
                    ->orLike('razon_social', $cliente)
                    ->groupEnd();
        }
        if ($tipoDte !== '') {
            $builder->where('codigo_sii', (int)$tipoDte);
        }
        if ($targetEstado !== 'todos') {
            $builder->where('estado_pago', $targetEstado);
        }
        if (!empty($fechaInicio) && !empty($fechaFin)) {
            $builder->groupStart()
                        ->groupStart()
                            ->where('fecha_emision >=', $fechaInicio)
                            ->where('fecha_emision <=', $fechaFin)
                        ->groupEnd()
                        ->orGroupStart()
                            ->where('DATE(created_at) >=', $fechaInicio)
                            ->where('DATE(created_at) <=', $fechaFin)
                        ->groupEnd()
                    ->groupEnd();
        } elseif (!empty($fechaInicio)) {
            $builder->groupStart()
                        ->where('fecha_emision >=', $fechaInicio)
                        ->orWhere('DATE(created_at) >=', $fechaInicio)
                    ->groupEnd();
        } elseif (!empty($fechaFin)) {
            $builder->groupStart()
                        ->where('fecha_emision <=', $fechaFin)
                        ->orWhere('DATE(created_at) <=', $fechaFin)
                    ->groupEnd();
        }

        $builder->orderBy('fecha_emision', 'DESC');
        $builder->orderBy('CAST(folio AS UNSIGNED)', 'DESC');

        $rows = $builder->get()->getResultArray();

        // Cargar folios cargados en Cuentas por Cobrar (tbl_documentos_cobrar)
        $cobrarFolios = [];
        if ($db->tableExists('tbl_documentos_cobrar')) {
            $rowsCobrar = $db->table('tbl_documentos_cobrar')->select('numero')->get()->getResultArray();
            foreach ($rowsCobrar as $c) {
                $fRaw   = trim((string)$c['numero']);
                $fClean = ltrim($fRaw, '0');
                if ($fRaw !== '' && $fRaw !== 'N/A') {
                    $cobrarFolios[$fRaw] = true;
                }
                if ($fClean !== '') {
                    $cobrarFolios[$fClean] = true;
                }
            }
        }

        $documentos = [];
        $totalMonto = 0.00;

        foreach ($rows as $r) {
            $taxBureauCode = (int)$r['codigo_sii'];
            $tipoNombre    = $this->getTipoDteNombre($taxBureauCode);
            $totalDoc      = (float)$r['monto_total'];
            $folioDoc      = trim((string)$r['folio']);
            $folioDocClean = ltrim($folioDoc, '0');

            $obs            = (string)($r['observacion'] ?? '');
            $isManualCargado = (
                strripos($obs, 'Cargado') !== false || 
                strripos($obs, 'Conciliado') !== false || 
                strripos($obs, 'Pagada') !== false || 
                strripos($obs, 'Cobrar') !== false ||
                $r['estado_pago'] === 'pagada'
            );
            $existeEnCobrar = isset($cobrarFolios[$folioDoc]) || isset($cobrarFolios[$folioDocClean]);
            $estadoFlujo   = ($existeEnCobrar || $isManualCargado) ? 'cargado' : 'pendiente';

            $documentos[] = [
                'id'             => $r['id'],
                'folio'          => $r['folio'],
                'fecha'          => $r['fecha_emision'] ?? '—',
                'codigo_sii'     => $taxBureauCode,
                'tipo_documento' => $tipoNombre,
                'cliente_rut'    => $r['rut_cliente'] ?: '—',
                'cliente_nombre' => $r['razon_social'] ?: '—',
                'neto'           => (float)$r['monto_neto'],
                'iva'            => (float)$r['monto_iva'],
                'total'          => $totalDoc,
                'estado_sii'     => $r['estado_sii'] ?: 'Aceptado',
                'estado_pago'    => $r['estado_pago'],
                'estado_flujo'   => $estadoFlujo,
                'monto_pagado'   => (float)$r['monto_pagado'],
                'observacion'    => $r['observacion'] ?: 'Facto'
            ];

            $totalMonto += $totalDoc;
        }

        return $this->response->setJSON([
            'success'     => true,
            'data'        => $documentos,
            'total_monto' => $totalMonto,
            'pagination'  => ['current_page' => 1, 'total_pages' => 1, 'count' => count($documentos)]
        ]);
    }

    /**
     * POST /cobranza/facto/actualizar-estado-pago
     * Actualiza individualmente el estado de pago de una factura/guía de Facto.
     */
    public function actualizarEstadoPago(): ResponseInterface
    {
        $json = [];
        try {
            $json = $this->request->getJSON(true) ?: [];
        } catch (\Throwable $t) {
            $json = [];
        }
        if (empty($json)) {
            $raw = $this->request->getBody();
            $json = json_decode($raw, true) ?: $this->request->getPost();
        }

        $folio = trim((string) ($json['folio'] ?? ''));
        $codigoSii = (int) ($json['codigo_sii'] ?? 33);
        $estadoPago = trim((string) ($json['estado_pago'] ?? 'pendiente'));
        $montoPagado = (float) ($json['monto_pagado'] ?? 0);
        $obs = trim((string) ($json['observacion'] ?? ''));

        if (empty($folio) || !in_array($estadoPago, ['pendiente', 'pagada', 'parcial'], true)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Parámetros inválidos para actualizar el estado de pago.'
            ]);
        }

        $db = \Config\Database::connect();
        $sql = "INSERT INTO `tbl_facto_pagos` (`folio`, `codigo_sii`, `estado_pago`, `monto_pagado`, `observacion`)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                  `estado_pago` = VALUES(`estado_pago`),
                  `monto_pagado` = VALUES(`monto_pagado`),
                  `observacion` = VALUES(`observacion`),
                  `updated_at` = CURRENT_TIMESTAMP";

        $db->query($sql, [$folio, $codigoSii, $estadoPago, $montoPagado, $obs]);

        if ($estadoPago === 'pendiente') {
            $folioClean = ltrim($folio, '0');
            $factoRow = $db->table('tbl_facto_pagos')
                           ->groupStart()
                               ->where('folio', $folio)
                               ->orWhere('folio', $folioClean)
                           ->groupEnd()
                           ->get()->getRowArray();

            $existingCobrar = $db->table('tbl_documentos_cobrar')
                                 ->groupStart()
                                     ->where('numero', $folio)
                                     ->orWhere('numero', $folioClean)
                                 ->groupEnd()
                                 ->get()->getRowArray();

            if ($existingCobrar) {
                $montoTotal = $factoRow ? (float)$factoRow['monto_total'] : (float)$existingCobrar['total'];
                $db->table('tbl_documentos_cobrar')->where('id', $existingCobrar['id'])->update([
                    'impago'     => $montoTotal,
                    'pagado'     => 0.00,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            } else if ($factoRow) {
                $db->table('tbl_documentos_cobrar')->insert([
                    'rut_cliente'    => !empty($factoRow['rut_cliente']) ? $factoRow['rut_cliente'] : 'N/A',
                    'tipo_documento' => $this->getTipoDteNombre((int)$factoRow['codigo_sii']),
                    'numero'         => $folio,
                    'fecha'          => $factoRow['fecha_emision'] ?? date('Y-m-d'),
                    'total'          => (float)$factoRow['monto_total'],
                    'pagado'         => 0.00,
                    'impago'         => (float)$factoRow['monto_total'],
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s')
                ]);
            }
        } else if ($estadoPago === 'pagada') {
            $folioClean = ltrim($folio, '0');
            $db->table('tbl_documentos_cobrar')
               ->groupStart()
                   ->where('numero', $folio)
                   ->orWhere('numero', $folioClean)
               ->groupEnd()
               ->delete();
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => "Estado del folio {$folio} actualizado a '{$estadoPago}'."
        ]);
    }

    /**
     * POST /cobranza/facto/ingresar-manual
     * Registra manualmente un DTE de Facto en tbl_facto_pagos (para DTEs recién emitidos en Facto)
     */
    public function ingresarManual(): ResponseInterface
    {
        $json = [];
        try {
            $json = $this->request->getJSON(true) ?: [];
        } catch (\Throwable $t) {
            $json = [];
        }
        if (empty($json)) {
            $raw = $this->request->getBody();
            $json = json_decode($raw, true) ?: $this->request->getPost();
        }

        $folio       = trim((string)($json['folio'] ?? ''));
        $codigoSii   = (int)($json['codigo_sii'] ?? 33);
        $fecha       = trim((string)($json['fecha'] ?? date('Y-m-d')));
        $rutCliente  = trim((string)($json['rut_cliente'] ?? ''));
        $razonSocial = trim((string)($json['razon_social'] ?? ''));
        $montoTotal  = (float)($json['monto_total'] ?? 0);

        if (empty($folio) || $montoTotal <= 0) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Indique un número de folio válido y un monto total mayor a cero.'
            ]);
        }

        $neto = round($montoTotal / 1.19, 2);
        $iva  = round($montoTotal - $neto, 2);

        $db = \Config\Database::connect();
        $sql = "INSERT INTO `tbl_facto_pagos` 
                (`folio`, `codigo_sii`, `rut_cliente`, `razon_social`, `monto_neto`, `monto_iva`, `monto_total`, `fecha_emision`, `estado_sii`, `estado_pago`, `monto_pagado`, `observacion`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Aceptado', 'pendiente', 0.00, 'Ingreso Manual')
                ON DUPLICATE KEY UPDATE 
                  `rut_cliente` = VALUES(`rut_cliente`),
                  `razon_social` = VALUES(`razon_social`),
                  `monto_neto` = VALUES(`monto_neto`),
                  `monto_iva` = VALUES(`monto_iva`),
                  `monto_total` = VALUES(`monto_total`),
                  `fecha_emision` = VALUES(`fecha_emision`),
                  `updated_at` = CURRENT_TIMESTAMP;";

        $db->query($sql, [$folio, $codigoSii, $rutCliente, $razonSocial, $neto, $iva, $montoTotal, $fecha]);

        return $this->response->setJSON([
            'success' => true,
            'message' => "Documento folio {$folio} registrado correctamente."
        ]);
    }

    /**
     * POST /cobranza/facto/actualizar-estado-masivo
     * Actualiza de forma masiva múltiples documentos seleccionados desde el combo box.
     */
    public function actualizarEstadoMasivo(): ResponseInterface
    {
        $raw = $this->request->getBody();
        $json = json_decode($raw, true) ?: $this->request->getPost();

        $documentos = $json['documentos'] ?? [];
        $nuevoEstado = trim((string) ($json['nuevo_estado'] ?? ''));

        if (empty($documentos) || !in_array($nuevoEstado, ['pendiente', 'pagada', 'parcial'], true)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Debe seleccionar al menos un documento y un estado válido.'
            ]);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $updatedCount = 0;
        foreach ($documentos as $doc) {
            $folio = trim((string) ($doc['folio'] ?? ''));
            $codigoSii = (int) ($doc['codigo_sii'] ?? 33);
            if (empty($folio))
                continue;

            $sql = "INSERT INTO `tbl_facto_pagos` (`folio`, `codigo_sii`, `estado_pago`)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                      `estado_pago` = VALUES(`estado_pago`),
                      `updated_at` = CURRENT_TIMESTAMP";
            $db->query($sql, [$folio, $codigoSii, $nuevoEstado]);

            if ($nuevoEstado === 'pendiente') {
                $folioClean = ltrim($folio, '0');
                $factoRow = $db->table('tbl_facto_pagos')
                               ->groupStart()
                                   ->where('folio', $folio)
                                   ->orWhere('folio', $folioClean)
                               ->groupEnd()
                               ->get()->getRowArray();

                $existingCobrar = $db->table('tbl_documentos_cobrar')
                                     ->groupStart()
                                         ->where('numero', $folio)
                                         ->orWhere('numero', $folioClean)
                                     ->groupEnd()
                                     ->get()->getRowArray();

                if ($existingCobrar) {
                    $montoTotal = $factoRow ? (float)$factoRow['monto_total'] : (float)$existingCobrar['total'];
                    $db->table('tbl_documentos_cobrar')->where('id', $existingCobrar['id'])->update([
                        'impago'     => $montoTotal,
                        'pagado'     => 0.00,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                } else if ($factoRow) {
                    $db->table('tbl_documentos_cobrar')->insert([
                        'rut_cliente'    => !empty($factoRow['rut_cliente']) ? $factoRow['rut_cliente'] : 'N/A',
                        'tipo_documento' => $this->getTipoDteNombre((int)$factoRow['codigo_sii']),
                        'numero'         => $folio,
                        'fecha'          => $factoRow['fecha_emision'] ?? date('Y-m-d'),
                        'total'          => (float)$factoRow['monto_total'],
                        'pagado'         => 0.00,
                        'impago'         => (float)$factoRow['monto_total'],
                        'created_at'     => date('Y-m-d H:i:s'),
                        'updated_at'     => date('Y-m-d H:i:s')
                    ]);
                }
            } else if ($nuevoEstado === 'pagada') {
                $folioClean = ltrim($folio, '0');
                $db->table('tbl_documentos_cobrar')
                   ->groupStart()
                       ->where('numero', $folio)
                       ->orWhere('numero', $folioClean)
                   ->groupEnd()
                   ->delete();
            }

            $updatedCount++;
        }

        $db->transComplete();

        return $this->response->setJSON([
            'success' => true,
            'message' => "Se actualizaron {$updatedCount} documentos a '{$nuevoEstado}' correctamente.",
            'count' => $updatedCount
        ]);
    }

    /**
     * POST /cobranza/facto/actualizar-estado-flujo
     * Actualiza manualmente el estado de flujo (cargado vs pendiente) en tbl_facto_pagos.
     */
    public function actualizarEstadoFlujo(): ResponseInterface
    {
        $json = [];
        try {
            $json = $this->request->getJSON(true) ?: [];
        } catch (\Throwable $t) {
            $json = [];
        }
        if (empty($json)) {
            $raw = $this->request->getBody();
            $json = json_decode($raw, true) ?: $this->request->getPost();
        }

        $folio       = trim((string)($json['folio'] ?? ''));
        $codigoSii   = (int)($json['codigo_sii'] ?? 33);
        $estadoFlujo = trim((string)($json['estado_flujo'] ?? ''));

        if (empty($folio) || !in_array($estadoFlujo, ['cargado', 'pendiente'], true)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Folio o estado de flujo inválido.'
            ]);
        }

        $folioClean = ltrim($folio, '0');
        $db = \Config\Database::connect();

        $obs = ($estadoFlujo === 'cargado') ? 'Cargado / Conciliado en Ventas' : 'Facto';

        $exists = $db->table('tbl_facto_pagos')
            ->where('folio', $folio)
            ->orWhere('folio', $folioClean)
            ->get()->getRowArray();

        if ($exists) {
            $db->query(
                "UPDATE `tbl_facto_pagos` SET `observacion` = ?, `updated_at` = NOW() WHERE `folio` = ? OR `folio` = ?",
                [$obs, $folio, $folioClean]
            );
        } else {
            $db->table('tbl_facto_pagos')->insert([
                'folio'        => $folio,
                'codigo_sii'   => $codigoSii,
                'observacion'  => $obs,
                'estado_pago'  => 'pendiente',
                'monto_neto'   => 0.00,
                'monto_iva'    => 0.00,
                'monto_total'  => 0.00,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s')
            ]);
        }

        $label = ($estadoFlujo === 'cargado') ? 'Cargado' : 'Pendiente';
        return $this->response->setJSON([
            'success' => true,
            'message' => "Estado de flujo del folio {$folio} actualizado a '{$label}'."
        ]);
    }

    /**
     * POST /cobranza/facto/marcar-pagadas-desde-cobrar
     * Actualiza el estado a 'pagada' o reactiva a 'pendiente' en tbl_facto_pagos y sincroniza tbl_documentos_cobrar.
     */
    /**
     * POST /cobranza/facto/marcar-pagadas-desde-cobrar
     * Actualiza el estado a 'pagada' o reactiva a 'pendiente' en tbl_documentos_cobrar y tbl_facto_pagos.
     */
    public function marcarPagadasDesdeCobrar(): ResponseInterface
    {
        $raw = $this->request->getBody();
        $json = json_decode($raw, true) ?: $this->request->getPost();
        $documentos = $json['documentos'] ?? [];

        if (empty($documentos)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se enviaron documentos para actualizar.'
            ]);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $updatedCount = 0;
        foreach ($documentos as $doc) {
            $folioRaw    = trim((string)($doc['nro'] ?? $doc['numero'] ?? $doc['folio'] ?? ''));
            $rutRaw      = trim((string)($doc['rut'] ?? $doc['rut_cliente'] ?? ''));
            $nuevoEstado = strtolower(trim((string)($doc['estado'] ?? 'pagada')));

            if (empty($folioRaw) || $folioRaw === 'N/A' || $folioRaw === '—') {
                continue;
            }

            $folioClean = ltrim($folioRaw, '0');
            if (empty($folioClean)) $folioClean = '0';
            $rutClean   = !empty($rutRaw) ? str_replace(['.', ' ', '-'], '', strtoupper($rutRaw)) : '';

            $esPagada   = ($nuevoEstado === 'pagado' || $nuevoEstado === 'pagada');

            // 1. Actualizar tbl_documentos_cobrar
            $builderCobrar = $db->table('tbl_documentos_cobrar')
                ->groupStart()
                    ->where('numero', $folioRaw)
                    ->orWhere('numero', $folioClean)
                    ->orWhere("TRIM(LEADING '0' FROM TRIM(numero)) = '{$folioClean}'")
                ->groupEnd();

            if (!empty($rutClean)) {
                $builderCobrar->groupStart()
                    ->where('rut_cliente', $rutRaw)
                    ->orWhere('rut_cliente', $rutClean)
                    ->orWhere("REPLACE(REPLACE(REPLACE(rut_cliente, '.', ''), ' ', ''), '-', '') = '{$rutClean}'")
                ->groupEnd();
            }

            $resCobrar = $builderCobrar->get();
            $existingCobrar = ($resCobrar && is_object($resCobrar)) ? $resCobrar->getRowArray() : null;

            if ($existingCobrar) {
                $totalVal  = (float)$existingCobrar['total'];
                $pagadoReq = isset($doc['pagado']) ? (float)$doc['pagado'] : null;
                $impagoReq = isset($doc['impago']) ? (float)$doc['impago'] : null;

                if ($nuevoEstado === 'parcial') {
                    $pagadoVal = ($pagadoReq !== null) ? $pagadoReq : (float)($existingCobrar['pagado'] ?? 0);
                    $impagoVal = ($impagoReq !== null) ? $impagoReq : max(0, $totalVal - $pagadoVal);
                } else if ($esPagada) {
                    $pagadoVal = $totalVal;
                    $impagoVal = 0.00;
                } else {
                    $pagadoVal = 0.00;
                    $impagoVal = $totalVal;
                }

                $db->table('tbl_documentos_cobrar')->where('id', $existingCobrar['id'])->update([
                    'pagado'     => $pagadoVal,
                    'impago'     => $impagoVal,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }

            // 2. Actualizar tbl_facto_pagos (solo si existe la tabla)
            if ($db->tableExists('tbl_facto_pagos')) {
                $estadoFacto  = ($nuevoEstado === 'parcial') ? 'parcial' : ($esPagada ? 'pagada' : 'pendiente');
                $obsFacto     = ($nuevoEstado === 'parcial') ? 'Abono Parcial registrado' : ($esPagada ? 'Pagada desde Cuentas por Cobrar' : 'Reactivado a Pendiente');
                $montoPagadoFacto = ($nuevoEstado === 'parcial') ? $pagadoVal : ($esPagada ? (float)($existingCobrar['total'] ?? 0) : 0.00);

                $sqlFacto = "UPDATE `tbl_facto_pagos` 
                             SET `estado_pago` = ?, 
                                 `monto_pagado` = ?, 
                                 `observacion` = ?, 
                                 `updated_at` = CURRENT_TIMESTAMP
                             WHERE (`folio` = ? OR `folio` = ? OR TRIM(LEADING '0' FROM `folio`) = ?);";
                $db->query($sqlFacto, [$estadoFacto, $montoPagadoFacto, $obsFacto, $folioRaw, $folioClean, $folioClean]);
            }

            $updatedCount++;
        }

        $db->transComplete();

        return $this->response->setJSON([
            'success' => true,
            'message' => "Se actualizaron {$updatedCount} documento(s) correctamente.",
            'count'   => $updatedCount
        ]);
    }

    /**
     * POST /cobranza/facto/importar-a-cobrar
     * Traslada los DTEs seleccionados (Facturas 33, Boletas 39, Guías 52) directamente a tbl_documentos_cobrar.
     */
    public function importarACobrar(): ResponseInterface
    {
        $raw = $this->request->getBody();
        $json = json_decode($raw, true) ?: $this->request->getPost();

        $documentos = $json['documentos'] ?? [];

        if (empty($documentos)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Debe seleccionar al menos un documento para trasladar a Cuentas por Cobrar.'
            ]);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $importedCount = 0;
        foreach ($documentos as $doc) {
            $folioRaw = trim((string)($doc['folio'] ?? ''));
            if (empty($folioRaw) || $folioRaw === 'N/A' || $folioRaw === '—') {
                continue;
            }

            $folioClean = ltrim($folioRaw, '0');
            $rutRaw     = trim((string)($doc['cliente_rut'] ?? ''));
            $rutCliente = !empty($rutRaw) ? $this->formatRut($rutRaw) : '';
            $nombreCliente = trim((string)($doc['cliente_nombre'] ?? 'Cliente Facto'));
            $codigoSii = (int)($doc['codigo_sii'] ?? 33);
            $fecha = trim((string)($doc['fecha'] ?? date('Y-m-d')));
            $total = (float)($doc['total'] ?? 0);

            $tipoNombre = $this->getTipoDteNombre($codigoSii);

            // 1. Asegurar registro del cliente en tbl_clientes si no existe
            if (!empty($rutCliente)) {
                $qCliente = $db->table('tbl_clientes')
                                   ->where('rut', $rutCliente)
                                   ->get();
                $existCliente = ($qCliente && is_object($qCliente)) ? $qCliente->getRowArray() : null;
                if (!$existCliente) {
                    $db->table('tbl_clientes')->insert([
                        'nombre' => $nombreCliente,
                        'rut' => $rutCliente,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }

            // 2. Insertar / Actualizar en tbl_documentos_cobrar (coincidencia por folio exacto o limpio)
            $qExisting = $db->table('tbl_documentos_cobrar')
                           ->groupStart()
                               ->where('numero', $folioRaw)
                               ->orWhere('numero', $folioClean)
                           ->groupEnd()
                           ->get();
            $existing  = ($qExisting && is_object($qExisting)) ? $qExisting->getRowArray() : null;

            if ($existing) {
                $db->table('tbl_documentos_cobrar')->where('id', $existing['id'])->update([
                    'rut_cliente'    => $rutCliente,
                    'tipo_documento' => $tipoNombre,
                    'fecha'          => $fecha,
                    'total'          => $total,
                    'impago'         => $total - (float)($existing['pagado'] ?? 0),
                    'updated_at'     => date('Y-m-d H:i:s')
                ]);
            } else {
                $db->table('tbl_documentos_cobrar')->insert([
                    'rut_cliente'    => $rutCliente,
                    'tipo_documento' => $tipoNombre,
                    'numero'         => $folioRaw,
                    'fecha'          => $fecha,
                    'total'          => $total,
                    'pagado'         => 0.00,
                    'impago'         => $total,
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s')
                ]);
            }

            // 3. Registrar / Actualizar en tbl_facto_pagos en estado 'pendiente'
            $sqlFacto = "INSERT INTO `tbl_facto_pagos` (`folio`, `codigo_sii`, `rut_cliente`, `razon_social`, `monto_total`, `fecha_emision`, `estado_sii`, `estado_pago`, `monto_pagado`, `observacion`)
                         VALUES (?, ?, ?, ?, ?, ?, 'Aceptado', 'pendiente', 0.00, 'Cuentas por Cobrar (Deuda Impaga)')
                         ON DUPLICATE KEY UPDATE 
                           `rut_cliente` = VALUES(`rut_cliente`),
                           `razon_social` = VALUES(`razon_social`),
                           `monto_total` = VALUES(`monto_total`),
                           `fecha_emision` = VALUES(`fecha_emision`),
                           `estado_pago` = 'pendiente',
                           `monto_pagado` = 0.00,
                           `observacion` = VALUES(`observacion`),
                           `updated_at` = CURRENT_TIMESTAMP";
            $db->query($sqlFacto, [$folioClean, $codigoSii, $rutCliente, $nombreCliente, $total, $fecha]);

            $importedCount++;
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Ocurrió un error al guardar los documentos en Cuentas por Cobrar.'
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => "Se trasladaron {$importedCount} documentos a Cuentas por Cobrar con éxito.",
            'count' => $importedCount
        ]);
    }

    /**
     * Autentica contra Facto API y retorna token Bearer con caché de 55 minutos
     */
    private function getFactoToken(): ?string
    {
        $cachedToken = cache('facto_bearer_token');
        if ($cachedToken) {
            return $cachedToken;
        }

        $clientId = env('FACTO_CLIENT_ID') ?: 'd1a179675157';
        $clientSecret = env('FACTO_CLIENT_SECRET') ?: '5de78437e4bc19ebcada104b254d5529';
        $username = env('FACTO_USERNAME') ?: '77775829-2/5634';
        $password = env('FACTO_PASSWORD') ?: '1de8af70e35aa7e0d2c7b6d4750cad25';

        $authUrl = "https://api.facto.cl/auth";
        $authBody = [
            "grant_type" => "password",
            "client_id" => $clientId,
            "client_secret" => $clientSecret,
            "username" => $username,
            "password" => $password
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $authUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($authBody));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);

        $authResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $authResponse) {
            $authData = json_decode($authResponse, true);
            $token = $authData['access_token'] ?? null;
            if ($token) {
                cache()->save('facto_bearer_token', $token, 3300); // 55 minutos
                return $token;
            }
        }

        return null;
    }

    /**
     * Mapeo de nombres descriptivos de DTE SII
     */
    private function getTipoDteNombre(int $codigoSii): string
    {
        switch ($codigoSii) {
            case 33: return 'Factura Electrónica';
            case 39: return 'Boleta Electrónica';
            case 52: return 'Guía de Despacho Electrónica';
            case 61: return 'Nota de Crédito Electrónica';
            case 56: return 'Nota de Débito Electrónica';
            default: return 'Documento DTE (' . $codigoSii . ')';
        }
    }

    private function formatRut(string $rut): string
    {
        $clean = preg_replace('/[^0-9kK]/', '', $rut);
        if (strlen($clean) < 2) return $rut;
        $body = substr($clean, 0, -1);
        $dv   = strtoupper(substr($clean, -1));
        return $body . '-' . $dv;
    }

    /**
     * GET /ejecutar-migracion-db-prod?token=sync2026
     * Importa local_admin.sql a la base de datos de producción desde el propio CodeIgniter
     */
    public function ejecutarMigracionDb(): ResponseInterface
    {
        $token = $this->request->getGet('token');
        if ($token !== 'sync2026') {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Acceso denegado']);
        }

        $db = \Config\Database::connect();
        $db->query("SET FOREIGN_KEY_CHECKS = 0;");
        $db->query("DROP TABLE IF EXISTS tbl_productos_reservas, tbl_listaprecios, tbl_abonos_pagar, tbl_documentos_pagar, tbl_documentos_cobrar, tbl_caja_movimientos, tbl_cajas, tbl_productos, tbl_clientes, tbl_proveedores, tbl_funcionarios, tbl_perfiles, tbl_users, tbl_usuarios, tbl_facto_pagos, tbl_notificaciones;");

        $sqlPath = FCPATH . 'local_admin.sql';
        if (!file_exists($sqlPath)) {
            $sqlPath = ROOTPATH . 'local_admin.sql';
        }

        if (!file_exists($sqlPath)) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'local_admin.sql no encontrado en ' . $sqlPath]);
        }

        $lines = file($sqlPath);
        $query = '';
        $count = 0;
        $errors = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (substr($trimmed, 0, 2) == '--' || $trimmed == '' || substr($trimmed, 0, 2) == '/*') {
                continue;
            }
            $query .= $line;
            if (substr($trimmed, -1) == ';') {
                try {
                    if (!$db->query($query)) {
                        $errors[] = "Error SQL";
                    } else {
                        $count++;
                    }
                } catch (\Throwable $e) {
                    $errors[] = $e->getMessage();
                }
                $query = '';
            }
        }

        $db->query("SET FOREIGN_KEY_CHECKS = 1;");
        @unlink($sqlPath);

        return $this->response->setJSON([
            'success' => true,
            'message' => "Sincronización de base de datos completada con éxito. {$count} consultas ejecutadas.",
            'queries_count' => $count,
            'errors_count'  => count($errors),
            'errors'        => array_slice($errors, 0, 5)
        ]);
    }
}
