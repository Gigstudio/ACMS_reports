export const APIClient = {
    /**
     * Отправка запроса к API
     * @param {string} module - Название модуля
     * @param {string} action - Действие
     * @param {object|null} data - Дополнительные параметры
     * @param {'json'|'text'|'auto'} expect - Что ожидаем получить (по умолчанию — автоопределение)
     * @returns {Promise<any>} - Результат запроса
     */
    async send(module, action, data = null, expect = 'auto') {
        try {
            const response = await fetch('/api/', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ module, action, data })
            });

            const contentType = response.headers.get('content-type');

            if (expect === 'text' || (expect === 'auto' && contentType && contentType.includes('text/html'))) {
                return await response.text();
            }

            if (expect === 'json' || (expect === 'auto' && contentType && contentType.includes('application/json'))) {
                return await response.json();
            }

            // fallback
            return await response.text();

        } catch (error) {
            console.error(`APIClient Error: ${error}`);
            throw error;
        }
    }
};
