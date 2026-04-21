<?php
/**
 * ANTIQUA BARBAE - Dettaglio Prodotto
 * File: product.php
 */

require_once 'includes/config.php';

// Avvia sessione se non già attiva
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se l'utente è loggato
$is_logged_in = isset($_SESSION['user_id']);

// Verifica che sia stato passato un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: shop.php');
    exit;
}

$id = intval($_GET['id']);

// Recupera il prodotto
$sql = "SELECT p.*, c.name as category_name, c.slug as category_slug 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = :id";
$product = querySingle($sql, [':id' => $id]);

// Se il prodotto non esiste, reindirizza
if (!$product) {
    header('Location: shop.php');
    exit;
}

// Prodotti correlati (stessa categoria)
$related_sql = "SELECT * FROM products WHERE category_id = :cid AND id != :id AND stock > 0 LIMIT 4";
$related_products = query($related_sql, [':cid' => $product['category_id'], ':id' => $id]);

// Imposta titolo pagina
$page_title = $product['name'];
$page_description = substr(strip_tags($product['description']), 0, 160);

require_once 'includes/header.php';
?>

<!-- Breadcrumb -->
<section class="breadcrumb-section">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <ol>
                <li><a href="index.php">Home</a></li>
                <li><a href="shop.php">Shop</a></li>
                <?php if ($product['category_name']): ?>
                    <li><a href="shop.php?category=<?php echo urlencode($product['category_slug']); ?>"><?php echo htmlspecialchars($product['category_name']); ?></a></li>
                <?php endif; ?>
                <li aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
            </ol>
        </nav>
    </div>
</section>

<!-- Dettaglio Prodotto -->
<section class="product-detail-section">
    <div class="container product-detail-container">
        <!-- Immagine -->
        <div class="product-detail-image">
            <?php if ($product['image']): ?>
                <img src="./assets/images/<?php echo htmlspecialchars($product['image']); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>">
            <?php else: ?>
                <span class="product-placeholder-large">🧴</span>
            <?php endif; ?>
        </div>
        
        <!-- Info -->
        <div class="product-detail-info">
            <span class="product-category-tag"><?php echo htmlspecialchars($product['category_name'] ?? 'Senza categoria'); ?></span>
            <h1 class="product-detail-title"><?php echo htmlspecialchars($product['name']); ?></h1>
            <p class="product-detail-price">€<?php echo number_format($product['price'], 2); ?></p>
            
            <div class="product-detail-description">
                <?php echo $product['description']; ?>
            </div>
            
            <div class="product-detail-stock">
                <?php if ($product['stock'] > 0): ?>
                    <span class="stock-available">✅ Disponibile (<?php echo $product['stock']; ?> pezzi)</span>
                <?php else: ?>
                    <span class="stock-unavailable">❌ Esaurito</span>
                <?php endif; ?>
            </div>
            
            <div class="product-detail-actions">
                <?php if ($is_logged_in): ?>
                    <!-- Utente loggato: può acquistare -->
                    <label for="quantity">Quantità:</label>
                    <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="quantity-input">
                    
                    <button class="btn btn-primary btn-large add-to-cart-detail" 
                            data-id="<?php echo $product['id']; ?>"
                            data-name="<?php echo htmlspecialchars($product['name']); ?>"
                            data-price="<?php echo $product['price']; ?>"
                            <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>
                        🛒 Aggiungi al Carrello
                    </button>
                <?php else: ?>
                    <!-- Utente NON loggato: messaggio e link login -->
                    <div class="login-prompt">
                        <p>🔐 <strong>Accedi o registrati</strong> per aggiungere prodotti al carrello e completare l'acquisto.</p>
                        <div class="login-prompt-actions">
                            <a href="admin/login.php" class="btn btn-primary">Accedi</a>
                            <a href="admin/register.php" class="btn btn-outline">Registrati</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <a href="shop.php" class="back-link">← Torna allo Shop</a>
        </div>
    </div>
</section>

<!-- Prodotti Correlati -->
<?php if (!empty($related_products)): ?>
<section class="related-products">
    <div class="container">
        <h2 class="section-title">Potrebbero Interessarti</h2>
        <div class="products-grid">
            <?php foreach ($related_products as $related): ?>
                <article class="product-card">
                    <div class="product-image">
                        <?php if ($related['image']): ?>
                            <img src="./assets/images/<?php echo htmlspecialchars($related['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($related['name']); ?>">
                        <?php else: ?>
                            <span class="product-placeholder">🧴</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title">
                            <a href="product.php?id=<?php echo $related['id']; ?>"><?php echo htmlspecialchars($related['name']); ?></a>
                        </h3>
                        <p class="product-price">€<?php echo number_format($related['price'], 2); ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
<?php if ($is_logged_in): ?>
document.querySelector('.add-to-cart-detail')?.addEventListener('click', function() {
    const id = this.dataset.id;
    const name = this.dataset.name;
    const price = parseFloat(this.dataset.price);
    const quantity = parseInt(document.getElementById('quantity').value);
    
    let cart = JSON.parse(localStorage.getItem('antiqua_cart')) || [];
    const existing = cart.find(item => item.id === id);
    
    if (existing) {
        existing.quantity += quantity;
    } else {
        cart.push({ id, name, price, quantity });
    }
    
    localStorage.setItem('antiqua_cart', JSON.stringify(cart));
    this.textContent = '✅ Aggiunto!';
    setTimeout(() => { this.textContent = '🛒 Aggiungi al Carrello'; }, 1000);
    updateCartCount();
});
<?php endif; ?>

function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('antiqua_cart')) || [];
    const total = cart.reduce((sum, item) => sum + item.quantity, 0);
    const cartLink = document.querySelector('a[href="cart.php"]');
    if (cartLink) cartLink.textContent = `Carrello 🛒 (${total})`;
}
updateCartCount();
</script>

<?php require_once 'includes/footer.php'; ?>
