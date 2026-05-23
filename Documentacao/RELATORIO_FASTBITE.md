# Relatorio do Projeto FastBite

**Unidade Curricular:** Programacao em Dispositivos Web e Moveis  
**Projeto:** 2526-IPP-ESTG-MEI-PEDWM-AC-TP2  
**Instituicao:** Instituto Politecnico do Porto - ESTG  
**Ano letivo:** 2025/2026  
**Data:** Maio de 2026  
**Elementos do grupo:** A preencher  
**Docentes:** A preencher

## Resumo

O FastBite e uma plataforma academica de encomendas e entregas de refeicoes que liga tres atores principais: cliente, restaurante e estafeta. O sistema permite que clientes consultem restaurantes e menus, criem carrinhos, finalizem encomendas, acompanhem o estado da entrega e comuniquem atraves de notificacoes e chat. Do lado do restaurante, a aplicacao web suporta a gestao operacional de pedidos, catalogo, campanhas, avaliacoes, notificacoes e acompanhamento da cozinha. Do lado do estafeta, a aplicacao mobile permite gerir disponibilidade, receber ofertas de entrega, aceitar ou rejeitar servicos, atualizar estados e enviar localizacao para tracking.

Do ponto de vista tecnico, o projeto foi desenvolvido com backend em Laravel, API GraphQL atraves de Lighthouse, base de dados PostgreSQL, frontend web em React/Vite e frontend mobile em React Native/Expo. A comunicacao em tempo real foi implementada com WebSockets usando GatewayWorker/Workerman, de acordo com a implementacao existente no codigo. O sistema aplica uma arquitetura modular e orientada a eventos, recorrendo a outbox, jobs, notificacoes, tracking, chat, pagamentos simulados, auditoria e maquinas de estados para controlar encomendas, pagamentos e entregas.

## Abstract

FastBite is an academic meal ordering and delivery platform connecting three main actors: customer, restaurant and courier. The system allows customers to browse restaurants and menus, create carts, place orders, track deliveries and communicate through notifications and chat. On the restaurant side, the web application supports operational management of orders, catalogue, campaigns, reviews, notifications and kitchen workflow. On the courier side, the mobile application allows couriers to manage availability, receive delivery offers, accept or reject jobs, update delivery states and send location updates for tracking.

From a technical perspective, the project was developed with a Laravel backend, a GraphQL API powered by Lighthouse, PostgreSQL as the relational database, a React/Vite web frontend and a React Native/Expo mobile frontend. Real-time communication was implemented with WebSockets using GatewayWorker/Workerman, matching the actual project implementation. The system follows a modular and event-driven architecture, using an outbox pattern, background jobs, notifications, tracking, chat, simulated payments, audit events and state machines to control orders, payments and deliveries.

## Indice

1. Contextualizacao e Motivacao
2. Enquadramento Tecnologico
3. Conceptualizacao do Problema
4. Arquitetura da Solucao
5. Modelacao e Especificacao
6. Paradigmas e Padroes Aplicados
7. Implementacao do Backend
8. Implementacao dos Frontends
9. Validacao e Testes
10. Resultados Obtidos
11. Gestao do Projeto
12. Limitacoes e Decisoes Assumidas
13. Trabalho Futuro
14. Conclusao
15. Bibliografia
16. Anexos

## 1. Contextualizacao e Motivacao

### 1.1 Introducao

O FastBite foi desenvolvido no contexto da unidade curricular de Programacao em Dispositivos Web e Moveis, com o objetivo de construir uma solucao completa que integrasse backend, aplicacao web, aplicacao mobile, persistencia, comunicacao em tempo real e regras de negocio nao triviais. O dominio escolhido foi o de encomendas e entregas de refeicoes, por ser um problema familiar, rico em estados e adequado para demonstrar interacao entre diferentes perfis de utilizador.

Uma plataforma deste tipo tem de coordenar processos que acontecem em simultaneo: um cliente cria uma encomenda, um restaurante aceita e prepara o pedido, o sistema atribui um estafeta, o estafeta atualiza a localizacao e todos os intervenientes precisam de receber informacao atualizada. Assim, o projeto nao se limita a operacoes CRUD; exige tambem sincronizacao entre interfaces, consistencia de estados, auditoria de eventos e integracao entre canais HTTP/GraphQL e WebSockets.

### 1.2 Motivacao

O dominio de delivery e particularmente interessante para esta unidade curricular porque combina aplicacoes web e moveis com necessidades reais de tempo real. O cliente tende a utilizar uma experiencia mobile, o restaurante beneficia de um painel web de operacao e o estafeta precisa de uma interface movel orientada a disponibilidade, localizacao e entrega.

Ao mesmo tempo, o sistema permite aplicar conceitos tecnicos importantes: programacao funcional em calculos e transformacoes de dados, programacao orientada a aspetos para transacoes, arquitetura orientada a eventos para notificacoes e sincronizacao, maquinas de estados para impedir transicoes invalidas e padroes como Service Layer, Repository, DTO, Factory e Publish-Subscribe.

### 1.3 Objetivos do Projeto

Os objetivos funcionais do FastBite foram definidos em torno dos tres atores principais:

- permitir que clientes consultem restaurantes, menus, produtos e opcoes;
- permitir que clientes adicionem produtos ao carrinho e finalizem uma encomenda;
- suportar pagamentos simulados e aplicacao de promocoes ou cupoes;
- permitir que restaurantes recebam, aceitem, rejeitem e preparem pedidos;
- permitir que restaurantes giram catalogo, disponibilidade de produtos, campanhas e avaliacoes;
- permitir que estafetas indiquem disponibilidade, recebam ofertas e atualizem entregas;
- permitir tracking da entrega atraves de localizacao;
- disponibilizar notificacoes e chat entre participantes.

Os objetivos tecnicos foram:

- implementar um backend modular em Laravel;
- expor uma API GraphQL tipada com Lighthouse;
- persistir o dominio em PostgreSQL;
- separar regras de negocio, acesso a dados e interfaces;
- usar WebSockets com GatewayWorker/Workerman para atualizacoes em tempo real;
- registar eventos de dominio para auditoria;
- usar outbox e jobs para publicar eventos de forma controlada;
- aplicar maquinas de estados para encomendas, pagamentos e entregas;
- validar o sistema atraves de testes unitarios, testes de feature e testes manuais ponta-a-ponta.

### 1.4 Organizacao do Documento

