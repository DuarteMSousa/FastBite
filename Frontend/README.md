# FastBite Frontend

Este diretorio contem os dois clientes do projeto:

- `web`: aplicacao React/Vite para gestao do restaurante.
- `mobile`: aplicacao React Native/Expo para cliente e estafeta.

## Decisoes de ambito

Estas decisoes fazem parte do ambito atual do TP e nao sao consideradas funcionalidades em falta:

- Autenticacao/autorizacao real nao sera implementada nesta versao.
- Protecao de canais WebSocket nao sera implementada nesta versao.
- O realtime usa GatewayWorker/Workerman em vez de Laravel Reverb.
- Pagamentos reais nao serao integrados; o pagamento e simulado atraves dos estados expostos pelo backend.

## Frontend web

```bash
cd Frontend/web
npm install
npm run dev
```

Por omissao, o Vite abre em `http://localhost:5173`.

Variaveis uteis:

- `VITE_API_BASE_URL`
- `VITE_GATEWAY_WORKER_HOST`
- `VITE_GATEWAY_WORKER_PORT`
- `VITE_GATEWAY_WORKER_SCHEME`

## Frontend mobile

```bash
cd Frontend/mobile
npm install
npm start
```

Scripts uteis:

```bash
npm run android
npm run ios
npm run web
npm run lint
```

Variaveis uteis:

- `EXPO_PUBLIC_API_BASE_URL`
- `EXPO_PUBLIC_GATEWAY_WORKER_HOST`
- `EXPO_PUBLIC_GATEWAY_WORKER_PORT`
- `EXPO_PUBLIC_GATEWAY_WORKER_SCHEME`

## Organizacao mobile

A camada `src/services/commerceService.js` e uma fachada para modulos separados em `src/services/commerce/`, agrupados por dominio: restaurantes, carrinho, encomendas, estafeta, tracking, chat, notificacoes, avaliacoes, moradas e pagamentos.
