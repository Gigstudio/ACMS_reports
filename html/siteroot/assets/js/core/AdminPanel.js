// AdminPanel.js
import { APIClient } from './APIClient.js';
import { showSnack } from './Utils.js';

export class AdminPanel {
  constructor() {
    // Навигация и панели
    this.navLinks   = document.querySelectorAll('.admin-nav a[data-target]');
    this.panels     = document.querySelectorAll('.admin-main .panel');

    // Контейнеры
    this.workersBox = document.getElementById('workers_table');
    this.usersBox   = document.getElementById('users_table');
    this.settingsBox= document.getElementById('settings_table');

    // Кнопки обновления
    this.btnRefreshWorkers = document.getElementById('refresh_workers');
    this.btnRefreshUsers   = document.getElementById('refresh_users');

    // бинды
    this.onNavClick          = this.onNavClick.bind(this);
    this.setActive           = this.setActive.bind(this);
    this.handleWorkerAction  = this.handleWorkerAction.bind(this);
  }

  init() {
    // вкладки
    this.navLinks.forEach(a => a.addEventListener('click', this.onNavClick));

    // начальная вкладка: hash → storage → workers
    const fromHash = location.hash ? ('sec-' + location.hash.replace('#','')) : null;
    const saved    = localStorage.getItem('admin.activePanel');
    const initial  = fromHash || saved || 'sec-workers';
    this.setActive(initial);

    // refresh-кнопки
    if (this.btnRefreshWorkers) this.btnRefreshWorkers.addEventListener('click', () => this.loadWorkers());
    if (this.btnRefreshUsers)   this.btnRefreshUsers.addEventListener('click',   () => this.loadUsers());

    // делегирование кликов по action-кнопкам внутри таблицы воркеров
    if (this.workersBox) {
      this.workersBox.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        this.handleWorkerAction({ currentTarget: btn });
      });
    }

    // первичная загрузка для активной вкладки
    if (initial === 'sec-workers') this.loadWorkers();
    if (initial === 'sec-users')   this.loadUsers();
  }

  onNavClick(e) {
    e.preventDefault();
    const target = e.currentTarget.dataset.target;
    this.setActive(target);
    history.replaceState(null, '', '#' + target.replace('sec-',''));
    if (target === 'sec-workers') this.loadWorkers();
    if (target === 'sec-users')   this.loadUsers();
  }

  setActive(panelId) {
    this.panels.forEach(p => p.classList.toggle('hidden', p.id !== panelId));
    this.navLinks.forEach(a => a.classList.toggle('active', a.dataset.target === panelId));
    localStorage.setItem('admin.activePanel', panelId);
  }

  /* ===== Workers ===== */
  async loadWorkers() {
    if (!this.workersBox) return;
    this.workersBox.innerHTML = 'Загрузка…';

    try {
      const response = await APIClient.request('/api/background/workers', 'GET');
      if (response.status !== 'success') {
        this.workersBox.innerHTML = `<div class="error">Ошибка: ${response.message || 'неизвестная'}</div>`;
        return;
      }
      const workers = (response.data && response.data.workers) ? response.data.workers : [];
      this.workersBox.innerHTML = AdminPanel.buildWorkerTable(workers);
    } catch (e) {
      console.error(e);
      this.workersBox.innerHTML = `<div class="error">Ошибка загрузки данных</div>`;
    }
  }

  static buildWorkerTable(workers) {
    if (!workers || !workers.length) return '<div>Нет доступных воркеров</div>';

    let html = `
      <table class="workers">
        <thead>
          <tr>
            <th>Имя</th>
            <th>Статус</th>
            <th>Описание</th>
            <th>Действия</th>
          </tr>
        </thead>
        <tbody>
    `;

    for (const w of workers) {
      const name   = w.name || '';
      const status = w.status || w.statename || '';
      const desc   = w.description || '-';

      html += `
        <tr>
          <td>${name}</td>
          <td>${status}</td>
          <td>${desc}</td>
          <td class="actions">
            <button class="icon-btn" data-action="start" data-name="${name}" title="Старт" aria-label="Старт" ${status === 'RUNNING' ? 'disabled' : ''}>
              <i class="fas fa-play" aria-hidden="true"></i>
            </button>
            <button class="icon-btn" data-action="stop" data-name="${name}" title="Стоп" aria-label="Стоп" ${status !== 'RUNNING' ? 'disabled' : ''}>
              <i class="fas fa-stop" aria-hidden="true"></i>
            </button>
            <button class="icon-btn" data-action="restart" data-name="${name}" title="Рестарт" aria-label="Рестарт">
              <i class="fas fa-redo" aria-hidden="true"></i>
            </button>
          </td>
        </tr>
      `;
    }

    html += '</tbody></table>';
    return html;
  }

  async handleWorkerAction(event) {
    const btn = event.currentTarget;
    const program = (btn.dataset.name || '').trim();
    const action  = btn.dataset.action;
    if (!program || !action) return;

    btn.disabled = true;
    try {
      const result = await APIClient.request('/api/background/workers', 'POST', { program, action });
      const ok = result.status === 'success';
      showSnack(result.message || (ok ? 'Готово' : 'Ошибка'), ok ? 'ok' : 'error');
      await this.loadWorkers();
    } catch (e) {
      console.error(e);
      showSnack('Ошибка выполнения действия', 'error');
    }
  }

  /* ===== Users (заглушка для будущего) ===== */
  async loadUsers() {
    if (!this.usersBox) return;
    this.usersBox.innerHTML = '<div class="text-muted">Раздел в разработке</div>';
  }
}
