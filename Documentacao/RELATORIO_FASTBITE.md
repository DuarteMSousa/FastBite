# Relatório do Projeto FastBite

**Unidade Curricular:** Programação em Dispositivos Web e Móveis  
**Projeto:** 2526-IPP-ESTG-MEI-PEDWM-AC-TP2  
**Instituição:** Instituto Politécnico do Porto - ESTG  
**Ano letivo:** 2025/2026  
**Data:** Maio de 2026  
**Elementos do grupo:** A preencher  
**Docentes:** A preencher

## Resumo

O FastBite é uma plataforma académica de encomendas e entregas de refeições que liga três atores principais: cliente, restaurante e estafeta. O sistema permite que clientes consultem restaurantes e menus, criem carrinhos, finalizem encomendas, acompanhem o estado da entrega e comuniquem através de notificações e chat. Do lado do restaurante, a aplicação web suporta a gestão operacional de pedidos, catálogo, campanhas, avaliações, notificações e acompanhamento da cozinha. Do lado do estafeta, a aplicação mobile permite gerir disponibilidade, receber ofertas de entrega, aceitar ou rejeitar serviços, atualizar estados e enviar localização para tracking.

Do ponto de vista técnico, o projeto foi desenvolvido com backend em Laravel, API GraphQL através de Lighthouse, base de dados PostgreSQL, frontend web em React/Vite e frontend mobile em React Native/Expo. A comunicação em tempo real foi implementada com WebSockets usando GatewayWorker/Workerman, de acordo com a implementação existente no código. O sistema aplica uma arquitetura modular e orientada a eventos, recorrendo a outbox, jobs, notificações, tracking, chat, pagamentos simulados, auditoria e máquinas de estados para controlar encomendas, pagamentos e entregas.

## Abstract

FastBite is an academic meal ordering and delivery platform connecting three main actors: customer, restaurant and courier. The system allows customers to browse restaurants and menus, create carts, place orders, track deliveries and communicate through notifications and chat. On the restaurant side, the web application supports operational management of orders, catalogue, campaigns, reviews, notifications and kitchen workflow. On the courier side, the mobile application allows couriers to manage availability, receive delivery offers, accept or reject jobs, update delivery states and send location updates for tracking.

From a technical perspective, the project was developed with a Laravel backend, a GraphQL API powered by Lighthouse, PostgreSQL as the relational database, a React/Vite web frontend and a React Native/Expo mobile frontend. Real-time communication was implemented with WebSockets using GatewayWorker/Workerman, matching the actual project implementation. The system follows a modular and event-driven architecture, using an outbox pattern, background jobs, notifications, tracking, chat, simulated payments, audit events and state machines to control orders, payments and deliveries.

## Índice

1. Contextualização e Motivação
2. Enquadramento Tecnológico
3. Conceptualização do Problema
4. Arquitetura da Solução
5. Modelação e Especificação
6. Paradigmas e Padrões Aplicados
7. Implementação do Backend
8. Implementação dos Frontends
9. Validação e Testes
10. Resultados Obtidos
11. Gestão do Projeto
12. Limitações e Decisões Assumidas
13. Trabalho Futuro
14. Conclusão
15. Bibliografia
16. Anexos

## 1. Contextualização e Motivação

### 1.1 Introdução

O FastBite foi desenvolvido no contexto da unidade curricular de Programação em Dispositivos Web e Móveis, com o objetivo de construir uma solução completa que integrasse backend, aplicação web, aplicação mobile, persistência, comunicação em tempo real e regras de negócio não triviais. O domínio escolhido foi o de encomendas e entregas de refeições, por ser um problema familiar, rico em estados e adequado para demonstrar interação entre diferentes perfis de utilizador.

Uma plataforma deste tipo tem de coordenar processos que acontecem em simultâneo: um cliente cria uma encomenda, um restaurante aceita e prepara o pedido, o sistema atribui um estafeta, o estafeta atualiza a localização e todos os intervenientes precisam de receber informação atualizada. Assim, o projeto não se limita a operações CRUD; exige também sincronização entre interfaces, consistência de estados, auditoria de eventos e integração entre canais HTTP/GraphQL e WebSockets.

### 1.2 Motivação

O domínio de delivery é particularmente interessante para esta unidade curricular porque combina aplicações web e móveis com necessidades reais de tempo real. O cliente tende a utilizar uma experiência mobile, o restaurante beneficia de um painel web de operação e o estafeta precisa de uma interface móvel orientada a disponibilidade, localização e entrega.

Ao mesmo tempo, o sistema permite aplicar conceitos técnicos importantes: programação funcional em cálculos e transformações de dados, programação orientada a aspetos para transações, arquitetura orientada a eventos para notificações e sincronização, máquinas de estados para impedir transições inválidas e padrões como Service Layer, Repository, DTO, Factory e Publish-Subscribe.

### 1.3 Objetivos do Projeto

Os objetivos funcionais do FastBite foram definidos em torno dos três atores principais:

- permitir que clientes consultem restaurantes, menus, produtos e opções;
- permitir que clientes adicionem produtos ao carrinho e finalizem uma encomenda;
- suportar pagamentos simulados e aplicação de promoções ou cupões;
- permitir que restaurantes recebam, aceitem, rejeitem e preparem pedidos;
- permitir que restaurantes possam gerir o catálogo, disponibilidade de produtos, campanhas e avaliações;
- permitir que estafetas indiquem disponibilidade, recebam ofertas e atualizem entregas;
- permitir tracking da entrega através de localização;
- disponibilizar notificações e chat entre participantes.

Os objetivos técnicos foram:

- implementar um backend modular em Laravel;
- expor uma API GraphQL tipada com Lighthouse;
- persistir o domínio em PostgreSQL;
- separar regras de negócio, acesso a dados e interfaces;
- usar WebSockets com GatewayWorker/Workerman para atualizações em tempo real;
- registar eventos de domínio para auditoria;
- usar outbox e jobs para publicar eventos de forma controlada;
- aplicar máquinas de estados para encomendas, pagamentos e entregas;
- validar o sistema através de testes unitários, testes de feature e testes manuais ponta-a-ponta.

