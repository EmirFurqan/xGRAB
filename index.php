<?php
session_start();
require("connect.php");
require("image_handler.php");

// Get featured movies for hero carousel (top 5 with genres)
$hero_sql = "SELECT m.*, 
             GROUP_CONCAT(g.genre_name SEPARATOR ', ') as genres,
             GROUP_CONCAT(g.genre_id SEPARATOR ',') as genre_ids
             FROM movies m
             LEFT JOIN movie_genres mg ON m.movie_id = mg.movie_id
             LEFT JOIN genres g ON mg.genre_id = g.genre_id
             WHERE m.average_rating > 0 AND m.poster_image IS NOT NULL AND m.poster_image != ''
             GROUP BY m.movie_id
             ORDER BY m.average_rating DESC, m.total_ratings DESC
             LIMIT 5";
$hero_result = myQuery($hero_sql);
$hero_movies = [];
while($movie = mysqli_fetch_assoc($hero_result)) {
    $hero_movies[] = $movie;
}

// Get featured movies for grid (top rated)
$sql = "SELECT * FROM movies ORDER BY average_rating DESC, total_ratings DESC LIMIT 12";
$result = myQuery($sql);

// Get favorite status for all movies (if user is logged in)
$favorite_movies = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    // Get all movie IDs from hero and featured
    $all_movie_ids = [];
    foreach ($hero_movies as $movie) {
        $all_movie_ids[] = $movie['movie_id'];
    }
    mysqli_data_seek($result, 0);
    while($row = mysqli_fetch_assoc($result)) {
        $all_movie_ids[] = $row['movie_id'];
    }
    mysqli_data_seek($result, 0);
    
    if (!empty($all_movie_ids)) {
        $movie_ids_str = implode(',', array_map('intval', array_unique($all_movie_ids)));
        $favorites_sql = "SELECT entity_id FROM favorites 
                         WHERE user_id = $user_id 
                         AND entity_type = 'movie' 
                         AND entity_id IN ($movie_ids_str)";
        $favorites_result = myQuery($favorites_sql);
        while($fav = mysqli_fetch_assoc($favorites_result)) {
            $favorite_movies[] = $fav['entity_id'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Database - Home</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        
        @keyframes fadeInSlide {
            from { 
                opacity: 0; 
                transform: translateX(30px); 
            }
            to { 
                opacity: 1; 
                transform: translateX(0); 
            }
        }
        
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }
        
        .hero-slide {
            animation: fadeInSlide 0.8s ease-out;
        }
        
        .hero-text {
            animation: slideInLeft 0.8s ease-out;
        }
        
        .hero-carousel {
            position: relative;
            height: 100vh;
            min-height: 600px;
            overflow: hidden;
        }
        
        .hero-slide-item {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
        }
        
        .hero-slide-item.active {
            opacity: 1;
            z-index: 1;
        }
        
        .hero-backdrop {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            transform: scale(1.05);
            transition: transform 10s ease-out;
            filter: brightness(0.4);
        }
        
        .hero-slide-item.active .hero-backdrop {
            transform: scale(1);
        }
        
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.7) 30%, rgba(0,0,0,0.5) 50%, transparent 100%);
            z-index: 1;
        }
        
        .hero-poster-container {
            position: absolute;
            left: 4rem;
            top: 50%;
            transform: translateY(-50%);
            z-index: 2;
            width: 300px;
            height: 450px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.8);
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
        }
        
        .hero-slide-item.active .hero-poster-container {
            opacity: 1;
        }
        
        .hero-poster-container:hover {
            transform: translateY(-50%) scale(1.05);
            transition: transform 0.3s ease, opacity 0.8s ease-in-out;
        }
        
        .hero-poster-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            height: 100%;
            display: flex;
            align-items: center;
            padding-left: 28rem;
            padding-right: 4rem;
        }
        
        .hero-nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            background: rgba(0,0,0,0.5);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .hero-nav-btn:hover {
            background: rgba(220, 38, 38, 0.8);
            border-color: rgba(220, 38, 38, 1);
        }
        
        .hero-nav-btn.prev {
            left: 1rem;
        }
        
        .hero-nav-btn.next {
            right: 1rem;
        }
        
        .hero-dots {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
            display: flex;
            gap: 0.75rem;
        }
        
        .hero-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255,255,255,0.4);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .hero-dot.active {
            background: #dc2626;
            width: 32px;
            border-radius: 6px;
        }
        
        @media (max-width: 1024px) {
            .hero-poster-container {
                width: 200px;
                height: 300px;
                left: 2rem;
            }
            
            .hero-content {
                padding-left: 18rem;
            }
        }
        
        @media (max-width: 768px) {
            .hero-carousel {
                height: 70vh;
                min-height: 500px;
            }
            
            .hero-backdrop {
                background-size: cover;
                background-position: center;
            }
            
            .hero-poster-container {
                width: 150px;
                height: 225px;
                left: 1rem;
            }
            
            .hero-content {
                padding-left: 10rem;
                padding-right: 2rem;
            }
            
            .hero-nav-btn {
                width: 40px;
                height: 40px;
            }
            
            .hero-nav-btn.prev {
                left: 0.5rem;
            }
            
            .hero-nav-btn.next {
                right: 0.5rem;
            }
        }
    </style>
