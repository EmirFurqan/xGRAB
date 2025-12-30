<?php
session_start();
require("connect.php");
$error = "";

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: menu.php");
    exit();
}

// Handle failed login attempts
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['login_attempt_time'] = time();
}

// Reset attempts after 15 minutes
if (isset($_SESSION['login_attempt_time']) && (time() - $_SESSION['login_attempt_time']) > 900) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['login_attempt_time'] = time();
}

if (isset($_POST['submit'])) {
    // Check if account is locked
    if ($_SESSION['login_attempts'] >= 5) {
        $error = "Account temporarily locked. Please try again in 15 minutes.";
    } else {
        $email = escapeString($_POST['email']);
        $password = md5($_POST['password']); // MD5 hashing
        
        $sql = "SELECT * FROM users WHERE email = '$email' AND password_hash = '$password'";
        $res = myQuery($sql);
        
        if (mysqli_num_rows($res) == 1) {
            $row = mysqli_fetch_assoc($res);
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['email'] = $row['email'];
            // Convert is_admin to proper boolean (MySQL BOOLEAN returns 0/1 as int)
            $_SESSION['is_admin'] = (bool)$row['is_admin'];
            
            // Reset login attempts on success
            $_SESSION['login_attempts'] = 0;
            
            header("Location: menu.php");
            exit();
        } else {
            $_SESSION['login_attempts']++;
            $_SESSION['login_attempt_time'] = time();
            $error = "Invalid email or password";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Database - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center">
    <div class="bg-gray-800 p-8 rounded-2xl shadow-2xl w-full max-w-md border border-gray-700 fade-in">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-red-600 to-red-800 rounded-full mb-4">
                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/>
                </svg>
            </div>
            <h2 class="text-3xl font-bold bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">Welcome Back</h2>
            <p class="text-gray-400 mt-2">Sign in to your account</p>
        </div>
        <?php if($error): ?>
            <div class="bg-red-800 border border-red-600 text-red-200 px-4 py-3 rounded-lg mb-4 fade-in">
                <strong class="font-bold">Error!</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="post" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Email:</label>
                <input type="email" name="email" required 
                       placeholder="Enter your email"
                       class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Password:</label>
                <input type="password" name="password" required 
                       placeholder="Enter your password"
                       class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400">
            </div>
            <button type="submit" name="submit" 
                    class="w-full bg-gradient-to-r from-red-600 to-red-800 text-white py-3 px-4 rounded-lg hover:from-red-700 hover:to-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 font-medium">
                Login
            </button>
        </form>
        <div class="mt-6 text-center space-y-2">
            <a href="register.php" class="text-red-400 hover:text-red-300 font-medium transition-colors duration-300">Don't have an account? Register</a>
            <div>
                <a href="profile/reset_password.php" class="text-gray-400 hover:text-gray-300 text-sm transition-colors duration-300">Forgot Password?</a>
            </div>
        </div>
    </div>
</body>
</html>

