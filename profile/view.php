<?php
session_start();
require("../connect.php");
require("../image_handler.php");

// Get user_id from query or session
$view_user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0);

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
    'bg-red-500',
    'bg-red-600',
    'bg-orange-500',
    'bg-amber-500',
    'bg-yellow-500',
    'bg-lime-500',
    'bg-green-500',
    'bg-emerald-500',
    'bg-teal-500',
    'bg-cyan-500',
    'bg-blue-500',
    'bg-indigo-500',
    'bg-purple-500',
    'bg-pink-500',
    'bg-rose-500',
    'bg-violet-500'
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
        <div class="bg-gray-800 rounded-xl shadow-lg p-8 mb-6 border border-gray-700 fade-in">
            <div class="flex items-center space-x-6">
                <div class="relative">
                    <div class="w-32 h-32 <?php echo $user['profile_avatar'] ? '' : $avatar_color; ?> rounded-full flex items-center justify-center overflow-hidden shadow-lg border-2 <?php echo $user['profile_avatar'] ? 'border-gray-600' : 'border-transparent'; ?>"
                        style="text-align: center; line-height: 1;">
                        <?php if ($user['profile_avatar']): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_avatar']); ?>"
                                alt="<?php echo htmlspecialchars($user['username']); ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <?php
                            $first_letter = strtoupper(mb_substr(trim($user['username']), 0, 1, 'UTF-8'));
                            if (empty($first_letter) || strlen($first_letter) > 1) {
                                $first_letter = strtoupper(substr(trim($user['username']), 0, 1)) ?: '?';
                            }
                            ?>
                            <span class="text-5xl text-white font-bold select-none whitespace-nowrap"
                                style="display: block; overflow: hidden; text-overflow: clip; max-width: 100%;"><?php echo htmlspecialchars($first_letter); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($user['is_admin']): ?>
                        <div
                            class="absolute -bottom-2 -right-2 bg-yellow-400 text-gray-900 px-3 py-1 rounded-full text-xs font-bold shadow-lg">
                            Admin
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <h1
                        class="text-4xl font-bold mb-2 bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                        <?php echo htmlspecialchars($user['username']); ?>
                    </h1>
                    <p class="text-gray-400 text-lg">Member since
                        <?php echo date('F Y', strtotime($user['join_date'])); ?>
                    </p>
                </div>
            </div>

            <!-- Favorite User Button (only if not own profile) -->
            <?php if (isset($_SESSION['user_id']) && !$is_own_profile): ?>
                <div class="mt-4">
                    <form method="post" action="../favorites/toggle.php" class="inline">
                        <input type="hidden" name="entity_type" value="user">
                        <input type="hidden" name="entity_id" value="<?php echo $view_user_id; ?>">
                        <input type="hidden" name="redirect_url"
                            value="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?user_id=' . $view_user_id); ?>">
                        <button type="submit"
                            class="relative group/icon px-4 py-2 rounded-lg transition-all duration-300 flex items-center space-x-2 <?php echo $is_user_favorited ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                            <?php if ($is_user_favorited): ?>
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>Remove from Favorites</span>
                            <?php else: ?>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                                <span>Add to Favorites</span>
                            <?php endif; ?>
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($is_own_profile): ?>
                <div class="mt-6">
                    <a href="edit.php"
                        class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 font-medium inline-block">
                        Edit Profile
                    </a>
                    <p class="text-sm text-gray-400 mt-3">Password change is available in the Edit Profile section</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Statistics -->
        <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700 mb-6 fade-in">
            <h2 class="text-2xl font-bold mb-6 bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                Statistics</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <!-- Reviews Written -->
                <div
                    class="group relative bg-gradient-to-br from-purple-600/20 to-purple-800/20 rounded-xl p-4 border border-purple-500/30 hover:border-purple-400/60 transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-purple-500/20">
                    <div class="flex flex-col items-center text-center">
                        <div
                            class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-700 rounded-full flex items-center justify-center mb-3 shadow-lg group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <span class="text-3xl font-bold text-purple-400 mb-1"><?php echo $review_count; ?></span>
                        <span class="text-xs text-gray-400 uppercase tracking-wider">Reviews</span>
                    </div>
                </div>

                <!-- Watchlists -->
                <div
                    class="group relative bg-gradient-to-br from-blue-600/20 to-blue-800/20 rounded-xl p-4 border border-blue-500/30 hover:border-blue-400/60 transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-blue-500/20">
                    <div class="flex flex-col items-center text-center">
                        <div
                            class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-700 rounded-full flex items-center justify-center mb-3 shadow-lg group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <span class="text-3xl font-bold text-blue-400 mb-1"><?php echo $watchlist_count; ?></span>
                        <span class="text-xs text-gray-400 uppercase tracking-wider">Watchlists</span>
                    </div>
                </div>

                <!-- Favorite Movies -->
                <div
                    class="group relative bg-gradient-to-br from-red-600/20 to-red-800/20 rounded-xl p-4 border border-red-500/30 hover:border-red-400/60 transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-red-500/20">
                    <div class="flex flex-col items-center text-center">
                        <div
                            class="w-12 h-12 bg-gradient-to-br from-red-500 to-red-700 rounded-full flex items-center justify-center mb-3 shadow-lg group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <span class="text-3xl font-bold text-red-400 mb-1"><?php echo $favorite_movies_count; ?></span>
                        <span class="text-xs text-gray-400 uppercase tracking-wider">Fav Movies</span>
                    </div>
                </div>

                <!-- Favorite Cast -->
                <div
                    class="group relative bg-gradient-to-br from-amber-600/20 to-amber-800/20 rounded-xl p-4 border border-amber-500/30 hover:border-amber-400/60 transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-amber-500/20">
                    <div class="flex flex-col items-center text-center">
                        <div
                            class="w-12 h-12 bg-gradient-to-br from-amber-500 to-amber-700 rounded-full flex items-center justify-center mb-3 shadow-lg group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </div>
                        <span class="text-3xl font-bold text-amber-400 mb-1"><?php echo $favorite_cast_count; ?></span>
                        <span class="text-xs text-gray-400 uppercase tracking-wider">Fav Cast</span>
                    </div>
                </div>

                <!-- Favorite Users -->
                <div
                    class="group relative bg-gradient-to-br from-pink-600/20 to-pink-800/20 rounded-xl p-4 border border-pink-500/30 hover:border-pink-400/60 transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-pink-500/20">
                    <div class="flex flex-col items-center text-center">
                        <div
                            class="w-12 h-12 bg-gradient-to-br from-pink-500 to-pink-700 rounded-full flex items-center justify-center mb-3 shadow-lg group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <span class="text-3xl font-bold text-pink-400 mb-1"><?php echo $favorite_users_count; ?></span>
                        <span class="text-xs text-gray-400 uppercase tracking-wider">Fav Users</span>
                    </div>
                </div>

                <!-- Watched Movies -->
                <div
                    class="group relative bg-gradient-to-br from-emerald-600/20 to-emerald-800/20 rounded-xl p-4 border border-emerald-500/30 hover:border-emerald-400/60 transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-emerald-500/20">
                    <div class="flex flex-col items-center text-center">
                        <div
                            class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-emerald-700 rounded-full flex items-center justify-center mb-3 shadow-lg group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <span class="text-3xl font-bold text-emerald-400 mb-1"><?php echo $watched_count; ?></span>
                        <span class="text-xs text-gray-400 uppercase tracking-wider">Watched</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Links -->
        <?php if ($is_own_profile): ?>
            <div class="mb-6 flex flex-wrap gap-3 fade-in">
                <a href="../favorites/index.php"
                    class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl font-medium">
                    View All Favorites
                </a>
                <a href="../watched/index.php"
                    class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-800 text-white rounded-lg hover:from-green-700 hover:to-green-900 transition-all duration-300 shadow-lg hover:shadow-xl font-medium">
                    View All Watched
                </a>
            </div>
        <?php endif; ?>

        <!-- Last Favorite Movies -->
        <?php if (mysqli_num_rows($last_fav_movies_result) > 0): ?>
            <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700 mb-6 fade-in">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                        Last Favorite Movies
                    </h2>
                    <?php if ($is_own_profile): ?>
                        <a href="../favorites/index.php"
                            class="text-red-400 hover:text-red-300 text-sm font-medium transition-colors duration-300">
                            View All →
                        </a>
                    <?php endif; ?>
                </div>
                <div class="flex flex-wrap justify-start gap-4">
                    <?php
                    mysqli_data_seek($last_fav_movies_result, 0);
                    while ($fav = mysqli_fetch_assoc($last_fav_movies_result)):
                        ?>
                        <a href="../movies/details.php?id=<?php echo $fav['entity_id']; ?>" class="inline-block">
                            <div class="aspect-[2/3] bg-gray-200 rounded overflow-hidden relative w-[120px]">
                                <?php if ($fav['poster_image']): ?>
                                    <img src="<?php echo htmlspecialchars(getImagePath($fav['poster_image'], 'poster')); ?>"
                                        alt="<?php echo htmlspecialchars($fav['title']); ?>"
                                        class="w-full h-full object-cover hover:scale-110 transition-transform duration-200 ease-out">
                                <?php else: ?>
                                    <div
                                        class="w-full h-full bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center">
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
            <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700 mb-6 fade-in">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                        Last Favorite Cast
                    </h2>
                    <?php if ($is_own_profile): ?>
                        <a href="../favorites/index.php"
                            class="text-red-400 hover:text-red-300 text-sm font-medium transition-colors duration-300">
                            View All →
                        </a>
                    <?php endif; ?>
                </div>
                <div class="flex flex-wrap justify-start gap-4">
                    <?php
                    mysqli_data_seek($last_fav_cast_result, 0);
                    while ($fav = mysqli_fetch_assoc($last_fav_cast_result)):
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
                                        <span
                                            class="text-white text-xl font-bold"><?php echo htmlspecialchars($cast_initial); ?></span>
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
            <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700 mb-6 fade-in">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                        Last Favorite Users
                    </h2>
                    <?php if ($is_own_profile): ?>
                        <a href="../favorites/index.php"
                            class="text-red-400 hover:text-red-300 text-sm font-medium transition-colors duration-300">
                            View All →
                        </a>
                    <?php endif; ?>
                </div>
                <div class="flex flex-wrap justify-start gap-4">
                    <?php
                    mysqli_data_seek($last_fav_users_result, 0);
                    while ($fav = mysqli_fetch_assoc($last_fav_users_result)):
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
                                        <span
                                            class="text-white text-xl font-bold"><?php echo htmlspecialchars($user_initial); ?></span>
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
            <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700 mb-6 fade-in">
                <div class="flex justify-between items-center mb-4">
                    <h2
                        class="text-xl font-bold bg-gradient-to-r from-green-400 to-green-600 bg-clip-text text-transparent">
                        Last Watched Movies
                    </h2>
                    <?php if ($is_own_profile): ?>
                        <a href="../watched/index.php"
                            class="text-green-400 hover:text-green-300 text-sm font-medium transition-colors duration-300">
                            View All →
                        </a>
                    <?php endif; ?>
                </div>
                <div class="flex flex-wrap justify-start gap-4">
                    <?php
                    mysqli_data_seek($last_watched_result, 0);
                    while ($watched = mysqli_fetch_assoc($last_watched_result)):
                        ?>
                        <a href="../movies/details.php?id=<?php echo $watched['movie_id']; ?>" class="inline-block">
                            <div class="aspect-[2/3] bg-gray-200 rounded overflow-hidden relative w-[120px]">
                                <?php if ($watched['poster_image']): ?>
                                    <img src="<?php echo htmlspecialchars(getImagePath($watched['poster_image'], 'poster')); ?>"
                                        alt="<?php echo htmlspecialchars($watched['title']); ?>"
                                        class="w-full h-full object-cover hover:scale-110 transition-transform duration-200 ease-out">
                                    <div
                                        class="absolute top-1 right-1 bg-green-500 text-white px-1 py-0.5 rounded text-[9px] font-bold shadow-lg">
                                        ✓
                                    </div>
                                <?php else: ?>
                                    <div
                                        class="w-full h-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center">
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
                <h2 class="text-2xl font-bold mb-6 bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                    Recent Reviews</h2>
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
                    <?php while ($review = mysqli_fetch_assoc($reviews_result)): ?>
                        <div
                            class="bg-gray-700 rounded-lg p-4 border border-gray-600 hover:border-red-500 transition-colors duration-300">
                            <div class="flex items-center justify-between mb-2">
                                <a href="../movies/details.php?id=<?php echo $review['movie_id']; ?>"
                                    class="font-semibold text-red-400 hover:text-red-300 transition-colors duration-300">
                                    <?php echo htmlspecialchars($review['title']); ?>
                                </a>
                                <span class="text-yellow-400 font-bold">★
                                    <?php echo number_format($review['rating_value'], 1); ?></span>
                            </div>
                            <p class="text-gray-300 text-sm mb-2">
                                <?php echo nl2br(htmlspecialchars(substr($review['review_text'], 0, 200))); ?>
                                <?php echo strlen($review['review_text']) > 200 ? '...' : ''; ?>
                            </p>
                            <p class="text-xs text-gray-400"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></p>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Danger Zone - Delete Account (at the bottom) -->
        <?php if ($is_own_profile): ?>
            <div class="mt-12 mb-2 fade-in">
                <div class="bg-gray-800/50 rounded-xl shadow-lg p-6 border border-red-900/50 relative overflow-hidden">
                    <!-- Warning stripe pattern -->
                    <div class="absolute inset-0 opacity-5">
                        <div class="absolute inset-0"
                            style="background: repeating-linear-gradient(45deg, #ef4444, #ef4444 10px, transparent 10px, transparent 20px);">
                        </div>
                    </div>

                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-red-600/20 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <h2 class="text-xl font-bold text-red-500">Danger Zone</h2>
                        </div>

                        <p class="text-gray-400 text-sm mb-4">
                            Once you delete your account, there is no going back. All your data including reviews,
                            watchlists, and favorites will be permanently deleted.
                        </p>

                        <a href="delete_account.php"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-transparent border-2 border-red-600 text-red-500 rounded-lg hover:bg-red-600 hover:text-white transition-all duration-300 font-medium group"
                            onclick="return confirm('Are you sure you want to delete your account? This action cannot be undone. All your data will be permanently deleted.');">
                            <svg class="w-5 h-5 transition-transform duration-300 group-hover:scale-110" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Delete My Account
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php require("../includes/footer.php"); ?>
</body>

</html>