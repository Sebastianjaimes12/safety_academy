<?php
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/config/database.php';
requireAdmin();

// Stats per section
$sec_report = [];
$res = mysqli_query($sa_conn, "SELECT s.title, s.order_num,
    COUNT(DISTINCT p.user_id) as participants,
    SUM(CASE WHEN p.completed = 1 THEN 1 ELSE 0 END) as completions,
    AVG(p.score) as avg_score,
    MAX(p.points_earned) as max_pts,
    AVG(p.points_earned) as avg_pts
    FROM sa_sections s
    LEFT JOIN sa_user_progress p ON p.section_id = s.id
    WHERE s.module_id = 1 GROUP BY s.id ORDER BY s.order_num");
while ($r = mysqli_fetch_assoc($res)) $sec_report[] = $r;

// Question difficulty (most wrong answers)
$hard_qs = [];
$res2 = mysqli_query($sa_conn, "SELECT q.question_text, s.order_num as sec_order, q.points,
    q.section_id, q.id 
    FROM sa_questions q JOIN sa_sections s ON s.id = q.section_id 
    WHERE q.is_active = 1 AND q.module_id = 1 ORDER BY q.points DESC LIMIT 10");
while ($r = mysqli_fetch_assoc($res2)) $hard_qs[] = $r;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes — Safety Academy Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <?php include 'header.php'; ?>
</head>
<body>

<div class="admin-topbar">
    <button class="menu-btn" onclick="toggleSidebar()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </button>
    <div class="topbar-title">Reportes <span>de Desempeño</span></div>
</div>

<main class="admin-content">
<div class="content-inner">

    <style>
        .page-title { font-family: 'Bebas Neue', sans-serif; font-size: 32px; letter-spacing: 2px; margin-bottom: 4px; }
        .page-sub { font-size: 13px; color: var(--gray-text); margin-bottom: 24px; }
        .panel { background: var(--gray); border: 1px solid #2A2A2A; border-radius: 16px; overflow: hidden; margin-bottom: 20px; }
        .panel-header { padding: 16px 20px; border-bottom: 1px solid #2A2A2A; }
        .panel-header h2 { font-family: 'Bebas Neue', sans-serif; font-size: 20px; letter-spacing: 1px; }
        .panel-header p { font-size: 12px; color: var(--gray-text); margin-top: 2px; }

        .sec-report-item {
            padding: 16px 20px; border-bottom: 1px solid #1A1A1A;
            display: flex; flex-direction: column; gap: 10px;
        }
        .sec-report-item:last-child { border-bottom: none; }
        .sri-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .sri-name { display: flex; align-items: center; gap: 10px; }
        .sri-num {
            width: 30px; height: 30px; border-radius: 8px;
            background: rgba(255,214,0,0.1); display: flex; align-items: center; justify-content: center;
            font-family: 'Bebas Neue'; font-size: 14px; color: var(--yellow); flex-shrink: 0;
        }
        .sri-title { font-size: 13px; font-weight: 500; }
        .sri-stats { display: flex; gap: 16px; }
        .sri-stat { text-align: right; }
        .sri-stat .v { font-family: 'Bebas Neue', sans-serif; font-size: 20px; letter-spacing: 1px; }
        .sri-stat .l { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: var(--gray-text); }
        .comp-bar { height: 3px; background: #2A2A2A; border-radius: 2px; overflow: hidden; }
        .comp-fill { height: 100%; background: var(--yellow); border-radius: 2px; }

        .q-report-item {
            padding: 14px 20px; border-bottom: 1px solid #1A1A1A;
            display: flex; align-items: flex-start; gap: 12px;
        }
        .q-report-item:last-child { border-bottom: none; }
        .q-pts-badge {
            background: rgba(255,214,0,0.1); border: 1px solid rgba(255,214,0,0.2);
            border-radius: 8px; padding: 6px 10px; text-align: center; flex-shrink: 0;
        }
        .q-pts-badge .v { font-family: 'Bebas Neue', sans-serif; font-size: 18px; color: var(--yellow); }
        .q-pts-badge .l { font-size: 9px; text-transform: uppercase; color: var(--gray-text); }
        .q-report-text { font-size: 13px; line-height: 1.4; }
        .q-report-meta { font-size: 11px; color: var(--gray-text); margin-top: 4px; }
    </style>

    <div class="page-title">Reportes</div>
    <p class="page-sub">Análisis del desempeño por sección y preguntas del Módulo 1.</p>

    <!-- Section performance -->
    <div class="panel">
        <div class="panel-header">
            <h2>📊 Desempeño por Sección</h2>
            <p>Módulo 1 — La Seguridad Como Nuestra Forma de Vida</p>
        </div>
        <?php foreach ($sec_report as $s): ?>
        <?php 
        $pct = $s['participants'] > 0 ? round(($s['completions'] / $s['participants']) * 100) : 0;
        ?>
        <div class="sec-report-item">
            <div class="sri-top">
                <div class="sri-name">
                    <div class="sri-num"><?= $s['order_num'] ?></div>
                    <div class="sri-title"><?= htmlspecialchars($s['title']) ?></div>
                </div>
                <div class="sri-stats">
                    <div class="sri-stat">
                        <div class="v" style="color:var(--yellow)"><?= (int)$s['participants'] ?></div>
                        <div class="l">Participantes</div>
                    </div>
                    <div class="sri-stat">
                        <div class="v" style="color:#50C878"><?= (int)$s['completions'] ?></div>
                        <div class="l">Completaron</div>
                    </div>
                    <div class="sri-stat">
                        <div class="v"><?= round($s['avg_pts'] ?? 0) ?></div>
                        <div class="l">Pts prom.</div>
                    </div>
                </div>
            </div>
            <div>
                <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--gray-text);margin-bottom:4px">
                    <span>Tasa de completación</span>
                    <span><?= $pct ?>%</span>
                </div>
                <div class="comp-bar">
                    <div class="comp-fill" style="width:<?= $pct ?>%"></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Question value list -->
    <div class="panel">
        <div class="panel-header">
            <h2>🎯 Preguntas por Valor de Puntos</h2>
            <p>Las preguntas con mayor peso en el puntaje total</p>
        </div>
        <?php foreach ($hard_qs as $q): ?>
        <div class="q-report-item">
            <div class="q-pts-badge">
                <div class="v"><?= $q['points'] ?></div>
                <div class="l">pts</div>
            </div>
            <div>
                <div class="q-report-text"><?= htmlspecialchars(substr($q['question_text'], 0, 100)) ?>...</div>
                <div class="q-report-meta">Sección <?= $q['sec_order'] ?> · <a href="/admin/questions.php?edit=<?= $q['id'] ?>" style="color:var(--yellow);text-decoration:none">Editar →</a></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

</div>
</main>
</body>
</html>
