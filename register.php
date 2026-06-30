<?php
session_start();
require_once 'db_connect.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id  = trim($_POST['student_id']  ?? '');
    $fullname    = trim($_POST['fullname']     ?? '');
    $gender      = trim($_POST['gender']       ?? '');
    $email       = trim($_POST['email']        ?? '');
    $contact     = trim($_POST['contact']      ?? '');
    $birth_date  = trim($_POST['birth_date']   ?? '');
    $address     = trim($_POST['address']      ?? '');
    $password    = $_POST['password']          ?? '';
    $terms       = isset($_POST['terms']);

    // Basic validation
    if (!$student_id || !$fullname || !$email || !$password) {
        $error = 'Please fill in all required fields.';
    } elseif (!$terms) {
        $error = 'You must agree to the Terms and Conditions.';
    } else {
        if ($birth_date === '') {
            $birth_date = null;
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);

        try {
            $db = getDB();

            // Check duplicate student_id
            $check = $db->prepare("SELECT id FROM users WHERE student_id = ? LIMIT 1");
            $check->bind_param("s", $student_id);
            $check->execute();
            $check_res = $check->get_result();
            if ($check_res->num_rows > 0) {
                $error = 'Student ID already exists. Please use a different ID or login.';
                $check->close();
            } else {
                $check->close();
                $stmt = $db->prepare("
                    INSERT INTO users (student_id, fullname, gender, email, contact, birth_date, address, password, role, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'student', NOW())
                ");
                $stmt->bind_param("ssssssss", $student_id, $fullname, $gender, $email, $contact, $birth_date, $address, $hashed);
                if ($stmt->execute()) {
                    $stmt->close();
                    header('Location: login.php?registered=1');
                    exit;
                } else {
                    $error = 'Registration failed: ' . $stmt->error;
                    $stmt->close();
                }
            }
        } catch (Exception $e) {
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}

$showSuccess = isset($_GET['registered']) && $_GET['registered'] == '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HTU Library - Register</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="css/register.css">
</head>
<body>

    <!-- Site Header -->
    <header class="site-header">
        <div class="header-left">
            <img src="images/htulogo.png" alt="HTU Logo">
            <div class="site-title">
                <h1>Holy Trinity University Library</h1>
                <p>Knowledge &amp; Discovery</p>
            </div>
        </div>
        
    </header>

    <!-- Main content -->
    <div class="main-container">

        <!-- Form header -->
        <div class="form-header">
            <h2>Register Form</h2>
            <p>Fill in the details below to become a Trinitas Library borrower</p>
        </div>

        <!-- Success / Error banners -->
        <?php if ($showSuccess): ?>
            <div class="banner-success">
                &#10003; Registration successful! Please <a href="login.php" style="color:#155724;font-weight:700;">login</a>.
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="banner-error">
                &#9888; <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Form card -->
        <div class="form-card">
            <form method="POST" action="register.php">

                <p class="section-label">Personal Information</p>

                <!-- Student ID -->
                <div class="field-row">
                    <label for="student-id">Student ID <span style="color:#be3835">*</span></label>
                    <input type="text" id="student-id" name="student_id"
                           placeholder="e.g. 2024100949"
                           value="<?= htmlspecialchars($_POST['student_id'] ?? '') ?>"
                           required>
                </div>

                <!-- Full Name -->
                <div class="field-row">
                    <label for="fullname">Full Name <span style="color:#be3835">*</span></label>
                    <input type="text" id="fullname" name="fullname"
                           placeholder="Lastname, Firstname, Middlename"
                           value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>"
                           required>
                </div>

                <!-- Gender pills -->
                <div class="field-row">
                    <label>Gender</label>
                    <div class="gender-pills">
                        <input type="radio" id="gender-male" name="gender" value="Male"
                            <?= (($_POST['gender'] ?? '') === 'Male') ? 'checked' : '' ?>>
                        <label for="gender-male">Male</label>

                        <input type="radio" id="gender-female" name="gender" value="Female"
                            <?= (($_POST['gender'] ?? '') === 'Female') ? 'checked' : '' ?>>
                        <label for="gender-female">Female</label>
                    </div>
                </div>

                <!-- Email -->
                <div class="field-row">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email"
                           placeholder="yourname@example.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <!-- Contact -->
                <div class="field-row">
                    <label for="contact">Contact Number</label>
                    <input type="tel" id="contact" name="contact"
                           placeholder="09XXXXXXXXX"
                           value="<?= htmlspecialchars($_POST['contact'] ?? '') ?>">
                </div>

                <!-- Birth Date -->
                <div class="field-row">
                    <label for="birth_date">Birth Date</label>
                    <input type="date" id="birth_date" name="birth_date"
                           value="<?= htmlspecialchars($_POST['birth_date'] ?? '') ?>">
                </div>

                <!-- Address -->
                <div class="field-row">
                    <label for="address">Home Address</label>
                    <input type="text" id="address" name="address"
                           placeholder="House No., Street, Barangay, City"
                           value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
                </div>

                <div class="divider"></div>
                <p class="section-label">Account Credentials</p>



                <!-- Password -->
                <div class="field-row">
                    <label for="password">Password <span style="color:#be3835">*</span></label>
                    <input type="password" id="password" name="password"
                           placeholder="Create a strong password"
                           required>
                </div>

                <div class="divider"></div>

                <!-- Terms -->
                <div class="terms-row">
                    <input type="checkbox" id="terms" name="terms"
                           <?= isset($_POST['terms']) ? 'checked' : '' ?>>
                    <span>
                        By signing up you are agreeing to our
                        <a href="terms_and_conditions.html">Terms and Conditions</a>
                    </span>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-register">Register</button>

                <!-- Login link -->
                <p class="login-link">
                    Already have an account? <a href="login.php">Login here</a>
                </p>

            </form>
        </div><!-- /form-card -->
    </div><!-- /main-container -->

</body>
</html>
