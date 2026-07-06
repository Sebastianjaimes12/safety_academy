<?php
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/config/database.php';
requireAdmin();

// Stats
$total_users_r = mysqli_query($pa_conn, "SELECT COUNT(*) as c FROM user_register WHERE admin = 0");
$total_users = mysqli_fetch_assoc($total_users_r)['c'] ?? 0;

$total_q_r = mysqli_query($sa_conn, "SELECT COUNT(*) as c FROM sa_questions WHERE is_active = 1");
$total_q = mysqli_fetch_assoc($total_q_r)['c'] ?? 0;

$completions_r = mysqli_query($sa_conn, "SELECT COUNT(*) as c FROM sa_user_progress WHERE completed = 1");
$completions = mysqli_fetch_assoc($completions_r)['c'] ?? 0;

$pts_r = mysqli_query($pa_conn, "SELECT SUM(points) as s FROM user_register WHERE admin = 0");
$total_pts = mysqli_fetch_assoc($pts_r)['s'] ?? 0;

// Top users
$top_users = [];
$res = mysqli_query($pa_conn, "SELECT name, points FROM user_register WHERE admin = 0 ORDER BY points DESC LIMIT 8");
while ($r = mysqli_fetch_assoc($res)) $top_users[] = $r;

// Recent activity
$recent = [];
$res2 = mysqli_query($sa_conn, "SELECT p.user_id, p.section_id, p.points_earned, p.completed_at, s.title as sec_title 
    FROM sa_user_progress p JOIN sa_sections s ON s.id = p.section_id 
    WHERE p.completed = 1 ORDER BY p.completed_at DESC LIMIT 10");
while ($r = mysqli_fetch_assoc($res2)) $recent[] = $r;

// Questions per section
$sec_stats = [];
$res3 = mysqli_query($sa_conn, "SELECT s.title, COUNT(q.id) as q_count, SUM(q.points) as total_pts 
    FROM sa_sections s LEFT JOIN sa_questions q ON q.section_id = s.id AND q.is_active = 1
    WHERE s.module_id = 1 GROUP BY s.id ORDER BY s.order_num");
while ($r = mysqli_fetch_assoc($res3)) $sec_stats[] = $r;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Safety Academy Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <?php include 'header.php'; ?>
</head>
<body>

<!-- TOPBAR (mobile) -->
<div class="admin-topbar">
    <button class="menu-btn" onclick="toggleSidebar()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </button>
    <div class="topbar-title">Safety <span>Admin</span></div>
    <a href="/admin/questions.php" style="background:var(--yellow);color:var(--black);border:none;border-radius:10px;padding:8px 14px;font-family:'Bebas Neue',sans-serif;font-size:14px;letter-spacing:1px;text-decoration:none;cursor:pointer">
        + Pregunta
    </a>
</div>

<main class="admin-content">
<div class="content-inner">

    <style>
        /* Dashboard specific */
        .page-title { font-family: 'Bebas Neue', sans-serif; font-size: 32px; letter-spacing: 2px; margin-bottom: 4px; }
        .page-sub { font-size: 13px; color: var(--gray-text); margin-bottom: 28px; }

        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px; }
        @media (min-width: 640px) { .stats-grid { grid-template-columns: repeat(4, 1fr); } }

        .stat-card {
            background: var(--gray); border: 1px solid #2A2A2A; border-radius: 16px;
            padding: 18px 16px;
        }
        .sc-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; }
        .sc-icon.yellow { background: rgba(255,214,0,0.15); }
        .sc-icon.green { background: rgba(80,200,120,0.15); }
        .sc-icon.blue { background: rgba(100,150,255,0.15); }
        .sc-icon.orange { background: rgba(255,150,50,0.15); }
        .sc-icon svg { width: 18px; height: 18px; }
        .sc-val { font-family: 'Bebas Neue', sans-serif; font-size: 32px; letter-spacing: 1px; line-height: 1; }
        .sc-label { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--gray-text); margin-top: 4px; }

        .grid-2 { display: grid; grid-template-columns: 1fr; gap: 16px; margin-bottom: 24px; }
        @media (min-width: 768px) { .grid-2 { grid-template-columns: 1fr 1fr; } }

        .panel {
            background: var(--gray); border: 1px solid #2A2A2A; border-radius: 16px; overflow: hidden;
        }
        .panel-header {
            padding: 16px 20px; border-bottom: 1px solid #2A2A2A;
            display: flex; justify-content: space-between; align-items: center;
        }
        .panel-header h2 { font-family: 'Bebas Neue', sans-serif; font-size: 18px; letter-spacing: 1px; }
        .panel-link { font-size: 12px; color: var(--yellow); text-decoration: none; font-weight: 600; }

        /* Top users list */
        .user-item {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 20px; border-bottom: 1px solid #1A1A1A;
        }
        .user-item:last-child { border-bottom: none; }
        .user-rank { font-family: 'Bebas Neue', sans-serif; font-size: 14px; color: var(--gray-text); width: 20px; }
        .user-avatar-sm {
            width: 32px; height: 32px; border-radius: 50%; background: var(--yellow);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Bebas Neue', sans-serif; font-size: 14px; color: var(--black); flex-shrink: 0;
        }
        .user-name-sm { flex: 1; font-size: 13px; font-weight: 500; }
        .user-pts { font-family: 'Bebas Neue', sans-serif; font-size: 16px; color: var(--yellow); letter-spacing: 1px; }

        /* Section stats */
        .sec-item {
            padding: 14px 20px; border-bottom: 1px solid #1A1A1A;
            display: flex; align-items: center; gap: 12px;
        }
        .sec-item:last-child { border-bottom: none; }
        .sec-num { width: 28px; height: 28px; border-radius: 8px; background: rgba(255,214,0,0.1); display: flex; align-items: center; justify-content: center; font-family: 'Bebas Neue'; font-size: 14px; color: var(--yellow); flex-shrink: 0; }
        .sec-title { flex: 1; font-size: 12px; line-height: 1.3; }
        .sec-meta { text-align: right; }
        .sec-meta .q { font-family: 'Bebas Neue', sans-serif; font-size: 16px; color: var(--white); }
        .sec-meta .p { font-size: 10px; color: var(--gray-text); }

        /* Activity */
        .activity-item {
            display: flex; align-items: flex-start; gap: 12px;
            padding: 12px 20px; border-bottom: 1px solid #1A1A1A;
        }
        .activity-item:last-child { border-bottom: none; }
        .act-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--yellow); flex-shrink: 0; margin-top: 6px; }
        .act-text { flex: 1; font-size: 12px; color: var(--gray-text); line-height: 1.4; }
        .act-text strong { color: var(--white); }
        .act-pts { font-size: 12px; font-weight: 600; color: var(--yellow); white-space: nowrap; }

        /* Quick actions */
        .quick-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
        .qa-btn {
            display: flex; align-items: center; gap: 8px;
            padding: 10px 16px; border-radius: 10px; text-decoration: none;
            font-size: 13px; font-weight: 600; transition: transform 0.2s;
            border: 1px solid;
        }
        .qa-btn:hover { transform: translateY(-2px); }
        .qa-btn.primary { background: var(--yellow); color: var(--black); border-color: var(--yellow); }
        .qa-btn.secondary { background: var(--gray); color: var(--white); border-color: #2A2A2A; }
        .qa-btn svg { width: 15px; height: 15px; }
    </style>

    <div class="page-title">Dashboard</div>
    <p class="page-sub">Bienvenido de vuelta, <?= htmlspecialchars(explode(' ', getUserName())[0]) ?>. Aquí tienes un resumen del estado del programa.</p>

    <!-- Quick actions -->
    <div class="quick-actions">
        <a href="/admin/questions.php?action=new" class="qa-btn primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nueva Pregunta
        </a>
        <a href="/admin/questions.php" class="qa-btn secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
            Ver Preguntas
        </a>
        <a href="/admin/users.php" class="qa-btn secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            Ver Usuarios
        </a>
        <a href="/admin/reports.php" class="qa-btn secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            Reportes
        </a>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="sc-icon yellow">
                <svg viewBox="0 0 24 24" fill="none" stroke="#FFD600" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            </div>
            <div class="sc-val"><?= $total_users ?></div>
            <div class="sc-label">Usuarios activos</div>
        </div>
        <div class="stat-card">
            <div class="sc-icon green">
                <svg viewBox="0 0 24 24" fill="none" stroke="#50C878" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
            </div>
            <div class="sc-val"><?= $total_q ?></div>
            <div class="sc-label">Preguntas activas</div>
        </div>
        <div class="stat-card">
            <div class="sc-icon blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="#6496FF" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            </div>
            <div class="sc-val"><?= $completions ?></div>
            <div class="sc-label">Secciones completadas</div>
        </div>
        <div class="stat-card">
            <div class="sc-icon orange">
                <svg viewBox="0 0 24 24" fill="none" stroke="#FF9632" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            </div>
            <div class="sc-val"><?= number_format($total_pts) ?></div>
            <div class="sc-label">Puntos totales ganados</div>
        </div>
    </div>

    <!-- 2 col grid -->
    <div class="grid-2">
        <!-- Top users -->
        <div class="panel">
            <div class="panel-header">
                <h2>🏆 Top Usuarios</h2>
                <a href="/admin/users.php" class="panel-link">Ver todos →</a>
            </div>
            <?php if (empty($top_users)): ?>
            <div style="padding:20px;text-align:center;color:var(--gray-text);font-size:13px">Sin actividad aún</div>
            <?php else: ?>
            <?php foreach ($top_users as $i => $u): ?>
            <div class="user-item">
                <div class="user-rank"><?= $i + 1 ?></div>
                <div class="user-avatar-sm" style="<?= $i === 0 ? 'box-shadow:0 0 0 2px #FFD600' : '' ?>">
                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                </div>
                <div class="user-name-sm"><?= htmlspecialchars(explode(' ', $u['name'])[0]) ?></div>
                <div class="user-pts"><?= number_format($u['points']) ?> pts</div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Section stats -->
        <div class="panel">
            <div class="panel-header">
                <h2>📊 Módulo 1 — Secciones</h2>
                <a href="/admin/questions.php" class="panel-link">Gestionar →</a>
            </div>
            <?php foreach ($sec_stats as $i => $s): ?>
            <div class="sec-item">
                <div class="sec-num"><?= $i + 1 ?></div>
                <div class="sec-title"><?= htmlspecialchars($s['title']) ?></div>
                <div class="sec-meta">
                    <div class="q"><?= $s['q_count'] ?> <span style="font-size:12px;color:var(--gray-text)">pregs</span></div>
                    <div class="p"><?= $s['total_pts'] ?? 0 ?> pts posibles</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="panel">
        <div class="panel-header">
            <h2>⚡ Actividad Reciente</h2>
        </div>
        <?php if (empty($recent)): ?>
        <div style="padding:20px;text-align:center;color:var(--gray-text);font-size:13px">Sin actividad reciente</div>
        <?php else: ?>
        <?php foreach ($recent as $a): ?>
        <div class="activity-item">
            <div class="act-dot"></div>
            <div class="act-text">
                <strong>Usuario #<?= $a['user_id'] ?></strong> completó 
                "<?= htmlspecialchars(substr($a['sec_title'], 0, 40)) ?>..."
                <br><span style="font-size:11px;color:#555"><?= date('d/m/Y H:i', strtotime($a['completed_at'])) ?></span>
            </div>
            <div class="act-pts">+<?= $a['points_earned'] ?> pts</div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>
</main>
</body>
</html>
