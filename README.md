<a name="readme-top"></a>

<!-- PROJECT SHIELDS -->
<!--
*** I'm using markdown "reference style" links for readability.
*** Reference links are enclosed in brackets [ ] instead of parentheses ( ).
*** See the bottom of this document for the declaration of the reference variables
*** for contributors-url, forks-url, etc. This is an optional, concise syntax you may use.
*** https://www.markdownguide.org/basic-syntax/#reference-style-links
-->
[![Contributors][contributors-shield]][contributors-url]
[![Forks][forks-shield]][forks-url]
[![Stargazers][stars-shield]][stars-url]
[![Issues][issues-shield]][issues-url]
[![MIT License][license-shield]][license-url]



<!-- PROJECT LOGO -->
<br />
<div align="center">
      <h3 align="center">Цифровая платформа "Demo" для чемпионатов по веб-технологиям</h3>
    <br />
</div>



<!-- TABLE OF CONTENTS -->
<details>
  <summary>Оглавление</summary>
  <ol>
    <li>
      <a href="#about-the-project">О проекте</a>
      <ul>
        <li><a href="#the-main-functionality">Уникальность проекта</a></li>
        <li><a href="#the-main-functionality">Функционал</a></li>
      </ul>
    </li>
    <li>
        <a href="#the-main-functionality">Требования к развертыванию</a>
    </li>
    <li>
      <a href="#getting-started">Как развернуть</a>
      <ul>
           <li><a href="#the-main-functionality">Получение проекта</a></li>
           <li><a href="#the-main-functionality">Установка зависимостей</a></li>
           <li><a href="#the-main-functionality">Инициализация проекта</a></li>
           <li><a href="#the-main-functionality">Настройка конфигурации</a></li>
           <li><a href="#the-main-functionality">Применение миграций</a></li>
           <li><a href="#the-main-functionality">Проверка</a></li>
      </ul>
    </li>
    <li>
        <a href="#documentation">Дополнительно</a>
        <ul>
            <li><a href="#the-main-functionality">Технологический стек</a></li>
            <li><a href="#the-main-functionality">Будущие улучшения</a></li>
            <li><a href="#the-main-functionality">Контакты</a></li>
            <li><a href="#license">Лицензия</a></li>
        </ul>
    </li>
  </ol>
</details>



<!-- ABOUT THE PROJECT -->
## О проекте

Цифровая платформа **"Demo"** — это специализированное решение для организации и проведения чемпионатов по веб-технологиям в локальной сети колледжа. Проект разработан для автоматизации процессов управления соревнованиями, предоставления студентам удобной среды для выполнения заданий и обеспечения экспертов инструментами для контроля и оценки. "Demo" помогает студентам оттачивать навыки веб-разработки, а преподавателям — эффективно организовывать образовательные мероприятия.

### Уникальность проекта

Уникальность платформы "Demo" заключается в интеграции виртуальных хостингов для каждого студента, что позволяет выполнять задания непосредственно на сервере колледжа с централизованным хранением данных. Это обеспечивает доступ к работам с любого ПК в локальной сети, а также упрощает управление чемпионатами через модульную структуру этапов (например, создание API, разработка SPA, верстка и дизайн).

<!-- REQUIREMENTS -->
## Функционал

- **Для экспертов**:
  - Управление чемпионатами (создание, удаление, настройка).
  - Управление студентами и экспертами (добавление, удаление).
  - Настройка модулей чемпионата (этапы соревнования) и управление файлами.
- **Для студентов**:
  - Личный кабинет с доступом к файлам чемпионата.
  - Получение команды для подключения к виртуальному диску через cmd.
  - Доступ к базам данных через phpMyAdmin для выполнения заданий.
- **Для неавторизованных пользователей**:
  - Возможность авторизации в системе.

## Требования к развертыванию

- PHP версии 5.6.0 или выше (рекомендуется PHP 7.4+).
- MySQL для базы данных.
- Composer для управления зависимостями.
- Веб-сервер (Apache или Nginx).
- Git (для клонирования репозитория).
- Доступ к хостингу или серверу (например, Bluehost) с поддержкой SSH (желательно).

<!-- GETTING STARTED -->
## Как развернуть

### 1. Получение проекта

Вы можете развернуть проект двумя способами:

#### Через ZIP-архив
- Скачайте ZIP-архив проекта из репозитория.
- Разархивируйте его на локальном компьютере, убедившись, что структура папок (`frontend`, `backend`, `common`, `console`) сохранена.

