<?php
/**
 * Unified Authentication Page
 * Handles both user login and registration in a single page with mode switching.
 * Uses password_hash() for secure password storage (different from root login.php which uses MD5).
 */

session_start();

// Include configuration file
$config_path = realpath(__DIR__ . '/../includes/config.php');
if (file_exists($config_path)) {
    require_once $config_path;
}

// Include database connection from src/Core directory
// This uses a different database connection than the root connect.php
require_once '../src/Core/connect.php';

$error = "";
$success = "";
// Determine which mode to display: 'login' or 'register'
// Defaults to 'login' if no mode parameter is provided
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'login';

// Process form submissions (both login and registration)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- REGISTRATION HANDLING ---
    if (isset($_POST['action']) && $_POST['action'] == 'register') {

        // Sanitize user inputs using addslashes (basic SQL injection prevention)
        // Note: addslashes is less secure than prepared statements
        $username = addslashes($_POST['username']);
        $email = addslashes($_POST['email']);
        $password = $_POST['password'];
        $confirm = $_POST['confirm_password'];

        // Validate that password and confirmation match
        if ($password !== $confirm) {
            $error = "Passwords do not match.";
            $mode = 'register';
        }
        // Check minimum password length requirement (6 characters)
        elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
            $mode = 'register';
        } else {
            // Check if username or email already exists in database
            // This prevents duplicate accounts
            $check_sql = "SELECT user_id FROM users WHERE email = '$email' OR username = '$username'";
            $check_result = myQuery($check_sql);

            if ($check_result && $check_result->num_rows > 0) {
                $error = "Username or Email already exists.";
                $mode = 'register';
            } else {
                // Hash password using PHP's password_hash with default algorithm (bcrypt)
                // This is more secure than MD5 used in root register.php
                $pass_hash = password_hash($password, PASSWORD_DEFAULT);

                // Generate default avatar URL using placeholder service
                // Creates avatar with first letter of username as text
                $avatar = "https://placehold.co/100x100?text=" . strtoupper(substr($username, 0, 1));

                // Insert new user into database with hashed password and default avatar
                // NOW() function sets join_date to current timestamp
                $sql_insert = "INSERT INTO users (username, email, password_hash, join_date, profile_avatar) 
                               VALUES ('$username', '$email', '$pass_hash', NOW(), '$avatar')";

                myQuery($sql_insert);

                $success = "Account created! Please log in.";
                // Switch to login mode after successful registration
                $mode = 'login';
            }
        }
    }

    // --- LOGIN HANDLING ---
    elseif (isset($_POST['action']) && $_POST['action'] == 'login') {

        // Sanitize email/username input
        $email = addslashes($_POST['email']);
        $password = $_POST['password'];

        // Query database to find user by email OR username
        // This allows users to login with either their email or username
        $sql = "SELECT user_id, username, password_hash, is_admin, profile_avatar 
                FROM users 
                WHERE email = '$email' OR username = '$email'";

        $result = myQuery($sql);

        // Check if user was found in database
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Verify password against stored hash using password_verify
            // This works with password_hash() and handles bcrypt verification
            if (password_verify($password, $user['password_hash'])) {
                // Store user information in session for authentication
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'];

                // Redirect to external application URL after successful login
                // This appears to be a custom redirect for a specific deployment
                // Redirect to homepage using configured BASE_URL
                if (defined('BASE_URL')) {
                    header("Location: " . BASE_URL . "index.php");
                } else {
                    header("Location: ../index.php");
                }
                exit;
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "No account found.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Authentication - xGrab</title>
    <style>
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            color: #666;
        }

        .password-toggle:hover {
            color: #333;
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 40px;
        }

        .eye-closed {
            display: none;
        }
    </style>
</head>

<body style="font-family: sans-serif; background-color: #f0f2f5; margin: 0; padding: 0;">

    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center;">

        <div
            style="background-color: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 400px;">

            <?php if ($error): ?>
                <div
                    style="background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div
                    style="background-color: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center;">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($mode == 'register'): ?>

                <h2 style="text-align: center; margin-top: 0; color: #333;">Create Account</h2>
                <p style="text-align: center; color: #666; margin-bottom: 30px;">Join the community</p>

                <form method="POST" action="<?php echo defined('BASE_URL') ? BASE_URL : ''; ?>auth/index.php?mode=register">
                    <input type="hidden" name="action" value="register">

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; color: #333;">Username</label>
                        <input type="text" name="username" required
                            style="width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; color: #333;">Email Address</label>
                        <input type="email" name="email" required
                            style="width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; color: #333;">Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="reg_password" required minlength="6"
                                style="width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;">
                            <button type="button" class="password-toggle" onclick="togglePassword('reg_password', this)"
                                aria-label="Toggle password visibility">
                                <svg class="eye-open" width="20" height="20" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg class="eye-closed" width="20" height="20" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label style="display: block; margin-bottom: 5px; color: #333;">Confirm Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="confirm_password" id="reg_confirm_password" required minlength="6"
                                style="width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;">
                            <button type="button" class="password-toggle"
                                onclick="togglePassword('reg_confirm_password', this)"
                                aria-label="Toggle password visibility">
                                <svg class="eye-open" width="20" height="20" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg class="eye-closed" width="20" height="20" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit"
                        style="width: 100%; padding: 12px; background-color: #28a745; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; font-weight: bold;">
                        Sign Up
                    </button>
                </form>

                <div style="margin-top: 20px; text-align: center; font-size: 14px;">
                    Already have an account? <a
                        href="<?php echo defined('BASE_URL') ? BASE_URL : ''; ?>auth/index.php?mode=login"
                        style="color: #007bff; text-decoration: none;">Log in</a>
                </div>

            <?php else: ?>

                <h2 style="text-align: center; margin-top: 0; color: #333;">Welcome Back</h2>
                <p style="text-align: center; color: #666; margin-bottom: 30px;">Login to your account</p>

                <form method="POST" action="<?php echo defined('BASE_URL') ? BASE_URL : ''; ?>auth/index.php?mode=login">
                    <input type="hidden" name="action" value="login">

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px; color: #333;">Email or Username</label>
                        <input type="text" name="email" required
                            style="width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;">
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label style="display: block; margin-bottom: 5px; color: #333;">Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="login_password" required
                                style="width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;">
                            <button type="button" class="password-toggle" onclick="togglePassword('login_password', this)"
                                aria-label="Toggle password visibility">
                                <svg class="eye-open" width="20" height="20" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg class="eye-closed" width="20" height="20" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit"
                        style="width: 100%; padding: 12px; background-color: #007bff; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; font-weight: bold;">
                        Log In
                    </button>
                </form>

                <div style="margin-top: 20px; text-align: center; font-size: 14px;">
                    Don't have an account? <a
                        href="<?php echo defined('BASE_URL') ? BASE_URL : ''; ?>auth/index.php?mode=register"
                        style="color: #007bff; text-decoration: none;">Sign up</a>
                </div>

            <?php endif; ?>

        </div>
    </div>

    <script>
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const eyeOpen = button.querySelector('.eye-open');
            const eyeClosed = button.querySelector('.eye-closed');

            if (input.type === 'password') {
                input.type = 'text';
                eyeOpen.style.display = 'none';
                eyeClosed.style.display = 'block';
            } else {
                input.type = 'password';
                eyeOpen.style.display = 'block';
                eyeClosed.style.display = 'none';
            }
        }
    </script>

</body>

</html>