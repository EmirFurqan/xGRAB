<?php
/**
 * User Registration Page
 * Handles new user account creation with validation and duplicate checking.
 * Implements password strength requirements and ensures unique usernames and emails.
 */

session_start();
require("connect.php");
require("image_handler.php");
$error = "";
$success = "";

// Redirect users who are already logged in
// Prevents logged-in users from creating duplicate accounts
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Process registration form submission
if (isset($_POST['submit'])) {
    // Sanitize user inputs to prevent SQL injection
    $username = escapeString($_POST['username']);
    $email = escapeString($_POST['email']);

    // Store passwords without escaping (hashing will be applied)
    // Passwords should not be escaped as they will be hashed before database storage
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate email format using PHP's built-in filter
    // This ensures the email follows proper email address syntax
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    }
    // Validate password meets minimum length requirement
    // Minimum 8 characters is a common security standard
    elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    }
    // Check for at least one uppercase letter
    // This increases password complexity and security
    elseif (!preg_match('/[A-Z]/', $password)) {
        $error = "Password must contain at least one uppercase letter";
    }
    // Check for at least one numeric digit
    // Mixing letters and numbers improves password strength
    elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Password must contain at least one number";
    }
    // Check for at least one special character (non-alphanumeric)
    // Special characters further enhance password security
    elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $error = "Password must contain at least one special character";
    }
    // Verify password confirmation matches original password
    // This prevents typos that would lock users out of their accounts
    elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Check if email address is already registered in the database
        // Prevents duplicate accounts with the same email
        $check_email = "SELECT * FROM users WHERE email = '$email'";
        $res_email = myQuery($check_email);
        if (mysqli_num_rows($res_email) > 0) {
            $error = "Email already registered";
        }
        // Check if username is already taken
        // Ensures each user has a unique username for identification
        else {
            $check_username = "SELECT * FROM users WHERE username = '$username'";
            $res_username = myQuery($check_username);
            if (mysqli_num_rows($res_username) > 0) {
                $error = "Username already taken";
            } else {
                // Hash password using MD5 algorithm
                // Note: MD5 is cryptographically weak; consider upgrading to bcrypt or Argon2
                $password_hash = md5($password);

                // Set join date to current date for tracking when user registered
                $join_date = date('Y-m-d');

                // Insert new user record into database
                // Stores username, email, hashed password, and join date
                $sql = "INSERT INTO users (username, email, password_hash, join_date) 
                        VALUES ('$username', '$email', '$password_hash', '$join_date')";

                // Execute insert query and check for success
                if (myQuery($sql)) {
                    $success = "Registration successful! You can now login.";
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        }
    }
}

