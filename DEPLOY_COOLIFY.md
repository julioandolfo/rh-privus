# Deploy do RH Privus no Coolify (Docker Compose)

Este guia explica como rodar o sistema no **Coolify** usando Docker, servindo na
**raiz do domínio** (ex.: `https://rh.suaempresa.com.br`), sem o subcaminho `/rh`.

## O que foi preparado

- `Dockerfile` — PHP 8.2 + Apache, com as extensões necessárias (`pdo_mysql`, `gd`,
  `mbstring`, `zip`) e `mod_rewrite` habilitado. Roda `composer install` no build.
- `docker-compose.yml` — dois serviços: `app` (o sistema) e `db` (MariaDB 11).
- `docker/entrypoint.sh` — espera o banco subir, aplica o schema/migração e inicia o Apache.
- `docker/install_cli.php` — instalador **idempotente** (cria banco, tabelas e o admin
  no primeiro boot). Usa o mesmo schema do `install.php` (`database/schema.php`).
- `config/db.php` e `config/email.php` — leem as credenciais de **variáveis de ambiente**.
- `.env.example` — modelo das variáveis.

O sistema monta os caminhos dinamicamente a partir do domínio acessado, por isso
funciona na raiz. Os caminhos fixos de PWA/push que apontavam para `/rh` foram
ajustados para usar a raiz por padrão.

## Passo a passo no Coolify

1. **Crie um recurso** do tipo **Docker Compose** apontando para este repositório
   (branch desejada). O Coolify vai usar o `docker-compose.yml` da raiz.

2. **Defina as variáveis de ambiente** (aba *Environment Variables*), baseadas no
   `.env.example`:

   ```
   DB_NAME=rh_privus
   DB_USER=rh_privus
   DB_PASSWORD=uma_senha_forte
   DB_ROOT_PASSWORD=outra_senha_forte
   ADMIN_EMAIL=voce@suaempresa.com.br
   ADMIN_PASSWORD=uma_senha_inicial
   ```

   (SMTP é opcional, preencha se for usar envio de e-mail.)

3. **Configure o domínio**: na aba de domínios do serviço `app`, informe o seu
   domínio (ex.: `https://rh.suaempresa.com.br`) apontando para a **porta 80** do
   container. O Coolify cuida do proxy reverso e do **HTTPS (Let's Encrypt)**
   automaticamente. Não é preciso mexer em arquivo nenhum para trocar de domínio —
   basta apontar o DNS do domínio para o servidor do Coolify.

4. **Deploy**. No primeiro boot o banco é criado, as tabelas são aplicadas e o
   usuário admin é gerado.

5. **Primeiro login**: acesse o domínio e entre com o `ADMIN_EMAIL` /
   `ADMIN_PASSWORD` definidos. **Troque a senha do admin** em seguida.

## Rodando localmente (teste antes do Coolify)

```bash
cp .env.example .env      # ajuste as senhas
docker compose up --build
# acesse http://localhost:8080
```

## Observações de segurança

- O `install.php` (instalador web) e o `.env` ficam **bloqueados** pelo Apache em
  produção (ver `docker/apache-vhost.conf`). A instalação roda via container.
- O `.env` está no `.gitignore` — nunca comite senhas reais.
- Os dados do banco ficam no volume `db_data`; os arquivos enviados em `uploads_data`.
  No Coolify esses volumes são persistentes entre deploys.

## Notas sobre PWA / Notificações Push

Os recursos de PWA/OneSignal foram ajustados para a raiz do domínio. Se você usa
OneSignal, lembre-se de atualizar a URL do site no painel do OneSignal para o novo
domínio (sem `/rh`).
