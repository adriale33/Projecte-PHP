<?php
// dashboard.php
require_once 'auth.php';
requireLogin();

require_once 'config.php';

$user_id   = $_SESSION['usuari_id'];
$user_name = $_SESSION['nom_usuari'] ?? 'Usuari';
$is_admin  = teRol('admin');

// Llistes de valors permesos (font de veritat del servidor)
$categories_ok  = ['Força', 'Cardiovascular', 'Cos i ment', 'Virtual', 'Aquàtica'];
$intensitats_ok = ['Baixa', 'Mitjana', 'Alta'];
$estudis_ok     = ['Estudi 1', 'Estudi 2', 'Estudi 3', 'Piscina'];

// Variables per al formulari (errors de servidor i valors antics)
$errors_nova = [];
$old_nova    = [];
$missatge       = '';
$missatge_tipus = '';

// =========================================================
// CREATE: Nova classe (només admin)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['accio'])
    && $_POST['accio'] === 'nova_classe'
) {
    // Doble comprovació: ha de ser admin fins i tot si manipulen el POST
    if (!$is_admin) {
        header('Location: dashboard.php');
        exit;
    }

    // --- Recollir i sanejar ---
    $nc_nom        = trim($_POST['nc_nom']        ?? '');
    $nc_tecnic     = trim($_POST['nc_tecnic']     ?? '');
    $nc_categoria  = trim($_POST['nc_categoria']  ?? '');
    $nc_intensitat = trim($_POST['nc_intensitat'] ?? '');
    $nc_durada_raw = trim($_POST['nc_durada']     ?? '');
    $nc_places_raw = trim($_POST['nc_places']     ?? '');
    $nc_estudi     = trim($_POST['nc_estudi']     ?? '');
    $nc_horari     = trim($_POST['nc_horari']     ?? '');

    // Guardar per repoblar el modal si hi ha errors
    $old_nova = [
        'nc_nom'        => $nc_nom,
        'nc_tecnic'     => $nc_tecnic,
        'nc_categoria'  => $nc_categoria,
        'nc_intensitat' => $nc_intensitat,
        'nc_durada_raw' => $nc_durada_raw,
        'nc_places_raw' => $nc_places_raw,
        'nc_estudi'     => $nc_estudi,
        'nc_horari'     => $nc_horari,
    ];

    // --- Validació camp per camp ---

    // NOM: obligatori, mínim 2 caràcters, màxim 100, no pot ser només números ni espais
    if ($nc_nom === '') {
        $errors_nova['nc_nom'] = 'El nom de la classe és obligatori.';
    } elseif (mb_strlen($nc_nom) < 2) {
        $errors_nova['nc_nom'] = 'El nom ha de tenir almenys 2 caràcters.';
    } elseif (mb_strlen($nc_nom) > 100) {
        $errors_nova['nc_nom'] = 'El nom no pot superar els 100 caràcters.';
    } elseif (is_numeric($nc_nom)) {
        $errors_nova['nc_nom'] = 'El nom no pot ser només números.';
    }

    // TÈCNIC: opcional, però si s'omple ha de ser text vàlid
    if ($nc_tecnic !== '') {
        if (mb_strlen($nc_tecnic) < 2) {
            $errors_nova['nc_tecnic'] = 'El nom del tècnic ha de tenir almenys 2 caràcters.';
        } elseif (mb_strlen($nc_tecnic) > 100) {
            $errors_nova['nc_tecnic'] = 'El nom del tècnic no pot superar els 100 caràcters.';
        } elseif (is_numeric($nc_tecnic)) {
            $errors_nova['nc_tecnic'] = 'El nom del tècnic no pot ser només números.';
        }
    }

    // CATEGORIA: ha de ser exactament un valor de la llista blanca
    if ($nc_categoria === '') {
        $errors_nova['nc_categoria'] = 'Has de seleccionar una categoria.';
    } elseif (!in_array($nc_categoria, $categories_ok, true)) {
        $errors_nova['nc_categoria'] = 'La categoria seleccionada no és vàlida.';
    }

    // INTENSITAT: ha de ser exactament un valor de la llista blanca
    if ($nc_intensitat === '') {
        $errors_nova['nc_intensitat'] = 'Has de seleccionar la intensitat.';
    } elseif (!in_array($nc_intensitat, $intensitats_ok, true)) {
        $errors_nova['nc_intensitat'] = 'La intensitat seleccionada no és vàlida.';
    }

    // DURADA: obligatòria, ha de ser un enter positiu entre 1 i 180
    if ($nc_durada_raw === '') {
        $errors_nova['nc_durada'] = 'La durada és obligatòria.';
    } elseif (!ctype_digit($nc_durada_raw)) {
        $errors_nova['nc_durada'] = 'La durada ha de ser un número enter positiu (sense decimals ni símbols).';
    } elseif ((int)$nc_durada_raw < 1) {
        $errors_nova['nc_durada'] = 'La durada ha de ser com a mínim 1 minut.';
    } elseif ((int)$nc_durada_raw > 180) {
        $errors_nova['nc_durada'] = 'La durada no pot superar els 180 minuts.';
    }

    // PLACES: obligatòries, ha de ser un enter positiu entre 1 i 500
    if ($nc_places_raw === '') {
        $errors_nova['nc_places'] = 'El nombre de places és obligatori.';
    } elseif (!ctype_digit($nc_places_raw)) {
        $errors_nova['nc_places'] = 'Les places han de ser un número enter positiu (sense decimals ni símbols).';
    } elseif ((int)$nc_places_raw < 1) {
        $errors_nova['nc_places'] = 'Ha d\'haver-hi almenys 1 plaça.';
    } elseif ((int)$nc_places_raw > 500) {
        $errors_nova['nc_places'] = 'No es poden superar les 500 places.';
    }

    // ESTUDI: ha de ser exactament un valor de la llista blanca
    if ($nc_estudi === '') {
        $errors_nova['nc_estudi'] = 'Has de seleccionar un espai.';
    } elseif (!in_array($nc_estudi, $estudis_ok, true)) {
        $errors_nova['nc_estudi'] = 'L\'espai seleccionat no és vàlid.';
    }

    // HORARI: obligatori, format HH:MM, hora vàlida real
    if ($nc_horari === '') {
        $errors_nova['nc_horari'] = 'L\'horari és obligatori.';
    } elseif (!preg_match('/^\d{2}:\d{2}$/', $nc_horari)) {
        $errors_nova['nc_horari'] = 'L\'horari ha de tenir el format HH:MM.';
    } else {
        [$h, $m] = explode(':', $nc_horari);
        if ((int)$h > 23 || (int)$m > 59) {
            $errors_nova['nc_horari'] = 'L\'horari introduït no és una hora real vàlida.';
        }
    }

    // --- Insertar NOMÉS si no hi ha cap error ---
    if (empty($errors_nova)) {
        $nc_tecnic_db = $nc_tecnic !== '' ? $nc_tecnic : null;
        $nc_durada    = (int)$nc_durada_raw;
        $nc_places    = (int)$nc_places_raw;

        $stmt = $pdo->prepare(
            "INSERT INTO classes (nom, `nom_tècnic`, durada, categoria, intensitat, estudi, horari, places)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $nc_nom, $nc_tecnic_db, $nc_durada,
            $nc_categoria, $nc_intensitat, $nc_estudi, $nc_horari, $nc_places
        ]);

        $missatge       = 'Classe "' . htmlspecialchars($nc_nom) . '" creada correctament!';
        $missatge_tipus = 'success';
        $old_nova       = []; // netejar formulari
    } else {
        $missatge       = 'Corregeix els errors marcats al formulari.';
        $missatge_tipus = 'error';
    }
}

