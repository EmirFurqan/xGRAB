<?php
/**
 * Watchlist Index Page (src/App directory)
 * Alternative watchlist management page implementation.
 * Displays user watchlists with movie counts and handles watchlist creation.
 * This is an alternative implementation to the root watchlist/index.php.
 */

session_start();
require_once '../../Core/connect.php';

// Verify user is logged in before showing watchlists
if (!isset($_SESSION['user_id'])) {
    // Redirect to authentication endpoint
    header("Location: http://10.1.7.100:7777/auth.gr2025-022.com.php?mode=login");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

// Handle watchlist creation from form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_list_name'])) {
    // Sanitize watchlist name input
    $list_name = addslashes($_POST['new_list_name']);
    if (!empty($list_name)) {
        // Check if user already has a watchlist with this name
        // Prevents duplicate watchlist names for the same user
        $check = myQuery("SELECT watchlist_id FROM watchlists WHERE user_id = $user_id AND watchlist_name = '$list_name'");
        if ($check->num_rows > 0) {
            $message = "You already have a list with that name.";
        } else {
            // Create new watchlist with current timestamp
            $sql_create = "INSERT INTO watchlists (user_id, watchlist_name, date_created) VALUES ($user_id, '$list_name', NOW())";
            myQuery($sql_create);
            $message = "List created successfully!";
        }
    }
}

// Retrieve all watchlists for the current user
// LEFT JOIN with watchlist_movies to count movies in each watchlist
// GROUP BY ensures each watchlist appears once with its movie count
// Ordered by creation date (newest first)
$sql = "SELECT w.watchlist_id, w.watchlist_name, w.date_created, COUNT(wm.movie_id) as item_count 
        FROM watchlists w
        LEFT JOIN watchlist_movies wm ON w.watchlist_id = wm.watchlist_id
        WHERE w.user_id = $user_id
        GROUP BY w.watchlist_id
        ORDER BY w.date_created DESC";

$result = myQuery($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Watchlists</title>
</head>
<body style="font-family: sans-serif; max-width: 900px; margin: 0 auto; padding: 20px; background-color: #f9f9f9;">

    <header style="background-color: #fff; padding: 15px; border-bottom: 2px solid #ddd; margin-bottom: 20px;">
        <div style="overflow: auto;">
            <div style="float: left; font-size: 20px; font-weight: bold;">
                <a href="index.php" style="text-decoration: none; color: #333;">MovieDB</a>
            </div>
            <div style="float: right;">
                <span style="color: #555; margin-right: 15px;">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="index.php" style="text-decoration: none; color: #007bff; margin-right: 15px;">Home</a>
                <a href="http://10.1.7.100:7777/auth.gr2025-022.com.php?mode=login" style="text-decoration: none; color: #d9534f;">Logout</a>
            </div>
        </div>
    </header>

    <h1>My Watchlists</h1>

    <div style="background-color: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 30px;">
        <h3 style="margin-top: 0;">Create a New List</h3>
        <form method="POST" action="">
            <input type="text" name="new_list_name" placeholder="e.g. 'Horror Movies' or 'To Watch'" required 
                   style="padding: 10px; width: 300px; border: 1px solid #ccc; border-radius: 4px;">
            <button type="submit" style="padding: 10px 20px; background-color: #28a745; color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold;">+ Create</button>
        </form>
        <?php if($message) echo "<p style='color: #007bff; font-weight: bold;'>$message</p>"; ?>
    </div>

    <div>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <a href="watchlist_details.php?id=<?php echo $row['watchlist_id']; ?>" style="text-decoration: none; color: inherit;">
                    <div style="background-color: #fff; padding: 20px; border: 1px solid #ddd; margin-bottom: 15px; border-radius: 5px; transition: 0.3s; cursor: pointer;">
                        <h2 style="margin: 0; color: #007bff;"><?php echo htmlspecialchars($row['watchlist_name']); ?></h2>
                        <p style="color: #666; margin: 5px 0;">
                            <?php echo $row['item_count']; ?> movies &bull; Created: <?php echo date("M d, Y", strtotime($row['date_created'])); ?>
                        </p>
                    </div>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <p>You have no watchlists yet.</p>
        <?php endif; ?>
    </div>

</body>
</html>