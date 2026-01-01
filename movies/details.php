<?php
/**
 * Movie Details Page - Redesigned
 * Comprehensive movie information with modern, cinematic design
 */

session_start();
require("../connect.php");
require("../image_handler.php");

// Require movie ID parameter
if (!isset($_GET['id'])) {
    header("Location: browse.php");
    exit();
}

$movie_id = (int) $_GET['id'];

// Get movie info
$sql = "SELECT * FROM movies WHERE movie_id = $movie_id";
$result = myQuery($sql);

if (mysqli_num_rows($result) == 0) {
    header("Location: browse.php");
    exit();
}
$movie = mysqli_fetch_assoc($result);

// Get genres
$genres_sql = "SELECT g.genre_id, g.genre_name FROM movie_genres mg 
               JOIN genres g ON mg.genre_id = g.genre_id 
               WHERE mg.movie_id = $movie_id";
$genres_result = myQuery($genres_sql);

// Get cast (top 10)
$cast_sql = "SELECT cm.*, mc.character_name, mc.cast_order 
             FROM movie_cast mc 
             JOIN cast_members cm ON mc.cast_id = cm.cast_id 
             WHERE mc.movie_id = $movie_id 
             ORDER BY mc.cast_order ASC 
             LIMIT 10";
$cast_result = myQuery($cast_sql);

// Get crew
$crew_sql = "SELECT crm.*, mc.role 
             FROM movie_crew mc 
             JOIN crew_members crm ON mc.crew_id = crm.crew_id 
             WHERE mc.movie_id = $movie_id 
             ORDER BY mc.role";
$crew_result = myQuery($crew_sql);

// Get trailers
$trailer_sql = "SELECT * FROM movie_trailers WHERE movie_id = $movie_id";
$trailer_result = myQuery($trailer_sql);

// Get reviews with pagination
$review_page = isset($_GET['review_page']) ? (int) $_GET['review_page'] : 1;
$reviews_per_page = 5;
$review_offset = ($review_page - 1) * $reviews_per_page;

$review_sql = "SELECT r.*, u.username, u.profile_avatar 
               FROM reviews r 
               JOIN users u ON r.user_id = u.user_id 
               WHERE r.movie_id = $movie_id AND r.is_flagged = FALSE 
               ORDER BY r.like_count DESC, r.created_at DESC 
               LIMIT $reviews_per_page OFFSET $review_offset";
$review_result = myQuery($review_sql);

// Review count for pagination
$review_count_sql = "SELECT COUNT(*) as total FROM reviews WHERE movie_id = $movie_id AND is_flagged = FALSE";
$review_count_result = myQuery($review_count_sql);
$total_reviews = mysqli_fetch_assoc($review_count_result)['total'];
$total_review_pages = ceil($total_reviews / $reviews_per_page);

// User-specific data
$user_review = null;
$watchlist_result = null;
$is_favorited = false;
$is_watched = false;
$liked_reviews = [];
$watchlists_array = [];

if (isset($_SESSION['user_id'])) {
    // Check user's review
    $user_review_sql = "SELECT * FROM reviews WHERE movie_id = $movie_id AND user_id = " . $_SESSION['user_id'];
    $user_review_result = myQuery($user_review_sql);
    if (mysqli_num_rows($user_review_result) > 0) {
        $user_review = mysqli_fetch_assoc($user_review_result);
    }

    // Get watchlists
    $watchlist_sql = "SELECT w.watchlist_id, w.watchlist_name, wm.movie_id 
                      FROM watchlists w 
                      LEFT JOIN watchlist_movies wm ON w.watchlist_id = wm.watchlist_id AND wm.movie_id = $movie_id
                      WHERE w.user_id = " . $_SESSION['user_id'];
    $watchlist_result = myQuery($watchlist_sql);
    while ($wl = mysqli_fetch_assoc($watchlist_result)) {
        $watchlists_array[] = $wl;
    }

    // Check favorite status
    $favorite_sql = "SELECT favorite_id FROM favorites 
                    WHERE user_id = " . $_SESSION['user_id'] . " 
                    AND entity_type = 'movie' 
                    AND entity_id = $movie_id";
    $favorite_result = myQuery($favorite_sql);
    $is_favorited = mysqli_num_rows($favorite_result) > 0;

    // Check watched status
    $watched_sql = "SELECT * FROM user_watched_movies 
                   WHERE user_id = " . $_SESSION['user_id'] . " 
                   AND movie_id = $movie_id";
    $watched_result = myQuery($watched_sql);
    $is_watched = mysqli_num_rows($watched_result) > 0;

    // Get liked reviews
    $review_ids = [];
    mysqli_data_seek($review_result, 0);
    while ($rev = mysqli_fetch_assoc($review_result)) {
        $review_ids[] = $rev['review_id'];
    }
    mysqli_data_seek($review_result, 0);

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
}

