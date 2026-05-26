# FastBite - Diagramas atualizados

Esta pasta contém uma nova versão dos diagramas baseada no estado atual da implementação.

## Ficheiros

- `01_diagrama_classes_atual.puml`: classes principais do domínio, camadas de backend e relações com frontends/realtime.
- `02_diagrama_er_atual.puml`: modelo entidade-relação com as tabelas principais das migrations atuais.
- `03a_sequencia_checkout.puml`: fluxo de cliente desde carrinho até checkout, pagamento, outbox e atribuição de estafeta.
- `03b_sequencia_restaurante_preparacao.puml`: fluxo do restaurante ao aceitar/preparar/marcar pedido como pronto.
- `03c_sequencia_estafeta_entrega_tracking.puml`: fluxo de oferta, aceitação, recolha, tracking e entrega.
- `03f_sequencia_catalogo_campanhas.puml`: fluxo de gestão de catálogo, menu local, promoções e cupões.
- `03g_sequencia_avaliacao_pos_entrega.puml`: fluxo de avaliação após entrega e consulta de reviews.
- `04a_estado_encomenda.puml`: máquina de estados de `OrderStatus`.
- `04b_estado_pagamento.puml`: máquina de estados de `PaymentStatus`.
- `04c_estado_entrega.puml`: máquina de estados de `DeliveryStatus`.
- `04d_estado_oferta_entrega.puml`: ciclo de vida de `DeliveryOfferStatus`.
- `04e_estado_estafeta.puml`: ciclo operacional de `CourierStatus`.
- `04f_estado_item_encomenda.puml`: ciclo de vida de `OrderItemStatus`.
- `04g_estado_outbox.puml`: ciclo de publicação de `OutboxStatus`.

## Notas de coerência

- O realtime está representado com GatewayWorker/Workerman, através de eventos Laravel, outbox e `SocketMessageDispatcher`.
- A autenticação/autorização está representada como identificação simplificada por utilizador/sessão, de acordo com as decisões de âmbito.
- Os pagamentos são simulados. O domínio modela estados e eventos, mas não integra gateways externos.
- A base de dados considerada é PostgreSQL, coerente com o `README` e com o uso de `jsonb` nas migrations.
