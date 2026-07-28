/**
 * BalanceDiario.js — Entry Point de la vista Balance Diario
 * ─────────────────────────────────────────────────────────────────────────
 * Dependencias (cargar EN ESTE ORDEN en balance_diario.php):
 *   1. PortalApp.js      — Core genérico
 *   2. DocumentosModule.js — CRUD genérico cobrar/pagar
 *   3. BalanceDiario.js   — Este archivo (solo configura CFG e inicializa)
 * ─────────────────────────────────────────────────────────────────────────
 */
'use strict';

// Fallback si jsdelivr no carga
if (typeof XLSX === 'undefined') {
    document.write('<script src="https://unpkg.com/xlsx@0.18.5/dist/xlsx.full.min.js"><\/script>');
}

// ── DATA STORES ──────────────────────────────────────────────────────────
let dbCobrar = [];
let dbPagar = [];
let dbCaja = [];         // se llena desde /inventario/productos
let dbCajaFull = [];     // copia sin filtro para búsqueda
let nextId = { cobrar: 10, pagar: 10, caja: 10 };

// ── CFG COBRAR ───────────────────────────────────────────────────────────
const CFG_COBRAR = {
    tipo: 'cobrar',
    label: 'Cliente',
    labelPlural: 'clientes',
    color: 'var(--cobrar-head)',
    db: () => dbCobrar,
    setDb: v => { dbCobrar = v; },
    resetNextId: n => { nextId.cobrar = n; },
    incId: () => nextId.cobrar++,
    // Tabla
    tbody: 'bodyCobar',
    totalEl: 'totalCobrar',
    kpiVal: 'kpiCobrar',
    kpiSub: 'kpiCobrarSub',
    // Modales
    modalRegistro: 'modalRegistro',
    modalDetalle: 'modalDetalle',
    modalExcel: 'modalExcel',
    modalConfirmar: 'modalConfirmarEliminar',
    // Modal registro — header / titulo / botón
    hdrRegistro: 'modalHeader',
    tituloRegistro: 'modalTitulo',
    btnGuardar: 'btnGuardarModal',
    fieldsEl: 'fieldsCobrar',
    fieldsElOtro: 'fieldsPagar',
    fieldElDoc: 'fieldsCobrarDoc',
    // Inputs Cobrar
    inputNombre: 'cobrarCliente',
    inputRut: 'cobrarRut',
    inputMonto: 'cobrarMonto',
    inputTipoDoc: 'cobrarTipoDoc',
    inputNroDoc: 'cobrarNroDoc',
    inputFechaDoc: 'cobrarFechaDoc',
    inputPagadoDoc: 'cobrarPagadoDoc',
    inputImpagoDoc: 'cobrarImpagoDoc',
    montoAviso: 'cobrarMontoAviso',
    montoAvisoTxt: 'cobrarMontoAvisoTexto',
    // Detalle
    detalleHeader: 'detalleHeader',
    detalleTitulo: 'detalleTitulo',
    detalleBody: 'detalleBody',
    // Confirmar eliminar
    delNombre: 'delConfirmNombre',
    delDetalle: 'delConfirmDetalle',
    btnConfirmarOk: 'btnConfirmarEliminarOk',
    // Botón sync BD
    btnGuardarBD: 'btnGuardarBD',
    // Excel
    modalExcel: 'modalExcel',
    excelPreviewResumen: 'excelPreviewResumen',
    excelPreviewDetalle: 'excelPreviewDetalle',
    excelResumenCount: 'excelResumenCount',
    excelPreviewWrapper: 'excelPreviewWrapper',
    btnImportarExcel: 'btnImportarExcel',
    excelFileInput: 'excelFileInput',
    excelFileName: 'excelFileName',
    excelFileNameText: 'excelFileNameText',
    excelDropZone: 'excelDropZone',
    // Endpoints
    endpointPendientes: '/cuentas-cobrar/pendientes',
    endpointSincronizar: '/cuentas-cobrar/sincronizar',
    endpointEliminar: '/cuentas-cobrar/eliminar',
    endpointBuscar: '/clientes/buscar?q=',
    endpointVerificarDoc: '/cuentas-cobrar/verificar-documento?numero=',
    onRecalc: () => recalcNeto(),
};