Este relatorio comeca por enquadrar a motivacao e as tecnologias usadas. Depois descreve o dominio, os requisitos e a arquitetura. Segue-se a modelacao, a explicacao dos paradigmas e padroes aplicados, a implementacao do backend e dos frontends, a estrategia de validacao, os resultados obtidos, as limitacoes assumidas, o trabalho futuro e a conclusao. No final sao apresentadas referencias bibliograficas e anexos com os principais artefactos do projeto.

## 2. Enquadramento Tecnologico

### 2.1 Laravel no Backend

O backend foi desenvolvido em Laravel, usando PHP 8.3. Laravel foi escolhido por disponibilizar uma base robusta para aplicacoes web com models Eloquent, migrations, service providers, queues, jobs, configuracao de ambientes, testes com PHPUnit e integracao facilitada com Docker/Sail.

No projeto, Laravel e usado como nucleo da aplicacao de dominio. Os models representam entidades persistentes como `User`, `Restaurant`, `Cart`, `Order`, `Payment`, `Delivery`, `Notification`, `Chat` e `Review`. As migrations definem a estrutura relacional, incluindo tabelas de negocio e tabelas de eventos. Os services concentram regras de negocio, os repositories isolam o acesso a dados e os jobs permitem processamento assincrono, como expiracao de pagamentos, expiracao de ofertas de entrega, publicacao de eventos da outbox e envio de notificacoes push.

### 2.2 GraphQL e Lighthouse

A API foi exposta com GraphQL usando Lighthouse. A escolha de GraphQL permite que as interfaces pecam exatamente os dados necessarios para cada ecra, reduzindo over-fetching e evitando a proliferacao de endpoints REST demasiado especificos.

O schema esta organizado por dominio em `Backend/graphql`, com ficheiros para utilizadores, restaurantes, menus, carrinhos, encomendas, pagamentos, entregas, tracking, notificacoes, avaliacoes, campanhas e chat. As queries sao usadas para leitura, por exemplo obter restaurantes, consultar o carrinho, carregar encomendas de cliente ou restaurante e obter tracking. As mutations sao usadas para escrita, por exemplo adicionar itens ao carrinho, fazer checkout, aceitar encomendas, atualizar localizacao do estafeta, enviar mensagens e marcar notificacoes como lidas.

### 2.3 PostgreSQL e Modelo Relacional

A persistencia foi modelada numa base de dados relacional PostgreSQL. O modelo guarda utilizadores, moradas, restaurantes, cadeias, gestores, categorias, produtos, opcoes, carrinhos, encomendas, pagamentos, entregas, ofertas de entrega, posicoes de estafetas, promocoes, cupoes, avaliacoes, chats, mensagens e notificacoes.

O modelo relacional e adequado porque o dominio tem muitas relacoes fortes: uma encomenda pertence a um cliente e a um restaurante, contem varios itens, possui uma morada de entrega, um pagamento e uma entrega. Existem tambem entidades historicas e de auditoria, como `order_events`, `payment_events`, `delivery_events` e `outbox_events`. Para preservar historico, alguns dados sao guardados como snapshots, por exemplo nomes de produtos, nomes de restaurantes, precos e opcoes no momento da encomenda.

### 2.4 GatewayWorker/Workerman e WebSockets

A comunicacao em tempo real foi implementada com GatewayWorker/Workerman, e nao com Laravel Reverb. Esta decisao e coerente com o codigo, os README e a configuracao atual do projeto.

Os WebSockets sao usados para propagar eventos sem obrigar os clientes a fazer polling constante. Os canais incluem, entre outros, canais por cliente, restaurante, encomenda, estafeta, notificacao e chat. Exemplos de canais usados pelos frontends sao `restaurant.{id}.orders`, `customer.{id}.orders`, `order.{id}.tracking`, `courier.{id}.jobs`, `user.{id}.notifications` e `chat.{id}`.

As chamadas HTTP/GraphQL continuam a ser usadas para operacoes estruturadas, como criar uma encomenda ou consultar historico. Ja os WebSockets sao usados para avisar as interfaces quando algo mudou, como uma nova encomenda, uma alteracao de estado, uma posicao do estafeta, uma notificacao ou uma nova mensagem de chat.

### 2.5 React/Vite no Frontend Web

O frontend web foi desenvolvido com React e Vite. Esta aplicacao e focada principalmente na experiencia do restaurante e inclui ecras para login, navegacao lateral, fila de encomendas, detalhe de encomenda, historico, cozinha virtual, catalogo, campanhas e cupoes, avaliacoes, notificacoes, chat, perfil e estatisticas.

O React permite construir uma interface por componentes, com reutilizacao de elementos como badges de estado, timelines, botoes, dialogos, skeletons, mapas e componentes de avaliacao. O Vite oferece um ambiente rapido de desenvolvimento e build.

### 2.6 React Native/Expo no Frontend Mobile

O frontend mobile foi desenvolvido com React Native e Expo. A aplicacao cobre dois perfis: cliente e estafeta. No fluxo de cliente, a app permite login, home, exploracao de restaurantes, consulta de menus, carrinho, checkout, tracking, historico, perfil, moradas e avaliacoes. No fluxo do estafeta, a app permite alterar disponibilidade, receber ofertas de entrega, aceitar ou rejeitar trabalhos, ver detalhes, atualizar estados e enviar localizacao.

Expo facilita o desenvolvimento mobile, a gestao de permissoes e a integracao com funcionalidades nativas. O projeto usa bibliotecas como `expo-location`, `expo-notifications`, `expo-task-manager` e `react-native-maps` para localizacao, notificacoes, tarefas em background e mapas.

### 2.7 Bibliotecas e Ferramentas Auxiliares

Para alem das tecnologias principais, o projeto usa:

- `react-router-dom` na aplicacao web;
- `leaflet` e `react-leaflet` para mapas no frontend web;
- `react-native-maps` para mapas mobile;
- `expo-location`, `expo-notifications` e `expo-task-manager` no mobile;
- `ray/aop` para programacao orientada a aspetos;
- `spatie/laravel-data` para DTOs;
- `workerman/gateway-worker` e `workerman/gatewayclient` para realtime;
- PHPUnit para testes;
- Docker/Sail para facilitar ambiente local.

## 3. Conceptualizacao do Problema

### 3.1 Descricao do Dominio

O FastBite representa uma plataforma que interliga clientes, restaurantes e estafetas num fluxo de encomenda e entrega. O cliente escolhe produtos de um restaurante, configura opcoes, finaliza checkout e acompanha o estado. O restaurante recebe o pedido, gere a preparacao e atualiza estados. O estafeta recebe uma oferta, aceita a entrega, desloca-se ao restaurante, recolhe a encomenda e entrega ao cliente.

As entidades centrais do dominio incluem:

