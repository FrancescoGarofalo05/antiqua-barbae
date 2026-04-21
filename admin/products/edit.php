<?php
/**
 * ANTIQUA BARBAE - Modifica Prodotto
 * File: admin/products/edit.php
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

$categories = query("SELECT * FROM categories ORDER BY name");

$name = $product['name'];
$description = $product['description'];
$category_id = $product['category_id'];
$price = $product['price'];
$stock = $product['stock'];
$current_image = $product['image'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = $_POST['category_id'] ?? '';
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock'] ?? '';
    $remove_image = isset($_POST['remove_image']);
    
    // Validazione
    if (empty($name)) $errors['name'] = 'Nome obbligatorio.';
    elseif (strlen($name) < 3) $errors['name'] = 'Minimo 3 caratteri.';
    
    if (empty($category_id)) $errors['category_id'] = 'Seleziona una categoria.';
    
    if (empty($price)) $errors['price'] = 'Prezzo obbligatorio.';
    elseif (!is_numeric($price) || $price <= 0) $errors['price'] = 'Prezzo non valido.';
    
    if ($stock === '') $errors['stock'] = 'Stock obbligatorio.';
    elseif (!is_numeric($stock) || $stock < 0) $errors['stock'] = 'Stock non valido.';
    
    // Gestione immagine
    $image_name = $current_image;
    
    if ($remove_image) {
        if ($current_image && file_exists('../../assets/images/' . $current_image)) {
            unlink('../../assets/images/' . $current_image);
        }
        $image_name = null;
    }
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors['image'] = 'Solo JPG, PNG o WEBP.';
        } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
            $errors['image'] = 'Immagine troppo grande (max 2MB).';
        } else {
            // Elimina vecchia immagine se presente
            if ($current_image && file_exists('../../assets/images/' . $current_image)) {
                unlink('../../assets/images/' . $current_image);
            }
            $image_name = uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], '../../assets/images/' . $image_name);
        }
    }
    
    if (empty($errors)) {
        // Rigenera slug solo se il nome è cambiato
        if ($name !== $product['name']) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
            $original = $slug;
            $counter = 1;
            while (querySingle("SELECT id FROM products WHERE slug = :s AND id != :id", [':s' => $slug, ':id' => $id])) {
                $slug = $original . '-' . $counter++;
            }
        } else {
            $slug = $product['slug'];
        }
        
        execute("UPDATE products SET category_id = :cid, name = :n, slug = :s, description = :d, 
                 price = :p, stock = :st, image = :img WHERE id = :id", [
            ':cid' => $category_id ?: null, ':n' => $name, ':s' => $slug, ':d' => $description,
            ':p' => $price, ':st' => $stock, ':img' => $image_name, ':id' => $id
        ]);
        
        $_SESSION['success'] = 'Prodotto aggiornato con successo!';
        header('Location: list.php');
        exit;
    }
}

$page_title = 'Modifica Prodotto - Antiqua Barbae';
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
        .form-container { background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04); max-width: 700px; }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; font-weight: 600; margin-bottom: 0.5rem; }
        .form-input, .form-select, .form-textarea { width: 100%; padding: 0.75rem; border: 2px solid #e8d5c4; border-radius: 8px; font-size: 1rem; }
        .form-textarea { resize: vertical; min-height: 100px; }
        .has-error .form-input, .has-error .form-select { border-color: #C62828; }
        .form-error { color: #C62828; font-size: 0.85rem; margin-top: 0.25rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .form-actions { display: flex; gap: 1rem; margin-top: 2rem; }
        .image-preview { width: 150px; height: 150px; background: #f5ebe0; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-top: 0.5rem; }
        .image-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .current-image { display: flex; align-items: center; gap: 1rem; margin-top: 0.5rem; }
        .mobile-menu-toggle { display: none; position: fixed; top: 15px; left: 15px; z-index: 1001; background: #8B4513; color: white; border: none; width: 45px; height: 45px; border-radius: 8px; font-size: 1.5rem; cursor: pointer; }
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; }
        @media (max-width: 768px) {
            .mobile-menu-toggle { display: flex; }
            .sidebar-overlay { display: block; opacity: 0; pointer-events: none; transition: opacity 0.3s; }
            .sidebar-overlay.active { opacity: 1; pointer-events: auto; }
            .admin-sidebar { transform: translateX(-100%); width: 260px; }
            .admin-sidebar.mobile-open { transform: translateX(0); }
            .admin-main { margin-left: 0 !important; padding: 15px !important; padding-top: 70px !important; }
            .form-row { grid-template-columns: 1fr; }
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
                <h1 class="page-title">Modifica Prodotto</h1>
            </div>
            
            <div class="form-container">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group <?php echo isset($errors['name']) ? 'has-error' : ''; ?>">
                        <label class="form-label">Nome Prodotto *</label>
                        <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($name); ?>">
                        <?php if (isset($errors['name'])): ?><span class="form-error"><?php echo $errors['name']; ?></span><?php endif; ?>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group <?php echo isset($errors['category_id']) ? 'has-error' : ''; ?>">
                            <label class="form-label">Categoria *</label>
                            <select name="category_id" class="form-select">
                                <option value="">-- Seleziona --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>><?php echo $cat['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['category_id'])): ?><span class="form-error"><?php echo $errors['category_id']; ?></span><?php endif; ?>
                        </div>
                        
                        <div class="form-group <?php echo isset($errors['price']) ? 'has-error' : ''; ?>">
                            <label class="form-label">Prezzo (€) *</label>
                            <input type="number" name="price" class="form-input" step="0.01" min="0" value="<?php echo htmlspecialchars($price); ?>">
                            <?php if (isset($errors['price'])): ?><span class="form-error"><?php echo $errors['price']; ?></span><?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group <?php echo isset($errors['stock']) ? 'has-error' : ''; ?>">
                        <label class="form-label">Quantità in Stock *</label>
                        <input type="number" name="stock" class="form-input" min="0" value="<?php echo htmlspecialchars($stock); ?>">
                        <?php if (isset($errors['stock'])): ?><span class="form-error"><?php echo $errors['stock']; ?></span><?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Descrizione</label>
                        <textarea name="description" class="form-textarea"><?php echo htmlspecialchars($description); ?></textarea>
                    </div>
                    
                    <div class="form-group <?php echo isset($errors['image']) ? 'has-error' : ''; ?>">
                        <label class="form-label">Immagine Prodotto</label>
                        <?php if ($current_image): ?>
                            <div class="current-image">
                                <div class="image-preview"><img src="../../assets/images/<?php echo $current_image; ?>" alt="Preview"></div>
                                <label style="display:flex;align-items:center;gap:0.5rem;"><input type="checkbox" name="remove_image"> Rimuovi immagine</label>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="image" class="form-input" accept="image/jpeg,image/png,image/webp" id="imageInput">
                        <?php if (isset($errors['image'])): ?><span class="form-error"><?php echo $errors['image']; ?></span><?php endif; ?>
                        <div class="image-preview" id="imagePreview" style="<?php echo $current_image ? 'display:none;' : ''; ?>">🧴</div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">💾 Aggiorna Prodotto</button>
                        <a href="list.php" class="btn btn-outline">Annulla</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <script>
    const t=document.getElementById('mobileMenuToggle'), s=document.getElementById('adminSidebar'), o=document.getElementById('sidebarOverlay');
    t?.addEventListener('click',()=>{s.classList.toggle('mobile-open');o.classList.toggle('active');t.textContent=s.classList.contains('mobile-open')?'✕':'☰';});
    o?.addEventListener('click',()=>{s.classList.remove('mobile-open');o.classList.remove('active');t.textContent='☰';});
    
    const imgInput = document.getElementById('imageInput');
    const preview = document.getElementById('imagePreview');
    const removeCb = document.querySelector('[name="remove_image"]');
    
    imgInput?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => { preview.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:contain;">`; preview.style.display = 'flex'; };
            reader.readAsDataURL(file);
            if (removeCb) removeCb.checked = false;
        }
    });
    
    removeCb?.addEventListener('change', function() {
        if (this.checked) {
            preview.style.display = 'none';
            imgInput.value = '';
        } else {
            preview.style.display = 'flex';
        }
    });
    </script>
</body>
</html>