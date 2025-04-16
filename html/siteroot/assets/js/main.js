
import {Auth} from './core/Auth.js';
import {Console} from './core/Console.js';
import { APIClient } from './core/APIClient.js';

document.addEventListener("DOMContentLoaded", function () {
    function applyTheme(theme) {
        document.documentElement.setAttribute("data-theme", theme);
        localStorage.setItem("theme", theme);
        themeToggle.checked = (theme === "dark");
        themeToggle.parentElement.previousElementSibling.classList.toggle("highlited",theme === "light");
        themeToggle.parentElement.nextElementSibling.classList.toggle("highlited",theme === "dark");
    }

    function login(){
        APIClient.send('auth', 'getmodal', null, 'text')
        .then(html => {
            if (!document.getElementById('modalbg')) {
                document.body.insertAdjacentHTML('beforeend', html);
                const existingScript = document.querySelector('script[src="/assets/js/login.js"]');
                if(!existingScript){
                    const script = document.createElement('script');
                    script.src = '/assets/js/login.js';
                    script.onload = () => {
                        if (typeof Auth.initLoginUI === 'function') Auth.initLoginUI();
                    };
                    document.body.appendChild(script);
                } else {
                    if (typeof Auth.initLoginUI === 'function') Auth.initLoginUI();
                }
            }
        })
        .catch(err => console.error('Ошибка загрузки модального окна:', err));
    }

// INIT
    const themeToggle = document.getElementById("theme-toggle");
    const loginBtn = document.getElementById('login');
    // const dropdowns = document.getElementsByClassName('dropdown');
    const inject = document.getElementById('console_inject');
    if (typeof(inject) != 'undefined' && inject != null) {
        Console.init();
    }

// Обработчики
    themeToggle.addEventListener("change", function () {
        const newTheme = themeToggle.checked ? "dark" : "light";
        applyTheme(newTheme);
    });

    loginBtn.addEventListener('click', function(){
        login();
    });

    // for (let i = 0; i < dropdowns.length; i++) {
    //     dropdowns[i].addEventListener('click', function(){
    //         dropdowns[i].nextElementSibling.classList.toggle('collapsed');
    //         dropdowns[i].classList.toggle('opened');
    //         dropdowns[i].parentElement.classList.toggle('opened');
    //     });
    // }

// START
    const savedTheme = localStorage.getItem("theme") || "light";
    applyTheme(savedTheme);
});