### 1.4 Organização do Documento

Este relatório começa por enquadrar a motivação e as tecnologias usadas. Depois descreve o domínio, os requisitos e a arquitetura. Segue-se a modelação, a explicação dos paradigmas e padrões aplicados, a implementação do backend e dos frontends, a estratégia de validação, os resultados obtidos, as limitações assumidas, o trabalho futuro e a conclusão. No final são apresentadas referências bibliográficas e anexos com os principais artefactos do projeto.

## 2. Enquadramento Tecnológico

### 2.1 Laravel no Backend

O backend foi desenvolvido em Laravel, usando PHP 8.3. Laravel foi escolhido por disponibilizar uma base robusta para aplicações web com models Eloquent, migrations, service providers, queues, jobs, configuração de ambientes, testes com PHPUnit e integração facilitada com Docker/Sail.

No projeto, Laravel é usado como núcleo da aplicação de domínio. Os models representam entidades persistentes como `User`, `Restaurant`, `Cart`, `Order`, `Payment`, `Delivery`, `Notification`, `Chat` e `Review`. As migrations definem a estrutura relacional, incluindo tabelas de negócio e tabelas de eventos. Os services concentram regras de negócio, os repositories isolam o acesso a dados e os jobs permitem processamento assíncrono, como expiração de pagamentos, expiração de ofertas de entrega, publicação de eventos da outbox e envio de notificações push.

### 2.2 GraphQL e Lighthouse

A API foi exposta com GraphQL usando Lighthouse. A escolha de GraphQL permite que as interfaces peçam exatamente os dados necessários para cada ecrã, reduzindo over-fetching e evitando a proliferação de endpoints REST demasiado específicos.

O schema está organizado por domínio em `Backend/graphql`, com ficheiros para utilizadores, restaurantes, menus, carrinhos, encomendas, pagamentos, entregas, tracking, notificações, avaliações, campanhas e chat. As queries são usadas para leitura, por exemplo obter restaurantes, consultar o carrinho, carregar encomendas de cliente ou restaurante e obter tracking. As mutations são usadas para escrita, por exemplo adicionar itens ao carrinho, fazer checkout, aceitar encomendas, atualizar localização do estafeta, enviar mensagens e marcar notificações como lidas.

### 2.3 PostgreSQL e Modelo Relacional

A persistência foi modelada numa base de dados relacional PostgreSQL. O modelo guarda utilizadores, moradas, restaurantes, cadeias, gestores, categorias, produtos, opções, carrinhos, encomendas, pagamentos, entregas, ofertas de entrega, posições de estafetas, promoções, cupões, avaliações, chats, mensagens e notificações.

O modelo relacional é adequado porque o domínio tem muitas relações fortes: uma encomenda pertence a um cliente e a um restaurante, contém vários itens, possui uma morada de entrega, um pagamento e uma entrega. Existem também entidades históricas e de auditoria, como `order_events`, `payment_events`, `delivery_events` e `outbox_events`. Para preservar histórico, alguns dados são guardados como snapshots, por exemplo nomes de produtos, nomes de restaurantes, preços e opções no momento da encomenda.

### 2.4 GatewayWorker/Workerman e WebSockets

A comunicação em tempo real foi implementada com GatewayWorker/Workerman, e não com Laravel Reverb. Esta decisão é coerente com o código, os README e a configuração atual do projeto.

Os WebSockets são usados para propagar eventos sem obrigar os clientes a fazer polling constante. Os canais incluem, entre outros, canais por cliente, restaurante, encomenda, estafeta, notificação e chat. Exemplos de canais usados pelos frontends são `restaurant.{id}.orders`, `customer.{id}.orders`, `order.{id}.tracking`, `courier.{id}.jobs`, `user.{id}.notifications` e `chat.{id}`.

As chamadas HTTP/GraphQL continuam a ser usadas para operações estruturadas, como criar uma encomenda ou consultar histórico. Já os WebSockets são usados para avisar as interfaces quando algo mudou, como uma nova encomenda, uma alteração de estado, uma posição do estafeta, uma notificação ou uma nova mensagem de chat.

### 2.5 React/Vite no Frontend Web

O frontend web foi desenvolvido com React e Vite. Esta aplicação é focada principalmente na experiência do restaurante e inclui ecrãs para login, navegação lateral, fila de encomendas, detalhe de encomenda, histórico, cozinha virtual, catálogo, campanhas e cupões, avaliações, notificações, chat, perfil e estatísticas.

O React permite construir uma interface por componentes, com reutilização de elementos como badges de estado, timelines, botões, diálogos, skeletons, mapas e componentes de avaliação. O Vite oferece um ambiente rápido de desenvolvimento e build.

### 2.6 React Native/Expo no Frontend Mobile

O frontend mobile foi desenvolvido com React Native e Expo. A aplicação cobre dois perfis: cliente e estafeta. No fluxo de cliente, a app permite login, home, exploração de restaurantes, consulta de menus, carrinho, checkout, tracking, histórico, perfil, moradas e avaliações. No fluxo do estafeta, a app permite alterar disponibilidade, receber ofertas de entrega, aceitar ou rejeitar trabalhos, ver detalhes, atualizar estados e enviar localização.

Expo facilita o desenvolvimento mobile, a gestão de permissões e a integração com funcionalidades nativas. O projeto usa bibliotecas como `expo-location`, `expo-notifications`, `expo-task-manager` e `react-native-maps` para localização, notificações, tarefas em background e mapas.

### 2.7 Bibliotecas e Ferramentas Auxiliares

Para além das tecnologias principais, o projeto usa:

- `react-router-dom` na aplicação web;
- `leaflet` e `react-leaflet` para mapas no frontend web;
- `react-native-maps` para mapas mobile;
- `expo-location`, `expo-notifications` e `expo-task-manager` no mobile;
- `ray/aop` para programação orientada a aspetos;
- `spatie/laravel-data` para DTOs;
- `workerman/gateway-worker` e `workerman/gatewayclient` para realtime;
- PHPUnit para testes;
- Docker/Sail para facilitar ambiente local.

## 3. Conceptualização do Problema

