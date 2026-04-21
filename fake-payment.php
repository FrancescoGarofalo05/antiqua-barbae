<?php
/**
 * ANTIQUA BARBAE - Simulazione Gateway di Pagamento
 * File: fake-payment.php
 * 
 * Simula la pagina di pagamento di Stripe/PayPal.
 * In un ambiente reale, qui avverrebbe il reindirizzamento al gateway esterno.
 */

require_once 'includes/config.php';

// Verifica che sia stato passato un order_id
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    header('Location: shop.php');
    exit;
}

$order_id = intval($_GET['order_id']);

// Recupera l'ordine
$order = querySingle("SELECT * FROM orders WHERE id = :id", [':id' => $order_id]);

if (!$order) {
    header('Location: shop.php');
    exit;
}

// Se l'ordine è già pagato, reindirizza alla pagina di successo
if ($order['status'] === 'paid') {
    header('Location: success.php?order_id=' . $order_id);
    exit;
}

$page_title = 'Pagamento - Antiqua Barbae';
require_once 'includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1 class="page-title">💳 Pagamento Sicuro</h1>
        <p class="page-subtitle">Simulazione gateway di pagamento (Stripe/PayPal)</p>
    </div>
</section>

<section class="payment-section">
    <div class="container payment-container">
        <div class="payment-card">
            <div class="payment-header">
                <span class="payment-logo">💳 Stripe</span>
                <span class="payment-badge">🔒 Connessione Sicura</span>
            </div>
            
            <div class="payment-order-summary">
                <h3>Riepilogo Ordine #<?php echo $order_id; ?></h3>
                <p class="payment-total">Totale: <strong>€<?php echo number_format($order['total'], 2); ?></strong></p>
                <p class="payment-customer">Cliente: <?php echo htmlspecialchars($order['customer_name']); ?></p>
            </div>
            
            <form id="payment-form" method="POST" action="webhook.php" class="payment-form">
                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                <input type="hidden" name="simulate_payment" value="1">
                
                <div class="form-group">
                    <label for="card_number">Numero Carta</label>
                    <input type="text" id="card_number" name="card_number" class="form-input" 
                           placeholder="4242 4242 4242 4242" value="4242424242424242" readonly disabled>
                    <p class="form-hint">(Carta di test - simulazione)</p>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="expiry">Data Scadenza</label>
                        <input type="text" id="expiry" class="form-input" placeholder="MM/AA" value="12/28" readonly disabled>
                    </div>
                    <div class="form-group">
                        <label for="cvc">CVC</label>
                        <input type="text" id="cvc" class="form-input" placeholder="123" value="123" readonly disabled>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="card_name">Nome sulla Carta</label>
                    <input type="text" id="card_name" class="form-input" 
                           value="<?php echo htmlspecialchars($order['customer_name']); ?>" readonly disabled>
                </div>
                
                <div class="payment-actions">
                    <button type="button" onclick="history.back()" class="btn btn-outline">← Indietro</button>
                    <button type="submit" class="btn btn-success">💶 Paga €<?php echo number_format($order['total'], 2); ?></button>
                </div>
            </form>
            
            <div class="payment-note">
                <p>⚠️ <strong>Nota:</strong> Questa è una simulazione. In un ambiente reale, qui avverrebbe il reindirizzamento a Stripe/PayPal e i dati della carta non toccherebbero mai il tuo server.</p>
                <p>🔐 Il pagamento verrà "confermato" tramite webhook simulato.</p>
            </div>
        </div>
    </div>
</section>

<script>
document.getElementById('payment-form').addEventListener('submit', function(e) {
    // Simula elaborazione pagamento
    const btn = this.querySelector('button[type="submit"]');
    btn.textContent = '⏳ Elaborazione...';
    btn.disabled = true;
});
</script>

<?php require_once 'includes/footer.php'; ?>