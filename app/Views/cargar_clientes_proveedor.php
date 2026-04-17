<?php $activePage = 'cargar-entidad'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cargar Clientes / Proveedores – Portal Admin</title>
    <meta name="description" content="Importar clientes y proveedores desde Excel al sistema.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('public/assets/css/admin.css') ?>">
    <style>
        .upload-panel {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e8edf5;
            box-shadow: 0 2px 12px rgba(60,80,120,.06);
            padding: 28px;
            transition: box-shadow .2s;
        }
        .upload-panel:hover { box-shadow: 0 6px 24px rgba(60,80,120,.11); }
        .panel-icon {
            width: 52px; height: 52px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin-bottom: 14px;
        }
        .panel-icon.clientes  { background: #eff6ff; color: #2563eb; }
        .panel-icon.prov      { background: #f0fdf4; color: #16a34a; }
        .drop-zone {
            border: 2px dashed #c8d5e8;
            border-radius: 12px;
            padding: 32px 20px;
            text-align: center;
            cursor: pointer;
            transition: all .2s;
            background: #f8faff;
            position: relative;
        }
        .drop-zone.dragover { border-color: #4338ca; background: #eef2ff; }
        .drop-zone input[type=file] {
            position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%;
        }
        .drop-zone .dz-icon { font-size: 2rem; color: #94a3b8; margin-bottom: 8px; }
        .preview-table { font-size: .78rem; max-height: 260px; overflow-y: auto; }
        .preview-table table { min-width: 100%; }
        .preview-table th { background: #f0f4f9; position: sticky; top: 0; z-index: 1; }
        .badge-pill { font-size: .72rem; border-radius: 20px; padding: 2px 10px; }
        .step-badge {
            width: 26px; height: 26px; border-radius: 50%;
            background: var(--accent); color: #fff;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: .75rem; font-weight: 700; margin-right: 8px;
        }
        .result-box { border-radius: 12px; padding: 14px 18px; font-size: .82rem; }
        .result-ok   { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
        .result-warn { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
        .result-err  { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
        .btn-download {
            background: #f0f4f9; color: #4b5563; border: 1px solid #e2e8f0;
            border-radius: 9px; font-size: .8rem; padding: 6px 14px;
            transition: all .2s;
        }
        .btn-download:hover { background: #e2e8f0; color: #1f2937; }
        .progress-wrap { display: none; margin-top: 14px; }
        .import-btn:disabled { opacity: .6; cursor: not-allowed; }
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
                    <i class="bi bi-people-fill me-2" style="color:var(--accent);"></i>Cargar Clientes / Proveedores
                </div>
                <div class="topbar-sub">Portal Admin &rsaquo; Clientes / Proveedores &rsaquo; Cargar</div>
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

        <!-- Instrucciones -->
        <div class="alert" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;color:#1e40af;font-size:.83rem;padding:12px 18px;margin-bottom:20px;">
            <i class="bi bi-info-circle-fill me-2"></i>
            Descarga la plantilla, complétala y súbela aquí. El sistema importará los datos a la base de datos,
            actualizando registros existentes por RUT y creando los nuevos.
        </div>

        <div class="row g-4">

            <!-- ════ PANEL CLIENTES ════ -->
            <div class="col-xl-6">
                <div class="upload-panel">
                    <div class="panel-icon clientes"><i class="bi bi-person-lines-fill"></i></div>
                    <h5 style="font-weight:700;margin-bottom:4px;">Importar Clientes</h5>
                    <p style="font-size:.82rem;color:var(--text-sub);margin-bottom:16px;">
                        Carga masiva de clientes con línea de crédito y condiciones de pago.
                    </p>

                    <!-- Descargar plantilla -->
                    <a href="<?= base_url('plantillas/clientes/carga-clientes-proveedores-lineacredito.xlsx') ?>"
                       download class="btn btn-download mb-3">
                        <i class="bi bi-download me-1"></i>Descargar plantilla Clientes
                    </a>

                    <!-- Zona de drop -->
                    <div class="drop-zone" id="dropClientes"
                         ondragover="dzDragOver(event,'dropClientes')"
                         ondragleave="dzDragLeave('dropClientes')"
                         ondrop="dzDrop(event,'dropClientes','clientes')">
                        <input type="file" accept=".xlsx,.xls,.csv"
                               onchange="procesarArchivo(this,'clientes')">
                        <div class="dz-icon"><i class="bi bi-file-earmark-excel-fill"></i></div>
                        <div style="font-size:.85rem;color:#64748b;">
                            <strong>Haz clic o arrastra</strong> tu archivo Excel aquí
                        </div>
                        <div style="font-size:.75rem;color:#94a3b8;margin-top:4px;">.xlsx, .xls, .csv</div>
                    </div>

                    <!-- Estado del archivo -->
                    <div id="estadoClientes" style="display:none;margin-top:10px;" class="d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-check-fill" style="color:#16a34a;font-size:1.2rem;"></i>
                        <span id="nombreArchivoClientes" style="font-size:.82rem;font-weight:600;"></span>
                        <span id="countClientes" class="badge bg-primary badge-pill"></span>
                        <button class="btn btn-sm btn-outline-secondary" style="font-size:.72rem;padding:2px 8px;"
                                onclick="clearPanel('clientes')">Limpiar</button>
                    </div>

                    <!-- Preview tabla -->
                    <div class="preview-table mt-3" id="previewClientes" style="display:none;">
                        <table class="table table-sm table-hover" id="tableClientes"></table>
                    </div>

                    <!-- Barra de progreso -->
                    <div class="progress-wrap" id="progClientes">
                        <div class="progress" style="height:6px;border-radius:4px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width:100%"></div>
                        </div>
                        <div style="font-size:.75rem;color:var(--text-sub);margin-top:4px;">Importando...</div>
                    </div>

                    <!-- Resultado -->
                    <div id="resultClientes" style="display:none;margin-top:12px;"></div>

                    <!-- Botón importar -->
                    <button class="btn import-btn mt-3 w-100" id="btnImportarClientes"
                            style="background:linear-gradient(135deg,#1e1b4b,#4338ca);color:#fff;border-radius:10px;font-size:.86rem;font-weight:600;padding:10px;"
                            onclick="importar('clientes')" disabled>
                        <i class="bi bi-cloud-upload me-2"></i>Importar Clientes a la BD
                    </button>
                </div>
            </div>

            <!-- ════ PANEL PROVEEDORES ════ -->
            <div class="col-xl-6">
                <div class="upload-panel">
                    <div class="panel-icon prov"><i class="bi bi-truck"></i></div>
                    <h5 style="font-weight:700;margin-bottom:4px;">Importar Proveedores</h5>
                    <p style="font-size:.82rem;color:var(--text-sub);margin-bottom:16px;">
                        Carga masiva de proveedores con datos de contacto y ubicación.
                    </p>

                    <a href="<?= base_url('plantillas/Proveedores/carga-clientes-proveedores.xlsx') ?>"
                       download class="btn btn-download mb-3">
                        <i class="bi bi-download me-1"></i>Descargar plantilla Proveedores
                    </a>

                    <div class="drop-zone" id="dropProveedores"
                         ondragover="dzDragOver(event,'dropProveedores')"
                         ondragleave="dzDragLeave('dropProveedores')"
                         ondrop="dzDrop(event,'dropProveedores','proveedores')">
                        <input type="file" accept=".xlsx,.xls,.csv"
                               onchange="procesarArchivo(this,'proveedores')">
                        <div class="dz-icon"><i class="bi bi-file-earmark-excel-fill" style="color:#16a34a;"></i></div>
                        <div style="font-size:.85rem;color:#64748b;">
                            <strong>Haz clic o arrastra</strong> tu archivo Excel aquí
                        </div>
                        <div style="font-size:.75rem;color:#94a3b8;margin-top:4px;">.xlsx, .xls, .csv</div>
                    </div>

                    <div id="estadoProveedores" style="display:none;margin-top:10px;" class="d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-check-fill" style="color:#16a34a;font-size:1.2rem;"></i>
                        <span id="nombreArchivoProveedores" style="font-size:.82rem;font-weight:600;"></span>
                        <span id="countProveedores" class="badge bg-success badge-pill"></span>
                        <button class="btn btn-sm btn-outline-secondary" style="font-size:.72rem;padding:2px 8px;"
                                onclick="clearPanel('proveedores')">Limpiar</button>
                    </div>

                    <div class="preview-table mt-3" id="previewProveedores" style="display:none;">
                        <table class="table table-sm table-hover" id="tableProveedores"></table>
                    </div>

                    <div class="progress-wrap" id="progProveedores">
                        <div class="progress" style="height:6px;border-radius:4px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width:100%"></div>
                        </div>
                        <div style="font-size:.75rem;color:var(--text-sub);margin-top:4px;">Importando...</div>
                    </div>

                    <div id="resultProveedores" style="display:none;margin-top:12px;"></div>

                    <button class="btn import-btn mt-3 w-100" id="btnImportarProveedores"
                            style="background:linear-gradient(135deg,#14532d,#16a34a);color:#fff;border-radius:10px;font-size:.86rem;font-weight:600;padding:10px;"
                            onclick="importar('proveedores')" disabled>
                        <i class="bi bi-cloud-upload me-2"></i>Importar Proveedores a la BD
                    </button>
                </div>
            </div>

        </div><!-- /row -->
    </div><!-- /page-body -->
</div><!-- /main -->

<!-- Modal Info Admin -->
<div class="modal fade" id="modalAdminInfo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;">
            <div class="modal-header py-3" style="background:linear-gradient(135deg,#1e1b4b,#4338ca);border:none;">
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

<!-- SheetJS CDN -->
<script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE = "<?= rtrim(site_url(), '/') . '/' ?>";
window.ADMIN_SESSION = <?= json_encode($usuario ?? ['nombre'=>session()->get('Nombre')?? 'Administrador','apellidos'=>'','email'=>'','rut'=>'','telefono'=>'','estado'=>1,'ultimo_acceso'=>null,'perfil'=>'Administrador']) ?>;

// Datos parseados por tipo
const _data = { clientes: null, proveedores: null };

// ── Drag & Drop ──────────────────────────────
function dzDragOver(e, id) { e.preventDefault(); document.getElementById(id).classList.add('dragover'); }
function dzDragLeave(id) { document.getElementById(id).classList.remove('dragover'); }
function dzDrop(e, id, tipo) {
    e.preventDefault();
    document.getElementById(id).classList.remove('dragover');
    const files = e.dataTransfer.files;
    if (files.length) procesarArchivoFile(files[0], tipo);
}

// ── Procesar archivo ─────────────────────────
function procesarArchivo(input, tipo) {
    const file = input.files[0];
    if (file) procesarArchivoFile(file, tipo);
}

// â”€â”€ Deduplicar filas por RUT + Nombre â”€â”€ 
function deduplicarFilas(rows) {
    const seen  = new Map();
    const dupes = [];
    const clean = [];

    rows.forEach(function(row) {
        // Clave: RUT normalizado + nombre normalizado (insensible a mayÃºsculas/espacios)
        const rut    = String(row['Rut'] || row['RUT'] || row['rut'] || '').replace(/\s/g,'').toLowerCase();
        const nombre = String(
            row['Nombre Cliente'] || row['Razon Social'] || row['Razon_Social'] ||
            row['nombre'] || row['razon_social'] || row['Nombre Proveedor'] || ''
        ).trim().toLowerCase();

        const key = rut ? rut : nombre;   // Si hay RUT Ãºsalo como clave; si no, el nombre
        if (!key) { clean.push(row); return; }  // Sin clave â†’ siempre incluir

        if (!seen.has(key)) {
            seen.set(key, true);
            clean.push(row);
        } else {
            dupes.push(key);
        }
    });

    return { clean, dupes };
}

function procesarArchivoFile(file, tipo) {
    const reader = new FileReader();
    reader.onload = function(e) {
        const data = new Uint8Array(e.target.result);
        const wb   = XLSX.read(data, { type: 'array', cellDates: true });
        const ws   = wb.Sheets[wb.SheetNames[0]];
        const raw  = XLSX.utils.sheet_to_json(ws, { defval: '', raw: false });

        // Deduplicar antes de guardar/mostrar
        const { clean, dupes } = deduplicarFilas(raw);
        _data[tipo] = clean;

        const T = tipo.charAt(0).toUpperCase() + tipo.slice(1);
        document.getElementById('nombreArchivo' + T).textContent = file.name;
        document.getElementById('estado' + T).style.display = 'flex';
        document.getElementById('btnImportar' + T).disabled = !clean.length;

        // Mostrar badge con info de deduplicaciÃ³n
        const countEl = document.getElementById('count' + T);
        if (dupes.length > 0) {
            countEl.innerHTML = clean.length + ' filas <span style="background:#fef9c3;color:#92400e;border-radius:10px;padding:1px 7px;margin-left:4px;font-size:.70rem;" title="Filas duplicadas eliminadas">âš  ' + dupes.length + ' duplicado' + (dupes.length > 1 ? 's' : '') + ' eliminado' + (dupes.length > 1 ? 's' : '') + '</span>';
        } else {
            countEl.textContent = clean.length + ' filas';
        }

        // Mostrar alerta de duplicados en el panel de resultado
        const resEl = document.getElementById('result' + T);
        if (dupes.length > 0) {
            resEl.innerHTML = '<div class="result-box result-warn" style="margin-top:10px;">'
                + '<i class="bi bi-funnel-fill me-2"></i>'
                + '<strong>DeduplicaciÃ³n aplicada:</strong> Se eliminaron <strong>' + dupes.length + '</strong> fila(s) duplicada(s) del archivo.<br>'
                + '<span style="font-size:.76rem;opacity:.85;">Se detectaron registros con el mismo RUT o nombre y se conservÃ³ solo uno por entidad.</span>'
                + '</div>';
            resEl.style.display = 'block';
        } else {
            resEl.style.display = 'none';
        }

        renderPreview(clean, tipo);
    };
    reader.readAsArrayBuffer(file);
}

function renderPreview(rows, tipo) {
    if (!rows.length) return;
    const T   = tipo.charAt(0).toUpperCase() + tipo.slice(1);
    const preview = document.getElementById('preview' + T);
    const table   = document.getElementById('table' + T);
    const cols    = Object.keys(rows[0]);
    const sample  = rows.slice(0, 5);

    let html = '<thead><tr>' + cols.map(c => `<th>${c}</th>`).join('') + '</tr></thead><tbody>';
    sample.forEach(r => {
        html += '<tr>' + cols.map(c => `<td>${r[c] ?? ''}</td>`).join('') + '</tr>';
    });
    if (rows.length > 5) html += `<tr><td colspan="${cols.length}" style="text-align:center;color:#94a3b8;font-style:italic;">…y ${rows.length - 5} filas más</td></tr>`;
    html += '</tbody>';
    table.innerHTML = html;
    preview.style.display = 'block';
}

function clearPanel(tipo) {
    _data[tipo] = null;
    const T = tipo.charAt(0).toUpperCase() + tipo.slice(1);
    document.getElementById('estado' + T).style.display   = 'none';
    document.getElementById('preview' + T).style.display  = 'none';
    document.getElementById('result' + T).style.display   = 'none';
    document.getElementById('btnImportar' + T).disabled   = true;
    document.getElementById('table' + T).innerHTML        = '';
}

// ── Importar ─────────────────────────────────
async function importar(tipo) {
    const rows = _data[tipo];
    if (!rows || !rows.length) return;

    const T   = tipo.charAt(0).toUpperCase() + tipo.slice(1);
    const btn = document.getElementById('btnImportar' + T);
    const prog = document.getElementById('prog' + T);
    const res  = document.getElementById('result' + T);

    btn.disabled = true;
    prog.style.display = 'block';
    res.style.display  = 'none';

    try {
        const endpoint = tipo === 'clientes' ? 'importar-clientes' : 'importar-proveedores';
        const resp = await fetch(BASE + endpoint, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ rows }),
        });
        const json = await resp.json();

        let html = '';
        if (json.success) {
            const dedupLine = json.deduplicados > 0
                ? `<br><span style="font-size:.76rem;color:#92400e;"><i class="bi bi-funnel-fill me-1"></i>${json.deduplicados} duplicado(s) ignorados por el servidor.</span>`
                : "";
            html = `<div class="result-box result-ok">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>${json.message}</strong>
                <div class="mt-1" style="opacity:.85;">
                    ✅ Nuevos: ${json.insertados} &nbsp; 🔄 Actualizados: ${json.actualizados}
                </div>
            </div>`;
            if (json.errores && json.errores.length) {
                html += `<div class="result-box result-warn mt-2">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    ${json.errores.length} fila(s) con error:
                    <ul class="mb-0 mt-1">${json.errores.map(e=>`<li>${e}</li>`).join('')}</ul>
                </div>`;
            }
        } else {
            html = `<div class="result-box result-err">
                <i class="bi bi-x-circle me-2"></i>${json.message}
            </div>`;
        }
        res.innerHTML = html;
        res.style.display = 'block';

    } catch(err) {
        res.innerHTML = `<div class="result-box result-err"><i class="bi bi-x-circle me-2"></i>Error de conexión: ${err.message}</div>`;
        res.style.display = 'block';
    } finally {
        prog.style.display = 'none';
        btn.disabled = false;
    }
}

// ── Init ─────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    const u     = window.ADMIN_SESSION || {};
    const nombre= u.nombre || 'Admin';
    const ini   = nombre.substring(0,2).toUpperCase();
    const el    = (id, v) => { const e = document.getElementById(id); if(e) e.textContent = v; };
    el('topbarAvatar', ini); el('topbarNombre', nombre); el('topbarRol', u.perfil || 'Administrador');
    el('sidebarAvatar', ini); el('sidebarNombre', nombre); el('sidebarRol', u.perfil || 'Administrador');
    const f = document.getElementById('fechaHoy');
    if(f) f.textContent = new Date().toLocaleDateString('es-CL',{day:'2-digit',month:'2-digit',year:'numeric'});
});

function abrirModalAdmin() {
    const u = window.ADMIN_SESSION || {};
    const nombre = u.nombre || 'Admin';
    const ini    = nombre.substring(0,2).toUpperCase();
    const el = (id, v) => { const e = document.getElementById(id); if(e) e.textContent = v; };
    el('modalAdminAvatar', ini);
    el('modalAdminNombre', nombre + ' ' + (u.apellidos || ''));
    el('modalAdminPerfil', u.perfil || 'Administrador');
    const rows = document.getElementById('modalAdminRows');
    if(rows) {
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