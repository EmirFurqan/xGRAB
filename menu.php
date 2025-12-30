<?php
/**
 * User Dashboard Menu
 * Main navigation hub for logged-in users.
 * Provides quick access to movies, profile, watchlists, and admin panel (if admin).
 */

session_start();
require("connect.php");

// Verify user is logged in before showing dashboard
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - xGrab</title>
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
    <?php require("includes/nav.php"); ?>

    <div class="container mx-auto px-4 py-8">
        <div class="mb-8 fade-in">
            <h1 class="text-4xl font-bold mb-2 bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent">
                Dashboard
            </h1>
            <p class="text-gray-400">Welcome back! What would you like to do today?</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Browse Movies -->
            <div
                class="bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border-l-4 border-red-500 border border-gray-700 fade-in">
                <div class="flex items-center mb-4">
                    <div
                        class="w-12 h-12 bg-gradient-to-br from-red-600 to-red-800 rounded-lg flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-100">Browse Movies</h2>
                </div>
                <p class="text-gray-400 mb-6">Search, filter, and browse our movie collection</p>
                <a href="movies/browse.php"
                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl">
                    Browse Movies
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </a>
            </div>

            <!-- My Profile -->
            <div
                class="bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border-l-4 border-red-500 border border-gray-700 fade-in">
                <div class="flex items-center mb-4">
                    <div
                        class="w-12 h-12 bg-gradient-to-br from-red-600 to-red-800 rounded-lg flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-100">My Profile</h2>
                </div>
                <p class="text-gray-400 mb-6">View and edit your profile information</p>
                <a href="profile/view.php"
                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl">
                    View Profile
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </a>
            </div>

            <!-- My Watchlists -->
            <div
                class="bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border-l-4 border-red-500 border border-gray-700 fade-in">
                <div class="flex items-center mb-4">
                    <div
                        class="w-12 h-12 bg-gradient-to-br from-red-600 to-red-800 rounded-lg flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-100">My Watchlists</h2>
                </div>
                <p class="text-gray-400 mb-6">Manage your movie watchlists</p>
                <a href="watchlist/index.php"
                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl">
                    View Watchlists
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </a>
            </div>

            <?php if ($_SESSION['is_admin']): ?>
                <!-- Admin Panel -->
                <div
                    class="bg-gradient-to-br from-yellow-50 to-orange-50 p-6 rounded-xl shadow-md hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border-l-4 border-yellow-500 fade-in">
                    <div class="flex items-center mb-4">
                        <div
                            class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800">Admin Panel</h2>
                    </div>
                    <p class="text-gray-600 mb-6">Manage movies, reviews, and users</p>
                    <a href="admin/dashboard.php"
                        class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-yellow-500 to-orange-500 text-white rounded-lg hover:from-yellow-600 hover:to-orange-600 transition-all duration-300 shadow-lg hover:shadow-xl">
                        Admin Dashboard
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>