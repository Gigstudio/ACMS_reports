<h2 class="page-title"><?= $title ?></h2>
<div id="admin-panel" class="admin-layout">
    <aside class="admin-sidebar">
        <nav class="admin-nav">
            <a href="#workers" class="active" data-target="sec-workers">Фоновые процессы</a>
            <a href="#users" data-target="sec-users">Пользователи</a>
            <a href="#settings" data-target="sec-settings">Настройки</a>
        </nav>
    </aside>

    <!-- <div class="worker-list"> -->
        <!-- <h3>Фоновые процессы</h3> -->
        <!-- <div id="workers-table"> -->
            <!-- Сюда будет вставлен список воркеров -->
        <!-- </div> -->
    <!-- </div> -->

    <main class="admin-main">
        <section id="sec-workers" class="panel">
            <div class="panel-head">
                <h3>Фоновые процессы</h3>
                <div class="panel-tools">
                    <button class="btn small icon-btn" id="refresh_workers" title="Обновить"><i class="fas fa-sync" aria-hidden="true"></i></button>
                </div>
            </div>
            <div class="panel-body">
                <div id="workers_table"></div>
            </div>
        </section>

        <section id="sec-users" class="panel">
            <div class="panel-head">
                <h3>Пользователи</h3>
                <div class="panel-tools">
                    <button class="btn small icon-btn" id="refresh_users" title="Обновить"><i class="fas fa-sync" aria-hidden="true"></i></button>
                </div>
            </div>
            <div class="panel-body">
                <div id="users_table"></div>
            </div>
        </section>

        <section id="sec-settings" class="panel">
            <div class="panel-head">
                <h3>Настройки</h3>
            </div>
            <div class="panel-body">
                <div id="settings_table"></div>
            </div>
        </section>
    </main>

</div>

