# Proyecto Chat (Laravel + Vue + Docker)

Aplicación de chat en tiempo real construida con Laravel (v12.x) para el backend, Vue.js 3 (Vite) para el frontend, y Docker para el entorno de desarrollo. Usa MySQL y Redis.

## Prerrequisitos

* Docker Desktop instalado y corriendo.
* Git (opcional, para clonar).
* Navegador web moderno.
* Cliente API como Postman o Insomnia (recomendado para probar el backend).

## Configuración Inicial

1.  **Clonar Repositorio (Si aplica):**
    ```bash
    git clone <tu-url-de-repositorio>
    cd proyecto-chat
    ```

2.  **Archivo de Entorno Backend (`.env`):**
    * Copia el archivo de ejemplo:
        ```bash
        # Estando en la raíz del proyecto
        cp backend/.env.example backend/.env
        ```
    * **Edita `backend/.env`** y configura **cuidadosamente** las siguientes variables para que coincidan con tu entorno Docker y necesidades:
        ```dotenv
        APP_NAME="Proyecto Chat"
        APP_ENV=local
        APP_KEY= # Se generará después
        APP_DEBUG=true
        APP_URL=http://localhost:8000 # URL del backend

        LOG_CHANNEL=stack
        LOG_LEVEL=debug

        DB_CONNECTION=mysql
        DB_HOST=db          # Nombre del servicio DB en docker-compose.yml
        DB_PORT=3306
        DB_DATABASE=chat_app # Nombre de la BD en docker-compose.yml
        DB_USERNAME=root    # Usuario de la BD en docker-compose.yml
        DB_PASSWORD=secret  # Contraseña de la BD en docker-compose.yml

        # Usar Redis para mejor rendimiento (servicio 'redis' en docker-compose.yml)
        BROADCAST_CONNECTION=log # Cambiar a redis/pusher para WebSockets después
        CACHE_STORE=redis
        QUEUE_CONNECTION=redis
        SESSION_DRIVER=redis

        REDIS_HOST=redis    # Nombre del servicio Redis en docker-compose.yml
        REDIS_PASSWORD=null
        REDIS_PORT=6379

        # Configuración para Sanctum SPA (autenticación frontend/backend)
        SESSION_DOMAIN=localhost 
        FRONTEND_URL=http://localhost:5173 # URL del frontend Vue

        # Configuración Email (log para desarrollo)
        MAIL_MAILER=log
        MAIL_HOST=null
        MAIL_PORT=null
        MAIL_USERNAME=null
        MAIL_PASSWORD=null
        MAIL_ENCRYPTION=null
        MAIL_FROM_ADDRESS="hello@example.com"
        MAIL_FROM_NAME="${APP_NAME}"
        ```

3.  **Ajustar Imagen de Node en Docker Compose:**
    * Edita `docker-compose.yml`.
    * Asegúrate de que el servicio `frontend` use una imagen de Node reciente (ej: `node:20-alpine`):
        ```yaml
        services:
          # ... otros servicios ...
          frontend:
            image: node:20-alpine # Asegúrate de usar Node 18+
            # ... resto de la configuración ...
        ```

4.  **Construir e Iniciar Contenedores Docker:**
    ```bash
    docker-compose up -d --build
    ```

5.  **Instalar Dependencias Backend (Composer):**
    ```bash
    docker-compose exec backend composer install
    ```

6.  **Generar Clave de Aplicación Laravel:**
    ```bash
    docker-compose exec backend php artisan key:generate
    ```
    * Verifica que la variable `APP_KEY` en `backend/.env` ahora tiene un valor.

7.  **Limpiar Caché de Configuración:** (Importante después de generar clave y editar `.env`)
    ```bash
    docker-compose exec backend php artisan config:clear
    ```

8.  **Ejecutar Migraciones de Base de Datos:**
    ```bash
    docker-compose exec backend php artisan migrate
    ```

