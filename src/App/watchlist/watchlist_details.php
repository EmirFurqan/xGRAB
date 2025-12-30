<?php
session_start();
require_once '../../Core/connect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: http://10.1.7.100:7777/auth.gr2025-022.com.php?mode=login");
    exit;
}

$user_id = $_SESSION['user_id'];
if (!isset($_GET['id'])) die("No ID specified.");
$watchlist_id = intval($_GET['id']);

// 1. HANDLE REMOVE MOVIE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_movie_id'])) {
    $remove_id = intval($_POST['remove_movie_id']);
    // Verify ownership before deleting
    $verify_sql = "SELECT w.watchlist_id FROM watchlists w WHERE w.watchlist_id = $watchlist_id AND w.user_id = $user_id";
    $verify = myQuery($verify_sql);
    
    if ($verify->num_rows > 0) {
        myQuery("DELETE FROM watchlist_movies WHERE watchlist_id = $watchlist_id AND movie_id = $remove_id");
    }
}

// 2. GET LIST INFO (AND VERIFY OWNER)
$list_sql = "SELECT * FROM watchlists WHERE watchlist_id = $watchlist_id AND user_id = $user_id";
$list_result = myQuery($list_sql);
if ($list_result->num_rows == 0) die("Access Denied.");
$list = $list_result->fetch_assoc();

// 3. GET MOVIES (with watched status from user_watched_movies)
$movies_sql = "SELECT m.movie_id, m.title, m.poster_image, m.release_year, m.average_rating,
                      CASE WHEN uwm.movie_id IS NOT NULL THEN 1 ELSE 0 END as is_watched
               FROM watchlist_movies wm
               JOIN movies m ON wm.movie_id = m.movie_id
               LEFT JOIN user_watched_movies uwm ON uwm.movie_id = m.movie_id AND uwm.user_id = $user_id
               WHERE wm.watchlist_id = $watchlist_id
               ORDER BY wm.date_added DESC";
$movies = myQuery($movies_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($list['watchlist_name']); ?></title>
</head>
<body style="font-family: sans-serif; max-width: 900px; margin: 0 auto; padding: 20px; background-color: #fff;">

    <header style="margin-bottom: 20px;">
        <a href="watchlist.php" style="text-decoration: none; font-size: 18px; color: #007bff;">&larr; Back to Watchlists</a>
    </header>

    <h1 style="border-bottom: 2px solid #eee; padding-bottom: 10px;"><?php echo htmlspecialchars($list['watchlist_name']); ?></h1>

    <table width="100%" cellspacing="0" cellpadding="15">
        <thead style="background-color: #f4f4f4;">
            <tr>
                <th>Poster</th>
                <th align="left">Movie Info</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($movies && $movies->num_rows > 0): ?>
                <?php while($row = $movies->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td align="center">
                            <img src="<?php echo $row['poster_image'] ?: 'https://placehold.co/60x90'; ?>" width="60">
                        </td>
                        <td>
                            <strong><a href="movie_details.php?id=<?php echo $row['movie_id']; ?>"><?php echo htmlspecialchars($row['title']); ?></a></strong>
                            <br><span style="color: #777;"><?php echo $row['release_year']; ?></span>
                            <br><span style="color: #e6b800;">&#9733; <?php echo $row['average_rating']; ?></span>
                        </td>
                        <td align="center">
                            <?php echo ($row['is_watched'] == 1) ? "<span style='color:green;'>&#10003; Watched</span>" : "<span style='color:#999;'>To Watch</span>"; ?>
                        </td>
                        <td align="center">
                            <form method="POST">
                                <input type="hidden" name="remove_movie_id" value="<?php echo $row['movie_id']; ?>">
                                <button type="submit" style="color: red; border: 1px solid red; background: white; padding: 5px 10px; cursor: pointer; border-radius: 4px;">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4" align="center">This list is empty.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>