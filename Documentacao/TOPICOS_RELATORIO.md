# Topicos para o Relatorio do Projeto FastBite

Guiao sugerido para o relatorio do projeto **2526-IPP-ESTG-MEI-PEDWM-AC-TP2**.

> Nota de coerencia: no relatorio final, defender que o realtime foi implementado com **GatewayWorker/Workerman**, nao com Laravel Reverb, porque e isso que esta no codigo e nos README atuais.

## Elementos Pre-Textuais

### Capa

Indicar o nome do projeto, unidade curricular, ano letivo, instituicao, docentes, elementos do grupo e data.

### Resumo

Explicar em poucas linhas o que e o FastBite: uma plataforma de encomendas e entregas de refeicoes com tres atores principais, cliente, restaurante e estafeta.

Referir os pontos tecnicos mais importantes:

- backend em Laravel;
- API GraphQL com Lighthouse;
- frontend web em React/Vite;
- frontend mobile em React Native/Expo;
- comunicacao em tempo real com WebSockets via GatewayWorker/Workerman;
- arquitetura orientada a eventos;
- notificacoes, tracking, chat, pagamentos simulados e auditoria.

### Abstract

Versao em ingles do resumo. Deve manter as mesmas ideias, sem acrescentar informacao nova.

### Indice, Lista de Figuras e Lista de Tabelas

Listar capitulos, subcapitulos, diagramas UML/ER, figuras das interfaces e tabelas relevantes.

## 1. Contextualizacao e Motivacao

### 1.1 Introducao

Apresentar o contexto da unidade curricular e explicar porque foi escolhido um sistema de delivery.

Falar do problema real que o projeto tenta resolver:

- gerir encomendas entre clientes, restaurantes e estafetas;
- manter estados sincronizados em tempo real;
- evitar inconsistencias entre pedidos, pagamentos e entregas;
- fornecer uma experiencia fluida em web e mobile.

### 1.2 Motivacao

Explicar porque este dominio e interessante para PEDWM:

- envolve web e mobile;
- exige comunicacao em tempo real;
- permite aplicar programacao funcional, orientada a aspetos e event-driven;
- tem regras de negocio ricas, como estados, promocoes, carrinho, pagamentos e entregas.

### 1.3 Objetivos do Projeto

Separar objetivos funcionais e tecnicos.

Nos objetivos funcionais, falar de:

- clientes explorarem restaurantes, produtos e menus;
- clientes criarem carrinho e finalizarem encomenda;
- restaurantes gerirem pedidos, menus, campanhas e estado da cozinha;
- estafetas receberem ofertas de entrega e atualizarem localizacao;
- utilizadores receberem notificacoes e mensagens.

Nos objetivos tecnicos, falar de:

- backend modular;
- API GraphQL;
- WebSockets;
- estado persistente e auditavel;
- maquinas de estados;
- testes unitarios e de integracao;
- separacao entre dominio, servicos, repositorios e interfaces.

### 1.4 Organizacao do Documento

Explicar rapidamente o que contem cada capitulo do relatorio.

## 2. Enquadramento Tecnologico

### 2.1 Laravel no Backend

Explicar que o Laravel foi usado como base do backend.

Falar de:

- models Eloquent;
- migrations;
- services;
- repositories;
- jobs e queues;
- eventos;
- testes com PHPUnit;
- configuracao Docker/Sail.

### 2.2 GraphQL e Lighthouse

Explicar porque foi usado GraphQL em vez de uma API REST tradicional.

Falar de:

- queries para leitura;
- mutations para escrita;
- schemas por dominio em `Backend/graphql`;
- reducao de over-fetching;
- contratos tipados entre frontend e backend.

### 2.3 PostgreSQL e Modelo Relacional

Falar da base de dados relacional usada para persistir utilizadores, restaurantes, encomendas, pagamentos, entregas, carrinhos, promocoes, mensagens e eventos.

Referir tambem o uso de dados geograficos/localizacao quando se fala de estafetas e tracking.

### 2.4 GatewayWorker/Workerman e WebSockets

Explicar que WebSockets sao usados para atualizacoes em tempo real.

Falar de:

