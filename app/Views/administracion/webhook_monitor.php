<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor de Webhooks Facto / WooCommerce - Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Global Style Rules -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <style>
        .endpoint-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid #334155;
            border-radius: 16px;
            color: #f8fafc;
            padding: 24px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }
        .code-pill {
            background: #090d16;
            border: 1px solid #1e293b;
            color: #38bdf8;
            font-family: monospace;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 0.88rem;
            word-break: break-all;
        }
        .card-kpi {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #ffffff;
            transition: all 0.2s ease;
        }
        .card-kpi:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.05);
        }
        .payload-pre {
            background: #0f172a;
            color: #38bdf8;
            font-size: 0.76rem;
            padding: 16px;
            border-radius: 10px;
            max-height: 420px;
            overflow-y: auto;
            border: 1px solid #1e293b;
        }
    </style>
</head>
<body>

<?= $this->include('partials/sidebar') ?>

<!-- MAIN CONTENT -->
<div class="main">
    <div class="topbar">
        <div class="d-flex align-items-center gap-2">
            <button class="btn-menu-toggle" onclick="abrirSidebar()" aria-label="Abrir menú">
                <i class="bi bi-list"></i>
            </button>
            <div>
                <div class="topbar-title" id="topbarTitle">
                    <i class="bi bi-lightning-charge-fill me-2 text-warning"></i>Monitor de Webhooks (Localhost)
                </div>
                <div class="topbar-sub" id="topbarSub">Administración &rsaquo; Integración Facto & WooCommerce</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="date-badge">
                <i class="bi bi-broadcast me-1 text-success"></i><span id="webhookStatus">Receptor Activo</span>
            </span>
            <div class="user-badge">
                <div class="ub-avatar">ADM</div>
                <div>
                    <div class="ub-name">Administrador</div>
                    <div class="ub-role">Localhost</div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">

        <!-- Banner Endpoint Configuración -->
        <div class="endpoint-card mb-4">
            <div class="row align-items-center g-3">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                            <i class="bi bi-link-45deg me-1"></i>URL Receptor de Webhook (Localhost)
                        </span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                            <i class="bi bi-check-circle-fill me-1"></i>Listo para pruebas
                        </span>
                    </div>
                    <h5 class="fw-bold mb-2">Configuración para WooCommerce / Facto</h5>
                    <p class="text-slate-400 fs-7 mb-3">
                        Copia esta URL y configúrala en <strong>WooCommerce &rsaquo; Ajustes &rsaquo; Avanzado &rsaquo; Webhooks</strong> o en la app de Facto:
                    </p>
                    <div class="d-flex align-items-center gap-2">
                        <div class="code-pill flex-grow-1" id="urlReceiver">
                            http://localhost/Portal/webhook/receiver
                        </div>
                        <button class="btn btn-outline-light btn-sm px-3" onclick="copiarUrl()" title="Copiar URL">
                            <i class="bi bi-clipboard me-1"></i>Copiar
                        </button>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <button class="btn btn-warning fw-bold px-3 py-2 shadow-sm mb-2 w-100" onclick="abrirModalSimulador()">
                        <i class="bi bi-play-circle-fill me-2"></i>Simular Webhook de Prueba
                    </button>
                    <div class="text-slate-400 fs-8">
                        <i class="bi bi-info-circle me-1"></i>Actualiza automáticos Stock y Precios al recibir eventos.
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjetas de Resumen KPI -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card card-kpi h-100 p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 bg-primary-subtle text-primary rounded-4 fs-4">
                            <i class="bi bi-inbox-fill"></i>
                        </div>
                        <div>
                            <div class="text-muted fs-7 font-uppercase fw-semibold">Total Webhooks Recibidos</div>
                            <h4 class="fw-bold mb-0 text-slate-800" id="kpiTotal">0</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-kpi h-100 p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 bg-success-subtle text-success rounded-4 fs-4">
                            <i class="bi bi-arrow-repeat"></i>
                        </div>
                        <div>
                            <div class="text-muted fs-7 font-uppercase fw-semibold">Última Actualización</div>
                            <h4 class="fw-bold mb-0 text-slate-800 fs-6" id="kpiUltimo">Sin recibos aún</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-kpi h-100 p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 bg-info-subtle text-info rounded-4 fs-4">
                            <i class="bi bi-cpu-fill"></i>
                        </div>
                        <div>
                            <div class="text-muted fs-7 font-uppercase fw-semibold">Estado Receptor HTTP</div>
                            <h4 class="fw-bold mb-0 text-success fs-6"><i class="bi bi-record-fill me-1"></i>Escuchando HTTP POST</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Registros en Vivo -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                <div class="fw-bold text-slate-800">
                    <i class="bi bi-list-stars me-2 text-primary"></i>Historial de Webhooks Recibidos (Tiempo Real)
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-outline-secondary" onclick="cargarLogs()">
                        <i class="bi bi-arrow-clockwise me-1"></i>Refrescar
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="limpiarLogs()">
                        <i class="bi bi-trash me-1"></i>Limpiar Logs
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.82rem;">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Fecha/Hora</th>
                            <th>Origen</th>
                            <th>Tópico / Evento</th>
                            <th>Resumen / Stock</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="logsTableBody">
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <span class="spinner-border spinner-border-sm me-2"></span>Cargando historial de Webhooks...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Modal Ver Payload Completo JSON -->
<div class="modal fade" id="modalPayload" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-slate-900 text-white border-0 py-3">
                <h6 class="modal-title fw-bold" id="payloadTitle">
                    <i class="bi bi-code-square me-2 text-info"></i>Payload JSON del Webhook
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-slate-950 p-3">
                <pre class="payload-pre mb-0" id="payloadContent">Cargando...</pre>
            </div>
        </div>
    </div>
