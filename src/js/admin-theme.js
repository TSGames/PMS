(function () {
    'use strict';

    var KEY = 'adminTheme';
    var html = document.documentElement;

    function getStored() {
        try { return localStorage.getItem(KEY); } catch(e) { return null; }
    }

    function isDark() {
        var s = getStored();
        if (s === 'dark')  return true;
        if (s === 'light') return false;
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    function applyClass(mode) {
        html.classList.remove('dark', 'light');
        if (mode === 'dark')  html.classList.add('dark');
        if (mode === 'light') html.classList.add('light');
    }

    function save(mode) {
        try { localStorage.setItem(KEY, mode); } catch(e) {}
    }

    function updateUI() {
        var icon  = document.getElementById('theme-toggle-icon');
        var label = document.getElementById('theme-toggle-label');
        var dark  = isDark();
        if (icon)  icon.textContent  = dark ? '☀' : '☾';
        if (label) label.textContent = dark ? 'Heller Modus' : 'Dunkler Modus';
    }

    function toggle() {
        var next = isDark() ? 'light' : 'dark';
        applyClass(next);
        save(next);
        updateUI();
    }

    function init() {
        var btn = document.getElementById('theme-toggle');
        if (btn) btn.addEventListener('click', toggle);
        updateUI();

        var mq = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)');
        if (mq) {
            var handler = function() { if (!getStored()) updateUI(); };
            if (mq.addEventListener) mq.addEventListener('change', handler);
            else if (mq.addListener)  mq.addListener(handler);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
