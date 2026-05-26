# FastBite - Diagramas atualizados

Esta pasta contem uma nova versao dos diagramas baseada no estado atual da implementacao.

## Ficheiros

- `01_diagrama_classes_atual.puml`: classes principais do dominio, camadas de backend e relacoes com frontends/realtime.
- `02_diagrama_er_atual.puml`: modelo entidade-relacao com as tabelas principais das migrations atuais.
- `03a_sequencia_checkout.puml`: fluxo de cliente desde carrinho ate checkout, pagamento, outbox e atribuicao de estafeta.
- `03b_sequencia_restaurante_preparacao.puml`: fluxo do restaurante ao aceitar/preparar/marcar pedido como pronto.
- `03c_sequencia_estafeta_entrega_tracking.puml`: fluxo de oferta, aceitacao, recolha, tracking e entrega.
- `03d_sequencia_notificacoes_chat_realtime.puml`: fluxo de notificacoes, push, chat e publicacao realtime.
- `04a_estado_encomenda.puml`: maquina de estados de `OrderStatus`.
- `04b_estado_pagamento.puml`: maquina de estados de `PaymentStatus`.
- `04c_estado_entrega.puml`: maquina de estados de `DeliveryStatus`.
- `04d_estado_oferta_entrega.puml`: ciclo de vida de `DeliveryOfferStatus`.
- `04e_estado_estafeta.puml`: ciclo operacional de `CourierStatus`.
- `04f_estado_item_encomenda.puml`: ciclo de vida de `OrderItemStatus`.
- `04g_estado_outbox.puml`: ciclo de publicacao de `OutboxStatus`.

## Notas de coerencia

- O realtime esta representado com GatewayWorker/Workerman, atraves de eventos Laravel, outbox e `SocketMessageDispatcher`.
- A autenticacao/autorizacao esta representada como identificacao simplificada por utilizador/sessao, de acordo com as decisoes de ambito.
- Os pagamentos sao simulados. O dominio modela estados e eventos, mas nao integra gateways externos.
- A base de dados considerada e PostgreSQL, coerente com o `README` e com o uso de `jsonb` nas migrations.
