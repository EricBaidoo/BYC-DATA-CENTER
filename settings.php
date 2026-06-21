<?php
// settings.php - Administration & Settings Panel
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/layout.php';

$error = '';
$success = '';

// 1. Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // Add Service Type
    if ($action === 'add_service') {
        $name = trim($_POST['service_name'] ?? '');
        $description = trim($_POST['service_description'] ?? '');
        if (empty($name)) {
            $error = 'Service name is required.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO services (name, description) VALUES (?, ?)");
                $stmt->execute([$name, $description]);
                header("Location: settings?tab=services&msg=Service type created successfully");
                exit;
            } catch (PDOException $e) {
                $error = 'Error creating service: ' . $e->getMessage();
            }
        }
    }
    
    // Add Department
    elseif ($action === 'add_dept') {
        $name = trim($_POST['dept_name'] ?? '');
        $description = trim($_POST['dept_description'] ?? '');
        if (empty($name)) {
            $error = 'Department name is required.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO departments (name, description) VALUES (?, ?)");
                $stmt->execute([$name, $description]);
                header("Location: settings?tab=departments&msg=Department created successfully");
                exit;
            } catch (PDOException $e) {
                $error = 'Error creating department: ' . $e->getMessage();
            }
        }
    }
    
    // Add User Account
    elseif ($action === 'add_user') {
        $username = trim($_POST['username'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $password = trim($_POST['password'] ?? '');
        
        if (empty($username) || empty($password)) {
            $error = 'Username and password fields are required.';
        } else {
            try {
                $stmt_chk = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                $stmt_chk->execute([$username]);
                if ($stmt_chk->fetchColumn() > 0) {
                    $error = 'Username already exists.';
                } else {
                    $pass_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, name) VALUES (?, ?, ?)");
                    $stmt->execute([$username, $pass_hash, $name]);
                    header("Location: settings?tab=users&msg=User account created successfully");
                    exit;
                }
            } catch (PDOException $e) {
                $error = 'Error creating user: ' . $e->getMessage();
            }
        }
    }
    
    // Edit User Account
    elseif ($action === 'edit_user') {
        $id = intval($_POST['id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $password = trim($_POST['password'] ?? '');
        
        if ($id <= 0 || empty($username)) {
            $error = 'Invalid user ID or username.';
        } else {
            try {
                $stmt_chk = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
                $stmt_chk->execute([$username, $id]);
                if ($stmt_chk->fetchColumn() > 0) {
                    $error = 'Username already exists.';
                } else {
                    if (!empty($password)) {
                        $pass_hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, name = ? WHERE id = ?");
                        $stmt->execute([$username, $pass_hash, $name, $id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, name = ? WHERE id = ?");
                        $stmt->execute([$username, $name, $id]);
                    }
                    
                    if ($id === $_SESSION['user_id']) {
                        $_SESSION['username'] = $username;
                        $_SESSION['user_name'] = $name ?: 'Administrator';
                    }
                    
                    header("Location: settings?tab=users&msg=User profile updated successfully");
                    exit;
                }
            } catch (PDOException $e) {
                $error = 'Error updating user: ' . $e->getMessage();
            }
        }
    }
    
    // Reset Database
    elseif ($action === 'reset_db') {
        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            $pdo->exec("DROP TABLE IF EXISTS member_attendance;");
            $pdo->exec("DROP TABLE IF EXISTS attendance;");
            $pdo->exec("DROP TABLE IF EXISTS members;");
            $pdo->exec("DROP TABLE IF EXISTS departments;");
            $pdo->exec("DROP TABLE IF EXISTS services;");
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

            $schema_file = __DIR__ . '/schema.sql';
            if (file_exists($schema_file)) {
                $sql = file_get_contents($schema_file);
                $pdo->exec($sql);
                
                // Seed members
                $pdo->exec("INSERT INTO members (name, phone, email, address, birthday, gender, join_date, department_id) VALUES 
                    ('Alesha Cole', '0241112222', 'alesha@byc.org', 'Accra, Ghana', '1998-05-12', 'Female', '2024-01-10', 1),
                    ('David Boateng', '0243334444', 'david@byc.org', 'Kumasi, Ghana', '1995-11-20', 'Male', '2023-06-15', 2),
                    ('Emmanuel Mensah', '0245556666', 'emmanuel@byc.org', 'Tema, Ghana', '2000-03-08', 'Male', '2025-02-01', 3),
                    ('Sampson Tetteh', '0207778888', 'sampson@byc.org', 'Accra, Ghana', '1993-02-14', 'Male', '2022-04-10', 5),
                    ('Rebecca Larbi', '0209990000', 'rebecca@byc.org', 'Cape Coast, Ghana', '1997-09-22', 'Female', '2021-08-11', 5);");

                $pdo->exec("INSERT INTO services (name, description) VALUES ('Youth Mega Rally', 'Annual large scale youth cell assembly');");

                $sunday_service_id = $pdo->query("SELECT id FROM services WHERE name = 'Sunday Worship Service'")->fetchColumn();
                $mega_rally_id = $pdo->query("SELECT id FROM services WHERE name = 'Youth Mega Rally'")->fetchColumn();

                $pdo->exec("INSERT INTO attendance (service_id, date) VALUES ($sunday_service_id, DATE_SUB(CURDATE(), INTERVAL 3 DAY));");
                $pdo->exec("INSERT INTO attendance (service_id, date) VALUES ($mega_rally_id, DATE_SUB(CURDATE(), INTERVAL 8 MONTH));");

                $pdo->exec("INSERT INTO member_attendance (member_id, attendance_id, status) VALUES 
                    (1, 1, 'Present'),
                    (2, 1, 'Present'),
                    (3, 1, 'Present'),
                    (4, 1, 'Absent'),
                    (5, 1, 'Absent');");

                $pdo->exec("INSERT INTO member_attendance (member_id, attendance_id, status) VALUES 
                    (1, 2, 'Present'),
                    (2, 2, 'Present'),
                    (3, 2, 'Present'),
                    (4, 2, 'Present'),
                    (5, 2, 'Present');");
            }
            header("Location: settings?tab=system&msg=Database re-initialized to clean seeded defaults");
            exit;
        } catch (PDOException $e) {
            $error = 'Error resetting database: ' . $e->getMessage();
        }
    }
}

// 2. Handle GET Deletions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id'] ?? 0);
    
    if ($action === 'delete_service' && $id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: settings?tab=services&msg=Service type deleted successfully");
            exit;
        } catch (PDOException $e) {
            $error = 'Error deleting service: ' . $e->getMessage();
        }
    }
    
    elseif ($action === 'delete_dept' && $id > 0) {
        try {
            // Check default 'None'
            $stmt_check = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
            $stmt_check->execute([$id]);
            $dept_name = $stmt_check->fetchColumn();
            
            if ($dept_name === 'None') {
                $error = 'Default "None" category cannot be deleted.';
            } else {
                $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
                $stmt->execute([$id]);
                header("Location: settings?tab=departments&msg=Department deleted successfully");
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Error deleting department: ' . $e->getMessage();
        }
    }
    
    elseif ($action === 'delete_user' && $id > 0) {
        if ($id === $_SESSION['user_id']) {
            $error = 'You cannot delete your own active session.';
        } else {
            try {
                $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                if ($user_count <= 1) {
                    $error = 'At least one administrator account must be retained.';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$id]);
                    header("Location: settings?tab=users&msg=User account deleted successfully");
                    exit;
                }
            } catch (PDOException $e) {
                $error = 'Error deleting user: ' . $e->getMessage();
            }
        }
    }
}

if (isset($_GET['msg'])) {
    $success = $_GET['msg'];
}

// Fetch all services & departments for administration list
$services = $pdo->query("SELECT * FROM services ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$departments = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$users = $pdo->query("SELECT id, username, name, created_at FROM users ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);

// Determine active tab (defaults to services)
$active_tab = $_GET['tab'] ?? 'services';

render_header('Settings & Administration', 'settings');
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

<!-- Settings Tab Navigation -->
<div class="settings-tabs">
    <button class="settings-tab-btn <?= $active_tab === 'services' ? 'active' : '' ?>" onclick="switchTab('services')">Service Types</button>
    <button class="settings-tab-btn <?= $active_tab === 'departments' ? 'active' : '' ?>" onclick="switchTab('departments')">Departments</button>
    <button class="settings-tab-btn <?= $active_tab === 'users' ? 'active' : '' ?>" onclick="switchTab('users')">User Accounts</button>
    <button class="settings-tab-btn <?= $active_tab === 'system' ? 'active' : '' ?>" onclick="switchTab('system')">System Control</button>
</div>

<!-- Tab 1: Service Types Management -->
<div id="tabContent-services" class="settings-tab-content <?= $active_tab === 'services' ? 'active' : '' ?>">
    <div class="dashboard-grid">
        <!-- Add Service Form -->
        <div class="card-panel">
            <div class="panel-header">
                <h2 class="panel-title">Add Service Type</h2>
            </div>
            <form method="POST" action="settings?action=add_service">
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label for="srvName">Service Name</label>
                    <input type="text" name="service_name" id="srvName" required placeholder="e.g. Wednesday Communion Service">
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="srvDesc">Description</label>
                    <textarea name="service_description" id="srvDesc" rows="3" placeholder="Brief description of when this service runs or its target audience..."></textarea>
                </div>
                <button type="submit" class="btn btn-success" style="width: 100%; justify-content: center;">Save Service Type</button>
            </form>
        </div>
        
        <!-- List of Service Types -->
        <div class="card-panel">
            <div class="panel-header">
                <h2 class="panel-title">Current Service Definitions</h2>
            </div>
            <div class="table-container">
                <?php if (empty($services)): ?>
                    <p style="color: var(--text-muted); text-align: center; padding: 2rem 0;">No service types defined.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Name & Description</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $srv): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($srv['name']) ?></strong>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($srv['description'] ?: 'No description.') ?></div>
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="settings?action=delete_service&id=<?= $srv['id'] ?>" class="btn btn-secondary" style="padding: 0.35rem 0.7rem; font-size: 0.8rem; color: var(--danger); border-color: rgba(239, 68, 68, 0.2);" onclick="return confirm('Deleting this service will permanently delete all logged sessions and attendance mapped to it. Continue?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Tab 2: Departments Management -->
<div id="tabContent-departments" class="settings-tab-content <?= $active_tab === 'departments' ? 'active' : '' ?>">
    <div class="dashboard-grid">
        <!-- Add Department Form -->
        <div class="card-panel">
            <div class="panel-header">
                <h2 class="panel-title">Create Department</h2>
            </div>
            <form method="POST" action="settings?action=add_dept">
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label for="deptName">Department Name</label>
                    <input type="text" name="dept_name" id="deptName" required placeholder="e.g. Media & Tech Team">
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="deptDesc">Description</label>
                    <textarea name="dept_description" id="deptDesc" rows="3" placeholder="Brief description of tasks, responsibilities, or roles in this workforce cohort..."></textarea>
                </div>
                <button type="submit" class="btn btn-success" style="width: 100%; justify-content: center;">Save Department</button>
            </form>
        </div>
        
        <!-- List of Departments -->
        <div class="card-panel">
            <div class="panel-header">
                <h2 class="panel-title">Current Department Structures</h2>
            </div>
            <div class="table-container">
                <?php if (empty($departments)): ?>
                    <p style="color: var(--text-muted); text-align: center; padding: 2rem 0;">No departments defined.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Name & Description</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departments as $dept): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($dept['name']) ?></strong>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($dept['description'] ?: 'No description.') ?></div>
                                    </td>
                                    <td style="text-align: right;">
                                        <?php if ($dept['name'] !== 'None'): ?>
                                            <a href="settings?action=delete_dept&id=<?= $dept['id'] ?>" class="btn btn-secondary" style="padding: 0.35rem 0.7rem; font-size: 0.8rem; color: var(--danger); border-color: rgba(239, 68, 68, 0.2);" onclick="return confirm('Are you sure you want to delete this department? All assigned members will have their department reset to None.')">Delete</a>
                                        <?php else: ?>
                                            <span style="font-size: 0.75rem; color: var(--text-muted); font-style: italic;">Protected System Default</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Tab 3: User Accounts Management -->
<div id="tabContent-users" class="settings-tab-content <?= $active_tab === 'users' ? 'active' : '' ?>">
    <div class="dashboard-grid">
        <!-- Add/Edit User Form -->
        <div class="card-panel">
            <div class="panel-header">
                <h2 class="panel-title" id="userFormTitle">Register New User</h2>
            </div>
            <form id="userForm" method="POST" action="settings?action=add_user">
                <input type="hidden" name="id" id="formUserId">
                
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label for="formUserName">Display Name</label>
                    <input type="text" name="name" id="formUserName" required placeholder="e.g. John Doe">
                </div>
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label for="formUserUsername">Username</label>
                    <input type="text" name="username" id="formUserUsername" required placeholder="e.g. johndoe">
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="formUserPassword" id="formUserPasswordLabel">Password</label>
                    <input type="password" name="password" id="formUserPassword" required placeholder="Enter password...">
                </div>
                
                <div style="display: flex; gap: 0.75rem;">
                    <button type="submit" id="userSubmitBtn" class="btn btn-success" style="flex-grow: 1; justify-content: center;">Save User</button>
                    <button type="button" id="userCancelBtn" class="btn btn-secondary" style="display: none; justify-content: center;" onclick="resetUserForm()">Cancel</button>
                </div>
            </form>
        </div>
        
        <!-- List of Users -->
        <div class="card-panel">
            <div class="panel-header">
                <h2 class="panel-title">Active User Accounts</h2>
            </div>
            <div class="table-container">
                <?php if (empty($users)): ?>
                    <p style="color: var(--text-muted); text-align: center; padding: 2rem 0;">No user accounts found.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Name & Username</th>
                                <th>Created Date</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $usr): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($usr['name'] ?: 'No Name') ?></strong>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">@<?= htmlspecialchars($usr['username']) ?></div>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($usr['created_at'])) ?></td>
                                    <td style="text-align: right;">
                                        <button class="btn btn-secondary" style="padding: 0.35rem 0.7rem; font-size: 0.8rem;" onclick='openEditUserMode(<?= json_encode($usr) ?>)'>Edit</button>
                                        <?php if ($usr['id'] !== $_SESSION['user_id']): ?>
                                            <a href="settings?action=delete_user&id=<?= $usr['id'] ?>" class="btn btn-secondary" style="padding: 0.35rem 0.7rem; font-size: 0.8rem; color: var(--danger); border-color: rgba(239, 68, 68, 0.2);" onclick="return confirm('Are you sure you want to permanently delete this user account?')">Delete</a>
                                        <?php else: ?>
                                            <span style="font-size: 0.75rem; color: var(--text-muted); font-style: italic;">Active Session</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Tab 4: System Reset Control -->
