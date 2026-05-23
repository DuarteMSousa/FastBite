# FastBite Web

Aplicacao React/Vite para operacao de restaurante: fila de pedidos, cozinha virtual, menu, catalogo da cadeia, campanhas/cupoes, reviews, notificacoes e chat.

## Decisoes de ambito

- Autenticacao/autorizacao real nao sera implementada nesta versao.
- Protecao de canais WebSocket nao sera implementada nesta versao.
- O realtime usa GatewayWorker/Workerman em vez de Laravel Reverb.
- Pagamentos reais nao serao integrados; apenas sao mostrados/alterados estados simulados vindos do backend.

## Como correr

```bash
npm install
npm run dev
```

## Validacao

```bash
npm run lint
npm run build
```

O build escreve para `dist/`. Se o Windows bloquear a limpeza de `dist`, fecha processos que estejam a servir ficheiros dessa pasta ou apaga a pasta manualmente antes de voltar a correr o build.