#### Через GitHub
- Клонируйте репозиторий:
  ```bash
  git clone https://github.com/Semenov-Daniil/demo
  ```

### 2. Установка зависимостей
- Перейдите в корневую директорию проекта:
  ```bash
  cd /path/to/yii2-demo
  ```
- Установите зависимости через Composer:
  ```bash
  composer install
  ```
  Если Composer не установлен глобально, используйте php composer.phar install.

### 3. Инициализация проекта
- Выполните команду для инициализации:
  ```bash
  php init
  ```
- Выберите среду: 0 для Development или 1 для Production.

### 4. Настройка конфигурации
- Настройте подключение к базе данных в common/config/main-local.php:
  ```php
  'components' => [
    'db' => [
        'class' => 'yii\db\Connection',
        'dsn' => 'mysql:host=localhost;dbname=your_database_name',
        'username' => 'your_database_username',
        'password' => 'your_database_password',
        'charset' => 'utf8',
    ],
  ],
  ```
- Установите ключ валидации cookie в frontend/config/main-local.php и backend/config/main-local.php:
  ```php
  'request' => [
    'cookieValidationKey' => 'введите_случайную_строку_здесь',
  ],
  ```
- Установите обязательные значения в common/config/param-local.php:
  ```php
  'encryptionKey' => 'ключ-шифрования',
  'superExpert' => [
        'login' => 'логин-системного-эксперта',
        'password' => 'пароль-системного-эксперта',
  ]
  ```

### 5. Применение миграций
- Создайте базу данных через phpMyAdmin или другой инструмент.
- Примените миграции:
  ```bash
  php yii migrate
  ```

### 6. Проверка
- Откройте в браузере:
  - Студент: http://yourdomain.com
  - Эксперт: http://yourdomain.com/expert
- Проверьте логи в frontend/runtime/logs и backend/runtime/logs при возникновении ошибок.

<!-- Additionally -->
## Дополнительно

### Дополнительно
- Frontend: HTML, CSS, JavaScript, jQuery.
- Backend: PHP, Yii2 (шаблон Advanced).
- База данных: MySQL.
- Контроль версий: Git, GitHub.

### Будущие улучшения
- Интеграция автоматического создания виртуальных хостингов.
- Добавление системы автоматической проверки заданий.
- Расширение модулей для обучения (например, уроки по API или SPA).

### Контакты
Semenov Daniil - ds.daniilsemen.ds@gmail.com
Project Link: [https://github.com/Semenov-Daniil/demo](https://github.com/Semenov-Daniil/demo)

### Лиценция

Распространяется по лицензии MIT. Смотреть `LICENSE.mt` для получения более подробной информации.

---

Спасибо за интерес к проекту "Demo"! Мы надеемся, что платформа станет полезным инструментом для проведения чемпионатов и обучения веб-технологиям.

<!-- MARKDOWN LINKS & IMAGES -->
<!-- https://www.markdownguide.org/basic-syntax/#reference-style-links -->
[contributors-shield]: https://img.shields.io/github/contributors/Semenov-Daniil/api-file-cloud.svg?style=for-the-badge
[contributors-url]: https://github.com/Semenov-Daniil/api-file-cloud/graphs/contributors
[forks-shield]: https://img.shields.io/github/forks/Semenov-Daniil/api-file-cloud.svg?style=for-the-badge
[forks-url]: https://github.com/Semenov-Daniil/api-file-cloud/network/members
[stars-shield]: https://img.shields.io/github/stars/Semenov-Daniil/api-file-cloud.svg?style=for-the-badge
[stars-url]: https://github.com/Semenov-Daniil/api-file-cloud/stargazers
[issues-shield]: https://img.shields.io/github/issues/Semenov-Daniil/api-file-cloud.svg?style=for-the-badge
[issues-url]: https://github.com/Semenov-Daniil/api-file-cloud/issues
[license-shield]: https://img.shields.io/github/license/Semenov-Daniil/api-file-cloud.svg?style=for-the-badge
[license-url]: https://github.com/Semenov-Daniil/api-file-cloud/blob/master/LICENSE.txt
[linkedin-shield]: https://img.shields.io/badge/-LinkedIn-black.svg?style=for-the-badge&logo=linkedin&colorB=555
[linkedin-url]: https://linkedin.com/in/othneildrew
[Bootstrap.com]: https://img.shields.io/badge/Bootstrap-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white
[Bootstrap-url]: https://getbootstrap.com
[JQuery.com]: https://img.shields.io/badge/jQuery-0769AD?style=for-the-badge&logo=jquery&logoColor=white
[JQuery-url]: https://jquery.com 
