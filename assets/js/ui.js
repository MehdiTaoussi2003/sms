/**
 * SMS Design System - UI Interactions
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Menu Toggle
    const menuToggle = document.getElementById('mobile-menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    
    if (menuToggle && sidebar && sidebarOverlay) {
        const toggleMenu = () => {
            sidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('open');
            document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
        };

        menuToggle.addEventListener('click', (e) => {
            e.preventDefault();
            toggleMenu();
        });

        sidebarOverlay.addEventListener('click', toggleMenu);
    }
    
    // 2. Alert Dismissal (if any future alerts have close buttons)
    document.querySelectorAll('.alert-dismissible .close').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const alert = button.closest('.alert');
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 200);
        });
    });
});
