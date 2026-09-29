(function () {
    'use strict';
    window.notify = function (type, message) {
        if (!window.Swal) return;
        Swal.fire({ toast: true, position: 'bottom-end', icon: type, title: message,
            showConfirmButton: false, timer: 3500, timerProgressBar: true });
    };
    document.addEventListener('DOMContentLoaded', function () {
        var root = document.documentElement;
        var themeToggle = document.getElementById('themeToggle');
        function updateTheme(theme) {
            root.dataset.bsTheme = theme;
            if (themeToggle) {
                var label = 'Switch to ' + (theme === 'dark' ? 'light' : 'dark') + ' theme';
                themeToggle.setAttribute('aria-label', label);
                themeToggle.title = label;
                themeToggle.innerHTML = '<i class="fas ' + (theme === 'dark' ? 'fa-sun' : 'fa-moon') + '" aria-hidden="true"></i>';
            }
            document.dispatchEvent(new CustomEvent('themechange', { detail: { theme: theme } }));
        }
        updateTheme(root.dataset.bsTheme);
        if (themeToggle) themeToggle.addEventListener('click', function () {
            var theme = root.dataset.bsTheme === 'dark' ? 'light' : 'dark';
            try { localStorage.setItem('theme', theme); } catch (e) { /* Theme works without storage. */ }
            updateTheme(theme);
        });
        var sidebar = document.getElementById('app-sidebar');
        var menuToggle = document.getElementById('menu-toggle');
        var backdrop = document.getElementById('sidebar-backdrop');
        var closeButton = document.getElementById('sidebar-close');
        var main = document.querySelector('.main-content');
        var mobile = window.matchMedia('(max-width: 991px)');
        function setNavigation(open, restoreFocus) {
            if (!sidebar) return;
            sidebar.classList.toggle('is-open', open);
            document.body.classList.toggle('nav-open', open);
            backdrop.hidden = !open;
            menuToggle.setAttribute('aria-expanded', String(open));
            menuToggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
            sidebar.inert = mobile.matches && !open;
            main.inert = mobile.matches && open;
            if (open) {
                sidebar.setAttribute('role', 'dialog');
                sidebar.setAttribute('aria-modal', 'true');
                closeButton.focus();
            } else {
                sidebar.removeAttribute('role');
                sidebar.removeAttribute('aria-modal');
                if (restoreFocus) menuToggle.focus();
            }
        }
        if (sidebar) {
            setNavigation(false, false);
            menuToggle.addEventListener('click', function () { setNavigation(true, false); });
            closeButton.addEventListener('click', function () { setNavigation(false, true); });
            backdrop.addEventListener('click', function () { setNavigation(false, true); });
            mobile.addEventListener('change', function () { setNavigation(false, false); });
            document.addEventListener('keydown', function (event) {
                if (!sidebar.classList.contains('is-open')) return;
                if (event.key === 'Escape') { event.preventDefault(); setNavigation(false, true); }
                if (event.key === 'Tab') {
                    var items = Array.from(sidebar.querySelectorAll('a[href], button')).filter(function (el) { return el.getClientRects().length; });
                    var first = items[0], last = items[items.length - 1];
                    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
                    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
                }
            });
        }
        var fieldSequence = 0;
        function enhanceTables(container) {
            container.querySelectorAll('label.form-label:not([for])').forEach(function (label) {
                var field = label.parentElement.querySelector('input:not([type="hidden"]), select, textarea');
                if (field) {
                    if (!field.id) field.id = 'workspace-field-' + (++fieldSequence);
                    label.htmlFor = field.id;
                }
            });
            container.querySelectorAll('.modal').forEach(function (modal) {
                var title = modal.querySelector('.modal-title');
                if (title) {
                    if (!title.id) title.id = modal.id + '-title';
                    modal.setAttribute('aria-labelledby', title.id);
                }
            });
            container.querySelectorAll('.table-responsive, .dataTables_scrollBody').forEach(function (element) {
                element.tabIndex = 0;
                element.setAttribute('role', 'region');
                element.setAttribute('aria-label', 'Data table. Scroll horizontally to see more columns.');
            });
        }
        enhanceTables(document);
        if (window.jQuery) {
            $(document).on('init.dt draw.dt', function () { enhanceTables(document); });
            $(document).ajaxComplete(function () { enhanceTables(document); });
        }
        var resizeTimer;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                if (window.jQuery && $.fn.dataTable) $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
            }, 150);
        });
    });
}());
