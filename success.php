<?php
/**
 * ANTIQUA BARBAE - Pagina di Successo Pagamento
 * File: success.php
 */

require_once 'includes/config.php';

$order_id = intval($_GET['order_id'] ?? 0);
$order = null;

if ($order_id) {
    $order = querySingle("SELECT * FROM orders WHERE id = :id", [':id' => $order_id]);
}

$page_title = 'Pagamento Confermato - Antiqua Barbae';
require_once 'includes/header.php';
?>

<section class="success-section">
    <div class="container success-container">
        <div class="success-card">
            <div class="success-icon">✅</div>
            <h1 class="success-title">Grazie per il tuo ordine!</h1>
            <p class="success-message">Il pagamento è stato confermato con successo.</p>
            
            <?php if ($order): ?>
            <div class="order-details">
                <h3>Dettagli Ordine #<?php echo $order_id; ?></h3>
                <p><strong>Totale:</strong> €<?php echo number_format($order['total'], 2); ?></p>
                <p><strong>Cliente:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                <p><strong>Stato:</strong> 
                    <span class="order-status <?php echo $order['status']; ?>">
                        <?php echo $order['status'] === 'paid' ? 'Pagato' : 'In attesa'; ?>
                    </span>
                </p>
            </div>
            <?php endif; ?>
            
            <div class="success-note">
                <p>📧 Una conferma è stata inviata a <strong><?php echo htmlspecialchars($order['customer_email'] ?? 'tua email'); ?></strong> (simulazione).</p>
                <p>🔐 In produzione, qui arriveresti dopo il pagamento reale su Stripe/PayPal.</p>
            </div>
            
            <div class="success-actions">
                <a href="shop.php" class="btn btn-primary">🛍️ Continua lo Shopping</a>
                <a href="index.php" class="btn btn-outline">🏠 Torna alla Home</a>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>