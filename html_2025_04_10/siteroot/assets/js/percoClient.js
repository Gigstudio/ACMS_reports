class PercoClient extends ApiClient {
    constructor() {
        super();
    }

    async getToken() {
        if (!this.config.perco) {
            console.error("Конфиг Perco не загружен");
            return;
        }
        try {
            const { perco_uri, perco_admin, perco_password } = this.config.perco;
            const response = await this.request(`${perco_uri}/system/auth`, "POST", {
                login: perco_admin,
                password: perco_password
            }, false);
            this.token = response?.token || null;
            console.log("Токен получен:", this.token);
        } catch (error) {
            console.error("Ошибка получения токена:", error);
        }
    }

    async getStaffList() {
        if (!this.token) await this.getToken();
        return this.request(`${this.config.perco.perco_uri}/users/staff/list`);
    }
}
