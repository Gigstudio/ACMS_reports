export class StatusMonitor {
    constructor({url = 'api/status/stream'} = {}) {
        this.url = url;
        this.eventSource  = null;
    }

    start() {
        if (this.eventSource) return;
        this.eventSource = new EventSource(this.url);
        this.eventSource.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                this.updateStatusBar(data.services || {});
            } catch (e) {
                this.updateStatusBar({}, 'Ошибка формата данных');
            }
        };
        this.eventSource.onerror = (e) => {
            this.updateStatusBar({}, 'Потеря связи с сервером');
        };
    }

    stop() {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
    }

    updateStatusBar(services, errorMsg = null) {
        const serviceNodes = document.querySelectorAll('.statusbar-service[data-service]');
        serviceNodes.forEach(node => {
            const code = node.getAttribute('data-service');
            const light = node.querySelector('.statusbar-light');
            const name = node.querySelector('.statusbar-name');

            if (!light) return;

            let lastState = null;
            light.classList.forEach(cn => {
                if (cn.startsWith('status-')) lastState = cn.slice(7);
            });

            let status = 'warn';
            let message = '';
            if (errorMsg) {
                status = 'fail';
                message = 'Нет связи с сервером: ' + errorMsg;
            } else if (services && typeof services === 'object' && services[code]) {
                status = services[code].status || 'warn';
                message = services[code].message || '';
            } else {
                status = 'warn';
                message = 'Нет данных';
            }

            if (lastState !== status || light.title !== message) {
                light.classList.remove('status-ok', 'status-warn', 'status-fail');
                light.classList.add(`status-${status}`);
                light.title = message;
            }
            if (name) {
                name.title = message;
            }
        });

        const statusbar = document.querySelector('.statusbar');
        if (statusbar) {
            if (errorMsg) {
                statusbar.title = errorMsg;
                statusbar.classList.add('statusbar-error');
            } else {
                statusbar.title = '';
                statusbar.classList.remove('statusbar-error');
            }
        }
    }
}
