class LDAPClient extends ApiClient {
    constructor() {
        super("/api/ldap"); // Базовый URL для LDAP
    }

    getCredentials() {
        return { login: "ldap_admin", password: "ldap_pass" };
    }

    getUser(username) {
        return this.request(`/users/${username}`);
    }

    searchUsers(query) {
        return this.request(`/users/search?q=${encodeURIComponent(query)}`);
    }

    getGroups() {
        return this.request("/groups");
    }
}
