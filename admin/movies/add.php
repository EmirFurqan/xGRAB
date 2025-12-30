<?php
/**
 * Add Movie Page (Admin)
 * Allows administrators to add new movies to the database.
 * Handles movie data entry, poster image upload, and genre assignment.
 */

session_start();
require("../../connect.php");
require("../../image_handler.php");

// Verify user has admin privileges
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    die("Access Denied");
}

$error = "";
$success = "";

// Retrieve all available genres for multi-select dropdown
$genres_sql = "SELECT * FROM genres ORDER BY genre_name";
$genres_result = myQuery($genres_sql);

// Process movie creation form submission
if (isset($_POST['submit'])) {
    // Extract and sanitize form data
    $title = escapeString($_POST['title']);
    $release_year = (int) $_POST['release_year'];
    $description = escapeString($_POST['description']);
    $runtime = !empty($_POST['runtime']) ? (int) $_POST['runtime'] : NULL;
    $budget = !empty($_POST['budget']) ? (int) $_POST['budget'] : 0;
    $revenue = !empty($_POST['revenue']) ? (int) $_POST['revenue'] : 0;
    $original_language = escapeString($_POST['original_language']);
    $selected_genres = isset($_POST['genres']) ? $_POST['genres'] : [];

    // Handle poster image file upload
    $poster_image = '';
    if (isset($_FILES['poster_image'])) {
        // Check if file was uploaded successfully
        if ($_FILES['poster_image']['error'] == UPLOAD_ERR_OK) {
            // Use image_handler function to upload with validation
            // Max size: 5MB (5242880 bytes), prefix: 'poster_'
            $upload_result = uploadImage($_FILES['poster_image'], 'poster', 'poster_', 5242880);
            if ($upload_result['success']) {
                $poster_image = $upload_result['filename'];
            } else {
                $error = "Image upload failed: " . $upload_result['error'];
            }
        } elseif ($_FILES['poster_image']['error'] != UPLOAD_ERR_NO_FILE) {
            // Handle specific upload error codes
            // UPLOAD_ERR_NO_FILE is allowed (poster is optional)
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
            ];
            $error_code = $_FILES['poster_image']['error'];
            $error = "Image upload error: " . (isset($upload_errors[$error_code]) ? $upload_errors[$error_code] : 'Unknown error (code: ' . $error_code . ')');
        }
    }

    // Validate required fields
    if (empty($title) || empty($release_year)) {
        $error = "Title and release year are required";
    } elseif (empty($error)) {
        // Insert movie record into database
        // Handle NULL poster_image if no file was uploaded
        $poster_value = $poster_image ? "'$poster_image'" : 'NULL';
        $insert_sql = "INSERT INTO movies (title, release_year, description, poster_image, runtime, budget, revenue, original_language) 
                       VALUES ('$title', $release_year, '$description', $poster_value, " . ($runtime ? $runtime : 'NULL') . ", $budget, $revenue, '$original_language')";

        // Use getConnection() for access to mysqli_insert_id()
        $conn = getConnection();
        if (mysqli_query($conn, $insert_sql)) {
            // Get the ID of the newly inserted movie
            $movie_id = mysqli_insert_id($conn);
            mysqli_close($conn);

            // Associate selected genres with the movie
            // Creates records in movie_genres junction table
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
                        VALUES ($admin_id, 'movie_add', 'movie', $movie_id, 'Added new movie: $title')";
            myQuery($log_sql);

            $success = "Movie added successfully! <a href='../../movies/details.php?id=$movie_id' class='underline'>View Movie</a>";
        } else {
            $error = "Failed to add movie";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Movie - xGrab</title>
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
    <script>
        // Image preview functionality
        document.addEventListener('DOMContentLoaded', function () {
            const fileInput = document.querySelector('input[name="poster_image"]');
            const previewDiv = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');

            if (fileInput) {
                fileInput.addEventListener('change', function (e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            previewImg.src = e.target.result;
                            previewDiv.classList.remove('hidden');
                        };
                        reader.readAsDataURL(file);
                    } else {
                        previewDiv.classList.add('hidden');
                    }
                });
            }
        });
    </script>
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
                Add New Movie
            </h1>

            <?php if ($error): ?>
                <div class="bg-red-800 border border-red-600 text-red-200 px-4 py-3 rounded-lg mb-4 fade-in">
                    <strong class="font-bold">Error!</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-800 border border-green-600 text-green-200 px-4 py-3 rounded-lg mb-4 fade-in">
                    <strong class="font-bold">Success!</strong> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Title *:</label>
                        <input type="text" name="title" required placeholder="Enter movie title"
                            class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Release Year *:</label>
                        <input type="number" name="release_year" required min="1900" max="2100"
                            placeholder="Enter release year"
                            class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Description:</label>
                    <textarea name="description" rows="4" placeholder="Enter movie description"
                        class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Poster Image:</label>
                    <input type="file" name="poster_image" accept="image/*"
                        class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-red-600 file:text-white hover:file:bg-red-700 transition-all duration-300">
                    <p class="text-xs text-gray-400 mt-1">Upload a poster image (JPEG, PNG, GIF, WebP - Max 5MB)</p>
                    <div id="imagePreview" class="mt-3 hidden">
                        <img id="previewImg" src="" alt="Preview" class="max-w-xs rounded-lg border-2 border-gray-600">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Runtime (minutes):</label>
                        <input type="number" name="runtime" min="1" placeholder="Enter runtime"
                            class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Budget:</label>
                        <input type="number" name="budget" min="0" placeholder="Enter budget"
                            class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Revenue:</label>
                        <input type="number" name="revenue" min="0" placeholder="Enter revenue"
                            class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Original Language:</label>
                    <input type="text" name="original_language" maxlength="10" placeholder="e.g., en, es, fr"
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
                            <option value="<?php echo $genre['genre_id']; ?>" class="bg-gray-700 text-gray-100">
                                <?php echo htmlspecialchars($genre['genre_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Hold Ctrl/Cmd to select multiple</p>
                </div>

                <div class="flex space-x-3 pt-2">
                    <button type="submit" name="submit"
                        class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 font-medium">
                        Add Movie
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