</head>
<body class="bg-gray-900 min-h-screen text-gray-100">
    <?php require("includes/nav.php"); ?>
    
    <!-- Hero Carousel Section -->
    <?php if (!empty($hero_movies)): ?>
    <div class="hero-carousel relative mb-12">
        <?php foreach ($hero_movies as $index => $movie): 
            // Upgrade image quality for hero section
            $hero_image = getImagePath($movie['poster_image'], 'poster');
            $backdrop_image = getImagePath($movie['poster_image'], 'poster');
            // If it's a TMDB image, upgrade to higher quality
            if (strpos($hero_image, 'image.tmdb.org') !== false) {
                // Use w780 for poster (good quality, reasonable size)
                $hero_image = str_replace('/w500', '/w780', $hero_image);
                $hero_image = str_replace('/w1280', '/w780', $hero_image);
                // Use w1280 for backdrop (higher quality for background)
                $backdrop_image = str_replace('/w500', '/w1280', $backdrop_image);
                $backdrop_image = str_replace('/w780', '/w1280', $backdrop_image);
            }
        ?>
            <div class="hero-slide-item <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>">
                <div class="hero-backdrop" style="background-image: url('<?php echo htmlspecialchars($backdrop_image); ?>');"></div>
                <div class="hero-overlay"></div>
                <?php if ($hero_image): ?>
                    <div class="hero-poster-container">
                        <img src="<?php echo htmlspecialchars($hero_image); ?>" 
                             alt="<?php echo htmlspecialchars($movie['title']); ?>"
                             loading="eager">
                    </div>
                <?php endif; ?>
                <div class="hero-content">
                    <div class="max-w-3xl hero-text">
                        <h1 class="text-5xl md:text-7xl font-bold mb-4 bg-gradient-to-r from-red-400 to-red-600 bg-clip-text text-transparent whitespace-normal leading-tight">
                            <?php echo htmlspecialchars($movie['title']); ?>
                        </h1>
                        <div class="flex items-center gap-4 mb-4">
                            <div class="flex items-center bg-yellow-400 px-4 py-2 rounded-lg shadow-lg">
                                <span class="text-yellow-900 text-2xl">★</span>
                                <span class="text-2xl font-bold ml-2 text-gray-900"><?php echo number_format($movie['average_rating'], 1); ?></span>
                            </div>
                            <span class="text-gray-300 text-lg"><?php echo $movie['release_year']; ?></span>
                            <?php if ($movie['runtime']): ?>
                                <span class="text-gray-300 text-lg">
                                    <?php 
                                    $hours = floor($movie['runtime'] / 60);
                                    $minutes = $movie['runtime'] % 60;
                                    echo $hours . 'h ' . $minutes . 'm';
                                    ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if ($movie['genres']): ?>
                            <div class="flex flex-wrap gap-2 mb-4">
                                <?php 
                                $genres = explode(', ', $movie['genres']);
                                $genre_ids = explode(',', $movie['genre_ids']);
                                foreach ($genres as $idx => $genre): 
                                    $genre_id = isset($genre_ids[$idx]) ? trim($genre_ids[$idx]) : '';
                                ?>
                                    <a href="movies/browse.php?genres[]=<?php echo $genre_id; ?>" 
                                       class="px-3 py-1 bg-red-600/80 hover:bg-red-700 text-white rounded-full text-sm font-medium transition-all duration-300 transform hover:scale-105">
                                        <?php echo htmlspecialchars(trim($genre)); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($movie['description']): ?>
                            <p class="text-gray-200 text-lg mb-6 leading-relaxed line-clamp-3">
                                <?php echo htmlspecialchars($movie['description']); ?>
                            </p>
                        <?php endif; ?>
                        <div class="flex gap-4 flex-wrap">
                            <a href="movies/details.php?id=<?php echo $movie['movie_id']; ?>" 
                               class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 font-bold text-lg">
                                View Details
                                <svg class="w-6 h-6 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </a>
                            <?php if (isset($_SESSION['user_id'])): 
                                $is_favorited = in_array($movie['movie_id'], $favorite_movies);
                            ?>
                                <form method="post" action="favorites/toggle.php" class="inline">
                                    <input type="hidden" name="entity_type" value="movie">
                                    <input type="hidden" name="entity_id" value="<?php echo $movie['movie_id']; ?>">
                                    <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                    <button type="submit" 
                                            class="inline-flex items-center px-8 py-4 rounded-lg transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 font-bold text-lg <?php echo $is_favorited ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-gray-700/80 hover:bg-gray-600 text-white border-2 border-gray-600'; ?>">
                                        <?php if ($is_favorited): ?>
                                            <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                                            </svg>
                                            Remove from Favorites
                                        <?php else: ?>
                                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                            </svg>
                                            Add to Favorites
                                        <?php endif; ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <!-- Navigation Buttons -->
        <button class="hero-nav-btn prev" onclick="changeSlide(-1)">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
        <button class="hero-nav-btn next" onclick="changeSlide(1)">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
        
        <!-- Dot Indicators -->
        <div class="hero-dots">
            <?php foreach ($hero_movies as $index => $movie): ?>
                <div class="hero-dot <?php echo $index === 0 ? 'active' : ''; ?>" onclick="goToSlide(<?php echo $index; ?>)"></div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="container mx-auto px-4 py-8">
        
        <!-- Section Header -->
        <div class="mb-8 flex justify-between items-center fade-in">
            <h2 class="text-3xl font-bold text-gray-800">Top Rated Movies</h2>
            <div class="flex space-x-2">
                <a href="movies/browse.php?category=popular" class="px-4 py-2 bg-white rounded-lg shadow-md hover:shadow-lg transition-all duration-300 text-sm font-medium text-gray-700 hover:text-red-600">
                    Popular
                </a>
                <a href="movies/browse.php?category=top_rated" class="px-4 py-2 bg-white rounded-lg shadow-md hover:shadow-lg transition-all duration-300 text-sm font-medium text-gray-700 hover:text-red-600">
                    Top Rated
                </a>
            </div>
        </div>
        
        <!-- Movies Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
            <?php 
            $delay = 0;
            while($movie = mysqli_fetch_assoc($result)): 
                $delay += 50;
            ?>
                <div class="group bg-gray-800 rounded-xl shadow-md overflow-hidden hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-700 fade-in" style="animation-delay: <?php echo $delay; ?>ms">
                    <div class="relative">
                        <a href="movies/details.php?id=<?php echo $movie['movie_id']; ?>" class="block">
                            <div class="aspect-[2/3] bg-gray-200 flex items-center justify-center relative overflow-hidden">
                                <?php if ($movie['poster_image']): ?>
                                    <img src="<?php echo htmlspecialchars(getImagePath($movie['poster_image'], 'poster')); ?>" 
                                         alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                <?php else: ?>
                                    <div class="w-full h-full bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center">
                                        <span class="text-white text-sm font-medium">No Poster</span>
                                    </div>
                                <?php endif; ?>
                                <!-- Rating Badge -->
                                <div class="absolute top-2 left-2 bg-yellow-400 text-gray-900 px-2 py-1 rounded-md text-xs font-bold flex items-center space-x-1 shadow-lg">
                                    <span>★</span>
                                    <span><?php echo number_format($movie['average_rating'], 1); ?></span>
                                </div>
                            </div>
                        </a>
                        <!-- Favorite Button -->
                        <?php if (isset($_SESSION['user_id'])): 
                            $is_favorited = in_array($movie['movie_id'], $favorite_movies);
                        ?>
                            <form method="post" action="favorites/toggle.php" class="absolute top-2 right-2 z-10">
                                <input type="hidden" name="entity_type" value="movie">
                                <input type="hidden" name="entity_id" value="<?php echo $movie['movie_id']; ?>">
                                <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                <button type="submit" 
                                        class="relative group/icon p-2 rounded-full transition-all duration-300 <?php echo $is_favorited ? 'bg-red-600/90 text-white hover:bg-red-600' : 'bg-gray-800/80 text-gray-400 hover:bg-red-600/80 hover:text-white'; ?>" 
                                        title="<?php echo $is_favorited ? 'Remove from Favorites' : 'Add to Favorites'; ?>">
                                    <?php if ($is_favorited): ?>
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                                        </svg>
                                    <?php else: ?>
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                        </svg>
                                    <?php endif; ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="p-4">
                        <h3 class="font-bold text-sm text-gray-100 truncate group-hover:text-red-400 transition-colors duration-300">
                            <a href="movies/details.php?id=<?php echo $movie['movie_id']; ?>">
                                <?php echo htmlspecialchars($movie['title']); ?>
                            </a>
                        </h3>
                        <p class="text-xs text-gray-400 mt-1"><?php echo $movie['release_year']; ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <?php if (mysqli_num_rows($result) == 0): ?>
            <div class="text-center py-16 fade-in">
                <svg class="w-24 h-24 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-xl text-gray-600">No movies found.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        let currentSlide = 0;
        const slides = document.querySelectorAll('.hero-slide-item');
        const dots = document.querySelectorAll('.hero-dot');
        const totalSlides = slides.length;
        let autoRotateInterval;
        
        function showSlide(index) {
            // Remove active class from all slides and dots
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            // Add active class to current slide and dot
            if (slides[index]) {
                slides[index].classList.add('active');
            }
            if (dots[index]) {
                dots[index].classList.add('active');
            }
            
            currentSlide = index;
        }
        
        function changeSlide(direction) {
            let newIndex = currentSlide + direction;
            
            if (newIndex < 0) {
                newIndex = totalSlides - 1;
            } else if (newIndex >= totalSlides) {
                newIndex = 0;
            }
            
            showSlide(newIndex);
            resetAutoRotate();
        }
        
        function goToSlide(index) {
            showSlide(index);
            resetAutoRotate();
        }
        
        function resetAutoRotate() {
            clearInterval(autoRotateInterval);
            autoRotateInterval = setInterval(() => {
                changeSlide(1);
            }, 6000); // Change slide every 6 seconds
        }
        
        // Initialize auto-rotate
        if (totalSlides > 1) {
            resetAutoRotate();
            
            // Pause on hover
            const carousel = document.querySelector('.hero-carousel');
            if (carousel) {
                carousel.addEventListener('mouseenter', () => {
                    clearInterval(autoRotateInterval);
                });
                
                carousel.addEventListener('mouseleave', () => {
                    resetAutoRotate();
                });
            }
        }
        
        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') {
                changeSlide(-1);
            } else if (e.key === 'ArrowRight') {
                changeSlide(1);
            }
        });
        
        // Touch/swipe support for mobile
        let touchStartX = 0;
        let touchEndX = 0;
        
        const carousel = document.querySelector('.hero-carousel');
        if (carousel) {
            carousel.addEventListener('touchstart', (e) => {
                touchStartX = e.changedTouches[0].screenX;
            });
            
            carousel.addEventListener('touchend', (e) => {
                touchEndX = e.changedTouches[0].screenX;
                handleSwipe();
            });
        }
        
        function handleSwipe() {
            if (touchEndX < touchStartX - 50) {
                // Swipe left - next slide
                changeSlide(1);
            }
            if (touchEndX > touchStartX + 50) {
                // Swipe right - previous slide
                changeSlide(-1);
            }
        }
    </script>
</body>
</html>

