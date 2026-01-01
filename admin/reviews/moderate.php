<?php
/**
 * Review Moderation Page (Admin)
 * Allows administrators to review and moderate flagged reviews.
 * Supports approving (unflagging) or removing inappropriate reviews.
 */

session_start();
// Include config if not already loaded
if (!defined('BASE_URL') && file_exists(__DIR__ . '/../../includes/config.php')) {
    require_once __DIR__ . '/../../includes/config.php';
}
require("../../connect.php");

// Verify user has admin privileges
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    die("Access Denied");
}

$error = "";
$success = "";

// Process moderation actions (approve or remove)
if (isset($_POST['action'])) {
    $review_id = (int) $_POST['review_id'];
    $action = escapeString($_POST['action']);

    if ($action == 'approve') {
        // Approve review: unflag it and reset report count
        // This makes the review visible to users again
        $update_sql = "UPDATE reviews SET is_flagged = FALSE, report_count = 0 WHERE review_id = $review_id";
        myQuery($update_sql);

        // Delete all reports for this review since it's been approved
        // This clears the report history for the approved review
        $delete_reports_sql = "DELETE FROM review_reports WHERE review_id = $review_id";
        myQuery($delete_reports_sql);

        // Log admin action for audit trail
        $admin_id = $_SESSION['user_id'];
        $log_sql = "INSERT INTO admin_logs (admin_id, action_type, target_type, target_id, description) 
                    VALUES ($admin_id, 'review_approve', 'review', $review_id, 'Approved flagged review')";
        myQuery($log_sql);

        $success = "Review approved and unflagged";
    } elseif ($action == 'remove') {
        // Remove review: permanently delete it from database
        // CASCADE will handle deletion of related review_reports records
        $delete_sql = "DELETE FROM reviews WHERE review_id = $review_id";
        myQuery($delete_sql);

        // Log admin action for audit trail
        $admin_id = $_SESSION['user_id'];
        $log_sql = "INSERT INTO admin_logs (admin_id, action_type, target_type, target_id, description) 
                    VALUES ($admin_id, 'review_removal', 'review', $review_id, 'Removed flagged review')";
        myQuery($log_sql);

        $success = "Review removed";
    }
}

// Handle filter and search
$filter = isset($_GET['filter']) ? escapeString($_GET['filter']) : 'flagged';
$search = isset($_GET['search']) ? escapeString($_GET['search']) : '';
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where_conditions = [];
if ($filter == 'flagged') {
    $where_conditions[] = "r.is_flagged = TRUE";
} elseif ($filter == 'all') {
    // Show all reviews
} elseif ($filter == 'reported') {
    $where_conditions[] = "r.report_count > 0";
}