- `User`, que representa a identidade base;
- `UserAddress`, que guarda moradas de entrega;
- `Courier`, que representa o perfil de estafeta;
- `Restaurant` e `RestaurantChain`, que organizam lojas e cadeias;
- `Category`, `Product`, `ProductOptionGroup`, `ProductOption` e `RestaurantProduct`, que modelam o catalogo;
- `Cart`, `CartItem` e `CartItemOption`, que representam a preparacao da compra;
- `Order`, `OrderItem`, `OrderAddress` e `OrderDiscount`, que representam a encomenda final;
- `Payment` e `PaymentEvent`, que modelam pagamentos simulados;
- `Delivery`, `DeliveryOffer`, `DeliveryEvent` e `CourierPositionHistory`, que modelam entrega e tracking;
- `Promotion` e `Coupon`, que representam campanhas;
- `Notification`, `Chat`, `Message` e `Review`, que suportam comunicacao e feedback.

### 3.2 Atores do Sistema

O cliente e responsavel por pesquisar restaurantes, consultar menus, adicionar produtos ao carrinho, escolher morada, aplicar cupoes, finalizar checkout, acompanhar a encomenda e avaliar o servico.

O restaurante e responsavel por gerir catalogo, produtos, disponibilidade, campanhas e cupoes. Durante o ciclo operacional, recebe pedidos, aceita ou rejeita encomendas, atualiza a preparacao e acompanha historico, notificacoes, chat e avaliacoes.

O estafeta define a sua disponibilidade, recebe ofertas de entrega, aceita ou rejeita servicos, atualiza estados como recolha e entrega, envia localizacao e conclui a entrega.

O sistema executa regras internas: calcula precos, aplica descontos, calcula taxas de entrega, cria pagamentos simulados, atribui estafetas, gere expiracao de ofertas, publica eventos, cria notificacoes, mantem auditoria e sincroniza os clientes ligados por WebSockets.

### 3.3 Requisitos Funcionais

Os requisitos funcionais foram agrupados por modulo:

- autenticacao simplificada para identificar utilizadores em contexto academico;
- gestao de utilizadores, moradas, restaurantes, cadeias e gestores;
- consulta e gestao de catalogo, categorias, produtos, opcoes e disponibilidade local;
- criacao, atualizacao e limpeza de carrinho;
- preview de checkout com subtotal, taxa de entrega, descontos e total;
- checkout com criacao de encomenda e pagamento simulado;
- gestao do ciclo de encomendas pelo restaurante;
- pagamentos simulados com estados e eventos;
- criacao de entregas e atribuicao de estafetas;
- ofertas de entrega com aceitacao, rejeicao e expiracao;
- tracking de localizacao e calculo de rota/ETA;
- notificacoes internas e push tokens;
- chat associado a encomendas;
- campanhas, promocoes e cupoes;
- avaliacoes de restaurante e estafeta;
- auditoria atraves de eventos persistidos.

### 3.4 Requisitos Nao Funcionais

O projeto procurou cumprir requisitos nao funcionais relevantes para este tipo de sistema:

- modularidade, separando dominio, servicos, repositorios, DTOs e interfaces;
- manutencao, evitando concentrar regras em controllers ou resolvers;
- consistencia, atraves de maquinas de estados e transacoes;
- baixa latencia nas atualizacoes visuais, atraves de WebSockets;
- auditabilidade, com eventos persistidos;
- testabilidade, com testes unitarios e de feature;
- extensibilidade, permitindo adicionar novos consumidores de eventos;
- resiliencia parcial, com outbox e jobs reprocessaveis;
- separacao entre fluxos estruturados de API e notificacoes em tempo real.

### 3.5 Decisoes de Ambito

Algumas funcionalidades foram assumidas como fora do ambito desta versao. A autenticacao e autorizacao de producao nao foram implementadas de forma completa. O projeto usa identificacao simplificada adequada ao contexto academico, mas nao emite tokens de producao nem aplica policies completas por role.

A autorizacao fina dos canais WebSocket tambem ficou fora de ambito. Em ambiente local e de demonstracao, os clientes usam IDs de utilizador para subscricao e simulacao de identidade. Em producao, seria obrigatorio validar cada subscricao.

Os pagamentos sao simulados. O sistema modela metodos, estados, expiracao, cancelamento, falha e eventos, mas nao comunica com gateways reais como Stripe, PayPal ou MB Way.

## 4. Arquitetura da Solucao

### 4.1 Visao Geral da Arquitetura

A solucao e composta pelos seguintes blocos:

- frontend web React/Vite para operacao de restaurante;
- frontend mobile React Native/Expo para cliente e estafeta;
- backend Laravel responsavel pelo dominio;
- API GraphQL exposta por Lighthouse;
- base de dados PostgreSQL;
- filas e jobs Laravel;
- servidor WebSocket GatewayWorker/Workerman;
- mecanismo de outbox para publicacao assincrona de eventos.

O frontend comunica com o backend por GraphQL para operacoes principais. Quando uma operacao altera o estado do dominio, o backend regista eventos, cria entradas na outbox e despacha jobs. Esses jobs publicam eventos para WebSockets e notificacoes, permitindo que as interfaces atualizem de forma reativa.

### 4.2 Separacao por Camadas

O backend segue uma separacao clara de responsabilidades:

- `app/Models`: entidades Eloquent persistentes;
- `app/DTOs`: objetos de transferencia de dados para entradas estruturadas;
- `app/Services`: regras de negocio e orquestracao;
- `app/Repositories`: acesso a dados e queries persistentes;
- `app/GraphQL`: resolvers de queries e mutations;
- `app/Domain`: logica pura e maquinas de estados;
- `app/Jobs`: processamento assincrono;
- `app/Gateway`: integracao com GatewayWorker;
- `app/Enums`: estados, tipos de eventos e constantes tipadas.

Esta estrutura reduz acoplamento entre a API e a regra de negocio. Os resolvers GraphQL funcionam como camada de entrada, mas a decisao de negocio esta nos services. Os repositories escondem detalhes de Eloquent, facilitando testes e evolucao.

### 4.3 Fluxo HTTP vs Fluxo Realtime

O FastBite separa dois tipos de comunicacao. O fluxo GraphQL e usado quando uma interface precisa de executar uma operacao ou obter dados de forma estruturada. Exemplos incluem `checkoutOrder`, `getRestaurantOrders`, `acceptDeliveryOffer`, `updateCourierLocation` e `sendChatMessage`.