### 3.1 Descrição do Dominio

O FastBite representa uma plataforma que interliga clientes, restaurantes e estafetas num fluxo de encomenda e entrega. O cliente escolhe produtos de um restaurante, configura opções, finaliza checkout e acompanha o estado. O restaurante recebe o pedido, gere a preparação e atualiza estados. O estafeta recebe uma oferta, aceita a entrega, desloca-se ao restaurante, recolhe a encomenda e entrega ao cliente.

As entidades centrais do domínio incluem:

- `User`, que representa a identidade base;
- `UserAddress`, que guarda moradas de entrega;
- `Courier`, que representa o perfil de estafeta;
- `Restaurant` e `RestaurantChain`, que organizam lojas e cadeias;
- `Category`, `Product`, `ProductOptionGroup`, `ProductOption` e `RestaurantProduct`, que modelam o catálogo;
- `Cart`, `CartItem` e `CartItemOption`, que representam a preparação da compra;
- `Order`, `OrderItem`, `OrderAddress` e `OrderDiscount`, que representam a encomenda final;
- `Payment` e `PaymentEvent`, que modelam pagamentos simulados;
- `Delivery`, `DeliveryOffer`, `DeliveryEvent` e `CourierPositionHistory`, que modelam entrega e tracking;
- `Promotion` e `Coupon`, que representam campanhas;
- `Notification`, `Chat`, `Message` e `Review`, que suportam comunicação e feedback.

### 3.2 Atores do Sistema

O cliente é responsável por pesquisar restaurantes, consultar menus, adicionar produtos ao carrinho, escolher morada, aplicar cupões, finalizar checkout, acompanhar a encomenda e avaliar o serviço.

O restaurante é responsável por gerir catálogo, produtos, disponibilidade, campanhas e cupões. Durante o ciclo operacional, recebe pedidos, aceita ou rejeita encomendas, atualiza a preparação e acompanha histórico, notificações, chat e avaliações.

O estafeta define a sua disponibilidade, recebe ofertas de entrega, aceita ou rejeita serviços, atualiza estados como recolha e entrega, envia localização e conclui a entrega.

O sistema executa regras internas: calcula preços, aplica descontos, calcula taxas de entrega, cria pagamentos simulados, atribui estafetas, gere expiração de ofertas, publica eventos, cria notificações, mantém auditoria e sincroniza os clientes ligados por WebSockets.

### 3.3 Requisitos Funcionais

Os requisitos funcionais foram agrupados por módulo:

- autenticação simplificada para identificar utilizadores em contexto académico;
- gestão de utilizadores, moradas, restaurantes, cadeias e gestores;
- consulta e gestão de catálogo, categorias, produtos, opções e disponibilidade local;
- criação, atualização e limpeza de carrinho;
- preview de checkout com subtotal, taxa de entrega, descontos e total;
- checkout com criação de encomenda e pagamento simulado;
- gestão do ciclo de encomendas pelo restaurante;
- pagamentos simulados com estados e eventos;
- criação de entregas e atribuição de estafetas;
- ofertas de entrega com aceitação, rejeição e expiração;
- tracking de localização e cálculo de rota/ETA;
- notificações internas e push tokens;
- chat associado a encomendas;
- campanhas, promoções e cupões;
- avaliações de restaurante e estafeta;
- auditoria através de eventos persistidos.

### 3.4 Requisitos Não Funcionais

O projeto procurou cumprir requisitos não funcionais relevantes para este tipo de sistema:

- modularidade, separando domínio, serviços, repositórios, DTOs e interfaces;
- manutenção, evitando concentrar regras em controllers ou resolvers;
- consistência, através de máquinas de estados e transações;
- baixa latência nas atualizações visuais, através de WebSockets;
- auditabilidade, com eventos persistidos;
- testabilidade, com testes unitários e de feature;
- extensibilidade, permitindo adicionar novos consumidores de eventos;
- resiliência parcial, com outbox e jobs reprocessáveis;
- separação entre fluxos estruturados de API e notificações em tempo real.

### 3.5 Decisões de Âmbito

Algumas funcionalidades foram assumidas como fora do âmbito desta versão. A autenticação e autorização de produção não foram implementadas de forma completa. O projeto usa identificação simplificada adequada ao contexto académico, mas não emite tokens de produção nem aplica policies completas por role.

A autorização fina dos canais WebSocket também ficou fora de âmbito. Em ambiente local e de demonstração, os clientes usam IDs de utilizador para subscrição e simulação de identidade. Em produção, seria obrigatório validar cada subscrição.

Os pagamentos são simulados. O sistema modela métodos, estados, expiração, cancelamento, falha e eventos, mas não comunica com gateways reais como Stripe, PayPal ou MB Way.

## 4. Arquitetura da Solução

### 4.1 Visão Geral da Arquitetura

A solução é composta pelos seguintes blocos:

- frontend web React/Vite para operação de restaurante;
- frontend mobile React Native/Expo para cliente e estafeta;
- backend Laravel responsável pelo domínio;
- API GraphQL exposta por Lighthouse;
- base de dados PostgreSQL;
- filas e jobs Laravel;
- servidor WebSocket GatewayWorker/Workerman;
- mecanismo de outbox para publicação assincrona de eventos.

O frontend comunica com o backend por GraphQL para operações principais. Quando uma operação altera o estado do domínio, o backend regista eventos, cria entradas na outbox e despacha jobs. Esses jobs publicam eventos para WebSockets e notificações, permitindo que as interfaces atualizem de forma reativa.

### 4.2 Separacao por Camadas

O backend segue uma separação clara de responsabilidades:

- `app/Models`: entidades Eloquent persistentes;
- `app/DTOs`: objetos de transferência de dados para entradas estruturadas;
- `app/Services`: regras de negócio e orquestração;
- `app/Repositories`: acesso a dados e queries persistentes;
- `app/GraphQL`: resolvers de queries e mutations;
- `app/Domain`: lógica pura e máquinas de estados;
- `app/Jobs`: processamento assíncrono;
- `app/Gateway`: integração com GatewayWorker;
- `app/Enums`: estados, tipos de eventos e constantes tipadas.

