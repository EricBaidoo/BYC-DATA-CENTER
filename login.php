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
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BYC Data Center - Secure Login</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.0.6">
</head>
<body class="min-h-full flex items-center justify-center bg-bg-base px-4 py-8 relative overflow-hidden select-none">
    <!-- Floating background glow circles -->
    <div class="absolute w-[35rem] h-[35rem] rounded-full bg-primary blur-[10rem] opacity-20 -top-[10%] -left-[10%] pointer-events-none animate-pulse-glow z-[-1]"></div>
    <div class="absolute w-[35rem] h-[35rem] rounded-full bg-secondary blur-[10rem] opacity-15 -bottom-[10%] -right-[10%] pointer-events-none animate-pulse-glow [animation-delay:4s] z-[-1]"></div>

    <?php
    $settings = $GLOBALS['site_settings'] ?? [];
    $sys_name = $settings['system_name'] ?? 'BYC DATA CENTER';
    $sys_abbr = !empty($sys_name) ? substr($sys_name, 0, 1) : 'B';
    $org_name = $settings['organization_name'] ?? 'System Administration Portal';
    ?>
    <div class="w-full max-w-[26rem] p-8 sm:p-10 bg-bg-surface backdrop-blur-2xl border border-border-custom rounded-2xl shadow-[0_24px_56px_rgba(0,0,0,0.5)] flex flex-col gap-8 relative overflow-hidden">
        <!-- Glowing accent reflection inside card -->
        <div class="absolute inset-0 bg-gradient-to-tr from-primary/10 to-transparent pointer-events-none z-[-1]"></div>

        <div class="flex flex-col items-center gap-4 text-center">
            <div class="w-16 h-16 bg-gradient-to-br from-primary to-secondary rounded-2xl flex items-center justify-center font-extrabold text-3xl text-white shadow-[0_8px_24px_rgba(139,92,246,0.35)]"><?= htmlspecialchars($sys_abbr) ?></div>
            <div>
                <h1 class="font-heading font-extrabold text-2xl sm:text-3xl tracking-tight bg-gradient-to-r from-white to-gray-300 bg-clip-text text-transparent mb-1"><?= htmlspecialchars($sys_name) ?></h1>
                <p class="text-text-secondary text-xs sm:text-sm font-medium"><?= htmlspecialchars($org_name) ?></p>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-danger/15 border border-danger text-danger px-4 py-3 rounded-md text-sm font-semibold text-center animate-shake">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login" class="flex flex-col gap-5">
            <div class="flex flex-col gap-1.5">
                <label for="username" class="text-xs sm:text-sm font-semibold text-text-secondary">Username</label>
                <input type="text" name="username" id="username" required autocomplete="username" placeholder="Enter username..." class="w-full bg-black/30 border border-white/10 rounded-md px-4 py-3 text-white placeholder-text-muted focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200">
            </div>
            
            <div class="flex flex-col gap-1.5">
                <label for="password" class="text-xs sm:text-sm font-semibold text-text-secondary">Password</label>
                <input type="password" name="password" id="password" required autocomplete="current-password" placeholder="Enter password..." class="w-full bg-black/30 border border-white/10 rounded-md px-4 py-3 text-white placeholder-text-muted focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200">
            </div>
            
            <button type="submit" class="w-full bg-gradient-to-br from-primary to-[#7c3aed] hover:from-[#9061f9] hover:to-[#6d28d9] text-white font-bold py-3.5 px-6 rounded-md shadow-[0_8px_24px_rgba(139,92,246,0.35)] hover:shadow-[0_12px_32px_rgba(139,92,246,0.45)] hover:-translate-y-0.5 active:translate-y-0 active:scale-98 transition-all duration-200 cursor-pointer flex items-center justify-center gap-1.5 mt-2">
                <svg class="w-4 h-4 mr-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                Sign In
            </button>
        </form>
    </div>
</body>
</html>