<div id="tabContent-system" class="settings-tab-content <?= $active_tab === 'system' ? 'active' : '' ?>">
    <div style="max-width: 37.5rem; margin: 0 auto;">
        <div class="card-panel" style="border-color: rgba(239, 68, 68, 0.2); background: linear-gradient(135deg, rgba(239, 68, 68, 0.05), transparent);">
            <div class="panel-header" style="border-bottom-color: rgba(239, 68, 68, 0.2);">
                <h2 class="panel-title" style="color: var(--danger); display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Danger Zone: Reset Database
                </h2>
            </div>
            
            <p style="font-size: 0.95rem; line-height: 1.5; color: var(--text-primary); margin-bottom: 1.25rem;">
                Performing a database reset will drop all existing tables and re-initialize the schema. This action will:
            </p>
            <ul style="font-size: 0.85rem; line-height: 1.6; color: var(--text-secondary); margin-bottom: 1.5rem; padding-left: 1.5rem; display: flex; flex-direction: column; gap: 0.5rem;">
                <li>Permanently delete all custom service types, departments, and active member profiles.</li>
                <li>Clear all logged attendance session histories.</li>
                <li>Restore the system to clean, pre-seeded default profiles (5 members, 5 departments, 4 services, and 2 historical attendances) to facilitate demonstration.</li>
            </ul>
            
            <div style="background: rgba(0, 0, 0, 0.2); padding: 1rem; border-radius: var(--radius-sm); border: 0.0625rem solid var(--border-color); margin-bottom: 1.5rem; font-size: 0.8rem; color: var(--text-muted);">
                <strong>Note:</strong> This process happens locally on the server. If you want to keep your data, please manually duplicate `byc_church.db` before resetting.
            </div>

            <form method="POST" action="settings.php?action=reset_db" onsubmit="return confirm('CRITICAL WARNING: You are about to wipe all database tables and reload system seeds. This action is irreversible. Proceed?')">
                <button type="submit" class="btn btn-primary" style="background: var(--danger); box-shadow: 0 0.25rem 0.875rem rgba(239, 68, 68, 0.3); width: 100%; justify-content: center;">
                    Confirm System Re-initialization
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function switchTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.settings-tab-content').forEach(el => el.classList.remove('active'));
    // Un-activate all tab buttons
    document.querySelectorAll('.settings-tab-btn').forEach(el => el.classList.remove('active'));
    
    // Show active content and activate button
    const activeContent = document.getElementById('tabContent-' + tabName);
    if (activeContent) activeContent.classList.add('active');
    
    // Find button to activate
    const buttons = document.querySelectorAll('.settings-tab-btn');
    buttons.forEach(btn => {
        if (btn.textContent.trim().toLowerCase().includes(tabName.toLowerCase())) {
            btn.classList.add('active');
        }
    });

    // Update query string parameter for browser history
    const newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?tab=' + tabName;
    window.history.pushState({path:newurl},'',newurl);
}

