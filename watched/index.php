<?php
/**
 * Watched Movies Index Page
 * Displays all movies the user has marked as watched.
 * Supports sorting and pagination for large watched lists.
 */

session_start();
require("../connect.php");
require("../image_handler.php");

// Require user to be logged in to view watched movies
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Extract and sanitize sorting and pagination parameters from URL
$sort_by = isset($_GET['sort_by']) ? escapeString($_GET['sort_by']) : 'watched_date';
$sort_order = isset($_GET['sort_order']) ? escapeString($_GET['sort_order']) : 'DESC';
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$per_page = 24;
$offset = ($page - 1) * $per_page;

// Build ORDER BY clause based on sort parameter
// Maps sort options to appropriate database columns
$order_by = "uwm.watched_date";
if ($sort_by == 'title') {
    $order_by = "m.title";
} elseif ($sort_by == 'year') {
    $order_by = "m.release_year";
} elseif ($sort_by == 'rating') {
    $order_by = "m.average_rating";
} elseif ($sort_by == 'watched_date') {
    $order_by = "uwm.watched_date";
}

// Retrieve watched movies with full movie details
// JOINs with movies table to get complete movie information
// Includes watched_date to show when user marked movie as watched
$sql = "SELECT m.*, uwm.watched_date 
        FROM user_watched_movies uwm 
        JOIN movies m ON uwm.movie_id = m.movie_id 
        WHERE uwm.user_id = $user_id 
        ORDER BY $order_by $sort_order 
        LIMIT $per_page OFFSET $offset";
$result = myQuery($sql);

// Calculate total watched movies count for pagination
$count_sql = "SELECT COUNT(*) as total FROM user_watched_movies WHERE user_id = $user_id";
$count_result = myQuery($count_sql);
$total_movies = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_movies / $per_page);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Watched Movies - xGrab</title>
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
        <h1
            class="text-4xl font-bold mb-6 bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent fade-in">
            My Watched Movies
        </h1>

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

        <!-- Statistics and Sort -->
        <div class="bg-gray-800 rounded-xl shadow-lg p-6 mb-6 border border-gray-700 fade-in">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <p class="text-gray-400 text-sm">Total Watched</p>
                    <p class="text-3xl font-bold text-green-400"><?php echo $total_movies; ?></p>
                </div>

                <form method="get" class="flex flex-wrap gap-3 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Sort By:</label>
                        <select name="sort_by"
                            class="px-3 py-2 bg-gray-700 border-2 border-gray-600 rounded-lg text-gray-100 focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300">
                            <option value="watched_date" <?php echo $sort_by == 'watched_date' ? 'selected' : ''; ?>>Date
                                Watched</option>
                            <option value="title" <?php echo $sort_by == 'title' ? 'selected' : ''; ?>>Title</option>
                            <option value="year" <?php echo $sort_by == 'year' ? 'selected' : ''; ?>>Release Year</option>
                            <option value="rating" <?php echo $sort_by == 'rating' ? 'selected' : ''; ?>>Rating</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Order:</label>
                        <select name="sort_order"
                            class="px-3 py-2 bg-gray-700 border-2 border-gray-600 rounded-lg text-gray-100 focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300">
                            <option value="DESC" <?php echo $sort_order == 'DESC' ? 'selected' : ''; ?>>Descending
                            </option>
                            <option value="ASC" <?php echo $sort_order == 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                        </select>
                    </div>
                    <button type="submit"
                        class="px-4 py-2 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl font-medium">
                        Sort
                    </button>
                </form>
            </div>
        </div>

        <!-- Movies Grid -->
        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6 mb-8">
                <?php
                $delay = 0;
                mysqli_data_seek($result, 0);
                while ($movie = mysqli_fetch_assoc($result)):
                    $delay += 50;
                    ?>
                    <div class="group bg-gray-800 rounded-xl shadow-md overflow-hidden hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-700 fade-in"
                        style="animation-delay: <?php echo $delay; ?>ms">
                        <div class="relative">
                            <a href="../movies/details.php?id=<?php echo $movie['movie_id']; ?>">
                                <div class="aspect-[2/3] bg-gray-200 flex items-center justify-center relative overflow-hidden">
                                    <?php if ($movie['poster_image']): ?>
                                        <img src="<?php echo htmlspecialchars(getImagePath($movie['poster_image'], 'poster')); ?>"
                                            alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                        <div
                                            class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                        </div>
                                    <?php else: ?>
                                        <div
                                            class="w-full h-full bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center">
                                            <span class="text-white text-sm font-medium">No Poster</span>
                                        </div>
                                    <?php endif; ?>
                                    <!-- Watched Badge -->
                                    <div
                                        class="absolute top-2 right-2 bg-green-500 text-white px-2 py-1 rounded-md text-xs font-bold shadow-lg flex items-center space-x-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        <span>Watched</span>
                                    </div>
                                    <!-- Rating Badge -->
                                    <div
                                        class="absolute top-2 left-2 bg-yellow-400 text-gray-900 px-2 py-1 rounded-md text-xs font-bold flex items-center space-x-1 shadow-lg">
                                        <span>★</span>
                                        <span><?php echo number_format($movie['average_rating'], 1); ?></span>
                                    </div>
                                </div>
                            </a>
                            <!-- Remove from Watched Button -->
                            <form method="post" action="toggle.php" class="absolute bottom-2 right-2">
                                <input type="hidden" name="movie_id" value="<?php echo $movie['movie_id']; ?>">
                                <input type="hidden" name="redirect_url"
                                    value="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?' . http_build_query($_GET)); ?>">
                                <button type="submit"
                                    class="relative group/icon p-2 bg-red-600/80 rounded-full hover:bg-red-600 transition-all duration-300"
                                    title="Remove from Watched" onclick="return confirm('Remove this movie from watched?');">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                        <div class="p-4">
                            <h3
                                class="font-bold text-sm text-gray-100 truncate group-hover:text-red-400 transition-colors duration-300 mb-1">
                                <a href="../movies/details.php?id=<?php echo $movie['movie_id']; ?>">
                                    <?php echo htmlspecialchars($movie['title']); ?>
                                </a>
                            </h3>
                            <p class="text-xs text-gray-400 mb-2"><?php echo $movie['release_year']; ?></p>
                            <p class="text-xs text-gray-500">Watched
                                <?php echo date('M d, Y', strtotime($movie['watched_date'])); ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="flex justify-center items-center space-x-2 mb-8 fade-in">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>"
                            class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-all duration-300">
                            Previous
                        </a>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                        <a href="?page=<?php echo $i; ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>"
                            class="px-4 py-2 <?php echo $i == $page ? 'bg-red-600' : 'bg-gray-700'; ?> text-white rounded-lg hover:bg-red-700 transition-all duration-300">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>"
                            class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-all duration-300">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="bg-gray-800 rounded-lg shadow-md p-8 text-center border border-gray-700 fade-in">
                <p class="text-gray-400 mb-4">You haven't watched any movies yet.</p>
                <a href="../movies/browse.php"
                    class="px-6 py-2 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl inline-block font-medium">
                    Browse Movies
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>