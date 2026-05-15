<?php $activePage = 'historial-balances'; ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Balances Diario</title>

    <!-- Librerías de estilos y fuentes -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Archivos CSS compartidos -->
    <link rel="stylesheet" href="<?= base_url('public/assets/css/admin.css') ?>">
    <link rel="stylesheet" href="<?= base_url('public/assets/css/balanceDiario.css') ?>">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .chart-container {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            padding: 20px;
            margin-bottom: 24px;
            border: 1px solid #e5eaf0;
        }

        .tbl-historial {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            border: 1px solid #e5eaf0;
        }

        .tbl-historial th {
            background: #f8fafc;
            color: #5a7394;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            padding: 14px 20px;
            border-bottom: 2px solid #e2e8f0;
        }

        .tbl-historial td {
            padding: 14px 20px;
            font-size: 0.88rem;
            color: #374151;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .tbl-historial tbody tr:last-child td {
            border-bottom: none;
        }

        .tbl-historial tbody tr:hover {
            background: #f8fafc;
        }

        .badge-dia {
            background: #eff6ff;
            color: #2563eb;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .filter-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5eaf0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <?= $this->include('partials/sidebar') ?>

    <div class="main">

        <!-- Top Bar -->
        <div class="topbar">
            <div class="d-flex align-items-center gap-2">
                <button class="btn-menu-toggle" onclick="abrirSidebar()" aria-label="Abrir menú">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <div class="topbar-title"><i class="bi bi-clock-history me-2" style="color:var(--accent);"></i>Historial de Balances</div>
                    <div class="topbar-sub">Contabilidad &rsaquo; Evolución Diaria</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="date-badge"><i class="bi bi-info-circle me-1"></i>PROTOTIPO</span>
                <div class="user-badge" title="Ver información del administrador">
                    <div class="ub-avatar" id="topbarAvatar">--</div>
                    <div>
                        <div class="ub-name" id="topbarNombre">Cargando...</div>
                        <div class="ub-role" id="topbarRol">Administrador</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">

            <!-- KPIs Summary -->
            <div class="row g-3 mb-3">
                <div class="col-xl-4 col-md-4">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="background:#f0fdf4;"><i class="bi bi-graph-up-arrow" style="color:#16a34a;"></i></div>
                        <div>
                            <div class="kpi-val" id="kpiNeto" style="color:#16a34a;">$4.82M</div>
                            <div class="kpi-lbl">Promedio Neto Diario</div>
                            <div class="kpi-sub">Rango Seleccionado</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-4">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="background:#eff6ff;"><i class="bi bi-wallet2" style="color:#2563eb;"></i></div>
                        <div>
                            <div class="kpi-val" id="kpiCobrar" style="color:#2563eb;">$1.45M</div>
                            <div class="kpi-lbl">Promedio Cuentas por Cobrar</div>
                            <div class="kpi-sub">Rango Seleccionado</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-4">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="background:#f5f3ff;"><i class="bi bi-piggy-bank-fill" style="color:#7c3aed;"></i></div>
                        <div>
                            <div class="kpi-val" id="kpiPagar" style="color:#7c3aed;">$820K</div>
                            <div class="kpi-lbl">Promedio Cuentas por Pagar</div>
                            <div class="kpi-sub">Rango Seleccionado</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros Diarios -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="filter-card p-3 d-flex align-items-end gap-3 flex-wrap">
                        <div>
                            <label class="form-label text-secondary fw-semibold mb-1" style="font-size:0.75rem;"><i class="bi bi-calendar3 me-1"></i> Fecha Inicio</label>
                            <input type="date" class="form-control form-control-sm" id="fechaInicio" value="2026-05-01">
                        </div>
                        <div>
                            <label class="form-label text-secondary fw-semibold mb-1" style="font-size:0.75rem;"><i class="bi bi-calendar3 me-1"></i> Fecha Fin</label>
                            <input type="date" class="form-control form-control-sm" id="fechaFin" value="2026-05-07">
                        </div>
                        <div>
                            <button class="btn btn-primary btn-sm px-4" onclick="filtrarPorDia()" style="border-radius:6px; font-weight: 500;">
                                <i class="bi bi-search me-1"></i> Analizar Rango
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráfico de Evolución Diaria -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="chart-container">
                        <h6 style="color:#1a2940;font-weight:600;margin-bottom:20px;">
                            <i class="bi bi-bar-chart-line-fill me-2" style="color:#16a34a;"></i>Fluctuación y Márgenes por Día (Mock)
                        </h6>
                        <canvas id="balanceChart" height="80"></canvas>
                    </div>
                </div>
            </div>

            <!-- Tabla de Historial Diario -->
            <div class="row">
                <div class="col-12">
                    <table class="tbl-historial">
                        <thead>
                            <tr>
                                <th>Día</th>
                                <th class="text-end">Cuentas por Cobrar</th>
                                <th class="text-end">Caja / Inventario</th>
                                <th class="text-end">Cuentas por Pagar</th>
                                <th class="text-end">Margen Neto Total</th>
                                <th class="text-center">Detalle</th>
                            </tr>
                        </thead>
                        <tbody id="tablaHistorialBody">
                            <!-- Día 1 -->
                            <tr>
                                <td><span class="badge-dia">01 May 2026</span></td>
                                <td class="text-end" style="color:#2563eb;font-weight:500;">$1.250.000</td>
                                <td class="text-end" style="color:#0891b2;font-weight:500;">$3.800.000</td>
                                <td class="text-end" style="color:#dc2626;font-weight:500;">$900.000</td>
                                <td class="text-end" style="color:#16a34a;font-weight:600;">$4.150.000</td>
                                <td class="text-center">
                                    <button class="btn btn-sm" style="background:#f0f4f9;color:#5a7394;" onclick="alert('Detalle del día no disponible en prototipo.')"><i class="bi bi-eye"></i></button>
                                </td>
                            </tr>
                            <!-- Día 2 -->
                            <tr>
                                <td><span class="badge-dia">02 May 2026</span></td>
                                <td class="text-end" style="color:#2563eb;font-weight:500;">$1.400.000</td>
                                <td class="text-end" style="color:#0891b2;font-weight:500;">$3.750.000</td>
                                <td class="text-end" style="color:#dc2626;font-weight:500;">$850.000</td>
                                <td class="text-end" style="color:#16a34a;font-weight:600;">$4.300.000</td>
                                <td class="text-center">
                                    <button class="btn btn-sm" style="background:#f0f4f9;color:#5a7394;" onclick="alert('Detalle del día no disponible en prototipo.')"><i class="bi bi-eye"></i></button>
                                </td>
                            </tr>
                            <!-- Día 3 -->
                            <tr>
                                <td><span class="badge-dia">03 May 2026</span></td>
                                <td class="text-end" style="color:#2563eb;font-weight:500;">$1.350.000</td>
                                <td class="text-end" style="color:#0891b2;font-weight:500;">$3.900.000</td>
                                <td class="text-end" style="color:#dc2626;font-weight:500;">$600.000</td>
                                <td class="text-end" style="color:#16a34a;font-weight:600;">$4.650.000</td>
                                <td class="text-center">
                                    <button class="btn btn-sm" style="background:#f0f4f9;color:#5a7394;" onclick="alert('Detalle del día no disponible en prototipo.')"><i class="bi bi-eye"></i></button>
                                </td>
                            </tr>
                            <!-- Día 4 -->
                            <tr>
                                <td><span class="badge-dia">04 May 2026</span></td>
                                <td class="text-end" style="color:#2563eb;font-weight:500;">$1.600.000</td>
                                <td class="text-end" style="color:#0891b2;font-weight:500;">$3.850.000</td>
                                <td class="text-end" style="color:#dc2626;font-weight:500;">$700.000</td>
                                <td class="text-end" style="color:#16a34a;font-weight:600;">$4.750.000</td>
                                <td class="text-center">
                                    <button class="btn btn-sm" style="background:#f0f4f9;color:#5a7394;" onclick="alert('Detalle del día no disponible en prototipo.')"><i class="bi bi-eye"></i></button>
                                </td>
                            </tr>
                            <!-- Día 5 -->
                            <tr>
                                <td><span class="badge-dia">05 May 2026</span></td>
                                <td class="text-end" style="color:#2563eb;font-weight:500;">$1.850.000</td>
                                <td class="text-end" style="color:#0891b2;font-weight:500;">$3.800.000</td>
                                <td class="text-end" style="color:#dc2626;font-weight:500;">$800.000</td>
                                <td class="text-end" style="color:#16a34a;font-weight:600;">$4.850.000</td>
                                <td class="text-center">
                                    <button class="btn btn-sm" style="background:#f0f4f9;color:#5a7394;" onclick="alert('Detalle del día no disponible en prototipo.')"><i class="bi bi-eye"></i></button>
                                </td>
                            </tr>
                            <!-- Día 6 -->
                            <tr>
                                <td><span class="badge-dia">06 May 2026</span></td>
                                <td class="text-end" style="color:#2563eb;font-weight:500;">$1.550.000</td>
                                <td class="text-end" style="color:#0891b2;font-weight:500;">$4.100.000</td>
                                <td class="text-end" style="color:#dc2626;font-weight:500;">$950.000</td>
                                <td class="text-end" style="color:#16a34a;font-weight:600;">$4.700.000</td>
                                <td class="text-center">
                                    <button class="btn btn-sm" style="background:#f0f4f9;color:#5a7394;" onclick="alert('Detalle del día no disponible en prototipo.')"><i class="bi bi-eye"></i></button>
                                </td>
                            </tr>
                            <!-- Día 7 -->
                            <tr>
                                <td><span class="badge-dia">07 May 2026</span></td>
                                <td class="text-end" style="color:#2563eb;font-weight:500;">$2.100.000</td>
                                <td class="text-end" style="color:#0891b2;font-weight:500;">$4.200.000</td>
                                <td class="text-end" style="color:#dc2626;font-weight:500;">$1.100.000</td>
                                <td class="text-end" style="color:#16a34a;font-weight:600;">$5.200.000</td>
                                <td class="text-center">
                                    <button class="btn btn-sm" style="background:#f0f4f9;color:#5a7394;" onclick="alert('Detalle del día no disponible en prototipo.')"><i class="bi bi-eye"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div><!-- /page-body -->
    </div><!-- /main -->

    <script>
        let balanceChartInstance = null;

        document.addEventListener('DOMContentLoaded', function() {
            var usuarioJson = <?= json_encode($usuario ?? null) ?>;
            if (usuarioJson) {
                var initial = (usuarioJson.nombre || 'U').charAt(0).toUpperCase();
                document.getElementById('topbarAvatar').textContent = initial;
                document.getElementById('topbarNombre').textContent = usuarioJson.nombre;
                if(document.getElementById('sidebarAvatar')) document.getElementById('sidebarAvatar').textContent = initial;
                if(document.getElementById('sidebarNombre')) document.getElementById('sidebarNombre').textContent = usuarioJson.nombre;
            }

            renderChart();
        });

        function renderChart() {
            const ctx = document.getElementById('balanceChart').getContext('2d');
            
            if(balanceChartInstance) {
                balanceChartInstance.destroy();
            }

            balanceChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['01-May', '02-May', '03-May', '04-May', '05-May', '06-May', '07-May'],
                    datasets: [
                        {
                            label: 'Margen Neto ($)',
                            data: [4150000, 4300000, 4650000, 4750000, 4850000, 4700000, 5200000],
                            borderColor: '#16a34a',
                            backgroundColor: 'rgba(22, 163, 74, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.3,
                            pointRadius: 4,
                            pointBackgroundColor: '#16a34a'
                        },
                        {
                            label: 'Cuentas x Cobrar ($)',
                            data: [1250000, 1400000, 1350000, 1600000, 1850000, 1550000, 2100000],
                            borderColor: '#2563eb',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            borderDash: [4, 4],
                            tension: 0.3,
                            pointRadius: 3
                        },
                        {
                            label: 'Cuentas x Pagar ($)',
                            data: [900000, 850000, 600000, 700000, 800000, 950000, 1100000],
                            borderColor: '#dc2626',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            borderDash: [4, 4],
                            tension: 0.3,
                            pointRadius: 3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) { label += ': '; }
                                    if (context.parsed.y !== null) {
                                        label += new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' }).format(context.parsed.y);
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                borderDash: [5, 5]
                            },
                            ticks: {
                                callback: function(value) {
                                    return '$' + (value / 1000000).toFixed(1) + 'M';
                                }
                            }
                        }
                    }
                }
            });
        }

        function filtrarPorDia() {
            // Simulamos que al filtrar se recarga el gráfico o cambia la data
            const btn = event.currentTarget;
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Analizando...';
            btn.disabled = true;

            setTimeout(() => {
                alert('Filtro aplicado. En la versión final, el gráfico y la tabla se actualizarán con los datos exactos del rango: ' + document.getElementById('fechaInicio').value + ' al ' + document.getElementById('fechaFin').value);
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }, 800);
        }
    </script>
</body>
</html>
