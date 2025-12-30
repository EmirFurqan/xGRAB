<?php
session_start();
require("connect.php");
$error = "";
$success = "";

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: menu.php");
    exit();
}

if (isset($_POST['submit'])) {
    $username = escapeString($_POST['username']);
    $email = escapeString($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    }
    // Check password requirements
    elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    }
    elseif (!preg_match('/[A-Z]/', $password)) {
        $error = "Password must contain at least one uppercase letter";
    }
    elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Password must contain at least one number";
    }
    elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $error = "Password must contain at least one special character";
    }
    elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    }
    else {
        // Check if email already exists
        $check_email = "SELECT * FROM users WHERE email = '$email'";
        $res_email = myQuery($check_email);
        if (mysqli_num_rows($res_email) > 0) {
            $error = "Email already registered";
        }
        // Check if username already exists
        else {
            $check_username = "SELECT * FROM users WHERE username = '$username'";
            $res_username = myQuery($check_username);
            if (mysqli_num_rows($res_username) > 0) {
                $error = "Username already taken";
            }
            else {
                // Hash password with MD5
                $password_hash = md5($password);
                $join_date = date('Y-m-d');
                
                $sql = "INSERT INTO users (username, email, password_hash, join_date) 
                        VALUES ('$username', '$email', '$password_hash', '$join_date')";
                
                if (myQuery($sql)) {
                    $success = "Registration successful! You can now login.";
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Database - Register</title>
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
                    <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"/>
                </svg>
            </div>
            <h2 class="text-3xl font-bold bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">Create Account</h2>
            <p class="text-gray-400 mt-2">Join our movie community</p>
        </div>
        <?php if($error): ?>
            <div class="bg-red-800 border border-red-600 text-red-200 px-4 py-3 rounded-lg mb-4 fade-in">
                <strong class="font-bold">Error!</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="bg-green-800 border border-green-600 text-green-200 px-4 py-3 rounded-lg mb-4 fade-in">
                <strong class="font-bold">Success!</strong> <?php echo htmlspecialchars($success); ?>
                <br><a href="login.php" class="underline hover:text-green-300 transition-colors duration-300">Click here to login</a>
            </div>
        <?php endif; ?>
        <form method="post" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Username:</label>
                <input type="text" name="username" required 
                       placeholder="Enter your username"
                       class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400">
            </div>
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
                <p class="text-xs text-gray-400 mt-1">Min 8 chars, 1 uppercase, 1 number, 1 special char</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Confirm Password:</label>
                <input type="password" name="confirm_password" required 
                       placeholder="Confirm your password"
                       class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400">
            </div>
            <button type="submit" name="submit" 
                    class="w-full bg-gradient-to-r from-red-600 to-red-800 text-white py-3 px-4 rounded-lg hover:from-red-700 hover:to-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 font-medium">
                Register
            </button>
        </form>
        <div class="mt-4 text-center">
            <a href="login.php" class="text-red-400 hover:text-red-300 transition-colors duration-300">Already have an account? Login</a>
        </div>
    </div>
</body>
</html>

