<?php
session_start();
require("../connect.php");
require("../image_handler.php");

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
    $watchlist_id = (int) $_GET['delete'];
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

// Get all watchlists for user with movie count
$sql = "SELECT w.*, 
        (SELECT COUNT(*) FROM watchlist_movies WHERE watchlist_id = w.watchlist_id) as movie_count
        FROM watchlists w 
        WHERE w.user_id = $user_id 
        ORDER BY w.date_created DESC";
$result = myQuery($sql);

// Store watchlists and fetch poster previews for each
$watchlists = [];
while ($watchlist = mysqli_fetch_assoc($result)) {
    // Get up to 6 movie posters for this watchlist
    $poster_sql = "SELECT m.poster_image, m.title 
                   FROM watchlist_movies wm 
                   JOIN movies m ON wm.movie_id = m.movie_id 
                   WHERE wm.watchlist_id = {$watchlist['watchlist_id']} 
                   ORDER BY wm.date_added DESC 
                   LIMIT 6";
    $poster_result = myQuery($poster_sql);
    $posters = [];
    while ($poster = mysqli_fetch_assoc($poster_result)) {
        $posters[] = $poster;
    }
    $watchlist['posters'] = $posters;
    $watchlists[] = $watchlist;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Watchlists - xGrab</title>
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
            animation: fadeIn 0.6s ease-out forwards;
            opacity: 0;
        }

        /* Stacked poster effect */
        .poster-stack {
            display: flex;
            position: relative;
            height: 120px;
        }

        .poster-stack .poster-item {
            position: absolute;
            width: 80px;
            height: 120px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
            transition: all 0.3s ease;
            border: 2px solid rgba(255, 255, 255, 0.1);
        }

        .poster-stack .poster-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .poster-stack:hover .poster-item {
            transform: translateY(-5px);
        }

        /* Fan out effect on hover */
        .watchlist-card:hover .poster-stack .poster-item:nth-child(1) {
            transform: translateX(-10px) rotate(-5deg);
        }

        .watchlist-card:hover .poster-stack .poster-item:nth-child(2) {
            transform: translateX(0px) rotate(-2deg);
        }

        .watchlist-card:hover .poster-stack .poster-item:nth-child(3) {
            transform: translateX(10px) rotate(2deg);
        }

        .watchlist-card:hover .poster-stack .poster-item:nth-child(4) {
            transform: translateX(20px) rotate(5deg);
        }

        .watchlist-card:hover .poster-stack .poster-item:nth-child(5) {
            transform: translateX(30px) rotate(8deg);
        }

        .watchlist-card:hover .poster-stack .poster-item:nth-child(6) {
            transform: translateX(40px) rotate(10deg);
        }

        /* Empty state placeholder */
        .empty-poster {
            background: linear-gradient(135deg, #374151 0%, #1f2937 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .more-indicator {
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.9) 0%, rgba(153, 27, 27, 0.9) 100%);
            backdrop-filter: blur(4px);
        }
    </style>
</head>

