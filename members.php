<?php
// members.php - Membership Directory & Management
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/layout.php';

// Handle Actions (Add, Edit, Delete)
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_GET['action'])) {
        validate_csrf();
        $action = $_GET['action'];
        
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $birthday = trim($_POST['birthday'] ?? null);
        $gender = trim($_POST['gender'] ?? 'Male');
        $join_date = trim($_POST['join_date'] ?? date('Y-m-d'));
        $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;
        
        // Shepherding fields
        $member_status = trim($_POST['member_status'] ?? 'Member');
        $water_baptized = isset($_POST['water_baptized']) ? 1 : 0;
        $holy_ghost_baptized = isset($_POST['holy_ghost_baptized']) ? 1 : 0;
        $discipleship_completed = isset($_POST['discipleship_completed']) ? 1 : 0;
        $household_id = !empty($_POST['household_id']) ? intval($_POST['household_id']) : null;
        $home_cell_id = !empty($_POST['home_cell_id']) ? intval($_POST['home_cell_id']) : null;
        
        if (empty($first_name)) {
            $error = 'First name field is required.';
        } else {
            if ($action === 'add') {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO members (first_name, last_name, phone, email, address, birthday, gender, join_date, department_id, member_status, water_baptized, holy_ghost_baptized, discipleship_completed, household_id, home_cell_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$first_name, $last_name, $phone, $email, $address, $birthday, $gender, $join_date, $department_id, $member_status, $water_baptized, $holy_ghost_baptized, $discipleship_completed, $household_id, $home_cell_id]);
                    $new_id = $pdo->lastInsertId();
                    
                    log_audit_action('ADD_MEMBER', 'members', $new_id, json_encode([
                        'name' => "$first_name $last_name",
                        'status' => $member_status
                    ]));
                    
                    header("Location: members?msg=Member added successfully");
                    exit;
                } catch (PDOException $e) {
                    $error = 'Error adding member: ' . $e->getMessage();
                }
            } elseif ($action === 'edit') {
                $id = intval($_POST['id'] ?? 0);
                if ($id <= 0) {
                    $error = 'Invalid member ID.';
                } else {
                    try {
                        $stmt = $pdo->prepare("
                            UPDATE members 
                            SET first_name = ?, last_name = ?, phone = ?, email = ?, address = ?, birthday = ?, gender = ?, join_date = ?, department_id = ?, member_status = ?, water_baptized = ?, holy_ghost_baptized = ?, discipleship_completed = ?, household_id = ?, home_cell_id = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$first_name, $last_name, $phone, $email, $address, $birthday, $gender, $join_date, $department_id, $member_status, $water_baptized, $holy_ghost_baptized, $discipleship_completed, $household_id, $home_cell_id, $id]);
                        
                        log_audit_action('EDIT_MEMBER', 'members', $id, json_encode([
                            'name' => "$first_name $last_name",
                            'status' => $member_status
                        ]));
                        
                        header("Location: members?msg=Member updated successfully");
                        exit;
                    } catch (PDOException $e) {
                        $error = 'Error updating member: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

// Handle Delete (Soft Delete)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    validate_csrf();
    $id = intval($_GET['id']);
    try {
        $stmt = $pdo->prepare("UPDATE members SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$id]);
        
        log_audit_action('SOFT_DELETE_MEMBER', 'members', $id);
        
        header("Location: members?msg=Member record deleted successfully");
        exit;
    } catch (PDOException $e) {
        $error = 'Error deleting member: ' . $e->getMessage();
    }
}

if (isset($_GET['msg'])) {
    $success = $_GET['msg'];
}

// Search, Filters & Pagination Settings
$search = trim($_GET['search'] ?? '');
$dept_filter = trim($_GET['dept_id'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

// Build query
$query_parts = [];
$params = [];

// Exclude soft-deleted members by default
$query_parts[] = "m.deleted_at IS NULL";

if (!empty($search)) {
    $query_parts[] = "(m.first_name LIKE ? OR m.last_name LIKE ? OR m.phone LIKE ? OR m.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($dept_filter)) {
    $query_parts[] = "m.department_id = ?";
    $params[] = intval($dept_filter);
}

if (!empty($status_filter)) {
    if ($status_filter === 'committed') {
        $query_parts[] = "(EXISTS (
            SELECT 1 FROM member_attendance ma
            JOIN attendance a ON ma.attendance_id = a.id
            WHERE ma.member_id = m.id 
              AND ma.status = 'Present' 
              AND a.date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        ) OR m.join_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH))";
    } elseif ($status_filter === 'non_committed') {
        $query_parts[] = "NOT EXISTS (
            SELECT 1 FROM member_attendance ma
            JOIN attendance a ON ma.attendance_id = a.id
            WHERE ma.member_id = m.id 
              AND ma.status = 'Present' 
              AND a.date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        ) AND (
            EXISTS (SELECT 1 FROM member_attendance ma WHERE ma.member_id = m.id)
            OR m.join_date < DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        )";
    } elseif ($status_filter === 'workforce') {
        $query_parts[] = "m.department_id IS NOT NULL AND m.department_id != (SELECT id FROM departments WHERE name = 'None')";
    }
}

