<?php
/**
 * Reusable Navigation Component
 * Provides consistent navigation bar across all pages.
 * Automatically adjusts link paths based on current directory depth.
 * Usage: require("includes/nav.php"); or require("../includes/nav.php");
 */

// Include image handler for avatar path generation
// Check if image_handler.php exists in the expected location relative to this file
$image_handler_path = __DIR__ . '/../image_handler.php';
if (file_exists($image_handler_path)) {
    require_once $image_handler_path;
}

// Determine base path for navigation links based on current directory
// This ensures links work correctly regardless of which subdirectory the page is in
$base_path = "";
$script_path = $_SERVER['PHP_SELF'];

// Normalize path separators to forward slashes
// Handles Windows backslashes for cross-platform compatibility
$script_path = str_replace('\\', '/', $script_path);

// Detect directory depth and set appropriate base path
// Two-level deep directories (e.g., admin/users/, admin/movies/) need "../../"
if (
    strpos($script_path, '/admin/users/') !== false ||
    strpos($script_path, '/admin/movies/') !== false ||
    strpos($script_path, '/admin/reviews/') !== false
) {
    // We're in a subdirectory of admin (e.g., admin/users/, admin/movies/)
    $base_path = "../../";
} elseif (
    strpos($script_path, '/admin/') !== false ||
    strpos($script_path, '/movies/') !== false ||
    strpos($script_path, '/watchlist/') !== false ||
    strpos($script_path, '/profile/') !== false ||
    strpos($script_path, '/reviews/') !== false ||
    strpos($script_path, '/cast/') !== false ||
    strpos($script_path, '/favorites/') !== false ||
    strpos($script_path, '/watched/') !== false
) {
    // We're in a first-level subdirectory, need one level up
    $base_path = "../";
}

// Retrieve user information for navigation display
$user_avatar = null;
$user_initial = '';
$username = '';
if (isset($_SESSION['user_id'])) {
    // Get username from session and extract first letter for avatar fallback
    $username = htmlspecialchars($_SESSION['username']);
    $user_initial = strtoupper(substr($username, 0, 1));

    // Retrieve user avatar from database if database connection is available
    // Check if myQuery function exists to avoid errors if connect.php isn't loaded
    if (function_exists('myQuery') && isset($_SESSION['user_id'])) {
        $avatar_sql = "SELECT profile_avatar FROM users WHERE user_id = " . (int) $_SESSION['user_id'];
        $avatar_result = myQuery($avatar_sql);
        if ($avatar_result && mysqli_num_rows($avatar_result) > 0) {
            $avatar_row = mysqli_fetch_assoc($avatar_result);
            $user_avatar = $avatar_row['profile_avatar'];
        }
    }
}

// Generate consistent avatar background color based on username
// Uses CRC32 hash to convert username to number, then modulo to select color
// This ensures same username always gets same color
$avatar_colors = [
    'bg-red-500',
    'bg-red-600',
    'bg-orange-500',
    'bg-amber-500',
    'bg-yellow-500',
    'bg-lime-500',
    'bg-green-500',
    'bg-emerald-500',
    'bg-teal-500',
    'bg-cyan-500',
    'bg-blue-500',
    'bg-indigo-500',
    'bg-purple-500',
    'bg-pink-500',
    'bg-rose-500',
    'bg-violet-500'
];
$color_index = crc32($username) % count($avatar_colors);
$avatar_color = $avatar_colors[$color_index];
?>

<style>
    .nav-link {
        position: relative;
        padding: 0.5rem 1rem;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.85);
        transition: all 0.3s ease;
        border-radius: 0.5rem;
    }

    .nav-link:hover {
        color: #fbbf24;
        background: rgba(255, 255, 255, 0.05);
    }

    .nav-link::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 50%;
        width: 0;
        height: 2px;
        background: linear-gradient(90deg, #f87171, #fbbf24);
        transition: all 0.3s ease;
        transform: translateX(-50%);
        border-radius: 1px;
    }

    .nav-link:hover::after {
        width: 60%;
    }

    .dropdown-menu {
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .dropdown:hover .dropdown-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        color: #e5e7eb;
        transition: all 0.2s ease;
    }

    .dropdown-item:hover {
        background: rgba(239, 68, 68, 0.1);
        color: #f87171;
    }

    .dropdown-item:hover svg {
        transform: scale(1.1);
    }

    .dropdown-item svg {
        transition: transform 0.2s ease;
    }

    .mobile-menu {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
    }

    .mobile-menu.open {
        max-height: 400px;
    }
