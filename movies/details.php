<?php
/**
 * Movie Details Page
 * Displays comprehensive information about a single movie including cast, crew, reviews, and trailers.
 * Shows user-specific data like favorite status and watchlist membership if logged in.
 */

session_start();
require("../connect.php");
require("../image_handler.php");

// Require movie ID parameter in URL
if (!isset($_GET['id'])) {
    header("Location: browse.php");
    exit();
}

// Cast movie ID to integer for safety
$movie_id = (int)$_GET['id'];

// Retrieve basic movie information from database
$sql = "SELECT * FROM movies WHERE movie_id = $movie_id";
$result = myQuery($sql);

// Redirect if movie doesn't exist
if (mysqli_num_rows($result) == 0) {
    header("Location: browse.php");
    exit();
}
$movie = mysqli_fetch_assoc($result);

// Retrieve all genres associated with this movie
// JOIN with genres table to get genre names
$genres_sql = "SELECT g.genre_id, g.genre_name FROM movie_genres mg 
               JOIN genres g ON mg.genre_id = g.genre_id 
               WHERE mg.movie_id = $movie_id";
$genres_result = myQuery($genres_sql);

// Retrieve cast members for this movie
// Limits to top 10 cast members ordered by cast_order (billing order)
// Includes character name played by each cast member
$cast_sql = "SELECT cm.*, mc.character_name, mc.cast_order 
             FROM movie_cast mc 
             JOIN cast_members cm ON mc.cast_id = cm.cast_id 
             WHERE mc.movie_id = $movie_id 
             ORDER BY mc.cast_order ASC 
             LIMIT 10";
$cast_result = myQuery($cast_sql);

// Retrieve crew members for this movie
// Includes role information (director, producer, etc.)
// Ordered by role for consistent display
$crew_sql = "SELECT crm.*, mc.role 
             FROM movie_crew mc 
             JOIN crew_members crm ON mc.crew_id = crm.crew_id 
             WHERE mc.movie_id = $movie_id 
             ORDER BY mc.role";
$crew_result = myQuery($crew_sql);

// Retrieve movie trailers
// Can include multiple trailers (teaser, official, behind scenes)
$trailer_sql = "SELECT * FROM movie_trailers WHERE movie_id = $movie_id";
$trailer_result = myQuery($trailer_sql);

// Retrieve reviews with pagination
// Only shows non-flagged reviews to users
// Orders by like count (most liked first), then by creation date
$review_page = isset($_GET['review_page']) ? (int)$_GET['review_page'] : 1;
$reviews_per_page = 5;
$review_offset = ($review_page - 1) * $reviews_per_page;

$review_sql = "SELECT r.*, u.username, u.profile_avatar 
               FROM reviews r 
               JOIN users u ON r.user_id = u.user_id 
               WHERE r.movie_id = $movie_id AND r.is_flagged = FALSE 
               ORDER BY r.like_count DESC, r.created_at DESC 
               LIMIT $reviews_per_page OFFSET $review_offset";
$review_result = myQuery($review_sql);

// Calculate total review count for pagination
// Only counts non-flagged reviews
$review_count_sql = "SELECT COUNT(*) as total FROM reviews WHERE movie_id = $movie_id AND is_flagged = FALSE";
$review_count_result = myQuery($review_count_sql);
$total_reviews = mysqli_fetch_assoc($review_count_result)['total'];
$total_review_pages = ceil($total_reviews / $reviews_per_page);

