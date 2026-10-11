<?php
// login.php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$demoUsers = [
    'head_office' => ['label' => 'Head Office Officer', 'role' => 'Head Office Officer', 'hint' => 'head123'],
    'harar_branch' => ['label' => 'Branch Officer', 'role' => 'Branch Officer', 'hint' => 'harar123'],
    'dept_manager' => ['label' => 'Department Manager', 'role' => 'Department Manager', 'hint' => 'manager123'],
    'records_officer' => ['label' => 'Records Officer', 'role' => 'Records Officer', 'hint' => 'records123'],
    'director' => ['label' => 'Director General', 'role' => 'Director General', 'hint' => 'director123'],
    'intermediary' => ['label' => 'Intermediary Officer', 'role' => 'Intermediary Officer', 'hint' => ''],
    'admin' => ['label' => 'System Administrator', 'role' => 'System Administrator', 'hint' => 'admin123'],
];

$stmt = $pdo->prepare("SELECT username, full_name, role FROM users WHERE is_active != 0 ORDER BY username ASC");
$stmt->execute();
$dbUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$loginOptions = $demoUsers;
foreach ($dbUsers as $user) {
    $username = $user['username'] ?? '';
    if ($username === '') continue;
    if (!isset($loginOptions[$username])) {
        $displayName = trim(($user['full_name'] ?? '') . ' (' . ($user['role'] ?? 'User') . ')');
        $loginOptions[$username] = [
            'label' => $displayName ?: ucfirst($username),
            'role' => ucfirst(str_replace('_', ' ', $user['role'] ?? 'User')),
            'hint' => ''
        ];
    }
}
ksort($loginOptions);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - NALA ERMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <div id="loginPage" class="login-page">
        <div class="login-card">
            <div class="logo">
                <img src="images/logo.png" alt="NALA Logo" class="h-12 mx-auto mb-2"
                     onerror="this.src='https://via.placeholder.com/150x50?text=NALA+ERMS'" />
                <h1>ERMS</h1>
                <p>National Archive and Library Services</p>
                <p class="text-sm">Enhanced Records Management System</p>
            </div>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm">
                    <i class="fas fa-exclamation-circle mr-1"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 text-sm">
                    <i class="fas fa-check-circle mr-1"></i> <?php echo htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                </div>
            <?php endif; ?>
            
            <form id="loginForm" action="actions/login_action.php" method="POST" autocomplete="off">
                <div class="form-group">
                    <label><i class="fas fa-user mr-1"></i> Select User / Username</label>
                    <select id="userQuickSelect" name="username">
                        <?php foreach ($loginOptions as $value => $option): ?>
                            <option value="<?= htmlspecialchars($value) ?>" <?= $value === 'head_office' ? 'selected' : '' ?>><?= htmlspecialchars($option['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock mr-1"></i> Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required class="pr-12" />
                        <button type="button" id="togglePassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-blue-600" aria-label="Show password" title="Show password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div id="passwordHint" class="password-hint">
                        <i class="fas fa-info-circle mr-1"></i>Hint: head123 / harar123 / manager123 / records123 / director123 / admin123
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-user-tag mr-1"></i> Role Assigned</label>
                    <input type="text" id="roleDisplayInput" class="font-semibold" value="Head Office Officer" readonly />
                </div>
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </button>
            </form>
            <p class="text-center text-xs text-gray-400 mt-4">
                <i class="fas fa-shield-alt mr-1"></i> Secured · ISO 15489 Compliant
            </p>
        </div>
    </div>
    
    <script>
        const userQuickSelect = document.getElementById('userQuickSelect');
        const passwordHint = document.getElementById('passwordHint');
        const roleDisplayInput = document.getElementById('roleDisplayInput');
        
        const userData = {
            'head_office': { role: 'Head Office Officer', pw: 'head123' },
            'harar_branch': { role: 'Branch Officer', pw: 'harar123' },
            'dept_manager': { role: 'Department Manager', pw: 'manager123' },
            'records_officer': { role: 'Records Officer', pw: 'records123' },
            'director': { role: 'Director General', pw: 'director123' },
            'intermediary': { role: 'Intermediary Officer', pw: '' },
            'admin': { role: 'System Administrator', pw: 'admin123' }
        };
        
        function updateLoginHint(val) {
            if (userData[val]) {
                roleDisplayInput.value = userData[val].role;
                passwordHint.innerHTML = `<i class="fas fa-info-circle mr-1"></i>Hint: Your password is <strong>${userData[val].pw}</strong>`;
            } else {
                roleDisplayInput.value = 'User Account';
                passwordHint.innerHTML = '<i class="fas fa-info-circle mr-1"></i>Hint: Use the password assigned to this user account.';
            }
        }
        
        userQuickSelect.addEventListener('change', function() {
            updateLoginHint(this.value);
        });
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            const showing = passwordInput.type === 'text';
            passwordInput.type = showing ? 'password' : 'text';
            icon.className = showing ? 'fas fa-eye' : 'fas fa-eye-slash';
            this.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            this.setAttribute('title', showing ? 'Show password' : 'Hide password');
        });
        userQuickSelect.dispatchEvent(new Event('change'));
    </script>
</body>
</html>
