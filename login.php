<?php
// login.php - Secure login portal for BYC Data Center
require_once __DIR__ . '/includes/db.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Success! Set session parameters
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_name'] = $user['name'] ?: 'Administrator';
                
                header("Location: index");
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $error = 'Authentication Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BYC Data Center - Secure Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            background: var(--bg-base);
        }
        .login-card {
            width: 100%;
            max-width: 25rem;
            padding: 2.5rem;
            background: var(--bg-surface);
            backdrop-filter: blur(1.25rem);
            -webkit-backdrop-filter: blur(1.25rem);
            border: 0.0625rem solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: 0 1.25rem 3.125rem rgba(0, 0, 0, 0.4);
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.6s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(1.25rem); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at top right, rgba(139, 92, 246, 0.12), transparent 60%);
            pointer-events: none;
        }
        .login-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
            text-align: center;
        }
        .login-icon {
            width: 3.5rem;
            height: 3.5rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.75rem;
            color: white;
            box-shadow: 0 0.25rem 1rem rgba(139, 92, 246, 0.35);
        }
        .login-title {
            font-family: var(--font-heading);
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: -0.03125rem;
            background: linear-gradient(to right, #ffffff, #9ca3af);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .login-subtitle {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        .form-group input {
            width: 100%;
            background: rgba(0, 0, 0, 0.25);
            border-color: var(--border-color);
            transition: var(--transition);
        }
        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.125rem var(--primary-glow);
            background: rgba(0, 0, 0, 0.4);
        }
        .error-banner {
            background: var(--danger-glow);
            border: 0.0625rem solid var(--danger);
            color: var(--danger);
            padding: 0.75rem 1rem;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            font-weight: 500;
            text-align: center;
            animation: shake 0.3s ease-in-out;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-0.25rem); }
            75% { transform: translateX(0.25rem); }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-logo">
            <div class="login-icon">B</div>
            <div>
                <h1 class="login-title">BYC DATA CENTER</h1>
                <p class="login-subtitle">System Administration Portal</p>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-banner">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login" style="display: flex; flex-direction: column; gap: 1.25rem;">
            <div class="form-group">
                <label for="username" style="margin-bottom: 0.35rem;">Username</label>
                <input type="text" name="username" id="username" required autocomplete="username" placeholder="Enter username...">
            </div>
            
            <div class="form-group">
                <label for="password" style="margin-bottom: 0.35rem;">Password</label>
                <input type="password" name="password" id="password" required autocomplete="current-password" placeholder="Enter password...">
            </div>
            
            <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem; justify-content: center; width: 100%;">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 0.25rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                Sign In
            </button>
        </form>
    </div>
</body>
</html>
