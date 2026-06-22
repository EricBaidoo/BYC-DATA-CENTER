<?php
// attendance.php - Service Attendance Sessions and Service Directory
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/layout.php';

$error = '';
$success = '';

// Fetch active session state if running
$active_session_id = $GLOBALS['site_settings']['active_session_id'] ?? '';
$active_session = null;
$active_present_members = [];

if (!empty($active_session_id)) {
    $stmt_active = $pdo->prepare("
        SELECT a.id, a.date, s.name as service_name
        FROM attendance a
        JOIN services s ON a.service_id = s.id
        WHERE a.id = ?
    ");
    $stmt_active->execute([$active_session_id]);
    $active_session = $stmt_active->fetch(PDO::FETCH_ASSOC);

    if ($active_session) {
        $stmt_pres = $pdo->prepare("SELECT member_id FROM member_attendance WHERE attendance_id = ? AND status = 'Present'");
        $stmt_pres->execute([$active_session_id]);
        $active_present_members = $stmt_pres->fetchAll(PDO::FETCH_COLUMN);
    }
}

// 1. Handle Updating Active Session Attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'save_active') {
    validate_csrf();
    if (empty($active_session_id) || !$active_session) {
        $error = 'No active service session is running.';
    } else {
        $present_member_ids = $_POST['present_members'] ?? []; // Array of member IDs marked present
        try {
            $pdo->beginTransaction();

            // Fetch all active members
            $members = $pdo->query("SELECT id FROM members WHERE deleted_at IS NULL")->fetchAll(PDO::FETCH_COLUMN);

            // Re-populate all statuses (clear and insert)
            $stmt_del = $pdo->prepare("DELETE FROM member_attendance WHERE attendance_id = ?");
            $stmt_del->execute([$active_session_id]);

            $stmt_ma = $pdo->prepare("INSERT INTO member_attendance (member_id, attendance_id, status) VALUES (?, ?, ?)");
            foreach ($members as $member_id) {
                $status = in_array($member_id, $present_member_ids) ? 'Present' : 'Absent';
                $stmt_ma->execute([$member_id, $active_session_id, $status]);
            }

            $pdo->commit();
            
            log_audit_action('SAVE_ATTENDANCE', 'attendance', $active_session_id, json_encode([
                'present_count' => count($present_member_ids)
            ]));

            header("Location: attendance?msg=Active session attendance updated successfully");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error saving attendance: ' . $e->getMessage();
        }
    }
}

