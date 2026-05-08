<?php $activePage = 'despachos-agendados'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Despachos Agendados – Logística | Portal Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('public/assets/css/admin.css') ?>">
    <style>
        /* ── Header ── */
        .ag-header {
            background:linear-gradient(135deg,#1e3a5f,#1d4ed8);
            border-radius:18px; padding:20px 24px; margin-bottom:20px;
            box-shadow:0 4px 24px rgba(0,0,0,.18);
        }
        .ag-title { font-size:1.1rem; font-weight:800; color:#fff; display:flex; align-items:center; gap:10px; }
        .ag-title .iw { width:38px; height:38px; border-radius:10px; background:rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; font-size:1.1rem; }
        .mes-nav { display:flex; align-items:center; gap:8px; }
        .btn-mes { width:32px; height:32px; border-radius:8px; border:1.5px solid rgba(255,255,255,.2); background:rgba(255,255,255,.1); color:#fff; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:.18s; }
        .btn-mes:hover { background:rgba(255,255,255,.25); }
        .mes-label { font-size:.95rem; font-weight:800; color:#fff; min-width:180px; text-align:center; }
        .btn-hoy { font-size:.72rem; font-weight:700; color:#93c5fd; background:rgba(147,197,253,.15); border:1px solid rgba(147,197,253,.3); border-radius:6px; padding:3px 10px; cursor:pointer; }
        .stat-chip-ag { display:flex; align-items:center; gap:6px; padding:5px 12px; border-radius:20px; font-size:.76rem; font-weight:700; background:rgba(255,255,255,.12); color:#fff; border:1px solid rgba(255,255,255,.15); white-space:nowrap; }
        .btn-nuevo-ag { background:rgba(255,255,255,.15); border:1.5px solid rgba(255,255,255,.25); color:#fff; border-radius:10px; font-size:.80rem; font-weight:700; padding:7px 16px; display:flex; align-items:center; gap:6px; cursor:pointer; transition:.18s; }
        .btn-nuevo-ag:hover { background:rgba(255,255,255,.25); }

        /* ── Calendario ── */
        .cal-wrap {
            background:#fff; border-radius:18px; border:1.5px solid #e8edf5;
            overflow:hidden; box-shadow:0 2px 16px rgba(60,80,120,.08);
        }
        .cal-head {
            display:grid; grid-template-columns:repeat(7,1fr);
            background:#f8f5ff; border-bottom:2px solid #ddd6fe;
        }
        .cal-head-day {
            padding:10px 6px; text-align:center;
            font-size:.72rem; font-weight:800; color:#7c3aed;
            text-transform:uppercase; letter-spacing:.06em;
        }
        .cal-grid { display:grid; grid-template-columns:repeat(7,1fr); }
        .cal-cell {
            min-height:110px; padding:6px; border-right:1px solid #f0f4f9;
            border-bottom:1px solid #f0f4f9; position:relative;
            transition:background .15s;
        }
        .cal-cell:hover { background:#faf8ff; }
        .cal-cell.otro-mes { background:#fafafa; }
        .cal-cell.hoy { background:linear-gradient(135deg,#eff6ff,#f5f0ff); }
        .cal-cell.hoy .cal-day-num { background:#1d4ed8; color:#fff; }
        .cal-cell:nth-child(7n) { border-right:none; }
        .cal-day-num {
            width:24px; height:24px; border-radius:8px;
            font-size:.75rem; font-weight:800; color:#374151;
            display:flex; align-items:center; justify-content:center;
            margin-bottom:4px;
        }
        .cal-day-num.otro-mes { color:#d1d5db; }

        /* Chips de despacho dentro del día */
        .despacho-chip {
            display:flex; align-items:center; gap:4px;
            font-size:.68rem; font-weight:700; padding:3px 7px;
            border-radius:8px; margin-bottom:3px; cursor:pointer;
            transition:opacity .15s; line-height:1.3; max-width:100%;
            overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
        }
        .despacho-chip:hover { opacity:.82; }
        .chip-c0 { background:#dbeafe; color:#1d4ed8; border:1px solid #93c5fd; }
        .chip-c1 { background:#d1fae5; color:#059669; border:1px solid #6ee7b7; }
        .chip-c2 { background:#ede9fe; color:#7c3aed; border:1px solid #c4b5fd; }

        /* Más despachos */
        .mas-chip { font-size:.64rem; color:#94a3b8; font-weight:600; padding:1px 4px; }

        /* ── Panel lateral del día ── */
        .dia-panel {
            background:#fff; border-radius:18px; border:1.5px solid #e8edf5;
            padding:20px; box-shadow:0 2px 12px rgba(60,80,120,.07);
        }
        .dia-panel-title { font-size:.95rem; font-weight:800; color:#1a2940; margin-bottom:14px; border-bottom:2px solid #f0f4f9; padding-bottom:10px; }
        .ag-card {
            border-radius:12px; padding:12px 14px; margin-bottom:10px;
            border:1.5px solid #e8edf5;
        }
        .ag-card-hora { display:flex; align-items:center; gap:5px; font-size:.72rem; font-weight:800; color:#1d4ed8; background:#dbeafe; border-radius:6px; padding:2px 8px; width:fit-content; margin-bottom:8px; }
        .ag-card-cliente { font-size:.88rem; font-weight:800; color:#1a2940; }
        .ag-card-dir { font-size:.73rem; color:#64748b; margin-top:2px; }
        .zona-chip { font-size:.63rem; font-weight:800; padding:2px 8px; border-radius:20px; white-space:nowrap; text-transform:uppercase; letter-spacing:.04em; }

        /* ── Table futura (vista lista) ── */
        .ag-table { width:100%; border-collapse:collapse; font-size:.82rem; }
        .ag-table th { padding:10px 14px; background:#f8f5ff; color:#5b21b6; font-size:.70rem; font-weight:800; text-transform:uppercase; border-bottom:2px solid #ddd6fe; }
        .ag-table td { padding:10px 14px; border-bottom:1px solid #f0f4f9; vertical-align:middle; }
        .ag-table tr:last-child td { border-bottom:none; }
        .ag-table tr:hover td { background:#faf8ff; }

        /* Modal */
        .modal-hdr-ag { background:linear-gradient(135deg,#1e3a5f,#1d4ed8); color:#fff; border:none; padding:16px 22px; }
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

        /* Toggle vista */
        .toggle-vista { display:flex; border-radius:10px; overflow:hidden; border:1.5px solid rgba(255,255,255,.2); }
        .tv-btn { padding:5px 12px; font-size:.74rem; font-weight:700; color:rgba(255,255,255,.65); background:transparent; border:none; cursor:pointer; transition:.18s; }
        .tv-btn.active { background:rgba(255,255,255,.2); color:#fff; }
    </style>
</head>
<body>
<?= $this->include('partials/sidebar') ?>
<div class="main">
    <div class="topbar">
        <div class="d-flex align-items-center gap-2">
            <button class="btn-menu-toggle" onclick="abrirSidebar()"><i class="bi bi-list"></i></button>
            <div>
                <div class="topbar-title"><i class="bi bi-calendar-event-fill me-2" style="color:var(--accent);"></i>Despachos Agendados</div>
                <div class="topbar-sub">Portal Admin &rsaquo; Logística &rsaquo; Despachos Agendados</div>
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
        <div class="ag-header">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div>
                    <div class="ag-title mb-3">
                        <div class="iw"><i class="bi bi-calendar-event-fill"></i></div>
                        Despachos con Hora Agendada
                    </div>
                    <div class="mes-nav mb-3">
                        <button class="btn-mes" onclick="cambiarMes(-1)"><i class="bi bi-chevron-left"></i></button>
                        <div class="mes-label" id="mesLabel">—</div>
                        <button class="btn-mes" onclick="cambiarMes(1)"><i class="bi bi-chevron-right"></i></button>
                        <span class="btn-hoy" onclick="irHoy()">Este mes</span>
                    </div>
                    <div class="d-flex gap-2">
                        <div class="stat-chip-ag"><i class="bi bi-calendar-check-fill"></i> <span id="stMes">0</span> este mes</div>
                        <div class="stat-chip-ag"><i class="bi bi-calendar-day-fill"></i> <span id="stSemana">0</span> esta semana</div>
                    </div>
                </div>
                <div class="d-flex flex-column gap-2 align-items-end">
                    <button class="btn-nuevo-ag" onclick="abrirModalNuevo()"><i class="bi bi-plus-lg"></i> Nuevo agendamiento</button>
                    <div class="toggle-vista">
                        <button class="tv-btn active" id="tvCal" onclick="setVista('cal')"><i class="bi bi-grid-3x3-gap-fill me-1"></i>Calendario</button>
                        <button class="tv-btn" id="tvLista" onclick="setVista('lista')"><i class="bi bi-list-ul me-1"></i>Lista</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- VISTAS -->
        <div id="vistaCalendario">
            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="cal-wrap">
                        <div class="cal-head">
                            <div class="cal-head-day">Lun</div>
                            <div class="cal-head-day">Mar</div>
                            <div class="cal-head-day">Mié</div>
                            <div class="cal-head-day">Jue</div>
                            <div class="cal-head-day">Vie</div>
                            <div class="cal-head-day" style="color:#ef4444;">Sáb</div>
                            <div class="cal-head-day" style="color:#ef4444;">Dom</div>
                        </div>
                        <div class="cal-grid" id="calGrid"></div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="dia-panel" style="min-height:300px;">
                        <div class="dia-panel-title" id="diaSelTitle">Selecciona un día</div>
                        <div id="diaSelContent">
                            <div style="text-align:center;padding:30px 12px;color:#94a3b8;font-size:.82rem;">
                                <i class="bi bi-hand-index-thumb" style="font-size:1.5rem;display:block;margin-bottom:8px;opacity:.4;"></i>
                                Haz clic en un día del calendario para ver los despachos agendados.
                            </div>
                        </div>
                        <button class="btn-nuevo-ag mt-2" style="width:100%;justify-content:center;background:linear-gradient(135deg,#1d4ed8,#7c3aed);border:none;" id="btnAgregarDia" onclick="abrirModalNuevo()" style="display:none;">
                            <i class="bi bi-plus-lg"></i> Agendar en este día
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="vistaLista" style="display:none;">
            <div style="background:#fff;border-radius:16px;border:1.5px solid #e8edf5;overflow:hidden;box-shadow:0 2px 12px rgba(60,80,120,.07);">
                <table class="ag-table">
                    <thead>
                        <tr>
                            <th>Fecha</th><th>Hora</th><th>Cliente</th>
                            <th>Dirección</th><th>Zona</th><th>Chofer</th><th>Bultos</th><th>Nota</th><th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyLista"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL NUEVO AGENDAMIENTO -->
<div class="modal fade" id="modalNuevo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:18px;overflow:hidden;border:none;box-shadow:0 20px 60px rgba(0,0,0,.18);">
            <div class="modal-hdr-ag d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2"><i class="bi bi-calendar-plus-fill"></i><span style="font-weight:800;">Nuevo Agendamiento</span></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="f-row"><div class="f-label">Cliente *</div>
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
                    <div class="col-md-6">
                        <div class="f-row"><div class="f-label">Fecha comprometida *</div>
                        <input type="date" class="f-ctrl" id="nFecha"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="f-row"><div class="f-label">Hora comprometida *</div>
                        <input type="time" class="f-ctrl" id="nHora"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="f-row"><div class="f-label">Chofer *</div>
                        <select class="f-ctrl" id="nChofer">
                            <option value="">— Seleccionar —</option>
                            <option value="0">Carlos Ramírez</option>
                            <option value="1">Miguel Torres</option>
                            <option value="2">Rodrigo Silva</option>
                        </select></div>
                    </div>
                    <div class="col-md-6">
                        <div class="f-row"><div class="f-label">Bultos / cajas</div>
                        <input type="number" class="f-ctrl" id="nBultos" min="1" value="1"></div>
                    </div>
                    <div class="col-12">
                        <div class="f-row"><div class="f-label">Nota del cliente (coordinado por WhatsApp)</div>
                        <textarea class="f-ctrl" id="nNota" rows="2" placeholder="Lo que dijo el cliente al agendar…"></textarea></div>
                    </div>
                </div>
                <div id="nuevoError" class="alert alert-danger py-2 px-3 mt-1" style="display:none;font-size:.80rem;border-radius:8px;"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f0f4f9;">
                <button class="btn btn-sm" style="background:#f0f4f9;color:#5a7394;border-radius:9px;padding:7px 18px;" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm" onclick="agregarAgendamiento()"
                    style="background:linear-gradient(135deg,#1d4ed8,#7c3aed);color:#fff;border-radius:9px;padding:7px 20px;font-weight:700;border:none;">
                    <i class="bi bi-calendar-check-fill me-1"></i>Agendar despacho
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
    { id:0, nombre:'Carlos Ramírez', inicial:'CR', color:'#1d4ed8', chipClass:'chip-c0' },
    { id:1, nombre:'Miguel Torres',  inicial:'MT', color:'#059669', chipClass:'chip-c1' },
    { id:2, nombre:'Rodrigo Silva',  inicial:'RS', color:'#7c3aed', chipClass:'chip-c2' },
];
const ZONAS = {
    Norte:{color:'#3b82f6',bg:'#eff6ff',bdr:'#bfdbfe'},
    Sur:{color:'#10b981',bg:'#f0fdf4',bdr:'#bbf7d0'},
    Centro:{color:'#8b5cf6',bg:'#f5f0ff',bdr:'#ddd6fe'},
    Oriente:{color:'#f59e0b',bg:'#fefce8',bdr:'#fde68a'},
    Poniente:{color:'#06b6d4',bg:'#f0fdfe',bdr:'#a5f3fc'},
};

// Datos de prueba con fechas reales próximas
const hoy = new Date();
function fmtD(d) { return d.toISOString().split('T')[0]; }
function addDays(d,n){ const x=new Date(d); x.setDate(x.getDate()+n); return x; }

let agendados = [
    { id:1, chofer:0, cliente:'Fuente de soda La Palma',   rut:'',             dir:'Lo Ovalle 3512, La Pintana',        zona:'Sur',     bultos:2, hora:'10:30', fecha:fmtD(hoy),        nota:'Pedir por Fernando' },
    { id:2, chofer:1, cliente:'Cafetería Punto Azul',       rut:'',             dir:'Av. Grecia 4200, Peñalolén',        zona:'Oriente', bultos:3, hora:'15:00', fecha:fmtD(hoy),        nota:'' },
    { id:3, chofer:2, cliente:'Botillería La Cosecha',      rut:'',             dir:'Gran Av. 2100, San Miguel',         zona:'Sur',     bultos:4, hora:'11:00', fecha:fmtD(hoy),        nota:'Solo AM' },
    { id:4, chofer:0, cliente:'Hotel Los Andes',            rut:'96.685.690-4', dir:'Agustinas 814, Santiago Centro',    zona:'Centro',  bultos:5, hora:'09:00', fecha:fmtD(addDays(hoy,2)), nota:'Confirmar con conserje' },
    { id:5, chofer:1, cliente:'Distribuidora Norte Fácil',  rut:'77.287.577-0', dir:'Panamericana Norte 8900, Quilicura',zona:'Norte',   bultos:12,hora:'08:30', fecha:fmtD(addDays(hoy,2)), nota:'Pallet en rampa' },
    { id:6, chofer:2, cliente:'Rest. Sabor Caribeño',       rut:'',             dir:'Av. Tobalaba 940, Ñuñoa',          zona:'Oriente', bultos:6, hora:'13:00', fecha:fmtD(addDays(hoy,5)), nota:'' },
    { id:7, chofer:0, cliente:'Minimarket El Quisco',       rut:'10.249.958-1', dir:'Av. Independencia 2340, Independencia',zona:'Norte',bultos:4,hora:'14:00', fecha:fmtD(addDays(hoy,7)), nota:'' },
];
let nextId_ = 8;
let vista = 'cal';
let mesActual = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
let diaSeleccionado = null;

// ── Helpers ────────────────────────────────────────────────────
function zonaBadge(zona) {
    const z = ZONAS[zona]||{color:'#6b7280',bg:'#f1f5f9',bdr:'#e2e8f0'};
    return `<span class="zona-chip" style="background:${z.bg};color:${z.color};border:1px solid ${z.bdr};">${zona||'—'}</span>`;
}
function choferSpan(id) {
    const ch=CHOFERES[id];
    if(!ch) return '—';
    return `<span style="display:inline-flex;align-items:center;gap:5px;font-size:.78rem;font-weight:700;">
        <span style="width:20px;height:20px;border-radius:6px;background:${ch.color};color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:.58rem;font-weight:800;">${ch.inicial}</span>
        ${ch.nombre}
    </span>`;
}

// ── Calendario ─────────────────────────────────────────────────
function agDeDay(fecha) { return agendados.filter(a=>a.fecha===fecha); }

function renderCalendario() {
    const MESES_ES=['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    document.getElementById('mesLabel').textContent = `${MESES_ES[mesActual.getMonth()]} ${mesActual.getFullYear()}`;

    // Primer día del mes (ajustado: lunes=0)
    const primerDia = new Date(mesActual.getFullYear(), mesActual.getMonth(), 1);
    const ultimoDia = new Date(mesActual.getFullYear(), mesActual.getMonth()+1, 0);
    let dow = primerDia.getDay(); // 0=Dom
    const offset = dow===0 ? 6 : dow-1; // inicio lunes

    const grid = document.getElementById('calGrid');
    grid.innerHTML = '';

    const todayStr = fmtD(new Date());

    // Celdas vacías previas
    for (let i=0; i<offset; i++) {
        const prev = new Date(primerDia); prev.setDate(prev.getDate()-(offset-i));
        const cel = document.createElement('div');
        cel.className='cal-cell otro-mes';
        cel.innerHTML=`<div class="cal-day-num otro-mes">${prev.getDate()}</div>`;
        grid.appendChild(cel);
    }

    // Días del mes
    for (let d=1; d<=ultimoDia.getDate(); d++) {
        const ds = `${mesActual.getFullYear()}-${String(mesActual.getMonth()+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const esHoy = ds===todayStr;
        const esSelec = ds===diaSeleccionado;
        const cel = document.createElement('div');
        cel.className='cal-cell'+(esHoy?' hoy':'')+(esSelec?' hoy':'');
        cel.onclick = ()=>seleccionarDia(ds);
        cel.style.cursor='pointer';
        if(esSelec) cel.style.outline='2px solid #7c3aed';

        const dispatchs = agDeDay(ds);
        const shown = dispatchs.slice(0,2);
        const resto = dispatchs.length>2 ? dispatchs.length-2 : 0;

        cel.innerHTML=`<div class="cal-day-num">${d}</div>
            ${shown.map(a=>`<div class="despacho-chip ${CHOFERES[a.chofer]?.chipClass||''}" onclick="event.stopPropagation();seleccionarDia('${ds}')">
                <i class="bi bi-clock-fill" style="font-size:.58rem;flex-shrink:0;"></i>
                <span>${a.hora} ${a.cliente.split(' ')[0]}</span>
            </div>`).join('')}
            ${resto>0?`<div class="mas-chip">+${resto} más</div>`:''}`;
        grid.appendChild(cel);
    }

    // Stats
    const stMes = agendados.filter(a=>a.fecha.startsWith(`${mesActual.getFullYear()}-${String(mesActual.getMonth()+1).padStart(2,'0')}`)).length;
    const lun = new Date(); lun.setDate(lun.getDate()-lun.getDay()+1);
    const dom = new Date(lun); dom.setDate(dom.getDate()+6);
    const stSem = agendados.filter(a=>{ const d=new Date(a.fecha+'T12:00'); return d>=lun&&d<=dom; }).length;
    document.getElementById('stMes').textContent=stMes;
    document.getElementById('stSemana').textContent=stSem;
}

function seleccionarDia(fecha) {
    diaSeleccionado=fecha;
    renderCalendario(); // re-render para marcar día

    const DIAS_ES=['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    const MESES_ES=['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    const d=new Date(fecha+'T12:00');
    document.getElementById('diaSelTitle').textContent=`${DIAS_ES[d.getDay()]} ${d.getDate()} de ${MESES_ES[d.getMonth()]}`;

    const ags=agDeDay(fecha);
    if(!ags.length) {
        document.getElementById('diaSelContent').innerHTML=`<div style="text-align:center;padding:24px 12px;color:#94a3b8;font-size:.80rem;">
            <i class="bi bi-calendar-x" style="font-size:1.5rem;display:block;margin-bottom:8px;opacity:.3;"></i>Sin agendamientos este día.</div>`;
        return;
    }
    document.getElementById('diaSelContent').innerHTML=ags.sort((a,b)=>a.hora.localeCompare(b.hora)).map(a=>{
        const ch=CHOFERES[a.chofer];
        const z=ZONAS[a.zona]||{color:'#6b7280',bg:'#f1f5f9',bdr:'#e2e8f0'};
        return `<div class="ag-card" style="border-color:${z.bdr};">
            <div class="ag-card-hora"><i class="bi bi-clock-fill"></i>${a.hora}</div>
            <div class="ag-card-cliente">${a.cliente}</div>
            <div class="ag-card-dir"><i class="bi bi-geo-alt" style="font-size:.68rem;"></i> ${a.dir}</div>
            <div class="d-flex align-items-center gap-2 mt-2 flex-wrap">
                ${zonaBadge(a.zona)}
                <span style="display:inline-flex;align-items:center;gap:4px;font-size:.73rem;font-weight:700;">
                    <span style="width:18px;height:18px;border-radius:5px;background:${ch.color};color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:.55rem;font-weight:800;">${ch.inicial}</span>
                    ${ch.nombre}
                </span>
                <span style="font-size:.72rem;color:#64748b;"><i class="bi bi-box-seam"></i> ${a.bultos} bultos</span>
            </div>
            ${a.nota?`<div style="font-size:.70rem;color:#7c3aed;font-style:italic;margin-top:6px;background:#f5f0ff;border-radius:6px;padding:3px 7px;">${a.nota}</div>`:''}
            <button class="act-btn act-del mt-2" onclick="eliminar(${a.id})"><i class="bi bi-trash3"></i> Eliminar</button>
        </div>`;
    }).join('');
}

// ── Vista lista ────────────────────────────────────────────────
function renderLista() {
    const sorted = [...agendados].sort((a,b)=>a.fecha.localeCompare(b.fecha)||a.hora.localeCompare(b.hora));
    document.getElementById('tbodyLista').innerHTML = sorted.length
        ? sorted.map(a=>`<tr>
            <td><strong style="color:#1d4ed8;">${a.fecha}</strong></td>
            <td><span style="display:flex;align-items:center;gap:4px;font-size:.76rem;font-weight:800;color:#1d4ed8;background:#dbeafe;border-radius:6px;padding:2px 8px;width:fit-content;"><i class="bi bi-clock-fill"></i>${a.hora}</span></td>
            <td style="font-weight:700;">${a.cliente}${a.rut?`<div style="font-size:.68rem;color:#94a3b8;">${a.rut}</div>`:''}</td>
            <td style="font-size:.76rem;color:#64748b;">${a.dir}</td>
            <td>${zonaBadge(a.zona)}</td>
            <td>${choferSpan(a.chofer)}</td>
            <td><span style="font-size:.75rem;"><i class="bi bi-box-seam"></i> ${a.bultos}</span></td>
            <td style="font-size:.73rem;color:#7c3aed;font-style:italic;">${a.nota||'—'}</td>
            <td><button class="act-btn act-del" onclick="eliminar(${a.id})"><i class="bi bi-trash3"></i></button></td>
        </tr>`).join('')
        : `<tr><td colspan="9" style="text-align:center;padding:32px;color:#94a3b8;">Sin agendamientos registrados.</td></tr>`;
}

function setVista(v) {
    vista=v;
    document.getElementById('vistaCalendario').style.display=v==='cal'?'':'none';
    document.getElementById('vistaLista').style.display=v==='lista'?'':'none';
    document.getElementById('tvCal').classList.toggle('active',v==='cal');
    document.getElementById('tvLista').classList.toggle('active',v==='lista');
    if(v==='lista') renderLista();
}

// ── Mes nav ────────────────────────────────────────────────────
function cambiarMes(d) { mesActual.setMonth(mesActual.getMonth()+d); renderCalendario(); }
function irHoy() { mesActual=new Date(hoy.getFullYear(),hoy.getMonth(),1); diaSeleccionado=null; renderCalendario(); }

// ── Eliminar ───────────────────────────────────────────────────
function eliminar(id) {
    if(!confirm('¿Eliminar este agendamiento?')) return;
    agendados=agendados.filter(a=>a.id!==id);
    renderCalendario();
    if(diaSeleccionado) seleccionarDia(diaSeleccionado);
    if(vista==='lista') renderLista();
}

// ── Autocomplete clientes ──────────────────────────────────────
let acTimer=null;
async function buscarCliente(q) {
    const list=document.getElementById('acList');
    if(q.length<2){list.classList.remove('show');return;}
    clearTimeout(acTimer);
    acTimer=setTimeout(async()=>{
        try{
            const r=await fetch(BASE+'logistica/clientes?q='+encodeURIComponent(q));
            const j=await r.json();
            if(!j.success||!j.clientes.length){list.classList.remove('show');return;}
            list.innerHTML=j.clientes.map(c=>`<div class="ac-item" onclick="seleccionarCliente(${JSON.stringify(c).replace(/"/g,'&quot;')})">
                <div style="font-weight:700;">${c.nombre}</div>
                <div class="ac-rut">${c.rut} · ${c.comuna}</div>
            </div>`).join('');
            list.classList.add('show');
        }catch(e){}
    },280);
}
function seleccionarCliente(c){
    document.getElementById('nCliente').value=c.nombre;
    document.getElementById('nClienteRut').value=c.rut;
    document.getElementById('nDireccion').value=[c.direccion,c.comuna].filter(Boolean).join(', ');
    document.getElementById('acList').classList.remove('show');
}
document.addEventListener('click',e=>{if(!e.target.closest('.autocomplete-wrap'))document.getElementById('acList').classList.remove('show');});

// ── Modal ──────────────────────────────────────────────────────
function abrirModalNuevo(){
    ['nCliente','nDireccion','nNota'].forEach(id=>document.getElementById(id).value='');
    document.getElementById('nClienteRut').value='';
    document.getElementById('nZona').value='';
    document.getElementById('nChofer').value='';
    document.getElementById('nBultos').value=1;
    document.getElementById('nFecha').value=diaSeleccionado||'';
    document.getElementById('nHora').value='';
    document.getElementById('nuevoError').style.display='none';
    document.getElementById('acList').classList.remove('show');
    new bootstrap.Modal(document.getElementById('modalNuevo')).show();
}
function agregarAgendamiento(){
    const err=document.getElementById('nuevoError');
    const cliente=document.getElementById('nCliente').value.trim();
    const rut=document.getElementById('nClienteRut').value.trim();
    const dir=document.getElementById('nDireccion').value.trim();
    const zona=document.getElementById('nZona').value;
    const chofer=document.getElementById('nChofer').value;
    const bultos=parseInt(document.getElementById('nBultos').value)||1;
    const fecha=document.getElementById('nFecha').value;
    const hora=document.getElementById('nHora').value;
    const nota=document.getElementById('nNota').value.trim();
    if(!cliente||!dir||!fecha||!hora||chofer===''){err.textContent='Completa todos los campos obligatorios.';err.style.display='block';return;}
    err.style.display='none';
    agendados.push({id:nextId_++,chofer:parseInt(chofer),cliente,rut,dir,zona:zona||'Sin zona',bultos,hora,fecha,nota});
    bootstrap.Modal.getInstance(document.getElementById('modalNuevo')).hide();
    renderCalendario();
    if(diaSeleccionado===fecha) seleccionarDia(fecha);
    if(vista==='lista') renderLista();
}

// ── Init ────────────────────────────────────────────────────────
const act_btn_style=`
.act-btn { font-size:.66rem; padding:4px 9px; border-radius:7px; border:1px solid; cursor:pointer; font-weight:600; transition:.15s; }
.act-del { border-color:#fca5a5; color:#dc2626; background:#fff5f5; }
.act-del:hover { background:#dc2626; color:#fff; }`;
const sty=document.createElement('style');sty.textContent=act_btn_style;document.head.appendChild(sty);

document.addEventListener('DOMContentLoaded',()=>{
    const u=window.ADMIN_SESSION||{};
    const nom=u.nombre||'Admin';const ini=nom.substring(0,2).toUpperCase();
    const el=(id,v)=>{const e=document.getElementById(id);if(e)e.textContent=v;};
    el('topbarAvatar',ini);el('topbarNombre',nom);
    el('sidebarAvatar',ini);el('sidebarNombre',nom);el('sidebarRol',u.perfil||'Administrador');
    const f=document.getElementById('fechaHoy');
    if(f) f.textContent=new Date().toLocaleDateString('es-CL',{day:'2-digit',month:'2-digit',year:'numeric'});
    renderCalendario();
    seleccionarDia(fmtD(hoy));
});
</script>
</body>
</html>
