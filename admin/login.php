<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'owner' ? 'dashboard.php' : '../index.php'));
    exit;
}

$login = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($login)) $errors['login'] = 'Inserisci username o email.';
    if (empty($password)) $errors['password'] = 'Password obbligatoria.';
    
    if (empty($errors)) {
        $user = querySingle("SELECT * FROM users WHERE username = :u OR email = :e", [':u' => $login, ':e' => $login]);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['barberia_id'] = $user['barberia_id'];
            session_regenerate_id(true);
            
            header('Location: ' . ($user['role'] === 'owner' ? 'dashboard.php' : '../index.php'));
            exit;
        } else {
            $errors['general'] = 'Credenziali non valide.';
        }
    }
}

$page_title = 'Accedi - Antiqua Barbae';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #8B4513 0%, #2C1810 100%); padding: 1rem; }
        .admin-card { background: white; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); width: 100%; max-width: 450px; padding: 2rem; }
        .admin-logo { text-align: center; margin-bottom: 1.5rem; }
        .admin-logo a { text-decoration: none; font-family: 'Playfair Display', serif; font-size: 1.8rem; font-weight: 700; color: #1A1A1A; }
        .admin-logo span { color: #8B4513; }
        .admin-title { font-size: 1.5rem; text-align: center; margin-bottom: 0.5rem; }
        .admin-subtitle { text-align: center; color: #4A4A4A; margin-bottom: 1.5rem; }
        .alert-error { background: #FFEBEE; border: 1px solid #C62828; color: #B71C1C; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.2rem; }
        .form-label { display: block; font-weight: 600; margin-bottom: 0.4rem; }
        .form-input { width: 100%; padding: 0.7rem 1rem; border: 2px solid #e8d5c4; border-radius: 8px; font-size: 1rem; }
        .form-input:focus { outline: none; border-color: #8B4513; }
        .btn-admin { width: 100%; padding: 0.875rem; background: #8B4513; color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; }
        .btn-admin:hover { background: #2C1810; }
        .admin-footer { text-align: center; margin-top: 1.5rem; color: #4A4A4A; }
        .admin-footer a { color: #8B4513; text-decoration: none; font-weight: 600; }
        .password-wrapper { position: relative; }
        .password-toggle { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1.2rem; opacity: 0.6; }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-card">
            <div class="admin-logo"><a href="../index.php">Antiqua<span>Barbae</span></a></div>
            <h1 class="admin-title">Accedi</h1>
            <p class="admin-subtitle">Entra nel tuo account</p>
            
            <?php if (isset($errors['general'])): ?><div class="alert-error">⚠️ <?php echo $errors['general']; ?></div><?php endif; ?>
            
            <form method="POST">
                <div class="form-group"><label class="form-label">Username o Email</label><input type="text" name="login" class="form-input" value="<?php echo htmlspecialchars($login); ?>"></div>
                <div class="form-group"><label class="form-label">Password</label><div class="password-wrapper"><input type="password" name="password" id="password" class="form-input"><button type="button" class="password-toggle" id="togglePassword">👁️</button></div></div>
                <button type="submit" class="btn-admin">🔐 Accedi</button>
            </form>
            
            <div class="admin-footer">Non hai un account? <a href="register.php">Registrati</a></div>
            <div class="admin-footer" style="margin-top:1rem"><a href="../index.php">← Torna al sito</a></div>
        </div>
    </div>
    <script>
    document.getElementById('togglePassword')?.addEventListener('click', function(){ const p=document.getElementById('password'); p.type=p.type==='password'?'text':'password'; this.textContent=p.type==='password'?'👁️':'🙈'; });
    </script>
</body>
</html>