- canais por utilizador, restaurante, encomenda, estafeta e chat;
- eventos de encomenda, entrega, pagamento e notificacao;
- diferenca entre chamadas HTTP/GraphQL e mensagens realtime;
- razao para usar GatewayWorker/Workerman nesta versao.

### 2.5 React/Vite no Frontend Web

Explicar que o frontend web e focado principalmente na experiencia do restaurante.

Falar de:

- fila de encomendas;
- detalhe de encomenda;
- historico;
- gestao de menus/catalogo;
- campanhas e cupoes;
- avaliacoes;
- notificacoes;
- chat;
- dashboards/estatisticas.

### 2.6 React Native/Expo no Frontend Mobile

Explicar que a app mobile cobre cliente e estafeta.

Falar de:

- fluxo do cliente: home, restaurantes, menu, carrinho, checkout, tracking e historico;
- fluxo do estafeta: disponibilidade, ofertas de entrega, aceitacao, recolha, entrega e localizacao;
- mapas;
- notificacoes push;
- tarefas de localizacao em background.

### 2.7 Bibliotecas e Ferramentas Auxiliares

Referir bibliotecas relevantes:

- `react-router-dom` no frontend web;
- `leaflet` e `react-leaflet` para mapas web;
- `react-native-maps`, `expo-location`, `expo-notifications` e `expo-task-manager` no mobile;
- `ray/aop` para programacao orientada a aspetos;
- `spatie/laravel-data` para DTOs;
- `workerman/gateway-worker` e `workerman/gatewayclient` para realtime.

## 3. Conceptualizacao do Problema

### 3.1 Descricao do Dominio

Descrever o FastBite como uma plataforma que liga clientes, restaurantes e estafetas.

Explicar as entidades centrais:

- User;
- Customer;
- Courier;
- Restaurant;
- RestaurantChain;
- Cart;
- Order;
- Payment;
- Delivery;
- Product;
- Category;
- Promotion;
- Coupon;
- Notification;
- Chat;
- Review.

### 3.2 Atores do Sistema

Descrever responsabilidades de cada ator.

Cliente:

- procura restaurantes;
- cria carrinho;
- paga;
- acompanha encomenda;
- avalia restaurante/estafeta.

Restaurante:

- gere catalogo;
- recebe pedidos;
- confirma ou rejeita encomendas;
- atualiza preparacao;
- acompanha historico e avaliacoes.

Estafeta:

- define disponibilidade;
- recebe ofertas;
- aceita/rejeita entregas;
- atualiza estado e localizacao.

Sistema:

- calcula precos;
- aplica descontos;
- atribui estafetas;
- gere eventos;
- envia notificacoes;
- guarda auditoria.

### 3.3 Requisitos Funcionais

Agrupar os requisitos por modulo:

- autenticacao simplificada;
- gestao de utilizadores e moradas;
- restaurantes e catalogo;
- carrinho;
- checkout;
- pagamentos simulados;
- encomendas;
- entregas;
- tracking;
- notificacoes;
- chat;
- promocoes/cupoes;
- avaliacoes.

### 3.4 Requisitos Nao Funcionais

Falar de:

- modularidade;
- escalabilidade;
- baixa latencia nas atualizacoes;
- consistencia dos estados;
- auditabilidade;
- testabilidade;
- manutencao;
- separacao de responsabilidades.

### 3.5 Decisoes de Ambito

Assumir claramente o que ficou fora desta versao:

- autenticacao/autorizacao real de producao;
- autorizacao fina dos canais WebSocket;
- integracao com gateways reais de pagamento;
- testes de carga em escala real.

Explicar que estas decisoes foram delimitacoes de ambito, nao falhas funcionais.

## 4. Arquitetura da Solucao

### 4.1 Visao Geral da Arquitetura

Mostrar a arquitetura geral:

- frontend web;
- frontend mobile;
- backend Laravel;
- API GraphQL;
- servidor WebSocket GatewayWorker;
- base de dados PostgreSQL;
- filas/jobs;
- outbox events.

Explicar o papel de cada componente.

### 4.2 Separacao por Camadas

Falar da organizacao do backend:

- Models para entidades persistentes;
- DTOs para entrada de dados;
- Services para regras de negocio;
- Repositories para acesso a dados;
- GraphQL Queries/Mutations como camada de entrada;
- Jobs para processamento assincrono;
- Events/Gateway para comunicacao realtime.

### 4.3 Fluxo HTTP vs Fluxo Realtime

Explicar a divisao:

- GraphQL e usado para pedidos estruturados, como criar encomenda ou consultar historico;
- WebSockets sao usados para atualizar interfaces quando algo muda;
- Outbox ajuda a publicar eventos de forma fiavel apos persistencia.

### 4.4 Arquitetura Event-Driven

Explicar que alteracoes importantes geram eventos.

Exemplos:

- `ORDER_CREATED`;
- `ORDER_CONFIRMED`;
- `ORDER_READY`;
- `ORDER_DELIVERED`;
- `PAYMENT_COMPLETED`;
- `DELIVERY_ACCEPTED`;
- `DELIVERY_IN_TRANSIT`;
- `JOB_OFFERED`;
- `USER_NOTIFICATION_CREATED`.

Falar dos beneficios:

- desacoplamento;
- auditoria;
- sincronizacao em tempo real;
- facilidade de adicionar novos consumidores, como push, email ou analytics.

### 4.5 Outbox Pattern

Explicar o problema que o Outbox resolve: garantir que um evento persistido tambem e publicado de forma controlada.

Falar do fluxo:

1. servico altera o estado;
2. evento e guardado na tabela propria;
3. evento e colocado em `outbox_events`;
4. job publica para WebSocket/notificacoes;
5. em caso de erro, o evento pode ser reprocessado.

### 4.6 Comunicacao entre Clientes

Explicar como as interfaces recebem atualizacoes:

- cliente acompanha encomenda;
- restaurante recebe nova encomenda;
- estafeta recebe oferta;
- chat recebe mensagens;
- notificacoes sao propagadas para web/mobile.

## 5. Modelacao e Especificacao

### 5.1 Diagrama de Classes

Apresentar o diagrama de classes e explicar os grupos principais:

- utilizadores e perfis;
- restaurantes e catalogo;
- carrinho;
- encomendas;
- pagamentos;
- entregas;
- promocoes/cupoes;
- notificacoes;
- chat;
- avaliacoes;
- moradas.

Falar das relacoes mais importantes, por exemplo:

- um cliente tem varias encomendas;
- uma encomenda tem varios items;
- um restaurante tem varios produtos;
- uma encomenda tem pagamento e entrega;
- uma entrega pode ter varias ofertas a estafetas;
- uma encomenda tem eventos de auditoria.

### 5.2 Diagrama Entidade-Relacionamento

Explicar como o modelo de classes foi traduzido para tabelas.

Dar destaque a:

- normalizacao;
- relacoes 1:N e N:N;
- tabelas de eventos;
- tabelas de historico/localizacao;
- snapshots de nomes/precos em encomendas para preservar historico.

### 5.3 Diagramas de Sequencia

Incluir e explicar os fluxos principais:

- criar encomenda;
- restaurante aceitar/iniciar preparacao;
- atribuicao de estafeta;
- estafeta aceitar oferta;
- tracking da entrega;
- chat;
- notificacoes.

Em cada diagrama, explicar quem inicia o fluxo, que servicos sao chamados, que eventos sao gravados e que clientes sao notificados.

### 5.4 Maquinas de Estados

Explicar porque as maquinas de estados sao importantes para evitar transicoes invalidas.

Order:

- `PENDING`;
- `CONFIRMED`;
- `PREPARING`;
- `READY`;
- `OUT_FOR_DELIVERY`;
- `DELIVERED`;
- `CANCELLED`.

Payment:

- `PENDING`;
- `COMPLETED`;
- `FAILED`;
- `CANCELLED`;
- `REFUNDED`, se apresentado como extensao implementada.

Delivery:

- `PENDING`;
- `PICKED_UP`;
- `IN_TRANSIT`;
- `DELIVERED`;
- `FAILED`.

DeliveryOffer:

- `PENDING`;
- `ACCEPTED`;
- `REJECTED`;
- `EXPIRED`.

### 5.5 Modelo de Eventos

Explicar os eventos por dominio:

- eventos de encomenda;
- eventos de pagamento;
- eventos de entrega;
- eventos de oferta de entrega;
- eventos de notificacao;
- eventos de chat.

Referir que estes eventos servem tanto para auditoria como para sincronizacao realtime.

## 6. Paradigmas e Padroes Aplicados

### 6.1 Programacao Funcional

Falar de locais onde o projeto usa ideias funcionais:

- funcoes puras para calculo de precos e descontos em `PricingCalculator`;
- composicao de funcoes com `pipe`;
- currying/aplicacao parcial com uma funcao que fixa a quantidade antes de calcular o total;
- funcoes de ordem superior/callables para descontos, filtros de campanhas e scoring de estafetas;
- transformacoes de colecoes com `map`, `filter`, `flatMap`, `groupBy`, `diff`, `sum` e `values`;
- uso de `yield` para gerar descontos de promocoes de forma incremental;
- imutabilidade conceptual dos eventos e DTOs readonly;
- enums para representar estados;
- componentes React baseados em composicao;
- separacao entre calculo e efeitos colaterais.

Exemplos bons para mencionar:

- `PricingCalculator`;
- `CartService`, especialmente validacao de opcoes;
- `OrderPricingService`;
- `DeliveryService`, especialmente selecao funcional de candidatos;
- `OrderPricingServiceFunctionalTest`;
- formatadores de frontend, como labels de eventos/estado;
- mapeamento de dados recebidos da API para componentes.

### 6.2 Programacao Orientada a Aspetos

Explicar que a POA foi usada para preocupacoes transversais.

Falar de:

- `Transactional`;
- `TransactionInterceptor`;
- transacoes automaticas;
- reducao de repeticao de `begin/commit/rollback`;
- separacao entre regra de negocio e infraestrutura.

### 6.3 Programacao Event-Driven

Explicar como o dominio reage a eventos.

Falar de:

- eventos persistidos;
- outbox;
- jobs;
- broadcast via GatewayWorker;
- notificacoes;
- chat;
- tracking.

### 6.4 Padrao State

Falar das state machines de encomendas, pagamentos e entregas.

Explicar que cada estado conhece as transicoes validas e impede saltos invalidos, por exemplo voltar de `READY` para `PREPARING` ou alterar uma encomenda ja entregue.

### 6.5 Padrao Repository

Explicar a separacao entre acesso a dados e regras de negocio.

Dar exemplos:

- `OrderRepository`;
- `DeliveryRepository`;
- `PaymentRepository`;
- `RestaurantRepository`;
- `NotificationRepository`.

### 6.6 Padrao Service Layer

Explicar que a logica principal esta em services.

Dar exemplos:

- `OrderService`;
- `DeliveryService`;
- `PaymentService`;
- `CartService`;
- `RestaurantService`;
- `NotificationService`;
- `ChatService`.

### 6.7 Padrao Factory

Falar das factories de estado:

- `OrderStateFactory`;
- `PaymentStateFactory`;
- `DeliveryStateFactory`.

Explicar que centralizam a criacao do objeto certo a partir do enum atual.

### 6.8 Padrao Observer / Publish-Subscribe

Explicar a ideia aplicada no realtime:

- servicos produzem eventos;
- outbox/jobs publicam;
- clientes subscrevem canais;
- interfaces atualizam sem polling constante.

### 6.9 Padrao DTO

Explicar que os DTOs organizam dados de entrada e reduzem arrays soltos.

Dar exemplos:

- `CreateOrderDTO`;
- `CheckoutDTO`;
- `CreateDeliveryOfferDTO`;
- `RegisterPushTokenDTO`.

## 7. Implementacao do Backend

### 7.1 Organizacao do Codigo

Descrever a estrutura de pastas do backend:

- `app/Models`;
- `app/Services`;
- `app/Repositories`;
- `app/DTOs`;
- `app/Domain`;
- `app/GraphQL`;
- `app/Gateway`;
- `app/Jobs`;
- `app/Enums`;
- `database/migrations`;
- `tests`.

