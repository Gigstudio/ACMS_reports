export class ModalManager {
  static _escHandler = null;
  static _current = null; // ссылка на активный overlay

  static open(html, options = {}) {
    this.close(); // гарантируем единственность

    // Корень для модалок
    let root = document.getElementById('modal-root');
    if (!root) root = document.body;

    // Парсим HTML
    const temp = document.createElement('div');
    temp.innerHTML = (typeof html === 'string' ? html.trim() : '');
    let content = temp.firstElementChild;
    if (!content) return;

    // Если контент уже обёрнут в .login-wrapper — используем его
    let overlay;
    if (content.classList.contains('login-wrapper')) {
      overlay = content;
    } else {
      // Иначе создаём оверлей и вкладываем контент внутрь
      overlay = document.createElement('div');
      overlay.className = 'login-wrapper'; // совместимо с твоей версткой
      // если исходный контент — не блочный элемент, завернём его в div
      if (!(content instanceof HTMLElement)) {
        const wrap = document.createElement('div');
        wrap.innerHTML = html;
        content = wrap;
      }
      overlay.appendChild(content);
    }

    overlay.classList.remove('hidden');
    root.appendChild(overlay);
    this._current = overlay;

    // Клик снаружи закрывает, внутри — нет
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay || (e.target.id && e.target.id.toLowerCase().includes('close'))) {
        ModalManager.close(options.onClose);
      }
    });
    // Блокируем всплытие кликов внутри контента
    content.addEventListener('click', (e) => e.stopPropagation());

    // Esc для закрытия
    this._escHandler = (e) => {
      if (e.key === 'Escape') ModalManager.close(options.onClose);
    };
    document.addEventListener('keydown', this._escHandler);

    // Блокируем скролл фона
    document.documentElement.style.overflow = 'hidden';

    // onOpen
    if (typeof options.onOpen === 'function') options.onOpen(overlay);

    // Фокус — первый фокусируемый элемент
    const focusElem = overlay.querySelector('[autofocus], input, textarea, select, button, [tabindex]:not([tabindex="-1"])');
    if (focusElem) focusElem.focus();

    return overlay;
  }

  static close(onClose) {
    // снимаем Esc
    if (this._escHandler) {
      document.removeEventListener('keydown', this._escHandler);
      this._escHandler = null;
    }

    // возвращаем скролл
    document.documentElement.style.overflow = '';

    // удаляем активную модалку
    if (this._current && this._current.parentNode) {
      this._current.parentNode.removeChild(this._current);
    } else {
      // на всякий случай — старое поведение
      const wrapper = document.querySelector('.login-wrapper');
      if (wrapper) wrapper.remove();
    }
    this._current = null;

    if (typeof onClose === 'function') onClose();
  }
}