9.  **Instalar Laravel Breeze (Auth Scaffolding):**
    ```bash
    docker-compose exec backend composer require laravel/breeze --dev
    docker-compose exec backend php artisan breeze:install vue 
    docker-compose exec backend php artisan migrate # Ejecutar de nuevo por si Breeze añadió migraciones
    ```
    * *Nota: Esto instala el stack Breeze+Vue+Inertia. Se requieren adaptaciones (ver abajo).*

10. **Crear Archivo de Rutas API:**
    * Crea el archivo `backend/routes/api.php`.
    * Añade el contenido básico y la ruta `/api/user`:
        ```php
        <?php
        use Illuminate\Http\Request;
        use Illuminate\Support\Facades\Route;

        Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
            return $request->user();
        });
        // Añadir más rutas API aquí...
        ```

11. **Configurar Carga de Rutas API (Laravel 11+):**
    * Edita `backend/bootstrap/app.php`.
    * Modifica `->withRouting(...)` para incluir `api` y `apiPrefix`:
        ```php
        ->withRouting(
            web: __DIR__.'/../routes/web.php',
            api: __DIR__.'/../routes/api.php', // Carga api.php
            apiPrefix: 'api', // Añade prefijo /api
            commands: __DIR__.'/../routes/console.php',
            health: '/up',
        )
        ```
    * Asegúrate de que `$middleware->statefulApi();` esté presente en `->withMiddleware(...)`.

12. **Limpiar Caché de Rutas:**
    ```bash
    docker-compose exec backend php artisan route:clear
    ```

13. **Inicializar y Configurar Frontend (Vue):**
    * Entra al contenedor frontend: `docker-compose exec frontend bash`
    * Dentro del contenedor, inicializa Vite: `npm create vite@latest . --template vue` (elimina archivos existentes si pregunta).
    * Instala dependencias: `npm install axios vue-router pinia`
    * Edita `package.json` (dentro del contenedor o desde el host en `./frontend/package.json`): cambia el script `"dev"` a `"vite --host"`.
    * Sal del contenedor: `exit`

14. **Reiniciar Contenedor Frontend:**
    ```bash
    docker-compose up -d --force-recreate frontend
    ```

## Ejecutando la Aplicación

* **Iniciar todos los servicios:** `docker-compose up -d`
* **Iniciar Servidor Backend Laravel (Necesario Manualmente):** Ejecuta en una terminal separada y déjala corriendo:
    ```bash
    docker-compose exec backend php artisan serve --host=0.0.0.0 --port=8000
    ```
* **Detener todos los servicios:** `docker-compose down`
* **Ver estado:** `docker-compose ps`
* **Ver logs (ej: frontend):** `docker-compose logs frontend`

## Accediendo a la Aplicación

* **Frontend (Vue SPA):** `http://localhost:5173`
* **Backend API (Laravel):** `http://localhost:8000` (Necesita `php artisan serve` corriendo). Los endpoints API estarán bajo `/api/` (ej: `http://localhost:8000/api/user`).

## Nota Importante Post-Instalación

La instalación de Breeze (`breeze:install vue`) configura el backend para Inertia.js por defecto. Para usarlo como una API pura para tu SPA Vue separada, **necesitarás modificar los controladores de autenticación** en `backend/app/Http/Controllers/Auth/` para que acepten y devuelvan respuestas JSON en lugar de vistas/redirecciones. Prueba los endpoints (`/login`, `/register`, `/api/user`, `/logout`) con un cliente API (Postman/Insomnia) para verificar su comportamiento y ajusta los controladores según sea necesario.

## Tecnologías Principales

* Laravel 12.x (PHP 8.2+)
* Vue.js 3 (Vite)
* MySQL 8.0
* Redis (Alpine)
* Docker / Docker Compose
* Laravel Sanctum (para autenticación SPA)
* Laravel Breeze (scaffolding de autenticación adaptado)

---