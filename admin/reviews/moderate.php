<?php
session_start();
require("../../connect.php");

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    die("Access Denied");
}

$error = "";
$success = "";

// Handle approve/remove action
if (isset($_POST['action'])) {
    $review_id = (int) $_POST['review_id'];
    $action = escapeString($_POST['action']);

    if ($action == 'approve') {
        // Unflag review
        $update_sql = "UPDATE reviews SET is_flagged = FALSE, report_count = 0 WHERE review_id = $review_id";
        myQuery($update_sql);

        // Delete reports
        $delete_reports_sql = "DELETE FROM review_reports WHERE review_id = $review_id";
        myQuery($delete_reports_sql);

        // Log action
        $admin_id = $_SESSION['user_id'];
        $log_sql = "INSERT INTO admin_logs (admin_id, action_type, target_type, target_id, description) 
                    VALUES ($admin_id, 'review_approve', 'review', $review_id, 'Approved flagged review')";
        myQuery($log_sql);

        $success = "Review approved and unflagged";
    } elseif ($action == 'remove') {
        // Delete review
        $delete_sql = "DELETE FROM reviews WHERE review_id = $review_id";
        myQuery($delete_sql);

        // Log action
        $admin_id = $_SESSION['user_id'];
        $log_sql = "INSERT INTO admin_logs (admin_id, action_type, target_type, target_id, description) 
                    VALUES ($admin_id, 'review_removal', 'review', $review_id, 'Removed flagged review')";
        myQuery($log_sql);

        $success = "Review removed";
    }
}

// Get flagged reviews
$reviews_sql = "SELECT r.*, u.username, u.email, m.title as movie_title, m.movie_id
                FROM reviews r
                JOIN users u ON r.user_id = u.user_id
                JOIN movies m ON r.movie_id = m.movie_id
                WHERE r.is_flagged = TRUE
                ORDER BY r.report_count DESC, r.created_at DESC";
$reviews_result = myQuery($reviews_sql);
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
                            <form method="post" class="inline">
                                <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit"
                                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all duration-300 shadow-md hover:shadow-lg font-medium"
                                    onclick="return confirm('Approve this review?');">
                                    Approve
                                </button>
                            </form>
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
        <?php else: ?>
            <div class="bg-gray-800 rounded-xl shadow-md p-8 text-center border border-gray-700 fade-in">
                <p class="text-gray-400">No flagged reviews to moderate.</p>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>