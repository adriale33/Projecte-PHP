<?php
// dashboard.php
session_start();

if (!isset($_SESSION['usuari_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php'; // <- dona $pdo com a variable global

$user_id   = $_SESSION['usuari_id'];
$user_name = $_SESSION['nom_usuari'] ?? 'Usuari';

// --- CREATE: Inscripció a una classe ---
$missatge       = '';
$missatge_tipus = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inscriure'], $_POST['id_classe'])) {
    $id_classe = (int) $_POST['id_classe'];

    // Comprovar si ja està inscrit
    $stmt = $pdo->prepare("SELECT id_reserva FROM reserves WHERE usuari_id = ? AND classe_id = ?");
    $stmt->execute([$user_id, $id_classe]);

    if ($stmt->fetch()) {
        $missatge       = 'Ja estàs inscrit a aquesta classe.';
        $missatge_tipus = 'warning';
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO reserves (usuari_id, classe_id, data_reserva) VALUES (?, ?, NOW())"
        );
        $stmt->execute([$user_id, $id_classe]);
        $missatge       = 'Inscripció realitzada correctament!';
        $missatge_tipus = 'success';
    }
}

// --- READ: Totes les classes agrupades per categoria ---
$stmt = $pdo->query(
    "SELECT id_classe, nom, nom_tècnic, durada, categoria, intensitat, estudi, horari, places
     FROM classes
     ORDER BY categoria, horari"
);
$totes_classes = $stmt->fetchAll();

$classes_per_categoria = [];
foreach ($totes_classes as $classe) {
    $classes_per_categoria[$classe['categoria']][] = $classe;
}

// --- READ: Inscripcions de l'usuari ---
$stmt = $pdo->prepare("SELECT classe_id FROM reserves WHERE usuari_id = ?");
$stmt->execute([$user_id]);
$inscripcions_usuari = array_column($stmt->fetchAll(), 'classe_id');

// Icones per categoria
$icones_categoria = [
    'Força'          => '🏋️',
    'Cardiovascular' => '🏃',
    'Cos i ment'     => '🧘',
    'Virtual'        => '💻',
    'Aquàtica'       => '🏊',
];
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Projecte Gimnàs</title>
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

        html { scroll-behavior: smooth; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            line-height: 1.6;
        }

        /* TOPBAR */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(10, 10, 15, .9);
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

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 1.2rem;
        }

        .topbar-user {
            display: flex;
            align-items: center;
            gap: .6rem;
            font-size: .9rem;
            color: var(--text-muted);
        }
        .topbar-user span { color: var(--text); font-weight: 500; }

        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), #7c3aed);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .85rem;
            color: #000;
        }

        .btn-nav {
            padding: .4rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: transparent;
            color: var(--text-muted);
            font-family: 'DM Sans', sans-serif;
            font-size: .85rem;
            cursor: pointer;
            text-decoration: none;
            transition: all .2s;
        }
        .btn-nav:hover        { border-color: var(--accent); color: var(--accent); }
        .btn-nav.logout:hover { border-color: var(--red);    color: var(--red);    }
        .btn-nav.admin        { border-color: var(--accent); color: var(--accent); }

        /* LAYOUT */
        .main-wrap {
            max-width: 1300px;
            margin: 0 auto;
            padding: 2.5rem 1.5rem 4rem;
        }

        /* HEADER */
        .dash-header { margin-bottom: 2.5rem; }

        .dash-header h1 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(2.2rem, 5vw, 4rem);
            letter-spacing: 2px;
            line-height: 1;
        }
        .dash-header h1 em { color: var(--accent); font-style: normal; }
        .dash-header p { margin-top: .6rem; color: var(--text-muted); font-size: .95rem; }

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
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .feedback.success { background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.3); color: var(--green); }
        .feedback.warning { background: rgba(245,158,11,.12); border: 1px solid rgba(245,158,11,.3); color: var(--amber); }

        /* STATS */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.2rem 1.4rem;
        }
        .stat-label {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-muted);
            margin-bottom: .3rem;
        }
        .stat-val {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 2.2rem;
            letter-spacing: 1px;
            color: var(--accent);
            line-height: 1;
        }

        /* FILTRES */
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: .6rem;
            margin-bottom: 2rem;
        }
        .filter-btn {
            padding: .45rem 1.1rem;
            border-radius: 100px;
            border: 1px solid var(--border);
            background: var(--bg-card);
            color: var(--text-muted);
            font-family: 'DM Sans', sans-serif;
            font-size: .85rem;
            cursor: pointer;
            transition: all .2s;
        }
        .filter-btn:hover,
        .filter-btn.active {
            border-color: var(--accent);
            color: var(--accent);
            background: rgba(232,255,60,.08);
        }

        /* SECCIONS */
        .categoria-section { margin-bottom: 3rem; }

        .categoria-header {
            display: flex;
            align-items: center;
            gap: .8rem;
            margin-bottom: 1.2rem;
        }
        .categoria-header h2 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.5rem;
            letter-spacing: 2px;
        }
        .categoria-linia { flex: 1; height: 1px; background: var(--border); }

        /* GRID */
        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.2rem;
        }

        /* CARD */
        .classe-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform .25s, border-color .25s, box-shadow .25s;
        }
        .classe-card:hover {
            transform: translateY(-4px);
            border-color: rgba(232,255,60,.25);
            box-shadow: 0 12px 40px rgba(0,0,0,.4);
        }
        .classe-card.inscrit { border-color: rgba(34,197,94,.3); }

        .card-top { padding: 1.4rem 1.4rem .8rem; position: relative; }

        .card-badge-inscrit {
            position: absolute;
            top: 1rem; right: 1rem;
            background: rgba(34,197,94,.15);
            border: 1px solid rgba(34,197,94,.3);
            color: var(--green);
            border-radius: 100px;
            font-size: .72rem;
            font-weight: 600;
            padding: .25rem .7rem;
        }

        .card-nom {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.6rem;
            letter-spacing: 1.5px;
            line-height: 1;
            margin-bottom: .5rem;
        }

        .card-tecnic { font-size: .82rem; color: var(--text-muted); margin-bottom: .8rem; }
        .card-tecnic span { color: var(--text); font-weight: 500; }

        .card-pills { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: .6rem; }

        .pill {
            padding: .25rem .75rem;
            border-radius: 100px;
            font-size: .75rem;
            font-weight: 500;
            background: var(--bg-card2);
            border: 1px solid var(--border);
            color: var(--text-muted);
        }
        .pill-intensitat[data-level="Alta"]    { border-color: rgba(239,68,68,.4);  color: var(--red);   background: rgba(239,68,68,.08);  }
        .pill-intensitat[data-level="Mitjana"] { border-color: rgba(245,158,11,.4); color: var(--amber); background: rgba(245,158,11,.08); }
        .pill-intensitat[data-level="Baixa"]   { border-color: rgba(34,197,94,.4);  color: var(--green); background: rgba(34,197,94,.08);  }

        .card-divider { height: 1px; background: var(--border); margin: 0 1.4rem; }

        .card-bottom {
            padding: .9rem 1.4rem 1.2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .6rem;
        }

        .card-horari { font-size: .85rem; color: var(--text-muted); }
        .card-horari strong { font-size: 1rem; color: var(--text); }

        .card-actions { display: flex; gap: .5rem; align-items: center; }

        .btn-ficha {
            padding: .4rem .9rem;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text-muted);
            font-family: 'DM Sans', sans-serif;
            font-size: .8rem;
            cursor: pointer;
            text-decoration: none;
            transition: all .2s;
        }
        .btn-ficha:hover { border-color: var(--accent); color: var(--accent); }

        .btn-inscriure {
            padding: .4rem 1rem;
            border-radius: 8px;
            border: none;
            background: var(--accent);
            color: #000;
            font-family: 'DM Sans', sans-serif;
            font-weight: 600;
            font-size: .82rem;
            cursor: pointer;
            transition: background .2s, transform .15s;
        }
        .btn-inscriure:hover { background: var(--accent-dim); transform: scale(1.03); }

        .btn-ja-inscrit {
            padding: .4rem 1rem;
            border-radius: 8px;
            border: 1px solid rgba(34,197,94,.3);
            background: rgba(34,197,94,.1);
            color: var(--green);
            font-family: 'DM Sans', sans-serif;
            font-weight: 600;
            font-size: .82rem;
            cursor: default;
        }

        @media (max-width: 640px) {
            .topbar { padding: 0 1rem; }
            .main-wrap { padding: 1.5rem 1rem 3rem; }
            .classes-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- TOPBAR -->
<nav class="topbar">
    <a class="logo" href="dashboard.php">Projecte Gimnàs</a>
    <div class="topbar-right">
        <div class="topbar-user">
            <div class="avatar"><?= strtoupper(substr($user_name, 0, 1)) ?></div>
            Benvingut, <span><?= htmlspecialchars($user_name) ?></span>
        </div>
        <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
            <a href="admin.php" class="btn-nav admin">Admin</a>
        <?php endif; ?>
        <a href="logout.php" class="btn-nav logout">Sortir</a>
    </div>
</nav>

<div class="main-wrap">

    <!-- HEADER -->
    <div class="dash-header">
        <h1>Les teves <em>classes</em></h1>
        <p>Inscriu-te a les classes que vulguis i consulta la fitxa tècnica de cada una.</p>
    </div>

    <!-- FEEDBACK -->
    <?php if ($missatge): ?>
    <div class="feedback <?= $missatge_tipus ?>">
        <?= $missatge_tipus === 'success' ? '✅' : '⚠️' ?>
        <?= htmlspecialchars($missatge) ?>
    </div>
    <?php endif; ?>

    <!-- STATS -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-label">Total classes</div>
            <div class="stat-val"><?= count($totes_classes) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Les meves inscripcions</div>
            <div class="stat-val"><?= count($inscripcions_usuari) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Categories</div>
            <div class="stat-val"><?= count($classes_per_categoria) ?></div>
        </div>
    </div>

    <!-- FILTRES -->
    <div class="filter-row">
        <button class="filter-btn active" onclick="filtrar('tots', this)">Totes</button>
        <?php foreach (array_keys($classes_per_categoria) as $cat): ?>
            <button class="filter-btn" onclick="filtrar('<?= htmlspecialchars($cat) ?>', this)">
                <?= ($icones_categoria[$cat] ?? '') ?> <?= htmlspecialchars($cat) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- CLASSES PER CATEGORIA -->
    <?php foreach ($classes_per_categoria as $categoria => $classes): ?>
    <section class="categoria-section" data-categoria="<?= htmlspecialchars($categoria) ?>">

        <div class="categoria-header">
            <span><?= $icones_categoria[$categoria] ?? '🏅' ?></span>
            <h2><?= htmlspecialchars($categoria) ?></h2>
            <div class="categoria-linia"></div>
        </div>

        <div class="classes-grid">
            <?php foreach ($classes as $classe): ?>
            <?php $inscrit = in_array($classe['id_classe'], $inscripcions_usuari); ?>
            <div class="classe-card <?= $inscrit ? 'inscrit' : '' ?>">

                <div class="card-top">
                    <?php if ($inscrit): ?>
                        <span class="card-badge-inscrit">✓ Inscrit</span>
                    <?php endif; ?>

                    <div class="card-nom"><?= htmlspecialchars($classe['nom']) ?></div>

                    <div class="card-tecnic">
                        Tècnic: <span>
                            <?= $classe['nom_tècnic'] ? htmlspecialchars($classe['nom_tècnic']) : 'Virtual' ?>
                        </span>
                    </div>

                    <div class="card-pills">
                        <span class="pill">⏱ <?= (int)$classe['durada'] ?> min</span>
                        <span class="pill">📍 <?= htmlspecialchars($classe['estudi']) ?></span>
                        <span class="pill pill-intensitat" data-level="<?= htmlspecialchars($classe['intensitat']) ?>">
                            <?= htmlspecialchars($classe['intensitat']) ?>
                        </span>
                    </div>
                </div>

                <div class="card-divider"></div>

                <div class="card-bottom">
                    <div class="card-horari">
                        Horari <strong><?= htmlspecialchars($classe['horari']) ?></strong>
                    </div>
                    <div class="card-actions">
                        <a href="classes/clases.php?id=<?= (int)$classe['id_classe'] ?>" class="btn-ficha">
                            Fitxa →
                        </a>

                        <?php if ($inscrit): ?>
                            <span class="btn-ja-inscrit">✓ Inscrit</span>
                        <?php else: ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id_classe" value="<?= (int)$classe['id_classe'] ?>">
                                <button type="submit" name="inscriure" class="btn-inscriure">
                                    Inscriure'm
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
            <?php endforeach; ?>
        </div>

    </section>
    <?php endforeach; ?>

</div>

<script>
function filtrar(categoria, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.categoria-section').forEach(sec => {
        sec.style.display = (categoria === 'tots' || sec.dataset.categoria === categoria) ? '' : 'none';
    });
}

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
