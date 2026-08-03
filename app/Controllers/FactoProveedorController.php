<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class FactoProveedorController extends BaseController
{
    /**
     * Muestra la vista principal de Documentos Proveedores (Facto Compras API)
     */
    public function index(): string
    {
        $session = session();
        $userId = $session->get('is_logued_in');
        $loginModel = new \App\Models\LoginModel();
        $usuario = $userId ? $loginModel->obtenerPorId($userId) : null;

        $data = [
            'title'      => 'Documentos Proveedores — Factura Facto Compras',
            'base_url'   => base_url(),
            'assets_url' => base_url('public/assets/'),
            'activePage' => 'importar-documentos-proveedores',
            'usuario'    => $usuario,
        ];

        return view('facturacion/facturas_proveedores', $data);
    }

    /**
     * GET /facturacion/proveedores/buscar-dtes
     * Consulta la API de Facto para Documentos Recibidos (received_issued_flag = 0)
     */
    public function buscarDtes(): ResponseInterface
    {
        $token = $this->getFactoToken();
        if (!$token) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se pudo obtener autenticación con Facto API.'
            ]);
        }

        $cliente      = trim((string) ($this->request->getGet('cliente') ?? ''));
        $numero       = trim((string) ($this->request->getGet('numero') ?? ''));
        $tipoDte      = trim((string) ($this->request->getGet('tipo_dte') ?? ''));
        $estadoFiltro = trim((string) ($this->request->getGet('estado_pago') ?? ''));
        $fechaInicio  = trim((string) ($this->request->getGet('fecha_inicio') ?? ''));
        $fechaFin     = trim((string) ($this->request->getGet('fecha_fin') ?? ''));

        // Parámetros base para Facto API (received_issued_flag = 0 para Recibidos / Proveedores)
        $queryParams = [
            'received_issued_flag' => 0,
            'limit'                => 100
        ];

        if ($tipoDte !== '') {
            $queryParams['document_type_taxbureau'] = $tipoDte;
        }
        if ($fechaInicio !== '') {
            $queryParams['issue_date_from'] = $fechaInicio;
        }
        if ($fechaFin !== '') {
            $queryParams['issue_date_to'] = $fechaFin;
        }

        $db = \Config\Database::connect();

        // 1. Cargar estados de pago guardados en tbl_facto_pagos
        $estadosLocales = [];
        if ($db->tableExists('tbl_facto_pagos')) {
            $rowsFacto = $db->table('tbl_facto_pagos')->get()->getResultArray();
            foreach ($rowsFacto as $r) {
                $k = trim((string)$r['folio']) . '_' . (int)$r['codigo_sii'];
                $estadosLocales[$k] = [
                    'estado_pago'  => $r['estado_pago'],
                    'monto_pagado' => (float) $r['monto_pagado'],
                    'observacion'  => $r['observacion']
                ];
            }
        }

        // 2. Cargar impagas desde tbl_documentos_pagar por numero (folio)
        $pendientesPorFolio = [];
        if ($db->tableExists('tbl_documentos_pagar')) {
            $rowsPagar = $db->table('tbl_documentos_pagar')->where('impago >', 0)->get()->getResultArray();
            foreach ($rowsPagar as $p) {
                $folioRaw   = trim((string)$p['numero']);
                $folioClean = ltrim($folioRaw, '0');
                $impago     = (float)($p['impago'] ?? 0);
                $pagado     = (float)($p['pagado'] ?? 0);

                $dataItem = [
                    'estado_pago'  => 'pendiente',
                    'monto_pagado' => $pagado,
                    'impago'       => $impago,
                    'observacion'  => 'Cuentas por Pagar (Deuda Impaga)'
                ];

                if ($folioRaw !== '') {
                    $pendientesPorFolio[$folioRaw] = $dataItem;
                }
                if ($folioClean !== '') {
                    $pendientesPorFolio[$folioClean] = $dataItem;
                }
            }
        }

        // 3. Consultar API de Facto (Received = Proveedores)
        $url = 'https://api-billing.koywe.com/V1/documents?' . http_build_query($queryParams);
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$token}", "Accept: application/json"]);
        $responseRaw = curl_exec($ch);
        curl_close($ch);

        $dataApi  = json_decode($responseRaw, true);
        $rawItems = $dataApi['_embedded']['documents'] ?? $dataApi['_embedded']['items'] ?? [];

        // Resolución inteligente de Razón Social de Proveedores (Caché local + Multi-cURL)
        $proveedoresCache = [];
        if ($db->tableExists('tbl_proveedores')) {
            $rowsProv = $db->table('tbl_proveedores')->get()->getResultArray();
            foreach ($rowsProv as $prov) {
                $rutP = trim((string)$prov['rut']);
                if (!empty($rutP) && !empty($prov['nombre'])) {
                    $proveedoresCache[$rutP] = $prov['nombre'];
                }
            }
        }

        $itemsToFetch = [];
        foreach ($rawItems as $idx => $item) {
            $rutIssuer = trim((string)($item['issuer_tax_id_code'] ?? ''));
            if (!empty($rutIssuer) && empty($proveedoresCache[$rutIssuer]) && !empty($item['document_id'])) {
                $itemsToFetch[$idx] = $item['document_id'];
            }
        }

        if (!empty($itemsToFetch)) {
            $mh = curl_multi_init();
            $curlHandles = [];
            foreach ($itemsToFetch as $idx => $docId) {
                $chHandle = curl_init("https://api-billing.koywe.com/V1/documents/{$docId}");
                curl_setopt($chHandle, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($chHandle, CURLOPT_TIMEOUT, 5);
                curl_setopt($chHandle, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$token}", "Accept: application/json"]);
                curl_multi_add_handle($mh, $chHandle);
                $curlHandles[$idx] = $chHandle;
            }
            $running = null;
            do {
                curl_multi_exec($mh, $running);
                curl_multi_select($mh);
            } while ($running > 0);

            foreach ($curlHandles as $idx => $chHandle) {
                $content = curl_multi_getcontent($chHandle);
                curl_multi_remove_handle($mh, $chHandle);
                curl_close($chHandle);
                $detail = json_decode($content, true);
                $issuerName = trim((string)($detail['header']['issuer_legal_name'] ?? ''));
                $issuerRut  = trim((string)($detail['header']['issuer_tax_id_code'] ?? ''));
                if (!empty($issuerRut) && !empty($issuerName) && $issuerName !== 'N/A' && $issuerName !== 'FE ALIMENTOS SPA') {
                    $proveedoresCache[$issuerRut] = $issuerName;
                    if ($db->tableExists('tbl_proveedores')) {
                        $existP = $db->table('tbl_proveedores')->where('rut', $issuerRut)->get()->getRowArray();
                        if (!$existP) {
                            $db->table('tbl_proveedores')->insert([
                                'nombre'     => $issuerName,
                                'rut'        => $issuerRut,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                            ]);
                        }
                    }
                }
            }
            curl_multi_close($mh);
        }

        $documentos = [];
        $todayDate = date('Y-m-d');

        foreach ($rawItems as $item) {
            $folioDoc = (string) ($item['document_number'] ?? '');
            if ($numero !== '' && strpos($folioDoc, $numero) === false) {
                continue;
            }

            // Para Recibidos / Compras: el emisor es el Proveedor real
            $rutDoc = trim((string)($item['issuer_tax_id_code'] ?? ''));
            $nombreDoc = $proveedoresCache[$rutDoc] ?? (trim((string)($item['issuer_legal_name'] ?? '')) ?: ('Proveedor RUT ' . $rutDoc));
            if ($nombreDoc === 'FE ALIMENTOS SPA' || empty($nombreDoc)) {
                $nombreDoc = 'Proveedor ' . $rutDoc;
            }

            if ($cliente !== '') {
                $qLower = strtolower($cliente);
                if (strpos(strtolower($rutDoc), $qLower) === false && strpos(strtolower($nombreDoc), $qLower) === false) {
                    continue;
                }
            }

            $taxBureauCode = (int) ($item['document_type_taxbureau'] ?? 0);
            $tipoNombre    = $this->getTipoDteNombre($taxBureauCode);

            $neto     = (float) ($item['net_amount'] ?? 0);
            $iva      = (float) ($item['taxes_amount'] ?? 0);
            $total    = (float) ($item['total_amount'] ?? 0);
            $docFecha = trim((string)($item['issue_date'] ?? ''));

            $localKey      = $folioDoc . '_' . $taxBureauCode;
            $folioDocClean = ltrim($folioDoc, '0');

            if (isset($pendientesPorFolio[$folioDoc])) {
                $estadoPago  = $pendientesPorFolio[$folioDoc]['estado_pago'];
                $montoPagado = $pendientesPorFolio[$folioDoc]['monto_pagado'];
                $total       = $pendientesPorFolio[$folioDoc]['impago'];
                $obs         = 'Cuentas por Pagar (Deuda Impaga)';
            } elseif (isset($pendientesPorFolio[$folioDocClean])) {
                $estadoPago  = $pendientesPorFolio[$folioDocClean]['estado_pago'];
                $montoPagado = $pendientesPorFolio[$folioDocClean]['monto_pagado'];
                $total       = $pendientesPorFolio[$folioDocClean]['impago'];
                $obs         = 'Cuentas por Pagar (Deuda Impaga)';
            } elseif (isset($estadosLocales[$localKey])) {
                $estadoPago  = $estadosLocales[$localKey]['estado_pago'];
                $montoPagado = $estadosLocales[$localKey]['monto_pagado'];
                $obs         = ($estadosLocales[$localKey]['observacion'] === 'Cuentas por Pagar (Deuda Impaga)')
                               ? 'Pendiente por Cargar a Cuentas por Pagar'
                               : $estadosLocales[$localKey]['observacion'];
            } elseif ($estadoFiltro === 'pendiente') {
                $estadoPago  = 'pendiente';
                $montoPagado = 0.0;
                $obs         = 'Pendiente por Cargar a Cuentas por Pagar';
            } elseif ($docFecha === $todayDate) {
                $estadoPago  = 'pendiente';
                $montoPagado = 0.0;
                $obs         = 'Emitida hoy (' . $docFecha . ') - Pendiente';
            } else {
                $estadoPago  = 'pagada';
                $montoPagado = $total;
                $obs         = 'Conciliado (Sin deuda impaga)';
            }

            if ($estadoFiltro !== '' && $estadoPago !== $estadoFiltro) {
                continue;
            }

            $documentos[] = [
                'id'             => $item['document_id'] ?? null,
                'folio'          => $folioDoc ?: '—',
                'fecha'          => $item['issue_date'] ?? '—',
                'codigo_sii'     => $taxBureauCode,
                'tipo_documento' => $tipoNombre,
                'proveedor_rut'  => $rutDoc ?: '—',
                'proveedor_nombre' => $nombreDoc ?: '—',
                'cliente_rut'    => $rutDoc ?: '—',
                'cliente_nombre' => $nombreDoc ?: '—',
                'neto'           => $neto,
                'iva'            => $iva,
                'total'          => $total,
                'estado_sii'     => ((int) ($item['taxbureau_sending_status'] ?? 0) === 1) ? 'Aceptado' : 'Pendiente',
                'estado_pago'    => $estadoPago,
                'monto_pagado'   => $montoPagado,
                'observacion'    => $obs
            ];
        }

        // Deduplicación limpia por Folio
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

        return $this->response->setJSON([
            'success'     => true,
            'data'        => $documentos,
            'total_monto' => $totalMontoBatch,
            'count'       => count($documentos)
        ]);
    }

    /**
     * POST /facturacion/proveedores/importar-a-pagar
     * Traslada los DTEs de proveedores seleccionados directamente a tbl_documentos_pagar.
     */
    public function importarAPagar(): ResponseInterface
    {
        $raw = $this->request->getBody();
        $json = json_decode($raw, true) ?: $this->request->getPost();

        $documentos = $json['documentos'] ?? [];

        if (empty($documentos)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Debe seleccionar al menos un documento para trasladar a Cuentas por Pagar.'
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

            $folioClean      = ltrim($folioRaw, '0');
            $rutProveedor    = trim((string)($doc['proveedor_rut'] ?? $doc['cliente_rut'] ?? ''));
            $nombreProveedor = trim((string)($doc['proveedor_nombre'] ?? $doc['cliente_nombre'] ?? 'Proveedor Facto'));
            $codigoSii       = (int)($doc['codigo_sii'] ?? 33);
            $fecha           = trim((string)($doc['fecha'] ?? date('Y-m-d')));
            $total           = (float)($doc['total'] ?? 0);

            $tipoNombre = $this->getTipoDteNombre($codigoSii);

            // 1. Asegurar registro del proveedor en tbl_proveedores si no existe
            if (!empty($rutProveedor)) {
                $existProv = $db->table('tbl_proveedores')->where('rut', $rutProveedor)->get()->getRowArray();
                if (!$existProv) {
                    $db->table('tbl_proveedores')->insert([
                        'nombre'     => $nombreProveedor,
                        'rut'        => $rutProveedor,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }

            // 2. Insertar / Actualizar en tbl_documentos_pagar
            $existing = $db->table('tbl_documentos_pagar')->where('numero', $folioClean)->get()->getRowArray();
            if ($existing) {
                $db->table('tbl_documentos_pagar')->where('id', $existing['id'])->update([
                    'rut_proveedor'  => $rutProveedor,
                    'tipo_documento' => $tipoNombre,
                    'fecha'          => $fecha,
                    'total'          => $total,
                    'impago'         => $total,
                    'comentario'     => 'Importado desde Facto API Compras',
                    'updated_at'     => date('Y-m-d H:i:s')
                ]);
            } else {
                $db->table('tbl_documentos_pagar')->insert([
                    'rut_proveedor'  => $rutProveedor,
                    'tipo_documento' => $tipoNombre,
                    'numero'         => $folioClean,
                    'fecha'          => $fecha,
                    'total'          => $total,
                    'pagado'         => 0.00,
                    'impago'         => $total,
                    'comentario'     => 'Importado desde Facto API Compras',
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s')
                ]);
            }

            // 3. Registrar en tbl_facto_pagos en estado 'pendiente'
            $sqlFacto = "INSERT INTO `tbl_facto_pagos` (`folio`, `codigo_sii`, `estado_pago`, `monto_pagado`, `observacion`)
                         VALUES (?, ?, 'pendiente', 0.00, 'Cuentas por Pagar (Deuda Impaga)')
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
                'message' => 'Ocurrió un error al guardar los documentos en Cuentas por Pagar.'
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => "Se trasladaron {$importedCount} documentos a Cuentas por Pagar con éxito.",
            'count'   => $importedCount
        ]);
    }

    /**
     * POST /facturacion/proveedores/actualizar-estado-pago
     */
    public function actualizarEstadoPago(): ResponseInterface
    {
        $raw = $this->request->getBody();
        $json = json_decode($raw, true) ?: $this->request->getPost();

        $folio       = trim((string) ($json['folio'] ?? ''));
        $codigoSii   = (int) ($json['codigo_sii'] ?? 33);
        $estadoPago  = trim((string) ($json['estado_pago'] ?? 'pendiente'));
        $montoPagado = (float) ($json['monto_pagado'] ?? 0);
        $obs         = trim((string) ($json['observacion'] ?? ''));

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
            $db->table('tbl_documentos_pagar')->where('numero', $folio)->update([
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
     * POST /facturacion/proveedores/actualizar-estado-masivo
     */
    public function actualizarEstadoMasivo(): ResponseInterface
    {
        $raw = $this->request->getBody();
        $json = json_decode($raw, true) ?: $this->request->getPost();

        $documentos  = $json['documentos'] ?? [];
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
                $db->table('tbl_documentos_pagar')->where('numero', $folio)->update([
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
            'count'   => $updatedCount
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

        $clientId     = env('FACTO_CLIENT_ID') ?: 'd1a179675157';
        $clientSecret = env('FACTO_CLIENT_SECRET') ?: '5de78437e4bc19ebcada104b254d5529';
        $username     = env('FACTO_USERNAME') ?: '77775829-2/5634';
        $password     = env('FACTO_PASSWORD') ?: '1de8af70e35aa7e0d2c7b6d4750cad25';

        $authUrl  = "https://api-billing.koywe.com/V1/auth";
        $authBody = [
            "grant_type"    => "password",
            "client_id"     => $clientId,
            "client_secret" => $clientSecret,
            "username"      => $username,
            "password"      => $password
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
                cache()->save('facto_bearer_token', $token, 3300);
                return $token;
            }
        }

        return null;
    }

    private function getTipoDteNombre(int $code): string
    {
        switch ($code) {
            case 33: return 'Factura Electrónica';
            case 34: return 'Factura Exenta Electrónica';
            case 39: return 'Boleta Electrónica';
            case 52: return 'Guía de Despacho Electrónica';
            case 56: return 'Nota de Débito Electrónica';
            case 61: return 'Nota de Crédito Electrónica';
            default: return 'DTE ' . $code;
        }
    }
}