// =========================================================
// CREATE: Inscripció a una classe
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inscriure'], $_POST['id_classe'])) {
    $id_classe = (int) $_POST['id_classe'];

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

// =========================================================
// READ: Totes les classes agrupades per categoria
// =========================================================
$stmt = $pdo->query(
    "SELECT id_classe, nom, `nom_tècnic`, durada, categoria, intensitat, estudi, horari, places
     FROM classes
     ORDER BY categoria, horari"
);
$totes_classes = $stmt->fetchAll();

$classes_per_categoria = [];
foreach ($totes_classes as $classe) {
    $classes_per_categoria[$classe['categoria']][] = $classe;
}

// READ: Inscripcions de l'usuari actual
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
        .btn-nav.logout       { border-color: var(--red); background: var(--red); color: #fff; }
        .btn-nav.logout:hover { background: #c53030; border-color: #c53030; color: #fff; }

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
        .feedback.success { background: rgba(34,197,94,.12);  border: 1px solid rgba(34,197,94,.3);  color: var(--green); }
        .feedback.warning { background: rgba(245,158,11,.12); border: 1px solid rgba(245,158,11,.3); color: var(--amber); }
        .feedback.error   { background: rgba(239,68,68,.12);  border: 1px solid rgba(239,68,68,.3);  color: var(--red);   }

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

        /* ── ADMIN ─────────────────────────────────────── */
        body.is-admin { --accent: #ff6b35; --accent-dim: #cc5529; }
        body.is-admin .topbar { border-bottom-color: rgba(255,107,53,.4); }

        .admin-banner {
            background: linear-gradient(90deg, rgba(255,107,53,.12), rgba(255,107,53,.05));
            border-bottom: 1px solid rgba(255,107,53,.3);
            padding: .6rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .admin-banner-left {
            display: flex;
            align-items: center;
            gap: .6rem;
            font-size: .85rem;
            color: #ff6b35;
            font-weight: 600;
        }
        .admin-banner-right { display: flex; gap: .6rem; }

        .btn-admin-action {
            display: flex;
            align-items: center;
            gap: .4rem;
            padding: .4rem 1rem;
            border-radius: 8px;
            border: 1px solid rgba(255,107,53,.4);
            background: rgba(255,107,53,.1);
            color: #ff6b35;
            font-family: 'DM Sans', sans-serif;
            font-size: .82rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all .2s;
        }
        .btn-admin-action:hover {
            background: rgba(255,107,53,.2);
            border-color: #ff6b35;
        }

        .btn-icon {
            width: 30px;
            height: 30px;
            border-radius: 7px;
            border: 1px solid var(--border);
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: .85rem;
            transition: all .2s;
            text-decoration: none;
        }
        .btn-icon-edit:hover   { border-color: var(--amber); background: rgba(245,158,11,.1); }
        .btn-icon-delete:hover { border-color: var(--red);   background: rgba(239,68,68,.1);  }

        .topbar-admin-badge {
            background: rgba(255,107,53,.15);
            border: 1px solid rgba(255,107,53,.4);
            color: #ff6b35;
            border-radius: 100px;
            font-size: .72rem;
            font-weight: 700;
            padding: .2rem .7rem;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* ── MODAL NOVA CLASSE ─────────────────────────── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.75);
            backdrop-filter: blur(6px);
            z-index: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            opacity: 0;
            pointer-events: none;
            transition: opacity .25s;
        }
        .modal-overlay.open { opacity: 1; pointer-events: all; }

        .modal {
            background: #16161f;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 560px;
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(20px) scale(.97);
            transition: transform .25s;
            box-shadow: 0 24px 60px rgba(0,0,0,.6);
        }
        .modal-overlay.open .modal { transform: translateY(0) scale(1); }

        .modal-head {
            padding: 1.4rem 1.6rem 1rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-head h2 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.4rem;
            letter-spacing: 1.5px;
            color: var(--text);
        }
        .modal-close {
            width: 32px; height: 32px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text-muted);
            font-size: 1.1rem;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: all .2s;
        }
        .modal-close:hover { border-color: var(--red); color: var(--red); }

        .modal-body { padding: 1.4rem 1.6rem; }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .form-group { display: flex; flex-direction: column; gap: .4rem; }
        .form-group.full { grid-column: 1 / -1; }

        .form-label {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            font-weight: 600;
        }
        .form-label small {
            font-size: .72rem;
            text-transform: none;
            letter-spacing: 0;
            color: var(--text-muted);
            opacity: .7;
            font-weight: 400;
        }

        .form-input,
        .form-select {
            background: var(--bg-card2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: .65rem .9rem;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: .9rem;
            transition: border-color .2s;
            width: 100%;
        }
        .form-input:focus,
        .form-select:focus { outline: none; border-color: #ff6b35; }
        .form-select option { background: #16161f; }

        /* Estat error del camp */
        .input-error { border-color: var(--red) !important; background: rgba(239,68,68,.05) !important; }

        /* Missatge d'error per camp (servidor) */
        .form-error-server {
            font-size: .78rem;
            color: var(--red);
            display: block;
            margin-top: .1rem;
        }

        /* Missatge d'error JS (ocult per defecte) */
        .form-error-js {
            font-size: .78rem;
            color: var(--red);
            display: none;
            margin-top: .1rem;
        }

        .modal-footer {
            padding: 1rem 1.6rem 1.4rem;
            border-top: 1px solid var(--border);
            display: flex;
            gap: .7rem;
            justify-content: flex-end;
        }
        .btn-cancel {
            padding: .55rem 1.2rem;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text-muted);
            font-family: 'DM Sans', sans-serif;
            font-size: .9rem;
            cursor: pointer;
            transition: all .2s;
        }
        .btn-cancel:hover { border-color: var(--red); color: var(--red); }

        .btn-submit {
            padding: .55rem 1.4rem;
            border-radius: 8px;
            border: none;
            background: #ff6b35;
            color: #fff;
            font-family: 'DM Sans', sans-serif;
            font-weight: 700;
            font-size: .9rem;
            cursor: pointer;
            transition: background .2s, transform .15s;
        }
        .btn-submit:hover { background: #cc5529; transform: scale(1.02); }
        .btn-submit:disabled { opacity: .5; cursor: default; transform: none; }
    </style>
</head>
<body class="<?= $is_admin ? 'is-admin' : '' ?>">

<!-- TOPBAR -->
<nav class="topbar">
    <a class="logo" href="dashboard.php">Projecte Gimnàs</a>
    <div class="topbar-right">
        <div class="topbar-user">
            <div class="avatar"><?= strtoupper(substr($user_name, 0, 1)) ?></div>
            Benvingut, <span><?= htmlspecialchars($user_name) ?></span>
        </div>
        <?php if ($is_admin): ?>
            <span class="topbar-admin-badge">Admin</span>
        <?php endif; ?>
        <a href="logout.php" class="btn-nav logout">Tancar sessió</a>
    </div>
</nav>

<?php if ($is_admin): ?>
<div class="admin-banner">
    <div class="admin-banner-left">
        🛠️ Mode administrador — tens accés a la gestió de classes i usuaris
    </div>
    <div class="admin-banner-right">
        <a href="usuaris/usuaris.php" class="btn-admin-action">👥 Gestionar usuaris</a>
        <button onclick="obrirModal()" class="btn-admin-action">➕ Nova classe</button>
    </div>
</div>
<?php endif; ?>

<div class="main-wrap">

    <div class="dash-header">
        <h1>Les teves <em>classes</em></h1>
        <p>
            <?php if ($is_admin): ?>
                Com a administrador pots afegir, editar i eliminar classes des de cada targeta.
            <?php else: ?>
                Inscriu-te a les classes que vulguis i consulta la fitxa tècnica de cada una.
            <?php endif; ?>
        </p>
    </div>

    <?php if ($missatge): ?>
    <div class="feedback <?= $missatge_tipus ?>">
        <?= $missatge_tipus === 'success' ? '✅' : ($missatge_tipus === 'warning' ? '⚠️' : '❌') ?>
        <?= htmlspecialchars($missatge) ?>
    </div>
    <?php endif; ?>

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

    <div class="filter-row">
        <button class="filter-btn active" onclick="filtrar('tots', this)">Totes</button>
        <?php foreach (array_keys($classes_per_categoria) as $cat): ?>
            <button class="filter-btn" onclick="filtrar('<?= htmlspecialchars($cat) ?>', this)">
                <?= ($icones_categoria[$cat] ?? '') ?> <?= htmlspecialchars($cat) ?>
            </button>
        <?php endforeach; ?>
    </div>

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
                        <?php if ($is_admin): ?>
                            <a href="classes/editar_classe.php?id=<?= (int)$classe['id_classe'] ?>"
                               class="btn-icon btn-icon-edit" title="Editar classe">✏️</a>
                            <a href="classes/eliminar_classe.php?id=<?= (int)$classe['id_classe'] ?>"
                               class="btn-icon btn-icon-delete" title="Eliminar classe">🗑️</a>
                        <?php endif; ?>
                        <a href="classes/classes.php?id=<?= (int)$classe['id_classe'] ?>" class="btn-ficha">
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

<?php if ($is_admin): ?>
<!-- MODAL: NOVA CLASSE -->
<div class="modal-overlay" id="modalOverlay" onclick="tancarModalFora(event)">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitol">

        <div class="modal-head">
            <h2 id="modalTitol">➕ Nova Classe</h2>
            <button class="modal-close" onclick="tancarModal()" aria-label="Tancar">✕</button>
        </div>

        <form method="POST" id="formNovaClasse" novalidate>
            <input type="hidden" name="accio" value="nova_classe">
            <div class="modal-body">
                <div class="form-grid">

                    <!-- Nom -->
                    <div class="form-group full">
                        <label class="form-label" for="nc_nom">Nom de la classe *</label>
                        <input class="form-input <?= isset($errors_nova['nc_nom']) ? 'input-error' : '' ?>"
                               type="text" id="nc_nom" name="nc_nom"
                               placeholder="Ex: BodyPump, Yoga, Zumba..."
                               maxlength="100"
                               value="<?= htmlspecialchars($old_nova['nc_nom'] ?? '') ?>">
                        <?php if (isset($errors_nova['nc_nom'])): ?>
                            <span class="form-error-server">⚠ <?= htmlspecialchars($errors_nova['nc_nom']) ?></span>
                        <?php else: ?>
                            <span class="form-error-js" id="js_err_nom">El nom és obligatori (mínim 2 caràcters).</span>
                        <?php endif; ?>
                    </div>

                    <!-- Tècnic -->
                    <div class="form-group full">
                        <label class="form-label" for="nc_tecnic">
                            Nom del tècnic <small>(opcional — deixa buit si és virtual)</small>
                        </label>
                        <input class="form-input <?= isset($errors_nova['nc_tecnic']) ? 'input-error' : '' ?>"
                               type="text" id="nc_tecnic" name="nc_tecnic"
                               placeholder="Deixa buit si és classe virtual"
                               maxlength="100"
                               value="<?= htmlspecialchars($old_nova['nc_tecnic'] ?? '') ?>">
                        <?php if (isset($errors_nova['nc_tecnic'])): ?>
                            <span class="form-error-server">⚠ <?= htmlspecialchars($errors_nova['nc_tecnic']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Categoria -->
                    <div class="form-group">
                        <label class="form-label" for="nc_categoria">Categoria *</label>
                        <select class="form-select <?= isset($errors_nova['nc_categoria']) ? 'input-error' : '' ?>"
                                id="nc_categoria" name="nc_categoria">
                            <option value="">— Selecciona —</option>
                            <?php foreach (['Força' => '🏋️', 'Cardiovascular' => '🏃', 'Cos i ment' => '🧘', 'Virtual' => '💻', 'Aquàtica' => '🏊'] as $cat => $ico): ?>
                            <option value="<?= $cat ?>" <?= ($old_nova['nc_categoria'] ?? '') === $cat ? 'selected' : '' ?>>
                                <?= $ico ?> <?= $cat ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors_nova['nc_categoria'])): ?>
                            <span class="form-error-server">⚠ <?= htmlspecialchars($errors_nova['nc_categoria']) ?></span>
                        <?php else: ?>
                            <span class="form-error-js" id="js_err_categoria">Has de seleccionar una categoria.</span>
                        <?php endif; ?>
                    </div>

                    <!-- Intensitat -->
                    <div class="form-group">
                        <label class="form-label" for="nc_intensitat">Intensitat *</label>
                        <select class="form-select <?= isset($errors_nova['nc_intensitat']) ? 'input-error' : '' ?>"
                                id="nc_intensitat" name="nc_intensitat">
                            <option value="">— Selecciona —</option>
                            <?php foreach (['Baixa', 'Mitjana', 'Alta'] as $int): ?>
                            <option value="<?= $int ?>" <?= ($old_nova['nc_intensitat'] ?? '') === $int ? 'selected' : '' ?>>
                                <?= $int ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors_nova['nc_intensitat'])): ?>
                            <span class="form-error-server">⚠ <?= htmlspecialchars($errors_nova['nc_intensitat']) ?></span>
                        <?php else: ?>
                            <span class="form-error-js" id="js_err_intensitat">Has de seleccionar la intensitat.</span>
                        <?php endif; ?>
                    </div>

                    <!-- Durada -->
                    <div class="form-group">
                        <label class="form-label" for="nc_durada">Durada (minuts) *</label>
                        <input class="form-input <?= isset($errors_nova['nc_durada']) ? 'input-error' : '' ?>"
                               type="number" id="nc_durada" name="nc_durada"
                               min="1" max="180" placeholder="Ex: 60"
                               value="<?= htmlspecialchars($old_nova['nc_durada_raw'] ?? '') ?>">
                        <?php if (isset($errors_nova['nc_durada'])): ?>
                            <span class="form-error-server">⚠ <?= htmlspecialchars($errors_nova['nc_durada']) ?></span>
                        <?php else: ?>
                            <span class="form-error-js" id="js_err_durada">La durada ha d'estar entre 1 i 180 minuts.</span>
                        <?php endif; ?>
                    </div>

                    <!-- Places -->
                    <div class="form-group">
                        <label class="form-label" for="nc_places">Places *</label>
                        <input class="form-input <?= isset($errors_nova['nc_places']) ? 'input-error' : '' ?>"
                               type="number" id="nc_places" name="nc_places"
                               min="1" max="500" placeholder="Ex: 25"
                               value="<?= htmlspecialchars($old_nova['nc_places_raw'] ?? '') ?>">
                        <?php if (isset($errors_nova['nc_places'])): ?>
                            <span class="form-error-server">⚠ <?= htmlspecialchars($errors_nova['nc_places']) ?></span>
                        <?php else: ?>
                            <span class="form-error-js" id="js_err_places">Les places han d'estar entre 1 i 500.</span>
                        <?php endif; ?>
                    </div>

                    <!-- Estudi -->
                    <div class="form-group">
                        <label class="form-label" for="nc_estudi">Estudi / Espai *</label>
                        <select class="form-select <?= isset($errors_nova['nc_estudi']) ? 'input-error' : '' ?>"
                                id="nc_estudi" name="nc_estudi">
                            <option value="">— Selecciona —</option>
                            <?php foreach (['Estudi 1', 'Estudi 2', 'Estudi 3', 'Piscina'] as $est): ?>
                            <option value="<?= $est ?>" <?= ($old_nova['nc_estudi'] ?? '') === $est ? 'selected' : '' ?>>
                                <?= $est ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors_nova['nc_estudi'])): ?>
                            <span class="form-error-server">⚠ <?= htmlspecialchars($errors_nova['nc_estudi']) ?></span>
                        <?php else: ?>
                            <span class="form-error-js" id="js_err_estudi">Has de seleccionar un espai.</span>
                        <?php endif; ?>
                    </div>

                    <!-- Horari -->
                    <div class="form-group">
                        <label class="form-label" for="nc_horari">Horari *</label>
                        <input class="form-input <?= isset($errors_nova['nc_horari']) ? 'input-error' : '' ?>"
                               type="time" id="nc_horari" name="nc_horari"
                               value="<?= htmlspecialchars($old_nova['nc_horari'] ?? '') ?>">
                        <?php if (isset($errors_nova['nc_horari'])): ?>
                            <span class="form-error-server">⚠ <?= htmlspecialchars($errors_nova['nc_horari']) ?></span>
                        <?php else: ?>
                            <span class="form-error-js" id="js_err_horari">L'horari és obligatori.</span>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="tancarModal()">Cancel·lar</button>
                <button type="submit" class="btn-submit" id="btnSubmit">Crear classe</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
