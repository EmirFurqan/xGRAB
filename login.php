<?php
/**
 * User Login Page
 * Handles user authentication with login attempt tracking and session management.
 * Implements basic brute-force protection by locking accounts after 5 failed attempts.
 */

session_start();
// Include config if not already loaded
if (!defined('BASE_URL') && file_exists(__DIR__ . '/includes/config.php')) {
    require_once __DIR__ . '/includes/config.php';
}
require("connect.php");
require("image_handler.php");
$error = "";

// Redirect users who are already logged in to prevent duplicate sessions
// This improves user experience by skipping unnecessary login steps
if (isset($_SESSION['user_id'])) {
    $redirect_url = defined('BASE_URL') ? BASE_URL . 'index.php' : 'index.php';
    header("Location: " . $redirect_url);
    exit();
}

// Initialize login attempt tracking in session if not already set
// This tracks failed login attempts to prevent brute-force attacks
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['login_attempt_time'] = time();
}

// Reset login attempt counter after 15 minutes (900 seconds) have passed
// This allows users to try again after the lockout period expires
if (isset($_SESSION['login_attempt_time']) && (time() - $_SESSION['login_attempt_time']) > 900) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['login_attempt_time'] = time();
}

// Process login form submission
if (isset($_POST['submit'])) {
    // Check if account is temporarily locked due to too many failed attempts
    // Lock threshold is set to 5 failed attempts
    if ($_SESSION['login_attempts'] >= 5) {
        $error = "Account temporarily locked. Please try again in 15 minutes.";
    } else {
        // Sanitize email input to prevent SQL injection
        $email = escapeString($_POST['email']);

        // Hash password using MD5 to match stored password format
        // Note: MD5 is not cryptographically secure; consider upgrading to bcrypt or Argon2
        $password = md5($_POST['password']);

        // Query database to find user with matching email and password
        // This query checks both email and password_hash in a single operation
        $sql = "SELECT * FROM users WHERE email = '$email' AND password_hash = '$password'";
        $res = myQuery($sql);

        // Verify exactly one user was found (successful login)
        if (mysqli_num_rows($res) == 1) {
            // Fetch user data from query result
            $row = mysqli_fetch_assoc($res);

            // Store user information in session for use across the application
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['email'] = $row['email'];

            // Convert MySQL BOOLEAN (stored as 0/1) to PHP boolean type
            // This ensures proper boolean evaluation in conditional statements
            $_SESSION['is_admin'] = (bool) $row['is_admin'];

            // Reset login attempt counter on successful login
            // This clears any previous failed attempts
            $_SESSION['login_attempts'] = 0;

            // Redirect to main menu after successful authentication
            $redirect_url = defined('BASE_URL') ? BASE_URL . 'index.php?login=success' : 'index.php?login=success';
            header("Location: " . $redirect_url);
            exit();
        } else {
            // Increment failed login attempt counter
            // Update timestamp to track when lockout period should expire
            $_SESSION['login_attempts']++;
            $_SESSION['login_attempt_time'] = time();
            $error = "Invalid email or password";
        }
    }
}

