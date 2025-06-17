# Итоговая сводка архитектуры и зависимостей (2025-06-13)
## **1. Слои приложения (High-Level Overview)**
- Infrastructure
Хранение, загрузка, миграции, клиенты БД, внешние сервисы (PERCo-Web, LDAP).

- Domain
Основные бизнес-объекты и сервисы (User, PercoUser, Division, Company, Event).

- Core
Ядро фреймворка: Request, Response, Router, ErrorHandler, Event, Config, AssetManager, Block, Renderer.

- Presentation
Контроллеры, шаблоны (View), утилиты визуализации, MenuBuilder, FontManager.

- Contracts
Интерфейсы всех ключевых абстракций (Database, EventLogger, т.п.).

## **2. Ключевые модули и зависимости**
### 2.1. Ядро (Core)
- Request
Инкапсулирует все данные HTTP-запроса, реализует детекторы типа (GET/POST/API/AJAX), работу с route/query/body.

- Response
Унифицированная отдача данных: json, html, text, file download, статусы, заголовки, запрет повторной отправки.

- Router
Маршрутизация, сопоставление route patterns и вызов action (с передачей объекта Request).

- Controller (Base)
Родитель всех UI и API контроллеров: render(), json(), setStatus(), isApiRequest().

- Renderer
Обеспечивает безопасность и изоляцию рендера, обрабатывает ошибки шаблонов через GeneralException.

- Block
Базовый строитель блоковой структуры UI, поддерживает вложенность, собственные ассеты.

- AssetManager
Централизует стили и скрипты для всех блоков/шаблонов.

- ViewHelper
Единая точка доступа для ассетов, меню, шрифтов, конфига во View.

- MenuBuilder / FontManager
Генерация меню по ролям и настройка подключения шрифтов.

### 2.2. Логирование и события
- Event
Статический интерфейс: log(), setLogger(), getTitle(), getClass().
Описания событий — через config/events.php, поддержка кэширования.
Логгеры (FileEventLogger, др.) реализуют EventLoggerInterface.

- FileEventLogger
Запись событий в файл (JSON-line). Путь — через конструктор.

- EventLoggerInterface
Определяет API для логгеров: log(), и пр.

### 2.3. Обработка ошибок
- ErrorHandler
Централизует ловлю, классификацию, логирование и рендер ошибок в любом контексте (API/CLI/UI).
Генерирует payload для MessageController/UI, всегда вызывает Event::log.

- GeneralException
Кастомные исключения с дополнительными полями/деталями (short/detail/trace).

### 2.4. Данные и БД
- Database (abstract)
Базовые CRUD и транзакции.
Потомки: MySQLClient, FirebirdClient, SQLiteClient реализуют специфику.

- DatabaseClientInterface
Унификация доступа к данным (exec, value, get, first, massInsert, ...).

- DbSchemaManager
Миграции, сверка и создание схемы по файлам config/dbschema.

### 2.5. Доменные сервисы и репозитории
- User, PercoUser, Division, Position, Company
Сущности (Entity), репозитории (например, MySQLUserRepository), сервисы (UserService).

- PERCoWebClient, LDAPClient
Интеграция с внешними системами, работа с API/AD.

### 2.6. API и Console
- ApiController (Base)
Стандартизирует работу всех API (json-ответы через ApiAnswer, requireAuth, requireRole, abort).

- ApiAnswer
Унифицированная структура ответа: status, code, message, data, extra.

- ConsoleController
(В стадии доработки) API-интерфейс для backend-консоли: get, add, clear.

- Console (class)
Хранение и обработка сообщений через сессию PHP. Методы add, get, clear, строгая работа через этот класс.

### 2.7. Presentation (View/UI)
- Шаблоны
Layouts: layouts/main, layouts/auth и др.
Blocks: blocks/error, blocks/console, blocks/report, ...
Partials: partials/menu, partials/userinfo, partials/footer и т.п.

- MessageController
Специализированный контроллер для красивого вывода ошибок/сообщений в UI (title, message, detail).

## 3. Потоки и взаимодействия
### a. Входящий HTTP-запрос
Request → Router (match route) → Controller (UI/API)

- UI: Controller → Renderer/Block/ViewHelper → Response::html

- API: Controller (через ApiController) → ApiAnswer → Response::json

### b. Ошибки
- ErrorHandler ловит любые ошибки/исключения (в т.ч. ранние)

- Error → Event::log (через FileEventLogger) → (UI: MessageController → Block::error → Response::html)
                    (API: GeneralException → ApiAnswer[status=error] → Response::json)

### c. Консоль
- Все операции через ConsoleController (API):
  add → Console::add (в сессию)
  get → Console::get (из сессии)
  clear → Console::clear (очистка в сессии)

- На фронте: fetch/await — отрисовка новой ленты сообщений без polling.

### d. Event и логгирование
- Любое действие уровня info/warning/error → Event::log → FileEventLogger (или др. логгер)

- Описания (title/class) — из config/events.php

### e. Миграции и структура БД
- При запуске: DbSchemaManager сверяет схему, создает/модифицирует таблицы, заносит служебные записи.

- Таблицы: users, perco_users, divisions, companies, positions, roles, user_profiles, db_settings, db_migrations.

## 4. Зависимости (направления связи)
- Controller зависит только от Core (Renderer, Response, ErrorHandler) и Domain (сервисы, репозитории).

- View использует только ViewHelper/AssetManager/MenuBuilder.

- Event и ErrorHandler не имеют зависимости от UI и Presentation.

- ConsoleController работает только через Console-класс (никакой работы напрямую с сессией).

## 5. Расширяемость
- Все новые контроллеры должны наследовать Controller (UI) или ApiController (API).

- Все новые события должны регистрироваться через Event.

- Новые шаблоны только через Block/ViewHelper, стили — через AssetManager.

- Любые дополнительные логгеры (например, email, Telegram, Kafka) должны реализовывать EventLoggerInterface.

