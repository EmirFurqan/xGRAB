<?php
/**
 * Add Crew Member Page (Admin)
 * Allows administrators to add new crew members to the database.
 * Handles crew member data entry and photo upload.
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

// Process crew member creation form submission
if (isset($_POST['submit'])) {
    // Extract and sanitize form data
    $name = escapeString($_POST['name']);
    $biography = escapeString($_POST['biography']);

    // Handle crew member photo upload
    $photo_url = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
        // Use image_handler function to upload with validation
        // Note: Uses 'cast' type directory but 'crew_' prefix for filename
        // Max size: 5MB (5242880 bytes)
        $upload_result = uploadImage($_FILES['photo'], 'cast', 'crew_', 5242880);
        if ($upload_result['success']) {
            $photo_url = $upload_result['filename'];
        } else {
            $error = $upload_result['error'];
        }
    }

    // Validate required fields
    if (empty($name)) {
        $error = "Name is required";
    } elseif (empty($error)) {
        // Insert crew member record into database
        // Handle NULL photo_url and biography if not provided
        $photo_value = $photo_url ? "'$photo_url'" : 'NULL';
        $insert_sql = "INSERT INTO crew_members (name, photo_url, biography) 
                       VALUES ('$name', $photo_value, " . ($biography ? "'$biography'" : 'NULL') . ")";

        // Use getConnection() for access to mysqli_insert_id()
        $conn = getConnection();
        if (mysqli_query($conn, $insert_sql)) {
            // Get the ID of the newly inserted crew member
            $crew_id = mysqli_insert_id($conn);
            mysqli_close($conn);

            // Log admin action for audit trail
            $admin_id = $_SESSION['user_id'];
            $log_sql = "INSERT INTO admin_logs (admin_id, action_type, target_type, target_id, description) 
                        VALUES ($admin_id, 'crew_add', 'crew', $crew_id, 'Added new crew member: $name')";
            myQuery($log_sql);

            $success = "Crew member added successfully!";
        } else {
            $error = "Failed to add crew member";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Crew Member - xGrab</title>
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
            const fileInput = document.querySelector('input[name="photo"]');
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
                Add New Crew Member
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
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Name *:</label>
                    <input type="text" name="name" required placeholder="Enter crew member name"
                        class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Photo:</label>
                    <input type="file" name="photo" accept="image/*"
                        class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-red-600 file:text-white hover:file:bg-red-700 transition-all duration-300">
                    <p class="text-xs text-gray-400 mt-1">Upload a photo (JPEG, PNG, GIF, WebP - Max 5MB)</p>
                    <div id="imagePreview" class="mt-3 hidden">
                        <img id="previewImg" src="" alt="Preview" class="max-w-xs rounded-lg border-2 border-gray-600">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Biography:</label>
                    <textarea name="biography" rows="6" placeholder="Enter crew member biography"
                        class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400"></textarea>
                </div>

                <div class="flex space-x-3 pt-2">
                    <button type="submit" name="submit"
                        class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 font-medium">
                        Add Crew Member
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