// Get some featured movies for the background showcase
$featured_sql = "SELECT poster_image, title FROM movies WHERE poster_image IS NOT NULL AND poster_image != '' ORDER BY average_rating DESC LIMIT 12";
$featured_result = myQuery($featured_sql);
$featured_movies = [];
while ($movie = mysqli_fetch_assoc($featured_result)) {
    $featured_movies[] = $movie;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - xGrab</title>
    <meta name="description"
        content="Sign in to your xGrab account to discover, track, and review your favorite movies.">
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

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(2deg);
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

        @keyframes posterFloat {

            0%,
            100% {
                transform: translateY(0) scale(1);
            }

            50% {
                transform: translateY(-10px) scale(1.02);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.8s ease-out forwards;
        }

        .slide-in {
            animation: slideIn 0.6s ease-out forwards;
        }

        .glow-effect {
            animation: glow 3s ease-in-out infinite;
        }

        .poster-card {
            animation: posterFloat 6s ease-in-out infinite;
        }

        .poster-card:nth-child(odd) {
            animation-delay: -3s;
        }

        .glass-card {
            background: rgba(17, 24, 39, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .input-focus:focus {
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
        }

        .gradient-border {
            position: relative;
        }

        .gradient-border::before {
            content: '';
            position: absolute;
            inset: -2px;
            background: linear-gradient(135deg, #ef4444, #f97316, #ef4444);
            border-radius: inherit;
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .gradient-border:hover::before,
        .gradient-border:focus-within::before {
            opacity: 1;
        }

        .bg-grid {
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 50px 50px;
        }

        .movie-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            transform: perspective(1000px) rotateY(-5deg) rotateX(5deg);
        }

        .movie-grid img {
            transition: all 0.5s ease;
        }

        .movie-grid img:hover {
            transform: scale(1.1) translateZ(20px);
            z-index: 10;
        }
    </style>
</head>

<body class="bg-gray-950 min-h-screen text-gray-100 overflow-x-hidden">
    <!-- Background Elements -->
    <div class="fixed inset-0 bg-grid opacity-30"></div>
    <div class="fixed top-0 right-0 w-1/2 h-full bg-gradient-to-l from-red-950/20 to-transparent"></div>
    <div class="fixed bottom-0 left-0 w-full h-1/2 bg-gradient-to-t from-gray-950 to-transparent"></div>

    <!-- Floating Accent Orbs -->
    <div class="fixed top-20 left-10 w-64 h-64 bg-red-500/10 rounded-full blur-3xl"></div>
    <div class="fixed bottom-20 right-10 w-96 h-96 bg-orange-500/10 rounded-full blur-3xl"></div>

    <div class="relative min-h-screen flex">
        <!-- Left Side - Form Section -->
        <div class="w-full lg:w-1/2 flex flex-col justify-center items-center px-6 py-12 relative z-10">
            <div class="w-full max-w-md">
                <!-- Logo -->
                <a href="index.php" class="flex items-center gap-3 mb-12 group fade-in-up"
                    style="animation-delay: 0.1s;">
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

                <!-- Welcome Text -->
                <div class="mb-8 fade-in-up" style="animation-delay: 0.2s;">
                    <h1
                        class="text-4xl lg:text-5xl font-bold mb-3 bg-gradient-to-r from-white via-gray-100 to-gray-300 bg-clip-text text-transparent">
                        Welcome back
                    </h1>
                    <p class="text-gray-400 text-lg">Sign in to continue your cinematic journey</p>
                </div>

                <!-- Error Message -->
                <?php if ($error): ?>
                    <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/30 fade-in-up"
                        style="animation-delay: 0.3s;">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex-shrink-0 w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="text-red-300 font-medium"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="post" class="space-y-5 fade-in-up" style="animation-delay: 0.3s;">
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">Email Address</label>
                        <div class="gradient-border rounded-xl">
                            <input type="email" name="email" required placeholder="you@example.com"
                                class="w-full px-5 py-4 bg-gray-900 border border-gray-700 rounded-xl text-gray-100 placeholder-gray-500 focus:outline-none focus:border-red-500 input-focus transition-all duration-300">
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-semibold text-gray-300">Password</label>
                            <a href="profile/reset_password.php"
                                class="text-sm text-red-400 hover:text-red-300 transition-colors duration-300">
                                Forgot password?
                            </a>
                        </div>
                        <div class="gradient-border rounded-xl">
                            <div class="relative">
                                <input type="password" name="password" id="password" required
                                    placeholder="Enter your password"
                                    class="w-full px-5 py-4 pr-12 bg-gray-900 border border-gray-700 rounded-xl text-gray-100 placeholder-gray-500 focus:outline-none focus:border-red-500 input-focus transition-all duration-300">
                                <button type="button" onclick="togglePassword('password', this)"
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
                        </div>
                    </div>

                    <button type="submit" name="submit"
                        class="w-full py-4 px-6 bg-gradient-to-r from-red-600 to-red-700 text-white font-bold rounded-xl hover:from-red-500 hover:to-red-600 focus:outline-none focus:ring-4 focus:ring-red-500/30 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl hover:shadow-red-500/20 glow-effect">
                        <span class="flex items-center justify-center gap-2">
                            Sign In
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </span>
                    </button>
                </form>

                <!-- Divider -->
                <div class="flex items-center gap-4 my-8 fade-in-up" style="animation-delay: 0.4s;">
                    <div class="flex-1 h-px bg-gradient-to-r from-transparent via-gray-700 to-transparent"></div>
                    <span class="text-gray-500 text-sm font-medium">New to xGrab?</span>
                    <div class="flex-1 h-px bg-gradient-to-r from-transparent via-gray-700 to-transparent"></div>
                </div>

                <!-- Register Link -->
                <a href="register.php" class="block fade-in-up" style="animation-delay: 0.5s;">
                    <div
                        class="w-full py-4 px-6 bg-gray-900 border-2 border-gray-700 text-gray-300 font-semibold rounded-xl hover:bg-gray-800 hover:border-red-500/50 hover:text-white focus:outline-none transition-all duration-300 text-center group">
                        <span class="flex items-center justify-center gap-2">
                            Create an account
                            <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform duration-300" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </span>
                    </div>
                </a>

                <!-- Footer Text -->
                <p class="text-center text-gray-500 text-sm mt-8 fade-in-up" style="animation-delay: 0.6s;">
                    By signing in, you agree to our
                    <a href="#" class="text-red-400 hover:text-red-300 transition-colors">Terms of Service</a> and
                    <a href="#" class="text-red-400 hover:text-red-300 transition-colors">Privacy Policy</a>
                </p>
            </div>
        </div>

        <!-- Right Side - Visual Section (Hidden on mobile) -->
        <div class="hidden lg:flex lg:w-1/2 relative items-center justify-center overflow-hidden">
            <!-- Movie Posters Grid -->
            <div class="absolute inset-0 flex items-center justify-center p-12">
                <div class="movie-grid w-full max-w-lg">
                    <?php
                    $delay = 0;
                    foreach ($featured_movies as $index => $movie):
                        if ($index >= 9)
                            break;
                        $delay += 100;
                        ?>
                        <div class="poster-card rounded-xl overflow-hidden shadow-2xl"
                            style="animation-delay: <?php echo $delay; ?>ms;">
                            <img src="<?php echo htmlspecialchars(getImagePath($movie['poster_image'], 'poster')); ?>"
                                alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                class="w-full aspect-[2/3] object-cover hover:brightness-110 transition-all duration-500">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Overlay Gradient -->
            <div class="absolute inset-0 bg-gradient-to-r from-gray-950 via-gray-950/50 to-transparent"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-gray-950 via-transparent to-gray-950/50"></div>

            <!-- Feature Text -->
            <div class="relative z-10 text-center px-12">
                <div
                    class="inline-flex items-center gap-2 px-4 py-2 bg-red-500/10 border border-red-500/30 rounded-full mb-6">
                    <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                    <span class="text-red-400 text-sm font-medium">Now Streaming</span>
                </div>
                <h2
                    class="text-4xl font-bold mb-4 bg-gradient-to-r from-white to-gray-400 bg-clip-text text-transparent">
                    Your Next Favorite Film Awaits
                </h2>
                <p class="text-gray-400 text-lg max-w-md mx-auto">
                    Discover, track, and share your love for cinema with millions of movie enthusiasts.
                </p>
            </div>
        </div>
    </div>

    <?php require_once 'includes/toast.php'; ?>
    <?php if ($error): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                showToast('<?php echo addslashes($error); ?>', 'error');
            });
        </script>
    <?php endif; ?>

    <script>
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