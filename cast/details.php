<?php
session_start();
require("../connect.php");
require("../image_handler.php");

if (!isset($_GET['id'])) {
    header("Location: ../movies/browse.php");
    exit();
}

$cast_id = (int) $_GET['id'];

// Get cast member details
$cast_sql = "SELECT * FROM cast_members WHERE cast_id = $cast_id";
$cast_result = myQuery($cast_sql);
if (mysqli_num_rows($cast_result) == 0) {
    header("Location: ../movies/browse.php");
    exit();
}
$cast_member = mysqli_fetch_assoc($cast_result);

// Get movies this cast member appeared in
$movies_sql = "SELECT m.*, mc.character_name, mc.cast_order
               FROM movie_cast mc
               JOIN movies m ON mc.movie_id = m.movie_id
               WHERE mc.cast_id = $cast_id
               ORDER BY m.release_year DESC, mc.cast_order ASC";
$movies_result = myQuery($movies_sql);

// Get total movie count
$movie_count_sql = "SELECT COUNT(*) as total FROM movie_cast WHERE cast_id = $cast_id";
$movie_count_result = myQuery($movie_count_sql);
$total_movies = mysqli_fetch_assoc($movie_count_result)['total'];

// Check if cast member is favorited (if user is logged in)
$is_favorited = false;
if (isset($_SESSION['user_id'])) {
    $favorite_sql = "SELECT favorite_id FROM favorites 
                    WHERE user_id = " . $_SESSION['user_id'] . " 
                    AND entity_type = 'cast' 
                    AND entity_id = $cast_id";
    $favorite_result = myQuery($favorite_sql);
    $is_favorited = mysqli_num_rows($favorite_result) > 0;
}

// Avatar colors for cast member (if no photo)
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
$color_index = crc32($cast_member['name']) % count($avatar_colors);
$avatar_color = $avatar_colors[$color_index];
$cast_initial = mb_strtoupper(mb_substr($cast_member['name'], 0, 1, 'UTF-8'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($cast_member['name']); ?> - xGrab</title>
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
        <a href="../movies/browse.php"
            class="inline-flex items-center text-red-400 hover:text-red-300 mb-6 font-medium transition-colors duration-300 fade-in">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Browse
        </a>

        <div class="bg-gray-800 rounded-xl shadow-lg p-6 md:p-8 mb-6 border border-gray-700 fade-in">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Cast Member Photo -->
                <div>
                    <?php if ($cast_member['photo_url']): ?>
                        <img src="<?php echo htmlspecialchars($cast_member['photo_url']); ?>"
                            alt="<?php echo htmlspecialchars($cast_member['name']); ?>" class="w-full rounded-lg shadow-lg">
                    <?php else: ?>
                        <div
                            class="w-full aspect-[2/3] <?php echo $avatar_color; ?> rounded-lg flex items-center justify-center shadow-lg">
                            <span class="text-white text-6xl font-bold"
                                style="white-space: nowrap; overflow: hidden; text-overflow: clip; user-select: none;"><?php echo $cast_initial; ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Cast Member Info -->
                <div class="md:col-span-2">
                    <h1
                        class="text-4xl md:text-5xl font-bold mb-4 bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                        <?php echo htmlspecialchars($cast_member['name']); ?>
                    </h1>

                    <!-- Statistics and Favorite Button -->
                    <div class="mb-6">
                        <div class="flex items-center space-x-6 mb-4">
                            <div>
                                <p class="text-gray-400 text-sm">Movies</p>
                                <p class="text-2xl font-bold text-gray-100"><?php echo $total_movies; ?></p>
                            </div>
                        </div>

                        <!-- Favorite Button -->
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <form method="post" action="../favorites/toggle.php" class="inline">
                                <input type="hidden" name="entity_type" value="cast">
                                <input type="hidden" name="entity_id" value="<?php echo $cast_id; ?>">
                                <input type="hidden" name="redirect_url"
                                    value="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $cast_id); ?>">
                                <button type="submit"
                                    class="relative group/icon px-4 py-2 rounded-lg transition-all duration-300 flex items-center space-x-2 <?php echo $is_favorited ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                                    <?php if ($is_favorited): ?>
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
                        <?php endif; ?>
                    </div>

                    <!-- Biography -->
                    <?php if ($cast_member['biography']): ?>
                        <div class="mb-6">
                            <h2 class="text-xl font-bold mb-3 text-gray-100">Biography</h2>
                            <p class="text-gray-300 leading-relaxed">
                                <?php echo nl2br(htmlspecialchars($cast_member['biography'])); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="mb-6">
                            <p class="text-gray-400 italic">No biography available.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Movies Section -->
        <div class="bg-gray-800 rounded-lg shadow-md p-6 border border-gray-700 fade-in">
            <h2 class="text-2xl font-bold mb-6 text-gray-100">
                Movies (<?php echo $total_movies; ?>)
            </h2>

            <?php if (mysqli_num_rows($movies_result) > 0): ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                    <?php
                    mysqli_data_seek($movies_result, 0);
                    $delay = 0;
                    while ($movie = mysqli_fetch_assoc($movies_result)):
                        $delay += 50;
                        ?>
                        <div class="group bg-gray-800 rounded-xl shadow-md overflow-hidden hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-700 fade-in"
                            style="animation-delay: <?php echo $delay; ?>ms">
                            <a href="../movies/details.php?id=<?php echo $movie['movie_id']; ?>" class="block">
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
                                    <!-- Rating Badge -->
                                    <?php if ($movie['average_rating'] > 0): ?>
                                        <div
                                            class="absolute top-2 right-2 bg-yellow-400 text-gray-900 px-2 py-1 rounded-md text-xs font-bold flex items-center space-x-1 shadow-lg">
                                            <span>★</span>
                                            <span><?php echo number_format($movie['average_rating'], 1); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="p-4">
                                    <h3
                                        class="font-bold text-sm text-gray-100 truncate group-hover:text-red-400 transition-colors duration-300 mb-1">
                                        <?php echo htmlspecialchars($movie['title']); ?>
                                    </h3>
                                    <p class="text-xs text-gray-400 mb-2"><?php echo $movie['release_year']; ?></p>
                                    <?php if ($movie['character_name']): ?>
                                        <p class="text-xs text-red-400 font-medium">as
                                            <?php echo htmlspecialchars($movie['character_name']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <p class="text-gray-400">No movies found for this cast member.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>