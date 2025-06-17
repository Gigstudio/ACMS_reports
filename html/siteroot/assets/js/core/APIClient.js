export class APIClient {
    static async request(url, method = 'GET', data = null) {
        const options = { method, headers: {} };
        if (data) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data);
        }
        const resp = await fetch(url, options);
        const contentType = resp.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return resp.json();
        }
        return resp.text();
    }
}
