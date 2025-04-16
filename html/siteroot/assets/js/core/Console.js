import { APIClient } from './APIClient.js';

export const Console = {
    ready: false,
    update(filterLevel = null) {
        if (!filterLevel) {
            let selectedInput = document.querySelector('input[name="log_filter"]:checked');
            filterLevel = selectedInput ? selectedInput.value : localStorage.getItem("consoleFilter") || 0;
        }

        localStorage.setItem("consoleFilter", filterLevel);

        APIClient.send('console', 'get', {filter: filterLevel})
        .then(messages => {
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
        });
    },

    clear() {
        APIClient.send('console', 'clear')
        .then(result => {
            console.log(result.message);
            this.update();
        });
    },

    init() {
        const clearBtn = document.querySelectorAll('.winc-btn')[0];
        const minBtn = document.querySelectorAll('.winc-btn')[1];
        const closeBtn = document.querySelectorAll('.winc-btn')[2];
        const resizer = document.querySelector('#console_inject .resizer');

        const consoleEl = document.getElementById('console');
        const consoleWin = consoleEl?.parentElement;

        if (resizer) {
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

        minBtn?.addEventListener('click', () => {
            const state = consoleEl.classList.toggle('hidden') ? 'maximized' : 'minimized';
            localStorage.setItem("consoleState", state);
        });

        closeBtn?.addEventListener('click', () => {
            consoleWin.remove();
            localStorage.setItem('consoleState', 'minimized');
        });

        clearBtn?.addEventListener('click', () => this.clear());

        document.querySelectorAll('input[name="log_filter"]').forEach(input => {
            input.addEventListener('change', () => {
                this.update(input.value);
            });
        });

        this.update(localStorage.getItem('consoleFilter') || 0);
        this.ready = true;
    }
};
