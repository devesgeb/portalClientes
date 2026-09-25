<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class CronController extends BaseController
{
    /**
     * Cron endpoint: GET /cron/sync-facto
     * Ejecutado automáticamente a las 07:00, 17:00 y 22:00
     * Consulta a Facto API todos los DTEs emitidos y los guarda/actualiza en tbl_facto_pagos
     */
    public function syncFacto(): ResponseInterface
    {
        $db = \Config\Database::connect();
        
        // 1. Asegurar tabla y columnas
        $this->ensureTableExists($db);

        // 2. Autenticación Facto API
        $token = $this->getFactoToken();
        if (!$token) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'El servidor externo de Facto API (Koywe) está experimentando una caída temporal (Error 502 Bad Gateway). Por favor, intenta de nuevo en unos minutos.'
            ])->setStatusCode(500);
        }

        // 3. Obtener la fecha del último documento registrado en tbl_facto_pagos
        $lastRow = $db->table('tbl_facto_pagos')
                      ->selectMax('fecha_emision', 'max_fecha')
                      ->get()
                      ->getRowArray();

        $fromDate = !empty($lastRow['max_fecha']) ? $lastRow['max_fecha'] : date('Y-01-01');

        // Consultar a Facto API únicamente los DTEs emitidos a partir del último documento registrado
        $rawItems = $this->fetchFactoDocuments($token, $fromDate);

        // 4. Cargar folios impagos de Cuentas por Cobrar (tbl_documentos_cobrar)
        $cobrarFolios = [];
        if ($db->tableExists('tbl_documentos_cobrar')) {
            $rowsCobrar = $db->table('tbl_documentos_cobrar')->where('impago >', 0)->get()->getResultArray();
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

        // 5. Cargar estados guardados previamente en tbl_facto_pagos
        $estadosExistentes = [];
        $rowsFacto = $db->table('tbl_facto_pagos')->get()->getResultArray();
        foreach ($rowsFacto as $rf) {
            $key = trim((string)$rf['folio']) . '_' . $rf['codigo_sii'];
            $estadosExistentes[$key] = $rf['estado_pago'];
        }

        $todayDate = date('Y-m-d');
        $processed = 0;
        $newItemsCount = 0;

        foreach ($rawItems as $item) {
            $folioDoc      = trim((string)($item['document_number'] ?? ''));
            if (empty($folioDoc)) continue;

            $taxBureauCode = (int)($item['document_type_taxbureau'] ?? 33);
            $rutDoc        = trim((string)($item['receiver_tax_id_code'] ?? ''));
            $nombreDoc     = trim((string)($item['receiver_legal_name'] ?? ''));
            $neto          = (float)($item['net_amount'] ?? 0);
            $iva           = (float)($item['taxes_amount'] ?? 0);
            $total         = (float)($item['total_amount'] ?? 0);
            $docFechaRaw   = trim((string)($item['issue_date'] ?? ''));
            $docFecha      = !empty($docFechaRaw) ? date('Y-m-d', strtotime($docFechaRaw)) : null;
            $estadoSii     = ((int)($item['taxbureau_sending_status'] ?? 0) === 1) ? 'Aceptado' : 'Pendiente';

            $folioDocClean = ltrim($folioDoc, '0');
            $key           = $folioDoc . '_' . $taxBureauCode;
            $existeEnCobrar= isset($cobrarFolios[$folioDoc]) || isset($cobrarFolios[$folioDocClean]);
            $esHoy         = ($docFecha === $todayDate);

            // Regla solicitada:
            // Toda factura o DTE nuevo traído por el Cron queda en estado 'pendiente' por defecto.
            // Si ya existía previamente en tbl_facto_pagos, se respeta el estado existente.
            if (isset($estadosExistentes[$key])) {
                $estadoPago  = $estadosExistentes[$key];
                $montoPagado = ($estadoPago === 'pagada') ? $total : 0.00;
                $obs         = 'Registrado / Sincronizado';
            } else {
                $estadoPago  = 'pendiente';
                $montoPagado = 0.00;
                $obs         = 'Ingresado por Cron Facto';
            }

            // INSERT OR UPDATE ON DUPLICATE KEY en tbl_facto_pagos (preservando estado_pago si ya existe)
            $sql = "INSERT INTO `tbl_facto_pagos` 
                    (`folio`, `codigo_sii`, `rut_cliente`, `razon_social`, `monto_neto`, `monto_iva`, `monto_total`, `fecha_emision`, `estado_sii`, `estado_pago`, `monto_pagado`, `observacion`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                      `rut_cliente` = VALUES(`rut_cliente`),
                      `razon_social` = VALUES(`razon_social`),
                      `monto_neto` = VALUES(`monto_neto`),
                      `monto_iva` = VALUES(`monto_iva`),
                      `monto_total` = VALUES(`monto_total`),
                      `fecha_emision` = VALUES(`fecha_emision`),
                      `estado_sii` = VALUES(`estado_sii`),
                      `updated_at` = CURRENT_TIMESTAMP;";

            $db->query($sql, [
                $folioDoc,
                $taxBureauCode,
                $rutDoc,
                $nombreDoc,
                $neto,
                $iva,
                $total,
                $docFecha,
                $estadoSii,
                $estadoPago,
                $montoPagado,
                $obs
            ]);

            $processed++;
            if (!isset($estadosExistentes[$key])) {
                $newItemsCount++;
            }
        }

        // Si se encontraron o cargaron nuevos documentos, generar notificación
        if ($newItemsCount > 0) {
            $db->query("CREATE TABLE IF NOT EXISTS `tbl_notificaciones` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `titulo` VARCHAR(255) NOT NULL,
              `mensaje` TEXT NOT NULL,
              `tipo` VARCHAR(50) NOT NULL DEFAULT 'info',
              `url_accion` VARCHAR(255) NOT NULL DEFAULT '/facturacion/importar/ventas',
              `leido` TINYINT(1) NOT NULL DEFAULT 0,
              `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $db->query("INSERT INTO `tbl_notificaciones` (`titulo`, `mensaje`, `tipo`, `url_accion`, `leido`) VALUES (?, ?, 'info', ?, 0)", [
                'Nuevos Documentos de Ventas',
                "¡Hey! Se han detectado {$newItemsCount} documentos nuevos cargados por el Cron. Haz clic en ir a gestionar.",
                site_url('facturacion/importar/ventas')
            ]);
        }

        return $this->response->setJSON([
            'success'   => true,
            'message'   => "Sincronización Cron Facto completada a las " . date('H:i:s'),
            'processed' => $processed,
            'new_items' => $newItemsCount,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    private function fetchFactoDocuments(string $token, string $fromDate): array
    {
        $rawItems = [];

        // Consultar a Facto únicamente los documentos emitidos desde $fromDate
        $yearParams = [
            'received_issued_flag' => 1,
            'issue_date_from'      => $fromDate,
            'limit'                => 100
        ];

        for ($page = 1; $page <= 5; $page++) {
            $yearParams['page'] = $page;
            $url = 'https://api.facto.cl/v1/documents?' . http_build_query($yearParams);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 12);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$token}", "Accept: application/json"]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code === 200 && $resp) {
                $data = json_decode($resp, true);
                $items = $data['_embedded']['documents'] ?? $data['_embedded']['items'] ?? [];
                if (empty($items)) break;
                
                $existingIds = array_filter(array_column($rawItems, 'document_id'));
                foreach ($items as $it) {
                    $dId = $it['document_id'] ?? null;
                    if ($dId && !in_array($dId, $existingIds, true)) {
                        $rawItems[] = $it;
                    }
                }
                if (count($items) < 100) break;
            } else {
                break;
            }
        }

        return $rawItems;
    }

    private function getFactoToken(): ?string
    {
        $cachedToken = cache('facto_bearer_token');
        if (!empty($cachedToken) && is_string($cachedToken)) return $cachedToken;

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
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($authBody));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json', 'User-Agent: Mozilla/5.0']);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && $resp) {
            $data = json_decode($resp, true);
            $token = $data['access_token'] ?? null;
            if ($token) {
                cache()->save('facto_bearer_token', $token, 3300);
                return $token;
            }
        }
        return null;
    }

    private function ensureTableExists($db): void
    {
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

        // Migración de columnas adicionales si faltan
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
     * Cron endpoint: GET /cron/sync-facto-productos
     * Obtiene listas de precios, productos, stock y precios (neto y total) desde Facto API
     * y los guarda en tbl_facto_listas_precios y tbl_facto_productos
     */
    public function syncFactoProductos(): ResponseInterface
    {
        $db = \Config\Database::connect();
        $this->ensureProductoTablesExist($db);

        $token = $this->getFactoToken();
        if (!$token) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'El servidor externo de Facto API (Koywe) está experimentando una caída temporal (Error 502 Bad Gateway). Por favor, intenta de nuevo en unos minutos.'
            ])->setStatusCode(500);
        }

        // 1. Obtener y guardar Listas de Precios desde Facto API
        $mapListas = $this->fetchAndSavePriceLists($token, $db);

        // 2. Obtener lista de productos desde Facto API
        $productListSummary = $this->fetchFactoProductList($token);

        $syncedProducts = 0;
        $syncedRows = 0;

        foreach ($productListSummary as $pSummary) {
            $pId = $pSummary['product_id'] ?? null;
            if (!$pId) continue;

            // Obtener detalle completo del producto
            $pDetail = $this->fetchFactoProductDetail($token, $pId);
            if (!$pDetail) continue;

            $sku          = trim((string)($pDetail['sku'] ?? ''));
            if (empty($sku)) {
                $sku = 'FACTO-' . $pId;
            }
            $nombre       = trim((string)($pDetail['name'] ?? 'Producto ' . $pId));
            $descripcion  = trim((string)($pDetail['additional_details'] ?? $pDetail['invoicing_details'] ?? ''));
            $marca        = trim((string)($pDetail['brand'] ?? ''));
            $modelo       = trim((string)($pDetail['model'] ?? ''));
            $unidadMedida = trim((string)($pDetail['measure_unit'] ?? 'UN'));

            // Extraer costo neto y costo con impuesto (19% IVA)
            $costoNeto = (float)($pDetail['cost']['value'] ?? 0);
            $costoTotal = round($costoNeto * 1.19, 2);

            // Calcular Stock y Stock Reservado desde la estructura de inventarios
            $stock = 0.000;
            $stockReservado = 0.000;

            if (!empty($pDetail['inventories']['details']) && is_array($pDetail['inventories']['details'])) {
                foreach ($pDetail['inventories']['details'] as $invDet) {
                    $stock += (float)($invDet['available_quantity'] ?? 0);
                    $stockReservado += (float)($invDet['reserved_quantity'] ?? 0);
                }
            } else {
                if (isset($pDetail['inventories']['total_available'])) {
                    $stock = (float)$pDetail['inventories']['total_available'];
                }
                if (isset($pDetail['inventories']['total_reserved'])) {
                    $stockReservado = (float)$pDetail['inventories']['total_reserved'];
                }
            }

            $prices = $pDetail['price'] ?? [];
            if (empty($prices) || !is_array($prices)) {
                // Intentar obtener precios desde el endpoint dedicado /products/{id}/prices
                $prices = $this->fetchFactoProductPrices($token, $pId);
            }
            if (empty($prices)) {
                // Último fallback: guardar con precio 0 solo en Lista #1
                $prices = [[
                    'product_price_list_id' => 1,
                    'unit_net'   => 0,
                    'unit_tax'   => 0,
                    'unit_total' => 0
                ]];
            }

            foreach ($prices as $pr) {
                // La API devuelve price_list_id / price_net / price_vat / price_total
                // El fallback usa product_price_list_id / unit_net / unit_tax / unit_total
                $listaId     = (int)($pr['price_list_id'] ?? $pr['product_price_list_id'] ?? 1);
                $nombreLista = $mapListas[$listaId] ?? ('Lista #' . $listaId);
                $unitNet     = (float)($pr['price_net']   ?? $pr['unit_net']   ?? 0);
                $unitTax     = (float)($pr['price_vat']   ?? $pr['unit_tax']   ?? 0);
                $unitTotal   = (float)($pr['price_total'] ?? $pr['unit_total'] ?? 0);

                if ($unitTotal == 0 && $unitNet > 0) {
                    $unitTax   = round($unitNet * 0.19, 2);
                    $unitTotal = $unitNet + $unitTax;
                }

                $sql = "INSERT INTO `tbl_facto_productos`
                        (`facto_product_id`, `sku`, `nombre`, `descripcion`, `marca`, `modelo`, `unidad_medida`, `stock`, `stock_reservado`, `costo_neto`, `costo_total`, `facto_lista_id`, `nombre_lista`, `precio_neto`, `precio_impuesto`, `precio_total`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                          `facto_product_id` = VALUES(`facto_product_id`),
                          `nombre`           = VALUES(`nombre`),
                          `descripcion`      = VALUES(`descripcion`),
                          `marca`           = VALUES(`marca`),
                          `modelo`          = VALUES(`modelo`),
                          `unidad_medida`   = VALUES(`unidad_medida`),
                          `stock`           = VALUES(`stock`),
                          `stock_reservado` = VALUES(`stock_reservado`),
                          `costo_neto`      = VALUES(`costo_neto`),
                          `costo_total`     = VALUES(`costo_total`),
                          `nombre_lista`    = VALUES(`nombre_lista`),
                          `precio_neto`     = VALUES(`precio_neto`),
                          `precio_impuesto` = VALUES(`precio_impuesto`),
                          `precio_total`    = VALUES(`precio_total`),
                          `updated_at`      = CURRENT_TIMESTAMP;";

                $db->query($sql, [
                    $pId,
                    $sku,
                    $nombre,
                    $descripcion,
                    $marca,
                    $modelo,
                    $unidadMedida,
                    $stock,
                    $stockReservado,
                    $costoNeto,
                    $costoTotal,
                    $listaId,
                    $nombreLista,
                    $unitNet,
                    $unitTax,
                    $unitTotal
                ]);

                $syncedRows++;
            }
            $syncedProducts++;
        }

        return $this->response->setJSON([
            'success'         => true,
            'message'         => 'Sincronización de productos desde Facto completada con éxito.',
            'productos_total' => $syncedProducts,
            'registros_lista' => $syncedRows,
            'timestamp'       => date('Y-m-d H:i:s')
        ]);
    }

    private function fetchAndSavePriceLists(string $token, $db): array
    {
        $mapListas = [];
        $url = 'https://api.facto.cl/v1/product_price_lists';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["User-Agent: Mozilla/5.0", "Authorization: Bearer {$token}", "Accept: application/json"]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && $resp) {
            $data = json_decode($resp, true);
            $items = $data['_embedded']['product_price_lists'] ?? [];
            foreach ($items as $it) {
                $lId   = (int)($it['product_price_list_id'] ?? 0);
                $lName = trim((string)($it['name'] ?? ''));
                if ($lId > 0 && !empty($lName)) {
                    $mapListas[$lId] = $lName;

                    $db->query("INSERT INTO `tbl_facto_listas_precios` (`facto_lista_id`, `nombre`) VALUES (?, ?)
                                ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`), `updated_at` = CURRENT_TIMESTAMP;", [
                        $lId, $lName
                    ]);
                }
            }
        }

        return $mapListas;
    }

    private function fetchFactoProductList(string $token): array
    {
        $allProducts = [];
        $page = 1;
        while (true) {
            $url = "https://api.facto.cl/v1/products?limit=100&page={$page}";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 12);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["User-Agent: Mozilla/5.0", "Authorization: Bearer {$token}", "Accept: application/json"]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code === 200 && $resp) {
                $data = json_decode($resp, true);
                $items = $data['_embedded']['products'] ?? [];
                if (empty($items)) break;

                foreach ($items as $it) {
                    $allProducts[] = $it;
                }

                $pageCount = (int)($data['page_count'] ?? 1);
                if ($page >= $pageCount) break;
                $page++;
            } else {
                break;
            }
        }
        return $allProducts;
    }

    private function fetchFactoProductDetail(string $token, $productId): ?array
    {
        $url = "https://api.facto.cl/v1/products/{$productId}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["User-Agent: Mozilla/5.0", "Authorization: Bearer {$token}", "Accept: application/json"]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && $resp) {
            return json_decode($resp, true);
        }
        return null;
    }

    /**
     * Obtiene los precios por lista de un producto desde el endpoint dedicado.
     * Usado como fallback cuando el detalle del producto trae price[] vacío.
     */
    private function fetchFactoProductPrices(string $token, $productId): array
    {
        // Intentar endpoint de precios por lista
        $url = "https://api.facto.cl/v1/products/{$productId}/prices";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "User-Agent: Mozilla/5.0",
            "Authorization: Bearer {$token}",
            "Accept: application/json"
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && $resp) {
            $data = json_decode($resp, true);
            // Intentar extraer del array raíz o de _embedded
            $items = $data['_embedded']['prices']
                  ?? $data['_embedded']['product_prices']
                  ?? $data['prices']
                  ?? (isset($data[0]) ? $data : []);

            $result = [];
            foreach ($items as $item) {
                $listaId = $item['product_price_list_id']
                         ?? $item['price_list_id']
                         ?? null;
                $unitNet   = (float)($item['unit_net']   ?? $item['net']   ?? 0);
                $unitTax   = (float)($item['unit_tax']   ?? $item['tax']   ?? 0);
                $unitTotal = (float)($item['unit_total'] ?? $item['total'] ?? 0);

                if ($listaId === null) continue;
                if ($unitTotal == 0 && $unitNet > 0) {
                    $unitTax   = round($unitNet * 0.19, 2);
                    $unitTotal = $unitNet + $unitTax;
                }

                $result[] = [
                    'product_price_list_id' => (int)$listaId,
                    'unit_net'   => $unitNet,
                    'unit_tax'   => $unitTax,
                    'unit_total' => $unitTotal,
                ];
            }
            return $result;
        }

        return [];
    }

    private function ensureProductoTablesExist($db): void
    {
        $db->query("CREATE TABLE IF NOT EXISTS `tbl_facto_listas_precios` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `facto_lista_id` INT UNIQUE NOT NULL,
          `nombre` VARCHAR(100) NOT NULL,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $db->query("CREATE TABLE IF NOT EXISTS `tbl_facto_productos` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `facto_product_id` INT NOT NULL,
          `sku` VARCHAR(50) NOT NULL,
          `nombre` VARCHAR(255) NOT NULL,
          `descripcion` TEXT NULL,
          `marca` VARCHAR(100) NULL,
          `modelo` VARCHAR(100) NULL,
          `unidad_medida` VARCHAR(20) NULL,
          `stock` DECIMAL(12,3) NOT NULL DEFAULT 0.000,
          `stock_reservado` DECIMAL(12,3) NOT NULL DEFAULT 0.000,
          `costo_neto` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `costo_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `facto_lista_id` INT NOT NULL,
          `nombre_lista` VARCHAR(100) NULL,
          `precio_neto` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `precio_impuesto` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `precio_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          UNIQUE KEY `uniq_prod_lista` (`sku`, `facto_lista_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // Migrar columnas adicionales si la tabla ya existía
        $cols = $db->getFieldNames('tbl_facto_productos');
        if (!in_array('costo_neto', $cols, true)) {
            $db->query("ALTER TABLE `tbl_facto_productos` ADD COLUMN `costo_neto` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `stock`;");
        }
        if (!in_array('costo_total', $cols, true)) {
            $db->query("ALTER TABLE `tbl_facto_productos` ADD COLUMN `costo_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `costo_neto`;");
        }
        if (!in_array('stock_reservado', $cols, true)) {
            $db->query("ALTER TABLE `tbl_facto_productos` ADD COLUMN `stock_reservado` DECIMAL(12,3) NOT NULL DEFAULT 0.000 AFTER `stock`;");
        }
    }
}
