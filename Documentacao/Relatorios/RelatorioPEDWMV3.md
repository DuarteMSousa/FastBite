# FastBite — PEDWM

**Alexandre Freitas (8220154), Duarte Sousa (8220160), Paulo Coelho (8220195)**

*05/2026*

---

## Resumo

O presente projeto consiste na conceção, desenvolvimento e implementação da plataforma **FastBite**, uma aplicação para a gestão completa do ciclo de vida de encomendas e entregas de refeições, permitindo a interação coordenada entre três atores principais: clientes, restaurantes e estafetas.

O principal objetivo do sistema é responder aos desafios associados à sincronização de estados num ambiente multiutilizador, onde diferentes atores necessitam de acesso contínuo e atualizado à informação. Para tal, foi adotada uma arquitetura orientada a eventos, combinando a utilização de **GraphQL** para operações estruturadas e **WebSockets** para comunicação bidirecional em tempo real, garantindo baixa latência e elevada responsividade.

O backend foi desenvolvido em **Laravel**, recorrendo ao package **Lighthouse** para suporte a GraphQL e à biblioteca **Workerman/GatewayWorker** para gestão de WebSockets. O sistema inclui dois clientes distintos: um frontend web desenvolvido em **React/Vite**, dirigido ao perfil de restaurante, e um frontend mobile desenvolvido em **React Native/Expo**, que cobre simultaneamente os perfis de cliente e de estafeta.

A aplicação suporta um conjunto abrangente de funcionalidades, incluindo exploração de restaurantes, gestão de carrinho de compras com opções configuráveis, realização de encomendas com promoções e cupões, pagamentos simulados, atribuição automática de estafetas via ofertas temporizadas, acompanhamento da entrega em tempo real através de tracking GPS, gestão de menus e campanhas por parte dos restaurantes, comunicação por chat e notificações push. Adicionalmente, foi implementado um padrão **Outbox** para garantir a publicação fiável de eventos, juntamente com um registo de auditoria completo de todas as transições de estado.

Durante o desenvolvimento foram aplicados conceitos de **programação funcional**, **programação orientada a aspetos** e **programação event-driven**, bem como diversos padrões de desenho (State, Strategy, Command, Observer, Singleton e Factory), promovendo a modularidade, escalabilidade e facilidade de manutenção do sistema.

Os resultados obtidos demonstram a eficácia da utilização de tecnologias modernas e arquiteturas reativas na construção de aplicações distribuídas em tempo real, evidenciando a importância da separação de responsabilidades e da modelação adequada do domínio na construção de sistemas robustos e extensíveis.

## Abstract

This project focuses on the design, development, and implementation of **FastBite**, a platform for managing the full lifecycle of food ordering and delivery, enabling coordinated interaction between three main actors: customers, restaurants, and couriers.

The main objective of the system is to address the challenges associated with state synchronization in a multi-user environment, where different actors require continuous and up-to-date access to information. To achieve this, an event-driven architecture was adopted, combining **GraphQL** for structured operations and **WebSockets** for real-time bidirectional communication, ensuring low latency and high responsiveness.

The backend was developed using **Laravel**, leveraging **Lighthouse** for GraphQL support and **Workerman/GatewayWorker** for WebSocket communication. The system includes two distinct client applications: a **React/Vite** web frontend, targeted at the restaurant role, and a **React Native/Expo** mobile frontend that covers both the customer and the courier roles.

The application supports a wide range of functionalities, including restaurant browsing, shopping cart management with configurable options, order placement with promotions and coupons, simulated payments, automatic courier assignment via timed offers, real-time GPS delivery tracking, restaurant menu and campaign management, in-app chat and push notifications. Additionally, an **Outbox** pattern was implemented to ensure reliable event publication, alongside a complete audit log of every state transition.

During development, concepts such as **functional programming**, **aspect-oriented programming**, and **event-driven programming** were applied, together with several design patterns (State, Strategy, Command, Observer, Singleton, and Factory), enhancing the system's modularity, scalability, and maintainability.

The results demonstrate the effectiveness of modern technologies and reactive architectures in building real-time distributed systems, highlighting the importance of proper domain modeling and separation of concerns in the development of robust and extensible applications.

---

## Índice

- Resumo
- Abstract
- Lista de Figuras
- Lista de Tabelas
- **Capítulo 1 — Contextualização e Motivação**
  - 1.1 Introdução
  - 1.2 Enquadramento Tecnológico
  - 1.3 Conhecimento Prévio das Tecnologias
- **Capítulo 2 — Conceptualização do Problema**
  - 2.1 A Plataforma FastBite
  - 2.2 Arquitetura Conceptual do Sistema
  - 2.3 Principais Desafios Técnicos
- **Capítulo 3 — Modelação e Especificação do Sistema**
  - 3.1 Introdução
  - 3.2 Diagrama de Classes e Aplicação de Design Patterns
  - 3.3 Diagrama ER
  - 3.4 Diagramas de Sequência
  - 3.5 Máquinas de Estado
  - 3.6 Comunicação em Tempo Real e Integração com o Domínio
- **Capítulo 4 — Implementação da Solução**
  - 4.1 Arquitetura Implementada
  - 4.2 Implementação do Backend
  - 4.3 API GraphQL
  - 4.4 Comunicação em Tempo Real com GatewayWorker
  - 4.5 Implementação dos Frontends
  - 4.6 Validação e Testes
  - 4.7 Organização do Projeto e Gestão do Trabalho
- **Capítulo 5 — Discussão dos Resultados e Conclusão**
  - 5.1 Apresentação e discussão de resultados
  - 5.2 Limitações
  - 5.3 Conclusão e trabalho futuro
- Bibliografia
- Anexos

## Lista de Figuras

- Figura 1 — Arquitetura Conceptual do Sistema
- Figura 2 — Diagrama de Classes (visão geral)
- Figura 3 — Padrão State aplicado à entidade `Order`
- Figura 4 — Padrão Observer (Domain Event → Outbox → WebSocket)
- Figura 5 — Diagrama ER
- Figura 6 — Diagrama de Sequência: Criar Encomenda
- Figura 7 — Diagrama de Sequência: Aceitar Pedido e Atribuir Estafeta
- Figura 8 — Diagrama de Sequência: Entrega em Tempo Real
- Figura 9 — Máquina de Estados — `Order`
- Figura 10 — Máquina de Estados — `Payment`
- Figura 11 — Máquina de Estados — `Delivery`
- Figura 12 — Fluxo de Comunicação em Tempo Real
- Figura 13 — Mobile (Cliente): Home e Restaurantes
- Figura 14 — Mobile (Cliente): Tracking da Entrega
- Figura 15 — Mobile (Estafeta): Oferta de Entrega
- Figura 16 — Web (Restaurante): Fila de Encomendas

## Lista de Tabelas

- Tabela 1 — Atores do Sistema e Responsabilidades
- Tabela 2 — Transições de Estado — `Order`
- Tabela 3 — Transições de Estado — `Payment`
- Tabela 4 — Transições de Estado — `Delivery`
- Tabela 5 — Distribuição de Story Points
- Tabela 6 — Casos de Teste (Backend)

---

# Capítulo 1

## Contextualização e Motivação

### 1.1 Introdução

No contexto da unidade curricular de **Paradigmas Emergentes para o Desenvolvimento Web e Mobile**, foi proposto o desenvolvimento de uma aplicação que integrasse diferentes paradigmas de programação e tecnologias modernas de comunicação em tempo real.

Neste âmbito, o presente projeto consiste na conceção e desenvolvimento da plataforma **FastBite**, uma aplicação digital para gestão de encomendas e entregas de refeições, permitindo a interação em tempo real entre clientes, restaurantes e estafetas.

A escolha do domínio de delivery foi propositada: é um problema familiar mas rico em estados e transições, exige coordenação contínua entre três perfis muito distintos, e impõe naturalmente requisitos de tempo real (notificações, atribuição de estafeta, tracking GPS) que vão além de operações CRUD triviais. Plataformas comerciais como Uber Eats, Glovo ou Bolt Food enfrentam diariamente desafios significativos relacionados com:

- sincronização de estados entre múltiplos intervenientes;
- atualização em tempo real do estado das encomendas e da localização do estafeta;
- escalabilidade do sistema face a múltiplos pedidos concorrentes;
- consistência das transições de estado em fluxos distribuídos;
- gestão fiável de eventos e notificações multi-canal.

A solução proposta baseia-se numa arquitetura moderna orientada a eventos, combinando **GraphQL** para operações estruturadas e **WebSockets** para comunicação em tempo real. Esta combinação permite que cada interface peça exatamente os dados de que necessita (reduzindo over-fetching) e, em paralelo, mantenha uma ligação persistente que propaga eventos do servidor para o cliente sem qualquer polling.

#### 1.1.1 Objetivos

O principal objetivo deste projeto consiste no desenvolvimento de uma aplicação que permita a gestão completa do ciclo de vida de encomendas de refeições, desde a navegação no catálogo até à conclusão da entrega.

De forma mais específica, os objetivos do trabalho dividem-se em **objetivos funcionais** e **objetivos técnicos**.

**Objetivos funcionais:**

- permitir que clientes consultem restaurantes, menus, produtos e opções configuráveis;
- permitir que clientes adicionem produtos ao carrinho e finalizem uma encomenda;
- suportar pagamentos simulados e aplicação de promoções automáticas ou cupões;
- permitir que restaurantes recebam, aceitem, rejeitem e preparem pedidos;
- permitir que restaurantes giram catálogo, disponibilidade de produtos, campanhas e avaliações;
- permitir que estafetas indiquem disponibilidade, recebam ofertas e atualizem o estado da entrega;
- disponibilizar tracking GPS da entrega em tempo real ao cliente;
- suportar chat e notificações entre participantes de uma encomenda.

**Objetivos técnicos:**

- conceber uma arquitetura cliente-servidor modular para um sistema distribuído;
- implementar um backend em Laravel, organizando o código em camadas (Domínio, Aplicação, Infraestrutura);
- expor uma API GraphQL tipada com Lighthouse;
- desenvolver um frontend web em React/Vite e um frontend mobile em React Native/Expo;
- implementar comunicação em tempo real através de WebSockets utilizando GatewayWorker;
- aplicar máquinas de estado para garantir transições válidas em encomendas, pagamentos e entregas;
- implementar um padrão Outbox para garantir a publicação fiável de eventos;
- aplicar **programação funcional**, **programação orientada a aspetos** e **programação event-driven**;
- aplicar padrões de desenho (State, Strategy, Command, Observer, Singleton, Factory) para melhorar a modularidade e manutenibilidade;
- documentar a arquitetura e modelação do sistema através de diagramas UML;
- validar o sistema através de testes unitários, testes de feature (PHPUnit/Pest) e testes manuais ponta-a-ponta.

#### 1.1.2 Resultados

Como resultado do desenvolvimento do projeto, foram obtidos os seguintes artefactos e componentes técnicos:

- Plataforma funcional FastBite, integrando backend e dois frontends multiplataforma;
- Backend Laravel com persistência em PostgreSQL e cerca de 24 services de domínio (`OrderService`, `DeliveryService`, `PaymentService`, `CartService`, `CampaignService`, `NotificationService`, `ChatService`, entre outros);
- API GraphQL modularizada por domínio em `Backend/graphql/`, com queries e mutations tipadas;
- Servidor WebSocket baseado em Workerman/GatewayWorker com canais segmentados por utilizador, restaurante, encomenda, entrega e chat;
- Frontend web em React/Vite, centrado na gestão operacional do restaurante (fila de encomendas, cozinha virtual, campanhas, chat, estatísticas);
- Frontend mobile em React Native/Expo, com dois fluxos distintos: **cliente** (home, restaurantes, carrinho, checkout, tracking, perfil) e **estafeta** (disponibilidade, ofertas, fluxo de entrega, transmissão de localização);
- Implementação de três máquinas de estado (`Order`, `Payment`, `Delivery`) com factory e validação rigorosa de transições;
- Padrão Outbox com job de processamento (`PublishOutboxEventJob`) e tabela `outbox_events` com retry automático;
- Programação Orientada a Aspetos (AOP) através de `ray/aop`, com interceptores `Transactional` aplicados a métodos críticos;
- Suite de testes Backend com ~70 métodos de teste organizados em **Feature** (GraphQL e serviços) e **Unit** (domínio e regras de negócio);
- Documentação técnica detalhada (este relatório) e diagramas UML em PlantUML.

#### 1.1.3 Organização do Documento

O presente relatório encontra-se organizado da seguinte forma:

- **Capítulo 1 — Contextualização e Motivação**: enquadramento do projeto, objetivos, resultados e tecnologias adotadas.
- **Capítulo 2 — Conceptualização do Problema**: descreve o domínio FastBite, os atores envolvidos, o ciclo de vida de uma encomenda e a arquitetura conceptual da solução.
- **Capítulo 3 — Modelação e Especificação do Sistema**: apresenta a modelação técnica da aplicação, incluindo diagrama de classes com aplicação detalhada de design patterns, diagrama ER, diagramas de sequência dos fluxos principais e máquinas de estado.
- **Capítulo 4 — Implementação da Solução**: descreve a arquitetura implementada, a estrutura do backend, a API GraphQL, o servidor WebSocket, os dois frontends, a aplicação dos paradigmas funcional/AOP/event-driven, e a estratégia de validação.
- **Capítulo 5 — Discussão dos Resultados e Conclusão**: análise crítica dos resultados, limitações conhecidas, trabalho futuro e síntese final.

### 1.2 Enquadramento Tecnológico

#### 1.2.1 Laravel (Backend)

O **Laravel** (PHP 8.3) foi adotado como framework base para o desenvolvimento do backend. Trata-se de uma framework PHP robusta e amplamente utilizada na indústria, com um ecossistema maduro que acelera o desenvolvimento e simplifica a manutenção.

A arquitetura do backend tira partido de diversos componentes fundamentais desta framework:

- **Models Eloquent**: representam entidades persistentes (`User`, `Restaurant`, `Cart`, `Order`, `Payment`, `Delivery`, `Notification`, `Chat`, `Review`, entre outras), oferecendo uma representação orientada a objetos das tabelas e suportando relações através de uma sintaxe declarativa;
- **Migrations**: controlo de versão da estrutura da base de dados, incluindo tabelas de negócio e tabelas auxiliares como `outbox_events`, `order_events`, `payment_events` e `delivery_events`;
- **Service Providers**: bindings centralizados em `AppServiceProvider`, `AppRepositoryProvider` e `RayAopServiceProvider`, garantindo a injeção de dependências por interfaces;
- **Jobs e Queues**: processamento assíncrono de tarefas críticas como `AssignCourierToDeliveryJob`, `PublishOutboxEventJob`, `ExpireDeliveryOfferJob`, `ExpirePendingPaymentJob` e `SendPushNotificationJob`;
- **Eventos e Listeners**: integração nativa com o sistema de eventos do Laravel, utilizada como camada de orquestração entre o domínio e a infraestrutura de tempo real;
- **PHPUnit/Pest**: integração de testes automatizados em `Backend/tests/`, cobrindo regras de negócio críticas e mutations GraphQL;
- **Docker/Sail**: ambiente de desenvolvimento contentorizado, garantindo paridade entre máquinas e com produção.

#### 1.2.2 GraphQL e Lighthouse

Para a comunicação entre frontends e backend, optou-se pela utilização de **GraphQL**, suportado no servidor pela biblioteca **Lighthouse**. Ao contrário de uma API REST tradicional, onde cada endpoint devolve uma estrutura de dados fixa, o GraphQL permite ao cliente requisitar apenas a informação estritamente necessária para o ecrã em causa.

Esta escolha tecnológica foi fundamentada em vários aspetos:

- **Redução de over-fetching**: o cliente dita a forma dos dados, evitando o tráfego de informações redundantes que não serão utilizadas na interface — algo particularmente importante na aplicação mobile, sujeita a redes celulares limitadas;
- **Queries e Mutations**: separação clara entre operações de leitura (queries) e de escrita/alteração de estado (mutations);
- **Schemas por domínio**: a definição da API foi modularizada em `Backend/graphql/`, dividindo os schemas por domínio (utilizadores, restaurantes, carrinhos, encomendas, pagamentos, entregas, tracking, notificações, chat, campanhas, avaliações), o que facilita a organização e manutenção à medida que o projeto cresce;
- **Contratos tipados**: o GraphQL garante um contrato fortemente tipado entre o frontend e o backend, minimizando erros de integração e melhorando a experiência de desenvolvimento.