O fluxo realtime e usado para avisar outras interfaces de que algo mudou. Por exemplo, quando o restaurante altera o estado de uma encomenda, o cliente recebe um evento no canal de encomendas. Quando o estafeta envia localizacao, o cliente recebe atualizacoes no canal de tracking. Quando uma mensagem e enviada, os participantes do chat recebem o evento correspondente.

### 4.4 Arquitetura Event-Driven

O sistema regista eventos de dominio sempre que acontecem alteracoes importantes. Exemplos de eventos incluem:

- `ORDER_CREATED`;
- `ORDER_PAYMENT_COMPLETED`;
- `ORDER_PREPARING`;
- `ORDER_READY`;
- `ORDER_COURIER_ASSIGNED`;
- `ORDER_OUT_FOR_DELIVERY`;
- `ORDER_DELIVERED`;
- `PAYMENT_COMPLETED`;
- `PAYMENT_FAILED`;
- `PAYMENT_EXPIRED`;
- `DELIVERY_ACCEPTED`;
- `DELIVERY_PICKED_UP`;
- `DELIVERY_IN_TRANSIT`;
- `DELIVERY_DELIVERED`;
- `JOB_OFFERED`;
- `JOB_ACCEPTED`;
- `JOB_REJECTED`;
- `JOB_EXPIRED`;
- `USER_NOTIFICATION_CREATED`;
- `CHAT_MESSAGE_SENT`;
- `COURIER_POSITION_UPDATED`.

Esta abordagem traz varios beneficios. A auditoria fica mais completa, porque e possivel reconstruir o historico de uma encomenda, pagamento ou entrega. A publicacao para WebSockets fica desacoplada do service que alterou o estado. Novos consumidores podem ser adicionados no futuro, como email, analytics ou dashboards, sem alterar profundamente a regra de negocio.

### 4.5 Outbox Pattern

O projeto usa o padrao Outbox para reduzir o risco de uma alteracao ser persistida sem que o evento correspondente seja publicado. O fluxo aplicado e:

1. um service valida e altera o estado do dominio;
2. o evento de dominio e guardado na tabela propria, como `order_events`, `payment_events` ou `delivery_events`;
3. o `OutboxService` cria uma entrada em `outbox_events`;
4. o `PublishOutboxEventJob` publica o evento;
5. em caso de erro, o evento pode ficar marcado para tentativa posterior.

No codigo, `OutboxService` centraliza a criacao do evento de outbox e despacha o job apos commit. O `PublishOutboxEventJob` trata eventos especificos como `COURIER_POSITION_UPDATED`, `CHAT_MESSAGE_SENT` e `USER_NOTIFICATION_CREATED`, ou eventos de dominio genericos com canais definidos no payload.

### 4.6 Comunicacao entre Clientes

A comunicacao entre clientes e feita por canais logicos. O restaurante subscreve eventos das suas encomendas. O cliente subscreve eventos das suas encomendas, tracking e notificacoes. O estafeta subscreve ofertas de entrega e eventos associados ao seu trabalho. Os chats usam canais proprios por conversa.

Esta divisao permite que cada interface receba apenas os eventos relevantes para o seu contexto, simplificando a atualizacao visual e evitando pedidos repetidos ao backend.

## 5. Modelacao e Especificacao

### 5.1 Diagrama de Classes

O diagrama de classes encontra-se em `Documentacao/Diagramas/01_diagrama_classes.puml`. Este diagrama organiza as classes por grupos de dominio: utilizadores e perfis, restaurantes e catalogo, carrinho, encomendas, pagamentos, entregas, promocoes, notificacoes, chat, avaliacoes e moradas.

As relacoes principais sao:

- um cliente pode ter varias moradas e varias encomendas;
- um restaurante pertence a uma cadeia e possui produtos locais;
- uma categoria agrupa produtos;
- um produto pode ter grupos de opcoes e opcoes;
- um carrinho contem itens e opcoes selecionadas;
- uma encomenda contem itens, morada, descontos, pagamento, entrega e eventos;
- uma entrega pode ter varias ofertas enviadas a estafetas;
- um chat pertence a uma encomenda e contem participantes e mensagens.

### 5.2 Diagrama Entidade-Relacionamento

O diagrama ER encontra-se em `Documentacao/Diagramas/02_diagrama_er.puml` e traduz o dominio para tabelas relacionais. O modelo privilegia normalizacao nos dados principais e usa tabelas especificas para historico e auditoria.

Destacam-se as tabelas de snapshots da encomenda, como itens, opcoes, morada e descontos. Esta decisao evita que alteracoes futuras no catalogo modifiquem retroativamente uma encomenda ja realizada. Tambem se destacam as tabelas `order_events`, `payment_events`, `delivery_events` e `outbox_events`, que suportam auditoria e integracao realtime.

### 5.3 Diagramas de Sequencia

Os principais fluxos estao representados nos diagramas:

- `03_sequencia_criar_encomenda.puml`;
- `04_sequencia_aceitar_pedido.puml`;
- `05_sequencia_entrega.puml`;
- `06_comunicacao_realtime.puml`.

No fluxo de criacao de encomenda, o cliente consulta o carrinho, escolhe morada e pagamento, executa checkout, e o backend cria encomenda, itens, descontos, pagamento e eventos. No fluxo de aceitacao, o restaurante altera o estado da encomenda e o sistema cria a entrega. No fluxo de entrega, o sistema seleciona estafetas, envia ofertas, aceita a primeira resposta valida e atualiza estados ate a conclusao. No fluxo realtime, os eventos persistidos sao publicados por WebSockets para os canais corretos.

### 5.4 Maquinas de Estados

As maquinas de estados impedem transicoes invalidas e tornam o comportamento do sistema previsivel.

A encomenda usa estados:

- `PENDING`;
- `CONFIRMED`;
- `PREPARING`;
- `READY`;
- `OUT_FOR_DELIVERY`;
- `DELIVERED`;
- `CANCELLED`.

O pagamento usa estados:

- `PENDING`;
- `COMPLETED`;
- `FAILED`;
- `CANCELLED`;
- `REFUNDED`.

A entrega usa estados:

- `PENDING`;
- `PICKED_UP`;
- `IN_TRANSIT`;
- `DELIVERED`;
- `FAILED`.

A oferta de entrega usa estados:

- `PENDING`;
- `ACCEPTED`;
- `REJECTED`;
- `EXPIRED`.

Os diagramas correspondentes estao em `07_state_machine_order.puml`, `08_state_machine_payment.puml` e `09_state_machine_delivery.puml`.

### 5.5 Modelo de Eventos

