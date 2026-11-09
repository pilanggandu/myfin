import $ from 'jquery';
import { initIndonesianDatePickers } from './date-picker-id.js';
window.$ = window.jQuery = $;

function applyTheme(theme) {
    const isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    if (isDark) {
        document.documentElement.classList.add('dark');
        document.documentElement.setAttribute('data-theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        document.documentElement.setAttribute('data-theme', 'light');
    }
}

function updateThemeUI(theme) {
    document.querySelectorAll('[data-theme-value]').forEach(el => {
        const val = el.getAttribute('data-theme-value');
        const isActive = val === theme;
        if (el.tagName === 'INPUT' && el.type === 'radio') {
            el.checked = isActive;
        } else {
            if (isActive) {
                el.classList.add('active-theme', 'font-bold', 'bg-forest-light', 'text-forest');
                el.setAttribute('aria-selected', 'true');
            } else {
                el.classList.remove('active-theme', 'font-bold', 'bg-forest-light', 'text-forest');
                el.setAttribute('aria-selected', 'false');
            }
        }
    });

    const labelEl = document.getElementById('currentThemeLabel');
    if (labelEl) {
        labelEl.textContent = theme === 'dark' ? 'Gelap' : (theme === 'light' ? 'Terang' : 'Sistem');
    }
    const iconEl = document.getElementById('currentThemeIcon');
    if (iconEl) {
        iconEl.textContent = theme === 'dark' ? '🌙' : (theme === 'light' ? '☀️' : '💻');
    }
}

window.setArusTheme = function(theme) {
    try {
        localStorage.setItem('arus-theme', theme);
    } catch (e) {}

    applyTheme(theme);
    updateThemeUI(theme);

    const dropdown = document.getElementById('themeMenu');
    if (dropdown) {
        dropdown.classList.add('hidden');
    }
};

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
    const saved = localStorage.getItem('arus-theme') || 'system';
    if (saved === 'system') {
        applyTheme('system');
    }
});

function initSidebarLiveClock() {
    function tick() {
        const dateEl = document.getElementById('sidebarLiveDate');
        const timeEl = document.getElementById('sidebarLiveTime');
        if (!dateEl && !timeEl) return;
        
        const now = new Date();
        if (dateEl) {
            const dateStr = now.toLocaleDateString('id-ID', {
                weekday: 'long',
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            });
            dateEl.textContent = dateStr;
        }
        if (timeEl) {
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            timeEl.textContent = `${hours}:${minutes}:${seconds}`;
        }
    }
    tick();
    setInterval(tick, 1000);
}

document.addEventListener('DOMContentLoaded', function() {
    const saved = localStorage.getItem('arus-theme') || 'system';
    applyTheme(saved);
    updateThemeUI(saved);
    initSidebarLiveClock();
    initIndonesianDatePickers();
});
