<?php
/**
 * ANTIQUA BARBAE - Funzioni Helper
 * File: includes/functions.php
 */

/**
 * Genera uno slug URL-friendly da una stringa
 * @param string $string La stringa da convertire
 * @return string Lo slug generato
 */
function generateSlug($string) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string), '-'));
    return $slug;
}

/**
 * Formatta un prezzo in euro
 * @param float $price Il prezzo da formattare
 * @return string Prezzo formattato (es. €10,99)
 */
function formatPrice($price) {
    return '€' . number_format($price, 2, ',', '.');
}

/**
 * Tronca un testo a una lunghezza massima
 * @param string $text Il testo da troncare
 * @param int $maxLength Lunghezza massima
 * @param string $suffix Suffisso da aggiungere (default: ...)
 * @return string Testo troncato
 */
function truncate($text, $maxLength = 100, $suffix = '...') {
    if (strlen($text) <= $maxLength) {
        return $text;
    }
    return substr($text, 0, $maxLength) . $suffix;
}

/**
 * Pulisce l'input da caratteri pericolosi (XSS Prevention)
 * @param string $input L'input da pulire
 * @return string Input pulito
 */
function clean($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Verifica se una richiesta è AJAX
 * @return bool
 */
function isAjax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Restituisce il totale del carrello (da LocalStorage - lato client)
 * Questa funzione è solo un placeholder, il calcolo reale avviene via JS e PHP
 * @param array $cartItems Array di prodotti nel carrello
 * @return float Totale
 */
function calculateCartTotal($cartItems) {
    $total = 0;
    foreach ($cartItems as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

/**
 * Verifica lo stock di un prodotto prima dell'acquisto
 * @param int $productId ID del prodotto
 * @param int $quantity Quantità richiesta
 * @return bool True se disponibile
 */
function checkStock($productId, $quantity) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = :id");
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch();
    return $product && $product['stock'] >= $quantity;
}

/**
 * Genera un token CSRF per i form
 * @return string Token generato
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica un token CSRF
 * @param string $token Il token da verificare
 * @return bool True se valido
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Reindirizza a una URL
 * @param string $url URL di destinazione
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Imposta un messaggio flash nella sessione
 * @param string $type Tipo di messaggio (success, error, info)
 * @param string $message Il messaggio
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

/**
 * Recupera e cancella un messaggio flash
 * @param string $type Tipo di messaggio
 * @return string|null Il messaggio o null
 */
function getFlashMessage($type) {
    $message = $_SESSION['flash'][$type] ?? null;
    unset($_SESSION['flash'][$type]);
    return $message;
}