<?php
/**
 * ANTIQUA BARBAE - Elimina Prodotto
 * File: admin/products/delete.php
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: ../login.php');
    exit;
}

$barberia_id = $_SESSION['barberia_id'];

// Verifica ID prodotto
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'ID prodotto non specificato.';
    header('Location: list.php');
    exit;
}

$id = intval($_GET['id']);
$product = querySingle("SELECT * FROM products WHERE id = :id AND barberia_id = :bid", [':id' => $id, ':bid' => $barberia_id]);

if (!$product) {
    $_SESSION['error'] = 'Prodotto non trovato.';
    header('Location: list.php');
    exit;
}

// Gestione eliminazione (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['confirm']) || $_POST['confirm'] !== 'yes') {
        header('Location: list.php');
        exit;
    }
    
    // Elimina immagine se presente
    if ($product['image'] && file_exists('../../assets/images/' . $product['image'])) {
        unlink('../../assets/images/' . $product['image']);
    }
    
    // Elimina prodotto
    execute("DELETE FROM products WHERE id = :id", [':id' => $id]);
    
    $_SESSION['success'] = 'Prodotto eliminato con successo!';
    header('Location: list.php');
    exit;
}

$page_title = 'Elimina Prodotto - Antiqua Barbae';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .admin-wrapper { display: flex; min-height: 100vh; background: #f7fafc; }
        .admin-sidebar { width: 280px; background: #2C1810; color: white; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; transition: transform 0.3s; z-index: 1000; }
        .sidebar-header { padding: 1.5rem; border-bottom: 1px solid #3a2a1a; }
        .sidebar-logo { font-family: 'Playfair Display', serif; font-size: 1.5rem; font-weight: 700; color: white; text-decoration: none; }
        .sidebar-logo span { color: #D4A373; }
        .sidebar-user { padding: 1.5rem; border-bottom: 1px solid #3a2a1a; }
        .user-name { font-weight: 600; }
        .user-role { font-size: 0.85rem; opacity: 0.7; }
        .sidebar-nav { flex: 1; padding: 1.5rem 0; }
        .nav-section { margin-bottom: 1.5rem; }
        .nav-section-title { padding: 0 1.5rem; font-size: 0.75rem; text-transform: uppercase; opacity: 0.5; margin-bottom: 0.75rem; }
        .nav-menu { list-style: none; }
        .nav-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.5rem; color: #cbd5e0; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: #8B4513; color: white; }
        .nav-icon { font-size: 1.2rem; }
        .sidebar-footer { padding: 1.5rem; border-top: 1px solid #3a2a1a; }
        .admin-main { flex: 1; margin-left: 280px; padding: 2rem; transition: margin-left 0.3s; }
        .admin-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; }
        .page-title { font-family: 'Playfair Display', serif; font-size: 2rem; }
        .confirm-container { background: white; border-radius: 12px; padding: 2.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04); max-width: 550px; margin: 0 auto; text-align: center; }
        .confirm-icon { font-size: 4rem; margin-bottom: 1.5rem; }
        .confirm-title { font-size: 1.5rem; margin-bottom: 1rem; }
        .confirm-message { color: #4A4A4A; margin-bottom: 1.5rem; }
        .product-name { font-weight: 700; color: #8B4513; background: #FDF8F5; padding: 0.5rem 1rem; border-radius: 8px; margin: 1rem 0; }
        .confirm-warning { background: #FFEBEE; border: 1px solid #C62828; color: #B71C1C; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; font-weight: 500; }
        .confirm-actions { display: flex; gap: 1rem; justify-content: center; }
        .btn-danger { background: #C62828; color: white; border: none; padding: 0.75rem 2rem; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn-danger:hover { background: #B71C1C; transform: translateY(-2px); }
        .mobile-menu-toggle { display: none; position: fixed; top: 15px; left: 15px; z-index: 1001; background: #8B4513; color: white; border: none; width: 45px; height: 45px; border-radius: 8px; font-size: 1.5rem; cursor: pointer; }
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; }
        @media (max-width: 768px) {
            .mobile-menu-toggle { display: flex; }
            .sidebar-overlay { display: block; opacity: 0; pointer-events: none; transition: opacity 0.3s; }
            .sidebar-overlay.active { opacity: 1; pointer-events: auto; }
            .admin-sidebar { transform: translateX(-100%); width: 260px; }
            .admin-sidebar.mobile-open { transform: translateX(0); }
            .admin-main { margin-left: 0 !important; padding: 15px !important; padding-top: 70px !important; }
            .confirm-actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle" id="mobileMenuToggle">☰</button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="admin-wrapper">
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-header"><a href="../dashboard.php" class="sidebar-logo">Antiqua<span>Barbae</span></a></div>
            <div class="sidebar-user"><div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div><div class="user-role">Proprietario</div></div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <p class="nav-section-title">Gestione</p>
                    <ul class="nav-menu">
                        <li><a href="../dashboard.php" class="nav-link"><span class="nav-icon">📊</span><span>Dashboard</span></a></li>
                        <li><a href="list.php" class="nav-link active"><span class="nav-icon">🧴</span><span>Prodotti</span></a></li>
                        <li><a href="../orders/list.php" class="nav-link"><span class="nav-icon">📦</span><span>Ordini</span></a></li>
                    </ul>
                </div>
            </nav>
            <div class="sidebar-footer">
                <a href="../../index.php" class="nav-link" target="_blank"><span class="nav-icon">🌐</span><span>Vai al sito</span></a>
                <a href="../logout.php" class="nav-link" style="color:#f56565;"><span class="nav-icon">🚪</span><span>Logout</span></a>
            </div>
        </aside>
        
        <main class="admin-main">
            <div class="admin-header">
                <a href="list.php" class="btn btn-outline">← Indietro</a>
                <h1 class="page-title">Elimina Prodotto</h1>
            </div>
            
            <div class="confirm-container">
                <div class="confirm-icon">⚠️</div>
                <h2 class="confirm-title">Conferma Eliminazione</h2>
                <p class="confirm-message">Stai per eliminare definitivamente questo prodotto:</p>
                
                <div class="product-name">"<?php echo htmlspecialchars($product['name']); ?>"</div>
                
                <div class="confirm-warning">
                    Questa azione è <strong>irreversibile</strong>. Il prodotto verrà rimosso dal catalogo e non sarà più disponibile per l'acquisto.
                </div>
                
                <form method="POST">
                    <input type="hidden" name="confirm" value="yes">
                    <div class="confirm-actions">
                        <button type="submit" class="btn-danger">🗑️ Sì, Elimina Definitivamente</button>
                        <a href="list.php" class="btn btn-outline">❌ Annulla</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <script>
    const t=document.getElementById('mobileMenuToggle'), s=document.getElementById('adminSidebar'), o=document.getElementById('sidebarOverlay');
    t?.addEventListener('click',()=>{s.classList.toggle('mobile-open');o.classList.toggle('active');t.textContent=s.classList.contains('mobile-open')?'✕':'☰';});
    o?.addEventListener('click',()=>{s.classList.remove('mobile-open');o.classList.remove('active');t.textContent='☰';});
    </script>
</body>
</html>