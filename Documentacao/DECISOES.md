# Decisoes de ambito

Este ficheiro regista decisoes assumidas para a versao entregue do FastBite.

## Fora do ambito desta versao

- Autenticacao/autorizacao real de producao.
- Protecao/autorizacao fina dos canais WebSocket.
- Integracao com gateways reais de pagamento.

## Decisoes tecnicas assumidas

- O realtime usa GatewayWorker/Workerman em vez de Laravel Reverb.
- O pagamento e simulado, mantendo apenas metodos, estados e eventos no dominio.
- O foco da implementacao esta no ciclo funcional de encomenda, restaurante, estafeta, tracking, notificacoes, chat, campanhas/cupoes e auditoria.

## Implicacoes na avaliacao

Os pontos acima devem ser apresentados como decisoes de ambito, nao como funcionalidades incompletas. O sistema continua a manter GraphQL para operacoes estruturadas e WebSockets para sincronizacao em tempo real.
