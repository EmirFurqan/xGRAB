<?php
session_start();
require("../connect.php");

$error = "";
$success = "";
$show_form = true;

// If token provided, show reset form
if (isset($_GET['token'])) {
    $token = escapeString($_GET['token']);

    // Check token validity
    $token_sql = "SELECT * FROM password_reset_tokens 
                  WHERE token = '$token' AND expires_at > NOW() AND used = FALSE";
    $token_result = myQuery($token_sql);

    if (mysqli_num_rows($token_result) == 0) {
        $error = "Invalid or expired reset token";
        $show_form = false;
    } else {
        $token_data = mysqli_fetch_assoc($token_result);
        $user_id = $token_data['user_id'];

        // Handle password reset
        if (isset($_POST['submit'])) {
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            // Validate password
            if (strlen($new_password) < 8) {
                $error = "Password must be at least 8 characters long";
            } elseif (!preg_match('/[A-Z]/', $new_password)) {
                $error = "Password must contain at least one uppercase letter";
            } elseif (!preg_match('/[0-9]/', $new_password)) {
                $error = "Password must contain at least one number";
            } elseif (!preg_match('/[^A-Za-z0-9]/', $new_password)) {
                $error = "Password must contain at least one special character";
            } elseif ($new_password !== $confirm_password) {
                $error = "Passwords do not match";
            } else {
                // Update password
                $new_password_hash = md5($new_password);
                $update_sql = "UPDATE users SET password_hash = '$new_password_hash' WHERE user_id = $user_id";
                if (myQuery($update_sql)) {
                    // Mark token as used
                    $mark_used_sql = "UPDATE password_reset_tokens SET used = TRUE WHERE token = '$token'";
                    myQuery($mark_used_sql);
                    $success = "Password reset successfully! You can now login.";
                    $show_form = false;
                } else {
                    $error = "Failed to reset password";
                }
            }
        }
    }
}
// If email provided, generate reset token
elseif (isset($_POST['request_reset'])) {
    $email = escapeString($_POST['email']);

    // Check if email exists
    $user_sql = "SELECT * FROM users WHERE email = '$email'";
    $user_result = myQuery($user_sql);

    if (mysqli_num_rows($user_result) == 0) {
        $error = "Email not found";
    } else {
        $user = mysqli_fetch_assoc($user_result);
        $user_id = $user['user_id'];

        // Generate token
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Insert token
        $insert_sql = "INSERT INTO password_reset_tokens (user_id, token, expires_at) 
                       VALUES ($user_id, '$token', '$expires_at')";
        if (myQuery($insert_sql)) {
            // Display reset link (simplified - no email)
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=$token";
            $success = "Password reset link generated. Click here to reset: <a href='$reset_link' class='underline hover:text-green-300 transition-colors duration-300'>$reset_link</a>";
        } else {
            $error = "Failed to generate reset token";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - xGrab</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>

<body class="bg-gray-900 min-h-screen flex items-center justify-center">
    <div class="bg-gray-800 p-8 rounded-2xl shadow-2xl w-full max-w-md border border-gray-700 fade-in">
        <div class="text-center mb-8">
            <div
                class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-red-600 to-red-800 rounded-full mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
            </div>
            <h2 class="text-3xl font-bold bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                <?php echo isset($_GET['token']) ? 'Reset Password' : 'Password Reset'; ?>
            </h2>
            <p class="text-gray-400 mt-2">
                <?php echo isset($_GET['token']) ? 'Enter your new password' : 'Enter your email to receive a reset link'; ?>
            </p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-800 border border-red-600 text-red-200 px-4 py-3 rounded-lg mb-4 fade-in">
                <strong class="font-bold">Error!</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-800 border border-green-600 text-green-200 px-4 py-3 rounded-lg mb-4 fade-in">
                <strong class="font-bold">Success!</strong>
                <?php if (strpos($success, '<a href') !== false): ?>
                    <?php echo $success; ?>
                <?php else: ?>
                    <?php echo htmlspecialchars($success); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($show_form && isset($_GET['token'])): ?>
            <!-- Reset password form -->
            <form method="post" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">New Password:</label>
                    <input type="password" name="new_password" required placeholder="Enter your new password"
                        class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400">
                    <p class="text-xs text-gray-400 mt-1">Min 8 chars, 1 uppercase, 1 number, 1 special char</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Confirm New Password:</label>
                    <input type="password" name="confirm_password" required placeholder="Confirm your new password"
                        class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400">
                </div>
                <button type="submit" name="submit"
                    class="w-full bg-gradient-to-r from-red-600 to-red-800 text-white py-3 px-4 rounded-lg hover:from-red-700 hover:to-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 font-medium">
                    Reset Password
                </button>
            </form>
        <?php elseif ($show_form): ?>
            <!-- Request reset form -->
            <form method="post" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Email:</label>
                    <input type="email" name="email" required placeholder="Enter your email"
                        class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400">
                </div>
                <button type="submit" name="request_reset"
                    class="w-full bg-gradient-to-r from-red-600 to-red-800 text-white py-3 px-4 rounded-lg hover:from-red-700 hover:to-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 font-medium">
                    Request Password Reset
                </button>
            </form>
        <?php endif; ?>

        <div class="mt-6 text-center">
            <a href="../login.php" class="text-red-400 hover:text-red-300 transition-colors duration-300">Back to
                Login</a>
        </div>
    </div>
</body>

</html>