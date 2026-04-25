<?php $activePage = 'productos'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos – Bodega | Portal Admin</title>
    <meta name="description" content="Listado de productos en bodega con edición rápida y gestión de stock.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('public/assets/css/admin.css') ?>">
    <style>
        /* ── Buscador y filtros ── */
        .toolbar {
            display: flex; align-items: center; gap: 10px;
            flex-wrap: wrap; margin-bottom: 18px;
        }
        .search-box {
            position: relative; flex: 1; min-width: 200px; max-width: 340px;
        }
        .search-box input {
            width: 100%; padding: 8px 14px 8px 36px;
            border: 1.5px solid #e2e8f0; border-radius: 10px;
            font-size: .84rem; font-family: 'Inter', sans-serif;
            background: #fff; color: #1a2940;
            transition: border-color .2s;
        }
        .search-box input:focus { outline: none; border-color: #7c3aed; }
        .search-box .srch-icon {
            position: absolute; left: 11px; top: 50%;
            transform: translateY(-50%); color: #94a3b8; font-size: .9rem;
        }
        .filter-select {
            padding: 8px 12px; border: 1.5px solid #e2e8f0; border-radius: 10px;
            font-size: .83rem; font-family: 'Inter', sans-serif;
            background: #fff; color: #374151; cursor: pointer;
            transition: border-color .2s;
        }
        .filter-select:focus { outline: none; border-color: #7c3aed; }

        /* ── Tabla ── */
        .table-card {
            background: #fff; border-radius: 16px;
            border: 1px solid #e8edf5;
            box-shadow: 0 2px 12px rgba(60,80,120,.07);
            overflow: hidden;
        }
        .prod-table { width: 100%; border-collapse: collapse; font-size: .83rem; }
        .prod-table thead th {
            background: #f8f5ff; color: #5b21b6;
            font-weight: 700; font-size: .72rem;
            text-transform: uppercase; letter-spacing: .06em;
            padding: 12px 14px; border-bottom: 2px solid #ede9fe;
            white-space: nowrap;
        }
        .prod-table tbody td {
            padding: 11px 14px; border-bottom: 1px solid #f3f4f6;
            color: #374151; vertical-align: middle;
        }
        .prod-table tbody tr:last-child td { border-bottom: none; }
        .prod-table tbody tr:hover { background: #faf8ff; }

        /* ── Celdas especiales ── */
        .sku-badge {
            font-size: .72rem; font-weight: 700;
            background: #f5f0ff; color: #6d28d9;
            border: 1px solid #ddd6fe; border-radius: 6px;
            padding: 2px 8px; white-space: nowrap;
        }
        .nombre-cell { font-weight: 600; color: #1a2940; max-width: 200px; }
        .precio-cell { font-weight: 600; color: #059669; white-space: nowrap; }
        .precio-imp  { font-weight: 500; color: #374151; white-space: nowrap; }
        .stock-cell  { font-weight: 700; }
        .stock-ok    { color: #059669; }
        .stock-low   { color: #d97706; }
        .stock-zero  { color: #dc2626; }
        .cat-chip {
            font-size: .71rem; font-weight: 600;
            border-radius: 20px; padding: 2px 10px;
            white-space: nowrap;
        }
        .cat-Congelados   { background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; }
        .cat-Preelaborados{ background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }
        .cat-Aceites      { background:#fefce8; color:#ca8a04; border:1px solid #fde68a; }
        .cat-default      { background:#f5f0ff; color:#6d28d9; border:1px solid #ddd6fe; }

        /* ── Lista badges ── */
        .lista-badge {
            display: inline-block;
            font-size: .66rem; font-weight: 600;
            border-radius: 20px; padding: 1px 8px;
            margin: 1px; white-space: nowrap;
            background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0;
        }
        .listas-cell { max-width: 220px; line-height: 1.6; }

        /* ── Botones de acción ── */
        .btn-accion {
            width: 30px; height: 30px; border-radius: 8px;
            border: 1.5px solid; display: inline-flex;
            align-items: center; justify-content: center;
            font-size: .82rem; cursor: pointer;
            transition: all .18s; background: transparent;
        }
        .btn-editar  { border-color: #c4b5fd; color: #7c3aed; }
        .btn-editar:hover  { background: #7c3aed; color: #fff; border-color: #7c3aed; }
        .btn-ver     { border-color: #93c5fd; color: #2563eb; }
        .btn-ver:hover     { background: #2563eb; color: #fff; border-color: #2563eb; }
        .btn-borrar  { border-color: #fca5a5; color: #dc2626; }
        .btn-borrar:hover  { background: #dc2626; color: #fff; border-color: #dc2626; }
        .acciones-cell { white-space: nowrap; display: flex; gap: 5px; align-items: center; }

        /* ── Empty state ── */
        .empty-row td {
            text-align: center; padding: 48px 20px;
            color: #94a3b8; font-size: .87rem;
        }

        /* ── Stats top ── */
        .stat-pill {
            background: #f5f0ff; color: #6d28d9;
            border: 1px solid #ddd6fe; border-radius: 20px;
            padding: 4px 14px; font-size: .78rem; font-weight: 600;
            display: inline-flex; align-items: center; gap: 5px;
        }

        /* ── Modales ── */
        .modal-header-prod {
            background: linear-gradient(135deg, #4c1d95, #7c3aed);
            color: #fff; border: none; padding: 16px 22px;
        }
        .modal-header-view {
            background: linear-gradient(135deg, #1e3a5f, #2563eb);
            color: #fff; border: none; padding: 16px 22px;
        }
        .modal-header-del {
            background: linear-gradient(135deg, #7f1d1d, #dc2626);
            color: #fff; border: none; padding: 16px 22px;
        }
        .form-label-sm { font-size: .78rem; font-weight: 600; color: #5b21b6; margin-bottom: 4px; }
        .form-control-sm-custom {
            padding: 8px 12px; border: 1.5px solid #ddd6fe;
            border-radius: 9px; font-size: .84rem;
            font-family: 'Inter', sans-serif; width: 100%;
            transition: border-color .2s;
        }
        .form-control-sm-custom:focus { outline: none; border-color: #7c3aed; }
        .detail-row {
            display: flex; justify-content: space-between;
            padding: 7px 0; border-bottom: 1px solid #f0f4f9;
            font-size: .82rem;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #64748b; font-weight: 500; }
        .detail-value { font-weight: 600; color: #1a2940; text-align: right; }
        .precio-list-row {
            display: flex; justify-content: space-between;
            padding: 6px 10px; border-radius: 8px; font-size: .80rem;
            margin-bottom: 4px;
        }
        .precio-list-row:nth-child(odd) { background: #f8f5ff; }
        .precio-list-row:nth-child(even) { background: #f5f0ff; }

        /* ── Paginación ── */
        .pag-btn {
            padding: 5px 12px; border-radius: 8px;
            border: 1.5px solid #e2e8f0; background: #fff;
            color: #374151; font-size: .80rem; cursor: pointer;
            transition: all .18s;
        }
        .pag-btn:hover:not(:disabled) { border-color: #7c3aed; color: #7c3aed; }
        .pag-btn:disabled { opacity: .4; cursor: not-allowed; }
        .pag-info { font-size: .78rem; color: #94a3b8; }
    </style>
</head>
<body>

<?= $this->include('partials/sidebar') ?>

<div class="main">
    <!-- Topbar -->
    <div class="topbar">
        <div class="d-flex align-items-center gap-2">
            <button class="btn-menu-toggle" onclick="abrirSidebar()" aria-label="Abrir menú">
                <i class="bi bi-list"></i>
            </button>
            <div>
                <div class="topbar-title">
                    <i class="bi bi-box-seam-fill me-2" style="color:var(--accent);"></i>Productos
                </div>
                <div class="topbar-sub">Portal Admin &rsaquo; Bodega &rsaquo; Productos</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="date-badge"><i class="bi bi-calendar3 me-1"></i><span id="fechaHoy"></span></span>
            <div class="user-badge" onclick="abrirModalAdmin()" title="Ver información del administrador">
                <div class="ub-avatar" id="topbarAvatar">--</div>
                <div>
                    <div class="ub-name" id="topbarNombre">Cargando...</div>
                    <div class="ub-role" id="topbarRol">Administrador</div>
                </div>
                <i class="bi bi-info-circle ms-1" style="color:var(--text-sub);font-size:.80rem;"></i>
            </div>
        </div>
    </div>

    <div class="page-body">

        <!-- Toolbar -->
        <div class="toolbar">
            <div class="search-box">
                <i class="bi bi-search srch-icon"></i>
                <input type="text" id="buscador" placeholder="Buscar por nombre, SKU, marca…"
                       oninput="filtrar()">
            </div>
            <select class="filter-select" id="filtroCat" onchange="filtrar()">
                <option value="">Todas las categorías</option>
            </select>
            <select class="filter-select" id="filtroLista" onchange="filtrar()">
                <option value="Precios base">Precios base</option>
                <option value="Horeca">Horeca</option>
                <option value="Mayorista">Mayorista</option>
                <option value="Distribucion detallista">Dist. Detallista</option>
                <option value="Distribucion Mayorista">Dist. Mayorista</option>
                <option value="Lista especial 1">Lista especial 1</option>
                <option value="lista especial 2">Lista especial 2</option>
            </select>
            <div style="margin-left:auto;display:flex;align-items:center;gap:8px;">
                <span class="stat-pill" id="pillTotal"><i class="bi bi-box-seam"></i> -- productos</span>
                <span class="stat-pill" style="background:#d1fae5;color:#059669;border-color:#6ee7b7;" id="pillStock">
                    <i class="bi bi-graph-up"></i> -- en stock
                </span>
            </div>
        </div>

        <!-- Tabla -->
        <div class="table-card">
            <div style="overflow-x:auto;">
                <table class="prod-table" id="tablaProductos">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>SKU</th>
                            <th>Lista de precio</th>
                            <th style="text-align:center;">Cant. disponible</th>
                            <th style="text-align:right;">Precio neto</th>
                            <th style="text-align:right;">Precio c/Impto</th>
                            <th style="text-align:center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyProductos">
                        <tr class="empty-row">
                            <td colspan="7">
                                <i class="bi bi-arrow-repeat me-2"></i>Cargando productos…
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Paginación -->
            <div class="d-flex align-items-center justify-content-between px-3 py-2" style="border-top:1px solid #f0f4f9;">
                <div class="pag-info" id="pagInfo">-- a -- de --</div>
                <div class="d-flex gap-2">
                    <button class="pag-btn" id="btnPrev" onclick="paginaAnterior()" disabled>
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button class="pag-btn" id="btnNext" onclick="paginaSiguiente()" disabled>
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>

    </div><!-- /page-body -->
</div><!-- /main -->


<!-- ══════════════════════════════════════════════════════════════
     MODAL EDITAR (solo Nombre y Cantidad)
════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content" style="border-radius:18px;overflow:hidden;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15);">
            <div class="modal-header-prod">
                <h6 class="modal-title d-flex align-items-center gap-2">
                    <i class="bi bi-pencil-fill"></i> Editar producto
                    <span style="font-size:.75rem;opacity:.7;font-weight:400;" id="editSku"></span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <div class="form-label-sm">Nombre del producto</div>
                    <input type="text" id="editNombre" class="form-control-sm-custom" placeholder="Nombre del producto">
                </div>
                <div class="mb-1">
                    <div class="form-label-sm">Cantidad disponible (Bodega Independencia)</div>
                    <input type="number" id="editCantidad" class="form-control-sm-custom"
                           placeholder="0" min="0" step="0.001">
                </div>
                <div id="editError" style="display:none;margin-top:10px;" class="alert alert-danger py-2 px-3" style="font-size:.80rem;border-radius:8px;"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f0f4f9;gap:8px;">
                <button class="btn btn-sm" style="background:#f0f4f9;color:#5a7394;border-radius:9px;padding:7px 18px;" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm" id="btnGuardar"
                        style="background:linear-gradient(135deg,#4c1d95,#7c3aed);color:#fff;border-radius:9px;padding:7px 20px;font-weight:600;border:none;"
                        onclick="guardarEdicion()">
                    <i class="bi bi-check-lg me-1"></i>Guardar cambios
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════════════
     MODAL VISUALIZAR (detalle completo + listas de precio)
════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalVer" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:560px;">
        <div class="modal-content" style="border-radius:18px;overflow:hidden;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15);">
            <div class="modal-header-view">
                <h6 class="modal-title d-flex align-items-center gap-2">
                    <i class="bi bi-eye-fill"></i> Detalle del producto
                    <span style="font-size:.75rem;opacity:.75;font-weight:400;" id="verSku"></span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Cargando -->
                <div id="verLoader" class="d-flex align-items-center justify-content-center py-5">
                    <i class="bi bi-arrow-repeat me-2" style="color:#94a3b8;"></i>
                    <span style="color:#94a3b8;font-size:.87rem;">Cargando…</span>
                </div>
                <!-- Contenido -->
                <div id="verContenido" style="display:none;">
                    <!-- Encabezado del producto -->
                    <div style="padding:18px 24px 14px;background:#f8f5ff;border-bottom:1px solid #ede9fe;">
                        <div style="font-size:1.05rem;font-weight:700;color:#1a2940;" id="verNombre">--</div>
                        <div style="font-size:.78rem;color:#7c3aed;font-weight:600;margin-top:2px;" id="verCat">--</div>
                    </div>
                    <div style="padding:18px 24px;">
                        <!-- Datos principales -->
                        <div style="font-size:.70rem;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;font-weight:700;margin-bottom:8px;">Información general</div>
                        <div id="verDatos"></div>

                        <!-- Stock -->
                        <div style="font-size:.70rem;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;font-weight:700;margin:16px 0 8px;">Stock</div>
                        <div id="verStock"></div>

                        <!-- Listas de precio -->
                        <div style="font-size:.70rem;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;font-weight:700;margin:16px 0 8px;">
                            Listas de precio
                        </div>
                        <div id="verListas">
                            <span style="color:#94a3b8;font-size:.82rem;">Sin listas de precio.</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f0f4f9;">
                <button class="btn btn-sm" style="background:#f0f4f9;color:#5a7394;border-radius:9px;padding:7px 18px;" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════════════
     MODAL ELIMINAR — Confirmación
════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content" style="border-radius:18px;overflow:hidden;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15);">
            <div class="modal-header-del">
                <h6 class="modal-title d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill"></i> Eliminar producto
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
            </div>
            <div class="modal-body px-4 py-4 text-center">
                <div style="font-size:2.5rem;color:#dc2626;margin-bottom:12px;">
                    <i class="bi bi-trash3-fill"></i>
                </div>
                <p style="font-size:.87rem;color:#374151;margin-bottom:4px;">
                    ¿Está seguro que desea eliminar el producto?
                </p>
                <div style="font-weight:700;font-size:1rem;color:#1a2940;" id="delNombre">--</div>
                <div style="font-size:.80rem;color:#6d28d9;margin-top:2px;" id="delSku">--</div>
                <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:10px 14px;margin-top:14px;font-size:.79rem;color:#dc2626;text-align:left;">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>
                    Esta acción también eliminará todas las listas de precio asociadas y <strong>no se puede deshacer</strong>.
                </div>
                <div id="delError" style="display:none;margin-top:10px;" class="alert alert-danger py-2 px-3 text-start" style="font-size:.80rem;border-radius:8px;"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f0f4f9;gap:8px;justify-content:center;">
                <button class="btn btn-sm" style="background:#f0f4f9;color:#5a7394;border-radius:9px;padding:8px 22px;" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm" id="btnConfirmarEliminar"
                        style="background:linear-gradient(135deg,#7f1d1d,#dc2626);color:#fff;border-radius:9px;padding:8px 22px;font-weight:600;border:none;"
                        onclick="confirmarEliminar()">
                    <i class="bi bi-trash3 me-1"></i>Sí, eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Info Admin -->
<div class="modal fade" id="modalAdminInfo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;">
            <div class="modal-header py-3" style="background:linear-gradient(135deg,#4c1d95,#7c3aed);border:none;">
                <h6 class="modal-title text-white"><i class="bi bi-person-badge-fill me-2"></i>Información del Administrador</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
            </div>
            <div class="modal-body p-4">
                <div class="admin-avatar-lg" id="modalAdminAvatar">--</div>
                <div style="text-align:center;margin-bottom:20px;">
                    <div style="font-size:1.05rem;font-weight:700;" id="modalAdminNombre">--</div>
                    <div style="font-size:.76rem;color:var(--text-sub);" id="modalAdminPerfil">--</div>
                </div>
                <div id="modalAdminRows"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f0f4f9;">
                <button class="btn btn-sm" style="background:#f0f4f9;color:#5a7394;border-radius:8px;" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE = "<?= rtrim(site_url(), '/') . '/' ?>";
window.ADMIN_SESSION = <?= json_encode($usuario ?? [
    'nombre'       => session()->get('Nombre') ?? 'Administrador',
    'apellidos'    => '', 'email' => '', 'rut' => '',
    'telefono'     => '', 'estado' => 1,
    'ultimo_acceso'=> null, 'perfil' => 'Administrador',
]) ?>;

// ── Estado global ──────────────────────────────────────────────
let _todos     = [];   // todos los productos de la API
let _filtrados = [];   // productos tras búsqueda/filtro
let _pagina    = 1;
const POR_PAG  = 15;
let _skuEditar  = null;
let _skuEliminar = null;

// ── Formateo ─────────────────────────────────────────────────
const fmt = v => v != null ? Number(v).toLocaleString('es-CL', { style:'currency', currency:'CLP', maximumFractionDigits:0 }) : '--';
const fmtN = v => v != null ? Number(v).toLocaleString('es-CL') : '0';

// ── Categoría → clase CSS ─────────────────────────────────────
function catClass(cat) {
    const c = (cat || '').trim();
    if (c === 'Congelados')    return 'cat-Congelados';
    if (c === 'Preelaborados') return 'cat-Preelaborados';
    if (c === 'Aceites')       return 'cat-Aceites';
    return 'cat-default';
}

// ── Stock clase ───────────────────────────────────────────────
function stockClass(v) {
    const n = parseFloat(v) || 0;
    if (n <= 0)   return 'stock-zero';
    if (n < 50)   return 'stock-low';
    return 'stock-ok';
}

// ── Cargar productos desde la BD ──────────────────────────────
async function cargarProductos() {
    try {
        const resp = await fetch(BASE + 'bodega/stats');
        const json = await resp.json();
        if (!json.success) throw new Error('stats fail');
    } catch(e) {}

    // Carga real: reutilizo el endpoint de stats para conteos,
    // pero necesito la lista completa → uso un endpoint propio
    try {
        const resp = await fetch(BASE + 'bodega/lista-productos');
        const json = await resp.json();
        if (json.success) {
            _todos = json.productos;
            poblarFiltros();
            filtrar();
            actualizarPills();
        }
    } catch(e) {
        document.getElementById('tbodyProductos').innerHTML =
            `<tr class="empty-row"><td colspan="7"><i class="bi bi-x-circle me-2" style="color:#dc2626;"></i>Error al cargar productos.</td></tr>`;
    }
}

// ── Poblar selects de filtro ──────────────────────────────────
function poblarFiltros() {
    const catEl = document.getElementById('filtroCat');
    const cats  = [...new Set(_todos.map(p => p.categoria).filter(Boolean))].sort();
    cats.forEach(c => catEl.innerHTML += `<option value="${c}">${c}</option>`);
    // El select de lista ya tiene sus opciones fijas en el HTML
}

// ── Filtrar y renderizar ──────────────────────────────────────
function filtrar() {
    const q     = (document.getElementById('buscador').value || '').toLowerCase();
    const cat   = document.getElementById('filtroCat').value;
    const lista = document.getElementById('filtroLista').value;

    _filtrados = _todos.filter(p => {
        const matchQ = !q ||
            (p.nombre  || '').toLowerCase().includes(q) ||
            (p.sku     || '').toLowerCase().includes(q) ||
            (p.marca   || '').toLowerCase().includes(q) ||
            (p.modelo  || '').toLowerCase().includes(q);
        const matchCat   = !cat   || p.categoria === cat;
        const matchLista = !lista || (p.listas || []).includes(lista);
        return matchQ && matchCat && matchLista;
    });

    _pagina = 1;
    renderTabla();
}

function renderTabla() {
    const start = (_pagina - 1) * POR_PAG;
    const end   = start + POR_PAG;
    const page  = _filtrados.slice(start, end);
    const tbody = document.getElementById('tbodyProductos');

    if (!page.length) {
        tbody.innerHTML = `<tr class="empty-row"><td colspan="7"><i class="bi bi-inbox me-2"></i>Sin productos para mostrar.</td></tr>`;
        actualizarPaginacion();
        return;
    }

    tbody.innerHTML = page.map(p => {
        const stock        = parseFloat(p.stock_bodega_ppral) || 0;
        const listaFiltro  = document.getElementById('filtroLista').value;

        // Determinar qué lista usar para mostrar el precio
        const precios      = p.precios_lista || {};
        const listaActiva  = precios[listaFiltro]
                             ? listaFiltro
                             : (precios['Precios base'] ? 'Precios base' : Object.keys(precios)[0] || null);
        const precioNeto   = listaActiva ? fmt(precios[listaActiva].precio_neto)  : '--';
        const precioTotal  = listaActiva ? fmt(precios[listaActiva].precio_total) : '--';

        // Badges de lista: solo la filtrada activa, o todas si no hay filtro
        const listasAMostrar = listaFiltro
            ? (p.listas || []).filter(l => l === listaFiltro)
            : (p.listas || []);
        const listas = listasAMostrar.map(l => `<span class="lista-badge">${l}</span>`).join('');
        return `
        <tr>
            <td class="nombre-cell" title="${p.nombre || ''}">${p.nombre || '--'}</td>
            <td><span class="sku-badge">${p.sku}</span></td>
            <td class="listas-cell">${listas || '<span style="color:#94a3b8;font-size:.76rem;">Sin lista</span>'}</td>
            <td style="text-align:center;">
                <span class="stock-cell ${stockClass(stock)}">${fmtN(stock)}</span>
                <span style="font-size:.70rem;color:#94a3b8;margin-left:2px;">${p.unidad || ''}</span>
            </td>
            <td class="precio-cell" style="text-align:right;">${precioNeto}</td>
            <td class="precio-imp"  style="text-align:right;">${precioTotal}</td>
            <td>
                <div class="acciones-cell">
                    <button class="btn-accion btn-editar" title="Editar nombre y cantidad"
                            onclick="abrirEditar('${p.sku}','${(p.nombre||'').replace(/'/g,"\\'")}',${stock})">
                        <i class="bi bi-pencil-fill"></i>
                    </button>
                    <button class="btn-accion btn-ver" title="Ver detalle"
                            onclick="abrirVer('${p.sku}')">
                        <i class="bi bi-eye-fill"></i>
                    </button>
                    <button class="btn-accion btn-borrar" title="Eliminar producto"
                            onclick="abrirEliminar('${p.sku}','${(p.nombre||'').replace(/'/g,"\\'")}')">
                        <i class="bi bi-trash3-fill"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');

    actualizarPaginacion();
}

function actualizarPaginacion() {
    const total  = _filtrados.length;
    const start  = (_pagina - 1) * POR_PAG + 1;
    const end    = Math.min(_pagina * POR_PAG, total);
    const maxPag = Math.ceil(total / POR_PAG) || 1;
    document.getElementById('pagInfo').textContent  = total ? `${start} a ${end} de ${total}` : 'Sin resultados';
    document.getElementById('btnPrev').disabled = _pagina <= 1;
    document.getElementById('btnNext').disabled = _pagina >= maxPag;
}
function paginaAnterior() { if (_pagina > 1) { _pagina--; renderTabla(); } }
function paginaSiguiente() {
    if (_pagina < Math.ceil(_filtrados.length / POR_PAG)) { _pagina++; renderTabla(); }
}

function actualizarPills() {
    const total = _todos.length;
    const stock = _todos.filter(p => (parseFloat(p.stock_bodega_ppral) || 0) > 0).length;
    document.getElementById('pillTotal').innerHTML = `<i class="bi bi-box-seam"></i> ${total} productos`;
    document.getElementById('pillStock').innerHTML = `<i class="bi bi-graph-up"></i> ${stock} con stock`;
}

// ── MODAL EDITAR ──────────────────────────────────────────────
function abrirEditar(sku, nombre, cantidad) {
    _skuEditar = sku;
    document.getElementById('editSku').textContent    = sku;
    document.getElementById('editNombre').value       = nombre;
    document.getElementById('editCantidad').value     = cantidad;
    document.getElementById('editError').style.display = 'none';
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}

async function guardarEdicion() {
    const btn  = document.getElementById('btnGuardar');
    const errEl = document.getElementById('editError');
    errEl.style.display = 'none';

    const nombre   = document.getElementById('editNombre').value.trim();
    const cantidad = parseFloat(document.getElementById('editCantidad').value);

    if (!nombre) { errEl.textContent = 'El nombre no puede estar vacío.'; errEl.style.display='block'; return; }

    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Guardando…';

    try {
        const resp = await fetch(BASE + 'bodega/producto/' + encodeURIComponent(_skuEditar), {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nombre, stock_bodega_ppral: cantidad }),
        });
        const json = await resp.json();
        if (json.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalEditar')).hide();
            // Actualizar en memoria sin recargar todo
            const p = _todos.find(x => x.sku === _skuEditar);
            if (p) { p.nombre = nombre; p.stock_bodega_ppral = cantidad; }
            filtrar();
        } else {
            errEl.textContent = json.message || 'Error al guardar.';
            errEl.style.display = 'block';
        }
    } catch(e) {
        errEl.textContent = 'Error de conexión.'; errEl.style.display = 'block';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Guardar cambios';
    }
}

// ── MODAL VISUALIZAR ──────────────────────────────────────────
async function abrirVer(sku) {
    document.getElementById('verSku').textContent = sku;
    document.getElementById('verLoader').style.display    = 'flex';
    document.getElementById('verContenido').style.display = 'none';
    new bootstrap.Modal(document.getElementById('modalVer')).show();

    try {
        const resp = await fetch(BASE + 'bodega/producto/' + encodeURIComponent(sku));
        const json = await resp.json();
        if (!json.success) throw new Error(json.message);

        const p = json.producto;
        document.getElementById('verNombre').textContent = p.nombre || '--';
        document.getElementById('verCat').textContent    = p.categoria || '--';

        document.getElementById('verDatos').innerHTML = [
            ['SKU',             p.sku],
            ['Marca',           p.marca       || '--'],
            ['Modelo',          p.modelo      || '--'],
            ['Unidad',          p.unidad      || '--'],
            ['Código barras',   p.codigo_barras || '--'],
            ['Tipo',            p.tipo        || '--'],
            ['Costo neto',      fmt(p.costo_neto)],
            ['Precio neto vta', fmt(p.precio_venta_neto)],
            ['Monto IVA',       fmt(p.monto_iva)],
            ['Precio total',    fmt(p.precio_venta_total)],
            ['Comisión vend.',  p.comision_vendedor != null ? p.comision_vendedor + '%' : '--'],
            ['Descripción',     p.descripcion || '--'],
        ].map(([l,v]) => `
            <div class="detail-row">
                <span class="detail-label">${l}</span>
                <span class="detail-value">${v}</span>
            </div>`).join('');

        document.getElementById('verStock').innerHTML = [
            ['Bodega Independencia', fmtN(p.stock_bodega_ppral) + ' ' + (p.unidad||'')],
            ['Ditron (sec.)',        fmtN(p.stock_bodega_sec)   + ' ' + (p.unidad||'')],
            ['Reservado',           fmtN(p.stock_reservado)    + ' ' + (p.unidad||'')],
            ['Stock mínimo',        fmtN(p.stock_minimo)        + ' ' + (p.unidad||'')],
        ].map(([l,v]) => `
            <div class="detail-row">
                <span class="detail-label">${l}</span>
                <span class="detail-value">${v}</span>
            </div>`).join('');

        const listas = json.listas || [];
        document.getElementById('verListas').innerHTML = listas.length
            ? listas.map(l => `
                <div class="precio-list-row">
                    <span style="font-weight:600;color:#374151;">${l.lista}</span>
                    <span style="color:#059669;font-weight:700;">${fmt(l.precio_total)}</span>
                </div>`).join('')
            : '<span style="color:#94a3b8;font-size:.82rem;">Sin listas de precio.</span>';

        document.getElementById('verLoader').style.display    = 'none';
        document.getElementById('verContenido').style.display = 'block';
    } catch(e) {
        document.getElementById('verLoader').innerHTML =
            `<span style="color:#dc2626;font-size:.84rem;"><i class="bi bi-x-circle me-1"></i>Error al cargar detalle.</span>`;
    }
}

// ── MODAL ELIMINAR ────────────────────────────────────────────
function abrirEliminar(sku, nombre) {
    _skuEliminar = sku;
    document.getElementById('delNombre').textContent = nombre;
    document.getElementById('delSku').textContent    = sku;
    document.getElementById('delError').style.display = 'none';
    new bootstrap.Modal(document.getElementById('modalEliminar')).show();
}

async function confirmarEliminar() {
    const btn   = document.getElementById('btnConfirmarEliminar');
    const errEl = document.getElementById('delError');
    errEl.style.display = 'none';

    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Eliminando…';

    try {
        const resp = await fetch(BASE + 'bodega/producto/' + encodeURIComponent(_skuEliminar), {
            method: 'DELETE',
        });
        const json = await resp.json();
        if (json.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalEliminar')).hide();
            _todos     = _todos.filter(p => p.sku !== _skuEliminar);
            filtrar();
            actualizarPills();
        } else {
            errEl.textContent = json.message || 'Error al eliminar.';
            errEl.style.display = 'block';
        }
    } catch(e) {
        errEl.textContent = 'Error de conexión.'; errEl.style.display = 'block';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-trash3 me-1"></i>Sí, eliminar';
    }
}

// ── Init ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    const u      = window.ADMIN_SESSION || {};
    const nombre = u.nombre || 'Admin';
    const ini    = nombre.substring(0, 2).toUpperCase();
    const el     = (id, v) => { const e = document.getElementById(id); if (e) e.textContent = v; };
    el('topbarAvatar', ini);   el('topbarNombre', nombre);  el('topbarRol', u.perfil || 'Administrador');
    el('sidebarAvatar', ini);  el('sidebarNombre', nombre); el('sidebarRol', u.perfil || 'Administrador');
    const f = document.getElementById('fechaHoy');
    if (f) f.textContent = new Date().toLocaleDateString('es-CL', { day:'2-digit', month:'2-digit', year:'numeric' });
    // Seleccionar Precios base por defecto
    document.getElementById('filtroLista').value = 'Precios base';
    cargarProductos();
});

function abrirModalAdmin() {
    const u      = window.ADMIN_SESSION || {};
    const nombre = u.nombre || 'Admin';
    const ini    = nombre.substring(0, 2).toUpperCase();
    const el     = (id, v) => { const e = document.getElementById(id); if (e) e.textContent = v; };
    el('modalAdminAvatar', ini);
    el('modalAdminNombre', nombre + ' ' + (u.apellidos || ''));
    el('modalAdminPerfil', u.perfil || 'Administrador');
    const rows = document.getElementById('modalAdminRows');
    if (rows) {
        const c = [['Correo', u.email||'--'], ['RUT', u.rut||'--'], ['Teléfono', u.telefono||'--']];
        rows.innerHTML = c.map(([l,v]) =>
            `<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f0f4f9;font-size:.80rem;">
                <span style="color:#5a7394;">${l}</span>
                <span style="font-weight:600;color:#1a2940;">${v}</span>
             </div>`).join('');
    }
    new bootstrap.Modal(document.getElementById('modalAdminInfo')).show();
}
</script>
</body>
</html>
