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

        $rawItems = [];
        $totalItems = 0;
        $totalPages = 1;
        $todayDate = date('Y-m-d');

        // Si se busca 'pendiente' y estamos en la primera página sin rango de fecha cerrado, incluir siempre documentos de HOY primero
        if ($estadoFiltro === 'pendiente' && $requestedPage === 1 && !$fechaInicio && !$fechaFin) {
            $todayParams = $queryParams;
            $todayParams['issue_date_from'] = $todayDate;
            $todayParams['issue_date_to']   = $todayDate;
            $todayParams['page']            = 1;
            
            $todayUrl = 'https://api-billing.koywe.com/V1/documents?' . http_build_query($todayParams);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $todayUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$token}", "Accept: application/json"]);
            $resToday = curl_exec($ch);
            curl_close($ch);
            if ($resToday) {
                $dToday = json_decode($resToday, true);
                $docsToday = $dToday['_embedded']['documents'] ?? $dToday['_embedded']['items'] ?? [];
                if (!empty($docsToday)) {
                    $rawItems = array_merge($rawItems, $docsToday);
                }
            }
        }

        // Si se aplica un filtro por estado de pago (ej: 'pendiente'), escaneamos varias páginas de Facto API para recopilar suficientes resultados
        $maxScanPages = ($estadoFiltro !== '') ? 5 : 1;
        $scanStartPage = ($estadoFiltro !== '') ? (($requestedPage - 1) * 3 + 1) : $requestedPage;

        for ($p = 0; $p < $maxScanPages; $p++) {
            $currentPageToFetch = $scanStartPage + $p;
            $currentParams = $queryParams;
            $currentParams['page'] = $currentPageToFetch;

            $url = 'https://api-billing.koywe.com/V1/documents?' . http_build_query($currentParams);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer {$token}",
                "Accept: application/json"
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                $pageDocs = $data['_embedded']['documents'] ?? $data['_embedded']['items'] ?? [];
                if (empty($pageDocs)) break;

                // Evitar duplicados por id/folio
                $existingIds = array_column($rawItems, 'document_id');
                foreach ($pageDocs as $pd) {
                    if (!in_array($pd['document_id'] ?? null, $existingIds, true)) {
                        $rawItems[] = $pd;
                    }
                }

                $totalItems = (int) ($data['total_items'] ?? count($rawItems));
                $totalPages = (int) ($data['page_count'] ?? 1);

                if ($currentPageToFetch >= $totalPages) break;
            } else {
                break;
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
            $st         = ($pagado > 0) ? 'parcial' : 'pendiente';

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

            if (isset($estadosLocales[$localKey])) {
                $estadoPago  = $estadosLocales[$localKey]['estado_pago'];
                $montoPagado = $estadosLocales[$localKey]['monto_pagado'];
                $obs         = $estadosLocales[$localKey]['observacion'];
            } elseif ($docFecha === $todayDate) {
                $estadoPago  = 'pendiente';
                $montoPagado = 0.0;
                $obs         = 'Emitida hoy (' . $docFecha . ') - Pendiente';
            } elseif (isset($pendientesPorFolio[$folioDoc])) {
                $estadoPago  = $pendientesPorFolio[$folioDoc]['estado_pago'];
                $montoPagado = $pendientesPorFolio[$folioDoc]['monto_pagado'];
                $obs         = $pendientesPorFolio[$folioDoc]['observacion'];
            } elseif (isset($pendientesPorFolio[$folioDocClean])) {
                $estadoPago  = $pendientesPorFolio[$folioDocClean]['estado_pago'];
                $montoPagado = $pendientesPorFolio[$folioDocClean]['monto_pagado'];
                $obs         = $pendientesPorFolio[$folioDocClean]['observacion'];
            } else {
                $estadoPago  = 'pagada';
                $montoPagado = $total;
                $obs         = 'Conciliado (Sin deuda impaga)';
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

        $countResult = count($documentos);
        if ($estadoFiltro !== '' || $numero !== '' || $cliente !== '') {
            $totalCount  = $countResult;
            $calcPages   = (int)ceil($totalCount / 25);
            $totalPages  = max(1, $calcPages);
            $currentPage = 1;
        } else {
            $totalCount  = $totalItems;
            $currentPage = (int) ($data['page'] ?? $requestedPage);
        }

        return $this->response->setJSON([
            'success'     => true,
            'data'        => $documentos,
            'total_monto' => $totalMontoBatch,
            'pagination'  => [
                'current_page' => $currentPage,
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
