<?php
/**
 * Watchlist Modal Component for Movie Details
 * Modal dialog for adding movie to watchlists
 */
?>

<div id="watchlistModal" class="fixed inset-0 z-50 hidden">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm transition-opacity duration-300"
        onclick="closeWatchlistModal()"></div>

    <!-- Modal Content -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md transform transition-all duration-300 scale-95 opacity-0"
        id="watchlistModalContent">
        <div class="bg-gray-800 rounded-2xl shadow-2xl border border-gray-700 overflow-hidden mx-4">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white flex items-center gap-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        Add to Watchlist
                    </h3>
                    <button type="button" onclick="closeWatchlistModal()"
                        class="text-white/80 hover:text-white transition-colors p-1 hover:bg-white/10 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <p class="text-blue-200 text-sm mt-1">Select watchlists to add this movie</p>
            </div>

            <!-- Body -->
            <form method="post" action="../watchlist/add_movie_multiple.php" id="watchlistForm">
                <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">

                <div class="px-6 py-4 max-h-[300px] overflow-y-auto custom-scrollbar">
                    <div class="space-y-2">
                        <?php foreach ($watchlists_array as $wl): ?>
                            <label
                                class="flex items-center p-3 rounded-xl cursor-pointer transition-all duration-200 hover:bg-gray-700/50 group <?php echo $wl['movie_id'] ? 'opacity-60' : ''; ?>">
                                <input type="checkbox" name="watchlist_ids[]" value="<?php echo $wl['watchlist_id']; ?>"
                                    <?php echo $wl['movie_id'] ? 'disabled checked' : ''; ?>
                                    class="w-5 h-5 rounded border-2 border-gray-500 bg-gray-700 text-blue-600 focus:ring-blue-500 focus:ring-offset-0 transition-all duration-200">
                                <div class="ml-3 flex-1">
                                    <span class="text-gray-100 font-medium group-hover:text-white transition-colors">
                                        <?php echo htmlspecialchars($wl['watchlist_name']); ?>
                                    </span>
                                    <?php if ($wl['movie_id']): ?>
                                        <span
                                            class="ml-2 text-xs text-green-400 font-medium flex items-center gap-1 inline-flex">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            Already added
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-gray-900/50 border-t border-gray-700 flex items-center justify-between gap-3">
                    <a href="../watchlist/create.php"
                        class="text-blue-400 hover:text-blue-300 text-sm font-medium transition-colors flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Create New Watchlist
                    </a>
                    <div class="flex gap-2">
                        <button type="button" onclick="closeWatchlistModal()"
                            class="px-4 py-2.5 bg-gray-700 text-gray-300 hover:bg-gray-600 rounded-xl transition-colors font-medium">
                            Cancel
                        </button>
                        <button type="submit" id="addToWatchlistBtn"
                            class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                            Add Selected
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: #374151;
        border-radius: 3px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #6B7280;
        border-radius: 3px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #9CA3AF;
    }

    @keyframes modalFadeIn {
        from {
            opacity: 0;
            transform: translate(-50%, -50%) scale(0.9);
        }

        to {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }
    }

    @keyframes modalFadeOut {
        from {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }

        to {
            opacity: 0;
            transform: translate(-50%, -50%) scale(0.9);
        }
    }

    #watchlistModalContent.show {
        animation: modalFadeIn 0.3s ease-out forwards;
    }

    #watchlistModalContent.hide {
        animation: modalFadeOut 0.2s ease-in forwards;
    }
</style>

<script>
    function openWatchlistModal() {
        const modal = document.getElementById('watchlistModal');
        const content = document.getElementById('watchlistModalContent');

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('show');
        }, 10);

        updateAddButton();
    }

    function closeWatchlistModal() {
        const modal = document.getElementById('watchlistModal');
        const content = document.getElementById('watchlistModalContent');

        content.classList.remove('show');
        content.classList.add('hide');

        setTimeout(() => {
            modal.classList.add('hidden');
            content.classList.remove('hide');
            content.classList.add('scale-95', 'opacity-0');
            document.body.style.overflow = '';
        }, 200);
    }

    function updateAddButton() {
        const checkboxes = document.querySelectorAll('#watchlistForm input[type="checkbox"]:not(:disabled):checked');
        const btn = document.getElementById('addToWatchlistBtn');
        if (btn) {
            btn.disabled = checkboxes.length === 0;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('watchlistForm');
        if (form) {
            form.addEventListener('change', updateAddButton);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('watchlistModal');
            if (modal && !modal.classList.contains('hidden')) {
                closeWatchlistModal();
            }
        }
    });
</script>