#### 1.2.3 Workerman/GatewayWorker e WebSockets

Para garantir atualizações em tempo real, o sistema recorre ao protocolo WebSocket. Enquanto as chamadas HTTP/GraphQL são adequadas para o carregamento inicial e ações pontuais, os WebSockets permitem uma ligação bidirecional contínua sem necessidade de requisições repetidas (polling).

Nesta versão, a infraestrutura de tempo real foi implementada utilizando **Workerman/GatewayWorker**, uma solução baseada em PHP de elevada performance, integrada no projeto através das dependências `workerman/gateway-worker` e `workerman/gatewayclient`. A camada de integração com Laravel reside em `Backend/app/Gateway/`, com os componentes principais a seguir descritos:

- `GatewayWorkerEvents` — ponto de entrada do servidor de gateway, recebe ligações dos clientes;
- `ClientEventDispatcher` / `ClientEventHandler` — encaminham mensagens enviadas pelos clientes (subscrições a tópicos, acks, mensagens de chat);
- `GatewayClientSocketPusher` — adaptador que permite que o backend Laravel envie mensagens para os clientes ligados ao gateway;
- `ServerEvents/SocketMessageDispatcher` — recebe eventos de domínio do Laravel e encaminha-os para o handler apropriado;
- `ServerEvents/Handlers/*SocketHandler` — handlers especializados que serializam o evento e fazem o broadcast para os canais corretos.

A comunicação está dividida em **canais segmentados** por utilizador, restaurante, encomenda, estafeta e chat, garantindo que cada cliente recebe apenas a informação relevante. Exemplos de canais utilizados pelo sistema:

- `customer.{userId}.orders` — encomendas do cliente;
- `restaurant.{restaurantId}.orders` — fila de pedidos do restaurante;
- `order.{orderId}.tracking` — tracking de uma encomenda específica;
- `courier.{courierId}.jobs` — ofertas de entrega disponíveis para o estafeta;
- `chat.{conversationId}` — conversas individuais.

#### 1.2.4 React/Vite no Frontend Web

O frontend web foi construído com **React** em conjunto com o bundler **Vite**, focando-se predominantemente na interface e experiência de gestão por parte do restaurante. Esta plataforma centraliza as operações do estabelecimento, abordando os seguintes módulos:

- **Gestão de Encomendas**: fila de pedidos ativos (`RestaurantOrdersQueueScreen`), histórico completo e detalhes específicos de cada encomenda (`RestaurantOrderDetailScreen`);
- **Cozinha Virtual**: visualização orientada ao fluxo de preparação dos itens individuais de cada pedido (`RestaurantVirtualKitchenScreen`);
- **Gestão de Menu**: administração do catálogo de produtos, categorias, opções e disponibilidade;
- **Marketing e Qualidade**: criação de campanhas promocionais, gestão de cupões de desconto e visualização das avaliações deixadas pelos clientes (`RestaurantCampaignsScreen`);
- **Comunicação**: chat com cliente/estafeta (`RestaurantChatScreen`) e centro de notificações;
- **Análise**: dashboards para análise de estatísticas e faturação.

A camada de tempo real foi isolada em `services/realtime/`, encapsulando a ligação WebSocket e as subscrições por tópico, de forma que cada ecrã apenas declara em que canais quer ouvir.

#### 1.2.5 React Native/Expo no Frontend Mobile

Para cobrir as necessidades dos **clientes e dos estafetas** num contexto de mobilidade, a aplicação mobile foi desenvolvida em **React Native** com recurso ao ecossistema **Expo**. Esta tecnologia permite a criação de aplicações nativas para iOS e Android a partir de uma base de código unificada em JavaScript/TypeScript.

A mesma aplicação Expo cobre os **dois perfis** num só binário, alternando entre fluxos através do `CourierAppScreen` e do `CustomerAppScreen`:

- **Fluxo do Cliente**: home page, listagem de restaurantes, visualização de menus, gestão do carrinho de compras, checkout, acompanhamento (tracking) da entrega em tempo real (`TrackingScreen`) e consulta de histórico (`OrdersHistoryScreen`);
- **Fluxo do Estafeta**: gestão de disponibilidade, receção de ofertas de entrega, aceitação/rejeição, confirmação de recolha, fluxo de entrega e transmissão contínua de localização.

A infraestrutura mobile apoia-se ainda em diversas integrações nativas: `react-native-maps` para a visualização cartográfica, `expo-location` para captação de coordenadas, `expo-notifications` para alertas push e `expo-task-manager` para processamento em segundo plano, garantindo o tracking fiável do estafeta mesmo com a aplicação em background.

#### 1.2.6 Bibliotecas e Ferramentas Auxiliares

O ecossistema do projeto foi complementado por um conjunto de bibliotecas e ferramentas especializadas:

- **Frontend Web**: `react-router-dom` (navegação), `leaflet` e `react-leaflet` (mapas);
- **Frontend Mobile**: `react-native-maps`, `expo-location`, `expo-notifications`, `expo-task-manager`;
- **Backend**: `spatie/laravel-data` para criação de Data Transfer Objects (DTOs) robustos, `workerman/gateway-worker` e `workerman/gatewayclient` para comunicação real-time;
- **AOP**: a biblioteca `ray/aop` foi integrada para suportar o paradigma de programação orientada a aspetos, focado na interceção de comportamentos (transações, logging) sem poluir a lógica de negócio.

#### 1.2.7 Paradigmas de Programação Aplicados

O desenvolvimento do sistema foi guiado por três paradigmas de programação, escolhidos para dar resposta aos desafios específicos de cada componente da aplicação.

**Programação Funcional.** A Programação Funcional foca-se na composição de funções puras, evitando partilha de estado e mutação. É o paradigma central no desenvolvimento dos dois frontends (React e React Native): a utilização de Hooks e a gestão de estado imutável tornam a UI uma representação previsível do estado num dado momento, reduzindo efeitos colaterais. No backend, é aplicada em pipelines de transformação de dados, recorrendo a `collect()->filter()->map()->sortBy()` para construir respostas e cálculos de pricing/promoções de forma declarativa.

**Programação Orientada a Aspetos (AOP).** Esta abordagem visa o encapsulamento de preocupações transversais (cross-cutting concerns) — registo de logs, gestão de transações, captura de erros — separando-as da lógica central de negócio. No projeto, foi implementada no backend através da biblioteca `ray/aop`, registada como singleton em `RayAopServiceProvider`. Os services principais são proxiados com interceptores que aplicam atributos como `#[Transactional]` a métodos críticos, garantindo o `beginTransaction/commit/rollback` automaticamente.

**Programação Orientada a Eventos.** Este paradigma estrutura a arquitetura em torno da produção, deteção e reação a eventos, promovendo um sistema altamente assíncrono e desacoplado. Manifesta-se de forma evidente em todo o backend: quando uma ação relevante ocorre (ex: pagamento concluído, encomenda aceite, estafeta atribuído), um evento é registado na tabela `*_events` e enfileirado no `outbox_events`. Listeners do Laravel reagem a esses eventos para criar notificações, e o `PublishOutboxEventJob` publica-os no `SocketMessageDispatcher`, que finalmente os envia para os clientes pelos canais WebSocket apropriados.

#### 1.2.8 Plataformas de Entregas

Atualmente, as plataformas digitais de entrega de refeições (tais como Uber Eats, Glovo ou Bolt Food) assumem um papel central no ecossistema da restauração e na economia de partilha. Caracterizam-se pela sua elevada complexidade logística e tecnológica, uma vez que necessitam de orquestrar três intervenientes distintos em simultâneo: o cliente final, o restaurante e o estafeta. Para garantir uma experiência fluida, dependem de arquiteturas distribuídas e de tecnologias de comunicação em tempo real, essenciais para a sincronização de dados e rastreamento de localização.

Frequentemente, estas plataformas recorrem a arquiteturas baseadas em APIs REST combinadas com serviços de terceiros ou mecanismos de polling para a atualização de coordenadas GPS e estados dos pedidos. No entanto, o uso extensivo de polling em redes móveis pode introduzir latência indesejada e resultar num consumo excessivo de bateria e de dados.

No presente projeto foi adotada uma abordagem híbrida e otimizada, baseada na utilização de **GraphQL** para operações de consulta e de comando, combinada com **WebSockets** para a propagação de eventos críticos e localização em tempo real. Esta arquitetura resolve o problema de over-fetching, fundamental para garantir a performance na aplicação móvel, e separa claramente as operações estruturadas do fluxo de eventos assíncronos.

A modelação explícita do domínio com entidades como `Restaurant`, `RestaurantChain`, `Customer`, `Courier`, `Order`, `Payment`, `Delivery`, `DeliveryOffer`, `Cart`, `Promotion`, `Coupon`, `Notification`, `Chat` e `Review`, permite encapsular as complexas regras de negócio, desde o cálculo de disponibilidade à atribuição de estafetas, de forma clara, modular e extensível. A conjugação dos paradigmas orientado a eventos, AOP e funcional constitui também um fator diferenciador, permitindo desacoplar a lógica de negócio das preocupações infraestruturais.

### 1.3 Conhecimento Prévio das Tecnologias

Relativamente às tecnologias e frameworks adotadas no desenvolvimento do projeto, os elementos do grupo não possuíam experiência prévia significativa com as principais ferramentas escolhidas. Este cenário implicou um processo de aprendizagem contínuo, autónomo e desafiante ao longo de todo o trabalho.

A framework **Laravel**, utilizada na construção do backend, constituiu uma novidade absoluta para a equipa. A sua adoção exigiu não só a familiarização com as particularidades do ecossistema PHP moderno, mas também a assimilação de ferramentas e conceitos arquiteturais inerentes à framework. Foi necessário investir tempo na compreensão do ORM Eloquent, na estruturação do código através de Services e Repositories, na utilização do sistema de Events/Listeners, Jobs e Queues, e na configuração e orquestração do ambiente de desenvolvimento com recurso ao Docker e Laravel Sail. A integração de `ray/aop` para AOP em PHP também exigiu pesquisa adicional, dado tratar-se de uma abordagem pouco convencional neste ecossistema.

No que diz respeito ao desenvolvimento do frontend, tanto o **React** (utilizado na plataforma web) como o **React Native** (utilizado na aplicação móvel) representavam tecnologias nunca exploradas pelos membros do grupo. O domínio destas ferramentas obrigou a uma aprendizagem aprofundada dos conceitos fundamentais da biblioteca, nomeadamente a sua abordagem declarativa baseada em componentes, a gestão de estado imutável e a utilização de Hooks (`useState`, `useEffect`, `useMemo`, `useCallback`, custom hooks). A integração com Apollo Client e a manutenção de cache local de queries GraphQL foram pontos particularmente relevantes da curva de aprendizagem.

Adicionalmente, a vertente mobile introduziu desafios específicos, exigindo a familiarização com o ecossistema **Expo** e com as particularidades do desenvolvimento multiplataforma para dispositivos móveis: gestão da navegação nativa, integração de mapas, captação de localização em foreground e background, push notifications com deep linking, e tarefas em segundo plano para o tracking fiável do estafeta.

O **GraphQL** e a sua implementação no Laravel via **Lighthouse**, bem como o **GatewayWorker** para WebSockets em PHP, eram igualmente novidade. A definição de schemas modulares, a configuração de directivas Lighthouse e a integração entre o broadcasting do Laravel e o servidor de gateway exigiram leitura técnica e várias iterações até estabilizarem.

De um modo geral, a ausência de contacto prévio com estas frameworks transformou o desenvolvimento deste projeto numa oportunidade significativa para a consolidação e expansão das competências técnicas do grupo, promovendo não só a aprendizagem de novas tecnologias com elevada relevância na indústria, mas também a sua aplicação prática e integrada na construção de um sistema distribuído de entregas.

---

# Capítulo 2

## Conceptualização do Problema

O desenvolvimento de plataformas de encomendas e entregas de refeições apresenta desafios técnicos significativos, especialmente quando múltiplos atores com papéis distintos necessitam de interagir simultaneamente sobre um estado partilhado do sistema. Sistemas de delivery representam um exemplo claro deste tipo de aplicações, uma vez que exigem coordenação contínua entre clientes, restaurantes e estafetas, validação centralizada de regras de negócio e atualização imediata da informação apresentada a cada participante.

### 2.1 A Plataforma FastBite

No caso específico da **FastBite**, a implementação digital envolve a gestão de vários elementos do domínio, incluindo utilizadores com diferentes perfis, restaurantes organizados em cadeias com catálogo partilhado, carrinhos de compras com opções configuráveis, encomendas com múltiplos itens, pagamentos simulados, entregas com tracking GPS, promoções automáticas, cupões de desconto, notificações multi-canal, chat e avaliações. É necessário garantir que as regras de negócio são aplicadas corretamente pelo sistema, por exemplo, que uma encomenda não pode ser entregue sem antes ter sido preparada, e que todos os participantes recebem atualizações do estado de forma consistente e em tempo real.

Outro desafio relevante consiste na gestão simultânea de múltiplos fluxos independentes. Num dado momento, vários clientes podem estar a criar encomendas, vários restaurantes podem estar a preparar pedidos e vários estafetas podem estar em trânsito, sendo necessário isolar o estado de cada processo e evitar interferência entre fluxos diferentes.

Tendo em conta estes requisitos, foi necessário conceber uma arquitetura que permitisse:

- manter um estado consistente de encomendas, pagamentos e entregas no servidor;
- suportar comunicação em tempo real entre clientes, restaurantes e estafetas;
- gerir múltiplos fluxos concorrentes de forma isolada;
- aplicar corretamente as regras de negócio e transições de estado;
- separar claramente a lógica de domínio da infraestrutura de comunicação;
- garantir a publicação fiável de eventos mesmo perante falhas parciais.

#### 2.1.1 Ciclo de Vida de uma Encomenda

O fluxo central da FastBite é o ciclo de vida de uma encomenda, que envolve a participação coordenada dos três atores principais do sistema.

O processo inicia-se quando um **cliente** consulta os restaurantes disponíveis, explora os respetivos catálogos de produtos e adiciona itens ao seu carrinho de compras. Cada item pode incluir opções configuráveis (ingredientes adicionais, variações, tamanhos), que influenciam o preço final. Após compor o carrinho, o cliente procede ao checkout, momento em que o `OrderPricingService` calcula o total da encomenda, aplica eventuais promoções automáticas ou cupões de desconto introduzidos, e a `OrderService::checkoutOrder` cria a encomenda com estado inicial `PENDING`.

Segue-se a fase de **pagamento**, na qual o sistema regista o método escolhido pelo cliente (cartão, MBWay simulado ou dinheiro) e simula o processamento da transação. O pagamento possui o seu próprio ciclo de estados (`PENDING`, `COMPLETED`, `FAILED`, `CANCELLED`, `REFUNDED`) e pode expirar caso não seja finalizado num determinado período — o `ExpirePendingPaymentJob` trata desta limpeza assíncrona.

Após a confirmação do pagamento, a encomenda transita para `CONFIRMED` e fica disponível para o **restaurante**, que é notificado em tempo real. O restaurante pode aceitar ou rejeitar o pedido; caso aceite, a encomenda transita para `PREPARING` e o sistema dispara o `AssignCourierToDeliveryJob`, responsável por iniciar o processo de atribuição de um estafeta.

A atribuição funciona por **ofertas temporizadas**: o job ordena os estafetas disponíveis por distância geográfica ao restaurante (calculada pelo `RoutingService`/`GeoMath`) e envia uma oferta ao mais próximo, com TTL de 30 segundos. Caso a oferta expire ou seja rejeitada, o `ExpireDeliveryOfferJob` despoleta nova tentativa, até um número máximo de tentativas. Quando um **estafeta** aceita a oferta, a entrega é criada/atualizada e ambos os fluxos (encomenda e entrega) ficam sincronizados.

Quando todos os itens estão prontos, a encomenda é marcada como `READY`. O estafeta desloca-se ao restaurante, confirma o `PICKED_UP` e inicia o transporte (`IN_TRANSIT`). Durante todo este percurso, a aplicação mobile do estafeta envia atualizações de localização periódicas que alimentam o sistema de tracking, permitindo ao cliente acompanhar a entrega em tempo real num mapa. A entrega transita finalmente para `DELIVERED`, sendo que cada transição gera um evento de auditoria persistido em `delivery_events` e publicado via outbox.

Após a entrega, o cliente pode avaliar tanto o restaurante como o estafeta, contribuindo para o sistema de reputação da plataforma.

