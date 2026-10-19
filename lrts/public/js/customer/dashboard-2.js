(function() {
            const menuToggle = document.getElementById("menuToggle");
            const primaryNav = document.getElementById("primaryNav");
            const backdrop = document.getElementById("sidebarBackdrop");

            if (!menuToggle || !primaryNav) return;

            menuToggle.addEventListener("click", () => {
                const isOpen = primaryNav.classList.toggle("is-open");
                backdrop.classList.toggle("is-open", isOpen);
                menuToggle.setAttribute("aria-expanded", String(isOpen));
            });

            primaryNav.querySelectorAll(".nav-link").forEach((link) => {
                link.addEventListener("click", () => {
                    primaryNav.classList.remove("is-open");
                    backdrop.classList.remove("is-open");
                    menuToggle.setAttribute("aria-expanded", "false");
                });
            });

            backdrop.addEventListener("click", () => {
                primaryNav.classList.remove("is-open");
                backdrop.classList.remove("is-open");
                menuToggle.setAttribute("aria-expanded", "false");
            });
        })();