// Get backdrop image (use poster if no backdrop)
$backdrop_image = $movie['backdrop_image'] ?? $movie['poster_image'];
$poster_path = $movie['poster_image'] ? getImagePath($movie['poster_image'], 'poster') : '';

// Format runtime
$runtime_formatted = '';
if ($movie['runtime']) {
    $hours = floor($movie['runtime'] / 60);
    $minutes = $movie['runtime'] % 60;
    $runtime_formatted = $hours . 'h ' . $minutes . 'm';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($movie['title']); ?> - xGrab</title>
    <meta name="description" content="<?php echo htmlspecialchars(substr($movie['description'] ?? '', 0, 160)); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        /* Hero backdrop */
        .hero-backdrop {
            position: relative;
            min-height: 70vh;
            background-size: cover;
            background-position: center top;
        }

        .hero-backdrop::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom,
                    rgba(17, 24, 39, 0.4) 0%,
                    rgba(17, 24, 39, 0.7) 50%,
                    rgba(17, 24, 39, 1) 100%);
        }

        .hero-backdrop::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to right,
                    rgba(17, 24, 39, 0.9) 0%,
                    rgba(17, 24, 39, 0.4) 50%,
                    rgba(17, 24, 39, 0.9) 100%);
        }

        /* Glassmorphism cards */
        .glass-card {
            background: rgba(31, 41, 55, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Poster hover effect */
        .poster-container {
            transform-style: preserve-3d;
            perspective: 1000px;
        }

        .poster-card {
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            transform: rotateY(0deg);
        }

        .poster-container:hover .poster-card {
            transform: rotateY(-5deg) translateZ(20px);
            box-shadow: 20px 20px 60px rgba(0, 0, 0, 0.5);
        }

        /* Rating badge pulse */
        @keyframes ratingPulse {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(234, 179, 8, 0.4);
            }

            50% {
                box-shadow: 0 0 0 10px rgba(234, 179, 8, 0);
            }
        }

        .rating-badge:hover {
            animation: ratingPulse 1.5s infinite;
        }

        /* Cast scroll */
        .cast-scroll {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .cast-scroll::-webkit-scrollbar {
            display: none;
        }

        /* Animations */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fadeUp {
            animation: fadeUp 0.6s ease-out forwards;
        }

        .delay-100 {
            animation-delay: 0.1s;
        }

        .delay-200 {
            animation-delay: 0.2s;
        }

        .delay-300 {
            animation-delay: 0.3s;
        }

        /* Star rating */
        .star-rating {
            display: flex;
            gap: 2px;
        }

        .star-rating .star {
            cursor: pointer;
            font-size: 1.5rem;
            color: #4b5563;
            transition: all 0.15s ease;
            position: relative;
        }

        .star-rating .star:hover {
            transform: scale(1.2);
        }

        .star-rating .star.filled {
            color: #fbbf24;
        }

        .star-rating .star.half {
            background: linear-gradient(90deg, #fbbf24 50%, #4b5563 50%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .star-rating .star.preview {
            color: #f59e0b;
        }

        .star-rating .star.half-preview {
            background: linear-gradient(90deg, #f59e0b 50%, #4b5563 50%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Action buttons */
        .action-btn {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .action-btn:hover {
            transform: translateY(-2px);
        }

        .action-btn:active {
            transform: scale(0.95);
        }
    </style>
</head>

<body class="bg-gray-900 text-gray-100">
    <?php require("../includes/nav.php"); ?>
    <?php require("../includes/toast.php"); ?>

    <!-- Hero Section with Backdrop -->
    <div class="hero-backdrop"
        style="background-image: url('<?php echo $backdrop_image ? htmlspecialchars(getImagePath($backdrop_image, 'backdrop')) : ''; ?>');">
        <div class="relative z-10 container mx-auto px-4 pt-8 pb-16">
            <!-- Back Button -->
            <a href="browse.php"
                class="inline-flex items-center gap-2 text-gray-300 hover:text-white mb-8 transition-colors group">
                <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span class="font-medium">Back to Browse</span>
            </a>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-12 items-start">
                <!-- Poster -->
                <div class="poster-container mx-auto lg:mx-0 opacity-0 animate-fadeUp">
                    <div class="poster-card rounded-2xl overflow-hidden shadow-2xl max-w-[300px]">
                        <?php if ($poster_path): ?>
                            <img src="<?php echo htmlspecialchars($poster_path); ?>"
                                alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                class="w-full aspect-[2/3] object-cover">
                        <?php else: ?>
                            <div class="w-full aspect-[2/3] bg-gray-800 flex items-center justify-center">
                                <svg class="w-16 h-16 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Movie Info -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Title & Year -->
                    <div class="opacity-0 animate-fadeUp delay-100">
                        <h1 class="text-4xl md:text-5xl lg:text-6xl font-black mb-3 leading-tight">
                            <?php echo htmlspecialchars($movie['title']); ?>
                        </h1>
                        <div class="flex flex-wrap items-center gap-3 text-gray-300">
                            <span class="text-xl font-semibold"><?php echo $movie['release_year']; ?></span>
                            <?php if ($runtime_formatted): ?>
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-500"></span>
                                <span><?php echo $runtime_formatted; ?></span>
                            <?php endif; ?>
                            <?php if ($movie['original_language']): ?>
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-500"></span>
                                <span class="uppercase"><?php echo $movie['original_language']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Ratings -->
                    <div class="flex flex-wrap gap-4 opacity-0 animate-fadeUp delay-200">
                        <!-- IMDb Rating -->
                        <div
                            class="rating-badge glass-card rounded-xl p-4 flex items-center gap-3 hover:border-yellow-500/50 transition-all cursor-default">
                            <div class="bg-yellow-500 text-gray-900 font-black text-sm px-2 py-1 rounded">IMDb</div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-yellow-400 text-2xl">★</span>
                                <span
                                    class="text-2xl font-bold"><?php echo number_format($movie['average_rating'], 1); ?></span>
                                <span class="text-gray-400">/10</span>
                            </div>
                            <span class="text-xs text-gray-400"><?php echo number_format($movie['total_ratings']); ?>
                                votes</span>
                        </div>

                        <!-- xGrab Rating -->
                        <?php if (isset($movie['xgrab_total_ratings']) && $movie['xgrab_total_ratings'] > 0): ?>
                            <div
                                class="rating-badge glass-card rounded-xl p-4 flex items-center gap-3 hover:border-red-500/50 transition-all cursor-default">
                                <div
                                    class="bg-gradient-to-r from-red-600 to-red-700 text-white font-black text-sm px-2 py-1 rounded flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                    xGrab
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-red-400 text-2xl">★</span>
                                    <span
                                        class="text-2xl font-bold"><?php echo number_format($movie['xgrab_average_rating'], 1); ?></span>
                                    <span class="text-gray-400">/10</span>
                                </div>
                                <span
                                    class="text-xs text-gray-400"><?php echo number_format($movie['xgrab_total_ratings']); ?>
                                    ratings</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Genres -->
                    <div class="flex flex-wrap gap-2 opacity-0 animate-fadeUp delay-300">
                        <?php
                        mysqli_data_seek($genres_result, 0);
                        while ($genre = mysqli_fetch_assoc($genres_result)):
                            ?>
                            <a href="browse.php?genres[]=<?php echo $genre['genre_id']; ?>"
                                class="px-4 py-1.5 bg-white/10 hover:bg-red-600 text-sm font-medium rounded-full transition-all duration-300 border border-white/20 hover:border-red-600">
                                <?php echo htmlspecialchars($genre['genre_name']); ?>
                            </a>
                        <?php endwhile; ?>
                    </div>

                    <!-- Overview -->
                    <?php if ($movie['description']): ?>
                        <div class="opacity-0 animate-fadeUp" style="animation-delay: 0.4s;">
                            <p class="text-gray-300 text-lg leading-relaxed max-w-3xl">
                                <?php echo nl2br(htmlspecialchars($movie['description'])); ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="flex flex-wrap gap-3 pt-2 opacity-0 animate-fadeUp" style="animation-delay: 0.5s;">
                            <!-- Favorite -->
                            <form method="post" action="../favorites/toggle.php" class="inline">
                                <input type="hidden" name="entity_type" value="movie">
                                <input type="hidden" name="entity_id" value="<?php echo $movie_id; ?>">
                                <input type="hidden" name="redirect_url"
                                    value="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $movie_id); ?>">
                                <button type="submit"
                                    class="action-btn flex items-center gap-2 px-5 py-3 rounded-xl font-semibold <?php echo $is_favorited ? 'bg-red-600 text-white' : 'bg-white/10 text-gray-200 hover:bg-white/20'; ?>">
                                    <?php if ($is_favorited): ?>
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        <span>Favorited</span>
                                    <?php else: ?>
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                        </svg>
                                        <span>Favorite</span>
                                    <?php endif; ?>
                                </button>
                            </form>

                            <!-- Watched -->
                            <form method="post" action="../watched/toggle.php" class="inline">
                                <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">
                                <input type="hidden" name="redirect_url"
                                    value="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $movie_id); ?>">
                                <button type="submit"
                                    class="action-btn flex items-center gap-2 px-5 py-3 rounded-xl font-semibold <?php echo $is_watched ? 'bg-green-600 text-white' : 'bg-white/10 text-gray-200 hover:bg-white/20'; ?>">
                                    <?php if ($is_watched): ?>
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        <span>Watched</span>
                                    <?php else: ?>
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <span>Mark Watched</span>
                                    <?php endif; ?>
                                </button>
                            </form>

                            <!-- Watchlist -->
                            <?php if (!empty($watchlists_array)): ?>
                                <button type="button" onclick="openWatchlistModal()"
                                    class="action-btn flex items-center gap-2 px-5 py-3 rounded-xl font-semibold bg-blue-600 hover:bg-blue-700 text-white">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    <span>Add to Watchlist</span>
                                </button>
                            <?php else: ?>
                                <a href="../watchlist/create.php"
                                    class="action-btn flex items-center gap-2 px-5 py-3 rounded-xl font-semibold bg-blue-600 hover:bg-blue-700 text-white">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    <span>Create Watchlist</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-12 space-y-12">

        <!-- Movie Stats -->
        <?php if ($movie['budget'] > 0 || $movie['revenue'] > 0): ?>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php if ($movie['budget'] > 0): ?>
                    <div class="glass-card rounded-xl p-5 text-center">
                        <div class="text-gray-400 text-sm mb-1">Budget</div>
                        <div class="text-xl font-bold text-green-400">$<?php echo number_format($movie['budget']); ?></div>
                    </div>
                <?php endif; ?>
                <?php if ($movie['revenue'] > 0): ?>
                    <div class="glass-card rounded-xl p-5 text-center">
                        <div class="text-gray-400 text-sm mb-1">Box Office</div>
                        <div class="text-xl font-bold text-emerald-400">$<?php echo number_format($movie['revenue']); ?></div>
                    </div>
                <?php endif; ?>
                <div class="glass-card rounded-xl p-5 text-center">
                    <div class="text-gray-400 text-sm mb-1">Reviews</div>
                    <div class="text-xl font-bold text-blue-400"><?php echo number_format($total_reviews); ?></div>
                </div>
                <div class="glass-card rounded-xl p-5 text-center">
                    <div class="text-gray-400 text-sm mb-1">Rating</div>
                    <div class="text-xl font-bold text-yellow-400">★
                        <?php echo number_format($movie['average_rating'], 1); ?></div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Trailer Section -->
        <?php
        mysqli_data_seek($trailer_result, 0);
        if (mysqli_num_rows($trailer_result) > 0):
            $trailer = mysqli_fetch_assoc($trailer_result);
            $video_id = '';
            if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $trailer['trailer_url'], $matches)) {
                $video_id = $matches[1];
            }
            ?>
            <section>
                <h2 class="text-2xl font-bold mb-6 flex items-center gap-3">
                    <svg class="w-7 h-7 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"
                            clip-rule="evenodd" />
                    </svg>
                    Trailer
                </h2>
                <?php if ($video_id): ?>
                    <div class="glass-card rounded-2xl overflow-hidden">
                        <div class="aspect-video">
                            <iframe class="w-full h-full" src="https://www.youtube.com/embed/<?php echo $video_id; ?>"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen></iframe>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <!-- Cast Section -->
        <?php if (mysqli_num_rows($cast_result) > 0): ?>
            <section>
                <h2 class="text-2xl font-bold mb-6 flex items-center gap-3">
                    <svg class="w-7 h-7 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Top Cast
                </h2>
                <div class="cast-scroll flex gap-4 overflow-x-auto pb-4">
                    <?php
                    mysqli_data_seek($cast_result, 0);
                    while ($cast = mysqli_fetch_assoc($cast_result)):
                        ?>
                        <a href="../cast/details.php?id=<?php echo $cast['cast_id']; ?>" class="flex-shrink-0 w-32 group">
                            <div class="relative mb-3 overflow-hidden rounded-xl">
                                <?php if ($cast['photo_url']): ?>
                                    <img src="<?php echo htmlspecialchars($cast['photo_url']); ?>"
                                        alt="<?php echo htmlspecialchars($cast['name']); ?>"
                                        class="w-32 h-40 object-cover group-hover:scale-110 transition-transform duration-300">
                                <?php else: ?>
                                    <div class="w-32 h-40 bg-gray-700 flex items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                <?php endif; ?>
                                <div
                                    class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                                </div>
                            </div>
                            <p class="font-semibold text-sm text-center group-hover:text-red-400 transition-colors truncate">
                                <?php echo htmlspecialchars($cast['name']); ?></p>
                            <?php if ($cast['character_name']): ?>
                                <p class="text-xs text-gray-400 text-center truncate">
                                    <?php echo htmlspecialchars($cast['character_name']); ?></p>
                            <?php endif; ?>
                        </a>
                    <?php endwhile; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Crew Section -->
        <?php if (mysqli_num_rows($crew_result) > 0): ?>
            <section>
                <h2 class="text-2xl font-bold mb-6 flex items-center gap-3">
                    <svg class="w-7 h-7 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    Crew
                </h2>
                <?php
                $crew_by_role = [];
                mysqli_data_seek($crew_result, 0);
                while ($crew = mysqli_fetch_assoc($crew_result)) {
                    $role = $crew['role'];
                    if (!isset($crew_by_role[$role])) {
                        $crew_by_role[$role] = [];
                    }
                    $crew_by_role[$role][] = $crew;
                }
                ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($crew_by_role as $role => $members): ?>
                        <div class="glass-card rounded-xl p-5">
                            <h3 class="font-bold text-red-400 mb-3"><?php echo htmlspecialchars($role); ?></h3>
                            <div class="space-y-3">
                                <?php foreach ($members as $member):
                                    $crew_colors = ['bg-red-500', 'bg-orange-500', 'bg-amber-500', 'bg-green-500', 'bg-teal-500', 'bg-blue-500', 'bg-indigo-500', 'bg-purple-500', 'bg-pink-500'];
                                    $crew_color = $crew_colors[crc32($member['name']) % count($crew_colors)];
                                    $crew_initial = strtoupper(substr(trim($member['name']), 0, 1));
                                    ?>
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-full overflow-hidden flex-shrink-0 <?php echo $member['photo_url'] ? '' : $crew_color; ?> flex items-center justify-center">
                                            <?php if ($member['photo_url']): ?>
                                                <img src="<?php echo htmlspecialchars($member['photo_url']); ?>"
                                                    class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <span class="text-white font-bold"><?php echo htmlspecialchars($crew_initial); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-gray-200"><?php echo htmlspecialchars($member['name']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Reviews Section -->
        <section>
            <h2 class="text-2xl font-bold mb-6 flex items-center gap-3">
                <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                Reviews
                <span class="text-base font-normal text-gray-400">(<?php echo $total_reviews; ?>)</span>
            </h2>

            <?php if (isset($_GET['error']) || isset($_GET['success'])): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    <?php if (isset($_GET['error'])): ?>
                    if (typeof showToast === 'function') {
                        showToast(<?php echo json_encode($_GET['error']); ?>, 'error', 5000);
                    }
                    <?php endif; ?>
                    <?php if (isset($_GET['success'])): ?>
                    if (typeof showToast === 'function') {
                        showToast(<?php echo json_encode($_GET['success']); ?>, 'success', 4000);
                    }
                    <?php endif; ?>
                    // Clean URL to remove query parameters
                    if (window.history.replaceState) {
                        const url = new URL(window.location);
                        url.searchParams.delete('success');
                        url.searchParams.delete('error');
                        window.history.replaceState({}, document.title, url.toString());
                    }
                });
            </script>
            <?php endif; ?>

            <?php require_once 'details_reviews.php'; ?>
        </section>
    </div>

    <!-- Watchlist Modal -->
    <?php if (isset($_SESSION['user_id']) && !empty($watchlists_array)): ?>
        <?php require_once 'details_watchlist_modal.php'; ?>
    <?php endif; ?>

    <?php require("../includes/footer.php"); ?>
    <?php require_once 'details_scripts.php'; ?>
</body>

</html>