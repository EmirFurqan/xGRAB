<?php
session_start();
require("../connect.php");
require("../image_handler.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$watchlist_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Get watchlist info
$watchlist_sql = "SELECT * FROM watchlists WHERE watchlist_id = $watchlist_id AND user_id = $user_id";
$watchlist_result = myQuery($watchlist_sql);

if (mysqli_num_rows($watchlist_result) == 0) {
    header("Location: index.php");
    exit();
}
$watchlist = mysqli_fetch_assoc($watchlist_result);

// Get sort parameter
$sort_by = isset($_GET['sort']) ? escapeString($_GET['sort']) : 'date_added';
$sort_order = isset($_GET['order']) ? escapeString($_GET['order']) : 'DESC';

// Build ORDER BY
$order_by = "wm.date_added";
if ($sort_by == 'title') {
    $order_by = "m.title";
} elseif ($sort_by == 'year') {
    $order_by = "m.release_year";
} elseif ($sort_by == 'date_added') {
    $order_by = "wm.date_added";
}

// Get movies in watchlist
$sql = "SELECT m.*, wm.personal_notes, wm.date_added 
        FROM watchlist_movies wm 
        JOIN movies m ON wm.movie_id = m.movie_id 
        WHERE wm.watchlist_id = $watchlist_id 
        ORDER BY $order_by $sort_order";
$result = myQuery($sql);

// Get favorite status and watched status for all movies in watchlist (if user is logged in)
$favorite_movies = [];
$watched_movies = [];
if (isset($_SESSION['user_id'])) {
    mysqli_data_seek($result, 0);
    $movie_ids = [];
    while($row = mysqli_fetch_assoc($result)) {
        $movie_ids[] = $row['movie_id'];
    }
    mysqli_data_seek($result, 0);
    
    if (!empty($movie_ids)) {
        $movie_ids_str = implode(',', array_map('intval', $movie_ids));
        
        // Get favorite status
        $favorites_sql = "SELECT entity_id FROM favorites 
                         WHERE user_id = $user_id 
                         AND entity_type = 'movie' 
                         AND entity_id IN ($movie_ids_str)";
        $favorites_result = myQuery($favorites_sql);
        while($fav = mysqli_fetch_assoc($favorites_result)) {
            $favorite_movies[] = $fav['entity_id'];
        }
        
        // Get watched status from user_watched_movies table
        $watched_sql = "SELECT movie_id FROM user_watched_movies 
                       WHERE user_id = $user_id 
                       AND movie_id IN ($movie_ids_str)";
        $watched_result = myQuery($watched_sql);
        while($watched = mysqli_fetch_assoc($watched_result)) {
            $watched_movies[] = $watched['movie_id'];
        }
    }
}

// Get counts
$total_movies = mysqli_num_rows($result);
$watched_count = count($watched_movies);
mysqli_data_seek($result, 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($watchlist['watchlist_name']); ?> - Watchlist</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        .checkbox-wrapper input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-gray-900 min-h-screen text-gray-100">
    <?php require("../includes/nav.php"); ?>
    
    <div class="container mx-auto px-4 py-8">
        <a href="index.php" class="inline-flex items-center text-red-400 hover:text-red-300 mb-6 font-medium transition-colors duration-300 fade-in">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Watchlists
        </a>
        
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
        
        <div class="bg-gray-800 rounded-xl shadow-lg p-6 mb-6 border border-gray-700 fade-in">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h1 class="text-4xl font-bold mb-2 bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                        <?php echo htmlspecialchars($watchlist['watchlist_name']); ?>
                    </h1>
                    <p class="text-gray-400">Created: <?php echo date('M d, Y', strtotime($watchlist['date_created'])); ?></p>
                </div>
                <a href="edit.php?id=<?php echo $watchlist_id; ?>" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-all duration-300 shadow-md hover:shadow-lg">
                    Rename
                </a>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <div class="bg-gray-700/50 rounded-lg p-3 border border-gray-600">
                    <p class="text-gray-400 text-sm">Total Movies</p>
                    <p class="text-2xl font-bold text-gray-100"><?php echo $total_movies; ?></p>
                </div>
                <div class="bg-gray-700/50 rounded-lg p-3 border border-gray-600">
                    <p class="text-gray-400 text-sm">Watched</p>
                    <p class="text-2xl font-bold text-green-400"><?php echo $watched_count; ?></p>
                </div>
                <div class="bg-gray-700/50 rounded-lg p-3 border border-gray-600">
                    <p class="text-gray-400 text-sm">Not Watched</p>
                    <p class="text-2xl font-bold text-red-400"><?php echo $total_movies - $watched_count; ?></p>
                </div>
            </div>
            
            <!-- Sort Options -->
            <div class="mb-4">
                <form method="get" class="inline-flex flex-wrap gap-2">
                    <input type="hidden" name="id" value="<?php echo $watchlist_id; ?>">
                    <select name="sort" class="px-3 py-2 bg-gray-700 border-2 border-gray-600 rounded-lg text-gray-100 focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300">
                        <option value="date_added" <?php echo $sort_by == 'date_added' ? 'selected' : ''; ?>>Date Added</option>
                        <option value="title" <?php echo $sort_by == 'title' ? 'selected' : ''; ?>>Title</option>
                        <option value="year" <?php echo $sort_by == 'year' ? 'selected' : ''; ?>>Release Year</option>
                    </select>
                    <select name="order" class="px-3 py-2 bg-gray-700 border-2 border-gray-600 rounded-lg text-gray-100 focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300">
                        <option value="DESC" <?php echo $sort_order == 'DESC' ? 'selected' : ''; ?>>Descending</option>
                        <option value="ASC" <?php echo $sort_order == 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl font-medium">
                        Sort
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Movies Grid -->
        <?php if ($total_movies > 0): ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                <?php 
                $delay = 0;
                mysqli_data_seek($result, 0);
                while($movie = mysqli_fetch_assoc($result)): 
                    $delay += 50;
                ?>
                    <div class="group bg-gray-800 rounded-xl shadow-md overflow-hidden hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-700 fade-in" style="animation-delay: <?php echo $delay; ?>ms">
                        <div class="relative">
                            <a href="../movies/details.php?id=<?php echo $movie['movie_id']; ?>">
                                <div class="aspect-[2/3] bg-gray-200 flex items-center justify-center relative overflow-hidden">
                                    <?php if ($movie['poster_image']): ?>
                                        <img src="<?php echo htmlspecialchars(getImagePath($movie['poster_image'], 'poster')); ?>" 
                                             alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                    <?php else: ?>
                                        <div class="w-full h-full bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center">
                                            <span class="text-white text-sm font-medium">No Poster</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (isset($_SESSION['user_id']) && in_array($movie['movie_id'], $watched_movies)): ?>
                                        <div class="absolute top-2 right-2 bg-green-500 text-white px-2 py-1 rounded-md text-xs font-bold shadow-lg flex items-center space-x-1">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            <span>Watched</span>
                                        </div>
                                    <?php endif; ?>
                                    <!-- Rating Badge -->
                                    <div class="absolute top-2 left-2 bg-yellow-400 text-gray-900 px-2 py-1 rounded-md text-xs font-bold flex items-center space-x-1 shadow-lg">
                                        <span>★</span>
                                        <span><?php echo number_format($movie['average_rating'], 1); ?></span>
                                    </div>
                                </div>
                            </a>
                            <!-- Favorite Button -->
                            <?php if (isset($_SESSION['user_id'])): 
                                $is_favorited = in_array($movie['movie_id'], $favorite_movies);
                            ?>
                                <form method="post" action="../favorites/toggle.php" class="absolute top-2 right-2 z-10">
                                    <input type="hidden" name="entity_type" value="movie">
                                    <input type="hidden" name="entity_id" value="<?php echo $movie['movie_id']; ?>">
                                    <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $watchlist_id); ?>">
                                    <button type="submit" 
                                            class="relative group/icon p-2 rounded-full transition-all duration-300 <?php echo $is_favorited ? 'bg-red-600/90 text-white hover:bg-red-600' : 'bg-gray-800/80 text-gray-400 hover:bg-red-600/80 hover:text-white'; ?>" 
                                            title="<?php echo $is_favorited ? 'Remove from Favorites' : 'Add to Favorites'; ?>">
                                        <?php if ($is_favorited): ?>
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                                            </svg>
                                        <?php else: ?>
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                            </svg>
                                        <?php endif; ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold text-sm text-gray-100 truncate group-hover:text-red-400 transition-colors duration-300 mb-1">
                                <?php echo htmlspecialchars($movie['title']); ?>
                            </h3>
                            <p class="text-xs text-gray-400 mb-3"><?php echo $movie['release_year']; ?></p>
                            
                            <!-- Action Icons -->
                            <div class="flex items-center justify-center space-x-3 mt-4">
                                <!-- Watched Icon Button -->
                                <?php 
                                $is_watched = in_array($movie['movie_id'], $watched_movies);
                                ?>
                                <form method="post" action="../watched/toggle.php" class="inline">
                                    <input type="hidden" name="movie_id" value="<?php echo $movie['movie_id']; ?>">
                                    <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $watchlist_id); ?>">
                                    <button type="submit" 
                                            class="relative group/icon p-2 rounded-lg transition-all duration-300 <?php echo $is_watched ? 'bg-green-600/20 text-green-400 hover:bg-green-600/30' : 'bg-gray-700/50 text-gray-400 hover:bg-gray-700 hover:text-green-400'; ?>"
                                            title="<?php echo $is_watched ? 'Mark as Not Watched' : 'Mark as Watched'; ?>">
                                        <?php if ($is_watched): ?>
                                            <!-- Filled checkmark icon for watched -->
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        <?php else: ?>
                                            <!-- Outline circle icon for not watched -->
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        <?php endif; ?>
                                        <!-- Tooltip -->
                                        <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 text-xs text-white bg-gray-800 rounded-lg opacity-0 group-hover/icon:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none z-10 shadow-lg border border-gray-700">
                                            <?php echo $is_watched ? 'Mark as Not Watched' : 'Mark as Watched'; ?>
                                        </span>
                                    </button>
                                </form>
                                
                                <!-- Remove Icon Button -->
                                <form method="post" action="remove_movie.php" class="inline">
                                    <input type="hidden" name="watchlist_id" value="<?php echo $watchlist_id; ?>">
                                    <input type="hidden" name="movie_id" value="<?php echo $movie['movie_id']; ?>">
                                    <button type="submit" 
                                            class="relative group/icon p-2 rounded-lg bg-gray-700/50 text-gray-400 hover:bg-red-600/20 hover:text-red-400 transition-all duration-300"
                                            title="Remove from Watchlist"
                                            onclick="return confirm('Remove this movie from watchlist?');">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        <!-- Tooltip -->
                                        <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 text-xs text-white bg-gray-800 rounded-lg opacity-0 group-hover/icon:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none z-10 shadow-lg">
                                            Remove from Watchlist
                                        </span>
                                    </button>
                                </form>
                            </div>
                            
                            <?php if ($movie['personal_notes']): ?>
                                <div class="mt-3 p-2 bg-gray-700/50 rounded-lg border border-gray-600">
                                    <p class="text-xs text-gray-300"><?php echo htmlspecialchars($movie['personal_notes']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="bg-gray-800 rounded-lg shadow-md p-8 text-center border border-gray-700 fade-in">
                <p class="text-gray-400 mb-4">This watchlist is empty.</p>
                <a href="../movies/browse.php" class="px-6 py-2 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl inline-block font-medium">
                    Browse Movies to Add
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

