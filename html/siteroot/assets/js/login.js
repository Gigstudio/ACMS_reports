// function initLoginUI() {
//     const passFields = document.querySelectorAll('input[type="password"]');
//     const passShowControls = document.querySelectorAll('label.showpass');
//     const closebtn = document.getElementById('close_login');
//     const modal = document.getElementById('modalbg');
//     const loginForm = document.forms.signin; //getElementById('login-form');
//     const registerForm = document.forms.signup; //getElementById('login-form');
//     // const messageBox = document.getElementById('login-message');

//     // Обработка кнопок показа пароля
//     passShowControls.forEach(element => {
//         element.addEventListener('mousedown', function () {
//             this.firstElementChild.className = 'fas fa-eye-slash';
//             this.classList.add('shine');
//             passFields.forEach(input => input.type = 'text');
//         });
//         element.addEventListener('mouseup', function () {
//             this.firstElementChild.className = 'fas fa-eye';
//             this.classList.remove('shine');
//             passFields.forEach(input => input.type = 'password');
//         });
//         element.addEventListener('mouseleave', function () {
//             this.firstElementChild.className = 'fas fa-eye';
//             this.classList.remove('shine');
//             passFields.forEach(input => input.type = 'password');
//         });
//     });

//     // Закрытие модального окна
//     if (closebtn && modal) {
//         closebtn.addEventListener('click', () => {
//             modal.remove();
//         });
//         modal.addEventListener('click', (e) => {
//             console.log(e.target);
//             console.log(isNested(e.target, document.getElementById('forms_holder')));
//             // modal.remove();
//         });
//     }

//     // Отправка формы логина
//     if (loginForm) {
//         loginForm.addEventListener('submit', function (e) {
//             e.preventDefault();
//             const formData = new FormData(loginForm);
//             const data = {
//                 module: 'auth',
//                 action: 'login',
//                 login: formData.get('login'),
//                 pass: formData.get('pass')
//             };

//             fetch('/api/', {
//                 method: 'POST',
//                 headers: { 'Content-Type': 'application/json' },
//                 body: JSON.stringify({ module: 'authentication', action: 'login' })
//             })
//             .then(res => res.json())
//             .then(result => {
//                 if (result.status === 'success') {
//                     messageBox.textContent = 'Добро пожаловать!';
//                     messageBox.classList.add('success');
//                     setTimeout(() => {
//                         modal.remove();
//                         window.location.reload(); // или обновление интерфейса
//                     }, 1000);
//                 } else {
//                     messageBox.textContent = result.message || 'Ошибка авторизации';
//                     messageBox.classList.add('error');
//                 }
//             })
//             .catch(err => {
//                 console.error(err);
//                 messageBox.textContent = 'Ошибка соединения с сервером.';
//                 messageBox.classList.add('error');
//             });
//         });
//     }
// }

// window.initLoginUI = initLoginUI;
