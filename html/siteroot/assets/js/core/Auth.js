import { APIClient } from './APIClient.js';
import { showSnack, sendMessage } from './Utils.js';
import { ModalManager } from './ModalManager.js';

export class Auth {
    static badgeChanged = false;
    static loginChanged = false;
    static candidate = [];
    static _enterListenerAttached = false;

    static _refs = {
        viewSwitch: null,
        loginUser: null,
        loginEmail: null,
        loginHint: null,
        badgeInput: null,
        searchBtn: null,    // #reg-back  (кнопка "Найти")
        nextBtn: null,      // #reg-next  (кнопка "Далее")
        loginPreview: null, // #logincheck (сгенерированный/проверяемый логин)
        step1: null,        // .reg-step1
        step2: null,        // .reg-step2
        regSubmit: null,    // #reg-last (submit)
        regUser: null,      // #reguser
        regEmail: null      // #regemail
    }

    static setRefs(wrapper = document) {
        this._refs.viewSwitch   = wrapper.querySelector('#checkreg');
        this._refs.loginUser    = wrapper.querySelector('#username');
        // this._refs.loginEmail   = wrapper.querySelector('#useremail');
        this._refs.loginHint    = wrapper.querySelector('#login_hint');
        this._refs.badgeInput   = wrapper.querySelector('#badge');
        this._refs.searchBtn    = wrapper.querySelector('#reg-back');
        this._refs.nextBtn      = wrapper.querySelector('#reg-next');
        this._refs.loginPreview = wrapper.querySelector('#logincheck');
        this._refs.step1        = wrapper.querySelector('.reg-step1');
        this._refs.step2        = wrapper.querySelector('.reg-step2');
        this._refs.regSubmit    = wrapper.querySelector('#reg-last') || wrapper.querySelector('button[type="submit"]');
        this._refs.regUser      = wrapper.querySelector('#reguser');
        this._refs.regEmail     = wrapper.querySelector('#regemail');
        // console.log(this._refs);
    }

    static switchView(state = 0) {
        const { viewSwitch } = this._refs;
        viewSwitch.checked = state == 1;
    }

