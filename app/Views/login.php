<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Portal de Administración - Inicio de sesión">
    <title>Portal Admin — Iniciar sesión</title>

    <!-- Bootstrap + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <?php
        if (isset($css)) {
            foreach ($css as $css_file) {
                echo '<link rel="stylesheet" href="' . esc($css_file) . '">';
            }
        }
        $img_file = $img[0] ?? '';
    ?>

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Círculos decorativos de fondo */
        body::before, body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            opacity: .12;
            pointer-events: none;
        }
        body::before {
            width: 600px; height: 600px;
            background: radial-gradient(circle, #6366f1, transparent);
            top: -150px; left: -150px;
        }
        body::after {
            width: 500px; height: 500px;
            background: radial-gradient(circle, #a78bfa, transparent);
            bottom: -120px; right: -120px;
        }

        /* Card login */
        .login-card {
            width: 100%; max-width: 420px;
            background: rgba(255,255,255,.06);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 24px;
            padding: 40px 36px;
            box-shadow: 0 24px 60px rgba(0,0,0,.5);
            position: relative;
            z-index: 10;
        }

        /* Logo */
        .login-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 28px;
        }
        .login-logo img { max-height: 64px; max-width: 200px; object-fit: contain; filter: brightness(1.2); }

        /* Título */
        .login-title {
            font-size: 1.4rem; font-weight: 800;
            color: #fff; text-align: center;
            margin-bottom: 4px;
        }
        .login-sub {
            font-size: .82rem; color: rgba(255,255,255,.5);
            text-align: center; margin-bottom: 28px;
        }

        /* Inputs */
        .form-group { margin-bottom: 16px; position: relative; }
        .form-label-custom {
            display: block; font-size: .75rem; font-weight: 600;
            color: rgba(255,255,255,.6); margin-bottom: 6px;
            text-transform: uppercase; letter-spacing: .06em;
        }
        .form-input {
            width: 100%; padding: 12px 44px 12px 16px;
            background: rgba(255,255,255,.08);
            border: 1.5px solid rgba(255,255,255,.14);
            border-radius: 12px; color: #fff;
            font-size: .9rem; font-family: 'Inter', sans-serif;
            outline: none; transition: .2s;
        }
        .form-input::placeholder { color: rgba(255,255,255,.3); }
        .form-input:focus {
            border-color: #818cf8;
            background: rgba(255,255,255,.12);
            box-shadow: 0 0 0 3px rgba(129,140,248,.2);
        }
        .input-icon {
            position: absolute; right: 14px; bottom: 13px;
            color: rgba(255,255,255,.3); font-size: 1rem; cursor: pointer;
        }
        .input-icon:hover { color: rgba(255,255,255,.7); }

        /* Alerta de error */
        .login-error {
            background: rgba(239,68,68,.15);
            border: 1.5px solid rgba(239,68,68,.4);
            border-radius: 12px;
            padding: 10px 14px;
            color: #fca5a5;
            font-size: .82rem;
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 18px;
            animation: shake .4s ease;
        }
        @keyframes shake {
            0%,100%{ transform:translateX(0); }
            25%{ transform:translateX(-6px); }
            75%{ transform:translateX(6px); }
        }

        /* Botón */
        .btn-login {
            width: 100%; padding: 13px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: none; border-radius: 12px;
            color: #fff; font-size: .9rem; font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer; transition: .2s;
            margin-top: 8px;
            letter-spacing: .02em;
            box-shadow: 0 4px 20px rgba(99,102,241,.4);
        }
        .btn-login:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 8px 28px rgba(99,102,241,.6);
        }
        .btn-login:disabled { opacity: .7; cursor: not-allowed; transform: none; }

        /* ── Loading Overlay ────────────────────────────────────── */
        #loadingOverlay {
            display: none;
            position: fixed; inset: 0; z-index: 9999;
            background: rgba(15,12,41,.85);
            backdrop-filter: blur(8px);
            flex-direction: column;
            align-items: center; justify-content: center;
            gap: 24px;
        }
        #loadingOverlay.show { display: flex; }

        .loading-spinner {
            width: 64px; height: 64px;
            border-radius: 50%;
            border: 4px solid rgba(129,140,248,.2);
            border-top-color: #818cf8;
            animation: spin .9s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .loading-text {
            color: #fff; font-size: 1rem; font-weight: 600;
            text-align: center;
        }
        .loading-sub {
            color: rgba(255,255,255,.45); font-size: .8rem;
            margin-top: 4px; text-align: center;
        }

        /* Barra de progreso */
        .loading-bar-wrap {
            width: 220px; height: 4px;
            background: rgba(255,255,255,.1);
            border-radius: 99px; overflow: hidden;
        }
        .loading-bar {
            height: 100%;
            background: linear-gradient(90deg, #6366f1, #a78bfa);
            border-radius: 99px;
            width: 0%;
            transition: width .1s linear;
        }

        /* Footer */
        .login-footer {
            text-align: center; margin-top: 24px;
            font-size: .73rem; color: rgba(255,255,255,.25);
        }
    </style>
</head>
<body>

<!-- ══ Loading Overlay ════════════════════════════════════════════ -->
<div id="loadingOverlay">
    <div class="loading-spinner"></div>
    <div>
        <div class="loading-text">Verificando credenciales...</div>
        <div class="loading-sub" id="loadingSubText">Iniciando sesión</div>
    </div>
    <div class="loading-bar-wrap">
        <div class="loading-bar" id="loadingBar"></div>
    </div>
</div>

<!-- ══ Card de Login ══════════════════════════════════════════════ -->
<div class="login-card">

    <!-- Logo -->
    <div class="login-logo">
        <img src="<?= base_url('public/assets/img/logo_empresa.png') ?>" alt="Logo Portal" onerror="this.style.display='none'">
    </div>

    <div class="login-title">Bienvenido</div>
    <div class="login-sub">Ingresa tus credenciales para continuar</div>

    <!-- Alerta de error (flash) -->
    <?php $loginError = session()->getFlashdata('login_error'); ?>
    <?php if ($loginError): ?>
    <div class="login-error" id="errorAlert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span><?= esc($loginError) ?></span>
    </div>
    <?php endif; ?>

    <!-- Formulario -->
    <form id="loginForm" method="post" action="<?= site_url('home/validaUser') ?>" onsubmit="iniciarLogin(event)">
        <?= csrf_field() ?>

        <div class="form-group">
            <label class="form-label-custom" for="inputUsuario">Usuario</label>
            <input type="text"
                   id="inputUsuario"
                   name="Usuario"
                   class="form-input"
                   placeholder="Ingresa tu usuario"
                   autocomplete="username"
                   required>
            <i class="bi bi-person-fill input-icon"></i>
        </div>

        <div class="form-group">
            <label class="form-label-custom" for="inputClave">Contraseña</label>
            <input type="password"
                   id="inputClave"
                   name="Clave"
                   class="form-input"
                   placeholder="••••••••"
                   autocomplete="current-password"
                   required>
            <i class="bi bi-eye input-icon" id="toggleOjo" onclick="togglePassword()" style="cursor:pointer;"></i>
        </div>

        <button type="submit" class="btn-login" id="btnLogin">
            <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar
        </button>
    </form>

    <div class="login-footer">
        © <?= date('Y') ?> F Alimentos · Portal de Administración
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ── Mostrar/ocultar clave ───────────────────────────────────── */
function togglePassword() {
    const inp = document.getElementById('inputClave');
    const ico = document.getElementById('toggleOjo');
    if (inp.type === 'password') {
        inp.type = 'text';
        ico.className = 'bi bi-eye-slash input-icon';
    } else {
        inp.type = 'password';
        ico.className = 'bi bi-eye input-icon';
    }
}

/* ── Loading al hacer submit ─────────────────────────────────── */
function iniciarLogin(e) {
    const usuario = document.getElementById('inputUsuario').value.trim();
    const clave   = document.getElementById('inputClave').value.trim();

    if (!usuario || !clave) return; // dejar que HTML5 maneje el required

    // Mostrar overlay
    const overlay = document.getElementById('loadingOverlay');
    const bar     = document.getElementById('loadingBar');
    const subText = document.getElementById('loadingSubText');
    const btn     = document.getElementById('btnLogin');

    overlay.classList.add('show');
    btn.disabled = true;

    // Textos progresivos
    const pasos = [
        { pct: 20, txt: 'Conectando al servidor...' },
        { pct: 45, txt: 'Verificando usuario...' },
        { pct: 70, txt: 'Comprobando permisos...' },
        { pct: 90, txt: 'Cargando tu perfil...' },
        { pct: 99, txt: 'Preparando el panel...' },
    ];

    let i = 0;
    const intervalo = setInterval(() => {
        if (i < pasos.length) {
            bar.style.width     = pasos[i].pct + '%';
            subText.textContent = pasos[i].txt;
            i++;
        } else {
            clearInterval(intervalo);
        }
    }, 600); // cada 600ms → ~3 segundos en total antes de que el form haga el POST

    // Delay de 3 seg antes de enviar el form
    e.preventDefault();
    setTimeout(() => {
        clearInterval(intervalo);
        bar.style.width = '100%';
        subText.textContent = 'Redirigiendo...';
        setTimeout(() => document.getElementById('loginForm').submit(), 300);
    }, 3000);
}

/* ── Auto-ocultar error después de 6 seg ────────────────────── */
const alertaError = document.getElementById('errorAlert');
if (alertaError) {
    setTimeout(() => {
        alertaError.style.transition = 'opacity .5s';
        alertaError.style.opacity    = '0';
        setTimeout(() => alertaError.remove(), 500);
    }, 6000);
}
</script>
</body>
</html>
