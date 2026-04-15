/* ═══════════════════════════════════════════════════════════════
   PORTAL ADMIN – JavaScript principal
   Ruta: public/assets/js/admin.js
   ═══════════════════════════════════════════════════════════════ */

'use strict';

/* ══════════════════════════════════════════════════════════════════
   ESTADO GLOBAL
══════════════════════════════════════════════════════════════════ */
const Admin = {
    usuario: null,         // Datos del usuario logueado (desde servidor o sessionStorage)
    seccionActiva: null,
};

/* ══════════════════════════════════════════════════════════════════
   INIT
══════════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
    initFecha();
    initUsuario();
    initSublinks();
    restoreMenu();
});

/* ── Fecha actual en el badge ───────────────────────────────────── */
function initFecha() {
    const el = document.getElementById('fechaHoy');
    if (!el) return;
    const now = new Date();
    el.textContent = now.toLocaleDateString('es-CL', {
        day: '2-digit', month: '2-digit', year: 'numeric'
    });
}

/* ══════════════════════════════════════════════════════════════════
   USUARIO LOGUEADO
   Intenta leer desde sessionStorage (inyectado por CI4 al hacer login).
   Si no existe, usa datos de muestra para que el HTML no quede vacío.
══════════════════════════════════════════════════════════════════ */
function initUsuario() {
    // CI4 puede inyectar los datos así en la vista PHP:
    // <script>window.ADMIN_SESSION = { nombre:'Freddy', apellidos:'Espinoza', email:'...', ... };</script>
    const session = window.ADMIN_SESSION || null;

    if (session) {
        Admin.usuario = session;
    } else {
        // Fallback: leer desde sessionStorage (si lo guardaste al hacer login)
        try {
            const raw = sessionStorage.getItem('adminUsuario');
            Admin.usuario = raw ? JSON.parse(raw) : null;
        } catch (e) { Admin.usuario = null; }
    }

    // Si aún no hay datos, usar placeholder
    if (!Admin.usuario) {
        Admin.usuario = {
            nombre: 'Usuario',
            apellidos: '',
            email: '',
            rut: '',
            telefono: '',
            estado: 1,
            ultimo_acceso: null,
            perfil: 'Administrador',
        };
    }

    renderUsuario();
}

function renderUsuario() {
    const u = Admin.usuario;
    const nombreCompleto = `${u.nombre} ${u.apellidos || ''}`.trim();
    const iniciales = obtenerIniciales(nombreCompleto);

    // Sidebar footer
    setTextSafe('sidebarNombre', nombreCompleto);
    setTextSafe('sidebarRol', u.perfil || 'Administrador');
    setTextSafe('sidebarAvatar', iniciales);

    // Topbar badge
    setTextSafe('topbarNombre', nombreCompleto);
    setTextSafe('topbarRol', u.perfil || 'Administrador');
    setTextSafe('topbarAvatar', iniciales);
}

/* ══════════════════════════════════════════════════════════════════
   MODAL INFORMACIÓN ADMINISTRADOR
══════════════════════════════════════════════════════════════════ */
function abrirModalAdmin() {
    const u = Admin.usuario;
    const nombreCompleto = `${u.nombre} ${u.apellidos || ''}`.trim();
    const iniciales = obtenerIniciales(nombreCompleto);

    // Avatar grande
    setTextSafe('modalAdminAvatar', iniciales);
    setTextSafe('modalAdminNombre', nombreCompleto);
    setTextSafe('modalAdminPerfil', u.perfil || 'Administrador');

    // Rows de info
    const rows = [
        { icon: 'bi-envelope-fill', label: 'Correo electrónico', val: u.email || '—' },
        { icon: 'bi-person-vcard', label: 'RUT', val: u.rut || '—' },
        { icon: 'bi-telephone-fill', label: 'Teléfono', val: u.telefono || '—' },
        { icon: 'bi-shield-lock', label: 'Perfil de acceso', val: u.perfil || '—' },
        { icon: 'bi-clock', label: 'Último acceso', val: formatFecha(u.ultimo_acceso) },
    ];

    const container = document.getElementById('modalAdminRows');
    if (container) {
        container.innerHTML = rows.map(r => `
            <div class="info-row">
                <div class="ir-icon"><i class="bi ${r.icon}"></i></div>
                <div>
                    <div class="ir-lbl">${r.label}</div>
                    <div class="ir-val">${r.val}</div>
                </div>
            </div>
        `).join('');
    }

    // Estado
    const estadoEl = document.getElementById('modalEstadoAcceso');
    if (estadoEl) {
        if (u.estado === 1 || u.estado === true) {
            estadoEl.style.background = '#f0fdf4';
            estadoEl.style.borderColor = '#bbf7d0';
            estadoEl.style.color = '#15803d';
            estadoEl.innerHTML = '<i class="bi bi-check-circle-fill"></i><span>Cuenta activa</span>';
        } else {
            estadoEl.style.background = '#fef2f2';
            estadoEl.style.borderColor = '#fecaca';
            estadoEl.style.color = '#dc2626';
            estadoEl.innerHTML = '<i class="bi bi-x-circle-fill"></i><span>Cuenta suspendida</span>';
        }
    }

    new bootstrap.Modal(document.getElementById('modalAdminInfo')).show();
}

