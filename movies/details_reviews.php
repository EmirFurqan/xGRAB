<?php
// Include config if not already loaded (when accessed directly or included)
if (!defined('BASE_URL')) {
    if (file_exists(__DIR__ . '/../includes/config.php')) {
        require_once __DIR__ . '/../includes/config.php';
    }
}
/**
 * Reviews Section Component for Movie Details
 * Handles displaying existing reviews and the review submission form
 */
?>

<!-- User Review Section -->
<?php if (isset($_SESSION['user_id'])): ?>
    <?php if ($user_review): ?>
        <!-- User's Existing Review -->
        <div class="glass-card rounded-2xl p-6 mb-6 border-2 border-red-500/50">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
                    <h3 class="font-bold text-lg">Your Review</h3>
                </div>
                <div class="flex gap-2">
                    <button onclick="document.getElementById('editReviewForm').classList.toggle('hidden')" 
                            class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-medium transition-colors">
                        Edit
                    </button>
                    <a href="../reviews/delete.php?review_id=<?php echo $user_review['review_id']; ?>&movie_id=<?php echo $movie_id; ?>" 
                       class="px-4 py-2 bg-red-600/20 hover:bg-red-600/40 text-red-400 rounded-lg text-sm font-medium transition-colors"
                       onclick="return confirm('Are you sure you want to delete your review?');">
                        Delete
                    </a>
                </div>
            </div>
            
            <div class="flex items-center gap-2 mb-3">
                <div class="flex text-yellow-400 text-xl">
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <span class="<?php echo $i <= $user_review['rating_value'] ? 'opacity-100' : 'opacity-30'; ?>">★</span>
                    <?php endfor; ?>
                </div>
                <span class="font-bold text-xl"><?php echo number_format($user_review['rating_value'], 1); ?>/10</span>
            </div>
            
            <?php if ($user_review['is_spoiler']): ?>
            <div class="bg-yellow-900/50 border border-yellow-600/50 text-yellow-300 px-4 py-2 rounded-lg mb-3 text-sm flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Contains spoilers
            </div>
            <?php endif; ?>
            
            <p class="text-gray-300 leading-relaxed"><?php echo nl2br(htmlspecialchars($user_review['review_text'])); ?></p>
            
            <p class="text-xs text-gray-500 mt-4">
                <?php echo date('M d, Y', strtotime($user_review['created_at'])); ?>
                <?php if ($user_review['updated_at'] != $user_review['created_at']): ?>
                    <span class="text-gray-600">• edited</span>
                <?php endif; ?>
            </p>
            
            <!-- Edit Form -->
            <div id="editReviewForm" class="hidden mt-6 pt-6 border-t border-gray-700">
                <h4 class="font-semibold mb-4">Edit Your Review</h4>
                <form method="post" action="../reviews/submit.php">
                    <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2 text-gray-300">Your Rating</label>
                        <div class="flex items-center gap-4">
                            <div class="star-rating" id="editStarRating" data-initial="<?php echo $user_review['rating_value']; ?>">
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <span class="star" data-value="<?php echo $i; ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <span class="text-lg font-bold text-yellow-400" id="editRatingDisplay"><?php echo number_format($user_review['rating_value'], 1); ?>/10</span>
                        </div>
                        <input type="hidden" name="rating_value" id="edit_rating_value" value="<?php echo $user_review['rating_value']; ?>" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2 text-gray-300">Review</label>
                        <textarea name="review_text" id="edit_review_text" rows="4" required maxlength="1000"
                                  class="w-full px-4 py-3 bg-gray-800/80 border border-gray-600 rounded-xl text-gray-100 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all resize-none"><?php echo htmlspecialchars($user_review['review_text']); ?></textarea>
                        <div class="mt-1 text-xs text-gray-400 flex justify-between">
                            <span id="edit_review_counter"><?php echo strlen($user_review['review_text']); ?> / 1000</span>
                            <span>Min: 2 characters</span>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="flex items-center gap-2 text-gray-300 cursor-pointer">
                            <input type="checkbox" name="is_spoiler" value="1" 
                                   class="w-5 h-5 rounded border-gray-600 bg-gray-800 text-red-600 focus:ring-red-500"
                                   <?php echo $user_review['is_spoiler'] ? 'checked' : ''; ?>>
                            <span>Contains spoilers</span>
                        </label>
                    </div>
                    
                    <div class="flex gap-3">
                        <button type="submit" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl font-medium transition-colors">
                            Update Review
                        </button>
                        <button type="button" onclick="document.getElementById('editReviewForm').classList.add('hidden')" 
                                class="px-5 py-2.5 bg-gray-700 hover:bg-gray-600 text-white rounded-xl font-medium transition-colors">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- New Review Form -->
        <div class="glass-card rounded-2xl p-6 mb-6">
            <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Write a Review
            </h3>
            <form method="post" action="../reviews/submit.php" id="newReviewForm">
                <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2 text-gray-300">Your Rating</label>
                    <div class="flex items-center gap-4">
                        <div class="star-rating" id="newStarRating">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <span class="star" data-value="<?php echo $i; ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <span class="text-lg font-bold text-gray-400" id="newRatingDisplay">0/10</span>
                    </div>
                    <input type="hidden" name="rating_value" id="new_rating_value" value="" required>
                    <p class="text-xs text-gray-500 mt-1">Click stars to rate (click same star twice for half rating)</p>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2 text-gray-300">Review</label>
                    <textarea name="review_text" id="new_review_text" rows="4" required maxlength="1000"
                              placeholder="Share your thoughts about this movie..."
                              class="w-full px-4 py-3 bg-gray-800/80 border border-gray-600 rounded-xl text-gray-100 placeholder-gray-500 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all resize-none"></textarea>
                    <div class="mt-1 text-xs text-gray-400 flex justify-between">
                        <span id="new_review_counter">0 / 1000</span>
                        <span>Min: 2 characters</span>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="flex items-center gap-2 text-gray-300 cursor-pointer">
                        <input type="checkbox" name="is_spoiler" value="1" 
                               class="w-5 h-5 rounded border-gray-600 bg-gray-800 text-red-600 focus:ring-red-500">
                        <span>Contains spoilers</span>
                    </label>
                </div>
                
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white rounded-xl font-semibold transition-all shadow-lg hover:shadow-red-500/25">
                    Submit Review
                </button>
            </form>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Reviews List -->
