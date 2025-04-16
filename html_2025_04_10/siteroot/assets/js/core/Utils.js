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
