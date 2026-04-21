<?php
/**
 * ANTIQUA BARBAE - Checkout
 * File: checkout.php
 */

require_once 'includes/config.php';

$page_title = 'Checkout - Antiqua Barbae';
$page_description = 'Completa il tuo ordine.';

$errors = [];
$success = false;

// Gestione invio form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $cart_data = $_POST['cart_data'] ?? '';
    
    // Validazione
    if (empty($customer_name)) $errors['customer_name'] = 'Nome obbligatorio.';
    if (empty($customer_email)) $errors['customer_email'] = 'Email obbligatoria.';
    elseif (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) $errors['customer_email'] = 'Email non valida.';
    
    $cart = json_decode($cart_data, true);
    if (empty($cart)) $errors['cart'] = 'Carrello vuoto.';
    
    if (empty($errors)) {
        // Calcola totale (RICONTROLLATO LATO SERVER per sicurezza!)
        $total = 0;
        foreach ($cart as $item) {
            // Verifica prezzo dal database (anti-manipolazione)
            $product = querySingle("SELECT price, barberia_id FROM products WHERE id = :id", [':id' => $item['id']]);
            if ($product) {
                $total += $product['price'] * $item['quantity'];
                $barberia_id = $product['barberia_id'];
            }
        }
        
        // Crea ordine nel database
        try {
            $pdo->beginTransaction();
            
            execute(
                "INSERT INTO orders (barberia_id, customer_name, customer_email, total, status) 
                 VALUES (:bid, :name, :email, :total, 'pending')",
                [
                    ':bid' => $barberia_id,
                    ':name' => $customer_name,
                    ':email' => $customer_email,
                    ':total' => $total
                ]
            );
            $order_id = $pdo->lastInsertId();
            
            // Inserisci items
            foreach ($cart as $item) {
                $product = querySingle("SELECT price FROM products WHERE id = :id", [':id' => $item['id']]);
                execute(
                    "INSERT INTO order_items (order_id, product_id, quantity, price) 
                     VALUES (:oid, :pid, :qty, :price)",
                    [
                        ':oid' => $order_id,
                        ':pid' => $item['id'],
                        ':qty' => $item['quantity'],
                        ':price' => $product['price']
                    ]
                );
            }
            
            $pdo->commit();
            $success = true;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors['general'] = 'Errore durante la creazione dell\'ordine.';
            error_log('Errore checkout: ' . $e->getMessage());
        }
    }
}

require_once 'includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1 class="page-title">💳 Checkout</h1>
        <p class="page-subtitle">Completa il tuo ordine</p>
    </div>
</section>

<section class="checkout-section">
    <div class="container checkout-container">
        <?php if ($success): ?>
            <div class="alert-success">
                <h2>✅ Ordine Creato!</h2>
                <p>Il tuo ordine è stato registrato. Procedi al pagamento.</p>
                <a href="fake-payment.php?order_id=<?php echo $order_id; ?>" class="btn btn-primary">💳 Vai al Pagamento</a>
            </div>
        <?php else: ?>
            <div id="checkout-content">
                <!-- Popolato via JS -->
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
const cart = JSON.parse(localStorage.getItem('antiqua_cart')) || [];

if (cart.length === 0) {
    window.location.href = 'cart.php';
}

function renderCheckout() {
    let subtotal = 0;
    let itemsHtml = '';
    
    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        subtotal += itemTotal;
        itemsHtml += `
            <div class="checkout-item">
                <span>${item.name} x ${item.quantity}</span>
                <span>€${itemTotal.toFixed(2)}</span>
            </div>
        `;
    });
    
    const html = `
        <div class="checkout-grid">
            <div class="checkout-form-container">
                <h2>Dati Cliente</h2>
                <form method="POST" action="checkout.php" class="checkout-form">
                    <input type="hidden" name="cart_data" value='${JSON.stringify(cart)}'>
                    
                    <div class="form-group">
                        <label for="customer_name">Nome Completo *</label>
                        <input type="text" id="customer_name" name="customer_name" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_email">Email *</label>
                        <input type="email" id="customer_email" name="customer_email" class="form-input" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-large">💳 Procedi al Pagamento</button>
                </form>
            </div>
            
            <div class="checkout-summary">
                <h2>Riepilogo Ordine</h2>
                <div class="checkout-items">
                    ${itemsHtml}
                </div>
                <div class="checkout-total">
                    <span>Totale:</span>
                    <span>€${subtotal.toFixed(2)}</span>
                </div>
                <a href="cart.php" class="back-link">← Torna al Carrello</a>
            </div>
        </div>
    `;
    
    document.getElementById('checkout-content').innerHTML = html;
}

renderCheckout();
</script>

<?php require_once 'includes/footer.php'; ?>