<?php
/**
 * Account Deletion Page
 * Handles permanent account deletion with confirmation requirement.
 * Deletes user record and all related data via database CASCADE constraints.
 */

session_start();
require("../connect.php");

// Require user to be logged in to delete account
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = "";

// Process account deletion confirmation
if (isset($_POST['confirm_delete'])) {
    // Sanitize confirmation text input
    $confirm_text = escapeString($_POST['confirm_text']);

    // Require exact match of 'DELETE' to prevent accidental deletions
    // This adds an extra layer of confirmation beyond just clicking a button
    if ($confirm_text !== 'DELETE') {
        $error = "Confirmation text must be exactly 'DELETE'";
    } else {
        // Delete user record from database
        // Database foreign key constraints with CASCADE will automatically delete:
        // - Reviews (reviews.user_id)
        // - Watchlists (watchlists.user_id)
        // - Favorites (favorites.user_id)
        // - Watched movies (user_watched_movies.user_id)
        // - Password reset tokens (password_reset_tokens.user_id)
        // - Admin logs (admin_logs.admin_id)
        $delete_sql = "DELETE FROM users WHERE user_id = $user_id";
        if (myQuery($delete_sql)) {
            // Destroy session to log out the user
            session_destroy();
            
            // Redirect to registration page with success message
            header("Location: ../register.php?message=Account deleted successfully");
            exit();
        } else {
            $error = "Failed to delete account";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account - xGrab</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <a href="../index.php" class="text-2xl font-bold">Movie Database</a>
            <div class="flex items-center space-x-4">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="../menu.php" class="hover:underline">Menu</a>
                <a href="../logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <a href="view.php" class="text-blue-600 hover:underline mb-4 inline-block">← Back to Profile</a>

        <div class="bg-white rounded-lg shadow-md p-6 max-w-2xl">
            <h1 class="text-2xl font-bold mb-4 text-red-600">Delete Account</h1>

            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <strong>Warning:</strong> This action cannot be undone. All your data including reviews, watchlists, and
                profile information will be permanently deleted.
            </div>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Type <strong>DELETE</strong> to confirm:
                    </label>
                    <input type="text" name="confirm_text" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>

                <div class="flex space-x-2">
                    <button type="submit" name="confirm_delete"
                        class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700"
                        onclick="return confirm('Are you absolutely sure you want to delete your account? This cannot be undone!');">
                        Delete My Account
                    </button>
                    <a href="view.php" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 inline-block">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>