Os eventos foram organizados por dominio. Eventos de encomenda registam criacao, confirmacao, preparacao, pronto, atribuicao de estafeta, recolha, saida para entrega, entrega e cancelamento. Eventos de pagamento registam criacao, conclusao, falha, expiracao, cancelamento e reembolso. Eventos de entrega registam aceitacao, recolha, transporte, entrega e falha. Eventos de oferta registam oferta, aceitacao, rejeicao e expiracao.

Para alem destes, existem eventos de notificacao, chat e tracking. Estes eventos servem simultaneamente para auditoria e para sincronizacao das interfaces.

## 6. Paradigmas e Padroes Aplicados

### 6.1 Programacao Funcional

O projeto aplica ideias de programacao funcional em partes onde a previsibilidade e importante. O exemplo mais direto e `PricingCalculator`, que concentra funcoes puras para normalizar quantidades, calcular totais de itens, subtotal, descontos e total final. Estas funcoes recebem dados de entrada e devolvem resultados sem depender de estado externo.

Tambem se encontram ideias funcionais em transformacoes de colecoes no backend e nos frontends, no mapeamento de eventos para labels de interface e nos componentes React, que sao compostos a partir de propriedades e estado local. A separacao entre calculo e efeitos colaterais torna o sistema mais testavel.

### 6.2 Programacao Orientada a Aspetos

A programacao orientada a aspetos foi aplicada atraves de `ray/aop`. O projeto define o atributo `Transactional` e o `TransactionInterceptor`. Em vez de escrever manualmente `begin`, `commit` e `rollback` em cada metodo critico, os services podem ser anotados com `#[Transactional]`.

Assim, a regra de negocio fica mais limpa e a preocupacao transversal de transacoes e tratada por infraestrutura. Isto aparece em operacoes como checkout, aceitacao de ofertas, atualizacao de estados e criacao de entregas.

### 6.3 Programacao Event-Driven

A programacao orientada a eventos e uma das bases da arquitetura. Services como `OrderService`, `PaymentService`, `DeliveryService`, `TrackingService`, `ChatService` e `NotificationService` geram eventos quando ocorrem alteracoes relevantes. Esses eventos sao persistidos, colocados na outbox e depois publicados por jobs.

Esta abordagem desacopla a alteracao de estado da notificacao das interfaces. O restaurante nao precisa de consultar continuamente se existem novas encomendas; recebe eventos. O cliente nao precisa de atualizar manualmente o tracking; recebe posicoes do estafeta.

### 6.4 Padrao State

O padrao State foi usado nas maquinas de estados de encomendas, pagamentos e entregas. Cada estado conhece as transicoes validas e impede saltos que nao fazem sentido. Por exemplo, uma encomenda entregue nao deve voltar a preparacao, e um pagamento concluido nao deve regressar a pendente.

No codigo, isto aparece em factories como `OrderStateFactory`, `PaymentStateFactory` e `DeliveryStateFactory`, que constroem o objeto de estado correto a partir do enum atual.

### 6.5 Padrao Repository

O padrao Repository separa o acesso a dados da regra de negocio. Os services dependem de interfaces como `OrderRepositoryInterface`, `DeliveryRepositoryInterface`, `PaymentRepositoryInterface`, `RestaurantRepositoryInterface`, `NotificationRepositoryInterface` e outras. Isto evita que queries Eloquent complexas fiquem espalhadas pela aplicacao.

### 6.6 Padrao Service Layer

A camada de services concentra a logica principal do projeto. Exemplos importantes incluem:

- `OrderService`, responsavel por checkout e ciclo da encomenda;
- `CartService`, responsavel pela manipulacao do carrinho;
- `PaymentService`, responsavel por pagamentos simulados;
- `DeliveryService`, responsavel por entregas e ofertas;
- `TrackingService`, responsavel por localizacao e tracking;
- `NotificationService` e `NotificationFeedService`;
- `ChatService`;
- `PromotionService` e `CouponService`;
- `RestaurantService` e services de catalogo.

### 6.7 Padrao Factory

As factories de estado centralizam a criacao do estado correto. Isto evita condicionais repetidas em varias zonas do codigo e torna explicita a relacao entre enum e comportamento.

### 6.8 Padrao Observer / Publish-Subscribe

O realtime segue a logica de Publish-Subscribe. Os services produzem eventos, a outbox e os jobs publicam-nos, e os clientes subscrevem canais especificos. Desta forma, o cliente, o restaurante e o estafeta recebem atualizacoes sem depender de polling constante.

### 6.9 Padrao DTO

Os DTOs estruturam dados de entrada e reduzem o uso de arrays soltos. Exemplos incluem `CheckoutDTO`, `CreateOrderDTO`, `CreateDeliveryOfferDTO`, `UpdateCourierLocationDTO`, `RegisterPushTokenDTO`, `CreateNotificationDTO` e DTOs de produtos, restaurantes, campanhas e carrinho.

## 7. Implementacao do Backend

### 7.1 Organizacao do Codigo

O backend esta organizado de forma modular:

- `Backend/app/Models`: models Eloquent;
- `Backend/app/Services`: services de dominio;
- `Backend/app/Repositories`: repositories e interfaces;
- `Backend/app/DTOs`: objetos de entrada;
- `Backend/app/Domain`: logica pura, pricing, geo e state machines;
- `Backend/app/GraphQL`: queries e mutations;
- `Backend/app/Gateway`: WebSockets e handlers GatewayWorker;
- `Backend/app/Jobs`: jobs assincronos;
- `Backend/app/Enums`: enums de estados e eventos;
- `Backend/database/migrations`: schema;
- `Backend/tests`: testes unitarios e de feature.

### 7.2 Modelos e Migrations

O schema inicial cria as entidades principais do dominio. Migrations posteriores acrescentam funcionalidades como outbox, push tokens, cupoes avancados, delivery offers, normalizacao de estados e suporte a campanhas polimorficas.

As tabelas mais relevantes incluem `users`, `user_addresses`, `restaurants`, `restaurant_chains`, `categories`, `products`, `restaurant_products`, `carts`, `orders`, `payments`, `deliveries`, `delivery_offers`, `notifications`, `chats`, `messages`, `promotions`, `coupons`, `reviews`, `order_events`, `payment_events`, `delivery_events` e `outbox_events`.

### 7.3 API GraphQL

A API GraphQL esta dividida por ficheiros de dominio. Algumas operacoes relevantes sao:

- `searchRestaurants` e `getRestaurantMenu`;
- `addCartItem`, `updateCartItem`, `clearCart`;
- `previewCheckout` e `checkoutOrder`;
- `getClientOrders` e `getRestaurantOrders`;
- `acceptOrderByRestaurant`, `rejectOrderByRestaurant`, `markOrderReady`;
- `ensureCourierProfile`, `updateCourierStatus`, `acceptDeliveryOffer`;
- `markDeliveryPickedUp`, `markDeliveryInTransit`, `markDeliveryDelivered`;
- `orderTracking`, `deliveryTracking`, `updateCourierLocation`;
- `getNotificationsByUserId`, `markNotificationAsRead`, `registerPushToken`;
- `createPromotion`, `createCoupon`;
- `createOrderChat` e `sendChatMessage`.

Esta API tipada funciona como contrato entre backend, web e mobile.

### 7.4 Servicos de Dominio

O `OrderService` gere checkout, criacao de encomendas, snapshots de itens, descontos, pagamentos iniciais e transicoes de estado. Quando uma encomenda e aceite ou preparada, cria a entrega e despacha a atribuicao de estafeta.

O `PaymentService` controla pagamentos simulados, incluindo confirmacao, cancelamento, falha, expiracao e reembolso. Cada transicao regista evento e pode provocar alteracoes na encomenda.

O `DeliveryService` cria entregas, seleciona estafetas disponiveis por distancia, cria ofertas com TTL, trata aceitacao, rejeicao, expiracao e transicoes de entrega.

O `TrackingService` valida coordenadas, atualiza a localizacao do estafeta, grava historico de posicoes e publica eventos de tracking. O `RoutingService` tenta obter rota via OSRM e usa fallback local com calculo geoespacial quando necessario.

### 7.5 Pagamentos Simulados

O sistema suporta metodos como `CASH`, `CARD`, `MBWAY` e `PAYPAL`. O pagamento em dinheiro e tratado como concluido no checkout; os restantes podem ficar pendentes e expirar. O backend modela estados, eventos e ligacao ao ciclo da encomenda, mas nao integra um gateway externo real.

Esta decisao permite demonstrar consistencia de dominio sem depender de servicos externos. Em producao, os estados seriam atualizados por callbacks ou webhooks de um fornecedor de pagamentos.

### 7.6 Atribuicao de Estafetas

A atribuicao de estafetas segue um fluxo controlado:

1. a entrega e criada quando a encomenda entra em preparacao;
2. o job `AssignCourierToDeliveryJob` procura estafetas disponiveis;
3. o sistema ignora estafetas ja tentados;
4. apenas considera estafetas dentro do raio configurado;
5. ordena candidatos por distancia ao restaurante;
6. cria uma oferta de entrega;
7. a oferta expira se nao houver resposta;
8. se for rejeitada ou expirar, o sistema tenta outro estafeta;
9. ao aceitar, o estafeta fica ocupado e a entrega fica associada.

O limite de tentativas evita loops infinitos. Se nao houver estafeta disponivel, a entrega pode falhar de forma controlada.

### 7.7 Notificacoes e Push Tokens

O sistema guarda notificacoes internas e permite registo de push tokens Expo. Eventos de dominio podem originar notificacoes atraves de listeners e jobs. O envio para canais e feito de forma assincrona, separando criacao da notificacao da tentativa de entrega.

As notificacoes sao consultadas por GraphQL e tambem publicadas por WebSockets no canal do utilizador.

### 7.8 Chat

O chat esta associado a encomendas e suporta tipos como conversa cliente-restaurante e cliente-estafeta. Cada chat tem participantes e mensagens. O envio de mensagem pode ser feito via mutation GraphQL ou evento WebSocket, sendo depois persistido e publicado para o canal `chat.{id}`.

### 7.9 GatewayWorker e Eventos Realtime

A pasta `Backend/app/Gateway` contem a logica de mensagens WebSocket. O servidor aceita eventos de cliente como `hello`, `subscribe`, `unsubscribe`, `chat.message.send`, `courier.status.set` e `courier.position.set`. As respostas incluem acknowledgements como `hello.ack`, `subscribe.ack`, `chat.message.send.ack`, `courier.status.ack` e `courier.position.ack`.

Os handlers do GatewayWorker associam sockets a utilizadores e canais, recebem mensagens dos clientes e distribuem eventos enviados pelo backend.

## 8. Implementacao dos Frontends

### 8.1 Frontend Web do Restaurante

A aplicacao web do restaurante esta em `Frontend/web`. O seu objetivo e apoiar a operacao diaria do restaurante. A interface inclui:

- login do restaurante/gestor;
- shell com navegacao lateral;
- fila de encomendas ativas;
- detalhe da encomenda;
- historico;
- cozinha virtual;
- catalogo e produtos;
- catalogo da cadeia;
- campanhas e cupoes;
- notificacoes;
- avaliacoes;
- chat;
- perfil e estatisticas.

O frontend usa services para encapsular comunicacao com a API e modulos realtime para subscrever canais de encomendas, notificacoes, tracking e chat.

### 8.2 Experiencia Mobile do Cliente

No mobile, o cliente pode autenticar-se de forma simplificada, consultar restaurantes, abrir menus, escolher produtos, configurar opcoes, gerir carrinho, fazer preview de checkout, aplicar cupoes e criar encomenda.

Depois do checkout, pode acompanhar a encomenda, ver eventos de estado, receber notificacoes e seguir a localizacao do estafeta quando a entrega esta ativa. O historico permite consultar encomendas anteriores e repetir uma encomenda.

### 8.3 Experiencia Mobile do Estafeta

O fluxo do estafeta esta concentrado em disponibilidade, ofertas e execucao da entrega. O estafeta pode ficar disponivel, receber uma oferta, aceitar ou rejeitar, ver detalhes da entrega, marcar recolha, iniciar entrega, concluir ou reportar falha.

A aplicacao tambem envia localizacao para o backend, permitindo atualizar tracking e ETA para o cliente.

### 8.4 Componentes Reutilizaveis

Os frontends usam componentes comuns para reduzir repeticao e manter consistencia visual. Exemplos incluem:

- `StatusBadge`;
- `MoneyText`;
- `RatingStars`;
- `OrderTimeline`;
- `Skeleton`;
- `ErrorBoundary`;
- `ConfirmDialog`;
- componentes de mapa para web e mobile;
- cards de realtime e tracking.

### 8.5 Gestao de Estado e Integracao com API

A integracao com a API esta organizada por services. No mobile, `commerceService.js` funciona como fachada sobre modulos em `src/services/commerce/`, separados por dominio: restaurantes, carrinho, encomendas, estafeta, tracking, chat, notificacoes, avaliacoes, moradas e pagamentos.