#### 2.1.2 Atores do Sistema

O sistema distingue quatro perfis, cada um com responsabilidades específicas. A *Tabela 1* sintetiza estas responsabilidades.

**Tabela 1 — Atores do Sistema e Responsabilidades**

| Ator | Responsabilidades |
|---|---|
| **Cliente** (mobile) | Procurar restaurantes; explorar catálogos e menus; gerir carrinho com opções; finalizar encomendas; efetuar pagamentos; acompanhar tracking em tempo real; comunicar via chat; avaliar serviço. |
| **Restaurante** (web) | Receber novas encomendas em tempo real; aceitar/rejeitar pedidos; atualizar estado de preparação por item (cozinha virtual); gerir catálogo de produtos, opções e categorias; gerir campanhas promocionais e cupões; consultar avaliações; consultar estatísticas e faturação. |
| **Estafeta** (mobile) | Indicar disponibilidade; receber ofertas de entrega; aceitar/rejeitar; confirmar recolha no restaurante; transmitir localização em tempo real; marcar entrega como concluída; comunicar com cliente quando necessário. |
| **Sistema** (orquestrador) | Calcular preços e aplicar descontos; validar e executar transições de estado; atribuir estafetas via ofertas; publicar eventos via outbox; manter auditoria; expirar pagamentos e ofertas; despoletar notificações. |

#### 2.1.3 Modelação do Domínio

Para garantir uma representação clara e organizada do sistema, foi realizada uma modelação do domínio baseada nos principais conceitos da plataforma. Esta modelação permite identificar as entidades fundamentais do sistema e as relações existentes entre elas.

Os principais elementos do domínio identificados são:

- **User** — representa um utilizador do sistema, podendo assumir diferentes perfis;
- **Customer** — perfil de cliente que efetua encomendas;
- **Courier** — perfil de estafeta responsável por entregas;
- **RestaurantChain** — cadeia de restaurantes que partilha catálogo e campanhas;
- **Restaurant** — estabelecimento individual pertencente a uma cadeia;
- **Category** — categoria de produtos no catálogo;
- **Product** — produto disponível para encomenda;
- **RestaurantProduct** — ponte entre produto e restaurante, com preço local e disponibilidade;
- **Cart** — carrinho de compras de um cliente, com itens e opções;
- **Order** — encomenda finalizada;
- **OrderItem / OrderItemOption** — item da encomenda com opções configuradas;
- **Payment** — pagamento associado a uma encomenda;
- **Delivery** — entrega física de uma encomenda;
- **DeliveryOffer** — oferta temporizada enviada a um estafeta;
- **Promotion** — campanha promocional com desconto automático;
- **Coupon** — cupão de desconto introduzido pelo cliente;
- **Notification** — notificação enviada a um utilizador (in-app e push);
- **Chat / Message** — conversa entre participantes de uma encomenda;
- **Review** — avaliação feita por um cliente após entrega.

Estas entidades permitem representar de forma estruturada as regras e o fluxo do negócio, garantindo que o estado da plataforma pode ser gerido de forma consistente pelo sistema.

### 2.2 Arquitetura Conceptual do Sistema

A arquitetura do sistema foi concebida seguindo um modelo cliente-servidor com múltiplos clientes heterogéneos, com uma separação clara entre as interfaces de utilizador e a lógica de negócio.

**Figura 1 — Arquitetura Conceptual do Sistema**

```
+-------------------+        +-------------------+        +-------------------+
|  Frontend Web     |        | Frontend Mobile   |        | Frontend Mobile   |
|  (React/Vite)     |        | (React Native)    |        | (React Native)    |
|  perfil:          |        | perfil:           |        | perfil:           |
|  Restaurante      |        | Cliente           |        | Estafeta          |
+---------+---------+        +---------+---------+        +---------+---------+
          |                            |                            |
          | GraphQL (HTTPS)            | GraphQL                    | GraphQL
          | WebSocket                  | WebSocket                  | WebSocket
          v                            v                            v
+---------------------------------------------------------------------------+
|                        Backend Laravel (PHP 8.3)                          |
|  +---------------+   +----------------+   +-----------------------------+ |
|  | GraphQL API   |   | GatewayWorker  |   | Domain & Application        | |
|  | (Lighthouse)  |   | (Workerman)    |   | Services + State Machines   | |
|  +-------+-------+   +--------+-------+   +--------------+--------------+ |
|          |                    ^                          |                |
|          v                    |                          v                |
|  +-------------------+   +----+-------------+   +-----------------------+ |
|  | Repositories      |   | SocketDispatcher |   | Events / Listeners /  | |
|  | (Eloquent)        |   | + Handlers       |   | Outbox + Jobs (Queue) | |
|  +--------+----------+   +------------------+   +-----------+-----------+ |
|           |                                                  |             |
+-----------|--------------------------------------------------|-------------+
            v                                                  v
       +-----------+                                  +-----------------+
       | PostgreSQL|                                  | Queue (database)|
       +-----------+                                  +-----------------+
```

De forma conceptual, a arquitetura do sistema é composta pelos seguintes elementos principais:

- **Frontend Web (React/Vite)** — interface de gestão do restaurante;
- **Frontend Mobile (React Native/Expo)** — aplicação única para cliente e estafeta, com fluxos distintos;
- **Backend (Laravel 11/PHP 8.3)** — lógica de negócio, gestão de estados, orquestração de eventos;
- **API GraphQL (Lighthouse)** — operações estruturadas e tipadas;
- **Servidor WebSocket (GatewayWorker)** — propagação de eventos em tempo real;
- **Base de dados (PostgreSQL)** — persistência relacional de todas as entidades;
- **Sistema de Filas e Jobs** — processamento assíncrono (expiração, atribuição de estafetas, outbox, push notifications).

Esta organização permite separar claramente as responsabilidades de cada componente e facilitar a evolução futura do sistema.

#### 2.2.1 Comunicação Cliente-Servidor

A comunicação entre clientes e servidor é realizada através de duas tecnologias complementares.

A primeira corresponde à utilização de **GraphQL**, que permite executar operações e consultas estruturadas sobre o sistema (criação de encomendas, consulta de restaurantes, adição de itens ao carrinho, checkout, consulta de histórico, etc.). O GraphQL permite que cada interface solicite exatamente os dados de que necessita para cada ecrã, reduzindo o over-fetching e evitando a proliferação de endpoints demasiado específicos.

A segunda corresponde à utilização de **WebSockets via GatewayWorker**, que permite a distribuição de eventos em tempo real para todos os utilizadores ligados. Quando ocorre uma ação relevante — por exemplo, quando um restaurante confirma uma encomenda, quando um estafeta aceita uma oferta de entrega, ou quando a localização do estafeta é atualizada — o sistema publica o evento no outbox; o `PublishOutboxEventJob` consome o outbox e dispara o `DomainEventBroadcasted` (ou um evento Laravel específico); o `DispatchSocketMessage` listener encaminha o evento para o `SocketMessageDispatcher`; o handler apropriado faz finalmente o broadcast para os canais correspondentes via GatewayWorker.

Os **canais WebSocket** estão organizados por contexto e descritos em 1.2.3.

Esta abordagem dual reduz significativamente a latência na atualização do estado e melhora a experiência de todos os atores do sistema.

### 2.3 Principais Desafios Técnicos

Durante a conceção do sistema foram identificados vários desafios técnicos associados à implementação de uma plataforma de entregas com múltiplos atores e comunicação em tempo real.

**Sincronização de estado entre múltiplos participantes.** Uma mesma encomenda é acompanhada simultaneamente pelo cliente, pelo restaurante e pelo estafeta, sendo necessário garantir que todas as ações executadas são processadas pelo servidor de forma consistente e que as atualizações são propagadas corretamente para todos os intervenientes, sem race conditions nem ordem de eventos invertida.

**Gestão de múltiplos fluxos concorrentes.** Em qualquer momento, o sistema pode estar a processar dezenas de encomendas em estados diferentes, cada uma com o seu ciclo de pagamento e entrega. Isto exige isolamento de estado por agregado e cuidado nas transações.

**Consistência das transições de estado.** O sistema deve impedir que ações inválidas corrompam o domínio, como cancelar uma encomenda já entregue ou aceitar uma oferta de entrega já expirada. Para tal, recorreu-se a máquinas de estado formais com validação rigorosa em cada transição, materializadas em `AbstractOrderState`, `AbstractPaymentState` e `AbstractDeliveryState`.

**Fiabilidade na publicação de eventos.** Quando o estado de uma encomenda muda, é fundamental garantir que o evento correspondente é efetivamente publicado para os canais WebSocket e para o sistema de notificações. O padrão **Outbox** foi adotado precisamente para garantir esta entrega: o evento é inserido na tabela `outbox_events` dentro da mesma transação que altera o estado, e um job em background publica-o de forma controlada, com retries automáticos perante falhas.

**Tracking em tempo real e dados geográficos.** A atribuição inteligente de estafetas e o tracking exigem o tratamento de coordenadas: localização de restaurantes, endereços de entrega e posição atual dos estafetas. O `RoutingService` invoca a API OSRM para cálculo de rotas reais, com fallback para fórmula haversine via `GeoMath` quando o serviço externo não está acessível. Adicionalmente, a captação de localização no estafeta tem de funcionar mesmo em background, recorrendo ao `expo-task-manager`.

**Autenticação e isolamento de perfis.** Apesar de cliente e estafeta partilharem a mesma aplicação mobile, os respetivos fluxos têm de estar claramente segregados em termos de operações permitidas e de canais subscritos, evitando vazamentos de informação entre perfis.

---

# Capítulo 3

## Modelação e Especificação do Sistema

### 3.1 Introdução

Neste capítulo é apresentada a modelação detalhada do sistema FastBite, com o objetivo de descrever a sua estrutura, comportamento e organização interna de forma clara e fundamentada.

Para tal, foram utilizados diferentes tipos de diagramas UML:

- **Diagramas de classes** — representação da estrutura estática do sistema e aplicação dos design patterns;
- **Diagrama ER** — modelação da persistência relacional;
- **Diagramas de sequência** — representação dos fluxos de interação entre atores, frontends, GraphQL, services, outbox e canais WebSocket;
- **Diagramas de estados** — modelação do ciclo de vida das entidades principais (Order, Payment, Delivery).

Adicionalmente, é descrita a integração da arquitetura orientada a eventos com o domínio do sistema, evidenciando o funcionamento da comunicação em tempo real.

### 3.2 Diagrama de Classes e Aplicação de Design Patterns

#### 3.2.1 Visão Geral do Domínio

O diagrama de classes (Figura 2) representa as principais entidades do sistema e as relações entre elas. No centro encontra-se a entidade `User`, especializada nos perfis `Customer`, `Courier` e gestores de restaurante. Em torno desta, organizam-se três grandes blocos de domínio: o **catálogo** (`RestaurantChain`, `Restaurant`, `Category`, `Product`, `RestaurantProduct`), as **encomendas** (`Cart`, `Order`, `OrderItem`, `OrderItemOption`, `Payment`), e a **entrega** (`Delivery`, `DeliveryOffer`, eventos de tracking).

**Entidades principais e responsabilidades:**

- **`Order`** — agregado central do domínio. Contém estado, totais, snapshot de endereço de entrega, referência ao cliente e ao restaurante. Cada `Order` agrega `OrderItem`s (com `OrderItemOption`s), tem exatamente um `Payment` e, após confirmação, exatamente uma `Delivery`. O ciclo de vida da `Order` é controlado pelo padrão State (ver 3.2.2.1);
- **`OrderItem`** — item da encomenda, permite gestão independente do progresso na cozinha virtual (estado próprio para cada item: `WAITING`, `PREPARING`, `READY`);
- **`Cart`** — estrutura temporária do cliente antes do checkout, com `CartItem`s e respetivas opções; é descartada ou transformada em `Order` no checkout;
- **`Restaurant` / `RestaurantChain`** — separação entre o conceito de marca (catálogo, promoções globais) e estabelecimento físico (preços locais, disponibilidade, localização). A entidade `RestaurantProduct` materializa esta ponte;
- **`Delivery`** — entrega física com estado próprio, estafeta atribuído, snapshots de pickup/delivery e relação com `DeliveryOffer`s;
- **`DeliveryOffer`** — oferta temporizada enviada a um estafeta com TTL de 30 segundos; mantém histórico de tentativas;
- **`Payment`** — pagamento com método e estado próprio (simulação de cartão, MBWay e dinheiro);
- **`Promotion` / `Coupon`** — gestão de descontos automáticos (`Promotion`) e por código (`Coupon`);
- **`Notification`** — notificação multi-canal (in-app via WebSocket, push via Expo) ligada a um utilizador específico;
- **`Chat` / `Message`** — chat por encomenda com múltiplos participantes;
- **`Review`** — avaliação feita pelo cliente após entrega, podendo cobrir restaurante e/ou estafeta.

**Relações principais:**

- Um `Customer` tem várias `Order`s; uma `Order` tem múltiplos `OrderItem`s;
- Cada `Order` tem exatamente um `Payment` (1:1) e, após confirmação, exatamente uma `Delivery` (1:1);
- Um `Restaurant` pertence a uma `RestaurantChain` e disponibiliza vários `Product`s via `RestaurantProduct`;
- Um `Courier` está, em cada momento, associado a no máximo uma `Delivery` ativa;
- Eventos de domínio (`order_events`, `payment_events`, `delivery_events`) são armazenados separadamente das entidades principais e referenciados via `aggregate_id`.

Esta estrutura permite modelar de forma completa o fluxo de encomendas do sistema e suporta diretamente os padrões de desenho descritos na secção seguinte.

#### 3.2.2 Aplicação de Design Patterns

Foram aplicados diversos padrões de desenho para melhorar a organização, manutenibilidade e extensibilidade do sistema. Para cada padrão é apresentada a finalidade geral, a sua aplicação concreta no FastBite com referências às classes reais, e os benefícios obtidos.

##### 3.2.2.1 Padrão State

**Finalidade.** O padrão State permite que um objeto altere o seu comportamento de acordo com o estado em que se encontra. O próprio estado passa a ser um objeto com os seus próprios comportamentos, eliminando estruturas condicionais extensas (`if`/`switch` por estado) e tornando o sistema mais resiliente a erros de fluxo.

**Aplicação no Projeto.** O padrão State foi aplicado de forma rigorosa às três entidades cujo ciclo de vida é crítico: `Order`, `Payment` e `Delivery`. A implementação segue uma estrutura uniforme para as três entidades, com quatro componentes principais (exemplificados aqui para `Order`):

1. **Interface base** — `App\Domain\StateMachines\Orders\OrderState`. Define o contrato de cada estado:

   ```php
   interface OrderState
   {
       public function status(): OrderStatus;
       public function canTransitionTo(OrderStatus $next): bool;
       public function transition(Order $order, OrderStatus $next): void;
   }
   ```

2. **Classe abstrata** — `AbstractOrderState`. Implementa a lógica comum de validação de transições, garantindo que apenas as transições permitidas pelo respetivo estado podem ser atingidas. Caso contrário, é lançada uma `ValidationException` com uma mensagem clara:

   ```php
   abstract class AbstractOrderState implements OrderState
   {
       abstract protected function allowedTransitions(): array;

       public function canTransitionTo(OrderStatus $next): bool
       {
           return in_array($next, $this->allowedTransitions(), true);
       }

       public function transition(Order $order, OrderStatus $next): void
       {
           if (! $this->canTransitionTo($next)) {
               throw ValidationException::withMessages([
                   'status' => "Invalid order transition from {$this->status()->value} to {$next->value}.",
               ]);
           }
           $order->update(['status' => $next->value]);
       }
   }
   ```

3. **Estados concretos** — uma classe por estado (`PendingOrderState`, `ConfirmedOrderState`, `PreparingOrderState`, `ReadyOrderState`, `OutForDeliveryOrderState`, `DeliveredOrderState`, `CancelledOrderState`). Cada um expõe o seu `status()` e a lista `allowedTransitions()`. Por exemplo:

   ```php
   class PendingOrderState extends AbstractOrderState
   {
       public function status(): OrderStatus { return OrderStatus::PENDING; }
       protected function allowedTransitions(): array {
           return [OrderStatus::CONFIRMED, OrderStatus::CANCELLED];
       }
   }
   ```

