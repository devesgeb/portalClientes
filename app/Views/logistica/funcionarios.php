<?php $activePage = 'funcionarios'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Funcionarios – Logística | Portal Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('public/assets/css/admin.css') ?>">
    <style>
        /* ── Header ── */
        .func-header {
            background: linear-gradient(135deg, #1e3a5f 0%, #7c3aed 100%);
            border-radius: 18px; padding: 20px 24px; margin-bottom: 20px;
            box-shadow: 0 4px 24px rgba(0,0,0,.2);
        }
        .func-title { font-size:1.1rem; font-weight:800; color:#fff; display:flex; align-items:center; gap:10px; }
        .func-title .iw { width:38px; height:38px; border-radius:10px; background:rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; font-size:1.1rem; }
        .stat-pill { display:flex; align-items:center; gap:6px; padding:5px 13px; border-radius:20px; font-size:.76rem; font-weight:700; background:rgba(255,255,255,.12); color:#fff; border:1px solid rgba(255,255,255,.18); white-space:nowrap; }
        .btn-nuevo-func { background:rgba(255,255,255,.15); border:1.5px solid rgba(255,255,255,.25); color:#fff; border-radius:10px; font-size:.80rem; font-weight:700; padding:7px 16px; display:flex; align-items:center; gap:6px; cursor:pointer; transition:.18s; }
        .btn-nuevo-func:hover { background:rgba(255,255,255,.28); }

        /* ── Toolbar ── */
        .toolbar { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:18px; background:#fff; border-radius:14px; padding:12px 16px; border:1.5px solid #e8edf5; box-shadow:0 1px 6px rgba(60,80,120,.05); }
        .tb-label { font-size:.72rem; font-weight:700; color:#94a3b8; white-space:nowrap; }
        .tb-select { font-size:.78rem; padding:5px 10px; border-radius:8px; border:1.5px solid #e2e8f0; color:#374151; font-weight:600; background:#f8f9fc; cursor:pointer; }
        .tb-select:focus { outline:none; border-color:#7c3aed; }
        .tb-search { flex:1; min-width:160px; font-size:.78rem; padding:5px 10px; border-radius:8px; border:1.5px solid #e2e8f0; font-family:'Inter',sans-serif; }
        .tb-search:focus { outline:none; border-color:#7c3aed; }

        /* ── Grilla de cards ── */
        .func-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:18px; }

        .func-card {
            background:#fff; border-radius:18px; border:1.5px solid #e8edf5;
            overflow:hidden; box-shadow:0 2px 12px rgba(60,80,120,.07);
            transition:box-shadow .2s, transform .2s;
        }
        .func-card:hover { box-shadow:0 8px 28px rgba(60,80,120,.14); transform:translateY(-2px); }
        .func-card.inactivo { opacity:.58; filter:grayscale(.5); }

        .card-top {
            padding:18px 18px 14px;
            display:flex; align-items:center; gap:14px;
            border-bottom:1.5px solid #f0f4f9;
        }
        .func-avatar {
            width:54px; height:54px; border-radius:14px;
            display:flex; align-items:center; justify-content:center;
            font-size:1.3rem; font-weight:900; color:#fff; flex-shrink:0;
        }
        .cargo-chofer    { background:linear-gradient(135deg,#1d4ed8,#3b82f6); }
        .cargo-vendedor  { background:linear-gradient(135deg,#059669,#10b981); }
        .cargo-operador  { background:linear-gradient(135deg,#7c3aed,#a78bfa); }

        .func-name { font-size:.95rem; font-weight:800; color:#1a2940; line-height:1.2; }
        .func-cargo-badge {
            font-size:.65rem; font-weight:800; padding:2px 9px; border-radius:20px;
            text-transform:uppercase; letter-spacing:.04em; display:inline-block; margin-top:4px;
        }
        .badge-chofer   { background:#dbeafe; color:#1d4ed8; border:1px solid #93c5fd; }
        .badge-vendedor { background:#d1fae5; color:#059669; border:1px solid #6ee7b7; }
        .badge-operador { background:#ede9fe; color:#7c3aed; border:1px solid #c4b5fd; }

        .card-body { padding:12px 18px; }
        .info-row { display:flex; align-items:center; gap:7px; font-size:.78rem; color:#64748b; margin-bottom:7px; }
        .info-row i { font-size:.82rem; color:#94a3b8; width:16px; }
        .info-row strong { color:#374151; }

        .card-footer-btns { display:flex; gap:6px; padding:10px 14px; border-top:1.5px solid #f0f4f9; background:#fafbfc; }
        .cbtn {
            flex:1; padding:6px 4px; border-radius:9px; border:1.5px solid;
            font-size:.72rem; font-weight:700; cursor:pointer; transition:.15s;
            display:flex; align-items:center; justify-content:center; gap:4px;
        }
        .cbtn-ver  { border-color:#c4b5fd; color:#7c3aed; background:#f5f0ff; }
        .cbtn-ver:hover { background:#7c3aed; color:#fff; }
        .cbtn-edit { border-color:#bfdbfe; color:#1d4ed8; background:#eff6ff; }
        .cbtn-edit:hover { background:#1d4ed8; color:#fff; }
        .cbtn-del  { border-color:#fca5a5; color:#dc2626; background:#fff5f5; }
        .cbtn-del:hover { background:#dc2626; color:#fff; }
        .cbtn-hist { border-color:#bbf7d0; color:#059669; background:#f0fdf4; }
        .cbtn-hist:hover { background:#059669; color:#fff; }

        /* ── Modal ── */
        .modal-hdr-func { background:linear-gradient(135deg,#1e3a5f,#7c3aed); color:#fff; border:none; padding:16px 22px; }
        .f-label { font-size:.76rem; font-weight:700; color:#5b21b6; margin-bottom:4px; }
        .f-ctrl { width:100%; padding:8px 11px; border:1.5px solid #ddd6fe; border-radius:9px; font-size:.83rem; font-family:'Inter',sans-serif; transition:.2s; }
        .f-ctrl:focus { outline:none; border-color:#7c3aed; }
        .f-row { margin-bottom:14px; }

        /* ── Hoja de vida (timeline) ── */
        .hv-section { padding:16px 20px; }
        .hv-add-btn {
            width:100%; padding:7px; border-radius:10px;
            border:1.5px dashed #c4b5fd; background:transparent;
            color:#7c3aed; font-size:.76rem; font-weight:700;
            cursor:pointer; transition:.18s; margin-bottom:16px;
            display:flex; align-items:center; justify-content:center; gap:5px;
        }
        .hv-add-btn:hover { background:#f5f0ff; }
        .timeline { position:relative; padding-left:24px; }
        .timeline::before { content:''; position:absolute; left:7px; top:4px; bottom:4px; width:2px; background:#e8edf5; border-radius:2px; }
        .tl-event { position:relative; margin-bottom:18px; }
        .tl-dot {
            width:16px; height:16px; border-radius:50%; position:absolute;
            left:-24px; top:2px; display:flex; align-items:center; justify-content:center;
            font-size:.52rem; border:2.5px solid #fff; box-shadow:0 0 0 1.5px currentColor;
        }
        .tl-card { background:#f8f9fc; border-radius:11px; padding:10px 13px; border:1.5px solid #e8edf5; }
        .tl-head { display:flex; align-items:center; gap:7px; margin-bottom:5px; flex-wrap:wrap; }
        .tl-tipo { font-size:.68rem; font-weight:800; padding:2px 8px; border-radius:20px; text-transform:uppercase; letter-spacing:.04em; }
        .tl-fecha { font-size:.68rem; color:#94a3b8; margin-left:auto; }
        .tl-desc { font-size:.78rem; color:#374151; }
        .tl-grav { font-size:.62rem; font-weight:700; padding:2px 7px; border-radius:20px; margin-left:4px; }
        .tl-del { background:none; border:none; color:#fca5a5; font-size:.70rem; cursor:pointer; padding:2px 5px; border-radius:5px; }
        .tl-del:hover { background:#fff5f5; color:#dc2626; }

        /* Colores evento */
        .ev-accidente { background:#fee2e2; color:#dc2626; }
        .ev-infraccion { background:#fef3c7; color:#d97706; }
        .ev-mantencion { background:#dbeafe; color:#1d4ed8; }
        .ev-capacitacion { background:#d1fae5; color:#059669; }
        .ev-licencia { background:#ede9fe; color:#7c3aed; }
        .ev-otro { background:#f1f5f9; color:#64748b; }
        .dot-accidente { color:#dc2626; }
        .dot-infraccion { color:#d97706; }
        .dot-mantencion { color:#1d4ed8; }
        .dot-capacitacion { color:#059669; }
        .dot-licencia { color:#7c3aed; }
        .dot-otro { color:#64748b; }
        .grav-grave { background:#fee2e2; color:#dc2626; }
        .grav-moderado { background:#fef3c7; color:#d97706; }
        .grav-leve { background:#fefce8; color:#ca8a04; }
        .grav-sin { background:#f1f5f9; color:#94a3b8; }

        /* Empty state */
        .empty-func { text-align:center; padding:60px 20px; color:#94a3b8; }
        .empty-func i { font-size:2.5rem; opacity:.25; display:block; margin-bottom:10px; }
    </style>
</head>
<body>
<?= $this->include('partials/sidebar') ?>
<div class="main">
    <div class="topbar">
        <div class="d-flex align-items-center gap-2">
            <button class="btn-menu-toggle" onclick="abrirSidebar()"><i class="bi bi-list"></i></button>
            <div>
                <div class="topbar-title"><i class="bi bi-people-fill me-2" style="color:var(--accent);"></i>Funcionarios</div>
                <div class="topbar-sub">Portal Admin › Logística › Funcionarios</div>
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

        <!-- HEADER -->
        <div class="func-header">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div>
                    <div class="func-title mb-3">
                        <div class="iw"><i class="bi bi-people-fill"></i></div>
                        Funcionarios de Logística
                    </div>
                    <div class="d-flex gap-2 flex-wrap" id="statsHeader">
                        <div class="stat-pill"><i class="bi bi-people-fill"></i> <span id="stTotal">…</span> funcionarios</div>
                        <div class="stat-pill"><i class="bi bi-truck"></i> <span id="stChoferes">…</span> choferes</div>
                        <div class="stat-pill"><i class="bi bi-bag-fill"></i> <span id="stVendedores">…</span> vendedores</div>
                        <div class="stat-pill"><i class="bi bi-box-seam-fill"></i> <span id="stOperadores">…</span> operadores</div>
                    </div>
                </div>
                <button class="btn-nuevo-func" onclick="abrirModalNuevo()">
                    <i class="bi bi-person-plus-fill"></i> Nuevo funcionario
                </button>
            </div>
        </div>

        <!-- TOOLBAR -->
        <div class="toolbar">
            <span class="tb-label">CARGO</span>
            <select class="tb-select" id="filtroCargo" onchange="filtrar()">
                <option value="">Todos</option>
                <option>Chofer</option>
                <option>Vendedor Autoventa</option>
                <option>Operador Logístico</option>
            </select>
            <span class="tb-label" style="margin-left:8px;">ESTADO</span>
            <select class="tb-select" id="filtroEstado" onchange="filtrar()">
                <option value="">Todos</option>
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
            </select>
            <input type="text" class="tb-search" id="busqueda" placeholder="🔍 Buscar por nombre, RUT…" oninput="filtrar()">
        </div>

        <!-- GRID -->
        <div class="func-grid" id="funcGrid">
            <div class="empty-func" style="grid-column:1/-1;">
                <i class="bi bi-hourglass-split"></i>Cargando…
            </div>
        </div>

    </div>
</div>

<!-- ═══ MODAL NUEVO / EDITAR ═══════════════════════════════════ -->
<div class="modal fade" id="modalForm" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:18px;overflow:hidden;border:none;box-shadow:0 20px 60px rgba(0,0,0,.18);">
            <div class="modal-hdr-func d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-person-badge-fill"></i>
                    <span style="font-weight:800;" id="modalFormTitle">Nuevo Funcionario</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="fId">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="f-row"><div class="f-label">Nombre *</div>
                        <input type="text" class="f-ctrl" id="fNombre" placeholder="Nombre"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="f-row"><div class="f-label">Apellidos *</div>
                        <input type="text" class="f-ctrl" id="fApellidos" placeholder="Apellidos"></div>
                    </div>
                    <div class="col-md-4">
                        <div class="f-row"><div class="f-label">RUT *</div>
                        <input type="text" class="f-ctrl" id="fRut" placeholder="12.345.678-9"></div>
                    </div>
                    <div class="col-md-4">
                        <div class="f-row"><div class="f-label">Fecha de ingreso *</div>
                        <input type="date" class="f-ctrl" id="fFechaIngreso"></div>
                    </div>
                    <div class="col-md-4">
                        <div class="f-row"><div class="f-label">Cargo *</div>
                        <select class="f-ctrl" id="fCargo">
                            <option value="">— Seleccionar —</option>
                            <option>Chofer</option>
                            <option>Vendedor Autoventa</option>
                            <option>Operador Logístico</option>
                        </select></div>
                    </div>
                    <div class="col-md-6">
                        <div class="f-row"><div class="f-label">Patente vehículo asignado</div>
                        <input type="text" class="f-ctrl" id="fPatente" placeholder="BCDF-31 (opcional)" style="text-transform:uppercase;"></div>
                    </div>
                    <div class="col-md-6" id="rowActivo" style="display:none;">
                        <div class="f-row"><div class="f-label">Estado</div>
                        <select class="f-ctrl" id="fActivo">
                            <option value="1">✅ Activo</option>
                            <option value="0">⛔ Inactivo</option>
                        </select></div>
                    </div>
                </div>
                <div id="formError" class="alert alert-danger py-2 px-3 mt-2" style="display:none;font-size:.80rem;border-radius:8px;"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f0f4f9;">
                <button class="btn btn-sm" style="background:#f0f4f9;color:#5a7394;border-radius:9px;padding:7px 18px;" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm" id="btnGuardar" onclick="guardarFuncionario()"
                    style="background:linear-gradient(135deg,#1e3a5f,#7c3aed);color:#fff;border-radius:9px;padding:7px 20px;font-weight:700;border:none;">
                    <i class="bi bi-check-lg me-1"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ MODAL VISUALIZAR ══════════════════════════════════════ -->
<div class="modal fade" id="modalVer" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content" style="border-radius:18px;overflow:hidden;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15);">
            <div class="modal-hdr-func d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-person-circle"></i>
                    <span style="font-weight:800;" id="verNombre">Funcionario</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
            </div>
            <div class="modal-body p-0">
                <div style="padding:20px 22px;" id="verBody">—</div>
            </div>
        </div>
    </div>
</div>

<!-- ═══ MODAL HOJA DE VIDA ════════════════════════════════════ -->
<div class="modal fade" id="modalHV" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:18px;overflow:hidden;border:none;box-shadow:0 20px 60px rgba(0,0,0,.18);">
            <div class="modal-hdr-func d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-journal-text"></i>
                    <span style="font-weight:800;">Hoja de Vida — <span id="hvNombre"></span></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
            </div>
            <div class="modal-body p-0" style="max-height:75vh;overflow-y:auto;">
                <!-- Formulario agregar evento -->
                <div style="background:#f8f5ff;border-bottom:1.5px solid #e8edf5;padding:16px 20px;">
                    <div style="font-size:.76rem;font-weight:800;color:#7c3aed;margin-bottom:10px;">
                        <i class="bi bi-plus-circle-fill me-1"></i>Agregar evento al historial
                    </div>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <div class="f-label" style="color:#7c3aed;">Fecha</div>
                            <input type="date" class="f-ctrl" id="evFecha" style="border-color:#ddd6fe;">
                        </div>
                        <div class="col-md-3">
                            <div class="f-label" style="color:#7c3aed;">Tipo *</div>
                            <select class="f-ctrl" id="evTipo" style="border-color:#ddd6fe;">
                                <option>Accidente</option><option>Infracción</option>
                                <option>Mantención</option><option>Capacitación</option>
                                <option>Licencia</option><option>Otro</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="f-label" style="color:#7c3aed;">Gravedad</div>
                            <select class="f-ctrl" id="evGravedad" style="border-color:#ddd6fe;">
                                <option>Sin gravedad</option><option>Leve</option>
                                <option>Moderado</option><option>Grave</option>
                            </select>
                        </div>
                        <div class="col-md-9">
                            <div class="f-label" style="color:#7c3aed;">Descripción *</div>
                            <input type="text" class="f-ctrl" id="evDesc" placeholder="Detalle del evento…" style="border-color:#ddd6fe;">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button onclick="agregarEvento()"
                                style="width:100%;padding:8px;border-radius:9px;background:linear-gradient(135deg,#1e3a5f,#7c3aed);color:#fff;border:none;font-weight:700;font-size:.78rem;cursor:pointer;">
                                <i class="bi bi-plus-lg me-1"></i>Agregar
                            </button>
                        </div>
                    </div>
                    <div id="evError" class="mt-2" style="font-size:.76rem;color:#dc2626;display:none;"></div>
                </div>
                <!-- Timeline -->
                <div class="hv-section" id="hvTimeline">
                    <div style="text-align:center;padding:24px;color:#94a3b8;font-size:.82rem;">
                        <i class="bi bi-journal-x" style="font-size:1.5rem;display:block;margin-bottom:8px;opacity:.3;"></i>
                        Sin eventos registrados.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE = "<?= rtrim(site_url(), '/') . '/' ?>";
window.ADMIN_SESSION = <?= json_encode($usuario ?? ['nombre'=>'Administrador','perfil'=>'Administrador']) ?>;

let funcionarios = [];
let hvFuncionarioId = null;

// ── Helpers ────────────────────────────────────────────────────
function avatarIniciales(nombre, apellidos) {
    return (nombre[0]||'') + (apellidos[0]||'');
}
function avatarClass(cargo) {
    if (cargo==='Chofer') return 'cargo-chofer';
    if (cargo==='Vendedor Autoventa') return 'cargo-vendedor';
    return 'cargo-operador';
}
function badgeClass(cargo) {
    if (cargo==='Chofer') return 'badge-chofer';
    if (cargo==='Vendedor Autoventa') return 'badge-vendedor';
    return 'badge-operador';
}
function fmtFecha(iso) {
    if (!iso) return '—';
    const d = new Date(iso+'T12:00'); 
    return d.toLocaleDateString('es-CL',{day:'2-digit',month:'2-digit',year:'numeric'});
}

// ── Cargar funcionarios desde API ─────────────────────────────
async function cargarFuncionarios() {
    try {
        const r = await fetch(BASE + 'logistica/api/funcionarios');
        const j = await r.json();
        funcionarios = j.data || [];
        actualizarStats();
        filtrar();
    } catch(e) {
        document.getElementById('funcGrid').innerHTML = `<div class="empty-func" style="grid-column:1/-1;"><i class="bi bi-exclamation-triangle"></i>Error al cargar datos.</div>`;
    }
}

function actualizarStats() {
    document.getElementById('stTotal').textContent     = funcionarios.filter(f=>f.activo=='1').length;
    document.getElementById('stChoferes').textContent  = funcionarios.filter(f=>f.cargo==='Chofer'&&f.activo=='1').length;
    document.getElementById('stVendedores').textContent= funcionarios.filter(f=>f.cargo==='Vendedor Autoventa'&&f.activo=='1').length;
    document.getElementById('stOperadores').textContent= funcionarios.filter(f=>f.cargo==='Operador Logístico'&&f.activo=='1').length;
}

function filtrar() {
    const cargo  = document.getElementById('filtroCargo').value;
    const estado = document.getElementById('filtroEstado').value;
    const q      = document.getElementById('busqueda').value.toLowerCase();
    const filtrados = funcionarios.filter(f => {
        if (cargo  && f.cargo !== cargo) return false;
        if (estado !== '' && String(f.activo) !== estado) return false;
        if (q && !`${f.nombre} ${f.apellidos} ${f.rut}`.toLowerCase().includes(q)) return false;
        return true;
    });
    renderGrid(filtrados);
}

function renderGrid(lista) {
    const grid = document.getElementById('funcGrid');
    if (!lista.length) {
        grid.innerHTML = `<div class="empty-func" style="grid-column:1/-1;">
            <i class="bi bi-person-x"></i>No se encontraron funcionarios.</div>`;
        return;
    }
    grid.innerHTML = lista.map(f => {
        const ini = avatarIniciales(f.nombre, f.apellidos);
        const ac  = avatarClass(f.cargo);
        const bc  = badgeClass(f.cargo);
        const inactivo = f.activo=='0' ? ' inactivo' : '';
        return `
        <div class="func-card${inactivo}">
            <div class="card-top">
                <div class="func-avatar ${ac}">${ini}</div>
                <div style="flex:1;min-width:0;">
                    <div class="func-name">${f.nombre} ${f.apellidos}</div>
                    <span class="func-cargo-badge ${bc}">${f.cargo}</span>
                    ${f.activo=='0' ? `<span style="font-size:.63rem;color:#dc2626;font-weight:700;margin-left:4px;">⛔ Inactivo</span>` : ''}
                </div>
            </div>
            <div class="card-body">
                <div class="info-row"><i class="bi bi-card-text"></i><span>RUT: <strong>${f.rut}</strong></span></div>
                <div class="info-row"><i class="bi bi-calendar-check"></i><span>Ingreso: <strong>${fmtFecha(f.fecha_ingreso)}</strong></span></div>
                <div class="info-row"><i class="bi bi-truck"></i><span>Patente: <strong>${f.patente||'—'}</strong></span></div>
            </div>
            <div class="card-footer-btns">
                <button class="cbtn cbtn-ver"  onclick="verFuncionario(${f.id})"><i class="bi bi-eye-fill"></i>Ver</button>
                <button class="cbtn cbtn-edit" onclick="editarFuncionario(${f.id})"><i class="bi bi-pencil-fill"></i>Editar</button>
                <button class="cbtn cbtn-hist" onclick="abrirHojaVida(${f.id})"><i class="bi bi-journal-text"></i>Historial</button>
                <button class="cbtn cbtn-del"  onclick="eliminarFuncionario(${f.id})"><i class="bi bi-trash3-fill"></i>Eliminar</button>
            </div>
        </div>`;
    }).join('');
}

// ── Modal Nuevo / Editar ──────────────────────────────────────
function abrirModalNuevo() {
    document.getElementById('fId').value         = '';
    document.getElementById('fNombre').value     = '';
    document.getElementById('fApellidos').value  = '';
    document.getElementById('fRut').value        = '';
    document.getElementById('fFechaIngreso').value = '';
    document.getElementById('fCargo').value      = '';
    document.getElementById('fPatente').value    = '';
    document.getElementById('fActivo').value     = '1';
    document.getElementById('rowActivo').style.display = 'none';
    document.getElementById('formError').style.display = 'none';
    document.getElementById('modalFormTitle').textContent = 'Nuevo Funcionario';
    new bootstrap.Modal(document.getElementById('modalForm')).show();
}
function editarFuncionario(id) {
    const f = funcionarios.find(x=>x.id==id);
    if (!f) return;
    document.getElementById('fId').value         = f.id;
    document.getElementById('fNombre').value     = f.nombre;
    document.getElementById('fApellidos').value  = f.apellidos;
    document.getElementById('fRut').value        = f.rut;
    document.getElementById('fFechaIngreso').value = f.fecha_ingreso;
    document.getElementById('fCargo').value      = f.cargo;
    document.getElementById('fPatente').value    = f.patente||'';
    document.getElementById('fActivo').value     = f.activo;
    document.getElementById('rowActivo').style.display = '';
    document.getElementById('formError').style.display = 'none';
    document.getElementById('modalFormTitle').textContent = 'Editar Funcionario';
    new bootstrap.Modal(document.getElementById('modalForm')).show();
}

async function guardarFuncionario() {
    const err = document.getElementById('formError');
    const id  = document.getElementById('fId').value;
    const data = {
        nombre:       document.getElementById('fNombre').value.trim(),
        apellidos:    document.getElementById('fApellidos').value.trim(),
        rut:          document.getElementById('fRut').value.trim(),
        fecha_ingreso:document.getElementById('fFechaIngreso').value,
        cargo:        document.getElementById('fCargo').value,
        patente:      document.getElementById('fPatente').value.trim(),
        activo:       document.getElementById('fActivo').value,
    };
    if (!data.nombre||!data.apellidos||!data.rut||!data.fecha_ingreso||!data.cargo) {
        err.textContent='Completa los campos obligatorios (*).'; err.style.display='block'; return;
    }
    err.style.display='none';
    const url    = id ? `${BASE}logistica/api/funcionarios/${id}` : `${BASE}logistica/api/funcionarios`;
    const method = id ? 'PUT' : 'POST';
    const r = await fetch(url, { method, headers:{'Content-Type':'application/json'}, body:JSON.stringify(data) });
    const j = await r.json();
    if (!j.success) { err.textContent=j.error||'Error al guardar.'; err.style.display='block'; return; }
    bootstrap.Modal.getInstance(document.getElementById('modalForm')).hide();
    cargarFuncionarios();
}

// ── Modal Visualizar ──────────────────────────────────────────
function verFuncionario(id) {
    const f = funcionarios.find(x=>x.id==id);
    if (!f) return;
    const bc = badgeClass(f.cargo);
    document.getElementById('verNombre').textContent = `${f.nombre} ${f.apellidos}`;
    document.getElementById('verBody').innerHTML = `
        <div style="text-align:center;margin-bottom:16px;">
            <div class="func-avatar ${avatarClass(f.cargo)}" style="width:70px;height:70px;border-radius:18px;font-size:1.6rem;margin:0 auto 10px;">
                ${avatarIniciales(f.nombre,f.apellidos)}
            </div>
            <span class="func-cargo-badge ${bc}" style="font-size:.70rem;">${f.cargo}</span>
            ${f.activo=='0'?`<span style="font-size:.70rem;color:#dc2626;font-weight:700;margin-left:6px;">⛔ Inactivo</span>`:''}
        </div>
        <table style="width:100%;font-size:.83rem;border-collapse:collapse;">
            ${row('Nombre completo',`${f.nombre} ${f.apellidos}`,'person')}
            ${row('RUT',f.rut,'card-text')}
            ${row('Cargo',f.cargo,'briefcase')}
            ${row('Fecha de ingreso',fmtFecha(f.fecha_ingreso),'calendar-check')}
            ${row('Patente vehículo',f.patente||'—','truck')}
        </table>`;
    new bootstrap.Modal(document.getElementById('modalVer')).show();
}
function row(label, value, icon) {
    return `<tr>
        <td style="padding:9px 0;border-bottom:1px solid #f0f4f9;color:#64748b;width:45%;">
            <i class="bi bi-${icon}" style="margin-right:5px;"></i>${label}
        </td>
        <td style="padding:9px 0;border-bottom:1px solid #f0f4f9;font-weight:700;color:#1a2940;">${value}</td>
    </tr>`;
}

// ── Eliminar ──────────────────────────────────────────────────
async function eliminarFuncionario(id) {
    const f = funcionarios.find(x=>x.id==id);
    if (!confirm(`¿Eliminar a ${f?.nombre} ${f?.apellidos}? Esto también eliminará su historial de conducción.`)) return;
    await fetch(`${BASE}logistica/api/funcionarios/${id}`, { method:'DELETE' });
    cargarFuncionarios();
}

// ── Hoja de Vida ──────────────────────────────────────────────
async function abrirHojaVida(id) {
    hvFuncionarioId = id;
    const f = funcionarios.find(x=>x.id==id);
    document.getElementById('hvNombre').textContent = `${f.nombre} ${f.apellidos}`;
    document.getElementById('evFecha').value = new Date().toISOString().split('T')[0];
    document.getElementById('evTipo').value  = 'Accidente';
    document.getElementById('evGravedad').value = 'Sin gravedad';
    document.getElementById('evDesc').value  = '';
    document.getElementById('evError').style.display = 'none';
    new bootstrap.Modal(document.getElementById('modalHV')).show();
    await cargarEventos();
}

async function cargarEventos() {
    const r = await fetch(`${BASE}logistica/api/funcionarios/${hvFuncionarioId}/eventos`);
    const j = await r.json();
    renderTimeline(j.data || []);
}

function renderTimeline(eventos) {
    const tl = document.getElementById('hvTimeline');
    if (!eventos.length) {
        tl.innerHTML = `<div style="text-align:center;padding:24px;color:#94a3b8;font-size:.82rem;">
            <i class="bi bi-journal-x" style="font-size:1.5rem;display:block;margin-bottom:8px;opacity:.3;"></i>
            Sin eventos registrados.</div>`;
        return;
    }
    const tipoClass = t => ({ Accidente:'ev-accidente', Infracción:'ev-infraccion', Mantención:'ev-mantencion', Capacitación:'ev-capacitacion', Licencia:'ev-licencia' }[t] || 'ev-otro');
    const dotClass  = t => ({ Accidente:'dot-accidente', Infracción:'dot-infraccion', Mantención:'dot-mantencion', Capacitación:'dot-capacitacion', Licencia:'dot-licencia' }[t] || 'dot-otro');
    const gravClass = g => ({ Grave:'grav-grave', Moderado:'grav-moderado', Leve:'grav-leve' }[g] || 'grav-sin');
    const gravIcon  = g => ({ Grave:'⛔', Moderado:'⚠️', Leve:'🟡' }[g] || '');
    tl.innerHTML = `<div class="timeline">` + eventos.map(ev=>`
        <div class="tl-event">
            <div class="tl-dot ${dotClass(ev.tipo)}" style="background:currentColor;"><span style="color:#fff;font-size:.52rem;">●</span></div>
            <div class="tl-card">
                <div class="tl-head">
                    <span class="tl-tipo ${tipoClass(ev.tipo)}">${ev.tipo}</span>
                    ${ev.gravedad!=='Sin gravedad'?`<span class="tl-grav ${gravClass(ev.gravedad)}">${gravIcon(ev.gravedad)} ${ev.gravedad}</span>`:''}
                    <span class="tl-fecha">${fmtFecha(ev.fecha)}</span>
                    <button class="tl-del" onclick="eliminarEvento(${ev.id})" title="Eliminar"><i class="bi bi-trash3"></i></button>
                </div>
                <div class="tl-desc">${ev.descripcion}</div>
            </div>
        </div>`).join('') + `</div>`;
}

async function agregarEvento() {
    const err  = document.getElementById('evError');
    const data = {
        fecha:       document.getElementById('evFecha').value,
        tipo:        document.getElementById('evTipo').value,
        gravedad:    document.getElementById('evGravedad').value,
        descripcion: document.getElementById('evDesc').value.trim(),
    };
    if (!data.descripcion) { err.textContent='La descripción es obligatoria.'; err.style.display='block'; return; }
    err.style.display='none';
    const r = await fetch(`${BASE}logistica/api/funcionarios/${hvFuncionarioId}/eventos`, {
        method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data)
    });
    const j = await r.json();
    if (!j.success) { err.textContent=j.error||'Error.'; err.style.display='block'; return; }
    document.getElementById('evDesc').value = '';
    await cargarEventos();
}

async function eliminarEvento(id) {
    if (!confirm('¿Eliminar este evento del historial?')) return;
    await fetch(`${BASE}logistica/api/eventos/${id}`, { method:'DELETE' });
    await cargarEventos();
}

// ── Init ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const u=window.ADMIN_SESSION||{};
    const nom=u.nombre||'Admin'; const ini=nom.substring(0,2).toUpperCase();
    const el=(id,v)=>{const e=document.getElementById(id);if(e)e.textContent=v;};
    el('topbarAvatar',ini);el('topbarNombre',nom);
    el('sidebarAvatar',ini);el('sidebarNombre',nom);el('sidebarRol',u.perfil||'Administrador');
    const f=document.getElementById('fechaHoy');
    if(f) f.textContent=new Date().toLocaleDateString('es-CL',{day:'2-digit',month:'2-digit',year:'numeric'});
    cargarFuncionarios();
});
</script>
</body>
</html>
