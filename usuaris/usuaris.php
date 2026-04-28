<?php
// usuaris/usuaris.php
require_once '../auth.php';
requireAdmin();

require_once '../config.php';

$admin_name = $_SESSION['nom_usuari'] ?? 'Administrador';

// =========================================================
// Variables formulari
// =========================================================
$errors_nou     = [];
$old_nou        = [];
$missatge       = '';
$missatge_tipus = '';

$rols_ok = ['usuari', 'admin'];

// =========================================================
// CREATE: Nou usuari
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['accio'])
    && $_POST['accio'] === 'nou_usuari'
) {
    $nou_nom         = trim($_POST['nou_nom']         ?? '');
    $nou_email       = trim($_POST['nou_email']       ?? '');
    $nou_contrasenya = trim($_POST['nou_contrasenya'] ?? '');
    $nou_confirmar   = trim($_POST['nou_confirmar']   ?? '');
    $nou_rol         = trim($_POST['nou_rol']         ?? '');

    $old_nou = [
        'nou_nom'   => $nou_nom,
        'nou_email' => $nou_email,
        'nou_rol'   => $nou_rol,
    ];

    // --- Validació camp per camp ---

    // Nom d'usuari: obligatori, 3-50 caràcters, únic a la BD
    if ($nou_nom === '') {
        $errors_nou['nou_nom'] = 'El nom d\'usuari és obligatori.';
    } elseif (mb_strlen($nou_nom) < 3) {
        $errors_nou['nou_nom'] = 'El nom d\'usuari ha de tenir almenys 3 caràcters.';
    } elseif (mb_strlen($nou_nom) > 50) {
        $errors_nou['nou_nom'] = 'El nom d\'usuari no pot superar els 50 caràcters.';
    } elseif (!preg_match('/^[a-zA-Z0-9._\-àáèéíïòóúüçÀÁÈÉÍÏÒÓÚÜÇ]+$/', $nou_nom)) {
        $errors_nou['nou_nom'] = 'El nom d\'usuari només pot contenir lletres, números, punts i guions.';
    } else {
        $stmtChk = $pdo->prepare("SELECT id_usuari FROM usuaris WHERE nom_usuari = ?");
        $stmtChk->execute([$nou_nom]);
        if ($stmtChk->fetch()) {
            $errors_nou['nou_nom'] = 'Aquest nom d\'usuari ja existeix.';
        }
    }

    // Email: opcional, però si s'omple ha de ser vàlid i únic
    if ($nou_email !== '') {
        if (!filter_var($nou_email, FILTER_VALIDATE_EMAIL)) {
            $errors_nou['nou_email'] = 'El format del correu electrònic no és vàlid.';
        } elseif (mb_strlen($nou_email) > 100) {
            $errors_nou['nou_email'] = 'El correu no pot superar els 100 caràcters.';
        } else {
            $stmtChk = $pdo->prepare("SELECT id_usuari FROM usuaris WHERE email = ?");
            $stmtChk->execute([$nou_email]);
            if ($stmtChk->fetch()) {
                $errors_nou['nou_email'] = 'Aquest correu electrònic ja està registrat.';
            }
        }
    }

    // Contrasenya: obligatòria, mínim 6 caràcters
    if ($nou_contrasenya === '') {
        $errors_nou['nou_contrasenya'] = 'La contrasenya és obligatòria.';
    } elseif (mb_strlen($nou_contrasenya) < 6) {
        $errors_nou['nou_contrasenya'] = 'La contrasenya ha de tenir almenys 6 caràcters.';
    } elseif (mb_strlen($nou_contrasenya) > 100) {
        $errors_nou['nou_contrasenya'] = 'La contrasenya no pot superar els 100 caràcters.';
    }

    // Confirmar contrasenya
    if (!isset($errors_nou['nou_contrasenya'])) {
        if ($nou_confirmar === '') {
            $errors_nou['nou_confirmar'] = 'Has de confirmar la contrasenya.';
        } elseif ($nou_confirmar !== $nou_contrasenya) {
            $errors_nou['nou_confirmar'] = 'Les contrasenyes no coincideixen.';
        }
    }

    // Rol: obligatori, ha de ser de la llista blanca
    if ($nou_rol === '') {
        $errors_nou['nou_rol'] = 'Has de seleccionar un rol.';
    } elseif (!in_array($nou_rol, $rols_ok, true)) {
        $errors_nou['nou_rol'] = 'El rol seleccionat no és vàlid.';
    }

    // --- INSERT si no hi ha errors ---
    if (empty($errors_nou)) {
        $hash      = password_hash($nou_contrasenya, PASSWORD_BCRYPT);
        $email_db  = $nou_email !== '' ? $nou_email : null;

        $stmt = $pdo->prepare(
            "INSERT INTO usuaris (nom_usuari, email, contrasenya, rol, actiu)
             VALUES (?, ?, ?, ?, 1)"
        );
        $stmt->execute([$nou_nom, $email_db, $hash, $nou_rol]);

        $missatge       = 'Usuari "' . htmlspecialchars($nou_nom) . '" creat correctament!';
        $missatge_tipus = 'success';
        $old_nou        = [];
    } else {
        $missatge       = 'Corregeix els errors marcats al formulari.';
        $missatge_tipus = 'error';
    }
}

