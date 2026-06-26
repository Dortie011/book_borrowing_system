<?php
session_start();
require_once 'db_connect.php';

// Handle JSON POST login request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';

    try {
        $db = getDB();
        // Look up user by student_id/username in the users table
        $stmt = $db->prepare("SELECT * FROM users WHERE student_id = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['role']     = $user['role'];
            $_SESSION['fullname'] = $user['fullname'];
            
            if ($user['role'] === 'admin') {
                echo json_encode(['success' => true, 'redirect' => 'admin/admin.php']);
            } else {
                $_SESSION['student_id'] = $user['student_id'];
                echo json_encode(['success' => true, 'redirect' => 'student/home.php']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid Student ID/Username or password.']);
        }
        $stmt->close();
        $db->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HTU Library – Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>

    <!-- ── Site Header ── -->
    <header class="site-header">
        <div class="header-left">
            <img src="images/htulogo.png" alt="HTU Logo">
            <div class="site-title">
                <h1>Holy Trinity University Library</h1>
                <p>Knowledge &amp; Discovery</p>
            </div>
        </div>
    </header>

    <!-- ── Main Content ── -->
    <main class="main-area">
        <div class="login-card">
            <img src="images/htulogo.png" alt="HTU Logo" class="card-logo">
            <h2>HTU Library Portal</h2>
            <span class="subtitle">Sign in to continue</span>

            <form id="login-form" onsubmit="login(event)">

                <!-- Username -->
                <div class="input-group">
                    <span class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#be3835">
                            <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                        </svg>
                    </span>
                    <input type="text" id="username" name="username" placeholder="Student ID or Username" autocomplete="username" required>
                </div>

                <!-- Password -->
                <div class="input-group">
                    <span class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#be3835">
                            <path d="M18 8h-1V6c0-2.8-2.2-5-5-5S7 3.2 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.7 1.4-3.1 3.1-3.1 1.7 0 3.1 1.4 3.1 3.1v2z"/>
                        </svg>
                    </span>
                    <input type="password" id="password" name="password" placeholder="Password" autocomplete="current-password" required>
                </div>

                <!-- Error -->
                <div id="error-msg"></div>

                <!-- Submit -->
                <button type="submit" class="btn-login">Sign In</button>
            </form>

            <div class="divider"></div>
            <p class="signup-text">
                Don't have an account? <a href="register.php">Sign up</a>
            </p>
        </div>
    </main>

    <script>
        async function login(event) {
            event.preventDefault();

            const username  = document.getElementById('username').value.trim();
            const password  = document.getElementById('password').value;
            const errorDiv  = document.getElementById('error-msg');
            const btn       = document.querySelector('.btn-login');

            errorDiv.style.display = 'none';
            btn.textContent = 'Signing in…';
            btn.disabled = true;

            try {
                const response = await fetch('login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password })
                });

                const result = await response.json();

                if (result.success) {
                    btn.textContent = 'Redirecting…';
                    window.location.href = result.redirect;
                } else {
                    errorDiv.textContent = result.message || 'Invalid credentials. Please try again.';
                    errorDiv.style.display = 'block';
                    btn.textContent = 'Sign In';
                    btn.disabled = false;
                }
            } catch (err) {
                errorDiv.textContent = 'Connection error. Please try again.';
                errorDiv.style.display = 'block';
                btn.textContent = 'Sign In';
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
