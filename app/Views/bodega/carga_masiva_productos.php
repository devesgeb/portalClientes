<?php $activePage = 'carga-masiva-productos'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carga masiva – Bodega | Portal Admin</title>
    <meta name="description" content="Importar y actualizar productos y listas de precio desde Excel.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('public/assets/css/admin.css') ?>">
    <style>
        /* ── Tabs ───────────────────────────────── */
        .tab-bar {
            display: flex;
            gap: 6px;
            background: #f5f0ff;
            border-radius: 14px;
            padding: 5px;
            margin-bottom: 24px;
            width: fit-content;
        }
        .tab-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 22px;
            border: none;
            border-radius: 10px;
            background: transparent;
            color: #6d28d9;
            font-family: 'Inter', sans-serif;
            font-size: .84rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .22s;
        }
        .tab-btn:hover { background: rgba(124,58,237,.12); }
        .tab-btn.active {
            background: #7c3aed;
            color: #fff;
            box-shadow: 0 3px 12px rgba(124,58,237,.35);
        }
        .tab-btn .tab-count {
            background: rgba(255,255,255,.25);
            border-radius: 20px;
            font-size: .70rem;
            padding: 1px 8px;
            font-weight: 700;
        }
        .tab-btn:not(.active) .tab-count {
            background: rgba(109,40,217,.15);
            color: #6d28d9;
        }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }

        /* ── Tarjeta ────────────────────────────── */
        .upload-card {
            background: #fff;
            border-radius: 18px;
            border: 1px solid #e8edf5;
            box-shadow: 0 2px 16px rgba(60,80,120,.07);
            padding: 28px 32px;
            transition: box-shadow .25s;
        }
        .upload-card:hover { box-shadow: 0 8px 32px rgba(60,80,120,.12); }

        /* ── Icono tarjeta ──────────────────────── */
        .card-icon {
            width: 52px; height: 52px; border-radius: 14px;
            display: flex; align-items: center; justify-content:center;
            font-size: 1.5rem; flex-shrink: 0;
        }
        .card-icon.prod { background: linear-gradient(135deg,#ede9fe,#ddd6fe); color: #6d28d9; }
        .card-icon.price { background: linear-gradient(135deg,#d1fae5,#a7f3d0); color: #059669; }

        /* ── Drop zone ──────────────────────────── */
        .drop-zone {
            border: 2.5px dashed #c4b5fd;
            border-radius: 14px;
            padding: 34px 20px;
            text-align: center;
            cursor: pointer;
            transition: all .2s;
            background: #faf8ff;
            position: relative;
        }
        .drop-zone.price-zone { border-color: #6ee7b7; background: #f0fdf9; }
        .drop-zone.dragover { transform: scale(1.01); }
        .drop-zone.dragover:not(.price-zone) { border-color: #7c3aed; background: #f5f0ff; }
        .drop-zone.price-zone.dragover { border-color: #059669; background: #ecfdf5; }
        .drop-zone input[type=file] {
            position: absolute; inset: 0; opacity: 0;
            cursor: pointer; width: 100%; height: 100%;
        }
        .drop-zone .dz-icon { font-size: 2.3rem; margin-bottom: 8px; }
        .drop-zone:not(.price-zone) .dz-icon { color: #a78bfa; }
        .drop-zone.price-zone .dz-icon { color: #34d399; }

        /* ── Preview ────────────────────────────── */
        .preview-wrap {
            border-radius: 12px; overflow: hidden;
            border: 1px solid #ede9fe;
            max-height: 260px; overflow-y: auto;
        }
        .price-pane .preview-wrap { border-color: #a7f3d0; }
        .preview-wrap table { min-width: 100%; font-size: .76rem; margin: 0; }
        .preview-wrap thead th {
            background: #f5f0ff; color: #5b21b6;
            font-weight: 600; position: sticky; top: 0; z-index: 2;
            padding: 8px 10px; white-space: nowrap;
        }
        .price-pane .preview-wrap thead th { background: #d1fae5; color: #065f46; }
        .preview-wrap tbody td { padding: 5px 10px; }
        .preview-wrap tbody tr:hover { background: #faf8ff; }
        .price-pane .preview-wrap tbody tr:hover { background: #f0fdf4; }

        /* ── Botones ────────────────────────────── */
        .btn-import {
            border: none; border-radius: 12px;
            font-size: .87rem; font-weight: 600;
            padding: 11px 0; width: 100%;
            transition: all .25s; letter-spacing: .02em; cursor: pointer;
        }
        .btn-import.prod-btn {
            background: linear-gradient(135deg,#4c1d95,#7c3aed); color: #fff;
        }
        .btn-import.prod-btn:hover:not(:disabled) {
            background: linear-gradient(135deg,#5b21b6,#8b5cf6);
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(124,58,237,.35);
        }
        .btn-import.price-btn {
            background: linear-gradient(135deg,#064e3b,#059669); color: #fff;
        }
        .btn-import.price-btn:hover:not(:disabled) {
            background: linear-gradient(135deg,#065f46,#10b981);
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(5,150,105,.35);
        }
        .btn-import:disabled { opacity: .45; cursor: not-allowed; transform: none !important; }

        .btn-plantilla {
            display: inline-flex; align-items: center; gap: 6px;
            background: #f5f0ff; color: #6d28d9;
            border: 1.5px solid #ddd6fe; border-radius: 10px;
            font-size: .81rem; font-weight: 600; padding: 7px 16px;
            transition: all .2s; text-decoration: none;
        }
        .btn-plantilla:hover { background: #ede9fe; color: #5b21b6; border-color: #c4b5fd; }
        .btn-plantilla.green { background: #d1fae5; color: #059669; border-color: #6ee7b7; }
        .btn-plantilla.green:hover { background: #a7f3d0; color: #047857; border-color: #34d399; }

        /* ── Estado archivo ─────────────────────── */
        .file-status {
            display: none; align-items: center; gap: 10px;
            padding: 8px 14px; border-radius: 10px; margin-top: 10px; font-size: .82rem;
            background: #f0fdf4; border: 1px solid #bbf7d0;
        }
        .file-status .file-name { font-weight: 600; color: #15803d; flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        /* ── Resultado / Barra ──────────────────── */
        .result-box { border-radius: 12px; padding: 14px 18px; font-size: .83rem; }
        .result-ok   { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
        .result-warn { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
        .result-err  { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
        .progress-wrap { display: none; margin-top: 16px; }
        .prog-bar-prod   { background: linear-gradient(90deg,#7c3aed,#a78bfa); }
        .prog-bar-price  { background: linear-gradient(90deg,#059669,#34d399); }

        /* ── Chips / Badges ─────────────────────── */
        .stat-chip {
            display: inline-flex; align-items: center; gap: 4px;
            border-radius: 20px; padding: 3px 12px;
            font-size: .76rem; font-weight: 600;
        }
        .stat-chip.v { background: #f5f0ff; color: #6d28d9; border: 1px solid #ddd6fe; }
        .stat-chip.g { background: #d1fae5; color: #059669; border: 1px solid #6ee7b7; }
        .col-badge {
            display: inline-block; border-radius: 6px;
            font-size: .70rem; font-weight: 600; padding: 2px 8px; margin: 2px;
        }
        .col-badge.req   { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
        .col-badge.opt   { background: #f5f0ff; border: 1px solid #ddd6fe; color: #6d28d9; }
        .col-badge.greq  { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
        .col-badge.gopt  { background: #d1fae5; border: 1px solid #6ee7b7; color: #059669; }

        /* ── Panel stats ────────────────────────── */
        .stats-card { background: #fff; border-radius: 18px; border: 1px solid #e8edf5; padding: 22px 26px; box-shadow: 0 2px 12px rgba(60,80,120,.06); }
        .sec-title { font-size: .71rem; text-transform: uppercase; letter-spacing: .08em; color: #94a3b8; font-weight: 700; margin-bottom: 8px; }

        /* ── Info alert ─────────────────────────── */
        .info-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; color: #1e40af; font-size: .82rem; padding: 12px 18px; }
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
                    <i class="bi bi-file-earmark-arrow-up-fill me-2" style="color:var(--accent);"></i>Carga masiva – Bodega
                </div>
                <div class="topbar-sub">Portal Admin &rsaquo; Bodega &rsaquo; Carga masiva</div>
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

        <!-- Info global -->
        <div class="info-box mb-4">
            <i class="bi bi-info-circle-fill me-2"></i>
            Usa las pestañas para importar <strong>Productos</strong> al maestro o actualizar las
            <strong>Listas de precio</strong> por canal. Ambas operaciones usan SKU como clave única
            (inserta nuevos, actualiza existentes).
        </div>

        <!-- ══ TAB BAR ══════════════════════════════════════════════ -->
        <div class="tab-bar">
            <button class="tab-btn active" id="tabBtnProductos" onclick="switchTab('productos')">
                <i class="bi bi-box-seam-fill"></i> Productos
                <span class="tab-count" id="tabCountProductos">0</span>
            </button>
            <button class="tab-btn" id="tabBtnPrecios" onclick="switchTab('precios')">
                <i class="bi bi-tags-fill"></i> Lista de precios
                <span class="tab-count" id="tabCountPrecios">0</span>
            </button>
        </div>

        <!-- ══════════════════════════════════════════════════════════
             TAB 1 – PRODUCTOS
        ════════════════════════════════════════════════════════════ -->
        <div class="tab-pane active" id="paneProductos">
            <div class="row g-4">
                <!-- Panel upload -->
                <div class="col-xl-7">
                    <div class="upload-card">
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div class="card-icon prod"><i class="bi bi-box-seam-fill"></i></div>
                            <div>
                                <h5 style="font-weight:700;margin-bottom:2px;">Importar Productos</h5>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-3 mb-3">
                            <a href="<?= site_url('bodega/plantilla-productos') ?>" class="btn-plantilla">
                                <i class="bi bi-download"></i> Descargar plantilla
                            </a>
                            <span style="font-size:.75rem;color:#94a3b8;">SKU, Nombre, Categoría, Marca, Unidad, Precios, Stock…</span>
                        </div>



                        <!-- Drop zone -->
                        <div class="drop-zone" id="dropProductos"
                             ondragover="dzDragOver(event,'dropProductos')"
                             ondragleave="dzDragLeave('dropProductos')"
                             ondrop="dzDrop(event,'dropProductos','productos')">
                            <input type="file" id="fileProductos" accept=".xlsx,.xls,.csv"
                                   onchange="procesarArchivo(this,'productos')">
                            <div class="dz-icon"><i class="bi bi-file-earmark-excel-fill"></i></div>
                            <div style="font-size:.88rem;color:#64748b;">
                                <strong>Haz clic o arrastra</strong> tu archivo aquí
                            </div>
                            <div style="font-size:.76rem;color:#94a3b8;margin-top:4px;">.xlsx · .xls · .csv</div>
                        </div>

                        <!-- Estado archivo -->
                        <div class="file-status" id="estadoProductos">
                            <i class="bi bi-file-earmark-check-fill" style="color:#16a34a;font-size:1.2rem;flex-shrink:0;"></i>
                            <span class="file-name" id="nombreArchivoProductos"></span>
                            <span class="stat-chip v" id="countProductos">0 filas</span>
                            <button class="btn btn-sm btn-outline-secondary" style="font-size:.72rem;padding:2px 10px;flex-shrink:0;"
                                    onclick="clearPanel('productos')">Limpiar</button>
                        </div>

                        <!-- Preview -->
                        <div id="previewProductos" style="display:none;margin-top:14px;">
                            <div class="sec-title">Vista previa (primeras 5 filas)</div>
                            <div class="preview-wrap">
                                <table class="table table-sm table-hover mb-0" id="tableProductos"></table>
                            </div>
                        </div>

                        <!-- Progress -->
                        <div class="progress-wrap" id="progProductos">
                            <div class="progress" style="height:6px;border-radius:4px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated prog-bar-prod" style="width:100%;"></div>
                            </div>
                            <div style="font-size:.75rem;color:var(--text-sub);margin-top:6px;">
                                <i class="bi bi-arrow-repeat me-1"></i>Importando productos...
                            </div>
                        </div>

                        <div id="resultProductos" style="display:none;margin-top:14px;"></div>

                        <button class="btn-import prod-btn mt-4" id="btnImportarProductos"
                                onclick="importar('productos')" disabled>
                            <i class="bi bi-cloud-upload me-2"></i>Importar Productos a la BD
                        </button>
                    </div>
                </div>

                <!-- Panel stats productos -->
                <div class="col-xl-5 d-flex flex-column gap-3">
                    <div class="stats-card">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-database-fill-check" style="font-size:1.2rem;color:#7c3aed;"></i>
                            <h6 style="font-weight:700;margin:0;">Estado en BD</h6>
                        </div>
                        <div id="statsBD" style="font-size:.83rem;color:var(--text-sub);">
                            <i class="bi bi-arrow-repeat me-1"></i>Cargando...
                        </div>
                    </div>
                    <div class="stats-card">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-shield-check" style="font-size:1.2rem;color:#7c3aed;"></i>
                            <h6 style="font-weight:700;margin:0;">Reglas</h6>
                        </div>
                        <ul style="font-size:.82rem;color:#4b5563;padding-left:18px;margin:0;line-height:1.9;">
                            <li>El <strong>SKU</strong> es clave única obligatoria.</li>
                            <li>SKU existente → <strong>actualiza</strong> el producto.</li>
                            <li>SKU nuevo → <strong>inserta</strong> el producto.</li>
                            <li>Unidades: <code>KG, CAJA, UN, LT, GR, MT, PAQ</code></li>
                            <li>Precios sin símbolo <code>$</code>.</li>
                        </ul>
                    </div>
                    <div class="stats-card">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-tags-fill" style="font-size:1.2rem;color:#7c3aed;"></i>
                            <h6 style="font-weight:700;margin:0;">Categorías en BD</h6>
                        </div>
                        <div id="statsCategorias" style="font-size:.82rem;color:var(--text-sub);">
                            <i class="bi bi-arrow-repeat me-1"></i>Cargando...
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /paneProductos -->


        <!-- ══════════════════════════════════════════════════════════
             TAB 2 – LISTA DE PRECIOS
        ════════════════════════════════════════════════════════════ -->
        <div class="tab-pane price-pane" id="panePrecios">
            <div class="row g-4">
                <!-- Panel upload lista de precios -->
                <div class="col-xl-7">
                    <div class="upload-card">
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div class="card-icon price"><i class="bi bi-tags-fill"></i></div>
                            <div>
                                <h5 style="font-weight:700;margin-bottom:2px;">Importar Lista de Precios</h5>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-3 mb-3">
                            <a href="<?= site_url('bodega/plantilla-lista-precios') ?>" class="btn-plantilla green">
                                <i class="bi bi-download"></i> Descargar plantilla
                            </a>
                            <span style="font-size:.75rem;color:#94a3b8;">SKU, Lista, Precio Neto, IVA, Precio Total</span>
                        </div>





                        <!-- Drop zone -->
                        <div class="drop-zone price-zone" id="dropPrecios"
                             ondragover="dzDragOver(event,'dropPrecios')"
                             ondragleave="dzDragLeave('dropPrecios')"
                             ondrop="dzDrop(event,'dropPrecios','precios')">
                            <input type="file" id="filePrecios" accept=".xlsx,.xls,.csv"
                                   onchange="procesarArchivo(this,'precios')">
                            <div class="dz-icon"><i class="bi bi-file-earmark-spreadsheet-fill"></i></div>
                            <div style="font-size:.88rem;color:#64748b;">
                                <strong>Haz clic o arrastra</strong> tu archivo aquí
                            </div>
                            <div style="font-size:.76rem;color:#94a3b8;margin-top:4px;">.xlsx · .xls · .csv</div>
                        </div>

                        <!-- Estado archivo -->
                        <div class="file-status" id="estadoPrecios">
                            <i class="bi bi-file-earmark-check-fill" style="color:#16a34a;font-size:1.2rem;flex-shrink:0;"></i>
                            <span class="file-name" id="nombreArchivoPrecios"></span>
                            <span class="stat-chip g" id="countPrecios">0 filas</span>
                            <button class="btn btn-sm btn-outline-secondary" style="font-size:.72rem;padding:2px 10px;flex-shrink:0;"
                                    onclick="clearPanel('precios')">Limpiar</button>
                        </div>

                        <!-- Preview -->
                        <div id="previewPrecios" style="display:none;margin-top:14px;">
                            <div class="sec-title">Vista previa (primeras 5 filas)</div>
                            <div class="preview-wrap">
                                <table class="table table-sm table-hover mb-0" id="tablePrecios"></table>
                            </div>
                        </div>

                        <!-- Progress -->
                        <div class="progress-wrap" id="progPrecios">
                            <div class="progress" style="height:6px;border-radius:4px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated prog-bar-price" style="width:100%;"></div>
                            </div>
                            <div style="font-size:.75rem;color:var(--text-sub);margin-top:6px;">
                                <i class="bi bi-arrow-repeat me-1"></i>Importando lista de precios...
                            </div>
                        </div>

                        <div id="resultPrecios" style="display:none;margin-top:14px;"></div>

                        <button class="btn-import price-btn mt-4" id="btnImportarPrecios"
                                onclick="importar('precios')" disabled>
                            <i class="bi bi-cloud-upload me-2"></i>Importar Lista de Precios a la BD
                        </button>
                    </div>
                </div>

                <!-- Panel stats listas -->
                <div class="col-xl-5 d-flex flex-column gap-3">
                    <div class="stats-card">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-database-fill-check" style="font-size:1.2rem;color:#059669;"></i>
                            <h6 style="font-weight:700;margin:0;">Estado en BD</h6>
                        </div>
                        <div id="statsLP" style="font-size:.83rem;color:var(--text-sub);">
                            <i class="bi bi-arrow-repeat me-1"></i>Cargando...
                        </div>
                    </div>
                    <div class="stats-card">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-shield-check" style="font-size:1.2rem;color:#059669;"></i>
                            <h6 style="font-weight:700;margin:0;">Reglas</h6>
                        </div>
                        <ul style="font-size:.82rem;color:#4b5563;padding-left:18px;margin:0;line-height:1.9;">
                            <li>El <strong>SKU</strong> debe existir en <code>tbl_productos</code>.</li>
                            <li>La clave única es <strong>SKU + Lista de precios</strong>.</li>
                            <li>Si ya existe la combinación → <strong>actualiza</strong> el precio.</li>
                            <li>Si no existe → <strong>inserta</strong> nueva fila.</li>
                            <li>Los SKUs no encontrados se reportan en el resultado.</li>
                        </ul>
                    </div>
                    <div class="stats-card">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-bar-chart-fill" style="font-size:1.2rem;color:#059669;"></i>
                            <h6 style="font-weight:700;margin:0;">Listas en BD</h6>
                        </div>
                        <div id="statsListasNombre" style="font-size:.82rem;color:var(--text-sub);">
                            <i class="bi bi-arrow-repeat me-1"></i>Cargando...
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /panePrecios -->

    </div><!-- /page-body -->
</div><!-- /main -->

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

<script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE = "<?= rtrim(site_url(), '/') . '/' ?>";
window.ADMIN_SESSION = <?= json_encode($usuario ?? [
    'nombre'       => session()->get('Nombre') ?? 'Administrador',
    'apellidos'    => '', 'email' => '', 'rut' => '',
    'telefono'     => '', 'estado' => 1,
    'ultimo_acceso'=> null, 'perfil' => 'Administrador',
]) ?>;

// Datos parseados por pestaña
let _datos = { productos: null, precios: null };

// ── Tabs ──────────────────────────────────────────────────────
function switchTab(t) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.getElementById('tabBtn' + cap(t)).classList.add('active');
    document.getElementById('pane'   + cap(t)).classList.add('active');
}
function cap(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

// ── Drag & Drop ───────────────────────────────────────────────
function dzDragOver(e, id)      { e.preventDefault(); document.getElementById(id).classList.add('dragover'); }
function dzDragLeave(id)        { document.getElementById(id).classList.remove('dragover'); }
function dzDrop(e, id, tipo)    {
    e.preventDefault();
    document.getElementById(id).classList.remove('dragover');
    if (e.dataTransfer.files.length) procesarArchivoFile(e.dataTransfer.files[0], tipo);
}
function procesarArchivo(inp, tipo) { if (inp.files[0]) procesarArchivoFile(inp.files[0], tipo); }

// ── Parsear Excel ─────────────────────────────────────────────
function procesarArchivoFile(file, tipo) {
    const reader = new FileReader();
    reader.onload = function(e) {
        const wb   = XLSX.read(new Uint8Array(e.target.result), { type: 'array', cellDates: true });
        const rows = XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]], { defval: '', raw: false });

        // Filtrar filas sin SKU
        const clean = rows.filter(r => {
            const sk = (r['SKU'] || r['sku'] || r['Sku'] || '').toString().trim();
            return sk !== '';
        });

        _datos[tipo] = clean;
        const T = tipo === 'productos' ? 'Productos' : 'Precios';
        document.getElementById('nombreArchivo' + T).textContent = file.name;
        document.getElementById('count'         + T).textContent = clean.length + ' filas';
        document.getElementById('estado'        + T).style.display = 'flex';
        document.getElementById('btnImportar'   + T).disabled = !clean.length;

        // Actualizar badge del tab
        const tabKey = tipo === 'productos' ? 'Productos' : 'Precios';
        document.getElementById('tabCount' + tabKey).textContent = clean.length;

        renderPreview(clean, T);
    };
    reader.readAsArrayBuffer(file);
}

// ── Preview ───────────────────────────────────────────────────
function renderPreview(rows, T) {
    if (!rows.length) return;
    const cols   = Object.keys(rows[0]);
    const sample = rows.slice(0, 5);
    let html = '<thead><tr>' + cols.map(c => `<th>${c}</th>`).join('') + '</tr></thead><tbody>';
    sample.forEach(r => {
        html += '<tr>' + cols.map(c => `<td>${r[c] ?? ''}</td>`).join('') + '</tr>';
    });
    if (rows.length > 5) {
        html += `<tr><td colspan="${cols.length}" style="text-align:center;color:#94a3b8;font-style:italic;padding:8px;">…y ${rows.length - 5} filas más</td></tr>`;
    }
    html += '</tbody>';
    document.getElementById('table' + T).innerHTML = html;
    document.getElementById('preview' + T).style.display = 'block';
}

// ── Limpiar Panel ─────────────────────────────────────────────
function clearPanel(tipo) {
    const T  = tipo === 'productos' ? 'Productos' : 'Precios';
    const fId = tipo === 'productos' ? 'fileProductos' : 'filePrecios';
    _datos[tipo] = null;
    ['estado','preview','result'].forEach(id => {
        const el = document.getElementById(id + T);
        if (el) el.style.display = 'none';
    });
    document.getElementById('table'        + T).innerHTML = '';
    document.getElementById('btnImportar'  + T).disabled  = true;
    document.getElementById(fId).value = '';
    document.getElementById('tabCount' + T).textContent = '0';
}

// ── Importar ──────────────────────────────────────────────────
async function importar(tipo) {
    const rows = _datos[tipo];
    if (!rows || !rows.length) return;

    const T       = tipo === 'productos' ? 'Productos' : 'Precios';
    const btn     = document.getElementById('btnImportar' + T);
    const prog    = document.getElementById('prog'        + T);
    const res     = document.getElementById('result'      + T);
    const endpoint = tipo === 'productos'
        ? 'bodega/importar-productos'
        : 'bodega/importar-lista-precios';

    btn.disabled = true;
    prog.style.display = 'block';
    res.style.display  = 'none';

    try {
        const resp = await fetch(BASE + endpoint, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ rows }),
        });
        const json = await resp.json();
        let html = '';

        if (json.success) {
            html = `<div class="result-box result-ok">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>${json.message}</strong>
                <div class="d-flex gap-3 mt-2" style="font-size:.80rem;">
                    <span>✅ <strong>${json.insertados}</strong> insertados</span>
                    <span>🔄 <strong>${json.actualizados}</strong> actualizados</span>
                </div>
            </div>`;

            // SKUs no encontrados (solo lista de precios)
            if (json.skuNoExiste && json.skuNoExiste.length) {
                const unicos = [...new Set(json.skuNoExiste)];
                html += `<div class="result-box result-warn mt-2">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>${unicos.length} SKU(s) no existen en el maestro de productos:</strong>
                    <div class="mt-1">${unicos.map(s => `<code class="me-1">${s}</code>`).join('')}</div>
                    <div style="font-size:.76rem;margin-top:4px;opacity:.8;">
                        Importa primero el maestro de productos con estos SKUs.
                    </div>
                </div>`;
            }

            if (json.errores && json.errores.length) {
                html += `<div class="result-box result-warn mt-2">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>${json.errores.length} fila(s) con error:</strong>
                    <ul class="mb-0 mt-1 ps-3">${json.errores.map(e => `<li style="font-size:.78rem;">${e}</li>`).join('')}</ul>
                </div>`;
            }
            cargarStats();
        } else {
            html = `<div class="result-box result-err">
                <i class="bi bi-x-circle me-2"></i>${json.message}
            </div>`;
        }
        res.innerHTML = html;
        res.style.display = 'block';

    } catch(err) {
        res.innerHTML = `<div class="result-box result-err">
            <i class="bi bi-x-circle me-2"></i>Error de conexión: ${err.message}
        </div>`;
        res.style.display = 'block';
    } finally {
        prog.style.display = 'none';
        btn.disabled = false;
    }
}

// ── Stats ─────────────────────────────────────────────────────
async function cargarStats() {
    try {
        const resp = await fetch(BASE + 'bodega/stats');
        const json = await resp.json();
        if (!json.success) return;
        const s = json.data;

        // Panel Productos
        document.getElementById('statsBD').innerHTML = `
            <div class="d-flex flex-wrap gap-2 mb-2">
                <div style="flex:1;min-width:80px;text-align:center;background:#f5f0ff;border-radius:12px;padding:12px;">
                    <div style="font-size:1.6rem;font-weight:700;color:#7c3aed;">${s.total_productos}</div>
                    <div style="font-size:.70rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;">Productos</div>
                </div>
                <div style="flex:1;min-width:80px;text-align:center;background:#eff6ff;border-radius:12px;padding:12px;">
                    <div style="font-size:1.6rem;font-weight:700;color:#2563eb;">${s.ultima_actualizacion}</div>
                    <div style="font-size:.70rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;">Últ. import.</div>
                </div>
            </div>`;

        // Categorías
        const cats = s.categorias || [];
        document.getElementById('statsCategorias').innerHTML = cats.length
            ? cats.map(c => `
                <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid #f0f4f9;">
                    <span style="font-weight:500;color:#374151;">${c.categoria}</span>
                    <span class="stat-chip v">${c.total} prod.</span>
                </div>`).join('')
            : '<span style="color:#94a3b8;">Sin datos</span>';

        // Panel Lista Precios
        document.getElementById('statsLP').innerHTML = `
            <div class="d-flex flex-wrap gap-2 mb-2">
                <div style="flex:1;min-width:80px;text-align:center;background:#d1fae5;border-radius:12px;padding:12px;">
                    <div style="font-size:1.6rem;font-weight:700;color:#059669;">${s.total_listas}</div>
                    <div style="font-size:.70rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;">Registros</div>
                </div>
                <div style="flex:1;min-width:80px;text-align:center;background:#eff6ff;border-radius:12px;padding:12px;">
                    <div style="font-size:1.6rem;font-weight:700;color:#2563eb;">${s.ultima_actualizacion_lp}</div>
                    <div style="font-size:.70rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;">Últ. import.</div>
                </div>
            </div>`;

        // Listas por nombre
        const listas = s.listas_resumen || [];
        document.getElementById('statsListasNombre').innerHTML = listas.length
            ? listas.map(l => `
                <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid #f0f4f9;">
                    <span style="font-weight:500;color:#374151;">${l.lista}</span>
                    <span class="stat-chip g">${l.total} prod.</span>
                </div>`).join('')
            : '<span style="color:#94a3b8;">Sin datos</span>';

    } catch(e) {
        ['statsBD','statsCategorias','statsLP','statsListasNombre'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '<span style="color:#94a3b8;font-size:.80rem;">Sin conexión.</span>';
        });
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
    cargarStats();
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