$where_clause = '';
if (!empty($query_parts)) {
    $where_clause = "WHERE " . implode(" AND ", $query_parts);
}

// Fetch members with computed attendance status
$sql = "
    SELECT m.*, CONCAT(m.first_name, ' ', COALESCE(m.last_name, '')) as name, d.name as dept_name,
           (
               SELECT MAX(a.date) 
               FROM member_attendance ma
               JOIN attendance a ON ma.attendance_id = a.id
               WHERE ma.member_id = m.id AND ma.status = 'Present'
           ) as last_attended
    FROM members m
    LEFT JOIN departments d ON m.department_id = d.id
    $where_clause
    ORDER BY m.first_name ASC, m.last_name ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch dropdown options
$departments = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$households = $pdo->query("SELECT * FROM households ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$home_cells = $pdo->query("SELECT * FROM home_cells ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

render_header('Membership Directory', 'members');
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

<!-- Filters Toolbar -->
<div class="bg-bg-surface backdrop-blur-md border border-border-custom rounded-xl p-5 mb-6">
    <form method="GET" action="members" class="flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-4">
        <div class="relative w-full lg:max-w-xs flex-grow">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <input type="text" name="search" class="w-full bg-black/30 border border-white/10 rounded-md pl-10 pr-4 py-2 text-white placeholder-text-muted focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm" placeholder="Search by name, phone..." value="<?= htmlspecialchars($search) ?>">
        </div>
        
        <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center w-full lg:w-auto">
            <select name="dept_id" onchange="this.form.submit()" class="bg-bg-surface-solid border border-border-custom rounded-md px-3.5 py-2 text-white placeholder-text-muted focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-opacity-80 focus:outline-none transition-all duration-200 text-sm">
                <option value="">All Departments</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?= $dept['id'] ?>" <?= $dept_filter == $dept['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dept['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="status" onchange="this.form.submit()" class="bg-bg-surface-solid border border-border-custom rounded-md px-3.5 py-2 text-white placeholder-text-muted focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-opacity-80 focus:outline-none transition-all duration-200 text-sm">
                <option value="">All Statuses</option>
                <option value="committed" <?= $status_filter === 'committed' ? 'selected' : '' ?>>Committed Members</option>
                <option value="non_committed" <?= $status_filter === 'non_committed' ? 'selected' : '' ?>>Non-Committed (Absent 6m+)</option>
                <option value="workforce" <?= $status_filter === 'workforce' ? 'selected' : '' ?>>Workforce</option>
            </select>

            <button type="submit" class="bg-bg-surface-solid text-text-primary border border-border-custom hover:bg-border-custom-hover hover:text-white font-semibold text-sm px-4 py-2 rounded transition-all duration-150 cursor-pointer">Filter</button>
            <button type="button" class="bg-success hover:bg-opacity-90 text-white font-semibold text-sm px-4 py-2 rounded shadow-md active:scale-95 transition-all duration-150 cursor-pointer flex items-center justify-center gap-1.5" onclick="openAddModal()">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                Add Member
            </button>
        </div>
    </form>
</div>

<!-- Members Table Container -->
<div class="bg-bg-surface backdrop-blur-md border border-border-custom rounded-xl p-6 sm:p-7 overflow-x-auto w-full max-w-full">
    <?php if (empty($members)): ?>
        <p class="text-text-muted text-center py-12">No members match the search criteria.</p>
    <?php else: ?>
        <table class="w-full border-collapse text-left min-w-[960px]">
            <thead>
                <tr class="border-b border-border-custom">
                    <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Name</th>
                    <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider hide-mobile">Gender</th>
                    <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Contact Info</th>
                    <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Department</th>
                    <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider hide-mobile">Join Date</th>
                    <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider hide-mobile">Last Attended</th>
                    <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Status & Discipleship</th>
                    <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $member): 
                    // Calculate Status badges
                    $is_workforce = ($member['department_id'] !== null && $member['dept_name'] !== 'None');
                    
                    $six_months_ago = date('Y-m-d', strtotime('-6 months'));
                    $is_new = ($member['join_date'] >= $six_months_ago);
                    
                    $is_committed = false;
                    if ($is_new) {
                        $is_committed = true;
                    } elseif (!empty($member['last_attended']) && $member['last_attended'] >= $six_months_ago) {
                        $is_committed = true;
                    }
                    
                    $status_badge = '';
                    if ($is_committed) {
                        $status_badge = '<span class="border text-3xs px-2 py-0.5 rounded font-semibold bg-secondary/10 border-secondary/35 text-secondary">Committed</span>';
                    } else {
                        $status_badge = '<span class="border text-3xs px-2 py-0.5 rounded font-semibold bg-danger/10 border-danger/35 text-danger">Non-Committed</span>';
                    }
                    
                    if ($is_workforce) {
                        $status_badge .= ' <span class="border text-3xs px-2 py-0.5 rounded font-semibold bg-warning/10 border-warning/35 text-warning">Workforce</span>';
                    }
                    
                    // Shepherding status color
                    $m_status = $member['member_status'] ?? 'Member';
                    $status_color = $m_status === 'Visitor' ? 'bg-purple-400/10 border-purple-400/35 text-purple-400' : 
                                   ($m_status === 'Adherent' ? 'bg-teal-400/10 border-teal-400/35 text-teal-400' : 
                                    'bg-blue-400/10 border-blue-400/35 text-blue-400');
                    
                    // Discipleship checklist output
                    $discipleship = [];
                    if ($member['water_baptized']) $discipleship[] = 'Water';
                    if ($member['holy_ghost_baptized']) $discipleship[] = 'Holy Ghost';
                    if ($member['discipleship_completed']) $discipleship[] = 'Grad';
                    $disc_str = !empty($discipleship) ? implode(', ', $discipleship) : 'None';
                ?>
                    <tr class="border-b border-white/[0.02] hover:bg-white/[0.01] transition-all">
                        <td class="p-3">
                            <strong class="text-sm text-white block leading-snug"><?= htmlspecialchars($member['name']) ?></strong>
                            <div class="text-2xs text-text-muted mt-0.5 hide-mobile"><?= htmlspecialchars($member['address'] ?: 'No address logged') ?></div>
                        </td>
                        <td class="p-3 text-sm text-text-primary hide-mobile"><?= htmlspecialchars($member['gender']) ?></td>
                        <td class="p-3 text-sm text-text-primary">
                            <div><?= htmlspecialchars($member['phone'] ?: '—') ?></div>
                            <div class="text-2xs text-text-muted hide-mobile"><?= htmlspecialchars($member['email'] ?: '') ?></div>
                        </td>
                        <td class="p-3 text-sm text-white font-medium">
                            <?= htmlspecialchars($member['dept_name'] ?? 'None') ?>
                        </td>
                        <td class="p-3 text-sm text-text-primary hide-mobile"><?= date('M d, Y', strtotime($member['join_date'])) ?></td>
                        <td class="p-3 text-sm text-text-primary hide-mobile">
                            <?= !empty($member['last_attended']) ? date('M d, Y', strtotime($member['last_attended'])) : '<span class="text-text-muted">Never</span>' ?>
                        </td>
                        <td class="p-3">
                            <div class="flex flex-col gap-1.5 items-start">
                                <div class="flex flex-wrap gap-1">
                                    <?= $status_badge ?>
                                    <span class="border text-3xs px-2 py-0.5 rounded font-semibold <?= $status_color ?>"><?= htmlspecialchars($m_status) ?></span>
                                </div>
                                <div class="text-3xs text-text-muted font-medium">
                                    Discipleship: <span class="text-secondary font-semibold"><?= $disc_str ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="p-3 text-right">
                            <div class="inline-flex gap-1.5">
                                <button class="bg-bg-surface-solid text-text-primary border border-border-custom hover:bg-border-custom-hover hover:text-white font-semibold text-xs px-2.5 py-1.5 rounded transition-all duration-150 cursor-pointer" onclick='openEditModal(<?= json_encode($member) ?>)'>Edit</button>
                                <a href="members?action=delete&id=<?= $member['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="bg-bg-surface-solid border border-border-custom hover:border-danger/30 hover:bg-danger/10 text-text-secondary hover:text-danger font-semibold text-xs px-2.5 py-1.5 rounded transition-all duration-150 text-center" onclick="return confirm('Are you sure you want to delete this member?')" style="text-decoration: none;">Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Add/Edit Member Modal Overlay -->
