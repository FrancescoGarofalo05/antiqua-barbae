<?php
/**
 * ANTIQUA BARBAE - Carrello Spesa
 * File: cart.php
 */

$page_title = 'Carrello - Antiqua Barbae';
$page_description = 'Riepilogo dei prodotti nel tuo carrello.';

require_once 'includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1 class="page-title">🛒 Il Tuo Carrello</h1>
        <p class="page-subtitle">Riepilogo dei prodotti selezionati</p>
    </div>
</section>

<section class="cart-section">
    <div class="container">
        <div id="cart-container">
            <!-- Il carrello verrà popolato via JavaScript -->
            <div class="cart-loading">Caricamento carrello...</div>
        </div>
        
        <div id="empty-cart-message" style="display: none;" class="empty-state">
            <p style="font-size: 3rem; margin-bottom: 1rem;">🛒</p>
            <p>Il tuo carrello è vuoto.</p>
            <a href="shop.php" class="btn btn-primary" style="margin-top: 1rem;">Vai allo Shop</a>
        </div>
    </div>
</section>

<script>
// Recupera carrello da LocalStorage
let cart = JSON.parse(localStorage.getItem('antiqua_cart')) || [];

const cartContainer = document.getElementById('cart-container');
const emptyMessage = document.getElementById('empty-cart-message');

function renderCart() {
    if (cart.length === 0) {
        cartContainer.style.display = 'none';
        emptyMessage.style.display = 'block';
        return;
    }
    
    cartContainer.style.display = 'block';
    emptyMessage.style.display = 'none';
    
    let html = `
        <div class="cart-table-wrapper">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Prodotto</th>
                        <th>Prezzo</th>
                        <th>Quantità</th>
                        <th>Totale</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    let subtotal = 0;
    
    cart.forEach((item, index) => {
        const itemTotal = item.price * item.quantity;
        subtotal += itemTotal;
        
        html += `
            <tr>
                <td class="cart-product">
                    <span class="cart-product-name">${item.name}</span>
                </td>
                <td class="cart-price">€${item.price.toFixed(2)}</td>
                <td class="cart-quantity">
                    <button class="qty-btn" onclick="updateQuantity(${index}, -1)">−</button>
                    <input type="number" value="${item.quantity}" min="1" max="99" 
                           onchange="setQuantity(${index}, this.value)" class="qty-input">
                    <button class="qty-btn" onclick="updateQuantity(${index}, 1)">+</button>
                </td>
                <td class="cart-total">€${itemTotal.toFixed(2)}</td>
                <td class="cart-remove">
                    <button class="remove-btn" onclick="removeItem(${index})">🗑️</button>
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
        
        <div class="cart-summary">
            <div class="cart-totals">
                <div class="total-row">
                    <span>Subtotale:</span>
                    <span>€${subtotal.toFixed(2)}</span>
                </div>
                <div class="total-row total-grand">
                    <span>Totale:</span>
                    <span>€${subtotal.toFixed(2)}</span>
                </div>
            </div>
            
            <div class="cart-actions">
                <a href="shop.php" class="btn btn-outline">← Continua lo Shopping</a>
                <button class="btn btn-primary" onclick="clearCart()">🗑️ Svuota Carrello</button>
                <a href="checkout.php" class="btn btn-success">💳 Procedi al Checkout →</a>
            </div>
        </div>
    `;
    
    cartContainer.innerHTML = html;
    updateCartCount();
}

function updateQuantity(index, change) {
    cart[index].quantity = Math.max(1, cart[index].quantity + change);
    saveCart();
}

function setQuantity(index, value) {
    cart[index].quantity = Math.max(1, parseInt(value) || 1);
    saveCart();
}

function removeItem(index) {
    cart.splice(index, 1);
    saveCart();
}

function clearCart() {
    if (confirm('Sei sicuro di voler svuotare il carrello?')) {
        cart = [];
        saveCart();
    }
}

function saveCart() {
    localStorage.setItem('antiqua_cart', JSON.stringify(cart));
    renderCart();
}

function updateCartCount() {
    const total = cart.reduce((sum, item) => sum + item.quantity, 0);
    const cartLink = document.querySelector('a[href="cart.php"]');
    if (cartLink) cartLink.textContent = `Carrello 🛒 (${total})`;
}

// Inizializza
renderCart();
</script>

<?php require_once 'includes/footer.php'; ?>