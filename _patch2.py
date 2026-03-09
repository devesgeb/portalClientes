file = r'c:\xampp\htdocs\Portal\public\assets\js\BalanceDiario.js'
with open(file, 'rb') as f:
    content = f.read().decode('utf-8')

# Reemplazar el array dbCobrar hardcodeado por array vacio
idx = content.find('let dbCobrar = [')
end = content.find('];', idx)
if idx < 0 or end < 0:
    print('ERROR: no encontre dbCobrar')
    exit(1)

new_dbCobrar = (
    "// dbCobrar: se carga dinamicamente desde la BD al iniciar\r\n"
    "let dbCobrar = [];"
)
content = content[:idx] + new_dbCobrar + content[end+2:]
print('OK: dbCobrar vaciado en idx', idx)

# Reemplazar la seccion INIT al final
old_init = "renderCobrar();\r\nrenderCaja();\r\nrenderPagar();"
new_init = (
    "// Carga inicial: BD manda\r\n"
    "renderCaja();\r\n"
    "renderPagar();\r\n"
    "cargarCobrarDesdeBD();"
)
if old_init in content:
    content = content.replace(old_init, new_init)
    print('OK: INIT actualizado (CRLF)')
else:
    old_init_lf = "renderCobrar();\nrenderCaja();\nrenderPagar();"
    if old_init_lf in content:
        content = content.replace(old_init_lf, new_init)
        print('OK: INIT actualizado (LF)')
    else:
        print('WARN: no se encontro INIT, buscando posicion...')
        idx2 = content.rfind('renderCobrar()')
        print('renderCobrar en idx:', idx2)

with open(file, 'wb') as f:
    f.write(content.encode('utf-8'))
print('Archivo guardado')
