<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --emerald: #10b981;
            --bg-page: #f8fafc;
            --panel-bg: #ffffff;
            --border-color: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-main);
        }

        .header-section {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            padding: 30px 0;
            border-bottom: 4px solid var(--primary);
        }

        .card-custom {
            background-color: var(--panel-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
        }

        .card-header-custom {
            background-color: transparent;
            border-bottom: 1px solid var(--border-color);
            font-weight: 700;
            padding: 16px 20px;
        }

        pre {
            background-color: #0f172a;
            color: #38bdf8;
            padding: 12px;
            border-radius: 8px;
            font-size: 0.78rem;
            max-height: 200px;
            overflow-y: auto;
            margin-bottom: 0;
        }
    </style>
</head>
<body>

    <div class="header-section mb-4">
        <div class="container">
            <div class="d-flex align-items-center gap-3">
                <div style="width:56px; height:56px; background:var(--primary); border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.8rem; color:white;">
                    <i class="bi bi-cpu"></i>
                </div>
                <div>
                    <h1 class="h3 fw-bold mb-0">Portal de Pruebas: Facto ⇄ Tu Pana ⇄ Portal</h1>
                    <p class="text-white-50 mb-0">Simulador de Webhooks y Visor de Consultas API en Tiempo Real.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            
            <!-- Columna de Simulaciones y Filtros -->
            <div class="col-lg-4">
                <!-- Simulador de Webhook -->
                <div class="card card-custom">
                    <div class="card-header-custom">
                        <i class="bi bi-send-fill me-2 text-emerald"></i>Simular Webhook de Emisión
                    </div>
                    <div class="card-body p-3">
                        <form id="formSimulador">
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label style-lbl" style="font-size:0.75rem;">Folio</label>
                                    <input type="number" class="form-control form-control-sm" id="simFolio" value="8577">
                                </div>
                                <div class="col-6">
                                    <label class="form-label style-lbl" style="font-size:0.75rem;">Tipo Documento</label>
                                    <select class="form-select form-select-sm" id="simTipoDoc">
                                        <option value="Factura Electrónica">Factura</option>
                                        <option value="Guía de Despacho">Guía Despacho</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label style-lbl" style="font-size:0.75rem;">Cliente (Nombre)</label>
                                <input type="text" class="form-control form-control-sm" id="simNombre" value="TAPROOM SPA">
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label style-lbl" style="font-size:0.75rem;">RUT Cliente</label>
                                    <input type="text" class="form-control form-control-sm" id="simRut" value="76.453.218-7">
                                </div>
                                <div class="col-6">
                                    <label class="form-label style-lbl" style="font-size:0.75rem;">Monto Total ($)</label>
                                    <input type="number" class="form-control form-control-sm" id="simTotal" value="119000">
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm w-100" style="background:var(--emerald); color:white; font-weight:600;" onclick="enviarSimulacion()">
                                <i class="bi bi-rocket-takeoff me-1"></i> Disparar Webhook
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Buscador de la API real -->
                <div class="card card-custom">
                    <div class="card-header-custom">
                        <i class="bi bi-funnel-fill me-2 text-primary"></i>Consultar API de Tu Pana
                    </div>
                    <div class="card-body p-3">
                        <form id="formFiltros">
                            <div class="mb-2">
                                <label class="form-label" style="font-size:0.75rem;">Cliente (Nombre o RUT)</label>
                                <input type="text" class="form-control form-control-sm" id="filterCliente" placeholder="Ej: TAPROOM o 76.453...">
                            </div>
                            <div class="mb-2">
                                <label class="form-label" style="font-size:0.75rem;">Número Documento (Folio)</label>
                                <input type="text" class="form-control form-control-sm" id="filterNumero" placeholder="Ej: 8577">
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label" style="font-size:0.75rem;">Fecha Desde</label>
                                    <input type="date" class="form-control form-control-sm" id="filterFechaInicio">
                                </div>
                                <div class="col-6">
                                    <label class="form-label" style="font-size:0.75rem;">Fecha Hasta</label>
                                    <input type="date" class="form-control form-control-sm" id="filterFechaFin">
                                </div>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="filterSoloImpagas" checked>
                                <label class="form-check-label" for="filterSoloImpagas" style="font-size:0.78rem; font-weight:500;">Solo Facturas Impagas</label>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary w-100" onclick="buscarDocumentos()">
                                <i class="bi bi-search me-1"></i> Consultar API
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Columna de Resultados y logs -->
            <div class="col-lg-8">
                <!-- Grilla de resultados -->
                <div class="card card-custom">
                    <div class="card-header-custom d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-list-task me-2 text-primary"></i>Resultados de Consulta API</span>
                        <span class="badge bg-secondary" id="countBadge">0 encontrados</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 250px;">
                            <table class="table table-hover mb-0" style="font-size:0.82rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Folio</th>
                                        <th>Fecha</th>
                                        <th>Tipo Doc</th>
                                        <th>Cliente</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-end">Saldo Pendiente</th>
                                        <th class="text-center">Estado</th>
                                    </tr>
                                </thead>
                                <tbody id="resultadosBody">
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            Realiza una consulta a la API de Tu Pana con el formulario de la izquierda.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Visor de Logs del Webhook/API -->
                <div class="card card-custom">
                    <div class="card-header-custom d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-terminal me-2 text-danger"></i>Logs de Actividad (Depuración)</span>
                        <button class="btn btn-xs btn-outline-danger py-0 px-2" style="font-size:0.70rem;" onclick="limpiarLogs()">Limpiar Logs</button>
                    </div>
                    <div class="card-body p-3" id="logsContainer" style="max-height: 250px; overflow-y: auto; font-size: 0.76rem;">
                        <!-- logs injected here -->
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const baseUrl = '<?= esc($base_url) ?>';

        async function enviarSimulacion() {
            const payload = {
                event: 'document.issued',
                timestamp: new Date().toISOString(),
                data: {
                    folio: document.getElementById('simFolio').value,
                    tipo_documento: document.getElementById('simTipoDoc').value,
                    cliente_rut: document.getElementById('simRut').value,
                    cliente_nombre: document.getElementById('simNombre').value,
                    total: parseInt(document.getElementById('simTotal').value),
                    pdf_url: "https://api.tupana.ai/v1/documents/simulado/pdf"
                }
            };

            try {
                const response = await fetch(baseUrl + 'webhook/tupana', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Pana-Event': 'document.issued',
                        'X-Pana-Signature': 'simulated_test_signature'
                    },
                    body: JSON.stringify(payload)
                });
                const res = await response.json();
                
                // Forzar recarga de logs
                setTimeout(cargarLogs, 300);
            } catch(e) {
                alert('Error al simular webhook: ' + e.message);
            }
        }

        async function buscarDocumentos() {
            const cliente = document.getElementById('filterCliente').value;
            const numero = document.getElementById('filterNumero').value;
            const fInicio = document.getElementById('filterFechaInicio').value;
            const fFin = document.getElementById('filterFechaFin').value;
            const soloImpagas = document.getElementById('filterSoloImpagas').checked;

            const params = new URLSearchParams({
                cliente: cliente,
                numero: numero,
                fecha_inicio: fInicio,
                fecha_fin: fFin,
                solo_impagas: soloImpagas
            });

            const tbody = document.getElementById('resultadosBody');
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Consultando datos en Tu Pana...</td></tr>`;

            try {
                const response = await fetch(baseUrl + 'webhook-test/buscar-dtes?' + params.toString());
                const res = await response.json();

                if (res.success && res.data.length > 0) {
                    document.getElementById('countBadge').textContent = `${res.data.length} encontrados`;
                    tbody.innerHTML = res.data.map(d => {
                        const totalFmt = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' }).format(d.total);
                        const saldoFmt = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' }).format(d.saldo_pendiente);
                        
                        return `
                        <tr>
                            <td class="fw-bold">${d.folio}</td>
                            <td>${d.fecha}</td>
                            <td><span class="badge bg-secondary">${d.tipo_documento}</span></td>
                            <td>
                                <div class="fw-semibold">${d.cliente_nombre}</div>
                                <div class="text-muted" style="font-size:0.75rem;">${d.cliente_rut}</div>
                            </td>
                            <td class="text-end fw-bold">${totalFmt}</td>
                            <td class="text-end fw-bold text-danger">${saldoFmt}</td>
                            <td class="text-center">
                                <span class="badge ${d.estado_sii === 'Aceptado' ? 'bg-success' : 'bg-warning'}">${d.estado_sii}</span>
                            </td>
                        </tr>`;
                    }).join('');
                } else {
                    document.getElementById('countBadge').textContent = `0 encontrados`;
                    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">No se encontraron documentos con los filtros seleccionados.</td></tr>`;
                }

                cargarLogs();
            } catch(e) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">Error al consultar API: ${e.message}</td></tr>`;
            }
        }

        async function cargarLogs() {
            const container = document.getElementById('logsContainer');
            try {
                const r = await fetch(baseUrl + 'webhook-test/logs');
                const logs = await r.json();

                if (logs.length === 0) {
                    container.innerHTML = `<div class="text-muted">Sin logs recientes.</div>`;
                    return;
                }

                container.innerHTML = logs.map((l, idx) => {
                    return `
                    <div class="mb-2 border-bottom pb-2">
                        <div class="d-flex justify-content-between">
                            <strong>[${l.timestamp}] ${l.event}</strong>
                            <button class="btn btn-link btn-xs p-0" style="font-size:0.70rem;" onclick="toggleDetails(${idx})">Ver JSON</button>
                        </div>
                        <div class="text-secondary">${l.message}</div>
                        <div id="details-${idx}" class="mt-2" style="display:none;">
                            <pre>${JSON.stringify(l.payload, null, 2)}</pre>
                        </div>
                    </div>`;
                }).join('');
            } catch(e) {
                container.innerHTML = `<div class="text-danger">Error de logs: ${e.message}</div>`;
            }
        }

        function toggleDetails(idx) {
            const el = document.getElementById('details-' + idx);
            el.style.display = el.style.display === 'none' ? 'block' : 'none';
        }

        async function limpiarFiltros() {
            document.getElementById('formFiltros').reset();
            buscarDocumentos();
        }

        async function limpiarLogs() {
            await fetch(baseUrl + 'webhook-test/logs', { method: 'DELETE' });
            cargarLogs();
        }

        // Carga inicial
        cargarLogs();
    </script>
</body>
</html>