Esta estrutura reduz acoplamento entre a API e a regra de negócio. Os resolvers GraphQL funcionam como camada de entrada, mas a decisão de negócio está nos services. Os repositories escondem detalhes de Eloquent, facilitando testes e evolução.

### 4.3 Fluxo HTTP vs Fluxo Realtime

O FastBite separa dois tipos de comunicação. O fluxo GraphQL é usado quando uma interface precisa de executar uma operação ou obter dados de forma estruturada. Exemplos incluem `checkoutOrder`, `getRestaurantOrders`, `acceptDeliveryOffer`, `updateCourierLocation` e `sendChatMessage`.

O fluxo realtime é usado para avisar outras interfaces de que algo mudou. Por exemplo, quando o restaurante altera o estado de uma encomenda, o cliente recebe um evento no canal de encomendas. Quando o estafeta envia localização, o cliente recebe atualizações no canal de tracking. Quando uma mensagem é enviada, os participantes do chat recebem o evento correspondente.

### 4.4 Arquitetura Event-Driven

O sistema regista eventos de domínio sempre que acontecem alterações importantes. Exemplos de eventos incluem:

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

Esta abordagem traz vários benefícios. A auditoria fica mais completa, porque é possível reconstruir o histórico de uma encomenda, pagamento ou entrega. A publicação para WebSockets fica desacoplada do service que alterou o estado. Novos consumidores podem ser adicionados no futuro, como email, analytics ou dashboards, sem alterar profundamente a regra de negócio.

### 4.5 Outbox Pattern

O projeto usa o padrão Outbox para reduzir o risco de uma alteração ser persistida sem que o evento correspondente seja publicado. O fluxo aplicado é:

1. um service valida e altera o estado do domínio;
2. o evento de domínio é guardado na tabela própria, como `order_events`, `payment_events` ou `delivery_events`;
3. o `OutboxService` cria uma entrada em `outbox_events`;
4. o `PublishOutboxEventJob` publica o evento;
5. em caso de erro, o evento pode ficar marcado para tentativa posterior.

No código, `OutboxService` centraliza a criação do evento de outbox e despacha o job após commit. O `PublishOutboxEventJob` trata eventos específicos como `COURIER_POSITION_UPDATED`, `CHAT_MESSAGE_SENT` e `USER_NOTIFICATION_CREATED`, ou eventos de domínio genéricos com canais definidos no payload.

### 4.6 Comunicacao entre Clientes

A comunicação entre clientes é feita por canais lógicos. O restaurante subscreve eventos das suas encomendas. O cliente subscreve eventos das suas encomendas, tracking e notificações. O estafeta subscreve ofertas de entrega e eventos associados ao seu trabalho. Os chats usam canais próprios por conversa.

Esta divisão permite que cada interface receba apenas os eventos relevantes para o seu contexto, simplificando a atualização visual e evitando pedidos repetidos ao backend.

## 5. Modelação e Especificação

### 5.1 Diagrama de Classes

O diagrama de classes encontra-se em `Documentacao/Diagramas/01_diagrama_classes.puml`. Este diagrama organiza as classes por grupos de domínio: utilizadores e perfis, restaurantes e catálogo, carrinho, encomendas, pagamentos, entregas, promoções, notificações, chat, avaliações e moradas.

As relações principais são:

- um cliente pode ter várias moradas e várias encomendas;
- um restaurante pertence a uma cadeia e possui produtos locais;
- uma categoria agrupa produtos;
- um produto pode ter grupos de opções e opções;
- um carrinho contém itens e opções selecionadas;
- uma encomenda contém itens, morada, descontos, pagamento, entrega e eventos;
- uma entrega pode ter várias ofertas enviadas a estafetas;
- um chat pertence a uma encomenda e contém participantes e mensagens.

### 5.2 Diagrama Entidade-Relacionamento

O diagrama ER encontra-se em `Documentacao/Diagramas/02_diagrama_er.puml` e traduz o domínio para tabelas relacionais. O modelo privilegia normalização nos dados principais e usa tabelas específicas para histórico e auditoria.

Destacam-se as tabelas de snapshots da encomenda, como itens, opções, morada e descontos. Esta decisão evita que alterações futuras no catálogo modifiquem retroativamente uma encomenda já realizada. Também se destacam as tabelas `order_events`, `payment_events`, `delivery_events` e `outbox_events`, que suportam auditoria e integração realtime.

### 5.3 Diagramas de Sequencia

Os principais fluxos estao representados nos diagramas:

- `03_sequencia_criar_encomenda.puml`;
- `04_sequencia_aceitar_pedido.puml`;
- `05_sequencia_entrega.puml`;
- `06_comunicacao_realtime.puml`.

No fluxo de criação de encomenda, o cliente consulta o carrinho, escolhe morada e pagamento, executa checkout, e o backend cria a encomenda, itens, descontos, pagamento e eventos. No fluxo de aceitação, o restaurante altera o estado da encomenda e o sistema cria a entrega. No fluxo de entrega, o sistema seleciona estafetas, envia ofertas, aceita a primeira resposta válida e atualiza estados até a conclusão. No fluxo realtime, os eventos persistidos são publicados por WebSockets para os canais corretos.

### 5.4 Maquinas de Estados

As máquinas de estados impedem transições inválidas e tornam o comportamento do sistema previsível.

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

Os diagramas correspondentes estão em `07_state_machine_order.puml`, `08_state_machine_payment.puml` e `09_state_machine_delivery.puml`.

### 5.5 Modelo de Eventos

Os eventos foram organizados por domínio. Eventos de encomenda registam criação, confirmação, preparação, pronto, atribuição de estafeta, recolha, saída para entrega, entrega e cancelamento. Eventos de pagamento registam criação, conclusão, falha, expiração, cancelamento e reembolso. Eventos de entrega registam aceitação, recolha, transporte, entrega e falha. Eventos de oferta registam oferta, aceitação, rejeição e expiração.

Para além destes, existem eventos de notificação, chat e tracking. Estes eventos servem simultaneamente para auditoria e para sincronização das interfaces.

## 6. Paradigmas e Padrões Aplicados

