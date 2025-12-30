<?php
session_start();
require("../connect.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";
$password_error = "";
$password_success = "";

// Get current user info
$sql = "SELECT * FROM users WHERE user_id = $user_id";
$result = myQuery($sql);
$user = mysqli_fetch_assoc($result);

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = md5($_POST['current_password']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Verify current password
    $check_sql = "SELECT * FROM users WHERE user_id = $user_id AND password_hash = '$current_password'";
    $check_result = myQuery($check_sql);

    if (mysqli_num_rows($check_result) == 0) {
        $password_error = "Current password is incorrect";
    }
    // Validate new password
    elseif (strlen($new_password) < 8) {
        $password_error = "Password must be at least 8 characters long";
    } elseif (!preg_match('/[A-Z]/', $new_password)) {
        $password_error = "Password must contain at least one uppercase letter";
    } elseif (!preg_match('/[0-9]/', $new_password)) {
        $password_error = "Password must contain at least one number";
    } elseif (!preg_match('/[^A-Za-z0-9]/', $new_password)) {
        $password_error = "Password must contain at least one special character";
    } elseif ($new_password !== $confirm_password) {
        $password_error = "New passwords do not match";
    } else {
        // Update password
        $new_password_hash = md5($new_password);
        $update_sql = "UPDATE users SET password_hash = '$new_password_hash' WHERE user_id = $user_id";
        if (myQuery($update_sql)) {
            $password_success = "Password changed successfully";
        } else {
            $password_error = "Failed to change password";
        }
    }
}

if (isset($_POST['submit'])) {
    $username = escapeString($_POST['username']);

    // Check if username is already taken (by another user)
    $check_sql = "SELECT * FROM users WHERE username = '$username' AND user_id != $user_id";
    $check_result = myQuery($check_sql);

    if (mysqli_num_rows($check_result) > 0) {
        $error = "Username already taken";
    } else {
        // Handle avatar upload
        $avatar_filename = $user['profile_avatar'];
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            $upload_dir = "../uploads/avatars/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $avatar_filename = "avatar_" . $user_id . "_" . time() . "." . $file_extension;
            $upload_path = $upload_dir . $avatar_filename;

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                // Delete old avatar if exists
                if ($user['profile_avatar'] && file_exists($upload_dir . $user['profile_avatar'])) {
                    unlink($upload_dir . $user['profile_avatar']);
                }
            } else {
                $error = "Failed to upload avatar";
                $avatar_filename = $user['profile_avatar'];
            }
        }

        // Update user
        $update_sql = "UPDATE users SET username = '$username', profile_avatar = '$avatar_filename' WHERE user_id = $user_id";
        if (myQuery($update_sql)) {
            $_SESSION['username'] = $username;
            $success = "Profile updated successfully";
            // Reload user data
            $result = myQuery($sql);
            $user = mysqli_fetch_assoc($result);
        } else {
            $error = "Failed to update profile";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - xGrab</title>
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

<body class="bg-gray-900 min-h-screen text-gray-100">
    <?php require("../includes/nav.php"); ?>

    <div class="container mx-auto px-4 py-8">
        <a href="view.php"
            class="text-red-400 hover:text-red-300 mb-4 inline-block transition-colors duration-300 fade-in">
            <span class="flex items-center space-x-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Back to Profile</span>
            </span>
        </a>

        <div class="bg-gray-800 rounded-xl shadow-lg p-8 max-w-2xl border border-gray-700 fade-in">
            <h1 class="text-3xl font-bold mb-6 bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                Edit Profile
            </h1>

            <?php if ($error): ?>
                <div class="bg-red-800 border border-red-600 text-red-200 px-4 py-3 rounded-lg mb-4 fade-in">
                    <strong class="font-bold">Error!</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-800 border border-green-600 text-green-200 px-4 py-3 rounded-lg mb-4 fade-in">
                    <strong class="font-bold">Success!</strong> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Username:</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>"
                        required
                        class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Email:</label>
                    <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled
                        class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg text-gray-400 cursor-not-allowed">
                    <p class="text-xs text-gray-400 mt-2">Email cannot be changed</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Profile Avatar:</label>
                    <?php if ($user['profile_avatar']): ?>
                        <div class="mb-3">
                            <p class="text-sm text-gray-400 mb-2">Current Avatar:</p>
                            <img src="../uploads/avatars/<?php echo htmlspecialchars($user['profile_avatar']); ?>"
                                alt="Current Avatar" class="w-24 h-24 rounded-full object-cover border-2 border-gray-600">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="avatar" accept="image/*"
                        class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-red-600 file:text-white hover:file:bg-red-700 transition-all duration-300">
                    <p class="text-xs text-gray-400 mt-2">Upload a new avatar image</p>
                </div>

                <div class="flex space-x-3 pt-2">
                    <button type="submit" name="submit"
                        class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 font-medium">
                        Update Profile
                    </button>
                    <a href="view.php"
                        class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 inline-block font-medium">
                        Cancel
                    </a>
                </div>
            </form>

            <!-- Change Password Section -->
            <div class="mt-8 pt-8 border-t border-gray-700">
                <button type="button" onclick="togglePasswordSection()"
                    class="w-full flex items-center justify-between px-4 py-3 bg-gray-700 hover:bg-gray-600 rounded-lg transition-all duration-300 text-left group">
                    <div class="flex items-center space-x-3">
                        <svg class="w-5 h-5 text-red-400 group-hover:text-red-300 transition-colors duration-300"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                        <span class="font-medium text-gray-200 group-hover:text-white">Change Password</span>
                    </div>
                    <svg id="passwordToggleIcon" class="w-5 h-5 text-gray-400 transition-transform duration-300"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div id="passwordSection" class="hidden mt-4 bg-gray-700 rounded-lg p-6 border border-gray-600">
                    <?php if ($password_error): ?>
                        <div class="bg-red-800 border border-red-600 text-red-200 px-4 py-3 rounded-lg mb-4 fade-in">
                            <strong class="font-bold">Error!</strong> <?php echo htmlspecialchars($password_error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($password_success): ?>
                        <div class="bg-green-800 border border-green-600 text-green-200 px-4 py-3 rounded-lg mb-4 fade-in">
                            <strong class="font-bold">Success!</strong> <?php echo htmlspecialchars($password_success); ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Current Password:</label>
                            <input type="password" name="current_password" required
                                class="w-full px-4 py-3 bg-gray-800 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">New Password:</label>
                            <input type="password" name="new_password" required
                                class="w-full px-4 py-3 bg-gray-800 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400">
                            <p class="text-xs text-gray-400 mt-2">Min 8 chars, 1 uppercase, 1 number, 1 special char</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Confirm New Password:</label>
                            <input type="password" name="confirm_password" required
                                class="w-full px-4 py-3 bg-gray-800 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400">
                        </div>

                        <div class="flex space-x-3 pt-2">
                            <button type="submit" name="change_password"
                                class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 font-medium">
                                Change Password
                            </button>
                            <button type="button" onclick="togglePasswordSection()"
                                class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 font-medium">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePasswordSection() {
            const section = document.getElementById('passwordSection');
            const icon = document.getElementById('passwordToggleIcon');
            const isHidden = section.classList.contains('hidden');

            if (isHidden) {
                section.classList.remove('hidden');
                icon.style.transform = 'rotate(180deg)';
            } else {
                section.classList.add('hidden');
                icon.style.transform = 'rotate(0deg)';
            }
        }

        // Auto-show password section if there's an error
        <?php if ($password_error): ?>
            document.addEventListener('DOMContentLoaded', function () {
                togglePasswordSection();
            });
        <?php endif; ?>
    </script>
</body>

</html>