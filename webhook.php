<?php
/**
 * ANTIQUA BARBAE - Webhook di Conferma Pagamento
 * File: webhook.php
 * 
 * Simula la notifica server-to-server che Stripe/PayPal invia dopo il pagamento.
 * In produzione, questo endpoint riceverebbe una richiesta POST dal gateway.
 */

require_once 'includes/config.php';

// Verifica che sia una richiesta POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Verifica che sia una simulazione
if (!isset($_POST['simulate_payment']) || $_POST['simulate_payment'] !== '1') {
    http_response_code(400);
    exit('Invalid request');
}

$order_id = intval($_POST['order_id'] ?? 0);

if (!$order_id) {
    http_response_code(400);
    exit('Order ID mancante');
}

// Verifica che l'ordine esista e non sia già pagato
$order = querySingle("SELECT * FROM orders WHERE id = :id", [':id' => $order_id]);

if (!$order) {
    http_response_code(404);
    exit('Ordine non trovato');
}

if ($order['status'] === 'paid') {
    // Già pagato, reindirizza comunque alla success
    header('Location: success.php?order_id=' . $order_id);
    exit;
}

// Simula la verifica della firma digitale del webhook
// In produzione, qui verificheresti che la richiesta provenga veramente da Stripe/PayPal
$signature_valid = true; // Simulazione

if (!$signature_valid) {
    http_response_code(401);
    exit('Invalid signature');
}

// Aggiorna lo stato dell'ordine a "paid"
execute("UPDATE orders SET status = 'paid' WHERE id = :id", [':id' => $order_id]);

// Aggiorna lo stock dei prodotti (decrementa quantità)
$items = query("SELECT product_id, quantity FROM order_items WHERE order_id = :oid", [':oid' => $order_id]);
foreach ($items as $item) {
    execute(
        "UPDATE products SET stock = stock - :qty WHERE id = :pid AND stock >= :qty",
        [':qty' => $item['quantity'], ':pid' => $item['product_id']]
    );
}

// Log del pagamento
$log_dir = __DIR__ . '/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0777, true);
}
$log_message = date('Y-m-d H:i:s') . " - PAGAMENTO CONFERMATO: Ordine #$order_id | Totale: €{$order['total']} | Cliente: {$order['customer_email']}\n";
@file_put_contents($log_dir . '/payments.log', $log_message, FILE_APPEND);

// Reindirizza alla pagina di successo
header('Location: success.php?order_id=' . $order_id);
exit;