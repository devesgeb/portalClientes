<?php
namespace App\Controllers;

use App\Models\ClientesModel;
use App\Models\ProveedoresModel;
use App\Models\LoginModel;
use CodeIgniter\HTTP\ResponseInterface;

class CargarEntidadController extends BaseController
{
    public function index()
    {
        $session = session();
        $userId = $session->get('is_logued_in');
        $loginModel = new LoginModel();
        $usuario = $userId ? $loginModel->obtenerPorId($userId) : null;

        return view('cargar_clientes_proveedor', [
            'title' => 'Cargar Clientes / Proveedores',
            'base_url' => base_url(),
            'assets_url' => base_url('public/assets/'),
            'activePage' => 'cargar-entidad',
            'usuario' => $usuario,
        ]);
    }

    /**
     * POST /importar-clientes
     * Body JSON: { "rows": [ { "nombre":..., "rut":..., ... } ] }
     */
    public function importarClientes(): ResponseInterface
    {
        return $this->_importar('clientes');
    }

    /**
     * POST /importar-proveedores
     */
    public function importarProveedores(): ResponseInterface
    {
        return $this->_importar('proveedores');
    }

    private function _importar(string $tipo): ResponseInterface
    {
        $body = $this->request->getJSON(true);
        $rows = $body['rows'] ?? [];

        if (empty($rows) || !is_array($rows)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'No se recibieron filas para importar.',
            ]);
        }

        // Mapeo de cabeceras Excel → campo BD
        $mapClientes = [
            'nombre cliente' => 'nombre',
            'razon social' => 'razon_social',
            'razon_social' => 'razon_social',
            'rut' => 'rut',
            'direccion' => 'direccion',
            'dirección' => 'direccion',
            'comuna' => 'comuna',
            'ciudad' => 'ciudad',
            'giro' => 'giro',
            'nombre contacto' => 'contacto_nombre',
            'apellido contacto' => 'contacto_apellido',
            'email' => 'email',
            'condiciones de pago en dias' => 'condiciones_pago_dias',
            'condiciones de pago en días' => 'condiciones_pago_dias',
            'linea de credito aprobada' => 'linea_credito',
            'línea de crédito aprobada' => 'linea_credito',
            'lista de precios' => 'lista_precios',
            'fecha vencimiento linea' => 'fecha_vencimiento_linea',
            'fecha vencimiento línea' => 'fecha_vencimiento_linea',
            'telefono empresa' => 'telefono_empresa',
            'teléfono empresa' => 'telefono_empresa',
            'telefono contacto' => 'telefono_contacto',
            'teléfono contacto' => 'telefono_contacto',
            'pais' => 'pais',
            'país' => 'pais',
            'usuario encargado' => 'usuario_encargado',
        ];

        $mapProveedores = [
            'nombre cliente' => 'nombre',
            'razon social' => 'razon_social',
            'razon_social' => 'razon_social',
            'rut' => 'rut',
            'direccion' => 'direccion',
            'dirección' => 'direccion',
            'comuna' => 'comuna',
            'ciudad' => 'ciudad',
            'giro' => 'giro',
            'nombre contacto' => 'contacto_nombre',
            'apellido contacto' => 'contacto_apellido',
            'email' => 'email',
            'telefono empresa' => 'telefono_empresa',
            'teléfono empresa' => 'telefono_empresa',
            'telefono contacto' => 'telefono_contacto',
            'teléfono contacto' => 'telefono_contacto',
            'pais' => 'pais',
            'país' => 'pais',
            'usuario encargado' => 'usuario_encargado',
        ];

        $map = $tipo === 'clientes' ? $mapClientes : $mapProveedores;

        // Normalizar cada fila
        $normalized = [];
        foreach ($rows as $row) {
            $nr = [];
            foreach ($row as $key => $val) {
                $keyNorm = strtolower(trim($key));
                if (isset($map[$keyNorm])) {
                    $nr[$map[$keyNorm]] = $val === '' ? null : $val;
                }
            }
            if (!empty($nr))
                $normalized[] = $nr;
        }

        // ── Deduplicar por RUT (prioridad) o nombre antes de insertar ────────
        $seen = [];
        $deduplicados = 0;
        $unique = [];
        foreach ($normalized as $row) {
            $rut = strtolower(trim(preg_replace('/\s+/', '', $row['rut'] ?? '')));
            $nombre = strtolower(trim($row['razon_social'] ?? $row['nombre'] ?? ''));
            $key = $rut !== '' ? $rut : $nombre;

            if ($key === '' || !isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $row;
            }
            else {
                $deduplicados++;
            }
        }
        $normalized = $unique;

        try {
            $model = $tipo === 'clientes' ? new ClientesModel() : new ProveedoresModel();
            $result = $model->importar($normalized);
        }
        catch (\Exception $e) {
            log_message('error', "[CargarEntidad::$tipo] " . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Error al importar: ' . $e->getMessage(),
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'insertados' => $result['insertados'],
            'actualizados' => $result['actualizados'],
            'errores' => $result['errores'],
            'deduplicados' => $deduplicados,
            'message' => "Importación completada: {$result['insertados']} nuevos, {$result['actualizados']} actualizados"
            . ($deduplicados > 0 ? ", {$deduplicados} duplicado(s) ignorado(s)." : '.'),
        ]);
    }
}