// ── CFG PAGAR ────────────────────────────────────────────────────────────
const CFG_PAGAR = {
    tipo: 'pagar',
    label: 'Proveedor',
    labelPlural: 'proveedores',
    color: 'var(--pagar-head)',
    db: () => dbPagar,
    setDb: v => { dbPagar = v; },
    resetNextId: n => { nextId.pagar = n; },
    incId: () => nextId.pagar++,
    // Tabla
    tbody: 'bodyPagar',
    totalEl: 'totalPagar',
    kpiVal: 'kpiPagar',
    kpiSub: 'kpiPagarSub',
    // Modales — IDs propios para Pagar
    modalRegistro: 'modalRegistroPagar',
    modalDetalle: 'modalDetallePagar',
    modalExcel: 'modalExcelPagar',
    modalConfirmar: 'modalConfirmarEliminarPagar',
    // Modal registro Pagar — header / titulo / botón
    hdrRegistro: 'modalHeaderPagar',
    tituloRegistro: 'modalTituloPagar',
    btnGuardar: 'btnGuardarModalPagar',
    fieldsEl: 'fieldsPagarModal',
    fieldsElOtro: null,    // modal propio, no hay otro campo que ocultar
    fieldElDoc: 'fieldsPagarDoc',
    // Inputs Pagar
    inputNombre: 'pagarProveedor',
    inputRut: 'pagarRut',
    inputMonto: 'pagarMonto',
    inputTipoDoc: 'pagarTipoDoc',
    inputNroDoc: 'pagarNroDoc',
    inputFechaDoc: 'pagarFechaDoc',
    inputPagadoDoc: 'pagarPagadoDoc',
    inputImpagoDoc: 'pagarImpagoDoc',
    montoAviso: 'pagarMontoAviso',
    montoAvisoTxt: 'pagarMontoAvisoTexto',
    // Detalle
    detalleHeader: 'detalleHeaderPagar',
    detalleTitulo: 'detalleTituloPagar',
    detalleBody: 'detalleBodyPagar',
    // Confirmar eliminar
    delNombre: 'delConfirmNombrePagar',
    delDetalle: 'delConfirmDetallePagar',
    btnConfirmarOk: 'btnConfirmarEliminarOkPagar',
    // Botón sync BD
    btnGuardarBD: 'btnGuardarBDPagar',
    // Excel Pagar
    excelPreviewResumen: 'excelPreviewResumenPagar',
    excelPreviewDetalle: 'excelPreviewDetallePagar',
    excelResumenCount: 'excelResumenCountPagar',
    excelPreviewWrapper: 'excelPreviewWrapperPagar',
    btnImportarExcel: 'btnImportarExcelPagar',
    excelFileInput: 'excelFileInputPagar',
    excelFileName: 'excelFileNamePagar',
    excelFileNameText: 'excelFileNameTextPagar',
    excelDropZone: 'excelDropZonePagar',
    // Endpoints
    endpointPendientes: '/cuentas-pagar/pendientes',
    endpointSincronizar: '/cuentas-pagar/sincronizar',
    endpointEliminar: '/cuentas-pagar/eliminar',
    endpointBuscar: '/proveedores/buscar?q=',
    endpointVerificarDoc: '/cuentas-pagar/verificar-documento?numero=',
    endpointAbonos: '/cuentas-pagar/abonos',
    endpointComentarioDoc: '/cuentas-pagar/comentario-doc',
    onRecalc: () => recalcNeto(),
};

// Exponer CFGs globalmente para que DocumentosModule pueda acceder desde onclicks
window._CFG_COBRAR = CFG_COBRAR;
window._CFG_PAGAR = CFG_PAGAR;

// ════════════════════════════════════════════════════════════════════════
//  CAJA / INVENTARIO — carga desde tbl_productos vía API
// ════════════════════════════════════════════════════════════════════════
async function cargarInventario() {
    const tbody = document.getElementById('bodyCaja');
    try {
        const r = await fetch(window.BD_BASE_URL.replace('index.php', '') + 'inventario/productos');
        const j = await r.json();
        dbCajaFull = (j.data || []).map((p, i) => ({
            idx: i,
            sku:           p.sku,
            nombre:        p.nombre,
            categoria:     p.categoria  || 'Sin categoría',
            marca:         p.marca      || '—',
            costo_neto:    parseFloat(p.costo_neto)    || 0,
            precio:        parseFloat(p.precio_con_iva) || 0,
            monto_iva:     parseFloat(p.monto_iva)     || 0,
            stock:         parseFloat(p.stock)         || 0,
            stock_reservado: parseFloat(p.stock_reservado) || 0,
        }));
        dbCaja = [...dbCajaFull];
        renderCaja();
    } catch(e) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:20px;color:#dc2626;font-size:.80rem;">
            <i class="bi bi-exclamation-triangle me-1"></i>Error al cargar inventario.
        </td></tr>`;
    }
}

function renderCaja() {
    const tbody = document.getElementById('bodyCaja');
    tbody.innerHTML = '';
    
    // Calcular el total completo del inventario basado en dbCajaFull (sin filtrar)
    const totalInventarioFull = dbCajaFull.reduce((s, r) => s + (r.precio * Math.max(0, r.stock - r.stock_reservado)), 0);

    if (!dbCaja.length) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:24px;color:#94a3b8;font-size:.80rem;">
            <i class="bi bi-inbox" style="display:block;font-size:1.5rem;margin-bottom:6px;opacity:.25;"></i>
            Sin productos coincidentes en el filtro.
        </td></tr>`;
    } else {
        dbCaja.forEach(r => {
            const disponible = Math.max(0, r.stock - r.stock_reservado);
            const monto = r.precio * disponible;
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="font-size:.80rem;font-weight:600;color:#1a2940;">${r.nombre}</td>
                <td class="text-end" style="font-size:.80rem;color:#0891b2;font-weight:600;">${PortalApp.fmt(r.precio)}</td>
                <td class="text-end" style="font-size:.80rem;font-weight:600;">${disponible}</td>
                <td class="text-end" style="font-size:.80rem;font-weight:600;color:#4f46e5;">${r.stock_reservado}</td>
                <td class="text-end amt-caja" style="font-size:.80rem;font-weight:700;">${PortalApp.fmt(monto)}</td>
                <td class="text-center">
                    <div class="d-flex gap-1 justify-content-center">
                        <button class="btn-act" title="Visualizar" onclick="verProductoCaja('${r.sku}')">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn-act add" title="Editar stock" onclick="editarProductoCaja('${r.sku}')">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                        <button class="btn-act del" title="Quitar de lista" onclick="eliminarCaja('${r.sku}')">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </td>`;
            tbody.appendChild(tr);
        });
    }

    // Mantener los Totales y KPIs con la totalidad real del inventario (dbCajaFull)
    document.getElementById('totalCaja').textContent = PortalApp.fmt(totalInventarioFull);
    document.getElementById('kpiCaja').textContent    = PortalApp.fmt(totalInventarioFull);
    document.getElementById('kpiCajaSub').textContent = `${dbCajaFull.length} ítem${dbCajaFull.length !== 1 ? 's' : ''}`;
    recalcNeto();
}

