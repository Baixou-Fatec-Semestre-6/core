#!/bin/bash
set -e

# As dependências já estão na imagem (veja docker/Dockerfile), não há nada para instalar aqui.
exec "$@"
