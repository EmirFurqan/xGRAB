<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- Handle Review Helpful/Like Buttons ---
        const likeForms = document.querySelectorAll('form[action*="reviews/like.php"]');
        likeForms.forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const button = form.querySelector('button');
                if (!button) return;

                button.disabled = true;
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

                            const isLiked = data.action === 'liked';
                            const count = data.like_count;
                            const spanText = `Helpful (${count})`;

                            if (isLiked) {
                                button.className = 'flex items-center gap-1.5 text-green-400 transition-colors';
                                button.innerHTML = `
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z"/>
                                    </svg>
                                    <span>${spanText}</span>
                                `;
                            } else {
                                button.className = 'flex items-center gap-1.5 text-gray-400 hover:text-green-400 transition-colors';
                                button.innerHTML = `
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                                    </svg>
                                    <span>${spanText}</span>
                                `;
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

        // --- Handle Favorite Toggle ---
        const favoriteForms = document.querySelectorAll('form[action*="favorites/toggle.php"]');
        favoriteForms.forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const button = form.querySelector('button');
                if (!button) return;

                button.disabled = true;
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

                            const isFavorited = data.action === 'added';

                            if (isFavorited) {
                                const intentInput = form.querySelector('input[name="intent"]');
                                if (intentInput) intentInput.value = 'remove';

                                button.className = 'action-btn min-w-[160px] justify-center flex items-center gap-2 px-5 py-3 rounded-xl font-semibold bg-red-600 text-white';
                                button.innerHTML = `
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>Favorited</span>
                                `;
                            } else {
                                const intentInput = form.querySelector('input[name="intent"]');
                                if (intentInput) intentInput.value = 'add';

                                button.className = 'action-btn min-w-[160px] justify-center flex items-center gap-2 px-5 py-3 rounded-xl font-semibold bg-white/10 text-gray-200 hover:bg-white/20';
                                button.innerHTML = `
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                    </svg>
                                    <span>Favorite</span>
                                `;
                            }
                        } else {
                            if (typeof showToast === 'function') {
                                showToast(data.message || 'Error updating favorite', 'error');
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
                        button.style.opacity = '1';
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

                            const isWatched = data.action === 'added';

                            if (isWatched) {
                                button.className = 'action-btn min-w-[160px] justify-center flex items-center gap-2 px-5 py-3 rounded-xl font-semibold bg-green-600 text-white';
                                button.innerHTML = `
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>Watched</span>
                                `;
                            } else {
                                button.className = 'action-btn min-w-[160px] justify-center flex items-center gap-2 px-5 py-3 rounded-xl font-semibold bg-white/10 text-gray-200 hover:bg-white/20';
                                button.innerHTML = `
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <span>Mark Watched</span>
                                `;
                            }
                        } else {
                            if (typeof showToast === 'function') {
                                showToast(data.message || 'Error updating watched status', 'error');
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
                        button.style.opacity = '1';
                    });
            });
        });

        // --- Handle New Review Form Submission via AJAX ---
        const newReviewForm = document.getElementById('newReviewForm');
        if (newReviewForm) {
            newReviewForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const submitBtn = newReviewForm.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Submitting...';

                const formData = new FormData(newReviewForm);

                fetch(newReviewForm.action, {
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
                                showToast(data.message || 'Review submitted successfully!', 'success');
                            }
                            // Reload the page to show the new review without going to top
                            setTimeout(() => {
                                const currentScroll = window.scrollY;
                                location.reload();
                            }, 1000);
                        } else {
                            if (typeof showToast === 'function') {
                                showToast(data.message || 'Failed to submit review', 'error');
                            }
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalBtnText;
                        }
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        if (typeof showToast === 'function') {
                            showToast('An error occurred while submitting your review', 'error');
                        }
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                    });
            });
        }

        // --- Handle Edit Review Form Submission via AJAX ---
        const editReviewForm = document.querySelector('#editReviewForm form');
        if (editReviewForm) {
            editReviewForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const submitBtn = editReviewForm.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Updating...';

                const formData = new FormData(editReviewForm);

                fetch(editReviewForm.action, {
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
                                showToast(data.message || 'Review updated successfully!', 'success');
                            }
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            if (typeof showToast === 'function') {
                                showToast(data.message || 'Failed to update review', 'error');
                            }
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalBtnText;
                        }
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        if (typeof showToast === 'function') {
                            showToast('An error occurred while updating your review', 'error');
                        }
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                    });
            });
        }
    });
</script>