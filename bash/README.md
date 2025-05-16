# README для Bash-скриптов проекта

## Оглавление
- [Общее описание](#общее-описание)
- [Структура папок](#структура-папок)
- [Зависимости](#зависимости)
- [Общие рекомендации](#общие-рекомендации)
- [Глобальный конфигурационный файл](#глобальный-конфигурационный-файл)
- [Скрипты](#скрипты)
  - [Папка `lib/`](#папка-lib)
  - [Папка `samba/`](#папка-samba)
  - [Папка `ssh/`](#папка-ssh)
  - [Папка `system/`](#папка-system)
  - [Папка `utils/`](#папка-utils)
  - [Папка `vhost/`](#папка-vhost)

## Общее описание
Этот документ описывает Bash-скрипты, используемые для настройки и управления проектом. Скрипты расположены в папке `bash` в корне проекта и предназначены для автоматизации задач, таких как настройка Samba, SSH, виртуальных хостов, системной логики и утилит.

## Структура папок
- **`bash/`** — корневая папка для всех скриптов.
  - **`config.sh`** — глобальный конфигурационный файл, подключаемый ко всем скриптам.
  - **`lib/`** — подключаемые файлы с общими функциями.
  - **`samba/`** — скрипты для настройки Samba.
  - **`ssh/`** — скрипты для настройки SSH.
  - **`system/`** — скрипты для системной логики.
  - **`utils/`** — утилиты, которые можно запускать самостоятельно.
  - **`vhost/`** — скрипты для настройки виртуальных хостов.
  - **`logs/`** — папка для хранения логов (создаётся автоматически).

Каждая из папок `samba`, `ssh`, `system`, `utils`, `vhost` содержит локальный файл `config.sh` с настройками, специфичными для данной категории скриптов.

## Зависимости
Скрипты требуют установленных пакетов, указанных в `REQUIRED_SERVICES`. Убедитесь, что они установлены:
```bash
sudo apt update
sudo apt install apache2 openssh-server samba samba-common-bin
```

## Общие рекомендации
1. **Права доступа**: Большинство скриптов требуют прав суперпользователя (`sudo`). Пользователь `www-data`, отвечающий за веб-сайт, должен иметь возможность выполнять скрипты без ввода пароля. Для этого добавьте строку в файл `sudoers`:
   ```bash
   sudo visudo
   ```
   Добавьте:
   ```bash
   www-data ALL=(ALL) NOPASSWD: /path/to/bash/*.sh
   ```
   Где `/path/to/` — путь к корневой папке сайта (например, `/var/www/project/`).
2. **Права выполнения для `www-data`**: Чтобы пользователь `www-data` мог выполнять скрипты, установите соответствующие права:
   ```bash
   sudo chown www-data:www-data /path/to/bash/*.sh
   sudo chmod 750 /path/to/bash/*.sh
   ```
   Это обеспечит, что только `www-data` и root смогут выполнять скрипты.
3. **Логи**: Логи сохраняются в `bash/logs/`. Проверяйте их для диагностики ошибок.
4. **Конфигурация**: Настраивайте переменные в `.env` или локальных `config.sh` перед запуском.
5. **Запуск утилит**: Скрипты в `utils/` можно запускать напрямую:
   ```bash
   sudo bash utils/<script_name>.sh
   ```

## Глобальный конфигурационный файл
Файл `bash/config.sh` задаёт основные переменные и функции, используемые всеми скриптами. Он должен быть подключён (`source`) в начале каждого скрипта.

### Основные переменные
Переменные задаются в файле `bash/.env` или напрямую в `config.sh`. Основные переменные, которые можно настроить:
- **`SITE_USER`** (по умолчанию: `www-data`) — пользователь для веб-сервера.
- **`SITE_GROUP`** (по умолчанию: `www-data`) — группа для веб-сервера.
- **`STUDENT_GROUP`** (по умолчанию: `students`) — группа для студентов.
- **`STUDENTS_DIR`** (по умолчанию: `${PROJECT_ROOT}/students`) — директория для данных студентов.
- **`REQUIRED_SERVICES`** — массив сервисов, необходимых для работы скриптов:
  - `apache2`
  - `openssh-server`
  - `samba`
  - `samba-common-bin`
- **`LOGS_DIR`** (по умолчанию: `${SCRIPTS_DIR}/logs`) — директория для логов.

### Использование
Глобальный `config.sh` подключается в скриптах с помощью:
```bash
source "${SCRIPTS_DIR}/config.sh"
```
**Важно**: Скрипт нельзя запускать напрямую (`./config.sh`), только подключать через `source`.

## Скрипты

### Папка `lib/`
Содержит подключаемые файлы с функциями, используемыми другими скриптами. Все скрипты подключаются через функцию `source_script` из `config.sh`:
```bash
source_script "${LIB_DIR}/<script_name>.sh"
```

#### `check_cmds.sh`
**Описание**: Проверяет наличие указанных команд в системе.

**Функции**:
- `check_cmds <команда1> <команда2> ...` — проверяет, доступны ли указанные команды.
  - Возвращает `EXIT_SUCCESS` (0), если все команды найдены, или `EXIT_NO_CMD` (1), если есть отсутствующие.
  - Пример: `check_cmds ls cat rm`

**Использование**:
```bash
source_script "${CHECK_CMDS_SCRIPT}"
check_cmds ls cat rm || exit $?
```

#### `check_deps.sh`
**Описание**: Проверяет, установлены ли указанные пакеты (зависимости).

**Функции**:
- `check_deps <пакет1> <пакет2> ...` — проверяет наличие пакетов через `dpkg-query`.
  - Возвращает `EXIT_SUCCESS` (0), если все пакеты установлены, или `EXIT_NO_DEPENDENCY` (1), если есть отсутствующие.
  - Пример: `check_deps grep tar`

**Использование**:
```bash
source_script "${CHECK_DEPS_SCRIPT}"
check_deps grep tar || exit $?
```

#### `create_dirs.sh`
**Описание**: Создаёт директории и настраивает их права и владельцев.

**Функции**:
- `create_directories <путь1> <путь2> ... <права> <владелец>` — создаёт директории, устанавливает права (в восьмеричном формате) и владельца (формат `user:group` или `user`).
  - Возвращает `EXIT_SUCCESS` (0) при успехе, `EXIT_INVALID_ARG` (2) при неверных аргументах, или `EXIT_GENERAL_ERROR` (1) при ошибке.
  - Пример: `create_directories /path/dir1 /path/dir2 750 root:root`

**Использование**:
```bash
source_script "${CREATE_DIRS_SCRIPT}"
create_directories /path/dir1 /path/dir2 750 root:root || exit $?
```

#### `logging.sh`
**Описание**: Предоставляет функции для логирования сообщений в файлы и вывода в консоль.

**Функции**:
- `log_message <уровень> <сообщение>` — записывает сообщение в лог-файл и выводит в консоль. Уровни: `info`, `warning`, `error`.
  - Пример: `log_message info "Operation completed"`
- `check_level <уровень>` — проверяет корректность уровня лога.
- `make_log_dir <путь_к_лог_файлу>` — создаёт директорию для логов.
- `clean_old_log <лог_файл>` — удаляет записи старше `LOG_RETENTION_DAYS` (по умолчанию 30 дней).
- `format_log <уровень> <сообщение>` — форматирует запись лога с временной меткой.
- `write_log <лог_файл> <запись>` — записывает сообщение в лог-файл.
- `print_log <уровень> <сообщение>` — выводит сообщение в консоль.

**Использование**:
```bash
source_script "${LOGGING_SCRIPT}" my_log.log
log_message info "Operation completed" || exit $?
```

**Переменные**:
- `LOG_RETENTION_DAYS` (по умолчанию: 30) — срок хранения логов в днях.
- `LOGS_DIR` (по умолчанию: `bash/logs/`) — директория для логов.
- `DEFAULT_LOG` (по умолчанию: `${LOGS_DIR}/logs.log`) — лог-файл по умолчанию.

#### `update_perms.sh`
**Описание**: Обновляет права и владельцев для файлов и директорий.

**Функции**:
- `update_permissions <путь1> <путь2> ... <права> <владелец>` — устанавливает права (в восьмеричном формате) и владельца (формат `user:group` или `user`) для файлов/директорий.
  - Возвращает `EXIT_SUCCESS` (0) при успехе, `EXIT_INVALID_ARG` (2) при неверных аргументах, или `EXIT_GENERAL_ERROR` (1) при ошибке.
  - Пример: `update_permissions /path/file1 /path/dir1 755 root:root`

**Использование**:
```bash
source_script "${UPDATE_PERMS_SCRIPT}"
update_permissions /path/file1 /path/dir1 755 root:root || exit $?
```

### Папка `samba/`
Содержит скрипты для настройки Samba-сервера и управления пользователями Samba. Все скрипты требуют прав суперпользователя и подключают локальный `config.sh`.

#### Локальный `config.sh`
**Описание**: Задаёт конфигурацию для скриптов Samba.

**Основные переменные**:
- **`SAMBA_CONFIG_FILE`** (по умолчанию: `/etc/samba/smb.conf`) — путь к конфигурационному файлу Samba.
- **`SAMBA_BACKUP_CONFIG`** (по умолчанию: `/etc/samba/smb.conf.bak`) — путь к резервной копии конфигурации.
- **`SAMBA_LOG_DIR`** (по умолчанию: `/var/log/samba`) — директория для логов Samba.
- **`SAMBA_LOG_FILE`** (по умолчанию: `${SAMBA_LOG_DIR}/samba.log`) — лог-файл Samba.
- **`SAMBA_TEMP_CONFIG`** (по умолчанию: `/tmp/smb.conf.tmp`) — временный файл конфигурации.
- **`SAMBA_SERVICES`** — массив сервисов Samba: `smbd`, `nmbd`.
- **`SAMBA_PORTS`** — порты для Samba: `137/udp`, `138/udp`, `139/tcp`, `445/tcp`.
- **`SAMBA_GLOBAL_PARAMS`** — параметры глобальной секции Samba:
  - `workgroup = WORKGROUP`
  - `server string = %h server (Samba, Ubuntu)`
  - `server role = standalone server`
  - `security = user`
  - `map to guest = never`
  - `smb encrypt = required`
  - `min protocol = SMB3`
  - `log file = ${SAMBA_LOG_FILE}`
  - `max log size = 1000`

**Использование**:
Подключается автоматически в скриптах Samba:
```bash
source "$(dirname "${BASH_SOURCE[0]}")/config.sh"
```

#### `add_student_samba.sh`
**Описание**: Добавляет пользователя в Samba, создавая учётную запись с паролем.

**Использование**:
```bash
sudo bash samba/add_student_samba.sh <username> <password> [--log=<log_file>]
```
- `<username>` — имя пользователя (должно существовать в системе и быть в группе `students`).
- `<password>` — пароль для Samba.
- `--log=<log_file>` — имя лог-файла (по умолчанию: `add_student_samba.log`).

**Пример**:
```bash
sudo bash samba/add_student_samba.sh student1 securepass
```

#### `delete_student_samba.sh`
**Описание**: Удаляет пользователя из Samba.

**Использование**:
```bash
sudo bash samba/delete_student_samba.sh <username> [--log=<log_file>]
```
- `<username>` — имя пользователя.
- `--log=<log_file>` — имя лог-файла (по умолчанию: `delete_student_samba.log`).

**Пример**:
```bash
sudo bash samba/delete_student_samba.sh student1
```

#### `delete_user_samba.sh`
**Описание**: Содержит функцию для удаления пользователя из Samba.

**Функции**:
- `delete_user_samba <username>` — удаляет пользователя из Samba, завершает его процессы и перезагружает конфигурацию.
  - Возвращает `EXIT_SUCCESS` (0) при успехе, или код ошибки (например, `EXIT_SAMBA_USER_DELETE_FAILED` (25)).
  - Пример: `delete_user_samba student1`
- `check_and_terminate_user <username>` — завершает активные процессы пользователя.

**Использование**:
Подключается в других скриптах:
```bash
source_script "${DELETE_USER_SAMBA}"
delete_user_samba student1 || exit $?
```

#### `setup_samba.sh`
**Описание**: Настраивает Samba-сервер: обновляет конфигурацию, создаёт шары, открывает порты, запускает сервисы.

**Функции**:
- `start_samba_services` — запускает сервисы `smbd` и `nmbd`.
- `configure_ufw` — открывает порты Samba в UFW.
- `backup_samba_config` — создаёт резервную копию конфигурации.
- `update_global_config` — обновляет глобальную секцию конфигурации Samba.
- `add_user_share` — добавляет пользовательскую шару для студентов.
- `apply_samba_config` — проверяет и применяет конфигурацию.
- `cleanup` — удаляет временные файлы.

**Использование**:
Подключается в других скриптах или запускается напрямую:
```bash
sudo bash samba/setup_samba.sh [--log=<log_file>]
```
- `--log=<log_file>` — имя лог-файла (по умолчанию: `setup_samba.log`).

**Пример**:
```bash
sudo bash samba/setup_samba.sh
```

### Папка `ssh/`
Sкрипты для настройки SSH и управления chroot-окружением для пользователей группы `students`. Все скрипты требуют прав суперпользователя и подключают локальный `config.sh`.

#### Локальный `config.sh`
**Описание**: Задаёт конфигурацию для скриптов SSH и chroot-окружения.

**Основные переменные**:
- **`CHROOT_DIR`** (по умолчанию: `/var/chroot`) — корневая директория для chroot-окружений.
- **`CHROOT_STUDENTS`** (по умолчанию: `${CHROOT_DIR}/${STUDENT_GROUP}`) — директория для chroot-окружений студентов.
- **`SSH_CONFIG_FILE`** (по умолчанию: `/etc/ssh/sshd_config`) — основной конфигурационный файл SSH.
- **`SSH_CONFIGS_DIR`** (по умолчанию: `/etc/ssh/sshd_config.d`) — директория для дополнительных конфигураций SSH.
- **`STUDENT_CONF_FILE`** (по умолчанию: `${SSH_CONFIGS_DIR}/${STUDENT_GROUP}.conf`) — файл конфигурации для группы `students`.
- **`MOUNT_DIRS`** — директории для монтирования в chroot: `dev`, `proc`, `usr`, `bin`, `lib`, `lib64`, `home`.
- **`MOUNT_FILES`** — файлы для монтирования (по умолчанию пустой массив).
- **`CHROOT_BASE_DIRS`** — базовые директории в chroot: `dev`, `etc`, `home`, `usr`, `bin`, `lib`, `lib64`, `proc`, `tmp`.

**Использование**:
Подключается автоматически в скриптах SSH:
```bash
source "$(dirname "${BASH_SOURCE[0]}")/config.sh"
```

#### `init_student_chroot.sh`
**Описание**: Инициализирует chroot-окружение для студента, создавая директории, монтируя ресурсы и настраивая `/etc/fstab`.

**Использование**:
```bash
sudo bash ssh/init_student_chroot.sh <username> [--log=<log_file>]
```
- `<username>` — имя пользователя (должно существовать в системе).
- `--log=<log_file>` — имя лог-файла (по умолчанию: `init_student_chroot.log`).

**Пример**:
```bash
sudo bash ssh/init_student_chroot.sh student1
```

#### `remove_student_chroot.sh`
**Описание**: Удаляет chroot-окружение студента.

**Использование**:
```bash
sudo bash ssh/remove_student_chroot.sh <username> [--log=<log_file>]
```
- `<username>` — имя пользователя.
- `--log=<log_file>` — имя лог-файла (по умолчанию: `remove_student_chroot.log`).

**Пример**:
```bash
sudo bash ssh/remove_student_chroot.sh student1
```

#### `remove_chroot.sh`
**Описание**: Содержит функции для удаления chroot-окружения.

**Функции**:
- `remove_chroot <username>` — удаляет chroot-окружение, размонтирует директории, очищает `/etc/fstab` и удаляет директорию.
  - Возвращает `EXIT_SUCCESS` (0) при успехе, или код ошибки (например, `EXIT_MOUNT_FAILED` (10)).
  - Пример: `remove_chroot student1`
- `check_and_terminate_user <username>` — завершает активные процессы пользователя.
- `remove_chroot_dir <chroot_dir>` — удаляет chroot-директорию.
- `clean_fstab <chroot_dir>` — удаляет записи из `/etc/fstab`.
- `cleanup_mounts <chroot_dir>` — размонтирует директории и файлы.

**Использование**:
Подключается в других скриптах:
```bash
source_script "${REMOVE_CHROOT}"
remove_chroot student1 || exit $?
```

#### `setup_ssh.sh`
**Описание**: Настраивает SSH-сервер для использования chroot-окружения для группы `students`, обновляет конфигурации и запускает сервис.

**Функции**:
- `start_ssh_service` — запускает SSH-сервис.
- `get_ssh_port` — получает порт SSH из конфигурации.
- `configure_ufw` — открывает SSH-порт в UFW.
- `check_configs_dir` — проверяет доступность директории `sshd_config.d`.
- `update_student_config` — создаёт/обновляет конфигурацию для группы `students`.
- `update_main_config` — добавляет директиву `Include` в основной `sshd_config`.
- `restart_ssh_service` — проверяет синтаксис и перезапускает SSH.

**Использование**:
Подключается в других скриптах или запускается напрямую:
```bash
sudo bash ssh/setup_ssh.sh [--log=<log_file>]
```
- `--log=<log_file>` — имя лог-файла (по умолчанию: `setup_ssh.log`).

**Пример**:
```bash
sudo bash ssh/setup_ssh.sh
```

### Папка `system/`
Содержит скрипты для создания и удаления системных пользователей, а также настройки директорий модулей. Все скрипты требуют прав суперпользователя и подключают локальный `config.sh`.

#### Локальный `config.sh`
**Описание**: Задаёт конфигурацию для скриптов системной логики.

**Основные переменные**:
- В данном файле отсутствуют специфические переменные, используются переменные из глобального `config.sh` (например, `SITE_USER`, `SITE_GROUP`, `STUDENT_GROUP`).

**Использование**:
Подключается автоматически в скриптах `system`:
```bash
source "$(dirname "${BASH_SOURCE[0]}")/config.sh"
```

#### `create_user.sh`
**Описание**: Создаёт системного пользователя, устанавливает пароль и назначает домашнюю директорию.

**Использование**:
```bash
sudo bash system/create_user.sh <username> <password> <home_dir> [--log=<log_file>]
```
- `<username>` — имя пользователя (должно быть уникальным).
- `<password>` — пароль пользователя.
- `<home_dir>` — путь к домашней директории (должен существовать).
- `--log=<log_file>` — имя лог-файла (по умолчанию: `create_user.log`).

**Пример**:
```bash
sudo bash system/create_user.sh student1 securepass /home/student1
```

#### `delete_user.sh`
**Описание**: Удаляет системного пользователя, завершая его процессы.

**Использование**:
```bash
sudo bash system/delete_user.sh <username> [--log=<log_file>]
```
- `<username>` — имя пользователя (должен быть в группе `students`).
- `--log=<log_file>` — имя лог-файла (по умолчанию: `delete_user.log`).

**Пример**:
```bash
sudo bash system/delete_user.sh student1
```

#### `setup_module_dirs.sh`
**Описание**: Настраивает права доступа к директориям модулей для указанного пользователя.

**Использование**:
```bash
sudo bash system/setup_module_dirs.sh <username> <dir1> <dir2> ... [--log=<log_file>]
```
- `<username>` — имя пользователя (должен существовать).
- `<dir1> <dir2> ...` — пути к директориям модулей (должны существовать).
- `--log=<log_file>` — имя лог-файла (по умолчанию: `setup_module_dirs.log`).

**Пример**:
```bash
sudo bash system/setup_module_dirs.sh student1 /path/to/module1 /path/to/module2
```

### Папка `utils/`
Содержит утилиты для самостоятельного запуска, которые помогают в управлении системой. Все скрипты требуют прав суперпользователя и подключают локальный `config.sh`.

#### Локальный `config.sh`
**Описание**: Задаёт конфигурацию для утилит.

**Основные переменные**:
- В данном файле отсутствуют специфические переменные, используются переменные из глобального `config.sh` (например, `REQUIRED_SERVICES`, `SERVICE_MAP`).

**Использование**:
Подключается автоматически в утилитах:
```bash
source "$(dirname "${BASH_SOURCE[0]}")/config.sh"
```

#### `check_services.sh`
**Описание**: Проверяет наличие и состояние необходимых сервисов, устанавливает отсутствующие пакеты и запускает/включает сервисы.

**Использование**:
```bash
sudo bash utils/check_services.sh [-y] [--log=<log_file>]
```
- `-y` — автоматически подтверждает установку пакетов и запуск сервисов.
- `--log=<log_file>` — имя лог-файла (по умолчанию: `check_services.log`).

**Пример**:
```bash
sudo bash utils/check_services.sh -y
```

### Папка `vhost/`
Содержит скрипты для настройки и управления виртуальными хостами Apache2. Все скрипты требуют прав суперпользователя и подключают локальный `config.sh`.

#### Локальный `config.sh`
**Описание**: Задаёт конфигурацию для скриптов управления виртуальными хостами Apache2.

**Основные переменные**:
- **`VHOST_AVAILABLE_DIR`** (по умолчанию: `/etc/apache2/sites-available`) — директория для доступных конфигураций виртуальных хостов.
- **`VHOST_ENABLED_DIR`** (по умолчанию: `/etc/apache2/sites-enabled`) — директория для активированных конфигураций.
- **`VHOST_LOG_DIR`** (по умолчанию: `/var/log/apache2`) — директория для логов Apache2.
- **`VHOST_LOG_FILE`** (по умолчанию: `${VHOST_LOG_DIR}/vhost.log`) — лог-файл для виртуальных хостов.
- **`APACHE_SERVICES`** — массив сервисов Apache2: `apache2`.
- **`APACHE_PORTS`** — порты Apache2: `80/tcp`, `443/tcp`.
- **`VHOST_PERMS`** (по умолчанию: `644`) — права доступа для файлов конфигурации.
- **`VHOST_OWNER`** (по умолчанию: `root:root`) — владелец файлов конфигурации.

**Использование**:
Подключается автоматически в скриптах `vhost`:
```bash
source "$(dirname "${BASH_SOURCE[0]}")/config.sh"
```

#### `create_vhost.sh`
**Описание**: Создаёт и активирует виртуальный хост Apache2, записывая указанную конфигурацию.

**Использование**:
```bash
sudo bash vhost/create_vhost.sh <vhost-name> <config-content> [--log=<log_file>]
```
- `<vhost-name>` — имя виртуального хоста (должно быть уникальным).
- `<config-content>` — содержимое конфигурационного файла (например, `<VirtualHost *:80>...</VirtualHost>`).
- `--log=<log_file>` — имя лог-файла (по умолчанию: `create_vhost.log`).

**Пример**:
```bash
sudo bash vhost/create_vhost.sh example.com "<VirtualHost *:80>
    ServerName example.com
    DocumentRoot /var/www/example
    ErrorLog ${VHOST_LOG_DIR}/example-error.log
    CustomLog ${VHOST_LOG_DIR}/example-access.log combined
</VirtualHost>"
```

#### `disable_vhost.sh`
**Описание**: Отключает виртуальный хост Apache2, удаляя его из активированных сайтов.

**Использование**:
```bash
sudo bash vhost/disable_vhost.sh <vhost-name> [--log=<log_file>]
```
- `<vhost-name>` — имя виртуального хоста.
- `--log=<log_file>` — имя лог-файла (по умолчанию: `disable_vhost.log`).

**Пример**:
```bash
sudo bash vhost/disable_vhost.sh example.com
```

#### `remove_vhost.fn.sh`
**Описание**: Содержит функцию для удаления виртуального хоста.

**Функции**:
- `remove_vhost <vhost-name>` — отключает и удаляет конфигурацию виртуального хоста.
  - Возвращает `EXIT_SUCCESS` (0) при успехе, или код ошибки (например, `EXIT_VHOST_DELETE_FAILED` (44)).
  - Пример: `remove_vhost example.com`

**Использование**:
Подключается в других скриптах:
```bash
source_script "${REMOVE_VHOST_SCRIPT}"
remove_vhost example.com || exit $?
```

#### `remove_vhost.sh`
**Описание**: Удаляет виртуальный хост Apache2, вызывая функцию `remove_vhost`.

**Использование**:
```bash
sudo bash vhost/remove_vhost.sh <vhost-name> [--log=<log_file>]
```
- `<vhost-name>` — имя виртуального хоста.
- `--log=<log_file>` — имя лог-файла (по умолчанию: `remove_vhost.log`).

**Пример**:
```bash
sudo bash vhost/remove_vhost.sh example.com
```

#### `setup_apache.sh`
**Описание**: Настраивает Apache2, проверяет зависимости, запускает сервис и открывает порты в UFW.

**Функции**:
- `start_apache_service` — запускает и включает сервис Apache2.
- `configure_ufw` — открывает порты Apache2 в UFW.

**Использование**:
Подключается в других скриптах:
```bash
source_script "${SETUP_APACHE_SCRIPT}"
```
**Пример**:
```bash
sudo bash vhost/setup_apache.sh [--log=<log_file>]
```