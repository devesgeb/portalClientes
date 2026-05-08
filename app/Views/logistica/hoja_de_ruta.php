<?php $activePage = 'hoja-de-ruta'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hoja de Ruta – Logística | Portal Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('public/assets/css/admin.css') ?>">
    <style>
        :root {
            --c1:#1d4ed8; --c2:#059669; --c3:#7c3aed;
            --zona-norte:#3b82f6; --zona-sur:#10b981;
            --zona-centro:#8b5cf6; --zona-oriente:#f59e0b; --zona-poniente:#06b6d4;
        }

        /* ── Header ── */
        .ruta-header {
            background:linear-gradient(135deg,#0f172a,#1e293b);
            border-radius:18px; padding:20px 24px; margin-bottom:20px;
            border:1px solid rgba(255,255,255,.06); box-shadow:0 4px 24px rgba(0,0,0,.18);
        }
        .ruta-title { font-size:1.1rem; font-weight:800; color:#f1f5f9; display:flex; align-items:center; gap:10px; }
        .ruta-title .iw {
            width:38px; height:38px; border-radius:10px;
            background:linear-gradient(135deg,#1d4ed8,#7c3aed);
            display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.1rem;
        }
        .btn-fecha { width:32px; height:32px; border-radius:8px; border:1.5px solid rgba(255,255,255,.12); background:rgba(255,255,255,.06); color:#94a3b8; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:.18s; }
        .btn-fecha:hover { background:rgba(255,255,255,.12); color:#fff; }
        .fecha-display { font-size:.88rem; font-weight:700; color:#e2e8f0; background:rgba(255,255,255,.07); border-radius:8px; padding:5px 14px; border:1.5px solid rgba(255,255,255,.1); cursor:pointer; }
        .btn-hoy { font-size:.72rem; font-weight:700; color:#a78bfa; background:rgba(124,58,237,.15); border:1px solid rgba(124,58,237,.3); border-radius:6px; padding:3px 10px; cursor:pointer; }
        .stat-chip { display:flex; align-items:center; gap:6px; padding:5px 12px; border-radius:20px; font-size:.76rem; font-weight:700; white-space:nowrap; }
        .chip-total { background:rgba(148,163,184,.12); color:#94a3b8; border:1px solid rgba(148,163,184,.2); }
        .btn-nuevo { background:linear-gradient(135deg,#1d4ed8,#7c3aed); color:#fff; border:none; border-radius:10px; font-size:.80rem; font-weight:700; padding:7px 16px; display:flex; align-items:center; gap:6px; cursor:pointer; transition:.18s; }
        .btn-nuevo:hover { opacity:.88; transform:translateY(-1px); }
        .btn-sec { background:rgba(255,255,255,.06); color:#94a3b8; border:1.5px solid rgba(255,255,255,.1); border-radius:10px; font-size:.80rem; padding:7px 14px; display:flex; align-items:center; gap:5px; cursor:pointer; transition:.18s; }
        .btn-sec:hover { background:rgba(255,255,255,.12); color:#e2e8f0; }
        .zona-legend { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
        .zon-dot { display:inline-flex; align-items:center; gap:4px; font-size:.70rem; color:#94a3b8; }
        .zon-dot span { width:9px; height:9px; border-radius:50%; display:inline-block; }

        /* ── Toolbar / Filtros ── */
        .toolbar {
            display:flex; gap:10px; flex-wrap:wrap; align-items:center;
            margin-bottom:16px; background:#fff; border-radius:14px;
            padding:12px 16px; border:1.5px solid #e8edf5;
            box-shadow:0 1px 6px rgba(60,80,120,.05);
        }
        .tb-label { font-size:.72rem; font-weight:700; color:#94a3b8; white-space:nowrap; }
        .tb-select {
            font-size:.78rem; padding:5px 10px; border-radius:8px;
            border:1.5px solid #e2e8f0; color:#374151; font-weight:600;
            background:#f8f9fc; cursor:pointer; transition:.18s;
        }
        .tb-select:focus { outline:none; border-color:#7c3aed; }
        .tb-search {
            flex:1; min-width:140px; font-size:.78rem; padding:5px 10px;
            border-radius:8px; border:1.5px solid #e2e8f0; font-family:'Inter',sans-serif;
        }
        .tb-search:focus { outline:none; border-color:#7c3aed; }

        /* ── Tabla principal ── */
        .ruta-table-wrap {
            background:#fff; border-radius:16px; border:1.5px solid #e8edf5;
            overflow:hidden; box-shadow:0 2px 12px rgba(60,80,120,.07);
        }
        .ruta-table { width:100%; border-collapse:collapse; font-size:.82rem; }
        .ruta-table thead th {
            padding:11px 14px; background:#f8f9fc;
            color:#5b21b6; font-size:.70rem; font-weight:800;
            text-transform:uppercase; letter-spacing:.06em;
            border-bottom:2px solid #e8edf5; white-space:nowrap;
        }
        .ruta-table thead th:first-child { width:42px; text-align:center; }
        .ruta-table tbody td {
            padding:11px 14px; border-bottom:1px solid #f0f4f9;
            vertical-align:middle; color:#374151;
        }
        .ruta-table tbody tr:last-child td { border-bottom:none; }
        .ruta-table tbody tr:hover td { background:#faf8ff; }

        /* Número de orden */
        .orden-num {
            width:26px; height:26px; border-radius:8px; background:#1e293b;
            color:#fff; font-size:.68rem; font-weight:800;
            display:inline-flex; align-items:center; justify-content:center;
        }

        /* Zona badge */
        .zona-chip {
            font-size:.65rem; font-weight:800; padding:2px 9px; border-radius:20px;
            white-space:nowrap; text-transform:uppercase; letter-spacing:.04em;
            display:inline-block;
        }

        /* Chofer badge */
        .chofer-badge {
            display:inline-flex; align-items:center; gap:6px;
            font-size:.76rem; font-weight:700;
        }
        .chofer-dot {
            width:22px; height:22px; border-radius:7px;
            display:inline-flex; align-items:center; justify-content:center;
            font-size:.60rem; font-weight:800; color:#fff;
        }

        /* Acciones */
        .act-btn {
            font-size:.66rem; padding:4px 9px; border-radius:7px;
            border:1px solid; cursor:pointer; font-weight:600; transition:.15s;
        }
        .act-reasignar { border-color:#c4b5fd; color:#7c3aed; background:#f5f0ff; }
        .act-reasignar:hover { background:#7c3aed; color:#fff; }
        .act-del { border-color:#fca5a5; color:#dc2626; background:#fff5f5; }
        .act-del:hover { background:#dc2626; color:#fff; }
        .act-up   { border-color:#c4b5fd; color:#7c3aed; background:#f5f0ff; padding:4px 7px; }
        .act-up:hover { background:#7c3aed; color:#fff; }

        /* Prioridad */
        .prio-badge {
            font-size:.68rem; font-weight:800; padding:4px 10px; border-radius:20px;
            cursor:pointer; white-space:nowrap; display:inline-flex; align-items:center;
            gap:4px; border:1.5px solid; transition:opacity .15s, transform .15s;
            user-select:none;
        }
        .prio-badge:hover { opacity:.82; transform:scale(.97); }
        .prio-alta    { background:#fee2e2; color:#dc2626; border-color:#fca5a5; }
        .prio-media   { background:#fef9c3; color:#ca8a04; border-color:#fde047; }
        .prio-baja    { background:#dcfce7; color:#16a34a; border-color:#86efac; }

        /* Agendado badge */
        .agend-pill {
            display:inline-flex; align-items:center; gap:4px;
            font-size:.68rem; font-weight:700; color:#1d4ed8;
            background:#dbeafe; border:1px solid #93c5fd;
            border-radius:20px; padding:2px 8px; white-space:nowrap;
        }

        /* Estado vacío */
        .empty-row td {
            text-align:center; padding:48px 12px;
            color:#94a3b8; font-size:.85rem;
        }

        /* ── Modal ── */
        .modal-hdr-logis { background:linear-gradient(135deg,#1e3a5f,#1d4ed8); color:#fff; border:none; padding:16px 22px; }
        .f-label { font-size:.76rem; font-weight:700; color:#1d4ed8; margin-bottom:4px; }
        .f-ctrl { width:100%; padding:8px 11px; border:1.5px solid #dbeafe; border-radius:9px; font-size:.83rem; font-family:'Inter',sans-serif; transition:.2s; }
        .f-ctrl:focus { outline:none; border-color:#1d4ed8; }
        .f-row { margin-bottom:14px; }
        .autocomplete-wrap { position:relative; }
        .autocomplete-list { position:absolute; top:100%; left:0; right:0; z-index:9999; background:#fff; border:1.5px solid #dbeafe; border-radius:9px; box-shadow:0 8px 24px rgba(0,0,0,.12); max-height:200px; overflow-y:auto; display:none; }
        .autocomplete-list.show { display:block; }
        .ac-item { padding:8px 12px; cursor:pointer; font-size:.80rem; border-bottom:1px solid #f0f4f9; transition:.12s; }
        .ac-item:last-child { border-bottom:none; }
        .ac-item:hover { background:#eff6ff; color:#1d4ed8; }
        .ac-rut { font-size:.70rem; color:#94a3b8; }
    </style>
</head>
<body>
<?= $this->include('partials/sidebar') ?>
<div class="main">
    <div class="topbar">
        <div class="d-flex align-items-center gap-2">
            <button class="btn-menu-toggle" onclick="abrirSidebar()"><i class="bi bi-list"></i></button>
            <div>
                <div class="topbar-title"><i class="bi bi-map-fill me-2" style="color:var(--accent);"></i>Hoja de Ruta</div>
                <div class="topbar-sub">Portal Admin &rsaquo; Logística &rsaquo; Hoja de Ruta</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="date-badge"><i class="bi bi-calendar3 me-1"></i><span id="fechaHoy"></span></span>
            <div class="user-badge"><div class="ub-avatar" id="topbarAvatar">--</div>
                <div><div class="ub-name" id="topbarNombre">Cargando...</div><div class="ub-role">Administrador</div></div>
            </div>
        </div>
    </div>

    <div class="page-body">

        <!-- ENCABEZADO -->
        <div class="ruta-header">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div>
                    <div class="ruta-title mb-2">
                        <div class="iw"><i class="bi bi-map-fill"></i></div>
                        Hoja de Ruta Diaria
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <button class="btn-fecha" onclick="cambiarDia(-1)"><i class="bi bi-chevron-left"></i></button>
                        <div class="fecha-display" id="fechaRuta" onclick="abrirDatePicker()">—</div>
                        <button class="btn-fecha" onclick="cambiarDia(1)"><i class="bi bi-chevron-right"></i></button>
                        <span class="btn-hoy" onclick="irHoy()">Hoy</span>
                        <input type="date" id="dpFecha" style="position:absolute;opacity:0;pointer-events:none;" onchange="seleccionarFecha(this.value)">
                    </div>
                    <div class="d-flex gap-2">
                        <div class="stat-chip chip-total"><i class="bi bi-geo-alt-fill"></i> <span id="stTotal">0</span> paradas totales</div>
                        <div class="stat-chip chip-total" style="color:#a78bfa;border-color:rgba(139,92,246,.25);background:rgba(139,92,246,.1);">
                            <i class="bi bi-calendar-event-fill"></i> <span id="stAgend">0</span> agendadas
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-column gap-3 align-items-end">
                    <div class="d-flex gap-2">
                        <button class="btn-sec" onclick="window.print()"><i class="bi bi-printer-fill"></i> Imprimir</button>
                        <button class="btn-nuevo" onclick="abrirModalNuevo()"><i class="bi bi-plus-lg"></i> Nuevo despacho</button>
                    </div>
                    <div class="zona-legend">
                        <span style="font-size:.68rem;color:#64748b;font-weight:600;">ZONAS:</span>
                        <span class="zon-dot"><span style="background:var(--zona-norte)"></span>Norte</span>
                        <span class="zon-dot"><span style="background:var(--zona-sur)"></span>Sur</span>
                        <span class="zon-dot"><span style="background:var(--zona-centro)"></span>Centro</span>
                        <span class="zon-dot"><span style="background:var(--zona-oriente)"></span>Oriente</span>
                        <span class="zon-dot"><span style="background:var(--zona-poniente)"></span>Poniente</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- TOOLBAR -->
        <div class="toolbar">
            <span class="tb-label">ZONA</span>
            <select class="tb-select" id="filtroZona" onchange="renderTabla()">
                <option value="">Todas</option>
                <option>Norte</option><option>Sur</option>
                <option>Centro</option><option>Oriente</option><option>Poniente</option>
            </select>
            <span class="tb-label" style="margin-left:8px;">CHOFER</span>
            <select class="tb-select" id="filtroChofer" onchange="renderTabla()">
                <option value="">Todos</option>
                <option value="0">Carlos Ramírez</option>
                <option value="1">Miguel Torres</option>
                <option value="2">Rodrigo Silva</option>
            </select>
            <input type="text" class="tb-search" id="busqueda" placeholder="🔍 Buscar cliente o dirección…" oninput="renderTabla()">
        </div>

        <!-- TABLA PRINCIPAL -->
        <div class="ruta-table-wrap">
            <table class="ruta-table" id="tablaRuta">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Dirección</th>
                        <th>Zona</th>
                        <th>Chofer asignado</th>
                        <th style="text-align:center;">Prioridad</th>
                        <th>Bultos</th>
                        <th>Nota / hora agendada</th>
                        <th style="text-align:center;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbodyRuta"></tbody>
            </table>
        </div>

    </div>
</div>

<!-- MODAL NUEVO DESPACHO -->
<div class="modal fade" id="modalNuevo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:18px;overflow:hidden;border:none;box-shadow:0 20px 60px rgba(0,0,0,.18);">
            <div class="modal-hdr-logis d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2"><i class="bi bi-geo-alt-fill"></i><span style="font-weight:800;">Nuevo Despacho</span></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="f-row">
                            <div class="f-label">Cliente *</div>
                            <div class="autocomplete-wrap">
                                <input type="text" class="f-ctrl" id="nCliente" placeholder="Buscar cliente…" autocomplete="off" oninput="buscarCliente(this.value)">
                                <div class="autocomplete-list" id="acList"></div>
                            </div>
                            <input type="hidden" id="nClienteRut">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="f-row"><div class="f-label">Dirección *</div>
                        <input type="text" class="f-ctrl" id="nDireccion" placeholder="Calle 123, Comuna"></div>
                    </div>
                    <div class="col-md-4">
                        <div class="f-row"><div class="f-label">Zona</div>
                        <select class="f-ctrl" id="nZona">
                            <option value="">— Seleccionar —</option>
                            <option>Norte</option><option>Sur</option><option>Centro</option><option>Oriente</option><option>Poniente</option>
                        </select></div>
                    </div>
                    <div class="col-md-4">
                        <div class="f-row"><div class="f-label">Chofer *</div>
                        <select class="f-ctrl" id="nChofer">
                            <option value="">— Seleccionar —</option>
                            <option value="0">Carlos Ramírez</option>
                            <option value="1">Miguel Torres</option>
                            <option value="2">Rodrigo Silva</option>
                        </select></div>
                    </div>
                    <div class="col-md-4">
                        <div class="f-row"><div class="f-label">Bultos / cajas</div>
                        <input type="number" class="f-ctrl" id="nBultos" min="1" value="1"></div>
                    </div>
                    <div class="col-md-4">
                        <div class="f-row"><div class="f-label">Prioridad</div>
                        <select class="f-ctrl" id="nPrioridad">
                            <option value="alta">🔴 Alta</option>
                            <option value="media" selected>🟡 Media</option>
                            <option value="baja">🟢 Baja</option>
                        </select></div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end pb-2">
                        <label style="display:flex;align-items:center;gap:8px;font-size:.80rem;font-weight:600;color:#374151;cursor:pointer;">
                            <input type="checkbox" id="nEsAgendado" onchange="toggleAgendado(this.checked)">
                            ¿Tiene hora agendada?
                        </label>
                    </div>
                    <div class="col-md-6" id="rowFechaAgend" style="display:none;">
                        <div class="f-row"><div class="f-label">Fecha agendada</div>
                        <input type="date" class="f-ctrl" id="nFechaAgend"></div>
                    </div>
                    <div class="col-md-6" id="rowHoraAgend" style="display:none;">
                        <div class="f-row"><div class="f-label">Hora comprometida</div>
                        <input type="time" class="f-ctrl" id="nHoraAgend"></div>
                    </div>
                    <div class="col-12">
                        <div class="f-row"><div class="f-label">Instrucción especial</div>
                        <textarea class="f-ctrl" id="nNota" rows="2" placeholder="Ej: Entregar a encargado, requiere rampa…"></textarea></div>
                    </div>
                </div>
                <div id="nuevoError" class="alert alert-danger py-2 px-3 mt-1" style="display:none;font-size:.80rem;border-radius:8px;"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f0f4f9;">
                <button class="btn btn-sm" style="background:#f0f4f9;color:#5a7394;border-radius:9px;padding:7px 18px;" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm" onclick="agregarDespacho()"
                    style="background:linear-gradient(135deg,#1d4ed8,#7c3aed);color:#fff;border-radius:9px;padding:7px 20px;font-weight:700;border:none;">
                    <i class="bi bi-plus-lg me-1"></i>Agregar a la ruta
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL REASIGNAR -->
<div class="modal fade" id="modalReasignar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:360px;">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;">
            <div class="modal-hdr-logis d-flex align-items-center justify-content-between">
                <span style="font-weight:800;"><i class="bi bi-arrow-left-right me-2"></i>Reasignar chofer</span>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
            </div>
            <div class="modal-body p-4">
                <div class="f-label mb-2">Parada: <span id="reasigCliente" style="color:#1a2940;font-weight:700;"></span></div>
                <div class="f-label">Asignar a</div>
                <select class="f-ctrl" id="reasigChofer">
                    <option value="0">Carlos Ramírez</option>
                    <option value="1">Miguel Torres</option>
                    <option value="2">Rodrigo Silva</option>
                </select>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f0f4f9;">
                <button class="btn btn-sm" style="background:#f0f4f9;color:#5a7394;border-radius:9px;" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm" onclick="confirmarReasignar()"
                    style="background:linear-gradient(135deg,#1d4ed8,#7c3aed);color:#fff;border-radius:9px;padding:7px 18px;font-weight:700;border:none;">
                    <i class="bi bi-check-lg me-1"></i>Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE = "<?= rtrim(site_url(), '/') . '/' ?>";
window.ADMIN_SESSION = <?= json_encode($usuario ?? ['nombre'=>'Administrador','perfil'=>'Administrador']) ?>;

const CHOFERES = [
    { id:0, nombre:'Carlos Ramírez', inicial:'CR', color:'#1d4ed8' },
    { id:1, nombre:'Miguel Torres',  inicial:'MT', color:'#059669' },
    { id:2, nombre:'Rodrigo Silva',  inicial:'RS', color:'#7c3aed' },
];
const ZONAS = {
    Norte:    { color:'#3b82f6', bg:'#eff6ff', bdr:'#bfdbfe' },
    Sur:      { color:'#10b981', bg:'#f0fdf4', bdr:'#bbf7d0' },
    Centro:   { color:'#8b5cf6', bg:'#f5f0ff', bdr:'#ddd6fe' },
    Oriente:  { color:'#f59e0b', bg:'#fefce8', bdr:'#fde68a' },
    Poniente: { color:'#06b6d4', bg:'#f0fdfe', bdr:'#a5f3fc' },
};

let despachos = [
    { id:1, chofer:0, cliente:'Minimarket El Quisco',       rut:'10.249.958-1', dir:'Av. Independencia 2340, Independencia', zona:'Norte',    bultos:4,  prioridad:'alta',  agendado:false, hora:null,  fechaAgend:null,         nota:'' },
    { id:2, chofer:0, cliente:'Supermercado Las Dos Lunas', rut:'',             dir:'Calle Loreto 890, Providencia',         zona:'Centro',   bultos:8,  prioridad:'media', agendado:false, hora:null,  fechaAgend:null,         nota:'Llegar antes de las 12h' },
    { id:3, chofer:0, cliente:'Fuente de soda La Palma',    rut:'',             dir:'Lo Ovalle 3512, La Pintana',            zona:'Sur',      bultos:2,  prioridad:'baja',  agendado:true,  hora:'10:30', fechaAgend:'2026-04-24', nota:'Pedir por Fernando' },
    { id:4, chofer:1, cliente:'Rest. Sabor Caribeño',       rut:'',             dir:'Av. Tobalaba 940, Ñuñoa',              zona:'Oriente',  bultos:6,  prioridad:'media', agendado:false, hora:null,  fechaAgend:null,         nota:'' },
    { id:5, chofer:1, cliente:'Distribuidora Norte Fácil',  rut:'77.287.577-0', dir:'Panamericana Norte 8900, Quilicura',   zona:'Norte',    bultos:12, prioridad:'alta',  agendado:false, hora:null,  fechaAgend:null,         nota:'Pallet en rampa' },
    { id:6, chofer:1, cliente:'Cafetería Punto Azul',       rut:'',             dir:'Av. Grecia 4200, Peñalolén',            zona:'Oriente',  bultos:3,  prioridad:'baja',  agendado:true,  hora:'15:00', fechaAgend:'2026-04-24', nota:'' },
    { id:7, chofer:2, cliente:'Hotel Los Andes',            rut:'96.685.690-4', dir:'Agustinas 814, Santiago Centro',        zona:'Centro',   bultos:5,  prioridad:'alta',  agendado:false, hora:null,  fechaAgend:null,         nota:'' },
    { id:8, chofer:2, cliente:'Snack El Conquistador',      rut:'',             dir:'Camino a Melipilla 5060, Pudahuel',    zona:'Poniente', bultos:7,  prioridad:'media', agendado:false, hora:null,  fechaAgend:null,         nota:'' },
    { id:9, chofer:2, cliente:'Botillería La Cosecha',      rut:'',             dir:'Gran Av. 2100, San Miguel',            zona:'Sur',      bultos:4,  prioridad:'baja',  agendado:true,  hora:'11:00', fechaAgend:'2026-04-24', nota:'Solo AM' },
];
let nextId_ = 10;
let reasigId = null;
let fechaRuta = new Date();

function zonaBadge(zona) {
    const z = ZONAS[zona] || { color:'#6b7280', bg:'#f1f5f9', bdr:'#e2e8f0' };
    return `<span class="zona-chip" style="background:${z.bg};color:${z.color};border:1px solid ${z.bdr};">${zona||'—'}</span>`;
}
function choferBadge(id) {
    const ch = CHOFERES[id];
    if (!ch) return '—';
    return `<span class="chofer-badge">
        <span class="chofer-dot" style="background:${ch.color};">${ch.inicial}</span>
        ${ch.nombre}
    </span>`;
}
const PRIO = {
    alta:  { label:'Alta',  icon:'🔴', cls:'prio-alta'  },
    media: { label:'Media', icon:'🟡', cls:'prio-media' },
    baja:  { label:'Baja',  icon:'🟢', cls:'prio-baja'  },
};
const PRIO_CICLO = { alta:'media', media:'baja', baja:'alta' };
function prioBadge(id, prio) {
    const p = PRIO[prio] || PRIO.media;
    return `<span class="prio-badge ${p.cls}" onclick="ciclarPrioridad(${id})" title="Clic para cambiar">${p.icon} ${p.label}</span>`;
}
function ciclarPrioridad(id) {
    const d = despachos.find(x=>x.id===id);
    if (d) d.prioridad = PRIO_CICLO[d.prioridad] || 'media';
    renderTabla();
}

function renderTabla() {
    const tbody = document.getElementById('tbodyRuta');
    const zona   = document.getElementById('filtroZona').value;
    const chofer = document.getElementById('filtroChofer').value;
    const q      = document.getElementById('busqueda').value.toLowerCase();

    let filtrados = despachos.filter(d => {
        if (zona   && d.zona !== zona)            return false;
        if (chofer && d.chofer !== parseInt(chofer)) return false;
        if (q && !d.cliente.toLowerCase().includes(q) && !d.dir.toLowerCase().includes(q)) return false;
        return true;
    });

    document.getElementById('stTotal').textContent = despachos.length;
    document.getElementById('stAgend').textContent = despachos.filter(d=>d.agendado).length;

    if (!filtrados.length) {
        tbody.innerHTML = `<tr class="empty-row"><td colspan="8">
            <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.25;"></i>
            Sin paradas para este día o filtro aplicado.
        </td></tr>`;
        return;
    }

    tbody.innerHTML = filtrados.map((d, i) => {
        const nota = d.agendado
            ? `<span class="agend-pill"><i class="bi bi-clock-fill"></i>${d.hora}</span>${d.nota ? ` <span style="font-size:.72rem;color:#64748b;">${d.nota}</span>` : ''}`
            : (d.nota ? `<span style="font-size:.73rem;color:#7c3aed;font-style:italic;">${d.nota}</span>` : '<span style="color:#d1d5db;">—</span>');
        return `<tr>
            <td style="text-align:center;"><span class="orden-num">${i+1}</span></td>
            <td>
                <div style="font-weight:700;color:#1a2940;">${d.cliente}</div>
                ${d.rut ? `<div style="font-size:.68rem;color:#94a3b8;">${d.rut}</div>` : ''}
            </td>
            <td style="font-size:.78rem;color:#64748b;max-width:220px;">${d.dir}</td>
            <td>${zonaBadge(d.zona)}</td>
            <td>${choferBadge(d.chofer)}</td>
            <td style="text-align:center;">${prioBadge(d.id, d.prioridad)}</td>
            <td><span style="font-size:.76rem;"><i class="bi bi-box-seam" style="color:#94a3b8;"></i> ${d.bultos}</span></td>
            <td>${nota}</td>
            <td style="text-align:center;">
                <div style="display:flex;gap:5px;justify-content:center;">
                    <button class="act-btn act-reasignar" onclick="abrirReasignar(${d.id})" title="Cambiar chofer">
                        <i class="bi bi-arrow-left-right"></i> Reasignar
                    </button>
                    <button class="act-btn act-del" onclick="eliminarParada(${d.id})" title="Eliminar">
                        <i class="bi bi-trash3"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

// ── Reasignar ──────────────────────────────────────────────────
function abrirReasignar(id) {
    reasigId = id;
    const d = despachos.find(x=>x.id===id);
    document.getElementById('reasigCliente').textContent = d.cliente;
    document.getElementById('reasigChofer').value = d.chofer;
    new bootstrap.Modal(document.getElementById('modalReasignar')).show();
}
function confirmarReasignar() {
    const d = despachos.find(x=>x.id===reasigId);
    if (d) d.chofer = parseInt(document.getElementById('reasigChofer').value);
    bootstrap.Modal.getInstance(document.getElementById('modalReasignar')).hide();
    renderTabla();
}
function eliminarParada(id) {
    if (!confirm('¿Eliminar esta parada de la hoja de ruta?')) return;
    despachos = despachos.filter(x=>x.id!==id);
    renderTabla();
}

// ── Autocomplete clientes ──────────────────────────────────────
let acTimer = null;
async function buscarCliente(q) {
    const list = document.getElementById('acList');
    if (q.length < 2) { list.classList.remove('show'); return; }
    clearTimeout(acTimer);
    acTimer = setTimeout(async () => {
        try {
            const r = await fetch(BASE+'logistica/clientes?q='+encodeURIComponent(q));
            const j = await r.json();
            if (!j.success || !j.clientes.length) { list.classList.remove('show'); return; }
            list.innerHTML = j.clientes.map(c=>`
                <div class="ac-item" onclick="seleccionarCliente(${JSON.stringify(c).replace(/"/g,'&quot;')})">
                    <div style="font-weight:700;">${c.nombre}</div>
                    <div class="ac-rut">${c.rut} · ${c.comuna}</div>
                </div>`).join('');
            list.classList.add('show');
        } catch(e) {}
    }, 280);
}
function seleccionarCliente(c) {
    document.getElementById('nCliente').value   = c.nombre;
    document.getElementById('nClienteRut').value= c.rut;
    document.getElementById('nDireccion').value = [c.direccion, c.comuna].filter(Boolean).join(', ');
    document.getElementById('acList').classList.remove('show');
}
document.addEventListener('click', e => {
    if (!e.target.closest('.autocomplete-wrap'))
        document.getElementById('acList').classList.remove('show');
});

// ── Modal nuevo despacho ───────────────────────────────────────
function abrirModalNuevo() {
    ['nCliente','nDireccion','nNota'].forEach(id=>document.getElementById(id).value='');
    document.getElementById('nClienteRut').value='';
    document.getElementById('nZona').value='';
    document.getElementById('nChofer').value='';
    document.getElementById('nBultos').value=1;
    document.getElementById('nPrioridad').value='media';
    document.getElementById('nEsAgendado').checked=false;
    document.getElementById('nuevoError').style.display='none';
    document.getElementById('acList').classList.remove('show');
    toggleAgendado(false);
    new bootstrap.Modal(document.getElementById('modalNuevo')).show();
}
function toggleAgendado(v) {
    document.getElementById('rowFechaAgend').style.display=v?'':'none';
    document.getElementById('rowHoraAgend').style.display=v?'':'none';
}
function agregarDespacho() {
    const err     = document.getElementById('nuevoError');
    const cliente = document.getElementById('nCliente').value.trim();
    const rut     = document.getElementById('nClienteRut').value.trim();
    const dir     = document.getElementById('nDireccion').value.trim();
    const zona    = document.getElementById('nZona').value;
    const chofer  = document.getElementById('nChofer').value;
    const bultos  = parseInt(document.getElementById('nBultos').value)||1;
    const esAgend = document.getElementById('nEsAgendado').checked;
    const hora    = document.getElementById('nHoraAgend').value;
    const fechaAg = document.getElementById('nFechaAgend').value;
    const nota    = document.getElementById('nNota').value.trim();
    const prioridad = document.getElementById('nPrioridad').value || 'media';
    if (!cliente || !dir || chofer==='') { err.textContent='Completa: Cliente, Dirección y Chofer.'; err.style.display='block'; return; }
    if (esAgend && (!hora||!fechaAg)) { err.textContent='Indica fecha y hora del agendamiento.'; err.style.display='block'; return; }
    err.style.display='none';
    despachos.push({ id:nextId_++, chofer:parseInt(chofer), cliente, rut, dir, zona:zona||'Sin zona', bultos, prioridad, agendado:esAgend, hora:esAgend?hora:null, fechaAgend:esAgend?fechaAg:null, nota });
    bootstrap.Modal.getInstance(document.getElementById('modalNuevo')).hide();
    renderTabla();
}

// ── Fecha ──────────────────────────────────────────────────────
const DIAS=['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
const MESES=['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
function actualizarFecha() { document.getElementById('fechaRuta').textContent=`${DIAS[fechaRuta.getDay()]} ${fechaRuta.getDate()} ${MESES[fechaRuta.getMonth()]} ${fechaRuta.getFullYear()}`; }
function cambiarDia(d) { fechaRuta.setDate(fechaRuta.getDate()+d); actualizarFecha(); }
function irHoy() { fechaRuta=new Date(); actualizarFecha(); }
function abrirDatePicker() { const dp=document.getElementById('dpFecha'); dp.style.pointerEvents='auto'; dp.click(); setTimeout(()=>dp.style.pointerEvents='none',300); }
function seleccionarFecha(v) { if(v){ fechaRuta=new Date(v+'T12:00:00'); actualizarFecha(); } }

document.addEventListener('DOMContentLoaded', () => {
    const u=window.ADMIN_SESSION||{};
    const nom=u.nombre||'Admin'; const ini=nom.substring(0,2).toUpperCase();
    const el=(id,v)=>{const e=document.getElementById(id);if(e)e.textContent=v;};
    el('topbarAvatar',ini);el('topbarNombre',nom);
    el('sidebarAvatar',ini);el('sidebarNombre',nom);el('sidebarRol',u.perfil||'Administrador');
    const f=document.getElementById('fechaHoy');
    if(f) f.textContent=new Date().toLocaleDateString('es-CL',{day:'2-digit',month:'2-digit',year:'numeric'});
    actualizarFecha();
    renderTabla();
});
</script>
</body>
</html>
