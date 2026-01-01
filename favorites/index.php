<?php
/**
 * Favorites Index Page
 * Displays all user favorites organized by type (movies, cast, users).
 * Shows favorite movies, cast members, and followed users in separate sections.
 */

session_start();
// Include config if not already loaded
if (!defined('BASE_URL') && file_exists(__DIR__ . '/../includes/config.php')) {
    require_once __DIR__ . '/../includes/config.php';
}
require("../connect.php");
require("../image_handler.php");

// Require user to be logged in to view favorites
if (!isset($_SESSION['user_id'])) {
    $redirect_url = defined('BASE_URL') ? BASE_URL . 'login.php' : '../login.php';
    header("Location: " . $redirect_url);
    exit();
}

$user_id = $_SESSION['user_id'];

// Retrieve all favorites grouped by entity type
// Each query JOINs with the appropriate table to get display information

// Get favorite movies with movie details
// JOINs with movies table to get title, poster, release year, and rating
$favorites_movies_sql = "SELECT f.*, m.title, m.poster_image, m.release_year, m.average_rating 
                        FROM favorites f 
                        JOIN movies m ON f.entity_id = m.movie_id 
                        WHERE f.user_id = $user_id AND f.entity_type = 'movie' 
                        ORDER BY f.date_added DESC";
$favorites_movies_result = myQuery($favorites_movies_sql);

// Get favorite cast members with cast details
// JOINs with cast_members table to get name and photo
$favorites_cast_sql = "SELECT f.*, cm.name, cm.photo_url 
                      FROM favorites f 
                      JOIN cast_members cm ON f.entity_id = cm.cast_id 
                      WHERE f.user_id = $user_id AND f.entity_type = 'cast' 
                      ORDER BY f.date_added DESC";
$favorites_cast_result = myQuery($favorites_cast_sql);

// Get favorite users (users following other users) with user details
// JOINs with users table to get username and avatar
$favorites_users_sql = "SELECT f.*, u.username, u.profile_avatar 
                       FROM favorites f 
                       JOIN users u ON f.entity_id = u.user_id 
                       WHERE f.user_id = $user_id AND f.entity_type = 'user' 
                       ORDER BY f.date_added DESC";
$favorites_users_result = myQuery($favorites_users_sql);

// Calculate counts for each favorite type for display
$movies_count = mysqli_num_rows($favorites_movies_result);
$cast_count = mysqli_num_rows($favorites_cast_result);
$users_count = mysqli_num_rows($favorites_users_result);