function openEditUserMode(user) {
    const title = document.getElementById('userFormTitle');
    const form = document.getElementById('userForm');
    const cancelBtn = document.getElementById('userCancelBtn');
    const submitBtn = document.getElementById('userSubmitBtn');
    const passLabel = document.getElementById('formUserPasswordLabel');
    const passInput = document.getElementById('formUserPassword');
    
    title.textContent = 'Edit User Profile';
    form.action = 'settings?action=edit_user';
    
    document.getElementById('formUserId').value = user.id;
    document.getElementById('formUserName').value = user.name || '';
    document.getElementById('formUserUsername').value = user.username || '';
    
    passLabel.textContent = 'Password (leave blank to keep current)';
    passInput.required = false;
    passInput.placeholder = 'Enter new password or leave blank...';
    passInput.value = '';
    
    cancelBtn.style.display = 'block';
    submitBtn.textContent = 'Update User';
}

function resetUserForm() {
    const title = document.getElementById('userFormTitle');
    const form = document.getElementById('userForm');
    const cancelBtn = document.getElementById('userCancelBtn');
    const submitBtn = document.getElementById('userSubmitBtn');
    const passLabel = document.getElementById('formUserPasswordLabel');
    const passInput = document.getElementById('formUserPassword');
    
    form.reset();
    title.textContent = 'Register New User';
    form.action = 'settings?action=add_user';
    
    document.getElementById('formUserId').value = '';
    
    passLabel.textContent = 'Password';
    passInput.required = true;
    passInput.placeholder = 'Enter password...';
    
    cancelBtn.style.display = 'none';
    submitBtn.textContent = 'Save User';
}
</script>

<?php
render_footer();
?>
