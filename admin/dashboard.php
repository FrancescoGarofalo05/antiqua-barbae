<?php
/**
 * ANTIQUA BARBAE - Dashboard Proprietario
 * File: admin/dashboard.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/config.php';

// Verifica login e ruolo owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: login.php');
    exit;
}

$barberia_id = $_SESSION['barberia_id'];

// Statistiche
$stats = [];
$stats['total_products'] = querySingle("SELECT COUNT(*) as total FROM products WHERE barberia_id = :bid", [':bid' => $barberia_id])['total'];
$stats['total_orders'] = querySingle("SELECT COUNT(*) as total FROM orders WHERE barberia_id = :bid", [':bid' => $barberia_id])['total'];
$stats['pending_orders'] = querySingle("SELECT COUNT(*) as total FROM orders WHERE barberia_id = :bid AND status = 'pending'", [':bid' => $barberia_id])['total'];
$stats['total_revenue'] = querySingle("SELECT SUM(total) as total FROM orders WHERE barberia_id = :bid AND status = 'paid'", [':bid' => $barberia_id])['total'] ?? 0;

// Ultimi ordini
$recent_orders = query("SELECT * FROM orders WHERE barberia_id = :bid ORDER BY created_at DESC LIMIT 5", [':bid' => $barberia_id]);

// Prodotti in esaurimento
$low_stock = query("SELECT * FROM products WHERE barberia_id = :bid AND stock <= 5 ORDER BY stock ASC", [':bid' => $barberia_id]);

$page_title = 'Dashboard - Antiqua Barbae';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-wrapper { display: flex; min-height: 100vh; background: #f7fafc; }
        .admin-sidebar { width: 280px; background: var(--color-accent); color: white; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; transition: transform 0.3s ease; z-index: 1000; overflow-y: auto; }
        .sidebar-header { padding: 1.5rem; border-bottom: 1px solid #3a2a1a; }
        .sidebar-logo { font-family: var(--font-secondary); font-size: 1.5rem; font-weight: 700; color: white; text-decoration: none; }
        .sidebar-logo span { color: var(--color-secondary); }
        .sidebar-user { padding: 1.5rem; border-bottom: 1px solid #3a2a1a; }
        .user-name { font-weight: 600; margin-bottom: 0.25rem; }
        .user-role { font-size: 0.85rem; opacity: 0.7; }
        .sidebar-nav { flex: 1; padding: 1.5rem 0; }
        .nav-section { margin-bottom: 1.5rem; }
        .nav-section-title { padding: 0 1.5rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; opacity: 0.5; margin-bottom: 0.75rem; }
        .nav-menu { list-style: none; }
        .nav-item { margin-bottom: 0.25rem; }
        .nav-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.5rem; color: #cbd5e0; text-decoration: none; transition: all 0.3s ease; }
        .nav-link:hover, .nav-link.active { background: var(--color-primary); color: white; }
        .nav-icon { font-size: 1.2rem; }
        .sidebar-footer { padding: 1.5rem; border-top: 1px solid #3a2a1a; }
        .admin-main { flex: 1; margin-left: 280px; padding: 2rem; transition: margin-left 0.3s ease; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .page-title { font-family: var(--font-secondary); font-size: 2rem; color: var(--color-dark); }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; border-radius: var(--border-radius-md); padding: 1.5rem; box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 1rem; }
        .stat-icon { width: 50px; height: 50px; background: var(--color-primary); color: white; border-radius: var(--border-radius-sm); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .stat-content h3 { font-size: 0.85rem; color: var(--color-gray); margin-bottom: 0.25rem; }
        .stat-value { font-size: 1.8rem; font-weight: 700; color: var(--color-dark); }
        .table-container { background: white; border-radius: var(--border-radius-md); padding: 1.5rem; box-shadow: var(--shadow-sm); margin-bottom: 2rem; overflow-x: auto; }
        .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .table-title { font-size: 1.2rem; font-weight: 600; }
        .admin-table { width: 100%; border-collapse: collapse; min-width: 600px; }
        .admin-table th { text-align: left; padding: 0.75rem; border-bottom: 2px solid #e8d5c4; color: var(--color-gray); }
        .admin-table td { padding: 0.75rem; border-bottom: 1px solid #e8d5c4; }
        .status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .status-paid { background: #E8F5E9; color: var(--color-success); }
        .status-pending { background: #FFF8E1; color: #F57F17; }
        .mobile-menu-toggle { display: none; position: fixed; top: 15px; left: 15px; z-index: 1001; background: var(--color-primary); color: white; border: none; width: 45px; height: 45px; border-radius: 8px; font-size: 1.5rem; cursor: pointer; }
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; }
        @media (max-width: 1024px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) {
            .mobile-menu-toggle { display: flex; }
            .sidebar-overlay { display: block; pointer-events: none; opacity: 0; transition: opacity 0.3s; }
            .sidebar-overlay.active { opacity: 1; pointer-events: auto; }
            .admin-sidebar { transform: translateX(-100%); position: fixed; width: 260px; }
            .admin-sidebar.mobile-open { transform: translateX(0); }
            .admin-main { margin-left: 0 !important; padding: 15px !important; padding-top: 70px !important; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle" id="mobileMenuToggle">☰</button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="admin-wrapper">
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-header">
                <a href="dashboard.php" class="sidebar-logo">Antiqua<span>Barbae</span></a>
            </div>
            <div class="sidebar-user">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                <div class="user-role">Proprietario</div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <p class="nav-section-title">Gestione</p>
                    <ul class="nav-menu">
                        <li class="nav-item"><a href="dashboard.php" class="nav-link active"><span class="nav-icon">📊</span><span>Dashboard</span></a></li>
                        <li class="nav-item"><a href="products/list.php" class="nav-link"><span class="nav-icon">🧴</span><span>Prodotti</span></a></li>
                        <li class="nav-item"><a href="orders/list.php" class="nav-link"><span class="nav-icon">📦</span><span>Ordini</span></a></li>
                    </ul>
                </div>
            </nav>
            <div class="sidebar-footer">
                <a href="../index.php" class="nav-link" target="_blank"><span class="nav-icon">🌐</span><span>Vai al sito</span></a>
                <a href="logout.php" class="nav-link" style="color: #f56565;"><span class="nav-icon">🚪</span><span>Logout</span></a>
            </div>
        </aside>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1 class="page-title">Dashboard</h1>
                <a href="products/create.php" class="btn btn-primary">➕ Nuovo Prodotto</a>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🧴</div>
                    <div class="stat-content"><h3>Prodotti</h3><div class="stat-value"><?php echo $stats['total_products']; ?></div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-content"><h3>Ordini Totali</h3><div class="stat-value"><?php echo $stats['total_orders']; ?></div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-content"><h3>In Attesa</h3><div class="stat-value"><?php echo $stats['pending_orders']; ?></div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content"><h3>Incasso</h3><div class="stat-value">€<?php echo number_format($stats['total_revenue'], 2); ?></div></div>
                </div>
            </div>
            
            <div class="table-container">
                <div class="table-header">
                    <h2 class="table-title">📋 Ultimi Ordini</h2>
                    <a href="orders/list.php" class="btn-outline-sm">Vedi tutti →</a>
                </div>
                <?php if (empty($recent_orders)): ?>
                    <p>Nessun ordine ancora.</p>
                <?php else: ?>
                    <table class="admin-table">
                        <thead><tr><th>ID</th><th>Cliente</th><th>Totale</th><th>Stato</th><th>Data</th></tr></thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td>€<?php echo number_format($order['total'], 2); ?></td>
                                    <td><span class="status-badge <?php echo $order['status'] === 'paid' ? 'status-paid' : 'status-pending'; ?>"><?php echo $order['status'] === 'paid' ? 'Pagato' : 'In attesa'; ?></span></td>
                                    <td><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($low_stock)): ?>
            <div class="table-container">
                <div class="table-header"><h2 class="table-title">⚠️ Scorte Basse</h2></div>
                <table class="admin-table">
                    <thead><tr><th>Prodotto</th><th>Stock</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($low_stock as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo $product['stock']; ?></td>
                                <td><a href="products/edit.php?id=<?php echo $product['id']; ?>" class="btn-outline-sm">Modifica</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
    const toggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    toggle?.addEventListener('click', () => { sidebar.classList.toggle('mobile-open'); overlay.classList.toggle('active'); toggle.textContent = sidebar.classList.contains('mobile-open') ? '✕' : '☰'; });
    overlay?.addEventListener('click', () => { sidebar.classList.remove('mobile-open'); overlay.classList.remove('active'); toggle.textContent = '☰'; });
    </script>
</body>
</html>