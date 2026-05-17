<?php
// classes/editar_classe.php
require_once '../auth.php';
requireAdmin();

require_once '../config.php';

// Llistes de valors permesos
$categories_ok  = ['Força', 'Cardiovascular', 'Cos i ment', 'Virtual', 'Aquàtica'];
$intensitats_ok = ['Baixa', 'Mitjana', 'Alta'];
$estudis_ok     = ['Estudi 1', 'Estudi 2', 'Estudi 3', 'Piscina'];

// Validar ID
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header('Location: ../dashboard.php');
    exit;
}

$id_classe = (int) $_GET['id'];

// READ: Obtenir la classe actual
$stmt = $pdo->prepare("SELECT * FROM classes WHERE id_classe = ? LIMIT 1");
$stmt->execute([$id_classe]);
$classe = $stmt->fetch();

if (!$classe) {
    header('Location: ../dashboard.php');
    exit;
}

$errors  = [];
$missatge       = '';
$missatge_tipus = '';

// =========================================================
// UPDATE: Modificar la classe
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accio']) && $_POST['accio'] === 'editar_classe') {

    $nom        = trim($_POST['nom']        ?? '');
    $nom_tecnic = trim($_POST['nom_tecnic'] ?? '');
    $categoria  = trim($_POST['categoria']  ?? '');
    $intensitat = trim($_POST['intensitat'] ?? '');
    $durada_raw = trim($_POST['durada']     ?? '');
    $places_raw = trim($_POST['places']     ?? '');
    $estudi     = trim($_POST['estudi']     ?? '');
    $horari     = trim($_POST['horari']     ?? '');

    // --- Validació ---

    if ($nom === '') {
        $errors['nom'] = 'El nom de la classe és obligatori.';
    } elseif (mb_strlen($nom) < 2) {
        $errors['nom'] = 'El nom ha de tenir almenys 2 caràcters.';
    } elseif (mb_strlen($nom) > 100) {
        $errors['nom'] = 'El nom no pot superar els 100 caràcters.';
    } elseif (is_numeric($nom)) {
        $errors['nom'] = 'El nom no pot ser només números.';
    }

    if ($nom_tecnic !== '') {
        if (mb_strlen($nom_tecnic) < 2) {
            $errors['nom_tecnic'] = 'El nom del tècnic ha de tenir almenys 2 caràcters.';
        } elseif (mb_strlen($nom_tecnic) > 100) {
            $errors['nom_tecnic'] = 'El nom del tècnic no pot superar els 100 caràcters.';
        } elseif (is_numeric($nom_tecnic)) {
            $errors['nom_tecnic'] = 'El nom del tècnic no pot ser només números.';
        }
    }

    if ($categoria === '') {
        $errors['categoria'] = 'Has de seleccionar una categoria.';
    } elseif (!in_array($categoria, $categories_ok, true)) {
        $errors['categoria'] = 'La categoria seleccionada no és vàlida.';
    }

    if ($intensitat === '') {
        $errors['intensitat'] = 'Has de seleccionar la intensitat.';
    } elseif (!in_array($intensitat, $intensitats_ok, true)) {
        $errors['intensitat'] = 'La intensitat seleccionada no és vàlida.';
    }

    if ($durada_raw === '') {
        $errors['durada'] = 'La durada és obligatòria.';
    } elseif (!ctype_digit($durada_raw)) {
        $errors['durada'] = 'La durada ha de ser un número enter positiu.';
    } elseif ((int)$durada_raw < 1 || (int)$durada_raw > 180) {
        $errors['durada'] = 'La durada ha d\'estar entre 1 i 180 minuts.';
    }

    if ($places_raw === '') {
        $errors['places'] = 'El nombre de places és obligatori.';
    } elseif (!ctype_digit($places_raw)) {
        $errors['places'] = 'Les places han de ser un número enter positiu.';
    } elseif ((int)$places_raw < 1 || (int)$places_raw > 500) {
        $errors['places'] = 'Les places han d\'estar entre 1 i 500.';
    }

    if ($estudi === '') {
        $errors['estudi'] = 'Has de seleccionar un espai.';
    } elseif (!in_array($estudi, $estudis_ok, true)) {
        $errors['estudi'] = 'L\'espai seleccionat no és vàlid.';
    }

    if ($horari === '') {
        $errors['horari'] = 'L\'horari és obligatori.';
    } elseif (!preg_match('/^\d{2}:\d{2}$/', $horari)) {
        $errors['horari'] = 'L\'horari ha de tenir el format HH:MM.';
    } else {
        [$h, $m] = explode(':', $horari);
        if ((int)$h > 23 || (int)$m > 59) {
            $errors['horari'] = 'L\'horari introduït no és una hora vàlida.';
        }
    }

    // --- UPDATE si no hi ha errors ---
    if (empty($errors)) {
        $nom_tecnic_db = $nom_tecnic !== '' ? $nom_tecnic : null;
        $durada        = (int)$durada_raw;
        $places        = (int)$places_raw;

        $stmt = $pdo->prepare(
            "UPDATE classes
             SET nom = ?, `nom_tècnic` = ?, durada = ?, categoria = ?,
                 intensitat = ?, estudi = ?, horari = ?, places = ?
             WHERE id_classe = ?"
        );
        $stmt->execute([
            $nom, $nom_tecnic_db, $durada, $categoria,
            $intensitat, $estudi, $horari, $places,
            $id_classe
        ]);

        // Refrescar les dades de la classe
        $stmt = $pdo->prepare("SELECT * FROM classes WHERE id_classe = ? LIMIT 1");
        $stmt->execute([$id_classe]);
        $classe = $stmt->fetch();

        $missatge       = 'Classe "' . htmlspecialchars($classe['nom']) . '" actualitzada correctament!';
        $missatge_tipus = 'success';
    } else {
        $missatge       = 'Corregeix els errors marcats al formulari.';
        $missatge_tipus = 'error';
    }
}

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
    <title>Editar <?= htmlspecialchars($classe['nom']) ?> — Projecte Gimnàs</title>
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
            --accent:     #ff6b35;
            --accent-dim: #cc5529;
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
        }

        /* TOPBAR */
        .topbar {
            position: sticky; top: 0; z-index: 100;
            background: rgba(10,10,15,.9);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255,107,53,.35);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 2rem; height: 64px;
        }
        .logo {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.8rem; letter-spacing: 3px;
            color: var(--accent); text-decoration: none;
        }
        .topbar-right { display: flex; align-items: center; gap: 1.2rem; }
        .topbar-admin-badge {
            background: rgba(255,107,53,.15);
            border: 1px solid rgba(255,107,53,.4);
            color: var(--accent); border-radius: 100px;
            font-size: .72rem; font-weight: 700;
            padding: .2rem .7rem; letter-spacing: 1px; text-transform: uppercase;
        }
        .btn-nav {
            padding: .4rem 1rem; border: 1px solid var(--border);
            border-radius: 8px; background: transparent;
            color: var(--text-muted); font-family: 'DM Sans', sans-serif;
            font-size: .85rem; text-decoration: none; transition: all .2s; cursor: pointer;
        }
        .btn-nav:hover        { border-color: var(--accent); color: var(--accent); }
        .btn-nav.logout       { border-color: var(--red); background: var(--red); color: #fff; }
        .btn-nav.logout:hover { background: #c53030; border-color: #c53030; }

        /* BANNER */
        .admin-banner {
            background: linear-gradient(90deg, rgba(255,107,53,.12), rgba(255,107,53,.05));
            border-bottom: 1px solid rgba(255,107,53,.3);
            padding: .6rem 2rem;
            font-size: .85rem; color: var(--accent); font-weight: 600;
            display: flex; align-items: center; gap: .6rem;
        }

        /* LAYOUT */
        .page-wrap { max-width: 800px; margin: 0 auto; padding: 2.5rem 1.5rem 4rem; }

        /* BREADCRUMB */
        .breadcrumb {
            display: flex; align-items: center; gap: .5rem;
            font-size: .82rem; color: var(--text-muted); margin-bottom: 1.8rem;
        }
        .breadcrumb a { color: var(--text-muted); text-decoration: none; }
        .breadcrumb a:hover { color: var(--accent); }
        .breadcrumb .current { color: var(--text); }

        /* HEADER */
        .page-header { margin-bottom: 2rem; }
        .page-header h1 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(2rem, 5vw, 3rem);
            letter-spacing: 2px; line-height: 1;
        }
        .page-header h1 em { color: var(--accent); font-style: normal; }
        .page-header p { margin-top: .4rem; color: var(--text-muted); font-size: .9rem; }

        /* FEEDBACK */
        .feedback {
            padding: 1rem 1.4rem; border-radius: var(--radius);
            margin-bottom: 1.5rem; font-weight: 500;
            display: flex; align-items: center; gap: .7rem;
            animation: slideDown .3s ease;
        }
        @keyframes slideDown {
            from { opacity:0; transform:translateY(-8px); }
            to   { opacity:1; transform:translateY(0); }
        }
        .feedback.success { background: rgba(34,197,94,.12);  border: 1px solid rgba(34,197,94,.3);  color: var(--green); }
        .feedback.error   { background: rgba(239,68,68,.12);  border: 1px solid rgba(239,68,68,.3);  color: var(--red);   }

        /* FORMULARI */
        .form-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }
        .form-card-head {
            padding: 1.2rem 1.6rem;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: .7rem;
        }
        .form-card-head h2 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.2rem; letter-spacing: 1.5px;
        }
        .form-card-body { padding: 1.6rem; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; }
        .form-group { display: flex; flex-direction: column; gap: .4rem; }
        .form-group.full { grid-column: 1 / -1; }

        .form-label {
            font-size: .76rem; text-transform: uppercase;
            letter-spacing: 1px; color: var(--text-muted); font-weight: 600;
        }
        .form-label small {
            font-size: .72rem; text-transform: none;
            letter-spacing: 0; opacity: .7; font-weight: 400;
        }
        .form-input, .form-select {
            background: var(--bg-card2); border: 1px solid var(--border);
            border-radius: 8px; padding: .7rem .9rem;
            color: var(--text); font-family: 'DM Sans', sans-serif;
            font-size: .9rem; transition: border-color .2s; width: 100%;
        }
        .form-input:focus, .form-select:focus { outline: none; border-color: var(--accent); }
        .form-select option { background: #16161f; }

        .input-error { border-color: var(--red) !important; background: rgba(239,68,68,.05) !important; }
        .form-error-server { font-size: .76rem; color: var(--red); display: block; margin-top: .2rem; }

        /* FOOTER DEL FORM */
        .form-card-footer {
            padding: 1.2rem 1.6rem;
            border-top: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
            gap: 1rem; flex-wrap: wrap;
        }
        .btn-cancel {
            padding: .6rem 1.4rem; border-radius: 8px;
            border: 1px solid var(--border); background: transparent;
            color: var(--text-muted); font-family: 'DM Sans', sans-serif;
            font-size: .9rem; text-decoration: none; transition: all .2s;
        }
        .btn-cancel:hover { border-color: var(--red); color: var(--red); }

        .btn-submit {
            padding: .6rem 1.8rem; border-radius: 8px; border: none;
            background: var(--accent); color: #fff;
            font-family: 'DM Sans', sans-serif; font-weight: 700;
            font-size: .9rem; cursor: pointer; transition: background .2s, transform .15s;
        }
        .btn-submit:hover { background: var(--accent-dim); transform: scale(1.02); }
        .btn-submit:disabled { opacity: .5; cursor: default; transform: none; }

        /* INFO ACTUAL */
        .info-actual {
            background: var(--bg-card2);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1rem 1.4rem;
            margin-bottom: 1.5rem;
            font-size: .85rem;
            color: var(--text-muted);
            display: flex; align-items: center; gap: .6rem;
        }
        .info-actual strong { color: var(--text); }

        @media (max-width: 640px) {
            .topbar { padding: 0 1rem; }
            .page-wrap { padding: 1.5rem 1rem 3rem; }
            .form-grid { grid-template-columns: 1fr; }
            .form-group.full { grid-column: 1; }
        }
    </style>
</head>
<body>

<!-- TOPBAR -->
<nav class="topbar">
    <a class="logo" href="../dashboard.php">Projecte Gimnàs</a>
    <div class="topbar-right">
        <span class="topbar-admin-badge">Admin</span>
        <a href="../dashboard.php" class="btn-nav">← Dashboard</a>
        <a href="../logout.php" class="btn-nav logout">Tancar sessió</a>
    </div>
</nav>

<!-- BANNER -->
<div class="admin-banner">
    🛠️ Mode administrador — Editar classe
</div>

<div class="page-wrap">

    <!-- BREADCRUMB -->
    <nav class="breadcrumb">
        <a href="../dashboard.php">Dashboard</a>
        <span>/</span>
        <a href="../dashboard.php">Classes</a>
        <span>/</span>
        <span class="current">Editar: <?= htmlspecialchars($classe['nom']) ?></span>
    </nav>

    <!-- HEADER -->
    <div class="page-header">
        <h1>Editar <em><?= htmlspecialchars($classe['nom']) ?></em></h1>
        <p>Modifica les dades de la classe. Tots els camps marcats amb * són obligatoris.</p>
    </div>

    <!-- INFO ACTUAL -->
    <div class="info-actual">
        ℹ️ Editant la classe <strong>#<?= $id_classe ?> — <?= htmlspecialchars($classe['nom']) ?></strong>
        · Categoria: <strong><?= htmlspecialchars($classe['categoria']) ?></strong>
        · Inscrits actuals: <strong>
            <?php
            $stmtI = $pdo->prepare("SELECT COUNT(*) FROM reserves WHERE classe_id = ?");
            $stmtI->execute([$id_classe]);
            echo (int)$stmtI->fetchColumn();
            ?>
        </strong>
    </div>

    <!-- FEEDBACK -->
    <?php if ($missatge): ?>
    <div class="feedback <?= $missatge_tipus ?>">
        <?= $missatge_tipus === 'success' ? '✅' : '❌' ?>
        <?= htmlspecialchars($missatge) ?>
    </div>
    <?php endif; ?>

    <!-- FORMULARI -->
    <form method="POST" id="formEditar">
        <input type="hidden" name="accio" value="editar_classe">

        <div class="form-card">
            <div class="form-card-head">
                <span>📋</span>
                <h2>Dades de la classe</h2>
            </div>

            <div class="form-card-body">
                <div class="form-grid">

                    <!-- Nom -->
                    <div class="form-group full">
                        <label class="form-label" for="nom">Nom de la classe *</label>
                        <input class="form-input <?= isset($errors['nom']) ? 'input-error' : '' ?>"
                               type="text" id="nom" name="nom"
                               maxlength="100"
                               value="<?= htmlspecialchars($classe['nom']) ?>">
                        <?php if (isset($errors['nom'])): ?>
                            <span class="form-error-server">⚠ <?= htmlspecialchars($errors['nom']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Tècnic -->
                    <div class="form-group full">
                        <label class="form-label" for="nom_tecnic">
                            Nom del tècnic <small>(opcional — buit si és virtual)</small>
                        </label>
                        <input class="form-input <?= isset($errors['nom_tecnic']) ? 'input-error' : '' ?>"
                               type="text" id="nom_tecnic" name="nom_tecnic"
                               maxlength="100"
                               value="<?= htmlspecialchars($classe['nom_tècnic'] ?? '') ?>">
                        <?php if (isset($errors['nom_tecnic'])): ?>
                            <span class="form-error-server">⚠ <?= htmlspecialchars($errors['nom_tecnic']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Categoria -->
                    <div class="form-group">
                        <label class="form-label" for="categoria">Categoria *</label>
                        <select class="form-select <?= isset($errors['categoria']) ? 'input-error' : '' ?>"
                                id="categoria" name="categoria">
                            <?php foreach (['Força' => '🏋️', 'Cardiovascular' => '🏃', 'Cos i ment' => '🧘', 'Virtual' => '💻', 'Aquàtica' => '🏊'] as $cat => $ico): ?>
                            <option value="<?= $cat ?>" <?= $classe['categoria'] === $cat ? 'selected' : '' ?>>
                                <?= $ico ?> <?= $cat ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['categoria'])): ?>
                            <span class="form-error-server">⚠ <?= htmlspecialchars($errors['categoria']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Intensitat -->
                    <div class="form-group">
                        <label class="form-label" for="intensitat">Intensitat *</label>
                        <select class="form-select <?= isset($errors['intensitat']) ? 'input-error' : '' ?>"
                                id="intensitat" name="intensitat">
                            <?php foreach (['Baixa', 'Mitjana', 'Alta'] as $int): ?>
                            <option value="<?= $int ?>" <?= $classe['intensitat'] === $int ? 'selected' : '' ?>>
                                <?= $int ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['intensitat'])): ?>
                            <span class="form-error-server">⚠ <?= htmlspecialchars($errors['intensitat']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Durada -->
                    <div class="form-group">
                        <label class="form-label" for="durada">Durada (minuts) *</label>
                        <input class="form-input <?= isset($errors['durada']) ? 'input-error' : '' ?>"
                               type="number" id="durada" name="durada"
                               min="1" max="180"
                               value="<?= (int)$classe['durada'] ?>">
                        <?php if (isset($errors['durada'])): ?>
                            <span class="form-error-server">⚠ <?= htmlspecialchars($errors['durada']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Places -->
                    <div class="form-group">
                        <label class="form-label" for="places">Places *</label>
                        <input class="form-input <?= isset($errors['places']) ? 'input-error' : '' ?>"
                               type="number" id="places" name="places"
                               min="1" max="500"
                               value="<?= (int)$classe['places'] ?>">
                        <?php if (isset($errors['places'])): ?>
                            <span class="form-error-server">⚠ <?= htmlspecialchars($errors['places']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Estudi -->
                    <div class="form-group">
                        <label class="form-label" for="estudi">Estudi / Espai *</label>
                        <select class="form-select <?= isset($errors['estudi']) ? 'input-error' : '' ?>"
                                id="estudi" name="estudi">
                            <?php foreach (['Estudi 1', 'Estudi 2', 'Estudi 3', 'Piscina'] as $est): ?>
                            <option value="<?= $est ?>" <?= $classe['estudi'] === $est ? 'selected' : '' ?>>
                                <?= $est ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['estudi'])): ?>
                            <span class="form-error-server">⚠ <?= htmlspecialchars($errors['estudi']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Horari -->
                    <div class="form-group">
                        <label class="form-label" for="horari">Horari *</label>
                        <input class="form-input <?= isset($errors['horari']) ? 'input-error' : '' ?>"
                               type="time" id="horari" name="horari"
                               value="<?= htmlspecialchars($classe['horari']) ?>">
                        <?php if (isset($errors['horari'])): ?>
                            <span class="form-error-server">⚠ <?= htmlspecialchars($errors['horari']) ?></span>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

            <div class="form-card-footer">
                <a href="../dashboard.php" class="btn-cancel">← Cancel·lar</a>
                <button type="submit" class="btn-submit" id="btnSubmit">
                    💾 Desar canvis
                </button>
            </div>
        </div>
    </form>

</div>

<script>
// Feedback auto-ocultar (només èxit)
const feedback = document.querySelector('.feedback.success');
if (feedback) {
    setTimeout(() => {
        feedback.style.transition = 'opacity .5s';
        feedback.style.opacity = '0';
        setTimeout(() => feedback.remove(), 500);
    }, 4000);
}

// Evitar doble enviament
document.getElementById('formEditar')?.addEventListener('submit', function() {
    const btn = document.getElementById('btnSubmit');
    btn.disabled = true;
    btn.textContent = 'Desant...';
});
</script>
</body>
</html>