function filtrarCaja(q) {
    const txt = (q || '').toLowerCase();
    dbCaja = txt
        ? dbCajaFull.filter(r => r.nombre.toLowerCase().includes(txt) || r.sku.toLowerCase().includes(txt) || r.categoria.toLowerCase().includes(txt))
        : [...dbCajaFull];
    renderCaja();
}

function recargarInventario() {
    document.getElementById('cajaBusqueda').value = '';
    cargarInventario();
}

async function eliminarCaja(sku) {
    if (!confirm('¿Eliminar este producto del inventario? Esta acción no se puede deshacer.')) return;
    try {
        const url = window.BD_BASE_URL.replace('index.php', '') + 'bodega/producto/' + encodeURIComponent(sku);
        const resp = await fetch(url, { method: 'DELETE' });
        const json = await resp.json();
        if (!json.success) throw new Error(json.message || 'Error al eliminar');
        dbCajaFull = dbCajaFull.filter(r => r.sku !== sku);
        dbCaja     = dbCaja.filter(r => r.sku !== sku);
        renderCaja();
        PortalApp.toast('Ítem eliminado del inventario', 'danger');
    } catch (e) {
        PortalApp.toast('Error: ' + e.message, 'danger');
    }
}

function verProductoCaja(sku) {
    const r = dbCajaFull.find(x => x.sku === sku);
    if (!r) return;
    const fila = (lbl, val, color) =>
        `<tr>
            <td style="padding:9px 0;border-bottom:1px solid #f0f4f9;color:#64748b;font-size:.80rem;width:45%;">${lbl}</td>
            <td style="padding:9px 0;border-bottom:1px solid #f0f4f9;font-weight:700;color:${color||'#1a2940'};font-size:.80rem;">${val}</td>
        </tr>`;
    document.getElementById('verProductoBody').innerHTML = `
        <div style="text-align:center;margin-bottom:16px;">
            <div style="width:50px;height:50px;border-radius:14px;background:linear-gradient(135deg,#0891b2,#06b6d4);
                display:flex;align-items:center;justify-content:center;
                font-size:1.4rem;color:#fff;margin:0 auto 8px;">
                <i class="bi bi-box-seam-fill"></i>
            </div>
            <div style="font-size:.95rem;font-weight:800;color:#1a2940;">${r.nombre}</div>
            <span style="font-size:.70rem;font-weight:700;padding:2px 9px;border-radius:20px;
                background:#ecfeff;color:#0891b2;border:1px solid #a5f3fc;display:inline-block;margin-top:4px;">
                IVA INCLUÍDO
            </span>
        </div>
        <table style="width:100%;border-collapse:collapse;">
            ${fila('SKU', r.sku)}
            ${fila('Categoría', r.categoria)}
            ${fila('Marca', r.marca)}
            ${fila('Stock bodega', r.stock + ' unidades')}
            ${fila('Stock reservado', r.stock_reservado + ' unidades')}
            ${fila('Costo neto', PortalApp.fmt(r.costo_neto), '#64748b')}
            ${fila('Monto IVA (19%)', PortalApp.fmt(r.monto_iva), '#d97706')}
            ${fila('Precio c/IVA', PortalApp.fmt(r.precio), '#0891b2')}
            ${fila('Total en stock', PortalApp.fmt(r.precio * r.stock), '#16a34a')}
        </table>`;
    new bootstrap.Modal(document.getElementById('modalVerProducto')).show();
}

function editarProductoCaja(sku) {
    const r = dbCajaFull.find(x => x.sku === sku);
    if (!r) return;
    document.getElementById('editProdSku').value       = r.sku;
    document.getElementById('editProdNombre').textContent  = r.nombre;
    document.getElementById('editProdSkuLabel').textContent = r.sku;
    document.getElementById('editProdPrecio').textContent  = PortalApp.fmt(r.precio);
    document.getElementById('editProdStock').value     = r.stock;
    document.getElementById('editProdReservado').value = r.stock_reservado || 0;
    cargarReservasResumenEditModal(r.sku);
    new bootstrap.Modal(document.getElementById('modalEditarProducto')).show();
}

async function guardarEdicionProducto() {
    const sku   = document.getElementById('editProdSku').value;
    const stock = parseFloat(document.getElementById('editProdStock').value) || 0;

    const btn = document.getElementById('btnGuardarEdicionProducto');
    const originalHtml = btn ? btn.innerHTML : '';
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...'; }

    try {
        const url = window.BD_BASE_URL.replace('index.php', '') + 'bodega/producto/' + encodeURIComponent(sku);
        const resp = await fetch(url, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ stock_bodega_ppral: stock }),
        });
        const json = await resp.json();
        if (!json.success) throw new Error(json.message || 'Error al guardar');

        // Actualizar memoria local
        const r = dbCajaFull.find(x => x.sku === sku);
        if (r) r.stock = stock;
        const rc = dbCaja.find(x => x.sku === sku);
        if (rc) rc.stock = stock;

        bootstrap.Modal.getInstance(document.getElementById('modalEditarProducto')).hide();
        renderCaja();
        PortalApp.toast('Stock actualizado correctamente', 'success');
    } catch (e) {
        PortalApp.toast('Error al guardar: ' + e.message, 'danger');
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
    }
}

