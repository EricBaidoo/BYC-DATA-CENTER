<?php
// index.php - Main Dashboard for BYC Data Center
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/layout.php';

// Fetch Statistics

// 1. Total Members
$stmt = $pdo->query("SELECT COUNT(*) FROM members WHERE deleted_at IS NULL");
$total_members = $stmt->fetchColumn();

// 2. Workforce (members assigned to any department except 'None')
$stmt = $pdo->query("
    SELECT COUNT(*) FROM members 
    WHERE department_id IS NOT NULL 
      AND department_id != (SELECT id FROM departments WHERE name = 'None')
      AND deleted_at IS NULL
");
$workforce_count = $stmt->fetchColumn();

// 3. Committed Members
$stmt = $pdo->query("
    SELECT COUNT(*) FROM members m
    WHERE m.deleted_at IS NULL AND (EXISTS (
        SELECT 1 FROM member_attendance ma
        JOIN attendance a ON ma.attendance_id = a.id
        WHERE ma.member_id = m.id 
          AND ma.status = 'Present' 
          AND a.date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    ) OR m.join_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH))
");
$committed_count = $stmt->fetchColumn();

// 4. Visitors Count (shepherding focus)
$stmt = $pdo->query("SELECT COUNT(*) FROM members WHERE member_status = 'Visitor' AND deleted_at IS NULL");
$visitor_count = $stmt->fetchColumn();

// Fetch Recent Attendance (Last 5 Services)
$stmt = $pdo->query("
    SELECT a.id, s.name as title, a.date,
           SUM(CASE WHEN ma.status = 'Present' THEN 1 ELSE 0 END) as present_count,
           COUNT(ma.member_id) as total_count
    FROM attendance a
    JOIN services s ON a.service_id = s.id
    LEFT JOIN member_attendance ma ON a.id = ma.attendance_id
    GROUP BY a.id
    ORDER BY a.date DESC
    LIMIT 5
");
$recent_services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Recently Added Members (LIMIT 3)
$stmt = $pdo->query("
    SELECT CONCAT(m.first_name, ' ', COALESCE(m.last_name, '')) as name, m.join_date, d.name as dept_name 
    FROM members m
    LEFT JOIN departments d ON m.department_id = d.id
    WHERE m.deleted_at IS NULL
    ORDER BY m.created_at DESC
    LIMIT 3
");
$recent_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Upcoming Birthdays (Next 30 Days, LIMIT 3)
$stmt = $pdo->query("
    SELECT CONCAT(first_name, ' ', COALESCE(last_name, '')) as name, birthday,
           DATE_ADD(birthday, INTERVAL YEAR(CURDATE()) - YEAR(birthday) + (CASE WHEN DATE_FORMAT(birthday, '%m%d') < DATE_FORMAT(CURDATE(), '%m%d') THEN 1 ELSE 0 END) YEAR) AS next_birthday
    FROM members
    WHERE birthday IS NOT NULL AND deleted_at IS NULL
    HAVING next_birthday BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY next_birthday ASC
    LIMIT 3
");
$upcoming_birthdays = $stmt->fetchAll(PDO::FETCH_ASSOC);

render_header('Overview Dashboard', 'dashboard');
?>

<!-- Statistics Cards Row -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
    
    <!-- Stat Card: Members -->
    <div class="relative overflow-hidden bg-bg-surface backdrop-blur-md border border-border-custom hover:border-primary/30 rounded-xl p-6 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg flex flex-col">
        <div class="absolute inset-0 bg-gradient-to-tr from-primary/5 to-transparent pointer-events-none z-[-1]"></div>
        <div class="flex justify-between items-center mb-4">
            <span class="text-xs font-semibold text-text-secondary uppercase tracking-wider">Entire Membership</span>
            <div class="w-11 h-11 rounded-full flex items-center justify-center text-lg bg-primary/15 text-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
        </div>
        <div class="font-heading font-extrabold text-3xl sm:text-4xl text-white mb-1"><?= $total_members ?></div>
        <div class="text-xs text-text-muted mt-1">Total registered church database</div>
    </div>

    <!-- Stat Card: Committed -->
    <div class="relative overflow-hidden bg-bg-surface backdrop-blur-md border border-border-custom hover:border-secondary/30 rounded-xl p-6 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg flex flex-col">
        <div class="absolute inset-0 bg-gradient-to-tr from-secondary/5 to-transparent pointer-events-none z-[-1]"></div>
        <div class="flex justify-between items-center mb-4">
            <span class="text-xs font-semibold text-text-secondary uppercase tracking-wider">Committed Members</span>
            <div class="w-11 h-11 rounded-full flex items-center justify-center text-lg bg-secondary/15 text-secondary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
        <div class="font-heading font-extrabold text-3xl sm:text-4xl text-white mb-1"><?= $committed_count ?></div>
        <div class="text-xs text-text-muted mt-1">Active attendance in the last 6 months</div>
    </div>

    <!-- Stat Card: Workforce -->
    <div class="relative overflow-hidden bg-bg-surface backdrop-blur-md border border-border-custom hover:border-warning/30 rounded-xl p-6 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg flex flex-col">
        <div class="absolute inset-0 bg-gradient-to-tr from-warning/5 to-transparent pointer-events-none z-[-1]"></div>
        <div class="flex justify-between items-center mb-4">
            <span class="text-xs font-semibold text-text-secondary uppercase tracking-wider">Workforce</span>
            <div class="w-11 h-11 rounded-full flex items-center justify-center text-lg bg-warning/15 text-warning">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
            </div>
        </div>
        <div class="font-heading font-extrabold text-3xl sm:text-4xl text-white mb-1"><?= $workforce_count ?></div>
        <div class="text-xs text-text-muted mt-1">Members serving in departments</div>
    </div>

    <!-- Stat Card: Visitors -->
    <div class="relative overflow-hidden bg-bg-surface backdrop-blur-md border border-border-custom hover:border-purple-400/30 rounded-xl p-6 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg flex flex-col">
        <div class="absolute inset-0 bg-gradient-to-tr from-purple-400/5 to-transparent pointer-events-none z-[-1]"></div>
        <div class="flex justify-between items-center mb-4">
            <span class="text-xs font-semibold text-purple-400 uppercase tracking-wider">Visitors & Guests</span>
            <div class="w-11 h-11 rounded-full flex items-center justify-center text-lg bg-purple-400/15 text-purple-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
            </div>
        </div>
        <div class="font-heading font-extrabold text-3xl sm:text-4xl text-white mb-1"><?= $visitor_count ?></div>
        <div class="text-xs text-text-muted mt-1">New visitors in shepherding pipeline</div>
    </div>
</div>

<!-- Dashboard Grid (2 columns on lg screens) -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    
    <!-- Attendance Progress Panel (Left: 2 cols) -->
    <div class="lg:col-span-2 bg-bg-surface backdrop-blur-md border border-border-custom rounded-xl p-6 sm:p-7">
        <div class="flex justify-between items-center mb-6 border-b border-border-custom pb-3">
            <h2 class="font-heading font-bold text-lg text-white">Recent Service Attendance</h2>
            <a href="attendance" class="bg-bg-surface-solid text-text-primary border border-border-custom font-semibold text-xs px-3 py-1.5 rounded hover:bg-border-custom-hover hover:text-white transition-all duration-150 flex items-center gap-1.5">View All</a>
        </div>
        <div class="flex flex-col gap-5">
            <?php if (empty($recent_services)): ?>
                <p class="text-text-muted text-center py-8">No attendance records found yet.</p>
            <?php else: ?>
                <?php foreach ($recent_services as $service): 
                    $percent = $service['total_count'] > 0 ? round(($service['present_count'] / $service['total_count']) * 100) : 0;
                ?>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                        <div class="w-full sm:w-44 text-sm text-text-secondary leading-tight truncate">
                            <strong class="text-white block"><?= htmlspecialchars($service['title']) ?></strong>
                            <span class="text-xs text-text-muted"><?= date('M d, Y', strtotime($service['date'])) ?></span>
                        </div>
                        <div class="flex-grow flex items-center gap-3">
                            <div class="flex-grow h-2.5 bg-white/5 rounded overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-primary to-secondary rounded transition-all duration-1000" style="width: <?= $percent ?>%;"></div>
                            </div>
                            <div class="w-10 text-right text-xs font-semibold text-white"><?= $percent ?>%</div>
                            <div class="text-2xs text-text-muted w-16 text-right font-medium">
                                <?= $service['present_count'] ?> / <?= $service['total_count'] ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column: Recent Members & Upcoming Birthdays (Right: 1 col) -->
    <div class="flex flex-col gap-6">
        
        <!-- Recently Registered Members Panel -->
        <div class="bg-bg-surface backdrop-blur-md border border-border-custom rounded-xl p-6 sm:p-7">
            <div class="flex justify-between items-center mb-6 border-b border-border-custom pb-3">
                <h2 class="font-heading font-bold text-lg text-white">Recently Registered</h2>
                <a href="members" class="bg-bg-surface-solid text-text-primary border border-border-custom font-semibold text-xs px-3 py-1.5 rounded hover:bg-border-custom-hover hover:text-white transition-all duration-150 flex items-center gap-1.5">View All</a>
            </div>
            <div class="flex flex-col gap-3">
                <?php if (empty($recent_members)): ?>
                    <p class="text-text-muted text-center py-6">No members registered yet.</p>
                <?php else: ?>
                    <?php foreach ($recent_members as $member): 
                        $initials = strtoupper(substr($member['name'], 0, 1));
                    ?>
                        <div class="flex items-center gap-3 p-2 bg-white/[0.015] border border-transparent hover:border-border-custom rounded-lg hover:bg-white/[0.04] transition-all duration-200">
                            <div class="w-9 h-9 rounded-full bg-bg-surface-solid flex items-center justify-center font-bold text-sm text-primary border border-border-custom"><?= htmlspecialchars($initials) ?></div>
                            <div class="flex-grow min-w-0">
                                <div class="text-sm font-semibold text-white truncate"><?= htmlspecialchars($member['name']) ?></div>
                                <div class="text-2xs text-text-muted truncate">Dept: <?= htmlspecialchars($member['dept_name'] ?? 'None') ?></div>
                            </div>
                            <div class="text-2xs text-text-muted font-medium pr-1">
                                <?= date('M d', strtotime($member['join_date'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upcoming Birthdays Panel -->
        <div class="bg-bg-surface backdrop-blur-md border border-border-custom rounded-xl p-6 sm:p-7">
            <div class="flex justify-between items-center mb-6 border-b border-border-custom pb-3">
                <h2 class="font-heading font-bold text-lg text-white">Upcoming Birthdays</h2>
            </div>
            <div class="flex flex-col gap-3">
                <?php if (empty($upcoming_birthdays)): ?>
                    <p class="text-text-muted text-center py-6">No birthdays in the next 30 days.</p>
                <?php else: ?>
                    <?php foreach ($upcoming_birthdays as $bday): 
                        $initials = strtoupper(substr($bday['name'], 0, 1));
                        $days_left = intval(round((strtotime($bday['next_birthday']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24)));
                        $days_text = $days_left === 0 ? 'Today!' : ($days_left === 1 ? 'Tomorrow' : "In $days_left days");
                        $days_color = $days_left === 0 ? 'text-danger' : ($days_left <= 7 ? 'text-warning' : 'text-text-muted');
                    ?>
                        <div class="flex items-center gap-3 p-2 bg-white/[0.015] border border-transparent hover:border-border-custom rounded-lg hover:bg-white/[0.04] transition-all duration-200">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-warning to-danger flex items-center justify-center font-bold text-sm text-white border border-border-custom"><?= htmlspecialchars($initials) ?></div>
                            <div class="flex-grow min-w-0">
                                <div class="text-sm font-semibold text-white truncate"><?= htmlspecialchars($bday['name']) ?></div>
                                <div class="text-2xs text-text-muted truncate">Birthday: <?= date('M d', strtotime($bday['birthday'])) ?></div>
                            </div>
                            <div class="text-2xs font-bold <?= $days_color ?> pr-1">
                                <?= htmlspecialchars($days_text) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php
render_footer();
?>
