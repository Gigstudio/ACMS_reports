import { isNested } from './Utils.js';
import { sendMessage } from './Utils.js';
import { APIClient } from './APIClient.js';

export const Auth = {
    initLoginUI() {
        const passFields = document.querySelectorAll('input[type="password"]');
        const passShowControls = document.querySelectorAll('label.showpass');
        const closebtn = document.getElementById('close_login');
        const modal = document.getElementById('modalbg');
        const loginForm = document.forms.signin;

        passShowControls.forEach(element => {
            element.addEventListener('mousedown', () => {
                element.firstElementChild.className = 'fas fa-eye-slash';
                element.classList.add('shine');
                passFields.forEach(input => input.type = 'text');
            });
            element.addEventListener('mouseup', () => {
                element.firstElementChild.className = 'fas fa-eye';
                element.classList.remove('shine');
                passFields.forEach(input => input.type = 'password');
            });
            element.addEventListener('mouseleave', () => {
                element.firstElementChild.className = 'fas fa-eye';
                element.classList.remove('shine');
                passFields.forEach(input => input.type = 'password');
            });
        });

        if (closebtn && modal) {
            closebtn.addEventListener('click', () => modal.remove());
            modal.addEventListener('click', (e) => {
                if (!isNested(e.target, document.getElementById('forms_holder')) && e.target.id !== 'checkreg') {
                    modal.remove();
                }
            });
        }

        if (loginForm) {
            loginForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const formData = new FormData(loginForm);
                const data = {
                    login: formData.get('user'),
                    pass: formData.get('password')
                };

                APIClient.send('auth', 'login', data)
                .then(result => {
                    if (result.status === 'success') {
                        // Ожидается JSON {status, message, data}
                        sendMessage(0, result.message || 'Успешная авторизация');
                        console.log(result);
                        setTimeout(() => {
                            modal.remove();
                            // window.location.reload();
                        }, 2000);
                    } else {
                        sendMessage(3, result.message || 'Ошибка авторизации');
                        console.log(result.message || 'Ошибка авторизации');
                    }
                })
                .catch(err => {
                    sendMessage(3, err.message || 'Сбой авторизации');
                    console.error(err);
                });
            });
        }
    },
};