// ════════════════════════════════════════════════════════════════════════
//  KPI NETO
// ════════════════════════════════════════════════════════════════════════
function recalcNeto() {
    const cobrar = dbCobrar.reduce((s, r) => s + (r.monto || 0), 0);
    const caja   = dbCajaFull.reduce((s, r) => s + (r.precio * Math.max(0, r.stock - r.stock_reservado)), 0);
    const pagar  = dbPagar.reduce((s, r) => s + (r.monto || 0), 0);
    const neto   = cobrar + caja - pagar;
    const el = document.getElementById('kpiNeto');
    if (el) { el.textContent = PortalApp.fmt(neto); el.style.color = neto >= 0 ? '#16a34a' : '#dc2626'; }
}

// ══════════════════════════════════════════════════════════════════════════
//  WRAPPERS PÚBLICOS — para onclicks en HTML (para compatibilidad)
// ══════════════════════════════════════════════════════════════════════════

// ── Cobrar ──
const guardarEnBD = () => DocumentosModule.guardarEnBD(CFG_COBRAR);
const abrirModalExcel = () => DocumentosModule.abrirModalExcel(CFG_COBRAR);
const importarExcel = () => DocumentosModule.importarExcel(CFG_COBRAR);
const guardarRegistro = () => DocumentosModule.guardarRegistro(CFG_COBRAR);
function confirmarEliminarCobrar() { DocumentosModule.confirmarEliminar(CFG_COBRAR); }
function filtrarCobrar(q) { DocumentosModule.filtrar(CFG_COBRAR, q); }

// ── Pagar ──
const guardarEnBDPagar = () => DocumentosModule.guardarEnBD(CFG_PAGAR);
const abrirModalExcelPagar = () => DocumentosModule.abrirModalExcel(CFG_PAGAR);
const importarExcelPagar = () => DocumentosModule.importarExcel(CFG_PAGAR);
const guardarRegistroPagar = () => DocumentosModule.guardarRegistro(CFG_PAGAR);
function confirmarEliminarPagar() { DocumentosModule.confirmarEliminar(CFG_PAGAR); }
function filtrarPagar(q) { DocumentosModule.filtrar(CFG_PAGAR, q); }

// ── Abrir modales desde botones del panel ──
function abrirModalAgregarCobrar() { DocumentosModule.abrirModalAgregar(CFG_COBRAR); }
function abrirModalAgregarPagar() { DocumentosModule.abrirModalAgregar(CFG_PAGAR); }

// ── Excel helpers drag&drop Cobrar ──
function dragOver(e) { e.preventDefault(); document.getElementById(CFG_COBRAR.excelDropZone).style.borderColor = '#22c55e'; document.getElementById(CFG_COBRAR.excelDropZone).style.background = '#dcfce7'; }
function dragLeave(e) { document.getElementById(CFG_COBRAR.excelDropZone).style.borderColor = '#86efac'; document.getElementById(CFG_COBRAR.excelDropZone).style.background = '#f0fdf4'; }
function dropFile(e) { e.preventDefault(); dragLeave(e); const f = e.dataTransfer.files[0]; if (f) DocumentosModule.leerExcel(CFG_COBRAR, f); }
function leerExcel(file) { DocumentosModule.leerExcel(CFG_COBRAR, file); }

// ── Excel helpers drag&drop Pagar ──
function dragOverPagar(e) { e.preventDefault(); document.getElementById(CFG_PAGAR.excelDropZone)?.style && (document.getElementById(CFG_PAGAR.excelDropZone).style.borderColor = '#f59e0b'); }
function dragLeavePagar(e) { if (document.getElementById(CFG_PAGAR.excelDropZone)) document.getElementById(CFG_PAGAR.excelDropZone).style.borderColor = ''; }
function dropFilePagar(e) { e.preventDefault(); dragLeavePagar(e); const f = e.dataTransfer.files[0]; if (f) DocumentosModule.leerExcel(CFG_PAGAR, f); }
function leerExcelPagar(file) { DocumentosModule.leerExcel(CFG_PAGAR, file); }

// ── Tab excel helper ──
function mostrarTabExcel(tab) {
    document.getElementById('tabResumen').style.display = tab === 'resumen' ? '' : 'none';
    document.getElementById('tabDetalle').style.display = tab === 'detalle' ? '' : 'none';
    document.getElementById('tabResumenBtn').classList.toggle('active', tab === 'resumen');
    document.getElementById('tabDetalleBtn').classList.toggle('active', tab === 'detalle');
}
function mostrarTabExcelPagar(tab) {
    document.getElementById('tabResumenPagar')?.style && (document.getElementById('tabResumenPagar').style.display = tab === 'resumen' ? '' : 'none');
    document.getElementById('tabDetallePagar')?.style && (document.getElementById('tabDetallePagar').style.display = tab === 'detalle' ? '' : 'none');
}

// ── Recalc impago en formulario ──
function recalcImpago() {
    const monto = parseFloat(document.getElementById('cobrarMonto')?.value) || 0;
    const pagado = parseFloat(document.getElementById('cobrarPagadoDoc')?.value) || 0;
    const el = document.getElementById('cobrarImpagoDoc');
    if (el) el.value = Math.max(0, monto - pagado);
}
function recalcImpagoPagar() {
    const monto = parseFloat(document.getElementById('pagarMonto')?.value) || 0;
    const pagado = parseFloat(document.getElementById('pagarPagadoDoc')?.value) || 0;
    const el = document.getElementById('pagarImpagoDoc');
    if (el) el.value = Math.max(0, monto - pagado);
}

