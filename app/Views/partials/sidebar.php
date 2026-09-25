<?php
/**
 * Partial: Sidebar compartido
 * Variables opcionales:
 *   $activePage  string  Página activa ('admin','balance-diario','gastos', etc.)
 *   $usuario     array   Datos del usuario logueado
 */
$activePage = $activePage ?? 'admin';
$contabOpen = ['balance-diario', 'gastos', 'pagos-mensuales', 'historial-balances'];
$cobranzaOpen = ['documentos-impago', 'manejo-caja'];
$cpOpen = ['cargar-entidad', 'buscar-entidad'];
$facturacionOpen = ['emitir-factura', 'buscar-documentos-facto', 'facturas-facto', 'importar-documentos-ventas', 'importar-documentos-proveedores'];
$bodegaOpen    = ['productos', 'carga-masiva-productos'];
$logisticaOpen = ['hoja-de-ruta', 'despachos-agendados', 'funcionarios'];
$devOpen       = ['solicitar-devolucion', 'historial-devoluciones'];
$adminOpen     = ['usuarios', 'cuentas-bancarias', 'datos-empresa', 'sucursales', 'api'];

function sbActive(string $p, string $active): string
{
    return $p === $active ? ' active' : '';
}
function sbOpen(array $g, string $active): string
{
    return in_array($active, $g) ? ' open' : '';
}
function sbOpenClass(array $g, string $active): string
{
    return in_array($active, $g) ? ' open' : '';
}
?>
<!-- Overlay móvil -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="cerrarSidebar()"></div>

<style>
.sidebar {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    bottom: 0 !important;
    height: 100vh !important;
    max-height: 100vh !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    -webkit-overflow-scrolling: touch !important;
    z-index: 1000 !important;
    scrollbar-width: thin !important;
    scrollbar-color: rgba(255, 255, 255, 0.4) rgba(0, 0, 0, 0.2) !important;
}
.sidebar::-webkit-scrollbar,
.sidebar-nav::-webkit-scrollbar {
    width: 8px !important;
}
.sidebar::-webkit-scrollbar-thumb,
.sidebar-nav::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.4) !important;
    border-radius: 4px !important;
}
.sidebar::-webkit-scrollbar-track,
.sidebar-nav::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.2) !important;
}
.sidebar-nav {
    overflow-y: visible !important;
}
</style>