// Fetch selected service details for modal/view details
$selected_session = null;
$selected_attendance_details = [];
if (isset($_GET['view_id'])) {
    $view_id = intval($_GET['view_id']);
    
    $stmt = $pdo->prepare("
        SELECT a.id, a.date, s.name as title, s.description
        FROM attendance a
        JOIN services s ON a.service_id = s.id
        WHERE a.id = ?
    ");
    $stmt->execute([$view_id]);
    $selected_session = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($selected_session) {
        $stmt = $pdo->prepare("
            SELECT CONCAT(m.first_name, ' ', COALESCE(m.last_name, '')) as name, d.name as dept_name, ma.status
            FROM member_attendance ma
            JOIN members m ON ma.member_id = m.id
            LEFT JOIN departments d ON m.department_id = d.id
            WHERE ma.attendance_id = ?
            ORDER BY m.first_name ASC, m.last_name ASC
        ");
        $stmt->execute([$view_id]);
        $selected_attendance_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (isset($_GET['msg'])) {
    $success = $_GET['msg'];
}

// Fetch all defined services
$services = $pdo->query("SELECT * FROM services ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all active members for the logging checklist
$all_members = $pdo->query("SELECT id, CONCAT(first_name, ' ', COALESCE(last_name, '')) as name, gender FROM members WHERE deleted_at IS NULL ORDER BY first_name ASC, last_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch service session attendance history with counts
$history = $pdo->query("
    SELECT a.id, a.date, s.name as title,
           SUM(CASE WHEN ma.status = 'Present' THEN 1 ELSE 0 END) as present_count,
           COUNT(ma.member_id) as total_count
    FROM attendance a
    JOIN services s ON a.service_id = s.id
    LEFT JOIN member_attendance ma ON a.id = ma.attendance_id
    GROUP BY a.id
    ORDER BY a.date DESC
")->fetchAll(PDO::FETCH_ASSOC);

render_header('Service Attendance Tracker', 'attendance');
?>

<!-- Action Status Alert -->
<?php if (!empty($error)): ?>
    <div class="bg-danger/15 border border-danger text-danger p-4 rounded-md mb-6 text-sm font-semibold">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="bg-secondary/15 border border-secondary text-secondary p-4 rounded-md mb-6 text-sm font-semibold">
        <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<!-- Two-Column Layout -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Left Column: Sessions History (2 cols on large screen) -->
    <div class="lg:col-span-2 bg-bg-surface backdrop-blur-md border border-border-custom rounded-xl p-6 sm:p-7">
        <div class="flex justify-between items-center mb-6 border-b border-border-custom pb-3">
            <h2 class="font-heading font-bold text-lg text-white">Attendance Service History</h2>
        </div>
        <div class="overflow-x-auto w-full max-w-full">
            <?php if (empty($history)): ?>
                <p class="text-text-muted text-center py-12">No services have been logged yet.</p>
            <?php else: ?>
                <table class="w-full border-collapse text-left min-w-[500px]">
                    <thead>
                        <tr class="border-b border-border-custom">
                            <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Service Title & Date</th>
                            <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider hide-mobile">Present</th>
                            <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider hide-mobile">Total Members</th>
                            <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Attendance Rate</th>
                            <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $row): 
                            $percent = $row['total_count'] > 0 ? round(($row['present_count'] / $row['total_count']) * 100) : 0;
                            $rate_class = 'bg-danger/10 border-danger/35 text-danger';
                            if ($percent >= 75) {
                                $rate_class = 'bg-secondary/10 border-secondary/35 text-secondary';
                            } elseif ($percent >= 50) {
                                $rate_class = 'bg-warning/10 border-warning/35 text-warning';
                            }
                        ?>
                            <tr class="border-b border-white/[0.02] hover:bg-white/[0.01] transition-all">
                                <td class="p-3">
                                    <strong class="text-sm text-white block leading-snug"><?= htmlspecialchars($row['title']) ?></strong>
                                    <span class="text-xs text-text-muted"><?= date('M d, Y', strtotime($row['date'])) ?></span>
                                </td>
                                <td class="p-3 text-sm text-text-primary hide-mobile"><?= $row['present_count'] ?></td>
                                <td class="p-3 text-sm text-text-primary hide-mobile"><?= $row['total_count'] ?></td>
                                <td class="p-3">
                                    <span class="inline-block border text-2xs px-2 py-0.5 rounded font-semibold <?= $rate_class ?>"><?= $percent ?>%</span>
                                </td>
                                <td class="p-3 text-right">
                                    <a href="attendance?view_id=<?= $row['id'] ?>" class="inline-block bg-bg-surface-solid text-text-primary border border-border-custom hover:bg-border-custom-hover hover:text-white font-semibold text-xs px-3 py-1.5 rounded transition-all duration-150">Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column: Form Panel (1 col) -->
    <div class="flex flex-col gap-6">
        
        <!-- Active Session Attendance Panel -->
        <?php if ($active_session): ?>
            <div class="bg-bg-surface backdrop-blur-md border border-border-custom rounded-xl p-6 sm:p-7">
                <div class="flex justify-between items-center mb-5 border-b border-border-custom pb-3">
                    <h2 class="font-heading font-bold text-lg text-white">Take Attendance</h2>
                </div>
                
                <div class="bg-secondary/10 border border-secondary/35 p-4 rounded-md mb-5">
                    <div class="text-3xs text-secondary font-extrabold uppercase tracking-widest mb-1">Active Service Session</div>
                    <strong class="text-sm sm:text-base text-white block leading-snug"><?= htmlspecialchars($active_session['service_name']) ?></strong>
                    <div class="text-2xs text-text-muted mt-0.5"><?= date('l, M d, Y', strtotime($active_session['date'])) ?></div>
                </div>

                <?php if (empty($all_members)): ?>
                    <p class="text-text-muted text-xs text-center py-8">Register members in the directory to mark attendance.</p>
                <?php else: ?>
                    <form method="POST" action="attendance?action=save_active" class="flex flex-col gap-4">
                        <?php render_csrf_input(); ?>
                        <div class="flex justify-between items-center gap-4 mt-2">
                            <label class="text-xs font-semibold text-text-secondary">Attendance Sheet (Present)</label>
                            <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-text-secondary select-none">
                                <input type="checkbox" id="toggleAllMembers" class="w-4 h-4 rounded border-white/10 bg-black/30 text-secondary checked:bg-secondary checked:border-secondary focus:ring-offset-0 focus:ring-2 focus:ring-secondary/30 transition-all cursor-pointer">
                                <span>Select All</span>
                            </label>
                        </div>

                        <div>
                            <input type="text" id="attendanceMemberSearch" placeholder="Type name to filter list..." class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white placeholder-text-muted focus:border-secondary focus:ring-4 focus:ring-secondary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-xs">
                        </div>

                        <div class="flex flex-col gap-1 border border-border-custom rounded-md p-2 bg-black/20 max-h-[20rem] overflow-y-auto pr-1">
                            <?php foreach ($all_members as $m): ?>
                                <div class="attendance-member-row flex items-center justify-between p-2 border-b border-white/[0.02] last:border-b-0 hover:bg-white/[0.02] rounded transition-all duration-150">
                                    <label class="flex items-center gap-3 cursor-pointer select-none">
                                        <input type="checkbox" class="member-checkbox w-4 h-4 rounded border-white/10 bg-black/30 text-secondary checked:bg-secondary checked:border-secondary focus:ring-offset-0 focus:ring-2 focus:ring-secondary/30 transition-all cursor-pointer" name="present_members[]" value="<?= $m['id'] ?>" <?= in_array($m['id'], $active_present_members) ? 'checked' : '' ?>>
                                        <span class="text-xs font-medium text-white"><?= htmlspecialchars($m['name']) ?></span>
                                    </label>
                                    <span class="text-3xs text-text-muted font-medium pr-1"><?= htmlspecialchars($m['gender']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="submit" class="w-full bg-primary hover:bg-opacity-90 text-white font-semibold text-sm py-2.5 px-4 rounded shadow-md active:scale-98 transition-all duration-150 cursor-pointer flex justify-center items-center">Save Attendance Changes</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="bg-white/[0.015] border border-border-custom p-8 rounded-xl text-center flex flex-col items-center justify-center gap-3">
                <div class="w-12 h-12 bg-white/[0.03] border border-border-custom rounded-full flex items-center justify-center text-text-muted mb-2">
                    <svg class="w-6 h-6 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <h3 class="text-sm sm:text-base font-bold text-white">No Active Service Session</h3>
                <p class="text-xs text-text-muted max-w-[240px] leading-relaxed">You must start an active service session in the Settings panel before taking member attendance.</p>
                <a href="settings?tab=sessions" class="bg-primary hover:bg-opacity-90 text-white font-semibold text-xs px-4 py-2 rounded shadow-md active:scale-95 transition-all duration-150 flex items-center justify-center gap-1.5 mt-2">Go to Settings</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- View Details Drawer/Modal -->
<?php if ($selected_session): ?>
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[1000] transition-all duration-300" id="detailsModal">
        <div class="bg-bg-surface-solid border border-border-custom rounded-2xl w-full max-w-[480px] p-6 shadow-2xl flex flex-col max-h-[90vh] transition-all duration-300">
            <div class="flex justify-between items-center mb-5 border-b border-border-custom pb-3">
                <div>
                    <h2 class="font-heading font-bold text-base sm:text-lg text-white"><?= htmlspecialchars($selected_session['title']) ?></h2>
                    <p class="text-2xs text-text-muted mt-0.5"><?= date('l, M d, Y', strtotime($selected_session['date'])) ?></p>
                </div>
                <a href="attendance" class="text-2xl text-text-muted hover:text-white bg-none border-none p-0 cursor-pointer text-center leading-none" style="text-decoration: none;">&times;</a>
            </div>
            
            <div class="flex flex-col gap-1 border border-border-custom rounded-md p-2 bg-black/20 overflow-y-auto max-h-[22rem] pr-1">
                <?php foreach ($selected_attendance_details as $detail): ?>
                    <div class="flex items-center justify-between p-2 border-b border-white/[0.02] last:border-b-0">
                        <div>
                            <strong class="text-xs sm:text-sm text-white font-medium block leading-snug"><?= htmlspecialchars($detail['name']) ?></strong>
                            <span class="text-3xs text-text-muted"><?= htmlspecialchars($detail['dept_name'] ?? 'No Department') ?></span>
                        </div>
                        <div>
                            <?php if ($detail['status'] === 'Present'): ?>
                                <span class="border text-3xs px-2 py-0.5 rounded font-semibold bg-secondary/10 border-secondary/35 text-secondary">Present</span>
                            <?php else: ?>
                                <span class="border text-3xs px-2 py-0.5 rounded font-semibold bg-danger/10 border-danger/35 text-danger">Absent</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="flex justify-end border-t border-border-custom pt-4 mt-4 flex-shrink-0">
                <a href="attendance" class="bg-bg-surface-solid text-text-primary border border-border-custom hover:bg-border-custom-hover hover:text-white font-semibold text-xs px-4 py-2 rounded transition-all duration-150 text-center" style="text-decoration: none;">Close</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
// Select All / Unselect All members checkbox logic
const toggleAll = document.getElementById('toggleAllMembers');
if (toggleAll) {
    toggleAll.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.member-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = toggleAll.checked;
        });
    });
}

// Live client-side checklist filter
const attSearchInput = document.getElementById('attendanceMemberSearch');
if (attSearchInput) {
    attSearchInput.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase().trim();
        const rows = document.querySelectorAll('.attendance-member-row');
        rows.forEach(row => {
            const labelText = row.querySelector('label').textContent.toLowerCase();
            if (labelText.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
}
</script>

<?php
render_footer();
?>
