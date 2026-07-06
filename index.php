<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin/dashboard.php' : 'modulo.php'));
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';
    
    if ($action === 'login') {
        $email = sanitize($pa_conn, $_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if ($email && $password) {
            $stmt = query($pa_conn, "SELECT id, name, email, password, admin FROM user_register WHERE email = ?", 's', $email);
            if ($stmt) {
                mysqli_stmt_bind_result($stmt, $id, $name, $db_email, $db_password, $admin);
                if (mysqli_stmt_fetch($stmt) && $password === $db_password) {
                    $_SESSION['user_id'] = $id;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $db_email;
                    $_SESSION['is_admin'] = $admin;
                    header('Location: ' . ($admin ? 'admin/dashboard.php' : 'modulo.php'));
                    exit;
                } else {
                    $error = 'Credenciales incorrectas. Verifica tu email y contraseña.';
                }
            }
        } else {
            $error = 'Por favor completa todos los campos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safety Academy — Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --yellow: #FFD600;
            --yellow-dark: #E6C000;
            --black: #0A0A0A;
            --black-soft: #111111;
            --white: #FFFFFF;
            --gray: #1A1A1A;
            --gray-mid: #2A2A2A;
            --gray-text: #888888;
        }

        html, body {
            height: 100%;
            font-family: 'DM Sans', sans-serif;
            background: var(--black);
            color: var(--white);
            overflow-x: hidden;
        }

        /* ---- Animated background ---- */
        .bg-canvas {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
        }

        .bg-canvas svg {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        /* ---- Shield SVG animation ---- */
        .shield-bg {
            position: fixed;
            right: -15vw;
            top: 50%;
            transform: translateY(-50%);
            width: 70vw;
            max-width: 500px;
            opacity: 0.04;
            z-index: 0;
            animation: pulse-shield 6s ease-in-out infinite;
        }

        @keyframes pulse-shield {
            0%, 100% { opacity: 0.04; transform: translateY(-50%) scale(1); }
            50% { opacity: 0.07; transform: translateY(-50%) scale(1.05); }
        }

        /* ---- Floating particles ---- */
        .particle {
            position: fixed;
            border-radius: 50%;
            background: var(--yellow);
            opacity: 0;
            animation: float-up linear infinite;
            z-index: 0;
        }

        @keyframes float-up {
            0% { transform: translateY(100vh) scale(0); opacity: 0; }
            10% { opacity: 0.3; }
            90% { opacity: 0.1; }
            100% { transform: translateY(-20vh) scale(1); opacity: 0; }
        }

        /* ---- Layout ---- */
        .page {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 20px;
        }

        /* ---- Logo / Header ---- */
        .brand {
            text-align: center;
            margin-bottom: 40px;
            animation: fade-down 0.8s ease both;
        }

        .brand-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 16px;
            position: relative;
        }

        .brand-icon svg {
            width: 100%;
            height: 100%;
            filter: drop-shadow(0 0 20px rgba(255,214,0,0.4));
            animation: icon-glow 3s ease-in-out infinite;
        }

        @keyframes icon-glow {
            0%, 100% { filter: drop-shadow(0 0 20px rgba(255,214,0,0.4)); }
            50% { filter: drop-shadow(0 0 40px rgba(255,214,0,0.8)); }
        }

        .brand-name {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(32px, 8vw, 48px);
            letter-spacing: 4px;
            color: var(--white);
            line-height: 1;
        }

        .brand-name span { color: var(--yellow); }

        .brand-tagline {
            font-size: 12px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--gray-text);
            margin-top: 6px;
        }

        /* ---- Card ---- */
        .card {
            width: 100%;
            max-width: 420px;
            background: var(--gray);
            border: 1px solid #2A2A2A;
            border-radius: 20px;
            padding: 36px 28px;
            animation: fade-up 0.8s ease 0.2s both;
        }

        .card-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 28px;
            letter-spacing: 2px;
            margin-bottom: 4px;
        }

        .card-sub {
            font-size: 13px;
            color: var(--gray-text);
            margin-bottom: 28px;
        }

        /* ---- Form ---- */
        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--gray-text);
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            background: var(--black-soft);
            border: 1px solid #2A2A2A;
            border-radius: 12px;
            padding: 14px 16px;
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            color: var(--white);
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
            -webkit-appearance: none;
        }

        .form-input:focus {
            border-color: var(--yellow);
            box-shadow: 0 0 0 3px rgba(255,214,0,0.1);
        }

        .form-input::placeholder { color: #444; }

        /* ---- Password toggle ---- */
        .input-wrap {
            position: relative;
        }

        .toggle-pass {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--gray-text);
            padding: 4px;
            transition: color 0.2s;
        }

        .toggle-pass:hover { color: var(--yellow); }

        /* ---- Submit button ---- */
        .btn-primary {
            width: 100%;
            background: var(--yellow);
            color: var(--black);
            border: none;
            border-radius: 12px;
            padding: 16px;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 18px;
            letter-spacing: 2px;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s, background 0.2s;
            margin-top: 8px;
            -webkit-tap-highlight-color: transparent;
            position: relative;
            overflow: hidden;
        }

        .btn-primary::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,0.15);
            opacity: 0;
            transition: opacity 0.2s;
        }

        .btn-primary:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 30px rgba(255,214,0,0.35);
        }

        .btn-primary:active { 
            transform: translateY(0); 
            box-shadow: none; 
        }

        .btn-primary:active::after { opacity: 1; }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* ---- Alert ---- */
        .alert {
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            animation: shake 0.4s ease;
        }

        .alert-error {
            background: rgba(255,59,48,0.1);
            border: 1px solid rgba(255,59,48,0.3);
            color: #FF6B6B;
        }

        .alert-success {
            background: rgba(255,214,0,0.1);
            border: 1px solid rgba(255,214,0,0.3);
            color: var(--yellow);
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-6px); }
            75% { transform: translateX(6px); }
        }

        /* ---- Footer info ---- */
        .card-footer {
            margin-top: 24px;
            text-align: center;
            font-size: 12px;
            color: var(--gray-text);
        }

        .card-footer strong { color: var(--yellow); }

        /* ---- Divider ---- */
        .divider {
            height: 1px;
            background: #2A2A2A;
            margin: 24px 0;
        }

        /* ---- Loading spinner ---- */
        .spinner {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(0,0,0,0.3);
            border-top-color: var(--black);
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            vertical-align: middle;
            margin-right: 8px;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* ---- Animations ---- */
        @keyframes fade-down {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fade-up {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ---- Bottom safe bar ---- */
        .safe-bar {
            margin-top: 32px;
            text-align: center;
            font-size: 11px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #333;
            animation: fade-up 0.8s ease 0.4s both;
        }
    </style>
</head>
<body>

<!-- Background decoration -->
<div class="bg-canvas">
    <!-- Animated grid lines -->
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none">
        <defs>
            <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                <path d="M 10 0 L 0 0 0 10" fill="none" stroke="#FFD600" stroke-width="0.05" opacity="0.3"/>
            </pattern>
        </defs>
        <rect width="100" height="100" fill="url(#grid)"/>
    </svg>
</div>

<!-- Shield background decoration -->
<svg class="shield-bg" viewBox="0 0 200 240" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M100 10L10 50V130C10 175 55 215 100 230C145 215 190 175 190 130V50L100 10Z" fill="#FFD600"/>
    <path d="M100 40L35 70V125C35 158 65 188 100 200C135 188 165 158 165 125V70L100 40Z" fill="#0A0A0A"/>
    <path d="M80 120L95 135L125 105" stroke="#FFD600" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
</svg>

<!-- Floating particles -->
<div class="particle" style="width:4px;height:4px;left:10%;animation-duration:8s;animation-delay:0s"></div>
<div class="particle" style="width:3px;height:3px;left:30%;animation-duration:12s;animation-delay:2s"></div>
<div class="particle" style="width:5px;height:5px;left:60%;animation-duration:10s;animation-delay:4s"></div>
<div class="particle" style="width:2px;height:2px;left:80%;animation-duration:9s;animation-delay:1s"></div>
<div class="particle" style="width:4px;height:4px;left:50%;animation-duration:14s;animation-delay:6s"></div>

<main class="page">

    <!-- Brand -->
    <div class="brand">
        <div class="brand-icon">
            <svg viewBox="0 0 80 96" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M40 4L4 20V52C4 72 22 88 40 94C58 88 76 72 76 52V20L40 4Z" fill="#FFD600"/>
                <path d="M40 18L14 30V50C14 65 27 78 40 83C53 78 66 65 66 50V30L40 18Z" fill="#0A0A0A"/>
                <path d="M30 50L38 58L54 40" stroke="#FFD600" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                <!-- Animated ring -->
                <circle cx="40" cy="48" r="34" stroke="#FFD600" stroke-width="1.5" stroke-dasharray="6 4" opacity="0.4">
                    <animateTransform attributeName="transform" type="rotate" from="0 40 48" to="360 40 48" dur="12s" repeatCount="indefinite"/>
                </circle>
            </svg>
        </div>
        <div class="brand-name">Safety <span>Academy</span></div>
        <div class="brand-tagline">Powered by People Academy</div>
    </div>

    <!-- Login Card -->
    <div class="card">
        <div class="card-title">Iniciar Sesión</div>
        <div class="card-sub">Usa tus credenciales de People Academy</div>

        <?php if ($error): ?>
        <div class="alert alert-error">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="loginForm" novalidate>
            <input type="hidden" name="action" value="login">
            
            <div class="form-group">
                <label class="form-label" for="email">Correo Electrónico</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-input" 
                    placeholder="tucorreo@empresa.com"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Contraseña</label>
                <div class="input-wrap">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        placeholder="••••••••"
                        autocomplete="current-password"
                        style="padding-right: 48px;"
                        required
                    >
                    <button type="button" class="toggle-pass" onclick="togglePassword()" aria-label="Mostrar contraseña">
                        <svg id="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-primary" id="submitBtn">
                INGRESAR A SAFETY ACADEMY
            </button>
        </form>

        <div class="divider"></div>

        <div class="card-footer">
            ¿No tienes cuenta? Regístrate en <strong>People Academy</strong><br>
            y luego accede aquí con tus mismas credenciales.
        </div>
    </div>

    <div class="safe-bar">SAFE TOGETHER · Cultura de Seguridad</div>

</main>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon = document.getElementById('eye-icon');
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    icon.innerHTML = isPass
        ? `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>`
        : `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
}

document.getElementById('loginForm').addEventListener('submit', function(e) {
    const btn = document.getElementById('submitBtn');
    const email = document.getElementById('email').value.trim();
    const pass = document.getElementById('password').value;
    
    if (!email || !pass) {
        e.preventDefault();
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span>VERIFICANDO...';
});
</script>
</body>
</html>
