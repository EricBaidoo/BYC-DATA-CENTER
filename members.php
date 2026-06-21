<?php
// members.php - Membership Directory & Management
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/layout.php';

// Handle Actions (Add, Edit, Delete)
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_GET['action'])) {
        $action = $_GET['action'];
        
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $birthday = trim($_POST['birthday'] ?? null);
        $gender = trim($_POST['gender'] ?? 'Male');
        $join_date = trim($_POST['join_date'] ?? date('Y-m-d'));
        $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;
        
        if (empty($name)) {
            $error = 'Name field is required.';
        } else {
            if ($action === 'add') {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO members (name, phone, email, address, birthday, gender, join_date, department_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$name, $phone, $email, $address, $birthday, $gender, $join_date, $department_id]);
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
                            SET name = ?, phone = ?, email = ?, address = ?, birthday = ?, gender = ?, join_date = ?, department_id = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$name, $phone, $email, $address, $birthday, $gender, $join_date, $department_id, $id]);
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

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $stmt = $pdo->prepare("DELETE FROM members WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: members?msg=Member deleted successfully");
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

if (!empty($search)) {
    $query_parts[] = "(m.name LIKE ? OR m.phone LIKE ? OR m.email LIKE ?)";
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
    SELECT m.*, d.name as dept_name,
           (
               SELECT MAX(a.date) 
               FROM member_attendance ma
               JOIN attendance a ON ma.attendance_id = a.id
               WHERE ma.member_id = m.id AND ma.status = 'Present'
           ) as last_attended
    FROM members m
    LEFT JOIN departments d ON m.department_id = d.id
    $where_clause
    ORDER BY m.name ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch departments for dropdowns
$departments = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

render_header('Membership Directory', 'members');
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

<!-- Filters Toolbar -->
<div class="card-panel" style="margin-bottom: 2rem; padding: 1.25rem;">
    <form method="GET" action="members" class="toolbar">
        <div class="search-input-wrapper">
            <svg class="search-icon" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <input type="text" name="search" class="search-input" placeholder="Search by name, phone or email..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="filter-group">
            <div class="form-group">
                <select name="dept_id" onchange="this.form.submit()">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= $dept_filter == $dept['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <select name="status" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="committed" <?= $status_filter === 'committed' ? 'selected' : '' ?>>Committed Members</option>
                    <option value="non_committed" <?= $status_filter === 'non_committed' ? 'selected' : '' ?>>Non-Committed (Absent 6m+)</option>
                    <option value="workforce" <?= $status_filter === 'workforce' ? 'selected' : '' ?>>Workforce</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">Filter</button>
            <button type="button" class="btn btn-success" onclick="openAddModal()">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                Add Member
            </button>
        </div>
    </form>
</div>

<!-- Members Table -->
<div class="card-panel table-container">
    <?php if (empty($members)): ?>
        <p style="color: var(--text-muted); text-align: center; padding: 3rem 0;">No members match the search criteria.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th class="hide-mobile">Gender</th>
                    <th>Contact Info</th>
                    <th>Department</th>
                    <th class="hide-mobile">Join Date</th>
                    <th class="hide-mobile">Last Attended</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $member): 
                    // Calculate Status badge
                    $is_workforce = ($member['department_id'] !== null && $member['dept_name'] !== 'None');
                    
                    // Committed vs Non-Committed check
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
                        $status_badge = '<span class="badge badge-success">Committed</span>';
                    } else {
                        $status_badge = '<span class="badge badge-danger">Non-Committed</span>';
                    }
                    
                    if ($is_workforce) {
                        $status_badge .= ' <span class="badge badge-warning">Workforce</span>';
                    }
                ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($member['name']) ?></strong>
                            <div class="hide-mobile" style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($member['address']) ?></div>
                        </td>
                        <td class="hide-mobile"><?= htmlspecialchars($member['gender']) ?></td>
                        <td>
                            <div><?= htmlspecialchars($member['phone']) ?></div>
                            <div class="hide-mobile" style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($member['email']) ?></div>
                        </td>
                        <td>
                            <span style="font-weight: 500;"><?= htmlspecialchars($member['dept_name'] ?? 'None') ?></span>
                        </td>
                        <td class="hide-mobile"><?= date('M d, Y', strtotime($member['join_date'])) ?></td>
                        <td class="hide-mobile">
                            <?= !empty($member['last_attended']) ? date('M d, Y', strtotime($member['last_attended'])) : '<span style="color: var(--text-muted);">Never</span>' ?>
                        </td>
                        <td><?= $status_badge ?></td>
                        <td style="text-align: right;">
                            <button class="btn btn-secondary" style="padding: 0.35rem 0.7rem; font-size: 0.8rem;" onclick='openEditModal(<?= json_encode($member) ?>)'>Edit</button>
                            <a href="members?action=delete&id=<?= $member['id'] ?>" class="btn btn-secondary" style="padding: 0.35rem 0.7rem; font-size: 0.8rem; color: var(--danger); border-color: rgba(239, 68, 68, 0.2);" onclick="return confirm('Are you sure you want to delete this member?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Add/Edit Member Modal Overlay -->