/* ══════════════════════════════════════════════════════════════════
   SIDEBAR – Menús colapsables
══════════════════════════════════════════════════════════════════ */
function toggleMenu(menuId) {
    const subNav = document.getElementById(menuId);
    const parent = document.querySelector(`[onclick="toggleMenu('${menuId}')"]`);
    if (!subNav) return;

    const isOpen = subNav.classList.contains('open');

    // Cerrar todos los demás
    document.querySelectorAll('.sub-nav.open').forEach(n => {
        n.classList.remove('open');
    });
    document.querySelectorAll('.s-parent.open').forEach(p => {
        p.classList.remove('open');
    });

    // Abrir o cerrar el actual
    if (!isOpen) {
        subNav.classList.add('open');
        if (parent) parent.classList.add('open');
    }

    saveMenuState();
}

/* ── Sub-links: cambiar sección activa ──────────────────────────── */
function initSublinks() {
    document.querySelectorAll('.sub-link[data-section]').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const section = link.getAttribute('data-section');
            activarSeccion(section, link.textContent.trim());

            // Marcar activo
            document.querySelectorAll('.sub-link').forEach(l => l.classList.remove('active'));
            link.classList.add('active');
        });
    });
}

/* ── Cambiar área de contenido ──────────────────────────────────── */
const SECCIONES = {
    'cargar-entidad': { icon: 'bi-cloud-upload', title: 'Cargar Cliente o Proveedor', desc: 'Sube masivamente clientes o proveedores desde un archivo Excel.', sub: 'Clientes / Proveedores &rsaquo; Cargar' },
    'buscar-entidad': { icon: 'bi-search', title: 'Buscar Cliente o Proveedor', desc: 'Busca y gestiona clientes o proveedores registrados en el sistema.', sub: 'Clientes / Proveedores &rsaquo; Buscar' },
    'gastos-diarios': { icon: 'bi-receipt-cutoff', title: 'Registro de Gastos Diarios', desc: 'Registra y revisa los gastos operacionales del día.', sub: 'Contabilidad &rsaquo; Gastos Diarios' },
    'pagos-mensuales': { icon: 'bi-calendar-check-fill', title: 'Pagos Mensuales', desc: 'Revisa y gestiona los pagos programados del mes.', sub: 'Contabilidad &rsaquo; Pagos Mensuales' },
    'historial-balances': { icon: 'bi-clock-history', title: 'Historial de Balances', desc: 'Consulta el historial de balances diarios anteriores.', sub: 'Contabilidad &rsaquo; Historial' },
    'productos': { icon: 'bi-box-seam-fill', title: 'Productos', desc: 'Administra el catálogo de productos de la tienda.', sub: 'La Tienda &rsaquo; Productos' },
    'mis-pedidos': { icon: 'bi-bag-check-fill', title: 'Mis Pedidos', desc: 'Revisa el listado de pedidos realizados.', sub: 'La Tienda &rsaquo; Pedidos' },
    'solicitar-devolucion': { icon: 'bi-send-check', title: 'Solicitar Devolución', desc: 'Gestiona las solicitudes de devolución de productos.', sub: 'Devoluciones &rsaquo; Solicitar' },
    'historial-devoluciones': { icon: 'bi-archive', title: 'Historial de Devoluciones', desc: 'Consulta el historial completo de devoluciones.', sub: 'Devoluciones &rsaquo; Historial' },
    'usuarios': { icon: 'bi-person-fill-gear', title: 'Usuarios', desc: 'Administra los usuarios del sistema, roles y accesos.', sub: 'Administración &rsaquo; Usuarios' },
    'cuentas-bancarias': { icon: 'bi-bank2', title: 'Cuentas Bancarias', desc: 'Registra y administra las cuentas bancarias de la empresa.', sub: 'Administración &rsaquo; Cuentas Bancarias' },
    'datos-empresa': { icon: 'bi-building', title: 'Datos de la Empresa', desc: 'Configura los datos generales de la empresa.', sub: 'Administración &rsaquo; Datos Empresa' },
    'sucursales': { icon: 'bi-geo-alt-fill', title: 'Mis Sucursales', desc: 'Administra las sucursales registradas.', sub: 'Administración &rsaquo; Sucursales' },
    'api-config': { icon: 'bi-plug-fill', title: 'Configuración API', desc: 'Gestiona las claves y conexiones API del sistema (Facto, WooCommerce, etc.).', sub: 'Administración &rsaquo; API' },
};

