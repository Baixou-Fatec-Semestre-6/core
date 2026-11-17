#!/bin/bash
set -e

# Verificar se o arquivo vendor/autoload.php existe, se não, executar composer install
if [ ! -f "vendor/autoload.php" ]; then
    composer install --no-interaction
fi

# Corrigir problema de permissões do git se necessário
if git rev-parse --git-dir > /dev/null 2>&1; then
    git config --global --add safe.directory /var/www || true
fi

# Executar o comando principal
exec "$@"
