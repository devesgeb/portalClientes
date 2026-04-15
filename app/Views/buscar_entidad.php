<?php $activePage = $activePage ?? 'buscar-entidad'; $usuario = $usuario ?? []; ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Buscar Cliente / Proveedor — Portal Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('public/assets/css/admin.css') ?>">
<style>
/* ── Bloque ERP ── */
.erp-block{background:#fff;border:1px solid #dde3ec;border-radius:4px;overflow:hidden;margin-bottom:20px;box-shadow:0 1px 4px rgba(0,0,0,.06)}
.erp-block-header{background:#2c2f36;color:#fff;padding:10px 18px;font-size:.82rem;font-weight:600;letter-spacing:.02em;display:flex;align-items:center;gap:8px}
.erp-block-body{padding:20px 18px 16px}
/* ── Inputs ERP ── */
.erp-field label{display:block;font-size:.72rem;font-weight:600;color:#64748b;margin-bottom:4px}
.erp-field .form-control{border:1px solid #cbd5e1;border-radius:4px;font-size:.83rem;color:#1a2940;padding:8px 12px;height:38px;transition:.15s}
.erp-field .form-control:focus{border-color:#2563eb;box-shadow:0 0 0 2px rgba(37,99,235,.12);outline:none}
.erp-field .form-control::placeholder{color:#94a3b8;font-size:.81rem}
/* ── Checks y acciones ── */
.erp-checks{display:flex;align-items:center;gap:20px}
.erp-check-item{display:flex;align-items:center;gap:7px;cursor:pointer;font-size:.83rem;font-weight:500;color:#1a2940}
.erp-check-item input[type=checkbox]{width:16px;height:16px;accent-color:#2563eb;cursor:pointer}
.erp-val-msg{font-size:.74rem;color:#dc2626;display:none;margin-top:4px}
.erp-actions{display:flex;align-items:center;gap:8px;padding-top:14px;border-top:1px solid #f1f5f9;margin-top:12px}
.btn-erp-buscar{background:#2563eb;color:#fff;border:none;border-radius:4px;padding:7px 20px;font-size:.82rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;transition:.15s}
.btn-erp-buscar:hover{background:#1d4ed8}
.btn-erp-limpiar{background:#fff;color:#64748b;border:1px solid #cbd5e1;border-radius:4px;padding:7px 16px;font-size:.82rem;font-weight:500;cursor:pointer;display:flex;align-items:center;gap:6px;transition:.15s}
.btn-erp-limpiar:hover{background:#f8fafc}
/* ── Resultados ── */
.erp-res-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
.erp-res-title{font-size:.80rem;font-weight:700;color:#1a2940;display:flex;align-items:center;gap:8px}
.erp-badge{padding:2px 10px;border-radius:20px;font-size:.72rem;font-weight:700}
.erp-badge-cl{background:rgba(37,99,235,.1);color:#2563eb}
.erp-badge-pr{background:rgba(124,58,237,.1);color:#7c3aed}
.res-count{font-size:.75rem;color:#64748b}
.erp-tw{background:#fff;border:1px solid #dde3ec;border-radius:4px;overflow:hidden;margin-bottom:20px}
.erp-tbl{width:100%;border-collapse:collapse;font-size:.81rem}
.erp-tbl thead th{background:#f8fafd;padding:9px 14px;font-size:.71rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid #e2e8f0;white-space:nowrap}
.erp-tbl tbody td{padding:9px 14px;border-bottom:1px solid #f1f5f9;color:#1a2940;vertical-align:middle}
.erp-tbl tbody tr:last-child td{border-bottom:none}
.erp-tbl tbody tr:hover{background:#f8fafd}
.td-b{font-weight:600}.td-m{font-family:monospace;font-size:.79rem;color:#4b6080}.td-s{color:#64748b}
.abtn{display:flex;gap:4px;justify-content:flex-end}
.bv{background:rgba(37,99,235,.1);color:#2563eb;border:none;border-radius:4px;padding:4px 10px;font-size:.76rem;font-weight:600;cursor:pointer;transition:.12s}
.be{background:rgba(22,163,74,.1);color:#16a34a;border:none;border-radius:4px;padding:4px 10px;font-size:.76rem;font-weight:600;cursor:pointer;transition:.12s}
.bd{background:rgba(220,38,38,.1);color:#dc2626;border:none;border-radius:4px;padding:4px 10px;font-size:.76rem;font-weight:600;cursor:pointer;transition:.12s}
.bv:hover{background:#2563eb;color:#fff}.be:hover{background:#16a34a;color:#fff}.bd:hover{background:#dc2626;color:#fff}
.empty-st{text-align:center;padding:40px;color:#94a3b8}
.empty-st i{font-size:2rem;display:block;margin-bottom:10px;opacity:.4}.empty-st p{font-size:.82rem;margin:0}
#be-sp{display:none;text-align:center;padding:40px}
.dl{font-size:.71rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:2px}
.dv{font-size:.85rem;font-weight:500;color:#1a2940}
</style>
</head>
<body>
<script>window.ADMIN_SESSION = <?= json_encode($usuario) ?>;</script>

<?= $this->include('partials/sidebar') ?>

<div class="main">
    <!-- Topbar -->
    <div class="topbar">
        <div>
            <div class="topbar-title"><i class="bi bi-search me-2" style="color:var(--accent);"></i>Buscar Cliente / Proveedor</div>
            <div class="topbar-sub">Portal Admin &rsaquo; Clientes / Proveedores &rsaquo; Buscar</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="user-badge">
                <div class="ub-avatar" id="topbarAvatar">--</div>
                <div>
                    <div class="ub-name" id="topbarNombre">Cargando...</div>
                    <div class="ub-role" id="topbarRol">Administrador</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Page body -->
    <div class="page-body">

        <!-- Bloque búsqueda ERP -->
        <div class="erp-block">
            <div class="erp-block-header"><i class="bi bi-search"></i>Buscar Cliente / Proveedor</div>
            <div class="erp-block-body">
                <div class="row g-3">

                    <div class="col-md-3">
                        <div class="erp-field">
                            <label for="fNombre">Nombre de fantasía</label>
                            <input type="text" id="fNombre" class="form-control" placeholder="Ej: Comercial XYZ" maxlength="120" oninput="clrV()">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="erp-field">
                            <label for="fRut">RUT Cliente / Proveedor</label>
                            <input type="text" id="fRut" class="form-control" placeholder="Ej: 12.345.678-9" maxlength="20" oninput="fmtRut(this);clrV()">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="erp-field">
                            <label for="fRazon">Nombre o Razón Social</label>
                            <input type="text" id="fRazon" class="form-control" placeholder="Ej: Constructora ABC Ltda." maxlength="200" oninput="clrV()">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="erp-field">
                            <label for="fGiro">Centro de costo</label>
                            <input type="text" id="fGiro" class="form-control" placeholder="** Todos **" maxlength="120" oninput="clrV()">
                        </div>
                    </div>

                </div>

                <!-- Checkboxes + Botones -->
                <div class="erp-actions">
                    <div class="erp-checks me-auto">
                        <label class="erp-check-item">
                            <input type="checkbox" id="chkCl" checked onchange="clrV()">
                            <span>Clientes</span>
                        </label>
                        <label class="erp-check-item">
                            <input type="checkbox" id="chkPr" onchange="clrV()">
                            <span>Proveedores</span>
                        </label>
                    </div>
                    <div class="erp-val-msg" id="msgTipo"><i class="bi bi-exclamation-circle me-1"></i>Selecciona al menos un tipo.</div>
                    <button class="btn-erp-limpiar" onclick="limpiar()"><i class="bi bi-eraser"></i>Limpiar</button>
                    <button class="btn-erp-buscar" onclick="buscar()"><i class="bi bi-search"></i>Buscar</button>
                </div>
            </div>
        </div>

        <div id="be-sp"><div class="spinner-border text-primary" style="width:1.8rem;height:1.8rem;"></div>
        <p class="mt-2" style="font-size:.80rem;color:#64748b;">Buscando...</p></div>

        <div id="be-res"></div>

    </div><!-- /page-body -->
</div><!-- /main -->

<!-- Modal Visualizar -->
<div class="modal fade" id="mVer" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable">
<div class="modal-content" style="border-radius:8px;">
<div class="modal-header py-3" style="background:#2c2f36;">
<h6 class="modal-title text-white"><i class="bi bi-eye me-2"></i><span id="vTit">Detalle</span></h6>
<button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button></div>
<div class="modal-body p-4"><div class="row" id="vBody"></div></div>
<div class="modal-footer border-0 pb-3"><button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">Cerrar</button></div>
</div></div></div>

<!-- Modal Modificar -->
<div class="modal fade" id="mEdit" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable">
<div class="modal-content" style="border-radius:8px;">
<div class="modal-header py-3" style="background:#064e3b;">
<h6 class="modal-title text-white"><i class="bi bi-pencil-square me-2"></i><span id="eTit">Modificar</span></h6>
<button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button></div>
<div class="modal-body p-4">
<input type="hidden" id="eId"><input type="hidden" id="eTipo">
<div class="row g-3" id="eBody"></div>
<div class="erp-val-msg mt-2" id="msgEdit"><i class="bi bi-exclamation-circle me-1"></i>Nombre o Razón Social requerido.</div>
</div>
<div class="modal-footer border-0 pb-3">
<button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">Cancelar</button>
<button type="button" class="btn btn-success btn-sm px-4" onclick="guardar()"><i class="bi bi-check-circle me-1"></i>Guardar</button>
</div></div></div></div>

<!-- Modal Eliminar -->
<div class="modal fade" id="mDel" tabindex="-1"><div class="modal-dialog modal-dialog-centered">
<div class="modal-content" style="border-radius:8px;">
<div class="modal-header py-3" style="background:#7f1d1d;">
<h6 class="modal-title text-white"><i class="bi bi-trash3 me-2"></i>Eliminar registro</h6>
<button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button></div>
<div class="modal-body p-4 text-center">
<i class="bi bi-exclamation-triangle-fill text-danger" style="font-size:2.5rem;"></i>
<p class="mt-3 mb-1 fw-semibold" id="dNom">—</p>
<p class="text-secondary" style="font-size:.82rem;">Esta acción es <strong>irreversible</strong>. ¿Confirmas?</p>
</div>
<div class="modal-footer border-0 pb-3 justify-content-center gap-2">
<button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">Cancelar</button>
<button type="button" class="btn btn-danger btn-sm px-4" onclick="confirmarDel()"><i class="bi bi-trash3 me-1"></i>Sí, eliminar</button>
</div>
<input type="hidden" id="dId"><input type="hidden" id="dTipo">
</div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
var BASE="<?= rtrim(site_url(), '/') ?>/";
function fmtRut(i){var v=i.value.replace(/[^0-9kK]/g,'').toUpperCase();i.value=v.length>1?v.slice(0,-1).replace(/\B(?=(\d{3})+(?!\d))/g,'.')+'-'+v.slice(-1):v;}
function clrV(){document.getElementById('msgTipo').style.display='none';var m=document.getElementById('msgEdit');if(m)m.style.display='none';}
function limpiar(){['fNombre','fRut','fRazon','fGiro'].forEach(function(id){var e=document.getElementById(id);if(e)e.value='';});document.getElementById('chkCl').checked=true;document.getElementById('chkPr').checked=false;document.getElementById('be-res').innerHTML='';clrV();}
function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

function buscar(){
    var cl=document.getElementById('chkCl').checked,pr=document.getElementById('chkPr').checked;
    if(!cl&&!pr){document.getElementById('msgTipo').style.display='block';return;}
    var tipo=(cl&&pr)?'ambos':(cl?'cliente':'proveedor');
    var p=new URLSearchParams({tipo:tipo});
    var n=document.getElementById('fNombre').value.trim(),r=document.getElementById('fRut').value.trim(),s=document.getElementById('fRazon').value.trim(),g=document.getElementById('fGiro').value.trim();
    if(n)p.append('nombre',n);if(r)p.append('rut',r);if(s)p.append('razon_social',s);if(g)p.append('giro',g);
    document.getElementById('be-sp').style.display='block';document.getElementById('be-res').innerHTML='';
    fetch(BASE+'buscar-entidad/buscar?'+p.toString())
    .then(function(r){return r.json();})
    .then(function(d){document.getElementById('be-sp').style.display='none';var h='';if(tipo==='cliente'||tipo==='ambos')h+=tbl(d.clientes||[],'cliente');if(tipo==='proveedor'||tipo==='ambos')h+=tbl(d.proveedores||[],'proveedor');document.getElementById('be-res').innerHTML=h;})
    .catch(function(){document.getElementById('be-sp').style.display='none';document.getElementById('be-res').innerHTML='<div class="alert alert-danger">Error de conexión.</div>';});}

function tbl(rows,tipo){
    var isCl=tipo==='cliente',label=isCl?'Clientes':'Proveedores',badge=isCl?'erp-badge-cl':'erp-badge-pr',icon=isCl?'bi-person-fill':'bi-truck';
    var h='<div class="erp-res-header"><span class="erp-res-title"><i class="bi '+icon+'"></i>'+label+' <span class="erp-badge '+badge+'">'+rows.length+'</span></span><span class="res-count">'+rows.length+' resultado'+(rows.length!==1?'s':'')+' encontrado'+(rows.length!==1?'s':'')+'</span></div>';
    if(!rows.length){h+='<div class="erp-tw"><div class="empty-st"><i class="bi bi-inbox"></i><p>Sin '+label.toLowerCase()+' con esos filtros.</p></div></div>';}
    else{h+='<div class="erp-tw"><table class="erp-tbl"><thead><tr><th>Nombre / Razón Social</th><th>RUT</th><th>Giro / Centro</th><th>Ciudad</th><th>Teléfono</th><th>Email</th><th style="text-align:right">Acciones</th></tr></thead><tbody>';
    rows.forEach(function(r){var n=esc(r.razon_social||r.nombre||'—');
    h+='<tr><td class="td-b">'+n+'</td><td class="td-m">'+esc(r.rut||'—')+'</td><td class="td-s">'+esc(r.giro||'—')+'</td><td class="td-s">'+esc(r.ciudad||'—')+'</td><td class="td-s">'+esc(r.telefono_empresa||'—')+'</td><td class="td-s">'+esc(r.email||'—')+'</td>';
    h+='<td><div class="abtn"><button class="bv" onclick="ver('+r.id+',\''+tipo+'\')" title="Visualizar"><i class="bi bi-eye"></i></button><button class="be" onclick="editar('+r.id+',\''+tipo+'\')" title="Modificar"><i class="bi bi-pencil"></i></button><button class="bd" onclick="eliminar('+r.id+',\''+tipo+'\',\''+n+'\')" title="Eliminar"><i class="bi bi-trash3"></i></button></div></td></tr>';});
    h+='</tbody></table></div>';}
    return h;}

function ver(id,tipo){
    fetch(BASE+'buscar-entidad/detalle?id='+id+'&tipo='+tipo).then(function(r){return r.json();}).then(function(d){
    document.getElementById('vTit').textContent=(tipo==='cliente'?'Cliente':'Proveedor')+': '+(d.razon_social||d.nombre||'');
    var cc=[['Nombre',d.nombre],['Razón Social',d.razon_social],['RUT',d.rut],['Giro',d.giro],['Email',d.email],['Teléfono',d.telefono_empresa],['Dirección',d.direccion],['Ciudad',d.ciudad],['Comuna',d.comuna],['País',d.pais],['Contacto',d.contacto_nombre],['Tel. Contacto',d.telefono_contacto]];
    var h='';cc.forEach(function(c){if(c[1]){h+='<div class="col-md-6 mb-3"><div class="dl">'+esc(c[0])+'</div><div class="dv">'+esc(c[1])+'</div></div>';}});
    document.getElementById('vBody').innerHTML=h||'<p class="text-muted small">Sin datos.</p>';
    new bootstrap.Modal(document.getElementById('mVer')).show();});}

var _EC=['nombre','razon_social','rut','giro','email','telefono_empresa','direccion','ciudad','comuna','pais','contacto_nombre','telefono_contacto'];
var _EL=['Nombre','Razón Social','RUT','Giro','Email','Teléfono Empresa','Dirección','Ciudad','Comuna','País','Nombre Contacto','Tel. Contacto'];
function editar(id,tipo){
    fetch(BASE+'buscar-entidad/detalle?id='+id+'&tipo='+tipo).then(function(r){return r.json();}).then(function(d){
    document.getElementById('eId').value=id;document.getElementById('eTipo').value=tipo;
    document.getElementById('eTit').textContent='Modificar '+(tipo==='cliente'?'Cliente':'Proveedor')+': '+(d.razon_social||d.nombre||'');
    var h='';_EC.forEach(function(k,i){h+='<div class="col-md-6"><div class="erp-field"><label>'+_EL[i]+'</label><input type="text" class="form-control form-control-sm" id="ef_'+k+'" value="'+esc(d[k]||'')+'"></div></div>';});
    document.getElementById('eBody').innerHTML=h;new bootstrap.Modal(document.getElementById('mEdit')).show();});}

function guardar(){
    var payload={id:parseInt(document.getElementById('eId').value),tipo:document.getElementById('eTipo').value};
    _EC.forEach(function(k){var e=document.getElementById('ef_'+k);if(e)payload[k]=e.value.trim();});
    if(!payload.nombre&&!payload.razon_social){document.getElementById('msgEdit').style.display='block';return;}
    fetch(BASE+'buscar-entidad/actualizar',{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)}).then(function(r){return r.json();}).then(function(d){if(d.ok){bootstrap.Modal.getInstance(document.getElementById('mEdit')).hide();buscar();}else alert('Error: '+(d.error||'desconocido'));});}

function eliminar(id,tipo,nom){document.getElementById('dId').value=id;document.getElementById('dTipo').value=tipo;document.getElementById('dNom').textContent=nom;new bootstrap.Modal(document.getElementById('mDel')).show();}
function confirmarDel(){var id=document.getElementById('dId').value,tipo=document.getElementById('dTipo').value;
    fetch(BASE+'buscar-entidad/eliminar?id='+id+'&tipo='+tipo,{method:'DELETE'}).then(function(r){return r.json();}).then(function(d){if(d.ok){bootstrap.Modal.getInstance(document.getElementById('mDel')).hide();buscar();}else alert('Error: '+(d.error||'desconocido'));});}

document.addEventListener('DOMContentLoaded',function(){
    var u=window.ADMIN_SESSION||{},n=u.nombre||'Admin',ini=n.substring(0,2).toUpperCase();
    var el=function(id,v){var e=document.getElementById(id);if(e)e.textContent=v;};
    el('topbarAvatar',ini);el('topbarNombre',n);el('topbarRol',u.perfil||'Administrador');
    el('sidebarAvatar',ini);el('sidebarNombre',n);el('sidebarRol',u.perfil||'Administrador');});
['fNombre','fRut','fRazon','fGiro'].forEach(function(id){var el=document.getElementById(id);if(el)el.addEventListener('keydown',function(e){if(e.key==='Enter')buscar();});});
</script>
</body></html>