<?php
/**
 * Movie Listing Page (src/App directory)
 * Alternative movie browsing page with search and genre filtering.
 * This is an alternative implementation to the root movies/browse.php.
 */

require_once '../../Core/connect.php';

// Initialize variables for dynamic filtering
$where_clauses = [];
$params = [];
$types = "";
$filter_title = "All Movies"; // Default page title

// Check for search parameter in URL
// Allows users to search movies by title
if (isset($_GET['search']) && !empty($_GET['search'])) {
    // Sanitize search input to prevent SQL injection
    // Note: This uses $conn which may not be defined if using myQuery()
    $search_term = $conn->real_escape_string($_GET['search']);
    $where_clauses[] = "m.title LIKE '%$search_term%'";
    $filter_title = "Search Results for: \"" . htmlspecialchars($_GET['search']) . "\"";
}

// Check for genre filter parameter
// Allows users to filter movies by specific genre
if (isset($_GET['genre_id']) && !empty($_GET['genre_id'])) {
    $genre_id = intval($_GET['genre_id']);
    // Filter using movie_genres junction table to find movies with this genre
    $where_clauses[] = "mg.genre_id = $genre_id";
    
    // Retrieve genre name for display in page title
    $g_sql = "SELECT genre_name FROM genres WHERE genre_id = $genre_id";
    $g_res = myQuery($g_sql);
    if($g_row = $g_res->fetch_assoc()) {
        $filter_title = $g_row['genre_name'] . " Movies";
    }
}

// Build WHERE clause string from collected conditions
// Joins multiple conditions with AND operator
$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

// Main query to retrieve movies with genre information
// Uses GROUP_CONCAT to combine multiple genres into comma-separated string
// LEFT JOINs ensure movies without genres are still included
// Orders by average rating (highest first), then by creation date
$sql = "SELECT 
            m.movie_id, 
            m.title, 
            m.release_year, 
            m.description, 
            m.average_rating,
            m.total_ratings,
            m.runtime,
            m.poster_image,
            GROUP_CONCAT(DISTINCT g.genre_name SEPARATOR ', ') as genre_list
        FROM movies m
        LEFT JOIN movie_genres mg ON m.movie_id = mg.movie_id
        LEFT JOIN genres g ON mg.genre_id = g.genre_id
        $where_sql
        GROUP BY m.movie_id
        ORDER BY m.average_rating DESC, m.created_at DESC";

$result = myQuery($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $filter_title; ?> - MovieDB</title>
</head>
<body style="font-family: sans-serif; max-width: 900px; margin: 0 auto; padding: 20px; background-color: #fff;">

    <header style="margin-bottom: 30px;">
        <h1 style="margin: 0;">MOVIES DATABASE</h1>
        <div style="margin-top: 10px; padding-bottom: 10px; border-bottom: 2px solid #eee;">
            <a href="../index.php" style="text-decoration: none; color: #555;">Home</a> | 
            <a href="movies.php" style="text-decoration: none; font-weight: bold; color: #000;">Movies</a> | 
            <a href="#" style="text-decoration: none; color: #555;">Watchlist</a>
        </div>
    </header>

    <h2><?php echo $filter_title; ?></h2>

    <div>
        <?php
        if ($result && $result->num_rows > 0) {
            $rank = 1; 
            
            while($row = $result->fetch_assoc()) {
                $hours = floor($row['runtime'] / 60);
                $minutes = $row['runtime'] % 60;
                $runtime_str = "{$hours}h {$minutes}m";
                $desc_preview = strlen($row['description']) > 180 ? substr($row['description'], 0, 180) . "..." : $row['description'];
                
                echo "<article style='margin-bottom: 30px; overflow: auto;'>";
                
                // Poster
                echo "<div style='float: left; margin-right: 20px; width: 100px; text-align: center;'>";
                    echo "<h3 style='margin: 0 0 5px 0; color: #555;'>#$rank</h3>";
                    echo "<a href='movie_details.php?id=" . $row["movie_id"] . "'>";
                    echo "<img src='" . ($row['poster_image'] ? $row['poster_image'] : 'https://placehold.co/100x150?text=No+Poster') . "' 
                             alt='Poster' width='100' height='150' style='border-radius: 4px; border: 1px solid #ccc;'>";
                    echo "</a>";
                echo "</div>";

                // Content
                echo "<div style='margin-left: 130px;'>";
                    echo "<h3 style='margin: 0 0 5px 0;'>";
                        echo "<a href='movie_details.php?id=" . $row["movie_id"] . "' style='text-decoration: none; color: #007bff;'>" . htmlspecialchars($row["title"]) . "</a> ";
                        echo "<span style='font-weight: normal; font-size: 0.9em; color: #666;'>(" . $row["release_year"] . ")</span>";
                    echo "</h3>";

                    echo "<p style='margin: 0 0 10px 0; font-size: 0.9em; color: #555;'>";
                        echo "$runtime_str &bull; " . ($row["genre_list"] ? htmlspecialchars($row["genre_list"]) : "Unknown Genre");
                    echo "</p>";

                    echo "<div style='margin-bottom: 10px;'>";
                        echo "<span style='color: #e6b800; font-size: 1.1em;'>&#9733; " . $row["average_rating"] . "</span>";
                        echo "<span style='font-size: 0.8em; color: #777;'> (" . number_format($row["total_ratings"]) . " votes)</span>";
                    echo "</div>";

                    echo "<p style='margin: 0 0 10px 0; line-height: 1.5; color: #333;'>";
                        echo htmlspecialchars($desc_preview);
                        echo " <a href='movie_details.php?id=" . $row["movie_id"] . "' style='font-size: 0.9em; text-decoration: underline;'>Read more</a>";
                    echo "</p>";

                echo "</div>";
                echo "<hr style='border: 0; border-top: 1px solid #eee; margin-top: 20px; clear: both;'>";
                echo "</article>";

                $rank++;
            }
        } else {
            echo "<p>No movies found matching your criteria.</p>";
            echo "<p><a href='movies.php'>View all movies</a></p>";
        }
        ?>
    </div>

    <footer style="text-align: center; color: #999; font-size: 0.8em; margin-top: 40px;">
        &copy; 2024 Movies Database
    </footer>

</body>
</html>