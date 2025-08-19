import { AppConsole } from './AppConsole.js';
import { APIClient } from './APIClient.js';

export function isNested(child, parent) {
  if (!child || !parent) return false;
  if (parent.contains) return parent.contains(child);
  // fallback
  let node = child;
  while (node) {
    if (node === parent) return true;
    node = node.parentElement;
  }
  return false;
}

export function getFormattedDate(date = new Date()) {
  const pad = n => n.toString().padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
}

export async function sendMessage(type = 0, msg = null) {
  const message = {
    level: type,
    source: 'User',
    message: msg ?? 'Действие пользователя',
  };
  try {
    const result = await APIClient.request('/api/console', 'POST', message);
    if (AppConsole?.ready) AppConsole.update();
    return result;
  } catch (error) {
    console.error('Ошибка отправки сообщений: ', error);
    return { status: 'error', message: error?.message || 'sendMessage failed' };
  }
}

let __snackbarTimer = null;
export function showSnack(msg) {
  let x = document.getElementById('snackbar');
  if (!x) {
    x = document.createElement('div');
    x.id = 'snackbar';
    document.body.appendChild(x);
  }

  // безопасный текст
  x.textContent = msg ?? '';

  // сброс предыдущей анимации/таймера
  x.classList.remove('toast');
  if (__snackbarTimer) {
    clearTimeout(__snackbarTimer);
    __snackbarTimer = null;
  }

  // запуск
  // форсим reflow, чтобы класс сработал повторно
  // eslint-disable-next-line no-unused-expressions
  x.offsetHeight;
  x.classList.add('toast');

  __snackbarTimer = setTimeout(() => {
    x.classList.remove('toast');
    __snackbarTimer = null;
  }, 3000);
}

export function translit(str) {
  return (str || '').toLowerCase()
    .replace(/а/g, 'a').replace(/б/g, 'b').replace(/в/g, 'v')
    .replace(/г/g, 'g').replace(/д/g, 'd').replace(/е/g, 'e')
    .replace(/ё/g, 'e').replace(/ж/g, 'zh').replace(/з/g, 'z')
    .replace(/и/g, 'i').replace(/й/g, 'y').replace(/к/g, 'k')
    .replace(/л/g, 'l').replace(/м/g, 'm').replace(/н/g, 'n')
    .replace(/о/g, 'o').replace(/п/g, 'p').replace(/р/g, 'r')
    .replace(/с/g, 's').replace(/т/g, 't').replace(/у/g, 'u')
    .replace(/ф/g, 'f').replace(/х/g, 'h').replace(/ц/g, 'ts')
    .replace(/ч/g, 'ch').replace(/ш/g, 'sh').replace(/щ/g, 'sch')
    .replace(/ъ/g, '').replace(/ы/g, 'y').replace(/ь/g, '')
    .replace(/э/g, 'e').replace(/ю/g, 'yu').replace(/я/g, 'ya')
    .replace(/[^a-z0-9]/g, '');
}
