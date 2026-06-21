<?php
// workforce.php - Department & Workforce Management
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/layout.php';

$error = '';
$success = '';

// Handle Actions (Edit Department Inline)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_GET['action'])) {
        $action = $_GET['action'];
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            $error = 'Department Name is required.';
        } else {
            if ($action === 'edit') {
                $id = intval($_POST['id'] ?? 0);
                if ($id <= 0) {
                    $error = 'Invalid Department ID.';
                } else {
                    try {
                        $stmt = $pdo->prepare("UPDATE departments SET name = ?, description = ? WHERE id = ?");
                        $stmt->execute([$name, $description, $id]);
                        header("Location: workforce?msg=Department updated successfully");
                        exit;
                    } catch (PDOException $e) {
                        $error = 'Error updating department: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

if (isset($_GET['msg'])) {
    $success = $_GET['msg'];
}

// Fetch all departments with member counts
$depts = $pdo->query("
    SELECT d.*, COUNT(m.id) as member_count 
    FROM departments d
    LEFT JOIN members m ON d.id = m.department_id
    GROUP BY d.id
    ORDER BY d.name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch members grouped by department
$dept_members = [];
foreach ($depts as $dept) {
    $stmt = $pdo->prepare("SELECT id, name, phone, email, gender FROM members WHERE department_id = ? ORDER BY name ASC");
    $stmt->execute([$dept['id']]);
    $dept_members[$dept['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

render_header('Workforce Departments', 'workforce');
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

<!-- Quick Actions Panel -->
<div class="card-panel" style="margin-bottom: 2.5rem; display: flex; justify-content: space-between; align-items: center; padding: 1.25rem;">
    <div>
        <h2 class="panel-title" style="margin-bottom: 0.25rem;">Workforce Structure</h2>
        <p style="font-size: 0.85rem; color: var(--text-secondary);">Manage department cohorts and coordinate active workers. Access <a href="settings?tab=departments" style="color: var(--primary); text-decoration: underline;">Settings page</a> to create or delete departments.</p>
    </div>
</div>

<!-- Departments Grid Layout -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(20rem, 1fr)); align-items: start; gap: 2rem;">
    <?php foreach ($depts as $dept): ?>
        <div class="card-panel" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; height: 100%;">
            
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h3 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 700; color: white;">
                        <?= htmlspecialchars($dept['name']) ?>
                    </h3>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
                        <?= htmlspecialchars($dept['description'] ?: 'No description provided.') ?>
                    </p>
                </div>
                <span class="badge badge-success"><?= $dept['member_count'] ?> Active</span>
            </div>

            <!-- Members list inside department -->
            <div style="flex-grow: 1; border-top: 0.0625rem solid var(--border-color); padding-top: 0.75rem;">
                <h4 style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.03125rem; color: var(--text-secondary); margin-bottom: 0.75rem;">Members Assigned</h4>
                
                <div class="activity-list" style="max-height: 12.5rem; overflow-y: auto;">
                    <?php if (empty($dept_members[$dept['id']])): ?>
                        <p style="color: var(--text-muted); font-size: 0.8rem; text-align: center; padding: 1.5rem 0;">No members assigned to this department.</p>
                    <?php else: ?>
                        <?php foreach ($dept_members[$dept['id']] as $m): 
                            $initials = strtoupper(substr($m['name'], 0, 1));
                        ?>
                            <div class="activity-item" style="padding: 0.5rem; gap: 0.5rem; background: transparent;">
                                <div class="activity-avatar" style="width: 1.75rem; height: 1.75rem; font-size: 0.8rem;"><?= htmlspecialchars($initials) ?></div>
                                <div class="activity-info">
                                    <div class="activity-name" style="font-size: 0.85rem;"><?= htmlspecialchars($m['name']) ?></div>
                                    <div class="activity-meta" style="font-size: 0.7rem;"><?= htmlspecialchars($m['phone']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actions footer -->
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; border-top: 0.0625rem solid var(--border-color); padding-top: 0.75rem; margin-top: auto;">
                <button class="btn btn-secondary" style="padding: 0.35rem 0.7rem; font-size: 0.8rem;" onclick='openEditDeptModal(<?= json_encode($dept) ?>)'>Rename / Edit</button>
            </div>

        </div>
    <?php endforeach; ?>
</div>

<!-- Edit Department Modal Overlay -->
<div class="modal-overlay" id="deptModal">
    <div class="modal-container" style="max-width: 28.125rem;">
        <div class="modal-header">
            <h2 class="panel-title" id="modalTitle">Edit Department Info</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form id="deptForm" method="POST" action="workforce?action=edit">
            <input type="hidden" name="id" id="deptId">
            
            <div class="modal-body">
                <div class="form-grid" style="grid-template-columns: 1fr;">
                    <div class="form-group">
                        <label for="formDeptName">Department Name</label>
                        <input type="text" name="name" id="formDeptName" required placeholder="e.g. Media & Tech Team">
                    </div>
                    
                    <div class="form-group">
                        <label for="formDeptDesc">Description</label>
                        <textarea name="description" id="formDeptDesc" rows="3" placeholder="Describe the roles and functions of this department..."></textarea>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-success" id="formSubmitBtn">Update Department</button>
            </div>
        </form>
    </div>
</div>

<script>
const modal = document.getElementById('deptModal');
const form = document.getElementById('deptForm');
const modalTitle = document.getElementById('modalTitle');
const formSubmitBtn = document.getElementById('formSubmitBtn');

function openEditDeptModal(dept) {
    form.reset();
    form.action = 'workforce?action=edit';
    modalTitle.textContent = 'Edit Department Info';
    formSubmitBtn.textContent = 'Update Department';
    
    document.getElementById('deptId').value = dept.id;
    document.getElementById('formDeptName').value = dept.name;
    document.getElementById('formDeptDesc').value = dept.description || '';
    
    modal.classList.add('active');
}

function closeModal() {
    modal.classList.remove('active');
}

// Close on escape key
window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeModal();
});
</script>

<?php
render_footer();
?>
