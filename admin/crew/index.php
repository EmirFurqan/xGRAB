<?php
/**
 * Crew Member Management Page (Admin)
 * Lists all crew members with edit and view options.
 */

session_start();
// Include config if not already loaded
if (!defined('BASE_URL') && file_exists(__DIR__ . '/../../includes/config.php')) {
    require_once __DIR__ . '/../../includes/config.php';
}
require("../../connect.php");
require("../../image_handler.php");

// Verify user has admin privileges
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    die("Access Denied");
}

// Handle delete action
if (isset($_GET['delete'])) {
    $crew_id = (int) $_GET['delete'];
    
    // Check if crew member exists
    $check_sql = "SELECT name FROM crew_members WHERE crew_id = $crew_id";
    $check_result = myQuery($check_sql);
    if (mysqli_num_rows($check_result) > 0) {
        $crew_data = mysqli_fetch_assoc($check_result);
        $crew_name = $crew_data['name'];
        
        // Delete crew member (CASCADE will handle movie_crew relationships)
        $delete_sql = "DELETE FROM crew_members WHERE crew_id = $crew_id";
        if (myQuery($delete_sql)) {
            // Log admin action
            $admin_id = $_SESSION['user_id'];
            $log_sql = "INSERT INTO admin_logs (admin_id, action_type, target_type, target_id, description) 
                        VALUES ($admin_id, 'crew_delete', 'crew', $crew_id, 'Deleted crew member: $crew_name')";
            myQuery($log_sql);
            
            $success = "Crew member deleted successfully";
        } else {
            $error = "Failed to delete crew member";
        }
    }
}

// Handle search and pagination
// Get search term from query string and sanitize it
$search = isset($_GET['search']) ? escapeString($_GET['search']) : '';
// Get current page number, default to page 1
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
// Number of items to display per page
$per_page = 12;
// Calculate offset for SQL LIMIT clause
$offset = ($page - 1) * $per_page;

// Build WHERE clause for search
$where_clause = '';
if (!empty($search)) {
    $where_clause = "WHERE c.name LIKE '%$search%' OR c.biography LIKE '%$search%'";
}

// Retrieve crew members with movie count, search, and pagination
$crew_sql = "SELECT c.*, 
             (SELECT COUNT(*) FROM movie_crew WHERE crew_id = c.crew_id) as movie_count
             FROM crew_members c 
             $where_clause
             ORDER BY c.name ASC
             LIMIT $per_page OFFSET $offset";
