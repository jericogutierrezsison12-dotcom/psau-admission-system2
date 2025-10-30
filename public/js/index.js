document.addEventListener('DOMContentLoaded', function() {
    // Get the mobile navigation elements
    const mobileNav = document.querySelector('.mobile-nav');
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('#mobileNavContent');
    const navLinks = document.querySelectorAll('.mobile-nav .nav-link');

    // Function to close mobile menu
    function closeMobileMenu() {
        navbarCollapse.classList.remove('show');
        navbarToggler.classList.remove('collapsed');
        navbarToggler.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('menu-open');
    }

    // Add click event listener to each nav link
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            // If it's a hash link (section navigation)
            if (link.getAttribute('href').startsWith('#')) {
                e.preventDefault();
                const targetId = link.getAttribute('href').substring(1);
                const targetSection = document.getElementById(targetId);
                
                if (targetSection) {
                    // Close mobile menu
                    closeMobileMenu();
                    
                    // Smooth scroll to section
                    targetSection.scrollIntoView({ 
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            } else {
                // For non-hash links, just close the menu
                closeMobileMenu();
            }
        });
    });

    // Toggle body scroll when mobile menu is opened/closed
    navbarToggler.addEventListener('click', function() {
        const isExpanded = this.getAttribute('aria-expanded') === 'true';
        document.body.classList.toggle('menu-open', !isExpanded);
    });

    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
        const isClickInside = mobileNav.contains(event.target);
        
        if (!isClickInside && navbarCollapse.classList.contains('show')) {
            closeMobileMenu();
        }
    });

    // Prevent menu close when clicking inside the menu
    navbarCollapse.addEventListener('click', function(event) {
        event.stopPropagation();
    });

    // Handle scroll position
    let lastScrollTop = 0;
    
    window.addEventListener('scroll', function() {
        const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
        
        if (currentScroll > lastScrollTop && currentScroll > 70) {
            // Scrolling down & past navbar height
            mobileNav.style.transform = 'translateY(-100%)';
        } else {
            // Scrolling up
            mobileNav.style.transform = 'translateY(0)';
        }
        
        lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
    }, { passive: true });

    // Program search/filter on homepage
    const searchInput = document.getElementById('course-search');
    const courseGrid = document.querySelector('.course-grid');

    function normalize(text) {
        return (text || '').toString().toLowerCase().trim();
    }

    function getProgramItems() {
        if (!courseGrid) return [];
        const cards = courseGrid.querySelectorAll('.course-card');
        if (cards && cards.length) return Array.from(cards);
        return Array.from(courseGrid.children || []);
    }

    function applyFilter(query) {
        const items = getProgramItems();
        const q = normalize(query);
        items.forEach(el => {
            const text = normalize(el.textContent);
            const matches = q === '' || text.includes(q);
            el.style.display = matches ? '' : 'none';
        });
    }

    function debounce(fn, delay) {
        let t;
        return function(...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), delay);
        }
    }

    if (searchInput) {
        const debounced = debounce(() => applyFilter(searchInput.value), 100);
        applyFilter('');
        searchInput.addEventListener('input', debounced);
        searchInput.addEventListener('keyup', debounced);
    }
}); 