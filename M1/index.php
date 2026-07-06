<?php
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/config/database.php';
requireLogin();

$user_id = getUserId();
$user_name = getUserName();

// Get sections
$sections = [];
$res = mysqli_query($sa_conn, "SELECT s.*, 
    (SELECT completed FROM sa_user_progress WHERE user_id = $user_id AND section_id = s.id) as completed,
    (SELECT points_earned FROM sa_user_progress WHERE user_id = $user_id AND section_id = s.id) as points_earned,
    (SELECT COUNT(*) FROM sa_questions WHERE section_id = s.id AND is_active = 1) as question_count
    FROM sa_sections s WHERE s.module_id = 1 ORDER BY s.order_num");
while ($row = mysqli_fetch_assoc($res)) $sections[] = $row;

// Get total completed
$completed_count = count(array_filter($sections, function($s) { return $s['completed']; }));
$total = count($sections);

// Get user points
$points = 0;
$stmt = query($pa_conn, "SELECT points FROM user_register WHERE id = ?", 'i', $user_id);
if ($stmt) { mysqli_stmt_bind_result($stmt, $points); mysqli_stmt_fetch($stmt); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo 1 — Safety Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --yellow: #FFD600;
            --black: #0A0A0A;
            --white: #FFFFFF;
            --gray: #1A1A1A;
            --gray-mid: #252525;
            --gray-text: #888888;
        }
        html, body { min-height: 100%; font-family: 'DM Sans', sans-serif; background: var(--black); color: var(--white); }

        .topnav {
            position: sticky; top: 0; z-index: 100;
            background: rgba(10,10,10,0.95); backdrop-filter: blur(12px);
            border-bottom: 1px solid #1A1A1A;
            padding: 14px 20px;
            display: flex; align-items: center; gap: 14px;
        }
        .back-btn {
            width: 36px; height: 36px;
            background: var(--gray); border: 1px solid #2A2A2A; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; color: var(--white);
            transition: border-color 0.2s;
        }
        .back-btn:hover { border-color: var(--yellow); }
        .nav-info { flex: 1; }
        .nav-module { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: var(--yellow); }
        .nav-title { font-family: 'Bebas Neue', sans-serif; font-size: 18px; letter-spacing: 1px; }
        .nav-pts {
            background: var(--gray); border: 1px solid #2A2A2A; border-radius: 50px;
            padding: 6px 12px; font-size: 12px; font-weight: 600;
            color: var(--yellow); font-family: 'Bebas Neue'; letter-spacing: 1px;
        }

        /* Hero */
        .hero {
            background: linear-gradient(180deg, #1A1400 0%, var(--black) 100%);
            padding: 32px 20px;
            border-bottom: 1px solid #1A1A1A;
            position: relative; overflow: hidden;
        }
        .hero::after {
            content: '01';
            position: absolute; right: -10px; top: 50%; transform: translateY(-50%);
            font-family: 'Bebas Neue', sans-serif; font-size: 120px; 
            color: rgba(255,214,0,0.05); letter-spacing: -5px;
            pointer-events: none;
        }
        .hero-tag { font-size: 11px; letter-spacing: 3px; text-transform: uppercase; color: var(--yellow); margin-bottom: 10px; }
        .hero-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(28px, 7vw, 40px); letter-spacing: 2px; line-height: 1.05;
            margin-bottom: 12px;
        }
        .hero-desc { font-size: 13px; color: var(--gray-text); line-height: 1.6; max-width: 340px; margin-bottom: 20px; }

        /* Overall progress */
        .overall-progress {
            background: var(--gray); border: 1px solid #2A2A2A; border-radius: 14px;
            padding: 16px; display: flex; align-items: center; gap: 16px;
        }
        .op-circle {
            width: 56px; height: 56px; flex-shrink: 0;
            position: relative;
        }
        .op-circle svg { transform: rotate(-90deg); }
        .op-text {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Bebas Neue', sans-serif; font-size: 16px; color: var(--yellow);
        }
        .op-info p { font-size: 14px; font-weight: 600; margin-bottom: 2px; }
        .op-info small { font-size: 12px; color: var(--gray-text); }

        /* TIMELINE */
        .timeline { padding: 28px 20px 100px; }
        .tl-header { margin-bottom: 20px; }
        .tl-header h2 { font-family: 'Bebas Neue', sans-serif; font-size: 22px; letter-spacing: 2px; }
        .tl-header p { font-size: 12px; color: var(--gray-text); margin-top: 2px; }

        .tl-item {
            display: flex; gap: 16px;
            margin-bottom: 4px;
            position: relative;
        }

        /* Left column: number + line */
        .tl-left { 
            display: flex; flex-direction: column; align-items: center;
            width: 44px; flex-shrink: 0;
        }
        .tl-num {
            width: 44px; height: 44px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Bebas Neue', sans-serif; font-size: 18px; letter-spacing: 1px;
            flex-shrink: 0; position: relative; z-index: 1;
            transition: all 0.3s;
        }
        .tl-num.done { background: var(--yellow); color: var(--black); }
        .tl-num.active { background: #1A1600; border: 2px solid var(--yellow); color: var(--yellow); }
        .tl-num.locked { background: #1A1A1A; border: 1px solid #2A2A2A; color: #444; }
        .tl-line {
            width: 2px; flex: 1; background: #2A2A2A; margin: 4px 0;
            min-height: 24px;
        }
        .tl-line.done { background: var(--yellow); }

        /* Right column: card */
        .tl-card {
            flex: 1; background: var(--gray); border: 1px solid #2A2A2A;
            border-radius: 16px; padding: 18px; margin-bottom: 20px;
            text-decoration: none; color: inherit;
            transition: border-color 0.2s, transform 0.2s;
            display: block;
            -webkit-tap-highlight-color: transparent;
        }
        .tl-card.clickable:hover {
            border-color: rgba(255,214,0,0.4);
            transform: translateX(3px);
        }
        .tl-card.locked-card { opacity: 0.5; cursor: not-allowed; }

        .tl-card-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        .tl-section-label { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: var(--yellow); }
        .tl-badge {
            font-size: 10px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase;
            padding: 3px 8px; border-radius: 50px;
        }
        .badge-done { background: rgba(50,200,80,0.15); color: #50C850; }
        .badge-go { background: rgba(255,214,0,0.15); color: var(--yellow); }
        .badge-lock { background: #2A2A2A; color: #555; }

        .tl-card h3 { font-family: 'Bebas Neue', sans-serif; font-size: 18px; letter-spacing: 1px; margin-bottom: 6px; }
        .tl-card p { font-size: 12px; color: var(--gray-text); line-height: 1.5; margin-bottom: 12px; }

        .tl-card-footer { display: flex; justify-content: space-between; align-items: center; }
        .tl-meta { display: flex; gap: 12px; }
        .tl-meta-item { display: flex; align-items: center; gap: 4px; font-size: 11px; color: var(--gray-text); }
        .tl-cta {
            display: flex; align-items: center; gap: 6px;
            font-size: 12px; font-weight: 600;
            color: var(--yellow);
        }

        /* Audio bar for intro */
        .audio-hint {
            background: #1A1600; border: 1px solid rgba(255,214,0,0.2);
            border-radius: 10px; padding: 10px 14px;
            display: flex; align-items: center; gap: 10px;
            margin-top: 10px; font-size: 12px; color: var(--gray-text);
        }
        .audio-hint svg { color: var(--yellow); flex-shrink: 0; }

        /* Animations */
        .tl-item { animation: slide-in 0.4s ease both; }
        .tl-item:nth-child(1) { animation-delay: 0.1s; }
        .tl-item:nth-child(2) { animation-delay: 0.2s; }
        .tl-item:nth-child(3) { animation-delay: 0.3s; }
        .tl-item:nth-child(4) { animation-delay: 0.4s; }
        .tl-item:nth-child(5) { animation-delay: 0.5s; }
        .tl-item:nth-child(6) { animation-delay: 0.6s; }
        @keyframes slide-in {
            from { opacity: 0; transform: translateX(-16px); }
            to { opacity: 1; transform: translateX(0); }
        }
    </style>
</head>
<body>

<nav class="topnav">
    <a href="../modulo.php" class="back-btn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="15 18 9 12 15 6"/>
        </svg>
    </a>
    <div class="nav-info">
        <div class="nav-module">Módulo 01</div>
        <div class="nav-title">SAFE TOGETHER</div>
    </div>
    <div class="nav-pts">⚡ <?= number_format($points) ?>pts</div>
</nav>

<div class="hero">
    <div class="hero-tag">Safety Academy · Módulo 1</div>
    <h1 class="hero-title">La Seguridad Como<br>Nuestra Forma de Vida</h1>
    <p class="hero-desc">Aprende sobre cultura de seguridad, la Curva de Bradley, el Modelo del Queso Suizo y la responsabilidad colectiva.</p>

    <?php
    $pct = $total > 0 ? round(($completed_count / $total) * 100) : 0;
    $circumference = 2 * M_PI * 22;
    $offset = $circumference - ($pct / 100 * $circumference);
    ?>
    <div class="overall-progress">
        <div class="op-circle">
            <svg width="56" height="56" viewBox="0 0 56 56">
                <circle cx="28" cy="28" r="22" stroke="#2A2A2A" stroke-width="4" fill="none"/>
                <circle cx="28" cy="28" r="22" stroke="#FFD600" stroke-width="4" fill="none"
                    stroke-dasharray="<?= $circumference ?>"
                    stroke-dashoffset="<?= $offset ?>"
                    stroke-linecap="round"/>
            </svg>
            <div class="op-text"><?= $pct ?>%</div>
        </div>
        <div class="op-info">
            <p><?= $completed_count ?> de <?= $total ?> secciones completadas</p>
            <small>Completa todas para ganar el máximo de puntos</small>
        </div>
    </div>
</div>

<!-- Timeline -->
<div class="timeline">
    <div class="tl-header">
        <h2>Ruta de Aprendizaje</h2>
        <p>Completa cada sección en orden para avanzar</p>
    </div>

    <?php 
    $section_icons = ['🛡️','🤝','⭐','📈','🧀','🎯'];
    foreach ($sections as $i => $section): 
        $is_done = (bool)$section['completed'];
        $prev_done = $i === 0 || (bool)$sections[$i-1]['completed'];
        $is_available = $is_done || $prev_done;
        $is_last = $i === count($sections) - 1;
        $pts_earned = (int)$section['points_earned'];
    ?>
    <div class="tl-item">
        <div class="tl-left">
            <div class="tl-num <?= $is_done ? 'done' : ($is_available ? 'active' : 'locked') ?>">
                <?php if ($is_done): ?>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <?php else: ?>
                <?= $i + 1 ?>
                <?php endif; ?>
            </div>
            <?php if (!$is_last): ?>
            <div class="tl-line <?= $is_done ? 'done' : '' ?>"></div>
            <?php endif; ?>
        </div>

        <?php if ($is_available): ?>
        <a href="sections/section.php?id=<?= $section['id'] ?>" class="tl-card clickable">
        <?php else: ?>
        <div class="tl-card locked-card">
        <?php endif; ?>
            <div class="tl-card-top">
                <span class="tl-section-label">Sección <?= $i + 1 ?> <?= $section_icons[$i] ?></span>
                <span class="tl-badge <?= $is_done ? 'badge-done' : ($is_available ? 'badge-go' : 'badge-lock') ?>">
                    <?= $is_done ? '✓ Completado' : ($is_available ? '→ Iniciar' : '🔒 Bloqueado') ?>
                </span>
            </div>
            <h3><?= htmlspecialchars($section['title']) ?></h3>
            <p><?= htmlspecialchars(substr($section['content'], 0, 120)) ?>...</p>
            <div class="tl-card-footer">
                <div class="tl-meta">
                    <span class="tl-meta-item">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                        ~5 min
                    </span>
                    <span class="tl-meta-item">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                        <?= $section['question_count'] ?> preguntas
                    </span>
                </div>
                <?php if ($is_done): ?>
                <span class="tl-cta" style="color:#50C850">+<?= $pts_earned ?> pts ganados</span>
                <?php elseif ($is_available): ?>
                <span class="tl-cta">
                    Comenzar
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                    </svg>
                </span>
                <?php endif; ?>
            </div>
        <?php if ($is_available): ?>
        </a>
        <?php else: ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

</body>
</html>
