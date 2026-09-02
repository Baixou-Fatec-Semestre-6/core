# Core — Baixou

Camada core do sistema de rastreamento de preços.

## Pré-requisitos

* [Docker](https://docs.docker.com/get-docker/)
* [Docker Compose](https://docs.docker.com/compose/install/)

---

## Como Executar

Para iniciar rapidamente, use o comando de setup após clonar o repositório:

1. Torne o script de gerenciamento executável (necessário apenas na primeira vez):
```bash
chmod +x bin/dev

```


2. Execute a rotina completa de setup:
```bash
./bin/dev setup

```

## Gerenciamento via Script (`bin/dev`)

O projeto conta com um script de automação (`bin/dev`) para facilitar o fluxo de desenvolvimento:

* `./bin/dev setup` — Executa o build, sobe os containers, gera o `APP_KEY` e roda as migrações
* `./bin/dev up` — Sobe os serviços em background
* `./bin/dev down` — Para e remove os containers (use `./bin/dev down -v` para descartar o volume do banco de dados)
* `./bin/dev restart` — Reinicia os serviços
* `./bin/dev logs` — Acompanha os logs em tempo real
* `./bin/dev sh` — Abre o shell interativo no container da aplicação
* `./bin/dev artisan <comando>` — Executa comandos do Laravel Artisan (ex: `./bin/dev artisan migrate`)

---

## Comandos Manuais (Docker Compose)

Caso prefira interagir diretamente com o compose sem o script auxiliar:

```bash
# Subir serviços
docker compose -f docker/docker-compose.yml up -d

# Parar serviços
docker compose -f docker/docker-compose.yml down

# Acompanhar logs
docker compose -f docker/docker-compose.yml logs -f

```


---

## Documentação da API

A documentação dos endpoints da API é gerada automaticamente pelo Scramble.

Acesse:

```text
http://localhost:8000/docs/api
```

---

## E-mails

O ambiente de desenvolvimento utiliza Mailpit para visualizar os e-mails enviados pela aplicação.

Acesse:

```text
http://localhost:8025
```

---