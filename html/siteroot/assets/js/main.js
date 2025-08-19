import { ModalManager } from './core/ModalManager.js';
import { Auth } from './core/Auth.js';
import { AppConsole } from './core/AppConsole.js';
import { StatusMonitor } from './core/StatusMonitor.js';
import { APIClient } from './core/APIClient.js';
import { AdminPanel } from './core/AdminPanel.js';

// --- Тема ---
function applyTheme(theme) {
    document.documentElement.setAttribute("data-theme", theme);
    localStorage.setItem("theme", theme);

    const themeToggle = document.getElementById("theme-toggle");
    if (themeToggle) {
        themeToggle.checked = (theme === "dark");
        themeToggle.parentElement.previousElementSibling?.classList.toggle("highlited", theme === "light");
        themeToggle.parentElement.nextElementSibling?.classList.toggle("highlited", theme === "dark");
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const savedConsoleState = localStorage.getItem("consoleState") || "maximized";
    const inject = document.getElementById('console_inject');
    if (inject) AppConsole.init();

    const themeToggle = document.getElementById("theme-toggle");
    if (themeToggle) {
        themeToggle.addEventListener("change", function () {
            const newTheme = themeToggle.checked ? "dark" : "light";
            applyTheme(newTheme);
        });
    }
    const savedTheme = localStorage.getItem("theme") || "light";
    applyTheme(savedTheme);

    const monitor = new StatusMonitor();
    monitor.start();

    const adminpanel = new AdminPanel();
    adminpanel.init();

    document.addEventListener('click', async (e) => {
        const trigger = e.target.closest('[data-modal]');
        if (!trigger) return;
        e.preventDefault();

        const modalId = trigger.dataset.modal;
        const apiUrl  = trigger.dataset.modalUrl;
        if (!apiUrl) {
            console.error('data-modal-url не найден в элементе:', trigger);
            return;
        }

        try {
            const html = await APIClient.request(apiUrl, 'GET');
            ModalManager.open(html, {
                onOpen: (wrapper) => {
                    if (modalId === 'login-modal' && typeof Auth !== 'undefined') Auth.initAuthForm(wrapper);
                }
            });
        } catch (err) {
            console.error('Ошибка открытия модального окна:', err);
        }
    });
});
