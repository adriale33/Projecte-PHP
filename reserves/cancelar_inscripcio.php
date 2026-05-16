<?php
// reserves/cancelar_inscripcio.php
require_once '../auth.php';
requireLogin();

require_once '../config.php';

$user_id   = $_SESSION['usuari_id'];
$id_classe = isset($_GET['id']) && ctype_digit($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_classe === 0) {
    header('Location: ../dashboard.php');
    exit;
}

// Comprovar que la inscripció existeix i pertany a aquest usuari
$stmt = $pdo->prepare("SELECT id_reserva FROM reserves WHERE usuari_id = ? AND classe_id = ?");
$stmt->execute([$user_id, $id_classe]);
$reserva = $stmt->fetch();

if (!$reserva) {
    header('Location: ../dashboard.php');
    exit;
}

// DELETE
$stmt = $pdo->prepare("DELETE FROM reserves WHERE usuari_id = ? AND classe_id = ?");
$stmt->execute([$user_id, $id_classe]);

header('Location: ../dashboard.php?cancelada=1');
exit;
