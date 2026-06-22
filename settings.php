<?php
// settings.php - Administration & Settings Panel
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/layout.php';

$error = '';
$success = '';

// 1. Handle POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    validate_csrf();
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
                $new_id = $pdo->lastInsertId();
                log_audit_action('ADD_SERVICE_TYPE', 'services', $new_id, $name);
                header("Location: settings?tab=services&msg=Service type created successfully");
                exit;
            } catch (PDOException $e) {
                $error = 'Error creating service: ' . $e->getMessage();
            }
        }
    }
    
    // Edit Service Type
    elseif ($action === 'edit_service') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['service_name'] ?? '');
        $description = trim($_POST['service_description'] ?? '');
        if ($id <= 0 || empty($name)) {
            $error = 'Service name and ID are required.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE services SET name = ?, description = ? WHERE id = ?");
                $stmt->execute([$name, $description, $id]);
                log_audit_action('EDIT_SERVICE_TYPE', 'services', $id, $name);
                header("Location: settings?tab=services&msg=Service type updated successfully");
                exit;
            } catch (PDOException $e) {
                $error = 'Error updating service: ' . $e->getMessage();
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
                $new_id = $pdo->lastInsertId();
                log_audit_action('ADD_DEPARTMENT', 'departments', $new_id, $name);
                header("Location: settings?tab=departments&msg=Department created successfully");
                exit;
            } catch (PDOException $e) {
                $error = 'Error creating department: ' . $e->getMessage();
            }
        }
    }
    
    // Edit Department
    elseif ($action === 'edit_dept') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['dept_name'] ?? '');
        $description = trim($_POST['dept_description'] ?? '');
        if ($id <= 0 || empty($name)) {
            $error = 'Department name and ID are required.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE departments SET name = ?, description = ? WHERE id = ?");
                $stmt->execute([$name, $description, $id]);
                log_audit_action('EDIT_DEPARTMENT', 'departments', $id, $name);
                header("Location: settings?tab=departments&msg=Department updated successfully");
                exit;
            } catch (PDOException $e) {
                $error = 'Error updating department: ' . $e->getMessage();
            }
        }
    }
    
    // Add Home Cell
    elseif ($action === 'add_cell') {
        $name = trim($_POST['cell_name'] ?? '');
        $leader_id = !empty($_POST['leader_id']) ? intval($_POST['leader_id']) : null;
        $location = trim($_POST['location'] ?? '');
        $meeting_day = trim($_POST['meeting_day'] ?? '');
        if (empty($name)) {
            $error = 'Cell Group name is required.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO home_cells (name, leader_id, location, meeting_day) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $leader_id, $location, $meeting_day]);
                $new_id = $pdo->lastInsertId();
                log_audit_action('ADD_HOME_CELL', 'home_cells', $new_id, $name);
                header("Location: settings?tab=cells&msg=Home Cell Group created successfully");
                exit;
            } catch (PDOException $e) {
                $error = 'Error creating cell group: ' . $e->getMessage();
            }
        }
    }
    
    // Edit Home Cell
    elseif ($action === 'edit_cell') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['cell_name'] ?? '');
        $leader_id = !empty($_POST['leader_id']) ? intval($_POST['leader_id']) : null;
        $location = trim($_POST['location'] ?? '');
        $meeting_day = trim($_POST['meeting_day'] ?? '');
        if ($id <= 0 || empty($name)) {
            $error = 'Cell Group name and ID are required.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE home_cells SET name = ?, leader_id = ?, location = ?, meeting_day = ? WHERE id = ?");
                $stmt->execute([$name, $leader_id, $location, $meeting_day, $id]);
                log_audit_action('EDIT_HOME_CELL', 'home_cells', $id, $name);
                header("Location: settings?tab=cells&msg=Home Cell Group updated successfully");
                exit;
            } catch (PDOException $e) {
                $error = 'Error updating cell group: ' . $e->getMessage();
            }
        }
    }
    
    // Add Household
    elseif ($action === 'add_household') {
        $name = trim($_POST['household_name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        if (empty($name)) {
            $error = 'Household name is required.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO households (name, address) VALUES (?, ?)");
                $stmt->execute([$name, $address]);
                $new_id = $pdo->lastInsertId();
                log_audit_action('ADD_HOUSEHOLD', 'households', $new_id, $name);
                header("Location: settings?tab=households&msg=Household cohort created successfully");
                exit;
            } catch (PDOException $e) {
                $error = 'Error creating household: ' . $e->getMessage();
            }
        }
    }
    
    // Edit Household
    elseif ($action === 'edit_household') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['household_name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        if ($id <= 0 || empty($name)) {
            $error = 'Household name and ID are required.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE households SET name = ?, address = ? WHERE id = ?");
                $stmt->execute([$name, $address, $id]);
                log_audit_action('EDIT_HOUSEHOLD', 'households', $id, $name);
                header("Location: settings?tab=households&msg=Household cohort updated successfully");
                exit;
            } catch (PDOException $e) {
                $error = 'Error updating household: ' . $e->getMessage();
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
                    $new_id = $pdo->lastInsertId();
                    
                    log_audit_action('ADD_USER', 'users', $new_id, $username);
                    
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
                    
                    log_audit_action('EDIT_USER', 'users', $id, $username);
                    
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
                
                log_audit_action('UPDATE_SITE_SETTINGS', 'site_settings', 0);
                
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
                
                // 3. Pre-populate all active members in member_attendance as 'Absent'
                $members = $pdo->query("SELECT id FROM members WHERE deleted_at IS NULL")->fetchAll(PDO::FETCH_COLUMN);
                $stmt_ma = $pdo->prepare("INSERT IGNORE INTO member_attendance (member_id, attendance_id, status) VALUES (?, ?, 'Absent')");
                foreach ($members as $member_id) {
                    $stmt_ma->execute([$member_id, $attendance_id]);
                }
                
                $pdo->commit();
                
                log_audit_action('START_SERVICE_SESSION', 'attendance', $attendance_id);
                
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
            $active_id = $GLOBALS['site_settings']['active_session_id'] ?? 0;
            $stmt_set = $pdo->prepare("UPDATE site_settings SET setting_value = '' WHERE setting_key = 'active_session_id'");
            $stmt_set->execute();
            
            log_audit_action('CLOSE_SERVICE_SESSION', 'attendance', $active_id);
            
            header("Location: settings?tab=sessions&msg=Service session closed successfully");
            exit;
        } catch (PDOException $e) {
            $error = 'Error closing service session: ' . $e->getMessage();
        }
    }
}

