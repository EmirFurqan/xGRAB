<?php
session_start();
require("../../connect.php");

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    die("Access Denied");
}

if (!isset($_GET['id'])) {
    header("Location: ../dashboard.php");
    exit();
}

$movie_id = (int) $_GET['id'];
$error = "";
$success = "";

// Get movie info
$movie_sql = "SELECT * FROM movies WHERE movie_id = $movie_id";
$movie_result = myQuery($movie_sql);
if (mysqli_num_rows($movie_result) == 0) {
    header("Location: ../dashboard.php");
    exit();
}
$movie = mysqli_fetch_assoc($movie_result);

// Get current genres
$current_genres_sql = "SELECT genre_id FROM movie_genres WHERE movie_id = $movie_id";
$current_genres_result = myQuery($current_genres_sql);
$current_genre_ids = [];
while ($row = mysqli_fetch_assoc($current_genres_result)) {
    $current_genre_ids[] = $row['genre_id'];
}

// Get all genres
$genres_sql = "SELECT * FROM genres ORDER BY genre_name";
$genres_result = myQuery($genres_sql);

if (isset($_POST['submit'])) {
    $title = escapeString($_POST['title']);
    $release_year = (int) $_POST['release_year'];
    $description = escapeString($_POST['description']);
    $poster_image = escapeString($_POST['poster_image']);
    $runtime = !empty($_POST['runtime']) ? (int) $_POST['runtime'] : NULL;
    $budget = !empty($_POST['budget']) ? (int) $_POST['budget'] : 0;
    $revenue = !empty($_POST['revenue']) ? (int) $_POST['revenue'] : 0;
    $original_language = escapeString($_POST['original_language']);
    $selected_genres = isset($_POST['genres']) ? $_POST['genres'] : [];

    if (empty($title) || empty($release_year)) {
        $error = "Title and release year are required";
    } else {
        // Update movie
        $update_sql = "UPDATE movies SET 
                       title = '$title',
                       release_year = $release_year,
                       description = '$description',
                       poster_image = '$poster_image',
                       runtime = " . ($runtime ? $runtime : 'NULL') . ",
                       budget = $budget,
                       revenue = $revenue,
                       original_language = '$original_language'
                       WHERE movie_id = $movie_id";

        if (myQuery($update_sql)) {
            // Update genres
            // Delete existing genres
            $delete_genres_sql = "DELETE FROM movie_genres WHERE movie_id = $movie_id";
            myQuery($delete_genres_sql);

            // Add new genres
            if (!empty($selected_genres)) {
                foreach ($selected_genres as $genre_id) {
                    $genre_id = (int) $genre_id;
                    $genre_sql = "INSERT INTO movie_genres (movie_id, genre_id) VALUES ($movie_id, $genre_id)";
                    myQuery($genre_sql);
                }
            }

            // Log admin action
            $admin_id = $_SESSION['user_id'];
            $log_sql = "INSERT INTO admin_logs (admin_id, action_type, target_type, target_id, description) 
                        VALUES ($admin_id, 'movie_update', 'movie', $movie_id, 'Updated movie: $title')";
            myQuery($log_sql);

            $success = "Movie updated successfully!";
            // Reload movie data
            $movie_result = myQuery($movie_sql);
            $movie = mysqli_fetch_assoc($movie_result);
        } else {
            $error = "Failed to update movie";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Movie - xGrab</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
    </style>
</head>

<body class="bg-gray-900 min-h-screen text-gray-100">
    <?php require("../../includes/nav.php"); ?>

    <div class="container mx-auto px-4 py-8">
        <a href="../dashboard.php"
            class="inline-flex items-center text-red-400 hover:text-red-300 mb-6 font-medium transition-colors duration-300 fade-in">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Dashboard
        </a>

        <div class="bg-gray-800 rounded-xl shadow-lg p-6 md:p-8 max-w-4xl border border-gray-700 fade-in">
            <h1 class="text-3xl font-bold mb-6 bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                Edit Movie
            </h1>

            <?php if ($error): ?>
                <div class="bg-red-800 border border-red-600 text-red-200 px-4 py-3 rounded-lg mb-4 fade-in">
                    <strong class="font-bold">Error!</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-800 border border-green-600 text-green-200 px-4 py-3 rounded-lg mb-4 fade-in">
                    <strong class="font-bold">Success!</strong> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Title *:</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($movie['title']); ?>"
                            required
                            class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Release Year *:</label>
                        <input type="number" name="release_year" value="<?php echo $movie['release_year']; ?>" required
                            min="1900" max="2100"
                            class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Description:</label>
                    <textarea name="description" rows="4"
                        class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400"><?php echo htmlspecialchars($movie['description']); ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Poster Image URL:</label>
                    <input type="text" name="poster_image"
                        value="<?php echo htmlspecialchars($movie['poster_image']); ?>"
                        class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Runtime (minutes):</label>
                        <input type="number" name="runtime" value="<?php echo $movie['runtime'] ?: ''; ?>" min="1"
                            class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Budget:</label>
                        <input type="number" name="budget" value="<?php echo $movie['budget']; ?>" min="0"
                            class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Revenue:</label>
                        <input type="number" name="revenue" value="<?php echo $movie['revenue']; ?>" min="0"
                            class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Original Language:</label>
                    <input type="text" name="original_language"
                        value="<?php echo htmlspecialchars($movie['original_language']); ?>" maxlength="10"
                        class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Genres:</label>
                    <select name="genres[]" multiple size="5"
                        class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100">
                        <?php
                        mysqli_data_seek($genres_result, 0);
                        while ($genre = mysqli_fetch_assoc($genres_result)):
                            ?>
                            <option value="<?php echo $genre['genre_id']; ?>" <?php echo in_array($genre['genre_id'], $current_genre_ids) ? 'selected' : ''; ?> class="bg-gray-700 text-gray-100">
                                <?php echo htmlspecialchars($genre['genre_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Hold Ctrl/Cmd to select multiple</p>
                </div>

                <div class="flex space-x-3 pt-2">
                    <button type="submit" name="submit"
                        class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 font-medium">
                        Update Movie
                    </button>
                    <a href="../dashboard.php"
                        class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 inline-block font-medium">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>