    <h2>{{headmain}}</h2>
    <form name="test API" method="POST">
        <!-- <div class="form-group">
            <label for="endpoint">Endpoint</label>
            <input type="text" name="endpoint" id="endpoint" value="/users/staff/list">
        </div> -->
        <!-- <div class="form-group">
            <?php //var_dump($ldap); ?>
            <label class="hidden" for="token">Authorization token</label>
            <input class="hidden" type="text" name="api_authkey" id="token" value="{{perco_bearer}}">
        </div> -->
        <hr>
        <div>
            <button id="connect-perco" class="control btn primary">Connect PERCo</button>
            <button id="connect-ldap" class="control btn primary">Connect LDAP</button>
        </div>
        <div>
            <button id="test-perco" class="control btn primary">Тест PERCo</button>
            <button id="test-ldap" class="control btn primary">Тест LDAP</button>
        </div>
    </form>
    <div id="api-loader" style="display:none; position:fixed; top:10px; right:10px; background:#333; color:#fff; padding:5px 10px; border-radius:5px;">Загрузка...</div>
    <div>{{response}}</div>