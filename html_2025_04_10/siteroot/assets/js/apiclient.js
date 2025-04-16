class ApiClient {
    constructor(configUrl = "/config/initconfig.json") {
        this.configUrl = configUrl;
        this.config = {};
        this.token = null;
        this.loadConfig();
    }

    async loadConfig() {
        try {
            const response = await fetch(this.configUrl);
            if (!response.ok) throw new Error("Ошибка загрузки конфига");
            this.config = await response.json();
            console.log("Конфиг загружен:", this.config);
        } catch (error) {
            console.error("Ошибка загрузки конфига:", error);
        }
    }

    async request(endpoint, method = "GET", data = null, useToken = true) {
        const headers = { "Content-Type": "application/json" };
        if (useToken && this.token) headers["Authorization"] = `Bearer ${this.token}`;

        const options = { method, headers };
        if (data) options.body = JSON.stringify(data);

        try {
            const response = await fetch(endpoint, options);
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || "Ошибка API");
            return result;
        } catch (error) {
            console.error("Ошибка запроса:", error);
            return null;
        }
    }
}
