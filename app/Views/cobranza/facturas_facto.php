<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?> – Portal de Clientes</title>
    <meta name="description" content="Visualización y gestión de documentos emitidos desde Facto API.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('public/assets/css/admin.css') ?>">
    
    <style>
        .card-stats {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
        }
        .card-stats:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .table-container {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        }
        .table thead {
            background-color: #f8fafc;
            border-bottom: 2px solid #edf2f7;
        }
        .table th {
            font-weight: 600;
            color: #475569;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 14px 16px;
        }
        .table td {
            padding: 14px 16px;
            vertical-align: middle;
            font-size: 0.85rem;
            color: #1e293b;
        }
        .badge-dte {
            padding: 5px 10px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.72rem;
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
                    <i class="bi bi-receipt me-2" style="color:var(--accent);"></i>Facturas Facto
                </div>
                <div class="topbar-sub" id="topbarSub">Cobranza &rsaquo; Documentos Emitidos Facto API</div>
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
                <i class="bi bi-info-circle ms-1" style="color:var(--text-sub);font-size:.80rem;"></i>
            </div>
        </div>
    </div>

    <div class="page-body">
        
        <!-- Tarjetas de Resumen Rápido -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card card-stats h-100 shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="p-3 bg-primary-subtle text-primary rounded-4" style="font-size: 1.5rem;">
                            <i class="bi bi-file-earmark-text-fill"></i>
                        </div>
                        <div>
                            <div class="text-muted fs-7 fw-medium text-uppercase">Total Documentos Emitidos</div>
                            <h4 class="fw-bold mb-0 text-slate-800" id="statTotalCount">0</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stats h-100 shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="p-3 bg-success-subtle text-success rounded-4" style="font-size: 1.5rem;">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div>
                            <div class="text-muted fs-7 fw-medium text-uppercase">Monto Total Página/Filtro</div>
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
                            <h4 class="fw-bold mb-0 text-slate-800" style="font-size: 1.1rem;">Koywe Billing API</h4>
                            <span class="badge bg-success-subtle text-success font-monospace" style="font-size:0.7rem;">RUT: 77.775.829-2</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros de Búsqueda -->
        <div class="search-card">
            <h6 class="fw-bold text-slate-700 mb-3"><i class="bi bi-funnel me-2 text-primary"></i>Filtros de Búsqueda Facto</h6>
            <form id="formFiltros" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label text-slate-600 fw-medium" style="font-size:0.78rem;">Cliente (Nombre o RUT)</label>
                    <input type="text" class="form-control form-control-sm" id="filterCliente" placeholder="Ej: Talbot o 96.685...">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-slate-600 fw-medium" style="font-size:0.78rem;">Folio (Número)</label>
                    <input type="text" class="form-control form-control-sm" id="filterNumero" placeholder="Ej: 1532">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-slate-600 fw-medium" style="font-size:0.78rem;">Tipo de Documento</label>
                    <select class="form-select form-select-sm" id="filterTipoDte">
                        <option value="">Todos los DTEs</option>
                        <option value="33">Facturas Electrónicas (33)</option>
                        <option value="52">Guías de Despacho (52)</option>
                        <option value="39">Boletas Electrónicas (39)</option>
                        <option value="61">Notas de Crédito (61)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-slate-600 fw-medium" style="font-size:0.78rem;">Estado de Pago</label>
                    <select class="form-select form-select-sm" id="filterEstadoPago">
                        <option value="">Todos los Estados</option>
                        <option value="pendiente">🔴 Pendientes</option>
                        <option value="pagada">🟢 Pagadas</option>
                        <option value="parcial">🟡 Parciales</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label text-slate-600 fw-medium" style="font-size:0.78rem;">Desde</label>
                    <input type="date" class="form-control form-control-sm" id="filterFechaInicio">
                </div>
                <div class="col-md-1">
                    <label class="form-label text-slate-600 fw-medium" style="font-size:0.78rem;">Hasta</label>
                    <input type="date" class="form-control form-control-sm" id="filterFechaFin">
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-primary w-100 py-2 fw-semibold" onclick="buscarDocumentos()" title="Buscar">
                        <i class="bi bi-search me-1"></i> Buscar
                    </button>
                    <button type="button" class="btn btn-sm btn-light py-2" onclick="limpiarFiltros()" title="Limpiar Filtros">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </form>
        </div>

        <!-- Barra de Acciones Masivas (Combo Box en la parte superior) -->
        <div id="bulkActionsBar" class="bulk-bar d-flex align-items-center justify-content-between shadow-sm" style="display: none !important;">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-check-square-fill text-primary" style="font-size: 1.2rem;"></i>
                <span class="fw-bold text-slate-700" style="font-size: 0.88rem;" id="selectedCountText">0 documentos seleccionados</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 text-slate-600 fw-medium" style="font-size: 0.8rem;">Marcar seleccionadas como:</label>
                <select id="bulkEstadoSelect" class="form-select form-select-sm" style="width: 190px;">
                    <option value="pagada">🟢 Pagada</option>
                    <option value="pendiente">🔴 Pendiente</option>
                    <option value="parcial">🟡 Pago Parcial</option>
                </select>
                <button type="button" class="btn btn-sm btn-success px-3 fw-semibold py-1.5" onclick="aplicarAccionMasiva()">
                    <i class="bi bi-check2-all me-1"></i> Aplicar Cambios
                </button>
            </div>
        </div>

        <!-- Grilla de Resultados -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center p-3 border-bottom bg-light-subtle">
                <span class="fw-bold text-slate-700" style="font-size:0.9rem;">
                    <i class="bi bi-table me-2 text-primary"></i>Listado de DTEs Emitidos en Facto
                </span>
                <span class="badge bg-primary rounded-pill" id="countBadge">0 documentos</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:40px;">
                                <input type="checkbox" class="form-check-input" id="checkSelectAll" onclick="toggleSelectAll(this)" title="Seleccionar todos en la página">
                            </th>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Tipo Doc</th>
                            <th>Cliente</th>
                            <th class="text-end">Neto</th>
                            <th class="text-end">IVA</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Estado SII</th>
                            <th class="text-center" style="width: 140px;">Estado de Pago</th>
                        </tr>
                    </thead>
                    <tbody id="resultadosBody">
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <span class="spinner-border spinner-border-sm me-2 text-primary"></span>
                                Cargando documentos emitidos desde Facto API...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Paginador -->
            <div class="d-flex justify-content-between align-items-center p-3 border-top bg-light-subtle" id="paginationContainer" style="display: none !important;">
                <div class="text-muted" id="paginationInfo" style="font-size: 0.78rem;">
                    Mostrando página <span class="fw-semibold text-dark" id="currentPageNum">1</span> de <span class="fw-semibold text-dark" id="totalPagesNum">1</span> (<span class="fw-semibold text-dark" id="totalDocsNum">0</span> documentos)
                </div>
                <nav aria-label="Navegación de páginas">
                    <ul class="pagination pagination-sm mb-0" id="paginationList">
                        <!-- Botones de página inyectados aquí -->
                    </ul>
                </nav>
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
    window.ADMIN_BASE_URL = "<?= site_url() ?>";
</script>
<script src="<?= base_url('public/assets/js/admin.js') ?>"></script>

<script>
    let currentFactoPage = 1;
    let currentDocsData = [];

    async function buscarDocumentos(page = 1) {
        currentFactoPage = page;
        document.getElementById('checkSelectAll').checked = false;
        actualizarSeleccion();

        const cliente    = document.getElementById('filterCliente').value;
        const numero     = document.getElementById('filterNumero').value;
        const tipoDte    = document.getElementById('filterTipoDte').value;
        const estadoPago = document.getElementById('filterEstadoPago').value;
        const fInicio    = document.getElementById('filterFechaInicio').value;
        const fFin       = document.getElementById('filterFechaFin').value;

        const params = new URLSearchParams({
            cliente: cliente,
            numero: numero,
            tipo_dte: tipoDte,
            estado_pago: estadoPago,
            fecha_inicio: fInicio,
            fecha_fin: fFin,
            page: currentFactoPage
        });

        const tbody = document.getElementById('resultadosBody');
        tbody.innerHTML = `<tr><td colspan="10" class="text-center py-5"><span class="spinner-border spinner-border-sm me-2 text-primary"></span>Consultando Facto API...</td></tr>`;

        try {
            const response = await fetch(baseUrl + 'cobranza/facto/buscar-dtes?' + params.toString());
            const res = await response.json();

            if (res.success && res.data.length > 0) {
                currentDocsData = res.data;
                const totalCount = res.pagination ? res.pagination.count : res.data.length;
                document.getElementById('countBadge').textContent = `${totalCount} documentos`;
                document.getElementById('statTotalCount').textContent = totalCount;
                
                tbody.innerHTML = res.data.map(d => {
                    const netoFmt  = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' }).format(d.neto);
                    const ivaFmt   = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' }).format(d.iva);
                    const totalFmt = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' }).format(d.total);
                    
                    let badgeClass = 'bg-secondary';
                    if (d.codigo_sii === 33) badgeClass = 'bg-primary-subtle text-primary border border-primary-subtle';
                    else if (d.codigo_sii === 52) badgeClass = 'bg-warning-subtle text-warning border border-warning-subtle';
                    else if (d.codigo_sii === 39) badgeClass = 'bg-success-subtle text-success border border-success-subtle';
                    else if (d.codigo_sii === 61) badgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';

                    const stClass = d.estado_pago || 'pendiente';
                    
                    return `
                    <tr>
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input doc-checkbox" data-folio="${d.folio}" data-codigo="${d.codigo_sii}" onclick="actualizarSeleccion()">
                        </td>
                        <td class="fw-bold">${d.folio}</td>
                        <td>${d.fecha}</td>
                        <td><span class="badge-dte ${badgeClass}">${d.tipo_documento}</span></td>
                        <td>
                            <div class="fw-semibold text-slate-800">${d.cliente_nombre}</div>
                            <div class="text-muted" style="font-size:0.75rem;">${d.cliente_rut}</div>
                        </td>
                        <td class="text-end fw-medium text-slate-600">${netoFmt}</td>
                        <td class="text-end fw-medium text-slate-600">${ivaFmt}</td>
                        <td class="text-end fw-bold text-dark">${totalFmt}</td>
                        <td class="text-center">
                            <span class="badge ${d.estado_sii === 'Aceptado' ? 'bg-success-subtle text-success border border-success' : 'bg-warning-subtle text-warning border border-warning'}">${d.estado_sii}</span>
                        </td>
                        <td class="text-center">
                            <select class="form-select form-select-sm status-select ${stClass}" onchange="cambiarEstadoIndividual('${d.folio}', ${d.codigo_sii}, this.value, this)">
                                <option value="pendiente" ${stClass === 'pendiente' ? 'selected' : ''}>🔴 Pendiente</option>
                                <option value="pagada" ${stClass === 'pagada' ? 'selected' : ''}>🟢 Pagada</option>
                                <option value="parcial" ${stClass === 'parcial' ? 'selected' : ''}>🟡 Parcial</option>
                            </select>
                        </td>
                    </tr>`;
                }).join('');
                
                const montoTotal = res.total_monto !== undefined ? res.total_monto : res.data.reduce((s, d) => s + parseFloat(d.total || 0), 0);
                document.getElementById('statTotalMonto').textContent = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' }).format(montoTotal);

                // Renderizar Paginación
                if (res.pagination && res.pagination.total_pages > 1) {
                    const pag = res.pagination;
                    document.getElementById('paginationContainer').style.setProperty('display', 'flex', 'important');
                    document.getElementById('currentPageNum').textContent = pag.current_page;
                    document.getElementById('totalPagesNum').textContent = pag.total_pages;
                    document.getElementById('totalDocsNum').textContent = pag.count;

                    let pagListHtml = '';
                    
                    // Botón Anterior
                    const prevDisabled = pag.current_page === 1 ? 'disabled' : '';
                    pagListHtml += `<li class="page-item ${prevDisabled}"><a class="page-link" href="#" onclick="changePage(event, ${pag.current_page - 1})"><i class="bi bi-chevron-left"></i></a></li>`;

                    // Rango de páginas inteligentes
                    const range = 2;
                    let startPage = Math.max(1, pag.current_page - range);
                    let endPage = Math.min(pag.total_pages, pag.current_page + range);

                    if (startPage > 1) {
                        pagListHtml += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(event, 1)">1</a></li>`;
                        if (startPage > 2) pagListHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }

                    for (let i = startPage; i <= endPage; i++) {
                        const activeClass = i === pag.current_page ? 'active' : '';
                        pagListHtml += `<li class="page-item ${activeClass}"><a class="page-link" href="#" onclick="changePage(event, ${i})">${i}</a></li>`;
                    }

                    if (endPage < pag.total_pages) {
                        if (endPage < pag.total_pages - 1) pagListHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                        pagListHtml += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(event, ${pag.total_pages})">${pag.total_pages}</a></li>`;
                    }

                    // Botón Siguiente
                    const nextDisabled = pag.current_page === pag.total_pages ? 'disabled' : '';
                    pagListHtml += `<li class="page-item ${nextDisabled}"><a class="page-link" href="#" onclick="changePage(event, ${pag.current_page + 1})"><i class="bi bi-chevron-right"></i></a></li>`;

                    document.getElementById('paginationList').innerHTML = pagListHtml;
                } else {
                    document.getElementById('paginationContainer').style.setProperty('display', 'none', 'important');
                }
            } else {
                document.getElementById('countBadge').textContent = `0 documentos`;
                document.getElementById('statTotalCount').textContent = '0';
                document.getElementById('statTotalMonto').textContent = '$0';
                
                let message = 'No se encontraron documentos en Facto API.';
                if (res.message) {
                    message = `<span class="text-danger fw-medium">${res.message}</span>`;
                }
                
                tbody.innerHTML = `<tr><td colspan="10" class="text-center py-5 text-muted">${message}</td></tr>`;
                document.getElementById('paginationContainer').style.setProperty('display', 'none', 'important');
            }
        } catch(e) {
            tbody.innerHTML = `<tr><td colspan="10" class="text-center py-5 text-danger">Error al consultar Facto API: ${e.message}</td></tr>`;
            document.getElementById('paginationContainer').style.setProperty('display', 'none', 'important');
        }
    }

    // ── Selección Masiva Checkbox ────────────────────────────────
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

    // ── Actualización Individual ────────────────────────────────
    async function cambiarEstadoIndividual(folio, codigoSii, nuevoEstado, selectElem) {
        selectElem.className = `form-select form-select-sm status-select ${nuevoEstado}`;

        try {
            const response = await fetch(baseUrl + 'cobranza/facto/actualizar-estado-pago', {
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
            } else {
                mostrarToast(res.message || 'Error al actualizar', 'danger');
            }
        } catch (e) {
            mostrarToast('Error al conectar con el servidor', 'danger');
        }
    }

    // ── Actualización Masiva Combo Box ──────────────────────────
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
            const response = await fetch(baseUrl + 'cobranza/facto/actualizar-estado-masivo', {
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
                buscarDocumentos(currentFactoPage);
            } else {
                mostrarToast(res.message || 'Error al actualizar seleccionados', 'danger');
            }
        } catch (e) {
            mostrarToast('Error al conectar con el servidor', 'danger');
        }
    }

    function changePage(event, page) {
        event.preventDefault();
        buscarDocumentos(page);
    }

    function limpiarFiltros() {
        document.getElementById('formFiltros').reset();
        buscarDocumentos(1);
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

    // Carga inicial al cargar la página
    document.addEventListener('DOMContentLoaded', () => {
        buscarDocumentos(1);
    });
</script>
</body>
</html>
