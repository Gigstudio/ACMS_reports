import { isNested } from './Utils.js';

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

                fetch('/api/', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ module: 'auth', action: 'login', parameters: { data } })
                })
                .then(res => res.json())
                .then(result => {
                    if (result.status === 'success') {
                        console.log(result.message);
                        setTimeout(() => {
                            modal.remove();
                            window.location.reload();
                        }, 1000);
                    } else {
                        console.log(result.message || 'Ошибка авторизации');
                    }
                    // можно позже перенести в APIClient.sendMessage
                })
                .catch(err => {
                    console.error(err);
                });
            });
        }
    },

    showLoginModal() {
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
                if (!existingScript) {
                    const script = document.createElement('script');
                    script.src = '/assets/js/login.js';
                    script.onload = () => Auth.initLoginUI();
                    document.body.appendChild(script);
                } else {
                    Auth.initLoginUI();
                }
            }
        })
        .catch(err => console.error('Ошибка загрузки модального окна:', err));
    }
};
