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
          `estado_pago` ENUM('pendiente', 'pagada', 'parcial') NOT NULL DEFAULT 'pendiente',
          `monto_pagado` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `observacion` VARCHAR(255) NULL,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          UNIQUE KEY `uniq_facto_doc` (`folio`, `codigo_sii`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $db->query($sql);
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
     * Consulta documentos emitidos desde la API oficial de Facto y cruza con sus estados de pago locales.
     */
    public function buscarDtes(): ResponseInterface
    {
        $fechaInicio = $this->request->getGet('fecha_inicio');
        $fechaFin = $this->request->getGet('fecha_fin');
        $numero = trim((string) ($this->request->getGet('numero') ?? ''));
        $cliente = trim((string) ($this->request->getGet('cliente') ?? ''));
        $tipoDte = trim((string) ($this->request->getGet('tipo_dte') ?? ''));
        $estadoFiltro = trim((string) ($this->request->getGet('estado_pago') ?? '')); // filtro por estado de pago
        $requestedPage = (int) ($this->request->getGet('page') ?: 1);

        $token = $this->getFactoToken();

        if (!$token) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al autenticar con la API de Facto. Verifique credenciales FACTO_* en el archivo .env.'
            ]);
        }

        // Mapeo del código de DTE SII a document_type_id nativo de Facto API
        $typeIdMap = [
            '33' => 2,   // Facturas Electrónicas
            '52' => 54,  // Guías de Despacho Electrónicas
            '39' => 7,   // Boletas Electrónicas
            '61' => 16,  // Notas de Crédito Electrónicas
        ];

        // Construir query params nativos para Facto API
        $queryParams = [
            'received_issued_flag' => 1 // 1 = Documentos Emitidos
        ];

        if ($numero !== '') {
            $queryParams['document_number'] = $numero;
        }
        if ($cliente !== '') {
            $cleanRut = preg_replace('/[^0-9kK-]/', '', $cliente);
            if (!empty($cleanRut)) {
                $queryParams['receiver_tax_id_code'] = $cleanRut;
            }
        }
        if ($fechaInicio) {
            $queryParams['issue_date_from'] = $fechaInicio;
        }
        if ($fechaFin) {
            $queryParams['issue_date_to'] = $fechaFin;
        }
        if ($tipoDte && isset($typeIdMap[$tipoDte])) {
            $queryParams['document_type_id'] = $typeIdMap[$tipoDte];
        }

        $rawItems   = [];
        $totalItems = 0;
        $totalPages = 1;
        $todayDate  = date('Y-m-d');
        $db = \Config\Database::connect();

        // 1. Si no hay fecha de inicio explícita ni folio puntual, consultar a Facto API trayendo HOY y el AÑO ACTUAL (desde 2026-01-01)
        if (!$fechaInicio && $numero === '') {
            // A. DTEs de HOY
            $todayParams = $queryParams;
            $todayParams['issue_date_from'] = $todayDate;
            $todayParams['limit'] = 100;
            $urlToday = 'https://api-billing.koywe.com/V1/documents?' . http_build_query($todayParams);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $urlToday);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$token}", "Accept: application/json"]);
            $respToday = curl_exec($ch);
            $codeToday = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($codeToday === 200 && $respToday) {
                $dToday = json_decode($respToday, true);
                $itemsToday = $dToday['_embedded']['documents'] ?? $dToday['_embedded']['items'] ?? [];
                foreach ($itemsToday as $it) {
                    $rawItems[] = $it;
                }
            }

            // B. DTEs del Año Actual (desde 2026-01-01) con paginación
            $yearParams = $queryParams;
            $yearParams['issue_date_from'] = date('Y-01-01');
            $yearParams['limit'] = 100;

            for ($page = 1; $page <= 5; $page++) {
                $yearParams['page'] = $page;
                $urlYear = 'https://api-billing.koywe.com/V1/documents?' . http_build_query($yearParams);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $urlYear);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$token}", "Accept: application/json"]);
                $respYear = curl_exec($ch);
                $codeYear = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($codeYear === 200 && $respYear) {
                    $dYear = json_decode($respYear, true);
                    $itemsYear = $dYear['_embedded']['documents'] ?? $dYear['_embedded']['items'] ?? [];
                    if (empty($itemsYear)) {
                        break;
                    }
                    $existingIds = array_filter(array_column($rawItems, 'document_id'));
                    foreach ($itemsYear as $it) {
                        $dId = $it['document_id'] ?? null;
                        if ($dId && !in_array($dId, $existingIds, true)) {
                            $rawItems[] = $it;
                        }
                    }
                    if (count($itemsYear) < 100) {
                        break;
                    }
                } else {
                    break;
                }
            }
        } else {
            // Consulta normal con filtros de usuario
            $currentParams = $queryParams;
            $currentParams['page'] = $requestedPage;
            $url = 'https://api-billing.koywe.com/V1/documents?' . http_build_query($currentParams);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$token}", "Accept: application/json"]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                $rawItems = $data['_embedded']['documents'] ?? $data['_embedded']['items'] ?? [];
            }
        }

        // 2. Si el filtro es "pendiente", complementar además con folios impagos de la BD que no hayan venido en la página actual
        if ($estadoFiltro === 'pendiente') {
            $pendingFoliosList = [];

            // A. Folios pendientes en tbl_facto_pagos
            if ($db->tableExists('tbl_facto_pagos')) {
                $factoFolios = $db->table('tbl_facto_pagos')->select('folio')->where('estado_pago', 'pendiente')->get()->getResultArray();
                foreach ($factoFolios as $ff) {
                    $f = trim((string)$ff['folio']);
                    if ($f !== '' && $f !== 'N/A' && !in_array($f, $pendingFoliosList, true)) {
                        $pendingFoliosList[] = $f;
                    }
                }
            }

            // B. Folios impagos en tbl_documentos_cobrar
            if ($db->tableExists('tbl_documentos_cobrar')) {
                $cobrarFolios = $db->table('tbl_documentos_cobrar')->select('numero')->where('impago >', 0)->get()->getResultArray();
                foreach ($cobrarFolios as $cf) {
                    $f = trim((string)$cf['numero']);
                    if ($f !== '' && $f !== 'N/A' && !in_array($f, $pendingFoliosList, true)) {
                        $pendingFoliosList[] = $f;
                    }
                }
            }

            // C. Consultar folios de la BD faltantes vía cURL multi en lotes de 15 peticiones paralelas
            if (!empty($pendingFoliosList)) {
                $existingFolios = array_filter(array_column($rawItems, 'document_number'));
                $foliosToFetch = [];
                foreach ($pendingFoliosList as $f) {
                    if (!in_array((string)$f, array_map('strval', $existingFolios), true)) {
                        $foliosToFetch[] = $f;
                    }
                }

                if (!empty($foliosToFetch)) {
                    $chunks = array_chunk($foliosToFetch, 15);
                    foreach ($chunks as $chunk) {
                        $mh = curl_multi_init();
                        $handles = [];
                        foreach ($chunk as $f) {
                            $pParams = $queryParams;
                            $pParams['document_number'] = $f;
                            $pUrl = 'https://api-billing.koywe.com/V1/documents?' . http_build_query($pParams);

                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $pUrl);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$token}", "Accept: application/json"]);
                            curl_multi_add_handle($mh, $ch);
                            $handles[$f] = $ch;
                        }

                        if (!empty($handles)) {
                            $active = null;
                            do {
                                $mrc = curl_multi_exec($mh, $active);
                            } while ($mrc === CURLM_CALL_MULTI_PERFORM);

                            while ($active && $mrc === CURLM_OK) {
                                if (curl_multi_select($mh) === -1) {
                                    usleep(500);
                                }
                                do {
                                    $mrc = curl_multi_exec($mh, $active);
                                } while ($mrc === CURLM_CALL_MULTI_PERFORM);
                            }

                            foreach ($handles as $f => $ch) {
                                $resP = curl_multi_getcontent($ch);
                                if ($resP) {
                                    $dP = json_decode($resP, true);
                                    $dList = $dP['_embedded']['documents'] ?? $dP['_embedded']['items'] ?? [];
                                    if (!empty($dList)) {
                                        foreach ($dList as $docObj) {
                                            $rawItems[] = $docObj;
                                        }
                                    }
                                }
                                curl_multi_remove_handle($mh, $ch);
                                curl_close($ch);
                            }
                        }
                        curl_multi_close($mh);
                    }
                }
            }
        }

        if (empty($rawItems) && $requestedPage === 1) {
            return $this->response->setJSON([
                'success' => true,
                'data'    => [],
                'total_monto' => 0,
                'pagination'  => ['current_page' => 1, 'total_pages' => 1, 'count' => 0]
            ]);
        }

        $db = \Config\Database::connect();
        $estadosLocales     = [];
        $pendientesPorFolio = [];

        // 1. Cargar overrides o estados guardados explícitamente en tbl_facto_pagos
        $rowsFacto = $db->table('tbl_facto_pagos')->get()->getResultArray();
        foreach ($rowsFacto as $r) {
            $key = trim((string)$r['folio']) . '_' . $r['codigo_sii'];
            $estadosLocales[$key] = [
                'estado_pago'  => $r['estado_pago'],
                'monto_pagado' => (float) $r['monto_pagado'],
                'observacion'  => $r['observacion']
            ];
        }

        // 2. Cargar TODAS las impagas en tbl_documentos_cobrar por numero (folio)
        $rowsCobrar = $db->table('tbl_documentos_cobrar')->where('impago >', 0)->get()->getResultArray();
        foreach ($rowsCobrar as $c) {
            $folioRaw   = trim((string)$c['numero']);
            $folioClean = ltrim($folioRaw, '0');
            $impago     = (float)($c['impago'] ?? 0);
            $pagado     = (float)($c['pagado'] ?? 0);
            $st         = 'pendiente';

            $dataItem = [
                'estado_pago'  => $st,
                'monto_pagado' => $pagado,
                'impago'       => $impago,
                'observacion'  => 'Cuentas por Cobrar (Deuda Impaga)'
            ];

            if ($folioRaw !== '') {
                $pendientesPorFolio[$folioRaw] = $dataItem;
            }
            if ($folioClean !== '') {
                $pendientesPorFolio[$folioClean] = $dataItem;
            }
        }

        // 3. Cargar TODAS las impagas en tbl_documentos_pagar por numero (folio) si existe
        if ($db->tableExists('tbl_documentos_pagar')) {
            $rowsPagar = $db->table('tbl_documentos_pagar')->where('impago >', 0)->get()->getResultArray();
            foreach ($rowsPagar as $p) {
                $folioRaw   = trim((string)$p['numero']);
                $folioClean = ltrim($folioRaw, '0');
                $impago     = (float)($p['impago'] ?? 0);
                $pagado     = (float)($p['pagado'] ?? 0);
                $st         = ($pagado > 0) ? 'parcial' : 'pendiente';

                $dataItem = [
                    'estado_pago'  => $st,
                    'monto_pagado' => $pagado,
                    'impago'       => $impago,
                    'observacion'  => 'Cuentas por Pagar (Deuda Impaga)'
                ];

                if ($folioRaw !== '' && !isset($pendientesPorFolio[$folioRaw])) {
                    $pendientesPorFolio[$folioRaw] = $dataItem;
                }
                if ($folioClean !== '' && !isset($pendientesPorFolio[$folioClean])) {
                    $pendientesPorFolio[$folioClean] = $dataItem;
                }
            }
        }

        $documentos = [];
        $totalMontoBatch = 0;
        $todayDate = date('Y-m-d'); // 2026-07-27

        foreach ($rawItems as $item) {
            $folioDoc = (string) ($item['document_number'] ?? '');
            if ($numero !== '' && strpos($folioDoc, $numero) === false) {
                continue;
            }

            $rutDoc = (string) ($item['receiver_tax_id_code'] ?? '');
            $nombreDoc = (string) ($item['receiver_legal_name'] ?? '');
            if ($cliente !== '') {
                $qLower = strtolower($cliente);
                if (strpos(strtolower($rutDoc), $qLower) === false && strpos(strtolower($nombreDoc), $qLower) === false) {
                    continue;
                }
            }

            $taxBureauCode = (int) ($item['document_type_taxbureau'] ?? 0);
            $tipoNombre = $this->getTipoDteNombre($taxBureauCode);

            $neto  = (float) ($item['net_amount'] ?? 0);
            $iva   = (float) ($item['taxes_amount'] ?? 0);
            $total = (float) ($item['total_amount'] ?? 0);
            $docFecha = trim((string)($item['issue_date'] ?? ''));

            // Determinación por Base de Datos y Fecha:
            // 1. Si existe en tbl_facto_pagos (modificado manualmente), respetar ese estado.
            // 2. Si es de HOY (27/07/2026), marcar como 'pendiente' (impaga por ser emitida hoy).
            // 3. Si existe en cuentas por cobrar/pagar con impago > 0, asignar 'pendiente' o 'parcial'.
            // 4. Si NO existe en cuentas impagas y NO es de hoy, marcar como 'pagada' automáticamente.
            $localKey      = $folioDoc . '_' . $taxBureauCode;
            $folioDocClean = ltrim($folioDoc, '0');

            if (isset($pendientesPorFolio[$folioDoc])) {
                $estadoPago  = $pendientesPorFolio[$folioDoc]['estado_pago'];
                $montoPagado = $pendientesPorFolio[$folioDoc]['monto_pagado'];
                $total       = $pendientesPorFolio[$folioDoc]['impago'];
                $obs         = 'Cuentas por Cobrar (Deuda Impaga)';
            } elseif (isset($pendientesPorFolio[$folioDocClean])) {
                $estadoPago  = $pendientesPorFolio[$folioDocClean]['estado_pago'];
                $montoPagado = $pendientesPorFolio[$folioDocClean]['monto_pagado'];
                $total       = $pendientesPorFolio[$folioDocClean]['impago'];
                $obs         = 'Cuentas por Cobrar (Deuda Impaga)';
            } elseif (isset($estadosLocales[$localKey])) {
                $estadoPago  = $estadosLocales[$localKey]['estado_pago'];
                $montoPagado = $estadosLocales[$localKey]['monto_pagado'];
                $obs         = ($estadosLocales[$localKey]['observacion'] === 'Cuentas por Cobrar (Deuda Impaga)')
                               ? 'Pendiente por Cargar a Cuentas por Cobrar'
                               : $estadosLocales[$localKey]['observacion'];
            } elseif ($docFecha === $todayDate) {
                $estadoPago  = 'pendiente';
                $montoPagado = 0.0;
                $obs         = 'Emitida hoy (' . $docFecha . ') - Pendiente por Cargar';
            } elseif (!empty($docFecha) && $docFecha >= '2026-01-01') {
                $estadoPago  = 'pendiente';
                $montoPagado = 0.0;
                $obs         = 'Pendiente por Cargar a Cuentas por Cobrar';
            } else {
                $estadoPago  = 'pagada';
                $montoPagado = $total;
                $obs         = 'Conciliado (Histórico)';
            }

            // Filtrar si el usuario seleccionó un estado de pago específico
            if ($estadoFiltro !== '' && $estadoPago !== $estadoFiltro) {
                continue;
            }

            $totalMontoBatch += $total;

            $documentos[] = [
                'id' => $item['document_id'] ?? null,
                'folio' => $folioDoc ?: '—',
                'fecha' => $item['issue_date'] ?? '—',
                'codigo_sii' => $taxBureauCode,
                'tipo_documento' => $tipoNombre,
                'cliente_rut' => $rutDoc ?: '—',
                'cliente_nombre' => $nombreDoc ?: '—',
                'neto' => $neto,
                'iva' => $iva,
                'total' => $total,
                'estado_sii' => ((int) ($item['taxbureau_sending_status'] ?? 0) === 1) ? 'Aceptado' : 'Pendiente',
                'estado_pago' => $estadoPago,
                'monto_pagado' => $montoPagado,
                'observacion' => $obs
            ];
        }

        // Deduplicación limpia por Folio (evita duplicidad Guía DTE 52 + Factura DTE 33)
        $uniqueDocs = [];
        $seen = [];
        $totalMontoBatch = 0;
        foreach ($documentos as $doc) {
            $key = trim((string)$doc['folio']);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $uniqueDocs[] = $doc;
                $totalMontoBatch += (float)$doc['total'];
            }
        }
        $documentos = $uniqueDocs;

        // Ordenar por fecha descendente (lo más reciente de HOY 2026 arriba)
        usort($documentos, function($a, $b) {
            $fa = $a['fecha'] ?? '';
            $fb = $b['fecha'] ?? '';
            if ($fa === $fb) {
                return (int)($b['folio'] ?? 0) - (int)($a['folio'] ?? 0);
            }
            return strcmp($fb, $fa);
        });

        $totalCount  = count($documentos);
        $calcPages   = (int)ceil($totalCount / 25);
        $totalPages  = max(1, $calcPages);

        return $this->response->setJSON([
            'success'     => true,
            'data'        => $documentos,
            'total_monto' => $totalMontoBatch,
            'pagination'  => [
                'current_page' => 1,
                'total_pages'  => $totalPages,
                'count'        => $totalCount
            ]
        ]);
    }

    /**
     * POST /cobranza/facto/actualizar-estado-pago
     * Actualiza individualmente el estado de pago de una factura/guía de Facto.
     */
    public function actualizarEstadoPago(): ResponseInterface
    {
        $raw = $this->request->getBody();
        $json = json_decode($raw, true) ?: $this->request->getPost();

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

        if ($estadoPago === 'pagada') {
            $db->table('tbl_documentos_cobrar')->where('numero', $folio)->update([
                'impago' => 0.00,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => "Estado del folio {$folio} actualizado a '{$estadoPago}'."
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

            if ($nuevoEstado === 'pagada') {
                $db->table('tbl_documentos_cobrar')->where('numero', $folio)->update([
                    'impago' => 0.00,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
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
            $rutCliente = trim((string)($doc['cliente_rut'] ?? ''));
            $nombreCliente = trim((string)($doc['cliente_nombre'] ?? 'Cliente Facto'));
            $codigoSii = (int)($doc['codigo_sii'] ?? 33);
            $fecha = trim((string)($doc['fecha'] ?? date('Y-m-d')));
            $total = (float)($doc['total'] ?? 0);

            $tipoNombre = $this->getTipoDteNombre($codigoSii);

            // 1. Asegurar registro del cliente en tbl_clientes si no existe
            if (!empty($rutCliente)) {
                $existCliente = $db->table('tbl_clientes')->where('rut', $rutCliente)->get()->getRowArray();
                if (!$existCliente) {
                    $db->table('tbl_clientes')->insert([
                        'nombre' => $nombreCliente,
                        'rut' => $rutCliente,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }

            // 2. Insertar / Actualizar en tbl_documentos_cobrar
            $existing = $db->table('tbl_documentos_cobrar')->where('numero', $folioClean)->get()->getRowArray();
            if ($existing) {
                $db->table('tbl_documentos_cobrar')->where('id', $existing['id'])->update([
                    'rut_cliente'    => $rutCliente,
                    'tipo_documento' => $tipoNombre,
                    'fecha'          => $fecha,
                    'total'          => $total,
                    'impago'         => $total,
                    'updated_at'     => date('Y-m-d H:i:s')
                ]);
            } else {
                $db->table('tbl_documentos_cobrar')->insert([
                    'rut_cliente'    => $rutCliente,
                    'tipo_documento' => $tipoNombre,
                    'numero'         => $folioClean,
                    'fecha'          => $fecha,
                    'total'          => $total,
                    'pagado'         => 0.00,
                    'impago'         => $total,
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s')
                ]);
            }

            // 3. Registrar en tbl_facto_pagos en estado 'pendiente'
            $sqlFacto = "INSERT INTO `tbl_facto_pagos` (`folio`, `codigo_sii`, `estado_pago`, `monto_pagado`, `observacion`)
                         VALUES (?, ?, 'pendiente', 0.00, 'Cuentas por Cobrar (Deuda Impaga)')
                         ON DUPLICATE KEY UPDATE 
                           `estado_pago` = 'pendiente',
                           `monto_pagado` = 0.00,
                           `updated_at` = CURRENT_TIMESTAMP";
            $db->query($sqlFacto, [$folioClean, $codigoSii]);

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

        $authUrl = "https://api-billing.koywe.com/V1/auth";
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
    private function getTipoDteNombre(int $code): string
    {
        switch ($code) {
            case 33:
                return 'Factura Electrónica';
            case 34:
                return 'Factura Exenta Electrónica';
            case 39:
                return 'Boleta Electrónica';
            case 52:
                return 'Guía de Despacho Electrónica';
            case 61:
                return 'Nota de Crédito Electrónica';
            case 56:
                return 'Nota de Débito Electrónica';
            default:
                return 'DTE (' . $code . ')';
        }
    }
}
