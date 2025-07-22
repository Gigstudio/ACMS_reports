import { APIClient } from './APIClient.js';
import { showSnack } from './Utils.js';
import { sendMessage } from './Utils.js';
import { ModalManager } from './ModalManager.js';

export class Auth {
    static initLoginUI(wrapper = document) {
        // Форма логина по id="login"
        const loginForm = wrapper.querySelector('form#login');
        if (!loginForm) {
            // console.error('Login form не найден в модалке');
            return;
        }

        loginForm.onsubmit = null;
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            Auth.loginHandler(loginForm);
        });

        // Обработчик крестика
        const closeBtn = wrapper.querySelector('#close_login');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => ModalManager.close());
        }

        // Показать пароль (если есть чекбокс и label)
        const passShowControls = wrapper.querySelectorAll('.showpass');
        const passFields = wrapper.querySelectorAll('input[type="password"], input[name="password"], input[name="regpassword"], input[name="confirmpassword"]');

        passShowControls.forEach(ctrl => {
            ctrl.addEventListener('mousedown', () => {
                // Меняем иконку
                const icon = ctrl.querySelector('i');
                if (icon) icon.className = 'fas fa-eye-slash';
                ctrl.classList.add('shine');
                passFields.forEach(input => input.type = 'text');
            });
            ctrl.addEventListener('mouseup', () => {
                const icon = ctrl.querySelector('i');
                if (icon) icon.className = 'fas fa-eye';
                ctrl.classList.remove('shine');
                passFields.forEach(input => input.type = 'password');
            });
            ctrl.addEventListener('mouseleave', () => {
                const icon = ctrl.querySelector('i');
                if (icon) icon.className = 'fas fa-eye';
                ctrl.classList.remove('shine');
                passFields.forEach(input => input.type = 'password');
            });
        });
    }

    static async loginHandler(form) {
        const userInput = form.querySelector('input[name="user"]');
        const passInput = form.querySelector('input[name="password"]');
        const userHolder = userInput.parentElement;
        const passHolder = passInput.parentElement;

        // Сброс старых ошибок
        userHolder.classList.remove('input-error', 'blink');
        userHolder.firstElementChild.title = '';
        passHolder.classList.remove('input-error', 'blink');
        passHolder.firstElementChild.title = '';

        const login = userInput?.value?.trim();
        const password = passInput?.value;

        try {
            const result = await APIClient.request('/api/auth/login', 'POST', { login, password });
            if (result.status === 'success') {
                sendMessage(0, result.message || 'Успешная авторизация');
                showSnack('Вход выполнен!');
                ModalManager.close();
                window.location.reload();
            } else {
                // sendMessage(3, result.message || 'Ошибка авторизации');
                showSnack(result.message || 'Ошибка авторизации');

                // Проверяем reason и extra.reason
                const reason = result.reason || (result.extra && result.extra.reason) || '';
                if (['missing_login', 'not_found'].includes(reason)) {
                    userHolder.classList.add('input-error', 'blink');
                    userHolder.firstElementChild.title = result.message;
                }
                if (['missing_password', 'invalid_password', 'ldap_invalid_password'].includes(reason)) {
                    passHolder.classList.add('input-error', 'blink');
                    passHolder.firstElementChild.title = result.message;
                }
            }
        } catch (err) {
            sendMessage(3, err.message || 'Сбой авторизации');
            showSnack('Ошибка соединения с сервером');
        }
    }
}
