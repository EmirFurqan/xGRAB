<?php
session_start();
// Adjust this path if your folder structure is different
require_once '../src/Core/connect.php'; 

$error = "";
$success = "";
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'login'; 

// HANDLE FORM SUBMISSIONS
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- 1. HANDLE REGISTRATION ---
    if (isset($_POST['action']) && $_POST['action'] == 'register') {
        
        $username = addslashes($_POST['username']);
        $email = addslashes($_POST['email']);
        $password = $_POST['password'];
        $confirm = $_POST['confirm_password'];

        if ($password !== $confirm) {
            $error = "Passwords do not match.";
            $mode = 'register'; 
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
            $mode = 'register';
        } else {
            // Check existence
            $check_sql = "SELECT user_id FROM users WHERE email = '$email' OR username = '$username'";
            $check_result = myQuery($check_sql);

            if ($check_result && $check_result->num_rows > 0) {
                $error = "Username or Email already exists.";
                $mode = 'register';
            } else {
                // Create User
                $pass_hash = password_hash($password, PASSWORD_DEFAULT);
                $avatar = "https://placehold.co/100x100?text=" . strtoupper(substr($username, 0, 1));
                
                $sql_insert = "INSERT INTO users (username, email, password_hash, join_date, profile_avatar) 
                               VALUES ('$username', '$email', '$pass_hash', NOW(), '$avatar')";
                
                myQuery($sql_insert);
                
                $success = "Account created! Please log in.";
                $mode = 'login'; 
            }
        }
    }

    // --- 2. HANDLE LOGIN ---
    elseif (isset($_POST['action']) && $_POST['action'] == 'login') {
        
        $email = addslashes($_POST['email']);
        $password = $_POST['password'];

        $sql = "SELECT user_id, username, password_hash, is_admin, profile_avatar 
                FROM users 
                WHERE email = '$email' OR username = '$email'";
                
        $result = myQuery($sql);

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'];
                
                // --- UPDATED REDIRECT HERE ---
                header("Location: http://10.1.7.100:7777/gr2025-022.com");
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
    <title>Authentication - MovieDB</title>
</head>
<body style="font-family: sans-serif; background-color: #f0f2f5; margin: 0; padding: 0;">

    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center;">
        
        <div style="background-color: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 400px;">
            
            <?php if($error): ?>
                <div style="background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div style="background-color: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center;">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($mode == 'register'): ?>

                <h2 style="text-align: center; margin-top: 0; color: #333;">Create Account</h2>
                <p style="text-align: center; color: #666; margin-bottom: 30px;">Join the community</p>

                <form method="POST" action="auth.gr2025-022.com?mode=register">
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
                        <input type="password" name="password" required minlength="6"
                               style="width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;">
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label style="display: block; margin-bottom: 5px; color: #333;">Confirm Password</label>
                        <input type="password" name="confirm_password" required minlength="6"
                               style="width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;">
                    </div>

                    <button type="submit" 
                            style="width: 100%; padding: 12px; background-color: #28a745; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; font-weight: bold;">
                        Sign Up
                    </button>
                </form>

                <div style="margin-top: 20px; text-align: center; font-size: 14px;">
                    Already have an account? <a href="auth.gr2025-022.com?mode=login" style="color: #007bff; text-decoration: none;">Log in</a>
                </div>

            <?php else: ?>

                <h2 style="text-align: center; margin-top: 0; color: #333;">Welcome Back</h2>
                <p style="text-align: center; color: #666; margin-bottom: 30px;">Login to your account</p>

                <form method="POST" action="auth.gr2025-022.com?mode=login">
                    <input type="hidden" name="action" value="login">

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px; color: #333;">Email or Username</label>
                        <input type="text" name="email" required 
                               style="width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;">
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label style="display: block; margin-bottom: 5px; color: #333;">Password</label>
                        <input type="password" name="password" required 
                               style="width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;">
                    </div>

                    <button type="submit" 
                            style="width: 100%; padding: 12px; background-color: #007bff; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; font-weight: bold;">
                        Log In
                    </button>
                </form>

                <div style="margin-top: 20px; text-align: center; font-size: 14px;">
                    Don't have an account? <a href="auth.gr2025-022.com?mode=register" style="color: #007bff; text-decoration: none;">Sign up</a>
                </div>

            <?php endif; ?>

        </div>
    </div>

</body>
</html>