<div class="sidebar" id="mainSidebar">
    <div class="sidebar-logo">
        <img src="<?= base_url('public/assets/img/logo_empresa.png') ?>"
             alt="Logo"
             style="height:60px;max-width:180px;object-fit:contain;">
    </div>

    <nav class="sidebar-nav">

        <div class="nav-sec">Clientes / Proveedores</div>
        <button class="s-parent<?= sbOpen($cpOpen, $activePage) ?>" onclick="toggleMenu('menuCP')" id="parentCP">
            <i class="bi bi-people-fill"></i><span>Clientes / Proveedores</span>
            <i class="bi bi-chevron-right chevron"></i>
        </button>
        <div class="sub-nav" id="menuCP" class="sub-nav<?= sbOpenClass($cpOpen, $activePage) ?>">
            <a href="<?= site_url('cargar-entidad') ?>" class="sub-link<?= sbActive('cargar-entidad', $activePage) ?>">
                <i class="bi bi-cloud-upload"></i> Cargar cliente o proveedor
            </a>
            <a href="<?= site_url('buscar-entidad') ?>" class="sub-link<?= sbActive('buscar-entidad', $activePage) ?>">
                <i class="bi bi-search"></i> Buscar cliente o proveedor
            </a>
        </div>

        <div class="nav-sec">Facturación</div>
        <button class="s-parent<?= sbOpen($facturacionOpen, $activePage) ?>" onclick="toggleMenu('menuFacturacion')" id="parentFacturacion">
            <i class="bi bi-receipt-cutoff"></i><span>Facturación</span>
            <i class="bi bi-chevron-right chevron"></i>
        </button>
        <div class="sub-nav" id="menuFacturacion" class="sub-nav<?= sbOpenClass($facturacionOpen, $activePage) ?>">
            <a href="<?= site_url('facturacion/emitir') ?>" class="sub-link<?= sbActive('emitir-factura', $activePage) ?>">
                <i class="bi bi-plus-circle"></i> Emitir
            </a>
            <a href="<?= site_url('cobranza/facturas-facto') ?>" class="sub-link<?= sbActive('buscar-documentos-facto', $activePage) ?>">
                <i class="bi bi-search"></i> Buscar documentos
            </a>

            <!-- Subgrupo Importar -->
            <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #38bdf8; padding: 10px 16px 4px 16px; border-top: 1px solid rgba(255,255,255,0.08); margin-top: 6px; display: flex; align-items: center; gap: 6px;">
                <i class="bi bi-cloud-arrow-up-fill" style="font-size: 0.85rem;"></i> Importar
            </div>
            <a href="<?= site_url('facturacion/importar/ventas') ?>" class="sub-link ps-4<?= (sbActive('facturas-facto', $activePage) || sbActive('importar-documentos-ventas', $activePage)) ?>">
                <i class="bi bi-file-earmark-spreadsheet text-emerald-400"></i> Documentos Ventas
            </a>
            <a href="<?= site_url('facturacion/importar/proveedores') ?>" class="sub-link ps-4<?= sbActive('importar-documentos-proveedores', $activePage) ?>">
                <i class="bi bi-file-earmark-text text-amber-400"></i> Documentos Proveedores
            </a>
        </div>

        <div class="nav-sec">Contabilidad</div>
        <button class="s-parent<?= sbOpen($contabOpen, $activePage) ?>" onclick="toggleMenu('menuContab')"
            id="parentContab">
            <i class="bi bi-calculator-fill"></i><span>Contabilidad</span>
            <i class="bi bi-chevron-right chevron"></i>
        </button>
        <div class="sub-nav" id="menuContab" class="sub-nav<?= sbOpenClass($contabOpen, $activePage) ?>">
            <a href="<?= site_url('balance-diario') ?>" class="sub-link<?= sbActive('balance-diario', $activePage) ?>">
                <i class="bi bi-bar-chart-line-fill"></i> Balance diario
            </a>
            <a href="<?= site_url('historial-balances') ?>" class="sub-link<?= sbActive('historial-balances', $activePage) ?>">
                <i class="bi bi-clock-history"></i> Historial de balances
            </a>
        </div>

        <div class="nav-sec">Cobranza</div>
        <button class="s-parent<?= sbOpen($cobranzaOpen, $activePage) ?>" onclick="toggleMenu('menuCobranza')"
            id="parentCobranza">
            <i class="bi bi-wallet2"></i><span>Cobranza</span>
            <i class="bi bi-chevron-right chevron"></i>
        </button>
        <div class="sub-nav" id="menuCobranza" class="sub-nav<?= sbOpenClass($cobranzaOpen, $activePage) ?>">
            <a href="<?= site_url('balance-diario') ?>" class="sub-link<?= sbActive('balance-diario', $activePage) ?>">
                <i class="bi bi-cash-stack"></i> Cuentas por Cobrar
            </a>
            <a href="<?= site_url('cobranza/manejo-caja') ?>" class="sub-link<?= sbActive('manejo-caja', $activePage) ?>">
                <i class="bi bi-safe-fill"></i> Manejo de caja
            </a>
            <a href="<?= site_url('cobranza/documentos-impago') ?>" class="sub-link<?= sbActive('documentos-impago', $activePage) ?>">
                <i class="bi bi-file-earmark-exclamation-fill"></i> Documentos impago
            </a>
        </div>

        <div class="nav-sec">Bodega</div>
        <button class="s-parent<?= sbOpen($bodegaOpen, $activePage) ?>" onclick="toggleMenu('menuBodega')"
            id="parentBodega">
            <i class="bi bi-building"></i><span>Bodega</span>
            <i class="bi bi-chevron-right chevron"></i>
        </button>
        <div class="sub-nav" id="menuBodega" class="sub-nav<?= sbOpenClass($bodegaOpen, $activePage) ?>">
            <a href="<?= site_url('bodega/facto-productos') ?>" class="sub-link<?= (sbActive('facto_productos', $activePage) || sbActive('productos', $activePage)) ?>">
                <i class="bi bi-box-seam text-info"></i> Productos
            </a>
            <a href="<?= site_url('carga-masiva-productos') ?>" class="sub-link<?= sbActive('carga-masiva-productos', $activePage) ?>">
                <i class="bi bi-file-earmark-arrow-up-fill"></i> Carga masiva de productos
            </a>
        </div>

        <div class="nav-sec">Logística</div>
        <button class="s-parent<?= sbOpen($logisticaOpen, $activePage) ?>" onclick="toggleMenu('menuLogistica')"
            id="parentLogistica">
            <i class="bi bi-truck"></i><span>Logística</span>
            <i class="bi bi-chevron-right chevron"></i>
        </button>
        <div class="sub-nav" id="menuLogistica" class="sub-nav<?= sbOpenClass($logisticaOpen, $activePage) ?>">
            <a href="<?= site_url('hoja-de-ruta') ?>" class="sub-link<?= sbActive('hoja-de-ruta', $activePage) ?>">
                <i class="bi bi-map-fill"></i> Hoja de ruta
            </a>
            <a href="<?= site_url('despachos-agendados') ?>" class="sub-link<?= sbActive('despachos-agendados', $activePage) ?>">
                <i class="bi bi-calendar-event-fill"></i> Despachos agendados
            </a>
            <a href="<?= site_url('funcionarios') ?>" class="sub-link<?= sbActive('funcionarios', $activePage) ?>">
                <i class="bi bi-people-fill"></i> Funcionarios
            </a>
        </div>



        <div class="nav-sec">Administración</div>
        <button class="s-parent<?= sbOpen($adminOpen, $activePage) ?>" onclick="toggleMenu('menuAdmin')"
            id="parentAdmin">
            <i class="bi bi-gear-fill"></i><span>Administración</span>
            <i class="bi bi-chevron-right chevron"></i>
        </button>
        <div class="sub-nav" id="menuAdmin" class="sub-nav<?= sbOpenClass($adminOpen, $activePage) ?>">
            <a href="<?= site_url('usuarios') ?>" class="sub-link<?= sbActive('usuarios', $activePage) ?>">
                <i class="bi bi-person-fill-gear"></i> Usuarios
            </a>
            <a href="<?= site_url('administracion/cuentas-bancarias') ?>" class="sub-link<?= sbActive('cuentas-bancarias', $activePage) ?>">
                <i class="bi bi-bank2"></i> Cuenta bancaria / Efectivo
            </a>
            <a href="#" class="sub-link<?= sbActive('datos-empresa', $activePage) ?>">
                <i class="bi bi-building"></i> Datos de la empresa
            </a>
            <a href="#" class="sub-link<?= sbActive('sucursales', $activePage) ?>">
                <i class="bi bi-geo-alt-fill"></i> Mis sucursales
            </a>
            <a href="#" class="sub-link<?= sbActive('api', $activePage) ?>">
                <i class="bi bi-plug-fill"></i> API
            </a>
        </div>

    </nav>

    <div class="sidebar-foot">
        <div class="d-flex align-items-center gap-2" style="flex:1;min-width:0;">
            <div class="u-avatar" id="sidebarAvatar">--</div>
            <div style="min-width:0;flex:1;">
                <div class="u-name" id="sidebarNombre" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Cargando...</div>
                <div class="u-role" id="sidebarRol">Administrador</div>
            </div>
        </div>
        <a href="<?= site_url('logout') ?>"
           title="Cerrar sesión"
           onclick="return confirm('¿Seguro que deseas cerrar sesión?')"
           style="flex-shrink:0;width:34px;height:34px;border-radius:10px;
                  background:rgba(239,68,68,.12);color:#f87171;border:1.5px solid rgba(239,68,68,.2);
                  display:flex;align-items:center;justify-content:center;
                  font-size:1rem;text-decoration:none;transition:.2s;"
           onmouseover="this.style.background='#ef4444';this.style.color='#fff';"
           onmouseout="this.style.background='rgba(239,68,68,.12)';this.style.color='#f87171';">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</div>
