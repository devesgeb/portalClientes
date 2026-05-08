<?php

namespace App\Controllers;

use App\Models\LoginModel;
use CodeIgniter\HTTP\ResponseInterface;

class LogisticaController extends BaseController
{
    // ── Helper sesión ─────────────────────────────────────────────
    private function usuario(): ?array
    {
        $session = session();
        $userId  = $session->get('is_logued_in');
        if (!$userId) return null;
        return (new LoginModel())->obtenerPorId($userId);
    }

    // ── Vistas principales ────────────────────────────────────────
    public function hojaDeRuta()
    {
        return view('logistica/hoja_de_ruta', [
            'title'      => 'Hoja de Ruta – Logística',
            'activePage' => 'hoja-de-ruta',
            'usuario'    => $this->usuario(),
        ]);
    }

    public function despachosAgendados()
    {
        return view('logistica/despachos_agendados', [
            'title'      => 'Despachos Agendados – Logística',
            'activePage' => 'despachos-agendados',
            'usuario'    => $this->usuario(),
        ]);
    }

    public function funcionarios()
    {
        return view('logistica/funcionarios', [
            'title'      => 'Funcionarios – Logística',
            'activePage' => 'funcionarios',
            'usuario'    => $this->usuario(),
        ]);
    }

    // ── API Funcionarios ──────────────────────────────────────────

    /** GET /logistica/api/funcionarios */
    public function apiFuncionarios(): ResponseInterface
    {
        $db   = \Config\Database::connect();
        $rows = $db->table('tbl_funcionarios')
                   ->orderBy('activo', 'DESC')
                   ->orderBy('apellidos', 'ASC')
                   ->get()->getResultArray();
        return $this->response->setJSON(['success' => true, 'data' => $rows]);
    }

    /** POST /logistica/api/funcionarios */
    public function crearFuncionario(): ResponseInterface
    {
        $db  = \Config\Database::connect();
        $raw = $this->request->getJSON(true) ?? $this->request->getPost();
        $data = [
            'nombre'        => trim($raw['nombre']        ?? ''),
            'apellidos'     => trim($raw['apellidos']     ?? ''),
            'rut'           => trim($raw['rut']           ?? ''),
            'fecha_ingreso' => $raw['fecha_ingreso']      ?? '',
            'patente'       => strtoupper(trim($raw['patente'] ?? '')) ?: null,
            'cargo'         => $raw['cargo']              ?? '',
        ];
        if (!$data['nombre'] || !$data['apellidos'] || !$data['rut'] || !$data['fecha_ingreso'] || !$data['cargo']) {
            return $this->response->setJSON(['success' => false, 'error' => 'Faltan campos obligatorios']);
        }
        $db->table('tbl_funcionarios')->insert($data);
        return $this->response->setJSON(['success' => true, 'id' => $db->insertID()]);
    }

    /** PUT /logistica/api/funcionarios/(:num) */
    public function actualizarFuncionario(int $id): ResponseInterface
    {
        $db  = \Config\Database::connect();
        $raw = $this->request->getJSON(true) ?? [];
        $data = [];
        foreach (['nombre','apellidos','rut','fecha_ingreso','cargo'] as $campo) {
            if (isset($raw[$campo])) $data[$campo] = trim($raw[$campo]);
        }
        if (isset($raw['patente'])) $data['patente'] = strtoupper(trim($raw['patente'])) ?: null;
        if (isset($raw['activo']))  $data['activo']  = (int)$raw['activo'];
        $db->table('tbl_funcionarios')->where('id', $id)->update($data);
        return $this->response->setJSON(['success' => true]);
    }

    /** DELETE /logistica/api/funcionarios/(:num) */
    public function eliminarFuncionario(int $id): ResponseInterface
    {
        $db = \Config\Database::connect();
        $db->table('tbl_funcionarios')->where('id', $id)->delete();
        return $this->response->setJSON(['success' => true]);
    }

    // ── API Hoja de Vida (eventos) ────────────────────────────────

    /** GET /logistica/api/funcionarios/(:num)/eventos */
    public function apiEventos(int $funcionarioId): ResponseInterface
    {
        $db   = \Config\Database::connect();
        $rows = $db->table('tbl_eventos_conduccion')
                   ->where('funcionario_id', $funcionarioId)
                   ->orderBy('fecha', 'DESC')
                   ->get()->getResultArray();
        return $this->response->setJSON(['success' => true, 'data' => $rows]);
    }

    /** POST /logistica/api/funcionarios/(:num)/eventos */
    public function crearEvento(int $funcionarioId): ResponseInterface
    {
        $db  = \Config\Database::connect();
        $raw = $this->request->getJSON(true) ?? $this->request->getPost();
        $data = [
            'funcionario_id' => $funcionarioId,
            'fecha'          => $raw['fecha']        ?? date('Y-m-d'),
            'tipo'           => $raw['tipo']         ?? 'Otro',
            'descripcion'    => trim($raw['descripcion'] ?? ''),
            'gravedad'       => $raw['gravedad']     ?? 'Sin gravedad',
        ];
        if (!$data['descripcion']) {
            return $this->response->setJSON(['success' => false, 'error' => 'La descripción es obligatoria']);
        }
        $db->table('tbl_eventos_conduccion')->insert($data);
        return $this->response->setJSON(['success' => true, 'id' => $db->insertID()]);
    }

    /** DELETE /logistica/api/eventos/(:num) */
    public function eliminarEvento(int $id): ResponseInterface
    {
        $db = \Config\Database::connect();
        $db->table('tbl_eventos_conduccion')->where('id', $id)->delete();
        return $this->response->setJSON(['success' => true]);
    }

    // ── GET /logistica/clientes — Autocomplete ────────────────────
    public function clientes(): ResponseInterface
    {
        $q  = $this->request->getGet('q') ?? '';
        $db = \Config\Database::connect();
        $rows = $db->table('tbl_clientes')
                   ->like('nombre', $q)
                   ->orLike('rut', $q)
                   ->orderBy('nombre', 'ASC')
                   ->limit(30)
                   ->get()->getResultArray();
        $lista = array_map(fn($r) => [
            'rut'       => $r['rut'],
            'nombre'    => $r['nombre'],
            'direccion' => $r['direccion'] ?? '',
            'comuna'    => $r['comuna']    ?? '',
        ], $rows);
        return $this->response->setJSON(['success' => true, 'clientes' => $lista]);
    }
}
