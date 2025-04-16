
import {isNested} from './core/Utils.js';

function initLoginUI() {
    const passFields = document.querySelectorAll('input[type="password"]');
    const passShowControls = document.querySelectorAll('label.showpass');
    const closebtn = document.getElementById('close_login');
    const modal = document.getElementById('modalbg');
    const loginForm = document.forms.signin;
    const registerForm = document.forms.signup;
    // const messageBox = document.getElementById('login-message');

    // Обработка кнопок показа пароля
    passShowControls.forEach(element => {
        element.addEventListener('mousedown', function () {
            this.firstElementChild.className = 'fas fa-eye-slash';
            this.classList.add('shine');
            passFields.forEach(input => input.type = 'text');
        });
        element.addEventListener('mouseup', function () {
            this.firstElementChild.className = 'fas fa-eye';
            this.classList.remove('shine');
            passFields.forEach(input => input.type = 'password');
        });
        element.addEventListener('mouseleave', function () {
            this.firstElementChild.className = 'fas fa-eye';
            this.classList.remove('shine');
            passFields.forEach(input => input.type = 'password');
        });
    });

    // Закрытие модального окна
    if (closebtn && modal) {
        closebtn.addEventListener('click', () => {
            modal.remove();
        });
        modal.addEventListener('click', (e) => {
            if(!CoreUtils.isNested(e.target, document.getElementById('forms_holder')) && e.target.id != 'checkreg') modal.remove();
        });
    }

    // Отправка формы логина
    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(loginForm);
            const data = {
                login: formData.get('user'),
                pass: formData.get('password')
            };

            fetch('/api/', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ module: 'auth', action: 'login', parameters: {data: data} })
            })
            .then(res => res.json())
            .then(result => {
                if (result.status === 'success') {
                    console.log(result.message);
                    // messageBox.textContent = 'Добро пожаловать!';
                    // messageBox.classList.add('success');
                    setTimeout(() => {
                        modal.remove();
                        window.location.reload(); // или обновление интерфейса
                    }, 1000);
                } else {
                    console.log(result.message || 'Ошибка авторизации');
                    // messageBox.textContent = result.message || 'Ошибка авторизации';
                    // messageBox.classList.add('error');
                }
                CoreUtils.sendMessage(0, result.message);
            })
            .catch(err => {
                console.error(err);
                // messageBox.textContent = 'Ошибка соединения с сервером.';
                // messageBox.classList.add('error');
                CoreUtils.sendMessage(3, err);
            });
        });
    }
}

function getFormattedDate() {
    const now = new Date();

    const pad = (n) => n.toString().padStart(2, '0');

    const year = now.getFullYear();
    const month = pad(now.getMonth() + 1);
    const day = pad(now.getDate());
    const hours = pad(now.getHours());
    const minutes = pad(now.getMinutes());
    const seconds = pad(now.getSeconds());

    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

function sendMessage(type = 0, msg = null){
    message = {
        'level': type,
        'class': 'message',
        'source': 'User',
        'time': CoreUtils.getFormattedDate(),
        'message': (msg ?? 'Действие пользователя')
    };
    fetch('/api/', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ module: 'console', action: 'add', parameters: {data: message} })
    })
    .then(response => response.json())
    .then(result => {
        console.log(result.message);
        updateConsole();
    })
    .catch(error => console.error('Ошибка отправки сообщений:', error));
}

window.CoreUtils = {
    isNested,
    initLoginUI,
    getFormattedDate,
    sendMessage
};


document.addEventListener("DOMContentLoaded", function () {
    function applyTheme(theme) {
        document.documentElement.setAttribute("data-theme", theme);
        localStorage.setItem("theme", theme);
        themeToggle.checked = (theme === "dark");
        themeToggle.parentElement.previousElementSibling.classList.toggle("highlited",theme === "light");
        themeToggle.parentElement.nextElementSibling.classList.toggle("highlited",theme === "dark");
    }

    function login(){
        fetch('/api/', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ module: 'auth', action: 'getmodal' })
        })
        .then(response => response.text())
        .then(html => {
            if (!document.getElementById('modalbg')) {
                document.body.insertAdjacentHTML('beforeend', html);
                const existingScript = document.querySelector('script[src="/assets/js/login.js"]');
                if(!existingScript){
                    const script = document.createElement('script');
                    script.src = '/assets/js/login.js';
                    script.onload = () => {
                        if (typeof CoreUtils.initLoginUI === 'function') CoreUtils.initLoginUI();
                    };
                    document.body.appendChild(script);
                } else {
                    if (typeof CoreUtils.initLoginUI === 'function') CoreUtils.initLoginUI();
                }
            }
        })
        .catch(err => console.error('Ошибка загрузки модального окна:', err));
    }

// INIT
    const themeToggle = document.getElementById("theme-toggle");
    const loginBtn = document.getElementById('login');
    // const dropdowns = document.getElementsByClassName('dropdown');

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