document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const mobileNav = document.querySelector('.mobile-nav');
    const checkboxToggle = document.getElementById('nav-toggle');
    const menu = document.querySelector('.mobile-nav .menu-items');
    const navLinks = menu ? menu.querySelectorAll('a') : [];

    // Close menu helper (supports checkbox-driven menu)
    function closeMobileMenu() {
        if (checkboxToggle) checkboxToggle.checked = false;
        document.body.classList.remove('menu-open');
    }

    // Link behavior
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            const href = link.getAttribute('href') || '';
            if (href.startsWith('#')) {
                e.preventDefault();
                const targetId = href.substring(1);
                const targetSection = document.getElementById(targetId);
                closeMobileMenu();
                if (targetSection) {
                    targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            } else {
                closeMobileMenu();
            }
        });
    });

    // Toggle body scroll lock with checkbox
    if (checkboxToggle) {
        checkboxToggle.addEventListener('change', function() {
            const isOpen = this.checked === true;
            document.body.classList.toggle('menu-open', isOpen);
        });
    }

    // Click outside to close
    document.addEventListener('click', function(event) {
        if (!mobileNav) return;
        const isClickInside = mobileNav.contains(event.target);
        if (!isClickInside && checkboxToggle && checkboxToggle.checked) {
            closeMobileMenu();
        }
    });

    // Handle scroll hide/show of the mobile nav
    if (mobileNav) {
        let lastScrollTop = 0;
        window.addEventListener('scroll', function() {
            const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
            if (currentScroll > lastScrollTop && currentScroll > 70) {
                mobileNav.style.transform = 'translateY(-100%)';
            } else {
                mobileNav.style.transform = 'translateY(0)';
            }
            lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
        }, { passive: true });
    }
});