### 7.2 Modelos e Migrations

Falar das tabelas principais e da evolucao do schema via migrations.

Destacar:

- users;
- restaurants;
- products;
- carts;
- orders;
- payments;
- deliveries;
- notifications;
- chats;
- promotions/coupons;
- event tables;
- outbox events.

### 7.3 API GraphQL

Explicar a organizacao dos ficheiros `.graphql` por modulo.

Falar de exemplos de queries/mutations:

- procurar restaurantes;
- obter menu;
- adicionar item ao carrinho;
- fazer checkout;
- atualizar estado da encomenda;
- aceitar oferta de entrega;
- atualizar localizacao do estafeta;
- enviar mensagem;
- marcar notificacao como lida.

### 7.4 Servicos de Dominio

Explicar os servicos mais relevantes:

- `OrderService` para ciclo da encomenda;
- `CartService` para carrinho;
- `PaymentService` para pagamentos simulados;
- `DeliveryService` para entregas e ofertas;
- `TrackingService` para localizacao;
- `NotificationService` e `NotificationFeedService`;
- `ChatService`;
- `PromotionService` e `CouponService`.

### 7.5 Pagamentos Simulados

Explicar que nao ha gateway real.

Falar de:

- metodos suportados;
- estados;
- eventos de pagamento;
- expiracao/cancelamento;
- ligacao ao checkout.

### 7.6 Atribuicao de Estafetas

Explicar o fluxo:

1. entrega e criada;
2. sistema procura estafetas disponiveis;
3. oferta e enviada;
4. estafeta aceita, rejeita ou deixa expirar;
5. sistema tenta outro estafeta se necessario;
6. entrega passa a estar associada ao estafeta.

Referir calculo de distancia/localizacao e TTL das ofertas, se demonstrado na aplicacao.

### 7.7 Notificacoes e Push Tokens

Falar de:

- notificacoes internas;
- registo de push tokens;
- eventos que geram notificacoes;
- envio assincrono por jobs;
- deep links no mobile, se usado na demonstracao.

### 7.8 Chat

Explicar o chat associado a encomendas.

Falar de:

- tipos de chat;
- participantes;
- mensagens;
- envio em tempo real;
- leitura/historico.

### 7.9 GatewayWorker e Eventos Realtime

Explicar a pasta `app/Gateway`:

- eventos recebidos dos clientes;
- subscricoes;
- atualizacao de posicao;
- envio de mensagens;
- respostas/acks;
- handlers do servidor.

## 8. Implementacao dos Frontends

### 8.1 Frontend Web do Restaurante

Explicar o objetivo do frontend web.

Falar dos ecra principais:

- login;
- shell/navegacao lateral;
- fila de encomendas;
- detalhe da encomenda;
- historico;
- catalogo/menu;
- campanhas;
- cozinha virtual;
- notificacoes;
- reviews;
- perfil;
- estatisticas;
- chat.

### 8.2 Experiencia Mobile do Cliente

Explicar o fluxo do cliente:

- login;
- home;
- restaurantes;
- menu;
- carrinho;
- checkout;
- acompanhamento/tracking;
- historico;
- perfil e moradas;
- avaliacoes.

### 8.3 Experiencia Mobile do Estafeta

Explicar o fluxo do estafeta:

- login;
- estado de disponibilidade;
- receber oferta;
- aceitar/rejeitar;
- ver detalhes da entrega;
- atualizar estados;
- enviar localizacao;
- terminar entrega.

### 8.4 Componentes Reutilizaveis

Falar de componentes comuns:

- badges de estado;
- money text;
- rating stars;
- mapas;
- timeline de encomenda;
- skeleton/loading;
- error boundary;
- botoes e dialogs.

### 8.5 Gestao de Estado e Integracao com API

Explicar como os frontends comunicam:

- servicos de API;
- cliente GraphQL/fetch;
- modulos por dominio;
- subscricoes realtime;
- atualizacao visual apos eventos.

### 8.6 Mapas, Tracking e Localizacao

Falar de:

- mapa web com Leaflet;
- mapa mobile com React Native Maps;
- localizacao do estafeta;
- atualizacoes periodicas;
- historico de posicoes.

