<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Admin – Usuarios</title>
    <meta name="description" content="Gestión de usuarios del sistema.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('public/assets/css/admin.css') ?>">
    <style>
        /* ── Usuarios Module ─────────────────────────────────────────── */
        .usr-tabs { display:flex; gap:8px; margin-bottom:20px; flex-wrap:wrap; }
        .usr-tab  {
            padding:7px 20px; border-radius:50px; font-size:.82rem; font-weight:600;
            border:2px solid transparent; cursor:pointer; transition:.2s;
            background:#f0f4f9; color:#5a7394;
        }
        .usr-tab:hover  { background:#e2e8f0; }
        .usr-tab.active { background:var(--accent); color:#fff; border-color:var(--accent); }
        .usr-tab .badge-cnt {
            display:inline-block; background:rgba(255,255,255,.3); color:inherit;
            font-size:.72rem; border-radius:50px; padding:1px 7px; margin-left:4px;
        }
        .usr-tab:not(.active) .badge-cnt { background:#e2e8f0; }

        /* Tabla */
        .usr-table-wrap { border-radius:14px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.06); }
        #tablaUsuarios  { margin:0; font-size:.84rem; }
        #tablaUsuarios thead th {
            background:#1e1b4b; color:#c7d2fe; font-weight:600;
            font-size:.78rem; letter-spacing:.04em; text-transform:uppercase;
            padding:12px 16px; border:none;
        }
        #tablaUsuarios tbody tr { transition:.15s; }
        #tablaUsuarios tbody tr:hover { background:#f8faff; }
        #tablaUsuarios tbody td { padding:12px 16px; vertical-align:middle; border-color:#f0f4f9; }

        /* Avatar inline */
        .usr-avatar {
            width:34px; height:34px; border-radius:50%; background:linear-gradient(135deg,#4338ca,#7c3aed);
            color:#fff; display:inline-flex; align-items:center; justify-content:center;
            font-weight:700; font-size:.78rem; flex-shrink:0;
        }
        .usr-name  { font-weight:600; color:var(--text-main); }
        .usr-email { font-size:.78rem; color:var(--text-sub); }

        /* Badges de perfil */
        .badge-perfil {
            display:inline-block; font-size:.72rem; font-weight:600; border-radius:50px;
            padding:3px 10px;
        }
        .badge-admin    { background:#ede9fe; color:#6d28d9; }
        .badge-cliente  { background:#dbeafe; color:#1d4ed8; }
        .badge-proveedor{ background:#dcfce7; color:#15803d; }

        /* Estado */
        .badge-activo   { background:#dcfce7; color:#15803d; font-size:.72rem; border-radius:50px; padding:3px 10px; font-weight:600; }
        .badge-inactivo { background:#fee2e2; color:#dc2626; font-size:.72rem; border-radius:50px; padding:3px 10px; font-weight:600; }

        /* Botones de acción */
        .btn-accion {
            width:32px; height:32px; border-radius:8px; border:none;
            display:inline-flex; align-items:center; justify-content:center;
            font-size:.82rem; cursor:pointer; transition:.15s;
        }
        .btn-editar    { background:#fff3e0; color:#e65100; }
        .btn-editar:hover { background:#e65100; color:#fff; }
        .btn-permisos  { background:#e0f2fe; color:#0277bd; }
        .btn-permisos:hover { background:#0277bd; color:#fff; }
        .btn-toggle    { background:#fce4ec; color:#c62828; }
        .btn-toggle:hover      { background:#c62828; color:#fff; }
        .btn-toggle.activar    { background:#e8f5e9; color:#2e7d32; }
        .btn-toggle.activar:hover { background:#2e7d32; color:#fff; }

        /* Barra de herramientas */
        .usr-toolbar {
            display:flex; justify-content:space-between; align-items:center;
            flex-wrap:wrap; gap:12px; margin-bottom:16px;
        }
        .usr-search {
            border:1.5px solid #e2e8f0; border-radius:10px; padding:7px 14px;
            font-size:.83rem; outline:none; width:220px; transition:.2s;
        }
        .usr-search:focus { border-color:var(--accent); }

        /* Show registros */
        .usr-show { font-size:.82rem; color:var(--text-sub); display:flex; align-items:center; gap:8px; }
        .usr-show select { border:1.5px solid #e2e8f0; border-radius:8px; padding:4px 8px; font-size:.82rem; }

        /* Paginación */
        .usr-pagination { display:flex; justify-content:flex-end; align-items:center; gap:6px; margin-top:14px; }
        .usr-page-btn {
            min-width:32px; height:32px; border-radius:8px; border:1.5px solid #e2e8f0;
            background:#fff; color:var(--text-sub); font-size:.8rem; font-weight:600;
            cursor:pointer; display:inline-flex; align-items:center; justify-content:center;
            transition:.15s; padding:0 10px;
        }
        .usr-page-btn:hover  { border-color:var(--accent); color:var(--accent); }
        .usr-page-btn.active { background:var(--accent); color:#fff; border-color:var(--accent); }
        .usr-page-btn:disabled { opacity:.4; cursor:default; }

        /* Loading */
        .usr-loading { text-align:center; padding:40px; color:var(--text-sub); font-size:.85rem; }

        /* Modal */
        .modal-header-usr {
            background:linear-gradient(135deg,#1e1b4b,#4338ca);
            border:none; padding:16px 20px;
        }
    </style>
</head>
<body>

<?= $this->include('partials/sidebar') ?>

<div class="main">
    <div class="topbar">
        <div>
            <div class="topbar-title">
                <i class="bi bi-people-fill me-2" style="color:var(--accent);"></i>Gestión de Usuarios
            </div>
            <div class="topbar-sub">Administración › Usuarios del sistema</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="date-badge">
                <i class="bi bi-calendar3 me-1"></i><span id="fechaHoy"></span>
            </span>
            <div class="user-badge">
                <div class="ub-avatar" id="topbarAvatar">--</div>
                <div>
                    <div class="ub-name" id="topbarNombre">Cargando...</div>
                    <div class="ub-role">Administrador</div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="content-area" style="padding:28px;">

            <!-- Pestañas de perfil -->
            <div class="usr-tabs">
                <button class="usr-tab active" data-perfil="0" id="tabTodos">
                    <i class="bi bi-grid-fill me-1"></i>Todos
                    <span class="badge-cnt" id="cntTodos">0</span>
                </button>
                <button class="usr-tab" data-perfil="1" id="tabAdmin">
                    <i class="bi bi-shield-fill me-1"></i>Admin
                    <span class="badge-cnt" id="cntAdmin">0</span>
                </button>
                <button class="usr-tab" data-perfil="2" id="tabCliente">
                    <i class="bi bi-person-fill me-1"></i>Clientes
                    <span class="badge-cnt" id="cntCliente">0</span>
                </button>
                <button class="usr-tab" data-perfil="3" id="tabProveedor">
                    <i class="bi bi-truck me-1"></i>Proveedores
                    <span class="badge-cnt" id="cntProveedor">0</span>
                </button>
            </div>

            <!-- Toolbar -->
            <div class="usr-toolbar">
                <div class="usr-show">
                    Mostrar
                    <select id="usrPerPage" onchange="renderTabla()">
                        <option>10</option>
                        <option>25</option>
                        <option>50</option>
                    </select>
                    registros
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <input type="search" class="usr-search" id="usrSearch"
                           placeholder="Buscar usuario..." oninput="renderTabla()">
                    <button class="btn btn-sm px-3 py-2"
                            style="background:var(--accent);color:#fff;border-radius:10px;font-weight:600;font-size:.82rem;border:none;"
                            onclick="abrirModalNuevo()">
                        <i class="bi bi-person-plus-fill me-1"></i>Agregar usuario
                    </button>
                </div>
            </div>

            <!-- Tabla -->
            <div class="usr-table-wrap">
                <table class="table" id="tablaUsuarios">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Nombre completo</th>
                            <th>ID</th>
                            <th>Perfil</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="usrTbody">
                        <tr><td colspan="7" class="usr-loading"><i class="bi bi-arrow-repeat"></i> Cargando...</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="usr-pagination" id="usrPaginacion"></div>

        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     Modal Agregar / Editar Usuario
══════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;">
            <div class="modal-header modal-header-usr">
                <h6 class="modal-title text-white" id="modalUsrTitulo">
                    <i class="bi bi-person-plus-fill me-2"></i>Nuevo usuario
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="usrId">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label fw-600" style="font-size:.8rem;">Nombre *</label>
                        <input type="text" class="form-control" id="usrNombre" placeholder="Nombre">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-600" style="font-size:.8rem;">Apellidos</label>
                        <input type="text" class="form-control" id="usrApellidos" placeholder="Apellidos">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-600" style="font-size:.8rem;">RUT</label>
                        <input type="text" class="form-control" id="usrRut" placeholder="12.345.678-9">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-600" style="font-size:.8rem;">Teléfono</label>
                        <input type="text" class="form-control" id="usrTelefono" placeholder="+56 9 0000 0000">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-600" style="font-size:.8rem;">Email *</label>
                        <input type="email" class="form-control" id="usrEmail" placeholder="correo@ejemplo.cl">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-600" style="font-size:.8rem;">Perfil *</label>
                        <select class="form-select" id="usrPerfil">
                            <option value="1">Admin</option>
                            <option value="2">Cliente</option>
                            <option value="3">Proveedor</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-600" style="font-size:.8rem;">Estado</label>
                        <select class="form-select" id="usrEstado">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                    <div class="col-12" id="wrapClave">
                        <label class="form-label fw-600" style="font-size:.8rem;">
                            Contraseña <span id="claveHint" style="color:var(--text-sub);font-weight:400;">(requerida)</span>
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="usrClave" placeholder="••••••••">
                            <button class="btn btn-outline-secondary" type="button"
                                    onclick="toggleClave()" id="btnToggleClave">
                                <i class="bi bi-eye" id="icoOjo"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div id="usrModalError" class="alert alert-danger mt-3 py-2 d-none" style="font-size:.82rem;border-radius:10px;"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f0f4f9;">
                <button class="btn btn-sm" style="background:#f0f4f9;color:#5a7394;border-radius:8px;" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm px-4" id="btnGuardarUsr"
                        style="background:var(--accent);color:#fff;border-radius:8px;font-weight:600;"
                        onclick="guardarUsuario()">
                    <i class="bi bi-check2 me-1"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     Modal Confirmar Eliminar
══════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;">
            <div class="modal-body p-4 text-center">
                <div style="font-size:2.5rem;margin-bottom:12px;">🗑️</div>
                <h6 style="font-weight:700;margin-bottom:8px;">¿Eliminar usuario?</h6>
                <p style="font-size:.83rem;color:var(--text-sub);" id="eliminarNombre">--</p>
                <p style="font-size:.79rem;color:#dc2626;">Esta acción no se puede deshacer.</p>
                <div class="d-flex gap-2 justify-content-center mt-3">
                    <button class="btn btn-sm" style="background:#f0f4f9;color:#5a7394;border-radius:8px;" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-sm px-4" id="btnConfEliminar"
                            style="background:#dc2626;color:#fff;border-radius:8px;font-weight:600;"
                            onclick="confirmarEliminar()">
                        Sí, eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toastWrapper" style="position:fixed;bottom:24px;right:24px;z-index:9999;"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
window.USR_BASE_URL = "<?= site_url() ?>";
window.ADMIN_SESSION = <?= json_encode($usuario ?? ['nombre' => session()->get('Nombre') ?? 'Admin']) ?>;

/* ─── Estado global ───────────────────────────────────────────── */
let todosLosUsuarios = [];   // Todos de la BD (cache)
let usuariosFiltrados = [];  // Filtrados por pestaña + búsqueda
let paginaActual = 1;
let perfilActivo = 0;        // 0 = todos
let idParaEliminar = null;

/* ─── Init ────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    // Fecha topbar
    const d = new Date();
    const opciones = { weekday:'long', year:'numeric', month:'long', day:'numeric' };
    const el = document.getElementById('fechaHoy');
    if (el) el.textContent = d.toLocaleDateString('es-CL', opciones);

    // Nombre usuario
    const nom = window.ADMIN_SESSION?.nombre || 'Admin';
    const av  = document.getElementById('topbarAvatar');
    const nb  = document.getElementById('topbarNombre');
    if (av) av.textContent = nom.charAt(0).toUpperCase();
    if (nb) nb.textContent  = nom;

    // Sidebar avatar
    const sa = document.getElementById('sidebarAvatar');
    const sn = document.getElementById('sidebarNombre');
    if (sa) sa.textContent = nom.charAt(0).toUpperCase();
    if (sn) sn.textContent  = nom;

    // Pestañas
    document.querySelectorAll('.usr-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.usr-tab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            perfilActivo = parseInt(btn.dataset.perfil);
            paginaActual = 1;
            aplicarFiltros();
        });
    });

    cargarTodos();
});

/* ─── Carga datos ─────────────────────────────────────────────── */
function cargarTodos() {
    fetch(window.USR_BASE_URL + 'usuarios/lista?perfil_id=0')
        .then(r => r.json())
        .then(res => {
            if (!res.ok) return mostrarError('Error cargando usuarios.');
            todosLosUsuarios = res.data || [];
            actualizarContadores();
            aplicarFiltros();
        })
        .catch(() => mostrarError('Error de conexión.'));
}

/* ─── Contadores de pestañas ──────────────────────────────────── */
function actualizarContadores() {
    document.getElementById('cntTodos').textContent    = todosLosUsuarios.length;
    document.getElementById('cntAdmin').textContent    = todosLosUsuarios.filter(u => u.perfil_id == 1).length;
    document.getElementById('cntCliente').textContent  = todosLosUsuarios.filter(u => u.perfil_id == 2).length;
    document.getElementById('cntProveedor').textContent= todosLosUsuarios.filter(u => u.perfil_id == 3).length;
}

/* ─── Filtrar y renderizar ────────────────────────────────────── */
function aplicarFiltros() {
    const q = (document.getElementById('usrSearch')?.value || '').toLowerCase().trim();
    usuariosFiltrados = todosLosUsuarios.filter(u => {
        const perfilOk = perfilActivo === 0 || u.perfil_id == perfilActivo;
        const buscaOk  = !q ||
            (u.nombre    || '').toLowerCase().includes(q) ||
            (u.apellidos || '').toLowerCase().includes(q) ||
            (u.email     || '').toLowerCase().includes(q) ||
            (u.rut       || '').toLowerCase().includes(q);
        return perfilOk && buscaOk;
    });
    renderTabla();
}

function renderTabla() {
    const perPage = parseInt(document.getElementById('usrPerPage')?.value || 10);
    const q = (document.getElementById('usrSearch')?.value || '').toLowerCase().trim();

    // Re-filtrar con búsqueda
    usuariosFiltrados = todosLosUsuarios.filter(u => {
        const perfilOk = perfilActivo === 0 || u.perfil_id == perfilActivo;
        const buscaOk  = !q ||
            (u.nombre    || '').toLowerCase().includes(q) ||
            (u.apellidos || '').toLowerCase().includes(q) ||
            (u.email     || '').toLowerCase().includes(q) ||
            (u.rut       || '').toLowerCase().includes(q);
        return perfilOk && buscaOk;
    });

    const total  = usuariosFiltrados.length;
    const paginas = Math.ceil(total / perPage) || 1;
    if (paginaActual > paginas) paginaActual = 1;

    const inicio = (paginaActual - 1) * perPage;
    const slice  = usuariosFiltrados.slice(inicio, inicio + perPage);

    const tbody = document.getElementById('usrTbody');
    if (!slice.length) {
        tbody.innerHTML = `<tr><td colspan="7" class="usr-loading">
            <i class="bi bi-inbox" style="font-size:1.4rem;"></i><br>Sin usuarios para mostrar.</td></tr>`;
    } else {
        tbody.innerHTML = slice.map(u => filaUsuario(u)).join('');
    }

    renderPaginacion(paginas);
}

/* ─── Renderizar fila ─────────────────────────────────────────── */
function filaUsuario(u) {
    const iniciales = ((u.nombre || '?')[0] + (u.apellidos ? u.apellidos[0] : '')).toUpperCase();
    const perfil = badgePerfil(u.perfil_id, u.perfil_nombre);
    const estado = u.estado == 1
        ? '<span class="badge-activo"><i class="bi bi-check-circle-fill me-1"></i>Activo</span>'
        : '<span class="badge-inactivo"><i class="bi bi-x-circle-fill me-1"></i>Inactivo</span>';
    const toggleIcon  = u.estado == 1 ? 'bi-slash-circle' : 'bi-check-circle';
    const toggleClass = u.estado == 1 ? 'btn-toggle' : 'btn-toggle activar';
    const toggleTitle = u.estado == 1 ? 'Desactivar' : 'Activar';

    return `
    <tr>
        <td>
            <div class="d-flex align-items-center gap-2">
                <div class="usr-avatar">${iniciales}</div>
                <div>
                    <div class="usr-email">${esc(u.email)}</div>
                </div>
            </div>
        </td>
        <td>
            <div class="usr-name">${esc(u.nombre)} ${esc(u.apellidos || '')}</div>
            ${u.rut ? `<div class="usr-email">${esc(u.rut)}</div>` : ''}
        </td>
        <td style="color:var(--text-sub);font-weight:600;">${u.id}</td>
        <td>${perfil}</td>
        <td style="color:var(--text-sub);">${esc(u.telefono || '—')}</td>
        <td>${estado}</td>
        <td>
            <div class="d-flex gap-1">
                <button class="btn-accion btn-editar" title="Editar" onclick="abrirModalEditar(${u.id})">
                    <i class="bi bi-pencil-fill"></i>
                </button>
                <button class="btn-accion btn-permisos" title="Permisos" onclick="verPermisos(${u.id})">
                    <i class="bi bi-key-fill"></i>
                </button>
                <button class="btn-accion ${toggleClass}" title="${toggleTitle}" onclick="toggleEstado(${u.id})">
                    <i class="bi ${toggleIcon}"></i>
                </button>
                <button class="btn-accion" title="Eliminar"
                        style="background:#fce4ec;color:#c62828;"
                        onmouseover="this.style.background='#c62828';this.style.color='#fff'"
                        onmouseout="this.style.background='#fce4ec';this.style.color='#c62828'"
                        onclick="abrirModalEliminar(${u.id}, '${esc(u.nombre)} ${esc(u.apellidos || '')}')">
                    <i class="bi bi-trash-fill"></i>
                </button>
            </div>
        </td>
    </tr>`;
}

function badgePerfil(id, nombre) {
    const map = { 1: 'badge-admin', 2: 'badge-cliente', 3: 'badge-proveedor' };
    const cls = map[id] || '';
    return `<span class="badge-perfil ${cls}">${nombre || 'Sin perfil'}</span>`;
}

function esc(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

/* ─── Paginación ──────────────────────────────────────────────── */
function renderPaginacion(paginas) {
    const wrap = document.getElementById('usrPaginacion');
    if (paginas <= 1) { wrap.innerHTML = ''; return; }

    let html = `<button class="usr-page-btn" ${paginaActual===1?'disabled':''} onclick="irPagina(${paginaActual-1})">
                    <i class="bi bi-chevron-left"></i> Anterior</button>`;
    for (let i = 1; i <= paginas; i++) {
        html += `<button class="usr-page-btn ${i===paginaActual?'active':''}" onclick="irPagina(${i})">${i}</button>`;
    }
    html += `<button class="usr-page-btn" ${paginaActual===paginas?'disabled':''} onclick="irPagina(${paginaActual+1})">
                 Siguiente <i class="bi bi-chevron-right"></i></button>`;
    wrap.innerHTML = html;
}

function irPagina(p) { paginaActual = p; renderTabla(); }

/* ─── Modal Nuevo ─────────────────────────────────────────────── */
function abrirModalNuevo() {
    document.getElementById('usrId').value        = '';
    document.getElementById('usrNombre').value    = '';
    document.getElementById('usrApellidos').value = '';
    document.getElementById('usrRut').value       = '';
    document.getElementById('usrEmail').value     = '';
    document.getElementById('usrTelefono').value  = '';
    document.getElementById('usrPerfil').value    = '2';
    document.getElementById('usrEstado').value    = '1';
    document.getElementById('usrClave').value     = '';
    document.getElementById('claveHint').textContent  = '(requerida)';
    document.getElementById('modalUsrTitulo').innerHTML = '<i class="bi bi-person-plus-fill me-2"></i>Nuevo usuario';
    limpiarError();
    new bootstrap.Modal(document.getElementById('modalUsuario')).show();
}

/* ─── Modal Editar ────────────────────────────────────────────── */
function abrirModalEditar(id) {
    const u = todosLosUsuarios.find(x => x.id == id);
    if (!u) return;
    document.getElementById('usrId').value        = u.id;
    document.getElementById('usrNombre').value    = u.nombre || '';
    document.getElementById('usrApellidos').value = u.apellidos || '';
    document.getElementById('usrRut').value       = u.rut || '';
    document.getElementById('usrEmail').value     = u.email || '';
    document.getElementById('usrTelefono').value  = u.telefono || '';
    document.getElementById('usrPerfil').value    = u.perfil_id;
    document.getElementById('usrEstado').value    = u.estado;
    document.getElementById('usrClave').value     = '';
    document.getElementById('claveHint').textContent = '(dejar vacío para no cambiar)';
    document.getElementById('modalUsrTitulo').innerHTML = '<i class="bi bi-pencil-fill me-2"></i>Editar usuario';
    limpiarError();
    new bootstrap.Modal(document.getElementById('modalUsuario')).show();
}

/* ─── Guardar ─────────────────────────────────────────────────── */
function guardarUsuario() {
    const id       = parseInt(document.getElementById('usrId').value || '0');
    const nombre   = document.getElementById('usrNombre').value.trim();
    const apellidos= document.getElementById('usrApellidos').value.trim();
    const rut      = document.getElementById('usrRut').value.trim();
    const email    = document.getElementById('usrEmail').value.trim();
    const telefono = document.getElementById('usrTelefono').value.trim();
    const perfil   = parseInt(document.getElementById('usrPerfil').value);
    const estado   = parseInt(document.getElementById('usrEstado').value);
    const clave    = document.getElementById('usrClave').value.trim();

    if (!nombre || !email || !perfil) {
        return mostrarErrorModal('Nombre, email y perfil son requeridos.');
    }

    const body = { id, nombre, apellidos, rut, email, telefono, perfil_id: perfil, estado, clave };

    const btn = document.getElementById('btnGuardarUsr');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Guardando...';

    fetch(window.USR_BASE_URL + 'usuarios/guardar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Guardar';
        if (!res.ok) return mostrarErrorModal(res.msg);
        bootstrap.Modal.getInstance(document.getElementById('modalUsuario'))?.hide();
        toast(res.msg, 'success');
        // Actualizar cache local
        if (id > 0) {
            const idx = todosLosUsuarios.findIndex(x => x.id == id);
            if (idx >= 0) todosLosUsuarios[idx] = res.usuario;
        } else {
            todosLosUsuarios.push(res.usuario);
        }
        actualizarContadores();
        renderTabla();
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Guardar';
        mostrarErrorModal('Error de conexión.');
    });
}

/* ─── Toggle estado ───────────────────────────────────────────── */
function toggleEstado(id) {
    fetch(window.USR_BASE_URL + 'usuarios/toggle-estado/' + id, { method: 'PATCH' })
        .then(r => r.json())
        .then(res => {
            if (!res.ok) return toast(res.msg, 'error');
            const u = todosLosUsuarios.find(x => x.id == id);
            if (u) u.estado = res.estado;
            actualizarContadores();
            renderTabla();
            toast(res.msg, 'success');
        });
}

/* ─── Eliminar ────────────────────────────────────────────────── */
function abrirModalEliminar(id, nombre) {
    idParaEliminar = id;
    document.getElementById('eliminarNombre').textContent = nombre;
    new bootstrap.Modal(document.getElementById('modalEliminar')).show();
}

function confirmarEliminar() {
    if (!idParaEliminar) return;
    fetch(window.USR_BASE_URL + 'usuarios/eliminar/' + idParaEliminar, { method: 'DELETE' })
        .then(r => r.json())
        .then(res => {
            bootstrap.Modal.getInstance(document.getElementById('modalEliminar'))?.hide();
            if (!res.ok) return toast(res.msg, 'error');
            todosLosUsuarios = todosLosUsuarios.filter(u => u.id != idParaEliminar);
            actualizarContadores();
            renderTabla();
            toast(res.msg, 'success');
            idParaEliminar = null;
        });
}

/* ─── Permisos (2ª Etapa) ─────────────────────────────────────── */
function verPermisos(id) {
    toast('Módulo de permisos — próximamente disponible.', 'info');
}

/* ─── Helpers de UI ───────────────────────────────────────────── */
function toggleClave() {
    const inp = document.getElementById('usrClave');
    const ico = document.getElementById('icoOjo');
    if (inp.type === 'password') { inp.type = 'text'; ico.className = 'bi bi-eye-slash'; }
    else { inp.type = 'password'; ico.className = 'bi bi-eye'; }
}
function mostrarErrorModal(msg) {
    const el = document.getElementById('usrModalError');
    el.textContent = msg; el.classList.remove('d-none');
}
function limpiarError() {
    const el = document.getElementById('usrModalError');
    el.textContent = ''; el.classList.add('d-none');
}
function mostrarError(msg) {
    document.getElementById('usrTbody').innerHTML =
        `<tr><td colspan="7" class="usr-loading text-danger">${msg}</td></tr>`;
}
function toast(msg, tipo = 'success') {
    const id = 'toast_' + Date.now();
    const colores = { success:'#22c55e', error:'#ef4444', info:'#6366f1' };
    const html = `<div id="${id}" style="background:#fff;border-left:4px solid ${colores[tipo]||'#22c55e'};
        border-radius:10px;padding:12px 16px;margin-top:8px;box-shadow:0 4px 16px rgba(0,0,0,.12);
        font-size:.83rem;color:#1e293b;min-width:240px;max-width:320px;">
        ${msg}</div>`;
    document.getElementById('toastWrapper').insertAdjacentHTML('beforeend', html);
    setTimeout(() => document.getElementById(id)?.remove(), 3500);
}
</script>
</body>
</html>