// =========================================================
// READ: Tots els usuaris
// =========================================================
$filtre_rol  = $_GET['rol']   ?? '';
$filtre_cerca= trim($_GET['cerca'] ?? '');

$sql    = "SELECT id_usuari, nom_usuari, email, rol, actiu FROM usuaris WHERE 1=1";
$params = [];

if ($filtre_rol !== '' && in_array($filtre_rol, $rols_ok, true)) {
    $sql     .= " AND rol = ?";
    $params[] = $filtre_rol;
}
if ($filtre_cerca !== '') {
    $sql     .= " AND (nom_usuari LIKE ? OR email LIKE ?)";
    $params[] = '%' . $filtre_cerca . '%';
    $params[] = '%' . $filtre_cerca . '%';
}

$sql .= " ORDER BY id_usuari DESC";

$stmt    = $pdo->prepare($sql);
$stmt->execute($params);
$usuaris = $stmt->fetchAll();

// Stats
$total_usuaris = (int)$pdo->query("SELECT COUNT(*) FROM usuaris")->fetchColumn();
$total_admins  = (int)$pdo->query("SELECT COUNT(*) FROM usuaris WHERE rol = 'admin'")->fetchColumn();
$total_actius  = (int)$pdo->query("SELECT COUNT(*) FROM usuaris WHERE actiu = 1")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuaris — Projecte Gimnàs</title>
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
        .topbar-user {
            display: flex; align-items: center; gap: .6rem;
            font-size: .9rem; color: var(--text-muted);
        }
        .topbar-user span { color: var(--text); font-weight: 500; }
        .avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), #7c3aed);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .85rem; color: #fff;
        }
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

        /* BANNER ADMIN */
        .admin-banner {
            background: linear-gradient(90deg, rgba(255,107,53,.12), rgba(255,107,53,.05));
            border-bottom: 1px solid rgba(255,107,53,.3);
            padding: .6rem 2rem;
            display: flex; align-items: center; justify-content: space-between;
            gap: 1rem; flex-wrap: wrap;
        }
        .admin-banner-left {
            display: flex; align-items: center; gap: .6rem;
            font-size: .85rem; color: var(--accent); font-weight: 600;
        }
        .btn-admin-action {
            display: flex; align-items: center; gap: .4rem;
            padding: .4rem 1rem; border-radius: 8px;
            border: 1px solid rgba(255,107,53,.4);
            background: rgba(255,107,53,.1); color: var(--accent);
            font-family: 'DM Sans', sans-serif; font-size: .82rem; font-weight: 600;
            cursor: pointer; text-decoration: none; transition: all .2s;
        }
        .btn-admin-action:hover { background: rgba(255,107,53,.2); border-color: var(--accent); }

        /* LAYOUT */
        .page-wrap { max-width: 1200px; margin: 0 auto; padding: 2.5rem 1.5rem 4rem; }

        /* BREADCRUMB */
        .breadcrumb {
            display: flex; align-items: center; gap: .5rem;
            font-size: .82rem; color: var(--text-muted); margin-bottom: 1.8rem;
        }
        .breadcrumb a { color: var(--text-muted); text-decoration: none; }
        .breadcrumb a:hover { color: var(--accent); }
        .breadcrumb .current { color: var(--text); }

        /* PAGE HEADER */
        .page-header { margin-bottom: 2rem; }
        .page-header h1 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(2.2rem, 5vw, 3.5rem);
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
        .feedback.warning { background: rgba(245,158,11,.12); border: 1px solid rgba(245,158,11,.3); color: var(--amber); }
        .feedback.error   { background: rgba(239,68,68,.12);  border: 1px solid rgba(239,68,68,.3);  color: var(--red);   }

        /* STATS */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1rem; margin-bottom: 2rem;
        }
        .stat-card {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 1.1rem 1.3rem;
        }
        .stat-label {
            font-size: .75rem; text-transform: uppercase;
            letter-spacing: 1.5px; color: var(--text-muted); margin-bottom: .3rem;
        }
        .stat-val {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 2rem; letter-spacing: 1px;
            color: var(--accent); line-height: 1;
        }

        /* TOOLBAR */
        .toolbar {
            display: flex; align-items: center; gap: .8rem;
            margin-bottom: 1.5rem; flex-wrap: wrap;
        }
        .search-wrap { position: relative; flex: 1; min-width: 200px; }
        .search-icon {
            position: absolute; left: .85rem; top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted); font-size: .9rem; pointer-events: none;
        }
        .search-input {
            width: 100%; background: var(--bg-card);
            border: 1px solid var(--border); border-radius: 8px;
            padding: .6rem .9rem .6rem 2.4rem;
            color: var(--text); font-family: 'DM Sans', sans-serif;
            font-size: .9rem; transition: border-color .2s;
        }
        .search-input:focus { outline: none; border-color: var(--accent); }
        .search-input::placeholder { color: var(--text-muted); }

        .filter-select {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: 8px; padding: .6rem .9rem;
            color: var(--text); font-family: 'DM Sans', sans-serif;
            font-size: .85rem; cursor: pointer; transition: border-color .2s;
        }
        .filter-select:focus { outline: none; border-color: var(--accent); }
        .filter-select option { background: var(--bg-card); }

        .btn-search {
            padding: .6rem 1.2rem; border-radius: 8px; border: none;
            background: var(--accent); color: #fff;
            font-family: 'DM Sans', sans-serif; font-weight: 600;
            font-size: .85rem; cursor: pointer; transition: background .2s;
        }
        .btn-search:hover { background: var(--accent-dim); }

        .btn-reset {
            padding: .6rem 1rem; border-radius: 8px;
            border: 1px solid var(--border); background: transparent;
            color: var(--text-muted); font-family: 'DM Sans', sans-serif;
            font-size: .85rem; text-decoration: none; transition: all .2s;
        }
        .btn-reset:hover { border-color: var(--accent); color: var(--accent); }

        /* TAULA */
        .table-wrap {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius-lg); overflow: hidden;
        }
        .table-head-row {
            padding: 1rem 1.4rem; border-bottom: 1px solid var(--border);
            font-size: .82rem; color: var(--text-muted);
        }
        .table-head-row strong { color: var(--text); }

        table { width: 100%; border-collapse: collapse; }

        thead th {
            padding: .8rem 1.2rem; text-align: left;
            font-size: .72rem; text-transform: uppercase;
            letter-spacing: 1.5px; color: var(--text-muted); font-weight: 600;
            border-bottom: 1px solid var(--border); background: var(--bg-card2);
        }
        tbody tr { border-bottom: 1px solid var(--border); transition: background .15s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: rgba(255,255,255,.02); }
        tbody td { padding: .85rem 1.2rem; font-size: .88rem; vertical-align: middle; }

        .user-cell { display: flex; align-items: center; gap: .8rem; }
        .user-avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), #7c3aed);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .8rem; color: #fff; flex-shrink: 0;
        }
        .user-nom { font-weight: 500; color: var(--text); }

        .badge-rol {
            display: inline-flex; align-items: center; gap: .3rem;
            padding: .22rem .75rem; border-radius: 100px;
            font-size: .75rem; font-weight: 600;
        }
        .badge-rol.admin  { background: rgba(255,107,53,.12); border: 1px solid rgba(255,107,53,.35); color: var(--accent); }
        .badge-rol.usuari { background: rgba(168,85,247,.12); border: 1px solid rgba(168,85,247,.35); color: var(--purple); }

        .badge-actiu {
            display: inline-flex; align-items: center; gap: .3rem;
            padding: .22rem .75rem; border-radius: 100px;
            font-size: .75rem; font-weight: 600;
        }
        .badge-actiu.si  { background: rgba(34,197,94,.12);  border: 1px solid rgba(34,197,94,.35);  color: var(--green); }
        .badge-actiu.no  { background: rgba(239,68,68,.12);  border: 1px solid rgba(239,68,68,.35);  color: var(--red);   }

        .email-text  { color: var(--text-muted); font-size: .82rem; }
        .email-buit  { color: var(--border); font-size: .8rem; font-style: italic; }

        .actions-cell { display: flex; gap: .45rem; align-items: center; }
        .btn-icon {
            width: 30px; height: 30px; border-radius: 7px;
            border: 1px solid var(--border); background: transparent;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: .82rem; transition: all .2s; text-decoration: none;
        }
        .btn-icon-edit:hover   { border-color: var(--amber); background: rgba(245,158,11,.1); }
        .btn-icon-delete:hover { border-color: var(--red);   background: rgba(239,68,68,.1);  }

        .empty-row td {
            text-align: center; padding: 3rem;
            color: var(--text-muted); font-size: .9rem;
        }

        /* MODAL */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,.75);
            backdrop-filter: blur(6px); z-index: 500;
            display: flex; align-items: center; justify-content: center;
            padding: 1rem; opacity: 0; pointer-events: none; transition: opacity .25s;
        }
        .modal-overlay.open { opacity: 1; pointer-events: all; }
        .modal {
            background: #16161f; border: 1px solid var(--border);
            border-radius: var(--radius-lg); width: 100%; max-width: 520px;
            max-height: 90vh; overflow-y: auto;
            transform: translateY(20px) scale(.97); transition: transform .25s;
            box-shadow: 0 24px 60px rgba(0,0,0,.6);
        }
        .modal-overlay.open .modal { transform: translateY(0) scale(1); }

        .modal-head {
            padding: 1.4rem 1.6rem 1rem; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .modal-head h2 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.4rem; letter-spacing: 1.5px; color: var(--text);
        }
        .modal-close {
            width: 32px; height: 32px; border-radius: 8px;
            border: 1px solid var(--border); background: transparent;
            color: var(--text-muted); font-size: 1.1rem; cursor: pointer;
            display: flex; align-items: center; justify-content: center; transition: all .2s;
        }
        .modal-close:hover { border-color: var(--red); color: var(--red); }

        .modal-body { padding: 1.4rem 1.6rem; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
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
            border-radius: 8px; padding: .65rem .9rem;
            color: var(--text); font-family: 'DM Sans', sans-serif;
            font-size: .9rem; transition: border-color .2s; width: 100%;
        }
        .form-input:focus, .form-select:focus { outline: none; border-color: var(--accent); }
        .form-select option { background: #16161f; }

        .input-error { border-color: var(--red) !important; background: rgba(239,68,68,.05) !important; }
        .form-error-server { font-size: .76rem; color: var(--red); display: block; margin-top: .1rem; }
        .form-error-js     { font-size: .76rem; color: var(--red); display: none;  margin-top: .1rem; }

        /* Força contrasenya */
        .strength-bar { height: 4px; background: var(--border); border-radius: 100px; overflow: hidden; margin-top: .4rem; }
        .strength-fill { height: 100%; border-radius: 100px; transition: width .3s, background .3s; width: 0%; }
        .strength-text { font-size: .72rem; color: var(--text-muted); margin-top: .25rem; }

        .modal-footer {
            padding: 1rem 1.6rem 1.4rem; border-top: 1px solid var(--border);
            display: flex; gap: .7rem; justify-content: flex-end;
        }
        .btn-cancel {
            padding: .55rem 1.2rem; border-radius: 8px;
            border: 1px solid var(--border); background: transparent;
            color: var(--text-muted); font-family: 'DM Sans', sans-serif;
            font-size: .9rem; cursor: pointer; transition: all .2s;
        }
        .btn-cancel:hover { border-color: var(--red); color: var(--red); }
        .btn-submit {
            padding: .55rem 1.4rem; border-radius: 8px; border: none;
            background: var(--accent); color: #fff;
            font-family: 'DM Sans', sans-serif; font-weight: 700;
            font-size: .9rem; cursor: pointer; transition: background .2s, transform .15s;
        }
        .btn-submit:hover { background: var(--accent-dim); transform: scale(1.02); }
        .btn-submit:disabled { opacity: .5; cursor: default; transform: none; }

        @media (max-width: 768px) {
            .topbar { padding: 0 1rem; }
            .page-wrap { padding: 1.5rem 1rem 3rem; }
            table thead { display: none; }
            tbody td { display: block; padding: .4rem 1rem; }
            tbody td::before { content: attr(data-label) ': '; font-weight: 600; color: var(--text-muted); font-size: .75rem; }
            tbody tr { display: block; padding: .6rem 0; border-bottom: 1px solid var(--border); }
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
        <div class="topbar-user">
            <div class="avatar"><?= strtoupper(substr($admin_name, 0, 1)) ?></div>
            Benvingut, <span><?= htmlspecialchars($admin_name) ?></span>
        </div>
        <span class="topbar-admin-badge">Admin</span>
        <a href="../dashboard.php" class="btn-nav">← Dashboard</a>
        <a href="../logout.php" class="btn-nav logout">Tancar sessió</a>
    </div>
</nav>

<!-- BANNER ADMIN -->
<div class="admin-banner">
    <div class="admin-banner-left">
        🛠️ Mode administrador — Gestió d'usuaris
    </div>
    <button onclick="obrirModal()" class="btn-admin-action">➕ Nou usuari</button>
</div>

<div class="page-wrap">

    <!-- BREADCRUMB -->
    <nav class="breadcrumb">
        <a href="../dashboard.php">Dashboard</a>
        <span>/</span>
        <span class="current">Usuaris</span>
    </nav>

    <!-- HEADER -->
    <div class="page-header">
        <h1>Gestió d'<em>usuaris</em></h1>
        <p>Visualitza, crea i gestiona els usuaris registrats al sistema.</p>
    </div>

    <!-- FEEDBACK -->
    <?php if ($missatge): ?>
    <div class="feedback <?= $missatge_tipus ?>">
        <?= $missatge_tipus === 'success' ? '✅' : ($missatge_tipus === 'warning' ? '⚠️' : '❌') ?>
        <?= htmlspecialchars($missatge) ?>
    </div>
    <?php endif; ?>

    <!-- STATS -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-label">Total usuaris</div>
            <div class="stat-val"><?= $total_usuaris ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Administradors</div>
            <div class="stat-val"><?= $total_admins ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Usuaris normals</div>
            <div class="stat-val"><?= $total_usuaris - $total_admins ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Comptes actius</div>
            <div class="stat-val"><?= $total_actius ?></div>
        </div>
    </div>

    <!-- TOOLBAR -->
    <form method="GET" action="">
        <div class="toolbar">
            <div class="search-wrap">
                <span class="search-icon">🔍</span>
                <input class="search-input" type="text" name="cerca"
                       placeholder="Buscar per nom o email..."
                       value="<?= htmlspecialchars($filtre_cerca) ?>">
            </div>
            <select class="filter-select" name="rol">
                <option value="">Tots els rols</option>
                <option value="usuari" <?= $filtre_rol === 'usuari' ? 'selected' : '' ?>>Usuari</option>
                <option value="admin"  <?= $filtre_rol === 'admin'  ? 'selected' : '' ?>>Admin</option>
            </select>
            <button type="submit" class="btn-search">Filtrar</button>
            <?php if ($filtre_cerca !== '' || $filtre_rol !== ''): ?>
                <a href="usuaris.php" class="btn-reset">✕ Netejar</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- TAULA -->
    <div class="table-wrap">
        <div class="table-head-row">
            Mostrant <strong><?= count($usuaris) ?></strong> de
            <strong><?= $total_usuaris ?></strong> usuaris
            <?php if ($filtre_cerca !== '' || $filtre_rol !== ''): ?>
                <span style="color:var(--accent);">(filtrats)</span>
            <?php endif; ?>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Usuari</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Actiu</th>
                    <th>Accions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuaris)): ?>
                <tr class="empty-row">
                    <td colspan="6">😕 No s'han trobat usuaris amb els filtres aplicats.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($usuaris as $u): ?>
                <tr>
                    <td data-label="ID">
                        <span style="color:var(--text-muted);font-size:.8rem;">#<?= (int)$u['id_usuari'] ?></span>
                    </td>
                    <td data-label="Usuari">
                        <div class="user-cell">
                            <div class="user-avatar"><?= strtoupper(substr($u['nom_usuari'], 0, 1)) ?></div>
                            <span class="user-nom"><?= htmlspecialchars($u['nom_usuari']) ?></span>
                        </div>
                    </td>
                    <td data-label="Email">
                        <?php if (!empty($u['email'])): ?>
                            <span class="email-text"><?= htmlspecialchars($u['email']) ?></span>
                        <?php else: ?>
                            <span class="email-buit">— sense email —</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Rol">
                        <span class="badge-rol <?= htmlspecialchars($u['rol']) ?>">
                            <?= $u['rol'] === 'admin' ? '🛠️' : '👤' ?>
                            <?= htmlspecialchars($u['rol']) ?>
                        </span>
                    </td>
                    <td data-label="Actiu">
                        <?php if ((int)$u['actiu'] === 1): ?>
                            <span class="badge-actiu si">✓ Sí</span>
                        <?php else: ?>
                            <span class="badge-actiu no">✕ No</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Accions">
                        <div class="actions-cell">
                            <a href="editar_usuari.php?id=<?= (int)$u['id_usuari'] ?>"
                               class="btn-icon btn-icon-edit" title="Editar usuari">✏️</a>
                            <a href="eliminar_usuari.php?id=<?= (int)$u['id_usuari'] ?>"
                               class="btn-icon btn-icon-delete" title="Eliminar usuari">🗑️</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- MODAL: NOU USUARI -->