4. **Factory de estados** — `OrderStateFactory::from($status)`, que devolve a instância correta do estado em função do `OrderStatus` atual:

   ```php
   return match ($status) {
       OrderStatus::PENDING          => new PendingOrderState(),
       OrderStatus::CONFIRMED        => new ConfirmedOrderState(),
       OrderStatus::PREPARING        => new PreparingOrderState(),
       OrderStatus::READY            => new ReadyOrderState(),
       OrderStatus::OUT_FOR_DELIVERY => new OutForDeliveryOrderState(),
       OrderStatus::DELIVERED        => new DeliveredOrderState(),
       OrderStatus::CANCELLED        => new CancelledOrderState(),
   };
   ```

A mesma arquitetura é replicada para `Payment` (`PaymentState`, `AbstractPaymentState`, `PaymentStateFactory`, com estados `PendingPaymentState`, `CompletedPaymentState`, `FailedPaymentState`, `CancelledPaymentState`, `RefundedPaymentState`) e para `Delivery` (`DeliveryState`, `AbstractDeliveryState`, `DeliveryStateFactory`, com estados `PendingDeliveryState`, `PickedUpDeliveryState`, `InTransitDeliveryState`, `DeliveredDeliveryState`, `FailedDeliveryState`).

**Benefícios.** Esta divisão garante três propriedades importantes:

- **Validação centralizada das transições** — qualquer tentativa de transição inválida (e.g., `PENDING → DELIVERED` saltando estados intermédios) é imediatamente rejeitada com uma exceção tipada, sem necessidade de espalhar `if`/`switch` pelos services;
- **Extensibilidade fechada** — adicionar um novo estado ou alterar transições permitidas requer apenas tocar na classe do estado correspondente, sem modificar a lógica de negócio que orquestra as transições;
- **Auditabilidade** — como cada transição passa por `transition()`, é trivial estender o método para registar um evento de domínio na tabela `order_events` automaticamente (e foi o que se fez no projeto, ao chamar `$order->update()` em conjunto com a publicação do evento no outbox dentro do mesmo `OrderService::*`).

As transições reais permitidas em cada máquina estão detalhadas em 3.5.

##### 3.2.2.2 Padrão Strategy

**Finalidade.** O padrão Strategy permite que um conjunto de algoritmos seja definido e encapsulado em classes separadas, tornando-os intercambiáveis em tempo de execução, sem alterar o código que os utiliza.

**Aplicação no Projeto.** Embora as restantes operações de domínio sigam uma abordagem de Services, o sistema utiliza o padrão Strategy implicitamente em duas áreas-chave:

- **Cálculo de preços e descontos** — `OrderPricingService` orquestra um pipeline de cálculo onde diferentes regras (preço base por item, ajuste por opções, aplicação de promoções automáticas, validação e aplicação de cupões, cálculo da taxa de entrega em função da distância) são compostas. Cada regra é uma função pura encadeada via `collect()->reduce()`/`->map()`, facilmente substituível por uma variante (ex.: promoção sazonal vs. promoção do parceiro);
- **Cálculo de rotas e distâncias** — `RoutingService` expõe um contrato uniforme (`distanceKm`, `estimatedDuration`), mas internamente alterna entre duas implementações: chamada à API **OSRM** (rota real por estrada) quando o serviço está disponível, ou fallback para a fórmula **haversine** via `GeoMath` quando o serviço externo não responde. Esta substituição é transparente para o `AssignCourierToDeliveryJob` que ordena estafetas por distância — recebe sempre `km`, independentemente da estratégia usada.

A estratégia de atribuição de estafetas também é Strategy-friendly: hoje implementada como "estafeta mais próximo disponível", pode ser estendida no futuro (estafeta com melhor avaliação, menor tempo estimado, etc.) sem alterar o `AssignCourierToDeliveryJob` que apenas pede ao componente de seleção *"o próximo candidato"*.

**Benefícios.** Permite alterar comportamentos sem modificar a lógica principal, isolar regras de negócio testáveis em unidade (`PricingCalculatorTest`, `RoutingServiceTest`) e facilita a evolução do sistema para novas variantes (e.g., promoções condicionais, estratégias regionais de pricing).

##### 3.2.2.3 Padrão Command

**Finalidade.** O padrão Command transforma uma ação num objeto independente, que contém toda a informação necessária para a sua execução. Permite encapsular operações, enfileirá-las, registá-las ou executá-las de forma uniforme, desacoplando o emissor do recetor.

**Aplicação no Projeto.** A combinação **Laravel Jobs + GraphQL Mutations** materializa este padrão de duas formas complementares:

**Mutations GraphQL como comandos síncronos.** Cada mutation no schema (e.g., `addToCart`, `checkout`, `startPreparingOrder`, `acceptDeliveryOffer`, `markDeliveryPickedUp`, `markDeliveryInTransit`, `updateCourierLocation`, `sendChatMessage`) representa uma intenção do utilizador, que é delegada ao service correspondente. O resolver Lighthouse atua como dispatcher leve, e a mutation desconhece os detalhes de execução.

**Jobs como comandos assíncronos.** Cada job em `Backend/app/Jobs/` é literalmente um Command no sentido GoF — encapsula a operação, os parâmetros necessários e um método `handle()` que executa. Os mais relevantes são:

- **`AssignCourierToDeliveryJob`** — responsável por toda a lógica de seleção e oferta de estafeta. Recebe `deliveryId`, ordena estafetas disponíveis por distância via `RoutingService`, cria uma `DeliveryOffer` com TTL de 30 segundos para o melhor candidato, e despoleta um `ExpireDeliveryOfferJob` com `delay(30)`;
- **`PublishOutboxEventJob`** — bridge entre a tabela `outbox_events` e a infraestrutura Laravel de Events/WebSockets (descrito em 3.6);
- **`ExpireDeliveryOfferJob`** — verifica se a oferta ainda está `PENDING` no momento agendado e, se sim, marca-a como `EXPIRED` e despoleta nova execução do `AssignCourierToDeliveryJob` para encontrar o próximo estafeta;
- **`ExpirePendingPaymentJob`** — limpa pagamentos pendentes que ultrapassaram o TTL;
- **`DispatchNotificationChannelsJob`** / **`SendPushNotificationJob`** — encapsulam o envio de notificações por cada canal configurado (in-app, push Expo).

**Benefícios.**

- **Desacoplamento total** entre o emissor (controller GraphQL, listener) e o executor (job na queue);
- **Retry e tolerância a falhas** — cada Job tem retry automático configurável pelo Laravel, sem necessidade de implementar lógica de retry manual;
- **Auditabilidade** — todos os jobs ficam registados na tabela `jobs` (ou `failed_jobs`), permitindo inspeção pós-mortem;
- **Extensibilidade** — adicionar uma nova ação (ex.: "Cancelar Encomenda pelo Restaurante") requer apenas uma nova mutation que dispara um novo job, sem alterar código existente.

##### 3.2.2.4 Padrão Observer

**Finalidade.** O padrão Observer estabelece uma relação de subscrição, onde um sujeito notifica automaticamente todos os interessados sempre que ocorre uma mudança de estado, sem que estes precisem de estar fortemente ligados entre si.

**Aplicação no Projeto.** O padrão Observer é absolutamente central na arquitetura do FastBite, e a sua implementação combina o **Event System nativo do Laravel** com o **Outbox Pattern** e o **GatewayWorker** para garantir entrega fiável e em tempo real. O fluxo é o seguinte:

```
Service (transação)
        |
        +--> persiste evento em order_events / payment_events / delivery_events
        |
        +--> insere registo em outbox_events (mesma transação)
                       |
                       v
              PublishOutboxEventJob
                       |
                       +--> dispara Event Laravel:
                             - DomainEventBroadcasted
                             - CourierPositionUpdated
                             - UserNotificationCreated
                             - ChatMessageSent
                                            |
              +-----------------------------+-------------------+
              v                                                 v
   DispatchSocketMessage (Listener)        CreateNotificationFromDomainEvent
              |                                                 |
              v                                                 v
   SocketMessageDispatcher                              cria Notification
              |                                        + dispara push
              v
   *SocketHandler::handle()
              |
              v
   GatewayClientSocketPusher
              |
              v
   GatewayWorker --> Clientes ligados
```

**Componentes-chave:**

- **Events** (`Backend/app/Events/`):
  - `DomainEventBroadcasted` — evento genérico que transporta `aggregateType`, `aggregateId`, `eventName`, `payload` e lista de canais alvo. É o evento usado para transições de Order/Payment/Delivery;
  - `CourierPositionUpdated` — específico para o tracking, otimizado para alta frequência;
  - `UserNotificationCreated` — disparado quando uma notificação destinada a um utilizador é criada;
  - `ChatMessageSent` — disparado quando uma nova mensagem de chat é criada;
  - `CourierLastSocketDisconnected` / `NotificationEventRecorded` — eventos auxiliares de auditoria.

- **Listeners** (`Backend/app/Listeners/`):
  - `DispatchSocketMessage` — listener universal subscrito aos quatro eventos acima. A sua responsabilidade é encaminhar o evento ao `SocketMessageDispatcher`;
  - `CreateNotificationFromDomainEvent` — escuta eventos de domínio que justifiquem uma notificação (ex.: `ORDER_OUT_FOR_DELIVERY` → notificação ao cliente);
  - `CourierConnectionStatusListener` — atualiza o estado de ligação do estafeta com base em eventos de conexão/desconexão do gateway.

- **Bridge para WebSocket** — `App\Gateway\ServerEvents\SocketMessageDispatcher` mantém um registo de handlers (`OrderSocketHandler`, `DeliverySocketHandler`, `NotificationSocketHandler`, `ChatSocketHandler`, `CourierTrackingSocketHandler`) e despacha o evento ao apropriado em função do `eventName`. Cada handler serializa o payload e usa o `GatewayClientSocketPusher` para enviar a mensagem a todos os clientes subscritos aos canais alvo.

**Subscrições registadas no `EventServiceProvider`:**

```php
Event::listen(DomainEventBroadcasted::class, DispatchSocketMessage::class);
Event::listen(CourierPositionUpdated::class, DispatchSocketMessage::class);
Event::listen(UserNotificationCreated::class, DispatchSocketMessage::class);
Event::listen(ChatMessageSent::class, DispatchSocketMessage::class);
```

**Benefícios alcançados.**

- **Extensibilidade sem modificação** — para adicionar um novo subscritor (ex.: sistema de métricas, integração com webhook externo), basta criar um novo listener e registá-lo. A lógica de negócio que dispara o evento não precisa de saber que ele existe;
- **Desacoplamento de infraestrutura** — os services de domínio não conhecem WebSocket nem push notifications; limitam-se a publicar eventos no outbox;
- **Fiabilidade** — graças ao outbox, mesmo que o gateway esteja indisponível no momento da transação, o evento será publicado quando o worker retomar.

##### 3.2.2.5 Padrão Singleton

**Finalidade.** O padrão Singleton garante que uma classe tenha apenas uma instância ativa durante o ciclo de vida da aplicação, fornecendo um ponto de acesso global. É aplicável a coordenadores centrais cuja duplicação causaria conflitos de estado.

**Aplicação no Projeto.** O Singleton é aplicado através do **container de serviços do Laravel**, que oferece um mecanismo declarativo de bindings (`$this->app->singleton(...)`) sem necessidade de implementar manualmente a clássica receita GoF (construtor privado + método estático). Os componentes registados como singleton incluem:

- **`Ray\Aop\Aspect`** (registado em `RayAopServiceProvider`) — instância única da máquina de aspetos AOP responsável por aplicar os interceptores `Transactional` e logging a todos os services proxiados. Múltiplas instâncias causariam configurações divergentes;
- **`SocketMessageDispatcher` + `GatewayClientSocketPusher`** — atuam como hub central de envio de mensagens WebSocket. Manter uma única instância garante reuso do cliente de gateway e configuração coerente;
- **Repositórios e Services** — embora a maior parte esteja registada como `bind()` (nova instância por requisição), os singletons aplicam-se a componentes verdadeiramente stateless e globais.

**Benefícios.**

- **Consistência de configuração** — a instância de `Aspect` aplica os mesmos interceptores em toda a aplicação, evitando que duas chamadas ao mesmo método sigam regras diferentes;
- **Acesso global controlado** — qualquer parte do sistema pode resolver o `SocketMessageDispatcher` via container sem precisar de propagar referências;
- **Gestão de ciclo de vida pelo framework** — não há necessidade de código boilerplate para garantir unicidade; o Laravel encarrega-se disso.

##### 3.2.2.6 Padrão Factory

**Finalidade.** O padrão Factory fornece uma interface centralizada para a criação de objetos, escondendo a lógica complexa de instanciação. Garante que os objetos são criados em estado consistente e válido.

**Aplicação no Projeto.** O Factory é aplicado de forma evidente nas máquinas de estado, mas também noutros pontos:

- **State Factories** (`OrderStateFactory`, `PaymentStateFactory`, `DeliveryStateFactory`) — exemplificadas em 3.2.2.1. Garantem que dado um valor de enum (`OrderStatus`, `PaymentStatus`, `DeliveryStatus`) é devolvido o objeto State correto, evitando que qualquer cliente do código tenha de fazer `match`/`switch` sobre o estado;
- **DTO Factories via `spatie/laravel-data`** — os Data Transfer Objects do projeto (e.g., `CheckoutInputData`, `CreateOrderData`, `UpdateLocationData`) são construídos a partir de input GraphQL através do método estático `::from($input)`, que valida e converte os tipos. Esta abordagem unifica a construção de DTOs;
- **Model Factories de teste** — `Backend/database/factories/` contém factories de Eloquent que geram entidades válidas para os testes (`OrderFactory`, `UserFactory`, `RestaurantFactory`, etc.), garantindo que os testes não dependem de inserts SQL manuais nem de seed completo da BD;
- **Container de serviços do Laravel** — o próprio container atua como Abstract Factory quando interfaces são resolvidas via `bind()`, devolvendo a implementação correta (`OrderServiceInterface` → `OrderService`, com AOP aplicado).

**Benefícios.**

- **Encapsulamento da lógica de criação** — os clientes desconhecem detalhes como qual a classe State concreta a instanciar, ou como construir um DTO a partir de input parcial;
- **Garantia de integridade** — um objeto saído de uma factory é sempre válido (estado consistente, validações aplicadas);
- **Single responsibility** — as factories assumem a responsabilidade de saber como construir, enquanto as próprias entidades focam-se em saber como funcionar.

### 3.3 Diagrama ER

O diagrama entidade-relacionamento (Figura 5) representa a estrutura de dados persistente do sistema, evidenciando as principais entidades e as relações entre si.

**Entidades principais:**

- **`users`** — utilizadores da plataforma com atributos básicos (`id`, `name`, `email`, `password`, `role`, timestamps);
- **`customers`** — perfil específico de cliente, ligado 1:1 ao utilizador;
- **`couriers`** — perfil específico de estafeta, com `status` (`AVAILABLE`/`BUSY`/`OFFLINE`), `current_location` (coordenadas) e `vehicle_type`;
- **`addresses`** — endereços associados a clientes, incluindo geocoding;
- **`restaurant_chains`** — cadeias de restaurantes com nome, descrição e catálogo partilhado;
- **`restaurants`** — estabelecimentos individuais, ligados a uma cadeia, com localização e horários;
- **`categories`** — categorias de produtos no catálogo;
- **`products`** — produtos da cadeia com preço base e opções configuráveis;
- **`restaurant_products`** — ponte muitos-para-muitos entre `restaurants` e `products`, com preço local e disponibilidade;
- **`carts` / `cart_items` / `cart_item_options`** — carrinhos temporários do cliente, com itens e opções selecionadas;
- **`orders` / `order_items` / `order_item_options`** — encomendas finalizadas, snapshot dos itens no momento do checkout;
- **`payments`** — pagamentos 1:1 com encomenda, com método e estado;
- **`deliveries`** — entregas 1:1 com encomenda (após confirmação), com estafeta atribuído, snapshots de tempos e estado próprio;
- **`delivery_offers`** — ofertas temporizadas enviadas a estafetas, com `expires_at` e `status`;
- **`courier_position_history`** — histórico de posições do estafeta durante uma entrega ativa, alimenta o tracking;
- **`promotions` / `coupons`** — descontos automáticos e por código;
- **`notifications`** — notificações dirigidas a utilizadores;
- **`chats` / `messages`** — conversas associadas a encomendas;
- **`reviews`** — avaliações pós-entrega.

**Tabelas de eventos (event log):**