<script>
    // Función compartida para colapsar/expandir submenús del sidebar
    function toggleMenu(id) {
        var sub = document.getElementById(id);
        var parentId = "parent" + id.replace("menu", "");
        var btn = document.getElementById(parentId);
        var isOpen = sub && sub.classList.contains("open");
        // Cerrar todos
        document.querySelectorAll(".sub-nav").forEach(function (s) { s.classList.remove("open"); });
        document.querySelectorAll(".s-parent").forEach(function (b) { b.classList.remove("open"); });
        // Abrir el clickeado si estaba cerrado
        if (!isOpen && sub) {
            sub.classList.add("open");
            if (btn) btn.classList.add("open");
        }
    }
    (function () {
        var groups = {
            menuCP: <?= json_encode($cpOpen) ?>,
            menuFacturacion: <?= json_encode($facturacionOpen) ?>,
            menuContab: <?= json_encode($contabOpen) ?>,
            menuCobranza: <?= json_encode($cobranzaOpen) ?>,
            menuBodega: <?= json_encode($bodegaOpen) ?>,
            menuLogistica: <?= json_encode($logisticaOpen) ?>,
            menuDev: <?= json_encode($devOpen) ?>,
            menuAdmin: <?= json_encode($adminOpen) ?>
        };
        var active = <?= json_encode($activePage) ?>;
        document.addEventListener('DOMContentLoaded', function () {
            Object.keys(groups).forEach(function (menuId) {
                if (groups[menuId].indexOf(active) !== -1) {
                    var sub = document.getElementById(menuId);
                    var btn = document.getElementById('parent' + menuId.replace('menu', ''));
                    if (sub) sub.classList.add('open');
                    if (btn) btn.classList.add('open');
                }
            });
        });
    })();

    // ── Toggle sidebar móvil ──────────────────────────────────────
    function abrirSidebar() {
        document.getElementById('mainSidebar').classList.add('open');
        document.getElementById('sidebarOverlay').classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function cerrarSidebar() {
        document.getElementById('mainSidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('active');
        document.body.style.overflow = '';
    }
    // Cerrar sidebar al navegar (móvil)
    document.querySelectorAll('.sub-link[href]:not([href="#"])').forEach(function(a) {
        a.addEventListener('click', function() {
            if (window.innerWidth <= 768) cerrarSidebar();
        });
    });
</script>

<!-- ── ESTILOS Y COMPONENTE DE NOTIFICACIONES GLOBAL EN TOPBAR ── -->
<style>
.notif-bell-btn {
    position: relative;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.04);
}
.notif-bell-btn:hover {
    background: #f8fafc;
    color: #0284c7;
    border-color: #38bdf8;
    transform: translateY(-1px);
}
.notif-badge-count {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ef4444;
    color: #ffffff;
    font-size: 0.68rem;
    font-weight: 800;
    padding: 2px 6px;
    border-radius: 10px;
    border: 2px solid #ffffff;
    animation: pulseBadge 2s infinite;
}
@keyframes pulseBadge {
    0% { transform: scale(1); }
    50% { transform: scale(1.15); }
    100% { transform: scale(1); }
}
.notif-dropdown-card {
    position: fixed;
    top: 65px;
    right: 20px;
    width: 370px;
    max-width: calc(100vw - 40px);
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 16px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    z-index: 9999;
    display: none;
    overflow: hidden;
    animation: fadeInDownNotif 0.2s ease-out;
}
@keyframes fadeInDownNotif {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.notif-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #ffffff;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.88rem;
    font-weight: 700;
}
.notif-body {
    max-height: 360px;
    overflow-y: auto;
    padding: 12px;
}
.notif-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px 14px;
    margin-bottom: 10px;
    transition: all 0.2s ease;
}
.notif-item:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}
.btn-gestion-notif {
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
    color: #ffffff !important;
    border: none;
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 700;
    padding: 7px 14px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    margin-top: 8px;
    transition: all 0.2s ease;
}
.btn-gestion-notif:hover {
    background: linear-gradient(135deg, #0369a1 0%, #075985 100%);
    box-shadow: 0 4px 10px rgba(2, 132, 199, 0.3);
}
</style>

<!-- Panel Dropdown Notificaciones -->
<div class="notif-dropdown-card" id="notifDropdownCard">
    <div class="notif-header">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-bell-fill text-sky-400"></i> Notificaciones del Sistema
        </div>
        <button class="btn btn-sm text-slate-300 hover:text-white p-0" onclick="marcarNotificacionesLeidas()" style="font-size:0.75rem; border:none; background:none; text-decoration:underline; color:#cbd5e1;">
            Marcar leídas
        </button>
    </div>
    <div class="notif-body" id="notifListContainer">
        <!-- Notificaciones cargadas vía JS -->
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initNotificacionesSystem();
});

