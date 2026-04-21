<?php
/**
 * ANTIQUA BARBAE - Lista Prodotti (Proprietario)
 * File: admin/products/list.php
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
$search = trim($_GET['search'] ?? '');
$category_filter = $_GET['category'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Costruisce WHERE
$where = ["p.barberia_id = :bid"];
$params = [':bid' => $barberia_id];

if (!empty($search)) {
    $where[] = "(p.name LIKE :search OR p.description LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
if (!empty($category_filter)) {
    $where[] = "c.slug = :cat";
    $params[':cat'] = $category_filter;
}
$where_sql = 'WHERE ' . implode(' AND ', $where);

// Conteggio totale
$total = querySingle("SELECT COUNT(*) as cnt FROM products p LEFT JOIN categories c ON p.category_id = c.id $where_sql", $params)['cnt'];
$total_pages = ceil($total / $per_page);

// Recupera prodotti
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        $where_sql 
        ORDER BY p.created_at DESC 
        LIMIT :offset, :per_page";
$params[':offset'] = $offset;
$params[':per_page'] = $per_page;

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
$stmt->execute();
$products = $stmt->fetchAll();

$categories = query("SELECT * FROM categories ORDER BY name");
$success = $_SESSION['success'] ?? ''; unset($_SESSION['success']);
$error = $_SESSION['error'] ?? ''; unset($_SESSION['error']);

$page_title = 'Prodotti - Antiqua Barbae';
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
        .filters-container { background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .filters-form { display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end; }
        .filter-group { flex: 1; min-width: 200px; }
        .filter-label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.5rem; }
        .filter-input, .filter-select { width: 100%; padding: 0.5rem 0.75rem; border: 2px solid #e8d5c4; border-radius: 8px; }
        .table-container { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04); overflow-x: auto; }
        .admin-table { width: 100%; border-collapse: collapse; min-width: 700px; }
        .admin-table th { text-align: left; padding: 0.75rem; border-bottom: 2px solid #e8d5c4; background: #FDF8F5; }
        .admin-table td { padding: 0.75rem; border-bottom: 1px solid #e8d5c4; }
        .product-image { width: 50px; height: 50px; background: #f5ebe0; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
        .stock-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; }
        .stock-ok { background: #E8F5E9; color: #2E7D32; }
        .stock-low { background: #FFF8E1; color: #F57F17; }
        .stock-out { background: #FFEBEE; color: #C62828; }
        .action-links { display: flex; gap: 0.5rem; }
        .btn-icon { padding: 0.25rem 0.5rem; border-radius: 6px; text-decoration: none; font-size: 0.8rem; }
        .btn-edit { background: #8B4513; color: white; }
        .btn-delete { background: #C62828; color: white; }
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
                <h1 class="page-title">Gestione Prodotti</h1>
                <a href="create.php" class="btn btn-primary">➕ Nuovo Prodotto</a>
            </div>
            
            <?php if ($success): ?><div class="alert alert-success">✅ <?php echo $success; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error">❌ <?php echo $error; ?></div><?php endif; ?>
            
            <div class="filters-container">
                <form method="GET" class="filters-form">
                    <div class="filter-group"><label class="filter-label">🔍 Cerca</label><input type="text" name="search" class="filter-input" value="<?php echo htmlspecialchars($search); ?>" placeholder="Nome o descrizione..."></div>
                    <div class="filter-group"><label class="filter-label">📂 Categoria</label><select name="category" class="filter-select"><option value="">Tutte</option><?php foreach($categories as $c): ?><option value="<?php echo $c['slug']; ?>" <?php echo $category_filter===$c['slug']?'selected':''; ?>><?php echo $c['name']; ?></option><?php endforeach; ?></select></div>
                    <div class="filter-group" style="flex:0;"><button type="submit" class="btn btn-primary">Filtra</button><a href="list.php" class="btn btn-outline" style="margin-left:0.5rem;">Reset</a></div>
                </form>
            </div>
            
            <div class="table-container">
                <?php if (empty($products)): ?>
                    <p style="text-align:center;padding:2rem;">Nessun prodotto trovato. <a href="create.php">Crea il primo</a>.</p>
                <?php else: ?>
                    <table class="admin-table">
                        <thead><tr><th></th><th>Nome</th><th>Categoria</th><th>Prezzo</th><th>Stock</th><th>Azioni</th></tr></thead>
                        <tbody>
                            <?php foreach ($products as $p): 
                                $stockClass = $p['stock'] > 10 ? 'stock-ok' : ($p['stock'] > 0 ? 'stock-low' : 'stock-out');
                            ?>
                                <tr>
                                    <td><div class="product-image"><?php echo $p['image'] ? "<img src='../../assets/images/{$p['image']}' style='width:100%;height:100%;object-fit:cover;'>" : '🧴'; ?></div></td>
                                    <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($p['category_name'] ?? '-'); ?></td>
                                    <td>€<?php echo number_format($p['price'], 2); ?></td>
                                    <td><span class="stock-badge <?php echo $stockClass; ?>"><?php echo $p['stock']; ?></span></td>
                                    <td class="action-links"><a href="edit.php?id=<?php echo $p['id']; ?>" class="btn-icon btn-edit">✏️</a><a href="delete.php?id=<?php echo $p['id']; ?>" class="btn-icon btn-delete" onclick="return confirm('Eliminare questo prodotto?')">🗑️</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?><a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>" class="page-link">←</a><?php endif; ?>
                            <?php for ($i=1; $i<=$total_pages; $i++): ?><a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>" class="page-link <?php echo $i==$page?'active':''; ?>"><?php echo $i; ?></a><?php endfor; ?>
                            <?php if ($page < $total_pages): ?><a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>" class="page-link">→</a><?php endif; ?>
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