<div class="modal-overlay" id="memberModal">
    <div class="modal-container">
        <div class="modal-header">
            <h2 class="panel-title" id="modalTitle">Register New Member</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form id="memberForm" method="POST" action="members?action=add">
            <input type="hidden" name="id" id="memberId">
            
            <div class="form-grid">
                <div class="form-group full-width">
                    <label for="formName">Full Name</label>
                    <input type="text" name="name" id="formName" required placeholder="e.g. John Doe">
                </div>
                
                <div class="form-group">
                    <label for="formGender">Gender</label>
                    <select name="gender" id="formGender">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="formPhone">Phone Number</label>
                    <input type="text" name="phone" id="formPhone" placeholder="e.g. 0244123456">
                </div>
                
                <div class="form-group">
                    <label for="formEmail">Email Address</label>
                    <input type="email" name="email" id="formEmail" placeholder="e.g. john@byc.org">
                </div>
                
                <div class="form-group">
                    <label for="formBirthday">Birthday</label>
                    <input type="date" name="birthday" id="formBirthday">
                </div>
                
                <div class="form-group">
                    <label for="formJoinDate">Join Date</label>
                    <input type="date" name="join_date" id="formJoinDate" value="<?= date('Y-m-d') ?>">
                </div>

                <div class="form-group">
                    <label for="formDept">Department Assignment</label>
                    <select name="department_id" id="formDept">
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="formAddress">Residential Address</label>
                    <textarea name="address" id="formAddress" rows="2" placeholder="e.g. House No. 4, Airport West, Accra"></textarea>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-success" id="formSubmitBtn">Save Member</button>
            </div>
        </form>
    </div>
</div>

<script>
const modal = document.getElementById('memberModal');
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
    
    modal.classList.add('active');
}

function openEditModal(member) {
    form.reset();
    form.action = 'members?action=edit';
    modalTitle.textContent = 'Edit Member Profile';
    formSubmitBtn.textContent = 'Update Member';
    
    document.getElementById('memberId').value = member.id;
    document.getElementById('formName').value = member.name;
    document.getElementById('formGender').value = member.gender;
    document.getElementById('formPhone').value = member.phone || '';
    document.getElementById('formEmail').value = member.email || '';
    document.getElementById('formBirthday').value = member.birthday || '';
    document.getElementById('formJoinDate').value = member.join_date || '';
    document.getElementById('formDept').value = member.department_id || '';
    document.getElementById('formAddress').value = member.address || '';
    
    modal.classList.add('active');
}

function closeModal() {
    modal.classList.remove('active');
}

// Live client-side search filtering
const searchInput = document.querySelector('.search-input');
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
                emptyRow.innerHTML = `<td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem;">No matching members found in this view.</td>`;
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
