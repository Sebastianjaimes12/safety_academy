<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';
requireLogin();

$user_name = getUserName();
$user_id = getUserId();

// Get user points from People Academy
$points = 0;
$stmt = query($pa_conn, "SELECT points FROM user_register WHERE id = ?", 'i', $user_id);
if ($stmt) {
    mysqli_stmt_bind_result($stmt, $points);
    mysqli_stmt_fetch($stmt);
}

// Get user progress per module from Safety Academy
$progress = [];
$res = mysqli_query($sa_conn, "SELECT module_id, COUNT(*) as completed FROM sa_user_progress WHERE user_id = $user_id AND completed = 1 GROUP BY module_id");
while ($row = mysqli_fetch_assoc($res)) {
    $progress[$row['module_id']] = $row['completed'];
}

// Get total sections per module
$total_sections = [];
$res2 = mysqli_query($sa_conn, "SELECT module_id, COUNT(*) as total FROM sa_sections GROUP BY module_id");
while ($row = mysqli_fetch_assoc($res2)) {
    $total_sections[$row['module_id']] = $row['total'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safety Academy — Módulos</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --yellow: #FFD600;
            --black: #0A0A0A;
            --black-soft: #111111;
            --white: #FFFFFF;
            --gray: #1A1A1A;
            --gray-mid: #252525;
            --gray-text: #888888;
        }
        html, body { min-height: 100%; font-family: 'DM Sans', sans-serif; background: var(--black); color: var(--white); }

        /* NAV */
        .topnav {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(10,10,10,0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid #1A1A1A;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nav-brand svg { width: 32px; height: 32px; }
        .nav-brand-text {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 20px;
            letter-spacing: 2px;
        }
        .nav-brand-text span { color: var(--yellow); }
        .nav-points {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--gray);
            border: 1px solid #2A2A2A;
            border-radius: 50px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
        }
        .nav-points .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--yellow); }
        .nav-points span { color: var(--yellow); font-family: 'Bebas Neue'; font-size: 16px; letter-spacing: 1px; }

        /* HERO */
        .hero {
            padding: 40px 20px 32px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: -60px;
            left: 50%;
            transform: translateX(-50%);
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,214,0,0.08) 0%, transparent 70%);
            pointer-events: none;
        }
        .greeting {
            font-size: 13px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--gray-text);
            margin-bottom: 8px;
        }
        .hero-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(36px, 9vw, 56px);
            letter-spacing: 3px;
            line-height: 1;
            margin-bottom: 12px;
        }
        .hero-title span { color: var(--yellow); }
        .hero-sub {
            font-size: 14px;
            color: var(--gray-text);
            max-width: 300px;
            margin: 0 auto;
            line-height: 1.5;
        }

        /* POINTS BANNER */
        .points-banner {
            margin: 0 20px 28px;
            background: linear-gradient(135deg, #1A1600 0%, #252100 100%);
            border: 1px solid rgba(255,214,0,0.2);
            border-radius: 16px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .pb-left h3 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 36px;
            letter-spacing: 2px;
            color: var(--yellow);
            line-height: 1;
        }
        .pb-left p { font-size: 11px; letter-spacing: 2px; text-transform: uppercase; color: var(--gray-text); margin-top: 2px; }
        .pb-right { text-align: right; }
        .pb-right .badge {
            display: inline-block;
            background: rgba(255,214,0,0.15);
            border: 1px solid rgba(255,214,0,0.3);
            border-radius: 50px;
            padding: 6px 12px;
            font-size: 11px;
            font-weight: 600;
            color: var(--yellow);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* MODULES */
        .section-title {
            padding: 0 20px;
            margin-bottom: 16px;
        }
        .section-title h2 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 22px;
            letter-spacing: 2px;
        }
        .section-title p { font-size: 12px; color: var(--gray-text); margin-top: 2px; }

        .modules-grid {
            padding: 0 20px;
            display: grid;
            gap: 16px;
            padding-bottom: 100px;
        }

        /* MODULE CARD */
        .module-card {
            background: var(--gray);
            border: 1px solid #2A2A2A;
            border-radius: 20px;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            display: block;
            transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s;
            -webkit-tap-highlight-color: transparent;
            position: relative;
        }
        .module-card.active:hover {
            transform: translateY(-3px);
            border-color: rgba(255,214,0,0.4);
            box-shadow: 0 12px 40px rgba(255,214,0,0.1);
        }
        .module-card.locked { opacity: 0.5; cursor: not-allowed; }
        .module-card.active { cursor: pointer; }

        .card-header {
            padding: 24px 24px 20px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }
        .card-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .card-icon.yellow { background: var(--yellow); }
        .card-icon.gray { background: #2A2A2A; }
        .card-icon svg { width: 28px; height: 28px; }

        .card-status {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 50px;
        }
        .status-active { background: rgba(255,214,0,0.15); color: var(--yellow); }
        .status-locked { background: #2A2A2A; color: var(--gray-text); }
        .status-complete { background: rgba(50,200,80,0.15); color: #50C850; }

        .card-body { padding: 0 24px 24px; }
        .card-num {
            font-size: 11px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--yellow);
            margin-bottom: 6px;
        }
        .card-title-text {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 22px;
            letter-spacing: 1px;
            line-height: 1.1;
            margin-bottom: 10px;
        }
        .card-desc {
            font-size: 13px;
            color: var(--gray-text);
            line-height: 1.5;
            margin-bottom: 18px;
        }

        /* Progress bar */
        .progress-wrap { }
        .progress-info {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: var(--gray-text);
            margin-bottom: 6px;
        }
        .progress-bar {
            height: 4px;
            background: #2A2A2A;
            border-radius: 2px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: var(--yellow);
            border-radius: 2px;
            transition: width 1s ease;
        }

        /* Stats row */
        .card-stats {
            display: flex;
            gap: 16px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #2A2A2A;
        }
        .stat { text-align: center; }
        .stat-val {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 22px;
            color: var(--yellow);
            letter-spacing: 1px;
            line-height: 1;
        }
        .stat-label { font-size: 10px; color: var(--gray-text); letter-spacing: 1px; text-transform: uppercase; margin-top: 2px; }

        /* Arrow */
        .card-arrow {
            position: absolute;
            right: 24px;
            bottom: 24px;
            width: 36px;
            height: 36px;
            background: var(--yellow);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
        }
        .module-card.active:hover .card-arrow { transform: translate(3px, -3px); }

        /* BOTTOM NAV */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(10,10,10,0.95);
            backdrop-filter: blur(12px);
            border-top: 1px solid #1A1A1A;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .nav-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nav-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--yellow);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 16px;
            color: var(--black);
            font-weight: 700;
        }
        .nav-user-info { }
        .nav-user-name { font-size: 13px; font-weight: 600; }
        .nav-user-role { font-size: 11px; color: var(--gray-text); }
        .btn-logout {
            background: #1A1A1A;
            border: 1px solid #2A2A2A;
            border-radius: 10px;
            padding: 8px 16px;
            color: var(--gray-text);
            font-family: 'DM Sans', sans-serif;
            font-size: 12px;
            cursor: pointer;
            transition: color 0.2s, border-color 0.2s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .btn-logout:hover { color: var(--white); border-color: #444; }

        /* Animate in */
        .module-card { animation: card-in 0.5s ease both; }
        .module-card:nth-child(1) { animation-delay: 0.1s; }
        .module-card:nth-child(2) { animation-delay: 0.2s; }
        .module-card:nth-child(3) { animation-delay: 0.3s; }
        @keyframes card-in {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<!-- Top Nav -->
<nav class="topnav">
    <div class="nav-brand">
        <svg viewBox="0 0 80 96" fill="none">
            <path d="M40 4L4 20V52C4 72 22 88 40 94C58 88 76 72 76 52V20L40 4Z" fill="#FFD600"/>
            <path d="M40 18L14 30V50C14 65 27 78 40 83C53 78 66 65 66 50V30L40 18Z" fill="#0A0A0A"/>
            <path d="M30 50L38 58L54 40" stroke="#FFD600" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <div class="nav-brand-text">Safety <span>Academy</span></div>
    </div>
    <div class="nav-points">
        <div class="dot"></div>
        <span><?= number_format($points) ?></span>
        pts
    </div>
</nav>

<!-- Hero -->
<div class="hero">
    <div class="greeting">Bienvenido de vuelta</div>
    <h1 class="hero-title">Hola, <span><?= htmlspecialchars(explode(' ', $user_name)[0]) ?></span></h1>
    <p class="hero-sub">Continúa tu aprendizaje y gana puntos canjeables</p>
</div>

<!-- Points Banner -->
<div class="points-banner">
    <div class="pb-left">
        <h3><?= number_format($points) ?></h3>
        <p>Puntos ganados</p>
    </div>
    <div class="pb-right">
        <div class="badge">⚡ Canjéalos</div>
    </div>
</div>

<!-- Modules -->
<div class="section-title">
    <h2>Módulos de Aprendizaje</h2>
    <p>Selecciona un módulo para comenzar</p>
</div>

<div class="modules-grid">
    <?php
    $modules = [
        [
            'id' => 1, 'num' => '01', 
            'title' => 'La Seguridad Como Nuestra Forma de Vida',
            'desc' => 'SAFE Together: cultura de seguridad interdependiente, Curva de Bradley, Modelo del Queso Suizo y más.',
            'link' => 'M1/index.php',
            'active' => true,
            'sections' => 6,
            'points_possible' => 255
        ],
        [
            'id' => 2, 'num' => '02',
            'title' => 'Módulo 2',
            'desc' => 'Próximamente disponible.',
            'link' => '#',
            'active' => false,
            'sections' => 0,
            'points_possible' => 0
        ],
        [
            'id' => 3, 'num' => '03',
            'title' => 'Módulo 3',
            'desc' => 'Próximamente disponible.',
            'link' => '#',
            'active' => false,
            'sections' => 0,
            'points_possible' => 0
        ],
    ];

    foreach ($modules as $mod):
        $completed = $progress[$mod['id']] ?? 0;
        $total = $mod['sections'];
        $pct = $total > 0 ? round(($completed / $total) * 100) : 0;
        $is_done = $pct >= 100;
    ?>
    <<?= $mod['active'] ? 'a href="' . $mod['link'] . '"' : 'div' ?> 
        class="module-card <?= $mod['active'] ? 'active' : 'locked' ?>">
        
        <div class="card-header">
            <div class="card-icon <?= $mod['active'] ? 'yellow' : 'gray' ?>">
                <?php if ($mod['id'] == 1): ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="<?= $mod['active'] ? '#0A0A0A' : '#666' ?>" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                <?php elseif ($mod['id'] == 2): ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <?php else: ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
                <?php endif; ?>
            </div>
            <span class="card-status <?= $is_done ? 'status-complete' : ($mod['active'] ? 'status-active' : 'status-locked') ?>">
                <?= $is_done ? '✓ Completado' : ($mod['active'] ? 'Disponible' : 'Próximamente') ?>
            </span>
        </div>

        <div class="card-body">
            <div class="card-num">MÓDULO <?= $mod['num'] ?></div>
            <div class="card-title-text"><?= htmlspecialchars($mod['title']) ?></div>
            <div class="card-desc"><?= htmlspecialchars($mod['desc']) ?></div>

            <?php if ($mod['active']): ?>
            <div class="progress-wrap">
                <div class="progress-info">
                    <span><?= $completed ?>/<?= $total ?> secciones</span>
                    <span><?= $pct ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:<?= $pct ?>%"></div>
                </div>
            </div>
            <div class="card-stats">
                <div class="stat">
                    <div class="stat-val"><?= $mod['sections'] ?></div>
                    <div class="stat-label">Secciones</div>
                </div>
                <div class="stat">
                    <div class="stat-val"><?= $mod['points_possible'] ?></div>
                    <div class="stat-label">Pts totales</div>
                </div>
                <div class="stat">
                    <div class="stat-val"><?= $completed > 0 ? round(($completed/$total)*100) : 0 ?>%</div>
                    <div class="stat-label">Progreso</div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($mod['active']): ?>
        <div class="card-arrow">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0A0A0A" stroke-width="2.5">
                <line x1="5" y1="12" x2="19" y2="12"/>
                <polyline points="12 5 19 12 12 19"/>
            </svg>
        </div>
        <?php endif; ?>

    </<?= $mod['active'] ? 'a' : 'div' ?>>
    <?php endforeach; ?>
</div>

<!-- Bottom Nav -->
<div class="bottom-nav">
    <div class="nav-user">
        <div class="nav-avatar"><?= strtoupper(substr($user_name, 0, 1)) ?></div>
        <div class="nav-user-info">
            <div class="nav-user-name"><?= htmlspecialchars($user_name) ?></div>
            <div class="nav-user-role">Participante</div>
        </div>
    </div>
    <a href="logout.php" class="btn-logout">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
        Salir
    </a>
</div>

</body>
</html>
