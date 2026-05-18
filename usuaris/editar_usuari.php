<?php
// usuaris/editar_usuari.php
require_once '../auth.php';
requireAdmin();

require_once '../config.php';
require_once '../csrf.php';
csrf_generar();

$rols_ok = ['usuari', 'admin'];

// Validar ID
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header('Location: usuaris.php');
    exit;
}

$id_usuari = (int) $_GET['id'];

// READ: Obtenir l'usuari actual
$stmt = $pdo->prepare("SELECT id_usuari, nom_usuari, email, rol, actiu FROM usuaris WHERE id_usuari = ? LIMIT 1");
$stmt->execute([$id_usuari]);
$usuari = $stmt->fetch();

if (!$usuari) {
    header('Location: usuaris.php');
    exit;
}

$errors         = [];
$missatge       = '';
$missatge_tipus = '';

// =========================================================
// UPDATE: Modificar l'usuari
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['accio'])
    && $_POST['accio'] === 'editar_usuari'
) {
    csrf_verificar();
    $nom_usuari      = trim($_POST['nom_usuari']      ?? '');
    $email           = trim($_POST['email']           ?? '');
    $nova_contrasenya= trim($_POST['nova_contrasenya']?? '');
    $confirmar       = trim($_POST['confirmar']       ?? '');
    $rol             = trim($_POST['rol']             ?? '');
    $actiu           = isset($_POST['actiu']) ? 1 : 0;

    // --- Validació ---

    // Nom d'usuari: obligatori, 3-50 caràcters, únic (excepte ell mateix)
    if ($nom_usuari === '') {
        $errors['nom_usuari'] = 'El nom d\'usuari és obligatori.';
    } elseif (mb_strlen($nom_usuari) < 3) {
        $errors['nom_usuari'] = 'El nom d\'usuari ha de tenir almenys 3 caràcters.';
    } elseif (mb_strlen($nom_usuari) > 50) {
        $errors['nom_usuari'] = 'El nom d\'usuari no pot superar els 50 caràcters.';
    } elseif (!preg_match('/^[a-zA-Z0-9._\-àáèéíïòóúüçÀÁÈÉÍÏÒÓÚÜÇ]+$/', $nom_usuari)) {
        $errors['nom_usuari'] = 'El nom d\'usuari només pot contenir lletres, números, punts i guions.';
    } else {
        $stmtChk = $pdo->prepare("SELECT id_usuari FROM usuaris WHERE nom_usuari = ? AND id_usuari != ?");
        $stmtChk->execute([$nom_usuari, $id_usuari]);
        if ($stmtChk->fetch()) {
            $errors['nom_usuari'] = 'Aquest nom d\'usuari ja existeix.';
        }
    }

    // Email: opcional, format vàlid i únic (excepte ell mateix)
    if ($email !== '') {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El format del correu electrònic no és vàlid.';
        } elseif (mb_strlen($email) > 100) {
            $errors['email'] = 'El correu no pot superar els 100 caràcters.';
        } else {
            $stmtChk = $pdo->prepare("SELECT id_usuari FROM usuaris WHERE email = ? AND id_usuari != ?");
            $stmtChk->execute([$email, $id_usuari]);
            if ($stmtChk->fetch()) {
                $errors['email'] = 'Aquest correu electrònic ja està registrat per un altre usuari.';
            }
        }
    }

    // Contrasenya: opcional — només es canvia si s'omple
    if ($nova_contrasenya !== '') {
        if (mb_strlen($nova_contrasenya) < 6) {
            $errors['nova_contrasenya'] = 'La nova contrasenya ha de tenir almenys 6 caràcters.';
        } elseif (mb_strlen($nova_contrasenya) > 100) {
            $errors['nova_contrasenya'] = 'La contrasenya no pot superar els 100 caràcters.';
        } elseif ($confirmar === '') {
            $errors['confirmar'] = 'Has de confirmar la nova contrasenya.';
        } elseif ($confirmar !== $nova_contrasenya) {
            $errors['confirmar'] = 'Les contrasenyes no coincideixen.';
        }
    }

    // Rol: obligatori, llista blanca
    if ($rol === '') {
        $errors['rol'] = 'Has de seleccionar un rol.';
    } elseif (!in_array($rol, $rols_ok, true)) {
        $errors['rol'] = 'El rol seleccionat no és vàlid.';
    }

    // --- UPDATE si no hi ha errors ---
    if (empty($errors)) {
        $email_db = $email !== '' ? $email : null;

        if ($nova_contrasenya !== '') {
            // Actualitzar amb nova contrasenya
            $hash = password_hash($nova_contrasenya, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare(
                "UPDATE usuaris
                 SET nom_usuari = ?, email = ?, contrasenya = ?, rol = ?, actiu = ?
                 WHERE id_usuari = ?"
            );
            $stmt->execute([$nom_usuari, $email_db, $hash, $rol, $actiu, $id_usuari]);
        } else {
            // Actualitzar sense canviar la contrasenya
            $stmt = $pdo->prepare(
                "UPDATE usuaris
                 SET nom_usuari = ?, email = ?, rol = ?, actiu = ?
                 WHERE id_usuari = ?"
            );
            $stmt->execute([$nom_usuari, $email_db, $rol, $actiu, $id_usuari]);
        }

        // Refrescar dades
        $stmt = $pdo->prepare("SELECT id_usuari, nom_usuari, email, rol, actiu FROM usuaris WHERE id_usuari = ? LIMIT 1");
        $stmt->execute([$id_usuari]);
        $usuari = $stmt->fetch();

        $missatge       = 'Usuari "' . htmlspecialchars($usuari['nom_usuari']) . '" actualitzat correctament!';
        $missatge_tipus = 'success';
    } else {
        $missatge       = 'Corregeix els errors marcats al formulari.';
        $missatge_tipus = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar usuari — Projecte Gimnàs</title>
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
            --purple:     #a855f7;
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
            font-size: .85rem; text-decoration: none; transition: all .2s;
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
        .page-wrap { max-width: 760px; margin: 0 auto; padding: 2.5rem 1.5rem 4rem; }

        /* BREADCRUMB */
        .breadcrumb {
            display: flex; align-items: center; gap: .5rem;
            font-size: .82rem; color: var(--text-muted); margin-bottom: 1.8rem;
        }
        .breadcrumb a { color: var(--text-muted); text-decoration: none; }
        .breadcrumb a:hover { color: var(--accent); }
        .breadcrumb .current { color: var(--text); }

        /* HEADER */
        .page-header { margin-bottom: 1.5rem; }
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

        /* INFO USUARI */
        .user-info-bar {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1rem 1.4rem;
            margin-bottom: 1.5rem;
            display: flex; align-items: center; gap: 1rem;
            flex-wrap: wrap;
        }
        .user-avatar-lg {
            width: 46px; height: 46px; border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), #7c3aed);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 1.1rem; color: #fff; flex-shrink: 0;
        }
        .user-info-text { flex: 1; }
        .user-info-text strong { font-size: 1rem; color: var(--text); }
        .user-info-text span   { font-size: .82rem; color: var(--text-muted); display: block; }
        .badge-rol {
            display: inline-flex; align-items: center; gap: .3rem;
            padding: .22rem .75rem; border-radius: 100px;
            font-size: .75rem; font-weight: 600;
        }
        .badge-rol.admin  { background: rgba(255,107,53,.12); border: 1px solid rgba(255,107,53,.35); color: var(--accent); }
        .badge-rol.usuari { background: rgba(168,85,247,.12); border: 1px solid rgba(168,85,247,.35); color: var(--purple); }

        /* FORM CARD */
        .form-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            margin-bottom: 1.2rem;
        }
        .form-card-head {
            padding: 1.1rem 1.6rem;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: .7rem;
        }
        .form-card-head h2 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.1rem; letter-spacing: 1.5px;
        }
        .form-card-body { padding: 1.4rem 1.6rem; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.1rem; }
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
            border-radius: 8px; padding: .68rem .9rem;
            color: var(--text); font-family: 'DM Sans', sans-serif;
            font-size: .9rem; transition: border-color .2s; width: 100%;
        }
        .form-input:focus, .form-select:focus { outline: none; border-color: var(--accent); }
        .form-select option { background: #16161f; }

        .input-error { border-color: var(--red) !important; background: rgba(239,68,68,.05) !important; }
        .form-error-server { font-size: .76rem; color: var(--red); display: block; margin-top: .2rem; }

        /* Toggle actiu */
        .toggle-group {
            display: flex; align-items: center; gap: .8rem;
            padding: .8rem 1rem;
            background: var(--bg-card2);
            border: 1px solid var(--border);
            border-radius: 8px;
        }
        .toggle-group label { font-size: .9rem; color: var(--text); cursor: pointer; flex: 1; }
        .toggle-group small  { font-size: .78rem; color: var(--text-muted); display: block; }

        .toggle {
            position: relative; width: 42px; height: 24px; flex-shrink: 0;
        }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute; inset: 0;
            background: var(--border); border-radius: 100px;
            cursor: pointer; transition: background .2s;
        }
        .toggle-slider::before {
            content: ''; position: absolute;
            width: 18px; height: 18px; border-radius: 50%;
            background: #fff; top: 3px; left: 3px;
            transition: transform .2s;
        }
        .toggle input:checked + .toggle-slider { background: var(--green); }
        .toggle input:checked + .toggle-slider::before { transform: translateX(18px); }

        /* Força contrasenya */
        .strength-bar { height: 4px; background: var(--border); border-radius: 100px; overflow: hidden; margin-top: .4rem; }
        .strength-fill { height: 100%; border-radius: 100px; transition: width .3s, background .3s; width: 0%; }
        .strength-text { font-size: .72rem; color: var(--text-muted); margin-top: .25rem; }

        /* Footer */
        .form-card-footer {
            padding: 1.1rem 1.6rem;
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

        @media (max-width: 600px) {
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
        <a href="usuaris.php" class="btn-nav">← Usuaris</a>
        <a href="../logout.php" class="btn-nav logout">Tancar sessió</a>
    </div>
</nav>

<!-- BANNER -->
<div class="admin-banner">
    🛠️ Mode administrador — Editar usuari
</div>

<div class="page-wrap">

    <!-- BREADCRUMB -->
    <nav class="breadcrumb">
        <a href="../dashboard.php">Dashboard</a>
        <span>/</span>
        <a href="usuaris.php">Usuaris</a>
        <span>/</span>
        <span class="current">Editar: <?= htmlspecialchars($usuari['nom_usuari']) ?></span>
    </nav>

    <!-- HEADER -->
    <div class="page-header">
        <h1>Editar <em><?= htmlspecialchars($usuari['nom_usuari']) ?></em></h1>
        <p>Modifica les dades de l'usuari. Els camps de contrasenya es poden deixar buits si no es vol canviar.</p>
    </div>

    <!-- INFO USUARI ACTUAL -->
    <div class="user-info-bar">
        <div class="user-avatar-lg"><?= strtoupper(substr($usuari['nom_usuari'], 0, 1)) ?></div>
        <div class="user-info-text">
            <strong><?= htmlspecialchars($usuari['nom_usuari']) ?></strong>
            <span>#<?= (int)$usuari['id_usuari'] ?> · <?= $usuari['email'] ? htmlspecialchars($usuari['email']) : 'Sense email' ?></span>
        </div>
        <span class="badge-rol <?= $usuari['rol'] ?>">
            <?= $usuari['rol'] === 'admin' ? '🛠️' : '👤' ?>
            <?= htmlspecialchars($usuari['rol']) ?>
        </span>
    </div>

    <!-- FEEDBACK -->
    <?php if ($missatge): ?>
    <div class="feedback <?= $missatge_tipus ?>">
        <?= $missatge_tipus === 'success' ? '✅' : '❌' ?>
        <?= htmlspecialchars($missatge) ?>
    </div>
    <?php endif; ?>

    <form method="POST" id="formEditar">
        <input type="hidden" name="accio" value="editar_usuari">
        <?= csrf_camp() ?>

        <!-- DADES PRINCIPALS -->
        <div class="form-card">
            <div class="form-card-head"><span>👤</span><h2>Dades principals</h2></div>
            <div class="form-card-body">
                <div class="form-grid">

                    <!-- Nom usuari -->
                    <div class="form-group full">
                        <label class="form-label" for="nom_usuari">Nom d'usuari *</label>
                        <input class="form-input <?= isset($errors['nom_usuari']) ? 'input-error' : '' ?>"
                               type="text" id="nom_usuari" name="nom_usuari"
                               maxlength="50"
                               value="<?= htmlspecialchars($usuari['nom_usuari']) ?>">
                        <?php if (isset($errors['nom_usuari'])): ?>
                            <span class="form-error-server">⚠ <?= htmlspecialchars($errors['nom_usuari']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Email -->
                    <div class="form-group full">
                        <label class="form-label" for="email">
                            Email <small>(opcional)</small>
                        </label>
                        <input class="form-input <?= isset($errors['email']) ? 'input-error' : '' ?>"
                               type="email" id="email" name="email"
                               maxlength="100"
                               placeholder="Deixa buit per eliminar el correu"
                               value="<?= htmlspecialchars($usuari['email'] ?? '') ?>">
                        <?php if (isset($errors['email'])): ?>
                            <span class="form-error-server">⚠ <?= htmlspecialchars($errors['email']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Rol -->
                    <div class="form-group">
                        <label class="form-label" for="rol">Rol *</label>
                        <select class="form-select <?= isset($errors['rol']) ? 'input-error' : '' ?>"
                                id="rol" name="rol">
                            <option value="usuari" <?= $usuari['rol'] === 'usuari' ? 'selected' : '' ?>>👤 Usuari</option>
                            <option value="admin"  <?= $usuari['rol'] === 'admin'  ? 'selected' : '' ?>>🛠️ Administrador</option>
                        </select>
                        <?php if (isset($errors['rol'])): ?>
                            <span class="form-error-server">⚠ <?= htmlspecialchars($errors['rol']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Actiu -->
                    <div class="form-group">
                        <label class="form-label">Estat del compte</label>
                        <div class="toggle-group">
                            <label for="actiu">
                                Compte actiu
                                <small>L'usuari pot iniciar sessió</small>
                            </label>
                            <label class="toggle">
                                <input type="checkbox" id="actiu" name="actiu"
                                       <?= (int)$usuari['actiu'] === 1 ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- CANVI DE CONTRASENYA -->
        <div class="form-card">
            <div class="form-card-head"><span>🔒</span><h2>Canviar contrasenya</h2></div>
            <div class="form-card-body">
                <div class="form-grid">

                    <div class="form-group">
                        <label class="form-label" for="nova_contrasenya">
                            Nova contrasenya <small>(opcional)</small>
                        </label>
                        <input class="form-input <?= isset($errors['nova_contrasenya']) ? 'input-error' : '' ?>"
                               type="password" id="nova_contrasenya" name="nova_contrasenya"
                               placeholder="Deixa buit per no canviar-la">
                        <?php if (isset($errors['nova_contrasenya'])): ?>
                            <span class="form-error-server">⚠ <?= htmlspecialchars($errors['nova_contrasenya']) ?></span>
                        <?php endif; ?>
                        <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                        <div class="strength-text" id="strengthText"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirmar">Confirmar nova contrasenya</label>
                        <input class="form-input <?= isset($errors['confirmar']) ? 'input-error' : '' ?>"
                               type="password" id="confirmar" name="confirmar"
                               placeholder="Repeteix la nova contrasenya">
                        <?php if (isset($errors['confirmar'])): ?>
                            <span class="form-error-server">⚠ <?= htmlspecialchars($errors['confirmar']) ?></span>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

            <div class="form-card-footer">
                <a href="usuaris.php" class="btn-cancel">← Cancel·lar</a>
                <button type="submit" class="btn-submit" id="btnSubmit">💾 Desar canvis</button>
            </div>
        </div>

    </form>

</div>

<script>
// Força contrasenya
document.getElementById('nova_contrasenya')?.addEventListener('input', function() {
    const v = this.value;
    if (v === '') {
        document.getElementById('strengthFill').style.width = '0%';
        document.getElementById('strengthText').textContent = '';
        return;
    }
    let score = 0;
    if (v.length >= 6)  score++;
    if (v.length >= 10) score++;
    if (/[A-Z]/.test(v) && /[a-z]/.test(v)) score++;
    if (/\d/.test(v))   score++;
    if (/[^a-zA-Z0-9]/.test(v)) score++;

    const nivells = [
        { pct:'0%',   color:'var(--border)', label:'' },
        { pct:'25%',  color:'var(--red)',    label:'Molt feble' },
        { pct:'50%',  color:'var(--amber)',  label:'Feble' },
        { pct:'75%',  color:'var(--amber)',  label:'Acceptable' },
        { pct:'90%',  color:'var(--green)',  label:'Forta' },
        { pct:'100%', color:'var(--green)',  label:'Molt forta' },
    ];
    const n = nivells[Math.min(score, 5)];
    document.getElementById('strengthFill').style.width      = n.pct;
    document.getElementById('strengthFill').style.background = n.color;
    document.getElementById('strengthText').textContent      = n.label;
    document.getElementById('strengthText').style.color      = n.color;
});

// Feedback auto-ocultar
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
