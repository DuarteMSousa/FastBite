# FastBite

Projeto com tres partes:

- `Backend`: API Laravel/GraphQL, PostgreSQL, queue jobs e WebSockets com GatewayWorker.
- `Frontend/web`: aplicacao React/Vite para gestao/operacao do restaurante.
- `Frontend/mobile`: aplicacao React Native/Expo para cliente e estafeta.

## 1. Preparar o projeto

Depois de extrair o zip, abrir um terminal na pasta raiz do projeto:

```bash
cd PEDWM_TP2
```

Criar os ficheiros `.env` a partir dos exemplos:

```bash
copy Backend\.env.example Backend\.env
copy Frontend\web\.env.example Frontend\web\.env
copy Frontend\mobile\.env.example Frontend\mobile\.env
```

## 2. Backend

```bash
cd Backend
docker compose up -d --build
```

O backend fica disponivel em:

- API/GraphQL: `http://127.0.0.1/graphql`
- GraphQL Playground, se ativo: `http://127.0.0.1/graphql-playground`
- WebSocket Gateway: `ws://127.0.0.1:8090`
- pgAdmin: `http://127.0.0.1:5050`

Para parar o backend:

```bash
docker compose down
```

## 3. Correr o frontend web

Abrir outro terminal:

```bash
cd Frontend\web
npm install
npm run dev
```

A aplicacao web fica normalmente em:

```text
http://localhost:5173
```

O ficheiro `Frontend/web/.env` deve apontar para o backend

## 4. Correr o frontend mobile

Abrir outro terminal:

```bash
cd Frontend\mobile
npm install
npm start
```

Depois:

- Para telemovel fisico: ler o QR code com Expo Go.
- Para Android Emulator: carregar `a` no terminal ou correr `npm run android`.
- Para iOS Simulator, em macOS: carregar `i` no terminal ou correr `npm run ios`.
- Para testar em browser: `npm run web`.

### Configurar IP para telemovel fisico

Se usar Expo Go num telemovel real, `127.0.0.1` aponta para o proprio telemovel, nao para o computador. Nesse caso, editar `Frontend/mobile/.env` e usar o IP local do computador na rede Wi-Fi.

Exemplo:

```env
EXPO_PUBLIC_API_BASE_URL=http://192.168.1.xxx
EXPO_PUBLIC_GATEWAY_WORKER_HOST=192.168.1.xxx
EXPO_PUBLIC_GATEWAY_WORKER_PORT=8090
EXPO_PUBLIC_GATEWAY_WORKER_SCHEME=ws
```