</div>

<!-- Modal Simular Webhook de Prueba -->
<div class="modal fade" id="modalSimulador" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-warning-subtle text-warning-emphasis border-0 py-3">
                <h6 class="modal-title fw-bold">
                    <i class="bi bi-play-fill me-2"></i>Simulador de Webhook (WooCommerce / Facto)
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info py-2 fs-8 mb-3">
                    <i class="bi bi-info-circle-fill me-1"></i>Simula el envío instantáneo de una actualización de producto/stock desde WooCommerce o Facto para verificar la reacción del Portal.
                </div>
                <form id="formSimular">
                    <div class="mb-3">
                        <label class="form-label fw-bold fs-8 text-muted">SKU Producto</label>
                        <input type="text" class="form-control form-control-sm" id="simSku" value="PROCE-01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold fs-8 text-muted">Nombre Producto</label>
                        <input type="text" class="form-control form-control-sm" id="simNombre" value="Papa Natural - Semi rustica 12x12" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold fs-8 text-muted">Stock a Actualizar</label>
                            <input type="number" class="form-control form-control-sm" id="simStock" value="150" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold fs-8 text-muted">Precio ($)</label>
                            <input type="number" class="form-control form-control-sm" id="simPrecio" value="1300" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold fs-8 text-muted">Evento / Tópico</label>
                        <select class="form-select form-select-sm" id="simEvento">
                            <option value="product.updated">product.updated (Actualización de Producto)</option>
                            <option value="stock.updated">stock.updated (Cambio de Stock)</option>
                            <option value="order.created">order.created (Nueva Venta)</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-warning w-100 fw-bold shadow-sm" onclick="ejecutarSimulacion()">
                        <i class="bi bi-send-fill me-2"></i>Enviar Webhook Simulado
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    let logsData = [];

    document.addEventListener('DOMContentLoaded', () => {
        cargarLogs();
        // Polling automático cada 4 segundos
        setInterval(cargarLogs, 4000);
    });

    function copiarUrl() {
        const url = document.getElementById('urlReceiver').innerText.trim();
        navigator.clipboard.writeText(url);
        alert('URL copiada al portapapeles: ' + url);
    }

    async function cargarLogs() {
        try {
            const resp = await fetch('<?= base_url("webhook/logs-json") ?>');
            const data = await resp.json();
            if (data.success) {
                logsData = data.logs || [];
                document.getElementById('kpiTotal').textContent = data.total || 0;
                if (logsData.length > 0) {
                    document.getElementById('kpiUltimo').textContent = logsData[0].created_at;
                }
                renderTablaLogs(logsData);
            }
        } catch (e) {
            console.error('Error al cargar logs de webhooks:', e);
        }
    }

    function renderTablaLogs(logs) {
        const tbody = document.getElementById('logsTableBody');
        if (logs.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-2 d-block mb-2"></i>No se han recibido webhooks aún. Haz clic en "Simular Webhook de Prueba" para verificar el flujo.
            </td></tr>`;
            return;
        }

        tbody.innerHTML = logs.map(l => {
            const topicBadge = `<span class="badge bg-primary-subtle text-primary border border-primary-subtle">${l.topic || 'evento'}</span>`;
            const statusBadge = `<span class="badge bg-success-subtle text-success border border-success-subtle">${l.status || 'procesado'}</span>`;
            return `
                <tr>
                    <td class="fw-bold text-slate-700">#${l.id}</td>
                    <td class="text-muted">${l.created_at}</td>
                    <td class="fw-medium">${l.source || 'WooCommerce'}</td>
                    <td>${topicBadge}</td>
                    <td class="fw-bold text-slate-800">${l.summary || '—'}</td>
                    <td>${statusBadge}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary py-0" onclick="verPayload(${l.id})">
                            <i class="bi bi-eye me-1"></i>Ver Payload
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function verPayload(id) {
        const item = logsData.find(x => parseInt(x.id) === parseInt(id));
        if (!item) return;

        let formatted = item.payload;
        try {
            const obj = JSON.parse(item.payload);
            formatted = JSON.stringify(obj, null, 2);
        } catch (e) {}

        document.getElementById('payloadTitle').innerHTML = `<i class="bi bi-code-square me-2 text-info"></i>Payload JSON Webhook #${id} (${item.topic})`;
        document.getElementById('payloadContent').textContent = formatted;
        
        const modal = new bootstrap.Modal(document.getElementById('modalPayload'));
        modal.show();
    }

    function abrirModalSimulador() {
        const modal = new bootstrap.Modal(document.getElementById('modalSimulador'));
        modal.show();
    }

    async function ejecutarSimulacion() {
        const payload = {
            sku: document.getElementById('simSku').value,
            nombre: document.getElementById('simNombre').value,
            stock: parseInt(document.getElementById('simStock').value) || 0,
            precio: parseFloat(document.getElementById('simPrecio').value) || 0,
            evento: document.getElementById('simEvento').value
        };

        try {
            const resp = await fetch('<?= base_url("webhook/test-simular") ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const res = await resp.json();
            if (res.success) {
                const modalEl = document.getElementById('modalSimulador');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                cargarLogs();
                alert('✓ ' + res.message + '\n\n' + res.summary);
            }
        } catch (e) {
            alert('Error al simular webhook: ' + e.message);
        }
    }

    async function limpiarLogs() {
        if (!confirm('¿Desea limpiar el historial de logs de webhooks en Localhost?')) return;
        try {
            const resp = await fetch('<?= base_url("webhook/limpiar-logs") ?>', { method: 'DELETE' });
            const res = await resp.json();
            if (res.success) {
                cargarLogs();
            }
        } catch (e) {
            alert('Error al limpiar logs: ' + e.message);
        }
    }
</script>
</body>
</html>