// ── Caja ──
function ajustarStock(id, delta) {
    const r = dbCaja.find(x => x.id === id);
    if (!r) return;
    r.stock = Math.max(0, r.stock + delta);
    renderCaja();
}
function clonarCaja(id) {
    const r = dbCaja.find(x => x.id === id);
    if (!r) return;
    dbCaja.push({ id: nextId.caja++, sku: r.sku + ' (copia)', precio: r.precio, stock: r.stock });
    renderCaja();
    PortalApp.toast('Ítem duplicado', 'success');
}
// (eliminado - se usa eliminarCaja(sku) con llamada real a la BD)
function addCajaRow() { document.getElementById('formCaja').style.display = ''; }
function cancelarCajaRow() { document.getElementById('formCaja').style.display = 'none'; }
function confirmarCajaRow() {
    const sku = document.getElementById('cajaSku').value.trim();
    const precio = parseFloat(document.getElementById('cajaPrecio').value) || 0;
    const stock = parseInt(document.getElementById('cajaStock').value) || 0;
    if (!sku) { PortalApp.toast('Ingrese descripción o SKU', 'warning'); return; }
    dbCaja.push({ id: nextId.caja++, sku, precio, stock });
    renderCaja();
    document.getElementById('cajaSku').value = '';
    document.getElementById('cajaPrecio').value = '';
    document.getElementById('cajaStock').value = '';
    document.getElementById('formCaja').style.display = 'none';
    PortalApp.toast('Ítem agregado', 'success');
}

// ── Misc ──
function exportarBalance() { PortalApp.toast('Exportando balance diario...', 'info'); }

// ── Maximizar Panel ──
function maximizarPanel(tipo) {
    const modalEl = document.getElementById('modalMaximizar');
    const header = document.getElementById('maxModalHeader');
    const titulo = document.getElementById('maxModalTitulo');
    const body = document.getElementById('maxModalBody');
    
    if (!modalEl || !body) return;
    
    let color = 'var(--cobrar-head)';
    let tituloTxt = 'Cuentas por Cobrar (Maximizado)';
    let contentHtml = '';
    
    if (tipo === 'cobrar') {
        color = 'var(--cobrar-head)';
        tituloTxt = '<i class="bi bi-arrow-down-circle-fill me-2"></i>Cuentas por Cobrar (Maximizado)';
        contentHtml = renderMaxizadoCobrar();
    } else if (tipo === 'pagar') {
        color = 'var(--pagar-head)';
        tituloTxt = '<i class="bi bi-arrow-up-circle-fill me-2"></i>Cuentas por Pagar (Maximizado)';
        contentHtml = renderMaxizadoPagar();
    } else if (tipo === 'caja') {
        color = 'var(--caja-head)';
        tituloTxt = '<i class="bi bi-box-seam-fill me-2"></i>Caja / Inventario (Maximizado)';
        contentHtml = renderMaxizadoCaja();
    }
    
    if (header) header.style.background = color;
    if (titulo) titulo.innerHTML = tituloTxt;
    body.innerHTML = contentHtml;
    
    new bootstrap.Modal(modalEl).show();
}

function cerrarModalMaximizado() {
    const el = document.getElementById('modalMaximizar');
    if (el) {
        const inst = bootstrap.Modal.getInstance(el);
        if (inst) inst.hide();
    }
}

function renderMaxizadoCobrar() {
    let html = `
        <table class="tbl table table-borderless table-hover">
            <thead>
                <tr style="background:#f8fafc; border-bottom:1px solid #e5eaf0;">
                    <th style="width:50px; padding:12px;">#</th>
                    <th style="padding:12px;">Cliente</th>
                    <th style="padding:12px;">RUT</th>
                    <th class="text-end" style="padding:12px;">Monto Total</th>
                    <th class="text-center" style="width:150px; padding:12px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
    `;
    if (!dbCobrar.length) {
        html += `<tr><td colspan="5" style="text-align:center;padding:24px;color:#94a3b8;"><i class="bi bi-inbox me-1"></i>No hay cuentas por cobrar con saldo pendiente.</td></tr>`;
    } else {
        dbCobrar.forEach((r, i) => {
            html += `
                <tr style="border-bottom: 1px solid #f0f4f9;">
                    <td style="color:#b0bec5; padding:12px; vertical-align:middle;">${i + 1}</td>
                    <td style="font-weight:600; padding:12px; vertical-align:middle;">${r.nombre || r.cliente || '—'}</td>
                    <td style="padding:12px; vertical-align:middle;">${r.rut || '—'}</td>
                    <td class="text-end" style="font-weight:700; color:var(--cobrar-head); padding:12px; vertical-align:middle;">${PortalApp.fmt(r.monto || 0)}</td>
                    <td class="text-center" style="padding:12px; vertical-align:middle;">
                        <div class="d-flex gap-1 justify-content-center">
                            <button class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size:.75rem;" title="Ver detalle" onclick="cerrarModalMaximizado(); setTimeout(() => DocumentosModule.verDetalle(window._CFG_COBRAR, ${r.id}), 300);"><i class="bi bi-eye"></i> Detalle</button>
                            <button class="btn btn-sm btn-outline-warning py-1 px-2" style="font-size:.75rem;" title="Editar" onclick="cerrarModalMaximizado(); setTimeout(() => DocumentosModule._abrirEditarWrap('cobrar', ${r.id}), 300);"><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-sm btn-outline-danger py-1 px-2" style="font-size:.75rem;" title="Eliminar" onclick="cerrarModalMaximizado(); setTimeout(() => DocumentosModule.eliminar(window._CFG_COBRAR, ${r.id}), 300);"><i class="bi bi-trash3"></i></button>
                        </div>
                    </td>
                </tr>
            `;
        });
    }
    html += `
            </tbody>
        </table>
    `;
    return html;
}