<div class="modal-overlay" id="modalOverlay" onclick="tancarModalFora(event)">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitol">

        <div class="modal-head">
            <h2 id="modalTitol">➕ Nou Usuari</h2>
            <button class="modal-close" onclick="tancarModal()" aria-label="Tancar">✕</button>
        </div>

        <form method="POST" id="formNouUsuari" novalidate>
            <input type="hidden" name="accio" value="nou_usuari">
            <div class="modal-body">
                <div class="form-grid">

                    <!-- Nom d'usuari -->
                    <div class="form-group full">
                        <label class="form-label" for="nou_nom">Nom d'usuari *</label>
                        <input class="form-input <?= isset($errors_nou['nou_nom']) ? 'input-error' : '' ?>"
                               type="text" id="nou_nom" name="nou_nom"
                               placeholder="Ex: joan_garcia" maxlength="50"
                               value="<?= htmlspecialchars($old_nou['nou_nom'] ?? '') ?>">
                        <?php if (isset($errors_nou['nou_nom'])): ?>
                            <span class="form-error-server">⚠ <?= htmlspecialchars($errors_nou['nou_nom']) ?></span>
                        <?php else: ?>
                            <span class="form-error-js" id="js_err_nom">El nom d'usuari és obligatori (mínim 3 caràcters).</span>
                        <?php endif; ?>
                    </div>

                    <!-- Email -->
                    <div class="form-group full">
                        <label class="form-label" for="nou_email">
                            Email <small>(opcional)</small>
                        </label>
                        <input class="form-input <?= isset($errors_nou['nou_email']) ? 'input-error' : '' ?>"
                               type="email" id="nou_email" name="nou_email"
                               placeholder="Ex: joan@exemple.com" maxlength="100"
                               value="<?= htmlspecialchars($old_nou['nou_email'] ?? '') ?>">
                        <?php if (isset($errors_nou['nou_email'])): ?>
                            <span class="form-error-server">⚠ <?= htmlspecialchars($errors_nou['nou_email']) ?></span>
                        <?php else: ?>
                            <span class="form-error-js" id="js_err_email">El format de l'email no és vàlid.</span>
                        <?php endif; ?>
                    </div>

                    <!-- Contrasenya -->
                    <div class="form-group">
                        <label class="form-label" for="nou_contrasenya">Contrasenya *</label>
                        <input class="form-input <?= isset($errors_nou['nou_contrasenya']) ? 'input-error' : '' ?>"
                               type="password" id="nou_contrasenya" name="nou_contrasenya"
                               placeholder="Mínim 6 caràcters">
                        <?php if (isset($errors_nou['nou_contrasenya'])): ?>
                            <span class="form-error-server">⚠ <?= htmlspecialchars($errors_nou['nou_contrasenya']) ?></span>
                        <?php else: ?>
                            <span class="form-error-js" id="js_err_contrasenya">La contrasenya ha de tenir almenys 6 caràcters.</span>
                        <?php endif; ?>
                        <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                        <div class="strength-text" id="strengthText"></div>
                    </div>

                    <!-- Confirmar -->
                    <div class="form-group">
                        <label class="form-label" for="nou_confirmar">Confirmar *</label>
                        <input class="form-input <?= isset($errors_nou['nou_confirmar']) ? 'input-error' : '' ?>"
                               type="password" id="nou_confirmar" name="nou_confirmar"
                               placeholder="Repeteix la contrasenya">
                        <?php if (isset($errors_nou['nou_confirmar'])): ?>
                            <span class="form-error-server">⚠ <?= htmlspecialchars($errors_nou['nou_confirmar']) ?></span>
                        <?php else: ?>
                            <span class="form-error-js" id="js_err_confirmar">Les contrasenyes no coincideixen.</span>
                        <?php endif; ?>
                    </div>

                    <!-- Rol -->
                    <div class="form-group full">
                        <label class="form-label" for="nou_rol">Rol *</label>
                        <select class="form-select <?= isset($errors_nou['nou_rol']) ? 'input-error' : '' ?>"
                                id="nou_rol" name="nou_rol">
                            <option value="">— Selecciona un rol —</option>
                            <option value="usuari" <?= ($old_nou['nou_rol'] ?? '') === 'usuari' ? 'selected' : '' ?>>👤 Usuari</option>
                            <option value="admin"  <?= ($old_nou['nou_rol'] ?? '') === 'admin'  ? 'selected' : '' ?>>🛠️ Administrador</option>
                        </select>
                        <?php if (isset($errors_nou['nou_rol'])): ?>
                            <span class="form-error-server">⚠ <?= htmlspecialchars($errors_nou['nou_rol']) ?></span>
                        <?php else: ?>
                            <span class="form-error-js" id="js_err_rol">Has de seleccionar un rol.</span>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="tancarModal()">Cancel·lar</button>
                <button type="submit" class="btn-submit" id="btnSubmit">Crear usuari</button>
            </div>
        </form>
    </div>
