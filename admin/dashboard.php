<?php
/**
 * Admin Dashboard
 * Displays system-wide statistics and recent admin activity.
 * Access restricted to users with admin privileges.
 */

session_start();
// Include config if not already loaded (via connect.php)
if (!defined('BASE_URL') && file_exists(__DIR__ . '/../includes/config.php')) {
    require_once __DIR__ . '/../includes/config.php';
}
require("../connect.php");

// Verify user is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    $redirect_url = defined('BASE_URL') ? BASE_URL . 'login.php' : '../login.php';
    header("Location: " . $redirect_url);
    exit();
}

// Calculate system statistics for dashboard display
$stats = [];

// Count total movies in database
$movies_sql = "SELECT COUNT(*) as total FROM movies";
$movies_result = myQuery($movies_sql);
$stats['movies'] = mysqli_fetch_assoc($movies_result)['total'];

// Count total registered users
$users_sql = "SELECT COUNT(*) as total FROM users";
$users_result = myQuery($users_sql);
$stats['users'] = mysqli_fetch_assoc($users_result)['total'];

// Count total reviews submitted by users
$reviews_sql = "SELECT COUNT(*) as total FROM reviews";
$reviews_result = myQuery($reviews_sql);
$stats['reviews'] = mysqli_fetch_assoc($reviews_result)['total'];

// Count total watchlists created by users
$watchlists_sql = "SELECT COUNT(*) as total FROM watchlists";
$watchlists_result = myQuery($watchlists_sql);
$stats['watchlists'] = mysqli_fetch_assoc($watchlists_result)['total'];

// Count reviews that have been flagged for moderation
// These require admin attention
$flagged_sql = "SELECT COUNT(*) as total FROM reviews WHERE is_flagged = TRUE";
$flagged_result = myQuery($flagged_sql);
$stats['flagged_reviews'] = mysqli_fetch_assoc($flagged_result)['total'];

// Retrieve recent admin activity log entries
// JOIN with users table to get admin usernames
// Ordered by most recent first, limited to 10 entries
$activity_sql = "SELECT al.*, u.username 
                 FROM admin_logs al 
                 JOIN users u ON al.admin_id = u.user_id 
                 ORDER BY al.created_at DESC 
                 LIMIT 10";
