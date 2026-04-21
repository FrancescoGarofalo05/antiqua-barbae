<?php
$page_title = 'Cura della Barba Artigianale';
$page_description = 'Scopri i nostri oli, cere e kit da barba artigianali per una rasatura perfetta.';
require_once 'includes/header.php';
?>

<section class="hero">
    <div class="hero-image-container">
        <img src="./assets/images/hero-bg.jpg" 
             alt="Barbershop vintage con prodotti da barba artigianali" 
             class="hero-img"
             loading="eager">
        <div class="hero-overlay">
            <div class="container hero-container">
                <div class="hero-content">
                    <h1 class="hero-title">L'Arte della <span class="hero-accent">Rasatura</span></h1>
                    <p class="hero-text">Oli, cere e kit da barba artigianali per prenderti cura della tua barba con prodotti naturali e di qualità.</p>
                    <div class="hero-actions">
                        <a href="shop.php" class="btn btn-primary">🛍️ Vai allo Shop</a>
                        <a href="#featured" class="btn btn-outline">Scopri di più</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="featured-products" id="featured">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Prodotti in Evidenza</h2>
            <p class="section-subtitle">I nostri best seller per la cura della barba</p>
        </div>
        <div class="products-grid">
            <?php
            require_once 'includes/config.php';
            $products = query("SELECT * FROM products LIMIT 3");
            foreach ($products as $product):
            ?>
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
                    <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p class="product-price">€<?php echo number_format($product['price'], 2); ?></p>
                    <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline btn-sm">Vedi Dettagli</a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>