- **`order_events`**, **`payment_events`**, **`delivery_events`** — registo imutável e ordenado de eventos por agregado, com `event_name`, `payload` (JSONB) e `created_at`. Permitem reconstruir a história completa de qualquer entidade.

**Tabela de outbox:**

- **`outbox_events`** — tabela transversal usada pelo padrão Outbox (ver 3.6). Colunas: `id` (UUID), `aggregate_type`, `aggregate_id`, `event_name`, `payload` (JSON), `status` (`PENDING`/`PROCESSING`/`PUBLISHED`/`FAILED`), `retry_count`, `next_attempt_at`, `published_at`, `last_error`. Índices em `(status, next_attempt_at)` para eficiência do worker.

A separação clara entre entidades de negócio, tabelas de eventos e outbox permite que a publicação assíncrona ocorra sem bloquear as transações principais e suporta auditoria e replay completos.

### 3.4 Diagramas de Sequência

Os diagramas de sequência constituem uma ferramenta fundamental na modelação de sistemas, permitindo representar de forma clara e estruturada os principais fluxos de interação entre os diferentes componentes ao longo do tempo. Nesta secção são apresentados os três fluxos mais representativos do FastBite. Os diagramas PlantUML completos encontram-se em `Documentacao/Diagramas/`.

#### 3.4.1 Fluxo de criação de encomenda

Este fluxo descreve o percurso desde a construção do carrinho pelo cliente até à criação efetiva da encomenda no servidor e à notificação do restaurante em tempo real. A interação envolve a app mobile do cliente, a API GraphQL, o `CartService`, o `OrderService` (com `#[Transactional]`), o `PaymentService`, o sistema de Outbox e, por fim, o servidor de gateway WebSocket que notifica o painel web do restaurante.

**Etapa 1 — Construção do carrinho.** O cliente, navegando na app mobile, adiciona produtos ao carrinho. Cada adição despoleta uma mutation `addToCart(input)` que é resolvida pelo `CartService::addItem`, persistindo `cart_items` e `cart_item_options` em PostgreSQL. O carrinho atualizado (com totalizadores) é devolvido ao cliente, que vê o estado refletido em tempo real no ecrã.

**Etapa 2 — Checkout.** Quando o cliente pressiona "Finalizar Pedido", é enviada a mutation `checkout(CheckoutInput)`. O `OrderService::checkoutOrder`, protegido pelo interceptor `#[Transactional]`, abre uma transação e executa em sequência:

1. **Cálculo de preços** via `OrderPricingService`: soma itens, aplica `Promotion`s automáticas e validações de `Coupon`s, calcula a taxa de entrega com base na distância entre restaurante e endereço de entrega;
2. **Criação da `Order`** com `status = PENDING`, snapshot do endereço (`order_address`) e referência ao restaurante;
3. **Persistência dos `OrderItem`s e `OrderItemOption`s** com os preços e opções congelados no momento;
4. **Registo do primeiro evento** `ORDER_CREATED` em `order_events` e enfileiramento da entrada correspondente em `outbox_events`;
5. **Criação do `Payment`** via `PaymentService::createPayment`, com `status = PENDING` e o método escolhido;
6. **Simulação do pagamento**: para `CARD` e `MBWAY` (mocks), o pagamento transita imediatamente para `COMPLETED`, gerando `PAYMENT_COMPLETED` e despoletando a transição de `Order` para `CONFIRMED` (com novo evento `ORDER_CONFIRMED` e respetivas entradas no outbox). Para `CASH`, o pagamento permanece `PENDING` e o cliente continua o fluxo com a encomenda no estado pendente.
7. **Commit da transação** — só agora os dados são efetivamente persistidos. A `Order` completa é devolvida ao cliente, que é redirecionado para o ecrã de tracking.

**Etapa 3 — Publicação outbox → WebSocket.** Em paralelo, o worker `PublishOutboxEventJob` consome continuamente a tabela `outbox_events` (com `status = PENDING` ordenados por `next_attempt_at`). Para cada entrada, dispara um evento Laravel (`DomainEventBroadcasted`) com os canais alvo:

- `customer.{userId}.orders` — para o cliente que criou a encomenda;
- `restaurant.{restaurantId}.orders` — para o painel web do restaurante;
- `order.{orderId}.tracking` — para o ecrã de tracking (que poderá ainda ter ouvintes).

O listener `DispatchSocketMessage` encaminha o evento ao `SocketMessageDispatcher` e este, através do handler `OrderSocketHandler`, faz finalmente o broadcast via `GatewayClientSocketPusher`. O painel web do restaurante, subscrito ao seu canal, recebe `ORDER_CONFIRMED` e a fila de encomendas (`RestaurantOrdersQueueScreen`) atualiza-se sem qualquer polling, em latência sub-segundo.

#### 3.4.2 Fluxo de aceitação de encomenda e atribuição de estafeta

Este fluxo cobre o que acontece quando o restaurante aceita uma encomenda e o sistema procura um estafeta para a entrega. Envolve a interação entre o frontend web do restaurante, a API GraphQL, o `OrderService`, o `OrderPricingService`, o `DeliveryService`, a queue do Laravel, o `AssignCourierToDeliveryJob`, o `GeoMath`/`RoutingService`, o outbox e finalmente o GatewayWorker para notificar o estafeta.

**Etapa 1 — Restaurante aceita pedido.** O gestor do restaurante carrega em "Começar Preparação" no painel web. A mutation `startPreparingOrder(orderId)` é resolvida pelo `OrderService::startPreparingOrder`. Dentro de uma transação:

1. A `Order` transita de `CONFIRMED` para `PREPARING` (via `OrderStateFactory::from($status)->transition($order, PREPARING)`);
2. É registado o evento `ORDER_PREPARING` em `order_events` e no outbox;
3. O `OrderPricingService::deliveryFee(restaurant, address)` calcula a taxa final de entrega com base na distância;
4. O `DeliveryService::createDeliveryForOrder` faz `firstOrCreate` da `Delivery` com `status = PENDING`;
5. Após o commit (`->afterCommit()`), é despoletado o `AssignCourierToDeliveryJob(deliveryId)` na queue.

**Etapa 2 — Job seleciona estafeta.** O `AssignCourierToDeliveryJob` executa em background:

1. Carrega a `Delivery` e verifica que ainda não tem `courier_id` atribuído;
2. Consulta a tabela `couriers` para todos os estafetas com `status = AVAILABLE`, excluindo aqueles a quem já tinha sido feita uma oferta para esta entrega (lista negra de tentativas anteriores);
3. Para cada candidato, invoca o `RoutingService::distanceKm(courier_location, restaurant_address)`, que usa OSRM ou cai em `GeoMath` (haversine);
4. Ordena por distância ascendente e seleciona o mais próximo.

**Etapa 3 — Resolução da seleção.** Se não houver candidatos disponíveis ou já se atingiu o número máximo de tentativas (`MAX_ATTEMPTS = 3`), o `DeliveryService::markDeliveryFailedBySystem` é invocado com motivo `NO_COURIER_AVAILABLE`, marcando a entrega como `FAILED` e enfileirando `DELIVERY_FAILED` no outbox. Caso contrário:

1. Cria-se um `DeliveryOffer` com `status = PENDING`, `courier_id = ?`, `expires_at = now() + 30s`;
2. Enfileira-se `JOB_OFFERED` no outbox com canal `courier.{courierId}.jobs`;
3. Despoleta-se um `ExpireDeliveryOfferJob` agendado para 30 segundos depois;

**Etapa 4 — Estafeta recebe a oferta em tempo real.** O fluxo outbox→GatewayWorker já descrito propaga o `JOB_OFFERED` para o canal específico do estafeta. A `CourierAppScreen` na app mobile, subscrita ao seu canal de jobs via `subscribeToCourierJobsTopic`, recebe a mensagem e apresenta um cartão "Aceitar / Recusar" com countdown visual de 30 segundos.

#### 3.4.3 Fluxo de entrega em tempo real

Este fluxo cobre o ciclo completo a partir do momento em que o estafeta aceita a oferta: aceitação, recolha no restaurante, transporte com tracking GPS e conclusão da entrega.

**Etapa 1 — Aceitar a oferta.** O estafeta carrega em "Aceitar" na app. A mutation `acceptDeliveryOffer(offerId)` invoca `DeliveryService::acceptOffer`. Dentro de uma transação, com **`SELECT ... FOR UPDATE`** na `delivery_offer`, o sistema previne corridas (race conditions) caso a oferta tenha sido aceite por outro estafeta ou tenha expirado entre o broadcast e o clique. Se válida:

1. `delivery_offer.status = ACCEPTED`;
2. `delivery.courier_id = ?` (atribuição efetiva);
3. Insere `DELIVERY_ACCEPTED` em `delivery_events`;
4. `couriers.status = BUSY` (deixa de receber outras ofertas);
5. Enfileira `DELIVERY_ACCEPTED` no outbox (canais `order.{orderId}.tracking` e `courier.{courierId}.jobs`);
6. `OrderService` regista `ORDER_COURIER_ASSIGNED` no outbox.

Após o commit, a app navega para o ecrã de tracking do lado do estafeta (`TrackingScreen` com vista de estafeta), enquanto o cliente é também notificado em tempo real.

**Etapa 2 — Pickup no restaurante.** Ao chegar ao restaurante, o estafeta confirma "Cheguei ao Restaurante". A mutation `markDeliveryPickedUp` invoca o `DeliveryService::markPickedUp`, que valida a transição `PENDING → PICKED_UP` via `DeliveryStateFactory`, regista `DELIVERY_PICKED_UP` em `delivery_events` e no outbox. O `OrderService` é informado e regista também `ORDER_PICKED_UP` (informativo, não altera o estado da Order). O cliente recebe a atualização e o seu mapa apresenta o estafeta no restaurante.

**Etapa 3 — Em trânsito + posição em tempo real.** O estafeta marca "Em Trânsito" (mutation `markDeliveryInTransit`), transição `PICKED_UP → IN_TRANSIT` validada pelo State. A partir deste momento, e enquanto a entrega estiver `IN_TRANSIT`, o cliente da app mobile do estafeta envia em loop, a cada N segundos (tipicamente 5-10s), uma mutation `updateCourierLocation(deliveryId, lat, lng)`:

1. O `DeliveryService::updateLocation` valida que a entrega está efetivamente `IN_TRANSIT` e que o estafeta corresponde;
2. Insere a coordenada em `courier_position_history` (para histórico de tracking);
3. Atualiza `couriers.current_location`;
4. **Diretamente** (sem outbox, dada a alta frequência e tolerância a perdas pontuais) dispara o evento `CourierPositionUpdated` no canal `order.{orderId}.tracking`;
5. O cliente recebe a coordenada e o mapa (Leaflet no web, `react-native-maps` no mobile) move o marcador.

Esta otimização — usar publicação direta para localização em vez do outbox — é deliberada: perder uma coordenada GPS entre 5 atualizações por segundo é aceitável (o cliente recebe a seguinte logo a seguir); perder um evento de transição de estado não é.

**Etapa 4 — Entrega concluída.** Ao chegar ao endereço, o estafeta carrega em "Entregue com Sucesso". A mutation `markDeliveryDelivered` invoca o `DeliveryService::markDelivered` dentro de uma transação:

1. Transição `IN_TRANSIT → DELIVERED` na `Delivery`, com `delivery_time = now()`;
2. Registo `DELIVERY_DELIVERED` em `delivery_events` e no outbox;
3. O `OrderService::markAsDelivered` é invocado e transita `OUT_FOR_DELIVERY → DELIVERED` na `Order`;
4. `couriers.status = AVAILABLE` (o estafeta volta a estar disponível para novas ofertas);
5. Commit.

Após a publicação outbox, o cliente recebe `ORDER_DELIVERED` e é redirecionado para o ecrã de avaliação (`ReviewScreen`), permitindo classificar restaurante e estafeta. O painel web do restaurante também é notificado para arquivar a encomenda.

### 3.5 Máquinas de Estado

Para além dos fluxos de interação, foi modelado o ciclo de vida das principais entidades do sistema através de máquinas de estado formais. As transições são validadas pelas implementações descritas em 3.2.2.1, e cada transição gera obrigatoriamente um evento de auditoria.

#### 3.5.1 Máquina de estados — `Order`

A *Tabela 2* lista as transições permitidas para a entidade `Order`. A máquina garante que apenas estes movimentos são aceites; qualquer outra tentativa é rejeitada com `ValidationException`.

**Tabela 2 — Transições de Estado — `Order`**

| Estado origem | Transições permitidas | Estados finais |
|---|---|---|
| `PENDING` | `CONFIRMED`, `CANCELLED` | — |
| `CONFIRMED` | `PREPARING`, `CANCELLED` | — |
| `PREPARING` | `READY`, `CANCELLED` | — |
| `READY` | `OUT_FOR_DELIVERY`, `CANCELLED` | — |
| `OUT_FOR_DELIVERY` | `DELIVERED`, `CANCELLED` | — |
| `DELIVERED` | (nenhuma) | ✓ |
| `CANCELLED` | (nenhuma) | ✓ |

Em qualquer fase anterior ao `DELIVERED`, a encomenda pode ser cancelada, transitando para `CANCELLED`. Cada mudança gera um evento (`ORDER_CONFIRMED`, `ORDER_PREPARING`, `ORDER_READY`, `ORDER_OUT_FOR_DELIVERY`, `ORDER_DELIVERED` ou `ORDER_CANCELLED`) que é registado em `order_events` e propagado em tempo real através do outbox.

#### 3.5.2 Máquina de estados — `Payment`

**Tabela 3 — Transições de Estado — `Payment`**

| Estado origem | Transições permitidas | Estados finais |
|---|---|---|
| `PENDING` | `COMPLETED`, `FAILED`, `CANCELLED` | — |
| `COMPLETED` | `REFUNDED` | — (parcialmente final) |
| `FAILED` | (nenhuma) | ✓ |
| `CANCELLED` | (nenhuma) | ✓ |
| `REFUNDED` | (nenhuma) | ✓ |

O pagamento inicia em `PENDING` e pode resolver-se em sucesso (`COMPLETED`), falha (`FAILED`) ou cancelamento (`CANCELLED`). Um pagamento bem-sucedido pode ainda ser revertido para `REFUNDED` no contexto de devolução. O `ExpirePendingPaymentJob` força a transição `PENDING → CANCELLED` para pagamentos que ultrapassem o TTL.

#### 3.5.3 Máquina de estados — `Delivery`

**Tabela 4 — Transições de Estado — `Delivery`**

| Estado origem | Transições permitidas | Estados finais |
|---|---|---|
| `PENDING` | `PICKED_UP`, `FAILED` | — |
| `PICKED_UP` | `IN_TRANSIT`, `FAILED` | — |
| `IN_TRANSIT` | `DELIVERED`, `FAILED` | — |
| `DELIVERED` | (nenhuma) | ✓ |
| `FAILED` | (nenhuma) | ✓ |

A entrega segue o fluxo natural `PENDING → PICKED_UP → IN_TRANSIT → DELIVERED`, podendo cair em `FAILED` a partir de qualquer estado não-final (e.g., estafeta abandona, oferta esgota tentativas). Durante o estado `IN_TRANSIT`, a posição do estafeta é atualizada em tempo real conforme descrito em 3.4.3.

Estas três máquinas de estado garantem consistência global do sistema e centralizam a validação de regras de negócio críticas. Os testes `OrderStateMachineTest`, `PaymentStateMachineTest` e `DeliveryStateMachineTest` cobrem explicitamente cada transição permitida e cada tentativa inválida.

### 3.6 Comunicação em Tempo Real e Integração com o Domínio

Um dos aspetos mais importantes do sistema é a integração entre a lógica de domínio e a infraestrutura de comunicação em tempo real. Esta integração é realizada através da combinação do padrão **Outbox** com o **Event System do Laravel** e o servidor **GatewayWorker**.

**Fluxo completo (Figura 12):**

```
Service (transação)
     |
     +--> persiste evento em *_events
     +--> insere registo PENDING em outbox_events  (mesma transação!)
                            |
                            v
              PublishOutboxEventJob (worker contínuo)
                            |
                            +--> select PENDING ordered by next_attempt_at
                            +--> status = PROCESSING
                            +--> Event::dispatch(DomainEventBroadcasted | ...)
                            +--> on success: status = PUBLISHED
                            +--> on failure: retry_count++, next_attempt_at += backoff
                                            |
                                            v
                             DispatchSocketMessage (Listener)
                                            |
                                            v
                          SocketMessageDispatcher.dispatch(event)
                                            |
                                            v
                          *SocketHandler.handle(payload, channels)
                                            |
                                            v
                            GatewayClientSocketPusher.push(channel, msg)
                                            |
                                            v
                                  GatewayWorker (Workerman)
                                            |
                            +---------------+----------------+
                            v               v                v
                         Mobile          Web             Mobile
                         Cliente       Restaurante      Estafeta
```

