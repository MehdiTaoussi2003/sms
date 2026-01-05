/**
 * Universal Mobile Menu Script for All Pages
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
        return; // Elements not found on this page
    }
    
    // Enhanced mobile menu toggle
    mobileToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        toggleMobileMenu();
    });
    
    // Touch support for mobile devices
    mobileToggle.addEventListener('touchstart', function(e) {
        e.preventDefault();
        e.stopPropagation();
        toggleMobileMenu();
    }, { passive: false });
    
    // Close menu when clicking overlay
    sidebarOverlay.addEventListener('click', function(e) {
        e.preventDefault();
        closeMobileMenu();
    });
    
    sidebarOverlay.addEventListener('touchstart', function(e) {
        e.preventDefault();
        closeMobileMenu();
    }, { passive: false });
    
    // Close menu when clicking nav links on mobile
    const navLinks = document.querySelectorAll('.sidebar-nav a');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 1024) {
                setTimeout(closeMobileMenu, 150);
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
    
    function toggleMobileMenu() {
        const isOpen = sidebar.classList.contains('mobile-open');
        if (isOpen) {
            closeMobileMenu();
        } else {
            openMobileMenu();
        }
    }
    
    function openMobileMenu() {
        sidebar.classList.add('mobile-open');
        sidebarOverlay.classList.add('active');
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.width = '100%';
        document.body.style.top = `-${window.scrollY}px`;
        
        // Focus management for accessibility
        const firstNavItem = sidebar.querySelector('.sidebar-nav a');
        if (firstNavItem) {
            setTimeout(() => firstNavItem.focus(), 300);
        }
    }
    
    function closeMobileMenu() {
        const scrollY = document.body.style.top;
        
        sidebar.classList.remove('mobile-open');
        sidebarOverlay.classList.remove('active');
        
        // Restore body scroll
        document.body.style.overflow = '';
        document.body.style.position = '';
        document.body.style.width = '';
        document.body.style.top = '';
        
        // Restore scroll position
        if (scrollY) {
            window.scrollTo(0, parseInt(scrollY || '0') * -1);
        }
    }
}

// Export for use in other scripts
window.SMS = window.SMS || {};
window.SMS.MobileMenu = {
    init: initializeMobileMenu
};