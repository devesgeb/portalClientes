<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class FactoController extends BaseController
{
    /**
     * GET /cobranza/facturas-facto
     */
    public function facturasFacto()
    {
        return view('cobranza/facturas_facto', [
            'title'      => 'Facturas y Guías Facto',
            'activePage' => 'facturas-facto',
            'base_url'   => base_url(),
            'assets_url' => base_url('public/assets/'),
        ]);
    }

    /**
     * GET /cobranza/facto/buscar-dtes
     * Consulta documentos emitidos (Facturas 33, Guías 52, Boletas 39) directamente desde la API oficial de Facto.
     */
    public function buscarDtes(): ResponseInterface
    {
        $fechaInicio = $this->request->getGet('fecha_inicio');
        $fechaFin    = $this->request->getGet('fecha_fin');
        $numero      = trim((string)($this->request->getGet('numero') ?? ''));
        $cliente     = trim((string)($this->request->getGet('cliente') ?? ''));
        $tipoDte     = trim((string)($this->request->getGet('tipo_dte') ?? ''));
        $requestedPage = (int)($this->request->getGet('page') ?: 1);

        $token = $this->getFactoToken();

        if (!$token) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al autenticar con la API de Facto. Verifique credenciales FACTO_* en el archivo .env.'
            ]);
        }

        // Construir query params nativos para Facto API
        $queryParams = [
            'page'                 => $requestedPage,
            'received_issued_flag' => 1 // 1 = Documentos Emitidos
        ];

        if ($fechaInicio) {
            $queryParams['issue_date_from'] = $fechaInicio;
        }
        if ($fechaFin) {
            $queryParams['issue_date_to'] = $fechaFin;
        }
        if ($tipoDte && in_array($tipoDte, ['33', '52', '39', '61'])) {
            $queryParams['document_type_taxbureau'] = $tipoDte;
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
        $totalItems = (int)($data['total_items'] ?? count($rawItems));
        $totalPages = (int)($data['page_count'] ?? 1);

        $documentos = [];
        $totalMontoBatch = 0;

        foreach ($rawItems as $item) {
            $folioDoc = (string)($item['document_number'] ?? '');
            if ($numero !== '' && strpos($folioDoc, $numero) === false) {
                continue;
            }

            $rutDoc = (string)($item['receiver_tax_id_code'] ?? '');
            $nombreDoc = (string)($item['receiver_legal_name'] ?? '');
            if ($cliente !== '') {
                $qLower = strtolower($cliente);
                if (strpos(strtolower($rutDoc), $qLower) === false && strpos(strtolower($nombreDoc), $qLower) === false) {
                    continue;
                }
            }

            $taxBureauCode = (int)($item['document_type_taxbureau'] ?? 0);
            $tipoNombre = $this->getTipoDteNombre($taxBureauCode);

            $neto  = (float)($item['net_amount'] ?? 0);
            $iva   = (float)($item['taxes_amount'] ?? 0);
            $total = (float)($item['total_amount'] ?? 0);

            $totalMontoBatch += $total;

            $documentos[] = [
                'id'              => $item['document_id'] ?? null,
                'folio'           => $folioDoc ?: '—',
                'fecha'           => $item['issue_date'] ?? '—',
                'codigo_sii'      => $taxBureauCode,
                'tipo_documento'  => $tipoNombre,
                'cliente_rut'     => $rutDoc ?: '—',
                'cliente_nombre'  => $nombreDoc ?: '—',
                'neto'            => $neto,
                'iva'             => $iva,
                'total'           => $total,
                'estado_sii'      => ((int)($item['taxbureau_sending_status'] ?? 0) === 1) ? 'Aceptado' : 'Pendiente'
            ];
        }

        return $this->response->setJSON([
            'success'     => true,
            'data'        => $documentos,
            'total_monto' => $totalMontoBatch,
            'pagination'  => [
                'current_page' => (int)($data['page'] ?? $requestedPage),
                'total_pages'  => $totalPages,
                'count'        => $totalItems
            ]
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

        $authUrl = "https://api-billing.koywe.com/V1/auth";
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
            case 33: return 'Factura Electrónica';
            case 34: return 'Factura Exenta Electrónica';
            case 39: return 'Boleta Electrónica';
            case 52: return 'Guía de Despacho Electrónica';
            case 61: return 'Nota de Crédito Electrónica';
            case 56: return 'Nota de Débito Electrónica';
            default: return 'DTE (' . $code . ')';
        }
    }
}
