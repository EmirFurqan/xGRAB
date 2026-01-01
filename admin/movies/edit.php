<?php
/**
 * Edit Movie Page (Admin)
 * Allows administrators to update existing movie information.
 * Handles movie data updates and genre reassignment.
 */

session_start();
// Include config if not already loaded
if (!defined('BASE_URL') && file_exists(__DIR__ . '/../../includes/config.php')) {
    require_once __DIR__ . '/../../includes/config.php';
}
require("../../connect.php");

// Verify user has admin privileges
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    die("Access Denied");
}

// Validate movie ID parameter
if (!isset($_GET['id'])) {
    header("Location: ../dashboard.php");
    exit();
}

$movie_id = (int) $_GET['id'];
$error = "";
$success = "";

// Retrieve current movie information for form pre-population
$movie_sql = "SELECT * FROM movies WHERE movie_id = $movie_id";
$movie_result = myQuery($movie_sql);
if (mysqli_num_rows($movie_result) == 0) {
    header("Location: ../dashboard.php");
    exit();
}
$movie = mysqli_fetch_assoc($movie_result);

// Retrieve current genre associations for this movie
// Used to pre-select genres in the edit form
$current_genres_sql = "SELECT genre_id FROM movie_genres WHERE movie_id = $movie_id";
$current_genres_result = myQuery($current_genres_sql);
$current_genre_ids = [];
while ($row = mysqli_fetch_assoc($current_genres_result)) {
    $current_genre_ids[] = $row['genre_id'];
}

// Retrieve all available genres for selection
$genres_sql = "SELECT * FROM genres ORDER BY genre_name";
$genres_result = myQuery($genres_sql);

// Retrieve current cast members for this movie
$current_cast_sql = "SELECT cm.*, mc.character_name, mc.cast_order 
                     FROM movie_cast mc 
                     JOIN cast_members cm ON mc.cast_id = cm.cast_id 
                     WHERE mc.movie_id = $movie_id 
                     ORDER BY mc.cast_order ASC";
$current_cast_result = myQuery($current_cast_sql);

// Retrieve current crew members for this movie
$current_crew_sql = "SELECT crm.*, mc.role 
                     FROM movie_crew mc 
                     JOIN crew_members crm ON mc.crew_id = crm.crew_id 
                     WHERE mc.movie_id = $movie_id 
                     ORDER BY mc.role, crm.name";
$current_crew_result = myQuery($current_crew_sql);

// Retrieve all available cast members for selection
$all_cast_sql = "SELECT * FROM cast_members ORDER BY name ASC";
$all_cast_result = myQuery($all_cast_sql);

// Retrieve all available crew members for selection
$all_crew_sql = "SELECT * FROM crew_members ORDER BY name ASC";
$all_crew_result = myQuery($all_crew_sql);