// ── Filtres ───────────────────────────────────────────
function filtrar(categoria, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.categoria-section').forEach(sec => {
        sec.style.display = (categoria === 'tots' || sec.dataset.categoria === categoria) ? '' : 'none';
    });
}

// ── Feedback auto-ocultar ─────────────────────────────
const feedback = document.querySelector('.feedback');
if (feedback) {
    setTimeout(() => {
        feedback.style.transition = 'opacity .5s';
        feedback.style.opacity = '0';
        setTimeout(() => feedback.remove(), 500);
    }, 4000);
}

// ── Modal ─────────────────────────────────────────────
function obrirModal() {
    document.getElementById('modalOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('nc_nom')?.focus(), 100);
}
function tancarModal() {
    document.getElementById('modalOverlay').classList.remove('open');
    document.body.style.overflow = '';
}
function tancarModalFora(e) {
    if (e.target === document.getElementById('modalOverlay')) tancarModal();
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') tancarModal(); });

// Auto-obrir si el servidor ha retornat errors de validació
<?php if (!empty($errors_nova)): ?>
window.addEventListener('DOMContentLoaded', () => obrirModal());
<?php endif; ?>

// ── Validació JS (primera capa — el servidor sempre és l'última paraula) ──
document.getElementById('formNovaClasse')?.addEventListener('submit', function(e) {
    let valid = true;

    // Configuració dels camps a validar
    const camps = [
        {
            id: 'nc_nom',
            errId: 'js_err_nom',
            check: v => v.trim().length >= 2 && v.trim().length <= 100 && !(/^\d+$/.test(v.trim()))
        },
        {
            id: 'nc_categoria',
            errId: 'js_err_categoria',
            check: v => v !== ''
        },
        {
            id: 'nc_intensitat',
            errId: 'js_err_intensitat',
            check: v => v !== ''
        },
        {
            id: 'nc_durada',
            errId: 'js_err_durada',
            check: v => v !== '' && Number.isInteger(Number(v)) && Number(v) >= 1 && Number(v) <= 180
        },
        {
            id: 'nc_places',
            errId: 'js_err_places',
            check: v => v !== '' && Number.isInteger(Number(v)) && Number(v) >= 1 && Number(v) <= 500
        },
        {
            id: 'nc_estudi',
            errId: 'js_err_estudi',
            check: v => v !== ''
        },
        {
            id: 'nc_horari',
            errId: 'js_err_horari',
            check: v => v !== ''
        },
    ];

    camps.forEach(({ id, errId, check }) => {
        const input  = document.getElementById(id);
        const errEl  = document.getElementById(errId);
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
        e.preventDefault(); // Atura l'enviament si el JS detecta errors
    } else {
        // Deshabilitar el botó per evitar doble enviament
        const btn = document.getElementById('btnSubmit');
        btn.disabled = true;
        btn.textContent = 'Creant...';
    }
});
</script>
</body>
</html>
