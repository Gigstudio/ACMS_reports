import { AppConsole } from './AppConsole.js';

export class APIClient {
    static async request(url, method = 'GET', data = null) {
        const options = { method, headers: {} };
        if (data) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data);
        }
        const resp = await fetch(url, options);
        const contentType = resp.headers.get('content-type');
        let result;
        if (contentType && contentType.includes('application/json')) {
            result = resp.json();
        } else {
            result = await resp.text();
        }

        if (
            resp.status >= 400 ||
            (typeof result === 'object' && result !== null && (
                result.status === 'fail' || result.status === 'error' || result.error
            ))
        ) {
            if (AppConsole.ready) {
                AppConsole.update();
            } else {
                console.log("AppConsole is not defined");
            }
        }

        return result;
    }
}
