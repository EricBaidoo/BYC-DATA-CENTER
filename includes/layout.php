<?php
// layout.php - Master layout rendering helper for BYC Data Center

function render_header($title, $active_page = 'dashboard') {
    $settings = $GLOBALS['site_settings'] ?? [];
    $sys_name = $settings['system_name'] ?? 'BYC DATA CENTER';
    $sys_abbr = !empty($sys_name) ? substr($sys_name, 0, 1) : 'B';
    $org_name = $settings['organization_name'] ?? 'Beersheba Youth Church';

    $nav_items = [
        'dashboard' => ['label' => 'Dashboard', 'url' => 'index', 'icon' => '<svg class="nav-icon" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z"></path></svg>'],
        'members' => ['label' => 'Members', 'url' => 'members', 'icon' => '<svg class="nav-icon" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>'],
        'attendance' => ['label' => 'Attendance', 'url' => 'attendance', 'icon' => '<svg class="nav-icon" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>'],
        'workforce' => ['label' => 'Workforce', 'url' => 'workforce', 'icon' => '<svg class="nav-icon" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>'],
        'settings' => ['label' => 'Settings', 'url' => 'settings', 'icon' => '<svg class="nav-icon" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>'],
        'logout' => ['label' => 'Logout', 'url' => 'logout', 'icon' => '<svg class="nav-icon" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>']
    ];
    
    // Determine dynamic greeting
    date_default_timezone_set('UTC'); // Or dynamic
    $hour = date('H');
    if ($hour < 12) {
        $greeting = 'Good morning';
    } elseif ($hour < 17) {
        $greeting = 'Good afternoon';
    } else {
        $greeting = 'Good evening';
    }
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($sys_name) ?> - <?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.0.4">
</head>
<body>

    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="logo-container">
            <?php if (!empty($settings['logo_path']) && file_exists(dirname(__DIR__) . '/' . $settings['logo_path'])): ?>
                <img src="<?= htmlspecialchars($settings['logo_path']) ?>" alt="Logo" class="logo-img" style="width: 2.5rem; height: 2.5rem; object-fit: contain;">
            <?php else: ?>
                <div class="logo-icon"><?= htmlspecialchars($sys_abbr) ?></div>
            <?php endif; ?>
            <span class="logo-text"><?= htmlspecialchars($sys_name) ?></span>
        </div>
        <ul class="nav-links">
            <?php foreach ($nav_items as $key => $item): ?>
                <li class="nav-item <?= $active_page === $key ? 'active' : '' ?>">
                    <a href="<?= $item['url'] ?>">
                        <?= $item['icon'] ?>
                        <span class="nav-text"><?= htmlspecialchars($item['label']) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="sidebar-footer">
            <?= htmlspecialchars($org_name) ?><br>&copy; <?= date('Y') ?>
        </div>
    </aside>

    <!-- Mobile Fixed Header -->
    <div class="mobile-header">
        <div class="logo-container" style="margin-bottom: 0;">
            <?php if (!empty($settings['logo_path']) && file_exists(dirname(__DIR__) . '/' . $settings['logo_path'])): ?>
                <img src="<?= htmlspecialchars($settings['logo_path']) ?>" alt="Logo" class="logo-img" style="width: 2rem; height: 2rem; object-fit: contain;">
            <?php else: ?>
                <div class="logo-icon" style="width: 2rem; height: 2rem; font-size: 1rem; box-shadow: none;"><?= htmlspecialchars($sys_abbr) ?></div>
            <?php endif; ?>
            <span class="logo-text" style="font-size: 1.1rem;"><?= htmlspecialchars($sys_name) ?></span>
        </div>
        <button class="mobile-menu-btn" onclick="toggleMobileDrawer()">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
    </div>

    <!-- Mobile Slide-out Drawer Overlay -->
    <div class="mobile-drawer" id="mobileDrawer">
        <div class="mobile-drawer-header">
            <div class="logo-container" style="margin-bottom: 0;">
                <?php if (!empty($settings['logo_path']) && file_exists(dirname(__DIR__) . '/' . $settings['logo_path'])): ?>
                    <img src="<?= htmlspecialchars($settings['logo_path']) ?>" alt="Logo" class="logo-img" style="width: 2.25rem; height: 2.25rem; object-fit: contain;">
                <?php else: ?>
                    <div class="logo-icon" style="width: 2.25rem; height: 2.25rem; font-size: 1.1rem;"><?= htmlspecialchars($sys_abbr) ?></div>
                <?php endif; ?>
                <span class="logo-text" style="font-size: 1.2rem;"><?= htmlspecialchars($sys_name) ?></span>
            </div>
            <button class="mobile-menu-btn" onclick="toggleMobileDrawer()" style="font-size: 1.8rem; line-height: 1; color: var(--text-muted); background: none; border: none; padding: 0;">&times;</button>
        </div>
        <ul class="nav-links" style="margin-top: 2rem;">
            <?php foreach ($nav_items as $key => $item): ?>
                <li class="nav-item <?= $active_page === $key ? 'active' : '' ?>">
                    <a href="<?= $item['url'] ?>" onclick="toggleMobileDrawer()">
                        <?= $item['icon'] ?>
                        <span class="nav-text"><?= htmlspecialchars($item['label']) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <div style="margin-top: auto; padding-top: 1.5rem; text-align: center; font-size: 0.75rem; color: var(--text-muted); border-top: 0.0625rem solid var(--border-color);">
            <?= htmlspecialchars($org_name) ?><br>&copy; <?= date('Y') ?>
        </div>
    </div>

    <script>
    function toggleMobileDrawer() {
        const drawer = document.getElementById('mobileDrawer');
        if (drawer) {
            drawer.classList.toggle('active');
        }
    }
    </script>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
        <header>
            <div class="header-title">
                <h1><?= htmlspecialchars($title) ?></h1>
                <p><?= $greeting ?>, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Administrator') ?>! Here is the latest church data summary.</p>
            </div>
        </header>
        <main>
    <?php
}

function render_footer() {
    ?>
        </main>
    </div>
</body>
</html>
    <?php
}
?>