No web, os services como `restaurantOpsService.js`, `chatService.js`, `healthService.js` e `apiClient.js` concentram as chamadas ao backend. A camada realtime fornece funcoes de subscricao para canais e eventos, atualizando a interface quando chegam mensagens.

### 8.6 Mapas, Tracking e Localizacao

O frontend web usa Leaflet e React Leaflet para mapas. O mobile usa React Native Maps. A localizacao do estafeta e enviada para o backend, persistida como historico e publicada para os clientes ligados.

O tracking inclui pontos de rota, distancia, duracao estimada, distancia restante, ETA e identificacao do provider de rota. Quando OSRM nao esta disponivel, o backend usa fallback baseado em distancia geodesica.

## 9. Validacao e Testes

### 9.1 Estrategia de Testes

A validacao combina testes unitarios, testes de feature/GraphQL e testes manuais ponta-a-ponta. Os testes unitarios focam regras de negocio isoladas, enquanto os testes de feature validam operacoes integradas expostas por GraphQL.

### 9.2 Testes Unitarios

O projeto contem testes unitarios para:

- maquinas de estados de encomendas;
- maquinas de estados de pagamentos;
- maquinas de estados de entregas;
- calculo de precos em `PricingCalculator`;
- validacoes de carrinho;
- validacoes de encomenda;
- validacoes de utilizadores e moradas;
- calculo geoespacial em `GeoMath`;
- mapeamento de notificacoes;
- regras de delivery e routing;
- validacoes de reviews e cadeias de restaurantes.

Exemplos de ficheiros incluem `OrderStateMachineTest.php`, `PaymentServiceTransitionTest.php`, `DeliveryStateMachineTest.php`, `PricingCalculatorTest.php`, `GeoMathTest.php` e `NotificationMapperTest.php`.

### 9.3 Testes de Integracao / Feature

Os testes de feature GraphQL validam fluxos de maior nivel. Existem testes para operacoes de restaurante, operacoes de estafeta, tracking, notificacoes, campanhas e atualizacao de estado da entrega.

Exemplos:

- `RestaurantOperationsGraphQLTest.php`;
- `CourierOperationsMutationTest.php`;
- `OrderTrackingQueryTest.php`;
- `NotificationsGraphQLTest.php`;
- `CampaignPromotionItemsMutationTest.php`;
- `UpdateCourierLocationMutationTest.php`;
- `UpdateDeliveryStatusMutationTest.php`.

### 9.4 Testes Manuais E2E

Os cenarios manuais recomendados para demonstracao sao:

1. o cliente abre a app mobile e cria uma encomenda;
2. o restaurante ve o pedido aparecer na fila web;
3. o restaurante inicia preparacao;
4. o sistema cria entrega e envia oferta a um estafeta;
5. o estafeta aceita a oferta no mobile;
6. o cliente acompanha eventos e tracking;
7. o estafeta marca recolha, transito e entrega;
8. as notificacoes e mensagens de chat sao refletidas nas interfaces.

### 9.5 Limitacoes dos Testes

Nao foram realizados testes profundos de carga com muitos utilizadores simultaneos. Tambem nao foi validada seguranca de producao, integracao com pagamentos reais, autorizacao fina de canais WebSocket ou comportamento em redes moveis instaveis. Estas limitacoes sao coerentes com o ambito academico do projeto.

## 10. Resultados Obtidos

### 10.1 Funcionalidades Implementadas

O projeto implementa os principais fluxos pretendidos:

- gestao de restaurantes, cadeias e catalogo;
- carrinho e checkout;
- preview de precos, taxa de entrega, descontos e cupoes;
- encomendas e ciclo de estados;
- pagamentos simulados;
- preparacao pelo restaurante;
- atribuicao de estafeta;
- ofertas de entrega com expiracao;
- tracking com localizacao;
- notificacoes internas e push tokens;
- chat;
- promocoes e cupoes;
- avaliacoes;
- auditoria por eventos;
- sincronizacao realtime via GatewayWorker/Workerman.

### 10.2 Demonstracao dos Fluxos Principais

A demonstracao ideal deve mostrar as tres perspetivas em simultaneo:

- app mobile como cliente;
- frontend web como restaurante;
- app mobile como estafeta.

O ciclo demonstrado deve comecar com o cliente a criar uma encomenda, seguir para o restaurante a receber e preparar o pedido, mostrar a oferta ao estafeta, acompanhar a localizacao em tempo real e terminar com a entrega concluida. Durante o processo, devem ser visiveis notificacoes, eventos e, se possivel, mensagens de chat.

### 10.3 Discussao Tecnica

GraphQL revelou-se adequado para a comunicacao estruturada porque as interfaces precisam de dados compostos, como encomendas com itens, pagamentos, entrega e eventos. WebSockets complementam GraphQL ao enviar atualizacoes de forma imediata.

As maquinas de estados ajudam a manter consistencia e reduzem erros em transicoes. O outbox melhora a fiabilidade da publicacao de eventos. A separacao em services e repositories torna a base de codigo mais facil de manter e testar.

### 10.4 Dificuldades Encontradas

As maiores dificuldades estiveram na coordenacao de estados entre cliente, restaurante e estafeta. Um unico fluxo de encomenda envolve pagamento, preparacao, entrega, notificacoes, tracking e possiveis falhas. Garantir que cada evento chega ao publico correto tambem exigiu uma modelacao cuidadosa de canais.

Outra dificuldade foi alinhar GraphQL com as necessidades reais das interfaces, especialmente quando o frontend precisava de dados agregados. A integracao web, mobile, backend, queues e GatewayWorker exigiu tambem configuracao cuidada do ambiente local.

## 11. Gestao do Projeto

### 11.1 Organizacao da Equipa

O trabalho foi organizado por areas: backend, frontend web, frontend mobile, modelacao, testes e documentacao. Esta divisao permitiu desenvolver partes em paralelo, mantendo o fluxo principal de encomenda como prioridade comum.

### 11.2 Planeamento e Metodologia

A metodologia seguida foi incremental. Primeiro foram definidos os modelos principais e o fluxo base de encomenda. Depois foram acrescentados pagamentos simulados, entrega, tracking, notificacoes, chat, campanhas e avaliacoes. A validacao foi sendo feita de forma progressiva com testes automatizados e verificacao manual dos fluxos.

### 11.3 Controlo de Versoes

O projeto foi gerido em Git, com organizacao por pastas principais: `Backend`, `Frontend` e `Documentacao`. Esta estrutura facilita perceber que artefactos pertencem a cada parte da solucao.

## 12. Limitacoes e Decisoes Assumidas

### 12.1 Autenticacao e Autorizacao

