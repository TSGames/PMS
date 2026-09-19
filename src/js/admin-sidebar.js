/**
 * Ein- und Ausklappen der Seitenleiste auf schmalen Geräten.
 *
 * Das Skript lief früher als Inline-Block in admin.php und brach auf der
 * Anmeldemaske ab, weil dort keine Seitenleiste existiert.
 */
(function () {
    'use strict';

    var toggle = document.getElementById('sidebar-toggle');
    var close = document.getElementById('sidebar-close');
    var sidebar = document.getElementById('admin-sidebar');

    if (!toggle || !sidebar) {
        return;
    }

    function openSidebar() {
        sidebar.classList.add('open');
        document.body.classList.add('sidebar-open');
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        document.body.classList.remove('sidebar-open');
    }

    toggle.addEventListener('click', function (event) {
        event.stopPropagation();
        if (sidebar.classList.contains('open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    if (close) {
        close.addEventListener('click', function (event) {
            event.stopPropagation();
            closeSidebar();
        });
    }

    document.addEventListener('click', function (event) {
        if (!sidebar.classList.contains('open')) {
            return;
        }
        if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
            closeSidebar();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });
})();
