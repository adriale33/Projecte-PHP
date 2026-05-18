<?php
// csrf.php - Protecció CSRF centralitzada

/**
 * Genera el token CSRF si no existeix a la sessió.
 * Ha de cridar-se DESPRÉS de session_start().
 */
function csrf_generar(): void {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * Retorna el camp hidden amb el token CSRF per incloure als formularis.
 */
function csrf_camp(): string {
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8')
        . '">';
}

/**
 * Verifica que el token POST coincideix amb el de la sessió.
 * Si no coincideix, atura l'execució amb error 403.
 */
function csrf_verificar(): void {
    if (
        empty($_POST['csrf_token']) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        die('Error 403: Token CSRF no vàlid. Torna enrere i torna-ho a intentar.');
    }
}
?>
