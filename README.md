# FastBite

FastBite is a full-stack food delivery platform built as an end-to-end product prototype. It includes a Laravel GraphQL backend, a React restaurant operations dashboard, and a React Native/Expo mobile app for customers and couriers.

The project focuses on the operational flow behind modern food delivery systems: restaurant catalog management, carts and checkout, order lifecycle tracking, courier assignment, live delivery updates, notifications, reviews, campaigns, and real-time chat.

FastBite was designed to go beyond a simple CRUD application. It demonstrates how different user roles interact inside the same product ecosystem and how backend domain logic, real-time events, mobile workflows, and web dashboards can work together.

- Full-stack product thinking across backend, web, and mobile clients.
- API design with GraphQL and typed domain operations.
- Real-time communication using WebSockets and event-driven backend flows.
- Domain modeling for orders, payments, deliveries, couriers, restaurants, menus, campaigns, reviews, notifications, and chat.
- Clean backend layering with services, repositories, DTOs, state machines, events, listeners, jobs, and an outbox-style event flow.
- Frontend implementation with role-specific screens and reusable UI components.

## Product Scope

### Restaurant Web Dashboard

- Restaurant manager login flow.
- Restaurant profile and address management.
- Menu catalog management with categories, products, options, and restaurant availability.
- Restaurant chain and virtual kitchen management.
- Live order queue and order detail views.
- Order history.
- Campaigns, promotions, and coupon management.
- Reviews and rating monitoring.
- Notifications center.
- Restaurant/customer chat.

### Mobile Customer App

- Customer login/session flow.
- Restaurant browsing.
- Menu browsing with product options.
- Cart and checkout flow.
- Address management.
- Order history.
- Live order and delivery tracking.
- Reviews.
- Notifications.
- In-app chat.

### Mobile Courier App

- Courier role flow.
- Delivery offers.
- Active delivery management.
- Courier status and location updates.
- Delivery lifecycle actions such as pickup, in transit, delivered, and failed.
- Real-time tracking integration.

## Tech stack Overview

**Backend**

- PHP 8.3
- Laravel 13
- Lighthouse GraphQL
- PostgreSQL
- Laravel Queues
- GatewayWorker / Workerman WebSockets
- Spatie Laravel Data
- Docker Compose / Laravel Sail
- PHPUnit

**Web**

- React 19
- Vite
- React Router
- Leaflet / React Leaflet
- ESLint

**Mobile**

- React Native
- Expo
- React Navigation
- Expo Location
- Expo Notifications
- React Native Maps
- Async Storage

## Backend Design Highlights

- GraphQL schema split by domain: users, restaurants, menu, carts, orders, payments, deliveries, tracking, notifications, reviews, campaigns, and chat.
- Service and repository layers to separate business logic from persistence.
- DTOs for structured application input.
- State machines for order, payment, and delivery transitions.
- Event/listener/job flow for asynchronous side effects.
- Outbox-style event publishing for socket messages and notifications.
- WebSocket gateway for real-time updates such as order status, courier position, delivery offers, notifications, and chat messages.
- Demo database seeding for a realistic local environment.

## Getting Started

### 1. Clone And Configure

From the project root:

Create the environment files from the examples:

```bash
copy Backend\.env.example Backend\.env
copy Frontend\web\.env.example Frontend\web\.env
copy Frontend\mobile\.env.example Frontend\mobile\.env
```

On macOS/Linux, use `cp` instead of `copy`.

### 2. Run The Backend

```bash
cd Backend
docker compose up -d --build
```

Default local services:

- GraphQL API: `http://127.0.0.1/graphql`
- GraphQL Playground, if enabled: `http://127.0.0.1/graphql-playground`
- WebSocket Gateway: `ws://127.0.0.1:8090`
- pgAdmin: `http://127.0.0.1:5050`

To stop the backend:

```bash
docker compose down
```

### 3. Run The Web Dashboard

Open a second terminal:

```bash
cd Frontend\web
npm install
npm run dev
```

The web app usually runs at:

```text
http://localhost:5173
```

Make sure `Frontend/web/.env` points to the backend API and WebSocket gateway.

### 4. Run The Mobile App

Open another terminal:

```bash
cd Frontend\mobile
npm install
npm start
```

Then choose one of the Expo options:

- Physical device: scan the QR code with Expo Go.
- Android Emulator: press `a` or run `npm run android`.
- iOS Simulator on macOS: press `i` or run `npm run ios`.
- Browser preview: run `npm run web`.

### Mobile Device Network Setup

When using Expo Go on a physical device, `127.0.0.1` points to the phone itself, not to your computer. Update `Frontend/mobile/.env` with your computer's local Wi-Fi IP.

Example:

```env
EXPO_PUBLIC_API_BASE_URL=http://192.168.1.xxx
EXPO_PUBLIC_GATEWAY_WORKER_HOST=192.168.1.xxx
EXPO_PUBLIC_GATEWAY_WORKER_PORT=8090
EXPO_PUBLIC_GATEWAY_WORKER_SCHEME=ws
```

## Demo Data

The backend includes a demo seeder with a FastBite restaurant chain, two restaurant locations, managers, customers, couriers, menus, product options, coupons, and promotions.

Useful demo accounts are printed by the seeder when it runs, including:

- Chain manager: `chain@fastbite.pt`
- Local manager: `local@fastbite.pt`

Check `Backend/database/seeders/DemoSeeder.php` for the complete demo dataset and credentials.

## Useful Commands

Backend:

```bash
cd Backend
docker compose up -d --build
docker compose down
composer test
```

Web:

```bash
cd Frontend\web
npm run dev
npm run build
npm run lint
```

Mobile:

```bash
cd Frontend\mobile
npm start
npm run android
npm run ios
npm run web
npm run lint
```

## Suggested Demo Flow

1. Start the backend and seed the demo data.
2. Open the restaurant web dashboard and log in as a manager.
3. Review the menu catalog, active order queue, campaigns, reviews, notifications, and chat.
4. Open the mobile app as a customer and create an order.
5. Open the courier flow, accept or manage a delivery, and update the delivery status.
6. Watch the order status, notifications, courier tracking, and chat update across the system.


