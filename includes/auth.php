<?php
/**
 * ANTIQUA BARBAE - Verifica Autenticazione
 * File: includes/auth.php
 * 
 * Funzioni per verificare lo stato di login e i permessi.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica se l'utente è loggato
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Verifica se l'utente è un proprietario (owner)
 * @return bool
 */
function isOwner() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'owner';
}

/**
 * Verifica se l'utente è un cliente (customer)
 * @return bool
 */
function isCustomer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
}

/**
 * Reindirizza alla pagina di login se non loggato
 * @param string $redirect URL di reindirizzamento dopo login (opzionale)
 */
function requireLogin($redirect = null) {
    if (!isLoggedIn()) {
        $url = 'admin/login.php';
        if ($redirect) {
            $url .= '?redirect=' . urlencode($redirect);
        }
        header('Location: ' . $url);
        exit;
    }
}

/**
 * Reindirizza se non è proprietario
 * @param string $redirect URL di reindirizzamento
 */
function requireOwner($redirect = '../index.php') {
    if (!isOwner()) {
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * Reindirizza se non è cliente
 * @param string $redirect URL di reindirizzamento
 */
function requireCustomer($redirect = '../index.php') {
    if (!isCustomer()) {
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * Ottiene l'ID dell'utente loggato
 * @return int|null
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Ottiene il ruolo dell'utente loggato
 * @return string|null
 */
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Ottiene l'ID della barberia (solo per owner)
 * @return int|null
 */
function getBarberiaId() {
    return $_SESSION['barberia_id'] ?? null;
}