<?php
// layout.php - Master layout rendering helper for BYC Data Center
// Updated to use Tailwind CSS v4

function render_header($title, $active_page = 'dashboard') {
    $settings = $GLOBALS['site_settings'] ?? [];
    $sys_name = $settings['system_name'] ?? 'BYC DATA CENTER';
    $sys_abbr = !empty($sys_name) ? substr($sys_name, 0, 1) : 'B';
    $org_name = $settings['organization_name'] ?? 'Beersheba Youth Church';

    $nav_items = [
        'dashboard' => ['label' => 'Dashboard', 'url' => 'index', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z"></path></svg>'],
        'members' => ['label' => 'Members', 'url' => 'members', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>'],
        'attendance' => ['label' => 'Attendance', 'url' => 'attendance', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>'],
        'workforce' => ['label' => 'Workforce', 'url' => 'workforce', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>'],
        'settings' => ['label' => 'Settings', 'url' => 'settings', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>'],
        'logout' => ['label' => 'Logout', 'url' => 'logout', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>']
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
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($sys_name) ?> - <?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.0.6">
</head>
<body class="min-h-full flex overflow-x-hidden">

    <!-- Sidebar Navigation for Desktop -->
    <aside class="w-64 h-screen bg-bg-surface-solid border-r border-border-custom px-6 py-8 flex flex-col fixed left-0 top-0 z-40 hidden lg:flex">
        <div class="flex items-center gap-3 mb-10">
            <?php if (!empty($settings['logo_path']) && file_exists(dirname(__DIR__) . '/' . $settings['logo_path'])): ?>
                <img src="<?= htmlspecialchars($settings['logo_path']) ?>" alt="Logo" class="w-10 h-10 object-contain">
            <?php else: ?>
                <div class="w-10 h-10 bg-gradient-to-br from-primary to-secondary rounded flex items-center justify-center font-extrabold text-xl text-white shadow-[0_4px_12px_rgba(139,92,246,0.35)]"><?= htmlspecialchars($sys_abbr) ?></div>
            <?php endif; ?>
            <span class="font-heading font-bold text-xl tracking-tight bg-gradient-to-r from-white to-gray-400 bg-clip-text text-transparent"><?= htmlspecialchars($sys_name) ?></span>
        </div>
        <ul class="flex flex-col gap-2 list-none p-0 m-0">
            <?php foreach ($nav_items as $key => $item): ?>
                <li class="list-none">
                    <a href="<?= $item['url'] ?>" class="flex items-center gap-4 px-4 py-3 rounded text-text-secondary hover:text-text-primary hover:bg-bg-surface-hover border border-transparent transition-all duration-200 <?= $active_page === $key ? 'text-white bg-gradient-to-r from-primary-glow to-transparent border-l-4 border-l-primary! pl-3! font-semibold' : 'font-medium' ?>">
                        <?= $item['icon'] ?>
                        <span><?= htmlspecialchars($item['label']) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="mt-auto pt-4 border-t border-border-custom text-xs text-text-muted text-center leading-relaxed">
            <?= htmlspecialchars($org_name) ?><br>&copy; <?= date('Y') ?>
        </div>
    </aside>

    <!-- Mobile Fixed Header -->
    <div class="flex lg:hidden fixed top-0 left-0 w-full h-16 bg-bg-surface-solid border-b border-border-custom z-30 items-center justify-between px-5 backdrop-blur-md bg-opacity-70">
        <div class="flex items-center gap-3">
            <?php if (!empty($settings['logo_path']) && file_exists(dirname(__DIR__) . '/' . $settings['logo_path'])): ?>
                <img src="<?= htmlspecialchars($settings['logo_path']) ?>" alt="Logo" class="w-8 h-8 object-contain">
            <?php else: ?>
                <div class="w-8 h-8 bg-gradient-to-br from-primary to-secondary rounded flex items-center justify-center font-extrabold text-sm text-white"><?= htmlspecialchars($sys_abbr) ?></div>
            <?php endif; ?>
            <span class="font-heading font-bold text-lg tracking-tight bg-gradient-to-r from-white to-gray-400 bg-clip-text text-transparent"><?= htmlspecialchars($sys_name) ?></span>
        </div>
        <button class="bg-none border-none text-text-primary cursor-pointer p-1 flex items-center justify-center" onclick="toggleMobileDrawer()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
    </div>

    <!-- Mobile Slide-out Drawer Overlay -->
    <div class="flex lg:hidden flex-col fixed top-0 -left-[290px] w-[290px] h-screen bg-[#0f1422] border-r border-border-custom z-50 p-6 transition-all duration-300 ease-in-out shadow-2xl" id="mobileDrawer">
        <div class="flex justify-between items-center border-b border-border-custom pb-4">
            <div class="flex items-center gap-3">
                <?php if (!empty($settings['logo_path']) && file_exists(dirname(__DIR__) . '/' . $settings['logo_path'])): ?>
                    <img src="<?= htmlspecialchars($settings['logo_path']) ?>" alt="Logo" class="w-9 h-9 object-contain">
                <?php else: ?>
                    <div class="w-9 h-9 bg-gradient-to-br from-primary to-secondary rounded flex items-center justify-center font-extrabold text-base text-white"><?= htmlspecialchars($sys_abbr) ?></div>
                <?php endif; ?>
                <span class="font-heading font-bold text-lg tracking-tight bg-gradient-to-r from-white to-gray-400 bg-clip-text text-transparent"><?= htmlspecialchars($sys_name) ?></span>
            </div>
            <button class="text-3xl line-height-none text-text-muted hover:text-white bg-none border-none p-0 cursor-pointer" onclick="toggleMobileDrawer()">&times;</button>
        </div>
        <ul class="flex flex-col gap-2 list-none p-0 m-0 mt-8">
            <?php foreach ($nav_items as $key => $item): ?>
                <li class="list-none">
                    <a href="<?= $item['url'] ?>" onclick="toggleMobileDrawer()" class="flex items-center gap-4 px-4 py-3 rounded text-text-secondary hover:text-text-primary hover:bg-bg-surface-hover border border-transparent transition-all duration-200 <?= $active_page === $key ? 'text-white bg-gradient-to-r from-primary-glow to-transparent border-l-4 border-l-primary! pl-3! font-semibold' : 'font-medium' ?>">
                        <?= $item['icon'] ?>
                        <span><?= htmlspecialchars($item['label']) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="mt-auto pt-4 border-t border-border-custom text-xs text-text-muted text-center leading-relaxed">
            <?= htmlspecialchars($org_name) ?><br>&copy; <?= date('Y') ?>
        </div>
    </div>

    <script>
    function toggleMobileDrawer() {
        const drawer = document.getElementById('mobileDrawer');
        if (drawer) {
            if (drawer.classList.contains('-left-[290px]')) {
                drawer.classList.remove('-left-[290px]');
                drawer.classList.add('left-0');
            } else {
                drawer.classList.remove('left-0');
                drawer.classList.add('-left-[290px]');
            }
        }
    }
    </script>

    <!-- Main Content Wrapper -->
    <div class="lg:ml-64 flex-grow p-4 sm:p-6 lg:p-10 min-h-screen flex flex-col pt-20 lg:pt-10 w-full overflow-x-hidden">
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="font-heading font-extrabold text-2xl sm:text-3xl tracking-tight text-white mb-1"><?= htmlspecialchars($title) ?></h1>
                <p class="text-text-secondary text-xs sm:text-sm"><?= $greeting ?>, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Administrator') ?>! Here is the latest church shepherding summary.</p>
            </div>
        </header>
        <main class="flex-grow">
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

function render_csrf_input() {
    $token = $_SESSION['csrf_token'] ?? '';
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}
?>
