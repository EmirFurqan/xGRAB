<?php
/**
 * Global Search Results Page
 * Displays search results for movies, cast members, and users.
 */

session_start();
// Include config if not already loaded
if (!defined('BASE_URL') && file_exists(__DIR__ . '/includes/config.php')) {
    require_once __DIR__ . '/includes/config.php';
}
require("connect.php");
require("image_handler.php");

// Get search query
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$search_term = escapeString($query);
$search_pattern = "%$search_term%";

// Get type filter (all, movies, cast, users)
$type = isset($_GET['type']) ? $_GET['type'] : 'all';

// Results arrays
$movies = [];
$cast_members = [];
$users = [];

// Search based on type filter
if ($type === 'all' || $type === 'movies') {
    $movies_sql = "SELECT m.*, 
                   GROUP_CONCAT(g.genre_name SEPARATOR ', ') as genres
                   FROM movies m
                   LEFT JOIN movie_genres mg ON m.movie_id = mg.movie_id
                   LEFT JOIN genres g ON mg.genre_id = g.genre_id
                   WHERE m.title LIKE '$search_pattern'
                   GROUP BY m.movie_id
                   ORDER BY m.average_rating DESC, m.total_ratings DESC
                   LIMIT 20";
    $movies_result = myQuery($movies_sql);
    while ($movie = mysqli_fetch_assoc($movies_result)) {
        $movies[] = $movie;
    }
}

if ($type === 'all' || $type === 'cast') {
    $cast_sql = "SELECT cm.*, 
                 (SELECT COUNT(*) FROM movie_cast WHERE cast_id = cm.cast_id) as movie_count
                 FROM cast_members cm
                 WHERE cm.name LIKE '$search_pattern'
                 ORDER BY movie_count DESC, cm.name ASC
                 LIMIT 20";
    $cast_result = myQuery($cast_sql);
    while ($cast = mysqli_fetch_assoc($cast_result)) {
        $cast_members[] = $cast;
    }
}

if ($type === 'all' || $type === 'users') {
    $users_sql = "SELECT u.*, 
                  (SELECT COUNT(*) FROM reviews WHERE user_id = u.user_id) as review_count
                  FROM users u
                  WHERE u.username LIKE '$search_pattern'
                  ORDER BY review_count DESC, u.username ASC
                  LIMIT 20";
    $users_result = myQuery($users_sql);
    while ($user = mysqli_fetch_assoc($users_result)) {
        $users[] = $user;
    }
}

// Count totals
$total_movies = count($movies);
$total_cast = count($cast_members);
$total_users = count($users);
$total_results = $total_movies + $total_cast + $total_users;

// Avatar colors for fallback
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search: <?php echo htmlspecialchars($query); ?> - xGrab</title>
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
            animation: fadeIn 0.4s ease-out;
        }
    </style>
</head>

