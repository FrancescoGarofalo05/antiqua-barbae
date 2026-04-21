<?php
/**
 * ANTIQUA BARBAE - Dettaglio Ordine
 * File: admin/orders/detail.php
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: ../login.php');
    exit;
}

$barberia_id = $_SESSION['barberia_id'];

// Verifica ID ordine
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'ID ordine non specificato.';
    header('Location: list.php');
    exit;
}

$order_id = intval($_GET['id']);

// Recupera ordine
$order = querySingle("SELECT * FROM orders WHERE id = :id AND barberia_id = :bid", [':id' => $order_id, ':bid' => $barberia_id]);

if (!$order) {
    $_SESSION['error'] = 'Ordine non trovato.';
    header('Location: list.php');
    exit;
}

// Recupera items dell'ordine
$items = query("SELECT oi.*, p.name as product_name, p.image as product_image 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = :oid", [':oid' => $order_id]);

// Gestione cambio stato (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = $_POST['status'] ?? '';
    if (in_array($new_status, ['pending', 'paid', 'cancelled'])) {
        execute("UPDATE orders SET status = :status WHERE id = :id", [':status' => $new_status, ':id' => $order_id]);
        $_SESSION['success'] = 'Stato ordine aggiornato!';
        header("Location: detail.php?id=$order_id");
        exit;
    }
}

$page_title = 'Ordine #' . $order_id . ' - Antiqua Barbae';
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
        .admin-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
        .page-title { font-family: 'Playfair Display', serif; font-size: 2rem; }
        
        .detail-grid { display: grid; grid-template-columns: 1fr 350px; gap: 1.5rem; }
        .detail-card { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .card-title { font-size: 1.2rem; font-weight: 600; margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e8d5c4; }
        
        .customer-info p { margin-bottom: 0.5rem; }
        .status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .status-paid { background: #E8F5E9; color: #2E7D32; }
        .status-pending { background: #FFF8E1; color: #F57F17; }
        .status-cancelled { background: #FFEBEE; color: #C62828; }
        
        .items-table { width: 100%; border-collapse: collapse; }
        .items-table th { text-align: left; padding: 0.75rem; border-bottom: 2px solid #e8d5c4; background: #FDF8F5; }
        .items-table td { padding: 0.75rem; border-bottom: 1px solid #e8d5c4; }
        .item-image { width: 50px; height: 50px; background: #f5ebe0; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
        .item-image img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; }
        .total-row { display: flex; justify-content: space-between; padding: 1rem 0; font-size: 1.1rem; }
        .grand-total { font-size: 1.3rem; font-weight: 700; border-top: 2px solid #e8d5c4; padding-top: 1rem; margin-top: 0.5rem; }
        
        .status-form { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e8d5c4; }
        .status-select { width: 100%; padding: 0.75rem; border: 2px solid #e8d5c4; border-radius: 8px; margin-bottom: 1rem; }
        .btn-update { width: 100%; padding: 0.75rem; background: #8B4513; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn-update:hover { background: #2C1810; }
        
        .mobile-menu-toggle { display: none; position: fixed; top: 15px; left: 15px; z-index: 1001; background: #8B4513; color: white; border: none; width: 45px; height: 45px; border-radius: 8px; font-size: 1.5rem; cursor: pointer; }
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; }
        
        @media (max-width: 768px) {
            .mobile-menu-toggle { display: flex; }
            .sidebar-overlay { display: block; opacity: 0; pointer-events: none; transition: opacity 0.3s; }
            .sidebar-overlay.active { opacity: 1; pointer-events: auto; }
            .admin-sidebar { transform: translateX(-100%); width: 260px; }
            .admin-sidebar.mobile-open { transform: translateX(0); }
            .admin-main { margin-left: 0 !important; padding: 15px !important; padding-top: 70px !important; }
            .detail-grid { grid-template-columns: 1fr; }
            .items-table { min-width: 500px; }
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
                        <li><a href="../products/list.php" class="nav-link"><span class="nav-icon">🧴</span><span>Prodotti</span></a></li>
                        <li><a href="list.php" class="nav-link active"><span class="nav-icon">📦</span><span>Ordini</span></a></li>
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
                <a href="list.php" class="btn btn-outline">← Torna agli Ordini</a>
                <h1 class="page-title">Ordine #<?php echo $order_id; ?></h1>
                <span class="status-badge <?php echo $order['status'] === 'paid' ? 'status-paid' : ($order['status'] === 'pending' ? 'status-pending' : 'status-cancelled'); ?>">
                    <?php echo $order['status'] === 'paid' ? '✅ Pagato' : ($order['status'] === 'pending' ? '⏳ In attesa' : '❌ Cancellato'); ?>
                </span>
            </div>
            
            <div class="detail-grid">
                <div class="detail-card">
                    <h2 class="card-title">📦 Prodotti Ordinati</h2>
                    <div style="overflow-x:auto;">
                        <table class="items-table">
                            <thead><tr><th></th><th>Prodotto</th><th>Prezzo</th><th>Qtà</th><th>Totale</th></tr></thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><div class="item-image"><?php if ($item['product_image']): ?><img src="../../assets/images/<?php echo $item['product_image']; ?>" alt=""><?php else: ?>🧴<?php endif; ?></div></td>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td>€<?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>€<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="total-row"><span>Subtotale:</span><span>€<?php echo number_format($order['total'], 2); ?></span></div>
                    <div class="grand-total"><span>Totale Ordine:</span><span>€<?php echo number_format($order['total'], 2); ?></span></div>
                </div>
                
                <div>
                    <div class="detail-card">
                        <h2 class="card-title">👤 Cliente</h2>
                        <div class="customer-info">
                            <p><strong>Nome:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                            <p><strong>Data Ordine:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                        </div>
                    </div>
                    
                    <div class="detail-card">
                        <h2 class="card-title">⚙️ Gestione Stato</h2>
                        <form method="POST" class="status-form">
                            <label>Stato Attuale:</label>
                            <select name="status" class="status-select">
                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>⏳ In Attesa</option>
                                <option value="paid" <?php echo $order['status'] === 'paid' ? 'selected' : ''; ?>>✅ Pagato</option>
                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>❌ Cancellato</option>
                            </select>
                            <button type="submit" class="btn-update">🔄 Aggiorna Stato</button>
                        </form>
                        <p style="font-size:0.85rem; color:#4A4A4A; margin-top:1rem;">Se segni come "Pagato", lo stock dei prodotti verrà automaticamente scalato.</p>
                    </div>
                </div>
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