## 9. Validacao e Testes

### 9.1 Estrategia de Testes

Explicar que foram combinados:

- testes unitarios;
- testes de feature/GraphQL;
- testes manuais ponta-a-ponta;
- validacao com varios clientes ligados.

### 9.2 Testes Unitarios

Falar de testes sobre:

- maquinas de estados;
- calculo de precos;
- validacoes de servicos;
- mapeamento de notificacoes;
- calculo geoespacial;
- regras de carrinho/encomenda.

### 9.3 Testes de Integracao / Feature

Falar de testes GraphQL:

- operacoes do restaurante;
- operacoes do estafeta;
- tracking;
- notificacoes;
- campanhas;
- atualizacao de estado da entrega.

### 9.4 Testes Manuais E2E

Descrever cenarios demonstrados:

1. cliente cria encomenda;
2. restaurante recebe pedido;
3. restaurante inicia preparacao;
4. sistema atribui estafeta;
5. estafeta aceita;
6. cliente acompanha tracking;
7. entrega e concluida;
8. notificacoes/chat sao atualizados.

### 9.5 Limitacoes dos Testes

Assumir o que nao foi validado profundamente:

- carga real com muitos utilizadores;
- seguranca em producao;
- pagamentos reais;
- autorizacao fina em WebSockets;
- comportamento em redes moveis instaveis.

## 10. Resultados Obtidos

### 10.1 Funcionalidades Implementadas

Listar o que ficou funcional:

- gestao de restaurantes e catalogo;
- carrinho e checkout;
- encomendas;
- pagamentos simulados;
- preparacao pelo restaurante;
- atribuicao de estafeta;
- tracking;
- notificacoes;
- chat;
- promocoes e cupoes;
- avaliacoes;
- auditoria por eventos.

### 10.2 Demonstracao dos Fluxos Principais

Explicar como a demonstracao deve ser apresentada:

- abrir mobile cliente;
- criar encomenda;
- mostrar web restaurante a receber;
- mostrar mobile estafeta a receber oferta;
- mostrar tracking em tempo real;
- mostrar notificacoes/eventos na interface ou base de dados.

### 10.3 Discussao Tecnica

Falar do que funcionou bem:

- GraphQL para operacoes estruturadas;
- WebSockets para realtime;
- state machines para consistencia;
- outbox para publicacao fiavel;
- services/repositories para manutencao;
- frontends separados por perfil de utilizador.

### 10.4 Dificuldades Encontradas

Falar de dificuldades reais e tecnicas:

- coordenar estados entre cliente, restaurante e estafeta;
- garantir que eventos chegam ao publico certo;
- modelar transicoes validas;
- gerir dados geograficos;
- integrar web, mobile e backend;
- alinhar GraphQL com as necessidades das interfaces;
- configurar ambiente local e containers.

## 11. Gestao do Projeto

### 11.1 Organizacao da Equipa

Explicar como o trabalho foi dividido:

- backend;
- frontend web;
- frontend mobile;
- documentacao;
- testes;
- diagramas.

### 11.2 Planeamento e Metodologia

Falar da abordagem usada:

- divisao por funcionalidades;
- iteracoes;
- validacao progressiva;
- integracao continua manual;
- priorizacao dos fluxos centrais.

### 11.3 Controlo de Versoes

Falar de Git:

- organizacao do repositorio;
- commits por funcionalidade;
- gestao de alteracoes;
- integracao entre membros.

## 12. Limitacoes e Decisoes Assumidas

### 12.1 Autenticacao e Autorizacao

Explicar que a autenticacao e simplificada para contexto academico.

Referir que em producao seria necessario:

- JWT/OAuth;
- roles e policies;
- protecao de dados sensiveis;
- autorizacao de queries/mutations/canais.

### 12.2 Pagamentos

Explicar que o pagamento e simulado.

Referir que o sistema modela estados e eventos, mas nao comunica com Stripe, PayPal, MB Way real ou outro gateway externo.

### 12.3 WebSockets

Explicar que a protecao fina dos canais ficou fora de ambito.