<div class="space-y-4">
    <?php if (mysqli_num_rows($review_result) > 0): ?>
        <?php 
        mysqli_data_seek($review_result, 0);
        while($review = mysqli_fetch_assoc($review_result)): 
            $is_liked = isset($_SESSION['user_id']) && in_array($review['review_id'], $liked_reviews);
            $is_own_review = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $review['user_id'];
            
            $review_colors = ['bg-red-500', 'bg-orange-500', 'bg-amber-500', 'bg-green-500', 'bg-teal-500', 'bg-blue-500', 'bg-indigo-500', 'bg-purple-500', 'bg-pink-500'];
            $review_color = $review_colors[crc32($review['username']) % count($review_colors)];
            $review_initial = strtoupper(substr(trim($review['username']), 0, 1));
        ?>
        <div class="glass-card rounded-xl p-5 hover:border-gray-600 transition-colors">
            <div class="flex items-start gap-4">
                <!-- Avatar -->
                <a href="../profile/view.php?user_id=<?php echo $review['user_id']; ?>" class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-full overflow-hidden <?php echo $review['profile_avatar'] ? '' : $review_color; ?> flex items-center justify-center">
                        <?php if ($review['profile_avatar']): ?>
                            <img src="<?php echo htmlspecialchars(getImagePath($review['profile_avatar'], 'avatar')); ?>" 
                                 alt="<?php echo htmlspecialchars($review['username']); ?>"
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <span class="text-white font-bold text-lg"><?php echo htmlspecialchars($review_initial); ?></span>
                        <?php endif; ?>
                    </div>
                </a>
                
                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-3">
                            <a href="../profile/view.php?user_id=<?php echo $review['user_id']; ?>" 
                               class="font-semibold hover:text-red-400 transition-colors">
                                <?php echo htmlspecialchars($review['username']); ?>
                            </a>
                            <div class="flex items-center gap-1 text-yellow-400 text-sm">
                                <span>★</span>
                                <span class="font-semibold"><?php echo number_format($review['rating_value'], 1); ?></span>
                            </div>
                        </div>
                        <span class="text-xs text-gray-500">
                            <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                            <?php if ($review['updated_at'] != $review['created_at']): ?>
                                <span class="text-gray-600">• edited</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <?php if ($review['is_spoiler']): ?>
                    <div class="bg-yellow-900/30 border border-yellow-600/30 text-yellow-400 px-3 py-1.5 rounded-lg mb-2 text-xs inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        Spoiler
                    </div>
                    <?php endif; ?>
                    
                    <p class="text-gray-300 leading-relaxed mb-3"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                    
                    <!-- Actions -->
                    <div class="flex items-center gap-4 text-sm">
                        <?php if (!$is_own_review): ?>
                        <form method="post" action="../reviews/like.php" class="inline">
                            <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                            <button type="submit" class="flex items-center gap-1.5 <?php echo $is_liked ? 'text-green-400' : 'text-gray-400 hover:text-green-400'; ?> transition-colors">
                                <?php if ($is_liked): ?>
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z"/>
                                    </svg>
                                <?php else: ?>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                                    </svg>
                                <?php endif; ?>
                                <span>Helpful (<?php echo $review['like_count']; ?>)</span>
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $review['user_id']): ?>
                        <form method="post" action="../reviews/report.php" class="inline">
                            <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                            <button type="submit" class="text-gray-500 hover:text-red-400 transition-colors">
                                Report
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
        
        <!-- Pagination -->
        <?php if ($total_review_pages > 1): ?>
        <div class="flex justify-center gap-2 pt-6">
            <?php if ($review_page > 1): ?>
            <a href="?id=<?php echo $movie_id; ?>&review_page=<?php echo $review_page - 1; ?>" 
               class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors">
                Previous
            </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $review_page - 2); $i <= min($total_review_pages, $review_page + 2); $i++): ?>
            <a href="?id=<?php echo $movie_id; ?>&review_page=<?php echo $i; ?>" 
               class="px-4 py-2 rounded-lg transition-colors <?php echo $i == $review_page ? 'bg-red-600 text-white' : 'bg-gray-700 hover:bg-gray-600'; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
            
            <?php if ($review_page < $total_review_pages): ?>
            <a href="?id=<?php echo $movie_id; ?>&review_page=<?php echo $review_page + 1; ?>" 
               class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors">
                Next
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            <p class="text-gray-400 text-lg">No reviews yet. Be the first to review this movie!</p>
        </div>
    <?php endif; ?>
