import { isNested } from './Utils.js';
import { sendMessage } from './Utils.js';
import { showSnack } from './Utils.js';
import { translit } from './Utils.js';
import { APIClient } from './APIClient.js';

export const Auth = {
    initLoginUI() {
        const passFields = document.querySelectorAll('input[type="password"]');
        const passShowControls = document.querySelectorAll('label.showpass');
        const closebtn = document.getElementById('close_login');
        const modal = document.getElementById('modalbg');
        const loginForm = document.forms.signin;
        const registerForm = document.forms.signup;
        const nextBtn = document.getElementById('reg-next');
        const prevBtn = document.getElementById('reg-prev');
        const wrapper = document.querySelector('.reg-wrapper');
        const badgeInput = document.getElementById('badge');
        const hintText = document.querySelector('.inputset.hint .hint-text');
        const hintCard = document.querySelector('.inputset.hint .card-wrapper');
        const regUserInput = document.getElementById('reguser');

        let foundUser = null;

        // Валидация номера пропуска: только цифры
        badgeInput.addEventListener('input', function () {
            const value = this.value;
            const parent = this.closest('.input-holder');

            if (!/^[\d]*$/.test(value)) {
                this.value = value.replace(/\D/g, '');
                parent.classList.add('input-error');

                setTimeout(() => {
                    parent.classList.remove('input-error');
                }, 1200);
            } else {
                parent.classList.remove('input-error');
            }

            nextBtn.textContent = 'Найти';
            nextBtn.classList.remove('ready', 'disabled');
            nextBtn.disabled = false;
            hintText.innerHTML = value.length > 0 ? '' : '<br><i class="fas fa-info-circle"></i> Номер пропуска находится на его обратной стороне, внизу карточки';
            hintText.className = 'hint-text';
            hintText.parentElement.classList.toggle('successhint', false);
            hintText.parentElement.classList.toggle('errorhint', false);
            hintCard.classList.toggle('hidden', value.length > 0);
            foundUser = null;
        });

        const bageForm = document.getElementById('register');
        if(bageForm){
            bageForm.addEventListener('keydown', (e) => {
                const isBadgeStage = !document.querySelector('.reg-wrapper').classList.contains('stage2');
                if (e.key === 'Enter' && isBadgeStage) {
                    e.preventDefault();
                    if (!nextBtn.disabled && !nextBtn.classList.contains('ready')) nextBtn.click();
                }
            });
        }

        if (nextBtn && wrapper) {
            nextBtn.addEventListener('click', async () => {
                const badge = badgeInput.value.trim();

                if (!/^\d+$/.test(badge)) return;

                if (nextBtn.classList.contains('ready')) {
                    wrapper.classList.add('stage2');
                    return;
                }

                nextBtn.textContent = 'Поиск...';
                nextBtn.disabled = true;

                try {
                    const result = await APIClient.send('perco', 'lookup', { identifier: badge });

                    if (result.status === 'success') {
                        foundUser = result.data;
                        nextBtn.textContent = 'Далее';
                        nextBtn.classList.add('ready');
                        hintText.className = 'hint-text success';
                        hintText.parentElement.classList.toggle('successhint', true);
                        const divisionName = foundUser.division ? Object.values(foundUser.division)[0] : '';
                        const loginSuggestion = translit(foundUser.first_name?.[0] || '') + '.' + translit(foundUser.last_name || '');
                        hintText.innerHTML = `
                            <strong>Найден пользователь:</strong>
                            <br>
                            ${foundUser.first_name || ''}<br>
                            ${foundUser.last_name || ''}<br>
                            ${divisionName}<br>
                            <em>Логин по умолчанию:</em> ${loginSuggestion}
                        `;
                        if (regUserInput) regUserInput.value = loginSuggestion;
                        sendMessage(0, result.message || 'Пользователь PERCo-Web найден');
                        // console.log(result);
                    } else {
                        sendMessage(0, result.message || 'Пользователь не найден.');
                        // console.log(result.message || 'Пользователь не найден.');
                        hintText.className = 'hint-text error';
                        hintText.parentElement.classList.toggle('errorhint', true);
                        hintText.innerHTML = result.message || 'Пользователь не найден.';
                        nextBtn.textContent = 'Найти';
                    }
                } catch (err) {
                    sendMessage(3, result.message || 'Ошибка соединения с сервером.');
                    showSnack(err.message || 'Ошибка соединения с сервером. Попробуйте позднее.');
                    // console.error(err);
                    hintText.className = 'hint-text error';
                    hintText.parentElement.classList.toggle('errorhint', true);
                    hintText.innerHTML = 'Ошибка соединения с сервером.';
                    nextBtn.textContent = 'Найти';
                } finally {
                    nextBtn.disabled = false;
                }
            });
        }

        if (prevBtn && wrapper) {
            prevBtn.addEventListener('click', () => {
                wrapper.classList.remove('stage2');
            });
        }

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
                if (!isNested(e.target, document.getElementById('forms_holder')) && ['checkreg','regstage'].indexOf(e.target.id) == -1) {
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
                        sendMessage(0, result.message || 'Успешная авторизация');
                        showSnack('Добро пожаловать!');
                        // console.log(result);
                        setTimeout(() => {
                            modal.remove();
                        }, 2000);
                    } else {
                        sendMessage(3, result.message || 'Ошибка авторизации');
                        showSnack(result.message || 'Ошибка авторизации');
                        // console.log(result.message || 'Ошибка авторизации');

                        loginForm.user.parentElement.classList.remove('input-error', 'blink');
                        loginForm.user.parentElement.firstElementChild.title = '';
                        loginForm.password.parentElement.classList.remove('input-error', 'blink');
                        loginForm.password.parentElement.firstElementChild.title = '';

                        switch(result.reason){
                            case 'invalid_password':
                            case 'ldap_invalid_password':
                                loginForm.password.parentElement.classList.add('input-error', 'blink');
                                loginForm.password.parentElement.firstElementChild.title = result.message;
                                break;
                            case 'not_found':
                                loginForm.user.parentElement.classList.add('input-error', 'blink');
                                loginForm.user.parentElement.firstElementChild.title = result.message;
                                setTimeout(() => {
                                    const regToggle = document.getElementById('checkreg');
                                    if (regToggle) regToggle.checked = true;
                                }, 2000);
                                break;
                            case 'ldap_unreachable':
                                break;
                        }
                    }
                })
                .catch(err => {
                    sendMessage(3, err.message || 'Сбой авторизации');
                    showSnack(err.message || 'Сбой авторизации');
                    // console.error(err);
                });
            });
        }
    },
};
