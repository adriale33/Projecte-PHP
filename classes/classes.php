<?php
// classes/clases.php - Fitxa tècnica completa d'una classe
require_once '../auth.php';
requireLogin();

require_once '../config.php';

$user_id   = $_SESSION['usuari_id'];
$user_name = $_SESSION['nom_usuari'] ?? 'Usuari';

// Validar ID per GET
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header('Location: ../dashboard.php');
    exit;
}

$id_classe = (int) $_GET['id'];

// --- READ: Obtenir la classe ---
$stmt = $pdo->prepare("SELECT * FROM classes WHERE id_classe = ? LIMIT 1");
$stmt->execute([$id_classe]);
$classe = $stmt->fetch();

if (!$classe) {
    header('Location: ../dashboard.php');
    exit;
}

// --- READ: Comprovar si l'usuari ja està inscrit ---
$stmt = $pdo->prepare("SELECT id_reserva FROM reserves WHERE usuari_id = ? AND classe_id = ?");
$stmt->execute([$user_id, $id_classe]);
$inscrit = (bool) $stmt->fetch();

// --- READ: Comptar inscrits actuals ---
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reserves WHERE classe_id = ?");
$stmt->execute([$id_classe]);
$total_inscrits = (int) $stmt->fetchColumn();

// --- CREATE: Inscripció des de la fitxa ---
$missatge       = '';
$missatge_tipus = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inscriure'])) {
    if ($inscrit) {
        $missatge       = 'Ja estàs inscrit a aquesta classe.';
        $missatge_tipus = 'warning';
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO reserves (usuari_id, classe_id, data_reserva) VALUES (?, ?, NOW())"
        );
        $stmt->execute([$user_id, $id_classe]);
        $inscrit = true;
        $total_inscrits++;
        $missatge       = 'Inscripció realitzada correctament!';
        $missatge_tipus = 'success';
    }
}

// Icones i colors
$icones_categoria = [
    'Força'          => '🏋️',
    'Cardiovascular' => '🏃',
    'Cos i ment'     => '🧘',
    'Virtual'        => '💻',
    'Aquàtica'       => '🏊',
];

$colors_intensitat = [
    'Alta'    => ['color' => '#ef4444', 'bg' => 'rgba(239,68,68,.1)',  'border' => 'rgba(239,68,68,.35)'],
    'Mitjana' => ['color' => '#f59e0b', 'bg' => 'rgba(245,158,11,.1)', 'border' => 'rgba(245,158,11,.35)'],
    'Baixa'   => ['color' => '#22c55e', 'bg' => 'rgba(34,197,94,.1)',  'border' => 'rgba(34,197,94,.35)'],
];

