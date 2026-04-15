<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Admin – Panel de Administración</title>
    <meta name="description" content="Panel de administración del Portal de Clientes.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('public/assets/css/admin.css') ?>">
</head>
<body>

<?= $this->include('partials/sidebar') ?>

<!-- MAIN -->
<div class="main">
    <div class="topbar">
        <div>
            <div class="topbar-title" id="topbarTitle">
                <i class="bi bi-grid-3x3-gap-fill me-2" style="color:var(--accent);"></i>Panel de Administración
            </div>
            <div class="topbar-sub" id="topbarSub">Portal Admin &rsaquo; Módulos del sistema</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="date-badge">
                <i class="bi bi-calendar3 me-1"></i><span id="fechaHoy"></span>
            </span>
            <div class="user-badge" onclick="abrirModalAdmin()" title="Ver información del administrador">
                <div class="ub-avatar" id="topbarAvatar">--</div>
                <div>
                    <div class="ub-name" id="topbarNombre">Cargando...</div>
                    <div class="ub-role" id="topbarRol">Administrador</div>
                </div>
                <i class="bi bi-info-circle ms-1" style="color:var(--text-sub);font-size:.80rem;"></i>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="content-area" id="contentArea">
            <div class="ca-icon"><i class="bi bi-grid-3x3-gap-fill"></i></div>
            <h5>Bienvenido al Panel de Administración</h5>
            <p>Selecciona una opción del menú lateral para comenzar a trabajar.</p>
        </div>
    </div>
</div>

<!-- Modal Info Admin -->
<div class="modal fade" id="modalAdminInfo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;">
            <div class="modal-header py-3" style="background:linear-gradient(135deg,#1e1b4b,#4338ca);border:none;">
                <h6 class="modal-title text-white">
                    <i class="bi bi-person-badge-fill me-2"></i>Información del Administrador
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
            </div>
            <div class="modal-body p-4">
                <div class="admin-avatar-lg" id="modalAdminAvatar">--</div>
                <div style="text-align:center;margin-bottom:20px;">
                    <div style="font-size:1.05rem;font-weight:700;color:var(--text-main);" id="modalAdminNombre">--</div>
                    <div style="font-size:.76rem;color:var(--text-sub);margin-top:2px;" id="modalAdminPerfil">--</div>
                </div>
                <div id="modalAdminRows"></div>
                <div style="margin-top:14px;padding:10px 14px;background:#f0fdf4;border-radius:10px;border:1px solid #bbf7d0;font-size:.78rem;color:#15803d;display:flex;align-items:center;gap:8px;" id="modalEstadoAcceso">
                    <i class="bi bi-check-circle-fill"></i><span>Cuenta activa</span>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f0f4f9;">
                <button class="btn btn-sm" style="background:#f0f4f9;color:#5a7394;border-radius:8px;" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div id="toastWrapper" style="position:fixed;bottom:24px;right:24px;z-index:9999;"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
window.ADMIN_SESSION = <?= json_encode($usuario ?? ['nombre'=>session()->get('Nombre')??'Administrador','apellidos'=>'','email'=>'','rut'=>'','telefono'=>'','estado'=>1,'ultimo_acceso'=>null,'perfil'=>'Administrador']) ?>;
window.ADMIN_BASE_URL = "<?= site_url() ?>";
</script>
<script src="<?= base_url('public/assets/js/admin.js') ?>"></script>
</body>
</html>