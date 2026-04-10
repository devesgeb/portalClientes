/**
 * DocumentosModule.js — CRUD genérico de documentos (Cobrar / Pagar)
 * ─────────────────────────────────────────────────────────────────────────
 * Razón: Cuentas por Cobrar y Cuentas por Pagar son módulos idénticos
 * estructuralmente. En lugar de duplicar código, este módulo acepta un
 * objeto CFG que describe la configuración de cada instancia.
 * Agregar un tercer módulo solo requiere un nuevo CFG.
 *
 * CFG keys requeridas:
 *   tipo            — 'cobrar' | 'pagar'
 *   label           — 'Cliente' | 'Proveedor'
 *   labelPlural     — 'clientes' | 'proveedores'
 *   color           — CSS var, ej: 'var(--cobrar-head)'
 *   db              — () => array actual en memoria
 *   setDb           — v => { dbXxx = v }
 *   nextId          — () => nextId.xxx
 *   incId           — () => nextId.xxx++
 *   tbody           — ID del <tbody> de la tabla principal
 *   totalEl         — ID del span total
 *   kpiVal          — ID del span KPI valor
 *   kpiSub          — ID del span KPI subtitulo
 *   modalRegistro   — ID del modal registro
 *   modalDetalle    — ID del modal detalle
 *   modalExcel      — ID del modal excel
 *   modalConfirmar  — ID del modal confirmar eliminar
 *   hdrRegistro     — ID del header del modal registro
 *   tituloRegistro  — ID del span titulo modal registro
 *   btnGuardar      — ID del botón guardar modal
 *   fieldsEl        — ID del fieldset de este tipo en el modal registro
 *   fieldsElOtro    — ID del fieldset del otro tipo (para ocultar)
 *   fieldElDoc      — ID del panel de campos doc (solo en nuevo)
 *   inputNombre     — ID input nombre entidad
 *   inputRut        — ID input rut entidad
 *   inputMonto      — ID input monto
 *   inputTipoDoc    — ID select tipo documento
 *   inputNroDoc     — ID input nro documento
 *   inputFechaDoc   — ID input fecha documento
 *   inputPagadoDoc  — ID input pagado
 *   inputImpagoDoc  — ID input impago
 *   montoAviso      — ID del aviso multi-doc
 *   montoAvisoTxt   — ID del span del aviso texto
 *   detalleHeader   — ID del header modal detalle
 *   detalleTitulo   — ID del span titulo modal detalle
 *   detalleBody     — ID del div body modal detalle
 *   delNombre       — ID del span nombre confirmar eliminar
 *   delDetalle      — ID del span detalle confirmar eliminar
 *   btnConfirmarOk  — ID del botón confirmar OK
 *   excelPreviewResumen — ID del tbody resumen excel
 *   excelPreviewDetalle — ID del tbody detalle excel
 *   excelResumenCount   — ID del span count excel
 *   excelPreviewWrapper — ID del wrapper preview excel
 *   btnImportarExcel    — ID del botón importar excel
 *   excelFileInput      — ID del input file excel
 *   excelFileName       — ID del span nombre archivo excel
 *   excelFileNameText   — ID del texto nombre archivo
 *   excelDropZone       — ID del drop zone excel
 *   endpointPendientes  — URL GET pendientes
 *   endpointSincronizar — URL POST sincronizar
 *   endpointEliminar    — URL DELETE eliminar
 *   endpointBuscar      — URL GET autocomplete ej: '/clientes/buscar?q='
 *   endpointVerificarDoc— URL GET verificar doc ej: '/cuentas-cobrar/verificar-documento?numero='
 *   onRecalc            — función a llamar tras render (ej: recalcNeto)
 * ─────────────────────────────────────────────────────────────────────────
 */
'use strict';

