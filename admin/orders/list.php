<?php
/**
 * ANTIQUA BARBAE - Lista Ordini (Proprietario)
 * File: admin/orders/list.php
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../../includes/config.php';

// Verifica login e ruolo owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: ../login.php');
    exit;
}

$barberia_id = $_SESSION['barberia_id'];

// Filtri
$status_filter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Costruisce WHERE
$where = ["o.barberia_id = :bid"];
$params = [':bid' => $barberia_id];

if (!empty($status_filter)) {
    $where[] = "o.status = :status";
    $params[':status'] = $status_filter;
}
if (!empty($search)) {
    $where[] = "(o.customer_name LIKE :search OR o.customer_email LIKE :search OR o.id LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
$where_sql = 'WHERE ' . implode(' AND ', $where);

// Conteggio totale
$total = querySingle("SELECT COUNT(*) as cnt FROM orders o $where_sql", $params)['cnt'];
$total_pages = ceil($total / $per_page);

// Recupera ordini
$sql = "SELECT o.*, COUNT(oi.id) as items_count 
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id 
        $where_sql 
        GROUP BY o.id 
        ORDER BY o.created_at DESC 
        LIMIT :offset, :per_page";
$params[':offset'] = $offset;
$params[':per_page'] = $per_page;

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
$stmt->execute();
$orders = $stmt->fetchAll();

// Statistiche rapide
$stats = [];
$stats['total'] = querySingle("SELECT COUNT(*) as cnt FROM orders WHERE barberia_id = :bid", [':bid' => $barberia_id])['cnt'];
$stats['pending'] = querySingle("SELECT COUNT(*) as cnt FROM orders WHERE barberia_id = :bid AND status = 'pending'", [':bid' => $barberia_id])['cnt'];
$stats['paid'] = querySingle("SELECT COUNT(*) as cnt FROM orders WHERE barberia_id = :bid AND status = 'paid'", [':bid' => $barberia_id])['cnt'];
$stats['revenue'] = querySingle("SELECT SUM(total) as total FROM orders WHERE barberia_id = :bid AND status = 'paid'", [':bid' => $barberia_id])['total'] ?? 0;

$success = $_SESSION['success'] ?? ''; unset($_SESSION['success']);
$error = $_SESSION['error'] ?? ''; unset($_SESSION['error']);

$page_title = 'Ordini - Antiqua Barbae';
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
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .page-title { font-family: 'Playfair Display', serif; font-size: 2rem; }
        
        .stats-mini { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-mini { background: white; border-radius: 8px; padding: 1rem; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.04); }
        .stat-mini .value { font-size: 1.5rem; font-weight: 700; color: #8B4513; }
        .stat-mini .label { font-size: 0.8rem; color: #4A4A4A; }
        
        .filters-container { background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .filters-form { display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end; }
        .filter-group { flex: 1; min-width: 200px; }
        .filter-label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.5rem; }
        .filter-input, .filter-select { width: 100%; padding: 0.5rem 0.75rem; border: 2px solid #e8d5c4; border-radius: 8px; }
        
        .table-container { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04); overflow-x: auto; }
        .admin-table { width: 100%; border-collapse: collapse; min-width: 800px; }
        .admin-table th { text-align: left; padding: 0.75rem; border-bottom: 2px solid #e8d5c4; background: #FDF8F5; }
        .admin-table td { padding: 0.75rem; border-bottom: 1px solid #e8d5c4; }
        .status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .status-paid { background: #E8F5E9; color: #2E7D32; }
        .status-pending { background: #FFF8E1; color: #F57F17; }
        .status-cancelled { background: #FFEBEE; color: #C62828; }
        .btn-view { background: #8B4513; color: white; padding: 0.25rem 0.75rem; border-radius: 6px; text-decoration: none; font-size: 0.8rem; }
        .btn-view:hover { background: #2C1810; }
        
        .pagination { display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem; }
        .page-link { padding: 0.5rem 1rem; border: 1px solid #e8d5c4; border-radius: 6px; text-decoration: none; color: #4A4A4A; }
        .page-link.active { background: #8B4513; color: white; border-color: #8B4513; }
        
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .alert-success { background: #E8F5E9; color: #1B5E20; }
        .alert-error { background: #FFEBEE; color: #B71C1C; }
        
        .mobile-menu-toggle { display: none; position: fixed; top: 15px; left: 15px; z-index: 1001; background: #8B4513; color: white; border: none; width: 45px; height: 45px; border-radius: 8px; font-size: 1.5rem; cursor: pointer; }
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; }
        
        @media (max-width: 768px) {
            .mobile-menu-toggle { display: flex; }
            .sidebar-overlay { display: block; opacity: 0; pointer-events: none; transition: opacity 0.3s; }
            .sidebar-overlay.active { opacity: 1; pointer-events: auto; }
            .admin-sidebar { transform: translateX(-100%); width: 260px; }
            .admin-sidebar.mobile-open { transform: translateX(0); }
            .admin-main { margin-left: 0 !important; padding: 15px !important; padding-top: 70px !important; }
            .stats-mini { grid-template-columns: repeat(2, 1fr); }
            .filters-form { flex-direction: column; }
            .filter-group { width: 100%; }
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
                <h1 class="page-title">Gestione Ordini</h1>
                <a href="../dashboard.php" class="btn btn-outline">← Dashboard</a>
            </div>
            
            <?php if ($success): ?><div class="alert alert-success">✅ <?php echo $success; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error">❌ <?php echo $error; ?></div><?php endif; ?>
            
            <div class="stats-mini">
                <div class="stat-mini"><div class="value"><?php echo $stats['total']; ?></div><div class="label">Totale Ordini</div></div>
                <div class="stat-mini"><div class="value"><?php echo $stats['pending']; ?></div><div class="label">In Attesa</div></div>
                <div class="stat-mini"><div class="value"><?php echo $stats['paid']; ?></div><div class="label">Pagati</div></div>
                <div class="stat-mini"><div class="value">€<?php echo number_format($stats['revenue'], 2); ?></div><div class="label">Incasso Totale</div></div>
            </div>
            
            <div class="filters-container">
                <form method="GET" class="filters-form">
                    <div class="filter-group">
                        <label class="filter-label">🔍 Cerca</label>
                        <input type="text" name="search" class="filter-input" value="<?php echo htmlspecialchars($search); ?>" placeholder="Nome, email o ID ordine...">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">📌 Stato</label>
                        <select name="status" class="filter-select">
                            <option value="">Tutti</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>In Attesa</option>
                            <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Pagati</option>
                        </select>
                    </div>
                    <div class="filter-group" style="flex:0;">
                        <button type="submit" class="btn btn-primary">Filtra</button>
                        <a href="list.php" class="btn btn-outline" style="margin-left:0.5rem;">Reset</a>
                    </div>
                </form>
            </div>
            
            <div class="table-container">
                <?php if (empty($orders)): ?>
                    <p style="text-align:center;padding:2rem;">Nessun ordine trovato.</p>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Email</th>
                                <th>Prodotti</th>
                                <th>Totale</th>
                                <th>Stato</th>
                                <th>Data</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong>#<?php echo $order['id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                                    <td><?php echo $order['items_count']; ?> prodotti</td>
                                    <td><strong>€<?php echo number_format($order['total'], 2); ?></strong></td>
                                    <td>
                                        <span class="status-badge <?php echo $order['status'] === 'paid' ? 'status-paid' : ($order['status'] === 'pending' ? 'status-pending' : 'status-cancelled'); ?>">
                                            <?php echo $order['status'] === 'paid' ? '✅ Pagato' : ($order['status'] === 'pending' ? '⏳ In attesa' : '❌ Cancellato'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <a href="detail.php?id=<?php echo $order['id']; ?>" class="btn-view">👁️ Dettagli</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?><a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="page-link">←</a><?php endif; ?>
                            <?php for ($i=1; $i<=$total_pages; $i++): ?><a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="page-link <?php echo $i==$page?'active':''; ?>"><?php echo $i; ?></a><?php endfor; ?>
                            <?php if ($page < $total_pages): ?><a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="page-link">→</a><?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
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