$activity_result = myQuery($activity_sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - xGrab</title>
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
        <!-- Success/Error Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="mb-6 p-4 bg-green-800 border border-green-600 text-green-200 rounded-lg fade-in">
                <strong class="font-bold">Success!</strong> <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="mb-6 p-4 bg-red-800 border border-red-600 text-red-200 rounded-lg fade-in">
                <strong class="font-bold">Error!</strong> <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <div class="mb-8 fade-in">
            <h1 class="text-4xl font-bold mb-2 bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                Admin Dashboard
            </h1>
            <p class="text-gray-400">Manage your movie database</p>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <div
                class="bg-gray-800 rounded-xl shadow-md p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border-l-4 border-red-500 border border-gray-700 fade-in">
                <p class="text-gray-400 mb-2 text-sm font-medium">Total Movies</p>
                <p class="text-4xl font-bold bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                    <?php echo $stats['movies']; ?>
                </p>
            </div>
            <div
                class="bg-gray-800 rounded-xl shadow-md p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border-l-4 border-red-500 border border-gray-700 fade-in">
                <p class="text-gray-400 mb-2 text-sm font-medium">Total Users</p>
                <p class="text-4xl font-bold bg-gradient-to-r from-red-500 to-red-700 bg-clip-text text-transparent">
                    <?php echo $stats['users']; ?>
                </p>
            </div>
            <div
                class="bg-gray-800 rounded-xl shadow-md p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border-l-4 border-red-600 border border-gray-700 fade-in">
                <p class="text-gray-400 mb-2 text-sm font-medium">Total Reviews</p>
                <p class="text-4xl font-bold bg-gradient-to-r from-red-600 to-red-800 bg-clip-text text-transparent">
                    <?php echo $stats['reviews']; ?>
                </p>
            </div>
            <div
                class="bg-gray-800 rounded-xl shadow-md p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border-l-4 border-red-700 border border-gray-700 fade-in">
                <p class="text-gray-400 mb-2 text-sm font-medium">Total Watchlists</p>
                <p class="text-4xl font-bold bg-gradient-to-r from-red-700 to-red-900 bg-clip-text text-transparent">
                    <?php echo $stats['watchlists']; ?>
                </p>
            </div>
            <div
                class="bg-gray-800 rounded-xl shadow-md p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border-l-4 border-red-800 border border-gray-700 fade-in">
                <p class="text-gray-400 mb-2 text-sm font-medium">Flagged Reviews</p>
                <p class="text-4xl font-bold bg-gradient-to-r from-red-800 to-red-900 bg-clip-text text-transparent">
                    <?php echo $stats['flagged_reviews']; ?>
                </p>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div
                class="bg-gray-800 rounded-xl shadow-md p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-700 fade-in">
                <h2 class="text-2xl font-bold mb-4 text-gray-100">Movie Management</h2>
                <div class="space-y-3">
                    <a href="movies/add.php"
                        class="block px-4 py-3 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-md hover:shadow-lg text-center font-medium">
                        Add New Movie
                    </a>
                    <a href="<?php echo defined('BASE_URL') ? BASE_URL . 'movies/browse.php' : '../movies/browse.php'; ?>"
                        class="block bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-all duration-300 text-center">
                        Browse Movies
                    </a>
                </div>
            </div>

            <div class="bg-gray-800 rounded-xl shadow-md p-6 border border-gray-700 fade-in">
                <h2 class="text-xl font-bold mb-4 text-gray-100">Review Moderation</h2>
                <div class="space-y-2">
                    <a href="reviews/moderate.php"
                        class="block bg-gradient-to-r from-red-600 to-red-800 text-white px-4 py-2 rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 text-center">
                        Moderate Reviews
                    </a>
                    <a href="reports.php"
                        class="block bg-gradient-to-r from-red-700 to-red-900 text-white px-4 py-2 rounded-lg hover:from-red-800 hover:to-red-950 transition-all duration-300 text-center">
                        View Reports
                    </a>
                </div>
            </div>

            <div class="bg-gray-800 rounded-xl shadow-md p-6 border border-gray-700 fade-in">
                <h2 class="text-xl font-bold mb-4 text-gray-100">User Management</h2>
                <div class="space-y-2">
                    <a href="users/manage.php"
                        class="block bg-gradient-to-r from-red-600 to-red-800 text-white px-4 py-2 rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 text-center">
                        Manage Users
                    </a>
                </div>
            </div>

            <div class="bg-gray-800 rounded-xl shadow-md p-6 border border-gray-700 fade-in">
                <h2 class="text-xl font-bold mb-4 text-gray-100">Cast & Crew Management</h2>
                <div class="space-y-2">
                    <a href="cast/add.php"
                        class="block bg-gradient-to-r from-red-600 to-red-800 text-white px-4 py-2 rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 text-center">
                        Add Cast Member
                    </a>
                    <a href="cast/index.php"
                        class="block bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-all duration-300 text-center">
                        Manage Cast Members
                    </a>
                    <a href="crew/add.php"
                        class="block bg-gradient-to-r from-red-700 to-red-900 text-white px-4 py-2 rounded-lg hover:from-red-800 hover:to-red-950 transition-all duration-300 text-center">
                        Add Crew Member
                    </a>
                    <a href="crew/index.php"
                        class="block bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-all duration-300 text-center">
                        Manage Crew Members
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-gray-800 rounded-xl shadow-md p-6 border border-gray-700">
            <h2 class="text-xl font-bold mb-4 text-gray-100">Recent Admin Activity</h2>
            <?php if (mysqli_num_rows($activity_result) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-700">
                        <thead class="bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Admin</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Action</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Target</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-gray-800 divide-y divide-gray-700">
                            <?php while ($activity = mysqli_fetch_assoc($activity_result)): ?>
                                <tr class="hover:bg-gray-700 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        <?php echo htmlspecialchars($activity['username']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        <?php echo htmlspecialchars($activity['action_type']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        <?php echo htmlspecialchars($activity['target_type']); ?>
                                        <?php if ($activity['target_id']): ?>
                                            #<?php echo $activity['target_id']; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-300">
                                        <?php echo htmlspecialchars($activity['description']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                        <?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-400">No recent activity</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>