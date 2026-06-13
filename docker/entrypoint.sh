#!/bin/sh
set -e

DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"

echo "[entrypoint] Aguardando o banco de dados em ${DB_HOST}:${DB_PORT} ..."

# Espera o banco aceitar conexões (até ~60s)
i=0
until php -r '
    $h=getenv("DB_HOST")?:"db"; $p=getenv("DB_PORT")?:"3306";
    $u=getenv("DB_USER")?:"root"; $pw=getenv("DB_PASSWORD")!==false?getenv("DB_PASSWORD"):"";
    try { new PDO("mysql:host=$h;port=$p", $u, $pw); exit(0); }
    catch (Exception $e) { exit(1); }
' 2>/dev/null; do
    i=$((i+1))
    if [ "$i" -ge 30 ]; then
        echo "[entrypoint] Banco indisponível após várias tentativas. Abortando."
        exit 1
    fi
    echo "[entrypoint] Banco ainda não disponível (tentativa ${i})... aguardando 2s"
    sleep 2
done

echo "[entrypoint] Banco disponível. Rodando instalador/migração ..."
php /var/www/html/docker/install_cli.php

# Garante permissões de escrita para uploads
chown -R www-data:www-data /var/www/html/uploads 2>/dev/null || true

echo "[entrypoint] Iniciando Apache ..."
exec apache2-foreground
