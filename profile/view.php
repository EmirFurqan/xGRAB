<?php
session_start();
require("../connect.php");
require("../image_handler.php");

// Get user_id from query or session
$view_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0);

if ($view_user_id == 0) {
    header("Location: ../login.php");
    exit();
}

// Get user info
$sql = "SELECT * FROM users WHERE user_id = $view_user_id";
$result = myQuery($sql);
if (mysqli_num_rows($result) == 0) {
    header("Location: ../movies/browse.php");
    exit();
}
$user = mysqli_fetch_assoc($result);

// Get user statistics
$review_count_sql = "SELECT COUNT(*) as total FROM reviews WHERE user_id = $view_user_id";
$review_count_result = myQuery($review_count_sql);
$review_count = mysqli_fetch_assoc($review_count_result)['total'];

$watchlist_count_sql = "SELECT COUNT(*) as total FROM watchlists WHERE user_id = $view_user_id";
$watchlist_count_result = myQuery($watchlist_count_sql);
$watchlist_count = mysqli_fetch_assoc($watchlist_count_result)['total'];

// Get favorite counts
$favorite_movies_count_sql = "SELECT COUNT(*) as total FROM favorites WHERE user_id = $view_user_id AND entity_type = 'movie'";
$favorite_movies_count_result = myQuery($favorite_movies_count_sql);
$favorite_movies_count = mysqli_fetch_assoc($favorite_movies_count_result)['total'];

$favorite_cast_count_sql = "SELECT COUNT(*) as total FROM favorites WHERE user_id = $view_user_id AND entity_type = 'cast'";
$favorite_cast_count_result = myQuery($favorite_cast_count_sql);
$favorite_cast_count = mysqli_fetch_assoc($favorite_cast_count_result)['total'];

$favorite_users_count_sql = "SELECT COUNT(*) as total FROM favorites WHERE user_id = $view_user_id AND entity_type = 'user'";
$favorite_users_count_result = myQuery($favorite_users_count_sql);
$favorite_users_count = mysqli_fetch_assoc($favorite_users_count_result)['total'];

// Get watched count
$watched_count_sql = "SELECT COUNT(*) as total FROM user_watched_movies WHERE user_id = $view_user_id";
$watched_count_result = myQuery($watched_count_sql);
$watched_count = mysqli_fetch_assoc($watched_count_result)['total'];

// Get last favorite movies (no limit - show as many as fit)
$last_fav_movies_sql = "SELECT f.*, m.title, m.poster_image, m.release_year, m.average_rating 
                       FROM favorites f 
                       JOIN movies m ON f.entity_id = m.movie_id 
                       WHERE f.user_id = $view_user_id AND f.entity_type = 'movie' 
                       ORDER BY f.date_added DESC";
$last_fav_movies_result = myQuery($last_fav_movies_sql);

// Get last favorite cast (no limit - show as many as fit)
$last_fav_cast_sql = "SELECT f.*, cm.name, cm.photo_url 
                     FROM favorites f 
                     JOIN cast_members cm ON f.entity_id = cm.cast_id 
                     WHERE f.user_id = $view_user_id AND f.entity_type = 'cast' 
                     ORDER BY f.date_added DESC";
$last_fav_cast_result = myQuery($last_fav_cast_sql);

// Get last favorite users (no limit - show as many as fit)
$last_fav_users_sql = "SELECT f.*, u.username, u.profile_avatar 
                      FROM favorites f 
                      JOIN users u ON f.entity_id = u.user_id 
                      WHERE f.user_id = $view_user_id AND f.entity_type = 'user' 
                      ORDER BY f.date_added DESC";
$last_fav_users_result = myQuery($last_fav_users_sql);

// Get last watched movies (no limit - show as many as fit)
$last_watched_sql = "SELECT uwm.*, m.title, m.poster_image, m.release_year, m.average_rating 
                    FROM user_watched_movies uwm 
                    JOIN movies m ON uwm.movie_id = m.movie_id 
                    WHERE uwm.user_id = $view_user_id 
                    ORDER BY uwm.watched_date DESC";
$last_watched_result = myQuery($last_watched_sql);

$is_own_profile = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $view_user_id;

// Check if this user is favorited by current user (if logged in and not own profile)
$is_user_favorited = false;
if (isset($_SESSION['user_id']) && !$is_own_profile) {
    $favorite_user_sql = "SELECT favorite_id FROM favorites 
                         WHERE user_id = " . $_SESSION['user_id'] . " 
                         AND entity_type = 'user' 
                         AND entity_id = $view_user_id";
    $favorite_user_result = myQuery($favorite_user_sql);
    $is_user_favorited = mysqli_num_rows($favorite_user_result) > 0;
}

