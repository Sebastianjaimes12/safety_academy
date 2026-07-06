<?php
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/config/database.php';
requireAdmin();

// Get all users with their safety academy progress
$users = [];
$res = mysqli_query($pa_conn, "SELECT id, name, email, mobile_number, points, created_at FROM user_register WHERE admin = 0 ORDER BY points DESC");
while ($r = mysqli_fetch_assoc($res)) $users[] = $r;

// Get completed sections count per user
$completions = [];
$res2 = mysqli_query($sa_conn, "SELECT user_id, COUNT(*) as c FROM sa_user_progress WHERE completed = 1 GROUP BY user_id");
while ($r = mysqli_fetch_assoc($res2)) $completions[$r['user_id']] = $r['c'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios — Safety Academy Admin</title>
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
    <div class="topbar-title">Usuarios <span>Registrados</span></div>
</div>

<main class="admin-content">
<div class="content-inner">

    <style>
        .page-title { font-family: 'Bebas Neue', sans-serif; font-size: 32px; letter-spacing: 2px; margin-bottom: 4px; }
        .page-sub { font-size: 13px; color: var(--gray-text); margin-bottom: 24px; }

        .users-table { width: 100%; border-collapse: collapse; }
        .users-table th {
            text-align: left; font-size: 10px; letter-spacing: 2px; text-transform: uppercase;
            color: var(--gray-text); padding: 10px 16px; border-bottom: 1px solid #2A2A2A;
        }
        .users-table td { padding: 14px 16px; border-bottom: 1px solid #1A1A1A; font-size: 13px; }
        .users-table tr:hover td { background: rgba(255,255,255,0.01); }
        .users-table tr:last-child td { border-bottom: none; }

        .user-cell { display: flex; align-items: center; gap: 10px; }
        .u-avatar {
            width: 34px; height: 34px; border-radius: 50%; background: var(--yellow);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Bebas Neue', sans-serif; font-size: 16px; color: var(--black); flex-shrink: 0;
        }
        .u-name { font-size: 13px; font-weight: 600; }
        .u-email { font-size: 11px; color: var(--gray-text); }

        .pts-cell { font-family: 'Bebas Neue', sans-serif; font-size: 20px; letter-spacing: 1px; color: var(--yellow); }
        .progress-pill {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(255,214,0,0.1); border: 1px solid rgba(255,214,0,0.2);
            border-radius: 50px; padding: 4px 10px; font-size: 12px; color: var(--yellow);
        }
        .rank-badge {
            width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center;
            justify-content: center; font-family: 'Bebas Neue', sans-serif; font-size: 13px;
        }
        .rank-1 { background: #FFD600; color: #0A0A0A; }
        .rank-2 { background: #C0C0C0; color: #0A0A0A; }
        .rank-3 { background: #CD7F32; color: #fff; }
        .rank-other { background: #1A1A1A; color: #888; }

        .table-wrap { background: var(--gray); border: 1px solid #2A2A2A; border-radius: 16px; overflow: hidden; overflow-x: auto; }

        .summary-bar {
            display: flex; gap: 16px; margin-bottom: 20px; flex-wrap: wrap;
        }
        .sb-item {
            background: var(--gray); border: 1px solid #2A2A2A; border-radius: 12px;
            padding: 14px 18px; flex: 1; min-width: 120px;
        }
        .sb-val { font-family: 'Bebas Neue', sans-serif; font-size: 28px; letter-spacing: 1px; }
        .sb-label { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--gray-text); margin-top: 2px; }
    </style>

    <div class="page-title">Usuarios</div>
    <p class="page-sub">Participantes registrados via People Academy con su progreso en Safety Academy.</p>

    <div class="summary-bar">
        <div class="sb-item">
            <div class="sb-val"><?= count($users) ?></div>
            <div class="sb-label">Total participantes</div>
        </div>
        <div class="sb-item">
            <div class="sb-val" style="color:var(--yellow)"><?= array_sum($completions) ?></div>
            <div class="sb-label">Secciones completadas</div>
        </div>
        <div class="sb-item">
            <div class="sb-val" style="color:#50C878"><?= count(array_filter($completions, function($c) { return $c >= 6; })) ?></div>
            <div class="sb-label">Módulo 1 completo</div>
        </div>
    </div>

    <div class="table-wrap">
        <table class="users-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Usuario</th>
                    <th>Puntos</th>
                    <th>Progreso M1</th>
                    <th>Registro</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $i => $u): ?>
                <tr>
                    <td>
                        <div class="rank-badge rank-<?= $i < 3 ? $i+1 : 'other' ?>">
                            <?= $i + 1 ?>
                        </div>
                    </td>
                    <td>
                        <div class="user-cell">
                            <div class="u-avatar" style="<?= $i === 0 ? 'box-shadow:0 0 0 2px #FFD600' : '' ?>">
                                <?= strtoupper(substr($u['name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="u-name"><?= htmlspecialchars($u['name']) ?></div>
                                <div class="u-email"><?= htmlspecialchars($u['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="pts-cell"><?= number_format($u['points']) ?></td>
                    <td>
                        <?php $c = $completions[$u['id']] ?? 0; ?>
                        <span class="progress-pill">
                            <?= $c ?>/6 secciones
                        </span>
                    </td>
                    <td style="color:var(--gray-text);font-size:12px">
                        <?= date('d/m/Y', strtotime($u['created_at'])) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="5" style="text-align:center;padding:40px;color:var(--gray-text)">
                        Sin usuarios registrados aún
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</main>
</body>
</html>