</div>

<script>
function obrirModal() {
    document.getElementById('modalOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('nou_nom')?.focus(), 100);
}
function tancarModal() {
    document.getElementById('modalOverlay').classList.remove('open');
    document.body.style.overflow = '';
}
function tancarModalFora(e) {
    if (e.target === document.getElementById('modalOverlay')) tancarModal();
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') tancarModal(); });

<?php if (!empty($errors_nou)): ?>
window.addEventListener('DOMContentLoaded', () => obrirModal());
<?php endif; ?>

// Indicador força contrasenya
document.getElementById('nou_contrasenya')?.addEventListener('input', function() {
    const v = this.value;
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
const feedback = document.querySelector('.feedback');
if (feedback) {
    setTimeout(() => {
        feedback.style.transition = 'opacity .5s';
        feedback.style.opacity = '0';
        setTimeout(() => feedback.remove(), 500);
    }, 4000);
}

// Validació JS
document.getElementById('formNouUsuari')?.addEventListener('submit', function(e) {
    let valid = true;
    const camps = [
        { id:'nou_nom',         errId:'js_err_nom',         check: v => v.trim().length >= 3 && v.trim().length <= 50 },
        { id:'nou_email',       errId:'js_err_email',       check: v => v === '' || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) },
        { id:'nou_contrasenya', errId:'js_err_contrasenya', check: v => v.length >= 6 },
        { id:'nou_confirmar',   errId:'js_err_confirmar',   check: v => v === document.getElementById('nou_contrasenya').value && v !== '' },
        { id:'nou_rol',         errId:'js_err_rol',         check: v => v !== '' },
    ];
    camps.forEach(({ id, errId, check }) => {
        const input = document.getElementById(id);
        const errEl = document.getElementById(errId);
        if (!input) return;
        if (!check(input.value)) {
            input.classList.add('input-error');
            if (errEl) errEl.style.display = 'block';
            valid = false;
        } else {
            input.classList.remove('input-error');
            if (errEl) errEl.style.display = 'none';
        }
    });
    if (!valid) {
        e.preventDefault();
    } else {
        const btn = document.getElementById('btnSubmit');
        btn.disabled = true;
        btn.textContent = 'Creant...';
    }
});
</script>
</body>
</html>