**Garantias e propriedades:**

- **Atomicidade transacional** — a inserção no `outbox_events` ocorre na mesma transação que altera o estado de domínio, garantindo que se o commit falhar, o evento também não fica registado. Não existe ponto onde *"o estado mudou mas o evento não foi gerado"*;
- **At-least-once delivery** — o `PublishOutboxEventJob` faz retry com backoff em caso de falha, garantindo que o evento será eventualmente publicado;
- **Idempotência no cliente** — os payloads incluem `aggregate_id` + `event_name` + `created_at`, permitindo aos frontends ignorar duplicados se o mesmo evento for entregue duas vezes;
- **Desacoplamento total** — o domínio (Services, States) não conhece o GatewayWorker, nem sequer o Event System do Laravel. Apenas escreve no outbox;
- **Observabilidade** — a tabela `outbox_events` é por si só um registo de auditoria de tudo o que o sistema publicou (ou tentou publicar).

Esta arquitetura permite que, no futuro, a infraestrutura de WebSocket possa ser substituída (por exemplo, por Laravel Reverb, Pusher ou Soketi) sem qualquer impacto na lógica de domínio — basta alterar a implementação do `SocketMessageDispatcher`.

Adicionalmente, para o caso particular do **tracking de posição do estafeta**, o sistema utiliza um atalho direto (sem passar pelo outbox) através do evento `CourierPositionUpdated` publicado diretamente em `DeliveryService::updateLocation`. Esta otimização é deliberada e justificada pela alta frequência destas mensagens e pela tolerância a perda pontual de uma coordenada (o cliente receberá a próxima poucos segundos depois). Para transições de estado críticas, o outbox é sempre o caminho.

---

# Capítulo 4

## Implementação da Solução

Neste capítulo é descrita a implementação da plataforma FastBite, detalhando as principais decisões técnicas, a organização do sistema e as tecnologias utilizadas. A implementação segue diretamente a arquitetura definida nos capítulos anteriores, baseada numa abordagem cliente-servidor com suporte a comunicação em tempo real. O sistema foi desenvolvido com recurso a um backend em Laravel e dois clientes distintos (web e mobile), utilizando GraphQL para operações estruturadas e WebSockets via GatewayWorker para comunicação em tempo real.

### 4.1 Arquitetura Implementada

A arquitetura final do sistema foi implementada seguindo uma abordagem modular baseada na separação clara de responsabilidades, permitindo isolar as diferentes camadas e facilitar a sua evolução. O sistema encontra-se dividido em três camadas principais:

- **Camada de Domínio** — contém a lógica de negócio pura, as entidades (`App\Models\*`), as máquinas de estado (`App\Domain\StateMachines\*`), os enums e os value objects. Não conhece nem GraphQL, nem WebSocket, nem Eloquent diretamente (interage com a persistência por interfaces de repositório);
- **Camada de Aplicação** — orquestra os fluxos do sistema através dos services (`App\Services\*`), processa comandos (mutations e jobs) e coordena as transições de estado. Aplica AOP (`#[Transactional]`) e dispara eventos de domínio;
- **Camada de Infraestrutura** — implementa o acesso a dados (repositórios Eloquent em `App\Repositories\*`), expõe a API GraphQL (`Backend/graphql/*` + `App\GraphQL\*`), gere a comunicação WebSocket (`App\Gateway\*`) e implementa os jobs assíncronos (`App\Jobs\*`).

Esta separação permite garantir baixo acoplamento entre componentes e elevada coesão interna, facilitando a testabilidade e a manutenção. As interfaces (`OrderServiceInterface`, `DeliveryServiceInterface`, `*RepositoryInterface`, etc.) são registadas no container via `AppServiceProvider`/`AppRepositoryProvider`, e as implementações concretas são resolvidas em runtime — com proxies AOP aplicados aos services.

### 4.2 Implementação do Backend

O backend foi desenvolvido em Laravel (PHP 8.3), tendo como principal responsabilidade a gestão do estado do sistema, validação das regras de negócio e coordenação da comunicação entre os diferentes atores. A escolha do Laravel deve-se a:

- organização baseada em MVC com extensão clara para Services e Repositories;
- ecossistema maduro (Eloquent, Migrations, Queues, Events, Notifications);
- facilidade de integração com APIs (Lighthouse para GraphQL);
- suporte natural a processamento assíncrono via Queues e Jobs;
- ferramentas de testes integradas (PHPUnit, Pest, Mockery, Database transactions).

**Componentes principais** organizados em `Backend/app/`:

- **Models** (`App\Models\*`) — entidades Eloquent: `User`, `Customer`, `Courier`, `Restaurant`, `RestaurantChain`, `Product`, `Category`, `Order`, `OrderItem`, `Payment`, `Delivery`, `DeliveryOffer`, `Notification`, `Chat`, `Message`, `Review`, `Promotion`, `Coupon`;
- **Services** (`App\Services\*`) — ~24 services de domínio (`OrderService`, `OrderPricingService`, `DeliveryService`, `PaymentService`, `CartService`, `RestaurantService`, `RestaurantChainService`, `CampaignService`, `NotificationService`, `ChatService`, `ReviewService`, `UserService`, `UserAddressService`, `RoutingService`, entre outros). Cada service expõe uma interface pública e é registado no container;
- **Repositories** (`App\Repositories\*`) — repositórios Eloquent atrás de interfaces, isolando o acesso a dados;
- **State Machines** (`App\Domain\StateMachines\*`) — implementação do padrão State para `Order`, `Payment` e `Delivery`;
- **Gateway** (`App\Gateway\*`) — integração com GatewayWorker (ver 4.4);
- **Jobs** (`App\Jobs\*`) — comandos assíncronos para queue;
- **Events** (`App\Events\*`) / **Listeners** (`App\Listeners\*`) — sistema de eventos;
- **GraphQL** (`App\GraphQL\*`) — resolvers, types, directives Lighthouse complementares ao schema;
- **Data** (`App\Data\*`) — DTOs com `spatie/laravel-data`.

#### 4.2.1 Programação Orientada a Aspetos

A **Programação Orientada a Aspetos (AOP)** foi aplicada com o objetivo de separar preocupações transversais (cross-cutting concerns) da lógica principal do sistema. A implementação foi feita através da biblioteca `ray/aop`, integrada no `RayAopServiceProvider`, que regista a `Aspect::class` como singleton e configura proxies para os services-alvo.

A abordagem é baseada em **interceptores** que podem ser ligados a atributos PHP 8 (`#[Transactional]`, `#[Logged]`), permitindo adicionar comportamento a métodos sem alterar o seu código.

Foram definidos os seguintes aspetos:

- **Gestão de transações** — métodos críticos anotados com `#[Transactional]` são automaticamente envoltos por `DB::beginTransaction() / commit() / rollBack()`. Caso o método lance uma exceção, o rollback é garantido; em caso de sucesso, o commit é efetuado. Isto elimina o boilerplate `try/catch/DB::commit` que tipicamente polui services em Laravel;
- **Logging** — alguns métodos podem ser anotados para registar duração e parâmetros de entrada/saída, útil em ambientes de produção para detetar gargalos;
- **Tratamento de erros uniforme** — normalização de exceções para tipos esperados pelo GraphQL.

**Exemplo conceptual:**

```php
class OrderService implements OrderServiceInterface
{
    #[Transactional]
    public function checkoutOrder(CheckoutInputData $input): Order
    {
        // lógica pura de negócio:
        // - criar Order PENDING
        // - criar OrderItems + opções
        // - registar evento + outbox
        // - criar Payment
        // - simular pagamento / confirmar
        // (sem qualquer beginTransaction/commit/rollBack manual)
    }
}
```

O proxy AOP gerado pelo container do Laravel aplica o interceptor `TransactionInterceptor`, que envolve a chamada em `DB::transaction(fn() => $method(...))`. Para o programador, é como se o método fosse "naturalmente" transacional.

**Vantagens:**

- maior separação de responsabilidades (regras de negócio vs. infraestrutura);
- código mais limpo e legível, focado no domínio;
- consistência — todos os métodos críticos seguem a mesma política transacional;
- facilidade de evolução — adicionar um novo aspeto (ex.: invalidação de cache) só requer um novo interceptor.

#### 4.2.2 Event-Driven, Event Log e Outbox Pattern

A arquitetura do sistema segue uma abordagem **orientada a eventos**, onde todas as alterações relevantes são registadas como eventos imutáveis. Esta abordagem foi materializada através de três mecanismos complementares: **event log por agregado**, **outbox para publicação fiável** e **eventos Laravel para reação assíncrona**.

**Event log por agregado.** Para cada entidade principal (`Order`, `Payment`, `Delivery`), existe uma tabela dedicada (`order_events`, `payment_events`, `delivery_events`) que regista cada mudança de estado. Cada evento contém:

- `aggregate_id` — id da entidade afetada;
- `event_name` — nome do evento (ex.: `ORDER_CREATED`, `PAYMENT_COMPLETED`, `DELIVERY_PICKED_UP`);
- `payload` (JSONB) — dados associados ao evento;
- `created_at` — timestamp ordenado.

Esta abordagem permite reconstruir a história completa de qualquer encomenda, pagamento ou entrega para auditoria, debugging ou análise.

**Outbox pattern.** Toda a mudança que deve ser comunicada externamente (notificação ao cliente, broadcast a WebSocket, push notification, integração com terceiros) passa pela tabela `outbox_events`. Esta tabela é populada **na mesma transação** que persiste a mudança de domínio, garantindo que **não pode existir um estado em que o domínio mudou mas o evento não foi enfileirado**.

A tabela `outbox_events` (criada pela migration `2026_05_14_150000_create_outbox_events_table.php`) tem a estrutura:

| Coluna | Tipo | Descrição |
|---|---|---|
| `id` | UUID | identificador único |
| `aggregate_type` | string | tipo do agregado (`Order`, `Payment`, `Delivery`, `Chat`, `Notification`) |
| `aggregate_id` | string | id do agregado afetado |
| `event_name` | string | nome do evento de domínio |
| `payload` | JSON | dados a transmitir |
| `status` | enum | `PENDING` / `PROCESSING` / `PUBLISHED` / `FAILED` |
| `retry_count` | int | número de tentativas falhadas |
| `next_attempt_at` | timestamp | quando voltar a tentar |
| `published_at` | timestamp | quando foi efetivamente publicado |
| `last_error` | text | última mensagem de erro |

Existem índices em `(status, next_attempt_at)` (para o worker selecionar eficientemente os próximos a publicar) e em `(aggregate_type, aggregate_id)` (para inspeção por agregado).

**Worker de publicação.** O `PublishOutboxEventJob` é executado em loop pelo `OutboxRepository` (worker em background). Para cada entrada `PENDING` ordenada por `next_attempt_at`:

1. Marca como `PROCESSING` para evitar processamento duplicado;
2. Mapeia o `event_name` ao evento Laravel apropriado via `match`:
   - eventos de `Order`/`Payment`/`Delivery` → `DomainEventBroadcasted`;
   - eventos de notificação → `UserNotificationCreated`;
   - eventos de chat → `ChatMessageSent`;
3. Dispara o evento Laravel (`Event::dispatch(...)`), que ativa os listeners `DispatchSocketMessage` e `CreateNotificationFromDomainEvent`;
4. Em caso de sucesso, marca como `PUBLISHED` e regista `published_at`;
5. Em caso de falha, incrementa `retry_count`, define `next_attempt_at = now() + backoff` e regista `last_error`.

Esta abordagem garante **consistência entre persistência e comunicação em tempo real**, mesmo em cenários de falha parcial (gateway indisponível, queue worker em reinício, etc.).

### 4.3 API GraphQL

A API GraphQL foi implementada como principal interface de comunicação estruturada entre os clientes e o servidor, recorrendo ao package **Lighthouse**, que permite definir o schema em ficheiros `.graphql` decorados com directivas para mapear queries/mutations a resolvers ou diretamente a métodos de service.

**Organização do schema.** O schema está modularizado em `Backend/graphql/`:

- `schema.graphql` — root e includes;
- `common.graphql` — tipos partilhados (`User`, `Address`, scalars);
- `users.graphql`, `restaurants.graphql`, `menus.graphql`, `carts.graphql`, `orders.graphql`, `payments.graphql`, `deliveries.graphql`, `tracking.graphql`, `notifications.graphql`, `reviews.graphql`, `campaigns.graphql`, `chat.graphql` — schemas por domínio.

Os resolvers foram implementados de forma a delegar a lógica de negócio para a camada de aplicação, evitando a introdução de lógica complexa na camada de API. Esta abordagem promove uma clara separação de responsabilidades.

**Queries** permitem obter informação do sistema, por exemplo:

- `restaurants(filter: ...)` — listagem com filtros (categoria, distância, disponibilidade);
- `restaurant(id: ...)` com seleção fina dos campos necessários;
- `cart(customerId: ...)` — carrinho atual do cliente;
- `customerOrders(customerId: ..., status: ...)` — encomendas do cliente, opcionalmente filtradas;
- `restaurantOrdersQueue(restaurantId: ...)` — fila de encomendas ativas do restaurante;
- `orderTracking(orderId: ...)` — informação combinada de Order + Delivery + última posição do estafeta;
- `notifications(userId: ..., unread: true)` — notificações pessoais;
- `chatMessages(chatId: ..., limit: ..., before: ...)` — paginação reversa.

**Mutations** permitem executar ações:

- **Catálogo/Cart**: `addToCart(input)`, `removeFromCart(itemId)`, `updateCartItem(itemId, input)`;
- **Encomenda**: `checkout(CheckoutInput)`, `cancelOrder(orderId)`;
- **Operação restaurante**: `acceptOrder(orderId)`, `rejectOrder(orderId, reason)`, `startPreparingOrder(orderId)`, `updateOrderItemStatus(itemId, status)`, `markOrderReady(orderId)`;
- **Estafeta**: `acceptDeliveryOffer(offerId)`, `rejectDeliveryOffer(offerId)`, `markDeliveryPickedUp(deliveryId)`, `markDeliveryInTransit(deliveryId)`, `markDeliveryDelivered(deliveryId)`, `updateCourierLocation(deliveryId, lat, lng)`, `updateCourierAvailability(status)`;
- **Notificações**: `markNotificationRead(id)`, `markAllNotificationsRead`;
- **Chat**: `sendChatMessage(chatId, content)`, `ackChatMessage(messageId)`;
- **Campanhas**: `createCampaign(input)`, `updateCampaign(id, input)`, `applyCampaignItems(input)`.

**Vantagens.** Redução de over-fetching, contratos tipados que evitam erros de integração, flexibilidade na resposta (cada ecrã pede só o que precisa) e schemas autodocumentados.

### 4.4 Comunicação em Tempo Real com GatewayWorker

A comunicação em tempo real foi implementada utilizando **Workerman/GatewayWorker**, integrado via `workerman/gateway-worker` e `workerman/gatewayclient`. Esta combinação foi preferida ao Laravel Reverb por já estar madura no ecossistema PHP, ser mais leve e ter melhor performance em cargas elevadas, sendo viável correr o gateway no mesmo host que o backend.

**Arquitetura.** A integração reside em `Backend/app/Gateway/`, organizada em duas direções de fluxo:

**Receção de mensagens dos clientes** (`Backend/app/Gateway/ClientEvents/`):

- `GatewayWorkerEvents` — ponto de entrada do servidor, recebe eventos `onConnect`, `onMessage`, `onClose` do gateway;
- `ClientEventDispatcher` — interpreta o payload da mensagem (subscrição a tópico, ack de chat, ping) e encaminha ao handler apropriado;
- `ClientEventHandler` (base) — handlers especializados (subscrição, ack, etc.).

**Envio de mensagens para os clientes** (`Backend/app/Gateway/ServerEvents/`):

- `SocketMessageDispatcher` — recebe um evento Laravel via `DispatchSocketMessage` listener e mapeia ao handler;
- `Handlers/*SocketHandler` — handlers especializados por tipo de evento (`OrderSocketHandler`, `DeliverySocketHandler`, `NotificationSocketHandler`, `ChatSocketHandler`, `CourierTrackingSocketHandler`);
- `GatewayClientSocketPusher` — implementação concreta que usa o `GatewayClient` para fazer o broadcast.

