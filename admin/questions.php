<?php
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/config/database.php';
requireAdmin();

$msg = '';
$msg_type = '';
$edit_q = null;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $section_id = (int)$_POST['section_id'];
        $module_id = (int)$_POST['module_id'];
        $qtext = sanitize($sa_conn, $_POST['question_text']);
        $opt_a = sanitize($sa_conn, $_POST['option_a']);
        $opt_b = sanitize($sa_conn, $_POST['option_b']);
        $opt_c = sanitize($sa_conn, $_POST['option_c']);
        $opt_d = sanitize($sa_conn, $_POST['option_d']);
        $correct = strtoupper(sanitize($sa_conn, $_POST['correct_option']));
        $explanation = sanitize($sa_conn, $_POST['explanation']);
        $points = (int)$_POST['points'];
        $order_num = (int)($_POST['order_num'] ?? 1);

        if ($id > 0) {
            $sql = "UPDATE sa_questions SET section_id=$section_id, module_id=$module_id, 
                    question_text='$qtext', option_a='$opt_a', option_b='$opt_b', 
                    option_c='$opt_c', option_d='$opt_d', correct_option='$correct',
                    explanation='$explanation', points=$points, order_num=$order_num WHERE id=$id";
            mysqli_query($sa_conn, $sql);
            $msg = "Pregunta actualizada correctamente.";
        } else {
            $sql = "INSERT INTO sa_questions (section_id, module_id, question_text, option_a, option_b, option_c, option_d, correct_option, explanation, points, order_num)
                    VALUES ($section_id, $module_id, '$qtext', '$opt_a', '$opt_b', '$opt_c', '$opt_d', '$correct', '$explanation', $points, $order_num)";
            mysqli_query($sa_conn, $sql);
            $msg = "Pregunta creada correctamente.";
        }
        $msg_type = 'success';
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        mysqli_query($sa_conn, "UPDATE sa_questions SET is_active = 0 WHERE id = $id");
        $msg = "Pregunta eliminada.";
        $msg_type = 'info';
    }

    if ($action === 'toggle') {
        $id = (int)$_POST['id'];
        mysqli_query($sa_conn, "UPDATE sa_questions SET is_active = NOT is_active WHERE id = $id");
        $msg = "Estado actualizado.";
        $msg_type = 'info';
    }
}

// Edit mode
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $r = mysqli_query($sa_conn, "SELECT * FROM sa_questions WHERE id = $id");
    $edit_q = mysqli_fetch_assoc($r);
}