### 6.1 Programação Funcional

O projeto aplica ideias de programação funcional em partes onde a previsibilidade é importante. O exemplo mais direto é `PricingCalculator`, que concentra funções puras para normalizar quantidades, calcular totais de itens, subtotal, descontos e total final. Estas funções recebem dados de entrada e devolvem resultados sem depender de estado externo.

A implementação reforça conceitos apresentados nos slides de programação funcional:

- funções puras e ausência de efeitos colaterais em `PricingCalculator`, onde os métodos apenas transformam parâmetros em valores;
- composição de funções através de `pipe`, usada para encadear soma, aplicação de quantidade e arredondamento;
- currying/aplicação parcial em `multiplyByQuantity`, que recebe a quantidade e devolve uma função preparada para aplicar essa quantidade a qualquer valor monetário;
- funções de ordem superior em `sumBy`, `discountFor`, filtros de campanhas e scoring de estafetas, que recebem ou devolvem funções;
- operadores ao estilo LINQ/Laravel Collections em `CartService` e `OrderPricingService`, com `map`, `filter`, `flatMap`, `groupBy`, `diff`, `sum` e `values`;
- `yield` em `OrderPricingService`, onde os descontos de promoções são gerados de forma incremental por um generator;
- separação entre cálculo puro e efeitos colaterais: os cálculos de desconto e seleção de candidatos são isolados, enquanto persistência, eventos e jobs ficam nos services.

Também se encontram ideias funcionais nos frontends, no mapeamento de eventos para labels de interface e nos componentes React, que são compostos a partir de propriedades e estado local. A separação entre cálculo e efeitos colaterais torna o sistema mais testável; por exemplo, os testes unitários cobrem `PricingCalculator`, validações funcionais do carrinho, cálculo de descontos e scoring de candidatos sem depender de IO externo.

### 6.2 Programação Orientada a Aspetos

A programação orientada a aspetos foi aplicada através de `ray/aop`. O projeto define o atributo `Transactional` e o `TransactionInterceptor`. Em vez de escrever manualmente `begin`, `commit` e `rollback` em cada método crítico, os services podem ser anotados com `#[Transactional]`.

Assim, a regra de negócio fica mais limpa e a preocupação transversal de transações é tratada por infraestrutura. Isto aparece em operações como checkout, aceitação de ofertas, atualização de estados e criação de entregas.

### 6.3 Programação Event-Driven

A programação orientada a eventos é uma das bases da arquitetura. Services como `OrderService`, `PaymentService`, `DeliveryService`, `TrackingService`, `ChatService` e `NotificationService` geram eventos quando ocorrem alterações relevantes. Esses eventos são persistidos, colocados na outbox e depois publicados por jobs.

Esta abordagem desacopla a alteração de estado da notificação das interfaces. O restaurante não precisa de consultar continuamente se existem novas encomendas; recebe eventos. O cliente não precisa de atualizar manualmente o tracking; recebe posições do estafeta.

### 6.4 Padrao State

O padrão State foi usado nas máquinas de estados de encomendas, pagamentos e entregas. Cada estado conhece as transições válidas e impede saltos que não fazem sentido. Por exemplo, uma encomenda entregue não deve voltar a preparação, e um pagamento concluído não deve regressar a pendente.

No código, isto aparece em factories como `OrderStateFactory`, `PaymentStateFactory` e `DeliveryStateFactory`, que constroem o objeto de estado correto a partir do enum atual.

### 6.5 Padrao Repository

O padrão Repository separa o acesso a dados da regra de negócio. Os services dependem de interfaces como `OrderRepositoryInterface`, `DeliveryRepositoryInterface`, `PaymentRepositoryInterface`, `RestaurantRepositoryInterface`, `NotificationRepositoryInterface` e outras. Isto evita que queries Eloquent complexas fiquem espalhadas pela aplicação.

### 6.6 Padrao Service Layer

A camada de services concentra a lógica principal do projeto. Exemplos importantes incluem:

- `OrderService`, responsável por checkout e ciclo da encomenda;
- `CartService`, responsável pela manipulacao do carrinho;
- `PaymentService`, responsável por pagamentos simulados;
- `DeliveryService`, responsável por entregas e ofertas;
- `TrackingService`, responsável por localização e tracking;
- `NotificationService` e `NotificationFeedService`;
- `ChatService`;
- `PromotionService` e `CouponService`;
- `RestaurantService` e services de catálogo.

### 6.7 Padrao Factory

As factories de estado centralizam a criação do estado correto. Isto evita condicionais repetidas em várias zonas do código e torna explícita a relação entre enum e comportamento.

### 6.8 Padrao Observer / Publish-Subscribe

O realtime segue a lógica de Publish-Subscribe. Os services produzem eventos, a outbox e os jobs publicam-nos, e os clientes subscrevem canais específicos. Desta forma, o cliente, o restaurante e o estafeta recebem atualizações sem depender de polling constante.

### 6.9 Padrao DTO

Os DTOs estruturam dados de entrada e reduzem o uso de arrays soltos. Exemplos incluem `CheckoutDTO`, `CreateOrderDTO`, `CreateDeliveryOfferDTO`, `UpdateCourierLocationDTO`, `RegisterPushTokenDTO`, `CreateNotificationDTO` e DTOs de produtos, restaurantes, campanhas e carrinho.

## 7. Implementação do Backend

### 7.1 Organização do Código

O backend está organizado de forma modular:

- `Backend/app/Models`: models Eloquent;
- `Backend/app/Services`: services de domínio;
- `Backend/app/Repositories`: repositories e interfaces;
- `Backend/app/DTOs`: objetos de entrada;
- `Backend/app/Domain`: lógica pura, pricing, geo e state machines;
- `Backend/app/GraphQL`: queries e mutations;
- `Backend/app/Gateway`: WebSockets e handlers GatewayWorker;
- `Backend/app/Jobs`: jobs assíncronos;
- `Backend/app/Enums`: enums de estados e eventos;
- `Backend/database/migrations`: schema;
- `Backend/tests`: testes unitários e de feature.

### 7.2 Modelos e Migrations