function activarSeccion(section) {
    const cfg = SECCIONES[section];
    if (!cfg) return;

    Admin.seccionActiva = section;

    // Topbar
    setHTMLSafe('topbarTitle', `<i class="bi ${cfg.icon} me-2" style="color:var(--accent);"></i>${cfg.title}`);
    setHTMLSafe('topbarSub', `Portal Admin &rsaquo; ${cfg.sub || cfg.title}`);

    // Content area
    const area = document.getElementById('contentArea');
    if (area) {
        area.innerHTML = `
            <div class="ca-icon">
                <i class="bi ${cfg.icon}"></i>
            </div>
            <h5>${cfg.title}</h5>
            <p>${cfg.desc}</p>
            <span style="font-size:.74rem;color:var(--text-sub);background:#f0f4f9;padding:5px 14px;border-radius:8px;margin-top:4px;">
                <i class="bi bi-tools me-1"></i>Módulo en construcción
            </span>
        `;
    }

    sessionStorage.setItem('adminSeccion', section);
}

/* ══════════════════════════════════════════════════════════════════
   PERSISTENCIA DE MENÚ ABIERTO (navegación entre páginas)
══════════════════════════════════════════════════════════════════ */
function saveMenuState() {
    const open = [];
    document.querySelectorAll('.sub-nav.open').forEach(n => open.push(n.id));
    sessionStorage.setItem('adminMenuOpen', JSON.stringify(open));
}

function restoreMenu() {
    try {
        const open = JSON.parse(sessionStorage.getItem('adminMenuOpen') || '[]');
        open.forEach(id => {
            const sub = document.getElementById(id);
            const parent = document.querySelector(`[onclick="toggleMenu('${id}')"]`);
            if (sub) sub.classList.add('open');
            if (parent) parent.classList.add('open');
        });

        const seccion = sessionStorage.getItem('adminSeccion');
        if (seccion) activarSeccion(seccion);
    } catch (e) { }
}

/* ══════════════════════════════════════════════════════════════════
   UTILIDADES
══════════════════════════════════════════════════════════════════ */
function obtenerIniciales(nombre) {
    return nombre
        .trim()
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map(p => p[0].toUpperCase())
        .join('');
}

function formatFecha(fechaStr) {
    if (!fechaStr) return '—';
    try {
        return new Date(fechaStr).toLocaleString('es-CL', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    } catch (e) { return fechaStr; }
}

function setTextSafe(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
}

function setHTMLSafe(id, val) {
    const el = document.getElementById(id);
    if (el) el.innerHTML = val;
}

function toast(msg, tipo = 'success') {
    const wrap = document.getElementById('toastWrapper');
    if (!wrap) return;
    const colors = { success: '#16a34a', error: '#dc2626', info: '#2563eb' };
    const t = document.createElement('div');
    t.style.cssText = `background:${colors[tipo] || colors.info};color:#fff;padding:10px 18px;border-radius:10px;
        font-size:.80rem;box-shadow:0 4px 18px rgba(0,0,0,.18);margin-top:8px;
        animation:fadeIn .25s ease;`;
    t.innerHTML = `<i class="bi bi-${tipo === 'success' ? 'check-circle' : 'info-circle'}-fill me-2"></i>${msg}`;
    wrap.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}
