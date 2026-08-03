<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Cuenta bancaria / Efectivo – Portal') ?></title>
    <meta name="description" content="Gestión de Cuentas Bancarias y Cajas (manual y física).">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('public/assets/css/admin.css') ?>">
    <style>
        .cb-toolbar {
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 12px; margin-bottom: 20px;
        }
        .cb-search-wrap {
            position: relative; width: 280px;
        }
        .cb-search-wrap i {
            position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
            color: #94a3b8; font-size: .88rem;
        }
        .cb-search {
            border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 8px 14px 8px 36px;
            font-size: .83rem; outline: none; width: 100%; transition: .2s;
        }
        .cb-search:focus { border-color: var(--accent, #4338ca); box-shadow: 0 0 0 3px rgba(67,56,202,.1); }

        .cb-filter-tabs { display: flex; gap: 8px; }
        .cb-tab {
            padding: 7px 18px; border-radius: 50px; font-size: .82rem; font-weight: 600;
            border: 1.5px solid #e2e8f0; background: #fff; color: #5a7394; cursor: pointer; transition: .2s;
        }
        .cb-tab:hover { background: #f8fafc; border-color: #cbd5e1; }
        .cb-tab.active { background: #1e1b4b; color: #fff; border-color: #1e1b4b; }

        .btn-add-caja {
            background: linear-gradient(135deg, #1e1b4b, #4338ca); color: #fff;
            font-weight: 600; font-size: .85rem; border-radius: 10px; padding: 9px 18px;
            border: none; transition: .2s; display: inline-flex; align-items: center; gap: 6px;
            box-shadow: 0 4px 12px rgba(67,56,202,.25);
        }
        .btn-add-caja:hover { background: linear-gradient(135deg, #312e81, #3730a3); color: #fff; transform: translateY(-1px); }

        /* Grid / Tabla */
        .cb-table-wrap {
            background: #fff; border-radius: 16px; overflow: hidden;
            border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,.04);
        }
        #tablaCajas { margin: 0; font-size: .84rem; width: 100%; border-collapse: separate; border-spacing: 0; }
        #tablaCajas thead th {
            background: #1e1b4b; color: #c7d2fe; font-weight: 600;
            font-size: .78rem; letter-spacing: .04em; text-transform: uppercase;
            padding: 14px 18px; border: none;
        }
        #tablaCajas tbody tr { transition: .15s; }
        #tablaCajas tbody tr:hover { background: #f8fafc; }
        #tablaCajas tbody td { padding: 14px 18px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }

        /* Badges de Tipo */
        .badge-tipo {
            display: inline-flex; align-items: center; gap: 5px; font-size: .75rem; font-weight: 600;
            border-radius: 50px; padding: 4px 12px; text-transform: capitalize;
        }
        .badge-manual { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .badge-fisica { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-bancaria { background: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe; }

        /* Badges de Estado */
        .badge-estado-activa { background: #dcfce7; color: #166534; font-size: .72rem; border-radius: 50px; padding: 3px 10px; font-weight: 600; }
        .badge-estado-inactiva { background: #fee2e2; color: #991b1b; font-size: .72rem; border-radius: 50px; padding: 3px 10px; font-weight: 600; }

        /* Botones Acción */
        .cb-action-btn {
            width: 32px; height: 32px; border-radius: 8px; border: none;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: .85rem; cursor: pointer; transition: .15s; margin-right: 4px;
        }
        .cb-btn-ver { background: #e0f2fe; color: #0369a1; }
        .cb-btn-ver:hover { background: #0369a1; color: #fff; }
        .cb-btn-editar { background: #fff3e0; color: #e65100; }
        .cb-btn-editar:hover { background: #e65100; color: #fff; }
        .cb-btn-eliminar { background: #fee2e2; color: #dc2626; }
        .cb-btn-eliminar:hover { background: #dc2626; color: #fff; }

        .cb-empty {
            text-align: center; padding: 48px 16px; color: #94a3b8; font-size: .88rem;
        }
    </style>
</head>
<body>

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
                    <div class="topbar-title">Cuenta bancaria / Efectivo</div>
                    <div class="topbar-sub">Administración &rsaquo; Cuentas bancarias</div>
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

            <!-- Toolbar -->
            <div class="cb-toolbar">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="cb-search-wrap">
                        <i class="bi bi-search"></i>
                        <input type="text" id="inputBuscarCaja" class="cb-search" placeholder="Buscar caja..." onkeyup="filtrarCajas()">
                    </div>
                    <div class="cb-filter-tabs">
                        <button class="cb-tab active" onclick="filtrarTipo('todos', this)">Todas</button>
                        <button class="cb-tab" onclick="filtrarTipo('manual', this)"><i class="bi bi-journal-text me-1"></i>Cajas manuales</button>
                        <button class="cb-tab" onclick="filtrarTipo('fisica', this)"><i class="bi bi-safe2 me-1"></i>Cajas físicas</button>
                        <button class="cb-tab" onclick="filtrarTipo('bancaria', this)"><i class="bi bi-bank me-1"></i>Cajas bancarias</button>
                    </div>
                </div>
                <div>
                    <button class="btn-add-caja" onclick="abrirModalAgregarCaja()">
                        <i class="bi bi-plus-circle-fill"></i> Agregar caja
                    </button>
                </div>
            </div>

            <!-- Grilla / Tabla de Cajas -->
            <div class="cb-table-wrap">
                <div class="table-responsive">
                    <table class="table align-middle" id="tablaCajas">
                        <thead>
                            <tr>
                                <th style="width: 50px; text-align: center;">#</th>
                                <th style="text-align: left;">Nombre Caja</th>
                                <th style="text-align: center;">Tipo de Caja</th>
                                <th style="text-align: right;">Saldo Actual</th>
                                <th style="text-align: center;">Estado</th>
                                <th style="text-align: center; width: 140px; white-space: nowrap;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyCajas">
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>Cargando cajas...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL AGREGAR / EDITAR CAJA
════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalCaja" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content" style="border-radius:16px; overflow:hidden; border:none; box-shadow: 0 20px 50px rgba(0,0,0,.2);">
            <div class="modal-header py-3" style="background: linear-gradient(135deg, #1e1b4b, #4338ca); color:#fff; border:none;">
                <h6 class="modal-title d-flex align-items-center gap-2" id="modalCajaTitle">
                    <i class="bi bi-safe-fill"></i> Agregar caja
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
            </div>
            <form id="formCaja" onsubmit="guardarCaja(event)">
                <input type="hidden" id="cajaId" name="id" value="">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold style-sub" style="font-size:.82rem; color:#475569;">Nombre caja <span class="text-danger">*</span></label>
                        <input type="text" id="cajaNombre" name="nombre" class="form-control" placeholder="Ej. Caja Chica Ventas" style="border-radius:10px; font-size:.85rem;" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold style-sub" style="font-size:.82rem; color:#475569;">Tipo de caja <span class="text-danger">*</span></label>
                        <select id="cajaTipo" name="tipo" class="form-select" style="border-radius:10px; font-size:.85rem;" required>
                            <option value="manual">Caja manual</option>
                            <option value="fisica">Caja física</option>
                            <option value="bancaria">Caja bancaria</option>
                        </select>
                    </div>

                    <div class="mb-3" id="groupSaldoInicial">
                        <label class="form-label fw-semibold style-sub" style="font-size:.82rem; color:#475569;">Saldo inicial ($ CLPs)</label>
                        <input type="number" step="0.01" min="0" id="cajaSaldoInicial" name="saldo_inicial" class="form-control" placeholder="0" style="border-radius:10px; font-size:.85rem;" value="0">
                    </div>

                    <div class="mb-3" id="groupEstado" style="display:none;">
                        <label class="form-label fw-semibold style-sub" style="font-size:.82rem; color:#475569;">Estado</label>
                        <select id="cajaEstado" name="estado" class="form-select" style="border-radius:10px; font-size:.85rem;">
                            <option value="activa">Activa</option>
                            <option value="inactiva">Inactiva</option>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold style-sub" style="font-size:.82rem; color:#475569;">Observaciones (Opcional)</label>
                        <textarea id="cajaObservaciones" name="observaciones" class="form-control" rows="2" placeholder="Notas relativas a esta caja..." style="border-radius:10px; font-size:.83rem;"></textarea>
                    </div>
                </div>
                <div class="modal-footer py-3" style="border-top:1px solid #f1f5f9;">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal" style="border-radius:8px;">Cancelar</button>
                    <button type="submit" id="btnGuardarCaja" class="btn btn-sm" style="background:#4338ca; color:#fff; border-radius:8px; font-weight:600; padding: 6px 18px;">
                        <i class="bi bi-check-lg me-1"></i>Guardar caja
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL VISUALIZAR CAJA
════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalVerCaja" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
        <div class="modal-content" style="border-radius:16px; overflow:hidden; border:none; box-shadow:0 20px 50px rgba(0,0,0,.2);">
            <div class="modal-header py-3" style="background: linear-gradient(135deg, #0284c7, #0369a1); color:#fff; border:none;">
                <h6 class="modal-title d-flex align-items-center gap-2">
                    <i class="bi bi-eye-fill"></i> Detalle de la Caja
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-3">
                    <div style="width:50px; height:50px; border-radius:50%; background:#e0f2fe; color:#0284c7; display:inline-flex; align-items:center; justify-content:center; font-size:1.5rem; margin-bottom:8px;">
                        <i class="bi bi-safe2-fill"></i>
                    </div>
                    <h5 class="fw-bold mb-1" id="verNombre">--</h5>
                    <div id="verBadgeTipo"></div>
                </div>

                <div class="p-3" style="background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0;">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted" style="font-size:.82rem;">Saldo Actual:</span>
                        <span class="fw-bold text-success" style="font-size:.95rem;" id="verSaldoActual">$0</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted" style="font-size:.82rem;">Saldo Inicial:</span>
                        <span class="fw-semibold text-dark" style="font-size:.85rem;" id="verSaldoInicial">$0</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted" style="font-size:.82rem;">Estado:</span>
                        <span id="verEstado">--</span>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="text-muted" style="font-size:.82rem;">Fecha Registro:</span>
                        <span class="fw-semibold text-secondary" style="font-size:.82rem;" id="verFecha">--</span>
                    </div>
                </div>

                <div class="mt-3" id="verObsWrap" style="display:none;">
                    <div class="fw-semibold text-secondary mb-1" style="font-size:.78rem; text-transform:uppercase;">Observaciones:</div>
                    <div class="p-2 bg-light rounded text-dark" style="font-size:.82rem;" id="verObservaciones"></div>
                </div>
            </div>
            <div class="modal-footer py-2" style="border-top:1px solid #f1f5f9;">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" style="border-radius:8px;">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL ELIMINAR CAJA
════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalEliminarCaja" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content" style="border-radius:16px; overflow:hidden; border:none;">
            <div class="modal-body p-4 text-center">
                <div style="width:54px; height:54px; border-radius:50%; background:#fee2e2; color:#dc2626; display:inline-flex; align-items:center; justify-content:center; font-size:1.6rem; margin-bottom:12px;">
                    <i class="bi bi-trash3-fill"></i>
                </div>
                <h5 class="fw-bold mb-2" style="font-size:1.05rem;">¿Eliminar esta caja?</h5>
                <p class="text-muted mb-3" style="font-size:.83rem;" id="eliminarText">Esta acción no se puede deshacer.</p>
                <div class="d-flex justify-content-center gap-2">
                    <button class="btn btn-sm btn-light" data-bs-dismiss="modal" style="border-radius:8px; padding:6px 16px;">Cancelar</button>
                    <button class="btn btn-sm btn-danger" id="btnConfirmarEliminar" onclick="confirmarEliminarCaja()" style="border-radius:8px; padding:6px 18px; font-weight:600;">
                        Sí, eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast notifications -->
<div id="toastWrapper" style="position:fixed; bottom:24px; right:24px; z-index:9999;"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    window.BASE_URL = "<?= base_url() ?>";
    let cajasList = [];
    let filtroTipoActual = 'todos';
    let idEliminarActual = null;

    document.addEventListener('DOMContentLoaded', function () {
        cargarCajas();
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

    async function cargarCajas() {
        const tbody = document.getElementById('tbodyCajas');
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Cargando...</td></tr>`;

        try {
            const res = await fetch(BASE_URL + 'administracion/cuentas-bancarias/listar');
            const data = await res.json();

            if (res.ok && data.success) {
                cajasList = data.data || [];
                renderTabla();
            } else {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger"><i class="bi bi-exclamation-triangle me-1"></i>${data.message || 'Error al obtener cajas'}</td></tr>`;
            }
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger"><i class="bi bi-exclamation-triangle me-1"></i>No se pudo conectar con el servidor</td></tr>`;
        }
    }

    function renderTabla() {
        const tbody = document.getElementById('tbodyCajas');
        const search = (document.getElementById('inputBuscarCaja').value || '').toLowerCase().trim();

        let filtradas = cajasList.filter(c => {
            const matchTipo = filtroTipoActual === 'todos' || c.tipo === filtroTipoActual;
            const matchSearch = !search || c.nombre.toLowerCase().includes(search);
            return matchTipo && matchSearch;
        });

        if (!filtradas.length) {
            tbody.innerHTML = `<tr><td colspan="6" class="cb-empty"><i class="bi bi-inbox text-secondary fs-3 d-block mb-2"></i>No se encontraron cajas registradas</td></tr>`;
            return;
        }

        tbody.innerHTML = filtradas.map((c, i) => {
            const isManual = c.tipo === 'manual';
            const isBancaria = c.tipo === 'bancaria';
            const badgeTipoClass = isManual ? 'badge-manual' : (isBancaria ? 'badge-bancaria' : 'badge-fisica');
            const badgeTipoText = isManual
                ? '<i class="bi bi-journal-text"></i> Caja manual'
                : (isBancaria ? '<i class="bi bi-bank"></i> Caja bancaria' : '<i class="bi bi-safe2"></i> Caja física');
            
            const isActiva = c.estado === 'activa';
            const badgeEstado = isActiva
                ? `<span class="badge-estado-activa"><i class="bi bi-check-circle-fill me-1"></i>Activa</span>`
                : `<span class="badge-estado-inactiva"><i class="bi bi-x-circle-fill me-1"></i>Inactiva</span>`;

            return `
                <tr>
                    <td style="color:#94a3b8; font-weight:500; text-align:center;">${i + 1}</td>
                    <td>
                        <div class="fw-bold text-dark" style="font-size:.88rem;">${c.nombre}</div>
                    </td>
                    <td style="text-align:center;"><span class="badge-tipo ${badgeTipoClass}">${badgeTipoText}</span></td>
                    <td style="text-align:right; font-weight:700;" class="text-indigo">${fmtCLP(c.saldo_actual)}</td>
                    <td style="text-align:center;">${badgeEstado}</td>
                    <td style="text-align:center; white-space:nowrap;">
                        <div class="d-inline-flex align-items-center justify-content-center gap-1">
                            <button class="cb-action-btn cb-btn-ver" onclick="verCaja(${c.id})" title="Visualizar caja"><i class="bi bi-eye"></i></button>
                            <button class="cb-action-btn cb-btn-editar" onclick="editarCaja(${c.id})" title="Editar caja"><i class="bi bi-pencil"></i></button>
                            <button class="cb-action-btn cb-btn-eliminar" onclick="abrirModalEliminar(${c.id}, '${c.nombre.replace(/'/g, "\\'")}')" title="Eliminar caja"><i class="bi bi-trash3"></i></button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function filtrarTipo(tipo, btn) {
        filtroTipoActual = tipo;
        document.querySelectorAll('.cb-tab').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');
        renderTabla();
    }

    function filtrarCajas() {
        renderTabla();
    }

    function abrirModalAgregarCaja() {
        document.getElementById('formCaja').reset();
        document.getElementById('cajaId').value = '';
        document.getElementById('modalCajaTitle').innerHTML = '<i class="bi bi-plus-circle-fill"></i> Agregar caja';
        document.getElementById('groupSaldoInicial').style.display = '';
        document.getElementById('groupEstado').style.display = 'none';
        new bootstrap.Modal(document.getElementById('modalCaja')).show();
    }

    function editarCaja(id) {
        const c = cajasList.find(x => x.id == id);
        if (!c) return;

        document.getElementById('cajaId').value = c.id;
        document.getElementById('cajaNombre').value = c.nombre;
        document.getElementById('cajaTipo').value = c.tipo;
        document.getElementById('cajaSaldoInicial').value = c.saldo_inicial || 0;
        document.getElementById('cajaEstado').value = c.estado || 'activa';
        document.getElementById('cajaObservaciones').value = c.observaciones || '';

        document.getElementById('modalCajaTitle').innerHTML = '<i class="bi bi-pencil-square"></i> Editar caja';
        document.getElementById('groupSaldoInicial').style.display = 'none'; // No modificar saldo inicial al editar
        document.getElementById('groupEstado').style.display = '';

        new bootstrap.Modal(document.getElementById('modalCaja')).show();
    }

    async function guardarCaja(e) {
        e.preventDefault();
        const btn = document.getElementById('btnGuardarCaja');
        const origText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';

        const payload = {
            id: document.getElementById('cajaId').value,
            nombre: document.getElementById('cajaNombre').value,
            tipo: document.getElementById('cajaTipo').value,
            saldo_inicial: parseFloat(document.getElementById('cajaSaldoInicial').value || 0),
            estado: document.getElementById('cajaEstado').value,
            observaciones: document.getElementById('cajaObservaciones').value,
        };

        try {
            const res = await fetch(BASE_URL + 'administracion/cuentas-bancarias/guardar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (res.ok && data.success) {
                showToast(data.message || 'Caja guardada con éxito', 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalCaja')).hide();
                await cargarCajas();
            } else {
                showToast(data.message || 'Error al guardar la caja', 'danger');
            }
        } catch (err) {
            showToast('Error de comunicación con el servidor', 'danger');
        } finally {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
    }

    function verCaja(id) {
        const c = cajasList.find(x => x.id == id);
        if (!c) return;

        document.getElementById('verNombre').textContent = c.nombre;
        
        const isManual = c.tipo === 'manual';
        const isBancaria = c.tipo === 'bancaria';
        const badgeTipoClass = isManual ? 'badge-manual' : (isBancaria ? 'badge-bancaria' : 'badge-fisica');
        const badgeTipoText = isManual ? 'Caja manual' : (isBancaria ? 'Caja bancaria' : 'Caja física');
        document.getElementById('verBadgeTipo').innerHTML = `<span class="badge-tipo ${badgeTipoClass}">${badgeTipoText}</span>`;

        document.getElementById('verSaldoActual').textContent = fmtCLP(c.saldo_actual);
        document.getElementById('verSaldoInicial').textContent = fmtCLP(c.saldo_inicial);
        
        const isActiva = c.estado === 'activa';
        document.getElementById('verEstado').innerHTML = isActiva
            ? `<span class="badge-estado-activa">Activa</span>`
            : `<span class="badge-estado-inactiva">Inactiva</span>`;

        document.getElementById('verFecha').textContent = c.created_at ? c.created_at.substring(0, 10) : '--';

        if (c.observaciones && c.observaciones.trim()) {
            document.getElementById('verObservaciones').textContent = c.observaciones;
            document.getElementById('verObsWrap').style.display = '';
        } else {
            document.getElementById('verObsWrap').style.display = 'none';
        }

        new bootstrap.Modal(document.getElementById('modalVerCaja')).show();
    }

    function abrirModalEliminar(id, nombre) {
        idEliminarActual = id;
        document.getElementById('eliminarText').textContent = `¿Estás seguro de que deseas eliminar la caja "${nombre}"?`;
        new bootstrap.Modal(document.getElementById('modalEliminarCaja')).show();
    }

    async function confirmarEliminarCaja() {
        if (!idEliminarActual) return;
        const btn = document.getElementById('btnConfirmarEliminar');
        btn.disabled = true;

        try {
            const res = await fetch(BASE_URL + 'administracion/cuentas-bancarias/eliminar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: idEliminarActual })
            });
            const data = await res.json();

            if (res.ok && data.success) {
                showToast(data.message || 'Caja eliminada con éxito', 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalEliminarCaja')).hide();
                await cargarCajas();
            } else {
                showToast(data.message || 'No se pudo eliminar la caja', 'danger');
            }
        } catch (err) {
            showToast('Error al procesar la eliminación', 'danger');
        } finally {
            btn.disabled = false;
            idEliminarActual = null;
        }
    }
</script>
</body>
</html>
