
file = r'c:\xampp\htdocs\Portal\public\assets\js\BalanceDiario.js'
with open(file, 'rb') as f:
    content = f.read().decode('utf-8')

# Buscar la funcion guardarEnBD y reemplazarla completa
start_marker = 'async function guardarEnBD() {'
end_marker = '}\n\n\n'  # fin de guardarEnBD

idx_start = content.find(start_marker)
if idx_start < 0:
    print('ERROR: no encontre guardarEnBD')
    exit(1)

# Buscar el cierre de la funcion (buscamos el siguiente }\n tras el finally)
idx_finally = content.find('finally {', idx_start)
if idx_finally < 0:
    print('ERROR: no encontre finally en guardarEnBD')
    exit(1)

# Encontrar el cierre del finally { ... }
depth = 0
idx_end = idx_finally
while idx_end < len(content):
    if content[idx_end] == '{':
        depth += 1
    elif content[idx_end] == '}':
        depth -= 1
        if depth == 0:
            idx_end += 1
            break
    idx_end += 1

# El cierre de la funcion guardarEnBD es el siguiente }
# Buscar el } que cierra la funcion entera (nivel 0)
idx_func_end = content.rfind('}', idx_start, idx_end + 50)
# El cierre de la func externa viene despues del cierre de finally
# Buscar la linea que solo dice "}"
lines = content[idx_start:idx_start+5000].split('\n')
depth_func = 0
char_count = idx_start
func_end_char = idx_start
for line in lines:
    opens = line.count('{')
    closes = line.count('}')
    depth_func += opens - closes
    char_count += len(line) + 1
    if depth_func == 0 and (opens > 0 or closes > 0):
        func_end_char = char_count
        break

old_func = content[idx_start:func_end_char]
print(f'Funcion encontrada: {idx_start} -> {func_end_char}, len={len(old_func)}')
print('Primeros 200 chars:', repr(old_func[:200]))
print('Ultimos 100 chars:', repr(old_func[-100:]))

new_func = '''async function guardarEnBD() {
    // Construir el payload: todos los clientes con sus docs actuales
    const clientes = dbCobrar
        .filter(c => c.docs && c.docs.length > 0)
        .map(c => ({
            emisor_receptor: c.cliente || '',
            rut: c.rut || '',
            docs: c.docs.map(doc => {
                const total  = parseFloat(doc.total  ?? doc.monto ?? 0);
                const pagado = parseFloat(doc.pagado ?? 0);
                const impago = doc.impago !== undefined ? parseFloat(doc.impago) : (total - pagado);
                return {
                    tipo_documento: doc.tipo || 'Sin tipo',
                    fecha:  doc.fecha  || '',
                    numero: String(doc.nro || doc.numero || ''),
                    rut:    doc.rut || c.rut || '',
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
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Sincronizando\u2026';

    try {
        const response = await fetch(BD_SYNC_ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ clientes }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            dbCobrar.forEach(c => { if (c.docs && c.docs.length) c.enBD = true; });
            toast(`\u2713 ${data.message}`, 'success');
        } else {
            toast(`Error: ${data.message || 'Error desconocido del servidor.'}`, 'danger');
        }
    } catch (err) {
        toast('No se pudo conectar con el servidor: ' + err.message, 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = textoOriginal;
    }
}'''

content = content[:idx_start] + new_func + content[func_end_char:]
with open(file, 'wb') as f:
    f.write(content.encode('utf-8'))
print('OK: guardarEnBD reescrita y archivo guardado')
