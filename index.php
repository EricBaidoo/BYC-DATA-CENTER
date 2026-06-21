<?php
// index.php - Main Dashboard for BYC Data Center
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/layout.php';

// Fetch Statistics

// 1. Total Members
$stmt = $pdo->query("SELECT COUNT(*) FROM members");
$total_members = $stmt->fetchColumn();

// 2. Workforce (members assigned to any department except 'None')
$stmt = $pdo->query("
    SELECT COUNT(*) FROM members 
    WHERE department_id IS NOT NULL 
      AND department_id != (SELECT id FROM departments WHERE name = 'None')
");
$workforce_count = $stmt->fetchColumn();

// 3. Committed Members
// Criteria: Checked in present for at least one service in the last 6 months, OR joined within the last 6 months
$stmt = $pdo->query("
    SELECT COUNT(*) FROM members m
    WHERE EXISTS (
        SELECT 1 FROM member_attendance ma
        JOIN attendance a ON ma.attendance_id = a.id
        WHERE ma.member_id = m.id 
          AND ma.status = 'Present' 
          AND a.date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    )
    OR m.join_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
");
$committed_count = $stmt->fetchColumn();

// 4. Non-Committed Members (absent for the last 6 months and above)
// Criteria: Has NOT checked in present for any service in the last 6 months, AND (attended before OR joined > 6 months ago)
$stmt = $pdo->query("
    SELECT COUNT(*) FROM members m
    WHERE NOT EXISTS (
        SELECT 1 FROM member_attendance ma
        JOIN attendance a ON ma.attendance_id = a.id
        WHERE ma.member_id = m.id 
          AND ma.status = 'Present' 
          AND a.date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    )
    AND (
        EXISTS (SELECT 1 FROM member_attendance ma WHERE ma.member_id = m.id)
        OR m.join_date < DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    )
");
$non_committed_count = $stmt->fetchColumn();

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

// Fetch Recently Added Members
$stmt = $pdo->query("
    SELECT m.name, m.join_date, d.name as dept_name 
    FROM members m
    LEFT JOIN departments d ON m.department_id = d.id
    ORDER BY m.created_at DESC
    LIMIT 5
");
$recent_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Upcoming Birthdays (Next 30 Days)
$stmt = $pdo->query("
    SELECT name, birthday,
           DATE_ADD(birthday, INTERVAL YEAR(CURDATE()) - YEAR(birthday) + (CASE WHEN DATE_FORMAT(birthday, '%m%d') < DATE_FORMAT(CURDATE(), '%m%d') THEN 1 ELSE 0 END) YEAR) AS next_birthday
    FROM members
    WHERE birthday IS NOT NULL
    HAVING next_birthday BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY next_birthday ASC
    LIMIT 5
");
$upcoming_birthdays = $stmt->fetchAll(PDO::FETCH_ASSOC);

render_header('Overview Dashboard', 'dashboard');
?>

<!-- Statistics Cards Row -->
<div class="stats-grid">
    <div class="stat-card card-membership">
        <div class="stat-header">
            <span class="stat-title">Entire Membership</span>
            <div class="stat-icon-wrapper">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
        </div>
        <div class="stat-value"><?= $total_members ?></div>
        <div class="stat-desc">Total registered church database</div>
    </div>

    <div class="stat-card card-committed">
        <div class="stat-header">
            <span class="stat-title">Committed Members</span>
            <div class="stat-icon-wrapper">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
        <div class="stat-value"><?= $committed_count ?></div>
        <div class="stat-desc">Active attendance in the last 6 months</div>
    </div>

    <div class="stat-card card-workforce">
        <div class="stat-header">
            <span class="stat-title">Workforce</span>
            <div class="stat-icon-wrapper">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
            </div>
        </div>
        <div class="stat-value"><?= $workforce_count ?></div>
        <div class="stat-desc">Members serving in departments</div>
    </div>

    <div class="stat-card card-noncommitted">
        <div class="stat-header">
            <span class="stat-title">Non-Committed</span>
            <div class="stat-icon-wrapper">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
        </div>
        <div class="stat-value"><?= $non_committed_count ?></div>
        <div class="stat-desc">Absent for last 6 months or above</div>
    </div>
</div>

<!-- Dashboard Grid (2 columns) -->
<div class="dashboard-grid">
    
    <!-- Attendance Progress Panel -->
    <div class="card-panel">
        <div class="panel-header">
            <h2 class="panel-title">Recent Service Attendance</h2>
            <a href="attendance" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">View All</a>
        </div>
        <div class="attendance-chart-container">
            <?php if (empty($recent_services)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 2rem 0;">No attendance records found yet.</p>
            <?php else: ?>
                <?php foreach ($recent_services as $service): 
                    $percent = $service['total_count'] > 0 ? round(($service['present_count'] / $service['total_count']) * 100) : 0;
                ?>
                    <div class="chart-bar-row">
                        <div class="chart-label">
                            <strong><?= htmlspecialchars($service['title']) ?></strong>
                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?= date('M d, Y', strtotime($service['date'])) ?></div>
                        </div>
                        <div class="chart-bar-details">
                            <div class="chart-track">
                                <div class="chart-fill" style="width: <?= $percent ?>%;"></div>
                            </div>
                            <div class="chart-value"><?= $percent ?>%</div>
                            <div class="chart-count">
                                <?= $service['present_count'] ?> / <?= $service['total_count'] ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column: Recent Members & Upcoming Birthdays -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- Recently Registered Members Panel -->
        <div class="card-panel">
            <div class="panel-header">
                <h2 class="panel-title">Recently Registered</h2>
                <a href="members" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">View All</a>
            </div>
            <div class="activity-list">
                <?php if (empty($recent_members)): ?>
                    <p style="color: var(--text-muted); text-align: center; padding: 2rem 0;">No members registered yet.</p>
                <?php else: ?>
                    <?php foreach ($recent_members as $member): 
                        $initials = strtoupper(substr($member['name'], 0, 1));
                    ?>
                        <div class="activity-item">
                            <div class="activity-avatar"><?= htmlspecialchars($initials) ?></div>
                            <div class="activity-info">
                                <div class="activity-name"><?= htmlspecialchars($member['name']) ?></div>
                                <div class="activity-meta">Dept: <?= htmlspecialchars($member['dept_name'] ?? 'None') ?></div>
                            </div>
                            <div class="activity-meta" style="font-size: 0.75rem;">
                                <?= date('M d', strtotime($member['join_date'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upcoming Birthdays Panel -->
        <div class="card-panel">
            <div class="panel-header">
                <h2 class="panel-title">Upcoming Birthdays (Next 30 Days)</h2>
            </div>
            <div class="activity-list">
                <?php if (empty($upcoming_birthdays)): ?>
                    <p style="color: var(--text-muted); text-align: center; padding: 2rem 0;">No birthdays in the next 30 days.</p>
                <?php else: ?>
                    <?php foreach ($upcoming_birthdays as $bday): 
                        $initials = strtoupper(substr($bday['name'], 0, 1));
                        $days_left = intval(round((strtotime($bday['next_birthday']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24)));
                        $days_text = $days_left === 0 ? 'Today!' : ($days_left === 1 ? 'Tomorrow' : "In $days_left days");
                        $days_color = $days_left === 0 ? 'var(--danger)' : ($days_left <= 7 ? 'var(--warning)' : 'var(--text-muted)');
                    ?>
                        <div class="activity-item">
                            <div class="activity-avatar" style="background: linear-gradient(135deg, var(--warning), var(--danger));"><?= htmlspecialchars($initials) ?></div>
                            <div class="activity-info">
                                <div class="activity-name"><?= htmlspecialchars($bday['name']) ?></div>
                                <div class="activity-meta">Birthday: <?= date('M d', strtotime($bday['birthday'])) ?></div>
                            </div>
                            <div class="activity-meta" style="font-size: 0.75rem; font-weight: 600; color: <?= $days_color ?>;">
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