window.DocumentosModule = (function () {

    // ── Pendiente de confirmación { cfg, id } ─────────────────────────────
    let _eliminarPendiente = null;

    // ── Excel agrupado por CFG para no mezlcar Cobrar/Pagar ───────────────
    const _excelData = {};

    // ─────────────────────────────────────────────────────────────────────
    //  RENDER TABLA PRINCIPAL
    // ─────────────────────────────────────────────────────────────────────
    function renderTabla(cfg) {
        const tbody = document.getElementById(cfg.tbody);
        if (!tbody) return;
        tbody.innerHTML = '';
        const db = cfg.db();
        let total = 0;

        if (!db.length) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:24px;color:#94a3b8;font-size:.80rem;">
                <i class="bi bi-inbox" style="font-size:1.4rem;display:block;margin-bottom:6px;"></i>
                No hay cuentas por ${cfg.tipo} con saldo pendiente.
            </td></tr>`;
        } else {
            db.forEach((r, i) => {
                total += r.monto || 0;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                <td style="color:#b0bec5;font-size:.72rem;">${i + 1}</td>
                <td>
                    <div style="font-size:.82rem;font-weight:500;">${r.nombre || r.cliente || r.proveedor || '—'}</div>
                    <div style="font-size:.68rem;color:#94a3b8;">${r.rut || '—'}</div>
                </td>
                <td class="text-end" style="font-weight:700;">${PortalApp.fmt(r.monto || 0)}</td>
                <td class="text-center">
                    <div class="d-flex gap-1 justify-content-center">
                        <button class="btn-act det"  title="Ver detalle"       onclick="DocumentosModule.verDetalle(window._CFG_${cfg.tipo.toUpperCase()},${r.id})"><i class="bi bi-eye"></i></button>
                        <button class="btn-act edit" title="Editar"            onclick="DocumentosModule._abrirEditarWrap('${cfg.tipo}',${r.id})"><i class="bi bi-pencil"></i></button>
                        <button class="btn-act del"  title="Eliminar"          onclick="DocumentosModule.eliminar(window._CFG_${cfg.tipo.toUpperCase()},${r.id})"><i class="bi bi-trash3"></i></button>
                    </div>
                </td>`;
                tbody.appendChild(tr);
            });
        }

        const t = PortalApp.fmt(total);
        if (document.getElementById(cfg.totalEl)) document.getElementById(cfg.totalEl).textContent = t;
        if (document.getElementById(cfg.kpiVal)) document.getElementById(cfg.kpiVal).textContent = t;
        if (document.getElementById(cfg.kpiSub)) document.getElementById(cfg.kpiSub).textContent =
            `${db.length} ${db.length !== 1 ? cfg.labelPlural : cfg.label.toLowerCase()}`;
        if (typeof cfg.onRecalc === 'function') cfg.onRecalc();
    }

    // ─────────────────────────────────────────────────────────────────────
    //  MODAL REGISTRO — abrir (nuevo o editar)
    // ─────────────────────────────────────────────────────────────────────
    function abrirModalAgregar(cfg, id = null) {
        const header = document.getElementById(cfg.hdrRegistro);
        const titulo = document.getElementById(cfg.tituloRegistro);
        const btnG = document.getElementById(cfg.btnGuardar);
        const fEste = document.getElementById(cfg.fieldsEl);
        const fOtro = document.getElementById(cfg.fieldsElOtro);
        const modalEl = document.getElementById(cfg.modalRegistro);

        if (header) header.style.background = cfg.color;
        if (btnG) btnG.style.background = cfg.color;
        if (titulo) titulo.textContent = id ? `Editar ${cfg.label} — Cuentas por ${_capitalize(cfg.tipo)}`
            : `Nuevo — Cuentas por ${_capitalize(cfg.tipo)}`;
        if (fEste) fEste.style.display = '';
        if (fOtro) fOtro.style.display = 'none';

        const db = cfg.db();
        const r = id ? db.find(x => x.id === id) : null;

        if (r) {
            // Editar: cargar datos
            if (document.getElementById(cfg.inputNombre)) document.getElementById(cfg.inputNombre).value = r.nombre || r.cliente || r.proveedor || '';
            if (document.getElementById(cfg.inputRut)) document.getElementById(cfg.inputRut).value = r.rut || '';
            if (document.getElementById(cfg.inputMonto)) document.getElementById(cfg.inputMonto).value = r.monto || '0';
            const docPanel = document.getElementById(cfg.fieldElDoc);
            if (docPanel) docPanel.style.display = 'none';

            // Bloquear monto si tiene > 1 doc
            const cantDocs = r.docs ? r.docs.length : 0;
            function _aplicarBloqueo() {
                const montoInput = document.getElementById(cfg.inputMonto);
                const montoAviso = document.getElementById(cfg.montoAviso);
                const montoAvisoTxt = document.getElementById(cfg.montoAvisoTxt);
                if (cantDocs > 1) {
                    if (montoInput) {
                        montoInput.setAttribute('disabled', 'disabled');
                        montoInput.style.setProperty('background-color', '#e9ecef', 'important');
                        montoInput.style.setProperty('cursor', 'not-allowed', 'important');
                    }
                    if (montoAvisoTxt) montoAvisoTxt.textContent =
                        `El monto se calcula automáticamente desde los ${cantDocs} documentos. Use Ver detalle para modificar.`;
                    if (montoAviso) montoAviso.style.display = '';
                } else {
                    if (montoInput) {
                        montoInput.removeAttribute('disabled');
                        montoInput.style.removeProperty('background-color');
                        montoInput.style.removeProperty('cursor');
                    }
                    if (montoAviso) montoAviso.style.display = 'none';
                }
                if (modalEl) modalEl.removeEventListener('shown.bs.modal', _aplicarBloqueo);
            }
            if (modalEl) modalEl.addEventListener('shown.bs.modal', _aplicarBloqueo);

        } else {
            // Nuevo: limpiar todo
            ['inputNombre', 'inputRut', 'inputMonto', 'inputTipoDoc', 'inputNroDoc', 'inputPagadoDoc', 'inputImpagoDoc']
                .forEach(k => { const el = document.getElementById(cfg[k]); if (el) el.value = ''; });
            const fd = document.getElementById(cfg.inputFechaDoc);
            if (fd) fd.value = new Date().toISOString().slice(0, 10);
            const pp = document.getElementById(cfg.inputPagadoDoc);
            if (pp) pp.value = '0';
            const ip = document.getElementById(cfg.inputImpagoDoc);
            if (ip) ip.value = '0';
            const docPanel = document.getElementById(cfg.fieldElDoc);
            if (docPanel) docPanel.style.display = '';

            function _habilitarMonto() {
                const montoInput = document.getElementById(cfg.inputMonto);
                const montoAviso = document.getElementById(cfg.montoAviso);
                if (montoInput) {
                    montoInput.removeAttribute('disabled');
                    montoInput.style.removeProperty('background-color');
                    montoInput.style.removeProperty('cursor');
                }
                if (montoAviso) montoAviso.style.display = 'none';
                if (modalEl) modalEl.removeEventListener('shown.bs.modal', _habilitarMonto);
            }
            if (modalEl) modalEl.addEventListener('shown.bs.modal', _habilitarMonto);

            // Activar autocomplete para el input de nombre/rut si está configurado
            if (cfg.endpointBuscar && cfg.inputNombre) {
                const inputNombreEl = document.getElementById(cfg.inputNombre);
                const inputRutEl = document.getElementById(cfg.inputRut);
                PortalApp.Autocomplete.init(inputNombreEl, cfg.endpointBuscar, (item) => {
                    if (inputNombreEl) inputNombreEl.value = item.razon_social || item.nombre || '';
                    if (inputRutEl) inputRutEl.value = item.rut || '';
                }, {
                    labelFn: item => `${item.razon_social || item.nombre || ''} (${item.rut || ''})`,
                });
            }
        }

        PortalApp.showModal(cfg.modalRegistro);
        // Guardar id editado globalmente para que guardarRegistro lo use
        window['_editingId_' + cfg.tipo] = id;
    }

    // ─────────────────────────────────────────────────────────────────────
    //  GUARDAR REGISTRO
    // ─────────────────────────────────────────────────────────────────────
    async function guardarRegistro(cfg) {
        const nombre = (document.getElementById(cfg.inputNombre)?.value || '').trim();
        const rut = (document.getElementById(cfg.inputRut)?.value || '').trim();
        const monto = parseFloat(document.getElementById(cfg.inputMonto)?.value) || 0;

        if (!nombre) { PortalApp.toast(`Ingrese nombre de ${cfg.label.toLowerCase()}`, 'warning'); return; }
        if (!rut) { PortalApp.toast(`Ingrese el RUT del ${cfg.label.toLowerCase()}`, 'warning'); return; }
        if (monto <= 0) { PortalApp.toast('El monto debe ser mayor a 0', 'warning'); return; }

        const editingId = window['_editingId_' + cfg.tipo];
        const db = cfg.db();

        if (editingId) {
            // Editar
            const r = db.find(x => x.id === editingId);
            if (r) {
                r.nombre = nombre; r.cliente = nombre; r.proveedor = nombre;
                r.rut = rut;
                if (r.docs) r.docs.forEach(d => { d.rut = rut; });
                const cantDocs = r.docs ? r.docs.length : 0;
                if (cantDocs <= 1) r.monto = monto;
            }
        } else {
            // Nuevo: leer datos del documento
            const tipoDoc = document.getElementById(cfg.inputTipoDoc)?.value || '';
            const nroDoc = (document.getElementById(cfg.inputNroDoc)?.value || '').trim();
            const fechaDoc = document.getElementById(cfg.inputFechaDoc)?.value || '';
            const pagado = parseFloat(document.getElementById(cfg.inputPagadoDoc)?.value) || 0;

            if (!tipoDoc) { PortalApp.toast('Seleccione el Tipo de Documento', 'warning'); return; }
            if (!nroDoc) { PortalApp.toast('Ingrese el N° de Documento (o N/A)', 'warning'); return; }
            if (!fechaDoc) { PortalApp.toast('Ingrese la Fecha del documento', 'warning'); return; }

            const impago = Math.max(0, monto - pagado);
            const [fy, fm, fd] = fechaDoc.split('-');
            const fechaFmt = `${fd}/${fm}/${fy}`;
            const nuevoDoc = {
                tipo: tipoDoc, nro: nroDoc, fecha: fechaFmt,
                total: monto, pagado, impago, monto, rut,
            };

            // Verificar duplicado en BD
            if (nroDoc !== 'N/A' && cfg.endpointVerificarDoc) {
                try {
                    const { data } = await PortalApp.apiFetch(cfg.endpointVerificarDoc + encodeURIComponent(nroDoc));
                    if (data.existe) {
                        PortalApp.toast(`El N° ${nroDoc} ya existe en BD (${data.clientes || ''})`, 'warning');
                        return;
                    }
                } catch (_) { /* continuar si falla la verificación online */ }
            }

            const rutNorm = rut.replace(/\s/g, '').toLowerCase();
            const existente = db.find(x => (x.rut || '').replace(/\s/g, '').toLowerCase() === rutNorm);

            if (existente) {
                const nroEnCliente = existente.docs?.find(d => (d.nro || '').trim() === nroDoc);
                if (nroEnCliente && nroDoc !== 'N/A') {
                    PortalApp.toast(`El N° ${nroDoc} ya existe en "${existente.nombre || existente.cliente || existente.proveedor}".`, 'warning');
                    return;
                }
                if (!existente.docs) existente.docs = [];
                existente.docs.push(nuevoDoc);
                existente.monto += impago;
                existente.enBD = false;
            } else {
                const nroEnOtro = nroDoc !== 'N/A' && db.find(x => x.docs?.find(d => (d.nro || '').trim() === nroDoc));
                if (nroEnOtro) {
                    PortalApp.toast(`El N° ${nroDoc} ya está en uso por "${nroEnOtro.nombre || nroEnOtro.cliente || nroEnOtro.proveedor}".`, 'warning');
                    return;
                }
                const newItem = {
                    id: cfg.incId(), nombre, cliente: nombre, proveedor: nombre,
                    rut, monto, enBD: false, docs: [nuevoDoc],
                };
                cfg.setDb([...db, newItem]);
            }
        }

        renderTabla(cfg);
        PortalApp.hideModal(cfg.modalRegistro);
        window['_editingId_' + cfg.tipo] = null;
        await guardarEnBD(cfg);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  VER DETALLE
    // ─────────────────────────────────────────────────────────────────────
    function verDetalle(cfg, id) {
        const hdr = document.getElementById(cfg.detalleHeader);
        const tit = document.getElementById(cfg.detalleTitulo);
        const body = document.getElementById(cfg.detalleBody);
        if (hdr) hdr.style.background = cfg.color;
        if (tit) tit.textContent = `Detalle — Cuentas por ${_capitalize(cfg.tipo)}`;
        window['_detalleId_' + cfg.tipo] = id;
        _renderDetalleBody(cfg, id, body);
        PortalApp.showModal(cfg.modalDetalle);
    }

    function _renderDetalleBody(cfg, id, body) {
        if (!body) return;
        const r = cfg.db().find(x => x.id === id);
        if (!r) return;
        const nombre = r.nombre || r.cliente || r.proveedor || '—';
        const docs = r.docs || [];
        const color = cfg.color;

        const docsRows = docs.length
            ? docs.map((d, idx) => `
                <tr>
                    <td style="padding:6px 10px;">
                        <span class="badge" style="background:#eff6ff;color:#2563eb;font-size:.72rem;">${d.tipo || 'Sin tipo'}</span>
                    </td>
                    <td style="padding:6px 10px;font-size:.78rem;font-weight:500;">${d.nro || d.numero || '—'}</td>
                    <td style="padding:6px 10px;font-size:.76rem;color:#64748b;">${d.fecha || '—'}</td>
                    <td style="padding:6px 10px;text-align:right;color:#2563eb;font-weight:700;">${PortalApp.fmt(d.monto ?? d.total ?? 0)}</td>
                    <td style="padding:6px 8px;text-align:center;">
                        <button onclick="DocumentosModule.eliminarDocDetalle(window._CFG_${cfg.tipo.toUpperCase()},${id},${idx})"
                            title="Eliminar documento"
                            style="border:none;background:transparent;color:#dc2626;padding:2px 6px;border-radius:6px;cursor:pointer;font-size:.85rem;"
                        ><i class="bi bi-trash3"></i></button>
                    </td>
                </tr>`).join('')
            : `<tr><td colspan="5" style="text-align:center;padding:14px;color:#94a3b8;font-size:.78rem;">
                <i class="bi bi-info-circle me-1"></i>Sin documentos registrados
            </td></tr>`;

        const totalMonto = docs.reduce((s, d) => s + (d.monto ?? d.total ?? 0), 0);
        const labelEntidad = cfg.label;

        body.innerHTML = `
        <div class="row g-3 mb-3">
            <div class="col-6"><div style="font-size:.70rem;color:#8fa3bc;">${labelEntidad}</div><div style="font-size:.88rem;font-weight:600;">${nombre}</div></div>
            <div class="col-6"><div style="font-size:.70rem;color:#8fa3bc;">RUT</div><div style="font-size:.88rem;font-weight:600;">${r.rut || '—'}</div></div>
            <div class="col-12">
                <div style="font-size:.70rem;color:#8fa3bc;">Total Impago</div>
                <div style="font-size:1.4rem;font-weight:800;color:${color};">${PortalApp.fmt(totalMonto)}</div>
            </div>
        </div>
        <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:8px 12px;font-size:.74rem;color:#92400e;margin-bottom:10px;">
            <i class="bi bi-exclamation-triangle-fill me-1 text-warning"></i>
            Los cambios se guardan automáticamente en la base de datos.
        </div>
        <div style="font-size:.78rem;font-weight:600;color:#1a2940;margin-bottom:6px;">
            <i class="bi bi-receipt me-1" style="color:${color};"></i>
            Documentos <span class="badge" style="background:#eff6ff;color:#2563eb;margin-left:4px;">${docs.length}</span>
        </div>
        <div style="max-height:260px;overflow-y:auto;border-radius:8px;border:1px solid #e5eaf0;">
            <table class="table table-sm mb-0" style="font-size:.78rem;">
                <thead style="position:sticky;top:0;">
                    <tr style="background:#f8fafc;">
                        <th style="padding:8px 10px;font-size:.68rem;color:#8fa3bc;font-weight:600;text-transform:uppercase;">Tipo</th>
                        <th style="padding:8px 10px;font-size:.68rem;color:#8fa3bc;font-weight:600;text-transform:uppercase;">N° Doc.</th>
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
                        <td style="padding:8px 12px;text-align:right;font-weight:800;color:${color};">${PortalApp.fmt(totalMonto)}</td>
                        <td></td>
                    </tr>
                </tfoot>` : ''}
            </table>
        </div>`;
    }

    // ─────────────────────────────────────────────────────────────────────
    //  DETALLE — AGREGAR / ELIMINAR DOC
    // ─────────────────────────────────────────────────────────────────────
    function eliminarDocDetalle(cfg, entidadId, docIdx) {
        const db = cfg.db();
        const r = db.find(x => x.id === entidadId);
        if (!r || !r.docs) return;

        if (r.docs.length <= 1) {
            PortalApp.toast(`No puedes eliminar el único documento. Usa el botón eliminar del ${cfg.label.toLowerCase()}.`, 'warning');
            return;
        }

        r.docs.splice(docIdx, 1);
        r.monto = r.docs.reduce((s, d) => s + (d.monto ?? d.total ?? 0), 0);

        const body = document.getElementById(cfg.detalleBody);
        _renderDetalleBody(cfg, entidadId, body);
        renderTabla(cfg);
        PortalApp.toast('Documento eliminado. Recuerda presionar Guardar en BD.', 'warning');
    }

    function agregarDocDetalle(cfg, entidadId) {
        const db = cfg.db();
        const r = db.find(x => x.id === entidadId);
        if (!r) return;
        if (!r.docs) r.docs = [];
        r.docs.push({
            tipo: 'Factura', nro: '',
            fecha: new Date().toLocaleDateString('es-CL'),
            monto: 0, total: 0,
        });
        const body = document.getElementById(cfg.detalleBody);
        _renderDetalleBody(cfg, entidadId, body);
        PortalApp.toast('Nueva fila agregada. Completa los datos y presiona Guardar en BD.', 'warning');
    }

    // ─────────────────────────────────────────────────────────────────────
    //  ELIMINAR ENTIDAD (modal confirmación)
    // ─────────────────────────────────────────────────────────────────────
    function eliminar(cfg, id) {
        const db = cfg.db();
        const r = db.find(x => x.id === id);
        if (!r) return;

        _eliminarPendiente = { cfg, id };
        const cantDocs = r.docs ? r.docs.length : 0;
        const enBD = r.enBD === true;
        const nombre = r.nombre || r.cliente || r.proveedor || '—';

        const delNombre = document.getElementById(cfg.delNombre);
        const delDetalle = document.getElementById(cfg.delDetalle);
        if (delNombre) delNombre.textContent = nombre;
        if (delDetalle) delDetalle.innerHTML = enBD
            ? `<span class="text-danger"><i class="bi bi-database-dash me-1"></i>Se eliminarán <strong>${cantDocs} documento(s)</strong> de la base de datos.</span>`
            : `<span class="text-muted"><i class="bi bi-info-circle me-1"></i>Este ${cfg.label.toLowerCase()} aún no fue guardado en BD. Se eliminará solo de la pantalla.</span>`;

        PortalApp.showModal(cfg.modalConfirmar);
    }

    async function confirmarEliminar(cfg) {
        if (!_eliminarPendiente || _eliminarPendiente.cfg.tipo !== cfg.tipo) return;

        const { id } = _eliminarPendiente;
        const db = cfg.db();
        const r = db.find(x => x.id === id);
        if (!r) return;

        const btnConfirm = document.getElementById(cfg.btnConfirmarOk);

        if (r.enBD === true) {
            const originalText = btnConfirm ? btnConfirm.innerHTML : '';
            if (btnConfirm) {
                btnConfirm.disabled = true;
                btnConfirm.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Eliminando…';
            }

            try {
                const { res, data } = await PortalApp.apiFetch(cfg.endpointEliminar, {
                    method: 'DELETE',
                    body: JSON.stringify({ rut: r.rut }),
                });
                if (res.ok && data.success) {
                    PortalApp.toast(`✓ ${data.message}`, 'danger');
                } else {
                    PortalApp.toast(`Error: ${data.message || 'Error al eliminar en BD.'}`, 'danger');
                    if (btnConfirm) { btnConfirm.disabled = false; btnConfirm.innerHTML = originalText; }
                    return;
                }
            } catch (err) {
                PortalApp.toast('No se pudo conectar: ' + err.message, 'danger');
                if (btnConfirm) { btnConfirm.disabled = false; btnConfirm.innerHTML = originalText; }
                return;
            }

            if (btnConfirm) { btnConfirm.disabled = false; btnConfirm.innerHTML = originalText; }
        } else {
            PortalApp.toast(`${cfg.label} eliminado de la pantalla`, 'danger');
        }

        cfg.setDb(db.filter(x => x.id !== id));
        renderTabla(cfg);
        PortalApp.hideModal(cfg.modalConfirmar);
        _eliminarPendiente = null;
    }

    // ─────────────────────────────────────────────────────────────────────
    //  GUARDAR EN BD (sincronizar)
    // ─────────────────────────────────────────────────────────────────────
    async function guardarEnBD(cfg) {
        const db = cfg.db();
        const entidades = db
            .filter(c => c.docs && c.docs.length > 0)
            .map(c => ({
                emisor_receptor: c.nombre || c.cliente || c.proveedor || '',
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
                        total, pagado,
                        impago: Math.max(0, impago),
                    };
                }),
            }));

        if (!entidades.length) {
            PortalApp.toast(`No hay ${cfg.labelPlural} con documentos para sincronizar.`, 'warning');
            return;
        }

        const btn = document.getElementById(cfg.btnGuardarBD);
        const textoOriginal = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Sincronizando…'; }

        try {
            const payload = cfg.tipo === 'cobrar'
                ? { clientes: entidades }
                : { proveedores: entidades };

            const { res, data } = await PortalApp.apiFetch(cfg.endpointSincronizar, {
                method: 'POST',
                body: JSON.stringify(payload),
            });

            if (res.ok && data.success) {
                await cargarDesdeBD(cfg); // Recargar desde BD para reflejar el estado real
                PortalApp.toast(`✓ ${data.message}`, 'success');
            } else {
                PortalApp.toast(`Error: ${data.message || 'Error desconocido.'}`, 'danger');
            }
        } catch (err) {
            PortalApp.toast('No se pudo conectar: ' + err.message, 'danger');
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = textoOriginal; }
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    //  CARGAR DESDE BD
    // ─────────────────────────────────────────────────────────────────────
    async function cargarDesdeBD(cfg) {
        const tbody = document.getElementById(cfg.tbody);
        const cols = 5;
        if (tbody) tbody.innerHTML = `<tr><td colspan="${cols}" style="text-align:center;padding:20px;color:#94a3b8;font-size:.80rem;"><span class="spinner-border spinner-border-sm me-2"></span>Cargando datos...</td></tr>`;

        try {
            const { res, data } = await PortalApp.apiFetch(cfg.endpointPendientes);
            if (!res.ok || !data.success) throw new Error(data.message || 'Error al obtener datos');

            const mapa = {};
            let autoId = 1;
            const rutKey = cfg.tipo === 'cobrar' ? 'rut_cliente' : 'rut_proveedor';
            const nameKey = cfg.tipo === 'cobrar' ? 'nombre_cliente' : 'nombre_proveedor';

            (data.registros || []).forEach(row => {
                const rut = row[rutKey] || '';
                const key = rut || row[nameKey] || 'sin-rut-' + autoId;
                if (!mapa[key]) {
                    const nombre = row[nameKey] || row.razon_social || rut || 'Sin nombre';
                    mapa[key] = {
                        id: autoId++,
                        nombre, cliente: nombre, proveedor: nombre,
                        rut, monto: 0, enBD: true, docs: [],
                    };
                }
                const impago = parseFloat(row.impago ?? 0);
                mapa[key].monto += impago;
                mapa[key].docs.push({
                    tipo: row.tipo_documento || 'Sin tipo',
                    nro: row.numero || '',
                    fecha: row.fecha || '',
                    total: parseFloat(row.total ?? 0),
                    pagado: parseFloat(row.pagado ?? 0),
                    impago, monto: impago,
                });
            });

            // Preservar items locales no guardados
            const pending = cfg.db().filter(c => c.enBD === false);
// Solo mostrar entidades con monto > 0 (cobrar y pagar)
            const entries = Object.values(mapa).filter(e => e.monto > 0);
            cfg.setDb([...entries, ...pending]);
            cfg.resetNextId(autoId + 10);
            renderTabla(cfg);
        } catch (err) {
            if (tbody) tbody.innerHTML = `<tr><td colspan="${cols}" style="text-align:center;padding:20px;color:#dc2626;font-size:.80rem;"><i class="bi bi-exclamation-triangle me-1"></i>Error: ${err.message}</td></tr>`;
            console.error(`[DocumentosModule.cargarDesdeBD:${cfg.tipo}]`, err);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    //  EXCEL — carga masiva
    // ─────────────────────────────────────────────────────────────────────
    function abrirModalExcel(cfg) {
        _limpiarExcel(cfg);
        PortalApp.showModal(cfg.modalExcel);
    }

    function leerExcel(cfg, file) {
        if (!file) return;
        if (typeof XLSX === 'undefined') {
            PortalApp.toast('La librería Excel no cargó. Verifica tu conexión.', 'danger');
            return;
        }
        const ext = file.name.split('.').pop().toLowerCase();
        if (!['xlsx', 'xls'].includes(ext)) { PortalApp.toast('Solo se aceptan .xlsx o .xls', 'warning'); return; }

        const fileNameEl = document.getElementById(cfg.excelFileNameText);
        const fileNameWrap = document.getElementById(cfg.excelFileName);
        if (fileNameEl) fileNameEl.textContent = file.name;
        if (fileNameWrap) fileNameWrap.style.display = '';

        const reader = new FileReader();
        reader.onload = e => {
            try {
                const wb = XLSX.read(e.target.result, { type: 'array', cellDates: true });
                const ws = wb.Sheets[wb.SheetNames[0]];
                const rawData = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });
                if (!rawData.length || rawData.length < 2) { PortalApp.toast('Archivo vacío', 'warning'); return; }

                // Columnas requeridas: Tipo Documento | Numero | Emisor/Receptor | Rut | Total | Pagado | Impago
                const patterns = {
                    emisor: { regex: /emisor.{0,3}receptor|empresa|razon.?social|cliente|proveedor|receptor|emisor/i },
                    tipo:   { regex: /tipo.{0,4}doc/i },
                    nro:    { regex: /^n[uÃº]mero$|^numero$|^nro$|^num$|^nÂ°$/i },
                    rut:    { regex: /^rut$/i },
                    total:  { regex: /^total$/i },
                    pagado: { regex: /^pagado$/i },
                    impago: { regex: /^impago$|saldo.?pendiente/i },
                };
                const allRegex = Object.values(patterns).map(p => p.regex);

                let headerRowIdx = 0, maxMatches = 0;
                const scanLimit = Math.min(rawData.length, 15);
                for (let i = 0; i < scanLimit; i++) {
                    const hits = rawData[i].map(c => String(c).trim()).filter(cell => allRegex.some(rx => rx.test(cell))).length;
                    if (hits > maxMatches) { maxMatches = hits; headerRowIdx = i; }
                }

                const headers = rawData[headerRowIdx].map(h => String(h).trim());
                const rows = rawData.slice(headerRowIdx + 1)
                    .map(row => { const obj = {}; headers.forEach((h, i) => { obj[h] = row[i] !== undefined ? row[i] : ''; }); return obj; })
                    .filter(row => Object.values(row).some(v => String(v).trim() !== ''));

                if (!rows.length) { PortalApp.toast('Sin filas de datos válidas', 'warning'); return; }

                const colMap = {};
                headers.filter(h => h).forEach(k => {
                    Object.entries(patterns).forEach(([field, { regex }]) => {
                        if (!colMap[field] && regex.test(k)) colMap[field] = k;
                    });
                });

                if (!colMap.emisor) {
                    PortalApp.toast(`Columna requerida no encontrada: "Emisor / Receptor". Columnas detectadas: ${headers.join(', ')}`, 'danger');
                    return;
                }

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
                    const fecha = colMap.fecha ? String(row[colMap.fecha]) : '—';
                    const tipo = colMap.tipo ? String(row[colMap.tipo]) : '—';
                    const nro = colMap.nro ? String(row[colMap.nro]) : '—';

                    grupos[key].docs.push({ tipo, nro, fecha, total, impago, pagado, rut });
                    grupos[key].impago += impago;
                    grupos[key].pagado += pagado;
                });

                _excelData[cfg.tipo] = Object.values(grupos)
                    .filter(d => d.impago > 0 || d.pagado > 0 || (d.docs.length > 0 && d.docs.some(doc => doc.total > 0)))
                    .map(d => ({
                        nombre: d.nombre, cliente: d.nombre, proveedor: d.nombre,
                        rut: d.rut, monto: d.impago, pagado: d.pagado, docs: d.docs,
                    }));

                _mostrarPreviewExcel(cfg, _excelData[cfg.tipo]);
            } catch (err) {
                PortalApp.toast('Error al leer el archivo: ' + err.message, 'danger');
            }
        };
        reader.readAsArrayBuffer(file);
    }

    function _mostrarPreviewExcel(cfg, agrupado) {
        const tbody = document.getElementById(cfg.excelPreviewResumen);
        if (tbody) tbody.innerHTML = agrupado.map((c, i) => `
            <tr>
                <td style="padding:7px 12px;color:#b0bec5;font-size:.72rem;">${i + 1}</td>
                <td style="padding:7px 12px;font-weight:500;">${c.nombre}</td>
                <td style="padding:7px 12px;font-size:.76rem;color:#64748b;">${c.rut || '—'}</td>
                <td style="padding:7px 12px;text-align:center;">
                    <span class="badge" style="background:#eff6ff;color:#2563eb;">${c.docs.length} doc${c.docs.length !== 1 ? 's' : ''}</span>
                </td>
                <td style="padding:7px 12px;text-align:right;color:#2563eb;font-weight:700;">${PortalApp.fmt(c.monto)}</td>
            </tr>`).join('');

        const tbodyD = document.getElementById(cfg.excelPreviewDetalle);
        if (tbodyD) tbodyD.innerHTML = agrupado.flatMap(c => c.docs.map(d => {
            const tieneImpago = d.impago > 0;
            return `<tr${tieneImpago ? '' : ' style="opacity:.5;"'}>
                <td style="padding:6px 12px;"><span class="badge" style="background:#f0f4f9;color:#5a7394;font-size:.70rem;">${d.tipo}</span></td>
                <td style="padding:6px 12px;font-size:.76rem;">${d.fecha}</td>
                <td style="padding:6px 12px;font-size:.76rem;">${d.nro}</td>
                <td style="padding:6px 12px;font-weight:500;">${c.nombre}</td>
                <td style="padding:6px 12px;font-size:.76rem;color:#64748b;">${c.rut || '—'}</td>
                <td style="padding:6px 12px;text-align:right;">${PortalApp.fmt(d.total)}</td>
                <td style="padding:6px 12px;text-align:right;">${tieneImpago ? `<span style="color:#dc2626;font-weight:700;">${PortalApp.fmt(d.impago)}</span>` : `<span style="color:#94a3b8;">$0</span>`}</td>
            </tr>`;
        })).join('');

        const totalrows = agrupado.reduce((s, c) => s + c.docs.length, 0);
        const countEl = document.getElementById(cfg.excelResumenCount);
        if (countEl) countEl.textContent = `${agrupado.length} ${cfg.labelPlural}, ${totalrows} documento${totalrows !== 1 ? 's' : ''}`;

        const wrapEl = document.getElementById(cfg.excelPreviewWrapper);
        if (wrapEl) wrapEl.style.display = '';
        const btnEl = document.getElementById(cfg.btnImportarExcel);
        if (btnEl) btnEl.disabled = false;
    }

    function importarExcel(cfg) {
        const agrupado = _excelData[cfg.tipo] || [];
        if (!agrupado.length) return;
        const db = cfg.db();
        agrupado.forEach(c => {
            const existing = db.find(r => (r.rut || '').toLowerCase() === (c.rut || '').toLowerCase() && c.rut);
            if (existing) {
                existing.docs = [...(existing.docs || []), ...c.docs];
                existing.monto += c.monto;
            } else {
                cfg.setDb([...cfg.db(), {
                    id: cfg.incId(), nombre: c.nombre, cliente: c.nombre, proveedor: c.nombre,
                    rut: c.rut, monto: c.monto, docs: c.docs, enBD: false,
                }]);
            }
        });
        renderTabla(cfg);
        PortalApp.hideModal(cfg.modalExcel);
        PortalApp.toast(`Importados: ${agrupado.length} ${cfg.labelPlural}, ${agrupado.reduce((s, c) => s + c.docs.length, 0)} documentos`, 'success');
        _limpiarExcel(cfg);
    }

    function _limpiarExcel(cfg) {
        _excelData[cfg.tipo] = [];
        const ids = [cfg.excelFileInput, cfg.excelFileName, cfg.excelPreviewWrapper, cfg.btnImportarExcel, cfg.excelPreviewResumen, cfg.excelPreviewDetalle];
        const el = id => document.getElementById(id);
        if (el(cfg.excelFileInput)) el(cfg.excelFileInput).value = '';
        if (el(cfg.excelFileName)) el(cfg.excelFileName).style.display = 'none';
        if (el(cfg.excelPreviewWrapper)) el(cfg.excelPreviewWrapper).style.display = 'none';
        if (el(cfg.btnImportarExcel)) el(cfg.btnImportarExcel).disabled = true;
        if (el(cfg.excelPreviewResumen)) el(cfg.excelPreviewResumen).innerHTML = '';
        if (el(cfg.excelPreviewDetalle)) el(cfg.excelPreviewDetalle).innerHTML = '';
    }

    // ─────────────────────────────────────────────────────────────────────
    //  WRAPPERS públicos para onclick inline en HTML
    // ─────────────────────────────────────────────────────────────────────
    function _abrirModalWrap(tipo, id) {
        const cfg = tipo === 'cobrar' ? window._CFG_COBRAR : window._CFG_PAGAR;
        if (cfg) abrirModalAgregar(cfg, id);
    }

    function _abrirEditarWrap(tipo, id) {
        _abrirModalWrap(tipo, id);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  HELPER
    // ─────────────────────────────────────────────────────────────────────
    function _capitalize(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

    // API pública
    return {
        renderTabla, abrirModalAgregar, guardarRegistro,
        verDetalle, _renderDetalleBody,
        eliminarDocDetalle, agregarDocDetalle,
        eliminar, confirmarEliminar,
        guardarEnBD, cargarDesdeBD,
        abrirModalExcel, leerExcel, importarExcel,
        _abrirModalWrap, _abrirEditarWrap,
    };
})();
