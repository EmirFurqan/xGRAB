<?php
/**
 * Password Change Page
 * Allows logged-in users to change their password with validation.
 * Requires current password verification before allowing change.
 */

session_start();
require("../connect.php");

// Require user to be logged in to change password
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

// Process password change form submission
if (isset($_POST['submit'])) {
    // Hash current password with MD5 to compare with stored hash
    $current_password = md5($_POST['current_password']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Verify that entered current password matches stored password
    // This prevents unauthorized password changes
    $check_sql = "SELECT * FROM users WHERE user_id = $user_id AND password_hash = '$current_password'";
    $check_result = myQuery($check_sql);

    if (mysqli_num_rows($check_result) == 0) {
        $error = "Current password is incorrect";
    }
    // Validate new password meets minimum length requirement
    elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long";
    }
    // Require at least one uppercase letter for password complexity
    elseif (!preg_match('/[A-Z]/', $new_password)) {
        $error = "Password must contain at least one uppercase letter";
    }
    // Require at least one numeric digit
    elseif (!preg_match('/[0-9]/', $new_password)) {
        $error = "Password must contain at least one number";
    }
    // Require at least one special character (non-alphanumeric)
    elseif (!preg_match('/[^A-Za-z0-9]/', $new_password)) {
        $error = "Password must contain at least one special character";
    }
    // Verify password confirmation matches new password
    elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match";
    } else {
        // Hash new password with MD5 and update database
        // Note: MD5 is cryptographically weak; consider upgrading to bcrypt or Argon2
        $new_password_hash = md5($new_password);
        $update_sql = "UPDATE users SET password_hash = '$new_password_hash' WHERE user_id = $user_id";
        if (myQuery($update_sql)) {
            $success = "Password changed successfully";
        } else {
            $error = "Failed to change password";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - xGrab</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <a href="../index.php" class="text-2xl font-bold">Movie Database</a>
            <div class="flex items-center space-x-4">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="../index.php" class="hover:underline">Home</a>
                <a href="../logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <a href="view.php" class="text-blue-600 hover:underline mb-4 inline-block">← Back to Profile</a>

        <div class="bg-white rounded-lg shadow-md p-6 max-w-2xl">
            <h1 class="text-2xl font-bold mb-6">Change Password</h1>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Password:</label>
                    <input type="password" name="current_password" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Password:</label>
                    <input type="password" name="new_password" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Min 8 chars, 1 uppercase, 1 number, 1 special char</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password:</label>
                    <input type="password" name="confirm_password" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="flex space-x-2">
                    <button type="submit" name="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                        Change Password
                    </button>
                    <a href="view.php" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 inline-block">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php require("../includes/footer.php"); ?>
</body>

</html>