</style>

<nav
    class="bg-gradient-to-r from-gray-900/95 via-gray-800/95 to-gray-900/95 backdrop-blur-xl text-white shadow-2xl border-b border-white/10 sticky top-0 z-50">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center h-16 lg:h-18">
            <!-- Logo -->
            <a href="<?php echo $base_path; ?>index.php" class="flex items-center space-x-3 group">
                <div class="relative">
                    <div
                        class="absolute inset-0 bg-red-500 blur-xl opacity-40 group-hover:opacity-60 transition-opacity duration-500 rounded-full scale-150">
                    </div>
                    <div class="relative transition-all duration-300 group-hover:scale-110">
                        <img src="<?php echo getImagePath("logo.svg", 'poster'); ?>" alt"logo" class="w-auto h-8" />
                    </div>
                </div>
                <div class="flex flex-col">
                    <span
                        class="text-2xl font-black bg-gradient-to-r from-white via-red-200 to-yellow-400 bg-clip-text text-transparent group-hover:from-yellow-300 group-hover:via-red-300 group-hover:to-white transition-all duration-500 tracking-tight leading-none">xGrab</span>
                    <span
                        class="text-[10px] text-gray-400 font-medium tracking-widest uppercase group-hover:text-gray-300 transition-colors duration-300 hidden sm:block">Your
                        Next Watch</span>
                </div>
            </a>

            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center space-x-1">
                <a href="<?php echo $base_path; ?>movies/browse.php" class="nav-link flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                    </svg>
                    Browse
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo $base_path; ?>watchlist/index.php" class="nav-link flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        Watchlists
                    </a>
                    <a href="<?php echo $base_path; ?>favorites/index.php" class="nav-link flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                        Favorites
                    </a>
                    <a href="<?php echo $base_path; ?>watched/index.php" class="nav-link flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        Watched
                    </a>
                <?php endif; ?>
            </div>

            <!-- Global Search Bar -->
            <div class="hidden md:flex flex-1 max-w-md mx-4 relative" id="searchContainer">
                <form action="<?php echo $base_path; ?>search.php" method="GET" class="w-full" id="searchForm">
                    <div class="relative">
                        <input type="text" name="q" id="globalSearchInput" placeholder="Search movies, cast, users..."
                            autocomplete="off"
                            class="w-full pl-10 pr-4 py-2 bg-gray-800/80 border border-gray-700 rounded-lg text-gray-100 placeholder-gray-400 focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500 transition-all duration-300">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </form>

                <!-- Search Suggestions Dropdown -->
                <div id="searchSuggestions"
                    class="absolute top-full left-0 right-0 mt-1 bg-gray-800 border border-gray-700 rounded-lg shadow-2xl overflow-hidden z-[100] hidden">
                    <div id="searchLoading" class="px-4 py-3 text-gray-400 text-sm hidden">
                        <div class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            Searching...
                        </div>
                    </div>
                    <div id="searchResults"></div>
                    <div id="searchEmpty" class="px-4 py-3 text-gray-400 text-sm hidden">No results found</div>
                </div>
            </div>

            <!-- Mobile Search Toggle -->
            <button type="button" id="mobileSearchToggle"
                class="md:hidden p-2 rounded-lg hover:bg-white/10 transition-colors text-gray-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </button>

            <!-- Right Section -->
            <div class="flex items-center space-x-3">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- User Dropdown -->
                    <div class="dropdown relative">
                        <button
                            class="flex items-center space-x-2 p-1.5 rounded-xl hover:bg-white/5 transition-all duration-300 border border-transparent hover:border-white/10">
                            <div class="relative">
                                <?php if ($user_avatar): ?>
                                    <img src="<?php echo htmlspecialchars(function_exists('getImagePath') ? getImagePath($user_avatar, 'avatar') : $user_avatar); ?>"
                                        alt="<?php echo $username; ?>"
                                        class="w-9 h-9 rounded-lg border-2 border-gray-600 object-cover">
                                <?php else: ?>
                                    <?php
                                    $display_initial = strtoupper(mb_substr(trim($username), 0, 1, 'UTF-8'));
                                    if (empty($display_initial) || strlen($display_initial) > 1) {
                                        $display_initial = strtoupper(substr(trim($username), 0, 1)) ?: '?';
                                    }
                                    ?>
                                    <div
                                        class="w-9 h-9 rounded-lg <?php echo $avatar_color; ?> flex items-center justify-center font-bold text-white text-sm shadow-lg">
                                        <?php echo htmlspecialchars($display_initial); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                                    <span
                                        class="absolute -top-1 -right-1 w-4 h-4 bg-gradient-to-br from-yellow-400 to-amber-500 rounded-full border-2 border-gray-800 flex items-center justify-center">
                                        <svg class="w-2.5 h-2.5 text-gray-900" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span
                                class="hidden lg:block text-sm font-medium text-gray-200 max-w-[100px] truncate"><?php echo $username; ?></span>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <div
                            class="dropdown-menu absolute right-0 mt-2 w-64 bg-gray-800/95 backdrop-blur-xl rounded-xl shadow-2xl border border-white/10 overflow-hidden z-[100]">
                            <!-- User Info Header -->
                            <div
                                class="px-4 py-3 bg-gradient-to-r from-red-600/20 to-purple-600/20 border-b border-white/10">
                                <p class="text-sm font-semibold text-white"><?php echo $username; ?></p>
                                <p class="text-xs text-gray-400">Manage your account</p>
                            </div>

                            <div class="py-2">
                                <a href="<?php echo $base_path; ?>profile/view.php" class="dropdown-item">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span>My Profile</span>
                                </a>
                                <a href="<?php echo $base_path; ?>watchlist/index.php" class="dropdown-item">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    <span>My Watchlists</span>
                                </a>
                                <a href="<?php echo $base_path; ?>favorites/index.php" class="dropdown-item">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                    <span>My Favorites</span>
                                </a>
                                <a href="<?php echo $base_path; ?>watched/index.php" class="dropdown-item">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <span>Watched Movies</span>
                                </a>

                                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                                    <div class="border-t border-white/10 my-2"></div>
                                    <a href="<?php echo $base_path; ?>admin/dashboard.php" class="dropdown-item">
                                        <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span class="text-yellow-400">Admin Panel</span>
                                    </a>
                                <?php endif; ?>

                                <div class="border-t border-white/10 my-2"></div>
                                <a href="<?php echo $base_path; ?>logout.php"
                                    class="dropdown-item text-red-400 hover:text-red-300 hover:bg-red-500/10">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    <span>Sign Out</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile Menu Button -->
                    <button onclick="toggleMobileMenu()"
                        class="md:hidden p-2 rounded-lg hover:bg-white/10 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                <?php else: ?>
                    <a href="<?php echo $base_path; ?>login.php"
                        class="px-4 py-2 text-sm font-medium text-gray-300 hover:text-white transition-colors duration-300">
                        Sign In
                    </a>
                    <a href="<?php echo $base_path; ?>register.php"
                        class="px-5 py-2 bg-gradient-to-r from-red-500 to-red-600 text-white text-sm font-semibold rounded-lg hover:from-red-600 hover:to-red-700 transition-all duration-300 shadow-lg hover:shadow-red-500/30">
                        Get Started
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Mobile Menu -->
        <?php if (isset($_SESSION['user_id'])): ?>
            <div id="mobileMenu" class="mobile-menu md:hidden border-t border-white/10">
                <!-- Mobile Search Bar -->
                <div class="px-4 py-3 border-b border-white/5">
                    <form action="<?php echo $base_path; ?>search.php" method="GET" class="w-full">
                        <div class="relative">
                            <input type="text" name="q" placeholder="Search movies, cast, users..."
                                class="w-full pl-10 pr-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-gray-100 placeholder-gray-400 focus:outline-none focus:border-red-500 transition-all duration-300">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </form>
                </div>
                <div class="py-3 space-y-1">
                    <a href="<?php echo $base_path; ?>movies/browse.php"
                        class="flex items-center gap-3 px-4 py-3 text-gray-300 hover:text-white hover:bg-white/5 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                        </svg>
                        Browse Movies
                    </a>
                    <a href="<?php echo $base_path; ?>watchlist/index.php"
                        class="flex items-center gap-3 px-4 py-3 text-gray-300 hover:text-white hover:bg-white/5 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        My Watchlists
                    </a>
                    <a href="<?php echo $base_path; ?>favorites/index.php"
                        class="flex items-center gap-3 px-4 py-3 text-gray-300 hover:text-white hover:bg-white/5 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                        My Favorites
                    </a>
                    <a href="<?php echo $base_path; ?>watched/index.php"
                        class="flex items-center gap-3 px-4 py-3 text-gray-300 hover:text-white hover:bg-white/5 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        Watched Movies
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Mobile Search for non-logged-in users -->
            <div id="mobileMenu" class="mobile-menu md:hidden border-t border-white/10">
                <div class="px-4 py-3">
                    <form action="<?php echo $base_path; ?>search.php" method="GET" class="w-full">
                        <div class="relative">
                            <input type="text" name="q" placeholder="Search movies, cast, users..."
                                class="w-full pl-10 pr-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-gray-100 placeholder-gray-400 focus:outline-none focus:border-red-500 transition-all duration-300">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</nav>