    static initAuthForm(wrapper = document) {
        this.initLoginUI(wrapper);
        this.initRegisterUI(wrapper);

        // Обработчик крестика
        const closeBtn = wrapper.querySelector('#close_login');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => ModalManager.close());
        }

        // Показать пароль
        const passShowControls = wrapper.querySelectorAll('.showpass');
        const passFields = wrapper.querySelectorAll('input[type="password"], input[name="password"], input[name="regpassword"], input[name="confirmpassword"]');

        passShowControls.forEach((ctrl) => {
            const on = () => {
                const icon = ctrl.querySelector('i');
                if (icon) icon.className = 'fas fa-eye-slash';
                ctrl.classList.add('shine');
                passFields.forEach((input) => (input.type = 'text'));
            }
            const off = () => {
                const icon = ctrl.querySelector('i');
                if (icon) icon.className = 'fas fa-eye';
                ctrl.classList.remove('shine');
                passFields.forEach((input) => (input.type = 'password'));
            }
            ctrl.addEventListener('mousedown', on);
            ctrl.addEventListener('mouseup', off);
            ctrl.addEventListener('mouseleave', off);
        });
    }

    static initLoginUI(wrapper = document) {
        const loginForm = wrapper.querySelector('form#login');
        if (!loginForm) return;
        loginForm.onsubmit = null;
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            this._loginHandler(loginForm);
        });
    }

    static initRegisterUI(wrapper = document) {
        const regForm = wrapper.querySelector('form#register');
        if (!regForm) return;
        this._attachEnterHandler();
        this.setRefs(wrapper);

        const { badgeInput, searchBtn, nextBtn, loginPreview, regSubmit } = this._refs;

        this._initBadgeInput(badgeInput, searchBtn);
        this._initLoginPreview(loginPreview, searchBtn);
        this._initSearchFlow(badgeInput, searchBtn, nextBtn, loginPreview);
        this._initRegisterSubmit(regForm, regSubmit);

        this._resetHintState();
        this._switchStep(1);
        this._changeButtonsState(0, 2, { search: 'Найти', next: 'Далее' });
        this._updateSearchButtonState();
    }

    static _changeButtonsState(enableMask, visibleMask, labels = {}) {
        const { searchBtn, nextBtn } = this._refs;
        if (searchBtn) {
            const en  = enableMask & 1;
            const vis = visibleMask & 1;
            searchBtn.disabled = !en;
            searchBtn.classList.toggle('disabled', !en);
            searchBtn.classList.toggle('hidden', !vis);
            if (labels.search) searchBtn.textContent = labels.search;
        }
        if (nextBtn) {
            const en  = (enableMask >> 1) & 1;
            const vis = (visibleMask >> 1) & 1;
            nextBtn.disabled = !en;
            nextBtn.classList.toggle('disabled', !en);
            nextBtn.classList.toggle('hidden', !vis);
            if (labels.next) nextBtn.textContent = labels.next;
        }
    }

    static _switchStep(step = 1) {
        const { step1, step2, regSubmit } = this._refs;
        const s1 = step === 1;
        if (step1) step1.classList.toggle('hidden', !s1);
        if (step2) step2.classList.toggle('hidden',  s1);
        if (regSubmit) {
            regSubmit.disabled = s1;
            regSubmit.classList.toggle('disabled', s1);
        }
    }

    static _returnToStep1() {
        const { loginPreview, regUser } = this._refs;

        this.candidate = {};
        if (loginPreview) loginPreview.value = '';
        if (regUser)      regUser.value = '';

        this._switchStep(1);
        this._changeButtonsState(0, 1, { search: 'Найти', next: 'Далее' });
        this._updateSearchButtonState();
    }

    static _updateSearchButtonState() {
        const { badgeInput, searchBtn, loginPreview } = this._refs;
        if (!searchBtn) return;

        const badge = (badgeInput?.value ?? '').trim();
        const login = (loginPreview?.value ?? '').trim();

        const badgeValid = /^[\d/]+$/.test(badge);
        const loginValid = /^[a-zA-Z0-9]+\.[a-zA-Z0-9]+$/.test(login);

        const canSearch = (this.badgeChanged && badgeValid) || (this.loginChanged && loginValid);

        searchBtn.disabled = !canSearch;
        searchBtn.classList.toggle('disabled', !canSearch);
        searchBtn.textContent = 'Найти';
    }

    static setLoginHint(textEl, error = false, message = 'Используйте учётную запись корпоративной сети ПНХЗ, если она у вас есть.') {
        const icon = message.length > 0 ? (error ? '<i class="fas fa-exclamation-triangle"></i>' : '<i class="fas fa-info-circle"></i>') : '';
        textEl.classList.toggle('error', !!error);
        textEl.innerHTML = `${icon} ${message}`;
    }

    static setRegHint(textEl, error = false, message = 'Номер пропуска находится на его обратной стороне') {
        const icon = message.length > 0 ? (error ? '<i class="fas fa-exclamation-triangle"></i>' : '<i class="fas fa-info-circle"></i>') : '';
        textEl.classList.toggle('error', !!error);
        textEl.innerHTML = `${icon} ${message}`;
    }

    static _resetHintState() {
        const reghint = document.querySelector('#stage1_hint .reghint');
        const loginCheckBlock = document.querySelector('#stage1_hint fieldset.inputset')?.parentElement;
        const hintText = reghint?.querySelector('.hint-text');

        if (reghint) reghint.classList.remove('hidden');
        if (loginCheckBlock) loginCheckBlock.classList.add('hidden');
        if (hintText) this.setRegHint(hintText, false);
    }

    static _showInputHint(target, message) {
        let hint = target.parentElement.querySelector('.input-hint');
        if (!hint) {
            hint = document.createElement('div');
            hint.className = 'input-hint';
            Object.assign(hint.style, {
                position: 'absolute',
                zIndex: '100',
                top: '0',
                left: '49px',
                width: '362px',
                height: '38px',
                lineHeight: '38px',
                color: '#640005ff',
                backgroundColor: 'rgba(238, 192, 192, 0.73)',
                borderRadius: '5px',
                marginTop: '3px',
                pointerEvents: 'none'
            });
            target.parentElement.appendChild(hint);
            this._showAndRemove(hint);
        }
        hint.textContent = message;
        hint.style.display = 'block';
    }

    static _hideInputHint(target) {
        const hint = target.parentElement.querySelector('.input-hint');
        if (hint) hint.remove();
    }

    static _showAndRemove(element, delay = 5000, fadeDuration = 500) {
        if (!element) return;

        element.style.opacity = '0';
        element.style.transition = `opacity ${fadeDuration}ms ease`;

        requestAnimationFrame(() => (element.style.opacity = '1'));

        setTimeout(() => {
            element.style.opacity = '0';
            setTimeout(() => element.remove(), fadeDuration);
        }, delay);
    }

    static _attachEnterHandler() {
        if (this._enterListenerAttached) return;
        this._enterListenerAttached = true;

        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Enter') return;

            const left = document.querySelector('#reg-back');
            const right = document.querySelector('#reg-next');
            const last = document.querySelector('#reg-last');

            const isEnabled = (btn) => btn && !btn.disabled && !btn.classList.contains('hidden');
            if (isEnabled(last))  return last.click();
            if (isEnabled(right)) return right.click();
            if (isEnabled(left))  return left.click();
        });
    }

    static _initBadgeInput(input, searchBtn) {
        if (!input) return;

        const applyState = () => {
            input.value = input.value.replace(/[^\d/]/g, '');
            const valid = /^[\d/]+$/.test(input.value.trim());
            this.badgeChanged = valid;
            this.loginChanged = false;
            if (searchBtn) {
                searchBtn.disabled = !valid;
                searchBtn.classList.toggle('disabled', !valid);
                searchBtn.textContent = 'Найти';
            }
        };

        const onValueChanged = () => {
            this._returnToStep1();
            applyState();
        };

        input.addEventListener('input', onValueChanged);
        input.addEventListener('change', onValueChanged);
        input.addEventListener('paste', () => setTimeout(onValueChanged, 0));

        input.addEventListener('keydown', (e) => {
            if (!e.keyCode) return;
            this._hideInputHint(input);
            if (e.key == 'Enter') { e.preventDefault(); return; }
            const allowed = ['Control', 'Alt', 'Shift', 'Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Home', 'End', '/'];
            const isDigit = e.key >= '0' && e.key <= '9';
            if (!(isDigit || allowed.includes(e.key))) {
                this._showInputHint(input, 'Допустимы только цифры (0–9) и символ "/".');
                e.preventDefault();
            }
        });
    }

    static _initLoginPreview(input, searchBtn) {
        if (!input) return;

        const applyState = () => {
            input.value = input.value.replace(/[^a-zA-Z0-9.]/g, '');
            const valid = /^[a-zA-Z0-9]+\.[a-zA-Z0-9]+$/.test(input.value.trim());
            this.badgeChanged = false;
            this.loginChanged = valid;
            this._changeButtonsState(1, 1, { search: 'Найти', next: 'Далее' });
            if (searchBtn) {
                searchBtn.disabled = !valid;
                searchBtn.classList.toggle('disabled', !valid);
                searchBtn.textContent = 'Проверить';
            }
        };

        const onValueChanged = () => {
            // this._returnToStep1();
            applyState();
        };

        input.addEventListener('input', onValueChanged);
        input.addEventListener('change', onValueChanged);
        input.addEventListener('paste', () => setTimeout(onValueChanged, 0));

        input.addEventListener('keydown', (e) => {
            if (!e.keyCode) return;
            this._hideInputHint(input);
            if (e.key == 'Enter') { e.preventDefault(); return; }
            const allowed = ['Control', 'Alt', 'Shift', 'Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Home', 'End'];
            const isLetter = /^[a-zA-Z]$/.test(e.key);
            const isDigit = /^[0-9]$/.test(e.key);
            const isDot = e.key === '.';
            const alreadyHasDot = input.value.includes('.');
            if (!(isLetter || isDigit || (isDot && !alreadyHasDot) || allowed.includes(e.key))) {
                this._showInputHint(input, 'Допустимы только латинские буквы, цифры и одна точка ".".');
                e.preventDefault();
            }
        });
    }

    static _initSearchFlow(badgeInput, searchBtn, nextBtn, loginPreview) {
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                this._changeButtonsState(0, 0);
                this._switchStep(2);
                if (this._refs.regSubmit) {
                    this._refs.regSubmit.disabled = false;
                    this._refs.regSubmit.classList.remove('disabled');
                }
            });
        }
        if (!searchBtn) return;
        searchBtn.addEventListener('click', async () => {
            const badge = (badgeInput?.value ?? '').trim();
            const login = (loginPreview?.value ?? '').trim();

            const wantBadgeSearch = this.badgeChanged && /^[\d/]+$/.test(badge);
            const wantLoginCheck  = this.loginChanged && /^[a-zA-Z0-9]+\.[a-zA-Z0-9]+$/.test(login);

            if (!wantBadgeSearch && !wantLoginCheck) return;

            if (this.badgeChanged && !/^[\d/]+$/.test(badge)) {
                badgeInput?.parentElement.classList.add('input-error', 'blink');
                showSnack('Неверный формат пропуска');
                return;
            }
            if (this.loginChanged && !/^[a-zA-Z0-9.]+$/.test(login)) {
                loginPreview?.parentElement.classList.add('input-error', 'blink');
                showSnack('Неверный формат логина');
                return;
            }
            if (this.loginChanged) this.candidate['login'] = login;
            const url  = this.badgeChanged ? '/api/auth/badge-check' : '/api/auth/login-check';
            // console.log(this.candidate);
            const data = this.badgeChanged ? { badge } : { candidate: this.candidate };

            badgeInput?.parentElement.classList.remove('input-error', 'blink');
            loginPreview?.parentElement.classList.remove('input-error', 'blink');

            const hintText = document.querySelector('#stage1_hint .reghint .hint-text');
            const reghint  = document.querySelector('#stage1_hint .reghint');
            const loginCheckBlock = document.querySelector('#stage1_hint fieldset.inputset')?.parentElement;

            searchBtn.textContent = 'Поиск...';
            this._changeButtonsState(0, 1);
            this._resetHintState();

            try {
                const result = await APIClient.request(url, 'POST', data);
                // console.log(result);

                if (result.status !== 'success') {
                    const reason = result.extra?.reason || '';
                    if (['missing_badge', 'not_found'].includes(reason)) {
                        badgeInput?.parentElement.classList.add('input-error', 'blink');
                        this.setRegHint(hintText, true, result.message || 'Не удалось найти сотрудника');
                        showSnack(result.message || 'Ошибка поиска');
                        // searchBtn.textContent = 'Найти';
                        this._changeButtonsState(1, 1, { search: 'Найти', next: 'Далее' });
                        return;
                    }
                    if (reason === 'login_taken') {
                        const detail = result.extra?.data || {};
                        this.candidate = detail.user;
                        if (loginPreview) loginPreview.value = detail.user.login || '';
                        if (reghint) reghint.classList.add('hidden');
                        if (loginCheckBlock) loginCheckBlock.classList.remove('hidden');
                        this._showCheckLoginHint(false, result.message, detail.check, detail.user.login);
                        searchBtn.textContent = 'Найти';
                        this._changeButtonsState(1, 1, { search: 'Найти', next: 'Далее' });
                        return;
                    }
                    showSnack(result.message || 'Ошибка');
                    // searchBtn.textContent = 'Найти';
                    this._changeButtonsState(1, 1, { search: 'Найти', next: 'Далее' });
                    return;
                }

                const detail = result.data?.user || {};
                const nextLogin = detail?.login || '';
                // console.log(result);
                if (nextLogin) this.candidate.login = nextLogin;

                if (this._refs.regUser)  this._refs.regUser.value = nextLogin;
                if (this._refs.regEmail) this._refs.regEmail.value = this.candidate.email || '';

                if (reghint) reghint.classList.add('hidden');
                if (loginCheckBlock) loginCheckBlock.classList.remove('hidden');
                this._showCheckLoginHint(true, result.message, detail, login);

                // searchBtn.textContent = 'Найти';
                this._changeButtonsState(2, 2, { search: 'Найти', next: 'Далее' });

                this.badgeChanged = false;
                this.loginChanged = false;
            } catch (err) {
                sendMessage(3, 'Ошибка поиска: ' + (err?.message || err));
                this.setRegHint(hintText, true, err?.message || 'Ошибка соединения с сервером');
                showSnack('Ошибка соединения');
            }
        });
    }

    static _initRegisterSubmit(form, submitBtn) {
        const toggleSubmit = (enable) => {
            if (!submitBtn) return;
            submitBtn.disabled = !enable;
            submitBtn.classList.toggle('disabled', !enable);
        };

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // console.log(this.candidate);

            const loginInput = form.querySelector('#reguser');
            const emailInput = form.querySelector('#regemail');
            const regPassInput = form.querySelector('#regpassword');
            const confirmInput = form.querySelector('#confirmpassword');
            const {loginUser, loginHint} = this._refs;

            const loginHolder = loginInput?.closest('.input-holder');
            const emailHolder = emailInput?.closest('.input-holder');
            const regPassHolder = regPassInput?.closest('.input-holder');
            const confirmHolder = confirmInput?.closest('.input-holder');

            // Очистка прошлых ошибок
            [loginHolder, emailHolder, regPassHolder, confirmHolder].forEach(holder => {
                if (!holder) return;
                holder.classList.remove('input-error', 'blink');
                const label = holder.querySelector('label');
                if (label) label.title = '';
            });

            if ((regPassInput?.value || '') !== (confirmInput?.value || '')) {
                if (confirmHolder) {
                    confirmHolder.classList.add('input-error', 'blink');
                    const label = confirmHolder.querySelector('label');
                    if (label) label.title = 'Пароли не совпадают';
                }
                showSnack('Пароли не совпадают');
                return;
            }

            const email = emailInput?.value?.trim();
            const password = regPassInput?.value;

            this.candidate.email = email;
            this.candidate.password = password;

            toggleSubmit(false);
            const oldText = submitBtn.textContent;
            submitBtn.textContent = 'Регистрация...';

            try {
                const result = await APIClient.request('/api/auth/register', 'POST', {
                    newData: this.candidate
                });
                console.log(result);
                console.log(this.candidate);

                if (result.status === 'success') {
                    console.log(loginHint);
                    loginUser.value = this.candidate.login;
                    this.setLoginHint(loginHint, false, `Добро пожаловать, ${this.candidate.name}! Теперь Вы можете войти в систему со своими учетными данными!`);
                    showSnack('Регистрация успешна!');
                    this.switchView(0);
                    // ModalManager.close();
                    // window.location.reload();
                    return;
                }
                showSnack(result.message || 'Ошибка регистрации');

                const reason = result.extra?.reason || '';
                if (reason === 'missing_login') {
                    loginHolder.classList.add('input-error', 'blink');
                    loginHolder.firstElementChild.title = result.message;
                }
                if (['missing_email', 'invalid_email'].includes(reason)) {
                    emailHolder.classList.add('input-error', 'blink');
                    emailHolder.firstElementChild.title = result.message;
                }
                if (['missing_password', 'invalid_password'].includes(reason)) {
                    regPassHolder.classList.add('input-error', 'blink');
                    regPassHolder.firstElementChild.title = result.message;
                }
                if (reason === 'confirm_fail') {
                    confirmHolder.classList.add('input-error', 'blink');
                    confirmHolder.firstElementChild.title = result.message;
                }
            } catch (err) {
                sendMessage(3, 'Сбой регистрации: ' + (err.message || err));
                showSnack('Ошибка соединения');
            } finally {
                submitBtn.textContent = oldText;
                toggleSubmit(true);
            }
        });
    }

    static async _loginHandler(form) {
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

        this._resetHintState();
        try {
            const result = await APIClient.request('/api/auth/login', 'POST', { login, password });
            if (result.status === 'success') {
                if (result.data?.token) localStorage.setItem('authToken', result.data.token);
                showSnack('Вход выполнен!');
                ModalManager.close();
                window.location.reload();
                return;
            }

            showSnack(result.message || 'Ошибка авторизации');

            // Проверяем reason и extra.reason
            const reason = result.extra?.reason || '';
            if (['missing_login', 'not_found'].includes(reason)) {
                userHolder.classList.add('input-error', 'blink');
                userHolder.firstElementChild.title = result.message;
            }
            if (['missing_password', 'invalid_password', 'ldap_invalid_password'].includes(reason)) {
                passHolder.classList.add('input-error', 'blink');
                passHolder.firstElementChild.title = result.message;
            }
        } catch (err) {
            sendMessage(3, err.message || 'Сбой авторизации');
            showSnack('Ошибка соединения с сервером');
        }
    }

    // static _showLoginHint(success, message, data, login) {
    //     const hintText = document.querySelector('#login_hint');
    //     // const name = data.name || 'Неизвестный пользователь';
    //     // const division = data.division || '';
    //     // const source = data.source === 'ldap' ? 'в корпоративной сети' : 'в локальной базе';

    //     const text = success
    //         ? `Логин "${login}" свободен`
    //         : `Логин "${login}" уже используется ${source} сотрудником ${name}${division ? ' (' + division + ')' : ''}. Вы можете изменить логин или войти под своей учётной записью.`;

    //     this.setLoginHint(hintText, !success, text);
    //     showSnack(message);
    // }
    
    static _showCheckLoginHint(success, message, data, login) {
        const hintText = document.querySelector('#hint-warn');
        const name = data.name || 'Неизвестный пользователь';
        const division = data.division || '';
        const source = data.source === 'ldap' ? 'в корпоративной сети' : 'в локальной базе';

        const text = success
            ? `Логин "${login}" свободен`
            : `Логин "${login}" уже используется ${source} сотрудником ${name}${division ? ' (' + division + ')' : ''}. Вы можете изменить логин или войти под своей учётной записью.`;

        this.setRegHint(hintText, !success, text);
        showSnack(message);
    }
}