// 2. Handle GET Deletions
if (isset($_GET['action'])) {
    validate_csrf();
    $action = $_GET['action'];
    $id = intval($_GET['id'] ?? 0);
    
    if ($action === 'delete_service' && $id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
            $stmt->execute([$id]);
            log_audit_action('DELETE_SERVICE_TYPE', 'services', $id);
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
                log_audit_action('DELETE_DEPARTMENT', 'departments', $id);
                header("Location: settings?tab=departments&msg=Department deleted successfully");
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Error deleting department: ' . $e->getMessage();
        }
    }
    
    elseif ($action === 'delete_cell' && $id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM home_cells WHERE id = ?");
            $stmt->execute([$id]);
            log_audit_action('DELETE_HOME_CELL', 'home_cells', $id);
            header("Location: settings?tab=cells&msg=Home Cell Group deleted successfully");
            exit;
        } catch (PDOException $e) {
            $error = 'Error deleting cell group: ' . $e->getMessage();
        }
    }
    
    elseif ($action === 'delete_household' && $id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM households WHERE id = ?");
            $stmt->execute([$id]);
            log_audit_action('DELETE_HOUSEHOLD', 'households', $id);
            header("Location: settings?tab=households&msg=Household cohort deleted successfully");
            exit;
        } catch (PDOException $e) {
            $error = 'Error deleting household: ' . $e->getMessage();
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
                    log_audit_action('DELETE_USER', 'users', $id);
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

// Shepherding engine fetches
$cells = $pdo->query("
    SELECT c.*, CONCAT(m.first_name, ' ', COALESCE(m.last_name, '')) AS leader_name 
    FROM home_cells c 
    LEFT JOIN members m ON c.leader_id = m.id 
    ORDER BY c.name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$households = $pdo->query("SELECT * FROM households ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$audit_logs = $pdo->query("
    SELECT a.*, u.username, u.name as user_name 
    FROM audit_logs a 
    LEFT JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC 
    LIMIT 100
")->fetchAll(PDO::FETCH_ASSOC);

$all_members = $pdo->query("
    SELECT id, CONCAT(first_name, ' ', COALESCE(last_name, '')) as name 
    FROM members 
    WHERE deleted_at IS NULL 
    ORDER BY first_name ASC, last_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

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
    <div class="bg-danger/15 border border-danger text-danger p-4 rounded-md mb-6 text-sm font-semibold">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="bg-secondary/15 border border-secondary text-secondary p-4 rounded-md mb-6 text-sm font-semibold">
        <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<!-- Settings Tab Navigation -->
<div class="flex gap-1.5 border-b border-border-custom mb-8 overflow-x-auto pb-1 select-none whitespace-nowrap">
    <button class="px-4 py-2.5 bg-transparent border-none border-b-2 border-transparent text-text-secondary hover:text-text-primary font-heading font-semibold text-sm transition-all duration-200 shrink-0 cursor-pointer <?= $active_tab === 'sessions' ? 'text-primary! border-b-primary!' : '' ?>" onclick="switchTab('sessions')">Service Sessions</button>
    <button class="px-4 py-2.5 bg-transparent border-none border-b-2 border-transparent text-text-secondary hover:text-text-primary font-heading font-semibold text-sm transition-all duration-200 shrink-0 cursor-pointer <?= $active_tab === 'services' ? 'text-primary! border-b-primary!' : '' ?>" onclick="switchTab('services')">Service Types</button>
    <button class="px-4 py-2.5 bg-transparent border-none border-b-2 border-transparent text-text-secondary hover:text-text-primary font-heading font-semibold text-sm transition-all duration-200 shrink-0 cursor-pointer <?= $active_tab === 'departments' ? 'text-primary! border-b-primary!' : '' ?>" onclick="switchTab('departments')">Departments</button>
    <button class="px-4 py-2.5 bg-transparent border-none border-b-2 border-transparent text-text-secondary hover:text-text-primary font-heading font-semibold text-sm transition-all duration-200 shrink-0 cursor-pointer <?= $active_tab === 'cells' ? 'text-primary! border-b-primary!' : '' ?>" onclick="switchTab('cells')">Home Cells</button>
    <button class="px-4 py-2.5 bg-transparent border-none border-b-2 border-transparent text-text-secondary hover:text-text-primary font-heading font-semibold text-sm transition-all duration-200 shrink-0 cursor-pointer <?= $active_tab === 'households' ? 'text-primary! border-b-primary!' : '' ?>" onclick="switchTab('households')">Households</button>
    <button class="px-4 py-2.5 bg-transparent border-none border-b-2 border-transparent text-text-secondary hover:text-text-primary font-heading font-semibold text-sm transition-all duration-200 shrink-0 cursor-pointer <?= $active_tab === 'users' ? 'text-primary! border-b-primary!' : '' ?>" onclick="switchTab('users')">User Accounts</button>
    <button class="px-4 py-2.5 bg-transparent border-none border-b-2 border-transparent text-text-secondary hover:text-text-primary font-heading font-semibold text-sm transition-all duration-200 shrink-0 cursor-pointer <?= $active_tab === 'system' ? 'text-primary! border-b-primary!' : '' ?>" onclick="switchTab('system')">Site Settings</button>
    <button class="px-4 py-2.5 bg-transparent border-none border-b-2 border-transparent text-text-secondary hover:text-text-primary font-heading font-semibold text-sm transition-all duration-200 shrink-0 cursor-pointer <?= $active_tab === 'audit' ? 'text-primary! border-b-primary!' : '' ?>" onclick="switchTab('audit')">Audit Logs</button>
</div>

<!-- Tab: Service Sessions Control -->
<div id="tabContent-sessions" class="settings-tab-content <?= $active_tab === 'sessions' ? 'block' : 'hidden' ?>">
    <div class="max-w-[480px] mx-auto">
        <div class="bg-bg-surface backdrop-blur-md border border-border-custom rounded-xl p-6 sm:p-7">
            <div class="flex justify-between items-center mb-5 border-b border-border-custom pb-3">
                <h2 class="font-heading font-bold text-base sm:text-lg text-white">Active Service Session</h2>
            </div>
            
            <?php if ($active_session): ?>
                <div class="bg-secondary/10 border border-secondary/35 p-6 rounded-md mb-5 text-center flex flex-col items-center">
                    <span class="inline-block bg-secondary/20 text-secondary text-2xs px-2.5 py-1 rounded font-bold uppercase tracking-wider mb-3">LIVE SESSION RUNNING</span>
                    <h3 class="text-lg font-bold text-white mb-1">
                        <?= htmlspecialchars($active_session['service_name']) ?>
                    </h3>
                    <p class="text-xs text-text-secondary mb-5">
                        Started on <?= date('l, M d, Y', strtotime($active_session['date'])) ?>
                    </p>
                    
                    <div class="flex gap-3 justify-center w-full">
                        <a href="attendance" class="bg-primary hover:bg-opacity-90 text-white font-semibold text-xs px-4 py-2.5 rounded shadow-md active:scale-95 transition-all duration-150 flex items-center justify-center gap-1.5" style="text-decoration: none;">
                            Take Attendance
                        </a>
                        
                        <form method="POST" action="settings?action=close_session" class="m-0">
                            <button type="submit" class="bg-bg-surface-solid text-danger border border-danger/30 hover:bg-danger/10 font-semibold text-xs px-4 py-2.5 rounded transition-all duration-150 cursor-pointer" onclick="return confirm('Are you sure you want to close this service session? After closing, attendance for this session can no longer be updated live.')">
                                Close Session
                            </button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white/[0.015] border border-border-custom p-6 rounded-md mb-5 text-center">
                    <p class="text-xs text-text-secondary">No active service session is running. Start one below to begin taking attendance.</p>
                </div>
                
                <?php if (empty($services)): ?>
                    <p class="text-text-muted text-xs text-center py-4">Please define at least one service type in the <strong class="text-primary">Service Types</strong> tab before starting a session.</p>
                <?php else: ?>
                    <form method="POST" action="settings?action=start_session" class="flex flex-col gap-4">
                        <div class="flex flex-col gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label for="sessServiceId" class="text-xs font-semibold text-text-secondary">Select Service Type</label>
                                <select name="service_id" id="sessServiceId" required class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                                    <option value="">-- Choose Service --</option>
                                    <?php foreach ($services as $srv): ?>
                                        <option value="<?= $srv['id'] ?>"><?= htmlspecialchars($srv['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label for="sessDate" class="text-xs font-semibold text-text-secondary">Session Date</label>
                                <input type="date" name="date" id="sessDate" required value="<?= date('Y-m-d') ?>" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                            </div>
                        </div>
                        <button type="submit" class="w-full bg-success hover:bg-opacity-90 text-white font-semibold text-sm py-2.5 px-4 rounded shadow-md active:scale-98 transition-all duration-150 cursor-pointer flex justify-center items-center mt-2">
                            Start Service Session
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Tab 1: Service Types Management -->
<div id="tabContent-services" class="settings-tab-content <?= $active_tab === 'services' ? 'block' : 'hidden' ?>">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Add/Edit Service Form -->
        <div class="lg:col-span-1 bg-bg-surface backdrop-blur-md border border-border-custom rounded-xl p-6 flex flex-col gap-4 self-start">
            <div class="flex justify-between items-center border-b border-border-custom pb-3">
                <h2 class="font-heading font-bold text-base sm:text-lg text-white" id="srvFormTitle">Add Service Type</h2>
            </div>
            <form id="srvForm" method="POST" action="settings?action=add_service" class="flex flex-col gap-4">
                <?php render_csrf_input(); ?>
                <input type="hidden" name="id" id="formSrvId">
                <div class="flex flex-col gap-1.5">
                    <label for="srvName" class="text-xs font-semibold text-text-secondary">Service Name</label>
                    <input type="text" name="service_name" id="srvName" required placeholder="e.g. Wednesday Communion Service" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="srvDesc" class="text-xs font-semibold text-text-secondary">Description</label>
                    <textarea name="service_description" id="srvDesc" rows="3" placeholder="Brief description of when this service runs..." class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm"></textarea>
                </div>
                <div class="flex gap-2.5 mt-2">
                    <button type="submit" id="srvSubmitBtn" class="bg-success text-white font-semibold text-xs px-4 py-2.5 rounded hover:bg-opacity-90 active:scale-95 transition-all duration-150 cursor-pointer flex-grow justify-center">Save Service Type</button>
                    <button type="button" id="srvCancelBtn" class="bg-bg-surface-solid text-text-primary border border-border-custom hover:bg-border-custom-hover hover:text-white font-semibold text-xs px-4 py-2.5 rounded transition-all duration-150 cursor-pointer hidden justify-center" onclick="resetSrvForm()">Cancel</button>
                </div>
            </form>
        </div>
        
        <!-- List of Service Types -->
        <div class="lg:col-span-2 bg-bg-surface backdrop-blur-md border border-border-custom rounded-xl p-6 flex flex-col gap-4 overflow-x-auto">
            <div class="flex justify-between items-center border-b border-border-custom pb-3">
                <h2 class="font-heading font-bold text-base sm:text-lg text-white">Current Service Definitions</h2>
            </div>
            <div class="w-full max-w-full">
                <?php if (empty($services)): ?>
                    <p class="text-text-muted text-xs text-center py-8">No service types defined.</p>
                <?php else: ?>
                    <table class="w-full border-collapse text-left min-w-[500px]">
                        <thead>
                            <tr class="border-b border-border-custom">
                                <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Name & Description</th>
                                <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $srv): ?>
                                <tr class="border-b border-white/[0.02] hover:bg-white/[0.01] transition-all">
                                    <td class="p-3">
                                        <strong class="text-sm text-white block leading-snug"><?= htmlspecialchars($srv['name']) ?></strong>
                                        <div class="text-xs text-text-secondary mt-0.5"><?= htmlspecialchars($srv['description'] ?: 'No description.') ?></div>
                                    </td>
                                    <td class="p-3 text-right">
                                        <div class="inline-flex gap-1.5">
                                            <button class="bg-bg-surface-solid text-text-primary border border-border-custom hover:bg-border-custom-hover hover:text-white font-semibold text-xs px-2.5 py-1.5 rounded transition-all duration-150 cursor-pointer" onclick='openEditSrvMode(<?= json_encode($srv) ?>)'>Edit</button>
                                            <a href="settings?action=delete_service&id=<?= $srv['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="bg-bg-surface-solid border border-border-custom hover:border-danger/30 hover:bg-danger/10 text-text-secondary hover:text-danger font-semibold text-xs px-2.5 py-1.5 rounded transition-all duration-150 text-center" onclick="return confirm('Deleting this service will permanently delete all logged sessions and attendance mapped to it. Continue?')" style="text-decoration: none;">Delete</a>
                                        </div>
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
<div id="tabContent-departments" class="settings-tab-content <?= $active_tab === 'departments' ? 'block' : 'hidden' ?>">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Add/Edit Department Form -->
        <div class="lg:col-span-1 bg-bg-surface backdrop-blur-md border border-border-custom rounded-xl p-6 flex flex-col gap-4 self-start">
            <div class="flex justify-between items-center border-b border-border-custom pb-3">
                <h2 class="font-heading font-bold text-base sm:text-lg text-white" id="deptFormTitle">Create Department</h2>
            </div>
            <form id="deptForm" method="POST" action="settings?action=add_dept" class="flex flex-col gap-4">
                <?php render_csrf_input(); ?>
                <input type="hidden" name="id" id="formDeptId">
                <div class="flex flex-col gap-1.5">
                    <label for="deptName" class="text-xs font-semibold text-text-secondary">Department Name</label>
                    <input type="text" name="dept_name" id="deptName" required placeholder="e.g. Media & Tech Team" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="deptDesc" class="text-xs font-semibold text-text-secondary">Description</label>
                    <textarea name="dept_description" id="deptDesc" rows="3" placeholder="Brief description of workforce cohort role..." class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm"></textarea>
                </div>
                <div class="flex gap-2.5 mt-2">
                    <button type="submit" id="deptSubmitBtn" class="bg-success text-white font-semibold text-xs px-4 py-2.5 rounded hover:bg-opacity-90 active:scale-95 transition-all duration-150 cursor-pointer flex-grow justify-center">Save Department</button>
                    <button type="button" id="deptCancelBtn" class="bg-bg-surface-solid text-text-primary border border-border-custom hover:bg-border-custom-hover hover:text-white font-semibold text-xs px-4 py-2.5 rounded transition-all duration-150 cursor-pointer hidden justify-center" onclick="resetDeptForm()">Cancel</button>
                </div>
            </form>
        </div>
        
        <!-- List of Departments -->
        <div class="lg:col-span-2 bg-bg-surface backdrop-blur-md border border-border-custom rounded-xl p-6 flex flex-col gap-4 overflow-x-auto">
            <div class="flex justify-between items-center border-b border-border-custom pb-3">
                <h2 class="font-heading font-bold text-base sm:text-lg text-white">Current Department Structures</h2>
            </div>
            <div class="w-full max-w-full">
                <?php if (empty($departments)): ?>
                    <p class="text-text-muted text-xs text-center py-8">No departments defined.</p>
                <?php else: ?>
                    <table class="w-full border-collapse text-left min-w-[500px]">
                        <thead>
                            <tr class="border-b border-border-custom">
                                <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Name & Description</th>
                                <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departments as $dept): ?>
                                <tr class="border-b border-white/[0.02] hover:bg-white/[0.01] transition-all">
                                    <td class="p-3">
                                        <strong class="text-sm text-white block leading-snug"><?= htmlspecialchars($dept['name']) ?></strong>
                                        <div class="text-xs text-text-secondary mt-0.5"><?= htmlspecialchars($dept['description'] ?: 'No description.') ?></div>
                                    </td>
                                    <td class="p-3 text-right">
                                        <?php if ($dept['name'] !== 'None'): ?>
                                            <div class="inline-flex gap-1.5">
                                                <button class="bg-bg-surface-solid text-text-primary border border-border-custom hover:bg-border-custom-hover hover:text-white font-semibold text-xs px-2.5 py-1.5 rounded transition-all duration-150 cursor-pointer" onclick='openEditDeptMode(<?= json_encode($dept) ?>)'>Edit</button>
                                                <a href="settings?action=delete_dept&id=<?= $dept['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="bg-bg-surface-solid border border-border-custom hover:border-danger/30 hover:bg-danger/10 text-text-secondary hover:text-danger font-semibold text-xs px-2.5 py-1.5 rounded transition-all duration-150 text-center" onclick="return confirm('Are you sure you want to delete this department? All assigned members will have their department reset to None.')" style="text-decoration: none;">Delete</a>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-xs text-text-muted italic pr-2">Protected System Default</span>
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

<!-- Tab: Home Cells Management -->
<div id="tabContent-cells" class="settings-tab-content <?= $active_tab === 'cells' ? 'block' : 'hidden' ?>">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Add/Edit Cell Form -->
        <div class="lg:col-span-1 bg-bg-surface backdrop-blur-md border border-border-custom rounded-xl p-6 flex flex-col gap-4 self-start">
            <div class="flex justify-between items-center border-b border-border-custom pb-3">
                <h2 class="font-heading font-bold text-base sm:text-lg text-white" id="cellFormTitle">Create Cell Group</h2>
            </div>
            <form id="cellForm" method="POST" action="settings?action=add_cell" class="flex flex-col gap-4">
                <?php render_csrf_input(); ?>
                <input type="hidden" name="id" id="formCellId">
                <div class="flex flex-col gap-1.5">
                    <label for="cellName" class="text-xs font-semibold text-text-secondary">Cell Group Name</label>
                    <input type="text" name="cell_name" id="cellName" required placeholder="e.g. Grace Center Cell" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="cellLeader" class="text-xs font-semibold text-text-secondary">Cell Leader</label>
                    <select name="leader_id" id="cellLeader" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                        <option value="">-- No Leader Assigned --</option>
                        <?php foreach ($all_members as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="cellLocation" class="text-xs font-semibold text-text-secondary">Location / Neighborhood</label>
                    <input type="text" name="location" id="cellLocation" placeholder="e.g. East Legon, Accra" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="cellDay" class="text-xs font-semibold text-text-secondary">Meeting Day & Time</label>
                    <input type="text" name="meeting_day" id="cellDay" placeholder="e.g. Friday 7:00 PM" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                </div>
                <div class="flex gap-2.5 mt-2">
                    <button type="submit" id="cellSubmitBtn" class="bg-success text-white font-semibold text-xs px-4 py-2.5 rounded hover:bg-opacity-90 active:scale-95 transition-all duration-150 cursor-pointer flex-grow justify-center">Save Cell Group</button>
                    <button type="button" id="cellCancelBtn" class="bg-bg-surface-solid text-text-primary border border-border-custom hover:bg-border-custom-hover hover:text-white font-semibold text-xs px-4 py-2.5 rounded transition-all duration-150 cursor-pointer hidden justify-center" onclick="resetCellForm()">Cancel</button>
                </div>
            </form>
        </div>
        
        <!-- List of Home Cells -->
        <div class="lg:col-span-2 bg-bg-surface backdrop-blur-md border border-border-custom rounded-xl p-6 flex flex-col gap-4 overflow-x-auto">
            <div class="flex justify-between items-center border-b border-border-custom pb-3">
                <h2 class="font-heading font-bold text-base sm:text-lg text-white">Current Cell Fellowships</h2>
            </div>
            <div class="w-full max-w-full">
                <?php if (empty($cells)): ?>
                    <p class="text-text-muted text-xs text-center py-8">No Home Cell fellowships defined.</p>
                <?php else: ?>
                    <table class="w-full border-collapse text-left min-w-[650px]">
                        <thead>
                            <tr class="border-b border-border-custom">
                                <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Cell Group & Location</th>
                                <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Meeting Info</th>
                                <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Leader</th>
                                <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cells as $c): ?>
                                <tr class="border-b border-white/[0.02] hover:bg-white/[0.01] transition-all">
                                    <td class="p-3">
                                        <strong class="text-sm text-white block leading-snug"><?= htmlspecialchars($c['name']) ?></strong>
                                        <div class="text-xs text-text-secondary mt-0.5"><?= htmlspecialchars($c['location'] ?: 'No location.') ?></div>
                                    </td>
                                    <td class="p-3 text-sm text-text-primary font-medium"><?= htmlspecialchars($c['meeting_day'] ?: 'Not set.') ?></td>
                                    <td class="p-3 text-sm text-text-primary"><?= htmlspecialchars($c['leader_name'] ?: 'No leader.') ?></td>
                                    <td class="p-3 text-right">
                                        <div class="inline-flex gap-1.5">
                                            <button class="bg-bg-surface-solid text-text-primary border border-border-custom hover:bg-border-custom-hover hover:text-white font-semibold text-xs px-2.5 py-1.5 rounded transition-all duration-150 cursor-pointer" onclick='openEditCellMode(<?= json_encode($c) ?>)'>Edit</button>
                                            <a href="settings?action=delete_cell&id=<?= $c['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="bg-bg-surface-solid border border-border-custom hover:border-danger/30 hover:bg-danger/10 text-text-secondary hover:text-danger font-semibold text-xs px-2.5 py-1.5 rounded transition-all duration-150 text-center" onclick="return confirm('Are you sure you want to delete this Home Cell group?')" style="text-decoration: none;">Delete</a>
                                        </div>
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

<!-- Tab: Households Management -->
<div id="tabContent-households" class="settings-tab-content <?= $active_tab === 'households' ? 'block' : 'hidden' ?>">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Add/Edit Household Form -->
        <div class="lg:col-span-1 bg-bg-surface backdrop-blur-md border border-border-custom rounded-xl p-6 flex flex-col gap-4 self-start">
            <div class="flex justify-between items-center border-b border-border-custom pb-3">
                <h2 class="font-heading font-bold text-base sm:text-lg text-white" id="houseFormTitle">Create Household</h2>
            </div>
            <form id="houseForm" method="POST" action="settings?action=add_household" class="flex flex-col gap-4">
                <?php render_csrf_input(); ?>
                <input type="hidden" name="id" id="formHouseId">
                <div class="flex flex-col gap-1.5">
                    <label for="houseName" class="text-xs font-semibold text-text-secondary">Household Family Name</label>
                    <input type="text" name="household_name" id="houseName" required placeholder="e.g. The Boateng Family" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="houseAddress" class="text-xs font-semibold text-text-secondary">Primary Address</label>
                    <textarea name="address" id="houseAddress" rows="3" placeholder="Family residence address..." class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm"></textarea>
                </div>
                <div class="flex gap-2.5 mt-2">
                    <button type="submit" id="houseSubmitBtn" class="bg-success text-white font-semibold text-xs px-4 py-2.5 rounded hover:bg-opacity-90 active:scale-95 transition-all duration-150 cursor-pointer flex-grow justify-center">Save Household</button>
                    <button type="button" id="houseCancelBtn" class="bg-bg-surface-solid text-text-primary border border-border-custom hover:bg-border-custom-hover hover:text-white font-semibold text-xs px-4 py-2.5 rounded transition-all duration-150 cursor-pointer hidden justify-center" onclick="resetHouseForm()">Cancel</button>
                </div>
            </form>
        </div>
        
        <!-- List of Households -->
        <div class="lg:col-span-2 bg-bg-surface backdrop-blur-md border border-border-custom rounded-xl p-6 flex flex-col gap-4 overflow-x-auto">
            <div class="flex justify-between items-center border-b border-border-custom pb-3">
                <h2 class="font-heading font-bold text-base sm:text-lg text-white">Current Household Cohorts</h2>
            </div>
            <div class="w-full max-w-full">
                <?php if (empty($households)): ?>
                    <p class="text-text-muted text-xs text-center py-8">No household cohorts defined.</p>
                <?php else: ?>
                    <table class="w-full border-collapse text-left min-w-[500px]">
                        <thead>
                            <tr class="border-b border-border-custom">
                                <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Household</th>
                                <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Primary Residence</th>
                                <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($households as $h): ?>
                                <tr class="border-b border-white/[0.02] hover:bg-white/[0.01] transition-all">
                                    <td class="p-3 text-sm font-semibold text-white"><?= htmlspecialchars($h['name']) ?></td>
                                    <td class="p-3 text-sm text-text-primary truncate max-w-xs" title="<?= htmlspecialchars($h['address']) ?>"><?= htmlspecialchars($h['address'] ?: 'No address logged.') ?></td>
                                    <td class="p-3 text-right">
                                        <div class="inline-flex gap-1.5">
                                            <button class="bg-bg-surface-solid text-text-primary border border-border-custom hover:bg-border-custom-hover hover:text-white font-semibold text-xs px-2.5 py-1.5 rounded transition-all duration-150 cursor-pointer" onclick='openEditHouseMode(<?= json_encode($h) ?>)'>Edit</button>
                                            <a href="settings?action=delete_household&id=<?= $h['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="bg-bg-surface-solid border border-border-custom hover:border-danger/30 hover:bg-danger/10 text-text-secondary hover:text-danger font-semibold text-xs px-2.5 py-1.5 rounded transition-all duration-150 text-center" onclick="return confirm('Are you sure you want to delete this household? Linked members will be detached.')" style="text-decoration: none;">Delete</a>
                                        </div>
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
<div id="tabContent-users" class="settings-tab-content <?= $active_tab === 'users' ? 'block' : 'hidden' ?>">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Add/Edit User Form -->
        <div class="lg:col-span-1 bg-bg-surface backdrop-blur-md border border-border-custom rounded-xl p-6 flex flex-col gap-4 self-start">
            <div class="flex justify-between items-center border-b border-border-custom pb-3">
                <h2 class="font-heading font-bold text-base sm:text-lg text-white" id="userFormTitle">Register New User</h2>
            </div>
            <form id="userForm" method="POST" action="settings?action=add_user" class="flex flex-col gap-4">
                <?php render_csrf_input(); ?>
                <input type="hidden" name="id" id="formUserId">
                
                <div class="flex flex-col gap-1.5">
                    <label for="formUserName" class="text-xs font-semibold text-text-secondary">Display Name</label>
                    <input type="text" name="name" id="formUserName" required placeholder="e.g. John Doe" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="formUserUsername" class="text-xs font-semibold text-text-secondary">Username</label>
                    <input type="text" name="username" id="formUserUsername" required placeholder="e.g. johndoe" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="formUserPassword" id="formUserPasswordLabel" class="text-xs font-semibold text-text-secondary">Password</label>
                    <input type="password" name="password" id="formUserPassword" required placeholder="Enter password..." class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                </div>
                
                <div class="flex gap-2.5 mt-2">
                    <button type="submit" id="userSubmitBtn" class="bg-success text-white font-semibold text-xs px-4 py-2.5 rounded hover:bg-opacity-90 active:scale-95 transition-all duration-150 cursor-pointer flex-grow justify-center">Save User</button>
                    <button type="button" id="userCancelBtn" class="bg-bg-surface-solid text-text-primary border border-border-custom hover:bg-border-custom-hover hover:text-white font-semibold text-xs px-4 py-2.5 rounded transition-all duration-150 cursor-pointer hidden justify-center" onclick="resetUserForm()">Cancel</button>
                </div>
            </form>
        </div>
        
        <!-- List of Users -->
        <div class="lg:col-span-2 bg-bg-surface backdrop-blur-md border border-border-custom rounded-xl p-6 flex flex-col gap-4 overflow-x-auto">
            <div class="flex justify-between items-center border-b border-border-custom pb-3">
                <h2 class="font-heading font-bold text-base sm:text-lg text-white">Active User Accounts</h2>
            </div>
            <div class="w-full max-w-full">
                <?php if (empty($users)): ?>
                    <p class="text-text-muted text-xs text-center py-8">No user accounts found.</p>
                <?php else: ?>
                    <table class="w-full border-collapse text-left min-w-[500px]">
                        <thead>
                            <tr class="border-b border-border-custom">
                                <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Name & Username</th>
                                <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Created Date</th>
                                <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $usr): ?>
                                <tr class="border-b border-white/[0.02] hover:bg-white/[0.01] transition-all">
                                    <td class="p-3">
                                        <strong class="text-sm text-white block leading-snug"><?= htmlspecialchars($usr['name'] ?: 'No Name') ?></strong>
                                        <div class="text-xs text-text-secondary mt-0.5">@<?= htmlspecialchars($usr['username']) ?></div>
                                    </td>
                                    <td class="p-3 text-sm text-text-primary font-medium"><?= date('M d, Y', strtotime($usr['created_at'])) ?></td>
                                    <td class="p-3 text-right">
                                        <?php if ($usr['id'] !== $_SESSION['user_id']): ?>
                                            <div class="inline-flex gap-1.5">
                                                <button class="bg-bg-surface-solid text-text-primary border border-border-custom hover:bg-border-custom-hover hover:text-white font-semibold text-xs px-2.5 py-1.5 rounded transition-all duration-150 cursor-pointer" onclick='openEditUserMode(<?= json_encode($usr) ?>)'>Edit</button>
                                                <a href="settings?action=delete_user&id=<?= $usr['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="bg-bg-surface-solid border border-border-custom hover:border-danger/30 hover:bg-danger/10 text-text-secondary hover:text-danger font-semibold text-xs px-2.5 py-1.5 rounded transition-all duration-150 text-center" onclick="return confirm('Are you sure you want to permanently delete this user account?')" style="text-decoration: none;">Delete</a>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-xs text-text-muted italic pr-2">Active Session</span>
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
<div id="tabContent-system" class="settings-tab-content <?= $active_tab === 'system' ? 'block' : 'hidden' ?>">
    <div class="max-w-[640px] mx-auto bg-bg-surface backdrop-blur-md border border-border-custom rounded-xl p-6 sm:p-8">
        <div class="flex justify-between items-center mb-6 border-b border-border-custom pb-3">
            <h2 class="font-heading font-bold text-base sm:text-lg text-white flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Configure Application Settings
            </h2>
        </div>
        
        <form method="POST" action="settings?action=update_settings" enctype="multipart/form-data" class="flex flex-col gap-4">
            <?php render_csrf_input(); ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label for="setSystemName" class="text-xs font-semibold text-text-secondary">System Name</label>
                    <input type="text" name="system_name" id="setSystemName" required value="<?= htmlspecialchars($GLOBALS['site_settings']['system_name'] ?? '') ?>" placeholder="e.g. BYC DATA CENTER" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                </div>
                
                <div class="flex flex-col gap-1.5">
                    <label for="setOrgName" class="text-xs font-semibold text-text-secondary">Organization Name</label>
                    <input type="text" name="organization_name" id="setOrgName" required value="<?= htmlspecialchars($GLOBALS['site_settings']['organization_name'] ?? '') ?>" placeholder="e.g. Beersheba Youth Church" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label for="setContactEmail" class="text-xs font-semibold text-text-secondary">Contact Email</label>
                    <input type="email" name="contact_email" id="setContactEmail" value="<?= htmlspecialchars($GLOBALS['site_settings']['contact_email'] ?? '') ?>" placeholder="e.g. info@byc.org" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                </div>
                
                <div class="flex flex-col gap-1.5">
                    <label for="setContactPhone" class="text-xs font-semibold text-text-secondary">Contact Phone</label>
                    <input type="text" name="contact_phone" id="setContactPhone" value="<?= htmlspecialchars($GLOBALS['site_settings']['contact_phone'] ?? '') ?>" placeholder="e.g. 0241112222" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label for="setCurrency" class="text-xs font-semibold text-text-secondary">Currency Symbol</label>
                    <input type="text" name="currency" id="setCurrency" value="<?= htmlspecialchars($GLOBALS['site_settings']['currency'] ?? '') ?>" placeholder="e.g. GH₵, $" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
                </div>
                
                <div class="flex flex-col gap-1.5">
                    <label for="setTimezone" class="text-xs font-semibold text-text-secondary">Timezone</label>
                    <select name="timezone" id="setTimezone" class="w-full bg-black/30 border border-white/10 rounded-md px-3.5 py-2 text-white focus:border-primary focus:ring-4 focus:ring-primary/25 focus:bg-black/45 focus:outline-none transition-all duration-200 text-sm">
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

            <button type="submit" class="w-full bg-success hover:bg-opacity-90 text-white font-semibold text-sm py-2.5 px-4 rounded shadow-md active:scale-98 transition-all duration-150 cursor-pointer flex justify-center items-center gap-1.5 mt-4">
                <svg class="w-4 h-4 mr-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                Save Site Settings
            </button>
        </form>
    </div>
</div>

<!-- Tab 5: Audit Logs Viewer -->
<div id="tabContent-audit" class="settings-tab-content <?= $active_tab === 'audit' ? 'block' : 'hidden' ?>">
    <div class="bg-bg-surface backdrop-blur-md border border-border-custom rounded-xl p-6 sm:p-7 overflow-x-auto">
        <div class="flex justify-between items-center mb-6 border-b border-border-custom pb-3">
            <h2 class="font-heading font-bold text-base sm:text-lg text-white">Database Transaction & Audit Trail</h2>
        </div>
        <div class="w-full max-w-full max-h-[35rem] overflow-y-auto pr-1">
            <?php if (empty($audit_logs)): ?>
                <p class="text-text-muted text-xs text-center py-8">No audit trail events recorded yet.</p>
            <?php else: ?>
                <table class="w-full border-collapse text-left min-w-[750px]">
                    <thead>
                        <tr class="border-b border-border-custom">
                            <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">User (Admin)</th>
                            <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Action Taken</th>
                            <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Table</th>
                            <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Target ID</th>
                            <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Payload / Details</th>
                            <th class="p-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($audit_logs as $log): ?>
                            <tr class="border-b border-white/[0.02] hover:bg-white/[0.01] transition-all">
                                <td class="p-3">
                                    <strong class="text-sm text-white block leading-snug"><?= htmlspecialchars($log['user_name'] ?: 'System') ?></strong>
                                    <div class="text-2xs text-text-muted mt-0.5">@<?= htmlspecialchars($log['username'] ?: 'system') ?></div>
                                </td>
                                <td class="p-3">
                                    <span class="inline-block border text-3xs px-2 py-0.5 rounded font-semibold bg-primary/10 border-primary/35 text-primary uppercase tracking-wide"><?= htmlspecialchars($log['action']) ?></span>
                                </td>
                                <td class="p-3 text-xs font-mono text-text-secondary"><code><?= htmlspecialchars($log['target_table']) ?></code></td>
                                <td class="p-3 text-sm text-text-primary"><?= $log['target_id'] ?: '<span class="text-text-muted">N/A</span>' ?></td>
                                <td class="p-3 text-xs font-mono text-text-secondary max-w-[240px] truncate" title="<?= htmlspecialchars($log['details'] ?? '') ?>">
                                    <?= htmlspecialchars($log['details'] ?? '') ?>
                                </td>
                                <td class="p-3 text-xs text-text-muted font-medium"><?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function switchTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.settings-tab-content').forEach(el => {
        el.classList.add('hidden');
        el.classList.remove('block');
    });
    // Un-activate all tab buttons
    document.querySelectorAll('.settings-tab-btn').forEach(el => {
        el.classList.remove('text-primary!', 'border-b-primary!');
    });
    
    // Show active content and activate button
    const activeContent = document.getElementById('tabContent-' + tabName);
    if (activeContent) {
        activeContent.classList.remove('hidden');
        activeContent.classList.add('block');
    }
    
    // Find button to activate
    const buttons = document.querySelectorAll('.settings-tab-btn');
    buttons.forEach(btn => {
        if (btn.textContent.trim().toLowerCase().includes(tabName.toLowerCase())) {
            btn.classList.add('text-primary!', 'border-b-primary!');
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
    
    cancelBtn.classList.remove('hidden');
    cancelBtn.classList.add('inline-flex');
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
    
    cancelBtn.classList.add('hidden');
    cancelBtn.classList.remove('inline-flex');
    submitBtn.textContent = 'Save User';
}

function openEditSrvMode(srv) {
    const title = document.getElementById('srvFormTitle');
    const form = document.getElementById('srvForm');
    const cancelBtn = document.getElementById('srvCancelBtn');
    const submitBtn = document.getElementById('srvSubmitBtn');
    
    title.textContent = 'Edit Service Type';
    form.action = 'settings?action=edit_service';
    
    document.getElementById('formSrvId').value = srv.id;
    document.getElementById('srvName').value = srv.name || '';
    document.getElementById('srvDesc').value = srv.description || '';
    
    cancelBtn.classList.remove('hidden');
    cancelBtn.classList.add('inline-flex');
    submitBtn.textContent = 'Update Service Type';
}

function resetSrvForm() {
    const title = document.getElementById('srvFormTitle');
    const form = document.getElementById('srvForm');
    const cancelBtn = document.getElementById('srvCancelBtn');
    const submitBtn = document.getElementById('srvSubmitBtn');
    
    form.reset();
    title.textContent = 'Add Service Type';
    form.action = 'settings?action=add_service';
    document.getElementById('formSrvId').value = '';
    
    cancelBtn.classList.add('hidden');
    cancelBtn.classList.remove('inline-flex');
    submitBtn.textContent = 'Save Service Type';
}

function openEditDeptMode(dept) {
    const title = document.getElementById('deptFormTitle');
    const form = document.getElementById('deptForm');
    const cancelBtn = document.getElementById('deptCancelBtn');
    const submitBtn = document.getElementById('deptSubmitBtn');
    
    title.textContent = 'Edit Department';
    form.action = 'settings?action=edit_dept';
    
    document.getElementById('formDeptId').value = dept.id;
    document.getElementById('deptName').value = dept.name || '';
    document.getElementById('deptDesc').value = dept.description || '';
    
    cancelBtn.classList.remove('hidden');
    cancelBtn.classList.add('inline-flex');
    submitBtn.textContent = 'Update Department';
}

function resetDeptForm() {
    const title = document.getElementById('deptFormTitle');
    const form = document.getElementById('deptForm');
    const cancelBtn = document.getElementById('deptCancelBtn');
    const submitBtn = document.getElementById('deptSubmitBtn');
    
    form.reset();
    title.textContent = 'Create Department';
    form.action = 'settings?action=add_dept';
    document.getElementById('formDeptId').value = '';
    
    cancelBtn.classList.add('hidden');
    cancelBtn.classList.remove('inline-flex');
    submitBtn.textContent = 'Save Department';
}

function openEditCellMode(cell) {
    const title = document.getElementById('cellFormTitle');
    const form = document.getElementById('cellForm');
    const cancelBtn = document.getElementById('cellCancelBtn');
    const submitBtn = document.getElementById('cellSubmitBtn');
    
    title.textContent = 'Edit Cell Group';
    form.action = 'settings?action=edit_cell';
    
    document.getElementById('formCellId').value = cell.id;
    document.getElementById('cellName').value = cell.name || '';
    document.getElementById('cellLeader').value = cell.leader_id || '';
    document.getElementById('cellLocation').value = cell.location || '';
    document.getElementById('cellDay').value = cell.meeting_day || '';
    
    cancelBtn.classList.remove('hidden');
    cancelBtn.classList.add('inline-flex');
    submitBtn.textContent = 'Update Cell Group';
}

function resetCellForm() {
    const title = document.getElementById('cellFormTitle');
    const form = document.getElementById('cellForm');
    const cancelBtn = document.getElementById('cellCancelBtn');
    const submitBtn = document.getElementById('cellSubmitBtn');
    
    form.reset();
    title.textContent = 'Create Cell Group';
    form.action = 'settings?action=add_cell';
    document.getElementById('formCellId').value = '';
    
    cancelBtn.classList.add('hidden');
    cancelBtn.classList.remove('inline-flex');
    submitBtn.textContent = 'Save Cell Group';
}

function openEditHouseMode(house) {
    const title = document.getElementById('houseFormTitle');
    const form = document.getElementById('houseForm');
    const cancelBtn = document.getElementById('houseCancelBtn');
    const submitBtn = document.getElementById('houseSubmitBtn');
    
    title.textContent = 'Edit Household';
    form.action = 'settings?action=edit_household';
    
    document.getElementById('formHouseId').value = house.id;
    document.getElementById('houseName').value = house.name || '';
    document.getElementById('houseAddress').value = house.address || '';
    
    cancelBtn.classList.remove('hidden');
    cancelBtn.classList.add('inline-flex');
    submitBtn.textContent = 'Update Household';
}

function resetHouseForm() {
    const title = document.getElementById('houseFormTitle');
    const form = document.getElementById('houseForm');
    const cancelBtn = document.getElementById('houseCancelBtn');
    const submitBtn = document.getElementById('houseSubmitBtn');
    
    form.reset();
    title.textContent = 'Create Household';
    form.action = 'settings?action=add_household';
    document.getElementById('formHouseId').value = '';
    
    cancelBtn.classList.add('hidden');
    cancelBtn.classList.remove('inline-flex');
    submitBtn.textContent = 'Save Household';
}
</script>

<?php
render_footer();
?>