// Define avatar color palette for users/cast without photos
// Colors are selected based on username/name hash for consistency
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
    <title>My Favorites - xGrab</title>
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
            My Favorites
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

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 fade-in">
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <p class="text-gray-400 text-sm">Favorite Movies</p>
                <p class="text-3xl font-bold text-red-400"><?php echo $movies_count; ?></p>
            </div>
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <p class="text-gray-400 text-sm">Favorite Cast</p>
                <p class="text-3xl font-bold text-red-400"><?php echo $cast_count; ?></p>
            </div>
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <p class="text-gray-400 text-sm">Favorite Users</p>
                <p class="text-3xl font-bold text-red-400"><?php echo $users_count; ?></p>
            </div>
        </div>

        <!-- Favorite Movies -->
        <div class="mb-8 fade-in">
            <h2 class="text-2xl font-bold mb-4 bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                Movies (<?php echo $movies_count; ?>)
            </h2>
            <?php if ($movies_count > 0): ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                    <?php
                    mysqli_data_seek($favorites_movies_result, 0);
                    while ($fav = mysqli_fetch_assoc($favorites_movies_result)):
                        ?>
                        <div
                            class="group bg-gray-800 rounded-xl shadow-md overflow-hidden hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-700">
                            <div class="relative">
                                <a href="../movies/details.php?id=<?php echo $fav['entity_id']; ?>">
                                    <div
                                        class="aspect-[2/3] bg-gray-200 flex items-center justify-center relative overflow-hidden">
                                        <?php if ($fav['poster_image']): ?>
                                            <img src="<?php echo htmlspecialchars(getImagePath($fav['poster_image'], 'poster')); ?>"
                                                alt="<?php echo htmlspecialchars($fav['title']); ?>"
                                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                        <?php else: ?>
                                            <div
                                                class="w-full h-full bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center">
                                                <span class="text-white text-sm font-medium">No Poster</span>
                                            </div>
                                        <?php endif; ?>
                                        <div
                                            class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                        </div>
                                    </div>
                                </a>
                                <!-- Favorite Button -->
                                <form method="post" action="toggle.php" class="absolute top-2 right-2">
                                    <input type="hidden" name="entity_type" value="movie">
                                    <input type="hidden" name="entity_id" value="<?php echo $fav['entity_id']; ?>">
                                    <input type="hidden" name="redirect_url"
                                        value="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                    <button type="submit"
                                        class="relative group/icon p-2 bg-red-600/80 rounded-full hover:bg-red-600 transition-all duration-300"
                                        title="Remove from Favorites">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </form>
                                <!-- Rating Badge -->
                                <div
                                    class="absolute top-2 left-2 bg-yellow-400 text-gray-900 px-2 py-1 rounded-md text-xs font-bold flex items-center space-x-1 shadow-lg">
                                    <span>★</span>
                                    <span><?php echo number_format($fav['average_rating'], 1); ?></span>
                                </div>
                            </div>
                            <div class="p-4">
                                <h3
                                    class="font-bold text-sm text-gray-100 truncate group-hover:text-red-400 transition-colors duration-300 mb-1">
                                    <a href="../movies/details.php?id=<?php echo $fav['entity_id']; ?>">
                                        <?php echo htmlspecialchars($fav['title']); ?>
                                    </a>
                                </h3>
                                <p class="text-xs text-gray-400"><?php echo $fav['release_year']; ?></p>
                                <p class="text-xs text-gray-500 mt-2">Added
                                    <?php echo date('M d, Y', strtotime($fav['date_added'])); ?>
                                </p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="bg-gray-800 rounded-lg p-8 text-center border border-gray-700">
                    <p class="text-gray-400">No favorite movies yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Favorite Cast -->
        <div class="mb-8 fade-in">
            <h2 class="text-2xl font-bold mb-4 bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                Cast Members (<?php echo $cast_count; ?>)
            </h2>
            <?php if ($cast_count > 0): ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                    <?php
                    mysqli_data_seek($favorites_cast_result, 0);
                    while ($fav = mysqli_fetch_assoc($favorites_cast_result)):
                        $cast_color_index = crc32($fav['name']) % count($avatar_colors);
                        $cast_color = $avatar_colors[$cast_color_index];
                        $cast_initial = mb_strtoupper(mb_substr($fav['name'], 0, 1, 'UTF-8'));
                        ?>
                        <div
                            class="group bg-gray-800 rounded-xl shadow-md overflow-hidden hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-700">
                            <div class="relative">
                                <a href="../cast/details.php?id=<?php echo $fav['entity_id']; ?>">
                                    <div
                                        class="aspect-[2/3] bg-gray-200 flex items-center justify-center relative overflow-hidden">
                                        <?php if ($fav['photo_url']): ?>
                                            <img src="<?php echo htmlspecialchars($fav['photo_url']); ?>"
                                                alt="<?php echo htmlspecialchars($fav['name']); ?>"
                                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                        <?php else: ?>
                                            <div class="w-full h-full <?php echo $cast_color; ?> flex items-center justify-center">
                                                <span
                                                    class="text-white text-4xl font-bold"><?php echo htmlspecialchars($cast_initial); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                                <!-- Favorite Button -->
                                <form method="post" action="toggle.php" class="absolute top-2 right-2">
                                    <input type="hidden" name="entity_type" value="cast">
                                    <input type="hidden" name="entity_id" value="<?php echo $fav['entity_id']; ?>">
                                    <input type="hidden" name="redirect_url"
                                        value="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                    <button type="submit"
                                        class="relative group/icon p-2 bg-red-600/80 rounded-full hover:bg-red-600 transition-all duration-300"
                                        title="Remove from Favorites">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                            <div class="p-4">
                                <h3
                                    class="font-bold text-sm text-gray-100 truncate group-hover:text-red-400 transition-colors duration-300 mb-1">
                                    <a href="../cast/details.php?id=<?php echo $fav['entity_id']; ?>">
                                        <?php echo htmlspecialchars($fav['name']); ?>
                                    </a>
                                </h3>
                                <p class="text-xs text-gray-500 mt-2">Added
                                    <?php echo date('M d, Y', strtotime($fav['date_added'])); ?>
                                </p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="bg-gray-800 rounded-lg p-8 text-center border border-gray-700">
                    <p class="text-gray-400">No favorite cast members yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Favorite Users -->
        <div class="mb-8 fade-in">
            <h2 class="text-2xl font-bold mb-4 bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                Users (<?php echo $users_count; ?>)
            </h2>
            <?php if ($users_count > 0): ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                    <?php
                    mysqli_data_seek($favorites_users_result, 0);
                    while ($fav = mysqli_fetch_assoc($favorites_users_result)):
                        $user_color_index = crc32($fav['username']) % count($avatar_colors);
                        $user_color = $avatar_colors[$user_color_index];
                        $user_initial = mb_strtoupper(mb_substr($fav['username'], 0, 1, 'UTF-8'));
                        ?>
                        <div
                            class="group bg-gray-800 rounded-xl shadow-md overflow-hidden hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-700">
                            <div class="relative">
                                <a href="../profile/view.php?user_id=<?php echo $fav['entity_id']; ?>">
                                    <div
                                        class="aspect-[2/3] bg-gray-200 flex items-center justify-center relative overflow-hidden">
                                        <?php if ($fav['profile_avatar']): ?>
                                            <img src="<?php echo htmlspecialchars(getImagePath($fav['profile_avatar'], 'avatar')); ?>"
                                                alt="<?php echo htmlspecialchars($fav['username']); ?>"
                                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                        <?php else: ?>
                                            <div class="w-full h-full <?php echo $user_color; ?> flex items-center justify-center">
                                                <span
                                                    class="text-white text-4xl font-bold"><?php echo htmlspecialchars($user_initial); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                                <!-- Favorite Button -->
                                <form method="post" action="toggle.php" class="absolute top-2 right-2">
                                    <input type="hidden" name="entity_type" value="user">
                                    <input type="hidden" name="entity_id" value="<?php echo $fav['entity_id']; ?>">
                                    <input type="hidden" name="redirect_url"
                                        value="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                    <button type="submit"
                                        class="relative group/icon p-2 bg-red-600/80 rounded-full hover:bg-red-600 transition-all duration-300"
                                        title="Remove from Favorites">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                            <div class="p-4">
                                <h3
                                    class="font-bold text-sm text-gray-100 truncate group-hover:text-red-400 transition-colors duration-300 mb-1">
                                    <a href="../profile/view.php?user_id=<?php echo $fav['entity_id']; ?>">
                                        <?php echo htmlspecialchars($fav['username']); ?>
                                    </a>
                                </h3>
                                <p class="text-xs text-gray-500 mt-2">Added
                                    <?php echo date('M d, Y', strtotime($fav['date_added'])); ?>
                                </p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="bg-gray-800 rounded-lg p-8 text-center border border-gray-700">
                    <p class="text-gray-400">No favorite users yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php require("../includes/footer.php"); ?>
</body>

</html>