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
    
    // Update Site Settings
    elseif ($action === 'update_settings') {
        $system_name = trim($_POST['system_name'] ?? '');
        $organization_name = trim($_POST['organization_name'] ?? '');
        $contact_email = trim($_POST['contact_email'] ?? '');
        $contact_phone = trim($_POST['contact_phone'] ?? '');
        $currency = trim($_POST['currency'] ?? '');
        $timezone = trim($_POST['timezone'] ?? '');
        
        if (empty($system_name) || empty($organization_name)) {
            $error = 'System name and organization name are required.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
                $stmt->execute([$system_name, 'system_name']);
                $stmt->execute([$organization_name, 'organization_name']);
                $stmt->execute([$contact_email, 'contact_email']);
                $stmt->execute([$contact_phone, 'contact_phone']);
                $stmt->execute([$currency, 'currency']);
                $stmt->execute([$timezone, 'timezone']);
                
                // Refresh global array in memory
                $GLOBALS['site_settings']['system_name'] = $system_name;
                $GLOBALS['site_settings']['organization_name'] = $organization_name;
                $GLOBALS['site_settings']['contact_email'] = $contact_email;
                $GLOBALS['site_settings']['contact_phone'] = $contact_phone;
                $GLOBALS['site_settings']['currency'] = $currency;
                $GLOBALS['site_settings']['timezone'] = $timezone;

                // Handle logo removal
                if (isset($_POST['remove_logo']) && $_POST['remove_logo'] == '1') {
                    $old_logo = $GLOBALS['site_settings']['logo_path'] ?? '';
                    if (!empty($old_logo) && file_exists(__DIR__ . '/' . $old_logo)) {
                        @unlink(__DIR__ . '/' . $old_logo);
                    }
                    $stmt_logo = $pdo->prepare("UPDATE site_settings SET setting_value = '' WHERE setting_key = 'logo_path'");
                    $stmt_logo->execute();
                    $GLOBALS['site_settings']['logo_path'] = '';
                }

                // Handle logo upload
                if (isset($_FILES['logo_image']) && $_FILES['logo_image']['error'] === UPLOAD_ERR_OK) {
                    $file_tmp = $_FILES['logo_image']['tmp_name'];
                    $file_name = $_FILES['logo_image']['name'];
                    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed_exts = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'];
                    
                    if (in_array($ext, $allowed_exts)) {
                        $target_dir = __DIR__ . '/assets/';
                        if (!file_exists($target_dir)) {
                            mkdir($target_dir, 0755, true);
                        }
                        
                        // Remove old logo files to prevent clutter
                        foreach (glob($target_dir . 'logo_uploaded.*') as $old_file) {
                            @unlink($old_file);
                        }
                        
                        $new_filename = 'logo_uploaded.' . $ext;
                        $dest_path = $target_dir . $new_filename;
                        
                        if (move_uploaded_file($file_tmp, $dest_path)) {
                            $logo_rel_path = 'assets/' . $new_filename;
                            $stmt_logo = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'logo_path'");
                            $stmt_logo->execute([$logo_rel_path]);
                            $GLOBALS['site_settings']['logo_path'] = $logo_rel_path;
                        }
                    } else {
                        $error = 'Invalid logo format. Only PNG, JPG, JPEG, GIF, WEBP, and SVG are allowed.';
                    }
                }
                
                if (empty($error)) {
                    header("Location: settings?tab=system&msg=Site settings updated successfully");
                    exit;
                }
            } catch (PDOException $e) {
                $error = 'Error updating site settings: ' . $e->getMessage();
            }
        }
    }
    
    // Start Service Session
    elseif ($action === 'start_session') {
        $service_id = intval($_POST['service_id'] ?? 0);
        $date = trim($_POST['date'] ?? '');
        
        if ($service_id <= 0 || empty($date)) {
            $error = 'Service type and date selection are required to start a session.';
        } else {
            try {
                $pdo->beginTransaction();
                
                // 1. Insert session record into attendance
                $stmt = $pdo->prepare("INSERT INTO attendance (service_id, date) VALUES (?, ?)");
                $stmt->execute([$service_id, $date]);
                $attendance_id = $pdo->lastInsertId();
                
                // 2. Update active_session_id in settings
                $stmt_set = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'active_session_id'");
                $stmt_set->execute([$attendance_id]);
                
                // 3. Pre-populate all current members in member_attendance as 'Absent'
                $members = $pdo->query("SELECT id FROM members")->fetchAll(PDO::FETCH_COLUMN);
                $stmt_ma = $pdo->prepare("INSERT IGNORE INTO member_attendance (member_id, attendance_id, status) VALUES (?, ?, 'Absent')");
                foreach ($members as $member_id) {
                    $stmt_ma->execute([$member_id, $attendance_id]);
                }
                
                $pdo->commit();
                
                header("Location: settings?tab=sessions&msg=Service session started successfully. Go to the Attendance page to take attendance.");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Error starting service session: ' . $e->getMessage();
            }
        }
    }
    
    // Close Service Session
    elseif ($action === 'close_session') {
        try {
            $stmt_set = $pdo->prepare("UPDATE site_settings SET setting_value = '' WHERE setting_key = 'active_session_id'");
            $stmt_set->execute();
            header("Location: settings?tab=sessions&msg=Service session closed successfully");
            exit;
        } catch (PDOException $e) {
            $error = 'Error closing service session: ' . $e->getMessage();
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

// Fetch active session state if running
$active_session_id = $GLOBALS['site_settings']['active_session_id'] ?? '';
$active_session = null;
if (!empty($active_session_id)) {
    $stmt_active = $pdo->prepare("
        SELECT a.id, a.date, s.name as service_name
        FROM attendance a
        JOIN services s ON a.service_id = s.id
        WHERE a.id = ?
    ");
    $stmt_active->execute([$active_session_id]);
    $active_session = $stmt_active->fetch(PDO::FETCH_ASSOC);
}

// Determine active tab (defaults to sessions)
$active_tab = $_GET['tab'] ?? 'sessions';

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
    <button class="settings-tab-btn <?= $active_tab === 'sessions' ? 'active' : '' ?>" onclick="switchTab('sessions')">Service Sessions</button>
    <button class="settings-tab-btn <?= $active_tab === 'services' ? 'active' : '' ?>" onclick="switchTab('services')">Service Types</button>
    <button class="settings-tab-btn <?= $active_tab === 'departments' ? 'active' : '' ?>" onclick="switchTab('departments')">Departments</button>
    <button class="settings-tab-btn <?= $active_tab === 'users' ? 'active' : '' ?>" onclick="switchTab('users')">User Accounts</button>
    <button class="settings-tab-btn <?= $active_tab === 'system' ? 'active' : '' ?>" onclick="switchTab('system')">Site Settings</button>
</div>

<!-- Tab: Service Sessions Control -->
<div id="tabContent-sessions" class="settings-tab-content <?= $active_tab === 'sessions' ? 'active' : '' ?>">
    <div class="dashboard-grid" style="grid-template-columns: 1fr; max-width: 37.5rem; margin: 0 auto;">
        <div class="card-panel">
            <div class="panel-header">
                <h2 class="panel-title">Active Service Session</h2>
            </div>
            
            <?php if ($active_session): ?>
                <div style="background: rgba(6, 182, 212, 0.08); border: 0.0625rem solid var(--secondary); padding: 1.5rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; text-align: center;">
                    <span class="badge badge-success" style="margin-bottom: 0.75rem; font-size: 0.8rem; padding: 0.35rem 0.75rem;">LIVE SESSION RUNNING</span>
                    <h3 style="font-size: 1.3rem; font-weight: 700; margin-bottom: 0.25rem; color: var(--text-primary);">
                        <?= htmlspecialchars($active_session['service_name']) ?>
                    </h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.25rem;">
                        Started on <?= date('l, M d, Y', strtotime($active_session['date'])) ?>
                    </p>
                    
                    <a href="attendance" class="btn btn-primary" style="display: inline-flex; justify-content: center; width: auto; padding: 0.6rem 1.5rem; margin-right: 0.75rem;">
                        Take Attendance
                    </a>
                    
                    <form method="POST" action="settings?action=close_session" style="display: inline-block; margin: 0;">
                        <button type="submit" class="btn btn-secondary" style="border-color: var(--danger); color: var(--danger); padding: 0.6rem 1.5rem;" onclick="return confirm('Are you sure you want to close this service session? After closing, attendance for this session can no longer be updated live.')">
                            Close Session
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div style="background: rgba(255, 255, 255, 0.02); border: 0.0625rem solid var(--border-color); padding: 1.5rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; text-align: center;">
                    <p style="color: var(--text-muted); margin-bottom: 0;">No active service session is running. Start one below to begin taking attendance.</p>
                </div>
                
                <?php if (empty($services)): ?>
                    <p style="color: var(--text-muted); text-align: center; padding: 1.5rem 0;">Please define at least one service type in the <strong>Service Types</strong> tab before starting a session.</p>
                <?php else: ?>
                    <form method="POST" action="settings?action=start_session">
                        <div class="form-grid" style="grid-template-columns: 1fr; gap: 1.25rem;">
                            <div class="form-group">
                                <label for="sessServiceId">Select Service Type</label>
                                <select name="service_id" id="sessServiceId" required>
                                    <option value="">-- Choose Service --</option>
                                    <?php foreach ($services as $srv): ?>
                                        <option value="<?= $srv['id'] ?>"><?= htmlspecialchars($srv['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="sessDate">Session Date</label>
                                <input type="date" name="date" id="sessDate" required value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success" style="margin-top: 1.5rem; width: 100%; justify-content: center;">
                            Start Service Session
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
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

<!-- Tab 4: Site Settings -->
<div id="tabContent-system" class="settings-tab-content <?= $active_tab === 'system' ? 'active' : '' ?>">
    <div style="max-width: 40rem; margin: 0 auto;">
        <div class="card-panel">
            <div class="panel-header">
                <h2 class="panel-title" style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Configure Application Settings
                </h2>
            </div>
            
            <form method="POST" action="settings?action=update_settings">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="setSystemName">System Name</label>
                        <input type="text" name="system_name" id="setSystemName" required value="<?= htmlspecialchars($GLOBALS['site_settings']['system_name'] ?? '') ?>" placeholder="e.g. BYC DATA CENTER">
                    </div>
                    
                    <div class="form-group">
                        <label for="setOrgName">Organization Name</label>
                        <input type="text" name="organization_name" id="setOrgName" required value="<?= htmlspecialchars($GLOBALS['site_settings']['organization_name'] ?? '') ?>" placeholder="e.g. Beersheba Youth Church">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="setContactEmail">Contact Email</label>
                        <input type="email" name="contact_email" id="setContactEmail" value="<?= htmlspecialchars($GLOBALS['site_settings']['contact_email'] ?? '') ?>" placeholder="e.g. info@byc.org">
                    </div>
                    
                    <div class="form-group">
                        <label for="setContactPhone">Contact Phone</label>
                        <input type="text" name="contact_phone" id="setContactPhone" value="<?= htmlspecialchars($GLOBALS['site_settings']['contact_phone'] ?? '') ?>" placeholder="e.g. 0241112222">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="setCurrency">Currency Symbol</label>
                        <input type="text" name="currency" id="setCurrency" value="<?= htmlspecialchars($GLOBALS['site_settings']['currency'] ?? '') ?>" placeholder="e.g. GH₵, $">
                    </div>
                    
                    <div class="form-group">
                        <label for="setTimezone">Timezone</label>
                        <select name="timezone" id="setTimezone">
                            <?php
                            $timezones = ['Africa/Accra', 'Africa/Lagos', 'Africa/Nairobi', 'UTC', 'Europe/London', 'America/New_York'];
                            $current_tz = $GLOBALS['site_settings']['timezone'] ?? 'Africa/Accra';
                            foreach ($timezones as $tz):
                                $selected = $tz === $current_tz ? 'selected' : '';
                                echo "<option value=\"" . htmlspecialchars($tz) . "\" $selected>" . htmlspecialchars($tz) . "</option>";
                            endforeach;
                            ?>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-success" style="margin-top: 1.5rem; width: 100%; justify-content: center;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 0.25rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                    Save Site Settings
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
