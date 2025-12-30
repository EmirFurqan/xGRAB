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

// Handle create watchlist (inline form)
if (isset($_POST['submit'])) {
    $watchlist_name = escapeString($_POST['watchlist_name']);
    
    if (empty($watchlist_name)) {
        $error = "Watchlist name is required";
    } elseif (strlen($watchlist_name) > 50) {
        $error = "Watchlist name must be 50 characters or less";
    } else {
        $insert_sql = "INSERT INTO watchlists (user_id, watchlist_name) VALUES ($user_id, '$watchlist_name')";
        if (myQuery($insert_sql)) {
            $success = "Watchlist created successfully!";
        } else {
            $error = "Failed to create watchlist";
        }
    }
}

// Handle delete watchlist
if (isset($_GET['delete'])) {
    $watchlist_id = (int)$_GET['delete'];
    // Verify ownership
    $check_sql = "SELECT * FROM watchlists WHERE watchlist_id = $watchlist_id AND user_id = $user_id";
    $check_result = myQuery($check_sql);
    if (mysqli_num_rows($check_result) > 0) {
        $delete_sql = "DELETE FROM watchlists WHERE watchlist_id = $watchlist_id";
        if (myQuery($delete_sql)) {
            $success = "Watchlist deleted successfully";
        } else {
            $error = "Failed to delete watchlist";
        }
    } else {
        $error = "Watchlist not found or access denied";
    }
}

// Get success/error from URL
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// Get all watchlists for user
$sql = "SELECT w.*, 
        (SELECT COUNT(*) FROM watchlist_movies WHERE watchlist_id = w.watchlist_id) as movie_count
        FROM watchlists w 
        WHERE w.user_id = $user_id 
        ORDER BY w.date_created DESC";
$result = myQuery($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Watchlists - Movie Database</title>
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
<body class="bg-gray-900 min-h-screen text-gray-100">
    <?php require("../includes/nav.php"); ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8 fade-in">
            <div>
                <h1 class="text-4xl font-bold mb-2 bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                    My Watchlists
                </h1>
                <p class="text-gray-400">Manage your movie collections</p>
            </div>
            <button onclick="document.getElementById('createWatchlistSection').classList.toggle('hidden')" 
                    class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 font-medium">
                + Create New
            </button>
        </div>
        
        <?php if($error): ?>
            <div class="bg-red-800 border border-red-600 text-red-200 px-4 py-3 rounded-lg mb-4 fade-in">
                <strong class="font-bold">Error!</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="bg-green-800 border border-green-600 text-green-200 px-4 py-3 rounded-lg mb-4 fade-in">
                <strong class="font-bold">Success!</strong> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <!-- Create Watchlist Section (Collapsible) -->
        <div id="createWatchlistSection" class="hidden bg-gray-800 rounded-xl shadow-lg p-6 mb-6 border border-gray-700 fade-in">
            <h2 class="text-2xl font-bold mb-4 bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                Create New Watchlist
            </h2>
            <form method="post" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Watchlist Name:</label>
                    <input type="text" name="watchlist_name" required maxlength="50"
                           class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400"
                           placeholder="Enter watchlist name...">
                    <p class="text-xs text-gray-400 mt-2">Maximum 50 characters</p>
                </div>
                <div class="flex space-x-3">
                    <button type="submit" name="submit" 
                            class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 font-medium">
                        Create Watchlist
                    </button>
                    <button type="button" onclick="document.getElementById('createWatchlistSection').classList.add('hidden')" 
                            class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 font-medium">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
        
        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php 
                $delay = 0;
                while($watchlist = mysqli_fetch_assoc($result)): 
                    $delay += 100;
                ?>
                    <div class="bg-gray-800 rounded-xl shadow-md p-6 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border-l-4 border-red-500 border border-gray-700 fade-in" style="animation-delay: <?php echo $delay; ?>ms">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-2xl font-bold">
                                <a href="view.php?id=<?php echo $watchlist['watchlist_id']; ?>" 
                                   class="text-gray-100 hover:text-red-400 transition-colors duration-300">
                                    <?php echo htmlspecialchars($watchlist['watchlist_name']); ?>
                                </a>
                            </h2>
                            <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <p class="text-gray-400 text-sm mb-4">
                            Created: <?php echo date('M d, Y', strtotime($watchlist['date_created'])); ?>
                        </p>
                        <div class="mb-6">
                            <div class="flex items-center space-x-2">
                                <div class="w-12 h-12 bg-gradient-to-br from-red-600 to-red-800 rounded-lg flex items-center justify-center">
                                    <span class="text-white font-bold text-lg"><?php echo $watchlist['movie_count']; ?></span>
                                </div>
                                <span class="text-gray-300 font-medium">movie<?php echo $watchlist['movie_count'] != 1 ? 's' : ''; ?></span>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <a href="view.php?id=<?php echo $watchlist['watchlist_id']; ?>" 
                               class="flex-1 px-4 py-2 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 text-sm font-medium text-center shadow-md hover:shadow-lg">
                                View
                            </a>
                            <a href="edit.php?id=<?php echo $watchlist['watchlist_id']; ?>" 
                               class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-all duration-300 text-sm font-medium shadow-md hover:shadow-lg">
                                Edit
                            </a>
                            <a href="?delete=<?php echo $watchlist['watchlist_id']; ?>" 
                               class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all duration-300 text-sm font-medium shadow-md hover:shadow-lg"
                               onclick="return confirm('Are you sure you want to delete this watchlist?');">
                                Delete
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="bg-gray-800 rounded-lg shadow-md p-8 text-center border border-gray-700 fade-in">
                <p class="text-gray-400 mb-4">You don't have any watchlists yet.</p>
                <button onclick="document.getElementById('createWatchlistSection').classList.remove('hidden'); document.getElementById('createWatchlistSection').scrollIntoView({ behavior: 'smooth', block: 'nearest' });" 
                        class="bg-gradient-to-r from-red-600 to-red-800 text-white px-6 py-2 rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 inline-block font-medium">
                    Create Your First Watchlist
                </button>
            </div>
        <?php endif; ?>
        
        <script>
            // Auto-show create section if there's an error (form was submitted)
            <?php if ($error && isset($_POST['submit'])): ?>
                document.addEventListener('DOMContentLoaded', function() {
                    document.getElementById('createWatchlistSection').classList.remove('hidden');
                    document.getElementById('createWatchlistSection').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                });
            <?php endif; ?>
        </script>
    </div>
</body>
</html>

