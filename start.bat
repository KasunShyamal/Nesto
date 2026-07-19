@echo off
echo ===================================================
echo Starting Nesto Loyalty System Setup
echo ===================================================

:: Check if .env file exists, if not copy from .env
if not exist .env (
    echo [1/4] Creating .env file from .env...
    copy .env.example .env
) else (
    echo [1/4] .env file already exists.
)

echo [2/4] Starting Docker containers...
docker compose up -d --build

echo [3/4] Installing Composer dependencies inside the container...
docker compose exec app composer install

echo [4/4] Generating application key and running migrations with seeders...
docker compose exec app php artisan key:generate --force
docker compose exec app php artisan migrate:fresh --seed

echo ===================================================
echo SETUP COMPLETED SUCCESSFULLY!
echo ===================================================
echo Nesto Loyalty API: http://localhost:8080
echo API Documentation: http://localhost:8080/docs (if configured)
echo ===================================================
pause
