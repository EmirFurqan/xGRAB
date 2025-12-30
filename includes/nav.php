<?php
// Reusable Navigation Component
// Usage: require("includes/nav.php"); or require("../includes/nav.php");

// Determine base path for links based on current directory
$base_path = "";
$script_path = $_SERVER['PHP_SELF'];

// Normalize path separators (handle Windows backslashes)
$script_path = str_replace('\\', '/', $script_path);

// Explicitly handle known directory patterns
if (strpos($script_path, '/admin/users/') !== false || 
    strpos($script_path, '/admin/movies/') !== false || 
    strpos($script_path, '/admin/reviews/') !== false) {
    // We're in a subdirectory of admin (e.g., admin/users/, admin/movies/)
    $base_path = "../../";
} elseif (strpos($script_path, '/admin/') !== false || 
          strpos($script_path, '/movies/') !== false || 
          strpos($script_path, '/watchlist/') !== false ||
          strpos($script_path, '/profile/') !== false ||
          strpos($script_path, '/reviews/') !== false ||
          strpos($script_path, '/cast/') !== false ||
          strpos($script_path, '/favorites/') !== false ||
          strpos($script_path, '/watched/') !== false) {
    // We're in a first-level subdirectory
    $base_path = "../";
}

// Get user info if logged in
$user_avatar = null;
$user_initial = '';
$username = '';
if (isset($_SESSION['user_id'])) {
    $username = htmlspecialchars($_SESSION['username']);
    $user_initial = strtoupper(substr($username, 0, 1));
    
    // Get user avatar from database (if connect.php is loaded)
    if (function_exists('myQuery') && isset($_SESSION['user_id'])) {
        $avatar_sql = "SELECT profile_avatar FROM users WHERE user_id = " . (int)$_SESSION['user_id'];
        $avatar_result = myQuery($avatar_sql);
        if ($avatar_result && mysqli_num_rows($avatar_result) > 0) {
            $avatar_row = mysqli_fetch_assoc($avatar_result);
            $user_avatar = $avatar_row['profile_avatar'];
        }
    }
}

// Generate color for avatar based on username (more varied colors)
$avatar_colors = [
    'bg-red-500', 'bg-red-600', 'bg-orange-500', 'bg-amber-500', 
    'bg-yellow-500', 'bg-lime-500', 'bg-green-500', 'bg-emerald-500',
    'bg-teal-500', 'bg-cyan-500', 'bg-blue-500', 'bg-indigo-500',
    'bg-purple-500', 'bg-pink-500', 'bg-rose-500', 'bg-violet-500'
];
$color_index = crc32($username) % count($avatar_colors);
$avatar_color = $avatar_colors[$color_index];
?>
<nav class="bg-gradient-to-r from-red-800 to-red-900 text-white shadow-lg">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center h-16">
            <!-- Logo -->
            <a href="<?php echo $base_path; ?>index.php" class="flex items-center space-x-2 group">
                <svg class="w-8 h-8 text-yellow-400 group-hover:scale-110 transition-transform duration-300" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/>
                </svg>
                <span class="text-2xl font-bold group-hover:text-yellow-400 transition-colors duration-300">MovieDB</span>
            </a>
            
            <!-- Navigation Links -->
            <div class="hidden md:flex items-center space-x-6">
                <a href="<?php echo $base_path; ?>movies/browse.php" class="hover:text-yellow-400 transition-colors duration-300 font-medium">Browse</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo $base_path; ?>watchlist/index.php" class="hover:text-yellow-400 transition-colors duration-300 font-medium">Watchlists</a>
                    <a href="<?php echo $base_path; ?>menu.php" class="hover:text-yellow-400 transition-colors duration-300 font-medium">Menu</a>
                <?php endif; ?>
            </div>
            
            <!-- User Section -->
            <div class="flex items-center space-x-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Profile Icon with Dropdown -->
                    <div class="relative group">
                        <a href="<?php echo $base_path; ?>profile/view.php" class="flex items-center space-x-2 hover:opacity-80 transition-opacity duration-300">
                            <div class="relative">
                                <?php if ($user_avatar): ?>
                                    <img src="<?php echo htmlspecialchars($user_avatar); ?>" 
                                         alt="<?php echo $username; ?>"
                                         class="w-10 h-10 rounded-full border-2 border-gray-600 object-cover hover:border-gray-500 transition-colors duration-300">
                                <?php else: ?>
                                    <?php 
                                    $display_initial = strtoupper(mb_substr(trim($username), 0, 1, 'UTF-8'));
                                    if (empty($display_initial) || strlen($display_initial) > 1) {
                                        $display_initial = strtoupper(substr(trim($username), 0, 1)) ?: '?';
                                    }
                                    ?>
                                    <div class="w-10 h-10 rounded-full <?php echo $avatar_color; ?> flex items-center justify-center font-bold text-white hover:opacity-90 transition-all duration-300 hover:scale-110 select-none" style="text-align: center; line-height: 1;">
                                        <span style="display: block; overflow: hidden; text-overflow: clip; max-width: 100%;"><?php echo htmlspecialchars($display_initial); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                                    <span class="absolute -top-1 -right-1 w-4 h-4 bg-yellow-400 rounded-full border-2 border-red-600"></span>
                                <?php endif; ?>
                            </div>
                            <span class="hidden md:block text-sm font-medium"><?php echo $username; ?></span>
                        </a>
                        
                        <!-- Dropdown Menu -->
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50">
                            <div class="py-2">
                                <a href="<?php echo $base_path; ?>profile/view.php" class="block px-4 py-2 text-gray-800 hover:bg-red-50 transition-colors duration-200">
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        <span>My Profile</span>
                                    </div>
                                </a>
                                <a href="<?php echo $base_path; ?>watchlist/index.php" class="block px-4 py-2 text-gray-800 hover:bg-red-50 transition-colors duration-200">
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                        <span>Watchlists</span>
                                    </div>
                                </a>
                                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                                    <a href="<?php echo $base_path; ?>admin/dashboard.php" class="block px-4 py-2 text-gray-800 hover:bg-red-50 transition-colors duration-200">
                                        <div class="flex items-center space-x-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                            </svg>
                                            <span>Admin Panel</span>
                                        </div>
                                    </a>
                                <?php endif; ?>
                                <div class="border-t border-gray-200 my-1"></div>
                                <a href="<?php echo $base_path; ?>logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-50 transition-colors duration-200">
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                        </svg>
                                        <span>Logout</span>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo $base_path; ?>login.php" class="px-4 py-2 rounded-md hover:bg-red-700 transition-colors duration-300 font-medium">
                        Login
                    </a>
                    <a href="<?php echo $base_path; ?>register.php" class="px-4 py-2 bg-yellow-400 text-red-900 rounded-md hover:bg-yellow-300 transition-colors duration-300 font-medium">
                        Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<script>
// Handle favorite toggle forms with AJAX to prevent page refresh
document.addEventListener('DOMContentLoaded', function() {
    // Find all favorite toggle forms
    const favoriteForms = document.querySelectorAll('form[action*="favorites/toggle.php"]');
    
    favoriteForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
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