A autenticacao e simplificada por decisao de ambito. Em producao seria necessario implementar JWT, OAuth ou outro mecanismo robusto, alem de roles, policies, protecao de dados sensiveis e autorizacao rigorosa de queries, mutations e canais WebSocket.

### 12.2 Pagamentos

O pagamento e simulado. O sistema representa metodos, estados, eventos e expiracao, mas nao comunica com gateways reais. Em producao seria necessario integrar um fornecedor externo e tratar webhooks, reconciliacao e seguranca transacional.

### 12.3 WebSockets

A comunicacao realtime usa GatewayWorker/Workerman. A autorizacao fina de canais ficou fora de ambito. Em producao, cada subscricao teria de validar se o utilizador pode aceder ao canal solicitado.

### 12.4 Escalabilidade

A arquitetura foi desenhada para evoluir, mas nao foi testada em carga elevada. Evolucoes possiveis incluem multiplas instancias de workers, filas separadas por prioridade, cache, balanceamento de carga, monitorizacao e testes de carga.

## 13. Trabalho Futuro

### 13.1 Seguranca

Como trabalho futuro, seria importante implementar autenticacao robusta, autorizacao por roles, policies por recurso, protecao de canais WebSocket, rate limiting e auditoria de acessos.

### 13.2 Pagamentos Reais

A integracao com pagamentos reais permitiria aproximar o projeto de um cenario de producao. Isto incluiria gateways externos, callbacks, webhooks, validacao de assinaturas, reconciliacao de pagamentos e tratamento de reembolsos reais.

### 13.3 Otimizacao de Rotas

O sistema pode evoluir com atribuicao de estafetas mais inteligente, calculo de ETA mais preciso, otimizacao de multiplas entregas, janelas de tempo e integracao mais profunda com APIs de mapas.

### 13.4 Escalabilidade e Observabilidade

Seria util acrescentar logs estruturados, metricas, dashboards, tracing distribuido, alertas e testes de carga. Estes mecanismos permitiriam avaliar desempenho e diagnosticar problemas em cenarios mais proximos de producao.

### 13.5 Melhorias de Produto

Possiveis melhorias incluem favoritos, recomendacoes, programas de fidelizacao, painel administrativo, suporte a multiplas lojas por cadeia, analitica para restaurantes e melhor gestao de incidentes de entrega.

## 14. Conclusao

O FastBite cumpre os objetivos centrais do projeto ao apresentar uma solucao web e mobile integrada, com backend funcional, API GraphQL, persistencia relacional, comunicacao em tempo real, eventos, outbox, maquinas de estados, programacao orientada a aspetos, ideias de programacao funcional e padroes de desenho aplicados.

Mais do que uma aplicacao de encomendas, o projeto demonstra a complexidade de coordenar varios atores num sistema distribuido. A necessidade de manter estados consistentes, publicar eventos, atualizar interfaces e registar auditoria mostrou a importancia de uma boa modelacao e de uma separacao clara de responsabilidades.

O desenvolvimento permitiu consolidar conhecimentos de backend, frontend web, mobile, GraphQL, WebSockets, filas, estado e testes. As limitacoes identificadas sao sobretudo decisoes de ambito adequadas ao contexto academico, e apontam caminhos claros para evolucao futura.

## 15. Bibliografia

- Laravel Documentation. Disponivel em: https://laravel.com/docs
- Lighthouse GraphQL for Laravel. Disponivel em: https://lighthouse-php.com
- GraphQL Documentation. Disponivel em: https://graphql.org/learn/
- React Documentation. Disponivel em: https://react.dev
- React Native Documentation. Disponivel em: https://reactnative.dev
- Expo Documentation. Disponivel em: https://docs.expo.dev
- Workerman Documentation. Disponivel em: https://www.workerman.net
- GatewayWorker Documentation. Disponivel em: https://www.workerman.net/doc/gateway-worker
- PostgreSQL Documentation. Disponivel em: https://www.postgresql.org/docs/
- Fowler, Martin. Patterns of Enterprise Application Architecture.
- Gamma, Erich et al. Design Patterns: Elements of Reusable Object-Oriented Software.

## 16. Anexos

### Anexo A - Enunciado

O enunciado do trabalho encontra-se em `Documentacao/2526-IPP-ESTG-MEI-PEDWM-AC-TP2.pdf`.

### Anexo B - Diagramas

Os diagramas PlantUML encontram-se em `Documentacao/Diagramas`:

- `01_diagrama_classes.puml`;
- `02_diagrama_er.puml`;
- `03_sequencia_criar_encomenda.puml`;
- `04_sequencia_aceitar_pedido.puml`;
- `05_sequencia_entrega.puml`;
- `06_comunicacao_realtime.puml`;
- `07_state_machine_order.puml`;
- `08_state_machine_payment.puml`;
- `09_state_machine_delivery.puml`.

### Anexo C - Schema GraphQL

O schema GraphQL esta em `Backend/graphql`, dividido por dominios. Os ficheiros principais incluem `schema.graphql`, `common.graphql`, `orders.graphql`, `payments.graphql`, `deliveries.graphql`, `tracking.graphql`, `notifications.graphql`, `campaigns.graphql` e `chat.graphql`.

### Anexo D - Capturas de Ecra

Devem ser incluidas capturas da aplicacao web do restaurante, app mobile cliente, app mobile estafeta, tracking, notificacoes, chat, campanhas e cupoes.

### Anexo E - Excerto de Codigo

Excertos recomendados para apresentacao:

- `Backend/app/Aspects/TransactionInterceptor.php`;
- `Backend/app/Domain/Pricing/PricingCalculator.php`;
- `Backend/app/Services/OutboxService.php`;
- `Backend/app/Jobs/PublishOutboxEventJob.php`;
- `Backend/app/Jobs/AssignCourierToDeliveryJob.php`;
- `Backend/app/Domain/StateMachines/Orders/OrderStateFactory.php`;
- `Backend/app/Domain/StateMachines/Payments/PaymentStateFactory.php`;
- `Backend/app/Domain/StateMachines/Deliveries/DeliveryStateFactory.php`;
- exemplo de mutation GraphQL em `Backend/graphql/orders.graphql`;
- exemplo de subscricao realtime em `Frontend/web/src/services/realtime/topicsRealtime.js` ou `Frontend/mobile/src/services/realtime/topicsRealtime.js`.

### Anexo F - Testes

Os testes encontram-se em `Backend/tests`. Para correr os testes:

```bash
cd Backend
composer test
```

Em ambiente local sem driver PostgreSQL, os testes de feature devem ser corridos dentro do container Docker/Sail, conforme indicado no README do backend.
