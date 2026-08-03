<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Manejo de caja – Portal') ?></title>
    <meta name="description" content="Mantenimiento y trazabilidad de cajas (físicas, bancarias y manuales), saldos por fecha y movimientos.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('public/assets/css/admin.css') ?>">
    <style>
        .mc-card-kpi {
            background: #fff; border-radius: 16px; p-3; border: 1px solid #e2e8f0;
            box-shadow: 0 4px 15px rgba(0,0,0,.03); transition: transform .2s;
            display: flex; align-items: center; justify-content: space-between; padding: 20px;
        }
        .mc-card-kpi:hover { transform: translateY(-2px); }
        .mc-icon-kpi {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; font-size: 1.4rem;
        }
        .mc-kpi-blue { background: #e0e7ff; color: #3730a3; }
        .mc-kpi-green { background: #dcfce7; color: #15803d; }
        .mc-kpi-red { background: #fee2e2; color: #b91c1c; }

        .mc-toolbar {
            background: #fff; border-radius: 16px; border: 1px solid #e2e8f0;
            padding: 16px 20px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,.02);
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
        }

        .mc-table-wrap {
            background: #fff; border-radius: 16px; overflow: hidden;
            border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,.04);
        }

        #tablaMovimientos { margin: 0; font-size: .84rem; width: 100%; border-collapse: separate; border-spacing: 0; }
        #tablaMovimientos thead th {
            background: #1e1b4b; color: #c7d2fe; font-weight: 600;
            font-size: .78rem; letter-spacing: .04em; text-transform: uppercase;
            padding: 14px 18px; border: none;
        }
        #tablaMovimientos tbody tr:hover { background: #f8fafc; }
        #tablaMovimientos tbody td { padding: 14px 18px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }

        .badge-ingreso { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; font-size: .75rem; border-radius: 50px; padding: 4px 12px; font-weight: 600; }
        .badge-egreso  { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; font-size: .75rem; border-radius: 50px; padding: 4px 12px; font-weight: 600; }
        .badge-ajuste  { background: #ede9fe; color: #5b21b6; border: 1px solid #ddd6fe; font-size: .75rem; border-radius: 50px; padding: 4px 12px; font-weight: 600; }

        .badge-caja-tipo {
            font-size: .70rem; border-radius: 4px; padding: 2px 6px; font-weight: 600; margin-left: 4px; text-transform: capitalize;
        }
        .bct-manual { background: #eff6ff; color: #1d4ed8; }
        .bct-fisica { background: #f0fdf4; color: #15803d; }
        .bct-bancaria { background: #e0e7ff; color: #3730a3; }

        .btn-add-movimiento {
            background: linear-gradient(135deg, #1e1b4b, #4338ca); color: #fff;
            font-weight: 600; font-size: .85rem; border-radius: 10px; padding: 9px 18px;
            border: none; transition: .2s; display: inline-flex; align-items: center; gap: 6px;
            box-shadow: 0 4px 12px rgba(67,56,202,.25);
        }
        .btn-add-movimiento:hover { background: linear-gradient(135deg, #312e81, #3730a3); color: #fff; transform: translateY(-1px); }

        .btn-ajustar-saldo {
            background: linear-gradient(135deg, #0284c7, #0369a1); color: #fff;
            font-weight: 600; font-size: .85rem; border-radius: 10px; padding: 9px 18px;
            border: none; transition: .2s; display: inline-flex; align-items: center; gap: 6px;
            box-shadow: 0 4px 12px rgba(3,105,161,.25);
        }
        .btn-ajustar-saldo:hover { background: linear-gradient(135deg, #0369a1, #075985); color: #fff; transform: translateY(-1px); }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <?= $this->include('partials/sidebar') ?>

    <div class="main">
        <!-- Topbar -->
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="topbar-mobile-toggle d-lg-none border-0 bg-transparent" onclick="abrirSidebar()">
                    <i class="bi bi-list fs-4 text-dark"></i>
                </button>
                <div>
                    <div class="topbar-title">Manejo de caja</div>
                    <div class="topbar-sub">Cobranza &rsaquo; Gestión de cajas & saldos por fecha</div>
                </div>
            </div>
            <div class="topbar-right">
                <div class="d-flex align-items-center gap-2 bg-light px-3 py-1.5 rounded-3">
                    <div class="u-avatar" style="width:32px;height:32px;font-size:.75rem;">
                        <?= esc(strtoupper(substr($usuario['nombre'] ?? 'A', 0, 1))) ?>
                    </div>
                    <div class="d-none d-sm-block">
                        <div class="fw-semibold text-dark" style="font-size:.80rem;line-height:1.2;">
                            <?= esc(($usuario['nombre'] ?? 'Admin') . ' ' . ($usuario['apellidos'] ?? '')) ?>
                        </div>
                        <div class="text-muted" style="font-size:.68rem;"><?= esc($usuario['perfil'] ?? 'Administrador') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-content p-4">

            <!-- KPIs Informativos -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <div class="mc-card-kpi">
                        <div>
                            <div class="text-muted fw-semibold" style="font-size:.80rem; text-transform:uppercase;">Saldo Total Cajas</div>
                            <div class="fs-4 fw-extrabold text-dark mt-1" id="kpiTotalSaldo">$0</div>
                        </div>
                        <div class="mc-icon-kpi mc-kpi-blue">
                            <i class="bi bi-wallet2"></i>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="mc-card-kpi">
                        <div>
                            <div class="text-muted fw-semibold" style="font-size:.80rem; text-transform:uppercase;">Ingresos de Hoy</div>
                            <div class="fs-4 fw-extrabold text-success mt-1" id="kpiIngresosHoy">+$0</div>
                        </div>
                        <div class="mc-icon-kpi mc-kpi-green">
                            <i class="bi bi-arrow-down-left-circle-fill"></i>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="mc-card-kpi">
                        <div>
                            <div class="text-muted fw-semibold" style="font-size:.80rem; text-transform:uppercase;">Egresos de Hoy</div>
                            <div class="fs-4 fw-extrabold text-danger mt-1" id="kpiEgresosHoy">-$0</div>
                        </div>
                        <div class="mc-icon-kpi mc-kpi-red">
                            <i class="bi bi-arrow-up-right-circle-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Toolbar de Filtros -->
            <div class="mc-toolbar">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div>
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size:.75rem; text-transform:uppercase;">Filtrar por Caja</label>
                        <select id="selectFiltroCaja" class="form-select form-select-sm" style="border-radius:10px; width:220px; font-size:.84rem;" onchange="cargarMovimientos()">
                            <option value="">Todas las cajas</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size:.75rem; text-transform:uppercase;">Fecha Desde</label>
                        <input type="date" id="inputFechaInicio" class="form-control form-control-sm" style="border-radius:10px; font-size:.84rem;" onchange="cargarMovimientos()">
                    </div>
                    <div>
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size:.75rem; text-transform:uppercase;">Fecha Hasta</label>
                        <input type="date" id="inputFechaFin" class="form-control form-control-sm" style="border-radius:10px; font-size:.84rem;" onchange="cargarMovimientos()">
                    </div>
                    <div class="align-self-end">
                        <button class="btn btn-sm btn-outline-secondary" style="border-radius:10px; font-size:.84rem;" onclick="resetFiltros()">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Limpiar
                        </button>
                    </div>
                </div>
                <div class="align-self-end d-flex gap-2">
                    <button class="btn-ajustar-saldo" onclick="abrirModalAjuste()">
                        <i class="bi bi-sliders"></i> Cuadratura / Modificar Saldo
                    </button>
                    <button class="btn-add-movimiento" onclick="abrirModalMovimiento()">
                        <i class="bi bi-plus-circle-fill"></i> Registrar movimiento
                    </button>
                </div>
            </div>

            <!-- Grilla de Movimientos -->
            <div class="mc-table-wrap">
                <div class="table-responsive">
                    <table class="table align-middle" id="tablaMovimientos">
                        <thead>
                            <tr>
                                <th style="width: 50px; text-align: center;">#</th>
                                <th style="text-align: center;">Fecha</th>
                                <th style="text-align: left;">Caja</th>
                                <th style="text-align: center;">Tipo Movimiento</th>
                                <th style="text-align: right;">Monto</th>
                                <th style="text-align: right;">Saldo Resultante</th>
                                <th style="text-align: left;">Comentario / Motivo</th>
                                <th style="text-align: left;">Usuario Registrador</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyMovimientos">
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <span class="spinner-border spinner-border-sm me-2"></span>Cargando movimientos...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL REGISTRAR MOVIMIENTO DE CAJA
════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalMovimiento" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content" style="border-radius:16px; overflow:hidden; border:none; box-shadow: 0 20px 50px rgba(0,0,0,.2);">
            <div class="modal-header py-3" style="background: linear-gradient(135deg, #1e1b4b, #4338ca); color:#fff; border:none;">
                <h6 class="modal-title d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-down-up"></i> Registrar Movimiento de Caja
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
            </div>
            <form id="formMovimiento" onsubmit="guardarMovimiento(event)">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.82rem; color:#475569;">Caja Destino <span class="text-danger">*</span></label>
                        <select id="movCajaId" name="caja_id" class="form-select" style="border-radius:10px; font-size:.85rem;" required>
                            <option value="">-- Seleccione una caja --</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.82rem; color:#475569;">Tipo de Movimiento <span class="text-danger">*</span></label>
                        <select id="movTipo" name="tipo" class="form-select" style="border-radius:10px; font-size:.85rem;" required>
                            <option value="ingreso">🟢 Ingreso / Depósito / Cuadre (+)</option>
                            <option value="egreso">🔴 Egreso / Retiro / Gasto (-)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.82rem; color:#475569;">Monto ($ CLPs) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" id="movMonto" name="monto" class="form-control" placeholder="Ej. 50000" style="border-radius:10px; font-size:.85rem;" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.82rem; color:#475569;">Fecha del Movimiento <span class="text-danger">*</span></label>
                        <input type="date" id="movFecha" name="fecha" class="form-control" style="border-radius:10px; font-size:.85rem;" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold" style="font-size:.82rem; color:#475569;">Comentario / Observaciones <span class="text-danger">*</span></label>
                        <textarea id="movComentario" name="comentario" class="form-control" rows="3" placeholder="Ej. Depósito inicial por cierre de ventas día..." style="border-radius:10px; font-size:.83rem;" required></textarea>
                    </div>
                </div>
                <div class="modal-footer py-3" style="border-top:1px solid #f1f5f9;">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal" style="border-radius:8px;">Cancelar</button>
                    <button type="submit" id="btnGuardarMovimiento" class="btn btn-sm" style="background:#4338ca; color:#fff; border-radius:8px; font-weight:600; padding: 6px 18px;">
                        <i class="bi bi-check-lg me-1"></i>Guardar movimiento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL CUADRATURA / AJUSTAR SALDO DIRECTO
════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalAjustarSaldo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content" style="border-radius:16px; overflow:hidden; border:none; box-shadow: 0 20px 50px rgba(0,0,0,.2);">
            <div class="modal-header py-3" style="background: linear-gradient(135deg, #0284c7, #0369a1); color:#fff; border:none;">
                <h6 class="modal-title d-flex align-items-center gap-2">
                    <i class="bi bi-sliders"></i> Cuadratura / Modificar Saldo de Caja
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
            </div>
            <form id="formAjustarSaldo" onsubmit="guardarAjusteSaldo(event)">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.82rem; color:#475569;">Caja a Cuadrar / Modificar <span class="text-danger">*</span></label>
                        <select id="ajusteCajaId" name="caja_id" class="form-select" style="border-radius:10px; font-size:.85rem;" onchange="actualizarSaldoActualInfo()" required>
                            <option value="">-- Seleccione una caja --</option>
                        </select>
                    </div>

                    <div class="p-3 mb-3 bg-light rounded-3 border" id="infoSaldoActualBox" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted" style="font-size:.80rem;">Saldo Actual registrado:</span>
                            <span class="fw-bold text-dark" style="font-size:.95rem;" id="lblSaldoActualBox">$0</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.82rem; color:#475569;">Nuevo Saldo Real ($ CLPs) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" id="ajusteNuevoSaldo" name="nuevo_saldo" class="form-control" placeholder="Ej. 1500000" style="border-radius:10px; font-size:.85rem;" required>
                        <div class="form-text" style="font-size:.75rem;">Ingrese el saldo contado/arqueado para la cuadratura diaria.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.82rem; color:#475569;">Fecha de la Cuadratura <span class="text-danger">*</span></label>
                        <input type="date" id="ajusteFecha" name="fecha" class="form-control" style="border-radius:10px; font-size:.85rem;" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold" style="font-size:.82rem; color:#475569;">Comentario / Observación de Cuadratura <span class="text-danger">*</span></label>
                        <textarea id="ajusteComentario" name="comentario" class="form-control" rows="2" placeholder="Ej. Cuadratura diaria de caja al término del día..." style="border-radius:10px; font-size:.83rem;" required></textarea>
                    </div>
                </div>
                <div class="modal-footer py-3" style="border-top:1px solid #f1f5f9;">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal" style="border-radius:8px;">Cancelar</button>
                    <button type="submit" id="btnGuardarAjuste" class="btn btn-sm" style="background:#0369a1; color:#fff; border-radius:8px; font-weight:600; padding: 6px 18px;">
                        <i class="bi bi-check-lg me-1"></i>Aplicar cuadratura
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast notifications -->
<div id="toastWrapper" style="position:fixed; bottom:24px; right:24px; z-index:9999;"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    window.BASE_URL = "<?= base_url() ?>";
    let cajasGlobal = [];
    let movimientosGlobal = [];

    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('movFecha').value = new Date().toISOString().substring(0, 10);
        cargarMovimientos();
    });

    function fmtCLP(monto) {
        return '$' + Math.round(monto || 0).toLocaleString('es-CL');
    }

    function showToast(msg, tipo = 'success') {
        const toastId = 'toast_' + Date.now();
        const bg = tipo === 'success' ? '#10b981' : (tipo === 'danger' ? '#ef4444' : '#3b82f6');
        const icon = tipo === 'success' ? 'check-circle' : 'exclamation-circle';

        const html = `
            <div id="${toastId}" class="toast align-items-center text-white border-0 show" style="background:${bg}; border-radius:10px; box-shadow:0 10px 30px rgba(0,0,0,.2); margin-top:8px;">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center gap-2 style-sub" style="font-size:.84rem;">
                        <i class="bi bi-${icon}-fill"></i> <span>${msg}</span>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>`;
        
        document.getElementById('toastWrapper').insertAdjacentHTML('beforeend', html);
        setTimeout(() => {
            const el = document.getElementById(toastId);
            if (el) el.remove();
        }, 4000);
    }

    async function cargarMovimientos() {
        const tbody = document.getElementById('tbodyMovimientos');
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Cargando...</td></tr>`;

        const cajaId = document.getElementById('selectFiltroCaja').value;
        const fechaIni = document.getElementById('inputFechaInicio').value;
        const fechaFin = document.getElementById('inputFechaFin').value;

        const params = new URLSearchParams();
        if (cajaId) params.append('caja_id', cajaId);
        if (fechaIni) params.append('fecha_inicio', fechaIni);
        if (fechaFin) params.append('fecha_fin', fechaFin);

        try {
            const res = await fetch(BASE_URL + 'cobranza/manejo-caja/listar?' + params.toString());
            const data = await res.json();

            if (res.ok && data.success) {
                cajasGlobal = data.cajas || [];
                movimientosGlobal = data.movimientos || [];

                poblarSelectCajas();
                renderKPIs(data.resumen);
                renderTabla();
            } else {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger"><i class="bi bi-exclamation-triangle me-1"></i>${data.message || 'Error al obtener movimientos'}</td></tr>`;
            }
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Error de conexión con el servidor</td></tr>`;
        }
    }

    function poblarSelectCajas() {
        const selectFilter = document.getElementById('selectFiltroCaja');
        const selectModal  = document.getElementById('movCajaId');
        const selectAjuste = document.getElementById('ajusteCajaId');

        const currentFilterVal = selectFilter.value;
        const currentModalVal  = selectModal.value;
        const currentAjusteVal = selectAjuste.value;

        selectFilter.innerHTML = '<option value="">Todas las cajas</option>';
        selectModal.innerHTML  = '<option value="">-- Seleccione una caja --</option>';
        selectAjuste.innerHTML = '<option value="">-- Seleccione una caja --</option>';

        cajasGlobal.forEach(c => {
            const label = `${c.nombre} (${c.tipo})`;
            selectFilter.insertAdjacentHTML('beforeend', `<option value="${c.id}">${label}</option>`);
            selectModal.insertAdjacentHTML('beforeend', `<option value="${c.id}">${label} - Saldo: ${fmtCLP(c.saldo_actual)}</option>`);
            selectAjuste.insertAdjacentHTML('beforeend', `<option value="${c.id}">${label} - Saldo actual: ${fmtCLP(c.saldo_actual)}</option>`);
        });

        selectFilter.value = currentFilterVal;
        selectModal.value  = currentModalVal;
        selectAjuste.value = currentAjusteVal;
    }

    function renderKPIs(resumen) {
        if (!resumen) return;
        document.getElementById('kpiTotalSaldo').textContent  = fmtCLP(resumen.total_saldo);
        document.getElementById('kpiIngresosHoy').textContent = '+' + fmtCLP(resumen.ingresos_hoy);
        document.getElementById('kpiEgresosHoy').textContent  = '-' + fmtCLP(resumen.egresos_hoy);
    }

    function renderTabla() {
        const tbody = document.getElementById('tbodyMovimientos');

        if (!movimientosGlobal.length) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-muted"><i class="bi bi-inbox text-secondary fs-3 d-block mb-2"></i>No hay registros para el filtro seleccionado</td></tr>`;
            return;
        }

        tbody.innerHTML = movimientosGlobal.map((m, i) => {
            const isIngreso = m.tipo === 'ingreso';
            const isAjuste  = m.tipo === 'ajuste';

            let badgeTipo = '';
            let montoSigno = '';
            let montoClass = '';

            if (isAjuste) {
                badgeTipo  = `<span class="badge-ajuste"><i class="bi bi-sliders me-1"></i>Cuadratura</span>`;
                montoSigno = `${fmtCLP(m.monto)}`;
                montoClass = 'text-primary fw-bold';
            } else if (isIngreso) {
                badgeTipo  = `<span class="badge-ingreso"><i class="bi bi-arrow-down-left me-1"></i>Ingreso</span>`;
                montoSigno = `+${fmtCLP(m.monto)}`;
                montoClass = 'text-success';
            } else {
                badgeTipo  = `<span class="badge-egreso"><i class="bi bi-arrow-up-right me-1"></i>Egreso</span>`;
                montoSigno = `-${fmtCLP(m.monto)}`;
                montoClass = 'text-danger';
            }

            const cTipo = m.caja_tipo || 'manual';
            const bctClass = cTipo === 'manual' ? 'bct-manual' : (cTipo === 'bancaria' ? 'bct-bancaria' : 'bct-fisica');

            return `
                <tr>
                    <td style="color:#94a3b8; font-weight:500; text-align:center;">${i + 1}</td>
                    <td style="text-align:center; font-size:.82rem; font-weight:600; color:#334155;">${m.fecha}</td>
                    <td>
                        <span class="fw-bold text-dark">${m.caja_nombre || 'Caja #' + m.caja_id}</span>
                        <span class="badge-caja-tipo ${bctClass}">${cTipo}</span>
                    </td>
                    <td style="text-align:center;">${badgeTipo}</td>
                    <td style="text-align:right; font-weight:700;" class="${montoClass}">${montoSigno}</td>
                    <td style="text-align:right; font-weight:700; color:#1e1b4b;">${fmtCLP(m.saldo_nuevo)}</td>
                    <td style="font-size:.82rem; color:#475569;">${m.comentario || '—'}</td>
                    <td>
                        <div class="fw-semibold text-dark" style="font-size:.82rem;"><i class="bi bi-person-circle me-1 text-primary"></i>${m.usuario_nombre || 'Administrador'}</div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function resetFiltros() {
        document.getElementById('selectFiltroCaja').value = '';
        document.getElementById('inputFechaInicio').value = '';
        document.getElementById('inputFechaFin').value = '';
        cargarMovimientos();
    }

    function abrirModalMovimiento() {
        document.getElementById('formMovimiento').reset();
        document.getElementById('movFecha').value = new Date().toISOString().substring(0, 10);
        new bootstrap.Modal(document.getElementById('modalMovimiento')).show();
    }

    function abrirModalAjuste() {
        document.getElementById('formAjustarSaldo').reset();
        document.getElementById('ajusteFecha').value = new Date().toISOString().substring(0, 10);
        document.getElementById('infoSaldoActualBox').style.display = 'none';
        new bootstrap.Modal(document.getElementById('modalAjustarSaldo')).show();
    }

    function actualizarSaldoActualInfo() {
        const cajaId = document.getElementById('ajusteCajaId').value;
        const c = cajasGlobal.find(x => x.id == cajaId);
        const wrap = document.getElementById('infoSaldoActualBox');
        if (c) {
            document.getElementById('lblSaldoActualBox').textContent = fmtCLP(c.saldo_actual);
            document.getElementById('ajusteNuevoSaldo').value = c.saldo_actual || 0;
            wrap.style.display = '';
        } else {
            wrap.style.display = 'none';
        }
    }

    async function guardarMovimiento(e) {
        e.preventDefault();
        const btn = document.getElementById('btnGuardarMovimiento');
        const origText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';

        const payload = {
            caja_id: document.getElementById('movCajaId').value,
            tipo: document.getElementById('movTipo').value,
            monto: parseFloat(document.getElementById('movMonto').value || 0),
            fecha: document.getElementById('movFecha').value,
            comentario: document.getElementById('movComentario').value,
        };

        try {
            const res = await fetch(BASE_URL + 'cobranza/manejo-caja/guardar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (res.ok && data.success) {
                showToast(data.message || 'Movimiento registrado con éxito', 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalMovimiento')).hide();
                await cargarMovimientos();
            } else {
                showToast(data.message || 'Error al registrar movimiento', 'danger');
            }
        } catch (err) {
            showToast('Error de comunicación con el servidor', 'danger');
        } finally {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
    }

    async function guardarAjusteSaldo(e) {
        e.preventDefault();
        const btn = document.getElementById('btnGuardarAjuste');
        const origText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';

        const payload = {
            caja_id: document.getElementById('ajusteCajaId').value,
            nuevo_saldo: parseFloat(document.getElementById('ajusteNuevoSaldo').value || 0),
            fecha: document.getElementById('ajusteFecha').value,
            comentario: document.getElementById('ajusteComentario').value,
        };

        try {
            const res = await fetch(BASE_URL + 'cobranza/manejo-caja/ajustar-saldo', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (res.ok && data.success) {
                showToast(data.message || 'Cuadratura aplicada con éxito', 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalAjustarSaldo')).hide();
                await cargarMovimientos();
            } else {
                showToast(data.message || 'Error al guardar la cuadratura', 'danger');
            }
        } catch (err) {
            showToast('Error de comunicación con el servidor', 'danger');
        } finally {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
    }
</script>
</body>
</html>
