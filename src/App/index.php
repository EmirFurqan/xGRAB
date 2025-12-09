<?php
require_once '../Core/connect.php';

// 1. GET NEW RELEASES (Top 4 by Release Year)
$sql_new = "SELECT movie_id, title, poster_image, release_year, average_rating 
            FROM movies 
            ORDER BY release_year DESC, created_at DESC LIMIT 4";
$result_new = myQuery($sql_new);

// 2. GET FAN FAVORITES (Top 4 by Rating)
$sql_top = "SELECT movie_id, title, poster_image, release_year, average_rating 
            FROM movies 
            WHERE total_ratings > 0
            ORDER BY average_rating DESC LIMIT 4";
$result_top = myQuery($sql_top);

// 3. GET GENRES LIST (For Sidebar)
$sql_genres = "SELECT genre_id, genre_name FROM genres ORDER BY genre_name ASC";
$result_genres = myQuery($sql_genres);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MovieDB - Home</title>
</head>
<body style="font-family: sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; background-color: #fcfcfc;">

    <header style="background-color: #222; color: #fff; padding: 15px 20px; border-radius: 5px;">
        <div style="overflow: auto;">
            <div style="float: left; font-size: 24px; font-weight: bold;">
                <a href="index.php" style="color: #fff; text-decoration: none;">MovieDB</a>
            </div>
            <div style="float: right; margin-top: 5px;">
                <a href="movies/index.php" style="color: #ccc; text-decoration: none; margin-left: 20px;">All Movies</a>
                <a href="watchlist/index.php" style="color: #ccc; text-decoration: none; margin-left: 20px;">Watchlist</a>
                <a href="#" style="color: #fff; text-decoration: none; margin-left: 20px; font-weight: bold;">Sign In</a>
            </div>
        </div>
    </header>

    <div style="background-color: #eee; padding: 40px; text-align: center; margin-top: 20px; border-radius: 5px;">
        <h1 style="margin: 0;">Welcome to MovieDB</h1>
        <p style="color: #555;">Track, discover, and share the movies you love.</p>
        <form action="movies/index.php" method="GET" style="margin-top: 15px;">
            <input type="text" name="search" placeholder="Search for a movie..." style="padding: 10px; width: 300px;">
            <button type="submit" style="padding: 10px 20px; background-color: #e6b800; border: none; cursor: pointer; font-weight: bold;">Search</button>
        </form>
    </div>

    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-top: 30px;">
        <tr valign="top">
            
            <td>
                
                <div style="margin-bottom: 40px;">
                    <h2 style="border-left: 5px solid #e6b800; padding-left: 10px;">New Releases</h2>
                    <div style="overflow: auto;">
                        <?php if ($result_new && $result_new->num_rows > 0): ?>
                            <?php while($row = $result_new->fetch_assoc()): ?>
                                <div style="float: left; width: 23%; margin-right: 2%; text-align: center;">
                                    <a href="movies/movie_details.php?id=<?php echo $row['movie_id']; ?>">
                                        <img src="<?php echo  '/uploads/' . $row['poster_image'] ; ?>" 
                                            style="width: 100%; height: auto; border-radius: 4px; border: 1px solid #ddd;" 
                                            alt="Poster">
                                    </a>
                                    <h4 style="margin: 10px 0 5px 0; font-size: 16px;">
                                        <a href="movies/movie_details.php?id=<?php echo $row['movie_id']; ?>" style="text-decoration: none; color: #333;">
                                            <?php echo htmlspecialchars($row['title']); ?>
                                        </a>
                                    </h4>
                                    <span style="color: #777; font-size: 14px;"><?php echo $row['release_year']; ?></span>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>No movies found.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <hr style="border: 0; border-top: 1px solid #ddd; margin-bottom: 30px;">

                <div>
                    <h2 style="border-left: 5px solid #d00; padding-left: 10px;">Fan Favorites</h2>
                    <div style="overflow: auto;">
                        <?php if ($result_top && $result_top->num_rows > 0): ?>
                            <?php while($row = $result_top->fetch_assoc()): ?>
                                <div style="float: left; width: 23%; margin-right: 2%; text-align: center;">
                                    <a href="movies/movie_details.php?id=<?php echo $row['movie_id']; ?>">
                                        <img src="<?php echo $row['poster_image'] ? $row['poster_image'] : 'https://placehold.co/150x225?text=Poster'; ?>" 
                                             style="width: 100%; height: auto; border-radius: 4px; border: 1px solid #ddd;" alt="Poster">
                                    </a>
                                    <h4 style="margin: 10px 0 5px 0; font-size: 16px;">
                                        <a href="movies/movie_details.php?id=<?php echo $row['movie_id']; ?>" style="text-decoration: none; color: #333;">
                                            <?php echo htmlspecialchars($row['title']); ?>
                                        </a>
                                    </h4>
                                    <span style="color: #e6b800; font-weight: bold;">&#9733; <?php echo $row['average_rating']; ?></span>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>No ratings yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </td>

            <td width="250" style="padding-left: 30px;">
                <div style="background-color: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
                    <h3 style="margin-top: 0;">Browse by Genre</h3>
                    <ul style="list-style-type: none; padding: 0;">
                        <?php if ($result_genres && $result_genres->num_rows > 0): ?>
                            <?php while($genre = $result_genres->fetch_assoc()): ?>
                                <li style="margin-bottom: 8px;">
                                    <a href="movies.php?genre_id=<?php echo $genre['genre_id']; ?>" style="text-decoration: none; color: #007bff;">
                                        <?php echo htmlspecialchars($genre['genre_name']); ?>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </ul>
                </div>

                <div style="margin-top: 20px; background-color: #f0f8ff; padding: 20px; border: 1px solid #b8daff; border-radius: 4px;">
                    <h4 style="margin-top: 0; color: #004085;">Join the Community</h4>
                    <p style="font-size: 14px; color: #004085;">Create an account to track what you've watched.</p>
                    <button style="width: 100%; padding: 8px; background: #007bff; color: white; border: none; cursor: pointer;">Sign Up Free</button>
                </div>
            </td>

        </tr>
    </table>

    <footer style="margin-top: 50px; padding: 20px; border-top: 2px solid #222; text-align: center; color: #666;">
        &copy; 2024 MovieDB. All rights reserved.
    </footer>

</body>
</html>