#!/bin/bash

# check_services.sh - Утилита для проверки наличия служб
# Расположение: bash/setup/check_services.sh

set -euo pipefail

# Подключение локального config.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh" || {
    echo "Failed to source local config.sh"
    exit 1
}

[[ "$LOG_FILE" == "${DEFAULT_LOG_FILE:-}" ]] && LOG_FILE="services.log"

# Обработка флага -y
AUTO_YES=false
for arg in "${ARGS[@]}"; do
    if [[ "$arg" == "-y" ]]; then
        AUTO_YES=true
        break
    fi
done

# Проверка массива REQUIRED_SERVICES
if ! declare -p REQUIRED_SERVICES >/dev/null 2>&1; then
    log_message "error" "REQUIRED_SERVICES array is not defined in config.sh"
    exit ${EXIT_INVALID_ARG}
fi

# Проверка, что массив не пустой
if [[ ${#REQUIRED_SERVICES[@]} -eq 0 ]]; then
    log_message "error" "REQUIRED_SERVICES array is empty"
    exit ${EXIT_INVALID_ARG}
fi

# Проверка массива REQUIRED_SERVICE_MAP
if ! declare -p REQUIRED_SERVICE_MAP >/dev/null 2>&1; then
    log_message "error" "REQUIRED_SERVICE_MAP array is not defined in config.sh"
    exit ${EXIT_INVALID_ARG}
fi

# Проверка установки пакета и установка при необходимости
install_package() {
    local package="$1"
    local response

    if dpkg-query -W -f='${Status}' "$package" 2>/dev/null | grep -q "install ok installed"; then
        log_message "info" "Package $package is already installed"
        return 0
    fi

    if [[ "$AUTO_YES" == true ]]; then
        response="y"
    else
        read -p "Package '$package' is not installed. Install it? (y/yes/n/no): " response
        response=$(echo "$response" | tr '[:upper:]' '[:lower:]')
    fi

    case "$response" in
        y|yes)
            log_message "info" "Installing package $package"
            apt-get update -q 2>>"$LOG_FILE" || {
                log_message "error" "Failed to update package lists"
                return ${EXIT_NO_DEPENDENCY}
            }

            if command -v apt-get >/dev/null; then
                apt-get install -y "$package" 2>>"$LOG_FILE" | tee -a "$LOG_FILE" || {
                    log_message "error" "Failed to install package $package"
                    return ${EXIT_NO_DEPENDENCY}
                }
            elif command -v dnf >/dev/null; then
                dnf install -y "$package" 2>>"$LOG_FILE" | tee -a "$LOG_FILE" || {
                    log_message "error" "Failed to install package $package"
                    return ${EXIT_NO_DEPENDENCY}
                }
            fi
            
            log_message "info" "Package $package installed successfully"
            ;;
        n|no)
            log_message "warning" "Skipped installation of package $package"
            return ${EXIT_NO_DEPENDENCY}
            ;;
        *)
            log_message "error" "Invalid response: $response"
            return ${EXIT_INVALID_ARG}
            ;;
    esac

    return 0
}

# Включение и запуск сервиса
start_service() {
    local service="$1"
    local response

    if systemctl is-enabled "$service" >/dev/null 2>&1 && systemctl is-active "$service" >/dev/null 2>&1; then
        log_message "info" "Service $service is enabled and active"
        return 0
    fi

    if [[ "$AUTO_YES" == true ]]; then
        response="y"
    else
        read -p "Service '$service' is not enabled or active. Enable and start it? (y/yes/n/no): " response
        response=$(echo "$response" | tr '[:upper:]' '[:lower:]')
    fi

    case "$response" in
        y|yes)
            log_message "info" "Enabling and starting service $service"
            systemctl enable "$service" 2>>"$LOG_FILE" || {
                log_message "error" "Failed to enable service $service: $(tail -n 1 "$LOG_FILE")"
                return ${EXIT_GENERAL_ERROR}
            }
            systemctl start "$service" 2>>"$LOG_FILE" || {
                log_message "error" "Failed to start service $service"
                return ${EXIT_GENERAL_ERROR}
            }
            log_message "info" "Service $service enabled and started successfully"
            ;;
        n|no)
            log_message "warning" "Skipped enabling/starting service $service"
            return ${EXIT_GENERAL_ERROR}
            ;;
        *)
            log_message "error" "Invalid response: $response"
            return ${EXIT_INVALID_ARG}
            ;;
    esac

    return 0
}

log_message "info" "Starting check for required services: ${REQUIRED_SERVICES[*]}"

# Проверка и установка зависимостей
missing_deps=false
for package in "${REQUIRED_SERVICES[@]}"; do
    install_package "$package" || {
        missing_deps=true
        continue
    }
done

if [[ "$missing_deps" == true ]]; then
    log_message "error" "Some dependencies could not be installed"
    exit ${EXIT_NO_DEPENDENCY}
fi

# Проверка и запуск сервисов
for package in "${REQUIRED_SERVICES[@]}"; do
    # Получение списка сервисов из REQUIRED_SERVICE_MAP
    service_names="${REQUIRED_SERVICE_MAP[$package]:-}"
    
    # Пропуск пакетов без сервисов
    if [[ -z "$service_names" ]]; then
        log_message "info" "Package $package has no associated service"
        continue
    fi

    # Обработка каждого сервиса
    for service_name in $service_names; do
        if ! systemctl list-units --type=service --all | grep "$service_name.service" >/dev/null 2>&1; then
            log_message "warning" "Service $service_name not found for package $package. Check command: systemctl list-units --type=service --all | grep -qw "$service_name.service""
            continue
        fi

        start_service "$service_name" || {
            log_message "error" "Failed to enable/start service $service_name"
            exit ${EXIT_GENERAL_ERROR}
        }
    done
done

log_message "info" "All required services checked and configured successfully"

exit 0