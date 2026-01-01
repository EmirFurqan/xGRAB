<?php
/**
 * Password Reset Page
 * Handles password reset functionality with token-based authentication.
 * Supports both requesting a reset token and resetting password with a valid token.
 */

session_start();
// Include config if not already loaded
if (!defined('BASE_URL') && file_exists(__DIR__ . '/../includes/config.php')) {
    require_once __DIR__ . '/../includes/config.php';
}
require("../connect.php");
require("../image_handler.php");

$error = "";
$success = "";
$show_form = true;

// Handle password reset when token is provided in URL
// This is the second step: user clicks reset link and enters new password
if (isset($_GET['token'])) {
    // Sanitize token to prevent SQL injection
    $token = escapeString($_GET['token']);

    // Verify token is valid, not expired, and not already used
    // Token must exist, expire time must be in future, and used flag must be false
    // Using UTC_TIMESTAMP() for consistent timezone handling with token creation
    $token_sql = "SELECT *, expires_at, UTC_TIMESTAMP() as current_utc FROM password_reset_tokens 
                  WHERE token = '$token' AND expires_at > UTC_TIMESTAMP() AND used = FALSE";
    $token_result = myQuery($token_sql);

    if (mysqli_num_rows($token_result) == 0) {
        $error = "Invalid or expired reset token";
        $show_form = false;
    } else {
        // Extract user ID from token record
        $token_data = mysqli_fetch_assoc($token_result);
        $user_id = $token_data['user_id'];

        // Process password reset form submission
        if (isset($_POST['submit'])) {
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            // Validate new password meets security requirements
            // Minimum length check
            if (strlen($new_password) < 8) {
                $error = "Password must be at least 8 characters long";
            }
            // Require uppercase letter
            elseif (!preg_match('/[A-Z]/', $new_password)) {
                $error = "Password must contain at least one uppercase letter";
            }
            // Require numeric digit
            elseif (!preg_match('/[0-9]/', $new_password)) {
                $error = "Password must contain at least one number";
            }
            // Require special character
            elseif (!preg_match('/[^A-Za-z0-9]/', $new_password)) {
                $error = "Password must contain at least one special character";
            }
            // Verify password confirmation matches
            elseif ($new_password !== $confirm_password) {
                $error = "Passwords do not match";
            } else {
                // Hash new password with MD5 and update user record
                // Note: MD5 is cryptographically weak; consider upgrading
                $new_password_hash = md5($new_password);
                $update_sql = "UPDATE users SET password_hash = '$new_password_hash' WHERE user_id = $user_id";
                if (myQuery($update_sql)) {
                    // Mark token as used to prevent reuse
                    // This ensures each reset token can only be used once
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
// Handle reset token request (first step: user enters email)
elseif (isset($_POST['request_reset'])) {
    // Sanitize email input
    $email = escapeString($_POST['email']);

    // Check if email exists in database
    $user_sql = "SELECT * FROM users WHERE email = '$email'";
    $user_result = myQuery($user_sql);

    if (mysqli_num_rows($user_result) == 0) {
        // Don't reveal if email exists for security (prevents email enumeration)
        $error = "Email not found";
    } else {
        // Get user ID for token association
        $user = mysqli_fetch_assoc($user_result);
        $user_id = $user['user_id'];

        // Generate cryptographically secure random token
        // bin2hex converts binary to hexadecimal string (64 characters)
        $token = bin2hex(random_bytes(32));

        // Store token in database with expiration time (1 hour from now in UTC)
        // Using MySQL's DATE_ADD with UTC_TIMESTAMP for consistent timezone handling
        $insert_sql = "INSERT INTO password_reset_tokens (user_id, token, expires_at) 
                       VALUES ($user_id, '$token', DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1 HOUR))";
        if (myQuery($insert_sql)) {
            // Generate reset link URL
            // In production, this would be sent via email instead of displayed
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=$token";
            $success = "Password reset link generated successfully!";
            $generated_link = $reset_link;
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
    <meta name="description" content="Reset your xGrab account password securely.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse-ring {
            0% {
                transform: scale(0.8);
                opacity: 0.5;
            }

            50% {
                transform: scale(1.2);
                opacity: 0;
            }

            100% {
                transform: scale(0.8);
                opacity: 0.5;
            }
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0) rotate(0deg);
            }

            50% {
                transform: translateY(-15px) rotate(3deg);
            }
        }

        @keyframes glow {

            0%,
            100% {
                box-shadow: 0 0 20px rgba(239, 68, 68, 0.3);
            }

            50% {
                box-shadow: 0 0 40px rgba(239, 68, 68, 0.5);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.8s ease-out forwards;
        }

        .pulse-ring {
            animation: pulse-ring 3s ease-out infinite;
        }

        .float-anim {
            animation: float 6s ease-in-out infinite;
        }

        .glow-effect:hover {
            animation: glow 2s ease-in-out infinite;
        }

        .bg-grid {
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 50px 50px;
        }

        .glass-card {
            background: rgba(17, 24, 39, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .input-focus:focus {
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
        }

        .requirement-item.valid {
            color: #4ade80;
        }

        .step-indicator {
            transition: all 0.3s ease;
        }

        .step-indicator.active {
            background: linear-gradient(135deg, #ef4444, #f97316);
            color: white;
        }

        .step-indicator.completed {
            background: #22c55e;
            color: white;
        }
    </style>
</head>

<body class="bg-gray-950 min-h-screen text-gray-100 overflow-x-hidden">
    <!-- Background Elements -->
    <div class="fixed inset-0 bg-grid opacity-30"></div>

    <!-- Floating Accent Orbs -->
    <div class="fixed top-1/4 left-1/4 w-96 h-96 bg-red-500/10 rounded-full blur-3xl"></div>
    <div class="fixed bottom-1/4 right-1/4 w-80 h-80 bg-amber-500/10 rounded-full blur-3xl"></div>

    <!-- Main Content -->
    <div class="relative min-h-screen flex flex-col items-center justify-center px-4 py-12">
        <!-- Logo -->
        <a href="../index.php" class="flex items-center gap-3 mb-10 group fade-in-up" style="animation-delay: 0.1s;">
            <div class="relative">
                <div
                    class="absolute inset-0 bg-red-500 blur-xl opacity-50 group-hover:opacity-80 transition-opacity duration-500 rounded-full scale-150">
                </div>
                <img src="<?php echo getImagePath("logo.svg", 'poster'); ?>" alt="xGrab Logo"
                    class="w-12 h-12 relative transition-transform duration-300 group-hover:scale-110">
            </div>
            <span
                class="text-3xl font-extrabold bg-gradient-to-r from-white via-red-200 to-red-400 bg-clip-text text-transparent">xGrab</span>
        </a>

        <!-- Main Card -->
        <div class="w-full max-w-md">
            <div class="glass-card rounded-3xl border border-gray-800 shadow-2xl overflow-hidden fade-in-up"
                style="animation-delay: 0.2s;">
                <!-- Card Header with Icon -->
                <div class="relative bg-gradient-to-br from-red-600/20 via-orange-600/10 to-transparent p-8 pb-12">
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900/80 to-transparent"></div>
                    <div class="relative flex flex-col items-center">
                        <!-- Icon with Pulse Effect -->
                        <div class="relative mb-6">
                            <div class="absolute inset-0 bg-red-500 rounded-full blur-2xl opacity-30 pulse-ring"></div>
                            <div
                                class="relative w-20 h-20 bg-gradient-to-br from-red-500 to-orange-600 rounded-2xl flex items-center justify-center shadow-2xl float-anim">
                                <?php if (isset($_GET['token'])): ?>
                                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                <?php else: ?>
                                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Title & Description -->
                        <h1 class="text-2xl lg:text-3xl font-bold text-center mb-2">
                            <?php echo isset($_GET['token']) ? 'Create New Password' : 'Reset Your Password'; ?>
                        </h1>
                        <p class="text-gray-400 text-center text-sm">
                            <?php echo isset($_GET['token']) ? 'Enter your new password below' : 'Enter your email to receive a reset link'; ?>
                        </p>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="p-8 pt-0 -mt-4">
                    <!-- Error Message -->
                    <?php if ($error): ?>
                        <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/30">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex-shrink-0 w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <p class="text-red-300 font-medium text-sm"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Success Message -->
                    <?php if ($success): ?>
                        <div class="mb-6 p-4 rounded-xl bg-green-500/10 border border-green-500/30">
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex-shrink-0 w-10 h-10 rounded-full bg-green-500/20 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-green-300 font-medium text-sm"><?php echo htmlspecialchars($success); ?>
                                    </p>
                                    <?php if (isset($generated_link)): ?>
                                        <div class="mt-3 p-3 bg-gray-900 rounded-lg border border-gray-700">
                                            <p class="text-xs text-gray-400 mb-2">Reset Link (for testing):</p>
                                            <a href="<?php echo htmlspecialchars($generated_link); ?>"
                                                class="text-red-400 hover:text-red-300 text-xs break-all underline transition-colors">
                                                <?php echo htmlspecialchars($generated_link); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!isset($_GET['token']) && !isset($generated_link)): ?>
                                        <a href="../login.php"
                                            class="text-green-400 hover:text-green-300 underline text-sm mt-2 inline-block">
                                            Back to login
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Reset Password Form (with token) -->
                    <?php if ($show_form && isset($_GET['token'])): ?>
                        <form method="post" class="space-y-5" id="resetForm">
                            <div>
                                <label class="block text-sm font-semibold text-gray-300 mb-2">New Password</label>
                                <div class="relative">
                                    <input type="password" name="new_password" id="new_password" required
                                        placeholder="Enter your new password"
                                        class="w-full px-5 py-4 pr-12 bg-gray-900 border border-gray-700 rounded-xl text-gray-100 placeholder-gray-500 focus:outline-none focus:border-red-500 input-focus transition-all duration-300">
                                    <button type="button" onclick="togglePassword('new_password', this)"
                                        class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-200 transition-colors duration-300 focus:outline-none"
                                        aria-label="Toggle password visibility">
                                        <svg class="w-5 h-5 eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg class="w-5 h-5 eye-closed hidden" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Password Requirements -->
                                <div class="mt-3 p-3 rounded-lg bg-gray-900/50 border border-gray-800">
                                    <p class="text-xs font-medium text-gray-400 mb-2">Password must contain:</p>
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div class="requirement-item flex items-center gap-2 text-gray-500"
                                            data-requirement="length">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span>8+ characters</span>
                                        </div>
                                        <div class="requirement-item flex items-center gap-2 text-gray-500"
                                            data-requirement="uppercase">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span>1 uppercase</span>
                                        </div>
                                        <div class="requirement-item flex items-center gap-2 text-gray-500"
                                            data-requirement="number">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span>1 number</span>
                                        </div>
                                        <div class="requirement-item flex items-center gap-2 text-gray-500"
                                            data-requirement="special">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span>1 special char</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-300 mb-2">Confirm New Password</label>
                                <div class="relative">
                                    <input type="password" name="confirm_password" id="confirm_password" required
                                        placeholder="Confirm your new password"
                                        class="w-full px-5 py-4 pr-12 bg-gray-900 border border-gray-700 rounded-xl text-gray-100 placeholder-gray-500 focus:outline-none focus:border-red-500 input-focus transition-all duration-300">
                                    <button type="button" onclick="togglePassword('confirm_password', this)"
                                        class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-200 transition-colors duration-300 focus:outline-none"
                                        aria-label="Toggle password visibility">
                                        <svg class="w-5 h-5 eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg class="w-5 h-5 eye-closed hidden" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                        </svg>
                                    </button>
                                </div>
                                <p id="passwordMatch" class="hidden mt-2 text-sm"></p>
                            </div>

                            <button type="submit" name="submit"
                                class="w-full py-4 px-6 bg-gradient-to-r from-red-600 to-red-700 text-white font-bold rounded-xl hover:from-red-500 hover:to-red-600 focus:outline-none focus:ring-4 focus:ring-red-500/30 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl hover:shadow-red-500/20 glow-effect">
                                <span class="flex items-center justify-center gap-2">
                                    Reset Password
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </span>
                            </button>
                        </form>

                        <!-- Request Reset Form (without token) -->
                    <?php elseif ($show_form): ?>
                        <form method="post" class="space-y-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-300 mb-2">Email Address</label>
                                <input type="email" name="email" required placeholder="Enter your email address"
                                    class="w-full px-5 py-4 bg-gray-900 border border-gray-700 rounded-xl text-gray-100 placeholder-gray-500 focus:outline-none focus:border-red-500 input-focus transition-all duration-300">
                                <p class="text-xs text-gray-500 mt-2">We'll send you a link to reset your password</p>
                            </div>

                            <button type="submit" name="request_reset"
                                class="w-full py-4 px-6 bg-gradient-to-r from-red-600 to-red-700 text-white font-bold rounded-xl hover:from-red-500 hover:to-red-600 focus:outline-none focus:ring-4 focus:ring-red-500/30 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl hover:shadow-red-500/20 glow-effect">
                                <span class="flex items-center justify-center gap-2">
                                    Send Reset Link
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </span>
                            </button>
                        </form>
                    <?php endif; ?>

                    <!-- Back to Login Link -->
                    <div class="mt-6 pt-6 border-t border-gray-800">
                        <a href="../login.php"
                            class="flex items-center justify-center gap-2 text-gray-400 hover:text-white transition-colors duration-300 group">
                            <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform duration-300"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <span class="font-medium">Back to Sign In</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Security Note -->
            <p class="text-center text-gray-500 text-xs mt-6 fade-in-up" style="animation-delay: 0.4s;">
                <svg class="w-4 h-4 inline-block mr-1 text-gray-600" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Your connection is secure. We never share your information.
            </p>
        </div>
    </div>

    <script>
        // Real-time password validation for reset form
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const requirements = document.querySelectorAll('.requirement-item');
        const passwordMatch = document.getElementById('passwordMatch');

        if (newPassword) {
            newPassword.addEventListener('input', function () {
                const value = this.value;

                // Check each requirement
                requirements.forEach(req => {
                    const type = req.dataset.requirement;
                    let isValid = false;

                    switch (type) {
                        case 'length':
                            isValid = value.length >= 8;
                            break;
                        case 'uppercase':
                            isValid = /[A-Z]/.test(value);
                            break;
                        case 'number':
                            isValid = /[0-9]/.test(value);
                            break;
                        case 'special':
                            isValid = /[^A-Za-z0-9]/.test(value);
                            break;
                    }

                    if (isValid) {
                        req.classList.add('valid');
                        req.classList.remove('text-gray-500');
                    } else {
                        req.classList.remove('valid');
                        req.classList.add('text-gray-500');
                    }
                });

                // Check password match if confirm is filled
                if (confirmPassword && confirmPassword.value) {
                    checkPasswordMatch();
                }
            });
        }

        if (confirmPassword) {
            confirmPassword.addEventListener('input', checkPasswordMatch);
        }

        function checkPasswordMatch() {
            if (!confirmPassword || !passwordMatch) return;

            if (confirmPassword.value === '') {
                passwordMatch.classList.add('hidden');
                return;
            }

            passwordMatch.classList.remove('hidden');

            if (newPassword.value === confirmPassword.value) {
                passwordMatch.textContent = '✓ Passwords match';
                passwordMatch.className = 'mt-2 text-sm text-green-400';
            } else {
                passwordMatch.textContent = '✗ Passwords do not match';
                passwordMatch.className = 'mt-2 text-sm text-red-400';
            }
        }

        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const eyeOpen = button.querySelector('.eye-open');
            const eyeClosed = button.querySelector('.eye-closed');

            if (input.type === 'password') {
                input.type = 'text';
                eyeOpen.classList.add('hidden');
                eyeClosed.classList.remove('hidden');
            } else {
                input.type = 'password';
                eyeOpen.classList.remove('hidden');
                eyeClosed.classList.add('hidden');
            }
        }
    </script>
</body>

</html>