// Fallback si jsdelivr no carga
if (typeof XLSX === 'undefined') {
    document.write('<script src="https://unpkg.com/xlsx@0.18.5/dist/xlsx.full.min.js"><\/script>');
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  DATA STORE
//  En producciÃ³n: reemplazar fetch() a la API real.
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// dbCobrar: se carga dinamicamente desde la BD al iniciar
let dbCobrar = [];

let dbCaja = [
    { id: 1, sku: "Cemento 25 Kg", precio: 8500, stock: 200 },
    { id: 2, sku: "Arena mÂ³", precio: 45000, stock: 30 },
    { id: 3, sku: "Fierro 10mm x 6m", precio: 12800, stock: 150 },
    { id: 4, sku: "Pintura lÃ¡tex 4L", precio: 18900, stock: 80 },
];

let dbPagar = [
    { id: 1, proveedor: "Proveedora Sur Ltda.", monto: 750000, notas: "Vence 28/02/2026" },
    { id: 2, proveedor: "Materiales Norte S.A.", monto: 1100000, notas: "Factura 3345 - 30 dÃ­as" },
    { id: 3, proveedor: "Servicios LogÃ­sticos", monto: 280000, notas: "" },
];

let nextId = { cobrar: 10, caja: 10, pagar: 10 };
let modalMode = 'cobrar';
let editingId = null;

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  FORMATTERS
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
const fmt = n => '$' + Math.round(n).toLocaleString('es-CL');

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  RENDER: CUENTAS POR COBRAR
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
function renderCobrar() {
    const tbody = document.getElementById('bodyCobar');
    tbody.innerHTML = '';
    let total = 0;

    if (!dbCobrar.length) {
        tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:24px;color:#94a3b8;font-size:.80rem;">
            <i class="bi bi-inbox" style="font-size:1.4rem;display:block;margin-bottom:6px;"></i>
            No hay cuentas por cobrar con saldo pendiente.
        </td></tr>`;
        document.getElementById('totalCobrar').textContent = fmt(0);
        document.getElementById('kpiCobrar').textContent = fmt(0);
        document.getElementById('kpiCobrarSub').textContent = '0 clientes';
        recalcNeto();
        return;
    }

    dbCobrar.forEach((r, i) => {
        total += r.monto;
        const tr = document.createElement('tr');
        tr.innerHTML = `
    <td style="color:#b0bec5;font-size:.72rem;">${i + 1}</td>
    <td>
        <div style="font-size:.82rem;font-weight:500;">${r.cliente}</div>
        <div style="font-size:.68rem;color:#94a3b8;">${r.rut}</div>
    </td>
    <td class="text-end amt-cobrar">${fmt(r.monto)}</td>
    <td class="text-center">
        <div class="d-flex gap-1 justify-content-center">
            <button class="btn-act add"  title="Agregar documento"  onclick="abrirModalAgregar('cobrar',${r.id})"><i class="bi bi-plus-lg"></i></button>
            <button class="btn-act det"  title="Ver detalle"        onclick="verDetalle('cobrar',${r.id})"><i class="bi bi-eye"></i></button>
            <button class="btn-act edit" title="Editar"             onclick="abrirEditar('cobrar',${r.id})"><i class="bi bi-pencil"></i></button>
            <button class="btn-act del"  title="Eliminar"           onclick="eliminar('cobrar',${r.id})"><i class="bi bi-trash3"></i></button>
        </div>
    </td>`;
        tbody.appendChild(tr);
    });

    document.getElementById('totalCobrar').textContent = fmt(total);
    document.getElementById('kpiCobrar').textContent = fmt(total);
    document.getElementById('kpiCobrarSub').textContent = `${dbCobrar.length} cliente${dbCobrar.length !== 1 ? 's' : ''}`;
    recalcNeto();
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  RENDER: CAJA
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
function renderCaja() {
    const tbody = document.getElementById('bodyCaja');
    tbody.innerHTML = '';
    let total = 0;

    dbCaja.forEach(r => {
        const monto = r.precio * r.stock;
        total += monto;
        const tr = document.createElement('tr');
        tr.innerHTML = `
    <td style="font-size:.80rem;font-weight:500;">${r.sku}</td>
    <td class="text-end" style="font-size:.80rem;">${fmt(r.precio)}</td>
    <td class="text-end">
        <span style="display:inline-flex;align-items:center;gap:5px;">
            <button class="btn-act" style="width:22px;height:22px;font-size:.70rem;" title="Reducir stock" onclick="ajustarStock(${r.id},-1)"><i class="bi bi-dash"></i></button>
            <span style="font-size:.80rem;min-width:28px;text-align:center;font-weight:600;">${r.stock}</span>
            <button class="btn-act add" style="width:22px;height:22px;font-size:.70rem;" title="AÃ±adir stock" onclick="ajustarStock(${r.id},1)"><i class="bi bi-plus"></i></button>
        </span>
    </td>
    <td class="text-end amt-caja" style="font-size:.80rem;">${fmt(monto)}</td>
    <td class="text-center">
        <div class="d-flex gap-1 justify-content-center">
            <button class="btn-act add" title="Agregar fila similar" onclick="clonarCaja(${r.id})"><i class="bi bi-plus-lg"></i></button>
            <button class="btn-act del" title="Eliminar Ã­tem"        onclick="eliminar('caja',${r.id})"><i class="bi bi-trash3"></i></button>
        </div>
    </td>`;
        tbody.appendChild(tr);
    });

    document.getElementById('totalCaja').textContent = fmt(total);
    document.getElementById('kpiCaja').textContent = fmt(total);
    document.getElementById('kpiCajaSub').textContent = `${dbCaja.length} Ã­tem${dbCaja.length !== 1 ? 's' : ''}`;
    recalcNeto();
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  RENDER: CUENTAS POR PAGAR
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
function renderPagar() {
    const tbody = document.getElementById('bodyPagar');
    tbody.innerHTML = '';
    let total = 0;

    dbPagar.forEach((r, i) => {
        total += r.monto;
        const tr = document.createElement('tr');
        tr.innerHTML = `
    <td style="color:#b0bec5;font-size:.72rem;">${i + 1}</td>
    <td style="font-size:.82rem;font-weight:500;">${r.proveedor}</td>
    <td class="text-end amt-pagar">${fmt(r.monto)}</td>
    <td>
        <textarea class="notes-input" rows="1" placeholder="Notasâ€¦"
            onchange="actualizarNota('pagar',${r.id},this.value)">${r.notas}</textarea>
    </td>
    <td class="text-center">
        <div class="d-flex gap-1 justify-content-center">
            <button class="btn-act add"  title="Agregar documento"  onclick="abrirModalAgregar('pagar',${r.id})"><i class="bi bi-plus-lg"></i></button>
            <button class="btn-act det"  title="Ver detalle"        onclick="verDetalle('pagar',${r.id})"><i class="bi bi-eye"></i></button>
            <button class="btn-act edit" title="Editar"             onclick="abrirEditar('pagar',${r.id})"><i class="bi bi-pencil"></i></button>
            <button class="btn-act del"  title="Eliminar"           onclick="eliminar('pagar',${r.id})"><i class="bi bi-trash3"></i></button>
        </div>
    </td>`;
        tbody.appendChild(tr);
    });

    document.getElementById('totalPagar').textContent = fmt(total);
    document.getElementById('kpiPagar').textContent = fmt(total);
    document.getElementById('kpiPagarSub').textContent = `${dbPagar.length} proveedor${dbPagar.length !== 1 ? 'es' : ''}`;
    recalcNeto();
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  NETO KPI
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
function recalcNeto() {
    const cobrar = dbCobrar.reduce((s, r) => s + r.monto, 0);
    const caja = dbCaja.reduce((s, r) => s + (r.precio * r.stock), 0);
    const pagar = dbPagar.reduce((s, r) => s + r.monto, 0);
    const neto = cobrar + caja - pagar;
    const el = document.getElementById('kpiNeto');
    el.textContent = fmt(neto);
    el.style.color = neto >= 0 ? '#16a34a' : '#dc2626';
}

// Recalcular impago = monto - pagado (usado en formulario nuevo cliente)
function recalcImpago() {
    const monto = parseFloat(document.getElementById('cobrarMonto')?.value) || 0;
    const pagado = parseFloat(document.getElementById('cobrarPagadoDoc')?.value) || 0;
    const el = document.getElementById('cobrarImpagoDoc');
    if (el) el.value = Math.max(0, monto - pagado);
}

// ══════════════════════════════════════════════════════
//  MODAL: Abrir Agregar / Editar
// ══════════════════════════════════════════════════════
function abrirModalAgregar(tipo, id = null) {
    modalMode = tipo;
    editingId = id;

    const header = document.getElementById('modalHeader');
    const titulo = document.getElementById('modalTitulo');
    const btnG = document.getElementById('btnGuardarModal');
    const fC = document.getElementById('fieldsCobrar');
    const fP = document.getElementById('fieldsPagar');
    const modalEl = document.getElementById('modalRegistro');

    if (tipo === 'cobrar') {
        header.style.background = 'var(--cobrar-head)';
        btnG.style.background = 'var(--cobrar-head)';
        titulo.textContent = id ? 'Editar Cliente \u2013 Cuentas por Cobrar' : 'Nuevo \u2013 Cuentas por Cobrar';
        fC.style.display = ''; fP.style.display = 'none';

        if (id) {
            const r = dbCobrar.find(x => x.id === id);
            if (r) {
                document.getElementById('cobrarCliente').value = r.cliente;
                document.getElementById('cobrarRut').value = r.rut;
                document.getElementById('cobrarMonto').value = r.monto;

                // Aplicar bloqueo DESPUÉS de que Bootstrap muestre el modal completamente
                const cantDocs = r.docs ? r.docs.length : 0;
                function aplicarBloqueoMonto() {
                    const montoInput = document.getElementById('cobrarMonto');
                    const montoAviso = document.getElementById('cobrarMontoAviso');
                    const montoAvisoTexto = document.getElementById('cobrarMontoAvisoTexto');
                    if (cantDocs > 1) {
                        montoInput.setAttribute('disabled', 'disabled');
                        montoInput.style.setProperty('background-color', '#e9ecef', 'important');
                        montoInput.style.setProperty('cursor', 'not-allowed', 'important');
                        montoInput.style.setProperty('opacity', '1', 'important');
                        if (montoAvisoTexto) montoAvisoTexto.textContent = 'El monto se calcula autom\u00e1ticamente desde los ' + cantDocs + ' documentos asociados. Para modificarlo, use Ver detalle.';
                        if (montoAviso) montoAviso.style.display = '';
                    } else {
                        montoInput.removeAttribute('disabled');
                        montoInput.style.removeProperty('background-color');
                        montoInput.style.removeProperty('cursor');
                        montoInput.style.removeProperty('opacity');
                        if (montoAviso) montoAviso.style.display = 'none';
                    }
                    modalEl.removeEventListener('shown.bs.modal', aplicarBloqueoMonto);
                }
                modalEl.addEventListener('shown.bs.modal', aplicarBloqueoMonto);
                // Ocultar campos de documento al editar
                const docPanel = document.getElementById('fieldsCobrarDoc');
                if (docPanel) docPanel.style.display = 'none';
            }
        } else {
            // Nuevo cliente: limpiar todo y mostrar campos del documento
            document.getElementById('cobrarCliente').value = '';
            document.getElementById('cobrarRut').value = '';
            document.getElementById('cobrarMonto').value = '';
            document.getElementById('cobrarTipoDoc').value = '';
            document.getElementById('cobrarNroDoc').value = '';
            document.getElementById('cobrarFechaDoc').value = new Date().toISOString().slice(0, 10);
            document.getElementById('cobrarPagadoDoc').value = '0';
            document.getElementById('cobrarImpagoDoc').value = '0';
            const docPanel = document.getElementById('fieldsCobrarDoc');
            if (docPanel) docPanel.style.display = '';
            function habilitarMonto() {
                const montoInput = document.getElementById('cobrarMonto');
                montoInput.removeAttribute('disabled');
                montoInput.style.removeProperty('background-color');
                montoInput.style.removeProperty('cursor');
                montoInput.style.removeProperty('opacity');
                const montoAviso = document.getElementById('cobrarMontoAviso');
                if (montoAviso) montoAviso.style.display = 'none';
                modalEl.removeEventListener('shown.bs.modal', habilitarMonto);
            }
            modalEl.addEventListener('shown.bs.modal', habilitarMonto);
        }
    } else {
        header.style.background = 'var(--pagar-head)';
        btnG.style.background = 'var(--pagar-head)';
        titulo.textContent = id ? 'Editar Proveedor \u2013 Cuentas por Pagar' : 'Nuevo \u2013 Cuentas por Pagar';
        fC.style.display = 'none'; fP.style.display = '';

        if (id) {
            const r = dbPagar.find(x => x.id === id);
            if (r) {
                document.getElementById('pagarProveedor').value = r.proveedor;
                document.getElementById('pagarMonto').value = r.monto;
                document.getElementById('pagarNotas').value = r.notas;
            }
        } else {
            document.getElementById('pagarProveedor').value = '';
            document.getElementById('pagarMonto').value = '';
            document.getElementById('pagarNotas').value = '';
        }
    }

    new bootstrap.Modal(document.getElementById('modalRegistro')).show();
}

function abrirEditar(tipo, id) { abrirModalAgregar(tipo, id); }

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  GUARDAR MODAL
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
function guardarRegistro() {
    if (modalMode === 'cobrar') {
        const cliente = document.getElementById('cobrarCliente').value.trim();
        const rut = document.getElementById('cobrarRut').value.trim();
        const monto = parseFloat(document.getElementById('cobrarMonto').value) || 0;
        if (!cliente) { toast('Ingrese nombre de cliente', 'warning'); return; }
        if (!rut) { toast('Ingrese el RUT del cliente', 'warning'); return; }
        if (monto <= 0) { toast('El monto debe ser mayor a 0', 'warning'); return; }

        if (editingId) {
            // Editar: actualizar nombre, rut y monto del cliente
            const r = dbCobrar.find(x => x.id === editingId);
            if (r) {
                r.cliente = cliente;
                r.rut = rut;
                // Si ya tiene docs, actualizar el rut en ellos también
                if (r.docs) r.docs.forEach(d => { d.rut = rut; });
                // Solo actualizar el monto si tiene 0 o 1 documento (no múltiples)
                const cantDocs = r.docs ? r.docs.length : 0;
                if (cantDocs <= 1) r.monto = monto;
            }
        } else {
            // Nuevo cliente: verificar que el RUT no esté duplicado
            const rutDuplicado = dbCobrar.find(x => x.rut.replace(/\s/g, '').toLowerCase() === rut.replace(/\s/g, '').toLowerCase());
            if (rutDuplicado) {
                toast(`El RUT ${rut} ya existe (cliente: "${rutDuplicado.cliente}"). Use el botón Editar para modificarlo.`, 'warning');
                return;
            }
            // Validar datos del documento
            const tipoDoc = document.getElementById('cobrarTipoDoc').value;
            const nroDoc = document.getElementById('cobrarNroDoc').value.trim();
            const fechaDoc = document.getElementById('cobrarFechaDoc').value;
            const pagado = parseFloat(document.getElementById('cobrarPagadoDoc').value) || 0;
            if (!tipoDoc) { toast('Seleccione el Tipo de Documento', 'warning'); return; }
            if (!nroDoc) { toast('Ingrese el N\u00b0 de Documento (o N/A)', 'warning'); return; }
            if (!fechaDoc) { toast('Ingrese la Fecha del documento', 'warning'); return; }
            const impago = Math.max(0, monto - pagado);
            // Convertir fecha ISO a dd/mm/yyyy para consistencia
            const [fy, fm, fd] = fechaDoc.split('-');
            const fechaFmt = fd + '/' + fm + '/' + fy;
            const nuevoId = nextId.cobrar++;
            dbCobrar.push({
                id: nuevoId,
                cliente: cliente,
                rut: rut,
                monto: monto,
                enBD: false,
                docs: [{
                    tipo: tipoDoc,
                    nro: nroDoc,
                    fecha: fechaFmt,
                    total: monto,
                    pagado: pagado,
                    impago: impago,
                    monto: monto,
                    rut: rut,
                }],
            });
        }
        renderCobrar();
    } else {
        const proveedor = document.getElementById('pagarProveedor').value.trim();
        const monto = parseFloat(document.getElementById('pagarMonto').value) || 0;
        const notas = document.getElementById('pagarNotas').value.trim();
        if (!proveedor) { toast('Ingrese nombre de proveedor', 'warning'); return; }

        if (editingId) {
            const r = dbPagar.find(x => x.id === editingId);
            if (r) { r.proveedor = proveedor; r.monto = monto; r.notas = notas; }
        } else {
            dbPagar.push({ id: nextId.pagar++, proveedor, monto, notas });
        }
        renderPagar();
    }

    bootstrap.Modal.getInstance(document.getElementById('modalRegistro')).hide();
    toast('Registro guardado. Presiona Guardar en BD para persistir.', 'warning');
    editingId = null;
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  VER DETALLE  (con ediciÃ³n de documentos para cobrar)
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

// ID del cliente actualmente abierto en el modal Detalle
let _detalleClienteId = null;

function verDetalle(tipo, id) {
    const hdr = document.getElementById('detalleHeader');
    const tit = document.getElementById('detalleTitulo');
    const body = document.getElementById('detalleBody');

    if (tipo === 'cobrar') {
        _detalleClienteId = id;
        _renderDetalleBody(id, body, hdr, tit);
    } else {
        _detalleClienteId = null;
        const r = dbPagar.find(x => x.id === id);
        hdr.style.background = 'var(--pagar-head)';
        tit.textContent = 'Detalle \u2013 Cuentas por Pagar';
        body.innerHTML = `
    <div class="row g-3">
        <div class="col-12"><div style="font-size:.70rem;color:#8fa3bc;">Proveedor</div><div style="font-size:.88rem;font-weight:600;">${r.proveedor}</div></div>
        <div class="col-12"><div style="font-size:.70rem;color:#8fa3bc;">Monto</div><div style="font-size:1.4rem;font-weight:800;color:var(--pagar-head);">${fmt(r.monto)}</div></div>
        <div class="col-12"><div style="font-size:.70rem;color:#8fa3bc;">Notas</div><div style="font-size:.84rem;color:#374151;">${r.notas || '\u2014'}</div></div>
    </div>`;
    }

    new bootstrap.Modal(document.getElementById('modalDetalle')).show();
}

function _renderDetalleBody(id, body, hdr, tit) {
    const r = dbCobrar.find(x => x.id === id);
    if (!r) return;
    if (hdr) hdr.style.background = 'var(--cobrar-head)';
    if (tit) tit.textContent = 'Detalle \u2013 Cuentas por Cobrar';

    const docs = r.docs || [];

    const docsRows = docs.length
        ? docs.map((d, idx) => `
            <tr>
                <td style="padding:6px 10px;">
                    <span class="badge" style="background:#eff6ff;color:#2563eb;font-size:.72rem;">${d.tipo || 'Sin tipo'}</span>
                </td>
                <td style="padding:6px 10px;font-size:.78rem;font-weight:500;">${d.nro || d.numero || '\u2014'}</td>
                <td style="padding:6px 10px;font-size:.76rem;color:#64748b;">${d.fecha || '\u2014'}</td>
                <td style="padding:6px 10px;text-align:right;color:#2563eb;font-weight:700;">${fmt(d.monto ?? d.total ?? 0)}</td>
                <td style="padding:6px 8px;text-align:center;">
                    <button onclick="eliminarDocDetalle(${id},${idx})"
                        title="Eliminar documento"
                        style="border:none;background:transparent;color:#dc2626;padding:2px 6px;border-radius:6px;cursor:pointer;font-size:.85rem;"
                    ><i class="bi bi-trash3"></i></button>
                </td>
            </tr>`).join('')
        : `<tr><td colspan="5" style="text-align:center;padding:14px;color:#94a3b8;font-size:.78rem;">
                <i class="bi bi-info-circle me-1"></i>Sin documentos registrados
            </td></tr>`;

    const totalMonto = docs.reduce((s, d) => s + (d.monto ?? d.total ?? 0), 0);

    body.innerHTML = `
    <div class="row g-3 mb-3">
        <div class="col-6"><div style="font-size:.70rem;color:#8fa3bc;">Cliente</div><div style="font-size:.88rem;font-weight:600;">${r.cliente}</div></div>
        <div class="col-6"><div style="font-size:.70rem;color:#8fa3bc;">RUT</div><div style="font-size:.88rem;font-weight:600;">${r.rut || '\u2014'}</div></div>
        <div class="col-12">
            <div style="font-size:.70rem;color:#8fa3bc;">Deuda Total</div>
            <div style="font-size:1.4rem;font-weight:800;color:var(--cobrar-head);">${fmt(totalMonto)}</div>
        </div>
    </div>

    <!-- Aviso de sincronizacion -->
    <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:8px 12px;font-size:.74rem;color:#92400e;margin-bottom:10px;">
        <i class="bi bi-exclamation-triangle-fill me-1 text-warning"></i>
        Los cambios en documentos se aplicarÃ¡n permanentemente al presionar <strong>Guardar en BD</strong>.
    </div>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
        <div style="font-size:.78rem;font-weight:600;color:#1a2940;">
            <i class="bi bi-receipt me-1" style="color:var(--cobrar-head);"></i>
            Documentos asociados <span class="badge" style="background:#eff6ff;color:#2563eb;margin-left:4px;">${docs.length}</span>
        </div>
        <button onclick="agregarDocDetalle(${id})"
            style="border:none;background:#eff6ff;color:#2563eb;padding:4px 10px;border-radius:8px;font-size:.74rem;font-weight:600;cursor:pointer;">
            <i class="bi bi-plus-circle me-1"></i>Agregar documento
        </button>
    </div>

    <div style="max-height:260px;overflow-y:auto;border-radius:8px;border:1px solid #e5eaf0;">
        <table class="table table-sm mb-0" style="font-size:.78rem;">
            <thead style="position:sticky;top:0;">
                <tr style="background:#f8fafc;">
                    <th style="padding:8px 10px;font-size:.68rem;color:#8fa3bc;font-weight:600;text-transform:uppercase;">Tipo</th>
                    <th style="padding:8px 10px;font-size:.68rem;color:#8fa3bc;font-weight:600;text-transform:uppercase;">NÂ° Doc.</th>
                    <th style="padding:8px 10px;font-size:.68rem;color:#8fa3bc;font-weight:600;text-transform:uppercase;">Fecha</th>
                    <th style="padding:8px 10px;font-size:.68rem;color:#8fa3bc;font-weight:600;text-transform:uppercase;text-align:right;">Monto</th>
                    <th style="padding:8px 10px;"></th>
                </tr>
            </thead>
            <tbody>${docsRows}</tbody>
            ${docs.length > 1 ? `
            <tfoot>
                <tr style="background:#f0f4f9;">
                    <td colspan="3" style="padding:8px 12px;font-size:.74rem;font-weight:700;color:#1a2940;text-align:right;">TOTAL</td>
                    <td style="padding:8px 12px;text-align:right;font-weight:800;color:var(--cobrar-head);">${fmt(totalMonto)}</td>
                    <td></td>
                </tr>
            </tfoot>` : ''}
        </table>
    </div>`;
}

/** Elimina el documento en la posiciÃ³n docIdx del cliente clienteId */
function eliminarDocDetalle(clienteId, docIdx) {
    const r = dbCobrar.find(x => x.id === clienteId);
    if (!r || !r.docs) return;

    if (r.docs.length <= 1) {
        toast('No puedes eliminar el Ãºnico documento. Usa el botÃ³n eliminar del cliente.', 'warning');
        return;
    }

    r.docs.splice(docIdx, 1);
    r.monto = r.docs.reduce((s, d) => s + (d.monto ?? d.total ?? 0), 0);

    // Re-renderizar el modal y la tabla principal
    const body = document.getElementById('detalleBody');
    _renderDetalleBody(clienteId, body, null, null);
    renderCobrar();
    toast('Documento eliminado. Recuerda presionar Guardar en BD.', 'warning');
}

/** Agrega una fila vacÃ­a de nuevo documento al cliente clienteId */
function agregarDocDetalle(clienteId) {
    const r = dbCobrar.find(x => x.id === clienteId);
    if (!r) return;

    if (!r.docs) r.docs = [];
    r.docs.push({
        tipo: 'Factura',
        nro: '',
        fecha: new Date().toLocaleDateString('es-CL'),
        monto: 0,
        total: 0,
    });

    // Re-renderizar el modal
    const body = document.getElementById('detalleBody');
    _renderDetalleBody(clienteId, body, null, null);
    toast('Nueva fila agregada. Completa los datos y luego presiona Guardar en BD.', 'warning');
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  ELIMINAR
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

// Pendiente de confirmaciÃ³n: { tipo, id }
let _eliminarPendiente = null;

function eliminar(tipo, id) {
    // Caja y Pagar: solo memoria (sin BD)
    if (tipo === 'caja') {
        if (!confirm('Â¿Confirma eliminar este Ã­tem?')) return;
        dbCaja = dbCaja.filter(r => r.id !== id);
        renderCaja();
        toast('Ãtem eliminado', 'danger');
        return;
    }
    if (tipo === 'pagar') {
        if (!confirm('Â¿Confirma eliminar este proveedor?')) return;
        dbPagar = dbPagar.filter(r => r.id !== id);
        renderPagar();
        toast('Proveedor eliminado', 'danger');
        return;
    }

    // Cobrar: modal personalizado con info del cliente
    const r = dbCobrar.find(x => x.id === id);
    if (!r) return;

    _eliminarPendiente = { tipo, id };

    const cantDocs = r.docs ? r.docs.length : 0;
    const enBD = r.enBD === true;

    document.getElementById('delConfirmNombre').textContent = r.cliente;
    document.getElementById('delConfirmDetalle').innerHTML = enBD
        ? `<span class="text-danger"><i class="bi bi-database-dash me-1"></i>Se eliminarÃ¡n <strong>${cantDocs} documento(s)</strong> de la base de datos.</span>`
        : `<span class="text-muted"><i class="bi bi-info-circle me-1"></i>Este cliente aÃºn no fue guardado en BD. Se eliminarÃ¡ solo de la pantalla.<br><small class="text-secondary"><i class="bi bi-floppy me-1"></i>Si deseas actualizar los registros de manera permanente, usa el botÃ³n <strong>Guardar en BD</strong>.</small></span>`;

    new bootstrap.Modal(document.getElementById('modalConfirmarEliminar')).show();
}

async function confirmarEliminarCobrar() {
    if (!_eliminarPendiente) return;

    const { id } = _eliminarPendiente;
    const r = dbCobrar.find(x => x.id === id);
    if (!r) return;

    const modal = bootstrap.Modal.getInstance(document.getElementById('modalConfirmarEliminar'));
    const btnConfirm = document.getElementById('btnConfirmarEliminarOk');

    // Si tiene registros en BD, eliminar desde el servidor
    if (r.enBD === true) {
        const originalText = btnConfirm.innerHTML;
        btnConfirm.disabled = true;
        btnConfirm.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Eliminandoâ€¦';

        try {
            const response = await fetch(
                (window.BD_BASE_URL || '/Portal/index.php') + '/cuentas-cobrar/eliminar',
                {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ rut: r.rut, cliente: r.cliente }), // Enviamos ambos por seguridad, el backend usa 'rut'
                }
            );
            const data = await response.json();
            if (response.ok && data.success) {
                toast(`âœ“ ${data.message}`, 'danger');
            } else {
                toast(`Error: ${data.message || 'Error al eliminar en BD.'}`, 'danger');
                btnConfirm.disabled = false;
                btnConfirm.innerHTML = originalText;
                return; // No eliminar de la UI si fallÃ³ la BD
            }
        } catch (err) {
            toast('No se pudo conectar con el servidor: ' + err.message, 'danger');
            btnConfirm.disabled = false;
            btnConfirm.innerHTML = originalText;
            return;
        }

        btnConfirm.disabled = false;
        btnConfirm.innerHTML = originalText;
    } else {
        toast('Cliente eliminado de la pantalla', 'danger');
    }

    // Eliminar del array local y refrescar
    dbCobrar = dbCobrar.filter(x => x.id !== id);
    renderCobrar();
    modal.hide();
    _eliminarPendiente = null;
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  CAJA: Inline Add Row
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
function addCajaRow() { document.getElementById('formCaja').style.display = ''; }
function cancelarCajaRow() { document.getElementById('formCaja').style.display = 'none'; }

function confirmarCajaRow() {
    const sku = document.getElementById('cajaSku').value.trim();
    const precio = parseFloat(document.getElementById('cajaPrecio').value) || 0;
    const stock = parseInt(document.getElementById('cajaStock').value) || 0;
    if (!sku) { toast('Ingrese descripciÃ³n o SKU', 'warning'); return; }
    dbCaja.push({ id: nextId.caja++, sku, precio, stock });
    renderCaja();
    document.getElementById('cajaSku').value = '';
    document.getElementById('cajaPrecio').value = '';
    document.getElementById('cajaStock').value = '';
    document.getElementById('formCaja').style.display = 'none';
    toast('Ãtem agregado', 'success');
}

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
    toast('Ãtem duplicado', 'success');
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  NOTES
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
function actualizarNota(tipo, id, val) {
    if (tipo === 'pagar') { const r = dbPagar.find(x => x.id === id); if (r) r.notas = val; }
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  TOAST
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
function toast(msg, type = 'success') {
    const colors = { success: '#22c55e', warning: '#f59e0b', danger: '#ef4444', info: '#0ea5e9' };
    const icons = { success: 'bi-check-circle-fill', warning: 'bi-exclamation-triangle-fill', danger: 'bi-trash3-fill', info: 'bi-info-circle-fill' };
    const div = document.createElement('div');
    div.style.cssText = `background:${colors[type]};color:#fff;padding:10px 18px;border-radius:10px;font-size:.80rem;font-weight:500;box-shadow:0 4px 16px rgba(0,0,0,.15);margin-top:8px;transition:opacity .3s;`;
    div.innerHTML = `<i class="bi ${icons[type]} me-2"></i>${msg}`;
    document.getElementById('toastWrapper').appendChild(div);
    setTimeout(() => { div.style.opacity = '0'; setTimeout(() => div.remove(), 300); }, 2600);
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  EXPORT (placeholder)
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
function exportarBalance() { toast('Exportando balance diario...', 'info'); }

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  EXCEL â€“ CARGA MASIVA
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
let excelAgrupado = []; // datos parseados y agrupados por cliente

function abrirModalExcel() {
    limpiarExcel();
    new bootstrap.Modal(document.getElementById('modalExcel')).show();
}

function dragOver(e) {
    e.preventDefault();
    document.getElementById('excelDropZone').style.borderColor = '#22c55e';
    document.getElementById('excelDropZone').style.background = '#dcfce7';
}
function dragLeave(e) {
    document.getElementById('excelDropZone').style.borderColor = '#86efac';
    document.getElementById('excelDropZone').style.background = '#f0fdf4';
}
function dropFile(e) {
    e.preventDefault();
    dragLeave(e);
    const file = e.dataTransfer.files[0];
    if (file) leerExcel(file);
}

function leerExcel(file) {
    if (!file) return;
    if (typeof XLSX === 'undefined') {
        toast('La librerÃ­a de Excel no cargÃ³. Verifica tu conexiÃ³n a internet e intenta recargar la pÃ¡gina.', 'danger');
        return;
    }
    const ext = file.name.split('.').pop().toLowerCase();
    if (!['xlsx', 'xls'].includes(ext)) { toast('Solo se aceptan archivos .xlsx o .xls', 'warning'); return; }

    document.getElementById('excelFileNameText').textContent = file.name;
    document.getElementById('excelFileName').style.display = '';

    const reader = new FileReader();
    reader.onload = (e) => {
        try {
            const wb = XLSX.read(e.target.result, { type: 'array', cellDates: true });
            const ws = wb.Sheets[wb.SheetNames[0]];

            // â”€â”€ Lectura cruda: arrays de arrays, todas las celdas sin excepciÃ³n
            const rawData = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });
            if (!rawData.length || rawData.length < 2) {
                toast('El archivo estÃ¡ vacÃ­o o no tiene datos vÃ¡lidos', 'warning');
                return;
            }

            // â”€â”€ Patrones para identificar columnas (definidos antes del scan)
            const patterns = {
                emisor: { regex: /empresa|razon.?social|cliente|receptor|emisor/i, label: 'Empresa / Cliente / RazÃ³n Social' },
                tipo: { regex: /tipo\s*doc/i, label: 'Tipo Documento' },
                fecha: { regex: /^fecha$/i, label: 'Fecha' },
                nro: { regex: /^numero$|^n[uÃº]mero$|^nro$|^num$/i, label: 'Numero' },
                rut: { regex: /^rut$/i, label: 'Rut' },
                impago: { regex: /impago/i, label: 'Impago' },
                pagado: { regex: /^pagado$/i, label: 'Pagado' },
                total: { regex: /^total$/i, label: 'Total' },
            };
            const allRegex = Object.values(patterns).map(p => p.regex);

            // â”€â”€ Auto-detectar en quÃ© fila estÃ¡n los encabezados (escanea hasta 15 filas)
            //    Elige la fila con mÃ¡s celdas que coincidan con algÃºn patrÃ³n esperado
            let headerRowIdx = 0;
            let maxMatches = 0;
            const scanLimit = Math.min(rawData.length, 15);
            for (let i = 0; i < scanLimit; i++) {
                const rowCells = rawData[i].map(c => String(c).trim());
                const hits = rowCells.filter(cell => allRegex.some(rx => rx.test(cell))).length;
                if (hits > maxMatches) { maxMatches = hits; headerRowIdx = i; }
            }

            // â”€â”€ Encabezados de la fila detectada
            const headers = rawData[headerRowIdx].map(h => String(h).trim());

            // DiagnÃ³stico (activar si se necesita depurar)
            // console.log('[Excel] Hoja:', wb.SheetNames[0], '| Fila headers:', headerRowIdx, '| Headers:', headers);

            // â”€â”€ Construir filas de datos como objetos { encabezado: valor }
            const rows = rawData.slice(headerRowIdx + 1)
                .map(row => {
                    const obj = {};
                    headers.forEach((h, i) => { obj[h] = row[i] !== undefined ? row[i] : ''; });
                    return obj;
                })
                .filter(row => Object.values(row).some(v => String(v).trim() !== ''));

            if (!rows.length) { toast('El archivo no tiene filas de datos vÃ¡lidas', 'warning'); return; }

            // â”€â”€ Mapear columnas detectadas contra los patrones
            const colMap = {};
            const keys = headers.filter(h => h !== '');
            keys.forEach(k => {
                Object.entries(patterns).forEach(([field, { regex }]) => {
                    if (!colMap[field] && regex.test(k)) colMap[field] = k;
                });
            });


            // â”€â”€ Columna obligatoria: emisor
            if (!colMap.emisor) {
                const columnasDetectadas = keys.length
                    ? keys.map(k => `"${k}"`).join(', ')
                    : '(ninguna)';
                toast(
                    `Columna requerida no encontrada: "${patterns.emisor.label}". ` +
                    `Columnas detectadas: ${columnasDetectadas}. ` +
                    `AsegÃºrate de que exista una columna llamada "Empresa", "Cliente", "RazÃ³n Social", "Emisor" o "Receptor".`,
                    'danger'
                );
                return;
            }

            // â”€â”€ Agrupar por Empresa + Rut, usando columna Impago como monto adeudado
            const grupos = {};
            rows.forEach(row => {
                const nombre = String(row[colMap.emisor] || '').trim();
                if (!nombre) return;
                const rut = colMap.rut ? String(row[colMap.rut]).trim() : '';
                const key = rut ? `${nombre}||${rut}` : nombre;
                if (!grupos[key]) grupos[key] = { nombre, rut, docs: [], impago: 0, pagado: 0 };

                const parseNum = v => parseFloat(String(v || '0').replace(/[^0-9.\-]/g, '')) || 0;
                const impago = colMap.impago ? parseNum(row[colMap.impago]) : 0;
                const pagado = colMap.pagado ? parseNum(row[colMap.pagado]) : 0;
                const total = colMap.total ? parseNum(row[colMap.total]) : impago + pagado;
                const fecha = colMap.fecha ? String(row[colMap.fecha]) : 'â€”';
                const tipo = colMap.tipo ? String(row[colMap.tipo]) : 'â€”';
                const nro = colMap.nro ? String(row[colMap.nro]) : 'â€”';

                grupos[key].docs.push({ tipo, nro, fecha, total, impago, pagado, rut });
                grupos[key].impago += impago;
                grupos[key].pagado += pagado;
            });

            // Solo incluir clientes con monto impago > 0
            excelAgrupado = Object.values(grupos)
                .filter(data => data.impago > 0)
                .map(data => ({
                    cliente: data.nombre,
                    rut: data.rut,
                    monto: data.impago,   // monto adeudado = columna Impago
                    pagado: data.pagado,
                    docs: data.docs,
                }));

            mostrarPreviewExcel(excelAgrupado, rows);
        } catch (err) {
            toast('Error al leer el archivo: ' + err.message, 'danger');
        }
    };
    reader.readAsArrayBuffer(file);
}

function mostrarPreviewExcel(agrupado, rows) {
    // Resumen por cliente
    const tbody = document.getElementById('excelPreviewResumen');
    tbody.innerHTML = agrupado.map((c, i) => `
        <tr>
            <td style="padding:7px 12px;color:#b0bec5;font-size:.72rem;">${i + 1}</td>
            <td style="padding:7px 12px;font-weight:500;">${c.cliente}</td>
            <td style="padding:7px 12px;font-size:.76rem;color:#64748b;">${c.rut || 'â€”'}</td>
            <td style="padding:7px 12px;text-align:center;">
                <span class="badge" style="background:#eff6ff;color:#2563eb;">${c.docs.length} doc${c.docs.length !== 1 ? 's' : ''}</span>
            </td>
            <td style="padding:7px 12px;text-align:right;color:#2563eb;font-weight:700;">${fmt(c.monto)}</td>
        </tr>`).join('');

    // Detalle todos los docs
    const tbodyD = document.getElementById('excelPreviewDetalle');
    tbodyD.innerHTML = agrupado.flatMap(c => c.docs.map(d => {
        const tieneImpago = d.impago > 0;
        const impagoCell = tieneImpago
            ? `<span style="color:#dc2626;font-weight:700;">${fmt(d.impago)}</span>`
            : `<span style="color:#94a3b8;">$0</span>`;
        const pagadoCell = d.pagado > 0
            ? `<span style="color:#16a34a;font-weight:600;">${fmt(d.pagado)}</span>`
            : `<span style="color:#94a3b8;">$0</span>`;
        return `
        <tr${tieneImpago ? '' : ' style="opacity:.5;"'}>
            <td style="padding:6px 12px;">
                <span class="badge" style="background:#f0f4f9;color:#5a7394;font-size:.70rem;">${d.tipo}</span>
            </td>
            <td style="padding:6px 12px;font-size:.76rem;">${d.fecha}</td>
            <td style="padding:6px 12px;font-size:.76rem;">${d.nro}</td>
            <td style="padding:6px 12px;font-weight:500;">${c.cliente}</td>
            <td style="padding:6px 12px;font-size:.76rem;color:#64748b;">${c.rut || 'â€”'}</td>
            <td style="padding:6px 12px;text-align:right;">${fmt(d.total)}</td>
            <td style="padding:6px 12px;text-align:right;">${pagadoCell}</td>
            <td style="padding:6px 12px;text-align:right;">${impagoCell}</td>
        </tr>`;
    })).join('');

    const totalrows = agrupado.reduce((s, c) => s + c.docs.length, 0);
    document.getElementById('excelResumenCount').textContent =
        `${agrupado.length} cliente${agrupado.length !== 1 ? 's' : ''}, ${totalrows} documento${totalrows !== 1 ? 's' : ''}`;

    document.getElementById('excelPreviewWrapper').style.display = '';
    document.getElementById('btnImportarExcel').disabled = false;
}

function mostrarTabExcel(tab) {
    document.getElementById('tabResumen').style.display = tab === 'resumen' ? '' : 'none';
    document.getElementById('tabDetalle').style.display = tab === 'detalle' ? '' : 'none';
    document.getElementById('tabResumenBtn').classList.toggle('active', tab === 'resumen');
    document.getElementById('tabDetalleBtn').classList.toggle('active', tab === 'detalle');
}

function importarExcel() {
    if (!excelAgrupado.length) return;
    let imported = 0;
    excelAgrupado.forEach(c => {
        const existing = dbCobrar.find(r => r.cliente.toLowerCase() === c.cliente.toLowerCase());
        if (existing) {
            // acumula documentos y suma monto
            existing.docs = [...(existing.docs || []), ...c.docs];
            existing.monto += c.monto;
        } else {
            dbCobrar.push({ id: nextId.cobrar++, cliente: c.cliente, rut: '', monto: c.monto, docs: c.docs });
            imported++;
        }
    });
    renderCobrar();
    bootstrap.Modal.getInstance(document.getElementById('modalExcel')).hide();
    toast(`Importados: ${excelAgrupado.length} cliente(s), ${excelAgrupado.reduce((s, c) => s + c.docs.length, 0)} doc(s)`, 'success');
    limpiarExcel();
}

function limpiarExcel() {
    excelAgrupado = [];
    document.getElementById('excelFileInput').value = '';
    document.getElementById('excelFileName').style.display = 'none';
    document.getElementById('excelPreviewWrapper').style.display = 'none';
    document.getElementById('btnImportarExcel').disabled = true;
    document.getElementById('excelPreviewResumen').innerHTML = '';
    document.getElementById('excelPreviewDetalle').innerHTML = '';
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  SINCRONIZAR CON BASE DE DATOS (CodeIgniter 4)
//  Compara pantalla vs BD: agrega, actualiza y elimina
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

const BD_SYNC_ENDPOINT = (window.BD_BASE_URL || '/Portal/index.php') + '/cuentas-cobrar/sincronizar';

async function guardarEnBD() {
    // Construir el payload: todos los clientes con sus docs actuales
    const clientes = dbCobrar
        .filter(c => c.docs && c.docs.length > 0)
        .map(c => ({
            emisor_receptor: c.cliente || '',
            rut: c.rut || '',
            docs: c.docs.map(doc => {
                const total = parseFloat(doc.total ?? doc.monto ?? 0);
                const pagado = parseFloat(doc.pagado ?? 0);
                const impago = doc.impago !== undefined ? parseFloat(doc.impago) : (total - pagado);
                return {
                    tipo_documento: doc.tipo || 'Sin tipo',
                    fecha: doc.fecha || '',
                    numero: String(doc.nro || doc.numero || ''),
                    rut: doc.rut || c.rut || '',
                    total,
                    pagado,
                    impago: Math.max(0, impago),
                };
            }),
        }));

    if (!clientes.length) {
        toast('No hay clientes con documentos para sincronizar.', 'warning');
        return;
    }

    // Estado de carga en el boton
    const btn = document.getElementById('btnGuardarBD');
    const textoOriginal = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Sincronizando…';

    try {
        const response = await fetch(BD_SYNC_ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ clientes }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            dbCobrar.forEach(c => { if (c.docs && c.docs.length) c.enBD = true; });
            toast(`✓ ${data.message}`, 'success');
        } else {
            toast(`Error: ${data.message || 'Error desconocido del servidor.'}`, 'danger');
        }
    } catch (err) {
        toast('No se pudo conectar con el servidor: ' + err.message, 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = textoOriginal;
    }
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  INIT
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

// CARGAR CUENTAS x COBRAR DESDE BD

async function cargarCobrarDesdeBD() {
    const tbody = document.getElementById('bodyCobar');
    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;color:#94a3b8;font-size:.80rem;"><span class="spinner-border spinner-border-sm me-2"></span>Cargando datos...</td></tr>';
    try {
        const response = await fetch(
            (window.BD_BASE_URL || '/Portal/index.php') + '/cuentas-cobrar/pendientes',
            { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
        );
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Error al obtener datos');
        const registros = data.registros || [];
        const mapa = {};
        let autoId = 1;
        registros.forEach(row => {
            const nombre = row.emisor_receptor || 'Sin nombre';
            if (!mapa[nombre]) {
                mapa[nombre] = { id: autoId++, cliente: nombre, rut: row.rut || '', monto: 0, enBD: true, docs: [] };
            }
            const impago = parseFloat(row.impago ?? 0);
            mapa[nombre].monto += impago;
            mapa[nombre].docs.push({
                tipo: row.tipo_documento || 'Sin tipo',
                nro: row.numero || '',
                fecha: row.fecha || '',
                total: parseFloat(row.total ?? 0),
                pagado: parseFloat(row.pagado ?? 0),
                impago: impago,
                monto: impago,
                rut: row.rut || '',
            });
        });
        dbCobrar = Object.values(mapa);
        nextId.cobrar = autoId + 10;
        renderCobrar();
    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;color:#dc2626;font-size:.80rem;"><i class="bi bi-exclamation-triangle me-1"></i>Error: ' + err.message + '</td></tr>';
        console.error('[cargarCobrarDesdeBD]', err);
    }
}
// Carga inicial: BD manda
renderCaja();
renderPagar();
cargarCobrarDesdeBD();
