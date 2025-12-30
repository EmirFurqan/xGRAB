<?php
/**
 * Movie Browse Page
 * Provides advanced filtering, searching, and sorting for movies.
 * Supports pagination, genre filtering, year range, rating filters, and search.
 */

session_start();
require("../connect.php");
require("../image_handler.php");

// Extract and sanitize filter parameters from URL query string
// These parameters control what movies are displayed
$search = isset($_GET['search']) ? escapeString($_GET['search']) : '';
$genre_ids = isset($_GET['genres']) ? $_GET['genres'] : [];
$year_from = isset($_GET['year_from']) ? (int) $_GET['year_from'] : 0;
$year_to = isset($_GET['year_to']) ? (int) $_GET['year_to'] : 0;
$min_rating = isset($_GET['min_rating']) ? (float) $_GET['min_rating'] : 0;
$sort_by = isset($_GET['sort_by']) ? escapeString($_GET['sort_by']) : 'rating';
$sort_order = isset($_GET['sort_order']) ? escapeString($_GET['sort_order']) : 'DESC';
$category = isset($_GET['category']) ? escapeString($_GET['category']) : 'all';

// Calculate pagination parameters
// Default to page 1 if not specified, display 24 movies per page
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$per_page = 24;
$offset = ($page - 1) * $per_page;

// Build dynamic WHERE clause conditions based on filters
// Conditions are stored in array and joined with AND
$where_conditions = [];
$join_clauses = [];

// Search functionality: search by movie title or cast member name
// Uses LIKE with wildcards for partial matching
// Requires LEFT JOIN to cast_members table for actor search
if (!empty($search)) {
    $where_conditions[] = "(m.title LIKE '%$search%' OR cm.name LIKE '%$search%')";
    $join_clauses[] = "LEFT JOIN movie_cast mc_search ON m.movie_id = mc_search.movie_id";
    $join_clauses[] = "LEFT JOIN cast_members cm ON mc_search.cast_id = cm.cast_id";
}

// Filter by selected genres
// Uses subquery to find movies that have any of the selected genre IDs
// Converts genre IDs to integers for safety
if (!empty($genre_ids) && is_array($genre_ids)) {
    $genre_ids_escaped = array_map('intval', $genre_ids);
    $genre_ids_str = implode(',', $genre_ids_escaped);
    $where_conditions[] = "m.movie_id IN (
        SELECT movie_id FROM movie_genres WHERE genre_id IN ($genre_ids_str)
    )";
}

// Filter by release year range
// Only applies filter if year values are greater than 0
if ($year_from > 0) {
    $where_conditions[] = "m.release_year >= $year_from";
}
if ($year_to > 0) {
    $where_conditions[] = "m.release_year <= $year_to";
}

// Filter by minimum average rating
// Only applies if minimum rating is greater than 0
if ($min_rating > 0) {
    $where_conditions[] = "m.average_rating >= $min_rating";
}

// Category-based filtering and sorting
// These preset categories override sort_by parameter
if ($category == 'popular') {
    // Popular movies: must have at least one rating
    $where_conditions[] = "m.total_ratings > 0";
    $sort_by = 'total_ratings';
} elseif ($category == 'top_rated') {
    // Top rated: must have a rating above 0
    $where_conditions[] = "m.average_rating > 0";
    $sort_by = 'average_rating';
} elseif ($category == 'upcoming') {
    // Upcoming: release year is in the future
    $current_year = date('Y');
    $where_conditions[] = "m.release_year > $current_year";
    $sort_by = 'release_year';
}

// Build ORDER BY clause based on sort_by parameter
// Maps sort options to actual database columns
$order_by = "m.$sort_by";
if ($sort_by == 'title') {
    $order_by = "m.title";
} elseif ($sort_by == 'rating') {
    $order_by = "m.average_rating";
} elseif ($sort_by == 'year') {
    $order_by = "m.release_year";
} elseif ($sort_by == 'popularity') {
    $order_by = "m.total_ratings";
}

// Construct final SQL query with all filters and sorting
// Uses subquery to get comma-separated genre names for each movie
$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
$join_clause = !empty($join_clauses) ? implode(" ", array_unique($join_clauses)) : "";

