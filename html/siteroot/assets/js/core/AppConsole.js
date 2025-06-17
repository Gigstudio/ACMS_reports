import { APIClient } from './APIClient.js';

export class AppConsole {
    static ready = false;

    /**
     * Получить текущий уровень фильтра из radio/LS
     */
    static getFilterLevel() {
        let selectedInput = document.querySelector('input[name="log_filter"]:checked');
        return selectedInput ? selectedInput.value : localStorage.getItem("consoleFilter") || 0;
    }

    /**
     * Сохранить фильтр в LS
     */
    static setFilterLevel(level) {
        localStorage.setItem("consoleFilter", level);
    }

    /**
     * Обновить консоль (REST)
     */
    static async update(filterLevel = null) {
        if (filterLevel === null || filterLevel === undefined) {
            filterLevel = this.getFilterLevel();
        }
        this.setFilterLevel(filterLevel);

        let url = '/api/console';
        if (filterLevel) {
            url += '?level=' + encodeURIComponent(filterLevel);
        }

        try {
            const resp = await APIClient.request(url, 'GET');
            const messages = resp.data.messages || [];
            const consoleElement = document.getElementById('console');
            if (!consoleElement) return;
            consoleElement.innerHTML = '';
            messages.forEach(msg => {
                const el = document.createElement('p');
                el.classList.add('console-message', msg.class);
                el.innerHTML = `<span class="msg-head ${msg.class}">${msg.source}</span> 
                                <span class="msg-head ${msg.class}">[${msg.time}]</span> 
                                <span class="msg-detail">${msg.message}</span>`;
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
            await APIClient.request('/api/console', 'DELETE');
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
     * Полная инициализация AppConsole
     */
    static init() {
        // Кнопки управления
        const clearBtn = document.querySelectorAll('.winc-btn')[0];
        const minBtn = document.querySelectorAll('.winc-btn')[1];
        const closeBtn = document.querySelectorAll('.winc-btn')[2];
        const resizer = document.querySelector('#console_inject .resizer');
        const consoleEl = document.getElementById('console');
        const consoleWin = consoleEl?.parentElement;

        // Перетаскивание/resize консоли
        if (resizer && consoleEl) {
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

        // Кнопки
        minBtn?.addEventListener('click', () => {
            const state = consoleEl.classList.toggle('hidden') ? 'maximized' : 'minimized';
            localStorage.setItem("consoleState", state);
        });

        closeBtn?.addEventListener('click', () => {
            if (consoleWin) {
                consoleWin.remove();
                localStorage.setItem('consoleState', 'minimized');
            }
        });

        clearBtn?.addEventListener('click', () => this.clear());

        // Фильтр
        document.querySelectorAll('input[name="log_filter"]').forEach(input => {
            input.addEventListener('change', () => {
                this.update(input.value);
            });
        });

        // Первая инициализация консоли
        this.update(this.getFilterLevel());
        this.ready = true;
    }
}
