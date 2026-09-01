**Core**
Camada core do projeto de rastreamento de preços (BAIXOU).

**Pré-requisitos**

* Docker
* Docker Compose

**Gerenciamento via Script (`bin/dev`)**
Para facilitar o dia a dia, utilize o script de automação integrado (torne-o executável uma única vez com `chmod +x bin/dev`):

* `./bin/dev up` — Subir os serviços em background
* `./bin/dev down` — Parar e remover os containers
* `./bin/dev restart` — Reiniciar os serviços
* `./bin/dev logs` — Acompanhar os logs em tempo real
* `./bin/dev sh` — Abrir o shell interativo no container `app`
* `./bin/dev artisan <comando>` — Executar comandos do Laravel Artisan (ex: `./bin/dev artisan migrate`)

**Comandos Manuais (Docker Compose)**
Caso prefira interagir diretamente com o compose sem o script:

```bash
# Subir serviços
docker compose -f docker/docker-compose.yml up -d

# Parar serviços
docker compose -f docker/docker-compose.yml down

# Acompanhar logs
docker compose -f docker/docker-compose.yml logs -f

```