if (!empty($search)) {
    $where_conditions[] = "(m.title LIKE '%$search%' OR u.username LIKE '%$search%')";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Retrieve reviews for moderation with pagination
// JOINs with users and movies tables to get context information
// Includes user ban status (u.is_banned) to display banned badge
// Orders by report count (most reported first) then by creation date
$reviews_sql = "SELECT r.*, u.username, u.email, u.is_banned, m.title as movie_title, m.movie_id
                FROM reviews r
                JOIN users u ON r.user_id = u.user_id
                JOIN movies m ON r.movie_id = m.movie_id
                $where_clause
                ORDER BY r.report_count DESC, r.created_at DESC
                LIMIT $per_page OFFSET $offset";
$reviews_result = myQuery($reviews_sql);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total
              FROM reviews r
              JOIN users u ON r.user_id = u.user_id
              JOIN movies m ON r.movie_id = m.movie_id
              $where_clause";
$count_result = myQuery($count_sql);
$total_reviews = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_reviews / $per_page);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderate Reviews - xGrab</title>
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
            Review Moderation
        </h1>

        <!-- Filter and Search -->
        <div class="mb-6 bg-gray-800 rounded-xl p-4 border border-gray-700 fade-in">
            <form method="get" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Filter</label>
                    <select name="filter"
                        class="w-full px-4 py-2 bg-gray-700 border-2 border-gray-600 rounded-lg text-gray-100 focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300">
                        <option value="flagged" <?php echo $filter == 'flagged' ? 'selected' : ''; ?>>Flagged Reviews</option>
                        <option value="reported" <?php echo $filter == 'reported' ? 'selected' : ''; ?>>All Reported</option>
                        <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>All Reviews</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Movie title or username"
                        class="w-full px-4 py-2 bg-gray-700 border-2 border-gray-600 rounded-lg text-gray-100 focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300">
                </div>
                <div class="flex items-end">
                    <button type="submit"
                        class="w-full px-4 py-2 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 font-medium">
                        Apply
                    </button>
                </div>
            </form>
        </div>

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

        <?php if (mysqli_num_rows($reviews_result) > 0): ?>
            <div class="space-y-6">
                <?php
                $delay = 0;
                while ($review = mysqli_fetch_assoc($reviews_result)):
                    $delay += 100;
                    ?>
                    <div class="bg-gray-800 rounded-xl shadow-md p-6 border border-gray-700 fade-in"
                        style="animation-delay: <?php echo $delay; ?>ms">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-100">
                                    <a href="../../movies/details.php?id=<?php echo $review['movie_id']; ?>"
                                        class="text-red-400 hover:text-red-300 transition-colors duration-300">
                                        <?php echo htmlspecialchars($review['movie_title']); ?>
                                    </a>
                                </h3>
                                <p class="text-sm text-gray-400">
                                    By <a href="../../profile/view.php?user_id=<?php echo $review['user_id']; ?>"
                                        class="text-red-400 hover:text-red-300 transition-colors duration-300">
                                        <?php echo htmlspecialchars($review['username']); ?>
                                    </a>
                                    <!-- Banned User Badge -->
                                    <!-- Display badge only if user is banned -->
                                    <?php if (isset($review['is_banned']) && $review['is_banned']): ?>
                                        <span class="bg-red-900 border border-red-700 text-red-200 px-2 py-1 rounded text-xs font-bold ml-2">Banned</span>
                                    <?php endif; ?>
                                    on <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="bg-red-600 text-white px-3 py-1 rounded-lg text-sm font-medium">
                                    <?php echo $review['report_count']; ?> Report(s)
                                </span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <span class="text-yellow-400 text-lg">★
                                <?php echo number_format($review['rating_value'], 1); ?></span>
                            <?php if ($review['is_spoiler']): ?>
                                <span
                                    class="bg-yellow-900 border border-yellow-600 text-yellow-300 px-2 py-1 rounded text-xs ml-2">Spoiler</span>
                            <?php endif; ?>
                        </div>

                        <p class="text-gray-300 mb-4"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>

                        <!-- Get report reasons -->
                        <?php
                        $reports_sql = "SELECT reason, created_at FROM review_reports WHERE review_id = " . $review['review_id'];
                        $reports_result = myQuery($reports_sql);
                        if (mysqli_num_rows($reports_result) > 0):
                            ?>
                            <div class="bg-red-900/30 border border-red-700 rounded-lg p-3 mb-4">
                                <p class="font-semibold text-sm mb-2 text-red-300">Report Reasons:</p>
                                <ul class="list-disc list-inside text-sm text-gray-300 space-y-1">
                                    <?php
                                    mysqli_data_seek($reports_result, 0);
                                    while ($report_item = mysqli_fetch_assoc($reports_result)):
                                        ?>
                                        <li><?php echo htmlspecialchars($report_item['reason']); ?>
                                            (<?php echo date('M d, Y', strtotime($report_item['created_at'])); ?>)
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="flex space-x-3">
                            <!-- Approve Button -->
                            <!-- Only show approve button for reviews that have reports (unreported reviews don't need approval) -->
                            <?php if ($review['report_count'] > 0): ?>
                                <form method="post" class="inline">
                                    <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit"
                                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all duration-300 shadow-md hover:shadow-lg font-medium"
                                        onclick="return confirm('Approve this review?');">
                                        Approve
                                    </button>
                                </form>
                            <?php endif; ?>
                            <!-- Remove Button -->
                            <form method="post" class="inline">
                                <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                <input type="hidden" name="action" value="remove">
                                <button type="submit"
                                    class="px-4 py-2 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-md hover:shadow-lg font-medium"
                                    onclick="return confirm('Remove this review? This action cannot be undone.');">
                                    Remove
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="flex justify-center items-center space-x-2 mt-6 fade-in">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>"
                            class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-all duration-300">
                            Previous
                        </a>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                        <a href="?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>"
                            class="px-4 py-2 <?php echo $i == $page ? 'bg-red-600' : 'bg-gray-700'; ?> text-white rounded-lg hover:bg-red-700 transition-all duration-300">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>"
                            class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-all duration-300">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
                <p class="text-center text-gray-400 text-sm mt-4">
                    Page <?php echo $page; ?> of <?php echo $total_pages; ?> (<?php echo $total_reviews; ?> total reviews)
                </p>
            <?php endif; ?>
        <?php else: ?>
            <div class="bg-gray-800 rounded-xl shadow-md p-8 text-center border border-gray-700 fade-in">
                <p class="text-gray-400">No reviews found.</p>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>