**Canais.** A comunicação está organizada em canais segmentados (ver 1.2.3), garantindo que cada cliente recebe apenas o que lhe diz respeito. As subscrições são feitas pelo cliente após login (mutation GraphQL), e o gateway mantém um mapa `userId → connections`.

**Fluxo de eventos.** O fluxo completo, descrito em 3.6, garante que:

1. Eventos críticos (transições de estado) passam pelo outbox para entrega fiável;
2. Eventos de alta frequência tolerante (posição GPS) usam atalho direto;
3. Em qualquer caso, o resultado é uma mensagem JSON entregue em tempo real ao cliente.

Esta arquitetura elimina o polling, reduz a carga em base de dados e proporciona uma experiência fluida ao utilizador.

### 4.5 Implementação dos Frontends

O sistema inclui dois frontends, com responsabilidades segmentadas conforme os atores que servem.

#### 4.5.1 Frontend Web (React/Vite) — Restaurante

O frontend web foi desenvolvido em **React** com o bundler **Vite**, centrado na experiência de gestão do **restaurante**. A estrutura segue uma abordagem feature-first em `Frontend/web/src/`:

- `components/` — componentes UI partilhados;
- `features/` — módulos funcionais organizados por contexto;
- `screens/` — ecrãs raiz;
- `services/` — clientes GraphQL (Apollo Client), serviços de WebSocket (`services/realtime/`), serviços auxiliares;
- `utils/` — utilitários comuns.

**Ecrãs principais (features/restaurant/screens/):**

- `RestaurantOrdersQueueScreen` — **fila de pedidos ativos** em tempo real. Subscreve `restaurant.{id}.orders` e ordena pedidos por estado e por hora, com indicadores visuais de prioridade (próximos de ficarem prontos, atrasados);
- `RestaurantOrderDetailScreen` — detalhe de uma encomenda específica, com lista de itens e opções, ações disponíveis ("Aceitar", "Começar Preparação", "Marcar Pronto");
- `RestaurantVirtualKitchenScreen` — **cozinha virtual**, vista orientada aos itens individuais em vez de encomendas. Permite que múltiplos cozinheiros marquem itens prontos independentemente, e a encomenda automaticamente transita para `READY` quando todos os itens estão prontos;
- `RestaurantCampaignsScreen` — gestão de campanhas promocionais (criar, listar, ativar/desativar), com seleção de produtos a abranger;
- `RestaurantChatScreen` — chat com cliente/estafeta, subscrito ao canal `chat.{conversationId}`;
- ecrãs de catálogo (produtos, categorias, opções, disponibilidade);
- dashboards de estatísticas e faturação.

A camada `services/realtime/` encapsula a ligação WebSocket e oferece um padrão `subscribeToTopic(topic, handler)` para que os ecrãs declarem as suas subscrições de forma uniforme. As mensagens recebidas são integradas no cache do Apollo Client via `cache.modify`, mantendo o estado da UI sincronizado sem refetch.

#### 4.5.2 Frontend Mobile (React Native/Expo) — Cliente + Estafeta

A aplicação mobile foi desenvolvida em **React Native** com **Expo**, cobrindo num único binário os perfis de **cliente** e **estafeta**. A estrutura em `Frontend/mobile/src/`:

- `components/` — componentes UI partilhados (botões, mapas, listas);
- `features/` — módulos funcionais;
- `screens/` — ecrãs raiz, com bifurcação por perfil;
- `services/` — clientes GraphQL, WebSocket, localização;
- `navigation/` — definições de stacks e tabs.

**Fluxo do Cliente (`screens/customer/`):**

- `HomeScreen` — home com restaurantes próximos, banners, categorias;
- `MenuScreen` — menu de restaurante selecionado, com pesquisa e filtros;
- `CartScreen` — carrinho com itens, opções, totalizadores e botão de checkout;
- `OrdersHistoryScreen` — histórico de encomendas, com filtros por estado;
- `TrackingScreen` — **acompanhamento da entrega em tempo real**: mapa com restaurante, endereço de entrega e posição animada do estafeta, ETA estimado, estado atual da encomenda, botão para chat;
- `ProfileScreen` — perfil, endereços guardados, métodos de pagamento, definições.

**Fluxo do Estafeta (`screens/CourierAppScreen`):**

- ecrã principal com toggle de disponibilidade;
- receção de ofertas (`JOB_OFFERED`) com countdown visual e botões "Aceitar" / "Recusar";
- fluxo de entrega: navegação para restaurante, "Cheguei", marcar pickup, navegação para cliente, "Em Trânsito", marcar entregue;
- transmissão contínua de localização durante `IN_TRANSIT`, via `expo-location` em foreground e `expo-task-manager` para garantir continuidade com app em background.

**Autenticação** em `MobileLoginScreen` distingue automaticamente o perfil do utilizador (cliente vs. estafeta) e redireciona para o stack correto.

#### 4.5.3 Push Notifications

A aplicação móvel suporta notificações remotas via **Expo Notifications**, permitindo informar os utilizadores mesmo quando a aplicação não está ativa.

O funcionamento baseia-se em:

- registo de um identificador único do dispositivo (Expo Push Token) no primeiro arranque após login;
- envio desse token para o backend através da mutation `registerPushToken(token)`;
- armazenamento na tabela `user_push_tokens` ligada ao `User`;
- envio de notificações pelo backend através do `SendPushNotificationJob`, que chama o endpoint da Expo (`https://exp.host/--/api/v2/push/send`) com os tokens dos destinatários.

As notificações são utilizadas para informar sobre:

- novas encomendas (cliente e estafeta);
- atualizações de estado da entrega (cliente);
- novas mensagens de chat;
- alertas relevantes (oferta de entrega para estafeta com som distinto).

Adicionalmente, foi implementado **deep linking** com `expo-linking`, permitindo que o utilizador, ao tocar numa notificação, abra diretamente o ecrã correspondente (ex.: tocar em "Encomenda pronta para entrega" abre o `TrackingScreen` da encomenda em causa).

#### 4.5.4 Programação Funcional

Foram aplicados princípios de **programação funcional** em várias partes do sistema, com o objetivo de melhorar a clareza, previsibilidade e testabilidade do código.

**Principais abordagens utilizadas:**

- **Imutabilidade** — estados são representados através de **enums** (`OrderStatus`, `PaymentStatus`, `DeliveryStatus`) e os DTOs `spatie/laravel-data` são tipicamente readonly. No frontend, o estado dos Hooks é imutável por construção, e atualizações fazem-se com novas referências (`setItems([...items, newItem])`);
- **Funções puras** — regras de cálculo (pricing, distâncias, validações) implementadas como funções puras sem efeitos colaterais, o que facilita o teste em isolamento (`PricingCalculatorTest`, `GeoMathTest`);
- **Encadeamento de operações** — utilização de pipelines declarativos para transformar dados, tanto em PHP via Laravel Collections como em JavaScript via array methods.

**Exemplo (PHP):**

```php
$items = collect($cart->items)
    ->filter(fn ($item) => $item->isAvailable())
    ->map(fn ($item) => $this->applyPromotions($item))
    ->sortBy(fn ($item) => $item->order);
```

**Exemplo (JavaScript):**

```js
const visibleOrders = orders
  .filter(o => activeFilters.includes(o.status))
  .map(o => ({ ...o, etaMinutes: computeEta(o) }))
  .sort((a, b) => a.createdAt - b.createdAt);
```

Esta abordagem permite código mais expressivo, com menor probabilidade de erros de mutação acidental, e simplifica a escrita de testes determinísticos.

### 4.6 Validação e Testes

A validação do sistema foi feita em três planos complementares: testes automatizados no backend, testes manuais nos frontends e testes de integração ponta-a-ponta com vários dispositivos.

**Testes automatizados (Backend).** Foram escritos testes unitários e de feature com PHPUnit, localizados em `Backend/tests/` e organizados em `Unit/` (regras de domínio e serviços) e `Feature/` (fluxos completos via mutations GraphQL). No total, a suite contém **~70 métodos de teste** distribuídos por 27 ficheiros, cobrindo:

- **Máquinas de estado** (`OrderStateMachineTest`, `PaymentStateMachineTest`, `DeliveryStateMachineTest`) — validação explícita de cada transição permitida e cada tentativa inválida;
- **Pricing e descontos** (`PricingCalculatorTest`, `CampaignPricingTest`) — cálculos com promoções, cupões e taxas de entrega;
- **Operações críticas via GraphQL** (`OrderTrackingQueryTest`, `UpdateDeliveryStatusMutationTest`, `UpdateCourierLocationMutationTest`, `CourierOperationsMutationTest`, `RestaurantOperationsGraphQLTest`, `NotificationsGraphQLTest`, `CampaignPromotionItemsMutationTest`);
- **Validações de domínio** (`CartServiceValidationTest`, `OrderServiceValidationTest`, `UserServiceTest`, `UserAddressServiceValidationTest`, `RestaurantChainServiceValidationTest`, `ReviewServiceValidationTest`);
- **Serviços de infraestrutura** (`RoutingServiceTest`, `GeoMathTest`, `DeliveryServiceMappingTest`, `NotificationMapperTest`).

Os testes correm contra uma base de dados PostgreSQL dedicada de testing, configurada via Sail para garantir paridade com produção. A invocação é `docker compose exec laravel.test php artisan test`.

**Testes manuais (Frontends).** Os dois frontends foram validados manualmente por **cenários ponta-a-ponta (E2E)**, executando o fluxo completo:

1. Cliente regista-se → consulta restaurantes → constrói carrinho com opções → checkout;
2. Restaurante recebe na fila → aceita → começa preparação;
3. Sistema atribui estafeta → estafeta aceita oferta;
4. Cozinha marca itens prontos → encomenda transita para `READY`;
5. Estafeta confirma pickup → marca em trânsito → atualiza localização → marca entregue;
6. Cliente avalia restaurante e estafeta.

Durante o fluxo foi feita observação direta dos eventos no GatewayWorker (logs do worker), das tabelas `order_events`/`payment_events`/`delivery_events` e da `outbox_events` para confirmar a propagação correta dos eventos.

**Testes de integração em tempo real.** Foi feito teste de integração com **três dispositivos simultâneos** (cliente mobile, restaurante web, estafeta mobile) para confirmar:

- propagação correta dos eventos pelos múltiplos canais;
- ausência de race conditions na atribuição de ofertas (apenas o primeiro estafeta a aceitar fica com a entrega);
- atualização fluida da posição do estafeta no mapa do cliente;
- entrega correta de notificações push em background.

A lista completa de testes encontra-se no **Anexo D — Casos de Teste**.

### 4.7 Organização do Projeto e Gestão do Trabalho

A gestão do trabalho foi realizada com base numa metodologia ágil, utilizando **story points** para estimar o esforço associado a cada tarefa. O planeamento e acompanhamento foram suportados por uma ferramenta de board (Jira/Trello), onde as tarefas foram criadas, estimadas e movidas entre os estados *A fazer*, *Em desenvolvimento*, *Em revisão* e *Concluído*.

A distribuição de esforço pelos elementos da equipa foi a seguinte (*Tabela 5*):

**Tabela 5 — Distribuição de Story Points**

| Elemento | Story Points | Áreas principais |
|---|---:|---|
| Alexandre Freitas | 45 | Backend (Order/Delivery services), GatewayWorker, máquinas de estado |
| Duarte Sousa | 38 | Frontend Web (restaurante), GraphQL schema, Apollo Client |
| Paulo Coelho | 42 | Frontend Mobile (cliente + estafeta), tracking, push notifications |
| **Total** | **125** | |

Esta distribuição reflete uma divisão equilibrada do trabalho, tendo em conta as diferentes responsabilidades assumidas ao longo do desenvolvimento.

O **código-fonte** foi versionado em **Git**, alojado num repositório GitHub privado. O fluxo de trabalho seguiu um modelo simplificado de *feature branches* com merge para `main` após validação local, com mensagens de commit descritivas e PRs com co-autoria quando aplicável.

---

# Capítulo 5

## Discussão dos Resultados e Conclusão

### 5.1 Apresentação e discussão de resultados

O sistema desenvolvido, **FastBite**, cumpre o objetivo principal de permitir a gestão completa de encomendas de refeições num ambiente distribuído, suportando múltiplos utilizadores em simultâneo, com perfis distintos, e garantindo a sincronização do estado do sistema em tempo real entre todos os intervenientes.

Durante a fase de testes e validação, foi possível observar que as principais funcionalidades implementadas operam de forma consistente e estável, nomeadamente:

- **construção do carrinho e checkout** — com cálculo correto de promoções automáticas, validação de cupões e cálculo da taxa de entrega em função da distância;
- **aceitação e preparação por parte do restaurante** — com a vista de cozinha virtual a permitir gestão item-a-item;
- **atribuição automática de estafetas** — através de ofertas temporizadas com fallback para próximo candidato em caso de expiração;
- **tracking GPS em tempo real** — com a posição do estafeta a propagar-se em latência sub-segundo entre app do estafeta e app do cliente;
- **finalização e avaliação** — encerrando o ciclo de forma completa.

A arquitetura adotada, baseada na separação clara entre **GraphQL** (operações estruturadas) e **WebSockets via GatewayWorker** (comunicação em tempo real), revelou-se particularmente eficaz. A utilização de GraphQL permitiu estruturar de forma clara as operações de leitura e escrita, reduzindo problemas comuns em APIs REST como o over-fetching — um exemplo concreto é o ecrã de tracking, que pede apenas `Order { id, status, currentLocation, etaMinutes }` sem ter de carregar a encomenda inteira. Por outro lado, a utilização de WebSockets possibilitou a atualização imediata do estado das encomendas e da posição do estafeta, garantindo uma experiência fluida ao utilizador sem polling.

Um dos aspetos mais relevantes do sistema foi a implementação de uma **arquitetura orientada a eventos com Outbox Pattern**. Esta abordagem permitiu desacoplar a lógica de negócio da comunicação, e garantiu que **nenhuma transição de estado pode ocorrer sem que o evento correspondente seja eventualmente publicado** — um problema clássico em sistemas distribuídos resolvido elegantemente pela escrita atómica do outbox dentro da mesma transação que altera o domínio. Como benefício adicional, a tabela `outbox_events` torna-se um registo de auditoria completo de tudo o que o sistema comunicou.

A aplicação das três máquinas de estado (`Order`, `Payment`, `Delivery`), com validação centralizada nas suas factories e classes abstratas (ver 3.2.2.1 e 3.5), provou ser uma decisão acertada: durante o desenvolvimento foi possível detetar imediatamente várias tentativas inválidas de transição (e.g., chamadas duplicadas de `markDelivered` em race conditions), que foram tratadas como erros de domínio em vez de inconsistências silenciosas.

A aplicação dos **três paradigmas** (funcional, AOP, event-driven) demonstrou claros benefícios em termos de qualidade de código:

- a programação funcional nos frontends e nos cálculos de pricing tornou o código mais previsível e fácil de testar;
- a programação orientada a aspetos eliminou todo o boilerplate transacional nos services, deixando-os focados nas regras de negócio;
- a arquitetura event-driven, suportada pelo outbox, permitiu introduzir novas reações (notificações por push, atualização de estatísticas, integração futura com webhooks) sem tocar na lógica de domínio.

Outro ponto forte do sistema prende-se com a **separação clara de responsabilidades entre as três camadas** (Domínio, Aplicação, Infraestrutura). Esta organização contribuiu para uma melhor manutenibilidade e abre caminho para a evolução futura — por exemplo, substituir GatewayWorker por Laravel Reverb ou Soketi exigiria apenas alterar a implementação do `SocketMessageDispatcher`, sem qualquer impacto no domínio.

No que diz respeito às tecnologias utilizadas, a adoção de **Laravel + React + React Native + GatewayWorker** demonstrou ser adequada para o desenvolvimento de aplicações modernas e multiplataforma. A integração entre estas tecnologias foi realizada com sucesso, garantindo consistência na comunicação e na gestão de estado entre os múltiplos clientes.

### 5.2 Limitações

Apesar dos resultados positivos, foram identificadas algumas limitações no sistema desenvolvido:

- **Complexidade na gestão de estados das encomendas**: a implementação do ciclo de vida das encomendas revelou-se mais complexa do que inicialmente previsto, exigindo a introdução das máquinas de estado descritas em 3.5 para evitar inconsistências. Esta complexidade reflete-se também no número de eventos distintos a tratar nos frontends (cada cliente subscreve vários canais e tem de reagir a um conjunto variado de mensagens);
- **Gestão de comunicação em tempo real**: a utilização de WebSockets introduziu desafios relacionados com a gestão de ligações persistentes, reconexão automática (em particular na app mobile, com mudanças de rede móvel/Wi-Fi) e ordenação de eventos quando recebidos em rápida sucessão;
- **Escalabilidade não totalmente explorada**: o sistema não foi testado em cenários de elevada carga (centenas de encomendas em paralelo), pelo que a sua escalabilidade real não foi completamente validada. O GatewayWorker tem provadamente boa performance em PHP, mas o backend Laravel poderia tornar-se um gargalo em pico;
- **Limitações na atribuição de estafetas**: a estratégia atual considera apenas distância geográfica, ignorando potenciais melhorias como avaliação histórica do estafeta, carga atual, ou estimativa de tempo de chegada baseada em condições reais de tráfego;
- **Pagamentos simulados**: todos os métodos de pagamento são simulados; não foi integrado nenhum *payment gateway* real (Stripe, MBWay API). Esta foi uma decisão consciente para evitar requisitos legais/PCI no contexto académico;
- **Cobertura de testes nos frontends**: a validação dos frontends foi maioritariamente manual; não foi implementada uma suite de testes E2E automatizada (Cypress/Playwright para web, Detox/Maestro para mobile);
- **Sistema de avaliações simples**: as `Review`s são guardadas mas não há ainda um mecanismo de cálculo de reputação agregada nem de moderação;
- **Internacionalização não implementada**: todas as strings estão em português; uma plataforma deste tipo em produção exigiria i18n.

### 5.3 Conclusão e trabalho futuro

O desenvolvimento da plataforma FastBite permitiu aplicar, de forma integrada, diversos conceitos abordados na unidade curricular — incluindo **programação funcional**, **programação orientada a aspetos** e **arquiteturas orientadas a eventos** — bem como vários padrões de desenho clássicos (**State**, **Strategy**, **Command**, **Observer**, **Singleton**, **Factory**) aplicados a problemas reais do domínio.

De forma global, considera-se que os objetivos propostos foram atingidos: foi desenvolvido um sistema funcional, coerente e tecnicamente fundamentado, capaz de suportar comunicação em tempo real entre múltiplos intervenientes com perfis distintos. O projeto constituiu uma experiência relevante na construção de aplicações distribuídas modernas, permitindo consolidar conhecimentos técnicos e compreender em primeira mão os desafios associados à sincronização de estados em sistemas multiutilizador.

Para **trabalho futuro**, identificam-se várias direções de evolução:

- **Escalabilidade e distribuição**:
  - migração para uma arquitetura de microsserviços (separar `UserService`, `OrderService`, `DeliveryService`, `RoutingService`);
  - introdução de uma camada de cache (Redis) para queries de leitura frequentes (catálogo, fila de encomendas);
  - balanceamento de carga e replicação horizontal do GatewayWorker;
- **Melhoria do sistema de entrega**:
  - estratégias de atribuição de estafeta mais sofisticadas (multi-objetivo: distância + avaliação + carga + ETA real);
  - otimização de rotas multi-paragem (um estafeta com várias entregas simultâneas);
  - cálculo de ETA dinâmico baseado em histórico e condições atuais;
- **Pagamentos reais**:
  - integração com Stripe, MBWay (API SIBS) e PayPal;
  - implementação de devoluções automáticas para encomendas canceladas;
- **Segurança**:
  - reforço da autenticação (JWT com refresh tokens, OAuth/SSO);
  - rate limiting fino por mutation;
  - encriptação adicional de dados sensíveis (endereços, contactos);
- **Funcionalidades adicionais**:
  - sistema de avaliações com reputação agregada e moderação;
  - recomendações baseadas em histórico de encomendas;
  - programa de fidelidade e cupões personalizados;
- **Resiliência**:
  - mecanismos de recuperação de falhas no estafeta (reatribuição automática);
  - reconexão automática mais robusta dos clientes WebSocket;
  - circuit breakers para chamadas a serviços externos (OSRM, push providers);
- **Monitorização**:
  - logs estruturados (JSON) com correlation IDs;
  - métricas de desempenho (Prometheus + Grafana);
  - dashboards operacionais (encomendas/minuto, taxa de aceitação de ofertas, tempo médio de entrega);
- **Testes**:
  - suite E2E automatizada para os dois frontends;
  - testes de carga (k6, Artillery) para validar a escalabilidade real do GatewayWorker;
- **Internacionalização** com i18n no web e `react-i18next` no mobile.

Em síntese, o projeto estabelece uma **base sólida** para o desenvolvimento de uma plataforma de delivery em produção, demonstrando o potencial das tecnologias utilizadas e da arquitetura adotada na construção de sistemas distribuídos, reativos e extensíveis.

---

## Bibliografia

### Documentação Oficial

1. **Laravel Documentation** — https://laravel.com/docs
2. **Lighthouse PHP — A Framework for Serving GraphQL from Laravel** — https://lighthouse-php.com
3. **GraphQL Official Documentation** — https://graphql.org/learn/
4. **React Documentation** — https://react.dev
5. **React Native Documentation** — https://reactnative.dev/docs/getting-started
6. **Expo Documentation** — https://docs.expo.dev
7. **Workerman / GatewayWorker** — https://www.workerman.net/doc/gateway-worker/
8. **OSRM — Open Source Routing Machine** — https://project-osrm.org
9. **PostgreSQL Documentation** — https://www.postgresql.org/docs/

### Livros e Artigos de Referência

10. **Gamma, E., Helm, R., Johnson, R., Vlissides, J.** (1994). *Design Patterns: Elements of Reusable Object-Oriented Software*. Addison-Wesley.
11. **Fowler, M.** *Patterns of Enterprise Application Architecture*. Addison-Wesley.
12. **Fowler, M.** *Event Sourcing*. https://martinfowler.com/eaaDev/EventSourcing.html
13. **Microsoft Docs** — *Transactional Outbox Pattern*. https://learn.microsoft.com/azure/architecture/patterns/transactional-outbox
14. **Evans, E.** (2003). *Domain-Driven Design: Tackling Complexity in the Heart of Software*. Addison-Wesley.

### Bibliotecas e Ferramentas Utilizadas

15. **`workerman/gateway-worker`** — https://github.com/walkor/GatewayWorker
16. **`spatie/laravel-data`** — https://github.com/spatie/laravel-data
17. **`ray/aop`** — https://github.com/ray-di/Ray.Aop
18. **`react-leaflet`** — https://react-leaflet.js.org
19. **`react-native-maps`** — https://github.com/react-native-maps/react-native-maps

---

## Anexos

### Anexo A — Excerto do Schema GraphQL

Localização do schema completo: `Backend/graphql/`. Excerto dos tipos principais (`Backend/graphql/common.graphql` + `orders.graphql`):

```graphql
type User {
  id: ID!
  name: String!
  email: String!
  role: UserRole!
  createdAt: DateTime!
}

type Order {
  id: ID!
  status: OrderStatus!
  customer: Customer!
  restaurant: Restaurant!
  items: [OrderItem!]!
  payment: Payment
  delivery: Delivery
  totalAmount: Float!
  deliveryFee: Float!
  discountAmount: Float
  createdAt: DateTime!
  updatedAt: DateTime!
}

type Payment {
  id: ID!
  order: Order!
  method: PaymentMethod!
  status: PaymentStatus!
  amount: Float!
  paidAt: DateTime
}

type Delivery {
  id: ID!
  order: Order!
  courier: Courier
  status: DeliveryStatus!
  pickupTime: DateTime
  deliveryTime: DateTime
  currentLocation: Location
  estimatedArrival: DateTime
}

enum OrderStatus {
  PENDING
  CONFIRMED
  PREPARING
  READY
  OUT_FOR_DELIVERY
  DELIVERED
  CANCELLED
}

type Mutation {
  checkout(input: CheckoutInput!): Order!
  startPreparingOrder(orderId: ID!): Order!
  acceptDeliveryOffer(offerId: ID!): Delivery!
  updateCourierLocation(deliveryId: ID!, lat: Float!, lng: Float!): Boolean!
  markDeliveryDelivered(deliveryId: ID!): Delivery!
}

type Query {
  customerOrders(customerId: ID!, status: OrderStatus): [Order!]!
  restaurantOrdersQueue(restaurantId: ID!): [Order!]!
  orderTracking(orderId: ID!): OrderTracking!
}
```

### Anexo B — Esquema da Base de Dados

A lista completa de migrations aplicadas pode ser obtida com `docker compose exec laravel.test php artisan migrate:status`. As migrations encontram-se em `Backend/database/migrations/` e cobrem:

- **Identidade**: `users`, `password_resets`, `personal_access_tokens`, `user_push_tokens`;
- **Perfis**: `customers`, `couriers`, `addresses`;
- **Catálogo**: `restaurant_chains`, `restaurants`, `categories`, `products`, `restaurant_products`, `product_options`;
- **Carrinho**: `carts`, `cart_items`, `cart_item_options`;
- **Encomenda**: `orders`, `order_items`, `order_item_options`, `order_addresses`, `order_events`;
- **Pagamento**: `payments`, `payment_events`;
- **Entrega**: `deliveries`, `delivery_offers`, `delivery_events`, `courier_position_history`;
- **Descontos**: `promotions`, `coupons`, `coupon_redemptions`;
- **Comunicação**: `notifications`, `chats`, `messages`;
- **Avaliação**: `reviews`;
- **Outbox**: `outbox_events`.

### Anexo C — Snippets de Implementação

**OrderStateFactory** — Factory de estados (ver 3.2.2.1):

```php
namespace App\Domain\StateMachines\Orders;

class OrderStateFactory
{
    public static function from(OrderStatus|string $status): OrderState
    {
        $status = $status instanceof OrderStatus
            ? $status
            : OrderStatus::from($status);

        return match ($status) {
            OrderStatus::PENDING          => new PendingOrderState(),
            OrderStatus::CONFIRMED        => new ConfirmedOrderState(),
            OrderStatus::PREPARING        => new PreparingOrderState(),
            OrderStatus::READY            => new ReadyOrderState(),
            OrderStatus::OUT_FOR_DELIVERY => new OutForDeliveryOrderState(),
            OrderStatus::DELIVERED        => new DeliveredOrderState(),
            OrderStatus::CANCELLED        => new CancelledOrderState(),
        };
    }
}
```

**AssignCourierToDeliveryJob** (excerto conceptual):

```php
class AssignCourierToDeliveryJob implements ShouldQueue
{
    public function __construct(
        public readonly string $deliveryId,
        public readonly int $attempt = 1,
    ) {}

    public function handle(
        DeliveryServiceInterface $delivery,
        RoutingServiceInterface $routing,
    ): void {
        $d = $delivery->find($this->deliveryId);
        if ($d->courier_id || $this->attempt > self::MAX_ATTEMPTS) {
            return;
        }
        $candidates = $delivery->availableCouriersExcluding($d->offered_courier_ids);
        $best = collect($candidates)
            ->map(fn($c) => ['c' => $c, 'km' => $routing->distanceKm($c->location, $d->restaurant->address)])
            ->sortBy('km')
            ->first();

        if (! $best) {
            $delivery->markFailedBySystem($d, 'NO_COURIER_AVAILABLE');
            return;
        }
        $delivery->createOfferForCourier($d, $best['c'], ttl: 30);
        ExpireDeliveryOfferJob::dispatch($d, $best['c'])->delay(30);
    }
}
```

**PublishOutboxEventJob** (excerto conceptual):

```php
class PublishOutboxEventJob implements ShouldQueue
{
    public function handle(OutboxRepository $repo): void {
        $event = $repo->claimNext(); // PENDING -> PROCESSING (atomic)
        if (! $event) return;
        try {
            match ($event->event_name) {
                'COURIER_POSITION_UPDATED' => Event::dispatch(new CourierPositionUpdated($event)),
                'USER_NOTIFICATION_CREATED' => Event::dispatch(new UserNotificationCreated($event)),
                'CHAT_MESSAGE_SENT' => Event::dispatch(new ChatMessageSent($event)),
                default => Event::dispatch(new DomainEventBroadcasted($event)),
            };
            $repo->markPublished($event);
        } catch (\Throwable $e) {
            $repo->markFailed($event, $e->getMessage());
        }
    }
}
```

### Anexo D — Casos de Teste (Backend)

A *Tabela 6* lista os casos de teste implementados em `Backend/tests/`, organizados em **Unit** (regras de domínio e serviços) e **Feature** (fluxos via GraphQL). Cada ficheiro contém múltiplos métodos de teste; total aproximado: **70 métodos**.

**Tabela 6 — Casos de Teste (Backend)**

| ID | Ficheiro | Resultado esperado |
|---|---|---|
| **Unit — Domínio e Validações** | | |
| TU-001 | `OrderStateMachineTest` | Cada transição permitida em `Order` é executada; transições inválidas lançam `ValidationException` |
| TU-002 | `PaymentStateMachineTest` | Cada transição permitida em `Payment`; transições inválidas rejeitadas |
| TU-003 | `DeliveryStateMachineTest` | Cada transição permitida em `Delivery`; transições inválidas rejeitadas |
| TU-004 | `PricingCalculatorTest` | Cálculo correto de subtotais, descontos e taxas em vários cenários |
| TU-005 | `GeoMathTest` | Fórmula haversine devolve distâncias coerentes (Lisboa-Porto, etc.) |
| TU-006 | `RoutingServiceTest` | Integra OSRM com fallback para `GeoMath` perante indisponibilidade |
| TU-007 | `CartServiceValidationTest` | Adição/remoção de itens; rejeita produtos indisponíveis; valida opções |
| TU-008 | `OrderServiceValidationTest` | Checkout valida endereço, método de pagamento e disponibilidade |
| TU-009 | `PaymentServiceTransitionTest` | Transições do `Payment` em métodos do service |
| TU-010 | `UserServiceTest` | Registo, autenticação, perfis (cliente vs estafeta) |
| TU-011 | `UserAddressServiceValidationTest` | Validação e CRUD de endereços de cliente |
| TU-012 | `RestaurantChainServiceValidationTest` | Validações de cadeia (nome único, campos obrigatórios) |
| TU-013 | `ReviewServiceValidationTest` | Avaliações 1-5, rejeita avaliações sem encomenda entregue |
| TU-014 | `DeliveryServiceMappingTest` | Mapeamento de DTOs de entrega para a UI |
| TU-015 | `NotificationMapperTest` | Mapeamento de eventos para notificações por canal |
| **Feature — GraphQL** | | |
| TF-001 | `OrderTrackingQueryTest` | Query `orderTracking` devolve estado e localização; erro para encomenda inexistente |
| TF-002 | `UpdateDeliveryStatusMutationTest` | `markDeliveryPickedUp`, `markDeliveryInTransit`, `markDeliveryDelivered` em sequência |
| TF-003 | `UpdateCourierLocationMutationTest` | Mutation atualiza posição apenas se delivery está `IN_TRANSIT` |
| TF-004 | `CourierOperationsMutationTest` | Aceitar/rejeitar oferta; rejeição liberta para nova tentativa |
| TF-005 | `RestaurantOperationsGraphQLTest` | Aceitar/rejeitar pedido; transições corretas |
| TF-006 | `NotificationsGraphQLTest` | Listar, marcar lida, marcar todas lidas; canal correto |
| TF-007 | `ChatOperationsMutationTest` | Envio e ack de mensagens; broadcast no canal correto |
| TF-008 | `CampaignPromotionItemsMutationTest` | Criar/atualizar campanha; associar items |
| TF-009 | `CampaignPricingTest` | Promoções aplicadas corretamente; cupões validados |
| TF-010 | `DeliveryAssignmentTest` | Atribuição de estafeta segue a estratégia mais próximo + retries |

### Anexo E — Notas de Reprodução e Demonstração

Para reproduzir o sistema localmente:

```bash
# Backend
cd Backend
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan queue:work --tries=3       # worker da queue
./vendor/bin/sail artisan gateway:start              # GatewayWorker

# Frontend Web (restaurante)
cd Frontend/web
npm install && npm run dev

# Frontend Mobile (cliente + estafeta)
cd Frontend/mobile
npm install && npx expo start
```

Os seeds preparam: 2 cadeias com 3 restaurantes, ~20 produtos, 5 clientes e 4 estafetas de teste com posições iniciais distintas.