function renderMaxizadoPagar() {
    let html = `
        <table class="tbl table table-borderless table-hover">
            <thead>
                <tr style="background:#f8fafc; border-bottom:1px solid #e5eaf0;">
                    <th style="width:50px; padding:12px;">#</th>
                    <th style="padding:12px;">Proveedor</th>
                    <th style="padding:12px;">RUT</th>
                    <th class="text-end" style="padding:12px;">Monto Total</th>
                    <th class="text-center" style="width:150px; padding:12px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
    `;
    if (!dbPagar.length) {
        html += `<tr><td colspan="5" style="text-align:center;padding:24px;color:#94a3b8;"><i class="bi bi-inbox me-1"></i>No hay cuentas por pagar con saldo pendiente.</td></tr>`;
    } else {
        dbPagar.forEach((r, i) => {
            html += `
                <tr style="border-bottom: 1px solid #f0f4f9;">
                    <td style="color:#b0bec5; padding:12px; vertical-align:middle;">${i + 1}</td>
                    <td style="font-weight:600; padding:12px; vertical-align:middle;">${r.nombre || r.proveedor || '—'}</td>
                    <td style="padding:12px; vertical-align:middle;">${r.rut || '—'}</td>
                    <td class="text-end" style="font-weight:700; color:var(--pagar-head); padding:12px; vertical-align:middle;">${PortalApp.fmt(r.monto || 0)}</td>
                    <td class="text-center" style="padding:12px; vertical-align:middle;">
                        <div class="d-flex gap-1 justify-content-center">
                            <button class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size:.75rem;" title="Ver detalle" onclick="cerrarModalMaximizado(); setTimeout(() => DocumentosModule.verDetalle(window._CFG_PAGAR, ${r.id}), 300);"><i class="bi bi-eye"></i> Detalle</button>
                            <button class="btn btn-sm btn-outline-warning py-1 px-2" style="font-size:.75rem;" title="Editar" onclick="cerrarModalMaximizado(); setTimeout(() => DocumentosModule._abrirEditarWrap('pagar', ${r.id}), 300);"><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-sm btn-outline-danger py-1 px-2" style="font-size:.75rem;" title="Eliminar" onclick="cerrarModalMaximizado(); setTimeout(() => DocumentosModule.eliminar(window._CFG_PAGAR, ${r.id}), 300);"><i class="bi bi-trash3"></i></button>
                        </div>
                    </td>
                </tr>
            `;
        });
    }
    html += `
            </tbody>
        </table>
    `;
    return html;
}

function renderMaxizadoCaja() {
    let html = `
        <div class="mb-3">
            <input type="text" id="cajaBusquedaMax" class="form-control" placeholder="🔍 Filtrar producto por nombre, SKU o categoría..." oninput="filtrarCajaMax(this.value)" style="border-radius:10px; padding:10px 16px;">
        </div>
        <div id="cajaMaxTableWrapper" style="overflow-x:auto;">
            ${renderCajaMaxTable(dbCaja)}
        </div>
    `;
    return html;
}

function renderCajaMaxTable(items) {
    let html = `
        <table class="tbl table table-borderless table-hover">
            <thead>
                <tr style="background:#f8fafc; border-bottom:1px solid #e5eaf0;">
                    <th style="padding:12px;">SKU</th>
                    <th style="padding:12px;">Descripción</th>
                    <th style="padding:12px;">Categoría</th>
                    <th class="text-end" style="padding:12px;">Precio c/IVA</th>
                    <th class="text-end" style="padding:12px;">Stock disponible</th>
                    <th class="text-end" style="padding:12px;">Stock reservado</th>
                    <th class="text-end" style="padding:12px;">Total</th>
                    <th class="text-center" style="width:160px; padding:12px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
    `;
    if (!items.length) {
        html += `<tr><td colspan="8" style="text-align:center;padding:24px;color:#94a3b8;"><i class="bi bi-inbox me-1"></i>Sin productos en inventario.</td></tr>`;
    } else {
        items.forEach(r => {
            const monto = r.precio * r.stock;
            html += `
                <tr style="border-bottom: 1px solid #f0f4f9;">
                    <td style="font-family:monospace;font-weight:600; padding:12px; vertical-align:middle;">${r.sku}</td>
                    <td style="font-weight:600; padding:12px; vertical-align:middle;">${r.nombre}</td>
                    <td style="padding:12px; vertical-align:middle;"><span class="badge" style="background:#ecfeff; color:#0891b2; border:1px solid #a5f3fc;">${r.categoria}</span></td>
                    <td class="text-end" style="color:#0891b2;font-weight:600; padding:12px; vertical-align:middle;">${PortalApp.fmt(r.precio)}</td>
                    <td class="text-end" style="font-weight:600; padding:12px; vertical-align:middle;">${r.stock}</td>
                    <td class="text-end" style="font-weight:600; padding:12px; vertical-align:middle; color:#4f46e5;">${r.stock_reservado}</td>
                    <td class="text-end amt-caja" style="font-weight:700; padding:12px; vertical-align:middle;">${PortalApp.fmt(monto)}</td>
                    <td class="text-center" style="padding:12px; vertical-align:middle;">
                        <div class="d-flex gap-1 justify-content-center">
                            <button class="btn btn-sm btn-outline-info py-1 px-2" style="font-size:.75rem;" title="Visualizar" onclick="cerrarModalMaximizado(); setTimeout(() => verProductoCaja('${r.sku}'), 300);"><i class="bi bi-eye"></i></button>
                            <button class="btn btn-sm btn-outline-warning py-1 px-2" style="font-size:.75rem;" title="Editar stock" onclick="cerrarModalMaximizado(); setTimeout(() => editarProductoCaja('${r.sku}'), 300);"><i class="bi bi-pencil-fill"></i></button>
                            <button class="btn btn-sm btn-outline-danger py-1 px-2" style="font-size:.75rem;" title="Quitar de lista" onclick="eliminarCajaMax('${r.sku}')"><i class="bi bi-trash3"></i></button>
                        </div>
                    </td>
                </tr>
            `;
        });
    }
    html += `
            </tbody>
        </table>
    `;
    return html;
}

