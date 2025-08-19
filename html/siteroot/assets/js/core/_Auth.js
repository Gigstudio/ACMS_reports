import { APIClient } from './APIClient.js';
import { showSnack } from './Utils.js';
import { sendMessage } from './Utils.js';
import { ModalManager } from './ModalManager.js';

export class _Auth {
    static modalWindow;
    static loginForm;
    static registerForm;

    static badgeChanged = false;
    static loginChanged = false;
    static candidate = [];

    static initModal(wrapper = document) {
        this.modalWindow = this.initControls(wrapper);//{wrapper};
        this.loginForm = this.initLoginUI(wrapper);
        this.registerForm = this.initRegisterUI(wrapper);

        console.log(this.modalWindow);
    }

    static initControls(wrapper) {
        const modalWindow = wrapper;
        const closeBtn = wrapper.querySelector('#close_login');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => ModalManager.close());
        }

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

    static initLoginUI(wrapper = document) {
        const loginForm = wrapper.querySelector('form#login');
        if (!loginForm) return;

        loginForm.onsubmit = null;
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            console.log('LoginForm Submitted');
            this.loginHandler(loginForm);
        });
        return loginForm;
    }

    static initRegisterUI(wrapper = document) {
        const regForm = wrapper.querySelector('form#register');
        if (!regForm) return;

        // const badgeInput = regForm.querySelector('#badge');
        // const badgeSearch = regForm.querySelector('#reg-back');
        // const badgeNext = regForm.querySelector('#reg-next');
        // const loginPreview = regForm.querySelector('#logincheck');

        // const step1 = regForm.querySelector('.reg-step1');
        // const step2 = regForm.querySelector('.reg-step2');

        // const regBack = regForm.querySelector('#reg-prev');
        // const regSubmit = regForm.querySelector('button[type="submit"]');

        // this._initBadgeInput(badgeInput, badgeSearch);
        // this._initLoginCheckInput(loginPreview, badgeSearch);
        // this._initBadgeSearch(badgeInput, badgeSearch, badgeNext, loginPreview, step1, step2);
        regForm.onsubmit = null;
        regForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            console.log('RegForm Submitted');
            // this.regHandler(regForm);
        });

        // this._resetHintState();
        return regForm;
    }

}