// Get questions with filters
$filter_section = (int)($_GET['section'] ?? 0);
$where = $filter_section ? "WHERE q.section_id = $filter_section" : "WHERE 1=1";
$questions = [];
$res = mysqli_query($sa_conn, "SELECT q.*, s.title as sec_title, s.order_num as sec_order 
    FROM sa_questions q JOIN sa_sections s ON s.id = q.section_id 
    $where ORDER BY q.section_id, q.order_num");
while ($r = mysqli_fetch_assoc($res)) $questions[] = $r;

// Get sections for filter/form
$sections = [];
$res2 = mysqli_query($sa_conn, "SELECT * FROM sa_sections WHERE module_id = 1 ORDER BY order_num");
while ($r = mysqli_fetch_assoc($res2)) $sections[] = $r;

$show_form = isset($_GET['action']) && $_GET['action'] === 'new' || $edit_q;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preguntas Quiz — Safety Academy Admin</title>
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
    <div class="topbar-title">Preguntas <span>Quiz</span></div>
    <a href="?action=new" style="background:var(--yellow);color:var(--black);border:none;border-radius:10px;padding:8px 14px;font-family:'Bebas Neue',sans-serif;font-size:14px;letter-spacing:1px;text-decoration:none">
        + Nueva
    </a>
</div>

<main class="admin-content">
<div class="content-inner">

    <style>
        .page-title { font-family: 'Bebas Neue', sans-serif; font-size: 32px; letter-spacing: 2px; margin-bottom: 4px; }
        .page-sub { font-size: 13px; color: var(--gray-text); margin-bottom: 20px; }

        .alert {
            border-radius: 10px; padding: 12px 16px; font-size: 13px; margin-bottom: 16px;
            display: flex; align-items: center; gap: 10px;
        }
        .alert-success { background: rgba(80,200,120,0.1); border: 1px solid rgba(80,200,120,0.3); color: #7AE09A; }
        .alert-info { background: rgba(100,150,255,0.1); border: 1px solid rgba(100,150,255,0.3); color: #96B4FF; }

        /* Form */
        .form-panel {
            background: var(--gray); border: 1px solid #2A2A2A; border-radius: 16px;
            padding: 24px; margin-bottom: 24px;
        }
        .form-panel h2 { font-family: 'Bebas Neue', sans-serif; font-size: 22px; letter-spacing: 1px; margin-bottom: 20px; }
        .form-grid { display: grid; grid-template-columns: 1fr; gap: 16px; }
        @media (min-width: 640px) { .form-grid-2 { grid-template-columns: 1fr 1fr; } }

        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-label { font-size: 11px; letter-spacing: 2px; text-transform: uppercase; color: var(--gray-text); font-weight: 600; }
        .form-input, .form-select, .form-textarea {
            background: #111; border: 1px solid #2A2A2A; border-radius: 10px;
            padding: 12px 14px; color: var(--white); font-family: 'DM Sans', sans-serif;
            font-size: 14px; outline: none; transition: border-color 0.2s;
            width: 100%;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus { border-color: var(--yellow); }
        .form-textarea { min-height: 80px; resize: vertical; }
        .form-select option { background: #111; }

        /* Correct option selector */
        .option-selector { display: flex; gap: 8px; }
        .opt-sel-btn {
            flex: 1; padding: 10px; border-radius: 10px; text-align: center;
            font-family: 'Bebas Neue', sans-serif; font-size: 16px; letter-spacing: 1px;
            cursor: pointer; border: 2px solid #2A2A2A; background: #111; color: var(--gray-text);
            transition: all 0.2s;
        }
        .opt-sel-btn.selected { border-color: var(--yellow); background: rgba(255,214,0,0.1); color: var(--yellow); }
        .opt-sel-btn:hover { border-color: #444; color: var(--white); }

        .btn-save {
            background: var(--yellow); color: var(--black); border: none;
            border-radius: 12px; padding: 14px 28px;
            font-family: 'Bebas Neue', sans-serif; font-size: 18px; letter-spacing: 2px;
            cursor: pointer; transition: transform 0.2s;
        }
        .btn-save:hover { transform: translateY(-2px); }
        .btn-cancel {
            background: transparent; border: 1px solid #2A2A2A; color: var(--gray-text);
            border-radius: 12px; padding: 14px 20px;
            font-family: 'DM Sans', sans-serif; font-size: 14px;
            cursor: pointer; text-decoration: none; display: inline-flex; align-items: center;
        }
        .btn-cancel:hover { border-color: #444; color: var(--white); }

        /* Filters */
        .filter-bar {
            display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; align-items: center;
        }
        .filter-select {
            background: var(--gray); border: 1px solid #2A2A2A; border-radius: 10px;
            padding: 8px 14px; color: var(--white); font-size: 13px; outline: none;
            cursor: pointer;
        }
        .filter-count { margin-left: auto; font-size: 12px; color: var(--gray-text); }

        /* Questions list */
        .q-list { display: flex; flex-direction: column; gap: 10px; }
        .q-card {
            background: var(--gray); border: 1px solid #2A2A2A; border-radius: 14px;
            padding: 18px; transition: border-color 0.2s;
        }
        .q-card:hover { border-color: #3A3A3A; }
        .q-card.inactive { opacity: 0.5; }
        .q-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
        .q-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .q-sec-badge {
            font-size: 10px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase;
            background: rgba(255,214,0,0.1); border: 1px solid rgba(255,214,0,0.2);
            color: var(--yellow); border-radius: 50px; padding: 3px 10px;
        }
        .q-pts-badge {
            font-size: 11px; background: var(--gray-2); border-radius: 50px; padding: 3px 10px;
            color: var(--gray-text);
        }
        .q-pts-badge span { color: var(--yellow); font-family: 'Bebas Neue'; font-size: 14px; }
        .q-actions { display: flex; gap: 6px; flex-shrink: 0; }
        .q-action-btn {
            width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center;
            justify-content: center; cursor: pointer; border: 1px solid #2A2A2A; background: #111;
            color: var(--gray-text); text-decoration: none; transition: all 0.2s;
        }
        .q-action-btn:hover { border-color: var(--yellow); color: var(--yellow); }
        .q-action-btn.delete:hover { border-color: var(--red, #FF4D4D); color: #FF4D4D; }
        .q-action-btn svg { width: 15px; height: 15px; }

        .q-text { font-size: 14px; font-weight: 500; margin-bottom: 10px; line-height: 1.4; }
        .q-options { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
        .q-opt {
            font-size: 12px; padding: 8px 10px; border-radius: 8px; background: #111; border: 1px solid #2A2A2A;
            display: flex; gap: 8px; align-items: flex-start;
        }
        .q-opt.correct-opt { border-color: rgba(80,200,120,0.4); background: rgba(80,200,120,0.05); }
        .q-opt-letter { font-family: 'Bebas Neue'; font-size: 14px; flex-shrink: 0; }
        .q-opt.correct-opt .q-opt-letter { color: #50C878; }

        /* Empty state */
        .empty-state { text-align: center; padding: 60px 20px; color: var(--gray-text); }
        .empty-state svg { width: 48px; height: 48px; margin-bottom: 16px; opacity: 0.3; }
        .empty-state h3 { font-family: 'Bebas Neue', sans-serif; font-size: 24px; letter-spacing: 1px; margin-bottom: 8px; color: var(--white); }
    </style>

    <div class="page-title">Preguntas Quiz</div>
    <p class="page-sub">Gestiona las preguntas del módulo 1 — crea, edita, ajusta puntos y activa/desactiva.</p>

    <?php if ($msg): ?>
    <div class="alert alert-<?= $msg_type ?>">
        <?= $msg_type === 'success' ? '✓' : 'ℹ' ?> <?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <?php if ($show_form): ?>
    <div class="form-panel">
        <h2><?= $edit_q ? 'EDITAR PREGUNTA' : 'NUEVA PREGUNTA' ?></h2>
        <form method="POST">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= $edit_q['id'] ?? 0 ?>">
            <input type="hidden" name="module_id" value="1">
            <input type="hidden" name="correct_option" id="correctInput" value="<?= $edit_q['correct_option'] ?? 'A' ?>">

            <div class="form-grid">
                <!-- Section & Points row -->
                <div class="form-grid form-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div class="form-group">
                        <label class="form-label">Sección</label>
                        <select name="section_id" class="form-select" required>
                            <?php foreach ($sections as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= ($edit_q['section_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                                <?= $s['order_num'] ?>. <?= htmlspecialchars(substr($s['title'], 0, 40)) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Puntos</label>
                        <input type="number" name="points" class="form-input" value="<?= $edit_q['points'] ?? 10 ?>" min="5" max="100" step="5" required>
                    </div>
                </div>

                <!-- Question text -->
                <div class="form-group">
                    <label class="form-label">Pregunta</label>
                    <textarea name="question_text" class="form-textarea" required><?= htmlspecialchars($edit_q['question_text'] ?? '') ?></textarea>
                </div>

                <!-- Options -->
                <?php foreach (['a','b','c','d'] as $opt): ?>
                <div class="form-group">
                    <label class="form-label">Opción <?= strtoupper($opt) ?></label>
                    <input type="text" name="option_<?= $opt ?>" class="form-input" 
                           value="<?= htmlspecialchars($edit_q['option_'.$opt] ?? '') ?>" required>
                </div>
                <?php endforeach; ?>

                <!-- Correct option -->
                <div class="form-group">
                    <label class="form-label">Respuesta Correcta</label>
                    <div class="option-selector">
                        <?php foreach (['A','B','C','D'] as $opt): ?>
                        <button type="button" class="opt-sel-btn <?= ($edit_q['correct_option'] ?? 'A') === $opt ? 'selected' : '' ?>" 
                                onclick="setCorrect('<?= $opt ?>')">
                            <?= $opt ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Explanation -->
                <div class="form-group">
                    <label class="form-label">Explicación (después de responder)</label>
                    <textarea name="explanation" class="form-textarea"><?= htmlspecialchars($edit_q['explanation'] ?? '') ?></textarea>
                </div>

                <!-- Order -->
                <div class="form-group">
                    <label class="form-label">Orden dentro de la sección</label>
                    <input type="number" name="order_num" class="form-input" value="<?= $edit_q['order_num'] ?? 1 ?>" min="1">
                </div>
            </div>

            <div style="display:flex;gap:10px;margin-top:20px;flex-wrap:wrap">
                <button type="submit" class="btn-save">
                    <?= $edit_q ? 'GUARDAR CAMBIOS' : 'CREAR PREGUNTA' ?>
                </button>
                <a href="/admin/questions.php" class="btn-cancel">Cancelar</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="filter-bar">
        <form method="GET" style="display:flex;gap:10px;align-items:center">
            <select name="section" class="filter-select" onchange="this.form.submit()">
                <option value="">Todas las secciones</option>
                <?php foreach ($sections as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $filter_section == $s['id'] ? 'selected' : '' ?>>
                    Sección <?= $s['order_num'] ?>: <?= htmlspecialchars(substr($s['title'], 0, 35)) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php if (!$show_form): ?>
            <a href="?action=new" style="background:var(--yellow);color:var(--black);border:none;border-radius:10px;padding:9px 16px;font-family:'Bebas Neue',sans-serif;font-size:15px;letter-spacing:1px;text-decoration:none;display:flex;align-items:center;gap:6px;white-space:nowrap">
                + Nueva Pregunta
            </a>
            <?php endif; ?>
        </form>
        <div class="filter-count"><?= count($questions) ?> pregunta<?= count($questions) !== 1 ? 's' : '' ?></div>
    </div>

    <!-- Questions list -->
    <?php if (empty($questions)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <h3>Sin preguntas</h3>
        <p>Crea la primera pregunta para comenzar.</p>
    </div>
    <?php else: ?>
    <div class="q-list">
        <?php foreach ($questions as $q): ?>
        <div class="q-card <?= !$q['is_active'] ? 'inactive' : '' ?>">
            <div class="q-top">
                <div class="q-meta">
                    <span class="q-sec-badge">Sección <?= $q['sec_order'] ?></span>
                    <span class="q-pts-badge">Pts: <span><?= $q['points'] ?></span></span>
                    <?php if (!$q['is_active']): ?>
                    <span style="font-size:10px;background:#2A1A1A;border:1px solid #3A2A2A;color:#AA6666;border-radius:50px;padding:3px 10px;">Inactiva</span>
                    <?php endif; ?>
                </div>
                <div class="q-actions">
                    <a href="?edit=<?= $q['id'] ?><?= $filter_section ? '&section='.$filter_section : '' ?>" class="q-action-btn" title="Editar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </a>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= $q['id'] ?>">
                        <button type="submit" class="q-action-btn" title="<?= $q['is_active'] ? 'Desactivar' : 'Activar' ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <?php if ($q['is_active']): ?>
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><line x1="1" y1="1" x2="23" y2="23"/>
                                <?php else: ?>
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                <?php endif; ?>
                            </svg>
                        </button>
                    </form>
                    <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar esta pregunta?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $q['id'] ?>">
                        <button type="submit" class="q-action-btn delete" title="Eliminar">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
            <div class="q-text"><?= htmlspecialchars($q['question_text']) ?></div>
            <div class="q-options">
                <?php foreach (['a','b','c','d'] as $opt): ?>
                <div class="q-opt <?= strtolower($q['correct_option']) === $opt ? 'correct-opt' : '' ?>">
                    <span class="q-opt-letter"><?= strtoupper($opt) ?></span>
                    <span><?= htmlspecialchars($q['option_'.$opt]) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if ($q['explanation']): ?>
            <div style="margin-top:10px;font-size:12px;color:var(--gray-text);border-top:1px solid #2A2A2A;padding-top:10px;line-height:1.4">
                💡 <?= htmlspecialchars($q['explanation']) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
</main>

<script>
function setCorrect(opt) {
    document.getElementById('correctInput').value = opt;
    document.querySelectorAll('.opt-sel-btn').forEach(btn => {
        btn.classList.toggle('selected', btn.textContent.trim() === opt);
    });
}
</script>
</body>
</html>
