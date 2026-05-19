param(
    [switch]$SkipNode
)

$ErrorActionPreference = 'Stop'

function Write-Info($msg) { Write-Host "[INFO] $msg" -ForegroundColor Green }
function Write-WarnMsg($msg) { Write-Host "[WARN] $msg" -ForegroundColor Yellow }
function Write-ErrMsg($msg) { Write-Host "[ERROR] $msg" -ForegroundColor Red }

function Require-Command($name) {
    if (-not (Get-Command $name -ErrorAction SilentlyContinue)) {
        throw "$name is not installed or not on PATH."
    }
}

Write-Info "Installing Enterprise SaaS Starter Kit..."

Require-Command docker

if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
    Write-Info "Created .env from .env.example"
}

$envContent = Get-Content .env -Raw
if ($envContent -notmatch 'APP_KEY=base64:') {
    Write-Info "Generating APP_KEY in .env"
    $bytes = New-Object byte[] 32
    [Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($bytes)
    $key = 'base64:' + [Convert]::ToBase64String($bytes)

    if ($envContent -match '(?m)^APP_KEY=.*$') {
        $envContent = [Regex]::Replace($envContent, '(?m)^APP_KEY=.*$', "APP_KEY=$key")
    } else {
        $envContent += "`r`nAPP_KEY=$key`r`n"
    }

    Set-Content .env $envContent -NoNewline
}

Write-Info "Building containers"
docker compose build

Write-Info "Starting containers"
docker compose up -d

Write-Info "Waiting for postgres healthcheck"
for ($i = 0; $i -lt 60; $i++) {
    try {
        $status = docker inspect --format='{{json .State.Health.Status}}' postgres 2>$null
        if ($status -eq '"healthy"') { break }
    } catch {}
    Start-Sleep -Seconds 3
    if ($i -eq 59) { throw "Postgres did not become healthy in time." }
}

Write-Info "Installing PHP dependencies"
docker exec php-fpm composer config --no-plugins allow-plugins.pestphp/pest-plugin true
docker exec php-fpm composer install --no-interaction --prefer-dist

Write-Info "Running Laravel setup"
docker exec php-fpm php artisan key:generate --force
docker exec php-fpm php artisan migrate --seed --force
docker exec php-fpm php artisan storage:link

docker exec php-fpm php artisan horizon:install
try { docker exec php-fpm php artisan pulse:check } catch { Write-WarnMsg "Pulse check skipped." }

try { docker exec php-fpm php artisan telescope:install } catch { Write-WarnMsg "Telescope install skipped." }

if (-not $SkipNode) {
    try {
        Write-Info "Installing frontend dependencies"
        docker exec php-fpm sh -lc "npm install && npm run build"
    } catch {
        Write-WarnMsg "npm install/build skipped (Node may be unavailable in container)."
    }
}

Write-Info "Installation completed"
Write-Host "App: http://localhost"
Write-Host "Mailpit: http://localhost:8025"
Write-Host "MinIO: http://localhost:9001"
Write-Host "Horizon: http://localhost/horizon"
Write-Host "Pulse: http://localhost/pulse"
Write-Host "Telescope: http://localhost/telescope"
