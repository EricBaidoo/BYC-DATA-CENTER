<?php
// attendance.php - Service Attendance Sessions and Service Directory
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/layout.php';

$error = '';
$success = '';

// 1. Handle Logging Attendance Session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'log') {
    $date = trim($_POST['date'] ?? '');
    $service_id = intval($_POST['service_id'] ?? 0);
    $present_member_ids = $_POST['present_members'] ?? []; // Array of member IDs marked present

    if (empty($date) || $service_id <= 0) {
        $error = 'Service Date and Service Type selection are required.';
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();

            // 1. Insert session record
            $stmt = $pdo->prepare("INSERT INTO attendance (service_id, date) VALUES (?, ?)");
            $stmt->execute([$service_id, $date]);
            $attendance_id = $pdo->lastInsertId();

            // 2. Fetch all current members to record attendance status for each one
            $members = $pdo->query("SELECT id FROM members")->fetchAll(PDO::FETCH_COLUMN);

            $stmt_ma = $pdo->prepare("INSERT INTO member_attendance (member_id, attendance_id, status) VALUES (?, ?, ?)");
            
            foreach ($members as $member_id) {
                // If member_id exists in the checked present array, they are Present; otherwise Absent
                $status = in_array($member_id, $present_member_ids) ? 'Present' : 'Absent';
                $stmt_ma->execute([$member_id, $attendance_id, $status]);
            }

            $pdo->commit();
            header("Location: attendance?msg=Attendance session logged successfully");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error logging session attendance: ' . $e->getMessage();
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
            SELECT m.name, d.name as dept_name, ma.status
            FROM member_attendance ma
            JOIN members m ON ma.member_id = m.id
            LEFT JOIN departments d ON m.department_id = d.id
            WHERE ma.attendance_id = ?
            ORDER BY m.name ASC
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

// Fetch all members for the logging checklist
$all_members = $pdo->query("SELECT id, name, gender FROM members ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

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
    <div style="background: var(--danger-glow); border: 0.0625rem solid var(--danger); color: var(--danger); padding: 1rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div style="background: var(--secondary-glow); border: 0.0625rem solid var(--secondary); color: var(--secondary); padding: 1rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem;">
        <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<!-- Two-Column Layout -->
<div class="dashboard-grid">
    
    <!-- Left Column: Sessions History -->
    <div class="card-panel">
        <div class="panel-header">
            <h2 class="panel-title">Attendance Service History</h2>
        </div>
        <div class="table-container">
            <?php if (empty($history)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 3rem 0;">No services have been logged yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Service Title & Date</th>
                            <th class="hide-mobile">Present</th>
                            <th class="hide-mobile">Total Members</th>
                            <th>Attendance Rate</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $row): 
                            $percent = $row['total_count'] > 0 ? round(($row['present_count'] / $row['total_count']) * 100) : 0;
                            $rate_class = 'badge-danger';
                            if ($percent >= 75) {
                                $rate_class = 'badge-success';
                            } elseif ($percent >= 50) {
                                $rate_class = 'badge-warning';
                            }
                        ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($row['title']) ?></strong>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= date('M d, Y', strtotime($row['date'])) ?></div>
                                </td>
                                <td class="hide-mobile"><?= $row['present_count'] ?></td>
                                <td class="hide-mobile"><?= $row['total_count'] ?></td>
                                <td>
                                    <span class="badge <?= $rate_class ?>"><?= $percent ?>%</span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="attendance?view_id=<?= $row['id'] ?>" class="btn btn-secondary" style="padding: 0.35rem 0.7rem; font-size: 0.8rem;">Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column: Form Panel (Session Logger & Service Directory) -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- Log New Session -->
        <div class="card-panel">
            <div class="panel-header">
                <h2 class="panel-title">Start Attendance Session</h2>
            </div>
            
            <?php if (empty($services)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 1.5rem 0;">Please define at least one service type in the <a href="settings" style="color: var(--primary); text-decoration: underline;">Settings page</a> before starting a session.</p>
            <?php elseif (empty($all_members)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 1.5rem 0;">Register members in the directory before logging attendance.</p>
            <?php else: ?>
                <form method="POST" action="attendance?action=log">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="serviceId">Select Service Type</label>
                            <select name="service_id" id="serviceId" required>
                                <option value="">-- Choose Service --</option>
                                <?php foreach ($services as $srv): ?>
                                    <option value="<?= $srv['id'] ?>"><?= htmlspecialchars($srv['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="serviceDate">Session Date</label>
                            <input type="date" name="date" id="serviceDate" required value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>

                    <div class="attendance-sheet-header">
                        <label>Attendance Sheet (Check who is Present)</label>
                        <label class="checkbox-custom" style="font-size: 0.8rem;">
                            <input type="checkbox" id="toggleAllMembers">
                            <span class="checkbox-indicator"></span> Select All
                        </label>
                    </div>

                    <div class="form-group" style="margin-bottom: 0.75rem;">
                        <input type="text" id="attendanceMemberSearch" placeholder="🔍 Type name to filter attendance list..." class="search-input" style="height: auto; padding: 0.5rem 0.75rem; font-size: 0.85rem; border-color: var(--border-color);">
                    </div>

                    <div class="attendance-list-container" style="margin-bottom: 1.5rem; max-height: 15.625rem;">
                        <?php foreach ($all_members as $m): ?>
                            <div class="attendance-member-row">
                                <label class="checkbox-custom">
                                    <input type="checkbox" class="member-checkbox" name="present_members[]" value="<?= $m['id'] ?>">
                                    <span class="checkbox-indicator"></span>
                                    <span style="font-size: 0.9rem; font-weight: 500;"><?= htmlspecialchars($m['name']) ?></span>
                                </label>
                                <span style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($m['gender']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">Save Session & Take Attendance</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- View Details Drawer/Modal -->
<?php if ($selected_session): ?>
    <div class="modal-overlay active" id="detailsModal">
        <div class="modal-container" style="max-width: 31.25rem;">
            <div class="modal-header">
                <div>
                    <h2 class="panel-title"><?= htmlspecialchars($selected_session['title']) ?></h2>
                    <p style="font-size: 0.8rem; color: var(--text-muted);"><?= date('l, M d, Y', strtotime($selected_session['date'])) ?></p>
                </div>
                <a href="attendance" class="modal-close" style="text-decoration: none;">&times;</a>
            </div>
            
            <div class="attendance-list-container" style="max-height: 25rem; border-color: var(--border-color);">
                <?php foreach ($selected_attendance_details as $detail): ?>
                    <div class="attendance-member-row">
                        <div>
                            <strong><?= htmlspecialchars($detail['name']) ?></strong>
                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($detail['dept_name'] ?? 'No Department') ?></div>
                        </div>
                        <div>
                            <?php if ($detail['status'] === 'Present'): ?>
                                <span class="badge badge-success">Present</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Absent</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="modal-footer">
                <a href="attendance" class="btn btn-secondary">Close</a>
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