// Get some featured movies for the background showcase
$featured_sql = "SELECT poster_image, title FROM movies WHERE poster_image IS NOT NULL AND poster_image != '' ORDER BY RAND() LIMIT 12";
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
    <title>Create Account - xGrab</title>
    <meta name="description"
        content="Join xGrab to discover, track, and review your favorite movies. Create your free account today!">
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

        @keyframes glow {

            0%,
            100% {
                box-shadow: 0 0 20px rgba(239, 68, 68, 0.3);
            }

            50% {
                box-shadow: 0 0 40px rgba(239, 68, 68, 0.5);
            }
        }

        @keyframes posterSlide {
            0% {
                transform: translateY(0);
            }

            100% {
                transform: translateY(-50%);
            }
        }

        @keyframes checkPulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 0.3;
            }

            50% {
                transform: scale(1.2);
                opacity: 1;
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.8s ease-out forwards;
        }

        .glow-effect:hover {
            animation: glow 2s ease-in-out infinite;
        }

        .poster-column {
            animation: posterSlide 30s linear infinite;
        }

        .poster-column:nth-child(2) {
            animation-direction: reverse;
            animation-duration: 35s;
        }

        .poster-column:nth-child(3) {
            animation-duration: 25s;
        }

        .glass-card {
            background: rgba(17, 24, 39, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .input-focus:focus {
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
        }

        .bg-grid {
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 50px 50px;
        }

        .requirement-item {
            transition: all 0.3s ease;
        }

        .requirement-item.valid {
            color: #4ade80;
        }

        .requirement-item.valid svg {
            animation: checkPulse 0.5s ease-out;
        }

        .strength-bar {
            transition: all 0.3s ease;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #1f2937;
        }

        ::-webkit-scrollbar-thumb {
            background: #4b5563;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #6b7280;
        }
    </style>
</head>

<body class="bg-gray-950 min-h-screen text-gray-100 overflow-x-hidden">
    <!-- Background Elements -->
    <div class="fixed inset-0 bg-grid opacity-30"></div>

    <!-- Floating Accent Orbs -->
    <div class="fixed top-40 right-20 w-80 h-80 bg-red-500/10 rounded-full blur-3xl"></div>
    <div class="fixed bottom-40 left-20 w-96 h-96 bg-orange-500/10 rounded-full blur-3xl"></div>
    <div
        class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-red-900/5 rounded-full blur-3xl">
    </div>

    <div class="relative min-h-screen flex">
        <!-- Left Side - Movie Poster Columns (Hidden on mobile) -->
        <div class="hidden lg:block lg:w-1/2 relative overflow-hidden">
            <!-- Poster Columns -->
            <div class="absolute inset-0 flex gap-4 p-8 opacity-40">
                <?php for ($col = 0; $col < 3; $col++): ?>
                    <div class="flex-1 poster-column flex flex-col gap-4">
                        <?php
                        // Repeat movies to fill the column
                        for ($repeat = 0; $repeat < 3; $repeat++):
                            foreach ($featured_movies as $index => $movie):
                                if ($index >= 4)
                                    break;
                                ?>
                                <div
                                    class="rounded-xl overflow-hidden shadow-2xl transform hover:scale-105 transition-transform duration-500">
                                    <img src="<?php echo htmlspecialchars(getImagePath($movie['poster_image'], 'poster')); ?>"
                                        alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                        class="w-full aspect-[2/3] object-cover">
                                </div>
                                <?php
                            endforeach;
                        endfor;
                        ?>
                    </div>
                <?php endfor; ?>
            </div>

            <!-- Overlay Gradients -->
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-gray-950/70 to-gray-950"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-950/50 to-gray-950/30"></div>

            <!-- Feature Content -->
            <div class="absolute inset-0 flex flex-col justify-center items-center px-12 z-10">
                <div class="max-w-md text-center">
                    <div
                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-500/10 border border-red-500/30 rounded-full mb-6">
                        <svg class="w-4 h-4 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        <span class="text-red-400 text-sm font-medium">Join 10,000+ Movie Lovers</span>
                    </div>
                    <h2
                        class="text-4xl font-bold mb-4 bg-gradient-to-r from-white via-gray-100 to-gray-400 bg-clip-text text-transparent">
                        Start Your Cinematic Journey
                    </h2>
                    <p class="text-gray-400 text-lg mb-8">
                        Create watchlists, rate movies, write reviews, and connect with fellow film enthusiasts.
                    </p>

                    <!-- Features List -->
                    <div class="space-y-4 text-left">
                        <div class="flex items-center gap-3 p-3 rounded-lg bg-white/5 border border-white/10">
                            <div
                                class="flex-shrink-0 w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-gray-300">Create unlimited watchlists</span>
                        </div>
                        <div class="flex items-center gap-3 p-3 rounded-lg bg-white/5 border border-white/10">
                            <div
                                class="flex-shrink-0 w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-gray-300">Track watched movies & favorites</span>
                        </div>
                        <div class="flex items-center gap-3 p-3 rounded-lg bg-white/5 border border-white/10">
                            <div
                                class="flex-shrink-0 w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-gray-300">Write and share reviews</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side - Form Section -->
        <div class="w-full lg:w-1/2 flex flex-col justify-center items-center px-6 py-12 relative z-10">
            <div class="w-full max-w-md">
                <!-- Logo -->
                <a href="index.php" class="flex items-center gap-3 mb-10 group fade-in-up"
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
                        Create account
                    </h1>
                    <p class="text-gray-400 text-lg">Join our community of film enthusiasts</p>
                </div>

                <!-- Error/Success Messages -->
                <?php if ($error): ?>
                    <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/30 fade-in-up">
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

                <?php if ($success): ?>
                    <div class="mb-6 p-4 rounded-xl bg-green-500/10 border border-green-500/30 fade-in-up">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex-shrink-0 w-10 h-10 rounded-full bg-green-500/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-green-300 font-medium"><?php echo htmlspecialchars($success); ?></p>
                                <a href="login.php" class="text-green-400 hover:text-green-300 underline text-sm">Click here
                                    to login</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Registration Form -->
                <form method="post" class="space-y-4 fade-in-up" style="animation-delay: 0.3s;" id="registerForm">
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">Username</label>
                        <input type="text" name="username" required placeholder="Choose a username"
                            class="w-full px-5 py-4 bg-gray-900 border border-gray-700 rounded-xl text-gray-100 placeholder-gray-500 focus:outline-none focus:border-red-500 input-focus transition-all duration-300">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">Email Address</label>
                        <input type="email" name="email" required placeholder="you@example.com"
                            class="w-full px-5 py-4 bg-gray-900 border border-gray-700 rounded-xl text-gray-100 placeholder-gray-500 focus:outline-none focus:border-red-500 input-focus transition-all duration-300">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">Password</label>
                        <div class="relative">
                            <input type="password" name="password" id="password" required
                                placeholder="Create a strong password"
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
                        <label class="block text-sm font-semibold text-gray-300 mb-2">Confirm Password</label>
                        <div class="relative">
                            <input type="password" name="confirm_password" id="confirm_password" required
                                placeholder="Confirm your password"
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
                        class="w-full py-4 px-6 bg-gradient-to-r from-red-600 to-red-700 text-white font-bold rounded-xl hover:from-red-500 hover:to-red-600 focus:outline-none focus:ring-4 focus:ring-red-500/30 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl hover:shadow-red-500/20 glow-effect mt-6">
                        <span class="flex items-center justify-center gap-2">
                            Create Account
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
                    <span class="text-gray-500 text-sm font-medium">Already have an account?</span>
                    <div class="flex-1 h-px bg-gradient-to-r from-transparent via-gray-700 to-transparent"></div>
                </div>

                <!-- Login Link -->
                <a href="login.php" class="block fade-in-up" style="animation-delay: 0.5s;">
                    <div
                        class="w-full py-4 px-6 bg-gray-900 border-2 border-gray-700 text-gray-300 font-semibold rounded-xl hover:bg-gray-800 hover:border-red-500/50 hover:text-white focus:outline-none transition-all duration-300 text-center group">
                        <span class="flex items-center justify-center gap-2">
                            Sign in instead
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
                    By creating an account, you agree to our
                    <a href="#" class="text-red-400 hover:text-red-300 transition-colors">Terms of Service</a> and
                    <a href="#" class="text-red-400 hover:text-red-300 transition-colors">Privacy Policy</a>
                </p>
            </div>
        </div>
    </div>

    <?php require_once 'includes/toast.php'; ?>

    <script>
        // Real-time password validation
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const requirements = document.querySelectorAll('.requirement-item');
        const passwordMatch = document.getElementById('passwordMatch');

        password.addEventListener('input', function () {
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
            if (confirmPassword.value) {
                checkPasswordMatch();
            }
        });

        confirmPassword.addEventListener('input', checkPasswordMatch);

        function checkPasswordMatch() {
            if (confirmPassword.value === '') {
                passwordMatch.classList.add('hidden');
                return;
            }

            passwordMatch.classList.remove('hidden');

            if (password.value === confirmPassword.value) {
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

    <?php if ($error): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                showToast('<?php echo addslashes($error); ?>', 'error');
            });
        </script>
    <?php endif; ?>
    <?php if ($success): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                showToast('<?php echo addslashes($success); ?>', 'success', 5000);
            });
        </script>
    <?php endif; ?>
</body>

</html>