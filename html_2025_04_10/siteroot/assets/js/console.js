// function sendMessage(message = null){
//     if(!message){
//         message = {
//             'level': 1,
//             'class': 'message',
//             'source': 'User',
//             'time': CoreUtils.getFormattedDate(),
//             'message': 'Действие пользователя'
//         };
//     }
//     fetch('/api/', {
//         method: 'POST',
//         headers: { 'Content-Type': 'application/json' },
//         body: JSON.stringify({ module: 'console', action: 'add', parameters: {data: message} })
//     })
//     .then(response => response.json())
//     .then(result => {
//         console.log(result.message);
//         updateConsole();
//     })
//     .catch(error => console.error('Ошибка отправки сообщений:', error));
// }

function updateConsole(filterLevel = null) {
    if (!filterLevel) {
        let selectedInput = document.querySelector('input[name="log_filter"]:checked');
        filterLevel = selectedInput ? selectedInput.value : localStorage.getItem("consoleFilter") || 0;
    }

    localStorage.setItem("consoleFilter", filterLevel);
    document.querySelectorAll('input[name="log_filter"]').forEach(input => {
        input.checked = (input.value === filterLevel);
    });

    fetch('/api/', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ module: 'console', action: 'get', parameters: {filter: filterLevel} })
    })
    .then(response => response.json())
    .then(messages => {
        let consoleElement = document.getElementById('console');
        if (!consoleElement) return;

        consoleElement.innerHTML = '';

        messages.forEach(msg => {
            let messageElement = document.createElement('p');
            messageElement.classList.add('console-message', msg.class);
            messageElement.innerHTML = `<span class="msg-head ${msg.class}">${msg.source}</span> 
                                        <span class="msg-head ${msg.class}">[${msg.time}]</span> 
                                        <span class="msg-detail">${msg.message}</span>`;
            consoleElement.appendChild(messageElement);
        });
        consoleElement.scrollTop = consoleElement.scrollHeight;
    })
    .catch(error => console.error('Ошибка загрузки сообщений:', error));
}

function clearConsole() {
    fetch('/api/', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ module: 'console', action: 'clear' })
    })
    .then(response => response.json())
    .then(result => {
        console.log(result.message);
        updateConsole();
    })
    .catch(error => console.error('Ошибка очистки сообщений:', error));
}

document.addEventListener("DOMContentLoaded", function () {
    if (!inject) return;

    document.body.lastElementChild.insertAdjacentHTML('beforeend', inject);

    const consoleElement = document.getElementById('console');
    const consoleWindow = consoleElement?.parentElement;
    const consoleControls = document.getElementById('terminal_controls');
    const resizer = consoleWindow?.children[1];
    const filters = document.getElementById('terminal_filters');
    let minBtn, closeBtn, clearBtn;

    if (consoleControls) {
        [minBtn, closeBtn] = consoleControls.children;
        clearBtn = document.getElementById('clearbtn');
    }

    function applyConsoleState(state) {
        localStorage.setItem("consoleState", state);
        minBtn.title = state === 'maximized' ? 'maximize' : 'minimize';
        minBtn.firstElementChild.className = state === 'maximized' ? 'fas fa-window-maximize' : 'fas fa-window-minimize';
        consoleElement.classList.toggle('hidden', state === 'maximized');
    }

    let startY, startHeight;

    function initDrag(e) {
        startY = e.clientY;
        startHeight = parseInt(getComputedStyle(consoleElement).height, 10);
        document.documentElement.addEventListener('mousemove', doDrag);
        document.documentElement.addEventListener('mouseup', stopDrag);
    }

    function doDrag(e) {
        let newHeight = startHeight - e.clientY + startY;

        if (e.clientY >= window.innerHeight - 60) newHeight = 42;
        if (e.clientY <= window.innerHeight / 2) newHeight = window.innerHeight / 2 - 20;

        consoleElement.style.height = `${newHeight}px`;
        consoleElement.style.maxHeight = `${newHeight}px`;
    }

    function stopDrag() {
        document.documentElement.removeEventListener('mousemove', doDrag);
        document.documentElement.removeEventListener('mouseup', stopDrag);
    }

    resizer?.addEventListener('mousedown', initDrag);
    
    minBtn?.addEventListener('click', () => {
        const newState = minBtn.title === 'minimize' ? 'maximized' : 'minimized';
        applyConsoleState(newState);
    });
    clearBtn?.addEventListener('click', () =>{
        clearConsole();
    });

    closeBtn?.addEventListener('click', () => {
        consoleWindow.remove();
        localStorage.setItem('consoleState', 'minimized');
    });

    document.querySelectorAll('input[name="log_filter"]').forEach(input => {
        input.addEventListener('change', () => {
            updateConsole(input.value);
            // consoleElement.scrollTo(0, consoleElement.scrollHeight);
        });
    });

    applyConsoleState(localStorage.getItem('consoleState') || 'minimized');
    updateConsole(localStorage.getItem('consoleFilter') || 0);
});