// Check user-specific data if logged in
$user_review = null;
if (isset($_SESSION['user_id'])) {
    // Check if current user has already reviewed this movie
    // Used to show edit review option instead of submit new review
    $user_review_sql = "SELECT * FROM reviews WHERE movie_id = $movie_id AND user_id = " . $_SESSION['user_id'];
    $user_review_result = myQuery($user_review_sql);
    if (mysqli_num_rows($user_review_result) > 0) {
        $user_review = mysqli_fetch_assoc($user_review_result);
    }
    
    // Check which watchlists contain this movie
    // LEFT JOIN shows all user watchlists, with movie_id if movie is in that watchlist
    $watchlist_sql = "SELECT w.watchlist_id, w.watchlist_name, wm.movie_id 
                      FROM watchlists w 
                      LEFT JOIN watchlist_movies wm ON w.watchlist_id = wm.watchlist_id AND wm.movie_id = $movie_id
                      WHERE w.user_id = " . $_SESSION['user_id'];
    $watchlist_result = myQuery($watchlist_sql);
    
    // Check if movie is in user's favorites
    $favorite_sql = "SELECT favorite_id FROM favorites 
                    WHERE user_id = " . $_SESSION['user_id'] . " 
                    AND entity_type = 'movie' 
                    AND entity_id = $movie_id";
    $favorite_result = myQuery($favorite_sql);
    $is_favorited = mysqli_num_rows($favorite_result) > 0;
    
    // Check if movie is marked as watched by user
    $watched_sql = "SELECT * FROM user_watched_movies 
                   WHERE user_id = " . $_SESSION['user_id'] . " 
                   AND movie_id = $movie_id";
    $watched_result = myQuery($watched_sql);
    $is_watched = mysqli_num_rows($watched_result) > 0;
    
    // Get list of review IDs that the current user has liked
    // This is used to show which reviews the user has already liked
    // First, collect all review IDs from the current page
    $review_ids = [];
    mysqli_data_seek($review_result, 0); // Reset pointer to beginning
    while ($rev = mysqli_fetch_assoc($review_result)) {
        $review_ids[] = $rev['review_id'];
    }
    mysqli_data_seek($review_result, 0); // Reset again for display loop
    
    // Query which of these reviews the user has liked
    $liked_reviews = [];
    if (!empty($review_ids)) {
        $review_ids_str = implode(',', array_map('intval', $review_ids));
        $liked_sql = "SELECT review_id FROM review_likes 
                     WHERE user_id = " . $_SESSION['user_id'] . " 
                     AND review_id IN ($review_ids_str)";
        $liked_result = myQuery($liked_sql);
        while ($liked = mysqli_fetch_assoc($liked_result)) {
            $liked_reviews[] = $liked['review_id'];
        }
    }
} else {
    // Set defaults for non-logged-in users
    $watchlist_result = null;
    $is_favorited = false;
    $is_watched = false;
    $liked_reviews = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($movie['title']); ?> - xGrab</title>
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
        <a href="browse.php" class="inline-flex items-center text-red-400 hover:text-red-300 mb-6 font-medium transition-colors duration-300 fade-in">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Browse
        </a>
        
        <div class="bg-gray-800 rounded-xl shadow-lg p-4 md:p-6 mb-6 border border-gray-700 fade-in">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
                <!-- Poster -->
                <div>
                    <?php if ($movie['poster_image']): 
                        $poster_path = getImagePath($movie['poster_image'], 'poster');
                    ?>
                        <img src="<?php echo htmlspecialchars($poster_path); ?>" 
                             alt="<?php echo htmlspecialchars($movie['title']); ?>"
                             class="w-full rounded-lg"
                             onerror="console.error('Failed to load image: <?php echo htmlspecialchars($poster_path); ?>'); this.style.display='none';">
                    <?php else: ?>
                        <div class="w-full aspect-[2/3] bg-gray-200 rounded-lg flex items-center justify-center">
                            <span class="text-gray-400">No Poster</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Movie Info -->
                <div class="md:col-span-2">
                    <h1 class="text-4xl font-bold mb-2 bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                        <?php echo htmlspecialchars($movie['title']); ?>
                    </h1>
                    <p class="text-gray-400 mb-6 text-lg"><?php echo $movie['release_year']; ?></p>
                    
                    <!-- Rating -->
                    <div class="mb-6">
                        <!-- API Rating (TMDB) - Always displayed -->
                        <div class="flex items-center mb-2">
                            <div class="flex items-center bg-yellow-400 px-4 py-2 rounded-lg shadow-md">
                                <span class="text-yellow-900 text-2xl">★</span>
                                <span class="text-2xl font-bold ml-2 text-gray-900"><?php echo number_format($movie['average_rating'], 1); ?></span>
                            </div>
                            <span class="text-gray-400 ml-4">(<?php echo $movie['total_ratings']; ?> ratings)</span>
                        </div>
                        <!-- xGrab Rating (User-generated) - Only shown if there are user ratings -->
                        <?php if (isset($movie['xgrab_total_ratings']) && $movie['xgrab_total_ratings'] > 0): ?>
                            <div class="flex items-center">
                                <div class="flex items-center bg-red-600 px-4 py-2 rounded-lg shadow-md">
                                    <span class="text-white text-2xl">★</span>
                                    <span class="text-2xl font-bold ml-2 text-white"><?php echo number_format($movie['xgrab_average_rating'], 1); ?></span>
                                </div>
                                <span class="text-gray-400 ml-4">xGrab Rating (<?php echo $movie['xgrab_total_ratings']; ?> <?php echo $movie['xgrab_total_ratings'] == 1 ? 'rater' : 'raters'; ?>)</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Genres -->
                    <div class="mb-4">
                        <span class="font-semibold text-gray-300 mb-2 block">Genres: </span>
                        <div class="flex flex-wrap gap-2">
                            <?php 
                            mysqli_data_seek($genres_result, 0);
                            while($genre = mysqli_fetch_assoc($genres_result)): 
                            ?>
                                <a href="browse.php?genres[]=<?php echo $genre['genre_id']; ?>" 
                                   class="px-3 py-1 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-full text-sm font-medium hover:from-red-700 hover:to-red-900 transition-all duration-300 transform hover:scale-105 cursor-pointer">
                                    <?php echo htmlspecialchars($genre['genre_name']); ?>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    
                    <!-- Runtime & Language -->
                    <div class="mb-4 flex flex-wrap gap-4 text-gray-300">
                        <?php if ($movie['runtime']): ?>
                            <div>
                                <span class="font-semibold">Runtime: </span>
                                <?php 
                                $hours = floor($movie['runtime'] / 60);
                                $minutes = $movie['runtime'] % 60;
                                echo $hours . 'h ' . $minutes . 'm';
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($movie['original_language']): ?>
                            <div>
                                <span class="font-semibold">Language: </span>
                                <?php echo strtoupper($movie['original_language']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Budget & Revenue -->
                    <?php if ($movie['budget'] > 0 || $movie['revenue'] > 0): ?>
                        <div class="mb-4">
                            <?php if ($movie['budget'] > 0): ?>
                                <div class="text-gray-300"><span class="font-semibold">Budget: </span>$<?php echo number_format($movie['budget']); ?></div>
                            <?php endif; ?>
                            <?php if ($movie['revenue'] > 0): ?>
                                <div class="text-gray-300"><span class="font-semibold">Revenue: </span>$<?php echo number_format($movie['revenue']); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Overview -->
                    <?php if ($movie['description']): ?>
                        <div class="mb-4">
                            <h2 class="text-xl font-bold mb-2 text-gray-100">Overview</h2>
                            <p class="text-gray-300 leading-relaxed"><?php echo nl2br(htmlspecialchars($movie['description'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Favorite and Watched Actions -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="mb-4 flex flex-wrap gap-3 items-center">
                            <!-- Favorite Button -->
                            <form method="post" action="../favorites/toggle.php" class="inline">
                                <input type="hidden" name="entity_type" value="movie">
                                <input type="hidden" name="entity_id" value="<?php echo $movie_id; ?>">
                                <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $movie_id); ?>">
                                <button type="submit" 
                                        class="relative group/icon px-4 py-2 rounded-lg transition-all duration-300 flex items-center space-x-2 <?php echo $is_favorited ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                                    <?php if ($is_favorited): ?>
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>Remove from Favorites</span>
                                    <?php else: ?>
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                        </svg>
                                        <span>Add to Favorites</span>
                                    <?php endif; ?>
                                </button>
                            </form>
                            
                            <!-- Watched Button -->
                            <form method="post" action="../watched/toggle.php" class="inline">
                                <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">
                                <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $movie_id); ?>">
                                <button type="submit" 
                                        class="relative group/icon px-4 py-2 rounded-lg transition-all duration-300 flex items-center space-x-2 <?php echo $is_watched ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                                    <?php if ($is_watched): ?>
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>Mark as Not Watched</span>
                                    <?php else: ?>
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span>Mark as Watched</span>
                                    <?php endif; ?>
                                </button>
                            </form>
                        </div>
                        
                        <!-- Watchlist Actions -->
                        <div class="mb-4">
                            <?php 
                            // Check how many watchlists exist
                            $watchlist_count = 0;
                            $watchlists_array = [];
                            if ($watchlist_result) {
                                mysqli_data_seek($watchlist_result, 0);
                                while($wl = mysqli_fetch_assoc($watchlist_result)) {
                                    $watchlist_count++;
                                    $watchlists_array[] = $wl;
                                }
                            }
                            ?>
                            
                            <?php if ($watchlist_count > 0): ?>
                                <button type="button" 
                                        onclick="openWatchlistModal()"
                                        class="px-6 py-2 bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-lg hover:from-blue-700 hover:to-blue-900 transition-all duration-300 shadow-lg hover:shadow-xl font-medium flex items-center space-x-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    <span>Add to Watchlist</span>
                                </button>
                            <?php else: ?>
                                <a href="../watchlist/create.php" 
                                   class="px-6 py-2 bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-lg hover:from-blue-700 hover:to-blue-900 transition-all duration-300 shadow-lg hover:shadow-xl font-medium inline-flex items-center space-x-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    <span>Create a Watchlist</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Cast -->
        <?php if (mysqli_num_rows($cast_result) > 0): ?>
            <div class="bg-gray-800 rounded-lg shadow-md p-4 md:p-6 mb-6 border border-gray-700">
                <h2 class="text-2xl font-bold mb-4 text-gray-100">Cast</h2>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <?php 
                    mysqli_data_seek($cast_result, 0);
                    while($cast = mysqli_fetch_assoc($cast_result)): 
                    ?>
                        <a href="../cast/details.php?id=<?php echo $cast['cast_id']; ?>" class="text-center group">
                            <div class="w-24 h-24 mx-auto bg-gray-700 rounded-full mb-2 flex items-center justify-center overflow-hidden border border-gray-600 group-hover:border-red-500 transition-colors duration-300">
                                <?php if ($cast['photo_url']): ?>
                                    <img src="<?php echo htmlspecialchars($cast['photo_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($cast['name']); ?>"
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                <?php else: ?>
                                    <span class="text-gray-400 text-xs">No Photo</span>
                                <?php endif; ?>
                            </div>
                            <p class="font-semibold text-sm text-gray-100 group-hover:text-red-400 transition-colors duration-300"><?php echo htmlspecialchars($cast['name']); ?></p>
                            <?php if ($cast['character_name']): ?>
                                <p class="text-xs text-gray-400"><?php echo htmlspecialchars($cast['character_name']); ?></p>
                            <?php endif; ?>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Crew -->
        <?php if (mysqli_num_rows($crew_result) > 0): ?>
            <div class="bg-gray-800 rounded-lg shadow-md p-4 md:p-6 mb-6 border border-gray-700">
                <h2 class="text-2xl font-bold mb-6 text-gray-100">Crew</h2>
                <?php 
                $crew_by_role = [];
                mysqli_data_seek($crew_result, 0);
                while($crew = mysqli_fetch_assoc($crew_result)) {
                    $role = $crew['role'];
                    if (!isset($crew_by_role[$role])) {
                        $crew_by_role[$role] = [];
                    }
                    $crew_by_role[$role][] = $crew;
                }
                ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach($crew_by_role as $role => $members): ?>
                        <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600">
                            <h3 class="font-bold mb-3 text-red-400 text-lg"><?php echo htmlspecialchars($role); ?></h3>
                            <div class="space-y-3">
                                <?php foreach($members as $member): 
                                    // Generate color for avatar based on name
                                    $crew_avatar_colors = [
                                        'bg-red-500', 'bg-red-600', 'bg-orange-500', 'bg-amber-500', 
                                        'bg-yellow-500', 'bg-lime-500', 'bg-green-500', 'bg-emerald-500',
                                        'bg-teal-500', 'bg-cyan-500', 'bg-blue-500', 'bg-indigo-500',
                                        'bg-purple-500', 'bg-pink-500', 'bg-rose-500', 'bg-violet-500'
                                    ];
                                    $crew_color_index = crc32($member['name']) % count($crew_avatar_colors);
                                    $crew_avatar_color = $crew_avatar_colors[$crew_color_index];
                                    $crew_first_letter = strtoupper(mb_substr(trim($member['name']), 0, 1, 'UTF-8'));
                                    if (empty($crew_first_letter) || strlen($crew_first_letter) > 1) {
                                        $crew_first_letter = strtoupper(substr(trim($member['name']), 0, 1)) ?: '?';
                                    }
                                ?>
                                    <div class="flex items-center space-x-3">
                                        <div class="w-12 h-12 <?php echo $member['photo_url'] ? 'bg-gray-600' : $crew_avatar_color; ?> rounded-full flex items-center justify-center overflow-hidden border border-gray-500 flex-shrink-0">
                                            <?php if ($member['photo_url']): ?>
                                                <img src="<?php echo htmlspecialchars($member['photo_url']); ?>" 
                                                     alt="<?php echo htmlspecialchars($member['name']); ?>"
                                                     class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <span class="text-white font-bold text-lg select-none"><?php echo htmlspecialchars($crew_first_letter); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-gray-100 text-sm truncate"><?php echo htmlspecialchars($member['name']); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Trailer -->
        <?php if (mysqli_num_rows($trailer_result) > 0): ?>
            <div class="bg-gray-800 rounded-lg shadow-md p-6 mb-6 border border-gray-700">
                <h2 class="text-2xl font-bold mb-4 text-gray-100">Trailer</h2>
                <?php 
                mysqli_data_seek($trailer_result, 0);
                $trailer = mysqli_fetch_assoc($trailer_result);
                // Extract YouTube video ID if it's a YouTube URL
                $video_id = '';
                if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $trailer['trailer_url'], $matches)) {
                    $video_id = $matches[1];
                }
                ?>
                <?php if ($video_id): ?>
                    <div class="aspect-video">
                        <iframe class="w-full h-full" 
                                src="https://www.youtube.com/embed/<?php echo $video_id; ?>" 
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen>
                        </iframe>
                    </div>
                <?php else: ?>
                    <a href="<?php echo htmlspecialchars($trailer['trailer_url']); ?>" 
                       target="_blank" 
                       class="text-red-400 hover:text-red-300 hover:underline">
                        Watch Trailer
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Reviews Section -->
        <div class="bg-gray-800 rounded-lg shadow-md p-4 md:p-6 border border-gray-700">
            <h2 class="text-2xl font-bold mb-4 text-gray-100">Reviews</h2>
            
            <!-- Display error/success messages -->
            <?php if (isset($_GET['error'])): ?>
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>
            
            <!-- User Review Section (if logged in) -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($user_review): ?>
                    <!-- Display User's Review with Edit/Delete -->
                    <div class="mb-6 p-4 bg-gray-700 rounded-lg border-2 border-red-500 fade-in">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-bold text-lg text-gray-100">Your Review</h3>
                            <div class="flex space-x-2">
                                <button onclick="document.getElementById('editReviewForm').classList.toggle('hidden')" 
                                        class="px-4 py-2 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl text-sm font-medium">
                                    Edit
                                </button>
                                <a href="../reviews/delete.php?review_id=<?php echo $user_review['review_id']; ?>&movie_id=<?php echo $movie_id; ?>" 
                                   class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all duration-300 shadow-lg hover:shadow-xl text-sm font-medium"
                                   onclick="return confirm('Are you sure you want to delete your review?');">
                                    Delete
                                </a>
                            </div>
                        </div>
                        <div class="flex items-center mb-2">
                            <span class="text-yellow-400 font-bold text-xl mr-2">★</span>
                            <span class="text-xl font-bold text-gray-100"><?php echo number_format($user_review['rating_value'], 1); ?></span>
                        </div>
                        <?php if ($user_review['is_spoiler']): ?>
                            <div class="bg-yellow-900 border border-yellow-600 text-yellow-300 px-3 py-2 rounded mb-2 text-sm">
                                ⚠️ This review contains spoilers
                            </div>
                        <?php endif; ?>
                        <p class="text-gray-300 mb-2"><?php echo nl2br(htmlspecialchars($user_review['review_text'])); ?></p>
                        <p class="text-xs text-gray-400"><?php echo date('M d, Y', strtotime($user_review['created_at'])); ?>
                            <?php if ($user_review['updated_at'] != $user_review['created_at']): ?>
                                <span>(edited)</span>
                            <?php endif; ?>
                        </p>
                        
                        <!-- Edit Form (Hidden by default) -->
                        <div id="editReviewForm" class="hidden mt-4 pt-4 border-t border-gray-600">
                            <h4 class="font-semibold mb-3 text-gray-100">Edit Your Review</h4>
                            <form method="post" action="../reviews/submit.php">
                                <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">
                                <div class="mb-3">
                                    <label class="block text-sm font-medium mb-1 text-gray-300">Rating (1-10):</label>
                                    <input type="number" name="rating_value" min="1" max="10" step="0.1" 
                                           value="<?php echo $user_review['rating_value']; ?>" 
                                           required 
                                           class="w-full px-3 py-2 bg-gray-800 border-2 border-gray-600 rounded-lg text-gray-100 focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300">
                                </div>
                                <div class="mb-3">
                                    <label class="block text-sm font-medium mb-1 text-gray-300">Review (2-1000 characters):</label>
                                    <textarea name="review_text" id="edit_review_text" rows="4" required maxlength="1000"
                                              class="w-full px-3 py-2 bg-gray-800 border-2 border-gray-600 rounded-lg text-gray-100 focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300"><?php echo htmlspecialchars($user_review['review_text']); ?></textarea>
                                    <div class="mt-1 text-xs text-gray-400 flex justify-between">
                                        <span id="edit_review_counter" class="<?php echo strlen($user_review['review_text']) < 2 ? 'text-red-400' : (strlen($user_review['review_text']) > 1000 ? 'text-red-400' : ''); ?>">
                                            <?php echo strlen($user_review['review_text']); ?> / 1000 characters
                                        </span>
                                        <span class="<?php echo strlen($user_review['review_text']) < 2 ? 'text-red-400' : ''; ?>">
                                            Minimum: 2 characters
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="flex items-center text-gray-300">
                                        <input type="checkbox" name="is_spoiler" value="1" 
                                               class="rounded border-gray-600 bg-gray-800 text-red-600 focus:ring-red-500"
                                               <?php echo $user_review['is_spoiler'] ? 'checked' : ''; ?>>
                                        <span class="ml-2">Contains spoilers</span>
                                    </label>
                                </div>
                                <button type="submit" class="px-4 py-2 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl font-medium">
                                    Update Review
                                </button>
                                <button type="button" onclick="document.getElementById('editReviewForm').classList.add('hidden')" 
                                        class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 ml-2 transition-all duration-300 font-medium">
                                    Cancel
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Submit Review Form (if no review yet) -->
                    <div class="mb-6 p-4 bg-gray-700 rounded-lg border border-gray-600 fade-in">
                        <h3 class="font-semibold mb-3 text-gray-100">Write a Review</h3>
                        <form method="post" action="../reviews/submit.php">
                            <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">
                            <div class="mb-3">
                                <label class="block text-sm font-medium mb-1 text-gray-300">Rating (1-10):</label>
                                <input type="number" name="rating_value" min="1" max="10" step="0.1" 
                                       required 
                                       class="w-full px-3 py-2 bg-gray-800 border-2 border-gray-600 rounded-lg text-gray-100 focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300">
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm font-medium mb-1 text-gray-300">Review (2-1000 characters):</label>
                                <textarea name="review_text" id="new_review_text" rows="4" required maxlength="1000"
                                          class="w-full px-3 py-2 bg-gray-800 border-2 border-gray-600 rounded-lg text-gray-100 focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300"></textarea>
                                <div class="mt-1 text-xs text-gray-400 flex justify-between">
                                    <span id="new_review_counter">0 / 1000 characters</span>
                                    <span>Minimum: 2 characters</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="flex items-center text-gray-300">
                                    <input type="checkbox" name="is_spoiler" value="1" 
                                           class="rounded border-gray-600 bg-gray-800 text-red-600 focus:ring-red-500">
                                    <span class="ml-2">Contains spoilers</span>
                                </label>
                            </div>
                            <button type="submit" class="px-4 py-2 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl font-medium">
                                Submit Review
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- Display Reviews -->
            <div class="space-y-4">
                <?php if (mysqli_num_rows($review_result) > 0): ?>
                    <?php 
                    mysqli_data_seek($review_result, 0);
                    while($review = mysqli_fetch_assoc($review_result)): 
                    ?>
                        <div class="border-b border-gray-700 pb-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center">
                                    <?php
                                    // Generate color for avatar based on username (more varied colors)
                                    $review_avatar_colors = [
                                        'bg-red-500', 'bg-red-600', 'bg-orange-500', 'bg-amber-500', 
                                        'bg-yellow-500', 'bg-lime-500', 'bg-green-500', 'bg-emerald-500',
                                        'bg-teal-500', 'bg-cyan-500', 'bg-blue-500', 'bg-indigo-500',
                                        'bg-purple-500', 'bg-pink-500', 'bg-rose-500', 'bg-violet-500'
                                    ];
                                    $review_color_index = crc32($review['username']) % count($review_avatar_colors);
                                    $review_avatar_color = $review_avatar_colors[$review_color_index];
                                    ?>
                                    <div class="w-10 h-10 <?php echo $review['profile_avatar'] ? 'bg-gray-700' : $review_avatar_color; ?> rounded-full mr-3 flex items-center justify-center border border-gray-600">
                                        <?php if ($review['profile_avatar']): ?>
                                            <img src="<?php echo htmlspecialchars(getImagePath($review['profile_avatar'], 'avatar')); ?>" 
                                                 alt="<?php echo htmlspecialchars($review['username']); ?>"
                                                 class="w-full h-full object-cover rounded-full">
                                        <?php else: ?>
                                            <?php 
                                            $review_first_letter = strtoupper(mb_substr(trim($review['username']), 0, 1, 'UTF-8'));
                                            if (empty($review_first_letter) || strlen($review_first_letter) > 1) {
                                                $review_first_letter = strtoupper(substr(trim($review['username']), 0, 1)) ?: '?';
                                            }
                                            ?>
                                            <span class="text-white font-bold select-none whitespace-nowrap" style="display: block; overflow: hidden; text-overflow: clip; max-width: 100%;"><?php echo htmlspecialchars($review_first_letter); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <a href="../profile/view.php?user_id=<?php echo $review['user_id']; ?>" 
                                           class="font-semibold text-gray-100 hover:text-red-400 transition-colors duration-300">
                                            <?php echo htmlspecialchars($review['username']); ?>
                                        </a>
                                        <span class="text-yellow-400 text-sm ml-2">
                                            ★ <?php echo number_format($review['rating_value'], 1); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-sm text-gray-400">
                                    <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                    <?php if ($review['updated_at'] != $review['created_at']): ?>
                                        <span class="text-xs">(edited)</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($review['is_spoiler']): ?>
                                <div class="bg-yellow-900 border border-yellow-600 text-yellow-300 px-3 py-2 rounded mb-2">
                                    ⚠️ This review contains spoilers
                                </div>
                            <?php endif; ?>
                            <p class="text-gray-300 mb-2"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                            <div class="flex items-center space-x-4 text-sm">
                                <?php 
                                // Check if current user has liked this review
                                $is_liked = isset($_SESSION['user_id']) && in_array($review['review_id'], $liked_reviews);
                                // Check if this is the user's own review
                                $is_own_review = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $review['user_id'];
                                ?>
                                <?php if (!$is_own_review): ?>
                                    <form method="post" action="../reviews/like.php" class="inline">
                                        <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                        <button type="submit" class="<?php echo $is_liked ? 'text-green-400 hover:text-green-300' : 'text-red-400 hover:text-red-300'; ?> hover:underline transition-colors duration-300 flex items-center gap-1">
                                            <?php if ($is_liked): ?>
                                                <span>✓</span>
                                            <?php else: ?>
                                                <span>👍</span>
                                            <?php endif; ?>
                                            <span>Helpful (<?php echo $review['like_count']; ?>)</span>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $review['user_id']): ?>
                                    <form method="post" action="../reviews/report.php" class="inline">
                                        <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                        <button type="submit" class="text-red-400 hover:text-red-300 hover:underline transition-colors duration-300">
                                            Report
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    
                    <!-- Review Pagination -->
                    <?php if ($total_review_pages > 1): ?>
                        <div class="flex justify-center space-x-2 mt-4">
                            <?php if ($review_page > 1): ?>
                                <a href="?id=<?php echo $movie_id; ?>&review_page=<?php echo $review_page - 1; ?>" 
                                   class="bg-gradient-to-r from-red-600 to-red-800 text-white px-4 py-2 rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl">
                                    Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $review_page - 2); $i <= min($total_review_pages, $review_page + 2); $i++): ?>
                                <a href="?id=<?php echo $movie_id; ?>&review_page=<?php echo $i; ?>" 
                                   class="px-4 py-2 rounded-lg transition-all duration-300 <?php echo $i == $review_page ? 'bg-gradient-to-r from-red-600 to-red-800 text-white shadow-lg' : 'bg-gray-700 text-gray-300 hover:bg-gray-600 border border-gray-600'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($review_page < $total_review_pages): ?>
                                <a href="?id=<?php echo $movie_id; ?>&review_page=<?php echo $review_page + 1; ?>" 
                                   class="bg-gradient-to-r from-red-600 to-red-800 text-white px-4 py-2 rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-gray-400 text-center py-8">No reviews yet. Be the first to review this movie!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Watchlist Modal -->
    <?php if (isset($_SESSION['user_id']) && !empty($watchlists_array)): ?>
    <div id="watchlistModal" class="fixed inset-0 z-50 hidden">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity duration-300" onclick="closeWatchlistModal()"></div>
        
        <!-- Modal Content -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md transform transition-all duration-300 scale-95 opacity-0" id="watchlistModalContent">
            <div class="bg-gray-800 rounded-xl shadow-2xl border border-gray-700 overflow-hidden mx-4">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            Add to Watchlist
                        </h3>
                        <button type="button" onclick="closeWatchlistModal()" class="text-white/80 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <p class="text-blue-200 text-sm mt-1">Select one or more watchlists</p>
                </div>
                
                <!-- Body -->
                <form method="post" action="../watchlist/add_movie_multiple.php" id="watchlistForm">
                    <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">
                    
                    <div class="px-6 py-4 max-h-[300px] overflow-y-auto custom-scrollbar">
                        <div class="space-y-2">
                            <?php foreach ($watchlists_array as $wl): ?>
                                <label class="flex items-center p-3 rounded-lg cursor-pointer transition-all duration-200 hover:bg-gray-700/50 group <?php echo $wl['movie_id'] ? 'opacity-60' : ''; ?>">
                                    <input type="checkbox" 
                                           name="watchlist_ids[]" 
                                           value="<?php echo $wl['watchlist_id']; ?>"
                                           <?php echo $wl['movie_id'] ? 'disabled checked' : ''; ?>
                                           class="w-5 h-5 rounded border-2 border-gray-500 bg-gray-700 text-blue-600 focus:ring-blue-500 focus:ring-offset-0 focus:ring-offset-gray-800 transition-all duration-200">
                                    <div class="ml-3 flex-1">
                                        <span class="text-gray-100 font-medium group-hover:text-white transition-colors">
                                            <?php echo htmlspecialchars($wl['watchlist_name']); ?>
                                        </span>
                                        <?php if ($wl['movie_id']): ?>
                                            <span class="ml-2 text-xs text-green-400 font-medium">✓ Already added</span>
                                        <?php endif; ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="px-6 py-4 bg-gray-900/50 border-t border-gray-700 flex items-center justify-between gap-3">
                        <a href="../watchlist/create.php" class="text-blue-400 hover:text-blue-300 text-sm font-medium transition-colors flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Create New
                        </a>
                        <div class="flex gap-2">
                            <button type="button" onclick="closeWatchlistModal()" 
                                    class="px-4 py-2 bg-gray-700 text-gray-300 rounded-lg hover:bg-gray-600 transition-all duration-300 font-medium">
                                Cancel
                            </button>
                            <button type="submit" id="addToWatchlistBtn"
                                    class="px-5 py-2 bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-lg hover:from-blue-700 hover:to-blue-900 transition-all duration-300 shadow-lg hover:shadow-xl font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                                Add Selected
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #374151;
            border-radius: 3px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #6B7280;
            border-radius: 3px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #9CA3AF;
        }
        
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
        }
        
        @keyframes modalFadeOut {
            from {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
            to {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.9);
            }
        }
        
        #watchlistModalContent.show {
            animation: modalFadeIn 0.3s ease-out forwards;
        }
        
        #watchlistModalContent.hide {
            animation: modalFadeOut 0.2s ease-in forwards;
        }
    </style>
    
    <script>
        function openWatchlistModal() {
            const modal = document.getElementById('watchlistModal');
            const content = document.getElementById('watchlistModalContent');
            
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            // Trigger animation
            setTimeout(() => {
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('show');
            }, 10);
            
            updateAddButton();
        }
        
        function closeWatchlistModal() {
            const modal = document.getElementById('watchlistModal');
            const content = document.getElementById('watchlistModalContent');
            
            content.classList.remove('show');
            content.classList.add('hide');
            
            setTimeout(() => {
                modal.classList.add('hidden');
                content.classList.remove('hide');
                content.classList.add('scale-95', 'opacity-0');
                document.body.style.overflow = '';
            }, 200);
        }
        
        function updateAddButton() {
            const checkboxes = document.querySelectorAll('#watchlistForm input[type="checkbox"]:not(:disabled):checked');
            const btn = document.getElementById('addToWatchlistBtn');
            btn.disabled = checkboxes.length === 0;
        }
        
        // Listen for checkbox changes
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('watchlistForm');
            if (form) {
                form.addEventListener('change', updateAddButton);
            }
        });
        
        // Close on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('watchlistModal');
                if (modal && !modal.classList.contains('hidden')) {
                    closeWatchlistModal();
                }
            }
        });
        
        // Character counter for review textareas
        function setupReviewCounter(textareaId, counterId) {
            const textarea = document.getElementById(textareaId);
            const counter = document.getElementById(counterId);
            
            if (!textarea || !counter) return;
            
            function updateCounter() {
                const length = textarea.value.length;
                const maxLength = 1000;
                const minLength = 2;
                
                // Update counter text
                counter.textContent = length + ' / ' + maxLength + ' characters';
                
                // Update styling based on length
                if (length < minLength) {
                    counter.classList.add('text-red-400');
                    counter.classList.remove('text-gray-400', 'text-yellow-400');
                } else if (length > maxLength * 0.9) {
                    counter.classList.add('text-yellow-400');
                    counter.classList.remove('text-gray-400', 'text-red-400');
                } else {
                    counter.classList.add('text-gray-400');
                    counter.classList.remove('text-red-400', 'text-yellow-400');
                }
            }
            
            // Update on input
            textarea.addEventListener('input', updateCounter);
            // Initial update
            updateCounter();
        }
        
        // Setup counters when page loads
        document.addEventListener('DOMContentLoaded', function() {
            setupReviewCounter('new_review_text', 'new_review_counter');
            setupReviewCounter('edit_review_text', 'edit_review_counter');
        });
    </script>
    <?php endif; ?>
    <?php require("../includes/footer.php"); ?>
    <?php require_once 'details_scripts.php'; ?>
</body>
</html>