O schema inicial cria as entidades principais do domínio. Migrations posteriores acrescentam funcionalidades como outbox, push tokens, cupões avançados, delivery offers, normalização de estados e suporte a campanhas polimórficas.

As tabelas mais relevantes incluem `users`, `user_addresses`, `restaurants`, `restaurant_chains`, `categories`, `products`, `restaurant_products`, `carts`, `orders`, `payments`, `deliveries`, `delivery_offers`, `notifications`, `chats`, `messages`, `promotions`, `coupons`, `reviews`, `order_events`, `payment_events`, `delivery_events` e `outbox_events`.

### 7.3 API GraphQL

A API GraphQL está dividida por ficheiros de domínio. Algumas operações relevantes são:

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

### 7.4 Serviços de Dominio

O `OrderService` gere checkout, criação de encomendas, snapshots de itens, descontos, pagamentos iniciais e transições de estado. Quando uma encomenda é aceite ou preparada, cria a entrega e despacha a atribuição de estafeta.

O `PaymentService` controla pagamentos simulados, incluindo confirmação, cancelamento, falha, expiração e reembolso. Cada transição regista evento e pode provocar alterações na encomenda.

O `DeliveryService` cria entregas, seleciona estafetas disponíveis por distância, cria ofertas com TTL, trata aceitação, rejeição, expiração e transições de entrega.

O `TrackingService` valida coordenadas, atualiza a localização do estafeta, grava histórico de posições e publica eventos de tracking. O `RoutingService` tenta obter rota via OSRM e usa fallback local com cálculo geoespacial quando necessário.

### 7.5 Pagamentos Simulados

O sistema suporta métodos como `CASH`, `CARD`, `MBWAY` e `PAYPAL`. O pagamento em dinheiro é tratado como concluído no checkout; os restantes podem ficar pendentes e expirar. O backend modela estados, eventos e ligação ao ciclo da encomenda, mas não integra um gateway externo real.

Esta decisão permite demonstrar consistência de domínio sem depender de serviços externos. Em produção, os estados seriam atualizados por callbacks ou webhooks de um fornecedor de pagamentos.

### 7.6 Atribuição de Estafetas

A atribuição de estafetas segue um fluxo controlado:

1. a entrega é criada quando a encomenda entra em preparação;
2. o job `AssignCourierToDeliveryJob` procura estafetas disponíveis;
3. o sistema ignora estafetas já tentados;
4. apenas considera estafetas dentro do raio configurado;
5. ordena candidatos por distância ao restaurante;
6. cria uma oferta de entrega;
7. a oferta expira se não houver resposta;
8. se for rejeitada ou expirar, o sistema tenta outro estafeta;
9. ao aceitar, o estafeta fica ocupado e a entrega fica associada.

O limite de tentativas evita loops infinitos. Se não houver estafeta disponível, a entrega pode falhar de forma controlada.

### 7.7 Notificações e Push Tokens

O sistema guarda notificações internas e permite registo de push tokens Expo. Eventos de domínio podem originar notificações através de listeners e jobs. O envio para canais é feito de forma assíncrona, separando criação da notificação da tentativa de entrega.

As notificações são consultadas por GraphQL e também publicadas por WebSockets no canal do utilizador.

### 7.8 Chat

O chat está associado a encomendas e suporta tipos como conversa cliente-restaurante e cliente-estafeta. Cada chat tem participantes e mensagens. O envio de mensagem pode ser feito via mutation GraphQL ou evento WebSocket, sendo depois persistido e publicado para o canal `chat.{id}`.

### 7.9 GatewayWorker e Eventos Realtime

A pasta `Backend/app/Gateway` contém a lógica de mensagens WebSocket. O servidor aceita eventos de cliente como `hello`, `subscribe`, `unsubscribe`, `chat.message.send`, `courier.status.set` e `courier.position.set`. As respostas incluem acknowledgements como `hello.ack`, `subscribe.ack`, `chat.message.send.ack`, `courier.status.ack` e `courier.position.ack`.

Os handlers do GatewayWorker associam sockets a utilizadores e canais, recebem mensagens dos clientes e distribuem eventos enviados pelo backend.

## 8. Implementação dos Frontends

### 8.1 Frontend Web do Restaurante

A aplicação web do restaurante está em `Frontend/web`. O seu objetivo é apoiar a operação diaria do restaurante. A interface inclui:

- login do restaurante/gestor;
- shell com navegação lateral;
- fila de encomendas ativas;
- detalhe da encomenda;
- histórico;
- cozinha virtual;
- catálogo e produtos;
- catálogo da cadeia;
- campanhas e cupões;
- notificações;
- avaliações;
- chat;
- perfil e estatísticas.

O frontend usa services para encapsular comunicação com a API e módulos realtime para subscrever canais de encomendas, notificações, tracking e chat.

### 8.2 Experiencia Mobile do Cliente

No mobile, o cliente pode autenticar-se de forma simplificada, consultar restaurantes, abrir menus, escolher produtos, configurar opções, gerir carrinho, fazer preview de checkout, aplicar cupões e criar encomenda.

Depois do checkout, pode acompanhar a encomenda, ver eventos de estado, receber notificações e seguir a localização do estafeta quando a entrega está ativa. O histórico permite consultar encomendas anteriores e repetir uma encomenda.

### 8.3 Experiencia Mobile do Estafeta

O fluxo do estafeta está concentrado em disponibilidade, ofertas e execução da entrega. O estafeta pode ficar disponível, receber uma oferta, aceitar ou rejeitar, ver detalhes da entrega, marcar recolha, iniciar entrega, concluir ou reportar falha.

A aplicação também envia localização para o backend, permitindo atualizar tracking e ETA para o cliente.

### 8.4 Componentes Reutilizaveis

Os frontends usam componentes comuns para reduzir repetição e manter consistência visual. Exemplos incluem:

- `StatusBadge`;
- `MoneyText`;
- `RatingStars`;
- `OrderTimeline`;
- `Skeleton`;
- `ErrorBoundary`;
- `ConfirmDialog`;
- componentes de mapa para web e mobile;
- cards de realtime e tracking.

### 8.5 Gestão de Estado e Integração com API

