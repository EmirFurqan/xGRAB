<?php
require_once '../../Core/connect.php';

if (isset($_GET['id'])) {
    $movie_id = intval($_GET['id']);

    // 1. MOVIE INFO
    $movie_sql = "SELECT m.*, GROUP_CONCAT(g.genre_name SEPARATOR ', ') as genre_list 
                  FROM movies m
                  LEFT JOIN movie_genres mg ON m.movie_id = mg.movie_id
                  LEFT JOIN genres g ON mg.genre_id = g.genre_id
                  WHERE m.movie_id = $movie_id
                  GROUP BY m.movie_id";
    $movie_result = myQuery($movie_sql);
    $movie = $movie_result->fetch_assoc();

    if (!$movie) die("Movie not found.");

    // 2. CAST
    $cast_sql = "SELECT c.name, mc.character_name, c.photo_url 
                 FROM cast_members c
                 JOIN movie_cast mc ON c.cast_id = mc.cast_id
                 WHERE mc.movie_id = $movie_id
                 ORDER BY mc.cast_order ASC LIMIT 6";
    $cast_result = myQuery($cast_sql);

    // 3. CREW
    $crew_sql = "SELECT cm.name, mcrew.role 
                 FROM crew_members cm
                 JOIN movie_crew mcrew ON cm.crew_id = mcrew.crew_id
                 WHERE mcrew.movie_id = $movie_id
                 AND mcrew.role IN ('Director', 'Writer', 'Producer')";
    $crew_result = myQuery($crew_sql);

    // 4. REVIEWS
    $review_sql = "SELECT r.rating_value, r.review_text, u.username, r.created_at
                   FROM reviews r
                   JOIN users u ON r.user_id = u.user_id
                   WHERE r.movie_id = $movie_id
                   ORDER BY r.created_at DESC LIMIT 5";
    $review_result = myQuery($review_sql);

    $hours = floor($movie['runtime'] / 60);
    $minutes = $movie['runtime'] % 60;
    $runtime_str = "{$hours}h {$minutes}m";

} else {
    die("No movie ID specified.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($movie['title']); ?> - Details</title>
</head>
<body style="font-family: sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px;">

    <header style="border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px;">
        <nav>
            <a href="../index.php" style="text-decoration: none; font-weight: bold; margin-right: 15px;">Home</a> |
            <a href="index.php" style="text-decoration: none; margin-left: 15px; margin-right: 15px;">All Movies</a> | 
            <a href="#" style="text-decoration: none; color: #555;">Watchlist</a>
        </nav>
    </header>

    <div>
        <h1 style="margin-bottom: 5px;"><?php echo htmlspecialchars($movie['title']); ?></h1>
        <div style="color: #555; font-size: 14px; margin-bottom: 20px;">
            <span><?php echo $movie['release_year']; ?></span> &bull; 
            <span><?php echo $runtime_str; ?></span> &bull; 
            <span><?php echo htmlspecialchars($movie['genre_list']); ?></span>
        </div>
    </div>

    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr valign="top">
            <td width="320">
                <img src="<?php echo $movie['poster_image'] ? $movie['poster_image'] : 'https://placehold.co/300x450?text=No+Poster'; ?>" 
                     alt="Poster" width="300" style="border: 1px solid #ccc; padding: 5px;">
            </td>
            <td>
                <div style="background-color: #f4f4f4; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <span style="font-size: 24px; color: #e6b800;">&#9733; <?php echo $movie['average_rating']; ?>/10</span>
                    <span style="color: #666;">(<?php echo number_format($movie['total_ratings']); ?> votes)</span>
                </div>

                <h3>Overview</h3>
                <p style="line-height: 1.6; font-size: 16px;">
                    <?php echo nl2br(htmlspecialchars($movie['description'])); ?>
                </p>

                <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

                <div style="margin-bottom: 20px;">
                    <strong>Key Crew:</strong><br>
                    <?php 
                    if ($crew_result && $crew_result->num_rows > 0) {
                        while($crew = $crew_result->fetch_assoc()) {
                            echo "<span style='margin-right: 15px;'><strong>" . htmlspecialchars($crew['name']) . "</strong> (" . $crew['role'] . ")</span>";
                        }
                    } else {
                        echo "<span>No crew info.</span>";
                    }
                    ?>
                </div>

                <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

                <h3>Top Cast</h3>
                <table width="100%" cellpadding="5">
                    <?php 
                    if ($cast_result && $cast_result->num_rows > 0) {
                        while($actor = $cast_result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td width='50'><div style='width:50px; height:50px; background:#ddd; border-radius:50%; overflow:hidden;'><img src='https://placehold.co/50x50?text=Actor' width='50'></div></td>";
                            echo "<td><strong>" . htmlspecialchars($actor['name']) . "</strong></td>";
                            echo "<td style='color:#666;'>as " . htmlspecialchars($actor['character_name']) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td>No cast added.</td></tr>";
                    }
                    ?>
                </table>
            </td>
        </tr>
    </table>

    <br><br>

    <div style="background-color: #f9f9f9; padding: 20px; border-top: 2px solid #ccc;">
        <h2>User Reviews</h2>
        <?php if ($review_result && $review_result->num_rows > 0): ?>
            <?php while($review = $review_result->fetch_assoc()): ?>
                <div style="border-bottom: 1px solid #ddd; padding-bottom: 15px; margin-bottom: 15px;">
                    <div style="margin-bottom: 5px;">
                        <span style="font-weight: bold; color: #0056b3;"><?php echo htmlspecialchars($review['username']); ?></span>
                        <span style="color: #e6b800; margin-left: 10px;">&#9733; <?php echo $review['rating_value']; ?></span>
                        <span style="float: right; color: #999; font-size: 12px;"><?php echo $review['created_at']; ?></span>
                    </div>
                    <div style="font-style: italic;">
                        "<?php echo nl2br(htmlspecialchars($review['review_text'])); ?>"
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No reviews yet.</p>
        <?php endif; ?>
    </div>

    <footer style="text-align: center; margin-top: 50px; padding: 20px; color: #777;">
        &copy; 2024 Movie Database
    </footer>

</body>
</html>