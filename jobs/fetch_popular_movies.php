<?php
/**
 * TMDB API - Fetch Popular Movies Job
 * 
 * Background job that fetches popular movies from The Movie Database (TMDB) API
 * and imports them into the local database with full details including cast, crew, and genres.
 * 
 * Endpoint: http://localhost/Movie/jobs/fetch_popular_movies.php
 * 
 * Parameters (GET or POST):
 * - api_key: Your TMDB API key (required)
 * - page: Page number to fetch (default: 1, max 500 movies per page)
 * - limit: Number of movies to process (default: 20, max: 100 per request)
 * 
 * Example: 
 * http://localhost/Movie/jobs/fetch_popular_movies.php?api_key=YOUR_API_KEY&page=1&limit=20
 * 
 * This script:
 * - Fetches movie data from TMDB API
 * - Creates movie records with poster images, ratings, and metadata
 * - Maps TMDB genres to local genre database
 * - Fetches and creates cast members with biographies
 * - Fetches and creates crew members (directors, writers, etc.)
 * - Links movies to cast and crew via junction tables
 */

header('Content-Type: application/json');
// Set execution time limit to 12 hours (43200 seconds) for long-running movie fetches
// This allows processing large batches of movies without timeout
set_time_limit(43200);
ini_set('max_execution_time', 43200);
require("../connect.php");

// TMDB API configuration
// Base URL for TMDB API v3 endpoints
$TMDB_API_BASE = "https://api.themoviedb.org/3";
// Base URL for movie poster images (w500 = 500px width)
$TMDB_IMAGE_BASE = "https://image.tmdb.org/t/p/w500";
// Base URL for cast/crew profile photos (w185 = 185px width)
$TMDB_PROFILE_BASE = "https://image.tmdb.org/t/p/w185";

// Extract and validate parameters from GET or POST request
$api_key = isset($_GET['api_key']) ? $_GET['api_key'] : (isset($_POST['api_key']) ? $_POST['api_key'] : '');
$page = isset($_GET['page']) ? (int)$_GET['page'] : (isset($_POST['page']) ? (int)$_POST['page'] : 1);
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : (isset($_POST['limit']) ? (int)$_POST['limit'] : 20);

// Validate API key
if (empty($api_key)) {
    echo json_encode([
        'success' => false,
        'error' => 'TMDB API key is required. Add ?api_key=YOUR_API_KEY to the URL'
    ]);
    exit;
}

// Limit validation
if ($limit > 5000) $limit = 5000; // Max 500 movies
if ($limit < 1) $limit = 20;

// Calculate pages needed (TMDB returns 20 movies per page)
$movies_per_page = 100;
$total_pages_needed = ceil($limit / $movies_per_page);

$response = [
    'success' => true,
    'start_page' => $page,
    'total_pages' => $total_pages_needed,
    'limit' => $limit,
    'movies_fetched' => 0,
    'movies_added' => 0,
    'movies_skipped' => 0,
    'cast_added' => 0,
    'crew_added' => 0,
    'pages_processed' => [],
    'errors' => []
];

