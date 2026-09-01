#!/bin/bash
set -e

# Verificar se o diretório vendor existe, se não, executar composer install
if [ ! -d "vendor" ]; then
    echo "Diretório vendor não encontrado. Instalando dependências..."
    composer install --no-interaction
fi

# Corrigir problema de permissões do git se necessário
if git rev-parse --git-dir > /dev/null 2>&1; then
    git config --global --add safe.directory /var/www || true
fi

# Executar o comando principal
exec "$@"
