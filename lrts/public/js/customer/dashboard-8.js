document.addEventListener('DOMContentLoaded', function() {
            const zoomableImages = document.querySelectorAll('.lrts-zoomable-img');
            const lightbox = document.getElementById('lrtsImageLightbox');
            const lightboxImg = document.getElementById('lrtsLightboxImg');

            // Attach click event to all zoomable images
            zoomableImages.forEach(img => {
                img.addEventListener('click', function() {
                    // Get the source from data-src or src
                    const src = this.getAttribute('data-src') || this.src;
                    lightboxImg.src = src;
                    lightbox.classList.add('is-open');
                    document.body.style.overflow = 'hidden'; // Prevent background scrolling
                });
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