$ci = $colors_intensitat[$classe['intensitat']] ?? $colors_intensitat['Mitjana'];
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($classe['nom']) ?> — Projecte Gimnàs</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:         #0a0a0f;
            --bg-card:    #12121a;
            --bg-card2:   #1a1a26;
            --border:     #2a2a3a;
            --accent:     #e8ff3c;
            --accent-dim: #b8cc2a;
            --text:       #f0f0f5;
            --text-muted: #7a7a9a;
            --red:        #ef4444;
            --amber:      #f59e0b;
            --green:      #22c55e;
            --radius:     12px;
            --radius-lg:  20px;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* TOPBAR */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(10,10,15,.9);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            height: 64px;
        }
        .logo {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.8rem;
            letter-spacing: 3px;
            color: var(--accent);
            text-decoration: none;
        }
        .topbar-right { display: flex; align-items: center; gap: 1rem; }

        .btn-nav {
            padding: .4rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: transparent;
            color: var(--text-muted);
            font-family: 'DM Sans', sans-serif;
            font-size: .85rem;
            text-decoration: none;
            transition: all .2s;
        }
        .btn-nav:hover        { border-color: var(--accent); color: var(--accent); }
        .btn-nav.logout       { border-color: var(--red); background: var(--red); color: #fff; }
        .btn-nav.logout:hover { background: #c53030; border-color: #c53030; color: #fff; }

        /* HERO */
        .hero {
            position: relative;
            overflow: hidden;
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            padding: 4rem 2rem 3rem;
        }
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 70% 50%, rgba(232,255,60,.06), transparent 65%);
            pointer-events: none;
        }
        .hero-inner {
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: end;
            gap: 2rem;
        }
        .hero-category {
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: var(--text-muted);
            margin-bottom: .6rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .hero-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(3rem, 8vw, 6rem);
            letter-spacing: 2px;
            line-height: 1;
        }
        .hero-subtitle {
            margin-top: .8rem;
            color: var(--text-muted);
            font-size: .95rem;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            padding: .5rem 1.2rem;
            border-radius: 100px;
            font-weight: 600;
            font-size: .9rem;
            border: 1px solid <?= $ci['border'] ?>;
            color: <?= $ci['color'] ?>;
            background: <?= $ci['bg'] ?>;
        }

        /* PAGE */
        .page-wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2.5rem 1.5rem 4rem;
        }

        /* FEEDBACK */
        .feedback {
            padding: 1rem 1.4rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: .7rem;
            animation: slideDown .3s ease;
        }
        @keyframes slideDown { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
        .feedback.success { background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.3); color: var(--green); }
        .feedback.warning { background: rgba(245,158,11,.12); border: 1px solid rgba(245,158,11,.3); color: var(--amber); }

        /* BREADCRUMB */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: .5rem;
            font-size: .82rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }
        .breadcrumb a { color: var(--text-muted); text-decoration: none; }
        .breadcrumb a:hover { color: var(--accent); }
        .breadcrumb .current { color: var(--text); }

        /* GRID */
        .ficha-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 1.5rem;
            align-items: start;
        }
        @media (max-width: 860px) {
            .ficha-grid { grid-template-columns: 1fr; }
            .hero-inner  { grid-template-columns: 1fr; }
        }

        /* CARD */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            margin-bottom: 1.2rem;
        }
        .card-head {
            padding: 1.2rem 1.6rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: .7rem;
        }
        .card-head h3 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.1rem;
            letter-spacing: 1.5px;
        }
        .card-body { padding: 1.4rem 1.6rem; }

        /* TAULA */
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table tr + tr td { border-top: 1px solid var(--border); }
        .info-table td { padding: .85rem 0; font-size: .9rem; vertical-align: middle; }
        .info-table td:first-child { color: var(--text-muted); width: 40%; padding-right: 1rem; }
        .info-table td:last-child  { color: var(--text); font-weight: 500; }

        /* INTENSITAT */
        .intensitat-bar { display: flex; gap: 4px; align-items: center; }
        .intensitat-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            background: var(--border);
        }
        .intensitat-dot.actiu { background: <?= $ci['color'] ?>; }

        /* PLACES */
        .places-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: .5rem;
            font-size: .85rem;
            color: var(--text-muted);
        }
        .places-info span { color: var(--text); font-weight: 600; }
        .progress-bar {
            width: 100%; height: 6px;
            background: var(--border);
            border-radius: 100px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            border-radius: 100px;
            transition: width .6s ease;
        }

        /* BOTONS INSCRIPCIÓ */
        .btn-inscriure-big {
            width: 100%;
            padding: .9rem;
            border: none;
            border-radius: var(--radius);
            background: var(--accent);
            color: #000;
            font-family: 'DM Sans', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: background .2s, transform .15s;
        }
        .btn-inscriure-big:hover { background: var(--accent-dim); transform: scale(1.02); }

        .btn-inscrit-big {
            width: 100%;
            padding: .9rem;
            border: 1px solid rgba(34,197,94,.35);
            border-radius: var(--radius);
            background: rgba(34,197,94,.1);
            color: var(--green);
            font-family: 'DM Sans', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            text-align: center;
            cursor: default;
        }

        /* ESTUDI */
        .estudi-visual {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 120px;
            border-radius: var(--radius);
            background: var(--bg-card2);
            border: 1px dashed var(--border);
            font-size: 2.5rem;
            flex-direction: column;
            gap: .4rem;
        }
        .estudi-visual span {
            font-size: .8rem;
            color: var(--text-muted);
            font-family: 'DM Sans', sans-serif;
        }

        /* RESUM */
        .resum-row {
            display: flex;
            justify-content: space-between;
            font-size: .88rem;
            padding: .35rem 0;
        }
        .resum-row + .resum-row { border-top: 1px solid var(--border); }
        .resum-row span { color: var(--text-muted); }
        .resum-row strong { color: var(--text); }
    </style>