<script>
    function toggleMobileMenu() {
        const menu = document.getElementById('mobileMenu');
        menu.classList.toggle('open');
    }

    // Mobile search toggle
    document.addEventListener('DOMContentLoaded', function () {
        const mobileSearchToggle = document.getElementById('mobileSearchToggle');
        if (mobileSearchToggle) {
            mobileSearchToggle.addEventListener('click', function () {
                const menu = document.getElementById('mobileMenu');
                menu.classList.toggle('open');
                // Focus on search input when opened
                setTimeout(() => {
                    const searchInput = menu.querySelector('input[name="q"]');
                    if (searchInput && menu.classList.contains('open')) {
                        searchInput.focus();
                    }
                }, 100);
            });
        }
    });
</script>

<script>
    // Global Search with AJAX Suggestions
    (function () {
        const searchInput = document.getElementById('globalSearchInput');
        const searchSuggestions = document.getElementById('searchSuggestions');
        const searchResults = document.getElementById('searchResults');
        const searchLoading = document.getElementById('searchLoading');
        const searchEmpty = document.getElementById('searchEmpty');
        const basePath = '<?php echo $base_path; ?>';

        if (!searchInput || !searchSuggestions) return;

        let debounceTimer;
        let currentRequest = null;

        // Handle input changes with debounce
        searchInput.addEventListener('input', function () {
            const query = this.value.trim();

            clearTimeout(debounceTimer);

            if (query.length < 2) {
                hideSuggestions();
                return;
            }

            debounceTimer = setTimeout(() => {
                performSearch(query);
            }, 300);
        });

        // Handle focus
        searchInput.addEventListener('focus', function () {
            if (this.value.trim().length >= 2 && searchResults.innerHTML.trim() !== '') {
                showSuggestions();
            }
        });

        // Handle click outside to close
        document.addEventListener('click', function (e) {
            if (!searchSuggestions.contains(e.target) && e.target !== searchInput) {
                hideSuggestions();
            }
        });

        // Handle keyboard navigation
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                hideSuggestions();
                this.blur();
            }
        });

        function performSearch(query) {
            // Cancel previous request
            if (currentRequest) {
                currentRequest.abort();
            }

            showLoading();

            const controller = new AbortController();
            currentRequest = controller;

            fetch(basePath + 'search_suggest.php?q=' + encodeURIComponent(query), {
                signal: controller.signal
            })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    renderResults(data, query);
                })
                .catch(err => {
                    if (err.name !== 'AbortError') {
                        hideLoading();
                        hideSuggestions();
                    }
                });
        }

        function renderResults(data, query) {
            const hasMovies = data.movies && data.movies.length > 0;
            const hasCast = data.cast && data.cast.length > 0;
            const hasUsers = data.users && data.users.length > 0;

            if (!hasMovies && !hasCast && !hasUsers) {
                searchResults.innerHTML = '';
                searchEmpty.classList.remove('hidden');
                showSuggestions();
                return;
            }

            searchEmpty.classList.add('hidden');
            let html = '';

            // Movies section
            if (hasMovies) {
                html += '<div class="py-2">';
                html += '<div class="px-4 py-1 text-xs text-gray-400 font-semibold uppercase tracking-wider">🎬 Movies</div>';
                data.movies.forEach(movie => {
                    html += `
                        <a href="${basePath}${movie.url}" class="flex items-center gap-3 px-4 py-2 hover:bg-gray-700/50 transition-colors">
                            <div class="w-8 h-12 bg-gray-700 rounded overflow-hidden flex-shrink-0">
                                ${movie.poster ? `<img src="${movie.poster}" alt="" class="w-full h-full object-cover">` : '<div class="w-full h-full flex items-center justify-center text-xs text-gray-400">🎬</div>'}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-100 truncate">${escapeHtml(movie.title)}</div>
                                <div class="text-xs text-gray-400">${movie.year} · ★ ${movie.rating}</div>
                            </div>
                        </a>
                    `;
                });
                html += '</div>';
            }

            // Cast section
            if (hasCast) {
                html += '<div class="py-2 border-t border-gray-700">';
                html += '<div class="px-4 py-1 text-xs text-gray-400 font-semibold uppercase tracking-wider">⭐ Cast</div>';
                data.cast.forEach(cast => {
                    html += `
                        <a href="${basePath}${cast.url}" class="flex items-center gap-3 px-4 py-2 hover:bg-gray-700/50 transition-colors">
                            <div class="w-8 h-8 bg-gray-700 rounded-full overflow-hidden flex-shrink-0">
                                ${cast.photo ? `<img src="${cast.photo}" alt="" class="w-full h-full object-cover">` : '<div class="w-full h-full flex items-center justify-center text-xs text-gray-400">👤</div>'}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-100 truncate">${escapeHtml(cast.name)}</div>
                            </div>
                        </a>
                    `;
                });
                html += '</div>';
            }

            // Users section
            if (hasUsers) {
                html += '<div class="py-2 border-t border-gray-700">';
                html += '<div class="px-4 py-1 text-xs text-gray-400 font-semibold uppercase tracking-wider">👥 Users</div>';
                data.users.forEach(user => {
                    html += `
                        <a href="${basePath}${user.url}" class="flex items-center gap-3 px-4 py-2 hover:bg-gray-700/50 transition-colors">
                            <div class="w-8 h-8 bg-gray-700 rounded-full overflow-hidden flex-shrink-0">
                                ${user.avatar ? `<img src="${user.avatar}" alt="" class="w-full h-full object-cover">` : '<div class="w-full h-full flex items-center justify-center text-xs text-gray-400">👤</div>'}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-100 truncate">${escapeHtml(user.username)}</div>
                            </div>
                        </a>
                    `;
                });
                html += '</div>';
            }

            // View all link
            html += `
                <div class="py-2 border-t border-gray-700">
                    <a href="${basePath}search.php?q=${encodeURIComponent(query)}" class="flex items-center justify-center gap-2 px-4 py-2 text-red-400 hover:text-red-300 hover:bg-gray-700/50 transition-colors text-sm font-medium">
                        <span>View all results</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </div>
            `;

            searchResults.innerHTML = html;
            showSuggestions();
        }

        function showSuggestions() {
            searchSuggestions.classList.remove('hidden');
        }

        function hideSuggestions() {
            searchSuggestions.classList.add('hidden');
        }

        function showLoading() {
            searchLoading.classList.remove('hidden');
            searchEmpty.classList.add('hidden');
            searchResults.innerHTML = '';
            showSuggestions();
        }

        function hideLoading() {
            searchLoading.classList.add('hidden');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    })();