$sql = "SELECT DISTINCT m.*, 
        (SELECT GROUP_CONCAT(g.genre_name SEPARATOR ', ') 
         FROM movie_genres mg 
         JOIN genres g ON mg.genre_id = g.genre_id 
         WHERE mg.movie_id = m.movie_id) as genres
        FROM movies m
        $join_clause
        $where_clause
        ORDER BY $order_by $sort_order
        LIMIT $per_page OFFSET $offset";

$result = myQuery($sql);

// Calculate total movie count for pagination
// Uses same WHERE and JOIN clauses as main query for accurate count
$count_sql = "SELECT COUNT(DISTINCT m.movie_id) as total
              FROM movies m
              $join_clause
              $where_clause";
$count_result = myQuery($count_sql);
$total_movies = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_movies / $per_page);

// Get all genres for filter
$genres_sql = "SELECT * FROM genres ORDER BY genre_name";
$genres_result = myQuery($genres_sql);

// Get favorite status for all movies (if user is logged in)
$favorite_movies = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    mysqli_data_seek($result, 0);
    $movie_ids = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $movie_ids[] = $row['movie_id'];
    }
    mysqli_data_seek($result, 0);

    if (!empty($movie_ids)) {
        $movie_ids_str = implode(',', array_map('intval', $movie_ids));
        $favorites_sql = "SELECT entity_id FROM favorites 
                         WHERE user_id = $user_id 
                         AND entity_type = 'movie' 
                         AND entity_id IN ($movie_ids_str)";
        $favorites_result = myQuery($favorites_sql);
        while ($fav = mysqli_fetch_assoc($favorites_result)) {
            $favorite_movies[] = $fav['entity_id'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Movies - xGrab</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
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

        .genre-checkbox:checked+.genre-label {
            background: linear-gradient(to right, #6366f1, #8b5cf6) !important;
            color: white !important;
            border-color: #6366f1 !important;
        }
    </style>
    <script>
        // Update genre label styling when checkbox changes
        document.addEventListener('DOMContentLoaded', function () {
            const checkboxes = document.querySelectorAll('.genre-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function () {
                    const label = this.nextElementSibling;
                    if (this.checked) {
                        label.classList.add('bg-gradient-to-r', 'from-red-600', 'to-red-800', 'text-white', 'border-red-500');
                        label.classList.remove('bg-gray-800', 'text-gray-300', 'border-gray-600');
                    } else {
                        label.classList.remove('bg-gradient-to-r', 'from-red-600', 'to-red-800', 'text-white', 'border-red-500');
                        label.classList.add('bg-gray-800', 'text-gray-300', 'border-gray-600');
                    }
                });
            });
        });
    </script>
</head>

<body class="bg-gray-900 min-h-screen text-gray-100">
    <?php require("../includes/nav.php"); ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Header with Search -->
        <div class="mb-8 fade-in">
            <div class="text-center mb-6">
                <h1
                    class="text-4xl md:text-5xl font-bold mb-3 bg-gradient-to-r from-red-400 via-red-500 to-yellow-500 bg-clip-text text-transparent">
                    Browse Movies
                </h1>
                <p class="text-gray-400 text-lg">Discover your next favorite film from our collection</p>
            </div>

            <!-- Search Bar - Always Visible -->
            <form method="get" id="searchForm" class="max-w-3xl mx-auto">
                <div class="relative group">
                    <div
                        class="absolute inset-0 bg-gradient-to-r from-red-600 to-red-800 rounded-2xl blur-lg opacity-30 group-hover:opacity-50 transition-opacity duration-300">
                    </div>
                    <div
                        class="relative flex items-center bg-gray-800 rounded-2xl border-2 border-gray-700 group-hover:border-red-500/50 transition-all duration-300 shadow-xl">
                        <div class="pl-5 pr-3">
                            <svg class="w-6 h-6 text-gray-400 group-hover:text-red-400 transition-colors duration-300"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Search movies by title or actor..."
                            class="flex-1 py-4 px-2 bg-transparent text-gray-100 placeholder-gray-500 text-lg focus:outline-none">
                        <!-- Preserve other filter params when searching -->
                        <?php foreach ($genre_ids as $gid): ?>
                            <input type="hidden" name="genres[]" value="<?php echo $gid; ?>">
                        <?php endforeach; ?>
                        <?php if ($year_from > 0): ?><input type="hidden" name="year_from"
                                value="<?php echo $year_from; ?>"><?php endif; ?>
                        <?php if ($year_to > 0): ?><input type="hidden" name="year_to"
                                value="<?php echo $year_to; ?>"><?php endif; ?>
                        <?php if ($min_rating > 0): ?><input type="hidden" name="min_rating"
                                value="<?php echo $min_rating; ?>"><?php endif; ?>
                        <?php if ($category != 'all'): ?><input type="hidden" name="category"
                                value="<?php echo $category; ?>"><?php endif; ?>
                        <input type="hidden" name="sort_by" value="<?php echo $sort_by; ?>">
                        <input type="hidden" name="sort_order" value="<?php echo $sort_order; ?>">
                        <button type="submit"
                            class="m-2 px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-xl hover:from-red-500 hover:to-red-600 transition-all duration-300 font-medium flex items-center gap-2 shadow-lg">
                            <span class="hidden sm:inline">Search</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Quick Category Buttons & Filters Toggle -->
        <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6 fade-in">
            <!-- Category Pills -->
            <div class="flex flex-wrap justify-center gap-2">
                <a href="browse.php"
                    class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-300 <?php echo $category == 'all' && empty($search) && empty($genre_ids) ? 'bg-gradient-to-r from-red-600 to-red-700 text-white shadow-lg' : 'bg-gray-800 text-gray-300 hover:bg-gray-700 border border-gray-700'; ?>">
                    All Movies
                </a>
                <a href="browse.php?category=popular"
                    class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-300 <?php echo $category == 'popular' ? 'bg-gradient-to-r from-red-600 to-red-700 text-white shadow-lg' : 'bg-gray-800 text-gray-300 hover:bg-gray-700 border border-gray-700'; ?>">
                    🔥 Popular
                </a>
                <a href="browse.php?category=top_rated"
                    class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-300 <?php echo $category == 'top_rated' ? 'bg-gradient-to-r from-red-600 to-red-700 text-white shadow-lg' : 'bg-gray-800 text-gray-300 hover:bg-gray-700 border border-gray-700'; ?>">
                    ⭐ Top Rated
                </a>
                <a href="browse.php?category=upcoming"
                    class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-300 <?php echo $category == 'upcoming' ? 'bg-gradient-to-r from-red-600 to-red-700 text-white shadow-lg' : 'bg-gray-800 text-gray-300 hover:bg-gray-700 border border-gray-700'; ?>">
                    📅 Upcoming
                </a>
            </div>

            <!-- Filters Toggle & Clear -->
            <div class="flex items-center gap-3">
                <?php
                $has_active_filters = !empty($genre_ids) || $year_from > 0 || $year_to > 0 || $min_rating > 0;
                ?>
                <?php if ($has_active_filters || !empty($search)): ?>
                    <a href="browse.php"
                        class="px-4 py-2 text-gray-400 hover:text-red-400 text-sm font-medium transition-colors duration-300 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Clear All
                    </a>
                <?php endif; ?>
                <button type="button" id="toggleFilters"
                    class="px-5 py-2.5 bg-gray-800 text-gray-100 rounded-xl hover:bg-gray-700 transition-all duration-300 border border-gray-700 font-medium flex items-center gap-2 shadow-md hover:shadow-lg <?php echo $has_active_filters ? 'ring-2 ring-red-500/50' : ''; ?>">
                    <svg id="filterIcon" class="w-5 h-5 transition-transform duration-300" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                    </svg>
                    <span id="filterText">Filters</span>
                    <?php if ($has_active_filters): ?>
                        <span class="w-5 h-5 bg-red-600 text-white text-xs rounded-full flex items-center justify-center">
                            <?php echo count($genre_ids) + ($year_from > 0 ? 1 : 0) + ($year_to > 0 ? 1 : 0) + ($min_rating > 0 ? 1 : 0); ?>
                        </span>
                    <?php endif; ?>
                </button>
            </div>
        </div>

        <!-- Advanced Filters (Collapsible) -->
        <div id="filtersSection"
            class="bg-gradient-to-br from-gray-800 to-gray-850 rounded-2xl shadow-xl mb-8 border border-gray-700 overflow-hidden fade-in"
            style="display: none;">
            <form method="get" class="p-6">
                <!-- Keep search value -->
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">

                <!-- Genres -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-200 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        Select Genres
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-2">
                        <?php
                        mysqli_data_seek($genres_result, 0);
                        while ($genre = mysqli_fetch_assoc($genres_result)):
                            $is_selected = in_array($genre['genre_id'], $genre_ids);
                            ?>
                            <label class="cursor-pointer">
                                <input type="checkbox" name="genres[]" value="<?php echo $genre['genre_id']; ?>"
                                    class="genre-checkbox hidden" <?php echo $is_selected ? 'checked' : ''; ?>>
                                <span
                                    class="genre-label block px-3 py-2 rounded-lg border-2 text-sm font-medium text-center transition-all duration-300 hover:scale-105 <?php echo $is_selected ? 'bg-gradient-to-r from-red-600 to-red-700 text-white border-red-500 shadow-lg' : 'bg-gray-700/50 text-gray-300 border-gray-600 hover:border-red-500/50'; ?>">
                                    <?php echo htmlspecialchars($genre['genre_name']); ?>
                                </span>
                            </label>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Filter Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                    <!-- Year From -->
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5 uppercase tracking-wider">Year
                            From</label>
                        <input type="number" name="year_from" value="<?php echo $year_from ?: ''; ?>" min="1900"
                            max="2100" placeholder="1900"
                            class="w-full px-4 py-2.5 bg-gray-700/50 border border-gray-600 rounded-xl focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all duration-300 text-gray-100 placeholder-gray-500">
                    </div>

                    <!-- Year To -->
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5 uppercase tracking-wider">Year
                            To</label>
                        <input type="number" name="year_to" value="<?php echo $year_to ?: ''; ?>" min="1900" max="2100"
                            placeholder="<?php echo date('Y'); ?>"
                            class="w-full px-4 py-2.5 bg-gray-700/50 border border-gray-600 rounded-xl focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all duration-300 text-gray-100 placeholder-gray-500">
                    </div>

                    <!-- Min Rating -->
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5 uppercase tracking-wider">Min
                            Rating</label>
                        <input type="number" name="min_rating" value="<?php echo $min_rating ?: ''; ?>" min="0" max="10"
                            step="0.5" placeholder="0.0"
                            class="w-full px-4 py-2.5 bg-gray-700/50 border border-gray-600 rounded-xl focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all duration-300 text-gray-100 placeholder-gray-500">
                    </div>

                    <!-- Sort By -->
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5 uppercase tracking-wider">Sort
                            By</label>
                        <select name="sort_by"
                            class="w-full px-4 py-2.5 bg-gray-700/50 border border-gray-600 rounded-xl focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all duration-300 text-gray-100">
                            <option value="rating" <?php echo $sort_by == 'rating' ? 'selected' : ''; ?>>Rating</option>
                            <option value="popularity" <?php echo $sort_by == 'popularity' ? 'selected' : ''; ?>>
                                Popularity</option>
                            <option value="year" <?php echo $sort_by == 'year' ? 'selected' : ''; ?>>Release Year</option>
                            <option value="title" <?php echo $sort_by == 'title' ? 'selected' : ''; ?>>Title</option>
                        </select>
                    </div>

                    <!-- Sort Order -->
                    <div>
                        <label
                            class="block text-xs font-medium text-gray-400 mb-1.5 uppercase tracking-wider">Order</label>
                        <select name="sort_order"
                            class="w-full px-4 py-2.5 bg-gray-700/50 border border-gray-600 rounded-xl focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all duration-300 text-gray-100">
                            <option value="DESC" <?php echo $sort_order == 'DESC' ? 'selected' : ''; ?>>Descending
                            </option>
                            <option value="ASC" <?php echo $sort_order == 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                        </select>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-700">
                    <button type="submit"
                        class="px-6 py-2.5 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-xl hover:from-red-500 hover:to-red-600 transition-all duration-300 font-medium shadow-lg hover:shadow-xl flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Apply Filters
                    </button>
                    <a href="browse.php"
                        class="px-6 py-2.5 bg-gray-700 text-gray-300 rounded-xl hover:bg-gray-600 hover:text-white transition-all duration-300 font-medium inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Reset All
                    </a>
                </div>
            </form>
        </div>

        <script>
            // Toggle filters section
            document.addEventListener('DOMContentLoaded', function () {
                const toggleBtn = document.getElementById('toggleFilters');
                const filtersSection = document.getElementById('filtersSection');
                const filterIcon = document.getElementById('filterIcon');

                // Check if filters should be open (if filters are applied)
                const hasFilters = <?php echo ($has_active_filters) ? 'true' : 'false'; ?>;

                // Set initial state - show if filters are active
                if (hasFilters) {
                    filtersSection.style.display = 'block';
                }

                toggleBtn.addEventListener('click', function () {
                    if (filtersSection.style.display === 'none') {
                        filtersSection.style.display = 'block';
                        filterIcon.style.transform = 'rotate(180deg)';
                        filtersSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    } else {
                        filtersSection.style.display = 'none';
                        filterIcon.style.transform = 'rotate(0deg)';
                    }
                });

                // Genre checkbox styling
                const checkboxes = document.querySelectorAll('.genre-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', function () {
                        const label = this.nextElementSibling;
                        if (this.checked) {
                            label.classList.add('bg-gradient-to-r', 'from-red-600', 'to-red-700', 'text-white', 'border-red-500', 'shadow-lg');
                            label.classList.remove('bg-gray-700/50', 'text-gray-300', 'border-gray-600');
                        } else {
                            label.classList.remove('bg-gradient-to-r', 'from-red-600', 'to-red-700', 'text-white', 'border-red-500', 'shadow-lg');
                            label.classList.add('bg-gray-700/50', 'text-gray-300', 'border-gray-600');
                        }
                    });
                });
            });
        </script>

        <!-- Results Count -->
        <div class="mb-6 fade-in flex items-center justify-between">
            <p class="text-lg font-semibold text-gray-300">
                Found <span class="text-red-400 font-bold"><?php echo $total_movies; ?></span>
                movie<?php echo $total_movies != 1 ? 's' : ''; ?>
                <?php if (!empty($search)): ?>
                    <span class="text-gray-500">for "<span
                            class="text-gray-300"><?php echo htmlspecialchars($search); ?></span>"</span>
                <?php endif; ?>
            </p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6 mb-8">
            <?php
            $delay = 0;
            while ($movie = mysqli_fetch_assoc($result)):
                $delay += 50;
                ?>
                <div class="group bg-gray-800 rounded-xl shadow-md overflow-hidden hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-700 fade-in"
                    style="animation-delay: <?php echo $delay; ?>ms">
                    <div class="relative">
                        <a href="details.php?id=<?php echo $movie['movie_id']; ?>" class="block">
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
                                <div
                                    class="absolute top-2 left-2 bg-yellow-400 text-gray-900 px-2 py-1 rounded-md text-xs font-bold flex items-center space-x-1 shadow-lg">
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
                                <input type="hidden" name="redirect_url"
                                    value="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?' . http_build_query($_GET)); ?>">
                                <button type="submit"
                                    class="relative group/icon p-2 rounded-full transition-all duration-300 <?php echo $is_favorited ? 'bg-red-600/90 text-white hover:bg-red-600' : 'bg-gray-800/80 text-gray-400 hover:bg-red-600/80 hover:text-white'; ?>"
                                    title="<?php echo $is_favorited ? 'Remove from Favorites' : 'Add to Favorites'; ?>">
                                    <?php if ($is_favorited): ?>
                                        <!-- Filled heart -->
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    <?php else: ?>
                                        <!-- Outline heart -->
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                        </svg>
                                    <?php endif; ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="p-4">
                        <h3
                            class="font-bold text-sm text-gray-100 truncate group-hover:text-red-400 transition-colors duration-300">
                            <a href="details.php?id=<?php echo $movie['movie_id']; ?>">
                                <?php echo htmlspecialchars($movie['title']); ?>
                            </a>
                        </h3>
                        <p class="text-xs text-gray-400 mt-1"><?php echo $movie['release_year']; ?></p>
                        <?php if ($movie['genres']): ?>
                            <p class="text-xs text-gray-500 mt-2 truncate"><?php echo htmlspecialchars($movie['genres']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <?php if ($total_movies == 0): ?>
            <div class="text-center py-16 fade-in">
                <svg class="w-24 h-24 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-xl text-gray-400">No movies found matching your criteria.</p>
            </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="flex justify-center items-center space-x-2 mt-8 fade-in">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"
                        class="px-4 py-2 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        ← Previous
                    </a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
                        class="px-4 py-2 rounded-lg transition-all duration-300 <?php echo $i == $page ? 'bg-gradient-to-r from-red-600 to-red-800 text-white shadow-lg' : 'bg-gray-800 text-gray-300 hover:bg-gray-700 border border-gray-700 shadow-md hover:shadow-lg'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"
                        class="px-4 py-2 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        Next →
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php require("../includes/footer.php"); ?>
</body>

</html>