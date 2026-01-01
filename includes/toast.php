<?php
/**
 * Global Toast Notification System
 * Inspired by react-toastify
 * 
 * Usage:
 * showToast('Operation successful!', 'success');
 * showToast('Something went wrong', 'error');
 * showToast('Please wait...', 'info');
 * showToast('Warning message', 'warning');
 */
?>

<!-- Toast Container -->
<div id="toast-container" class="fixed top-24 right-4 z-[9999] flex flex-col gap-3 pointer-events-none"></div>

<style>
    .toast-item {
        pointer-events: auto;
        min-width: 300px;
        max-width: 400px;
        animation: slideIn 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        transition: all 0.3s ease;
    }

    .toast-item.removing {
        animation: slideOut 0.3s forwards cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }

        to {
            transform: translateX(110%);
            opacity: 0;
        }
    }

    .toast-progress {
        position: absolute;
        bottom: 0;
        left: 0;
        height: 3px;
        background: rgba(255, 255, 255, 0.3);
        width: 100%;
        animation: progress linear forwards;
    }

    @keyframes progress {
        from {
            width: 100%;
        }

        to {
            width: 0%;
        }
    }
</style>

<script>
    /**
     * Shows a toast notification
     * @param {string} message - The message to display
     * @param {string} type - 'success', 'error', 'info', 'warning' (default: 'success')
     * @param {number} duration - Duration in ms (default: 3000)
     */
    function showToast(message, type = 'success', duration = 3000) {
        const container = document.getElementById('toast-container');

        // Define styles and icons based on type
        const types = {
            success: {
                bg: 'bg-green-500/90',
                border: 'border-green-400/50',
                icon: '<svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                shadow: 'shadow-green-500/20'
            },
            error: {
                bg: 'bg-red-500/90',
                border: 'border-red-400/50',
                icon: '<svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                shadow: 'shadow-red-500/20'
            },
            info: {
                bg: 'bg-blue-500/90',
                border: 'border-blue-400/50',
                icon: '<svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                shadow: 'shadow-blue-500/20'
            },
            warning: {
                bg: 'bg-yellow-500/90',
                border: 'border-yellow-400/50',
                icon: '<svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>',
                shadow: 'shadow-yellow-500/20'
            }
        };

        const config = types[type] || types.success;

        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast-item relative overflow-hidden backdrop-blur-md rounded-xl shadow-lg border ${config.bg} ${config.border} ${config.shadow} p-4 text-white flex items-center gap-3`;

        toast.innerHTML = `
            <div class="flex-shrink-0">
                ${config.icon}
            </div>
            <div class="flex-1 text-sm font-medium">
                ${message}
            </div>
            <button onclick="this.parentElement.classList.add('removing'); setTimeout(() => this.parentElement.remove(), 300)" class="flex-shrink-0 text-white/70 hover:text-white transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
            <div class="toast-progress" style="animation-duration: ${duration}ms"></div>
        `;

        container.appendChild(toast);

        // Auto remove
        const timer = setTimeout(() => {
            if (toast && toast.parentElement) {
                toast.classList.add('removing');
                setTimeout(() => {
                    if (toast.parentElement) toast.remove();
                }, 300); // Wait for transition
            }
        }, duration);

        // Pause on hover
        toast.addEventListener('mouseenter', () => {
            const progress = toast.querySelector('.toast-progress');
            if (progress) progress.style.animationPlayState = 'paused';
            clearTimeout(timer); // Cancel removal
        });

        toast.addEventListener('mouseleave', () => {
            // We won't seamlessly resume duration here for simplicity in vanilla JS, 
            // but we'll restart a removal timer or just let them close it manually if they hovered.
            // A better UX might be to restart the timer with full or remaining duration.
            // For now, let's just re-set a short timer to close it if they unhover.
            const progress = toast.querySelector('.toast-progress');
            if (progress) progress.style.animationPlayState = 'running';

            setTimeout(() => {
                if (toast && toast.parentElement) {
                    toast.classList.add('removing');
                    setTimeout(() => toast.remove(), 300);
                }
            }, 1000); // Give 1s after mouse leave
        });
    }

    // Expose globally
    window.showToast = showToast;
</script>