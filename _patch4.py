
import re

# ─── PATCH 1: View PHP ───────────────────────────────────────────────
view_file = r'c:\xampp\htdocs\Portal\app\Views\balance_diario.php'
with open(view_file, 'rb') as f:
    view = f.read().decode('utf-8')

# Buscar la div del campo monto y agregar el div de aviso despues del input
old_div = view[view.find('<div class="mb-3">',
               view.find('cobrarMonto') - 200) :
               view.find('</div>', view.find('cobrarMonto') + 100) + 6]

print("Encontrado div monto:", repr(old_div[:80]))

# Construir el nuevo bloque
new_div = '''<div class="mb-3">
                            <label class="form-label" style="font-size:.78rem;font-weight:500;color:#5a7394;">Monto
                                Total (suma facturas/gu\u00edas pendientes)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="font-size:.78rem;">$</span>
                                <input class="form-control" id="cobrarMonto" type="number" placeholder="0">
                            </div>
                            <div id="cobrarMontoAviso" style="display:none;margin-top:6px;padding:7px 10px;background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;font-size:.73rem;color:#92400e;">
                                <i class="bi bi-exclamation-triangle-fill me-1 text-warning"></i>
                                Este cliente tiene <strong id="cobrarMontoAvisoCnt"></strong> documentos asociados. El monto total es calculado autom\u00e1ticamente.
                                Para editar documentos individuales usa el bot\u00f3n
                                <i class="bi bi-eye"></i> <strong>Visualizar</strong>.
                            </div>
                        </div>'''

view = view.replace(old_div, new_div, 1)

with open(view_file, 'wb') as f:
    f.write(view.encode('utf-8'))
print("View actualizado OK")

# ─── PATCH 2: JS ─────────────────────────────────────────────────────
js_file = r'c:\xampp\htdocs\Portal\public\assets\js\BalanceDiario.js'
with open(js_file, 'rb') as f:
    js = f.read().decode('utf-8')

old_edit_block = """        if (id) {
            const r = dbCobrar.find(x => x.id === id);
            if (r) {
                document.getElementById('cobrarCliente').value = r.cliente;
                document.getElementById('cobrarRut').value = r.rut;
                document.getElementById('cobrarMonto').value = r.monto;
            }
        } else {
            document.getElementById('cobrarCliente').value = '';
            document.getElementById('cobrarRut').value = '';
            document.getElementById('cobrarMonto').value = '';
        }"""

old_edit_crlf = old_edit_block.replace('\n', '\r\n')

new_edit_block = """        const montoInput = document.getElementById('cobrarMonto');
        const montoAviso = document.getElementById('cobrarMontoAviso');
        const avisoCnt   = document.getElementById('cobrarMontoAvisoCnt');

        if (id) {
            const r = dbCobrar.find(x => x.id === id);
            if (r) {
                document.getElementById('cobrarCliente').value = r.cliente;
                document.getElementById('cobrarRut').value     = r.rut;
                montoInput.value = r.monto;

                // Si tiene m\u00e1s de 1 doc, bloquear monto y mostrar aviso
                const numDocs = (r.docs || []).length;
                if (numDocs > 1) {
                    montoInput.disabled = true;
                    montoInput.style.background = '#f0f4f9';
                    avisoCnt.textContent = numDocs;
                    if (montoAviso) montoAviso.style.display = '';
                } else {
                    montoInput.disabled = false;
                    montoInput.style.background = '';
                    if (montoAviso) montoAviso.style.display = 'none';
                }
            }
        } else {
            document.getElementById('cobrarCliente').value = '';
            document.getElementById('cobrarRut').value     = '';
            montoInput.value    = '';
            montoInput.disabled = false;
            montoInput.style.background = '';
            if (montoAviso) montoAviso.style.display = 'none';
        }"""

if old_edit_crlf in js:
    js = js.replace(old_edit_crlf, new_edit_block.replace('\n', '\r\n'), 1)
    print("JS actualizado con CRLF OK")
elif old_edit_block in js:
    js = js.replace(old_edit_block, new_edit_block, 1)
    print("JS actualizado con LF OK")
else:
    print("ERROR: bloque JS no encontrado")
    # Buscar por substring parcial
    idx = js.find("document.getElementById('cobrarCliente').value = r.cliente;")
    print(f"  cobrarCliente encontrado en {idx}")
    idx2 = js.find("document.getElementById('cobrarMonto').value = r.monto;")
    print(f"  cobrarMonto encontrado en {idx2}")

with open(js_file, 'wb') as f:
    f.write(js.encode('utf-8'))
print("Archivo JS guardado")
