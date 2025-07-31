import { APIClient } from './APIClient.js';

export class AppConsole {
    static ready = false;

    static safeSetItem(key, value) {
        try {
            localStorage.setItem(key, value);
        } catch (e) {
            console.warn('localStorage.setItem failed:', e);
        }
    }

    static safeGetItem(key, fallback = null) {
        try {
            return localStorage.getItem(key) ?? fallback;
        } catch (e) {
            console.warn('localStorage.getItem failed:', e);
            return fallback;
        }
    }

    /**
     * Получить текущий уровень фильтра из radio/LS
     */
    static getFilterLevel() {
        const selectedInput = document.querySelector('input[name="log_filter"]:checked');
        const value = selectedInput ? selectedInput.value : this.safeGetItem("consoleFilter", 0);
        return parseInt(value, 10);
    }

    /**
     * Сохранить фильтр в LS
     */
    static setFilterLevel(level) {
        this.safeSetItem("consoleFilter", level);
    }

    static getFirstMsgId() {
        const value = this.safeGetItem("firstMsgId", 0);
        return parseInt(value, 10);
    }

    static setFirstMsgId(id) {
        this.safeSetItem("firstMsgId", id);
    }

    /**
     * Обновить консоль (REST)
     */
    static async update(filterLevel = null, firstMsgId = null) {
        filterLevel = (filterLevel === null || filterLevel === undefined) ? this.getFilterLevel() : parseInt(filterLevel, 10);
        this.setFilterLevel(filterLevel);

        firstMsgId = (firstMsgId === null || firstMsgId === undefined) ? this.getFirstMsgId() : parseInt(firstMsgId, 10);
        this.setFirstMsgId(firstMsgId);

        let url = '/api/console';
        const params = [];

        if (filterLevel > 0) {
            params.push('level=' + encodeURIComponent(filterLevel));
        }
        if (firstMsgId > 0) {
            params.push('fromId=' + encodeURIComponent(firstMsgId));
        }
        if (params.length > 0) {
            url += '?' + params.join('&');
        }

        try {
            const resp = await APIClient.request(url, 'GET');
            const raw = resp.data?.messages;

            const messages = raw && typeof raw === 'object'
                ? Object.values(raw)
                : [];
            const consoleElement = document.getElementById('console');
            if (!consoleElement) return;

            consoleElement.innerHTML = '';
            messages.forEach(msg => {
                const el = document.createElement('p');
                el.classList.add('console-message', msg.class);
                el.innerHTML = `<span class="msg-head ${msg.class}">${msg.source}</span> 
                                <span class="msg-head ${msg.class}">[${msg.time}]</span> 
                                <span class="msg-body">${msg.message}</span>
                                <span class="msg-detail">${msg.detail}</span>`;
                consoleElement.appendChild(el);
            });
            consoleElement.scrollTop = consoleElement.scrollHeight;
        } catch (error) {
            console.error('Ошибка загрузки сообщений консоли:', error);
        }
    }

    /**
     * Очистить консоль (REST)
     */
    static async clear() {
        try {
            const resp = await APIClient.request('/api/console', 'DELETE');
            console.log(resp.data.firstEventId);
            const firstId = resp.data?.firstEventId || 0;
            this.setFirstMsgId(firstId);
            this.update();
        } catch (error) {
            console.error('Ошибка очистки консоли:', error);
        }
    }

    /**
     * Добавить сообщение в консоль (REST, для теста)
     */
    static async add(msg) {
        try {
            await APIClient.request('/api/console', 'POST', msg);
            this.update();
        } catch (error) {
            console.error('Ошибка добавления сообщения в консоль:', error);
        }
    }

    /**
     * Получить количество сообщений
     */
    static async count() {
        try {
            const resp = await APIClient.request('/api/console/count', 'GET');
            return resp.count;
        } catch (error) {
            console.error('Ошибка получения количества сообщений:', error);
            return 0;
        }
    }

    /**
     * Получить последнее сообщение
     */
    static async last() {
        try {
            const resp = await APIClient.request('/api/console/last', 'GET');
            return resp.message;
        } catch (error) {
            console.error('Ошибка получения последнего сообщения:', error);
            return null;
        }
    }

    /**
     * Смена состояния (свернута / развернута)
     */
    static updateConsoleState(btn, target,  state) {
        target.classList.toggle('hidden', state != "maximized");
        btn.title = state != 'maximized' ? 'Развернуть' : 'Свернуть';
        btn.firstElementChild.className = state != 'maximized' ? 'fas fa-window-maximize' : 'fas fa-window-minimize';
    }

    /**
     * Инициализация управления размером консоли
     */
    static setupResizer(consoleEl, resizer) {
        if (!resizer || !consoleEl) return;

        let startY, startHeight;
        resizer.addEventListener('mousedown', (e) => {
            startY = e.clientY;
            startHeight = parseInt(getComputedStyle(consoleEl).height, 10);
            const onMove = (e) => {
                let newHeight = startHeight - e.clientY + startY;
                if (e.clientY >= window.innerHeight - 60) newHeight = 42;
                if (e.clientY <= window.innerHeight / 2) newHeight = window.innerHeight / 2 - 20;
                consoleEl.style.height = `${newHeight}px`;
                consoleEl.style.maxHeight = `${newHeight}px`;
            };
            const onUp = () => {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
            };
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        });
    }

    /**
     * Инициализация кнопок управления состоянием окна консоли
     */
    static setupButtons(consoleEl, consoleWin) {
        const [clearBtn, minBtn, closeBtn] = document.querySelectorAll('.winc-btn');

        const savedState = this.safeGetItem("consoleState", "maximized");
        this.updateConsoleState(minBtn, consoleEl, savedState);

        clearBtn?.addEventListener('click', () => this.clear());
        minBtn?.addEventListener('click', () => {
            const state = consoleEl.classList.toggle('hidden') ? 'minimized' : 'maximized';
            this.safeSetItem("consoleState", state)
            this.updateConsoleState(minBtn, consoleEl, state);
        });
        closeBtn?.addEventListener('click', () => {
            consoleWin?.remove();
            this.safeSetItem("consoleState", "minimized")
        });
    }

    /**
     * Инициализация слушателя фильтра
     */
    static setupFilter() {
        document.querySelectorAll('input[name="log_filter"]').forEach(input => {
            input.addEventListener('change', () => {
                this.update(input.value);
            });
        });
    }

    /**
     * Полная инициализация AppConsole
     */
    static init(state) {
        const consoleEl = document.getElementById('console');
        const consoleWin = consoleEl?.parentElement;
        const resizer = document.querySelector('#console_inject .resizer');

        this.setupResizer(consoleEl, resizer);
        this.setupButtons(consoleEl, consoleWin);
        this.setupFilter();

        this.update(this.getFilterLevel());
        this.ready = true;
    }
}