// Generate color for avatar based on username (more varied colors)
$avatar_colors = [
    'bg-red-500', 'bg-red-600', 'bg-orange-500', 'bg-amber-500', 
    'bg-yellow-500', 'bg-lime-500', 'bg-green-500', 'bg-emerald-500',
    'bg-teal-500', 'bg-cyan-500', 'bg-blue-500', 'bg-indigo-500',
    'bg-purple-500', 'bg-pink-500', 'bg-rose-500', 'bg-violet-500'
];
$color_index = crc32($user['username']) % count($avatar_colors);
$avatar_color = $avatar_colors[$color_index];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['username']); ?> - Profile</title>
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
        <div class="bg-gray-800 rounded-xl shadow-lg p-8 mb-6 border border-gray-700 fade-in">
            <div class="flex items-center space-x-6">
                <div class="relative">
                    <div class="w-32 h-32 <?php echo $user['profile_avatar'] ? '' : $avatar_color; ?> rounded-full flex items-center justify-center overflow-hidden shadow-lg border-2 <?php echo $user['profile_avatar'] ? 'border-gray-600' : 'border-transparent'; ?>" style="text-align: center; line-height: 1;">
                        <?php if ($user['profile_avatar']): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_avatar']); ?>" 
                                 alt="<?php echo htmlspecialchars($user['username']); ?>"
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <?php 
                            $first_letter = strtoupper(mb_substr(trim($user['username']), 0, 1, 'UTF-8'));
                            if (empty($first_letter) || strlen($first_letter) > 1) {
                                $first_letter = strtoupper(substr(trim($user['username']), 0, 1)) ?: '?';
                            }
                            ?>
                            <span class="text-5xl text-white font-bold select-none whitespace-nowrap" style="display: block; overflow: hidden; text-overflow: clip; max-width: 100%;"><?php echo htmlspecialchars($first_letter); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($user['is_admin']): ?>
                        <div class="absolute -bottom-2 -right-2 bg-yellow-400 text-gray-900 px-3 py-1 rounded-full text-xs font-bold shadow-lg">
                            Admin
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <h1 class="text-4xl font-bold mb-2 bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                        <?php echo htmlspecialchars($user['username']); ?>
                    </h1>
                    <p class="text-gray-400 text-lg">Member since <?php echo date('F Y', strtotime($user['join_date'])); ?></p>
                </div>
            </div>
            
            <!-- Favorite User Button (only if not own profile) -->
            <?php if (isset($_SESSION['user_id']) && !$is_own_profile): ?>
                <div class="mt-4">
                    <form method="post" action="../favorites/toggle.php" class="inline">
                        <input type="hidden" name="entity_type" value="user">
                        <input type="hidden" name="entity_id" value="<?php echo $view_user_id; ?>">
                        <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?user_id=' . $view_user_id); ?>">
                        <button type="submit" 
                                class="relative group/icon px-4 py-2 rounded-lg transition-all duration-300 flex items-center space-x-2 <?php echo $is_user_favorited ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                            <?php if ($is_user_favorited): ?>
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
                </div>
            <?php endif; ?>
            
            <?php if ($is_own_profile): ?>
                <div class="mt-6">
                    <a href="edit.php" class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 font-medium inline-block">
                        Edit Profile
                    </a>
                    <p class="text-sm text-gray-400 mt-3">Password change is available in the Edit Profile section</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700 fade-in">
                <h2 class="text-2xl font-bold mb-4 bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">Statistics</h2>
                <div class="space-y-3">
                    <div class="flex justify-between items-center p-3 bg-gray-700 rounded-lg">
                        <span class="text-gray-300">Reviews Written:</span>
                        <span class="font-bold text-red-400 text-lg"><?php echo $review_count; ?></span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-gray-700 rounded-lg">
                        <span class="text-gray-300">Watchlists:</span>
                        <span class="font-bold text-red-400 text-lg"><?php echo $watchlist_count; ?></span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-gray-700 rounded-lg">
                        <span class="text-gray-300">Favorite Movies:</span>
                        <span class="font-bold text-red-400 text-lg"><?php echo $favorite_movies_count; ?></span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-gray-700 rounded-lg">
                        <span class="text-gray-300">Favorite Cast:</span>
                        <span class="font-bold text-red-400 text-lg"><?php echo $favorite_cast_count; ?></span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-gray-700 rounded-lg">
                        <span class="text-gray-300">Favorite Users:</span>
                        <span class="font-bold text-red-400 text-lg"><?php echo $favorite_users_count; ?></span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-gray-700 rounded-lg">
                        <span class="text-gray-300">Watched Movies:</span>
                        <span class="font-bold text-green-400 text-lg"><?php echo $watched_count; ?></span>
                    </div>
                </div>
            </div>
            
            <?php if ($is_own_profile): ?>
                <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700 fade-in">
                    <h2 class="text-2xl font-bold mb-4 bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">Account Actions</h2>
                    <a href="delete_account.php" 
                       class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 inline-block font-medium"
                       onclick="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
                        Delete Account
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Navigation Links -->
        <?php if ($is_own_profile): ?>
            <div class="mb-6 flex flex-wrap gap-3 fade-in">
                <a href="../favorites/index.php" class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl font-medium">
                    View All Favorites
                </a>
                <a href="../watched/index.php" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-800 text-white rounded-lg hover:from-green-700 hover:to-green-900 transition-all duration-300 shadow-lg hover:shadow-xl font-medium">
                    View All Watched
                </a>
            </div>
        <?php endif; ?>
        
        <!-- Last Favorite Movies -->
        <?php if (mysqli_num_rows($last_fav_movies_result) > 0): ?>
            <div class="bg-gray-800 rounded-xl shadow-lg py-1.5 px-0.5 border border-gray-700 mb-2 fade-in">
                <div class="flex justify-between items-center mb-1 px-1">
                    <h2 class="text-xl font-bold bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                        Last Favorite Movies
                    </h2>
                    <?php if ($is_own_profile): ?>
                        <a href="../favorites/index.php" class="text-red-400 hover:text-red-300 text-xs font-medium transition-colors duration-300">
                            View All →
                        </a>
                    <?php endif; ?>
                </div>
                <div class="flex flex-wrap justify-start gap-3 px-1">
                    <?php 
                    mysqli_data_seek($last_fav_movies_result, 0);
                    while($fav = mysqli_fetch_assoc($last_fav_movies_result)): 
                    ?>
                        <a href="../movies/details.php?id=<?php echo $fav['entity_id']; ?>" class="inline-block">
                            <div class="aspect-[2/3] bg-gray-200 rounded overflow-hidden relative w-[120px]">
                                <?php if ($fav['poster_image']): ?>
                                    <img src="<?php echo htmlspecialchars(getImagePath($fav['poster_image'], 'poster')); ?>" 
                                         alt="<?php echo htmlspecialchars($fav['title']); ?>"
                                         class="w-full h-full object-cover hover:scale-110 transition-transform duration-200 ease-out">
                                <?php else: ?>
                                    <div class="w-full h-full bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center">
                                        <span class="text-white text-xs">No Poster</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Last Favorite Cast -->
        <?php if (mysqli_num_rows($last_fav_cast_result) > 0): ?>
            <div class="bg-gray-800 rounded-xl shadow-lg py-1.5 px-0.5 border border-gray-700 mb-2 fade-in">
                <div class="flex justify-between items-center mb-1 px-1">
                    <h2 class="text-xl font-bold bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                        Last Favorite Cast
                    </h2>
                    <?php if ($is_own_profile): ?>
                        <a href="../favorites/index.php" class="text-red-400 hover:text-red-300 text-xs font-medium transition-colors duration-300">
                            View All →
                        </a>
                    <?php endif; ?>
                </div>
                <div class="flex flex-wrap justify-start gap-3 px-1">
                    <?php 
                    mysqli_data_seek($last_fav_cast_result, 0);
                    while($fav = mysqli_fetch_assoc($last_fav_cast_result)): 
                        $cast_color_index = crc32($fav['name']) % count($avatar_colors);
                        $cast_color = $avatar_colors[$cast_color_index];
                        $cast_initial = mb_strtoupper(mb_substr($fav['name'], 0, 1, 'UTF-8'));
                    ?>
                        <a href="../cast/details.php?id=<?php echo $fav['entity_id']; ?>" class="inline-block">
                            <div class="aspect-[2/3] bg-gray-200 rounded overflow-hidden relative w-[120px]">
                                <?php if ($fav['photo_url']): ?>
                                    <img src="<?php echo htmlspecialchars($fav['photo_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($fav['name']); ?>"
                                         class="w-full h-full object-cover hover:scale-110 transition-transform duration-200 ease-out">
                                <?php else: ?>
                                    <div class="w-full h-full <?php echo $cast_color; ?> flex items-center justify-center">
                                        <span class="text-white text-xl font-bold"><?php echo htmlspecialchars($cast_initial); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Last Favorite Users -->
        <?php if (mysqli_num_rows($last_fav_users_result) > 0): ?>
            <div class="bg-gray-800 rounded-xl shadow-lg py-1.5 px-0.5 border border-gray-700 mb-2 fade-in">
                <div class="flex justify-between items-center mb-1 px-1">
                    <h2 class="text-xl font-bold bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                        Last Favorite Users
                    </h2>
                    <?php if ($is_own_profile): ?>
                        <a href="../favorites/index.php" class="text-red-400 hover:text-red-300 text-xs font-medium transition-colors duration-300">
                            View All →
                        </a>
                    <?php endif; ?>
                </div>
                <div class="flex flex-wrap justify-start gap-3 px-1">
                    <?php 
                    mysqli_data_seek($last_fav_users_result, 0);
                    while($fav = mysqli_fetch_assoc($last_fav_users_result)): 
                        $user_color_index = crc32($fav['username']) % count($avatar_colors);
                        $user_color = $avatar_colors[$user_color_index];
                        $user_initial = mb_strtoupper(mb_substr($fav['username'], 0, 1, 'UTF-8'));
                    ?>
                        <a href="view.php?user_id=<?php echo $fav['entity_id']; ?>" class="inline-block">
                            <div class="aspect-[2/3] bg-gray-200 rounded overflow-hidden relative w-[120px]">
                                <?php if ($fav['profile_avatar']): ?>
                                    <img src="<?php echo htmlspecialchars($fav['profile_avatar']); ?>" 
                                         alt="<?php echo htmlspecialchars($fav['username']); ?>"
                                         class="w-full h-full object-cover hover:scale-110 transition-transform duration-200 ease-out">
                                <?php else: ?>
                                    <div class="w-full h-full <?php echo $user_color; ?> flex items-center justify-center">
                                        <span class="text-white text-xl font-bold"><?php echo htmlspecialchars($user_initial); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Last Watched Movies -->
        <?php if (mysqli_num_rows($last_watched_result) > 0): ?>
            <div class="bg-gray-800 rounded-xl shadow-lg py-1.5 px-0.5 border border-gray-700 mb-2 fade-in">
                <div class="flex justify-between items-center mb-1 px-1">
                    <h2 class="text-xl font-bold bg-gradient-to-r from-green-400 to-green-600 bg-clip-text text-transparent">
                        Last Watched Movies
                    </h2>
                    <?php if ($is_own_profile): ?>
                        <a href="../watched/index.php" class="text-green-400 hover:text-green-300 text-xs font-medium transition-colors duration-300">
                            View All →
                        </a>
                    <?php endif; ?>
                </div>
                <div class="flex flex-wrap justify-start gap-3 px-1">
                    <?php 
                    mysqli_data_seek($last_watched_result, 0);
                    while($watched = mysqli_fetch_assoc($last_watched_result)): 
                    ?>
                        <a href="../movies/details.php?id=<?php echo $watched['movie_id']; ?>" class="inline-block">
                            <div class="aspect-[2/3] bg-gray-200 rounded overflow-hidden relative w-[120px]">
                                <?php if ($watched['poster_image']): ?>
                                    <img src="<?php echo htmlspecialchars(getImagePath($watched['poster_image'], 'poster')); ?>" 
                                         alt="<?php echo htmlspecialchars($watched['title']); ?>"
                                         class="w-full h-full object-cover hover:scale-110 transition-transform duration-200 ease-out">
                                    <div class="absolute top-1 right-1 bg-green-500 text-white px-1 py-0.5 rounded text-[9px] font-bold shadow-lg">
                                        ✓
                                    </div>
                                <?php else: ?>
                                    <div class="w-full h-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center">
                                        <span class="text-white text-xs">No Poster</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Recent Reviews -->
        <?php if ($review_count > 0): ?>
            <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700 fade-in">
                <h2 class="text-2xl font-bold mb-6 bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">Recent Reviews</h2>
                <?php
                $reviews_sql = "SELECT r.*, m.title, m.movie_id 
                               FROM reviews r 
                               JOIN movies m ON r.movie_id = m.movie_id 
                               WHERE r.user_id = $view_user_id 
                               ORDER BY r.created_at DESC 
                               LIMIT 5";
                $reviews_result = myQuery($reviews_sql);
                ?>
                <div class="space-y-4">
                    <?php while($review = mysqli_fetch_assoc($reviews_result)): ?>
                        <div class="bg-gray-700 rounded-lg p-4 border border-gray-600 hover:border-red-500 transition-colors duration-300">
                            <div class="flex items-center justify-between mb-2">
                                <a href="../movies/details.php?id=<?php echo $review['movie_id']; ?>" 
                                   class="font-semibold text-red-400 hover:text-red-300 transition-colors duration-300">
                                    <?php echo htmlspecialchars($review['title']); ?>
                                </a>
                                <span class="text-yellow-400 font-bold">★ <?php echo number_format($review['rating_value'], 1); ?></span>
                            </div>
                            <p class="text-gray-300 text-sm mb-2"><?php echo nl2br(htmlspecialchars(substr($review['review_text'], 0, 200))); ?><?php echo strlen($review['review_text']) > 200 ? '...' : ''; ?></p>
                            <p class="text-xs text-gray-400"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></p>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

