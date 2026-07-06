<?php
$root = dirname(dirname(dirname(__FILE__)));
require_once $root . '/config/session.php';
require_once $root . '/config/database.php';
requireLogin();

$user_id    = getUserId();
$user_name  = getUserName();
$section_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$section_id) { header('Location: ../index.php'); exit; }

// Get section — raw mysqli (no spread operator)
$s_stmt = mysqli_prepare($sa_conn, "SELECT s.id, s.module_id, s.title, s.content, s.order_num, s.created_at, m.title as module_title FROM sa_sections s JOIN sa_modules m ON m.id = s.module_id WHERE s.id = ?");
mysqli_stmt_bind_param($s_stmt, 'i', $section_id);
mysqli_stmt_execute($s_stmt);
mysqli_stmt_bind_result($s_stmt, $s_id, $s_module_id, $s_title, $s_content, $s_order, $s_created, $m_title);
if (!mysqli_stmt_fetch($s_stmt)) { header('Location: ../index.php'); exit; }
mysqli_stmt_close($s_stmt);

// Check access
if ($s_order > 1) {
    $prev_order = (int)$s_order - 1;
    $mid        = (int)$s_module_id;
    $prev_res   = mysqli_query($sa_conn, "SELECT id FROM sa_sections WHERE module_id = $mid AND order_num = $prev_order");
    $prev       = mysqli_fetch_assoc($prev_res);
    if ($prev) {
        $prev_id = (int)$prev['id'];
        $uid     = (int)$user_id;
        $chk     = mysqli_query($sa_conn, "SELECT completed FROM sa_user_progress WHERE user_id = $uid AND section_id = $prev_id");
        $pp      = mysqli_fetch_assoc($chk);
        if (!$pp || !$pp['completed']) { header('Location: ../index.php'); exit; }
    }
}

// Get questions
$questions = array();
$sid       = (int)$section_id;
$uid       = (int)$user_id;
$mid       = (int)$s_module_id;
$q_res     = mysqli_query($sa_conn, "SELECT * FROM sa_questions WHERE section_id = $sid AND is_active = 1 ORDER BY order_num");
while ($row = mysqli_fetch_assoc($q_res)) $questions[] = $row;

// Get user progress
$already_done = false;
$prev_score   = 0;
$prog_res     = mysqli_query($sa_conn, "SELECT completed, points_earned FROM sa_user_progress WHERE user_id = $uid AND section_id = $sid");
if ($prog = mysqli_fetch_assoc($prog_res)) {
    $already_done = (bool)$prog['completed'];
    $prev_score   = (int)$prog['points_earned'];
}

// Get next section
$next_order  = (int)$s_order + 1;
$next_res    = mysqli_query($sa_conn, "SELECT id FROM sa_sections WHERE module_id = $mid AND order_num = $next_order");
$next_section = mysqli_fetch_assoc($next_res);