$crew_result = myQuery($crew_sql);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM crew_members c $where_clause";
$count_result = myQuery($count_sql);
$total_crew = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_crew / $per_page);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Crew Members - xGrab</title>
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

        <div class="flex justify-between items-center mb-6 fade-in">
            <h1
                class="text-4xl font-bold bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                Crew Members
            </h1>
            <a href="add.php"
                class="px-4 py-2 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-md hover:shadow-lg font-medium">
                Add New Crew Member
            </a>
        </div>

        <?php if (isset($error)): ?>
            <div class="bg-red-800 border border-red-600 text-red-200 px-4 py-3 rounded-lg mb-4 fade-in">
                <strong class="font-bold">Error!</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="bg-green-800 border border-green-600 text-green-200 px-4 py-3 rounded-lg mb-4 fade-in">
                <strong class="font-bold">Success!</strong> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Search Bar -->
        <div class="mb-6 fade-in">
            <form method="get" action="" class="flex gap-3">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="Search crew members by name or biography..."
                    class="flex-1 px-4 py-3 bg-gray-800 border-2 border-gray-700 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-500 transition-all duration-300 text-gray-100 placeholder-gray-400">
                <button type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-md hover:shadow-lg font-medium">
                    Search
                </button>
                <?php if (!empty($search)): ?>
                    <a href="index.php"
                        class="px-6 py-3 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-all duration-300 font-medium">
                        Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Results Count -->
        <?php if (!empty($search)): ?>
            <div class="mb-4 text-gray-400 fade-in">
                Found <?php echo $total_crew; ?> crew member(s) matching "<?php echo htmlspecialchars($search); ?>"
            </div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($crew_result) > 0): ?>
            <!-- Card Grid Layout -->
            <!-- Responsive grid: 1 column on mobile, 2 on tablet, 3 on desktop, 4 on large screens -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8 fade-in">
                <?php while ($crew = mysqli_fetch_assoc($crew_result)): ?>
                    <!-- Individual Crew Member Card -->
                    <div class="bg-gray-800 rounded-xl shadow-lg border border-gray-700 overflow-hidden hover:shadow-xl hover:border-red-500 transition-all duration-300 transform hover:-translate-y-1">
                        <!-- Photo Section -->
                        <div class="relative h-64 bg-gray-700">
                            <?php if ($crew['photo_url']): ?>
                                <img src="<?php echo htmlspecialchars(getImagePath($crew['photo_url'], 'cast')); ?>"
                                    alt="<?php echo htmlspecialchars($crew['name']); ?>"
                                    class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-24 h-24 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                            <?php endif; ?>
                            <!-- Movie Count Badge -->
                            <!-- Display number of movies this crew member worked on -->
                            <div class="absolute top-3 right-3 bg-red-600 text-white px-3 py-1 rounded-full text-xs font-bold shadow-lg">
                                <?php echo $crew['movie_count']; ?> Movie<?php echo $crew['movie_count'] != 1 ? 's' : ''; ?>
                            </div>
                        </div>

                        <!-- Card Content Section -->
                        <div class="p-5">
                            <h3 class="text-lg font-bold text-gray-100 mb-2">
                                <?php echo htmlspecialchars($crew['name']); ?>
                            </h3>
                            
                            <?php if (!empty($crew['biography'])): ?>
                                <!-- Biography Preview -->
                                <!-- Display first 120 characters of biography with ellipsis if longer -->
                                <p class="text-sm text-gray-400 mb-4 line-clamp-3">
                                    <?php echo htmlspecialchars(substr($crew['biography'], 0, 120)); ?>
                                    <?php echo strlen($crew['biography']) > 120 ? '...' : ''; ?>
                                </p>
                            <?php else: ?>
                                <p class="text-sm text-gray-500 italic mb-4">No biography available</p>
                            <?php endif; ?>

                            <!-- Action Buttons -->
                            <!-- Edit and Delete actions for this crew member -->
                            <div class="flex gap-2 pt-3 border-t border-gray-700">
                                <a href="edit.php?id=<?php echo $crew['crew_id']; ?>"
                                    class="flex-1 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-300 text-center text-sm font-medium">
                                    Edit
                                </a>
                                <a href="?delete=<?php echo $crew['crew_id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                                    class="flex-1 px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all duration-300 text-center text-sm font-medium"
                                    onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars(addslashes($crew['name'])); ?>? This will also remove them from all movies.');">
                                    Delete
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination Controls -->
            <!-- Display pagination only if there is more than one page -->
            <?php if ($total_pages > 1): ?>
                <div class="flex justify-center items-center gap-2 mb-8 fade-in">
                    <!-- Previous Page Button -->
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                            class="px-4 py-2 bg-gray-800 border border-gray-700 text-gray-300 rounded-lg hover:bg-gray-700 transition-all duration-300">
                            Previous
                        </a>
                    <?php endif; ?>

                    <!-- Page Number Buttons -->
                    <!-- Show 2 pages before and after current page, with bounds checking -->
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                            class="px-4 py-2 <?php echo $i == $page ? 'bg-red-600 text-white' : 'bg-gray-800 border border-gray-700 text-gray-300 hover:bg-gray-700'; ?> rounded-lg transition-all duration-300">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <!-- Next Page Button -->
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                            class="px-4 py-2 bg-gray-800 border border-gray-700 text-gray-300 rounded-lg hover:bg-gray-700 transition-all duration-300">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="bg-gray-800 rounded-xl shadow-md p-12 text-center border border-gray-700 fade-in">
                <svg class="w-24 h-24 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <p class="text-gray-400 text-lg mb-2">
                    <?php echo !empty($search) ? 'No crew members found matching your search.' : 'No crew members found.'; ?>
                </p>
                <?php if (!empty($search)): ?>
                    <a href="index.php"
                        class="text-red-400 hover:text-red-300 transition-colors duration-300 font-medium">
                        Clear search and view all
                    </a>
                <?php else: ?>
                    <a href="add.php"
                        class="inline-block mt-4 px-6 py-3 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-md hover:shadow-lg font-medium">
                        Add First Crew Member
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>