</script>

<script>
    // Handle favorite toggle forms with AJAX to prevent page refresh
    document.addEventListener('DOMContentLoaded', function () {
        // Find all favorite toggle forms
        const favoriteForms = document.querySelectorAll('form[action*="favorites/toggle.php"]');

        favoriteForms.forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(form);
                const button = form.querySelector('button[type="submit"]');
                if (!button) return;

                const originalButtonHTML = button.innerHTML;
                const originalButtonClass = button.className;

                // Disable button during request
                button.disabled = true;
                button.style.opacity = '0.6';

                // Send AJAX request
                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Update button state based on action
                            const isFavorited = data.action === 'added';

                            // Get current button classes
                            let buttonClasses = button.className;

                            if (isFavorited) {
                                // Update to favorited state (red)
                                buttonClasses = buttonClasses.replace(/bg-gray-[0-9]+(\/80)?/g, 'bg-red-600');
                                buttonClasses = buttonClasses.replace(/bg-gray-700(\/80)?/g, 'bg-red-600');
                                buttonClasses = buttonClasses.replace(/text-gray-[0-9]+/g, 'text-white');
                                buttonClasses = buttonClasses.replace(/text-gray-300/g, 'text-white');
                                buttonClasses = buttonClasses.replace(/text-gray-400/g, 'text-white');
                                buttonClasses = buttonClasses.replace(/hover:bg-gray-[0-9]+(\/80)?/g, 'hover:bg-red-700');
                                buttonClasses = buttonClasses.replace(/hover:bg-gray-600/g, 'hover:bg-red-700');
                                buttonClasses = buttonClasses.replace(/hover:bg-red-600\/80/g, 'hover:bg-red-700');

                                // Update icon to filled heart
                                const svg = button.querySelector('svg');
                                if (svg) {
                                    const svgClasses = svg.className.baseVal || svg.className;
                                    const hasText = button.querySelector('span');
                                    const iconSize = svgClasses.match(/w-[0-9]+ h-[0-9]+/) ? svgClasses.match(/w-[0-9]+ h-[0-9]+/)[0] : 'w-5 h-5';
                                    const marginClass = hasText ? (svgClasses.includes('mr-') ? svgClasses.match(/mr-[0-9]+/)[0] : 'mr-2') : '';
                                    svg.outerHTML = '<svg class="' + iconSize + ' ' + marginClass + '" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/></svg>';
                                }

                                // Update text if present
                                const span = button.querySelector('span');
                                if (span) {
                                    if (span.textContent.includes('Add to Favorites')) {
                                        span.textContent = span.textContent.replace('Add to Favorites', 'Remove from Favorites');
                                    } else if (span.textContent.includes('Add')) {
                                        span.textContent = 'Remove from Favorites';
                                    }
                                }

                                // Update title attribute if present
                                if (button.title && button.title.includes('Add')) {
                                    button.title = button.title.replace(/Add.*Favorites/i, 'Remove from Favorites');
                                }
                            } else {
                                // Update to unfavorited state (gray)
                                buttonClasses = buttonClasses.replace(/bg-red-[0-9]+(\/90)?(\/80)?/g, 'bg-gray-700');
                                buttonClasses = buttonClasses.replace(/bg-red-600/g, 'bg-gray-700');
                                buttonClasses = buttonClasses.replace(/text-white/g, 'text-gray-300');
                                buttonClasses = buttonClasses.replace(/hover:bg-red-[0-9]+(\/80)?/g, 'hover:bg-gray-600');
                                buttonClasses = buttonClasses.replace(/hover:bg-red-700/g, 'hover:bg-gray-600');
                                buttonClasses = buttonClasses.replace(/hover:bg-red-600/g, 'hover:bg-gray-600');

                                // Update icon to outline heart
                                const svg = button.querySelector('svg');
                                if (svg) {
                                    const svgClasses = svg.className.baseVal || svg.className;
                                    const hasText = button.querySelector('span');
                                    const iconSize = svgClasses.match(/w-[0-9]+ h-[0-9]+/) ? svgClasses.match(/w-[0-9]+ h-[0-9]+/)[0] : 'w-5 h-5';
                                    const marginClass = hasText ? (svgClasses.includes('mr-') ? svgClasses.match(/mr-[0-9]+/)[0] : 'mr-2') : '';
                                    svg.outerHTML = '<svg class="' + iconSize + ' ' + marginClass + '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>';
                                }

                                // Update text if present
                                const span = button.querySelector('span');
                                if (span) {
                                    if (span.textContent.includes('Remove from Favorites')) {
                                        span.textContent = span.textContent.replace('Remove from Favorites', 'Add to Favorites');
                                    } else if (span.textContent.includes('Remove')) {
                                        span.textContent = 'Add to Favorites';
                                    }
                                }

                                // Update title attribute if present
                                if (button.title && button.title.includes('Remove')) {
                                    button.title = button.title.replace(/Remove.*Favorites/i, 'Add to Favorites');
                                }
                            }

                            button.className = buttonClasses;
                        } else {
                            alert(data.message || 'An error occurred');
                            // Restore original button state on error
                            button.innerHTML = originalButtonHTML;
                            button.className = originalButtonClass;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating favorites');
                        // Restore original button state on error
                        button.innerHTML = originalButtonHTML;
                        button.className = originalButtonClass;
                    })
                    .finally(() => {
                        // Re-enable button
                        button.disabled = false;
                        button.style.opacity = '1';
                    });
            });
        });
    });
</script>