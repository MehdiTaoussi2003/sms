/**
 * Mobile Menu Functionality
 * Stock Management System (SMS)
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeMobileMenu();
});

function initializeMobileMenu() {
    const mobileToggle = document.getElementById('mobile-menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    
    if (!mobileToggle || !sidebar || !sidebarOverlay) {
        return; // Elements not found, exit
    }
    
    // Toggle mobile menu
    mobileToggle.addEventListener('click', function(e) {
        e.preventDefault();
        toggleMobileMenu();
    });
    
    // Close menu when clicking overlay
    sidebarOverlay.addEventListener('click', function() {
        closeMobileMenu();
    });
    
    // Close menu when clicking nav links (mobile only)
    const navLinks = document.querySelectorAll('.sidebar-nav a');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 1024) {
                closeMobileMenu();
            }
        });
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 1024) {
            closeMobileMenu();
        }
    });
    
    // Handle escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('mobile-open')) {
            closeMobileMenu();
        }
    });
}

function toggleMobileMenu() {
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    
    sidebar.classList.toggle('mobile-open');
    sidebarOverlay.classList.toggle('active');
    
    // Prevent body scrolling when menu is open
    if (sidebar.classList.contains('mobile-open')) {
        document.body.style.overflow = 'hidden';
        // Add focus trap for accessibility
        trapFocus(sidebar);
    } else {
        document.body.style.overflow = '';
        removeFocusTrap();
    }
}

function closeMobileMenu() {
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    
    sidebar.classList.remove('mobile-open');
    sidebarOverlay.classList.remove('active');
    document.body.style.overflow = '';
    removeFocusTrap();
}

// Accessibility: Focus trap for mobile menu
function trapFocus(element) {
    const focusableElements = element.querySelectorAll(
        'a[href], button, textarea, input[type="text"], input[type="radio"], input[type="checkbox"], select'
    );
    
    if (focusableElements.length === 0) return;
    
    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];
    
    element.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            if (e.shiftKey) {
                if (document.activeElement === firstElement) {
                    lastElement.focus();
                    e.preventDefault();
                }
            } else {
                if (document.activeElement === lastElement) {
                    firstElement.focus();
                    e.preventDefault();
                }
            }
        }
    });
    
    // Focus first element
    firstElement.focus();
}

function removeFocusTrap() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        // Remove event listeners (clone and replace to remove all listeners)
        const newSidebar = sidebar.cloneNode(true);
        sidebar.parentNode.replaceChild(newSidebar, sidebar);
        
        // Re-initialize the menu functionality for the cloned element
        setTimeout(initializeMobileMenu, 0);
    }
}