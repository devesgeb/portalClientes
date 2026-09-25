<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class WebhookController extends BaseController
{
    public function __construct()
    {
        $this->ensureTableExists();
    }

    /**
     * Asegura la existencia de la tabla local tbl_webhook_logs
     */
    private function ensureTableExists(): void
    {
        $db = \Config\Database::connect();
        $sql = "CREATE TABLE IF NOT EXISTS `tbl_webhook_logs` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `topic` VARCHAR(100) NULL,
          `resource` VARCHAR(100) NULL,
          `event` VARCHAR(100) NULL,
          `source` VARCHAR(50) DEFAULT 'woocommerce',
          `payload` LONGTEXT NULL,
          `summary` VARCHAR(255) NULL,
          `status` VARCHAR(20) DEFAULT 'procesado',
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $db->query($sql);
    }

    /**
     * GET /webhook/monitor
     * Vista interactiva de monitoreo de Webhooks en tiempo real (Localhost)
     */
    public function monitor()
    {
        return view('administracion/webhook_monitor');
    }

    /**
     * POST /webhook/receiver (o /api/webhook/woocommerce)
     * Receptor HTTP de Webhooks de WooCommerce / Facto
     */
    public function receiver(): ResponseInterface
    {
        $request = $this->request;
        $db      = \Config\Database::connect();

        // 1. Obtener headers de WooCommerce / Facto
        $topic    = $request->getHeaderLine('X-WC-Webhook-Topic') ?: 'custom.event';
        $resource = $request->getHeaderLine('X-WC-Webhook-Resource') ?: 'product';
        $event    = $request->getHeaderLine('X-WC-Webhook-Event') ?: 'updated';
        $source   = $request->getHeaderLine('X-WC-Webhook-Source') ?: 'WooCommerce / Facto';

        // 2. Obtener el cuerpo raw de la petición
        $rawBody = $request->getBody();
        $json = json_decode($rawBody, true);

        if (!$json) {
            $json = $request->getPost();
        }

        $summaryParts = [];
        $sku   = $json['sku'] ?? $json['product_sku'] ?? null;
        $stock = $json['stock_quantity'] ?? $json['stock'] ?? $json['quantity'] ?? null;
        $price = $json['price'] ?? $json['regular_price'] ?? $json['unit_price'] ?? null;
        $name  = $json['name'] ?? $json['title'] ?? $json['line_description'] ?? null;

        if ($sku)   $summaryParts[] = "SKU: {$sku}";
        if ($name)  $summaryParts[] = "Nombre: {$name}";
        if ($stock !== null) $summaryParts[] = "Stock: {$stock}";
        if ($price !== null) $summaryParts[] = "Precio: $" . number_format((float)$price, 0, ',', '.');

        $summary = !empty($summaryParts) ? implode(' | ', $summaryParts) : 'Payload recibido sin resumen estructurado';

        // 3. Si viene SKU y Stock, actualizar automáticamente el stock en tbl_productos si existe
        if ($sku && $db->tableExists('tbl_productos')) {
            $prod = $db->table('tbl_productos')->where('sku', $sku)->get()->getRowArray();
            if ($prod) {
                $updateData = ['updated_at' => date('Y-m-d H:i:s')];
                if ($stock !== null) {
                    $updateData['stock_bodega_ppral'] = (float)$stock;
                }
                if ($price !== null) {
                    $updateData['precio_venta_total'] = (float)$price;
                }
                $db->table('tbl_productos')->where('sku', $prod['sku'])->update($updateData);
                $summary .= ' [Stock local en Bodega actualizado]';
            }
        }

        // 4. Guardar log en tbl_webhook_logs
        $db->table('tbl_webhook_logs')->insert([
            'topic'      => $topic,
            'resource'   => $resource,
            'event'      => $event,
            'source'     => $source,
            'payload'    => !empty($rawBody) ? $rawBody : json_encode($json, JSON_UNESCAPED_UNICODE),
            'summary'    => $summary,
            'status'     => 'procesado',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Webhook recibido y procesado correctamente en Localhost.',
            'summary' => $summary,
            'timestamp' => date('Y-m-d H:i:s')
        ])->setStatusCode(200);
    }

    /**
     * GET /webhook/logs-json
     * Retorna los logs más recientes de webhooks para el dashboard
     */
    public function getLogsJson(): ResponseInterface
    {
        $db = \Config\Database::connect();
        $logs = $db->table('tbl_webhook_logs')
                   ->orderBy('id', 'DESC')
                   ->limit(50)
                   ->get()
                   ->getResultArray();

        $totalCount = $db->table('tbl_webhook_logs')->countAllResults();

        return $this->response->setJSON([
            'success' => true,
            'total'   => $totalCount,
            'logs'    => $logs
        ]);
    }

    /**
     * POST /webhook/test-simular
     * Simula el envío de un Webhook desde la UI para pruebas
     */
    public function testSimular(): ResponseInterface
    {
        $raw = $this->request->getBody();
        $json = json_decode($raw, true) ?: $this->request->getPost();

        $sku   = trim((string)($json['sku'] ?? 'PROCE-01'));
        $nombre= trim((string)($json['nombre'] ?? 'Papa Natural - Semi rustica 12x12'));
        $stock = (int)($json['stock'] ?? 150);
        $precio= (float)($json['precio'] ?? 1300);
        $evento= trim((string)($json['evento'] ?? 'product.updated'));

        $mockPayload = [
            'id' => rand(1000, 9999),
            'name' => $nombre,
            'sku' => $sku,
            'stock_quantity' => $stock,
            'price' => $precio,
            'regular_price' => $precio,
            'manage_stock' => true,
            'stock_status' => 'instock',
            'date_modified' => date('Y-m-d\TH:i:s'),
        ];

        $db = \Config\Database::connect();
        $summary = "SKU: {$sku} | Nombre: {$nombre} | Stock: {$stock} | Precio: $" . number_format($precio, 0, ',', '.') . " (Simulado)";

        // Actualizar stock local si existe
        if ($db->tableExists('tbl_productos')) {
            $prod = $db->table('tbl_productos')->where('sku', $sku)->get()->getRowArray();
            if ($prod) {
                $db->table('tbl_productos')->where('id', $prod['id'])->update([
                    'stock' => $stock,
                    'precio_venta' => $precio,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                $summary .= ' [Stock local en BD actualizado]';
            }
        }

        $db->table('tbl_webhook_logs')->insert([
            'topic'      => $evento,
            'resource'   => 'product',
            'event'      => 'updated',
            'source'     => 'Simulador Localhost UI',
            'payload'    => json_encode($mockPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'summary'    => $summary,
            'status'     => 'procesado',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Simulación de Webhook ejecutada con éxito en Localhost.',
            'summary' => $summary
        ]);
    }

    /**
     * DELETE /webhook/limpiar-logs
     * Limpia el historial de logs
     */
    public function limpiarLogs(): ResponseInterface
    {
        $db = \Config\Database::connect();
        $db->table('tbl_webhook_logs')->truncate();

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Historial de logs limpiado con éxito.'
        ]);
    }
}
