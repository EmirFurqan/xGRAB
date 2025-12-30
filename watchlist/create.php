<?php
/**
 * Create Watchlist Page
 * Allows users to create a new watchlist with a custom name.
 * Validates watchlist name length and redirects to watchlist index on success.
 */

session_start();
require("../connect.php");

// Require user to be logged in to create watchlists
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

// Process watchlist creation form submission
if (isset($_POST['submit'])) {
    // Sanitize watchlist name input
    $watchlist_name = escapeString($_POST['watchlist_name']);

    // Validate watchlist name is not empty
    if (empty($watchlist_name)) {
        $error = "Watchlist name is required";
    }
    // Validate watchlist name doesn't exceed database column limit (50 characters)
    elseif (strlen($watchlist_name) > 50) {
        $error = "Watchlist name must be 50 characters or less";
    } else {
        // Insert new watchlist into database
        // date_created will be set automatically via DEFAULT CURRENT_TIMESTAMP
        $insert_sql = "INSERT INTO watchlists (user_id, watchlist_name) VALUES ($user_id, '$watchlist_name')";
        if (myQuery($insert_sql)) {
            $success = "Watchlist created successfully!";
            // Redirect to watchlist index page with success message
            header("Location: index.php?success=" . urlencode($success));
            exit();
        } else {
            $error = "Failed to create watchlist";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Watchlist - xGrab</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>

<body class="bg-gray-900 min-h-screen text-gray-100">
    <?php require("../includes/nav.php"); ?>

    <div class="container mx-auto px-4 py-8">
        <a href="index.php"
            class="text-indigo-400 hover:text-indigo-300 mb-4 inline-block transition-colors duration-300 fade-in">
            <span class="flex items-center space-x-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Back to Watchlists</span>
            </span>
        </a>

        <div class="bg-gray-800 rounded-xl shadow-lg p-8 max-w-2xl border border-gray-700 fade-in">
            <h1
                class="text-3xl font-bold mb-6 bg-gradient-to-r from-indigo-400 to-purple-400 bg-clip-text text-transparent">
                Create New Watchlist
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

            <form method="post" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Watchlist Name:</label>
                    <input type="text" name="watchlist_name" required maxlength="50"
                        class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 transition-all duration-300 text-gray-100 placeholder-gray-400"
                        placeholder="Enter watchlist name...">
                    <p class="text-xs text-gray-400 mt-2">Maximum 50 characters</p>
                </div>

                <div class="flex space-x-3 pt-2">
                    <button type="submit" name="submit"
                        class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 font-medium">
                        Create Watchlist
                    </button>
                    <a href="index.php"
                        class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 inline-block font-medium">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>