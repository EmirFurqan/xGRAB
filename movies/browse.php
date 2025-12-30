<?php
session_start();
require("../connect.php");
require("../image_handler.php");

// Get filter parameters
$search = isset($_GET['search']) ? escapeString($_GET['search']) : '';
$genre_ids = isset($_GET['genres']) ? $_GET['genres'] : [];
$year_from = isset($_GET['year_from']) ? (int)$_GET['year_from'] : 0;
$year_to = isset($_GET['year_to']) ? (int)$_GET['year_to'] : 0;
$min_rating = isset($_GET['min_rating']) ? (float)$_GET['min_rating'] : 0;
$sort_by = isset($_GET['sort_by']) ? escapeString($_GET['sort_by']) : 'rating';
$sort_order = isset($_GET['sort_order']) ? escapeString($_GET['sort_order']) : 'DESC';
$category = isset($_GET['category']) ? escapeString($_GET['category']) : 'all';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 24;
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where_conditions = [];
$join_clauses = [];

// Search by title or actor
if (!empty($search)) {
    $where_conditions[] = "(m.title LIKE '%$search%' OR cm.name LIKE '%$search%')";
    $join_clauses[] = "LEFT JOIN movie_cast mc_search ON m.movie_id = mc_search.movie_id";
    $join_clauses[] = "LEFT JOIN cast_members cm ON mc_search.cast_id = cm.cast_id";
}

// Filter by genres
if (!empty($genre_ids) && is_array($genre_ids)) {
    $genre_ids_escaped = array_map('intval', $genre_ids);
    $genre_ids_str = implode(',', $genre_ids_escaped);
    $where_conditions[] = "m.movie_id IN (
        SELECT movie_id FROM movie_genres WHERE genre_id IN ($genre_ids_str)
    )";
}

// Filter by year range
if ($year_from > 0) {
    $where_conditions[] = "m.release_year >= $year_from";
}
if ($year_to > 0) {
    $where_conditions[] = "m.release_year <= $year_to";
}

// Filter by rating
if ($min_rating > 0) {
    $where_conditions[] = "m.average_rating >= $min_rating";
}

// Category filters
if ($category == 'popular') {
    $where_conditions[] = "m.total_ratings > 0";
    $sort_by = 'total_ratings';
} elseif ($category == 'top_rated') {
    $where_conditions[] = "m.average_rating > 0";
    $sort_by = 'average_rating';
} elseif ($category == 'upcoming') {
    $current_year = date('Y');
    $where_conditions[] = "m.release_year > $current_year";
    $sort_by = 'release_year';
}

// Build ORDER BY
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

// Build final query
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

