<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class WebhookReceiver extends BaseController
{
    private $logFile;

    public function __construct()
    {
        $this->logFile = WRITEPATH . 'logs/tupana_webhooks.json';
    }

    /**
     * GET /webhook-test
     */
    public function index()
    {
        return view('tupana_test', [
            'title'      => 'Buscador de Documentos Tu Pana',
            'base_url'   => base_url(),
            'assets_url' => base_url('public/assets/'),
        ]);
    }

    /**
     * GET /cobranza/documentos-impago
     */
    public function documentosImpago()
    {
        return view('cobranza/documentos_impago', [
            'title'      => 'Documentos Impago',
            'activePage' => 'documentos-impago',
            'base_url'   => base_url(),
            'assets_url' => base_url('public/assets/'),
        ]);
    }

    /**
     * GET /webhook-test/buscar-dtes
     * Realiza una consulta 100% REAL a la API oficial de Tu Pana.
     * Retorna únicamente datos reales de la base de datos de Tu Pana para tu negocio.
     */
    public function buscarDtes(): ResponseInterface
    {
        $fechaInicio = $this->request->getGet('fecha_inicio');
        $fechaFin    = $this->request->getGet('fecha_fin');
        $numero      = trim((string)($this->request->getGet('numero') ?? ''));
        $cliente     = trim((string)($this->request->getGet('cliente') ?? ''));
        $soloImpagas = $this->request->getGet('solo_impagas') !== 'false';
        $requestedPage = (int)($this->request->getGet('page') ?: 1);

        $apiKey = env('TUPANA_API_KEY');

        if (!$apiKey) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Falta configurar TUPANA_API_KEY en el archivo .env de Portal.'
            ]);
        }

        $masterEntityId = env('TUPANA_MASTER_ENTITY_ID') ?: '2205607';
        $cacheKey = 'tupana_dtes_' . md5("{$fechaInicio}_{$fechaFin}_{$numero}_{$cliente}_" . ($soloImpagas ? 1 : 0));

        $allFiltered = cache($cacheKey);

        if ($allFiltered === null) {
            $allFiltered = [];
            $hasDateFilter = !empty($fechaInicio);
            $hasSearchFilter = !empty($fechaFin) || !empty($numero) || !empty($cliente);
            $maxPages = $hasDateFilter ? 15 : ($hasSearchFilter ? 8 : 4);
            $batchSize = 4; // 4 páginas simultáneas por ronda cURL multi

            $stopFetching = false;
            $currentStartPage = 1;

            while ($currentStartPage <= $maxPages && !$stopFetching) {
                $batchPages = range($currentStartPage, min($currentStartPage + $batchSize - 1, $maxPages));
                $responses = $this->fetchTupanaBatch($apiKey, $masterEntityId, $batchPages);

                foreach ($responses as $p => $data) {
                    if (!$data || !isset($data['results']) || empty($data['results'])) {
                        $stopFetching = true;
                        break;
                    }

                    foreach ($data['results'] as $item) {
                        $total = (float)($item['amount_with_iva'] ?? $item['total_amount'] ?? $item['total'] ?? 0);
                        $isPaid = isset($item['is_paid']) ? (bool)$item['is_paid'] : false;
                        $paymentStatus = $item['payment_status'] ?? 'unpaid';

                        $saldoPendiente = $total;
                        if (isset($item['pending_amount']) && $item['pending_amount'] !== null) {
                            $saldoPendiente = (float)$item['pending_amount'];
                        } elseif ($isPaid || $paymentStatus === 'paid') {
                            $saldoPendiente = 0.0;
                        }

                        if ($soloImpagas && $saldoPendiente <= 0) {
                            continue;
                        }

                        $fechaDoc = $item['date_issued'] ?? $item['date'] ?? $item['fecha'] ?? null;
                        $fechaDocClean = $fechaDoc ? substr($fechaDoc, 0, 10) : '';

                        if ($fechaInicio && $fechaDocClean && $fechaDocClean < $fechaInicio) {
                            $stopFetching = true;
                            break;
                        }

                        if ($fechaFin && $fechaDocClean && $fechaDocClean > $fechaFin) {
                            continue;
                        }

                        $folioDoc = (string)($item['folio'] ?? $item['number'] ?? '');
                        if ($numero !== '' && strpos($folioDoc, $numero) === false) {
                            continue;
                        }

                        $rutDoc = (string)($item['receiver_tax_id'] ?? $item['customer_rut'] ?? '');
                        $nombreDoc = (string)($item['receiver_name'] ?? $item['customer_name'] ?? '');
                        if ($cliente !== '') {
                            $qLower = strtolower($cliente);
                            if (strpos(strtolower($rutDoc), $qLower) === false && strpos(strtolower($nombreDoc), $qLower) === false) {
                                continue;
                            }
                        }

                        $allFiltered[] = [
                            'folio' => $folioDoc ?: '—',
                            'fecha' => $fechaDocClean ?: '—',
                            'tipo_documento' => $item['dte_type_description'] ?? $item['document_type'] ?? $item['tipo_documento'] ?? 'DTE',
                            'cliente_rut' => $rutDoc ?: '—',
                            'cliente_nombre' => $nombreDoc ?: '—',
                            'neto' => $item['net_amount'] ?? $item['neto'] ?? 0,
                            'iva' => $item['iva_amount'] ?? $item['iva'] ?? 0,
                            'total' => $total,
                            'estado_sii' => $item['sii_status'] ?? $item['estado_sii'] ?? 'Aceptado',
                            'saldo_pendiente' => $saldoPendiente
                        ];
                    }

                    if ($stopFetching) break;

                    $totalPagesTuPana = (int)($data['total_pages'] ?? 1);
                    if ($p >= $totalPagesTuPana) {
                        $stopFetching = true;
                        break;
                    }
                }

                $currentStartPage += $batchSize;
            }

            // Guardar en caché por 60 segundos
            cache()->save($cacheKey, $allFiltered, 60);
        }

        // Paginación precisa sobre la colección filtrada
        $perPage = 25;
        $totalCount = count($allFiltered);
        $totalMontoImpago = array_sum(array_column($allFiltered, 'saldo_pendiente'));
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $currentPage = min(max(1, $requestedPage), $totalPages);
        $offset = ($currentPage - 1) * $perPage;

        $pageItems = array_slice($allFiltered, $offset, $perPage);

        return $this->response->setJSON([
            'success' => true,
            'data' => $pageItems,
            'total_monto' => $totalMontoImpago,
            'pagination' => [
                'current_page' => $currentPage,
                'total_pages'  => $totalPages,
                'count'        => $totalCount
            ]
        ]);
    }

    /**
     * Consulta paralela vía cURL multi para múltiples páginas simultáneas
     */
    private function fetchTupanaBatch(string $apiKey, string $masterEntityId, array $pages): array
    {
        $mh = curl_multi_init();
        $handles = [];

        foreach ($pages as $p) {
            $url = 'https://api.tupana.ai/v1/documents?master_entity_id=' . urlencode($masterEntityId) . '&page=' . $p;
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 6,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Api-Key ' . $apiKey,
                    'Accept: application/json'
                ]
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$p] = $ch;
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh, 0.05);
        } while ($running > 0);

        $results = [];
        foreach ($handles as $p => $ch) {
            $response = curl_multi_getcontent($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $results[$p] = json_decode($response, true);
            }
        }
        curl_multi_close($mh);

        ksort($results);
        return $results;
    }

    /**
     * POST /webhook/tupana
     */
    public function receive(): ResponseInterface
    {
        $rawBody = $this->request->getBody();
        $payload = json_decode($rawBody, true);
        $signature = $this->request->getHeaderLine('X-Pana-Signature');
        $eventType = $this->request->getHeaderLine('X-Pana-Event') ?: ($payload['event'] ?? 'unknown');

        $logData = [
            'timestamp'  => date('Y-m-d H:i:s'),
            'ip'         => $this->request->getIPAddress(),
            'event'      => $eventType,
            'signature'  => $signature,
            'payload'    => $payload,
            'processed'  => true,
            'message' => 'Webhook recibido y registrado.'
        ];

        $this->saveToLog($logData);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Registrado correctamente',
            'received_event' => $eventType
        ]);
    }

    /**
     * GET /webhook-test/logs
     */
    public function getLogs(): ResponseInterface
    {
        if (!file_exists($this->logFile)) {
            return $this->response->setJSON([]);
        }

        $content = file_get_contents($this->logFile);
        $logs = json_decode('[' . rtrim(str_replace("\n", ",", $content), ',') . ']', true);
        
        $logs = array_reverse($logs);
        return $this->response->setJSON(array_slice($logs, 0, 15));
    }

    /**
     * DELETE /webhook-test/logs
     */
    public function clearLogs(): ResponseInterface
    {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }
        return $this->response->setJSON(['success' => true]);
    }

    private function saveToLog($data)
    {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($this->logFile, json_encode($data) . "\n", FILE_APPEND | LOCK_EX);
    }
}
