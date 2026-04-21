<?php
/**
 * ANTIQUA BARBAE - Shop / Catalogo Prodotti
 * File: shop.php
 */

require_once 'includes/config.php';

// Recupera il parametro categoria dalla URL
$category_slug = $_GET['category'] ?? '';
$category_name = 'Tutti i Prodotti';

// Se è selezionata una categoria, recupera il nome
if ($category_slug) {
    $cat = querySingle("SELECT name FROM categories WHERE slug = :slug", [':slug' => $category_slug]);
    if ($cat) {
        $category_name = htmlspecialchars($cat['name']);
    }
}

// Costruisce la query con filtro categoria
$sql = "SELECT p.*, c.name as category_name, c.slug as category_slug 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.stock > 0";
$params = [];

if ($category_slug) {
    $sql .= " AND c.slug = :slug";
    $params[':slug'] = $category_slug;
}

$sql .= " ORDER BY p.created_at DESC";
$products = query($sql, $params);

// Recupera tutte le categorie per il filtro
$categories = query("SELECT * FROM categories ORDER BY name");

// Imposta titolo pagina
$page_title = 'Shop - ' . $category_name;
$page_description = 'Scopri i nostri prodotti artigianali per la cura della barba: oli, cere, kit regalo e accessori.';

require_once 'includes/header.php';
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1 class="page-title"><?php echo $category_name; ?></h1>
        <p class="page-subtitle"><?php echo count($products); ?> prodotti disponibili</p>
    </div>
</section>

<!-- Shop Content -->
<section class="shop-section">
    <div class="container shop-container">
        <!-- Sidebar Filtri -->
        <aside class="shop-sidebar">
            <div class="sidebar-widget">
                <h3 class="widget-title">Categorie</h3>
                <ul class="category-list">
                    <li>
                        <a href="shop.php" class="<?php echo !$category_slug ? 'active' : ''; ?>">
                            🛍️ Tutti i prodotti
                            <span class="count">(<?php echo count(query("SELECT id FROM products WHERE stock > 0")); ?>)</span>
                        </a>
                    </li>
                    <?php foreach ($categories as $cat): ?>
                        <?php
                        $count_sql = "SELECT COUNT(*) as cnt FROM products WHERE category_id = :cid AND stock > 0";
                        $count = querySingle($count_sql, [':cid' => $cat['id']])['cnt'];
                        ?>
                        <li>
                            <a href="shop.php?category=<?php echo urlencode($cat['slug']); ?>" 
                               class="<?php echo $category_slug === $cat['slug'] ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($cat['name']); ?>
                                <span class="count">(<?php echo $count; ?>)</span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>

        <!-- Griglia Prodotti -->
        <div class="shop-content">
            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <p style="font-size: 3rem; margin-bottom: 1rem;">🧴</p>
                    <p>Nessun prodotto disponibile in questa categoria.</p>
                    <a href="shop.php" class="btn btn-primary" style="margin-top: 1rem;">Vedi tutti i prodotti</a>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <article class="product-card">
                            <div class="product-image">
                                <?php if ($product['image']): ?>
                                    <img src="./assets/images/<?php echo htmlspecialchars($product['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <span class="product-placeholder">🧴</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <span class="product-category"><?php echo htmlspecialchars($product['category_name'] ?? 'Senza categoria'); ?></span>
                                <h3 class="product-title">
                                    <a href="product.php?id=<?php echo $product['id']; ?>">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                </h3>
                                <p class="product-price">€<?php echo number_format($product['price'], 2); ?></p>
                                <div class="product-actions">
                                    <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline btn-sm">Dettagli</a>
                                    <button class="btn btn-primary btn-sm add-to-cart" 
                                            data-id="<?php echo $product['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                            data-price="<?php echo $product['price']; ?>">
                                        🛒 Aggiungi
                                    </button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
// Funzione per aggiungere al carrello (usando LocalStorage)
document.querySelectorAll('.add-to-cart').forEach(button => {
    button.addEventListener('click', function() {
        const id = this.dataset.id;
        const name = this.dataset.name;
        const price = parseFloat(this.dataset.price);
        
        // Recupera carrello esistente
        let cart = JSON.parse(localStorage.getItem('antiqua_cart')) || [];
        
        // Cerca se il prodotto è già nel carrello
        const existing = cart.find(item => item.id === id);
        if (existing) {
            existing.quantity++;
        } else {
            cart.push({ id, name, price, quantity: 1 });
        }
        
        // Salva in LocalStorage
        localStorage.setItem('antiqua_cart', JSON.stringify(cart));
        
        // Feedback visivo
        this.textContent = '✅ Aggiunto!';
        setTimeout(() => { this.textContent = '🛒 Aggiungi'; }, 1000);
        
        // Aggiorna contatore carrello (se presente)
        updateCartCount();
    });
});

function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('antiqua_cart')) || [];
    const total = cart.reduce((sum, item) => sum + item.quantity, 0);
    const cartLink = document.querySelector('a[href="cart.php"]');
    if (cartLink) {
        cartLink.textContent = `Carrello 🛒 (${total})`;
    }
}

// Inizializza contatore
updateCartCount();
</script>

<?php require_once 'includes/footer.php'; ?>