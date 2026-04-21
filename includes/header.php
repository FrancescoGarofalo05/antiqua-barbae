<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title><?php echo isset($page_title) ? $page_title . ' | Antiqua Barbae' : 'Antiqua Barbae - Cura della Barba Artigianale'; ?></title>
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'Oli, cere e kit da barba artigianali. Scopri i nostri prodotti per una rasatura perfetta.'; ?>">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💈</text></svg>">
</head>
<body>
    <a href="#main-content" class="skip-to-content">Salta al contenuto principale</a>
    
    <header class="site-header">
        <div class="container header-container">
            <div class="logo-area">
                <a href="index.php" class="logo-link">
                    <img src="./assets/images/logo.png" 
                         alt="Antiqua Barbae Logo" 
                         class="logo-img"
                         width="50" 
                         height="50">
                    <span class="site-title">Antiqua <span class="title-accent">Barbae</span></span>
                </a>
            </div>
            <nav class="main-nav" aria-label="Menu principale">
                <ul class="nav-list">
                    <li class="nav-item"><a href="index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">Home</a></li>
                    <li class="nav-item"><a href="shop.php" class="nav-link <?php echo $current_page == 'shop.php' ? 'active' : ''; ?>">Shop</a></li>
                    <li class="nav-item"><a href="cart.php" class="nav-link <?php echo $current_page == 'cart.php' ? 'active' : ''; ?>">Carrello 🛒</a></li>
                </ul>
            </nav>
            <div class="header-actions">
                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'owner'): ?>
                    <a href="admin/dashboard.php" class="btn btn-outline">Dashboard</a>
                    <a href="admin/logout.php" class="btn btn-outline">Esci</a>
                <?php elseif (isset($_SESSION['user_id']) && $_SESSION['role'] === 'customer'): ?>
                    <span class="user-welcome">👤 <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="admin/logout.php" class="btn btn-outline">Esci</a>
                <?php else: ?>
                    <a href="admin/login.php" class="btn btn-outline">Accedi</a>
                    <a href="admin/register.php" class="btn btn-primary">Registrati</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <main id="main-content"><?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title><?php echo isset($page_title) ? $page_title . ' | Antiqua Barbae' : 'Antiqua Barbae - Cura della Barba Artigianale'; ?></title>
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'Oli, cere e kit da barba artigianali. Scopri i nostri prodotti per una rasatura perfetta.'; ?>">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💈</text></svg>">
</head>
<body>
    <a href="#main-content" class="skip-to-content">Salta al contenuto principale</a>
    
    <header class="site-header">
        <div class="container header-container">
            <div class="logo-area">
                <a href="index.php" class="logo-link">
                    <img src="./assets/images/logo.png" 
                         alt="Antiqua Barbae Logo" 
                         class="logo-img"
                         width="50" 
                         height="50">
                    <span class="site-title">Antiqua <span class="title-accent">Barbae</span></span>
                </a>
            </div>
            <nav class="main-nav" aria-label="Menu principale">
                <ul class="nav-list">
                    <li class="nav-item"><a href="index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">Home</a></li>
                    <li class="nav-item"><a href="shop.php" class="nav-link <?php echo $current_page == 'shop.php' ? 'active' : ''; ?>">Shop</a></li>
                    <li class="nav-item"><a href="cart.php" class="nav-link <?php echo $current_page == 'cart.php' ? 'active' : ''; ?>">Carrello 🛒</a></li>
                </ul>
            </nav>
            <div class="header-actions">
                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'owner'): ?>
                    <a href="admin/dashboard.php" class="btn btn-outline">Dashboard</a>
                    <a href="admin/logout.php" class="btn btn-outline">Esci</a>
                <?php elseif (isset($_SESSION['user_id']) && $_SESSION['role'] === 'customer'): ?>
                    <span class="user-welcome">👤 <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="admin/logout.php" class="btn btn-outline">Esci</a>
                <?php else: ?>
                    <a href="admin/login.php" class="btn btn-outline">Accedi</a>
                    <a href="admin/register.php" class="btn btn-primary">Registrati</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <main id="main-content">
