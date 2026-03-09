<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('public/assets/css/balanceDiario.css') ?>">

</head>

<body>

    <!-- â•â•â•â•â•â•â•â•â•â•â• SIDEBAR â•â•â•â•â•â•â•â•â•â•â• -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <div class="d-flex align-items-center gap-2">
                <div
                    style="width:34px;height:34px;background:var(--accent);border-radius:9px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-grid-fill text-white" style="font-size:.95rem;"></i>
                </div>
                <div>
                    <div class="logo-text">Portal Admin</div>
                    <div class="logo-sub">v2.4.1</div>
                </div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-sec">Principal</div>
            <a href="#" class="s-link active"><i class="bi bi-bar-chart-line-fill"></i><span>Balance Diario</span></a>
            <a href="documentos.html" class="s-link"><i class="bi bi-receipt"></i><span>Documentos</span></a>
            <a href="#" class="s-link"><i class="bi bi-credit-card"></i><span>Pagos</span></a>
            <a href="#" class="s-link"><i class="bi bi-box-seam"></i><span>Inventario</span></a>
            <div class="nav-sec">ConfiguraciÃ³n</div>
            <a href="config-plazos.html" class="s-link"><i class="bi bi-sliders"></i><span>Plazos CrÃ©dito</span></a>
            <a href="#" class="s-link"><i class="bi bi-people"></i><span>Usuarios</span></a>
        </nav>
        <div class="sidebar-foot">
            <div class="d-flex align-items-center gap-2">
                <div class="u-avatar">JR</div>
                <div>
                    <div class="u-name">Juan RodrÃ­guez</div>
                    <div class="u-role">Administrador</div>
                </div>
            </div>
        </div>
    </div>

    <!-- â•â•â•â•â•â•â•â•â•â•â• MAIN â•â•â•â•â•â•â•â•â•â•â• -->
    <div class="main">

        <!-- Top Bar -->
        <div class="topbar">
            <div>
                <div class="topbar-title"><i class="bi bi-bar-chart-line me-2" style="color:var(--accent);"></i>Balance
                    Diario</div>
                <div class="topbar-sub">Portal Admin &rsaquo; Balance General &rsaquo; Vista del dÃ­a</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="date-badge"><i class="bi bi-calendar3 me-1"></i>25/02/2026</span>
                <button class="btn btn-sm"
                    style="background:var(--primary);color:#fff;border-radius:8px;font-size:.78rem;"
                    onclick="exportarBalance()">
                    <i class="bi bi-download me-1"></i>Exportar
                </button>
            </div>
        </div>

        <div class="page-body">

            <!-- â”€â”€ KPI Summary Strip â”€â”€ -->
            <div class="row g-3 mb-3">
                <div class="col-xl-3 col-md-6">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="background:#eff6ff;"><i class="bi bi-arrow-down-circle-fill"
                                style="color:#2563eb;"></i></div>
                        <div>
                            <div class="kpi-val" id="kpiCobrar" style="color:#2563eb;">$0</div>
                            <div class="kpi-lbl">Total x Cobrar</div>
                            <div class="kpi-sub" id="kpiCobrarSub">0 clientes</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="background:#ecfeff;"><i class="bi bi-box-seam-fill"
                                style="color:#0891b2;"></i></div>
                        <div>
                            <div class="kpi-val" id="kpiCaja" style="color:#0891b2;">$0</div>
                            <div class="kpi-lbl">Total Inventario</div>
                            <div class="kpi-sub" id="kpiCajaSub">0 Ã­tems</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="background:#f5f3ff;"><i class="bi bi-arrow-up-circle-fill"
                                style="color:#7c3aed;"></i></div>
                        <div>
                            <div class="kpi-val" id="kpiPagar" style="color:#7c3aed;">$0</div>
                            <div class="kpi-lbl">Total x Pagar</div>
                            <div class="kpi-sub" id="kpiPagarSub">0 proveedores</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="background:#f0fdf4;"><i class="bi bi-wallet2"
                                style="color:#16a34a;"></i></div>
                        <div>
                            <div class="kpi-val" id="kpiNeto" style="color:#16a34a;">$0</div>
                            <div class="kpi-lbl">Resultado Neto</div>
                            <div class="kpi-sub" style="color:#8fa3bc;">(Cobrar + Caja â€“ Pagar)</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
             THREE-COLUMN BALANCE LAYOUT
         â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
            <div class="row g-3 align-items-start">

                <!-- â”Œâ”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”
                 â”‚  COL 1 â€“ CUENTAS POR COBRAR               â”‚
                 â””â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”˜ -->
                <div class="col-xl-4 col-lg-12">
                    <div class="panel">
                        <!-- Header -->
                        <div class="panel-head" style="background:var(--cobrar-head);">
                            <div class="ph-title"><i class="bi bi-arrow-down-circle-fill"></i>Cuentas por Cobrar</div>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm"
                                    style="background:rgba(255,255,255,.18);color:#fff;border-radius:7px;font-size:.72rem;"
                                    onclick="abrirModalExcel()" title="Carga masiva desde Excel">
                                    <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
                                </button>
                                <button class="btn btn-sm"
                                    style="background:rgba(255,255,255,.18);color:#fff;border-radius:7px;font-size:.72rem;"
                                    onclick="abrirModalAgregar('cobrar')">
                                    <i class="bi bi-plus-lg me-1"></i>Nuevo
                                </button>
                                <button class="btn btn-sm" id="btnGuardarBD"
                                    style="background:rgba(255,255,255,.30);color:#fff;border-radius:7px;font-size:.72rem;border:1px solid rgba(255,255,255,.5);"
                                    onclick="guardarEnBD()" title="Guardar registros en base de datos">
                                    <i class="bi bi-floppy-fill me-1"></i>Guardar en BD
                                </button>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="panel-body">
                            <table class="tbl table table-borderless" id="tblCobrar">
                                <thead>
                                    <tr>
                                        <th style="width:40px;">#</th>
                                        <th>Cliente</th>
                                        <th class="text-end">Monto Total</th>
                                        <th class="text-center" style="width:90px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="bodyCobar">
                                    <!-- rows injected by JS -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Total -->
                        <div class="panel-total">
                            <span class="total-label"><i class="bi bi-sigma me-1"></i>Total x Cobrar</span>
                            <span class="total-value amt-cobrar" id="totalCobrar">$0</span>
                        </div>
                    </div>
                </div>

                <!-- â”Œâ”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”
                 â”‚  COL 2 â€“ CAJA / INVENTARIO                â”‚
                 â””â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”˜ -->
                <div class="col-xl-4 col-lg-12">
                    <div class="panel">
                        <!-- Header -->
                        <div class="panel-head" style="background:var(--caja-head);">
                            <div class="ph-title"><i class="bi bi-box-seam-fill"></i>Caja / Inventario</div>
                            <button class="btn btn-sm"
                                style="background:rgba(255,255,255,.18);color:#fff;border-radius:7px;font-size:.72rem;"
                                onclick="addCajaRow()">
                                <i class="bi bi-plus-lg me-1"></i>Agregar Ãtem
                            </button>
                        </div>

                        <!-- Table -->
                        <div class="panel-body">
                            <table class="tbl table table-borderless" id="tblCaja">
                                <thead>
                                    <tr>
                                        <th>DescripciÃ³n / SKU</th>
                                        <th class="text-end">Precio</th>
                                        <th class="text-end">Stock</th>
                                        <th class="text-end">Monto</th>
                                        <th class="text-center" style="width:64px;">+/-</th>
                                    </tr>
                                </thead>
                                <tbody id="bodyCaja">
                                    <!-- rows injected by JS -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Add row inline form -->
                        <div class="add-row-form" id="formCaja" style="display:none;">
                            <div class="row g-2 align-items-end">
                                <div class="col-4">
                                    <label style="font-size:.68rem;color:#8fa3bc;font-weight:600;">SKU / Desc.</label>
                                    <input class="form-control form-control-sm" id="cajaSku" placeholder="Producto A">
                                </div>
                                <div class="col-3">
                                    <label style="font-size:.68rem;color:#8fa3bc;font-weight:600;">Precio</label>
                                    <input class="form-control form-control-sm" id="cajaPrecio" type="number"
                                        placeholder="0">
                                </div>
                                <div class="col-2">
                                    <label style="font-size:.68rem;color:#8fa3bc;font-weight:600;">Stock</label>
                                    <input class="form-control form-control-sm" id="cajaStock" type="number"
                                        placeholder="0">
                                </div>
                                <div class="col-3 d-flex gap-1">
                                    <button class="btn btn-sm flex-grow-1"
                                        style="background:var(--caja-head);color:#fff;border-radius:7px;font-size:.72rem;"
                                        onclick="confirmarCajaRow()">
                                        <i class="bi bi-check-lg"></i> OK
                                    </button>
                                    <button class="btn btn-sm"
                                        style="background:#f0f4f9;color:#5a7394;border-radius:7px;"
                                        onclick="cancelarCajaRow()">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Total -->
                        <div class="panel-total">
                            <span class="total-label"><i class="bi bi-sigma me-1"></i>Total Inventario</span>
                            <span class="total-value amt-caja" id="totalCaja">$0</span>
                        </div>
                    </div>
                </div>

                <!-- â”Œâ”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”
                 â”‚  COL 3 â€“ CUENTAS POR PAGAR                â”‚
                 â””â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”˜ -->
                <div class="col-xl-4 col-lg-12">
                    <div class="panel">
                        <!-- Header -->
                        <div class="panel-head" style="background:var(--pagar-head);">
                            <div class="ph-title"><i class="bi bi-arrow-up-circle-fill"></i>Cuentas por Pagar</div>
                            <button class="btn btn-sm"
                                style="background:rgba(255,255,255,.18);color:#fff;border-radius:7px;font-size:.72rem;"
                                onclick="abrirModalAgregar('pagar')">
                                <i class="bi bi-plus-lg me-1"></i>Nuevo
                            </button>
                        </div>

                        <!-- Table -->
                        <div class="panel-body">
                            <table class="tbl table table-borderless" id="tblPagar">
                                <thead>
                                    <tr>
                                        <th style="width:40px;">#</th>
                                        <th>Proveedor</th>
                                        <th class="text-end">Monto</th>
                                        <th>Notas</th>
                                        <th class="text-center" style="width:90px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="bodyPagar">
                                    <!-- rows injected by JS -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Total -->
                        <div class="panel-total">
                            <span class="total-label"><i class="bi bi-sigma me-1"></i>Total x Pagar</span>
                            <span class="total-value amt-pagar" id="totalPagar">$0</span>
                        </div>
                    </div>
                </div>

            </div><!-- /row -->

        </div><!-- /page-body -->
    </div><!-- /main -->

    <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     MODAL â€“ Agregar / Editar Cobrar & Pagar
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
    <div class="modal fade" id="modalRegistro" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content" style="border-radius:14px;overflow:hidden;">
                <div class="modal-header py-3" id="modalHeader" style="background:var(--cobrar-head);">
                    <h6 class="modal-title text-white" id="modalTitulo">Agregar Registro</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Cobrar fields -->
                    <div id="fieldsCobrar">
                        <div class="mb-3">
                            <label class="form-label" style="font-size:.78rem;font-weight:500;color:#5a7394;">Nombre Cliente <span class="text-danger">*</span></label>
                            <input class="form-control form-control-sm" id="cobrarCliente" placeholder="Razón social del cliente">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-size:.78rem;font-weight:500;color:#5a7394;">RUT <span class="text-danger">*</span></label>
                            <input class="form-control form-control-sm" id="cobrarRut" placeholder="12.345.678-9">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-size:.78rem;font-weight:500;color:#5a7394;">Monto Total <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="font-size:.78rem;">$</span>
                                <input class="form-control" id="cobrarMonto" type="number" placeholder="0" oninput="recalcImpago()">
                            </div>
                            <div id="cobrarMontoAviso" style="display:none;font-size:.73rem;color:#b45309;background:#fffbeb;border:1px solid #fcd34d;border-radius:6px;padding:5px 10px;margin-top:5px;">
                                <i class="bi bi-lock-fill me-1"></i><span id="cobrarMontoAvisoTexto"></span>
                            </div>
                        </div>
                        <!-- Campos del documento: solo visibles al CREAR nuevo cliente -->
                        <div id="fieldsCobrarDoc" style="display:none;">
                            <hr style="border-color:#e5eaf0;margin:4px 0 12px 0;">
                            <div style="font-size:.74rem;font-weight:600;color:#2563eb;margin-bottom:10px;">
                                <i class="bi bi-receipt me-1"></i>Datos del documento
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label" style="font-size:.76rem;font-weight:500;color:#5a7394;">Tipo Documento <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm" id="cobrarTipoDoc">
                                        <option value="">Seleccionar...</option>
                                        <option value="Factura">Factura</option>
                                        <option value="Boleta">Boleta</option>
                                        <option value="N/A">N/A</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label" style="font-size:.76rem;font-weight:500;color:#5a7394;">N&deg; Documento <span class="text-danger">*</span></label>
                                    <input class="form-control form-control-sm" id="cobrarNroDoc" placeholder="Ej: 1234 o N/A">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label" style="font-size:.76rem;font-weight:500;color:#5a7394;">Fecha <span class="text-danger">*</span></label>
                                <input class="form-control form-control-sm" id="cobrarFechaDoc" type="date">
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label" style="font-size:.76rem;font-weight:500;color:#5a7394;">Pagado</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text" style="font-size:.78rem;">$</span>
                                        <input class="form-control" id="cobrarPagadoDoc" type="number" placeholder="0" min="0" oninput="recalcImpago()">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label" style="font-size:.76rem;font-weight:500;color:#5a7394;">Impago</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text" style="font-size:.78rem;">$</span>
                                        <input class="form-control" id="cobrarImpagoDoc" type="number" placeholder="0" readonly style="background:#f8fafc;color:#374151;">
                                    </div>
                                    <div class="form-text" style="font-size:.68rem;color:#94a3b8;">Monto &minus; Pagado</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagar fields -->
                    <div id="fieldsPagar" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label"
                                style="font-size:.78rem;font-weight:500;color:#5a7394;">Proveedor</label>
                            <input class="form-control form-control-sm" id="pagarProveedor"
                                placeholder="Nombre del proveedor">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                style="font-size:.78rem;font-weight:500;color:#5a7394;">Monto</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="font-size:.78rem;">$</span>
                                <input class="form-control" id="pagarMonto" type="number" placeholder="0">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                style="font-size:.78rem;font-weight:500;color:#5a7394;">Notas</label>
                            <textarea class="form-control form-control-sm" id="pagarNotas" rows="2"
                                placeholder="Observaciones, vencimiento, etc."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f0f4f9;">
                    <button class="btn btn-sm" style="background:#f0f4f9;color:#5a7394;border-radius:8px;"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-sm" id="btnGuardarModal"
                        style="background:var(--cobrar-head);color:#fff;border-radius:8px;" onclick="guardarRegistro()">
                        <i class="bi bi-floppy me-1"></i>Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL â€“ Detalle -->
    <div class="modal fade" id="modalDetalle" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius:14px;overflow:hidden;">
                <div class="modal-header py-3" id="detalleHeader" style="background:var(--cobrar-head);">
                    <h6 class="modal-title text-white" id="detalleTitulo">Detalle</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
                </div>
                <div class="modal-body p-4" id="detalleBody"></div>
                <div class="modal-footer" style="border-top:1px solid #f0f4f9;">
                    <button class="btn btn-sm" style="background:#f0f4f9;color:#5a7394;border-radius:8px;"
                        data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         MODAL â€“ Carga Masiva Excel Cobrar
    â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
    <div class="modal fade" id="modalExcel" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content" style="border-radius:14px;overflow:hidden;">
                <div class="modal-header py-3" style="background:#16a34a;">
                    <h6 class="modal-title text-white">
                        <i class="bi bi-file-earmark-excel-fill me-2"></i>Carga Masiva â€“ Cuentas por Cobrar
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
                </div>
                <div class="modal-body p-4">

                    <!-- Info columnas esperadas -->
                    <div class="alert d-flex gap-2 align-items-start mb-3"
                        style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 14px;font-size:.78rem;">
                        <i class="bi bi-table" style="color:#16a34a;font-size:1.1rem;margin-top:1px;"></i>
                        <div>
                            <strong style="color:#14532d;">Columnas requeridas en el Excel (en cualquier
                                orden):</strong><br>
                            <span style="color:#374151;">
                                <span class="badge" style="background:#dcfce7;color:#15803d;margin-right:4px;">Tipo
                                    Documento</span>
                                <span class="badge"
                                    style="background:#dcfce7;color:#15803d;margin-right:4px;">Fecha</span>
                                <span class="badge"
                                    style="background:#dcfce7;color:#15803d;margin-right:4px;">Numero</span>
                                <span class="badge" style="background:#dcfce7;color:#15803d;margin-right:4px;">Empresa /
                                    Cliente</span>
                                <span class="badge"
                                    style="background:#dcfce7;color:#15803d;margin-right:4px;">Rut</span>
                                <span class="badge"
                                    style="background:#dcfce7;color:#15803d;margin-right:4px;">Total</span>
                                <span class="badge" style="background:#dcfce7;color:#15803d;">Estado</span>
                            </span>
                            <div style="margin-top:4px;color:#6b7280;">
                                Los documentos se agrupan por <strong>Empresa</strong> y solo se suman los de
                                estado <strong>Pendiente</strong>.
                            </div>
                        </div>
                    </div>

                    <!-- Drop Zone -->
                    <div id="excelDropZone"
                        style="border:2px dashed #86efac;border-radius:12px;padding:36px;text-align:center;cursor:pointer;background:#f0fdf4;transition:all .2s;"
                        onclick="document.getElementById('excelFileInput').click()" ondragover="dragOver(event)"
                        ondragleave="dragLeave(event)" ondrop="dropFile(event)">
                        <i class="bi bi-cloud-upload" style="font-size:2.5rem;color:#22c55e;"></i>
                        <div style="font-size:.90rem;font-weight:600;color:#15803d;margin-top:8px;">Arrastra tu archivo
                            Excel aquÃ­</div>
                        <div style="font-size:.76rem;color:#6b7280;margin-top:4px;">o haz clic para seleccionar â€“ .xlsx,
                            .xls</div>
                        <input type="file" id="excelFileInput" accept=".xlsx,.xls" style="display:none;"
                            onchange="leerExcel(this.files[0])">
                    </div>

                    <!-- Nombre archivo seleccionado -->
                    <div id="excelFileName"
                        style="display:none;text-align:center;margin-top:10px;font-size:.78rem;color:#374151;">
                        <i class="bi bi-file-earmark-excel-fill me-1" style="color:#16a34a;"></i><span
                            id="excelFileNameText"></span>
                    </div>

                    <!-- Preview resumen (agrupado por cliente) -->
                    <div id="excelPreviewWrapper" style="display:none;margin-top:20px;">
                        <div style="font-size:.82rem;font-weight:600;color:#1a2940;margin-bottom:8px;">
                            <i class="bi bi-eye me-1" style="color:#16a34a;"></i>
                            Vista previa â€” <span id="excelResumenCount"></span>
                        </div>

                        <!-- Tabs: resumen | detalle -->
                        <ul class="nav nav-tabs mb-2" style="font-size:.78rem;">
                            <li class="nav-item">
                                <button class="nav-link active" id="tabResumenBtn" onclick="mostrarTabExcel('resumen')">
                                    <i class="bi bi-people me-1"></i>Por Cliente
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" id="tabDetalleBtn" onclick="mostrarTabExcel('detalle')">
                                    <i class="bi bi-list-ul me-1"></i>Documentos completos
                                </button>
                            </li>
                        </ul>

                        <!-- Tab resumen -->
                        <div id="tabResumen">
                            <div style="max-height:280px;overflow-y:auto;border-radius:8px;border:1px solid #e5eaf0;">
                                <table class="table table-sm mb-0" style="font-size:.78rem;">
                                    <thead style="position:sticky;top:0;">
                                        <tr style="background:#f8fafc;">
                                            <th
                                                style="padding:8px 12px;font-size:.68rem;color:#8fa3bc;font-weight:600;text-transform:uppercase;">
                                                #</th>
                                            <th
                                                style="padding:8px 12px;font-size:.68rem;color:#8fa3bc;font-weight:600;text-transform:uppercase;">
                                                Cliente</th>
                                            <th
                                                style="padding:8px 12px;font-size:.68rem;color:#8fa3bc;font-weight:600;text-transform:uppercase;text-align:center;">
                                                Docs</th>
                                            <th
                                                style="padding:8px 12px;font-size:.68rem;color:#8fa3bc;font-weight:600;text-transform:uppercase;text-align:right;">
                                                Total Deuda</th>
                                        </tr>
                                    </thead>
                                    <tbody id="excelPreviewResumen"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab detalle (todos los docs) -->
                        <div id="tabDetalle" style="display:none;">
                            <div style="max-height:280px;overflow-y:auto;border-radius:8px;border:1px solid #e5eaf0;">
                                <table class="table table-sm mb-0" style="font-size:.78rem;">
                                    <thead style="position:sticky;top:0;">
                                        <tr style="background:#f8fafc;">
                                            <th
                                                style="padding:8px 12px;font-size:.68rem;color:#8fa3bc;font-weight:600;text-transform:uppercase;">
                                                Tipo Doc.</th>
                                            <th
                                                style="padding:8px 12px;font-size:.68rem;color:#8fa3bc;font-weight:600;text-transform:uppercase;">
                                                Fecha</th>
                                            <th
                                                style="padding:8px 12px;font-size:.68rem;color:#8fa3bc;font-weight:600;text-transform:uppercase;">
                                                NÂ° Doc.</th>
                                            <th
                                                style="padding:8px 12px;font-size:.68rem;color:#8fa3bc;font-weight:600;text-transform:uppercase;">
                                                Cliente</th>
                                            <th
                                                style="padding:8px 12px;font-size:.68rem;color:#8fa3bc;font-weight:600;text-transform:uppercase;">
                                                Rut</th>
                                            <th
                                                style="padding:8px 12px;font-size:.68rem;color:#8fa3bc;font-weight:600;text-transform:uppercase;text-align:right;">
                                                Total</th>
                                            <th
                                                style="padding:8px 12px;font-size:.68rem;color:#16a34a;font-weight:600;text-transform:uppercase;text-align:right;">
                                                Pagado</th>
                                            <th
                                                style="padding:8px 12px;font-size:.68rem;color:#dc2626;font-weight:600;text-transform:uppercase;text-align:right;">
                                                Impago</th>
                                        </tr>
                                    </thead>
                                    <tbody id="excelPreviewDetalle"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer" style="border-top:1px solid #f0f4f9;gap:8px;">
                    <button class="btn btn-sm"
                        style="background:#f0f4f9;color:#5a7394;border-radius:8px;font-size:.82rem;"
                        onclick="limpiarExcel()">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Limpiar
                    </button>
                    <button class="btn btn-sm" style="background:#f0f4f9;color:#5a7394;border-radius:8px;"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-sm" id="btnImportarExcel"
                        style="background:#16a34a;color:#fff;border-radius:8px;font-size:.82rem;" disabled
                        onclick="importarExcel()">
                        <i class="bi bi-upload me-1"></i>Importar Clientes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
         MODAL – Confirmar Eliminar Cliente (Cobrar)
    ═══════════════════════════════════════════ -->
    <div class="modal fade" id="modalConfirmarEliminar" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-sm">
            <div class="modal-content" style="border-radius:14px;overflow:hidden;">
                <div class="modal-header py-3" style="background:#dc2626;">
                    <h6 class="modal-title text-white">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Confirmar eliminación
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
                </div>
                <div class="modal-body p-4">
                    <div style="font-size:.82rem;color:#374151;margin-bottom:10px;">
                        ¿Está seguro que desea eliminar al cliente:
                    </div>
                    <div id="delConfirmNombre" style="font-size:.95rem;font-weight:700;color:#1a2940;margin-bottom:12px;"></div>
                    <div id="delConfirmDetalle" style="font-size:.78rem;line-height:1.5;"></div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f0f4f9;gap:8px;">
                    <button class="btn btn-sm" style="background:#f0f4f9;color:#5a7394;border-radius:8px;"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-sm" id="btnConfirmarEliminarOk"
                        style="background:#dc2626;color:#fff;border-radius:8px;"
                        onclick="confirmarEliminarCobrar()">
                        <i class="bi bi-trash3 me-1"></i>Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Toast -->
    <div id="toastWrapper" style="position:fixed;bottom:24px;right:24px;z-index:9999;"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SheetJS para lectura de Excel (CDN primario + fallback) -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script>window.BD_BASE_URL = "<?= base_url('index.php') ?>";</script>
    <script src="<?= base_url('public/assets/js/BalanceDiario.js') ?>"></script>

</body>

</html>