function filtrarCajaMax(q) {
    const txt = (q || '').toLowerCase();
    const filtered = txt
        ? dbCajaFull.filter(r => r.nombre.toLowerCase().includes(txt) || r.sku.toLowerCase().includes(txt) || r.categoria.toLowerCase().includes(txt))
        : [...dbCajaFull];
    const wrapper = document.getElementById('cajaMaxTableWrapper');
    if (wrapper) {
        wrapper.innerHTML = renderCajaMaxTable(filtered);
    }
}

async function eliminarCajaMax(sku) {
    if (!confirm('¿Eliminar este producto del inventario? Esta acción no se puede deshacer.')) return;
    try {
        const url = window.BD_BASE_URL.replace('index.php', '') + 'bodega/producto/' + encodeURIComponent(sku);
        const resp = await fetch(url, { method: 'DELETE' });
        const json = await resp.json();
        if (!json.success) throw new Error(json.message || 'Error al eliminar');
        dbCajaFull = dbCajaFull.filter(r => r.sku !== sku);
        dbCaja     = dbCaja.filter(r => r.sku !== sku);
        renderCaja();
        const inputVal = document.getElementById('cajaBusquedaMax')?.value || '';
        const filtered = inputVal
            ? dbCajaFull.filter(r => r.nombre.toLowerCase().includes(inputVal.toLowerCase()) || r.sku.toLowerCase().includes(inputVal.toLowerCase()) || r.categoria.toLowerCase().includes(inputVal.toLowerCase()))
            : [...dbCajaFull];
        const wrapper = document.getElementById('cajaMaxTableWrapper');
        if (wrapper) wrapper.innerHTML = renderCajaMaxTable(filtered);
        PortalApp.toast('Ítem eliminado del inventario', 'danger');
    } catch (e) {
        PortalApp.toast('Error: ' + e.message, 'danger');
    }
}

// ══════════════════════════════════════════════════════════════════════════
//  INIT — Carga inicial desde BD
// ══════════════════════════════════════════════════════════════════════════
// INIT
cargarInventario();

// ── Reservas por Cliente JS (Balance Diario) ───────────────────
let _modalReservasObj = null;
let _timeoutAutocomplete = null;

function buscarClientesAutocomplete(q) {
    clearTimeout(_timeoutAutocomplete);
    const resultsEl = document.getElementById('resAutocompleteResults');
    resultsEl.style.display = 'none';
    resultsEl.innerHTML = '';
    document.getElementById('resRutCliente').value = '';

    if (q.trim().length < 2) return;

    _timeoutAutocomplete = setTimeout(async () => {
        try {
            const resp = await fetch(window.BD_BASE_URL.replace('index.php', '') + 'clientes/buscar?q=' + encodeURIComponent(q));
            const data = await resp.json();
            
            if (data && data.length > 0) {
                resultsEl.innerHTML = data.map(c => {
                    const text = `${c.nombre || c.razon_social} (${c.rut})`;
                    return `<div class="p-2 border-bottom autocomplete-item" style="cursor:pointer; font-size:.80rem;" onclick="seleccionarClienteAutocomplete('${c.rut}', '${(c.nombre || c.razon_social).replace(/'/g, "\\'")}')">${text}</div>`;
                }).join('');
                resultsEl.style.display = 'block';
            } else {
                resultsEl.innerHTML = '<div class="p-2 text-muted" style="font-size:.80rem;">Sin resultados</div>';
                resultsEl.style.display = 'block';
            }
        } catch (e) {
            console.error('Error fetching autocomplete:', e);
        }
    }, 300);
}

function seleccionarClienteAutocomplete(rut, nombre) {
    document.getElementById('resBuscarCliente').value = `${nombre} (${rut})`;
    document.getElementById('resRutCliente').value = rut;
    document.getElementById('resAutocompleteResults').style.display = 'none';
}

async function abrirSubModalReservasCaja() {
    const sku = document.getElementById('editProdSku').value;
    if (!sku) return;
    
    document.getElementById('resSku').textContent = sku;
    const prodNombre = document.getElementById('editProdNombre').textContent;
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
    
    await cargarReservasCliente(sku);
}