function initNotificacionesSystem() {
    var topbarRight = document.querySelector('.topbar > div.d-flex:last-child');
    if (!topbarRight) return;

    // Evitar inyección duplicada
    if (document.getElementById('notifBellBtn')) return;

    // Crear Botón Campanita
    var bellWrap = document.createElement('div');
    bellWrap.className = 'position-relative me-2';
    bellWrap.innerHTML = `
        <button class="notif-bell-btn" id="notifBellBtn" onclick="toggleNotifDropdown(event)" title="Notificaciones del Sistema">
            <i class="bi bi-bell-fill"></i>
            <span class="notif-badge-count" id="notifBadgeCount" style="display:none;">0</span>
        </button>
    `;

    var userBadge = topbarRight.querySelector('.user-badge');
    if (userBadge) {
        topbarRight.insertBefore(bellWrap, userBadge);
    } else {
        topbarRight.appendChild(bellWrap);
    }

    // Cargar notificaciones no leídas
    cargarNotificaciones();
}

function toggleNotifDropdown(e) {
    if (e) e.stopPropagation();
    var drop = document.getElementById('notifDropdownCard');
    if (drop) {
        var isOpen = drop.style.display === 'block';
        drop.style.display = isOpen ? 'none' : 'block';
        if (!isOpen) {
            cargarNotificaciones();
        }
    }
}

