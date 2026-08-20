# 🧪 Teste Técnico – Desenvolvedor PHP (Estech)

Neste teste avaliaremos seu **conhecimento técnico**, **organização de código**, **raciocínio lógico** e também sua **velocidade de desenvolvimento**.  
Leia atentamente as instruções antes de iniciar.

---

## 🎯 Objetivo do Desafio

Implementar uma **API REST** utilizando **Laravel (PHP)** e **MySQL**, cujo objetivo é realizar a **ingestão de dados** da API pública de Pokémons e disponibilizar **métricas consultáveis** a partir desses dados.

A API pública a ser utilizada é:  
👉 https://pokeapi.co/

---

## 📋 Requisitos Funcionais

### 1️⃣ Ingestão de Dados

Sua aplicação deve possuir um **Command do Laravel** responsável por:

- Consumir a API pública do PokeAPI;
- Persistir os dados relevantes no banco de dados MySQL;

---

### 2️⃣ Endpoint de Métricas

Criar uma **rota HTTP** que permita consultar métricas dos Pokémons armazenados.

A rota deve permitir, **de forma opcional**, os parâmetros:

- **Métrica a ser analisada**, por exemplo:
  - `hp`

- **Campo específico a ser retornado**
  - Exemplo: retornar apenas o `name` no ranking.

- **Ordenação**
  - Maiores valores (melhores)
  - Menores valores (piores)

📌 **Observação:**  
Todos os parâmetros devem ser **opcionais** e possuir valores padrão coerentes.
Você pode definir outros parâmetros que garantam performance, melhor visibilidade, etc.

---

## 🗄️ Banco de Dados

- O banco de dados deve ser modelado e criado **exclusivamente via Migrations do Laravel**;
- Fique à vontade para definir a melhor modelagem, desde que faça sentido para o domínio do problema.

---

## 🧰 Tecnologias Obrigatórias

O projeto **deve** utilizar:

- PHP
- Framework **Laravel**
- **MySQL**
- **Docker** (para construção do ambiente de desenvolvimento)

---

## 🚀 Entrega do Desafio

Para entregar o teste, siga rigorosamente os passos abaixo:

1. Faça um **fork** deste repositório  
   > ⚠️ Apenas clonar o repositório não permitirá o push.

2. Crie uma **branch com seu nome completo**;

3. Atualize o arquivo `teste-pratico.md`, descrevendo claramente:
   - Como subir o ambiente (Docker);
   - Comandos necessários (migrations, seeds, ingestão de dados, etc);
   - Qualquer observação relevante para execução do projeto.

4. Após finalizar, abra um **Pull Request** para o repositório original.

---

## ⭐ Bônus (Opcional)

- Implementar autenticação de usuários utilizando **Laravel Sanctum**.
- Usar Octane / Swoole
- Criar testes automatizados

---

## 🔍 O que será avaliado?

- Configuração e automação do ambiente com Docker;
- Modelagem e transformação de dados;
- Organização e legibilidade do código;
- Clareza no raciocínio lógico;
- Performance e otimização das consultas;
- Boas práticas com Laravel e PHP.

---

## 🍀 Boa sorte!

Seja claro, simples e consistente. Preferimos soluções bem pensadas a soluções excessivamente complexas.

---

# ✅ Solução — Instruções de Execução

A aplicação (**PokeStats**) está no diretório [`pokestats-laravel/`](./pokestats-laravel). Todos os comandos abaixo devem ser executados a partir dele:

```bash
cd pokestats-laravel
```

## 1. Subir o ambiente (Docker)

```bash
docker compose up -d --build
```

Serviços:

- **app** — PHP 8.3-CLI + Swoole, servindo via **Laravel Octane** em `http://localhost:8000`
- **mysql** — MySQL 8.0 (database `pokestats`, user `pokestats`, senha `secret`)
- **redis** — cache das consultas de métricas

No primeiro start, o entrypoint do container `app` já executa automaticamente: `composer install` (se necessário), cópia do `.env`, `key:generate` e **`migrate`**. Nenhum passo manual é necessário para o banco.

## 2. Ingestão de dados (Command)

```bash
# Importa todos os Pokémons (~1300) — leva alguns minutos
docker compose exec app php artisan pokemon:ingest

# Opções disponíveis
docker compose exec app php artisan pokemon:ingest --limit=151          # só a 1ª geração
docker compose exec app php artisan pokemon:ingest --offset=151 --limit=100
docker compose exec app php artisan pokemon:ingest --concurrency=20     # requisições paralelas
```

O command é **idempotente** (upsert por `external_id`): pode ser executado várias vezes sem duplicar registros. Falhas individuais são logadas sem interromper o lote, e ao final o cache de métricas é invalidado automaticamente.

## 3. Autenticação (Sanctum)

As rotas de métricas exigem token Bearer. Crie um usuário e obtenha o token:

```bash
# Registrar (retorna o token)
curl -s -X POST http://localhost:8000/api/auth/register \
  -H 'Content-Type: application/json' \
  -d '{"name":"Ash","email":"ash@pallet.town","password":"pikachu123","password_confirmation":"pikachu123"}'

# Ou logar
curl -s -X POST http://localhost:8000/api/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"ash@pallet.town","password":"pikachu123"}'
```

## 4. Endpoint de métricas

```
GET /api/pokemons/metrics
```

Todos os parâmetros são opcionais:

| Parâmetro  | Padrão  | Descrição |
|------------|---------|-----------|
| `metric`   | `hp`    | Métrica do ranking: `hp`, `attack`, `defense`, `special_attack`, `special_defense`, `speed`, `height`, `weight`, `base_experience` |
| `order`    | `desc`  | `desc` (maiores valores) ou `asc` (menores valores) |
| `fields`   | `name` + métrica | Campos retornados, separados por vírgula (ex: `name,sprite_url`). A métrica escolhida é sempre incluída |
| `per_page` | `10`    | Itens por página (máx. 100) |
| `page`     | `1`     | Página da paginação |

Exemplos:

```bash
TOKEN="<token obtido no login>"

# Top 10 por HP (padrão)
curl -s http://localhost:8000/api/pokemons/metrics \
  -H "Authorization: Bearer $TOKEN"

# Top 5 mais rápidos, retornando apenas nome e sprite
curl -s "http://localhost:8000/api/pokemons/metrics?metric=speed&per_page=5&fields=name,sprite_url" \
  -H "Authorization: Bearer $TOKEN"

# 10 piores defesas
curl -s "http://localhost:8000/api/pokemons/metrics?metric=defense&order=asc" \
  -H "Authorization: Bearer $TOKEN"
```

## 5. Testes automatizados

```bash
docker compose exec app php artisan test
```

Cobertura: endpoint de métricas (parâmetros padrão, ordenação, fields, validação 422, paginação, autenticação), autenticação (registro, login, logout) e command de ingestão (importação, idempotência, tolerância a falhas — com `Http::fake()`).

## Decisões técnicas

- **Modelagem em tabela única** (`pokemons`) com as stats como colunas indexadas: rankings por métrica são `ORDER BY` + índice, sem joins.
- **Ingestão concorrente**: listagem paginada + detalhes via `Http::pool()` com retry/backoff; persistência em `upsert()` em lotes.
- **Performance das métricas**: respostas cacheadas por 5 min com chave versionada (invalidação automática pós-ingestão), paginação e seleção explícita de colunas.
- **Bônus implementados**: autenticação via Sanctum, Octane/Swoole e testes automatizados.
- **Observação**: os testes rodam com SQLite em memória (configurado no `phpunit.xml`), independente do MySQL do ambiente.

