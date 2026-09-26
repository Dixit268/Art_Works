/**
 * Art Gallery - Vanilla JavaScript Interaction Engine
 */

document.addEventListener('DOMContentLoaded', () => {
    // Initialize tooltips if Bootstrap Tooltip is available
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href');
            if (targetId.length > 1) {
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    e.preventDefault();
                    targetElement.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            }
        });
    });
});

/**
 * Toggle Wishlist AJAX Helper Function
 * @param {number} artworkId
 * @param {HTMLElement} btn
 */
async function toggleWishlist(artworkId, btn) {
    try {
        const response = await fetch('api/wishlist-toggle.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ artwork_id: artworkId })
        });

        const data = await response.json();

        if (data.require_login) {
            if (confirm(data.message + "\n\nWould you like to sign in now?")) {
                window.location.href = 'login.php';
            }
            return;
        }

        if (data.success) {
            const icon = btn.querySelector('i');
            const isAdded = data.status === 'added';

            if (isAdded) {
                btn.classList.add('active');
                if (icon) {
                    icon.classList.remove('bi-heart');
                    icon.classList.add('bi-heart-fill');
                }
                btn.setAttribute('title', 'Remove from Wishlist');

                // Detail page button text update if present
                const textSpan = document.getElementById('wishlistBtnText');
                if (textSpan) {
                    textSpan.textContent = 'Saved in Wishlist';
                    btn.classList.remove('btn-outline-custom');
                    btn.classList.add('btn-danger');
                }
            } else {
                btn.classList.remove('active');
                if (icon) {
                    icon.classList.remove('bi-heart-fill');
                    icon.classList.add('bi-heart');
                }
                btn.setAttribute('title', 'Add to Wishlist');

                // Detail page button text update if present
                const textSpan = document.getElementById('wishlistBtnText');
                if (textSpan) {
                    textSpan.textContent = 'Add to Wishlist';
                    btn.classList.remove('btn-danger');
                    btn.classList.add('btn-outline-custom');
                }

                // If on wishlist.php page, gracefully remove the card
                const wishlistItem = document.getElementById('wishlist-item-' + artworkId);
                if (wishlistItem) {
                    wishlistItem.style.opacity = '0';
                    wishlistItem.style.transform = 'scale(0.95)';
                    wishlistItem.style.transition = 'all 0.3s ease';
                    setTimeout(() => {
                        wishlistItem.remove();
                    }, 300);
                }
            }

            // Quick notification indicator
            showGalleryToast(data.message);
        } else {
            alert(data.message || 'Error updating wishlist.');
        }
    } catch (err) {
        console.error('Wishlist AJAX error:', err);
    }
}

/**
 * Lightweight Toast Notification
 * @param {string} msg 
 */
function showGalleryToast(msg) {
    let toastContainer = document.getElementById('gallery-toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'gallery-toast-container';
        toastContainer.style.position = 'fixed';
        toastContainer.style.bottom = '24px';
        toastContainer.style.right = '24px';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    toast.style.background = '#171717';
    toast.style.color = '#F8F6F2';
    toast.style.borderLeft = '4px solid #C9A86A';
    toast.style.padding = '12px 20px';
    toast.style.marginTop = '10px';
    toast.style.borderRadius = '4px';
    toast.style.boxShadow = '0 10px 25px rgba(0,0,0,0.2)';
    toast.style.fontSize = '0.88rem';
    toast.style.fontFamily = "'Plus Jakarta Sans', sans-serif";
    toast.style.transition = 'all 0.3s ease';
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(10px)';
    toast.innerHTML = `<i class="bi bi-info-circle me-2 text-warning"></i> ${msg}`;

    toastContainer.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    }, 10);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        setTimeout(() => toast.remove(), 300);
    }, 3200);
}
