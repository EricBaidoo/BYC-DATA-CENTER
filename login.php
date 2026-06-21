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
        :root {
            --login-radius-lg: 1.25rem;
            --login-radius-md: 0.75rem;
            --login-radius-sm: 0.5rem;
        }
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: #06040e;
            font-family: var(--font-body);
            padding: 1.5rem 1rem;
            position: relative;
            overflow-x: hidden;
            box-sizing: border-box;
        }
        /* Animated backdrop mesh glow */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(139, 92, 246, 0.12) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(6, 182, 212, 0.08) 0%, transparent 40%);
            pointer-events: none;
            z-index: -2;
        }
        /* Floating blurred background glow shapes */
        .bg-glow {
            position: absolute;
            width: 25rem;
            height: 25rem;
            border-radius: 50%;
            filter: blur(8rem);
            pointer-events: none;
            z-index: -1;
            opacity: 0.15;
            animation: pulse-glow 8s infinite alternate;
        }
        .bg-glow-1 {
            top: -5%;
            left: -5%;
            background: var(--primary);
        }
        .bg-glow-2 {
            bottom: -5%;
            right: -5%;
            background: var(--secondary);
            animation-delay: 4s;
        }
        @keyframes pulse-glow {
            0% { transform: scale(1) translate(0, 0); opacity: 0.15; }
            100% { transform: scale(1.15) translate(1rem, -1rem); opacity: 0.25; }
        }
        .login-card {
            width: 100%;
            max-width: 26rem;
            padding: 3rem 2.5rem;
            background: rgba(20, 16, 36, 0.55);
            backdrop-filter: blur(1.5rem);
            -webkit-backdrop-filter: blur(1.5rem);
            border: 0.0625rem solid rgba(255, 255, 255, 0.08);
            border-radius: var(--login-radius-lg);
            box-shadow: 0 1.5rem 3.5rem rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            gap: 2rem;
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(1.5rem); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at top right, rgba(139, 92, 246, 0.15), transparent 60%);
            pointer-events: none;
        }
        .login-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            text-align: center;
        }
        .login-icon {
            width: 4rem;
            height: 4rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: var(--login-radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 2rem;
            color: white;
            box-shadow: 0 0.5rem 1.5rem rgba(139, 92, 246, 0.35);
        }
        .login-title {
            font-family: var(--font-heading);
            font-weight: 800;
            font-size: 1.75rem;
            letter-spacing: -0.04rem;
            margin-bottom: 0.25rem;
            background: linear-gradient(to right, #ffffff, #d1d5db);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .login-subtitle {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 500;
        }
        .form-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }
        .form-group input {
            width: 100%;
            background: rgba(0, 0, 0, 0.3);
            border: 0.0625rem solid rgba(255, 255, 255, 0.1);
            border-radius: var(--login-radius-sm);
            padding: 0.85rem 1.15rem;
            color: white;
            transition: var(--transition);
        }
        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.1875rem rgba(139, 92, 246, 0.25);
            background: rgba(0, 0, 0, 0.45);
            outline: none;
        }
        .btn-submit {
            background: linear-gradient(135deg, var(--primary), #7c3aed);
            color: white;
            border: none;
            border-radius: var(--login-radius-sm);
            padding: 0.875rem 1.5rem;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 0.5rem 1.5rem rgba(139, 92, 246, 0.35);
            transition: var(--transition);
            margin-top: 0.5rem;
            width: 100%;
            text-decoration: none;
        }
        .btn-submit:hover {
            transform: translateY(-0.125rem);
            box-shadow: 0 0.75rem 2rem rgba(139, 92, 246, 0.45);
            background: linear-gradient(135deg, #9061f9, #6d28d9);
        }
        .btn-submit:active {
            transform: translateY(0) scale(0.98);
        }
        .error-banner {
            background: rgba(255, 74, 107, 0.15);
            border: 0.0625rem solid var(--danger);
            color: var(--danger);
            padding: 0.85rem 1rem;
            border-radius: var(--login-radius-sm);
            font-size: 0.875rem;
            font-weight: 600;
            text-align: center;
            animation: shake 0.35s ease-in-out;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-0.35rem); }
            40%, 80% { transform: translateX(0.35rem); }
        }

        /* Mobile Responsiveness */
        @media (max-width: 480px) {
            body {
                padding: 1rem 0.75rem;
            }
            .bg-glow {
                width: 15rem;
                height: 15rem;
                filter: blur(5rem);
            }
            .login-card {
                padding: 2.25rem 1.5rem;
                gap: 1.5rem;
                border-radius: var(--login-radius-md);
            }
            .login-title {
                font-size: 1.5rem;
            }
            .login-subtitle {
                font-size: 0.8rem;
            }
            .login-icon {
                width: 3.5rem;
                height: 3.5rem;
                font-size: 1.75rem;
                border-radius: var(--login-radius-sm);
            }
            .form-group label {
                font-size: 0.8rem;
            }
            .form-group input {
                padding: 0.75rem 1rem;
                font-size: 0.875rem;
            }
            .btn-submit {
                padding: 0.75rem 1.25rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <!-- Floating background glow circles -->
    <div class="bg-glow bg-glow-1"></div>
    <div class="bg-glow bg-glow-2"></div>

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
                <label for="username">Username</label>
                <input type="text" name="username" id="username" required autocomplete="username" placeholder="Enter username...">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required autocomplete="current-password" placeholder="Enter password...">
            </div>
            
            <button type="submit" class="btn-submit">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 0.25rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                Sign In
            </button>
        </form>
    </div>
</body>
</html>
