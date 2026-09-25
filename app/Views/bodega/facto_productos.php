<?php $activePage = 'facto_productos'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos & Precios Facto API | Portal Admin</title>
    <meta name="description" content="Listado de productos sincronizado con Facto API.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('public/assets/css/admin.css') ?>">
    <style>
        .page-header-box {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 16px;
            padding: 24px 30px;
            color: #fff;
            margin-bottom: 24px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.2);
        }
        .filter-card {
            background: #fff;
            border-radius: 14px;
            padding: 16px 20px;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }

        /* ── Estilos calcados del diseño de la tabla ── */
        .table-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #f1f5f9;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .table-custom {
            margin-bottom: 0;
        }

        .table-custom thead th {
            background: #faf5ff;
            color: #6b21a8;
            font-weight: 700;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 16px 14px;
            border-bottom: 1.5px solid #f3e8ff;
            vertical-align: middle;
        }

        .table-custom thead th.th-costo-impto {
            color: #dc2626 !important;
        }

        .table-custom tbody td {
            padding: 16px 14px;
            vertical-align: middle;
            font-size: 0.88rem;
            color: #334155;
            border-bottom: 1px solid #f8fafc;
        }

        .sku-badge {
            background: #f3e8ff;
            color: #7c3aed;
            font-weight: 700;
            font-size: 0.8rem;
            padding: 5px 12px;
            border-radius: 10px;
            display: inline-block;
        }

        .text-costo-impto {
            color: #dc2626;
            font-weight: 700;
        }

        .custom-badge-lista {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 12px;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        .badge-base { background: #e0f2fe; color: #0369a1; border-color: #bae6fd; }
        .badge-horeca { background: #fef3c7; color: #b45309; border-color: #fde68a; }
        .badge-mayorista { background: #dcfce7; color: #15803d; border-color: #bbf7d0; }
        .badge-distribucion { background: #f3e8ff; color: #6b21a8; border-color: #e9d5ff; }

        .btn-action-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.15s ease-in-out;
        }
        .btn-action-edit {
            border: 1px solid #e9d5ff;
            color: #7c3aed;
            background: #faf5ff;
        }
        .btn-action-edit:hover {
            background: #7c3aed;
            color: #fff;
        }
        .btn-action-view {
            border: 1px solid #bae6fd;
            color: #0284c7;
            background: #f0f9ff;
        }
        .btn-action-view:hover {
            background: #0284c7;
            color: #fff;
        }
        .btn-action-delete {
            border: 1px solid #fecaca;
            color: #ef4444;
            background: #fef2f2;
        }
        .btn-action-delete:hover {
            background: #ef4444;
            color: #fff;
        }

        /* Modal Styles */
        .modal-header-prod {
            background: linear-gradient(135deg, #4c1d95, #7c3aed);
            color: #fff;
            padding: 16px 20px;
            border: none;
        }
        .form-label-sm {
            font-size: 0.82rem;
            font-weight: 600;
            color: #4c1d95;
            margin-bottom: 6px;
        }
        .form-control-sm-custom {
            width: 100%;
            padding: 8px 14px;
            border: 1.5px solid #ddd6fe;
            border-radius: 10px;
            font-size: 0.86rem;
            font-family: 'Inter', sans-serif;
            background: #fff;
            color: #1e293b;
        }
        .form-control-sm-custom:focus {
            outline: none;
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15);
        }
        .autocomplete-item:hover {
            background-color: #f5f3ff;
            color: #6d28d9;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
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
                        <i class="bi bi-box-seam me-2" style="color:var(--accent);"></i>Productos
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
            <!-- Page Header Box -->
            <div class="page-header-box d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <h4 class="mb-1 fw-bold text-white"><i class="bi bi-box-seam me-2"></i>Productos</h4>
                    <p class="mb-0 text-slate-300 fs-7">Listado completo de productos sincronizados desde Facto API con exactamente las mismas columnas y asignación de stock reservado.</p>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button id="btnSyncFacto" class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold" onclick="sincronizarFactoProductos()" style="font-size:.82rem;">
                        <i class="bi bi-arrow-repeat me-1"></i>Sincronizar
                    </button>
                    <div class="vr opacity-25 mx-1" style="height:28px;"></div>
                    <div class="input-group input-group-sm" style="width:auto;">
                        <select id="selectExportFormat" class="form-select form-select-sm" style="font-size:.80rem;min-width:110px;border-radius:8px 0 0 8px;border-color:#4b5563;background:#1e293b;color:#e2e8f0;">
                            <option value="pdf">📄 PDF</option>
                            <option value="excel">📊 Excel</option>
                        </select>
                        <button id="btnExportar" class="btn btn-sm fw-semibold" onclick="ejecutarExportacion()"
                                style="background:#7c3aed;color:#fff;border:none;border-radius:0 8px 8px 0;font-size:.80rem;padding:4px 14px;">
                            <i class="bi bi-download me-1"></i>Exportar
                        </button>
                    </div>
                </div>

            </div>

            <!-- Filter Bar -->
            <div class="filter-card">
                <div class="row g-3 align-items-center">
                    <div class="col-12 col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="inputSearch" class="form-control border-start-0" placeholder="Buscar por nombre, SKU o descripción..." oninput="cargarProductos()">
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <select id="selectLista" class="form-select" onchange="cargarProductos()">
                            <option value="">-- Todas las Listas de Precios --</option>
                            <?php foreach ($listas as $l): ?>
                                <option value="<?= $l['facto_lista_id'] ?>"><?= esc($l['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-3 text-end">
                        <button class="btn btn-outline-secondary w-100" onclick="resetFiltros()"><i class="bi bi-funnel me-1"></i>Limpiar Filtros</button>
                    </div>
                </div>
            </div>

            <!-- Table Container -->
            <div class="table-card">
                <div class="table-responsive">
                    <table class="table table-custom align-middle" id="tablaFactoProductos">
                        <thead>
                            <tr>
                                <th>NOMBRE</th>
                                <th>SKU</th>
                                <th>STOCK BODEGA</th>
                                <th>STOCK RESERVADO</th>
                                <th>COSTO NETO</th>
                                <th class="th-costo-impto">COSTO C/IMPTO</th>
                                <th>PRECIO NETO</th>
                                <th>PRECIO C/IMPTO</th>

                                <th class="text-center">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyFactoProductos">
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                    Cargando productos desde la base de datos...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         MODAL EDITAR PRODUCTO
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
                    <div class="mb-3">
                        <div class="form-label-sm">Costo neto ($)</div>
                        <input type="number" id="editCostoNeto" class="form-control-sm-custom" placeholder="0" min="0" step="1">
                    </div>
                    <div class="mb-3">
                        <div class="form-label-sm">Precio venta neto (Precios base) ($)</div>
                        <input type="number" id="editPrecioNeto" class="form-control-sm-custom" placeholder="0" min="0" step="1">
                    </div>
                    <div class="mb-3">
                        <div class="form-label-sm">Stock físico en bodega (Independencia)</div>
                        <input type="number" id="editCantidad" class="form-control-sm-custom" placeholder="0" min="0" step="0.001">
                    </div>
                    <div class="mb-1">
                        <div class="form-label-sm">Stock reservado</div>
                        <div class="input-group">
                            <input type="number" id="editReservado" class="form-control"
                                   style="padding: 8px 12px; border: 1.5px solid #ddd6fe; border-radius: 9px 0 0 9px; font-size: .84rem; font-family: 'Inter', sans-serif; background: #f8fafc;"
                                   placeholder="0" min="0" step="0.001" readonly>
                            <button class="btn btn-outline-primary" type="button" 
                                    style="border: 1.5px solid #7c3aed; background: #7c3aed; color: #fff; border-radius: 0 9px 9px 0; font-size: .80rem; font-weight: 600; padding: 0 16px;" 
                                    onclick="abrirSubModalReservas()">
                                <i class="bi bi-people-fill me-1"></i>Asignar
                            </button>
                        </div>
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
         MODAL RESERVAS POR CLIENTE
    ════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modalReservasCliente" tabindex="-1" style="z-index: 1070;">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 540px;">
            <div class="modal-content" style="border-radius: 18px; overflow: hidden; border: none; box-shadow: 0 20px 60px rgba(0,0,0,.25);">
                <div class="modal-header py-3" style="background: linear-gradient(135deg, #1e1b4b, #312e81); color: #fff; border: none;">
                    <h6 class="modal-title d-flex align-items-center gap-2">
                        <i class="bi bi-people-fill"></i> Reservas por Cliente
                        <span style="font-size: .75rem; opacity: .7; font-weight: 400;" id="resSku"></span>
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: invert(1);"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Info Producto -->
                    <div class="mb-3 p-3" style="background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                        <div style="font-size: .70rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em;">PRODUCTO</div>
                        <div style="font-size: .88rem; font-weight: 700; color: #1e293b;" id="resProductoNombre">--</div>
                    </div>

                    <!-- Tabla de Reservas Existentes -->
                    <div class="mb-4">
                        <div style="font-size: .72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 8px;">RESERVAS ACTIVAS</div>
                        <div style="max-height: 180px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff;">
                            <table class="table table-sm table-hover mb-0" style="font-size: .80rem; vertical-align: middle;">
                                <thead class="table-light" style="position: sticky; top: 0; font-size: .70rem; text-transform: uppercase;">
                                    <tr>
                                        <th class="ps-3">CLIENTE</th>
                                        <th class="text-end">CANTIDAD</th>
                                        <th class="text-center" style="width: 130px;">ACCIONES</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyReservasCliente">
                                    <tr>
                                        <td colspan="3" class="text-center py-3 text-muted">Cargando reservas...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Formulario Nueva Reserva -->
                    <div class="p-3" style="background: #f5f3ff; border-radius: 12px; border: 1px solid #ddd6fe;">
                        <div style="font-size: .72rem; font-weight: 700; color: #6d28d9; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 10px;">ASIGNAR NUEVA RESERVA</div>
                        
                        <div class="mb-3 position-relative">
                            <label class="form-label-sm" style="color: #6d28d9;">Buscar Cliente (Nombre o RUT)</label>
                            <input type="text" id="resBuscarCliente" class="form-control-sm-custom" placeholder="🔍 Escribe para buscar cliente..." oninput="buscarClientesAutocomplete(this.value)" autocomplete="off">
                            <!-- Resultados Autocomplete -->
                            <div id="resAutocompleteResults" class="shadow-sm border rounded-3 position-absolute w-100 bg-white" style="display: none; z-index: 1050; max-height: 150px; overflow-y: auto; margin-top: 2px;">
                            </div>
                            <input type="hidden" id="resRutCliente">
                        </div>
                        
                        <div class="row g-2 align-items-end">
                            <div class="col-8">
                                <label class="form-label-sm" style="color: #6d28d9;">Cantidad a Reservar</label>
                                <input type="number" id="resCantidad" class="form-control-sm-custom" placeholder="0.000" min="0.001" step="0.001">
                            </div>
                            <div class="col-4">
                                <button type="button" class="btn btn-sm w-100" style="background: #7c3aed; color: #fff; padding: 8px; border-radius: 9px; font-weight: 600; border: none; font-size: .78rem;" onclick="guardarNuevaReserva()">
                                    <i class="bi bi-plus-lg"></i> Asignar
                                </button>
                            </div>
                        </div>
                        <div id="resError" style="display: none; margin-top: 10px; font-size: .75rem; border-radius: 8px;" class="alert alert-danger py-1 px-3"></div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f0f4f9;">
                    <button type="button" class="btn btn-sm" style="background: #f0f4f9; color: #5a7394; border-radius: 9px; padding: 7px 18px;" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script>
        const baseUrl = '<?= base_url() ?>';
        let _skuEditar = null;
        let _todosProductos = [];
        let _modalEditarObj = null;
        let _modalReservasObj = null;
        let _timeoutAutocomplete = null;

        document.addEventListener('DOMContentLoaded', () => {
            const f = new Date();
            const elFecha = document.getElementById('fechaHoy');
            if (elFecha) {
                elFecha.textContent = f.toLocaleDateString('es-CL', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
            }
            cargarProductos();
        });

        function cargarProductos() {
            const q = document.getElementById('inputSearch').value.trim();
            const listaId = document.getElementById('selectLista').value;

            const url = `${baseUrl}bodega/facto-productos/listar?q=${encodeURIComponent(q)}&lista_id=${encodeURIComponent(listaId)}`;

            fetch(url)
                .then(r => r.json())
                .then(res => {
                    if (!res.success) throw new Error(res.message || 'Error al cargar productos');

                    _todosProductos = res.data;
                    const tbody = document.getElementById('tbodyFactoProductos');
                    if (res.data.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="bi bi-box-seam fs-1 text-slate-300 d-block mb-2"></i>
                                    No se encontraron productos registrados. Haz clic en <strong>Sincronizar con Facto API</strong>.
                                </td>
                            </tr>`;
                        return;
                    }

                    let html = '';
                    res.data.forEach(p => {
                        const costoNetoVal  = parseFloat(p.costo_neto || 0);
                        const costoTotalVal = parseFloat(p.costo_total || 0);
                        const precioNetoVal = parseFloat(p.precio_neto || 0);
                        const precioTotalVal= parseFloat(p.precio_total || 0);

                        const costoNeto  = Math.round(costoNetoVal).toLocaleString('es-CL');
                        const costoTotal = Math.round(costoTotalVal).toLocaleString('es-CL');
                        const precioNeto = Math.round(precioNetoVal).toLocaleString('es-CL');
                        const precioTotal= Math.round(precioTotalVal).toLocaleString('es-CL');

                        const stock = parseFloat(p.stock || 0);
                        const stockRes = parseFloat(p.stock_reservado || 0);
                        const um = escapeHtml(p.unidad_medida || 'UN');

                        let stockClass = 'text-dark';
                        if (stock <= 0) stockClass = 'text-danger fw-bold';
                        else if (stock < 10) stockClass = 'text-warning fw-bold';
                        else stockClass = 'text-danger fw-bold';

                        let badgeLista = 'custom-badge-lista';
                        const nombreL = (p.nombre_lista || '').toLowerCase();
                        if (nombreL.includes('base')) badgeLista += ' badge-base';
                        else if (nombreL.includes('horeca')) badgeLista += ' badge-horeca';
                        else if (nombreL.includes('mayorista')) badgeLista += ' badge-mayorista';
                        else if (nombreL.includes('distribucion')) badgeLista += ' badge-distribucion';

                        const safeNombre = escapeHtml(p.nombre).replace(/'/g, "\\'");

                        html += `
                            <tr>
                                <td>
                                    <div class="fw-semibold text-slate-900">${escapeHtml(p.nombre)}</div>
                                    ${p.descripcion ? `<small class="text-muted d-block text-truncate" style="max-width: 280px;">${escapeHtml(p.descripcion)}</small>` : ''}
                                </td>
                                <td><span class="sku-badge">${escapeHtml(p.sku)}</span></td>
                                <td><span class="${stockClass}">${stock}</span> <span class="text-muted fs-8">${um}</span></td>
                                <td><span class="fw-semibold text-purple" style="color:#7c3aed;">${stockRes}</span> <span class="text-muted fs-8">${um}</span></td>
                                <td>$${costoNeto}</td>
                                <td><span class="text-costo-impto">$${costoTotal}</span></td>
                                <td><span class="fw-semibold text-slate-800">$${precioNeto}</span></td>
                                <td>$${precioTotal}</td>

                                <td class="text-center">
                                    <div class="d-inline-flex gap-1">
                                        <button class="btn btn-action-icon btn-action-edit" title="Editar producto" onclick="abrirEditar('${p.sku}', '${safeNombre}', ${stock}, ${stockRes}, ${costoNetoVal}, ${precioNetoVal})">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-action-icon btn-action-view" title="Ver reservas por cliente" onclick="abrirReservasDirectas('${p.sku}', '${safeNombre}')">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-action-icon btn-action-delete" title="Eliminar" onclick="eliminarProductoPrompt('${p.sku}')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    tbody.innerHTML = html;
                })
                .catch(err => {
                    console.error(err);
                    document.getElementById('tbodyFactoProductos').innerHTML = `
                        <tr>
                            <td colspan="10" class="text-center py-4 text-danger">
                                Error al cargar datos: ${escapeHtml(err.message)}
                            </td>
                        </tr>`;
                });
        }

        // ── MODAL EDITAR PRODUCTO ──
        function abrirEditar(sku, nombre, cantidad, reservado, costoNeto, precioNeto) {
            _skuEditar = sku;
            document.getElementById('editSku').textContent = sku;
            document.getElementById('editNombre').value = nombre;
            document.getElementById('editCantidad').value = cantidad;
            document.getElementById('editReservado').value = reservado;
            document.getElementById('editCostoNeto').value = costoNeto;
            document.getElementById('editPrecioNeto').value = precioNeto;
            document.getElementById('editError').style.display = 'none';

            if (!_modalEditarObj) {
                _modalEditarObj = new bootstrap.Modal(document.getElementById('modalEditar'));
            }
            _modalEditarObj.show();
        }

        async function guardarEdicion() {
            const btn = document.getElementById('btnGuardar');
            const errEl = document.getElementById('editError');
            errEl.style.display = 'none';

            const nombre = document.getElementById('editNombre').value.trim();
            const cantidad = parseFloat(document.getElementById('editCantidad').value) || 0;
            const reservado = parseFloat(document.getElementById('editReservado').value) || 0;
            const costoNeto = parseFloat(document.getElementById('editCostoNeto').value) || 0;
            const precioNeto = parseFloat(document.getElementById('editPrecioNeto').value) || 0;

            if (!nombre) { errEl.textContent = 'El nombre no puede estar vacío.'; errEl.style.display='block'; return; }

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…';

            try {
                const resp = await fetch(`${baseUrl}bodega/producto/${encodeURIComponent(_skuEditar)}`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        nombre, 
                        stock_bodega_ppral: cantidad, 
                        stock_reservado: reservado,
                        costo_neto: costoNeto,
                        precio_venta_neto: precioNeto
                    }),
                });
                const json = await resp.json();
                if (json.success) {
                    _modalEditarObj.hide();
                    Swal.fire({
                        icon: 'success',
                        title: 'Producto Actualizado',
                        text: 'Los datos del producto han sido guardados correctamente.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    cargarProductos();
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

        // ── MODAL RESERVAS POR CLIENTE ──
        function abrirReservasDirectas(sku, nombre) {
            _skuEditar = sku;
            const editNom = document.getElementById('editNombre');
            if (editNom) editNom.value = nombre;
            abrirSubModalReservas(nombre);
        }

        async function abrirSubModalReservas(nombreOpt) {
            if (!_skuEditar) return;

            document.getElementById('resSku').textContent = _skuEditar;
            const editNom = document.getElementById('editNombre');
            const prodNombre = nombreOpt || (editNom ? editNom.value : '') || _skuEditar;
            document.getElementById('resProductoNombre').textContent = prodNombre || '--';

            document.getElementById('resBuscarCliente').value = '';
            document.getElementById('resRutCliente').value = '';
            document.getElementById('resCantidad').value = '';
            document.getElementById('resError').style.display = 'none';
            document.getElementById('resAutocompleteResults').style.display = 'none';

            if (!_modalReservasObj) {
                _modalReservasObj = new bootstrap.Modal(document.getElementById('modalReservasCliente'));
            }
            _modalReservasObj.show();

            await cargarReservasCliente(_skuEditar);
        }

        function buscarClientesAutocomplete(q) {
            clearTimeout(_timeoutAutocomplete);
            const resultsEl = document.getElementById('resAutocompleteResults');
            resultsEl.style.display = 'none';
            resultsEl.innerHTML = '';
            document.getElementById('resRutCliente').value = '';

            if (q.trim().length < 2) return;

            _timeoutAutocomplete = setTimeout(async () => {
                try {
                    const resp = await fetch(`${baseUrl}clientes/buscar?q=${encodeURIComponent(q)}`);
                    const data = await resp.json();

                    if (data && data.length > 0) {
                        resultsEl.innerHTML = data.map(c => {
                            const text = `${c.nombre || c.razon_social} (${c.rut})`;
                            const safeNombre = (c.nombre || c.razon_social || '').replace(/'/g, "\\'");
                            return `<div class="p-2 border-bottom autocomplete-item" style="cursor:pointer; font-size:.80rem;" onclick="seleccionarClienteAutocomplete('${c.rut}', '${safeNombre}')">${text}</div>`;
                        }).join('');
                        resultsEl.style.display = 'block';
                    } else {
                        resultsEl.innerHTML = '<div class="p-2 text-muted" style="font-size:.80rem;">Sin resultados de clientes</div>';
                        resultsEl.style.display = 'block';
                    }
                } catch (e) {
                    console.error('Error fetching autocomplete:', e);
                }
            }, 250);
        }

        function seleccionarClienteAutocomplete(rut, nombre) {
            document.getElementById('resBuscarCliente').value = `${nombre} (${rut})`;
            document.getElementById('resRutCliente').value = rut;
            document.getElementById('resAutocompleteResults').style.display = 'none';
        }

        async function cargarReservasCliente(sku) {
            const tbody = document.getElementById('tbodyReservasCliente');
            tbody.innerHTML = '<tr><td colspan="3" class="text-center py-3 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Cargando reservas...</td></tr>';

            try {
                const resp = await fetch(`${baseUrl}bodega/reservas/${encodeURIComponent(sku)}`);
                const json = await resp.json();

                if (json.success && json.reservas.length > 0) {
                    tbody.innerHTML = json.reservas.map(r => {
                        const nombre = r.nombre_cliente || 'Cliente sin nombre';
                        const rut = r.rut_cliente;
                        const cant = parseFloat(r.cantidad) || 0;

                        return `
                        <tr>
                            <td class="ps-3">
                                <div style="font-weight:600; color:#1e293b;">${escapeHtml(nombre)}</div>
                                <div style="font-size:.70rem; color:#94a3b8;">${escapeHtml(rut)}</div>
                            </td>
                            <td class="text-end font-monospace pe-3" style="font-weight:700; color:#4c1d95;">${cant.toLocaleString('es-CL')}</td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <button type="button" class="btn btn-sm btn-outline-warning py-0 px-2" style="font-size:.70rem;" onclick="descontarReservaPrompt(${r.id}, ${cant}, '${sku}')">
                                        <i class="bi bi-dash-circle"></i> Descontar
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:.70rem;" onclick="eliminarReservaCliente(${r.id}, '${sku}')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        `;
                    }).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="3" class="text-center py-3 text-muted"><i class="bi bi-info-circle me-1"></i>Sin reservas activas para este producto.</td></tr>';
                }
            } catch(e) {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center py-3 text-danger"><i class="bi bi-x-circle me-1"></i>Error al cargar reservas.</td></tr>';
            }
        }

        async function guardarNuevaReserva() {
            const sku = _skuEditar;
            const rut = document.getElementById('resRutCliente').value;
            const cantidad = parseFloat(document.getElementById('resCantidad').value);
            const errEl = document.getElementById('resError');
            errEl.style.display = 'none';

            if (!sku) return;
            if (!rut) { errEl.textContent = 'Seleccione un cliente válido de la lista.'; errEl.style.display = 'block'; return; }
            if (!cantidad || cantidad <= 0) { errEl.textContent = 'La cantidad debe ser mayor a 0.'; errEl.style.display = 'block'; return; }

            try {
                const resp = await fetch(`${baseUrl}bodega/reservas`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ sku, rut_cliente: rut, cantidad })
                });
                const json = await resp.json();

                if (json.success) {
                    document.getElementById('editReservado').value = json.stock_reservado;
                    await cargarReservasCliente(sku);

                    document.getElementById('resBuscarCliente').value = '';
                    document.getElementById('resRutCliente').value = '';
                    document.getElementById('resCantidad').value = '';

                    cargarProductos();
                } else {
                    errEl.textContent = json.message || 'Error al guardar la reserva.';
                    errEl.style.display = 'block';
                }
            } catch(e) {
                errEl.textContent = 'Error de conexión.'; errEl.style.display = 'block';
            }
        }

        async function descontarReservaPrompt(idReserva, cantActual, sku) {
            const { value: cantidad } = await Swal.fire({
                title: 'Descontar Stock Reservado',
                text: `Stock actualmente reservado: ${cantActual}`,
                input: 'number',
                inputLabel: 'Cantidad a descontar:',
                inputPlaceholder: '0.000',
                showCancelButton: true,
                confirmButtonColor: '#f59e0b',
                confirmButtonText: 'Descontar',
                cancelButtonText: 'Cancelar',
                inputValidator: (value) => {
                    if (!value || parseFloat(value) <= 0) {
                        return 'Debe ingresar una cantidad mayor a 0';
                    }
                }
            });

            if (cantidad) {
                try {
                    const resp = await fetch(`${baseUrl}bodega/reservas/descontar`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id_reserva: idReserva, cantidad: parseFloat(cantidad) })
                    });
                    const json = await resp.json();
                    if (json.success) {
                        document.getElementById('editReservado').value = json.stock_reservado;
                        await cargarReservasCliente(sku);
                        cargarProductos();
                    }
                } catch(e) {
                    console.error(e);
                }
            }
        }

        async function eliminarReservaCliente(idReserva, sku) {
            const res = await Swal.fire({
                title: '¿Eliminar Reserva?',
                text: 'Esta reserva será eliminada y el stock será liberado.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            });

            if (res.isConfirmed) {
                try {
                    const resp = await fetch(`${baseUrl}bodega/reservas/${idReserva}`, {
                        method: 'DELETE'
                    });
                    const json = await resp.json();
                    if (json.success) {
                        document.getElementById('editReservado').value = json.stock_reservado;
                        await cargarReservasCliente(sku);
                        cargarProductos();
                    }
                } catch(e) {
                    console.error(e);
                }
            }
        }

        function sincronizarFactoProductos() {
            const btn = document.getElementById('btnSyncFacto');
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status"></span>Sincronizando con Facto...`;

            Swal.fire({
                title: 'Conectando con Facto API',
                text: 'Obteniendo listas de precios, productos y stock...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            fetch(`${baseUrl}cron/sync-facto-productos`)
                .then(r => r.json())
                .then(res => {
                    btn.disabled = false;
                    btn.innerHTML = `<i class="bi bi-arrow-repeat me-2"></i>Sincronizar con Facto API`;

                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sincronización Exitosa',
                            text: `Se actualizaron ${res.productos_total} productos y ${res.registros_lista} combinaciones de precios.`,
                            confirmButtonColor: '#7c3aed'
                        });
                        cargarProductos();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de Sincronización',
                            text: res.message || 'No se pudo completar la sincronización'
                        });
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = `<i class="bi bi-arrow-repeat me-2"></i>Sincronizar con Facto API`;
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Red / Servidor',
                        text: err.message
                    });
                });
        }

        function resetFiltros() {
            document.getElementById('inputSearch').value = '';
            document.getElementById('selectLista').value = '';
            cargarProductos();
        }

        // ── EXPORTAR: orquestador del combo ──────────────────────────
        async function ejecutarExportacion() {
            const formato = document.getElementById('selectExportFormat').value;
            if (formato === 'pdf') {
                _exportarPdf();
            } else {
                await _exportarExcel();
            }
        }

        function _exportarPdf() {
            const btn = document.getElementById('btnExportar');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Abriendo...';
            const win = window.open(`${baseUrl}bodega/facto-productos/exportar-pdf`, '_blank');
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-download me-1"></i>Exportar';
                if (!win) {
                    Swal.fire({ icon: 'warning', title: 'Ventana bloqueada', text: 'Permite las ventanas emergentes para este sitio y vuelve a intentarlo.', confirmButtonColor: '#7c3aed' });
                }
            }, 1200);
        }

        async function _exportarExcel() {
            const btn = document.getElementById('btnExportar');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Generando...';

            try {
                const url = `${baseUrl}bodega/facto-productos/listar?q=&lista_id=`;
                const resp = await fetch(url);
                const json = await resp.json();

                if (!json.success || !json.data.length) {
                    Swal.fire({ icon: 'warning', title: 'Sin datos', text: 'No hay productos para exportar. Sincroniza primero con Facto API.' });
                    return;
                }

                const productos = json.data;

                // Detectar listas únicas
                const listasSet = new Set();
                productos.forEach(p => { if (p.nombre_lista) listasSet.add(p.nombre_lista); });
                const listas = [...listasSet].sort();

                // Pivot por SKU
                const porSku = {};
                productos.forEach(p => {
                    if (!porSku[p.sku]) {
                        porSku[p.sku] = {
                            SKU: p.sku, NOMBRE: p.nombre,
                            DESCRIPCION: p.descripcion || '', CATEGORIA: p.categoria || '',
                            MARCA: p.marca || '', UNIDAD: p.unidad_medida || 'UN',
                            STOCK: parseFloat(p.stock || 0),
                            COSTO_NETO: parseFloat(p.costo_neto || 0),
                            COSTO_TOTAL: parseFloat(p.costo_total || 0),
                        };
                    }
                    if (p.nombre_lista) {
                        porSku[p.sku][`PRECIO_NETO_${p.nombre_lista}`]  = parseFloat(p.precio_neto  || 0);
                        porSku[p.sku][`PRECIO_TOTAL_${p.nombre_lista}`] = parseFloat(p.precio_total || 0);
                    }
                });

                const filas = Object.values(porSku);
                const wb = XLSX.utils.book_new();

                // Hoja 1: Catálogo pivoteado
                const ws1 = XLSX.utils.json_to_sheet(filas);
                ws1['!cols'] = [{wch:12},{wch:35},{wch:30},{wch:20},{wch:15},{wch:8},{wch:10},{wch:14},{wch:14},
                    ...listas.flatMap(() => [{wch:16},{wch:16}])];
                XLSX.utils.book_append_sheet(wb, ws1, 'Catálogo Completo');

                // Hoja por lista
                listas.forEach(lista => {
                    const filasLista = productos
                        .filter(p => p.nombre_lista === lista)
                        .map(p => ({
                            SKU: p.sku, NOMBRE: p.nombre,
                            DESCRIPCION: p.descripcion || '', CATEGORIA: p.categoria || '',
                            UNIDAD: p.unidad_medida || 'UN',
                            PRECIO_NETO:  parseFloat(p.precio_neto  || 0),
                            PRECIO_TOTAL: parseFloat(p.precio_total || 0),
                        }));
                    if (filasLista.length) {
                        const wsL = XLSX.utils.json_to_sheet(filasLista);
                        wsL['!cols'] = [{wch:12},{wch:35},{wch:30},{wch:20},{wch:8},{wch:14},{wch:14}];
                        XLSX.utils.book_append_sheet(wb, wsL, lista.substring(0, 31));
                    }
                });

                const fecha = new Date().toISOString().slice(0, 10);
                XLSX.writeFile(wb, `catalogo_productos_${fecha}.xlsx`);

                Swal.fire({
                    icon: 'success', title: 'Excel listo',
                    text: `${filas.length} SKUs · ${listas.length} listas de precios`,
                    timer: 2000, showConfirmButton: false
                });

            } catch(e) {
                console.error(e);
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo generar el Excel: ' + e.message });
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-download me-1"></i>Exportar';
            }
        }


        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>"']/g, function(m) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
            });
        }

        function abrirSidebar() {
            const sb = document.getElementById('mainSidebar');
            if (sb) sb.classList.toggle('open');
        }
    </script>
</body>
</html>
