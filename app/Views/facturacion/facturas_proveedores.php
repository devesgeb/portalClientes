<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?> – Portal de Clientes</title>
    <meta name="description" content="Visualización y gestión de documentos recibidos de proveedores desde Facto API.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('public/assets/css/admin.css') ?>">
    <style>
        .card-stats {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #ffffff;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card-stats:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }
        .table-container {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        }
        .table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
            padding: 12px 16px;
        }
        .table td {
            font-size: 0.85rem;
            padding: 14px 16px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }
        .search-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #ffffff;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        }
        .status-select {
            font-size: 0.78rem;
            font-weight: 600;
            border-radius: 8px;
            padding: 4px 8px;
            cursor: pointer;
        }
        .status-select.pagada {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .status-select.pendiente {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .status-select.parcial {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .bulk-bar {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 12px 18px;
            margin-bottom: 16px;
        }
        .btn-amber-action {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            color: #ffffff !important;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.88rem;
            padding: 9px 20px;
            transition: all 0.2s ease-in-out;
            box-shadow: 0 4px 12px rgba(217, 119, 6, 0.25);
        }
        .btn-amber-action:hover {
            background: linear-gradient(135deg, #b45309 0%, #92400e 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(217, 119, 6, 0.35);
        }
        .btn-search-main {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff !important;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.88rem;
            padding: 9px 22px;
            transition: all 0.2s ease-in-out;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }
        .btn-search-main:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
        }
        .btn-clear-main {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 9px 18px;
            transition: all 0.2s ease-in-out;
        }
        .btn-clear-main:hover {
            background: #f8fafc;
            color: #1e293b;
            border-color: #94a3b8;
        }
    </style>
</head>
<body>

<?= $this->include('partials/sidebar') ?>

<!-- MAIN -->
<div class="main">
    <div class="topbar">
        <div class="d-flex align-items-center gap-2">
            <button class="btn-menu-toggle" onclick="abrirSidebar()" aria-label="Abrir menú">
                <i class="bi bi-list"></i>
            </button>
            <div>
                <div class="topbar-title" id="topbarTitle">
                    <i class="bi bi-file-earmark-text me-2" style="color:var(--accent);"></i>Documentos Proveedores (Compras)
                </div>
                <div class="topbar-sub" id="topbarSub">Facturación &rsaquo; Documentos Recibidos Facto API</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="date-badge">
                <i class="bi bi-calendar3 me-1"></i><span id="fechaHoy"></span>
            </span>
            <div class="user-badge" onclick="abrirModalAdmin()" title="Ver información del administrador">
                <div class="ub-avatar" id="topbarAvatar">--</div>
                <div>
                    <div class="ub-name" id="topbarNombre">Cargando...</div>
                    <div class="ub-role" id="topbarRol">Administrador</div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-content p-4">
        
        <!-- Tarjetas Estadísticas -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card card-stats h-100 shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="p-3 bg-warning-subtle text-warning rounded-4" style="font-size: 1.5rem;">
                            <i class="bi bi-file-earmark-text-fill"></i>
                        </div>
                        <div>
                            <div class="text-muted fs-7 fw-medium text-uppercase">Total Documentos Proveedores</div>
                            <h4 class="fw-bold mb-0 text-slate-800" id="statTotalDocs">0</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stats h-100 shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="p-3 bg-amber-subtle text-amber rounded-4" style="font-size: 1.5rem; background-color:#fef3c7; color:#d97706;">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div>
                            <div class="text-muted fs-7 fw-medium text-uppercase">Monto Total Compras / Proveedores</div>
                            <h4 class="fw-bold mb-0 text-slate-800" id="statTotalMonto">$0</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stats h-100 shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="p-3 bg-info-subtle text-info rounded-4" style="font-size: 1.5rem;">
                            <i class="bi bi-cloud-check-fill"></i>
                        </div>
                        <div>
                            <div class="text-muted fs-7 fw-medium text-uppercase">Conexión Facto API</div>
                            <h4 class="fw-bold mb-0 text-slate-800" style="font-size: 1.1rem;">Koywe Billing (Recibidos)</h4>
                            <span class="badge bg-success-subtle text-success font-monospace" style="font-size:0.7rem;">Proveedores DTE</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros de Búsqueda -->
        <div class="search-card">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                <h6 class="fw-bold text-slate-800 mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-funnel-fill text-amber-600" style="font-size:1.1rem; color:#d97706;"></i>Filtros de Búsqueda Proveedores
                </h6>
                <span class="text-muted" style="font-size:0.78rem;">Filtra y revisa los DTEs recibidos de proveedores desde Koywe Facto API</span>
            </div>
            
            <form id="formFiltros" onsubmit="event.preventDefault(); buscarDocumentos();">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label text-slate-700 fw-semibold" style="font-size:0.8rem;">Proveedor (Nombre o RUT)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-truck"></i></span>
                            <input type="text" class="form-control form-control-sm" id="filterCliente" placeholder="Ej: Frutti Fruit o 76.885...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-slate-700 fw-semibold" style="font-size:0.8rem;">Folio (Número)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-hash"></i></span>
                            <input type="text" class="form-control form-control-sm" id="filterNumero" placeholder="Ej: 1425">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-slate-700 fw-semibold" style="font-size:0.8rem;">Tipo de Documento</label>
                        <select class="form-select form-select-sm fw-medium" id="filterTipoDte">
                            <option value="">Todos los DTEs</option>
                            <option value="33">Facturas Electrónicas (33)</option>
                            <option value="34">Facturas Exentas (34)</option>
                            <option value="52">Guías de Despacho (52)</option>
                            <option value="61">Notas de Crédito (61)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-slate-700 fw-semibold" style="font-size:0.8rem;">Estado de Pago</label>
                        <select class="form-select form-select-sm fw-medium" id="filterEstadoPago">
                            <option value="">Todos los Estados</option>
                            <option value="pendiente">🔴 Pendientes</option>
                            <option value="pagada">🟢 Pagadas</option>
                            <option value="parcial">🟡 Parciales</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-slate-700 fw-semibold" style="font-size:0.8rem;">Rango de Fechas (Desde / Hasta)</label>
                        <div class="input-group input-group-sm">
                            <input type="date" class="form-control form-control-sm" id="filterFechaInicio" title="Fecha Desde">
                            <span class="input-group-text bg-light text-muted">a</span>
                            <input type="date" class="form-control form-control-sm" id="filterFechaFin" title="Fecha Hasta">
                        </div>
                    </div>
                </div>

                <!-- Botonera de Acciones Destacadas -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-4 pt-3 border-top bg-light-subtle rounded-3 p-2">
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-search-main d-flex align-items-center gap-2" onclick="buscarDocumentos()">
                            <i class="bi bi-search"></i>
                            <span>Buscar Proveedores</span>
                        </button>
                        <button type="button" class="btn btn-clear-main d-flex align-items-center gap-2" onclick="limpiarFiltros()">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            <span>Limpiar Filtros</span>
                        </button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-amber-action d-flex align-items-center gap-2 shadow" onclick="abrirModalImportarPagar()" title="Inspeccionar e Importar DTEs a Cuentas por Pagar">
                            <i class="bi bi-cloud-download-fill fs-6"></i>
                            <span>Cargar a Cuentas por Pagar</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Barra de Acciones Masivas -->
        <div id="bulkActionsBar" class="bulk-bar d-flex align-items-center justify-content-between shadow-sm" style="display: none !important;">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-check-square-fill text-amber-600" style="font-size: 1.2rem; color:#d97706;"></i>
                <span class="fw-bold text-slate-700" style="font-size: 0.88rem;" id="selectedCountText">0 documentos seleccionados</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 text-slate-600 fw-medium" style="font-size: 0.8rem;">Marcar seleccionadas como:</label>
                <select id="bulkEstadoSelect" class="form-select form-select-sm" style="width: 190px;">
                    <option value="pagada">🟢 Pagada</option>
                    <option value="pendiente">🔴 Pendiente</option>
                    <option value="parcial">🟡 Pago Parcial</option>
                </select>
                <button type="button" class="btn btn-sm btn-warning px-3 fw-semibold py-1.5" onclick="aplicarAccionMasiva()">
                    <i class="bi bi-check2-all me-1"></i> Aplicar Cambios
                </button>
            </div>
        </div>

        <!-- Grilla de Resultados -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center p-3 border-bottom bg-light-subtle">
                <span class="fw-bold text-slate-700" style="font-size:0.9rem;">
                    <i class="bi bi-table me-2 text-warning"></i>Listado de DTEs Recibidos de Proveedores
                </span>
                <span class="badge bg-warning text-dark fw-bold rounded-pill" id="countBadge">0 documentos</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:40px;">
                                <input type="checkbox" class="form-check-input" id="checkSelectAll" onclick="toggleSelectAll(this)">
                            </th>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Tipo Documento</th>
                            <th>RUT Proveedor</th>
                            <th>Razón Social Proveedor</th>
                            <th class="text-end">Total ($)</th>
                            <th class="text-center">Estado SII</th>
                            <th class="text-center">Estado Pago</th>
                            <th>Observación / Estado</th>
                        </tr>
                    </thead>
                    <tbody id="tableBodyDocs">
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <span class="spinner-border spinner-border-sm me-2 text-warning"></span>
                                Cargando documentos recibidos de proveedores...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center" id="paginationContainer">
                <div class="text-slate-600" style="font-size:0.82rem;">
                    Mostrando página <span class="fw-semibold text-dark" id="currentPageNum">1</span> de <span class="fw-semibold text-dark" id="totalPagesNum">1</span> (<span class="fw-semibold text-dark" id="totalDocsNum">0</span> documentos)
                </div>
                <nav aria-label="Navegación de páginas">
                    <ul class="pagination pagination-sm mb-0" id="paginationList">
                    </ul>
                </nav>
            </div>
        </div>

    </div>
</div>

<!-- Modal Cargar DTEs Proveedores a Cuentas por Pagar -->
<div class="modal fade" id="modalImportarPagar" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <div class="modal-header py-3" style="background:linear-gradient(135deg,#b45309,#78350f);border:none;">
                <h6 class="modal-title text-white fw-bold">
                    <i class="bi bi-cloud-download-fill me-2"></i>Inspección Visual: Cargar DTEs Proveedores a Cuentas por Pagar
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-warning d-flex align-items-center gap-3 py-2 px-3 mb-3" style="border-radius: 10px; font-size: 0.82rem; background-color:#fffbeb; border-color:#fde68a; color:#92400e;">
                    <i class="bi bi-info-circle-fill text-warning fs-5"></i>
                    <div>
                        Inspecciona y selecciona los documentos recibidos de proveedores (<strong>Facturas de Compra 33/34</strong>, <strong>Guías 52</strong>) que deseas trasladar a <strong>Cuentas por Pagar</strong>.
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded-3 border">
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-warning text-dark px-3 py-2 fw-bold" style="font-size:0.85rem;" id="modalImportCountBadge">0 seleccionados</span>
                        <span class="text-slate-800 font-monospace fw-bold" style="font-size:0.92rem;" id="modalImportTotalMonto">Total Seleccionado: $0</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <label class="form-label mb-0 fw-semibold text-slate-600" style="font-size: 0.8rem;">Filtrar Estado:</label>
                        <select id="modalEstadoFilter" class="form-select form-select-sm fw-medium" style="width: 215px;" onchange="filtrarModalDocs()">
                            <option value="todos">🌐 Todos los Estados</option>
                            <option value="pendiente" selected>🟡 Pendientes por Cargar</option>
                            <option value="cobranza">🟢 Ya en Cuentas por Pagar</option>
                        </select>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-3 ms-2" onclick="toggleModalCheckAllMaster(this)">
                            <i class="bi bi-check2-all me-1"></i> Marcar / Desmarcar
                        </button>
                    </div>
                </div>

                <div class="table-responsive border rounded-3" style="max-height: 420px;">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.83rem;">
                        <thead class="bg-light sticky-top" style="z-index:10;">
                            <tr>
                                <th class="text-center" style="width:40px;">
                                    <input type="checkbox" class="form-check-input" id="checkModalSelectAll" onclick="toggleModalCheckAll(this)">
                                </th>
                                <th>Folio</th>
                                <th>Fecha</th>
                                <th>Tipo DTE</th>
                                <th>RUT Proveedor</th>
                                <th>Razón Social Proveedor</th>
                                <th class="text-end">Monto Total</th>
                                <th class="text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="modalImportBody">
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <span class="spinner-border spinner-border-sm me-2 text-warning"></span>
                                    Cargando documentos de proveedores desde Facto API...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-top bg-light justify-content-between">
                <button type="button" class="btn btn-sm btn-light px-3 py-2 fw-semibold" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-warning text-dark px-4 py-2 fw-bold" id="btnConfirmarImportación" onclick="confirmarImportacionPagar()">
                    <i class="bi bi-check-lg me-1"></i> Confirmar e Importar a Cuentas por Pagar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Info Admin -->
<div class="modal fade" id="modalAdminInfo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;">
            <div class="modal-header py-3" style="background:linear-gradient(135deg,#1e1b4b,#4338ca);border:none;">
                <h6 class="modal-title text-white">
                    <i class="bi bi-person-badge-fill me-2"></i>Información del Administrador
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
            </div>
            <div class="modal-body p-4">
                <div class="admin-avatar-lg" id="modalAdminAvatar">--</div>
                <div style="text-align:center;margin-bottom:20px;">
                    <div style="font-size:1.05rem;font-weight:700;color:var(--text-main);" id="modalAdminNombre">--</div>
                    <div style="font-size:.76rem;color:var(--text-sub);margin-top:2px;" id="modalAdminPerfil">--</div>
                </div>
                <div id="modalAdminRows"></div>
                <div style="margin-top:14px;padding:10px 14px;background:#f0fdf4;border-radius:10px;border:1px solid #bbf7d0;font-size:.78rem;color:#15803d;display:flex;align-items:center;gap:8px;" id="modalEstadoAcceso">
                    <i class="bi bi-check-circle-fill"></i><span>Cuenta activa</span>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f0f4f9;">
                <button class="btn btn-sm" style="background:#f0f4f9;color:#5a7394;border-radius:8px;" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div id="toastWrapper" style="position:fixed;bottom:24px;right:24px;z-index:9999;"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const baseUrl = '<?= esc($base_url) ?>';
    window.ADMIN_SESSION = <?= json_encode($usuario ?? ['nombre'=>session()->get('Nombre')??'Administrador','apellidos'=>'','email'=>'','rut'=>'','telefono'=>'','estado'=>1,'ultimo_acceso'=>null,'perfil'=>'Administrador']) ?>;

    document.addEventListener('DOMContentLoaded', () => {
        const hoy = new Date();
        const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('fechaHoy').textContent = hoy.toLocaleDateString('es-CL', opciones);

        cargarInfoModalAdmin();
        buscarDocumentos();
    });

    let allFactoDocs = [];
    let currentFactoPage = 1;
    const itemsPerPage = 25;

    async function buscarDocumentos() {
        const tbody = document.getElementById('tableBodyDocs');
        tbody.innerHTML = `<tr><td colspan="10" class="text-center py-5 text-muted"><span class="spinner-border spinner-border-sm me-2 text-warning"></span>Consultando Facto API Compras...</td></tr>`;

        const cliente = document.getElementById('filterCliente').value;
        const numero = document.getElementById('filterNumero').value;
        const tipoDte = document.getElementById('filterTipoDte').value;
        const estadoPago = document.getElementById('filterEstadoPago').value;
        const fechaInicio = document.getElementById('filterFechaInicio').value;
        const fechaFin = document.getElementById('filterFechaFin').value;

        const params = new URLSearchParams({
            cliente: cliente,
            numero: numero,
            tipo_dte: tipoDte,
            estado_pago: estadoPago,
            fecha_inicio: fechaInicio,
            fecha_fin: fechaFin
        });

        try {
            const response = await fetch(baseUrl + 'facturacion/proveedores/buscar-dtes?' + params.toString());
            const res = await response.json();

            if (!res.success) {
                tbody.innerHTML = `<tr><td colspan="10" class="text-center py-5 text-danger"><i class="bi bi-exclamation-triangle me-2"></i>${res.message || 'Error al obtener datos'}</td></tr>`;
                return;
            }

            allFactoDocs = res.data || [];
            document.getElementById('statTotalDocs').textContent = res.count || 0;
            document.getElementById('statTotalMonto').textContent = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' }).format(res.total_monto || 0);

            if (allFactoDocs.length === 0) {
                tbody.innerHTML = `<tr><td colspan="10" class="text-center py-5 text-muted"><i class="bi bi-inbox fs-4 d-block mb-2"></i>No se encontraron documentos recibidos con los filtros aplicados.</td></tr>`;
                document.getElementById('paginationContainer').style.setProperty('display', 'none', 'important');
                return;
            }

            renderFactoPage(1);

        } catch (error) {
            tbody.innerHTML = `<tr><td colspan="10" class="text-center py-5 text-danger"><i class="bi bi-wifi-off me-2"></i>Error al conectar con Facto API: ${error.message}</td></tr>`;
        }
    }

    function renderFactoPage(page) {
        currentFactoPage = page;
        const totalDocs = allFactoDocs.length;
        const totalPages = Math.max(1, Math.ceil(totalDocs / itemsPerPage));

        const startIdx = (page - 1) * itemsPerPage;
        const endIdx = Math.min(startIdx + itemsPerPage, totalDocs);
        const pageDocs = allFactoDocs.slice(startIdx, endIdx);

        const tbody = document.getElementById('tableBodyDocs');
        tbody.innerHTML = pageDocs.map(d => {
            const totalFmt = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' }).format(d.total);
            const selectClass = `form-select form-select-sm status-select ${d.estado_pago}`;

            return `
            <tr>
                <td class="text-center">
                    <input type="checkbox" class="form-check-input doc-checkbox" data-folio="${d.folio}" data-codigo="${d.codigo_sii}" onclick="actualizarSeleccion()">
                </td>
                <td class="fw-bold text-slate-800">${d.folio}</td>
                <td class="text-nowrap">${d.fecha}</td>
                <td><span class="badge bg-light text-dark border">${d.tipo_documento}</span></td>
                <td class="font-monospace text-slate-600">${d.proveedor_rut || d.cliente_rut}</td>
                <td class="fw-semibold text-slate-800">${d.proveedor_nombre || d.cliente_nombre}</td>
                <td class="text-end fw-bold text-slate-900">${totalFmt}</td>
                <td class="text-center">
                    <span class="badge ${d.estado_sii === 'Aceptado' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'} border">
                        ${d.estado_sii}
                    </span>
                </td>
                <td class="text-center">
                    <select class="${selectClass}" onchange="cambiarEstadoIndividual('${d.folio}', ${d.codigo_sii}, this.value, this)">
                        <option value="pendiente" ${d.estado_pago === 'pendiente' ? 'selected' : ''}>🔴 Pendiente</option>
                        <option value="pagada" ${d.estado_pago === 'pagada' ? 'selected' : ''}>🟢 Pagada</option>
                        <option value="parcial" ${d.estado_pago === 'parcial' ? 'selected' : ''}>🟡 Parcial</option>
                    </select>
                </td>
                <td>
                    <span class="text-slate-600" style="font-size:0.8rem;">
                        <i class="bi bi-info-circle me-1 text-muted"></i>${d.observacion}
                    </span>
                </td>
            </tr>`;
        }).join('');

        document.getElementById('countBadge').textContent = `${totalDocs} documentos`;
        document.getElementById('totalDocsNum').textContent = totalDocs;
        document.getElementById('currentPageNum').textContent = currentFactoPage;
        document.getElementById('totalPagesNum').textContent = totalPages;

        if (totalPages > 1) {
            document.getElementById('paginationContainer').style.setProperty('display', 'flex', 'important');
            let pagListHtml = '';
            const prevDisabled = currentFactoPage === 1 ? 'disabled' : '';
            pagListHtml += `<li class="page-item ${prevDisabled}"><a class="page-link" href="#" onclick="changePage(event, ${currentFactoPage - 1})"><i class="bi bi-chevron-left"></i></a></li>`;

            for (let i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= currentFactoPage - 1 && i <= currentFactoPage + 1)) {
                    const activeClass = i === currentFactoPage ? 'active' : '';
                    pagListHtml += `<li class="page-item ${activeClass}"><a class="page-link" href="#" onclick="changePage(event, ${i})">${i}</a></li>`;
                } else if (i === currentFactoPage - 2 || i === currentFactoPage + 2) {
                    pagListHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            const nextDisabled = currentFactoPage === totalPages ? 'disabled' : '';
            pagListHtml += `<li class="page-item ${nextDisabled}"><a class="page-link" href="#" onclick="changePage(event, ${currentFactoPage + 1})"><i class="bi bi-chevron-right"></i></a></li>`;

            document.getElementById('paginationList').innerHTML = pagListHtml;
        } else {
            document.getElementById('paginationContainer').style.setProperty('display', 'none', 'important');
        }
    }

    function changePage(e, page) {
        if (e) e.preventDefault();
        renderFactoPage(page);
    }

    function toggleSelectAll(headerCheck) {
        const checkboxes = document.querySelectorAll('.doc-checkbox');
        checkboxes.forEach(c => c.checked = headerCheck.checked);
        actualizarSeleccion();
    }

    function actualizarSeleccion() {
        const selected = document.querySelectorAll('.doc-checkbox:checked');
        const bulkBar = document.getElementById('bulkActionsBar');
        const selectedCountText = document.getElementById('selectedCountText');

        if (selected.length > 0) {
            bulkBar.style.setProperty('display', 'flex', 'important');
            selectedCountText.textContent = `${selected.length} documento${selected.length > 1 ? 's' : ''} seleccionado${selected.length > 1 ? 's' : ''}`;
        } else {
            bulkBar.style.setProperty('display', 'none', 'important');
        }
    }

    async function cambiarEstadoIndividual(folio, codigoSii, nuevoEstado, selectElem) {
        selectElem.className = `form-select form-select-sm status-select ${nuevoEstado}`;

        try {
            const response = await fetch(baseUrl + 'facturacion/proveedores/actualizar-estado-pago', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    folio: folio,
                    codigo_sii: codigoSii,
                    estado_pago: nuevoEstado
                })
            });
            const res = await response.json();
            if (res.success) {
                mostrarToast(`Folio ${folio} actualizado a ${nuevoEstado}`, 'success');
                const found = allFactoDocs.find(x => String(x.folio) === String(folio) && parseInt(x.codigo_sii) === parseInt(codigoSii));
                if (found) found.estado_pago = nuevoEstado;
            } else {
                mostrarToast(res.message || 'Error al actualizar', 'danger');
            }
        } catch (e) {
            mostrarToast('Error al conectar con el servidor', 'danger');
        }
    }

    async function aplicarAccionMasiva() {
        const selected = document.querySelectorAll('.doc-checkbox:checked');
        if (selected.length === 0) {
            mostrarToast('Seleccione al menos un documento', 'warning');
            return;
        }

        const nuevoEstado = document.getElementById('bulkEstadoSelect').value;
        const documentos = Array.from(selected).map(c => ({
            folio: c.getAttribute('data-folio'),
            codigo_sii: parseInt(c.getAttribute('data-codigo'))
        }));

        try {
            const response = await fetch(baseUrl + 'facturacion/proveedores/actualizar-estado-masivo', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    documentos: documentos,
                    nuevo_estado: nuevoEstado
                })
            });
            const res = await response.json();

            if (res.success) {
                mostrarToast(res.message, 'success');
                documentos.forEach(d => {
                    const found = allFactoDocs.find(x => String(x.folio) === String(d.folio) && parseInt(x.codigo_sii) === parseInt(d.codigo_sii));
                    if (found) found.estado_pago = nuevoEstado;
                });
                renderFactoPage(currentFactoPage);
            } else {
                mostrarToast(res.message || 'Error al actualizar seleccionados', 'danger');
            }
        } catch (e) {
            mostrarToast('Error al conectar con el servidor', 'danger');
        }
    }

    function limpiarFiltros() {
        document.getElementById('formFiltros').reset();
        buscarDocumentos();
    }

    // ── Modal Inspección Visual e Importación a Cuentas por Pagar ──────────────
    let modalDocsData = [];

    async function abrirModalImportarPagar() {
        const modalElem = new bootstrap.Modal(document.getElementById('modalImportarPagar'));
        modalElem.show();

        const tbody = document.getElementById('modalImportBody');

        if (modalDocsData.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5"><span class="spinner-border spinner-border-sm me-2 text-warning"></span>Cargando DTEs proveedores desde Facto API...</td></tr>`;
            try {
                const response = await fetch(baseUrl + 'facturacion/proveedores/buscar-dtes?estado_pago=pendiente');
                const res = await response.json();
                if (res.success) {
                    modalDocsData = res.data || [];
                } else {
                    modalDocsData = allFactoDocs;
                }
            } catch (e) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-danger">Error al cargar DTEs: ${e.message}</td></tr>`;
                return;
            }
        }

        renderModalDocs();
    }

    function filtrarModalDocs() {
        renderModalDocs();
    }

    function renderModalDocs() {
        const tbody = document.getElementById('modalImportBody');
        const filterVal = document.getElementById('modalEstadoFilter') ? document.getElementById('modalEstadoFilter').value : 'pendiente';

        if (modalDocsData.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-muted">No hay DTEs disponibles.</td></tr>`;
            document.getElementById('modalImportCountBadge').textContent = '0 seleccionados';
            document.getElementById('modalImportTotalMonto').textContent = 'Total Seleccionado: $0';
            return;
        }

        let filtered = modalDocsData.filter(d => {
            const obs = d.observacion || '';
            if (filterVal === 'pendiente') {
                return obs.includes('Pendiente por Cargar');
            } else if (filterVal === 'cobranza') {
                return obs.includes('Cuentas por Pagar');
            }
            return true;
        });

        if (filtered.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted">No se encontraron DTEs para el filtro "${filterVal}".</td></tr>`;
            actualizarSeleccionModal();
            return;
        }

        filtered.sort((a, b) => {
            const isAPend = (a.observacion || '').includes('Pendiente por Cargar') ? 0 : 1;
            const isBPend = (b.observacion || '').includes('Pendiente por Cargar') ? 0 : 1;
            return isAPend - isBPend;
        });

        tbody.innerHTML = filtered.map((d) => {
            const indexInMaster = modalDocsData.findIndex(x => String(x.folio) === String(d.folio) && parseInt(x.codigo_sii) === parseInt(d.codigo_sii));
            const totalFmt = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' }).format(d.total);
            
            let badgeClass = 'bg-secondary';
            if (d.codigo_sii === 33) badgeClass = 'bg-primary-subtle text-primary border border-primary-subtle';
            else if (d.codigo_sii === 52) badgeClass = 'bg-warning-subtle text-warning border border-warning-subtle';
            else if (d.codigo_sii === 34) badgeClass = 'bg-info-subtle text-info border border-info-subtle';

            const esPendienteCarga = (d.observacion || '').includes('Pendiente por Cargar');
            const isChecked = esPendienteCarga ? 'checked' : '';
            
            const estadoBadge = esPendienteCarga
                ? `<span class="badge bg-warning-subtle text-dark border border-warning fw-bold"><i class="bi bi-clock-history me-1"></i>Pendiente por Cargar</span>`
                : `<span class="badge bg-success-subtle text-success border border-success fw-bold"><i class="bi bi-check-circle me-1"></i>Ya en Cuentas por Pagar</span>`;

            return `
            <tr class="${esPendienteCarga ? 'table-warning bg-warning-subtle' : ''}">
                <td class="text-center">
                    <input type="checkbox" class="form-check-input modal-doc-checkbox" data-index="${indexInMaster}" ${isChecked} onclick="actualizarSeleccionModal()">
                </td>
                <td class="fw-bold">${d.folio}</td>
                <td>${d.fecha}</td>
                <td><span class="badge ${badgeClass}">${d.tipo_documento}</span></td>
                <td class="font-monospace">${d.proveedor_rut || d.cliente_rut}</td>
                <td class="fw-semibold text-slate-800">${d.proveedor_nombre || d.cliente_nombre}</td>
                <td class="text-end fw-bold text-dark">${totalFmt}</td>
                <td class="text-center">${estadoBadge}</td>
            </tr>`;
        }).join('');

        actualizarSeleccionModal();
    }

    function toggleModalCheckAll(masterCheck) {
        const checkboxes = document.querySelectorAll('.modal-doc-checkbox');
        checkboxes.forEach(c => c.checked = masterCheck.checked);
        actualizarSeleccionModal();
    }

    function toggleModalCheckAllMaster(btn) {
        const masterCheck = document.getElementById('checkModalSelectAll');
        masterCheck.checked = !masterCheck.checked;
        toggleModalCheckAll(masterCheck);
    }

    function actualizarSeleccionModal() {
        const selected = document.querySelectorAll('.modal-doc-checkbox:checked');
        const count = selected.length;
        let sumTotal = 0;

        selected.forEach(c => {
            const idx = parseInt(c.getAttribute('data-index'));
            if (modalDocsData[idx]) {
                sumTotal += parseFloat(modalDocsData[idx].total || 0);
            }
        });

        document.getElementById('modalImportCountBadge').textContent = `${count} seleccionado${count !== 1 ? 's' : ''}`;
        document.getElementById('modalImportTotalMonto').textContent = `Total Seleccionado: ` + new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' }).format(sumTotal);
    }

    async function confirmarImportacionPagar() {
        const selected = document.querySelectorAll('.modal-doc-checkbox:checked');
        if (selected.length === 0) {
            mostrarToast('Seleccione al menos un documento para trasladar', 'warning');
            return;
        }

        const btnConfirmar = document.getElementById('btnConfirmarImportación');
        btnConfirmar.disabled = true;
        btnConfirmar.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Importando...`;

        const docsToImport = Array.from(selected).map(c => {
            const idx = parseInt(c.getAttribute('data-index'));
            return modalDocsData[idx];
        }).filter(Boolean);

        try {
            const response = await fetch(baseUrl + 'facturacion/proveedores/importar-a-pagar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ documentos: docsToImport })
            });

            const res = await response.json();

            btnConfirmar.disabled = false;
            btnConfirmar.innerHTML = `<i class="bi bi-check-lg me-1"></i> Confirmar e Importar a Cuentas por Pagar`;

            if (res.success) {
                mostrarToast(res.message, 'success');
                const modalInstance = bootstrap.Modal.getInstance(document.getElementById('modalImportarPagar'));
                if (modalInstance) modalInstance.hide();
                modalDocsData = [];
                buscarDocumentos();
            } else {
                mostrarToast(res.message || 'Error al importar documentos', 'danger');
            }
        } catch (e) {
            btnConfirmar.disabled = false;
            btnConfirmar.innerHTML = `<i class="bi bi-check-lg me-1"></i> Confirmar e Importar a Cuentas por Pagar`;
            mostrarToast('Error de conexión con el servidor', 'danger');
        }
    }

    function mostrarToast(mensaje, tipo = 'info') {
        const toastWrapper = document.getElementById('toastWrapper');
        const bgClass = tipo === 'success' ? 'bg-success' : (tipo === 'danger' ? 'bg-danger' : 'bg-warning');
        const toastHtml = `
            <div class="toast align-items-center text-white ${bgClass} border-0 show" role="alert">
                <div class="d-flex">
                    <div class="toast-body fw-medium">
                        ${mensaje}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>`;
        toastWrapper.innerHTML = toastHtml;
        setTimeout(() => { toastWrapper.innerHTML = ''; }, 3500);
    }

    function abrirModalAdmin() {
        const modalAdmin = new bootstrap.Modal(document.getElementById('modalAdminInfo'));
        modalAdmin.show();
    }

    function cargarInfoModalAdmin() {
        const user = window.ADMIN_SESSION || {};
        const avatar = document.getElementById('modalAdminAvatar');
        const topbarAvatar = document.getElementById('topbarAvatar');

        const iniciales = ((user.nombre ? user.nombre[0] : 'A') + (user.apellidos ? user.apellidos[0] : '')).toUpperCase();

        if (avatar) avatar.textContent = iniciales;
        if (topbarAvatar) topbarAvatar.textContent = iniciales;

        document.getElementById('topbarNombre').textContent = user.nombre ? `${user.nombre} ${user.apellidos || ''}` : 'Administrador';
        document.getElementById('topbarRol').textContent = user.perfil || 'Administrador';

        document.getElementById('modalAdminNombre').textContent = user.nombre ? `${user.nombre} ${user.apellidos || ''}` : 'Administrador';
        document.getElementById('modalAdminPerfil').textContent = user.perfil || 'Administrador';

        const rowsContainer = document.getElementById('modalAdminRows');
        if (rowsContainer) {
            rowsContainer.innerHTML = `
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:.82rem;">
                    <span style="color:var(--text-sub);">Email</span><span style="font-weight:600;">${user.email || '—'}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:.82rem;">
                    <span style="color:var(--text-sub);">RUT</span><span style="font-weight:600;">${user.rut || '—'}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:.82rem;">
                    <span style="color:var(--text-sub);">Teléfono</span><span style="font-weight:600;">${user.telefono || '—'}</span>
                </div>`;
        }
    }
</script>
</body>
</html>