A integração com a API está organizada por services. No mobile, `commerceService.js` funciona como fachada sobre módulos em `src/services/commerce/`, separados por domínio: restaurantes, carrinho, encomendas, estafeta, tracking, chat, notificações, avaliações, moradas e pagamentos.

No web, os services como `restaurantOpsService.js`, `chatService.js`, `healthService.js` e `apiClient.js` concentram as chamadas ao backend. A camada realtime fornece funções de subscrição para canais e eventos, atualizando a interface quando chegam mensagens.

### 8.6 Mapas, Tracking e Localização

O frontend web usa Leaflet e React Leaflet para mapas. O mobile usa React Native Maps. A localização do estafeta é enviada para o backend, persistida como histórico e publicada para os clientes ligados.

O tracking inclui pontos de rota, distância, duração estimada, distância restante, ETA e identificação do provider de rota. Quando OSRM não está disponível, o backend usa fallback baseado em distância geodésica.

## 9. Validação e Testes

### 9.1 Estrategia de Testes

A validação combina testes unitários, testes de feature/GraphQL e testes manuais ponta-a-ponta. Os testes unitários focam regras de negócio isoladas, enquanto os testes de feature validam operações integradas expostas por GraphQL.

### 9.2 Testes Unitarios

O projeto contém testes unitários para:

- máquinas de estados de encomendas;
- máquinas de estados de pagamentos;
- máquinas de estados de entregas;
- cálculo de preços em `PricingCalculator`;
- validações de carrinho;
- validações de encomenda;
- validações de utilizadores e moradas;
- cálculo geoespacial em `GeoMath`;
- mapeamento de notificações;
- regras de delivery e routing;
- validações de reviews e cadeias de restaurantes.

Exemplos de ficheiros incluem `OrderStateMachineTest.php`, `PaymentServiceTransitionTest.php`, `DeliveryStateMachineTest.php`, `PricingCalculatorTest.php`, `GeoMathTest.php` e `NotificationMapperTest.php`.

### 9.3 Testes de Integracao / Feature

Os testes de feature GraphQL validam fluxos de maior nível. Existem testes para operações de restaurante, operações de estafeta, tracking, notificações, campanhas e atualização de estado da entrega.

Exemplos:

- `RestaurantOperationsGraphQLTest.php`;
- `CourierOperationsMutationTest.php`;
- `OrderTrackingQueryTest.php`;
- `NotificationsGraphQLTest.php`;
- `CampaignPromotionItemsMutationTest.php`;
- `UpdateCourierLocationMutationTest.php`;
- `UpdateDeliveryStatusMutationTest.php`.

### 9.4 Testes Manuais E2E

Os cenários manuais recomendados para demonstração são:

1. o cliente abre a app mobile e cria uma encomenda;
2. o restaurante ve o pedido aparecer na fila web;
3. o restaurante inicia preparação;
4. o sistema cria entrega e envia oferta a um estafeta;
5. o estafeta aceita a oferta no mobile;
6. o cliente acompanha eventos e tracking;
7. o estafeta marca recolha, trânsito e entrega;
8. as notificações e mensagens de chat são refletidas nas interfaces.

### 9.5 Limitações dos Testes

Não foram realizados testes profundos de carga com muitos utilizadores simultâneos. Também não foi validada segurança de produção, integração com pagamentos reais, autorização fina de canais WebSocket ou comportamento em redes móveis instáveis. Estas limitações são coerentes com o âmbito académico do projeto.

## 10. Resultados Obtidos

### 10.1 Funcionalidades Implementadas

O projeto implementa os principais fluxos pretendidos:

- gestão de restaurantes, cadeias e catálogo;
- carrinho e checkout;
- preview de preços, taxa de entrega, descontos e cupões;
- encomendas e ciclo de estados;
- pagamentos simulados;
- preparação pelo restaurante;
- atribuição de estafeta;
- ofertas de entrega com expiração;
- tracking com localização;
- notificações internas e push tokens;
- chat;
- promoções e cupões;
- avaliações;
- auditoria por eventos;
- sincronização realtime via GatewayWorker/Workerman.

### 10.2 Demonstracao dos Fluxos Principais

A demonstração ideal deve mostrar as três perspetivas em simultâneo:

- app mobile como cliente;
- frontend web como restaurante;
- app mobile como estafeta.

O ciclo demonstrado deve começar com o cliente a criar uma encomenda, seguir para o restaurante a receber e preparar o pedido, mostrar a oferta ao estafeta, acompanhar a localização em tempo real e terminar com a entrega concluída. Durante o processo, devem ser visíveis notificações, eventos e, se possível, mensagens de chat.

### 10.3 Discussao Tecnica

GraphQL revelou-se adequado para a comunicação estruturada porque as interfaces precisam de dados compostos, como encomendas com itens, pagamentos, entrega e eventos. WebSockets complementam GraphQL ao enviar atualizações de forma imediata.

As máquinas de estados ajudam a manter consistência e reduzem erros em transições. O outbox melhora a fiabilidade da publicação de eventos. A separação em services e repositories torna a base de código mais fácil de manter e testar.

### 10.4 Dificuldades Encontradas

As maiores dificuldades estiveram na coordenação de estados entre cliente, restaurante e estafeta. Um único fluxo de encomenda envolve pagamento, preparação, entrega, notificações, tracking e possíveis falhas. Garantir que cada evento chega ao público correto também exigiu uma modelação cuidadosa de canais.

Outra dificuldade foi alinhar GraphQL com as necessidades reais das interfaces, especialmente quando o frontend precisava de dados agregados. A integração web, mobile, backend, queues e GatewayWorker exigiu também configuração cuidada do ambiente local.

## 11. Gestão do Projeto

### 11.1 Organização da Equipa

O trabalho foi organizado por áreas: backend, frontend web, frontend mobile, modelação, testes e documentação. Esta divisão permitiu desenvolver partes em paralelo, mantendo o fluxo principal de encomenda como prioridade comum.

### 11.2 Planeamento e Metodologia

