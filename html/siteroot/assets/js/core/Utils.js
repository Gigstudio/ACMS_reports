import { Console } from './Console.js';
import { APIClient } from './APIClient.js';

export function isNested(child, parent) {
    let node = child;
    while (node) {
        if (node === parent) return true;
        node = node.parentElement;
    }
    return false;
}

export function getFormattedDate() {
    const now = new Date();
    const pad = n => n.toString().padStart(2, '0');
    return `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())} ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
}

export async function sendMessage(type = 0, msg = null){
    const message = {
        'level': type,
        'source': 'User',
        'message': msg ?? 'Действие пользователя'
    };
    try{
        const result = await APIClient.send('console', 'add', message);
        if (Console.ready) Console.update();
        console.log(result.message);
    } catch (error) {
        console.error('Ошибка отправки сообщений: ', error);
    }
}