</div>

<script>
// Star Rating Initialization
document.addEventListener('DOMContentLoaded', function() {
    initStarRating('newStarRating', 'new_rating_value', 'newRatingDisplay');
    initStarRating('editStarRating', 'edit_rating_value', 'editRatingDisplay');
    
    // Character counters
    setupCounter('new_review_text', 'new_review_counter');
    setupCounter('edit_review_text', 'edit_review_counter');
});

function initStarRating(containerId, inputId, displayId) {
    const container = document.getElementById(containerId);
    const input = document.getElementById(inputId);
    const display = document.getElementById(displayId);
    
    if (!container || !input) return;
    
    const stars = container.querySelectorAll('.star');
    let currentRating = parseFloat(container.dataset.initial) || 0;
    let lastClickedValue = 0;
    
    updateStars(currentRating, false);
    
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const value = parseInt(this.dataset.value);
            
            if (value === lastClickedValue) {
                currentRating = currentRating === value ? value - 0.5 : value;
            } else {
                currentRating = value;
            }
            
            lastClickedValue = value;
            updateStars(currentRating, false);
            input.value = currentRating;
        });
        
        star.addEventListener('mouseenter', function() {
            updateStars(parseInt(this.dataset.value), true);
        });
    });
    
    container.addEventListener('mouseleave', function() {
        updateStars(currentRating, false);
    });
    
    function updateStars(rating, isPreview) {
        stars.forEach(star => {
            const value = parseInt(star.dataset.value);
            star.classList.remove('filled', 'preview', 'half', 'half-preview');
            // Reset inline styles that might interfere
            star.style.background = '';
            star.style.webkitBackgroundClip = '';
            star.style.webkitTextFillColor = '';
            star.style.backgroundClip = '';
            star.style.color = '';
            
            if (value <= Math.floor(rating)) {
                // Full star
                star.classList.add(isPreview ? 'preview' : 'filled');
            } else if (value === Math.ceil(rating) && rating % 1 !== 0) {
                // Half star - apply gradient styling
                star.classList.add(isPreview ? 'half-preview' : 'half');
            }
        });
        
        if (display) {
            display.textContent = rating > 0 ? (rating % 1 === 0 ? rating : rating.toFixed(1)) + '/10' : '0/10';
            display.className = rating > 0 ? 'text-lg font-bold text-yellow-400' : 'text-lg font-bold text-gray-400';
        }
    }
}

function setupCounter(textareaId, counterId) {
    const textarea = document.getElementById(textareaId);
    const counter = document.getElementById(counterId);
    
    if (!textarea || !counter) return;
    
    function update() {
        const length = textarea.value.length;
        counter.textContent = length + ' / 1000';
        counter.className = length < 2 ? 'text-red-400' : (length > 900 ? 'text-yellow-400' : 'text-gray-400');
    }
    
    textarea.addEventListener('input', update);
    update();
}
</script>
