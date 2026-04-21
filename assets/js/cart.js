/**
 * ANTIQUA BARBAE - Gestione Carrello
 * File: assets/js/cart.js
 */

const CART_KEY = 'antiqua_cart';

// Recupera carrello
function getCart() {
    const cart = localStorage.getItem(CART_KEY);
    return cart ? JSON.parse(cart) : [];
}

// Salva carrello
function saveCart(cart) {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
    updateCartCount();
}

// Aggiungi prodotto
function addToCart(id, name, price, quantity = 1) {
    const cart = getCart();
    const existing = cart.find(item => item.id === id);
    
    if (existing) {
        existing.quantity += quantity;
    } else {
        cart.push({ id, name, price, quantity });
    }
    
    saveCart(cart);
    return cart;
}

// Rimuovi prodotto
function removeFromCart(id) {
    let cart = getCart();
    cart = cart.filter(item => item.id !== id);
    saveCart(cart);
    return cart;
}

// Aggiorna quantità
function updateQuantity(id, quantity) {
    const cart = getCart();
    const item = cart.find(item => item.id === id);
    if (item) {
        item.quantity = Math.max(1, quantity);
        saveCart(cart);
    }
    return cart;
}

// Svuota carrello
function clearCart() {
    localStorage.removeItem(CART_KEY);
    updateCartCount();
}

// Totale prodotti
function getCartTotal() {
    const cart = getCart();
    return cart.reduce((sum, item) => sum + item.quantity, 0);
}

// Totale prezzo
function getCartPriceTotal() {
    const cart = getCart();
    return cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
}

// Aggiorna contatore nel menu
function updateCartCount() {
    const total = getCartTotal();
    const cartLinks = document.querySelectorAll('a[href="cart.php"]');
    cartLinks.forEach(link => {
        link.textContent = `Carrello 🛒 (${total})`;
    });
}

// Inizializza al caricamento
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
});