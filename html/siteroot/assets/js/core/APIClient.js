// APIClient.js
import { AppConsole } from './AppConsole.js';

export class APIClient {
  static async request(url, method = 'GET', data = null) {
    const options = { method, headers: { 'Accept': 'application/json' } };

    const token = localStorage.getItem('authToken');
    if (token) options.headers['Authorization'] = `Bearer ${token}`;

    if (data) {
      options.headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(data);
    }

    const resp = await fetch(url, options);
    const contentType = resp.headers.get('content-type') || '';

    let result;
    try {
      if (contentType.includes('application/json')) {
        result = await resp.json();
      } else {
        result = await resp.text();
      }
    } catch (e) {
      // сервер прислал битый JSON
      result = { status: 'error', message: 'Invalid JSON response' };
    }

    if (
      resp.status >= 400 ||
      (result && typeof result === 'object' && (
        result.status === 'fail' || result.status === 'error' || result.error
      ))
    ) {
      if (AppConsole?.ready) AppConsole.update();
      else console.log('AppConsole is not defined');
    }

    return result;
  }
}
