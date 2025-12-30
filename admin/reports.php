<?php
session_start();
require("../connect.php");

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    die("Access Denied");
}

// Get all reported reviews with details - group by review_id
$reports_sql = "SELECT r.review_id, r.review_text, r.rating_value, r.is_spoiler, r.report_count,
                r.user_id as reviewer_id,
                u1.username as reviewer_username,
                m.title as movie_title, m.movie_id
                FROM reviews r
                JOIN users u1 ON r.user_id = u1.user_id
                JOIN movies m ON r.movie_id = m.movie_id
                WHERE r.report_count > 0
                ORDER BY r.report_count DESC, r.created_at DESC";
$reports_result = myQuery($reports_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Reports - Admin</title>
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
        <a href="dashboard.php" class="inline-flex items-center text-red-400 hover:text-red-300 mb-6 font-medium transition-colors duration-300 fade-in">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Dashboard
        </a>
        
        <h1 class="text-4xl font-bold mb-6 bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent fade-in">
            Review Reports
        </h1>
        
        <?php if (mysqli_num_rows($reports_result) > 0): ?>
            <div class="space-y-6">
                <?php 
                $delay = 0;
                while($report = mysqli_fetch_assoc($reports_result)): 
                    $delay += 100;
                ?>
                    <div class="bg-gray-800 rounded-xl shadow-md p-6 border border-gray-700 fade-in" style="animation-delay: <?php echo $delay; ?>ms">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-100">
                                    <a href="../movies/details.php?id=<?php echo $report['movie_id']; ?>" 
                                       class="text-red-400 hover:text-red-300 transition-colors duration-300">
                                        <?php echo htmlspecialchars($report['movie_title']); ?>
                                    </a>
                                </h3>
                                <p class="text-sm text-gray-400">
                                    Review by <a href="../profile/view.php?user_id=<?php echo $report['reviewer_id']; ?>" 
                                                 class="text-red-400 hover:text-red-300 transition-colors duration-300">
                                        <?php echo htmlspecialchars($report['reviewer_username']); ?>
                                    </a>
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="bg-red-600 text-white px-3 py-1 rounded-lg text-sm font-medium">
                                    <?php echo $report['report_count']; ?> Total Report(s)
                                </span>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <span class="text-yellow-400 text-lg">★ <?php echo number_format($report['rating_value'], 1); ?></span>
                            <?php if ($report['is_spoiler']): ?>
                                <span class="bg-yellow-900 border border-yellow-600 text-yellow-300 px-2 py-1 rounded text-xs ml-2">Spoiler</span>
                            <?php endif; ?>
                        </div>
                        
                        <p class="text-gray-300 mb-4"><?php echo nl2br(htmlspecialchars($report['review_text'])); ?></p>
                        
                        <div class="bg-red-900/30 border border-red-700 rounded-lg p-3 mb-4">
                            <p class="font-semibold text-sm mb-2 text-red-300">Report Details:</p>
                            <ul class="list-disc list-inside text-sm space-y-1 text-gray-300">
                                <?php
                                // Get all reports for this review
                                $all_reports_sql = "SELECT rr.*, u.username 
                                                    FROM review_reports rr
                                                    JOIN users u ON rr.user_id = u.user_id
                                                    WHERE rr.review_id = " . $report['review_id'];
                                $all_reports_result = myQuery($all_reports_sql);
                                while($rpt = mysqli_fetch_assoc($all_reports_result)):
                                ?>
                                    <li>
                                        <strong class="text-gray-200"><?php echo htmlspecialchars($rpt['username']); ?></strong>: 
                                        <?php echo htmlspecialchars($rpt['reason']); ?>
                                        (<?php echo date('M d, Y H:i', strtotime($rpt['created_at'])); ?>)
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                        
                        <div class="flex space-x-3">
                            <a href="reviews/moderate.php" 
                               class="px-4 py-2 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-md hover:shadow-lg font-medium">
                                Go to Moderation
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="bg-gray-800 rounded-xl shadow-md p-8 text-center border border-gray-700 fade-in">
                <p class="text-gray-400">No reports found.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

