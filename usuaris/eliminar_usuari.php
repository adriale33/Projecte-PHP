<?php
// usuaris/eliminar_usuari.php
require_once '../auth.php';
requireAdmin();

require_once '../config.php';

// Validar ID
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header('Location: usuaris.php');
    exit;
}

$id_usuari = (int) $_GET['id'];

// No es pot eliminar a si mateix
if ($id_usuari === (int)$_SESSION['usuari_id']) {
    header('Location: usuaris.php?error=self');
    exit;
}

// READ: Obtenir l'usuari
$stmt = $pdo->prepare("SELECT id_usuari, nom_usuari, email, rol, actiu FROM usuaris WHERE id_usuari = ? LIMIT 1");
$stmt->execute([$id_usuari]);
$usuari = $stmt->fetch();

if (!$usuari) {
    header('Location: usuaris.php');
    exit;
}

// Comptar inscripcions actives de l'usuari
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reserves WHERE usuari_id = ?");
$stmt->execute([$id_usuari]);
$total_reserves = (int) $stmt->fetchColumn();

// =========================================================
// DELETE: Eliminar l'usuari
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['accio'])
    && $_POST['accio'] === 'eliminar_usuari'
    && isset($_POST['confirmar'])
    && $_POST['confirmar'] === 'si'
) {
    // Primer eliminar les reserves de l'usuari
    $stmt = $pdo->prepare("DELETE FROM reserves WHERE usuari_id = ?");
    $stmt->execute([$id_usuari]);

    // Després eliminar l'usuari
    $stmt = $pdo->prepare("DELETE FROM usuaris WHERE id_usuari = ?");
    $stmt->execute([$id_usuari]);

    header('Location: usuaris.php?eliminat=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar usuari — Projecte Gimnàs</title>
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
            background: linear-gradient(90deg, rgba(239,68,68,.12), rgba(239,68,68,.05));
            border-bottom: 1px solid rgba(239,68,68,.3);
            padding: .6rem 2rem;
            font-size: .85rem; color: var(--red); font-weight: 600;
            display: flex; align-items: center; gap: .6rem;
        }

        /* LAYOUT */
        .page-wrap {
            max-width: 580px;
            margin: 0 auto;
            padding: 3rem 1.5rem 4rem;
        }

        /* BREADCRUMB */
        .breadcrumb {
            display: flex; align-items: center; gap: .5rem;
            font-size: .82rem; color: var(--text-muted); margin-bottom: 2rem;
        }
        .breadcrumb a { color: var(--text-muted); text-decoration: none; }
        .breadcrumb a:hover { color: var(--accent); }
        .breadcrumb .current { color: var(--red); }

        /* CARD */
        .confirm-card {
            background: var(--bg-card);
            border: 1px solid rgba(239,68,68,.3);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }

        .confirm-head {
            background: rgba(239,68,68,.08);
            border-bottom: 1px solid rgba(239,68,68,.2);
            padding: 1.8rem 1.6rem;
            text-align: center;
        }
        .confirm-head .warn-icon { font-size: 3rem; display: block; margin-bottom: .8rem; }
        .confirm-head h1 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.8rem; letter-spacing: 2px; color: var(--red);
        }
        .confirm-head p { margin-top: .4rem; color: var(--text-muted); font-size: .9rem; }

        .confirm-body { padding: 1.6rem; }

        /* Info usuari */
        .user-info {
            background: var(--bg-card2);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.1rem 1.3rem;
            margin-bottom: 1.3rem;
            display: flex; align-items: center; gap: 1rem;
        }
        .user-avatar {
            width: 44px; height: 44px; border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), #7c3aed);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 1rem; color: #fff; flex-shrink: 0;
        }
        .user-info-text strong { font-size: .95rem; color: var(--text); display: block; }
        .user-info-text span   { font-size: .8rem;  color: var(--text-muted); }
        .badge-rol {
            display: inline-flex; align-items: center; gap: .3rem;
            padding: .22rem .75rem; border-radius: 100px;
            font-size: .75rem; font-weight: 600; margin-left: auto;
        }
        .badge-rol.admin  { background: rgba(255,107,53,.12); border: 1px solid rgba(255,107,53,.35); color: var(--accent); }
        .badge-rol.usuari { background: rgba(168,85,247,.12); border: 1px solid rgba(168,85,247,.35); color: var(--purple); }

        /* Avís reserves */
        .avis {
            display: flex; align-items: flex-start; gap: .7rem;
            padding: .9rem 1.1rem; border-radius: var(--radius);
            margin-bottom: 1.3rem; font-size: .87rem; line-height: 1.5;
        }
        .avis.amb-reserves {
            background: rgba(245,158,11,.08);
            border: 1px solid rgba(245,158,11,.3);
            color: var(--amber);
        }
        .avis.sense-reserves {
            background: rgba(34,197,94,.08);
            border: 1px solid rgba(34,197,94,.25);
            color: var(--green);
        }

        /* Text final */
        .avis-final {
            font-size: .85rem; color: var(--text-muted);
            text-align: center; margin-bottom: 1.4rem; line-height: 1.6;
        }
        .avis-final strong { color: var(--red); }

        /* Botons */
        .confirm-footer {
            display: flex; gap: .8rem; justify-content: center; flex-wrap: wrap;
        }
        .btn-cancel {
            padding: .7rem 1.8rem; border-radius: 8px;
            border: 1px solid var(--border); background: transparent;
            color: var(--text-muted); font-family: 'DM Sans', sans-serif;
            font-size: .95rem; font-weight: 500;
            text-decoration: none; transition: all .2s;
        }
        .btn-cancel:hover { border-color: var(--accent); color: var(--accent); }
        .btn-delete {
            padding: .7rem 1.8rem; border-radius: 8px; border: none;
            background: var(--red); color: #fff;
            font-family: 'DM Sans', sans-serif; font-weight: 700;
            font-size: .95rem; cursor: pointer; transition: background .2s, transform .15s;
        }
        .btn-delete:hover { background: #c53030; transform: scale(1.02); }
        .btn-delete:disabled { opacity: .5; cursor: default; transform: none; }

        @media (max-width: 480px) {
            .topbar { padding: 0 1rem; }
            .page-wrap { padding: 2rem 1rem 3rem; }
            .confirm-footer { flex-direction: column; }
            .btn-cancel, .btn-delete { text-align: center; }
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
    ⚠️ Zona de perill — Eliminar usuari
</div>

<div class="page-wrap">

    <!-- BREADCRUMB -->
    <nav class="breadcrumb">
        <a href="../dashboard.php">Dashboard</a>
        <span>/</span>
        <a href="usuaris.php">Usuaris</a>
        <span>/</span>
        <span class="current">Eliminar: <?= htmlspecialchars($usuari['nom_usuari']) ?></span>
    </nav>

    <!-- CARD -->
    <div class="confirm-card">

        <div class="confirm-head">
            <span class="warn-icon">🗑️</span>
            <h1>Eliminar usuari</h1>
            <p>Aquesta acció és permanent i no es pot desfer.</p>
        </div>

        <div class="confirm-body">

            <!-- Info usuari -->
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper(substr($usuari['nom_usuari'], 0, 1)) ?></div>
                <div class="user-info-text">
                    <strong><?= htmlspecialchars($usuari['nom_usuari']) ?></strong>
                    <span>
                        #<?= (int)$usuari['id_usuari'] ?>
                        <?= $usuari['email'] ? ' · ' . htmlspecialchars($usuari['email']) : '' ?>
                    </span>
                </div>
                <span class="badge-rol <?= $usuari['rol'] ?>">
                    <?= $usuari['rol'] === 'admin' ? '🛠️' : '👤' ?>
                    <?= htmlspecialchars($usuari['rol']) ?>
                </span>
            </div>

            <!-- Avís reserves -->
            <?php if ($total_reserves > 0): ?>
            <div class="avis amb-reserves">
                <span>⚠️</span>
                <div>
                    Aquest usuari té <strong><?= $total_reserves ?> inscripció<?= $total_reserves > 1 ? 'ns' : '' ?></strong> activa<?= $total_reserves > 1 ? 'es' : '' ?>.
                    En eliminar-lo, totes les seves reserves també s'eliminaran.
                </div>
            </div>
            <?php else: ?>
            <div class="avis sense-reserves">
                <span>✓</span>
                <div>Aquest usuari no té cap inscripció activa.</div>
            </div>
            <?php endif; ?>

            <!-- Text final -->
            <p class="avis-final">
                Estàs a punt d'eliminar l'usuari <strong><?= htmlspecialchars($usuari['nom_usuari']) ?></strong>
                de forma <strong>permanent</strong>.<br>Vols continuar?
            </p>

            <!-- Botons -->
            <div class="confirm-footer">
                <a href="usuaris.php" class="btn-cancel">Cancel·lar</a>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="accio"     value="eliminar_usuari">
                    <input type="hidden" name="confirmar" value="si">
                    <button type="submit" class="btn-delete" id="btnDelete"
                            onclick="this.disabled=true; this.textContent='Eliminant...'; this.form.submit();">
                        🗑️ Sí, eliminar
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

</body>
</html>