<div class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[1000] opacity-0 pointer-events-none transition-all duration-300" id="memberModal">
    <div class="bg-bg-surface-solid border border-border-custom rounded-none sm:rounded-2xl w-full max-w-full sm:max-w-[640px] h-full sm:h-auto max-h-full sm:max-h-[90vh] p-6 shadow-2xl flex flex-col scale-95 opacity-0 transition-all duration-300" id="memberModalContainer">
        <div class="flex justify-between items-center mb-5 flex-shrink-0">
            <h2 class="font-heading font-bold text-lg text-white" id="modalTitle">Register New Member</h2>
            <button class="text-2xl text-text-muted hover:text-white bg-none border-none p-0 cursor-pointer leading-none" onclick="closeModal()">&times;</button>
        </div>
        <form id="memberForm" method="POST" action="members?action=add" class="flex flex-col flex-grow overflow-hidden">
            <?php render_csrf_input(); ?>
            <input type="hidden" name="id" id="memberId">
            
            <div class="overflow-y-auto flex-grow pr-1 flex flex-col gap-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label for="formFirstName" class="text-xs font-semibold text-text-secondary">First Name</label>
                        <input type="text" name="first_name" id="formFirstName" required placeholder="e.g. John" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white placeholder-text-muted focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="formLastName" class="text-xs font-semibold text-text-secondary">Last Name</label>
                        <input type="text" name="last_name" id="formLastName" placeholder="e.g. Doe" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white placeholder-text-muted focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label for="formGender" class="text-xs font-semibold text-text-secondary">Gender</label>
                        <select name="gender" id="formGender" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="formPhone" class="text-xs font-semibold text-text-secondary">Phone Number</label>
                        <input type="text" name="phone" id="formPhone" placeholder="e.g. 0244123456" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white placeholder-text-muted focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label for="formEmail" class="text-xs font-semibold text-text-secondary">Email Address</label>
                        <input type="email" name="email" id="formEmail" placeholder="e.g. john@byc.org" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white placeholder-text-muted focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="formBirthday" class="text-xs font-semibold text-text-secondary">Birthday</label>
                        <input type="date" name="birthday" id="formBirthday" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label for="formJoinDate" class="text-xs font-semibold text-text-secondary">Join Date</label>
                        <input type="date" name="join_date" id="formJoinDate" value="<?= date('Y-m-d') ?>" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="formDept" class="text-xs font-semibold text-text-secondary">Department Assignment</label>
                        <select name="department_id" id="formDept" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label for="formMemberStatus" class="text-xs font-semibold text-text-secondary">Ministry Status</label>
                        <select name="member_status" id="formMemberStatus" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                            <option value="Member">Member</option>
                            <option value="Visitor">Visitor</option>
                            <option value="Adherent">Adherent</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="formHousehold" class="text-xs font-semibold text-text-secondary">Household Cohort</label>
                        <select name="household_id" id="formHousehold" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                            <option value="">-- No Household --</option>
                            <?php foreach ($households as $house): ?>
                                <option value="<?= $house['id'] ?>"><?= htmlspecialchars($house['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="formHomeCell" class="text-xs font-semibold text-text-secondary">Home Cell Fellowship</label>
                    <select name="home_cell_id" id="formHomeCell" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                        <option value="">-- No Home Cell --</option>
                        <?php foreach ($home_cells as $cell): ?>
                            <option value="<?= $cell['id'] ?>"><?= htmlspecialchars($cell['name']) ?> (<?= htmlspecialchars($cell['location']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex flex-wrap gap-4 sm:gap-6 py-1 select-none">
                    <label class="flex items-center gap-2 cursor-pointer text-xs sm:text-sm text-white font-medium">
                        <input type="checkbox" name="water_baptized" id="formWaterBaptized" value="1" class="w-4 h-4 rounded border-white/10 bg-black/30 text-primary checked:bg-primary checked:border-primary focus:ring-offset-0 focus:ring-2 focus:ring-primary/30 transition-all cursor-pointer">
                        <span>Water Baptized</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-xs sm:text-sm text-white font-medium">
                        <input type="checkbox" name="holy_ghost_baptized" id="formHolyGhostBaptized" value="1" class="w-4 h-4 rounded border-white/10 bg-black/30 text-primary checked:bg-primary checked:border-primary focus:ring-offset-0 focus:ring-2 focus:ring-primary/30 transition-all cursor-pointer">
                        <span>Holy Ghost Baptized</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-xs sm:text-sm text-white font-medium">
                        <input type="checkbox" name="discipleship_completed" id="formDiscipleshipCompleted" value="1" class="w-4 h-4 rounded border-white/10 bg-black/30 text-primary checked:bg-primary checked:border-primary focus:ring-offset-0 focus:ring-2 focus:ring-primary/30 transition-all cursor-pointer">
                        <span>Discipleship Graduate</span>
                    </label>
                </div>

                <div class="flex flex-col gap-1.5 mb-2">
                    <label for="formAddress" class="text-xs font-semibold text-text-secondary">Residential Address</label>
                    <textarea name="address" id="formAddress" rows="2" placeholder="e.g. House No. 4, Airport West, Accra" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white placeholder-text-muted focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm"></textarea>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 border-t border-border-custom pt-4 flex-shrink-0">
                <button type="button" class="bg-bg-surface-solid text-text-primary border border-border-custom hover:bg-border-custom-hover hover:text-white font-semibold text-xs px-4 py-2 rounded transition-all duration-150 cursor-pointer" onclick="closeModal()">Cancel</button>
                <button type="submit" class="bg-success text-white font-semibold text-xs px-4 py-2 rounded hover:bg-opacity-90 transition-all duration-150 cursor-pointer" id="formSubmitBtn">Save Member</button>
            </div>
        </form>
    </div>
</div>

<script>
const modal = document.getElementById('memberModal');
const container = document.getElementById('memberModalContainer');
const form = document.getElementById('memberForm');
const modalTitle = document.getElementById('modalTitle');
const formSubmitBtn = document.getElementById('formSubmitBtn');

function openAddModal() {
    form.reset();
    form.action = 'members?action=add';
    modalTitle.textContent = 'Register New Member';
    formSubmitBtn.textContent = 'Save Member';
    document.getElementById('memberId').value = '';
    
    // Set default join date to today
    document.getElementById('formJoinDate').value = new Date().toISOString().substring(0, 10);
    
    // Set default select states
    document.getElementById('formMemberStatus').value = 'Member';
    document.getElementById('formHousehold').value = '';
    document.getElementById('formHomeCell').value = '';
    
    // Checkboxes
    document.getElementById('formWaterBaptized').checked = false;
    document.getElementById('formHolyGhostBaptized').checked = false;
    document.getElementById('formDiscipleshipCompleted').checked = false;
    
    modal.classList.add('opacity-100', 'pointer-events-auto');
    container.classList.add('scale-100', 'opacity-100');
}

function openEditModal(member) {
    form.reset();
    form.action = 'members?action=edit';
    modalTitle.textContent = 'Edit Member Profile';
    formSubmitBtn.textContent = 'Update Member';
    
    document.getElementById('memberId').value = member.id;
    document.getElementById('formFirstName').value = member.first_name || '';
    document.getElementById('formLastName').value = member.last_name || '';
    document.getElementById('formGender').value = member.gender;
    document.getElementById('formPhone').value = member.phone || '';
    document.getElementById('formEmail').value = member.email || '';
    document.getElementById('formBirthday').value = member.birthday || '';
    document.getElementById('formJoinDate').value = member.join_date || '';
    document.getElementById('formDept').value = member.department_id || '';
    document.getElementById('formAddress').value = member.address || '';
    
    // Shepherding states
    document.getElementById('formMemberStatus').value = member.member_status || 'Member';
    document.getElementById('formHousehold').value = member.household_id || '';
    document.getElementById('formHomeCell').value = member.home_cell_id || '';
    
    // Checkboxes
    document.getElementById('formWaterBaptized').checked = (member.water_baptized == 1);
    document.getElementById('formHolyGhostBaptized').checked = (member.holy_ghost_baptized == 1);
    document.getElementById('formDiscipleshipCompleted').checked = (member.discipleship_completed == 1);
    
    modal.classList.add('opacity-100', 'pointer-events-auto');
    container.classList.add('scale-100', 'opacity-100');
}

function closeModal() {
    modal.classList.remove('opacity-100', 'pointer-events-auto');
    container.classList.remove('scale-100', 'opacity-100');
}

// Live client-side search filtering
const searchInput = document.querySelector('input[name="search"]');
if (searchInput) {
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase().trim();
        const rows = document.querySelectorAll('table tbody tr:not(#noMatchesRow)');
        let matches = 0;
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(query)) {
                row.style.display = '';
                matches++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Show indicator if no matches
        let emptyRow = document.getElementById('noMatchesRow');
        if (matches === 0 && rows.length > 0) {
            if (!emptyRow) {
                emptyRow = document.createElement('tr');
                emptyRow.id = 'noMatchesRow';
                emptyRow.innerHTML = `<td colspan="8" class="text-center text-text-muted py-8 text-sm font-medium">No matching members found in this view.</td>`;
                document.querySelector('table tbody').appendChild(emptyRow);
            }
        } else if (emptyRow) {
            emptyRow.remove();
        }
    });
}

// Close on escape key
window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeModal();
});
</script>

<?php
render_footer();
?>