// Get total count for pagination
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
    while($row = mysqli_fetch_assoc($result)) {
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
        while($fav = mysqli_fetch_assoc($favorites_result)) {
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
    <title>Browse Movies - Movie Database</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        .genre-checkbox:checked + .genre-label {
            background: linear-gradient(to right, #6366f1, #8b5cf6) !important;
            color: white !important;
            border-color: #6366f1 !important;
        }
    </style>
    <script>
        // Update genre label styling when checkbox changes
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.genre-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
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
        <div class="mb-6 fade-in">
            <h1 class="text-4xl font-bold mb-2 bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                Browse Movies
            </h1>
            <p class="text-gray-400">Discover and explore our movie collection</p>
        </div>
        
        <!-- Filters Toggle Button -->
        <div class="mb-4 fade-in">
            <button type="button" id="toggleFilters" class="px-6 py-3 bg-gray-800 text-gray-100 rounded-lg hover:bg-gray-700 transition-all duration-300 shadow-lg hover:shadow-xl border border-gray-700 font-medium flex items-center space-x-2">
                <svg id="filterIcon" class="w-5 h-5 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                <span id="filterText">Hide Filters</span>
            </button>
        </div>
        
        <!-- Filters -->
        <div id="filtersSection" class="bg-gray-800 p-6 rounded-xl shadow-lg mb-6 border border-gray-700 fade-in">
            <form method="get" class="space-y-4">
                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Search (Title or Actor):</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search movies or actors..."
                           class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400">
                </div>
                
                <!-- Genres - Checkbox Style -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-3">Genres:</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 p-3 bg-gray-700 rounded-lg border border-gray-600">
                        <?php 
                        mysqli_data_seek($genres_result, 0);
                        while($genre = mysqli_fetch_assoc($genres_result)): 
                            $is_selected = in_array($genre['genre_id'], $genre_ids);
                        ?>
                            <label class="flex items-center space-x-2 cursor-pointer group">
                                <input type="checkbox" 
                                       name="genres[]" 
                                       value="<?php echo $genre['genre_id']; ?>"
                                       class="genre-checkbox hidden"
                                       <?php echo $is_selected ? 'checked' : ''; ?>>
                                <span class="genre-label flex-1 px-3 py-2 rounded-lg border-2 border-gray-600 bg-gray-800 text-gray-300 text-sm font-medium text-center transition-all duration-300 group-hover:border-red-500 group-hover:bg-gray-750 <?php echo $is_selected ? 'bg-gradient-to-r from-red-600 to-red-800 text-white border-red-500' : ''; ?>">
                                    <?php echo htmlspecialchars($genre['genre_name']); ?>
                                </span>
                            </label>
                        <?php endwhile; ?>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Click to select/deselect genres</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                    
                    <!-- Year Range -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Year From:</label>
                        <input type="number" name="year_from" value="<?php echo $year_from ?: ''; ?>" 
                               min="1900" max="2100" 
                               class="w-full px-3 py-2 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Year To:</label>
                        <input type="number" name="year_to" value="<?php echo $year_to ?: ''; ?>" 
                               min="1900" max="2100" 
                               class="w-full px-3 py-2 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100">
                    </div>
                    
                    <!-- Min Rating -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Min Rating:</label>
                        <input type="number" name="min_rating" value="<?php echo $min_rating ?: ''; ?>" 
                               min="0" max="10" step="0.1" 
                               class="w-full px-3 py-2 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100">
                    </div>
                    
                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Category:</label>
                        <select name="category" class="w-full px-3 py-2 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100">
                            <option value="all" <?php echo $category == 'all' ? 'selected' : ''; ?>>All Movies</option>
                            <option value="popular" <?php echo $category == 'popular' ? 'selected' : ''; ?>>Popular</option>
                            <option value="top_rated" <?php echo $category == 'top_rated' ? 'selected' : ''; ?>>Top Rated</option>
                            <option value="upcoming" <?php echo $category == 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                        </select>
                    </div>
                    
                    <!-- Sort By -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Sort By:</label>
                        <select name="sort_by" class="w-full px-3 py-2 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100">
                            <option value="rating" <?php echo $sort_by == 'rating' ? 'selected' : ''; ?>>Rating</option>
                            <option value="popularity" <?php echo $sort_by == 'popularity' ? 'selected' : ''; ?>>Popularity</option>
                            <option value="year" <?php echo $sort_by == 'year' ? 'selected' : ''; ?>>Release Year</option>
                            <option value="title" <?php echo $sort_by == 'title' ? 'selected' : ''; ?>>Title</option>
                        </select>
                    </div>
                    
                    <!-- Sort Order -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Order:</label>
                        <select name="sort_order" class="w-full px-3 py-2 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100">
                            <option value="DESC" <?php echo $sort_order == 'DESC' ? 'selected' : ''; ?>>Descending</option>
                            <option value="ASC" <?php echo $sort_order == 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex space-x-3 pt-4">
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 font-medium">
                        Apply Filters
                    </button>
                    <a href="browse.php" class="px-6 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 inline-block font-medium">
                        Clear Filters
                    </a>
                </div>
            </form>
        </div>
        
        <script>
            // Toggle filters section
            document.addEventListener('DOMContentLoaded', function() {
                const toggleBtn = document.getElementById('toggleFilters');
                const filtersSection = document.getElementById('filtersSection');
                const filterIcon = document.getElementById('filterIcon');
                const filterText = document.getElementById('filterText');
                
                // Check if filters should be collapsed by default (if no filters are applied)
                const hasFilters = <?php echo (!empty($search) || !empty($genre_ids) || $year_from > 0 || $year_to > 0 || $min_rating > 0 || $category != 'all') ? 'true' : 'false'; ?>;
                
                // Set initial state
                if (!hasFilters) {
                    filtersSection.style.display = 'none';
                    filterText.textContent = 'Show Filters';
                    filterIcon.style.transform = 'rotate(180deg)';
                }
                
                toggleBtn.addEventListener('click', function() {
                    if (filtersSection.style.display === 'none') {
                        filtersSection.style.display = 'block';
                        filterText.textContent = 'Hide Filters';
                        filterIcon.style.transform = 'rotate(0deg)';
                        // Smooth scroll to filters
                        filtersSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    } else {
                        filtersSection.style.display = 'none';
                        filterText.textContent = 'Show Filters';
                        filterIcon.style.transform = 'rotate(180deg)';
                    }
                });
            });
        </script>
        
        <!-- Results -->
        <div class="mb-6 fade-in">
            <p class="text-lg font-semibold text-gray-300">Found <span class="text-red-400"><?php echo $total_movies; ?></span> movie(s)</p>
        </div>
        
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6 mb-8">
            <?php 
            $delay = 0;
            while($movie = mysqli_fetch_assoc($result)): 
                $delay += 50;
            ?>
                <div class="group bg-gray-800 rounded-xl shadow-md overflow-hidden hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-700 fade-in" style="animation-delay: <?php echo $delay; ?>ms">
                    <div class="relative">
                        <a href="details.php?id=<?php echo $movie['movie_id']; ?>" class="block">
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
                                <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?' . http_build_query($_GET)); ?>">
                                <button type="submit" 
                                        class="relative group/icon p-2 rounded-full transition-all duration-300 <?php echo $is_favorited ? 'bg-red-600/90 text-white hover:bg-red-600' : 'bg-gray-800/80 text-gray-400 hover:bg-red-600/80 hover:text-white'; ?>" 
                                        title="<?php echo $is_favorited ? 'Remove from Favorites' : 'Add to Favorites'; ?>">
                                    <?php if ($is_favorited): ?>
                                        <!-- Filled heart -->
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                                        </svg>
                                    <?php else: ?>
                                        <!-- Outline heart -->
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                        </svg>
                                    <?php endif; ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="p-4">
                        <h3 class="font-bold text-sm text-gray-100 truncate group-hover:text-red-400 transition-colors duration-300">
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
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
</body>
</html>