<body class="bg-gray-900 min-h-screen text-gray-100">
    <?php require("includes/nav.php"); ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Search Header -->
        <div class="mb-8 fade-in">
            <h1 class="text-3xl font-bold mb-2">
                Search Results for
                <span class="bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                    "<?php echo htmlspecialchars($query); ?>"
                </span>
            </h1>
            <p class="text-gray-400">Found <?php echo $total_results; ?>
                result<?php echo $total_results !== 1 ? 's' : ''; ?></p>
        </div>

        <!-- Type Filter Tabs -->
        <div class="flex flex-wrap gap-2 mb-6 fade-in">
            <a href="?q=<?php echo urlencode($query); ?>&type=all"
                class="px-4 py-2 rounded-lg font-medium transition-all duration-300 <?php echo $type === 'all' ? 'bg-gradient-to-r from-red-600 to-red-800 text-white shadow-lg' : 'bg-gray-800 text-gray-300 hover:bg-gray-700'; ?>">
                All (<?php echo $total_results; ?>)
            </a>
            <a href="?q=<?php echo urlencode($query); ?>&type=movies"
                class="px-4 py-2 rounded-lg font-medium transition-all duration-300 <?php echo $type === 'movies' ? 'bg-gradient-to-r from-red-600 to-red-800 text-white shadow-lg' : 'bg-gray-800 text-gray-300 hover:bg-gray-700'; ?>">
                🎬 Movies (<?php echo $total_movies; ?>)
            </a>
            <a href="?q=<?php echo urlencode($query); ?>&type=cast"
                class="px-4 py-2 rounded-lg font-medium transition-all duration-300 <?php echo $type === 'cast' ? 'bg-gradient-to-r from-red-600 to-red-800 text-white shadow-lg' : 'bg-gray-800 text-gray-300 hover:bg-gray-700'; ?>">
                ⭐ Cast (<?php echo $total_cast; ?>)
            </a>
            <a href="?q=<?php echo urlencode($query); ?>&type=users"
                class="px-4 py-2 rounded-lg font-medium transition-all duration-300 <?php echo $type === 'users' ? 'bg-gradient-to-r from-red-600 to-red-800 text-white shadow-lg' : 'bg-gray-800 text-gray-300 hover:bg-gray-700'; ?>">
                👥 Users (<?php echo $total_users; ?>)
            </a>
        </div>

        <?php if ($total_results === 0): ?>
            <div class="text-center py-16 fade-in">
                <svg class="w-24 h-24 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <h2 class="text-2xl font-bold text-gray-400 mb-2">No results found</h2>
                <p class="text-gray-500">Try a different search term</p>
            </div>
        <?php endif; ?>

        <!-- Movies Results -->
        <?php if (!empty($movies) && ($type === 'all' || $type === 'movies')): ?>
            <div class="mb-10 fade-in">
                <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                    <span>🎬</span>
                    <span class="bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">Movies</span>
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                    <?php foreach ($movies as $movie): ?>
                        <a href="movies/details.php?id=<?php echo $movie['movie_id']; ?>"
                            class="group bg-gray-800 rounded-xl shadow-md overflow-hidden hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-700">
                            <div class="aspect-[2/3] bg-gray-700 relative overflow-hidden">
                                <?php if ($movie['poster_image']): ?>
                                    <img src="<?php echo htmlspecialchars(getImagePath($movie['poster_image'], 'poster')); ?>"
                                        alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                <?php else: ?>
                                    <div
                                        class="w-full h-full bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center">
                                        <span class="text-white text-sm">No Poster</span>
                                    </div>
                                <?php endif; ?>
                                <!-- Rating Badge -->
                                <div
                                    class="absolute top-2 left-2 bg-yellow-400 text-gray-900 px-2 py-1 rounded-md text-xs font-bold flex items-center space-x-1 shadow-lg">
                                    <span>★</span>
                                    <span><?php echo number_format($movie['average_rating'], 1); ?></span>
                                </div>
                            </div>
                            <div class="p-3">
                                <h3
                                    class="font-bold text-sm text-gray-100 truncate group-hover:text-red-400 transition-colors duration-300">
                                    <?php echo htmlspecialchars($movie['title']); ?>
                                </h3>
                                <p class="text-xs text-gray-400 mt-1"><?php echo $movie['release_year']; ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Cast Results -->
        <?php if (!empty($cast_members) && ($type === 'all' || $type === 'cast')): ?>
            <div class="mb-10 fade-in">
                <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                    <span>⭐</span>
                    <span class="bg-gradient-to-r from-amber-400 to-amber-600 bg-clip-text text-transparent">Cast
                        Members</span>
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <?php foreach ($cast_members as $cast):
                        $cast_color_index = crc32($cast['name']) % count($avatar_colors);
                        $cast_color = $avatar_colors[$cast_color_index];
                        $cast_initial = strtoupper(mb_substr(trim($cast['name']), 0, 1, 'UTF-8'));
                        ?>
                        <a href="cast/details.php?id=<?php echo $cast['cast_id']; ?>"
                            class="group text-center p-4 bg-gray-800 rounded-xl border border-gray-700 hover:border-amber-500 transition-all duration-300 hover:shadow-lg hover:shadow-amber-500/10">
                            <div
                                class="w-20 h-20 mx-auto <?php echo $cast['photo_url'] ? 'bg-gray-700' : $cast_color; ?> rounded-full flex items-center justify-center overflow-hidden border-2 border-gray-600 group-hover:border-amber-500 transition-all duration-300 mb-3">
                                <?php if ($cast['photo_url']): ?>
                                    <img src="<?php echo htmlspecialchars(getImagePath($cast['photo_url'], 'cast')); ?>"
                                        alt="<?php echo htmlspecialchars($cast['name']); ?>"
                                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                <?php else: ?>
                                    <span
                                        class="text-white text-2xl font-bold"><?php echo htmlspecialchars($cast_initial); ?></span>
                                <?php endif; ?>
                            </div>
                            <h3
                                class="font-semibold text-sm text-gray-100 group-hover:text-amber-400 transition-colors duration-300 truncate">
                                <?php echo htmlspecialchars($cast['name']); ?>
                            </h3>
                            <p class="text-xs text-gray-400 mt-1"><?php echo $cast['movie_count']; ?>
                                movie<?php echo $cast['movie_count'] != 1 ? 's' : ''; ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Users Results -->
        <?php if (!empty($users) && ($type === 'all' || $type === 'users')): ?>
            <div class="mb-10 fade-in">
                <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                    <span>👥</span>
                    <span class="bg-gradient-to-r from-blue-400 to-blue-600 bg-clip-text text-transparent">Users</span>
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <?php foreach ($users as $user):
                        $user_color_index = crc32($user['username']) % count($avatar_colors);
                        $user_color = $avatar_colors[$user_color_index];
                        $user_initial = strtoupper(mb_substr(trim($user['username']), 0, 1, 'UTF-8'));
                        ?>
                        <a href="profile/view.php?user_id=<?php echo $user['user_id']; ?>"
                            class="group text-center p-4 bg-gray-800 rounded-xl border border-gray-700 hover:border-blue-500 transition-all duration-300 hover:shadow-lg hover:shadow-blue-500/10">
                            <div
                                class="w-20 h-20 mx-auto <?php echo $user['profile_avatar'] ? 'bg-gray-700' : $user_color; ?> rounded-full flex items-center justify-center overflow-hidden border-2 border-gray-600 group-hover:border-blue-500 transition-all duration-300 mb-3">
                                <?php if ($user['profile_avatar']): ?>
                                    <img src="<?php echo htmlspecialchars(getImagePath($user['profile_avatar'], 'avatar')); ?>"
                                        alt="<?php echo htmlspecialchars($user['username']); ?>"
                                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                <?php else: ?>
                                    <span
                                        class="text-white text-2xl font-bold"><?php echo htmlspecialchars($user_initial); ?></span>
                                <?php endif; ?>
                            </div>
                            <h3
                                class="font-semibold text-sm text-gray-100 group-hover:text-blue-400 transition-colors duration-300 truncate">
                                <?php echo htmlspecialchars($user['username']); ?>
                            </h3>
                            <p class="text-xs text-gray-400 mt-1"><?php echo $user['review_count']; ?>
                                review<?php echo $user['review_count'] != 1 ? 's' : ''; ?></p>
                            <?php if ($user['is_admin']): ?>
                                <span
                                    class="inline-block mt-1 px-2 py-0.5 bg-yellow-500/20 text-yellow-400 rounded text-xs font-medium">Admin</span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php require("includes/footer.php"); ?>
</body>

</html>