</head>
<body>

<!-- TOPBAR -->
<nav class="topbar">
    <a class="logo" href="../dashboard.php">Projecte Gimnàs</a>
    <div class="topbar-right">
        <a href="../dashboard.php" class="btn-nav">← Tornar</a>
        <a href="../logout.php" class="btn-nav logout">Tancar sessió</a>
    </div>
</nav>

<!-- HERO -->
<div class="hero">
    <div class="hero-inner">
        <div>
            <div class="hero-category">
                <?= $icones_categoria[$classe['categoria']] ?? '🏅' ?>
                <?= htmlspecialchars($classe['categoria']) ?>
            </div>
            <h1 class="hero-title"><?= htmlspecialchars($classe['nom']) ?></h1>
            <p class="hero-subtitle">
                <?php if ($classe['nom_tècnic']): ?>
                    Impartida per <strong><?= htmlspecialchars($classe['nom_tècnic']) ?></strong>
                    &nbsp;·&nbsp;
                <?php endif; ?>
                <?= htmlspecialchars($classe['estudi']) ?>
            </p>
        </div>
        <div class="hero-badge"><?= htmlspecialchars($classe['intensitat']) ?></div>
    </div>
</div>

<!-- CONTINGUT -->
<div class="page-wrap">

    <!-- BREADCRUMB -->
    <nav class="breadcrumb">
        <a href="../dashboard.php">Dashboard</a>
        <span>/</span>
        <a href="../dashboard.php">Classes</a>
        <span>/</span>
        <span class="current"><?= htmlspecialchars($classe['nom']) ?></span>
    </nav>

    <!-- FEEDBACK -->
    <?php if ($missatge): ?>
    <div class="feedback <?= $missatge_tipus ?>">
        <?= $missatge_tipus === 'success' ? '✅' : '⚠️' ?>
        <?= htmlspecialchars($missatge) ?>
    </div>
    <?php endif; ?>

    <!-- GRID -->
    <div class="ficha-grid">

        <!-- ESQUERRA -->
        <div>
            <!-- FITXA TÈCNICA -->
            <div class="card">
                <div class="card-head"><span>📋</span><h3>Fitxa Tècnica</h3></div>
                <div class="card-body">
                    <table class="info-table">
                        <tr>
                            <td>Nom de la classe</td>
                            <td><?= htmlspecialchars($classe['nom']) ?></td>
                        </tr>
                        <tr>
                            <td>Categoria</td>
                            <td><?= ($icones_categoria[$classe['categoria']] ?? '') ?> <?= htmlspecialchars($classe['categoria']) ?></td>
                        </tr>
                        <tr>
                            <td>Tècnic responsable</td>
                            <td><?= $classe['nom_tècnic']
                                    ? htmlspecialchars($classe['nom_tècnic'])
                                    : '<em style="color:var(--text-muted)">Classe virtual</em>' ?></td>
                        </tr>
                        <tr>
                            <td>Durada</td>
                            <td><?= (int)$classe['durada'] ?> minuts</td>
                        </tr>
                        <tr>
                            <td>Intensitat</td>
                            <td>
                                <div class="intensitat-bar">
                                    <?php
                                    $nivells = ['Baixa' => 1, 'Mitjana' => 2, 'Alta' => 3];
                                    $nivell  = $nivells[$classe['intensitat']] ?? 2;
                                    for ($i = 1; $i <= 3; $i++):
                                    ?>
                                    <div class="intensitat-dot <?= $i <= $nivell ? 'actiu' : '' ?>"></div>
                                    <?php endfor; ?>
                                    &nbsp;<?= htmlspecialchars($classe['intensitat']) ?>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>Horari</td>
                            <td><?= htmlspecialchars($classe['horari']) ?> h</td>
                        </tr>
                        <tr>
                            <td>Estudi / Espai</td>
                            <td><?= htmlspecialchars($classe['estudi']) ?></td>
                        </tr>
                        <tr>
                            <td>Places totals</td>
                            <td><?= (int)$classe['places'] ?></td>
                        </tr>
                        <tr>
                            <td>Inscrits actuals</td>
                            <td><?= $total_inscrits ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- UBICACIÓ -->
            <div class="card">
                <div class="card-head"><span>🗺️</span><h3>Ubicació</h3></div>
                <div class="card-body">
                    <?php
                    $estudi_icons = [
                        'Estudi 1' => '🏋️',
                        'Estudi 2' => '🏋️',
                        'Estudi 3' => '🏋️',
                        'Piscina'  => '🏊',
                    ];
                    $icon = $estudi_icons[$classe['estudi']] ?? '🏢';
                    ?>
                    <div class="estudi-visual">
                        <?= $icon ?>
                        <span><?= htmlspecialchars($classe['estudi']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- DRETA -->
        <div>
            <!-- OCUPACIÓ -->
            <div class="card">
                <div class="card-head"><span>👥</span><h3>Ocupació</h3></div>
                <div class="card-body">
                    <?php
                    $pct = $classe['places'] > 0
                        ? min(100, round($total_inscrits / $classe['places'] * 100))
                        : 0;
                    $color_barra = $pct >= 90 ? 'var(--red)' : ($pct >= 60 ? 'var(--amber)' : 'var(--accent)');
                    ?>
                    <div class="places-info">
                        <span><?= $total_inscrits ?> / <?= (int)$classe['places'] ?> inscrits</span>
                        <span><?= $pct ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width:<?= $pct ?>%; background:<?= $color_barra ?>;"></div>
                    </div>
                </div>
            </div>

            <!-- INSCRIPCIÓ -->
            <div class="card">
                <div class="card-head"><span>✍️</span><h3>Inscripció</h3></div>
                <div class="card-body">
                    <?php if ($inscrit): ?>
                        <div class="btn-inscrit-big">✓ Ja estàs inscrit</div>
                        <p style="margin-top:.8rem; font-size:.82rem; color:var(--text-muted); text-align:center;">
                            Ja tens plaça reservada per a aquesta classe.
                        </p>
                    <?php else: ?>
                        <form method="POST">
                            <button type="submit" name="inscriure" class="btn-inscriure-big">
                                Inscriure'm a <?= htmlspecialchars($classe['nom']) ?>
                            </button>
                        </form>
                        <p style="margin-top:.8rem; font-size:.82rem; color:var(--text-muted); text-align:center;">
                            La inscripció és immediata i gratuïta.
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RESUM -->
            <div class="card">
                <div class="card-head"><span>⚡</span><h3>Resum</h3></div>
                <div class="card-body">
                    <div class="resum-row"><span>Horari</span>  <strong><?= htmlspecialchars($classe['horari']) ?> h</strong></div>
                    <div class="resum-row"><span>Durada</span>  <strong><?= (int)$classe['durada'] ?> min</strong></div>
                    <div class="resum-row"><span>Espai</span>   <strong><?= htmlspecialchars($classe['estudi']) ?></strong></div>
                    <div class="resum-row"><span>Tècnic</span>  <strong><?= $classe['nom_tècnic'] ? htmlspecialchars($classe['nom_tècnic']) : 'Virtual' ?></strong></div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
const feedback = document.querySelector('.feedback');
if (feedback) {
    setTimeout(() => {
        feedback.style.transition = 'opacity .5s';
        feedback.style.opacity = '0';
        setTimeout(() => feedback.remove(), 500);
    }, 4000);
}
</script>
</body>
</html>