<body class="bg-gray-900 min-h-screen text-gray-100">
    <?php require("../includes/nav.php"); ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4 fade-in">
            <div>
                <h1
                    class="text-4xl font-bold mb-2 bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                    My Watchlists
                </h1>
                <p class="text-gray-400">Organize and track your movie collections</p>
            </div>
            <button onclick="document.getElementById('createWatchlistSection').classList.toggle('hidden')"
                class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 font-medium flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create New
            </button>
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

        <!-- Create Watchlist Section (Collapsible) -->
        <div id="createWatchlistSection"
            class="hidden bg-gray-800 rounded-xl shadow-lg p-6 mb-6 border border-gray-700 fade-in">
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
                    <button type="button"
                        onclick="document.getElementById('createWatchlistSection').classList.add('hidden')"
                        class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 font-medium">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <?php if (count($watchlists) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <?php
                $delay = 0;
                foreach ($watchlists as $watchlist):
                    $delay += 100;
                    $has_movies = count($watchlist['posters']) > 0;
                    $remaining_count = max(0, $watchlist['movie_count'] - 5);
                    ?>
                    <div class="watchlist-card group bg-gradient-to-br from-gray-800 to-gray-850 rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2 border border-gray-700 fade-in"
                        style="animation-delay: <?php echo $delay; ?>ms">

                        <!-- Poster Preview Section -->
                        <a href="view.php?id=<?php echo $watchlist['watchlist_id']; ?>" class="block p-5 pb-3">
                            <?php if ($has_movies): ?>
                                <div class="poster-stack mb-4">
                                    <?php
                                    $poster_count = min(count($watchlist['posters']), 5);
                                    foreach (array_slice($watchlist['posters'], 0, 5) as $idx => $poster):
                                        $offset = $idx * 50;
                                        $zIndex = 10 - $idx;
                                        ?>
                                        <div class="poster-item"
                                            style="left: <?php echo $offset; ?>px; z-index: <?php echo $zIndex; ?>;">
                                            <?php if ($poster['poster_image']): ?>
                                                <img src="<?php echo htmlspecialchars(getImagePath($poster['poster_image'], 'poster')); ?>"
                                                    alt="<?php echo htmlspecialchars($poster['title']); ?>" loading="lazy">
                                            <?php else: ?>
                                                <div class="empty-poster w-full h-full">
                                                    <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>

                                    <!-- More indicator if there are more movies -->
                                    <?php if ($remaining_count > 0): ?>
                                        <div class="poster-item more-indicator flex items-center justify-center"
                                            style="left: <?php echo $poster_count * 50; ?>px; z-index: 5;">
                                            <span class="text-white font-bold text-lg">+<?php echo $remaining_count; ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <p
                                    class="text-xs text-gray-500 group-hover:text-red-400 transition-colors duration-300 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Click to explore all movies
                                </p>
                            <?php else: ?>
                                <div
                                    class="h-32 flex items-center justify-center bg-gradient-to-br from-gray-700/50 to-gray-800/50 rounded-xl border-2 border-dashed border-gray-600 mb-4">
                                    <div class="text-center">
                                        <svg class="w-10 h-10 mx-auto text-gray-500 mb-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 4v16M17 4v16M3 8h18M3 16h18" />
                                        </svg>
                                        <p class="text-gray-500 text-sm">No movies yet</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </a>

                        <!-- Watchlist Info Section -->
                        <div class="px-5 pb-5">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <h2
                                        class="text-xl font-bold text-white group-hover:text-red-400 transition-colors duration-300 truncate">
                                        <a href="view.php?id=<?php echo $watchlist['watchlist_id']; ?>">
                                            <?php echo htmlspecialchars($watchlist['watchlist_name']); ?>
                                        </a>
                                    </h2>
                                    <p class="text-gray-500 text-xs mt-1">
                                        Created <?php echo date('M d, Y', strtotime($watchlist['date_created'])); ?>
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 bg-red-600/20 px-3 py-1.5 rounded-full">
                                    <svg class="w-4 h-4 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" />
                                    </svg>
                                    <span class="text-red-400 font-bold"><?php echo $watchlist['movie_count']; ?></span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex gap-2 mt-4">
                                <a href="view.php?id=<?php echo $watchlist['watchlist_id']; ?>"
                                    class="flex-1 px-4 py-2.5 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-lg hover:from-red-500 hover:to-red-600 transition-all duration-300 text-sm font-medium text-center shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Open
                                </a>
                                <a href="edit.php?id=<?php echo $watchlist['watchlist_id']; ?>"
                                    class="px-3 py-2.5 bg-gray-700 text-gray-300 rounded-lg hover:bg-gray-600 hover:text-white transition-all duration-300 text-sm shadow-md hover:shadow-lg"
                                    title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                <a href="?delete=<?php echo $watchlist['watchlist_id']; ?>"
                                    class="px-3 py-2.5 bg-gray-700 text-gray-300 rounded-lg hover:bg-red-600 hover:text-white transition-all duration-300 text-sm shadow-md hover:shadow-lg"
                                    onclick="return confirm('Are you sure you want to delete this watchlist?');" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div
                class="bg-gradient-to-br from-gray-800 to-gray-850 rounded-2xl shadow-lg p-12 text-center border border-gray-700 fade-in">
                <div
                    class="w-20 h-20 mx-auto mb-6 bg-gradient-to-br from-red-600/20 to-red-800/20 rounded-full flex items-center justify-center">
                    <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-white mb-2">No Watchlists Yet</h3>
                <p class="text-gray-400 mb-6 max-w-md mx-auto">Create your first watchlist to start organizing and tracking
                    the movies you want to watch.</p>
                <button
                    onclick="document.getElementById('createWatchlistSection').classList.remove('hidden'); document.getElementById('createWatchlistSection').scrollIntoView({ behavior: 'smooth', block: 'nearest' });"
                    class="bg-gradient-to-r from-red-600 to-red-800 text-white px-8 py-3 rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 inline-flex items-center gap-2 font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Your First Watchlist
                </button>
            </div>
        <?php endif; ?>

        <script>
            // Auto-show create section if there's an error (form was submitted)
            <?php if ($error && isset($_POST['submit'])): ?>
                document.addEventListener('DOMContentLoaded', function () {
                    document.getElementById('createWatchlistSection').classList.remove('hidden');
                    document.getElementById('createWatchlistSection').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                });
            <?php endif; ?>
        </script>
    </div>
</body>

</html>