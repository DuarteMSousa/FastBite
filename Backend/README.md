# FastBite Backend

Backend Laravel da plataforma FastBite, com API GraphQL via Lighthouse, filas para jobs assincronos, PostgreSQL e GatewayWorker para comunicacao realtime.

## Decisoes de ambito

Estas decisoes fazem parte do ambito atual do TP e nao devem ser lidas como funcionalidades em falta:

- Autenticacao/autorizacao real nao sera implementada nesta versao. O projeto usa login por credenciais para identificar o utilizador, mas nao emite tokens de producao nem aplica politicas completas por role.
- Protecao/autorizacao de canais WebSocket nao sera implementada nesta versao. Em ambiente local/testing pode ser usado `X-Dev-User-Id`/IDs de sessao para simular utilizadores.
- O realtime sera feito com GatewayWorker/Workerman em vez de Laravel Reverb.
- Os pagamentos sao simulados. O backend modela metodos e estados de pagamento, mas nao integra gateways externos.

## Stack

- PHP/Laravel
- Lighthouse GraphQL
- PostgreSQL
- Queue jobs Laravel
- GatewayWorker/Workerman para WebSockets
- Ray AOP para interceptores transacionais/logging

## Modulos principais

- Utilizadores, moradas e perfis de estafeta/gestores
- Restaurantes, cadeias, catalogo, produtos e opcoes
- Carrinho, checkout, encomendas e pagamentos simulados
- Entregas, ofertas a estafetas e tracking
- Eventos de dominio, outbox, notificacoes e chat
- Promocoes, cupoes e avaliacoes

## Eventos e auditoria

O sistema guarda eventos persistentes nas tabelas:

- `order_events`
- `payment_events`
- `delivery_events`
- `outbox_events`

Os eventos de `order`, `payment` e `delivery` representam historico/auditoria. A tabela `outbox_events` serve para publicar eventos para WebSockets/notificacoes de forma assincrona.

## Como correr

Com Docker/Sail:

```bash
cd Backend
docker compose up -d --build
docker compose exec laravel.test php artisan migrate --seed
```

Testes:

```bash
cd Backend
composer test
```

Nota: os testes de feature dependem de PostgreSQL/PDO disponivel. Em maquina local sem o driver `pgsql`, devem ser corridos dentro do container.
