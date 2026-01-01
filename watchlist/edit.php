<?php
/**
 * Edit Watchlist Page
 * Allows users to rename their watchlists.
 * Verifies watchlist ownership before allowing edits.
 */

session_start();
require("../connect.php");

// Require user to be logged in to edit watchlists
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Validate watchlist ID parameter
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$watchlist_id = (int) $_GET['id'];
$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

// Retrieve watchlist information and verify ownership
$watchlist_sql = "SELECT * FROM watchlists WHERE watchlist_id = $watchlist_id AND user_id = $user_id";
$watchlist_result = myQuery($watchlist_sql);

// Redirect if watchlist doesn't exist or user doesn't own it
if (mysqli_num_rows($watchlist_result) == 0) {
    header("Location: index.php");
    exit();
}
$watchlist = mysqli_fetch_assoc($watchlist_result);

// Process watchlist rename form submission
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
        // Update watchlist name in database
        $update_sql = "UPDATE watchlists SET watchlist_name = '$watchlist_name' WHERE watchlist_id = $watchlist_id";
        if (myQuery($update_sql)) {
            $success = "Watchlist renamed successfully!";
            // Redirect to watchlist index with success message
            header("Location: index.php?success=" . urlencode($success));
            exit();
        } else {
            $error = "Failed to rename watchlist";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rename Watchlist - xGrab</title>
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
        <a href="index.php"
            class="text-red-400 hover:text-red-300 mb-4 inline-block transition-colors duration-300 fade-in">
            <span class="flex items-center space-x-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Back to Watchlists</span>
            </span>
        </a>

        <div class="bg-gray-800 rounded-xl shadow-lg p-8 max-w-2xl border border-gray-700 fade-in">
            <h1 class="text-3xl font-bold mb-6 bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                Rename Watchlist
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
                    <input type="text" name="watchlist_name"
                        value="<?php echo htmlspecialchars($watchlist['watchlist_name']); ?>" required maxlength="50"
                        class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400">
                    <p class="text-xs text-gray-400 mt-2">Maximum 50 characters</p>
                </div>

                <div class="flex space-x-3 pt-2">
                    <button type="submit" name="submit"
                        class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 font-medium">
                        Rename Watchlist
                    </button>
                    <a href="index.php"
                        class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 inline-block font-medium">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php require("../includes/footer.php"); ?>
</body>

</html>