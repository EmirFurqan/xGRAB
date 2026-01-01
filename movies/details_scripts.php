<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- Handle Review Helpful/Like Buttons ---
        const likeForms = document.querySelectorAll('form[action*="reviews/like.php"]');
        likeForms.forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const button = form.querySelector('button');
                if (!button) return;

                // Disable button to prevent double clicks
                button.disabled = true;
                const originalHtml = button.innerHTML;

                // Optimistic UI update (optional, but good for perceived speed)
                // We'll wait for server response to be safe

                const formData = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show toast
                            if (typeof showToast === 'function') {
                                showToast(data.message, 'success');
                            }

                            // Update UI
                            const isLiked = data.action === 'liked';
                            const count = data.like_count;
                            const spanText = `Helpful (${count})`;

                            if (isLiked) {
                                // Update style to green/liked state
                                // Using string replacement to keep it simple or full class list replacement
                                button.className = button.className.replace('text-red-400', 'text-green-400')
                                    .replace('hover:text-red-300', 'hover:text-green-300');

                                // Update icon and text
                                button.innerHTML = `<span>✓</span><span>${spanText}</span>`;
                            } else {
                                // Update style to red/unliked state
                                button.className = button.className.replace('text-green-400', 'text-red-400')
                                    .replace('hover:text-green-300', 'hover:text-red-300');

                                // Update icon and text
                                button.innerHTML = `<span>👍</span><span>${spanText}</span>`;
                            }
                        } else {
                            if (typeof showToast === 'function') {
                                showToast(data.message || 'Error updating like', 'error');
                            }
                        }
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        if (typeof showToast === 'function') {
                            showToast('An error occurred', 'error');
                        }
                    })
                    .finally(() => {
                        button.disabled = false;
                    });
            });
        });

        // --- Handle Watched Toggle ---
        const watchedForms = document.querySelectorAll('form[action*="watched/toggle.php"]');
        watchedForms.forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const button = form.querySelector('button');
                if (!button) return;

                button.disabled = true;
                const originalHtml = button.innerHTML;
                button.style.opacity = '0.7';

                const formData = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (typeof showToast === 'function') {
                                showToast(data.message, 'success');
                            }

                            // Toggle UI state
                            const isWatched = data.action === 'added';

                            if (isWatched) {
                                // Change to Green / "Mark as Not Watched"
                                button.className = "relative group/icon px-4 py-2 rounded-lg transition-all duration-300 flex items-center space-x-2 bg-green-600 text-white hover:bg-green-700";
                                button.innerHTML = `
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Mark as Not Watched</span>
                            `;
                            } else {
                                // Change to Gray / "Mark as Watched"
                                button.className = "relative group/icon px-4 py-2 rounded-lg transition-all duration-300 flex items-center space-x-2 bg-gray-700 text-gray-300 hover:bg-gray-600";
                                button.innerHTML = `
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Mark as Watched</span>
                            `;
                            }
                        } else {
                            if (typeof showToast === 'function') {
                                showToast(data.message || 'Error updating watched status', 'error');
                            }
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        if (typeof showToast === 'function') {
                            showToast('An error occurred', 'error');
                        }
                    })
                    .finally(() => {
                        button.disabled = false;
                        button.style.opacity = '1';
                    });
            });
        });
    });
</script>