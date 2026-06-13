# Migração de dados (importação)

Coloque aqui um **dump SQL do banco atual** (ex.: `dump.sql`). Todo arquivo
`.sql` / `.sql.gz` nesta pasta é importado **automaticamente** pelo MariaDB na
**primeira inicialização do volume** (quando o banco está vazio). Depois disso,
o `docker/install_cli.php` detecta que o banco já tem dados e **não** recria nada.

## ⚠️ NÃO comite o dump no git

O dump contém **segredos** (chaves de API: OpenAI, Slack, Evolution/WhatsApp,
Autentique, OneSignal, VAPID; senha SMTP) e **dados pessoais** dos colaboradores
(incl. hashes de senha). Por isso `database/initdb/*.sql` está no `.gitignore` e
**não deve** ser enviado ao repositório.

## Como fazer a importação com segurança

### Opção A — colocar o dump no servidor (importação automática)
1. Gere o dump (veja comandos abaixo).
2. Envie o arquivo `dump.sql` para o servidor do Coolify, **dentro da pasta do
   projeto** em `database/initdb/` (via SFTP, ou pelo gerenciador de arquivos do
   Coolify). Não passa pelo git.
3. Faça o primeiro deploy com o volume do banco vazio → o MariaDB importa sozinho.

### Opção B — importar manualmente após o deploy
1. Faça o deploy normal (o banco sobe vazio / só com o schema base).
2. Abra o **terminal** do serviço de banco no Coolify (ou via SSH no host) e rode:
   ```bash
   # com o dump.sql disponível no host:
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