// Check for success/error messages from cast/crew management
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// Process movie update form submission
if (isset($_POST['submit'])) {
    // Extract and sanitize form data
    $title = escapeString($_POST['title']);
    $release_year = (int) $_POST['release_year'];
    $description = escapeString($_POST['description']);
    $poster_image = escapeString($_POST['poster_image']);
    $runtime = !empty($_POST['runtime']) ? (int) $_POST['runtime'] : NULL;
    $budget = !empty($_POST['budget']) ? (int) $_POST['budget'] : 0;
    $revenue = !empty($_POST['revenue']) ? (int) $_POST['revenue'] : 0;
    $original_language = escapeString($_POST['original_language']);
    $selected_genres = isset($_POST['genres']) ? $_POST['genres'] : [];

    // Validate required fields
    if (empty($title) || empty($release_year)) {
        $error = "Title and release year are required";
    } else {
        // Update movie record with new information
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
            // Update genre associations
            // First, delete all existing genre associations
            // This allows complete genre replacement
            $delete_genres_sql = "DELETE FROM movie_genres WHERE movie_id = $movie_id";
            myQuery($delete_genres_sql);

            // Then, insert new genre associations
            if (!empty($selected_genres)) {
                foreach ($selected_genres as $genre_id) {
                    $genre_id = (int) $genre_id;
                    $genre_sql = "INSERT INTO movie_genres (movie_id, genre_id) VALUES ($movie_id, $genre_id)";
                    myQuery($genre_sql);
                }
            }

            // Log admin action for audit trail
            $admin_id = $_SESSION['user_id'];
            $log_sql = "INSERT INTO admin_logs (admin_id, action_type, target_type, target_id, description) 
                        VALUES ($admin_id, 'movie_update', 'movie', $movie_id, 'Updated movie: $title')";
            myQuery($log_sql);

            $success = "Movie updated successfully!";
            // Reload movie data to reflect changes
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

                <!-- Cast Management Section -->
                <div class="border-t border-gray-700 pt-6">
                    <h2 class="text-xl font-bold mb-4 text-gray-100">Cast Members</h2>
                    
                    <!-- Current Cast Members -->
                    <?php if (mysqli_num_rows($current_cast_result) > 0): ?>
                        <div class="mb-4 space-y-2">
                            <?php
                            mysqli_data_seek($current_cast_result, 0);
                            while ($cast = mysqli_fetch_assoc($current_cast_result)):
                                ?>
                                <div class="flex items-center justify-between bg-gray-700 p-3 rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <span class="text-gray-300 font-medium"><?php echo htmlspecialchars($cast['name']); ?></span>
                                        <?php if ($cast['character_name']): ?>
                                            <span class="text-gray-400 text-sm">as <?php echo htmlspecialchars($cast['character_name']); ?></span>
                                        <?php endif; ?>
                                        <span class="text-gray-500 text-xs">(Order: <?php echo $cast['cast_order']; ?>)</span>
                                    </div>
                                    <form method="post" action="manage_cast.php?movie_id=<?php echo $movie_id; ?>" class="inline">
                                        <input type="hidden" name="cast_id" value="<?php echo $cast['cast_id']; ?>">
                                        <button type="submit" name="remove_cast"
                                            class="text-red-400 hover:text-red-300 text-sm transition-colors duration-300"
                                            onclick="return confirm('Remove <?php echo htmlspecialchars($cast['name']); ?> from this movie?');">
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-400 text-sm mb-4">No cast members added yet.</p>
                    <?php endif; ?>

                    <!-- Add Cast Member Form -->
                    <div class="bg-gray-700 p-4 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-300 mb-3">Add Cast Member</h3>
                        <form method="post" action="manage_cast.php?movie_id=<?php echo $movie_id; ?>" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-xs text-gray-400 mb-1">Cast Member</label>
                                <select name="cast_id" required
                                    class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-gray-100 text-sm">
                                    <option value="">Select cast member...</option>
                                    <?php
                                    mysqli_data_seek($all_cast_result, 0);
                                    while ($cast = mysqli_fetch_assoc($all_cast_result)):
                                        // Check if already in movie
                                        $check_in_movie = "SELECT * FROM movie_cast WHERE movie_id = $movie_id AND cast_id = " . $cast['cast_id'];
                                        $check_result = myQuery($check_in_movie);
                                        if (mysqli_num_rows($check_result) == 0):
                                            ?>
                                            <option value="<?php echo $cast['cast_id']; ?>">
                                                <?php echo htmlspecialchars($cast['name']); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-400 mb-1">Character Name</label>
                                <input type="text" name="character_name" placeholder="Character name"
                                    class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-gray-100 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-400 mb-1">Cast Order</label>
                                <input type="number" name="cast_order" value="0" min="0"
                                    class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-gray-100 text-sm">
                            </div>
                            <div class="flex items-end">
                                <button type="submit" name="add_cast"
                                    class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all duration-300 text-sm font-medium">
                                    Add
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Crew Management Section -->
                <div class="border-t border-gray-700 pt-6">
                    <h2 class="text-xl font-bold mb-4 text-gray-100">Crew Members</h2>
                    
                    <!-- Current Crew Members -->
                    <?php if (mysqli_num_rows($current_crew_result) > 0): ?>
                        <div class="mb-4 space-y-2">
                            <?php
                            mysqli_data_seek($current_crew_result, 0);
                            while ($crew = mysqli_fetch_assoc($current_crew_result)):
                                ?>
                                <div class="flex items-center justify-between bg-gray-700 p-3 rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <span class="text-gray-300 font-medium"><?php echo htmlspecialchars($crew['name']); ?></span>
                                        <span class="text-gray-400 text-sm">- <?php echo htmlspecialchars($crew['role']); ?></span>
                                    </div>
                                    <form method="post" action="manage_crew.php?movie_id=<?php echo $movie_id; ?>" class="inline">
                                        <input type="hidden" name="crew_id" value="<?php echo $crew['crew_id']; ?>">
                                        <input type="hidden" name="role" value="<?php echo htmlspecialchars($crew['role']); ?>">
                                        <button type="submit" name="remove_crew"
                                            class="text-red-400 hover:text-red-300 text-sm transition-colors duration-300"
                                            onclick="return confirm('Remove <?php echo htmlspecialchars($crew['name']); ?> (<?php echo htmlspecialchars($crew['role']); ?>) from this movie?');">
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-400 text-sm mb-4">No crew members added yet.</p>
                    <?php endif; ?>

                    <!-- Add Crew Member Form -->
                    <div class="bg-gray-700 p-4 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-300 mb-3">Add Crew Member</h3>
                        <form method="post" action="manage_crew.php?movie_id=<?php echo $movie_id; ?>" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs text-gray-400 mb-1">Crew Member</label>
                                <select name="crew_id" required
                                    class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-gray-100 text-sm">
                                    <option value="">Select crew member...</option>
                                    <?php
                                    mysqli_data_seek($all_crew_result, 0);
                                    while ($crew = mysqli_fetch_assoc($all_crew_result)):
                                        ?>
                                        <option value="<?php echo $crew['crew_id']; ?>">
                                            <?php echo htmlspecialchars($crew['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-400 mb-1">Role</label>
                                <input type="text" name="role" required placeholder="e.g., Director, Writer"
                                    class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-gray-100 text-sm">
                            </div>
                            <div class="flex items-end">
                                <button type="submit" name="add_crew"
                                    class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all duration-300 text-sm font-medium">
                                    Add
                                </button>
                            </div>
                        </form>
                    </div>
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