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

echo "[entrypoint] Banco disponível."

DB_NAME="${DB_NAME:-rh_privus}"
DB_USER="${DB_USER:-root}"
DUMP_FILE="/var/www/html/database/initdb/dump.sql"

# Migração: se o banco está vazio (sem a tabela 'usuarios') e existe um dump,
# importa o dump com o cliente mariadb (robusto para dumps grandes do phpMyAdmin).
if [ -f "$DUMP_FILE" ]; then
    HAS_USERS=$(mariadb -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASSWORD" -N -B \
        -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME' AND table_name='usuarios';" 2>/dev/null || echo "erro")
    if [ "$HAS_USERS" = "0" ]; then
        echo "[entrypoint] Banco vazio + dump encontrado. Importando dump.sql ..."
        if mariadb -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < "$DUMP_FILE"; then
            echo "[entrypoint] Dump importado com sucesso."
        else
            echo "[entrypoint] ERRO ao importar o dump. Verifique o arquivo."
        fi
    else
        echo "[entrypoint] Banco já possui dados (HAS_USERS=$HAS_USERS). Dump não reimportado."
    fi
fi

echo "[entrypoint] Rodando instalador/migração (idempotente) ..."
php /var/www/html/docker/install_cli.php

# Garante permissões de escrita para uploads
chown -R www-data:www-data /var/www/html/uploads 2>/dev/null || true

echo "[entrypoint] Iniciando Apache ..."
exec apache2-foreground
