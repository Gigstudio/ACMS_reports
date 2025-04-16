document.addEventListener("DOMContentLoaded", async function () {
    const perco = new PercoClient();
    // const ldap = new LDAPClient();

    document.getElementById("connect-perco").addEventListener("click", async () => {
        try {
            await perco.getToken();
            alert("Подключение к PERCo успешно! Токен обновлен.");
        } catch (error) {
            alert("Ошибка подключения к PERCo: " + error.message);
        }
    });

    document.getElementById("test-perco").addEventListener("click", async () => {
        const staffList = await perco.getStaffList();
        console.log("PERCo Staff List:", staffList);
    });

    // document.getElementById("connect-ldap").addEventListener("click", async () => {
    //     try {
    //         await ldap.refreshToken();
    //         alert("Подключение к LDAP успешно! Токен обновлен.");
    //     } catch (error) {
    //         alert("Ошибка подключения к LDAP: " + error.message);
    //     }
    // });
    
    // document.getElementById("test-ldap").addEventListener("click", async () => {
    //     const userInfo = await ldap.getUser("jdoe");
    //     console.log("LDAP User Info:", userInfo);
    // });
});