// ── QUIZ SUBMISSION ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quiz_submit'])) {
    header('Content-Type: application/json');
    $answers       = isset($_POST['answers']) ? $_POST['answers'] : array();
    $total_points  = 0;
    $correct_count = 0;
    $results       = array();

    foreach ($questions as $q) {
        $qid      = $q['id'];
        $user_ans = isset($answers[$qid]) ? strtoupper(trim($answers[$qid])) : '';
        $is_ok    = ($user_ans === strtoupper($q['correct_option']));
        if ($is_ok) { $total_points += (int)$q['points']; $correct_count++; }
        $results[$qid] = array(
            'correct'        => $is_ok,
            'user_answer'    => $user_ans,
            'correct_answer' => $q['correct_option'],
            'explanation'    => $q['explanation'],
            'points'         => $is_ok ? (int)$q['points'] : 0
        );
    }

    $total_q     = count($questions);
    $is_comp     = ($total_q > 0 && $correct_count >= (int)ceil($total_q * 0.6)) ? 1 : 0;

    $chk2    = mysqli_query($sa_conn, "SELECT id, points_earned FROM sa_user_progress WHERE user_id = $uid AND section_id = $sid");
    $existing = mysqli_fetch_assoc($chk2);

    if ($existing) {
        $old_pts    = (int)$existing['points_earned'];
        $pts_to_add = max(0, $total_points - $old_pts);
        mysqli_query($sa_conn, "UPDATE sa_user_progress SET
            completed    = GREATEST(completed, $is_comp),
            score        = IF($total_points > $old_pts, $correct_count, score),
            points_earned = GREATEST(points_earned, $total_points),
            attempts     = attempts + 1,
            completed_at = IF($total_points > $old_pts, NOW(), completed_at)
            WHERE user_id = $uid AND section_id = $sid");
    } else {
        $pts_to_add = $total_points;
        mysqli_query($sa_conn, "INSERT INTO sa_user_progress (user_id, module_id, section_id, completed, score, points_earned, attempts, completed_at)
            VALUES ($uid, $mid, $sid, $is_comp, $correct_count, $total_points, 1, NOW())");
    }

    if ($pts_to_add > 0) {
        mysqli_query($pa_conn, "UPDATE user_register SET points = points + $pts_to_add WHERE id = $uid");
    }

    echo json_encode(array(
        'success'         => true,
        'correct_count'   => $correct_count,
        'total_questions' => $total_q,
        'points_earned'   => $total_points,
        'results'         => $results,
        'next_section_id' => $next_section ? $next_section['id'] : null
    ));
    exit;
}

$fn_parts  = explode(' ', $user_name);
$first_name = $fn_parts[0];

$concepts_map = array(
    1 => array(
        array('SAFE Together','Responsabilidad colectiva y cuidado mutuo.'),
        array('Cultura Invisible','La cultura nos rodea pero es difícil de ver.'),
        array('Inteligencia Colectiva','Juntos somos más inteligentes que individualmente.'),
        array('Impacto Global','La seguridad conecta innovación, calidad y productividad.'),
    ),
    2 => array(
        array('Propiedad','Responsabilidad implícita y conexión emocional.'),
        array('Preocupación','Importarte el bienestar de los compañeros.'),
        array('Observación','Estar alerta a los riesgos del entorno.'),
        array('Liderazgo','Predicar con el ejemplo en todos los niveles.'),
    ),
    3 => array(
        array('Valor vs Prioridad','Los valores no cambian; las prioridades sí.'),
        array('Indicador Real','Actúas seguro aunque nadie te observe.'),
        array('Mentalidad Proactiva','De cero lesiones a cero exposición al riesgo.'),
        array('Indicadores','Pasar de reactivos a proactivos en seguridad.'),
    ),
    4 => array(
        array('Reactivo','Motivado por miedo al castigo.'),
        array('Dependiente','Sigue reglas porque el jefe lo exige.'),
        array('Independiente','Hace lo correcto por convicción propia.'),
        array('Interdependiente','El equipo se cuida mutuamente.'),
    ),
    5 => array(
        array('Queso Suizo','Accidentes: múltiples fallas alineadas.'),
        array('5 Peligros','Físicos, Ergonómicos, Biológicos, Químicos, Psicológicos.'),
        array('Capas Defensa','Cada capa protege; ninguna es perfecta sola.'),
        array('Enfoque Sistémico','Persona + Sistema + Infraestructura.'),
    ),
    6 => array(
        array('Individuo','Área libre de peligros, cero tolerancia al riesgo.'),
        array('Equipo','Cuidado mutuo, confianza, entrenamiento cruzado.'),
        array('Líder','Ejemplo, ambiente seguro, comunicación genuina.'),
        array('¿Quién?','En la etapa interdependiente: TODOS.'),
    ),
);
$cur_concepts = isset($concepts_map[$s_order]) ? $concepts_map[$s_order] : array();

$total_pts_possible = 0;
foreach ($questions as $q) $total_pts_possible += (int)$q['points'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($s_title) ?> — Safety Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{
            --y:#FFD600; --yl:#FFF8CC; --black:#111111;
            --white:#FFFFFF; --bg:#F4F4EF; --card:#FFFFFF;
            --border:#E5E5DC; --text:#1A1A1A; --muted:#777;
            --green:#16A34A; --red:#DC2626;
        }
        html,body{min-height:100%;font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text)}

        /* NAV */
        .topnav{position:sticky;top:0;z-index:200;background:rgba(255,255,255,0.97);backdrop-filter:blur(12px);border-bottom:3px solid var(--y);padding:12px 16px;display:flex;align-items:center;gap:12px}
        .back-btn{width:36px;height:36px;border-radius:10px;background:var(--black);border:none;display:flex;align-items:center;justify-content:center;text-decoration:none;color:var(--white);flex-shrink:0}
        .nav-info{flex:1;min-width:0}
        .nav-section-num{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--muted)}
        .nav-title{font-family:'Bebas Neue',sans-serif;font-size:17px;letter-spacing:1px;color:var(--black);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

        /* PHASES */
        #phase-read{display:block}#phase-quiz{display:none}#phase-result{display:none}

        /* GREETING */
        .greeting-banner{background:var(--black);padding:28px 20px 24px;position:relative;overflow:hidden}
        .gb-blob{position:absolute;right:-40px;top:-40px;width:200px;height:200px;border-radius:50%;background:var(--y);opacity:.08;pointer-events:none}
        .gb-blob2{position:absolute;right:60px;bottom:-60px;width:140px;height:140px;border-radius:50%;background:var(--y);opacity:.05;pointer-events:none}
        .gb-pill{display:inline-block;background:var(--y);color:var(--black);font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;padding:4px 12px;border-radius:50px;margin-bottom:14px}
        .gb-h{font-family:'Bebas Neue',sans-serif;font-size:clamp(28px,7vw,42px);letter-spacing:2px;line-height:1.1;color:var(--white);margin-bottom:10px}
        .gb-h span{color:var(--y)}
        .gb-sub{font-size:13px;color:#999;line-height:1.6;margin-bottom:20px}

        /* TTS */
        .tts-bar{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:14px;padding:12px 16px;display:flex;align-items:center;gap:12px}
        .tts-btn{width:42px;height:42px;border-radius:50%;background:var(--y);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:transform .2s,box-shadow .2s}
        .tts-btn:hover{transform:scale(1.08);box-shadow:0 4px 20px rgba(255,214,0,.5)}
        .tts-btn svg{color:var(--black)}
        .tts-label{font-size:13px;font-weight:600;color:var(--white);margin-bottom:2px}
        .tts-sub{font-size:11px;color:#888}
        .tts-wave{display:flex;align-items:center;gap:3px;height:24px}
        .wave-bar{width:3px;background:var(--y);border-radius:2px;animation:wave 1.2s ease-in-out infinite;opacity:0}
        .wave-bar:nth-child(2){animation-delay:.1s}.wave-bar:nth-child(3){animation-delay:.2s}
        .wave-bar:nth-child(4){animation-delay:.15s}.wave-bar:nth-child(5){animation-delay:.05s}
        @keyframes wave{0%,100%{height:4px}50%{height:20px}}
        .tts-wave.speaking .wave-bar{opacity:1}

        /* DONE BANNER */
        .done-banner{margin:16px 20px 0;background:#F0FDF4;border:1px solid #86EFAC;border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:12px}
        .done-icon{width:36px;height:36px;border-radius:50%;background:var(--green);display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .done-text p{font-size:13px;font-weight:600;color:#14532D}
        .done-text small{font-size:12px;color:#4ADE80}

        /* CONTENT CARD */
        .content-section{padding:20px}
        .content-card{background:var(--card);border:1px solid var(--border);border-radius:20px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.06)}
        .cc-header{padding:20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:14px;background:var(--black)}
        .cc-icon{width:48px;height:48px;border-radius:14px;background:var(--y);display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .cc-icon svg{color:var(--black)}
        .cc-title{font-family:'Bebas Neue',sans-serif;font-size:20px;letter-spacing:1px;color:var(--white)}
        .cc-sub{font-size:11px;color:#888;margin-top:2px}
        .cc-body{padding:22px 20px}
        .content-text{font-size:15px;line-height:1.8;color:#333;white-space:pre-line}

        /* KEY CONCEPTS */
        .key-concepts{margin-top:24px}
        .kc-label{font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-bottom:12px}
        .kc-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
        .kc-item{background:var(--bg);border:1px solid var(--border);border-left:3px solid var(--y);border-radius:12px;padding:12px 14px;font-size:12px;line-height:1.5}
        .kc-item strong{display:block;color:var(--black);font-size:13px;margin-bottom:3px}

        /* QUIZ CTA */
        .quiz-cta{padding:20px;border-top:1px solid var(--border);background:#FAFAF5}
        .quiz-cta-title{font-family:'Bebas Neue',sans-serif;font-size:22px;letter-spacing:1px;margin-bottom:4px}
        .quiz-cta-sub{font-size:13px;color:var(--muted);margin-bottom:16px}
        .quiz-stats{display:flex;gap:20px;margin-bottom:20px}
        .qs-val{font-family:'Bebas Neue',sans-serif;font-size:28px;letter-spacing:1px;color:var(--black)}
        .qs-lbl{font-size:10px;text-transform:uppercase;letter-spacing:1px;color:var(--muted)}
        .btn-start-quiz{width:100%;background:var(--y);color:var(--black);border:none;border-radius:14px;padding:18px;font-family:'Bebas Neue',sans-serif;font-size:20px;letter-spacing:2px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:10px;transition:transform .15s,box-shadow .15s;-webkit-tap-highlight-color:transparent}
        .btn-start-quiz:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(255,214,0,.35)}

        /* QUIZ */
        .quiz-header{background:var(--black);padding:16px 20px 20px}
        .qpbar{height:4px;background:rgba(255,255,255,.1);border-radius:2px;margin-bottom:16px;overflow:hidden}
        .qpfill{height:100%;background:var(--y);border-radius:2px;transition:width .5s ease}
        .qnav{display:flex;justify-content:space-between;align-items:center}
        .qn-lbl{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:#888}
        .qn-num{font-family:'Bebas Neue',sans-serif;font-size:30px;color:var(--white);letter-spacing:1px}
        .qn-num span{color:#555;font-size:20px}
        .qn-pts{font-family:'Bebas Neue',sans-serif;font-size:24px;color:var(--y);letter-spacing:1px}

        .question-area{padding:20px;background:var(--bg)}
        .q-tts-btn{display:inline-flex;align-items:center;gap:8px;background:var(--card);border:1px solid var(--border);border-radius:10px;padding:8px 14px;font-size:12px;color:var(--muted);cursor:pointer;margin-bottom:18px;transition:border-color .2s,color .2s}
        .q-tts-btn:hover{border-color:var(--black);color:var(--black)}
        .question-text{font-size:17px;font-weight:600;line-height:1.5;margin-bottom:20px;color:var(--text)}
        .options-list{display:flex;flex-direction:column;gap:10px}

        .option-btn{display:flex;align-items:center;gap:14px;background:var(--card);border:2px solid var(--border);border-radius:14px;padding:15px 16px;cursor:pointer;text-align:left;width:100%;transition:border-color .2s,background .2s,transform .15s;-webkit-tap-highlight-color:transparent}
        .option-btn:hover{border-color:var(--black);transform:translateX(3px)}
        .option-btn.selected{border-color:var(--black);background:#F8F8F3}
        .option-btn.correct{border-color:var(--green);background:#F0FDF4}
        .option-btn.wrong{border-color:var(--red);background:#FEF2F2}
        .option-btn:disabled{cursor:not-allowed}
        .opt-letter{width:34px;height:34px;border-radius:10px;flex-shrink:0;background:var(--bg);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-family:'Bebas Neue',sans-serif;font-size:17px;letter-spacing:1px;transition:background .2s,color .2s;color:var(--text)}
        .option-btn.selected .opt-letter{background:var(--black);color:var(--white);border-color:var(--black)}
        .option-btn.correct  .opt-letter{background:var(--green);color:var(--white);border-color:var(--green)}
        .option-btn.wrong    .opt-letter{background:var(--red);color:var(--white);border-color:var(--red)}
        .opt-text{font-size:14px;line-height:1.4;flex:1;color:var(--text)}

        .exp-box{margin-top:14px;border-radius:12px;padding:14px 16px;font-size:13px;line-height:1.6;display:none}
        .exp-ok{background:#F0FDF4;border:1px solid #86EFAC;color:#14532D}
        .exp-no{background:#FEF2F2;border:1px solid #FCA5A5;color:#991B1B}
        .exp-lbl{font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:1px;margin-bottom:5px}

        .quiz-nav-btns{padding:14px 20px;border-top:1px solid var(--border);display:flex;gap:10px;position:sticky;bottom:0;background:rgba(255,255,255,.97);backdrop-filter:blur(10px)}
        .btn-check{flex:1;background:var(--y);color:var(--black);border:none;border-radius:12px;padding:16px;font-family:'Bebas Neue',sans-serif;font-size:18px;letter-spacing:2px;cursor:pointer;transition:opacity .2s}
        .btn-check:disabled{opacity:.35;cursor:not-allowed}
        .btn-next{flex:1;background:var(--black);color:var(--white);border:none;border-radius:12px;padding:16px;font-family:'Bebas Neue',sans-serif;font-size:18px;letter-spacing:2px;cursor:pointer;display:none}
        .btn-finish{flex:1;background:var(--y);color:var(--black);border:none;border-radius:12px;padding:16px;font-family:'Bebas Neue',sans-serif;font-size:18px;letter-spacing:2px;cursor:pointer;display:none}

        /* RESULT */
        .result-hero{background:var(--black);padding:40px 20px 32px;text-align:center}
        .result-emoji{font-size:64px;display:block;margin-bottom:16px;animation:bi .6s cubic-bezier(.68,-.55,.265,1.55)}
        @keyframes bi{0%{transform:scale(0)}100%{transform:scale(1)}}
        .result-title{font-family:'Bebas Neue',sans-serif;font-size:clamp(28px,8vw,44px);letter-spacing:2px;color:var(--white);margin-bottom:8px}
        .result-title span{color:var(--y)}
        .result-sub{font-size:14px;color:#888}
        .rsc{margin:20px;background:var(--card);border:1px solid var(--border);border-radius:20px;padding:24px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,.08)}
        .rsc-big{font-family:'Bebas Neue',sans-serif;font-size:72px;color:var(--black);letter-spacing:2px;line-height:1}
        .rsc-lbl{font-size:11px;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-top:4px}
        .rsc-div{height:1px;background:var(--border);margin:20px 0}
        .rsc-stats{display:flex;justify-content:space-around}
        .rsc-stat .v{font-family:'Bebas Neue',sans-serif;font-size:28px}
        .rsc-stat .l{font-size:10px;text-transform:uppercase;letter-spacing:1px;color:var(--muted)}
        .result-actions{padding:0 20px 40px;display:flex;flex-direction:column;gap:10px}
        .btn-nxt-sec{width:100%;background:var(--y);color:var(--black);border:none;border-radius:14px;padding:18px;font-family:'Bebas Neue',sans-serif;font-size:20px;letter-spacing:2px;cursor:pointer;text-align:center;text-decoration:none;display:none;transition:transform .15s,box-shadow .15s}
        .btn-nxt-sec:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(255,214,0,.35)}
        .btn-retry{width:100%;background:var(--bg);border:1px solid var(--border);color:var(--muted);border-radius:14px;padding:15px;font-family:'DM Sans',sans-serif;font-size:14px;cursor:pointer;transition:border-color .2s,color .2s}
        .btn-retry:hover{border-color:var(--black);color:var(--black)}
        .btn-bck{background:transparent;border:none;color:var(--muted);font-size:13px;cursor:pointer;padding:8px;text-decoration:none;display:block;text-align:center;transition:color .2s}
        .btn-bck:hover{color:var(--black)}

        /* CONFETTI */
        .cfp{position:fixed;width:8px;height:8px;top:-10px;opacity:0;animation:cf linear forwards;border-radius:2px;z-index:9999}
        @keyframes cf{0%{transform:translateY(0) rotate(0deg);opacity:1}100%{transform:translateY(100vh) rotate(720deg);opacity:0}}
    </style>
</head>
<body>

<nav class="topnav">
    <a href="../index.php" class="back-btn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <div class="nav-info">
        <div class="nav-section-num">Sección <?= (int)$s_order ?> de 6</div>
        <div class="nav-title"><?= htmlspecialchars($s_title) ?></div>
    </div>
</nav>

<!-- ══ PHASE 1: READ ══════════════════════════════ -->
<div id="phase-read">
    <div class="greeting-banner">
        <div class="gb-blob"></div><div class="gb-blob2"></div>
        <div class="gb-pill">Safety Academy · Módulo 1</div>
        <h1 class="gb-h">¡Hola, <span><?= htmlspecialchars($first_name) ?></span>!<br>Sección <?= (int)$s_order ?> — Vamos</h1>
        <p class="gb-sub">Lee el contenido, luego responde <?= count($questions) ?> pregunta<?= count($questions) !== 1 ? 's' : '' ?> para ganar hasta <?= $total_pts_possible ?> puntos.</p>
        <div class="tts-bar">
            <button class="tts-btn" id="ttsBtn" onclick="toggleTTS()">
                <svg id="ttsIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/>
                    <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/>
                </svg>
            </button>
            <div>
                <div class="tts-label" id="ttsLabel">Escuchar contenido</div>
                <div class="tts-sub" id="ttsSub">Presiona para reproducir en voz alta</div>
            </div>
            <div class="tts-wave" id="ttsWave">
                <div class="wave-bar"></div><div class="wave-bar"></div><div class="wave-bar"></div>
                <div class="wave-bar"></div><div class="wave-bar"></div>
            </div>
        </div>
    </div>

    <?php if ($already_done): ?>
    <div class="done-banner">
        <div class="done-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div class="done-text">
            <p>Sección ya completada</p>
            <small>Ganaste <?= $prev_score ?> pts · Puedes repetir el quiz</small>
        </div>
    </div>
    <?php endif; ?>

    <div class="content-section">
        <div class="content-card">
            <div class="cc-header">
                <div class="cc-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <div>
                    <div class="cc-title" id="contentTitle"><?= htmlspecialchars($s_title) ?></div>
                    <div class="cc-sub">Lee con atención antes del quiz</div>
                </div>
            </div>
            <div class="cc-body">
                <div class="content-text" id="contentText"><?= nl2br(htmlspecialchars($s_content)) ?></div>
                <?php if (!empty($cur_concepts)): ?>
                <div class="key-concepts">
                    <div class="kc-label">Conceptos Clave</div>
                    <div class="kc-grid">
                        <?php foreach ($cur_concepts as $c): ?>
                        <div class="kc-item"><strong><?= htmlspecialchars($c[0]) ?></strong><?= htmlspecialchars($c[1]) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="quiz-cta">
                <div class="quiz-cta-title">¿Listo para el Quiz?</div>
                <div class="quiz-cta-sub">Necesitas un 60% para completar la sección.</div>
                <div class="quiz-stats">
                    <div><div class="qs-val"><?= count($questions) ?></div><div class="qs-lbl">Preguntas</div></div>
                    <div><div class="qs-val"><?= $total_pts_possible ?></div><div class="qs-lbl">Puntos</div></div>
                    <div><div class="qs-val">60%</div><div class="qs-lbl">Para pasar</div></div>
                </div>
                <button class="btn-start-quiz" onclick="startQuiz()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    COMENZAR QUIZ
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ PHASE 2: QUIZ ══════════════════════════════ -->
<div id="phase-quiz">
    <div class="quiz-header">
        <div class="qpbar"><div class="qpfill" id="qpfill" style="width:0%"></div></div>
        <div class="qnav">
            <div>
                <div class="qn-lbl">Pregunta</div>
                <div class="qn-num" id="qCounter">1 <span>/ <?= count($questions) ?></span></div>
            </div>
            <div style="text-align:right">
                <div class="qn-lbl">En juego</div>
                <div class="qn-pts" id="qPoints">— pts</div>
            </div>
        </div>
    </div>
    <div class="question-area">
        <button class="q-tts-btn" onclick="readQuestion()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
            Escuchar pregunta
        </button>
        <div class="question-text" id="questionText"></div>
        <div class="options-list" id="optionsList"></div>
        <div class="exp-box" id="expBox"></div>
    </div>
    <div class="quiz-nav-btns">
        <button class="btn-check"  id="btnCheck"  onclick="checkAnswer()" disabled>VERIFICAR</button>
        <button class="btn-next"   id="btnNext"   onclick="nextQuestion()">SIGUIENTE →</button>
        <button class="btn-finish" id="btnFinish" onclick="finishQuiz()">VER RESULTADOS</button>
    </div>
</div>

<!-- ══ PHASE 3: RESULT ════════════════════════════ -->
<div id="phase-result">
    <div class="result-hero">
        <span class="result-emoji" id="rEmoji">🏆</span>
        <h1 class="result-title" id="rTitle">¡BIEN HECHO, <span><?= strtoupper(htmlspecialchars($first_name)) ?></span>!</h1>
        <p class="result-sub" id="rSub">Has completado la sección</p>
    </div>
    <div class="rsc">
        <div class="rsc-big" id="rPts">0</div>
        <div class="rsc-lbl">Puntos ganados</div>
        <div class="rsc-div"></div>
        <div class="rsc-stats">
            <div class="rsc-stat"><div class="v" id="rOk"  style="color:var(--green)">0</div><div class="l">Correctas</div></div>
            <div class="rsc-stat"><div class="v" id="rNo"  style="color:var(--red)">0</div><div class="l">Incorrectas</div></div>
            <div class="rsc-stat"><div class="v" id="rPct" style="color:var(--black)">0%</div><div class="l">Acierto</div></div>
        </div>
    </div>
    <div class="result-actions">
        <?php if ($next_section): ?>
        <a href="section.php?id=<?= (int)$next_section['id'] ?>" class="btn-nxt-sec" id="btnNxt">SIGUIENTE SECCIÓN →</a>
        <?php else: ?>
        <a href="../index.php" class="btn-nxt-sec" id="btnNxt">¡MÓDULO COMPLETADO! 🎉</a>
        <?php endif; ?>
        <button class="btn-retry" onclick="retryQuiz()">Repetir Quiz</button>
        <a href="../index.php" class="btn-bck">← Volver al módulo</a>
    </div>
</div>

<script>
var Q    = <?= json_encode($questions) ?>;
var NAME = <?= json_encode($first_name) ?>;
var cQ   = 0, ua = {}, selOpt = null, answered = false, speaking = false;

function toggleTTS(){
    if(!window.speechSynthesis) return;
    if(speaking){speechSynthesis.cancel();setSpk(false);return;}
    var t=document.getElementById('contentTitle').innerText+'. '+document.getElementById('contentText').innerText;
    var u=new SpeechSynthesisUtterance(t);
    u.lang='es-ES';u.rate=0.92;
    u.onstart=function(){setSpk(true);};
    u.onend=function(){setSpk(false);};
    u.onerror=function(){setSpk(false);};
    speechSynthesis.speak(u);
}
function setSpk(v){
    speaking=v;
    var w=document.getElementById('ttsWave');
    var l=document.getElementById('ttsLabel');
    var s=document.getElementById('ttsSub');
    var i=document.getElementById('ttsIcon');
    if(v){
        w.classList.add('speaking');l.textContent='Reproduciendo...';s.textContent='Toca para pausar';
        i.innerHTML='<rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/>';
    } else {
        w.classList.remove('speaking');l.textContent='Escuchar contenido';s.textContent='Presiona para reproducir en voz alta';
        i.innerHTML='<polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/>';
    }
}
function readQuestion(){
    if(!window.speechSynthesis) return;
    speechSynthesis.cancel();
    var q=Q[cQ];
    var t='Pregunta '+(cQ+1)+'. '+q.question_text+'. A: '+q.option_a+'. B: '+q.option_b+'. C: '+q.option_c+'. D: '+q.option_d+'.';
    var u=new SpeechSynthesisUtterance(t);u.lang='es-ES';u.rate=0.9;
    speechSynthesis.speak(u);
}
function startQuiz(){
    if(window.speechSynthesis) speechSynthesis.cancel();
    document.getElementById('phase-read').style.display='none';
    document.getElementById('phase-quiz').style.display='block';
    window.scrollTo(0,0);loadQ(0);
}
function loadQ(i){
    cQ=i;selOpt=null;answered=false;
    var q=Q[i],tot=Q.length;
    document.getElementById('qpfill').style.width=((i/tot)*100)+'%';
    document.getElementById('qCounter').innerHTML=(i+1)+' <span>/ '+tot+'</span>';
    document.getElementById('qPoints').textContent=q.points+' pts';
    document.getElementById('questionText').textContent=q.question_text;
    var lo=['A','B','C','D'],lt=[q.option_a,q.option_b,q.option_c,q.option_d],list=document.getElementById('optionsList');
    list.innerHTML='';
    for(var k=0;k<4;k++){
        var b=document.createElement('button');
        b.className='option-btn';b.dataset.opt=lo[k];
        b.innerHTML='<span class="opt-letter">'+lo[k]+'</span><span class="opt-text">'+lt[k]+'</span>';
        b.onclick=(function(l){return function(){selOpt_(l);};})(lo[k]);
        list.appendChild(b);
    }
    var eb=document.getElementById('expBox');eb.style.display='none';eb.className='exp-box';
    document.getElementById('btnCheck').style.display='flex';
    document.getElementById('btnCheck').disabled=true;
    document.getElementById('btnNext').style.display='none';
    document.getElementById('btnFinish').style.display='none';
    window.scrollTo(0,0);
}
function selOpt_(l){
    if(answered) return;selOpt=l;
    var bs=document.querySelectorAll('.option-btn');
    for(var i=0;i<bs.length;i++) bs[i].classList.toggle('selected',bs[i].dataset.opt===l);
    document.getElementById('btnCheck').disabled=false;
}
function checkAnswer(){
    if(!selOpt||answered) return;answered=true;
    var q=Q[cQ],cor=q.correct_option.toUpperCase(),ok=selOpt===cor;
    ua[q.id]=selOpt;
    var bs=document.querySelectorAll('.option-btn');
    for(var i=0;i<bs.length;i++){
        bs[i].disabled=true;bs[i].classList.remove('selected');
        if(bs[i].dataset.opt===cor) bs[i].classList.add('correct');
        else if(bs[i].dataset.opt===selOpt&&!ok) bs[i].classList.add('wrong');
    }
    var eb=document.getElementById('expBox');
    eb.innerHTML='<div class="exp-lbl">'+(ok?'✓ ¡Correcto!':'✗ Incorrecto')+'</div>'+q.explanation;
    eb.className='exp-box '+(ok?'exp-ok':'exp-no');eb.style.display='block';
    if(window.speechSynthesis){
        var msg=ok?'¡Excelente, '+NAME+'! '+q.explanation:'Incorrecto. La respuesta era '+cor+'. '+q.explanation;
        var u=new SpeechSynthesisUtterance(msg);u.lang='es-ES';u.rate=0.92;speechSynthesis.speak(u);
    }
    document.getElementById('btnCheck').style.display='none';
    if(cQ===Q.length-1) document.getElementById('btnFinish').style.display='flex';
    else document.getElementById('btnNext').style.display='flex';
}
function nextQuestion(){if(window.speechSynthesis)speechSynthesis.cancel();if(cQ<Q.length-1)loadQ(cQ+1);}
function finishQuiz(){
    if(window.speechSynthesis)speechSynthesis.cancel();
    for(var i=0;i<Q.length;i++){if(!ua[Q[i].id])ua[Q[i].id]='';}
    var fd=new FormData();fd.append('quiz_submit','1');
    var ks=Object.keys(ua);for(var k=0;k<ks.length;k++)fd.append('answers['+ks[k]+']',ua[ks[k]]);
    var xhr=new XMLHttpRequest();xhr.open('POST',window.location.href,true);
    xhr.onload=function(){if(xhr.status===200){try{showRes(JSON.parse(xhr.responseText));}catch(e){alert('Error. Intenta de nuevo.');}}};
    xhr.send(fd);
}
function showRes(d){
    document.getElementById('phase-quiz').style.display='none';
    document.getElementById('phase-result').style.display='block';
    window.scrollTo(0,0);
    var pct=Math.round((d.correct_count/d.total_questions)*100),passed=pct>=60;
    document.getElementById('rPts').textContent='+'+d.points_earned;
    document.getElementById('rOk').textContent=d.correct_count;
    document.getElementById('rNo').textContent=d.total_questions-d.correct_count;
    document.getElementById('rPct').textContent=pct+'%';
    if(pct===100){document.getElementById('rEmoji').textContent='🏆';document.getElementById('rTitle').innerHTML='¡PERFECTO, <span>'+NAME.toUpperCase()+'</span>!';document.getElementById('rSub').textContent='¡Puntuación perfecta!';confetti();}
    else if(passed){document.getElementById('rEmoji').textContent='⭐';document.getElementById('rTitle').innerHTML='¡BIEN HECHO, <span>'+NAME.toUpperCase()+'</span>!';document.getElementById('rSub').textContent='Aprobaste con '+pct+'%.';confetti();}
    else{document.getElementById('rEmoji').textContent='💪';document.getElementById('rTitle').innerHTML='¡SIGUE, <span>'+NAME.toUpperCase()+'</span>!';document.getElementById('rSub').textContent='Obtuviste '+pct+'%. Necesitas 60% para pasar.';}
    if(passed) setTimeout(function(){document.getElementById('btnNxt').style.display='block';},800);
    if(window.speechSynthesis){
        var msg=passed?'¡Felicitaciones '+NAME+'! Ganaste '+d.points_earned+' puntos.':'Obtuviste '+pct+' por ciento. Necesitas 60 para pasar.';
        setTimeout(function(){var u=new SpeechSynthesisUtterance(msg);u.lang='es-ES';speechSynthesis.speak(u);},600);
    }
}
function retryQuiz(){
    if(window.speechSynthesis)speechSynthesis.cancel();ua={};
    document.getElementById('phase-result').style.display='none';
    document.getElementById('phase-quiz').style.display='block';
    window.scrollTo(0,0);loadQ(0);
}
function confetti(){
    var cols=['#FFD600','#111111','#FFD600','#FF6B6B','#4ECDC4'];
    for(var i=0;i<60;i++){
        (function(i){setTimeout(function(){
            var el=document.createElement('div');el.className='cfp';
            el.style.left=(Math.random()*100)+'vw';
            el.style.background=cols[Math.floor(Math.random()*cols.length)];
            el.style.animationDuration=(Math.random()*2+2)+'s';
            el.style.animationDelay=(Math.random())+'s';
            document.body.appendChild(el);
            setTimeout(function(){if(el.parentNode)el.parentNode.removeChild(el);},4000);
        },i*30);})(i);
    }
}
window.addEventListener('beforeunload',function(){if(window.speechSynthesis)speechSynthesis.cancel();});
</script>
</body>
</html>