A metodologia seguida foi incremental. Primeiro foram definidos os modelos principais e o fluxo base de encomenda. Depois foram acrescentados pagamentos simulados, entrega, tracking, notificações, chat, campanhas e avaliações. A validação foi sendo feita de forma progressiva com testes automatizados e verificação manual dos fluxos.

### 11.3 Controlo de Versões

O projeto foi gerido em Git, com organização por pastas principais: `Backend`, `Frontend` e `Documentacao`. Esta estrutura facilita perceber que artefactos pertencem a cada parte da solução.

## 12. Limitações e Decisões Assumidas

### 12.1 Autenticação e Autorização

A autenticação é simplificada por decisão de âmbito. Em produção seria necessário implementar JWT, OAuth ou outro mecanismo robusto, além de roles, policies, proteção de dados sensíveis e autorização rigorosa de queries, mutations e canais WebSocket.

### 12.2 Pagamentos

O pagamento é simulado. O sistema representa métodos, estados, eventos e expiração, mas não comunica com gateways reais. Em produção seria necessário integrar um fornecedor externo e tratar webhooks, reconciliação e segurança transacional.

### 12.3 WebSockets

A comunicação realtime usa GatewayWorker/Workerman. A autorização fina de canais ficou fora de âmbito. Em produção, cada subscrição teria de validar se o utilizador pode aceder ao canal solicitado.

### 12.4 Escalabilidade

A arquitetura foi desenhada para evoluir, mas não foi testada em carga elevada. Evoluções possíveis incluem múltiplas instâncias de workers, filas separadas por prioridade, cache, balanceamento de carga, monitorização e testes de carga.

## 13. Trabalho Futuro

### 13.1 Segurança

Como trabalho futuro, seria importante implementar autenticação robusta, autorização por roles, policies por recurso, proteção de canais WebSocket, rate limiting e auditoria de acessos.

### 13.2 Pagamentos Reais

A integração com pagamentos reais permitiria aproximar o projeto de um cenário de produção. Isto incluiria gateways externos, callbacks, webhooks, validação de assinaturas, reconciliação de pagamentos e tratamento de reembolsos reais.

### 13.3 Otimização de Rotas

O sistema pode evoluir com atribuição de estafetas mais inteligente, cálculo de ETA mais preciso, otimização de múltiplas entregas, janelas de tempo e integração mais profunda com APIs de mapas.

### 13.4 Escalabilidade e Observabilidade

Seria útil acrescentar logs estruturados, métricas, dashboards, tracing distribuído, alertas e testes de carga. Estes mecanismos permitiriam avaliar desempenho e diagnosticar problemas em cenários mais próximos de produção.

### 13.5 Melhorias de Produto

Possíveis melhorias incluem favoritos, recomendações, programas de fidelização, painel administrativo, suporte a múltiplas lojas por cadeia, analítica para restaurantes e melhor gestão de incidentes de entrega.

## 14. Conclusão

O FastBite cumpre os objetivos centrais do projeto ao apresentar uma solução web e mobile integrada, com backend funcional, API GraphQL, persistência relacional, comunicação em tempo real, eventos, outbox, máquinas de estados, programação orientada a aspetos, ideias de programação funcional e padrões de desenho aplicados.

Mais do que uma aplicação de encomendas, o projeto demonstra a complexidade de coordenar vários atores num sistema distribuído. A necessidade de manter estados consistentes, publicar eventos, atualizar interfaces e registar auditoria mostrou a importância de uma boa modelação e de uma separação clara de responsabilidades.

O desenvolvimento permitiu consolidar conhecimentos de backend, frontend web, mobile, GraphQL, WebSockets, filas, estado e testes. As limitações identificadas são sobretudo decisões de âmbito adequadas ao contexto académico, e apontam caminhos claros para evolução futura.

## 15. Bibliografia

- Laravel Documentation. Disponível em: https://laravel.com/docs
- Lighthouse GraphQL for Laravel. Disponível em: https://lighthouse-php.com
- GraphQL Documentation. Disponível em: https://graphql.org/learn/
- React Documentation. Disponível em: https://react.dev
- React Native Documentation. Disponível em: https://reactnative.dev
- Expo Documentation. Disponível em: https://docs.expo.dev
- Workerman Documentation. Disponível em: https://www.workerman.net
- GatewayWorker Documentation. Disponível em: https://www.workerman.net/doc/gateway-worker
- PostgreSQL Documentation. Disponível em: https://www.postgresql.org/docs/
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

O schema GraphQL está em `Backend/graphql`, dividido por domínios. Os ficheiros principais incluem `schema.graphql`, `common.graphql`, `orders.graphql`, `payments.graphql`, `deliveries.graphql`, `tracking.graphql`, `notifications.graphql`, `campaigns.graphql` e `chat.graphql`.

### Anexo D - Capturas de Ecrã

Devem ser incluídas capturas da aplicação web do restaurante, app mobile cliente, app mobile estafeta, tracking, notificações, chat, campanhas e cupões.

### Anexo E - Excerto de Código

Excertos recomendados para apresentação:

- `Backend/app/Aspects/TransactionInterceptor.php`;
- `Backend/app/Domain/Pricing/PricingCalculator.php`;
- `Backend/app/Services/OutboxService.php`;
- `Backend/app/Jobs/PublishOutboxEventJob.php`;
- `Backend/app/Jobs/AssignCourierToDeliveryJob.php`;
- `Backend/app/Domain/StateMachines/Orders/OrderStateFactory.php`;
- `Backend/app/Domain/StateMachines/Payments/PaymentStateFactory.php`;
- `Backend/app/Domain/StateMachines/Deliveries/DeliveryStateFactory.php`;
- exemplo de mutation GraphQL em `Backend/graphql/orders.graphql`;
- exemplo de subscrição realtime em `Frontend/web/src/services/realtime/topicsRealtime.js` ou `Frontend/mobile/src/services/realtime/topicsRealtime.js`.

### Anexo F - Testes

Os testes encontram-se em `Backend/tests`. Para correr os testes:

```bash
cd Backend
composer test
```

Em ambiente local sem driver PostgreSQL, os testes de feature devem ser corridos dentro do container Docker/Sail, conforme indicado no README do backend.