document.addEventListener('click', function(e) {
    var drop = document.getElementById('notifDropdownCard');
    var btn = document.getElementById('notifBellBtn');
    if (drop && drop.style.display === 'block') {
        if (!drop.contains(e.target) && (!btn || !btn.contains(e.target))) {
            drop.style.display = 'none';
        }
    }
});

function cargarNotificaciones() {
    fetch('<?= site_url('notificaciones/obtener') ?>')
        .then(function(res) { return res.json(); })
        .then(function(res) {
            if (!res || !res.success) return;
            var badge = document.getElementById('notifBadgeCount');
            var container = document.getElementById('notifListContainer');

            if (res.unread_count > 0) {
                if (badge) {
                    badge.innerText = res.unread_count;
                    badge.style.display = 'inline-block';
                }
            } else {
                if (badge) badge.style.display = 'none';
            }

            if (!container) return;

            if (res.data.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-4 text-slate-400" style="font-size:0.82rem;">
                        <i class="bi bi-check2-circle d-block mb-2" style="font-size:1.6rem; color:#10b981;"></i>
                        No tienes notificaciones pendientes
                    </div>
                `;
                return;
            }

            var html = '';
            res.data.forEach(function(item) {
                html += `
                    <div class="notif-item">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="fw-bold text-slate-800" style="font-size:0.84rem;">
                                <i class="bi bi-cloud-download-fill text-sky-500 me-1"></i> ${item.titulo}
                            </span>
                            <span class="text-slate-400" style="font-size:0.70rem;">${item.created_at ? item.created_at.substring(11,16) : ''}</span>
                        </div>
                        <div class="text-slate-600 mb-2" style="font-size:0.80rem; line-height:1.35;">
                            ${item.mensaje}
                        </div>
                        <div class="text-end">
                            <a href="${item.url_accion}" onclick="marcarNotificacionesLeidas(${item.id});" class="btn-gestion-notif">
                                <i class="bi bi-arrow-right-circle-fill"></i> Ir a gestionar
                            </a>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        })
        .catch(function(err) {
            console.error('Error cargando notificaciones:', err);
        });
}

function marcarNotificacionesLeidas(notifId) {
    var bodyData = notifId ? JSON.stringify({ id: notifId }) : '{}';
    fetch('<?= site_url('notificaciones/marcar-leidas') ?>', { 
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: bodyData
    })
        .then(function(res) { return res.json(); })
        .then(function(res) {
            if (res && res.success) {
                var badge = document.getElementById('notifBadgeCount');
                if (badge && !notifId) badge.style.display = 'none';
                cargarNotificaciones();
            }
        });
}
</script>