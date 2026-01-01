<?php
/**
 * Search Suggestions API Endpoint
 * Returns JSON with search results for movies, cast, and users.
 * Used for autocomplete in the global search bar.
 */

session_start();
// Include config if not already loaded
if (!defined('BASE_URL') && file_exists(__DIR__ . '/includes/config.php')) {
    require_once __DIR__ . '/includes/config.php';
}
require("connect.php");
require("image_handler.php");

// Set JSON response header
header('Content-Type: application/json');

// Get search query
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Return empty if query too short
if (strlen($query) < 2) {
    echo json_encode(['movies' => [], 'cast' => [], 'users' => []]);
    exit();
}

// Escape for SQL
$search_term = escapeString($query);
$search_pattern = "%$search_term%";

$results = [
    'movies' => [],
    'cast' => [],
    'users' => []
];

// Search movies (limit 5)
$movies_sql = "SELECT movie_id, title, release_year, poster_image, average_rating 
               FROM movies 
               WHERE title LIKE '$search_pattern' 
               ORDER BY average_rating DESC, total_ratings DESC 
               LIMIT 5";
$movies_result = myQuery($movies_sql);

while ($movie = mysqli_fetch_assoc($movies_result)) {
    $results['movies'][] = [
        'id' => $movie['movie_id'],
        'title' => $movie['title'],
        'year' => $movie['release_year'],
        'poster' => $movie['poster_image'] ? getImagePath($movie['poster_image'], 'poster') : null,
        'rating' => number_format($movie['average_rating'], 1),
        'url' => 'movies/details.php?id=' . $movie['movie_id']
    ];
}

// Search cast members (limit 5)
$cast_sql = "SELECT cast_id, name, photo_url 
             FROM cast_members 
             WHERE name LIKE '$search_pattern' 
             ORDER BY name ASC 
             LIMIT 5";
$cast_result = myQuery($cast_sql);

while ($cast = mysqli_fetch_assoc($cast_result)) {
    $results['cast'][] = [
        'id' => $cast['cast_id'],
        'name' => $cast['name'],
        'photo' => $cast['photo_url'] ? getImagePath($cast['photo_url'], 'cast') : null,
        'url' => 'cast/details.php?id=' . $cast['cast_id']
    ];
}

// Search users (limit 5)
$users_sql = "SELECT user_id, username, profile_avatar 
              FROM users 
              WHERE username LIKE '$search_pattern' 
              ORDER BY username ASC 
              LIMIT 5";
$users_result = myQuery($users_sql);

while ($user = mysqli_fetch_assoc($users_result)) {
    $results['users'][] = [
        'id' => $user['user_id'],
        'username' => $user['username'],
        'avatar' => $user['profile_avatar'] ? getImagePath($user['profile_avatar'], 'avatar') : null,
        'url' => 'profile/view.php?user_id=' . $user['user_id']
    ];
}

echo json_encode($results);
?>