<?php
session_start();
require("../../connect.php");

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    die("Access Denied");
}

// Get all users
$users_sql = "SELECT u.*, 
              (SELECT COUNT(*) FROM reviews WHERE user_id = u.user_id) as review_count,
              (SELECT COUNT(*) FROM watchlists WHERE user_id = u.user_id) as watchlist_count
              FROM users u 
              ORDER BY u.created_at DESC";
$users_result = myQuery($users_sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - xGrab</title>
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
    <?php require("../../includes/nav.php"); ?>

    <div class="container mx-auto px-4 py-8">
        <a href="../dashboard.php"
            class="inline-flex items-center text-red-400 hover:text-red-300 mb-6 font-medium transition-colors duration-300 fade-in">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Dashboard
        </a>

        <h1
            class="text-4xl font-bold mb-6 bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent fade-in">
            User Management
        </h1>

        <div class="bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-700 fade-in">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Username</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Join Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Reviews</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Watchlists</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-gray-800 divide-y divide-gray-700">
                        <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                            <tr class="hover:bg-gray-700 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                    <?php echo $user['user_id']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <a href="../../profile/view.php?user_id=<?php echo $user['user_id']; ?>"
                                        class="text-red-400 hover:text-red-300 transition-colors duration-300">
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                    <?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                    <?php echo date('M d, Y', strtotime($user['join_date'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                    <?php echo $user['review_count']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                    <?php echo $user['watchlist_count']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php if ($user['is_admin']): ?>
                                        <span
                                            class="bg-yellow-400 text-gray-900 px-2 py-1 rounded text-xs font-bold">Admin</span>
                                    <?php else: ?>
                                        <span class="bg-gray-600 text-white px-2 py-1 rounded text-xs font-medium">User</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <a href="../../profile/view.php?user_id=<?php echo $user['user_id']; ?>"
                                        class="text-red-400 hover:text-red-300 transition-colors duration-300">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>