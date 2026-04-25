<?php
/**
 * Partial: Sidebar compartido
 * Variables opcionales:
 *   $activePage  string  Página activa ('admin','balance-diario','gastos', etc.)
 *   $usuario     array   Datos del usuario logueado
 */
$activePage = $activePage ?? 'admin';
$contabOpen = ['balance-diario', 'gastos', 'pagos-mensuales', 'historial-balances'];
$cpOpen = ['cargar-entidad', 'buscar-entidad'];
$bodegaOpen = ['productos', 'maestro-articulos', 'carga-masiva-productos', 'reportes-bodega'];
$devOpen = ['solicitar-devolucion', 'historial-devoluciones'];
$adminOpen = ['usuarios', 'cuentas-bancarias', 'datos-empresa', 'sucursales', 'api'];

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
            <a href="#" class="sub-link<?= sbActive('gastos', $activePage) ?>">
                <i class="bi bi-receipt-cutoff"></i> Registro de gastos diarios
            </a>
            <a href="#" class="sub-link<?= sbActive('pagos-mensuales', $activePage) ?>">
                <i class="bi bi-calendar-check-fill"></i> Pagos mensuales
            </a>
            <a href="#" class="sub-link<?= sbActive('historial-balances', $activePage) ?>">
                <i class="bi bi-clock-history"></i> Historial de balances
            </a>
        </div>

        <div class="nav-sec">Bodega</div>
        <button class="s-parent<?= sbOpen($bodegaOpen, $activePage) ?>" onclick="toggleMenu('menuBodega')"
            id="parentBodega">
            <i class="bi bi-building"></i><span>Bodega</span>
            <i class="bi bi-chevron-right chevron"></i>
        </button>
        <div class="sub-nav" id="menuBodega" class="sub-nav<?= sbOpenClass($bodegaOpen, $activePage) ?>">
            <a href="<?= site_url('productos') ?>" class="sub-link<?= sbActive('productos', $activePage) ?>">
                <i class="bi bi-box-seam-fill"></i> Productos
            </a>
            <a href="#" class="sub-link<?= sbActive('maestro-articulos', $activePage) ?>">
                <i class="bi bi-journal-text"></i> Maestro de artículos
            </a>
            <a href="<?= site_url('carga-masiva-productos') ?>" class="sub-link<?= sbActive('carga-masiva-productos', $activePage) ?>">
                <i class="bi bi-file-earmark-arrow-up-fill"></i> Carga masiva de productos
            </a>
            <a href="#" class="sub-link<?= sbActive('reportes-bodega', $activePage) ?>">
                <i class="bi bi-graph-up"></i> Reportes
            </a>
        </div>

        <div class="nav-sec">Devoluciones</div>
        <button class="s-parent<?= sbOpen($devOpen, $activePage) ?>" onclick="toggleMenu('menuDev')" id="parentDev">
            <i class="bi bi-arrow-return-left"></i><span>Devoluciones</span>
            <i class="bi bi-chevron-right chevron"></i>
        </button>
        <div class="sub-nav" id="menuDev" class="sub-nav<?= sbOpenClass($devOpen, $activePage) ?>">
            <a href="#" class="sub-link<?= sbActive('solicitar-devolucion', $activePage) ?>">
                <i class="bi bi-send-check"></i> Solicitar devolución
            </a>
            <a href="#" class="sub-link<?= sbActive('historial-devoluciones', $activePage) ?>">
                <i class="bi bi-archive"></i> Historial de devoluciones
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
            <a href="#" class="sub-link<?= sbActive('cuentas-bancarias', $activePage) ?>">
                <i class="bi bi-bank2"></i> Cuentas bancarias
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
            menuContab: <?= json_encode($contabOpen) ?>,
            menuBodega: <?= json_encode($bodegaOpen) ?>,
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