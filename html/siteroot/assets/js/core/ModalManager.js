export class ModalManager {
    static open(html, options = {}) {
        this.close();

        // Найти контейнер для модалок (modal-root) или добавить в body
        let root = document.getElementById('modal-root');
        if (!root) root = document.body;

        // Парсим html (на случай, если там несколько элементов)
        const temp = document.createElement('div');
        temp.innerHTML = html.trim();
        // Берём первый div c .login-wrapper (или первый ребенок)
        const wrapper = temp.querySelector('.login-wrapper') || temp.firstElementChild;

        if (!wrapper) return;

        wrapper.classList.remove('hidden');
        root.appendChild(wrapper);

        // Глобальный обработчик закрытия
        wrapper.addEventListener('click', (e) => {
            if (e.target === wrapper || (e.target.id && e.target.id.toLowerCase().includes('close'))) {
                ModalManager.close(options.onClose);
            }
        });

        if (typeof options.onOpen === 'function') options.onOpen(wrapper);

        // Фокус — первый input
        const focusElem = wrapper.querySelector('input[autofocus], input, textarea, select');
        if (focusElem) focusElem.focus();

        return wrapper;
    }

    static close(onClose) {
        const wrapper = document.querySelector('.login-wrapper');
        if (wrapper) wrapper.remove();
        if (typeof onClose === 'function') onClose();
    }
}