try {
    $movies_processed = 0;
    $current_page = $page;
    
    // Loop through pages
    for ($p = 0; $p < $total_pages_needed && $movies_processed < $limit; $p++) {
        $page_num = $current_page + $p;
        
        // Calculate how many movies to process from this page
        $remaining = $limit - $movies_processed;
        $movies_to_process = min($movies_per_page, $remaining);
        
        // Fetch popular movies from TMDB for this page
        $url = "$TMDB_API_BASE/movie/popular?api_key=$api_key&page=$page_num&language=en-US";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $api_response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            throw new Exception("TMDB API returned HTTP $http_code on page $page_num. Response: " . substr($api_response, 0, 200));
        }
        
        $data = json_decode($api_response, true);
        
        if (!isset($data['results']) || !is_array($data['results'])) {
            throw new Exception("Invalid response from TMDB API on page $page_num");
        }
        
        $movies = array_slice($data['results'], 0, $movies_to_process);
        $response['movies_fetched'] += count($movies);
        
        // Track counts before processing this page
        $cast_count_before = $response['cast_added'];
        $crew_count_before = $response['crew_added'];
        $movies_added_before = $response['movies_added'];
        $movies_skipped_before = $response['movies_skipped'];
        
        $page_stats = [
            'page' => $page_num,
            'movies_fetched' => count($movies),
            'movies_added' => 0,
            'movies_skipped' => 0,
            'cast_added' => 0,
            'crew_added' => 0
        ];
        
        // Process each movie
        foreach ($movies as $tmdb_movie) {
        try {
            // Extract basic info
            $tmdb_id = $tmdb_movie['id'];
            $title = escapeString($tmdb_movie['title']);
            $release_date = $tmdb_movie['release_date'] ?? '';
            $release_year = !empty($release_date) ? (int)substr($release_date, 0, 4) : 0;
            $description = isset($tmdb_movie['overview']) ? escapeString(substr($tmdb_movie['overview'], 0, 2000)) : '';
            $poster_path = $tmdb_movie['poster_path'] ?? '';
            $poster_image = !empty($poster_path) ? $TMDB_IMAGE_BASE . $poster_path : '';
            $original_language = isset($tmdb_movie['original_language']) ? escapeString($tmdb_movie['original_language']) : 'en';
            $tmdb_rating = isset($tmdb_movie['vote_average']) ? (float)$tmdb_movie['vote_average'] : 0;
            $tmdb_vote_count = isset($tmdb_movie['vote_count']) ? (int)$tmdb_movie['vote_count'] : 0;
            
            // Validate required fields
            if (empty($title) || $release_year < 1900) {
                $response['movies_skipped']++;
                $response['errors'][] = "Skipped: $title (invalid data)";
                continue;
            }
            
            // Check if movie already exists (by title and year)
            $check_sql = "SELECT movie_id FROM movies WHERE title = '$title' AND release_year = $release_year";
            $check_result = myQuery($check_sql);
            
            if (mysqli_num_rows($check_result) > 0) {
                $response['movies_skipped']++;
                continue;
            }
            
            // Fetch detailed movie info for runtime, budget, revenue
            $detail_url = "$TMDB_API_BASE/movie/$tmdb_id?api_key=$api_key&language=en-US";
            $detail_ch = curl_init();
            curl_setopt($detail_ch, CURLOPT_URL, $detail_url);
            curl_setopt($detail_ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($detail_ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($detail_ch, CURLOPT_TIMEOUT, 30);
            $detail_response = curl_exec($detail_ch);
            curl_close($detail_ch);
            
            $movie_detail = json_decode($detail_response, true);
            $runtime = isset($movie_detail['runtime']) ? (int)$movie_detail['runtime'] : NULL;
            $budget = isset($movie_detail['budget']) ? (int)$movie_detail['budget'] : 0;
            $revenue = isset($movie_detail['revenue']) ? (int)$movie_detail['revenue'] : 0;
            
            // Insert movie
            $runtime_sql = $runtime ? $runtime : 'NULL';
            $insert_sql = "INSERT INTO movies (title, release_year, description, poster_image, runtime, budget, revenue, original_language, average_rating, total_ratings) 
                          VALUES ('$title', $release_year, '$description', '$poster_image', $runtime_sql, $budget, $revenue, '$original_language', $tmdb_rating, $tmdb_vote_count)";
            
            $conn = getConnection();
            if (mysqli_query($conn, $insert_sql)) {
                $movie_id = mysqli_insert_id($conn);
                mysqli_close($conn);
                
                // Handle genres
                if (isset($tmdb_movie['genre_ids']) && is_array($tmdb_movie['genre_ids'])) {
                    // Map TMDB genre IDs to our database genres
                    // TMDB genre IDs: https://developers.themoviedb.org/3/genres/get-movie-list
                    $tmdb_genre_map = [
                        28 => 'Action',
                        12 => 'Adventure',
                        16 => 'Animation',
                        35 => 'Comedy',
                        80 => 'Crime',
                        99 => 'Documentary',
                        18 => 'Drama',
                        10751 => 'Family',
                        14 => 'Fantasy',
                        36 => 'History',
                        27 => 'Horror',
                        10402 => 'Music',
                        9648 => 'Mystery',
                        10749 => 'Romance',
                        878 => 'Science Fiction',
                        10770 => 'TV Movie',
                        53 => 'Thriller',
                        10752 => 'War',
                        37 => 'Western'
                    ];
                    
                    foreach ($tmdb_movie['genre_ids'] as $tmdb_genre_id) {
                        if (isset($tmdb_genre_map[$tmdb_genre_id])) {
                            $genre_name = $tmdb_genre_map[$tmdb_genre_id];
                            
                            // Check if genre exists, if not create it
                            $genre_check = "SELECT genre_id FROM genres WHERE genre_name = '$genre_name'";
                            $genre_result = myQuery($genre_check);
                            
                            if (mysqli_num_rows($genre_result) > 0) {
                                $genre_row = mysqli_fetch_assoc($genre_result);
                                $genre_id = $genre_row['genre_id'];
                            } else {
                                // Create new genre
                                $genre_insert = "INSERT INTO genres (genre_name) VALUES ('$genre_name')";
                                $conn = getConnection();
                                mysqli_query($conn, $genre_insert);
                                $genre_id = mysqli_insert_id($conn);
                                mysqli_close($conn);
                            }
                            
                            // Link movie to genre
                            $movie_genre_sql = "INSERT IGNORE INTO movie_genres (movie_id, genre_id) VALUES ($movie_id, $genre_id)";
                            myQuery($movie_genre_sql);
                        }
                    }
                }
                
                // Fetch and add cast and crew
                $credits_url = "$TMDB_API_BASE/movie/$tmdb_id/credits?api_key=$api_key";
                $credits_ch = curl_init();
                curl_setopt($credits_ch, CURLOPT_URL, $credits_url);
                curl_setopt($credits_ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($credits_ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($credits_ch, CURLOPT_TIMEOUT, 30);
                $credits_response = curl_exec($credits_ch);
                curl_close($credits_ch);
                
                $credits_data = json_decode($credits_response, true);
                
                // Process Cast (top 10)
                if (isset($credits_data['cast']) && is_array($credits_data['cast'])) {
                    $cast_members = array_slice($credits_data['cast'], 0, 10); // Top 10 cast
                    
                    foreach ($cast_members as $index => $cast_member) {
                        try {
                            $cast_name = escapeString($cast_member['name']);
                            $character_name = isset($cast_member['character']) ? escapeString($cast_member['character']) : '';
                            $cast_order = $index + 1;
                            $photo_path = isset($cast_member['profile_path']) ? $cast_member['profile_path'] : '';
                            $photo_url = !empty($photo_path) ? $TMDB_PROFILE_BASE . $photo_path : '';
                            $tmdb_person_id = isset($cast_member['id']) ? (int)$cast_member['id'] : 0;
                            
                            if (empty($cast_name)) continue;
                            
                            // Check if cast member exists
                            $cast_check = "SELECT cast_id, photo_url, biography FROM cast_members WHERE name = '$cast_name'";
                            $cast_result = myQuery($cast_check);
                            
                            $biography = '';
                            $needs_biography = false;
                            
                            if (mysqli_num_rows($cast_result) > 0) {
                                $cast_row = mysqli_fetch_assoc($cast_result);
                                $cast_id = $cast_row['cast_id'];
                                
                                // Update photo if missing
                                if (empty($cast_row['photo_url']) && !empty($photo_url)) {
                                    $update_photo = "UPDATE cast_members SET photo_url = '$photo_url' WHERE cast_id = $cast_id";
                                    myQuery($update_photo);
                                }
                                
                                // Check if biography is missing and we have TMDB person ID
                                if (empty($cast_row['biography']) && $tmdb_person_id > 0) {
                                    $needs_biography = true;
                                }
                            } else {
                                // Create new cast member - fetch biography from TMDB if available
                                if ($tmdb_person_id > 0) {
                                    try {
                                        // Fetch person details from TMDB
                                        $person_url = "$TMDB_API_BASE/person/$tmdb_person_id?api_key=$api_key&language=en-US";
                                        $person_ch = curl_init();
                                        curl_setopt($person_ch, CURLOPT_URL, $person_url);
                                        curl_setopt($person_ch, CURLOPT_RETURNTRANSFER, true);
                                        curl_setopt($person_ch, CURLOPT_SSL_VERIFYPEER, false);
                                        curl_setopt($person_ch, CURLOPT_TIMEOUT, 10);
                                        
                                        $person_response = curl_exec($person_ch);
                                        $person_http_code = curl_getinfo($person_ch, CURLINFO_HTTP_CODE);
                                        curl_close($person_ch);
                                        
                                        if ($person_http_code === 200) {
                                            $person_data = json_decode($person_response, true);
                                            if (isset($person_data['biography']) && !empty($person_data['biography'])) {
                                                $biography = escapeString($person_data['biography']);
                                            }
                                        }
                                    } catch (Exception $e) {
                                        // Silently fail - biography is optional
                                    }
                                }
                                
                                // Create new cast member
                                $cast_insert = "INSERT INTO cast_members (name, photo_url, biography) VALUES ('$cast_name', '$photo_url', '$biography')";
                                $conn = getConnection();
                                mysqli_query($conn, $cast_insert);
                                $cast_id = mysqli_insert_id($conn);
                                mysqli_close($conn);
                                $response['cast_added']++;
                            }
                            
                            // Fetch biography if needed (for existing cast members)
                            if ($needs_biography && $tmdb_person_id > 0) {
                                try {
                                    $person_url = "$TMDB_API_BASE/person/$tmdb_person_id?api_key=$api_key&language=en-US";
                                    $person_ch = curl_init();
                                    curl_setopt($person_ch, CURLOPT_URL, $person_url);
                                    curl_setopt($person_ch, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($person_ch, CURLOPT_SSL_VERIFYPEER, false);
                                    curl_setopt($person_ch, CURLOPT_TIMEOUT, 10);
                                    
                                    $person_response = curl_exec($person_ch);
                                    $person_http_code = curl_getinfo($person_ch, CURLINFO_HTTP_CODE);
                                    curl_close($person_ch);
                                    
                                    if ($person_http_code === 200) {
                                        $person_data = json_decode($person_response, true);
                                        if (isset($person_data['biography']) && !empty($person_data['biography'])) {
                                            $biography = escapeString($person_data['biography']);
                                            $update_bio = "UPDATE cast_members SET biography = '$biography' WHERE cast_id = $cast_id";
                                            myQuery($update_bio);
                                        }
                                    }
                                } catch (Exception $e) {
                                    // Silently fail - biography is optional
                                }
                            }
                            
                            // Link cast to movie
                            $movie_cast_sql = "INSERT IGNORE INTO movie_cast (movie_id, cast_id, character_name, cast_order) 
                                              VALUES ($movie_id, $cast_id, '$character_name', $cast_order)";
                            myQuery($movie_cast_sql);
                            
                        } catch (Exception $e) {
                            $response['errors'][] = "Error adding cast member: " . $e->getMessage();
                        }
                    }
                }
                
                // Process Crew (key roles: Director, Writer, Producer, etc.)
                if (isset($credits_data['crew']) && is_array($credits_data['crew'])) {
                    $key_roles = ['Director', 'Writer', 'Screenplay', 'Producer', 'Executive Producer', 'Cinematography', 'Music', 'Editor'];
                    
                    foreach ($credits_data['crew'] as $crew_member) {
                        try {
                            $role = isset($crew_member['job']) ? $crew_member['job'] : '';
                            
                            // Only add key roles
                            if (!in_array($role, $key_roles)) continue;
                            
                            $crew_name = escapeString($crew_member['name']);
                            $photo_path = isset($crew_member['profile_path']) ? $crew_member['profile_path'] : '';
                            $photo_url = !empty($photo_path) ? $TMDB_PROFILE_BASE . $photo_path : '';
                            
                            if (empty($crew_name)) continue;
                            
                            // Check if crew member exists
                            $crew_check = "SELECT crew_id, photo_url FROM crew_members WHERE name = '$crew_name'";
                            $crew_result = myQuery($crew_check);
                            
                            if (mysqli_num_rows($crew_result) > 0) {
                                $crew_row = mysqli_fetch_assoc($crew_result);
                                $crew_id = $crew_row['crew_id'];
                                
                                // Update photo if missing
                                if (empty($crew_row['photo_url']) && !empty($photo_url)) {
                                    $update_photo = "UPDATE crew_members SET photo_url = '$photo_url' WHERE crew_id = $crew_id";
                                    myQuery($update_photo);
                                }
                            } else {
                                // Create new crew member
                                $crew_insert = "INSERT INTO crew_members (name, photo_url) VALUES ('$crew_name', '$photo_url')";
                                $conn = getConnection();
                                mysqli_query($conn, $crew_insert);
                                $crew_id = mysqli_insert_id($conn);
                                mysqli_close($conn);
                                $response['crew_added']++;
                            }
                            
                            // Link crew to movie (handle role mapping)
                            $role_mapped = $role;
                            if ($role == 'Screenplay') $role_mapped = 'Writer';
                            if ($role == 'Executive Producer') $role_mapped = 'Producer';
                            
                            $movie_crew_sql = "INSERT IGNORE INTO movie_crew (movie_id, crew_id, role) 
                                              VALUES ($movie_id, $crew_id, '$role_mapped')";
                            myQuery($movie_crew_sql);
                            
                        } catch (Exception $e) {
                            $response['errors'][] = "Error adding crew member: " . $e->getMessage();
                        }
                    }
                }
                
                $response['movies_added']++;
                $page_stats['movies_added']++;
                $movies_processed++;
            } else {
                $conn->close();
                throw new Exception("Failed to insert movie: $title");
            }
            
        } catch (Exception $e) {
            $response['errors'][] = "Error processing '$title': " . $e->getMessage();
            $response['movies_skipped']++;
            $page_stats['movies_skipped']++;
            $movies_processed++;
        }
        }
        
        // Calculate page stats
        $page_stats['movies_added'] = $response['movies_added'] - $movies_added_before;
        $page_stats['movies_skipped'] = $response['movies_skipped'] - $movies_skipped_before;
        $page_stats['cast_added'] = $response['cast_added'] - $cast_count_before;
        $page_stats['crew_added'] = $response['crew_added'] - $crew_count_before;
        
        // Store page stats
        $response['pages_processed'][] = $page_stats;
        
        // Wait 5 seconds before next page (except for the last page)
        if ($p < $total_pages_needed - 1 && $movies_processed < $limit) {
            sleep(5);
        }
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>

