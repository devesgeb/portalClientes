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
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:20px;color:#dc2626;font-size:.80rem;">
            <i class="bi bi-exclamation-triangle me-1"></i>Error al cargar inventario.
        </td></tr>`;
    }
}

function renderCaja() {
    const tbody = document.getElementById('bodyCaja');
    tbody.innerHTML = '';
    let total = 0;

    if (!dbCaja.length) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:24px;color:#94a3b8;font-size:.80rem;">
            <i class="bi bi-inbox" style="display:block;font-size:1.5rem;margin-bottom:6px;opacity:.25;"></i>
            Sin productos en inventario.
        </td></tr>`;
        document.getElementById('totalCaja').textContent = PortalApp.fmt(0);
        document.getElementById('kpiCaja').textContent = PortalApp.fmt(0);
        document.getElementById('kpiCajaSub').textContent = '0 ítems';
        recalcNeto();
        return;
    }

    dbCaja.forEach(r => {
        const monto = r.precio * r.stock;
        total += monto;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="font-size:.80rem;font-weight:600;color:#1a2940;">${r.nombre}</td>
            <td class="text-end" style="font-size:.80rem;color:#0891b2;font-weight:600;">${PortalApp.fmt(r.precio)}</td>
            <td class="text-end" style="font-size:.80rem;font-weight:600;">${r.stock}</td>
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

    document.getElementById('totalCaja').textContent = PortalApp.fmt(total);
    document.getElementById('kpiCaja').textContent    = PortalApp.fmt(total);
    document.getElementById('kpiCajaSub').textContent = `${dbCaja.length} ítem${dbCaja.length !== 1 ? 's' : ''}`;
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

function eliminarCaja(sku) {
    if (!confirm('¿Quitar este producto de la vista de inventario?')) return;
    dbCajaFull = dbCajaFull.filter(r => r.sku !== sku);
    dbCaja     = dbCaja.filter(r => r.sku !== sku);
    renderCaja();
    PortalApp.toast('Ítem quitado', 'danger');
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
    new bootstrap.Modal(document.getElementById('modalEditarProducto')).show();
}

function guardarEdicionProducto() {
    const sku   = document.getElementById('editProdSku').value;
    const stock = parseFloat(document.getElementById('editProdStock').value) || 0;
    const r = dbCajaFull.find(x => x.sku === sku);
    if (r) r.stock = stock;
    const rc = dbCaja.find(x => x.sku === sku);
    if (rc) rc.stock = stock;
    bootstrap.Modal.getInstance(document.getElementById('modalEditarProducto')).hide();
    renderCaja();
    PortalApp.toast('Stock actualizado', 'success');
}

// ════════════════════════════════════════════════════════════════════════
//  KPI NETO
// ════════════════════════════════════════════════════════════════════════
function recalcNeto() {
    const cobrar = dbCobrar.reduce((s, r) => s + (r.monto || 0), 0);
    const caja   = dbCaja.reduce((s, r) => s + (r.precio * r.stock), 0);
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

// ── Pagar ──
const guardarEnBDPagar = () => DocumentosModule.guardarEnBD(CFG_PAGAR);
const abrirModalExcelPagar = () => DocumentosModule.abrirModalExcel(CFG_PAGAR);
const importarExcelPagar = () => DocumentosModule.importarExcel(CFG_PAGAR);
const guardarRegistroPagar = () => DocumentosModule.guardarRegistro(CFG_PAGAR);
function confirmarEliminarPagar() { DocumentosModule.confirmarEliminar(CFG_PAGAR); }

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
function eliminarCaja(id) {
    if (!confirm('¿Confirma eliminar este ítem?')) return;
    dbCaja = dbCaja.filter(r => r.id !== id);
    renderCaja();
    PortalApp.toast('Ítem eliminado', 'danger');
}
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

// ══════════════════════════════════════════════════════════════════════════
//  INIT — Carga inicial desde BD
// ══════════════════════════════════════════════════════════════════════════
// INIT
cargarInventario();
DocumentosModule.cargarDesdeBD(CFG_COBRAR);
DocumentosModule.cargarDesdeBD(CFG_PAGAR);

