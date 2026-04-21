<?php
/**
 * ANTIQUA BARBAE - Registrazione (Proprietario con P.IVA / Cliente)
 * File: admin/register.php
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'owner' ? 'dashboard.php' : '../index.php'));
    exit;
}

$account_type = $_POST['account_type'] ?? 'customer';
$errors = [];
$success = false;

// Campi comuni
$username = $email = $full_name = '';
// Campi proprietario
$barberia_name = $piva = $address = $phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_type = $_POST['account_type'] ?? 'customer';
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Campi proprietario
    if ($account_type === 'owner') {
        $barberia_name = trim($_POST['barberia_name'] ?? '');
        $piva = trim($_POST['piva'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
    }
    
    // Validazione comune
    if (empty($username)) $errors['username'] = 'Username obbligatorio.';
    elseif (strlen($username) < 3) $errors['username'] = 'Minimo 3 caratteri.';
    elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) $errors['username'] = 'Solo lettere, numeri e underscore.';
    else {
        $check = querySingle("SELECT id FROM users WHERE username = :u", [':u' => $username]);
        if ($check) $errors['username'] = 'Username già in uso.';
    }
    
    if (empty($email)) $errors['email'] = 'Email obbligatoria.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email non valida.';
    else {
        $check = querySingle("SELECT id FROM users WHERE email = :e", [':e' => $email]);
        if ($check) $errors['email'] = 'Email già registrata.';
    }
    
    if (empty($full_name)) $errors['full_name'] = 'Nome completo obbligatorio.';
    if (empty($password)) $errors['password'] = 'Password obbligatoria.';
    elseif (strlen($password) < 6) $errors['password'] = 'Minimo 6 caratteri.';
    if ($password !== $confirm_password) $errors['confirm_password'] = 'Le password non coincidono.';
    
    // Validazione proprietario
    if ($account_type === 'owner') {
        if (empty($barberia_name)) $errors['barberia_name'] = 'Nome barberia obbligatorio.';
        if (empty($piva)) $errors['piva'] = 'Partita IVA obbligatoria.';
        elseif (!preg_match('/^[0-9]{11}$/', $piva)) $errors['piva'] = 'Partita IVA non valida (11 cifre).';
        if (empty($address)) $errors['address'] = 'Indirizzo obbligatorio.';
        if (empty($phone)) $errors['phone'] = 'Telefono obbligatorio.';
    }
    
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $role = $account_type === 'owner' ? 'owner' : 'customer';
        $barberia_id = null;
        
        if ($account_type === 'owner') {
            // Crea barberia
            execute("INSERT INTO barberias (name, address, phone) VALUES (:name, :addr, :phone)", [
                ':name' => $barberia_name, ':addr' => $address, ':phone' => $phone
            ]);
            $barberia_id = $pdo->lastInsertId();
        }
        
        // Crea utente
        execute("INSERT INTO users (username, email, password_hash, full_name, role, barberia_id) 
                 VALUES (:u, :e, :p, :f, :r, :bid)", [
            ':u' => $username, ':e' => $email, ':p' => $password_hash,
            ':f' => $full_name, ':r' => $role, ':bid' => $barberia_id
        ]);
        
        if ($account_type === 'owner' && $barberia_id) {
            execute("UPDATE barberias SET owner_id = :oid WHERE id = :bid", [
                ':oid' => $pdo->lastInsertId(), ':bid' => $barberia_id
            ]);
        }
        
        $success = true;
        $username = $email = $full_name = $barberia_name = $piva = $address = $phone = '';
    }
}

$page_title = 'Registrazione - Antiqua Barbae';
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
        .admin-card { background: white; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); width: 100%; max-width: 550px; padding: 2rem; }
        .admin-logo { text-align: center; margin-bottom: 1.5rem; }
        .admin-logo a { text-decoration: none; font-family: 'Playfair Display', serif; font-size: 1.8rem; font-weight: 700; color: #1A1A1A; }
        .admin-logo span { color: #8B4513; }
        .admin-title { font-size: 1.5rem; text-align: center; margin-bottom: 0.5rem; }
        .admin-subtitle { text-align: center; color: #4A4A4A; margin-bottom: 1.5rem; }
        .account-type-switch { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; background: #FDF8F5; padding: 0.4rem; border-radius: 12px; }
        .type-option { flex: 1; text-align: center; padding: 0.6rem; border-radius: 8px; cursor: pointer; font-weight: 600; background: transparent; border: none; font-size: 0.9rem; }
        .type-option.active { background: #8B4513; color: white; }
        .form-group { margin-bottom: 1.2rem; }
        .form-label { display: block; font-weight: 600; margin-bottom: 0.4rem; }
        .form-input { width: 100%; padding: 0.7rem 1rem; border: 2px solid #e8d5c4; border-radius: 8px; font-size: 1rem; }
        .form-input:focus { outline: none; border-color: #8B4513; }
        .has-error .form-input { border-color: #C62828; }
        .form-error { color: #C62828; font-size: 0.8rem; margin-top: 0.25rem; }
        .btn-admin { width: 100%; padding: 0.875rem; background: #8B4513; color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; }
        .btn-admin:hover { background: #2C1810; }
        .admin-footer { text-align: center; margin-top: 1.5rem; color: #4A4A4A; }
        .admin-footer a { color: #8B4513; text-decoration: none; font-weight: 600; }
        .alert-success { background: #E8F5E9; border: 1px solid #2E7D32; color: #1B5E20; padding: 1.5rem; border-radius: 8px; text-align: center; }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-card">
            <div class="admin-logo"><a href="../index.php">Antiqua<span>Barbae</span></a></div>
            <h1 class="admin-title">Crea un Account</h1>
            <p class="admin-subtitle">Scegli come registrarti</p>
            
            <?php if ($success): ?>
                <div class="alert-success"><h3>✅ Registrazione completata!</h3><p>Ora puoi accedere al tuo account.</p><p style="margin-top:1rem"><a href="login.php">Clicca qui per accedere</a></p></div>
            <?php else: ?>
                <form method="POST">
                    <div class="account-type-switch">
                        <button type="button" id="btnCustomer" class="type-option <?php echo $account_type === 'customer' ? 'active' : ''; ?>">👤 Cliente</button>
                        <button type="button" id="btnOwner" class="type-option <?php echo $account_type === 'owner' ? 'active' : ''; ?>">💈 Proprietario Barberia</button>
                    </div>
                    <input type="hidden" name="account_type" id="accountType" value="<?php echo $account_type; ?>">
                    
                    <div id="ownerFields" style="display: <?php echo $account_type === 'owner' ? 'block' : 'none'; ?>;">
                        <div class="form-group"><label class="form-label">Nome Barberia</label><input type="text" name="barberia_name" class="form-input" value="<?php echo htmlspecialchars($barberia_name); ?>"><?php if(isset($errors['barberia_name'])) echo "<span class='form-error'>{$errors['barberia_name']}</span>"; ?></div>
                        <div class="form-group"><label class="form-label">Partita IVA (11 cifre)</label><input type="text" name="piva" class="form-input" value="<?php echo htmlspecialchars($piva); ?>" placeholder="12345678901"><?php if(isset($errors['piva'])) echo "<span class='form-error'>{$errors['piva']}</span>"; ?></div>
                        <div class="form-group"><label class="form-label">Indirizzo</label><input type="text" name="address" class="form-input" value="<?php echo htmlspecialchars($address); ?>"><?php if(isset($errors['address'])) echo "<span class='form-error'>{$errors['address']}</span>"; ?></div>
                        <div class="form-group"><label class="form-label">Telefono</label><input type="text" name="phone" class="form-input" value="<?php echo htmlspecialchars($phone); ?>"><?php if(isset($errors['phone'])) echo "<span class='form-error'>{$errors['phone']}</span>"; ?></div>
                    </div>
                    
                    <div class="form-group"><label class="form-label">Username</label><input type="text" name="username" class="form-input" value="<?php echo htmlspecialchars($username); ?>"><?php if(isset($errors['username'])) echo "<span class='form-error'>{$errors['username']}</span>"; ?></div>
                    <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($email); ?>"><?php if(isset($errors['email'])) echo "<span class='form-error'>{$errors['email']}</span>"; ?></div>
                    <div class="form-group"><label class="form-label">Nome Completo</label><input type="text" name="full_name" class="form-input" value="<?php echo htmlspecialchars($full_name); ?>"><?php if(isset($errors['full_name'])) echo "<span class='form-error'>{$errors['full_name']}</span>"; ?></div>
                    <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-input"><?php if(isset($errors['password'])) echo "<span class='form-error'>{$errors['password']}</span>"; ?></div>
                    <div class="form-group"><label class="form-label">Conferma Password</label><input type="password" name="confirm_password" class="form-input"><?php if(isset($errors['confirm_password'])) echo "<span class='form-error'>{$errors['confirm_password']}</span>"; ?></div>
                    
                    <button type="submit" class="btn-admin">📝 Registrati</button>
                </form>
                
                <div class="admin-footer">Hai già un account? <a href="login.php">Accedi</a></div>
            <?php endif; ?>
            <div class="admin-footer" style="margin-top:1rem"><a href="../index.php">← Torna al sito</a></div>
        </div>
    </div>
    <script>
    document.getElementById('btnCustomer')?.addEventListener('click', function(e){ e.preventDefault(); document.getElementById('accountType').value='customer'; this.classList.add('active'); document.getElementById('btnOwner').classList.remove('active'); document.getElementById('ownerFields').style.display='none'; });
    document.getElementById('btnOwner')?.addEventListener('click', function(e){ e.preventDefault(); document.getElementById('accountType').value='owner'; this.classList.add('active'); document.getElementById('btnCustomer').classList.remove('active'); document.getElementById('ownerFields').style.display='block'; });
    </script>
</body>
</html>