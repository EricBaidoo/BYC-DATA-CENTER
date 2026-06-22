<?php
// workforce.php - Department & Workforce Management
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/layout.php';

$error = '';
$success = '';

// Handle Actions (Edit Department Inline)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_GET['action'])) {
        validate_csrf();
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
                        
                        log_audit_action('EDIT_DEPARTMENT', 'departments', $id, json_encode([
                            'name' => $name
                        ]));

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
    LEFT JOIN members m ON d.id = m.department_id AND m.deleted_at IS NULL
    GROUP BY d.id
    ORDER BY d.name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch members grouped by department
$dept_members = [];
foreach ($depts as $dept) {
    $stmt = $pdo->prepare("SELECT id, CONCAT(first_name, ' ', COALESCE(last_name, '')) as name, phone, email, gender FROM members WHERE department_id = ? AND deleted_at IS NULL ORDER BY first_name ASC, last_name ASC");
    $stmt->execute([$dept['id']]);
    $dept_members[$dept['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

render_header('Workforce Departments', 'workforce');
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

<!-- Quick Actions Panel -->
<div class="bg-bg-surface backdrop-blur-md border border-border-custom rounded-xl p-5 mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h2 class="font-heading font-bold text-lg text-white">Workforce Structure</h2>
        <p class="text-xs sm:text-sm text-text-secondary mt-0.5">Manage department cohorts and coordinate active workers. Access the <a href="settings?tab=departments" class="text-primary hover:underline font-semibold">Settings page</a> to create or delete departments.</p>
    </div>
</div>

<!-- Departments Grid Layout -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
    <?php foreach ($depts as $dept): ?>
        <div class="bg-bg-surface backdrop-blur-md border border-border-custom rounded-xl p-6 flex flex-col gap-4 h-full hover:border-primary/20 transition-all duration-300">
            
            <div class="flex justify-between items-start gap-3">
                <div class="min-w-0">
                    <h3 class="font-heading font-bold text-base sm:text-lg text-white truncate">
                        <?= htmlspecialchars($dept['name']) ?>
                    </h3>
                    <p class="text-xs text-text-secondary mt-1 leading-relaxed line-clamp-2" title="<?= htmlspecialchars($dept['description'] ?? '') ?>">
                        <?= htmlspecialchars($dept['description'] ?: 'No description provided.') ?>
                    </p>
                </div>
                <span class="flex-shrink-0 bg-secondary/10 border border-secondary/35 text-secondary text-2xs px-2.5 py-1 rounded font-semibold uppercase tracking-wider"><?= $dept['member_count'] ?> Active</span>
            </div>

            <!-- Members list inside department -->
            <div class="flex-grow border-t border-border-custom pt-4">
                <h4 class="text-2xs font-extrabold text-text-secondary uppercase tracking-widest mb-3">Members Assigned</h4>
                
                <div class="flex flex-col gap-2.5 max-h-[12.5rem] overflow-y-auto pr-1">
                    <?php if (empty($dept_members[$dept['id']])): ?>
                        <p class="text-text-muted text-xs text-center py-6 font-medium">No members assigned.</p>
                    <?php else: ?>
                        <?php foreach ($dept_members[$dept['id']] as $m): 
                            $initials = strtoupper(substr($m['name'], 0, 1));
                        ?>
                            <div class="flex items-center gap-2.5 p-2 bg-white/[0.015] border border-transparent rounded hover:border-border-custom hover:bg-white/[0.04] transition-all">
                                <div class="w-7 h-7 rounded-full bg-bg-surface-solid flex items-center justify-center font-bold text-xs text-primary border border-border-custom"><?= htmlspecialchars($initials) ?></div>
                                <div class="min-w-0 flex-grow">
                                    <div class="text-xs font-semibold text-white truncate"><?= htmlspecialchars($m['name']) ?></div>
                                    <div class="text-3xs text-text-muted truncate"><?= htmlspecialchars($m['phone'] ?: 'No contact') ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actions footer -->
            <div class="flex justify-end gap-2 border-t border-border-custom pt-4 mt-auto">
                <button class="bg-bg-surface-solid text-text-primary border border-border-custom hover:bg-border-custom-hover hover:text-white font-semibold text-xs px-3 py-1.5 rounded transition-all duration-150 cursor-pointer" onclick='openEditDeptModal(<?= json_encode($dept) ?>)'>Rename / Edit</button>
            </div>

        </div>
    <?php endforeach; ?>
</div>

<!-- Edit Department Modal Overlay -->
<div class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[1000] opacity-0 pointer-events-none transition-all duration-300" id="deptModal">
    <div class="bg-bg-surface-solid border border-border-custom rounded-2xl w-full max-w-[450px] p-6 shadow-2xl flex flex-col max-h-[90vh] scale-95 opacity-0 transition-all duration-300" id="deptModalContainer">
        <div class="flex justify-between items-center mb-5">
            <h2 class="font-heading font-bold text-lg text-white" id="modalTitle">Edit Department Info</h2>
            <button class="text-2xl text-text-muted hover:text-white bg-none border-none p-0 cursor-pointer" onclick="closeModal()">&times;</button>
        </div>
        <form id="deptForm" method="POST" action="workforce?action=edit" class="flex flex-col gap-4 overflow-hidden">
            <?php render_csrf_input(); ?>
            <input type="hidden" name="id" id="deptId">
            
            <div class="overflow-y-auto flex-grow pr-1 flex flex-col gap-4">
                <div class="flex flex-col gap-1.5">
                    <label for="formDeptName" class="text-xs font-semibold text-text-secondary">Department Name</label>
                    <input type="text" name="name" id="formDeptName" required placeholder="e.g. Media & Tech Team" class="w-full bg-black/30 border border-white/10 rounded-md px-4 py-2.5 text-white placeholder-text-muted focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                </div>
                
                <div class="flex flex-col gap-1.5">
                    <label for="formDeptDesc" class="text-xs font-semibold text-text-secondary">Description</label>
                    <textarea name="description" id="formDeptDesc" rows="3" placeholder="Describe the roles and functions of this department..." class="w-full bg-black/30 border border-white/10 rounded-md px-4 py-2.5 text-white placeholder-text-muted focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm"></textarea>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 border-t border-border-custom pt-4 flex-shrink-0">
                <button type="button" class="bg-bg-surface-solid text-text-primary border border-border-custom hover:bg-border-custom-hover hover:text-white font-semibold text-xs px-4 py-2 rounded transition-all duration-150 cursor-pointer" onclick="closeModal()">Cancel</button>
                <button type="submit" class="bg-success text-white font-semibold text-xs px-4 py-2 rounded hover:bg-opacity-90 transition-all duration-150 cursor-pointer" id="formSubmitBtn">Update Department</button>
            </div>
        </form>
    </div>
</div>

<script>
const modal = document.getElementById('deptModal');
const container = document.getElementById('deptModalContainer');
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
    
    modal.classList.add('opacity-100', 'pointer-events-auto');
    container.classList.add('scale-100', 'opacity-100');
}

function closeModal() {
    modal.classList.remove('opacity-100', 'pointer-events-auto');
    container.classList.remove('scale-100', 'opacity-100');
}

// Close on escape key
window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeModal();
});
</script>

<?php
render_footer();
?>
