// import { ModalManager } from './core/ModalManager.js';
// import { Auth } from './core/Auth.js';
import { AppConsole } from './core/AppConsole.js';
// import { APIClient } from './core/APIClient.js';

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

    // document.addEventListener('click', async (e) => {
    //     const trigger = e.target.closest('[data-modal]');
    //     if (!trigger) return;
    //     e.preventDefault();

    //     const modalId = trigger.dataset.modal;
    //     const apiModule = trigger.dataset.modalApi;
    //     const apiAction = trigger.dataset.modalAction;

    //     try {
    //         const html = await APIClient.send(apiModule, apiAction, null, '');
    //         ModalManager.open(html, {
    //             onOpen: (wrapper) => {
    //                 if (modalId === 'login-modal') Auth.initLoginUI(wrapper);
    //             }
    //         });
    //     } catch (err) {
    //         console.error('Ошибка открытия модального окна:', err);
    //     }
    // });
});