Referir que em producao seria necessario validar subscricoes por utilizador e permissao.

### 12.4 Escalabilidade

Referir que a arquitetura foi pensada para evoluir, mas nao foi testada em carga elevada.

Falar de evolucoes possiveis:

- multiplas instancias de workers;
- filas separadas por prioridade;
- cache;
- balanceamento de carga;
- monitorizacao.

## 13. Trabalho Futuro

### 13.1 Seguranca

Propor:

- autenticacao robusta;
- autorizacao por roles;
- protecao dos canais WebSocket;
- rate limiting;
- auditoria de acessos.

### 13.2 Pagamentos Reais

Propor integracao com gateway real e callbacks/webhooks.

### 13.3 Otimizacao de Rotas

Propor:

- selecao de estafeta mais inteligente;
- calculo de ETA;
- otimizacao de multiplas entregas;
- integracao com APIs de mapas.

### 13.4 Escalabilidade e Observabilidade

Propor:

- logs estruturados;
- metricas;
- dashboards;
- tracing;
- testes de carga.

### 13.5 Melhorias de Produto

Propor:

- recomendacoes;
- favoritos;
- programas de fidelizacao;
- suporte a multiplas lojas por cadeia;
- painel administrativo;
- analitica para restaurantes.

## 14. Conclusao

Resumir que o projeto cumpriu os objetivos principais do enunciado:

- aplicacao web e mobile;
- backend funcional;
- comunicacao em tempo real;
- GraphQL;
- eventos;
- POA;
- ideias de programacao funcional;
- padroes de desenho;
- modelacao UML/ER;
- testes e validacao.

Terminar com uma reflexao sobre aprendizagem:

- maior compreensao de sistemas distribuidos;
- dificuldade de manter consistencia em tempo real;
- importancia de modelar estados;
- valor de separar responsabilidades.

## 15. Bibliografia

Incluir fontes tecnicas:

- Laravel Documentation;
- Lighthouse GraphQL;
- GraphQL official documentation;
- React;
- React Native;
- Expo;
- Workerman/GatewayWorker;
- PostgreSQL;
- Martin Fowler sobre Event Sourcing/Outbox;
- livro Design Patterns, se forem mencionados padroes GoF.

## 16. Anexos

### Anexo A - Enunciado

Incluir ou referenciar o ficheiro `2526-IPP-ESTG-MEI-PEDWM-AC-TP2.pdf`.

### Anexo B - Diagramas

Incluir:

- diagrama de classes;
- diagrama ER;
- diagramas de sequencia;
- diagramas de estados;
- arquitetura realtime.

### Anexo C - Schema GraphQL

Incluir excertos relevantes:

- tipos principais;
- queries;
- mutations;
- inputs;
- enums de estado.

### Anexo D - Capturas de Ecra

Incluir screenshots:

- frontend web restaurante;
- mobile cliente;
- mobile estafeta;
- tracking;
- notificacoes;
- chat;
- campanhas/cupoes.

### Anexo E - Excerto de Codigo

Escolher excertos pequenos e bem justificados:

- `TransactionInterceptor`;
- `OrderStateFactory`;
- `PaymentStateFactory`;
- `DeliveryStateFactory`;
- `OutboxService`;
- `AssignCourierToDeliveryJob`;
- `PricingCalculator`;
- exemplo de mutation GraphQL;
- exemplo de listener realtime no frontend.

### Anexo F - Testes

Incluir:

- lista de testes;
- comandos para correr;
- screenshots ou output dos testes;
- cenarios manuais validados.

## Checklist de Coerencia Antes de Entregar

- Confirmar que todas as referencias a Reverb foram trocadas por GatewayWorker/Workerman, se for essa a implementacao final.
- Confirmar que os nomes dos testes mencionados existem no repositorio.
- Confirmar que as screenshots correspondem a funcionalidades implementadas.
- Confirmar que os diagramas estao atualizados face ao codigo.
- Confirmar que as limitacoes aparecem como decisoes de ambito.
- Confirmar que os paradigmas pedidos no enunciado aparecem explicitamente.
- Confirmar que o relatorio explica tanto a parte tecnica como a experiencia de utilizador.
