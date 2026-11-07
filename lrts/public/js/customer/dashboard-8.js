document.addEventListener('DOMContentLoaded', function() {
            const lightbox = document.getElementById('lrtsImageLightbox');
            const lightboxImg = document.getElementById('lrtsLightboxImg');

            document.addEventListener('click', function(event) {
                    const img = event.target.closest('.lrts-zoomable-img');
                    if (!img) return;
                    // Get the source from data-src or src
                    const src = img.getAttribute('data-src') || img.src;
                    lightboxImg.src = src;
                    lightbox.classList.add('is-open');
                    document.body.style.overflow = 'hidden'; // Prevent background scrolling
            });

            // Function to close the lightbox
            window.closeLightbox = function(e) {
                if (e) e.stopPropagation();
                lightbox.classList.remove('is-open');
                document.body.style.overflow = ''; // Restore background scrolling
                
                // Clear the src after the fade-out animation to save memory
                setTimeout(() => {
                    lightboxImg.src = '';
                }, 200);
            }

            // Allow closing with the Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && lightbox.classList.contains('is-open')) {
                    closeLightbox();
                }
            });
        });
