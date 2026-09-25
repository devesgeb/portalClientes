<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class NotificacionesController extends BaseController
{
    /**
     * Asegura la existencia de tbl_notificaciones
     */
    private function ensureTableExists(): void
    {
        $db = \Config\Database::connect();
        $sql = "CREATE TABLE IF NOT EXISTS `tbl_notificaciones` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `titulo` VARCHAR(255) NOT NULL,
          `mensaje` TEXT NOT NULL,
          `tipo` VARCHAR(50) NOT NULL DEFAULT 'info',
          `url_accion` VARCHAR(255) NOT NULL DEFAULT '/facturacion/importar/ventas',
          `leido` TINYINT(1) NOT NULL DEFAULT 0,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $db->query($sql);
    }

    /**
     * GET /notificaciones/obtener
     * Devuelve la lista de notificaciones no leídas y la cuenta total
     */
    public function obtener(): ResponseInterface
    {
        $this->ensureTableExists();
        $db = \Config\Database::connect();

        $rows = $db->table('tbl_notificaciones')
                   ->where('leido', 0)
                   ->orderBy('id', 'DESC')
                   ->limit(10)
                   ->get()
                   ->getResultArray();

        $unreadCount = count($rows);

        return $this->response->setJSON([
            'success'      => true,
            'unread_count' => $unreadCount,
            'data'         => $rows
        ]);
    }

    /**
     * POST /notificaciones/marcar-leidas
     */
    public function marcarLeidas(): ResponseInterface
    {
        $this->ensureTableExists();
        $db = \Config\Database::connect();
        
        $json = json_decode($this->request->getBody(), true) ?: $this->request->getPost();
        $id   = $json['id'] ?? null;

        $builder = $db->table('tbl_notificaciones')->where('leido', 0);
        if (!empty($id)) {
            $builder->where('id', (int)$id);
        }
        $builder->update(['leido' => 1]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Notificaciones marcadas como leídas.'
        ]);
    }
}
