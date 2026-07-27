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
            'page' => $requestedPage,
            'received_issued_flag' => 1 // 1 = Documentos Emitidos
        ];

        if ($fechaInicio) {
            $queryParams['issue_date_from'] = $fechaInicio;
        }
        if ($fechaFin) {
            $queryParams['issue_date_to'] = $fechaFin;
        }
        if ($tipoDte && isset($typeIdMap[$tipoDte])) {
            $queryParams['document_type_id'] = $typeIdMap[$tipoDte];
        }

        $url = 'https://api-billing.koywe.com/V1/documents?' . http_build_query($queryParams);

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
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return $this->response->setJSON([
                'success' => false,
                'message' => "Error al consultar la API de Facto (Código HTTP: {$httpCode}). " . ($curlError ?: '')
            ]);
        }

        $data = json_decode($response, true);
        $rawItems = $data['_embedded']['documents'] ?? $data['_embedded']['items'] ?? [];
        $totalItems = (int) ($data['total_items'] ?? count($rawItems));
        $totalPages = (int) ($data['page_count'] ?? 1);

        // Obtener folios del batch para consultar en tbl_facto_pagos y tbl_documentos_cobrar
        $foliosBatch = array_filter(array_column($rawItems, 'document_number'));
        $estadosLocales = [];
        $pendientesBD   = [];

        if (!empty($foliosBatch)) {
            $db = \Config\Database::connect();

            // 1. Cargar overrides o estados guardados explícitamente en tbl_facto_pagos
            $builder = $db->table('tbl_facto_pagos')->whereIn('folio', array_map('strval', $foliosBatch));
            $rows = $builder->get()->getResultArray();
            foreach ($rows as $r) {
                $key = $r['folio'] . '_' . $r['codigo_sii'];
                $estadosLocales[$key] = [
                    'estado_pago'  => $r['estado_pago'],
                    'monto_pagado' => (float) $r['monto_pagado'],
                    'observacion'  => $r['observacion']
                ];
            }

            // 2. Consultar documentos pendientes en tbl_documentos_cobrar por folio
            $builderCobrar = $db->table('tbl_documentos_cobrar')->whereIn('numero', array_map('strval', $foliosBatch));
            $rowsCobrar = $builderCobrar->get()->getResultArray();
            foreach ($rowsCobrar as $c) {
                $folioC   = (string)$c['numero'];
                $rutClean = strtolower(preg_replace('/[^0-9kK]/', '', (string)$c['rut_cliente']));
                $impago   = (float)($c['impago'] ?? 0);
                $pagado   = (float)($c['pagado'] ?? 0);

                if ($impago > 0) {
                    $st = ($pagado > 0) ? 'parcial' : 'pendiente';
                    $pendientesBD[$folioC . '_' . $rutClean] = [
                        'estado_pago'  => $st,
                        'monto_pagado' => $pagado,
                        'impago'       => $impago
                    ];
                }
            }
        }

        $documentos = [];
        $totalMontoBatch = 0;

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

            $neto = (float) ($item['net_amount'] ?? 0);
            $iva = (float) ($item['taxes_amount'] ?? 0);
            $total = (float) ($item['total_amount'] ?? 0);

            // Determinación por Base de Datos:
            // 1. Si existe en tbl_facto_pagos (modificado manualmente), respetar ese estado.
            // 2. Si existe en tbl_documentos_cobrar con impago > 0, asignar 'pendiente' o 'parcial'.
            // 3. Si NO existe en tbl_documentos_cobrar (o impago <= 0), marcar como 'pagada' automáticamente.
            $localKey  = $folioDoc . '_' . $taxBureauCode;
            $rutClean  = strtolower(preg_replace('/[^0-9kK]/', '', $rutDoc));
            $cobrarKey = $folioDoc . '_' . $rutClean;

            if (isset($estadosLocales[$localKey])) {
                $estadoPago  = $estadosLocales[$localKey]['estado_pago'];
                $montoPagado = $estadosLocales[$localKey]['monto_pagado'];
                $obs         = $estadosLocales[$localKey]['observacion'];
            } elseif (isset($pendientesBD[$cobrarKey])) {
                $estadoPago  = $pendientesBD[$cobrarKey]['estado_pago'];
                $montoPagado = $pendientesBD[$cobrarKey]['monto_pagado'];
                $obs         = 'Registrado en Cobranza (Impago)';
            } else {
                // Las que no estén en cuentas por cobrar / pendientes se dejan TODAS PAGADAS automáticamente
                $estadoPago  = 'pagada';
                $montoPagado = $total;
                $obs         = 'Conciliado (Sin deuda en BD)';
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

        return $this->response->setJSON([
            'success' => true,
            'data' => $documentos,
            'total_monto' => $totalMontoBatch,
            'pagination' => [
                'current_page' => (int) ($data['page'] ?? $requestedPage),
                'total_pages' => $totalPages,
                'count' => $totalItems
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