async function cargarReservasCliente(sku) {
    const tbody = document.getElementById('tbodyReservasCliente');
    tbody.innerHTML = '<tr><td colspan="3" class="text-center py-3 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Cargando reservas...</td></tr>';
    
    try {
        const resp = await fetch(window.BD_BASE_URL.replace('index.php', '') + 'bodega/reservas/' + encodeURIComponent(sku));
        const json = await resp.json();
        
        if (json.success && json.reservas.length > 0) {
            tbody.innerHTML = json.reservas.map(r => {
                const nombre = r.nombre_cliente || 'Cliente sin nombre';
                const rut = r.rut_cliente;
                const cant = parseFloat(r.cantidad) || 0;
                
                return `
                <tr>
                    <td class="ps-3">
                        <div style="font-weight:600; color:#1e293b;">${nombre}</div>
                        <div style="font-size:.70rem; color:#94a3b8;">${rut}</div>
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
    const sku = document.getElementById('editProdSku').value;
    const rut = document.getElementById('resRutCliente').value;
    const cantidad = parseFloat(document.getElementById('resCantidad').value);
    const errEl = document.getElementById('resError');
    errEl.style.display = 'none';

    if (!sku) return;
    if (!rut) { errEl.textContent = 'Seleccione un cliente válido de la lista.'; errEl.style.display = 'block'; return; }
    if (!cantidad || cantidad <= 0) { errEl.textContent = 'La cantidad debe ser mayor a 0.'; errEl.style.display = 'block'; return; }

    try {
        const resp = await fetch(window.BD_BASE_URL.replace('index.php', '') + 'bodega/reservas', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ sku, rut_cliente: rut, cantidad })
        });
        const json = await resp.json();
        
        if (json.success) {
            document.getElementById('editProdReservado').value = json.stock_reservado;
            
            const p = dbCajaFull.find(x => x.sku === sku);
            if (p) p.stock_reservado = json.stock_reservado;
            const pc = dbCaja.find(x => x.sku === sku);
            if (pc) pc.stock_reservado = json.stock_reservado;
            
            await cargarReservasCliente(sku);
            cargarReservasResumenEditModal(sku);
            
            document.getElementById('resBuscarCliente').value = '';
            document.getElementById('resRutCliente').value = '';
            document.getElementById('resCantidad').value = '';
            
            renderCaja();
        } else {
            errEl.textContent = json.message || 'Error al guardar la reserva.';
            errEl.style.display = 'block';
        }
    } catch (e) {
        errEl.textContent = 'Error de conexión.'; errEl.style.display = 'block';
    }
}

async function descontarReservaPrompt(id, cantActual, sku) {
    const input = prompt(`Descontar cantidad de la reserva:\nCantidad actual: ${cantActual.toLocaleString('es-CL')}\n\nIngresa la cantidad a restar:`);
    if (input === null) return;
    const cantidad = parseFloat(input);
    if (isNaN(cantidad) || cantidad <= 0) {
        alert('Cantidad inválida.');
        return;
    }

    try {
        const resp = await fetch(window.BD_BASE_URL.replace('index.php', '') + 'bodega/reservas/descontar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_reserva: id, cantidad })
        });
        const json = await resp.json();
        
        if (json.success) {
            document.getElementById('editProdReservado').value = json.stock_reservado;
            
            const p = dbCajaFull.find(x => x.sku === sku);
            if (p) p.stock_reservado = json.stock_reservado;
            const pc = dbCaja.find(x => x.sku === sku);
            if (pc) pc.stock_reservado = json.stock_reservado;
            
            await cargarReservasCliente(sku);
            cargarReservasResumenEditModal(sku);
            renderCaja();
        } else {
            alert(json.message || 'Error al descontar stock.');
        }
    } catch(e) {
        alert('Error de conexión.');
    }
}

async function eliminarReservaCliente(id, sku) {
    if (!confirm('¿Está seguro de eliminar esta reserva por completo?')) return;

    try {
        const resp = await fetch(window.BD_BASE_URL.replace('index.php', '') + 'bodega/reservas/' + id, {
            method: 'DELETE'
        });
        const json = await resp.json();
        
        if (json.success) {
            document.getElementById('editProdReservado').value = json.stock_reservado;
            
            const p = dbCajaFull.find(x => x.sku === sku);
            if (p) p.stock_reservado = json.stock_reservado;
            const pc = dbCaja.find(x => x.sku === sku);
            if (pc) pc.stock_reservado = json.stock_reservado;
            
            await cargarReservasCliente(sku);
            cargarReservasResumenEditModal(sku);
            renderCaja();
        } else {
            alert(json.message || 'Error al eliminar reserva.');
        }
    } catch(e) {
        alert('Error de conexión.');
    }
}

// Fix de scroll para sub-modales nested
document.addEventListener('DOMContentLoaded', function() {
    const subModalEl = document.getElementById('modalReservasCliente');
    if (subModalEl) {
        subModalEl.addEventListener('hidden.bs.modal', function () {
            if (document.getElementById('modalEditarProducto').classList.contains('show')) {
                document.body.classList.add('modal-open');
                document.body.style.overflow = 'hidden';
            }
        });
    }
});

async function cargarReservasResumenEditModal(sku) {
    const container = document.getElementById('editProdReservasContainer');
    const listaEl = document.getElementById('editProdReservasLista');
    if (!container || !listaEl) return;
    
    container.style.display = 'none';
    listaEl.innerHTML = '';
    
    try {
        const resp = await fetch(window.BD_BASE_URL.replace('index.php', '') + 'bodega/reservas/' + encodeURIComponent(sku));
        const json = await resp.json();
        
        if (json.success && json.reservas.length > 0) {
            listaEl.innerHTML = json.reservas.map(r => {
                const nombre = r.nombre_cliente || 'Cliente sin nombre';
                const cant = parseFloat(r.cantidad) || 0;
                return `
                    <div class="d-flex justify-content-between py-1 border-bottom" style="border-color:#e2e8f0 !important;">
                        <span style="font-weight:500;color:#334155;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${nombre}">${nombre}</span>
                        <span style="font-weight:700;color:#4c1d95;">${cant.toLocaleString('es-CL')}</span>
                    </div>
                `;
            }).join('');
            
            if (listaEl.lastElementChild) {
                listaEl.lastElementChild.style.setProperty('border-bottom', 'none', 'important');
            }
            container.style.display = 'block';
        }
    } catch(e) {
        console.error('Error al cargar resumen de reservas en modal:', e);
    }
}
DocumentosModule.cargarDesdeBD(CFG_COBRAR);
DocumentosModule.cargarDesdeBD(CFG_PAGAR);

