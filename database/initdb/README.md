# Migração de dados (importação)

Todo arquivo `.sql` / `.sql.gz` nesta pasta é importado **automaticamente** pelo
MariaDB na **primeira inicialização do volume** (quando o banco está vazio). Depois
disso, o `docker/install_cli.php` detecta que o banco já tem dados e **não** recria nada.

## `dump.sql` (versão SANITIZADA — pode ir ao git)

O `dump.sql` versionado aqui é a exportação completa do sistema **com os dados
preservados** (colaboradores, configs, etc.), porém com os **segredos substituídos
por placeholders** (`SANITIZADO_...`): chaves OpenAI, Evolution/WhatsApp, Autentique,
OneSignal e a senha SMTP. Os hashes de senha dos usuários foram mantidos.

> Após o deploy, **recadastre as chaves reais** nas telas de integração do sistema
> (OpenAI, WhatsApp/Evolution, Autentique, OneSignal) e a senha SMTP. Use **chaves
> novas** (as antigas devem ser rotacionadas por segurança).

`database/initdb/*.sql` continua no `.gitignore` (dumps crus, com segredos, **não**
devem ser commitados). Apenas `dump.sql`, por ser a versão sanitizada, é exceção.

## Importação manual (alternativa)

```bash
docker exec -i <container_do_db> \
  mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE" < dump.sql
```

## Como gerar o dump do sistema antigo

### Via linha de comando (mysqldump)
```bash
mysqldump -u SEU_USUARIO -p \
  --no-tablespaces --default-character-set=utf8mb4 \
  --single-transaction \
  NOME_DO_BANCO > dump.sql
```
> **Não** use `--databases` (assim o dump entra no banco configurado no deploy).

### Via phpMyAdmin
Selecione o banco → **Exportar** → método **Personalizado** → formato **SQL** →
em "Objetos", desmarque criar/usar database (CREATE DATABASE / USE) → baixe.

## Importante
- A importação automática só ocorre com o **volume do banco vazio** (primeira vez).
  Para reimportar, apague o volume do banco no Coolify.
- **Rotacione/troque as chaves de API** que estavam no dump antes de usar em produção,
  caso o arquivo tenha sido exposto em algum lugar.
