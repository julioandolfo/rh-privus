# Migração de dados (importação automática)

Coloque aqui um **dump SQL do seu banco atual** (ex.: `dump.sql` ou `dump.sql.gz`).

Todo arquivo `.sql` / `.sql.gz` nesta pasta é importado **automaticamente** pelo
MariaDB na **primeira inicialização do volume do banco** (quando ele está vazio).
Depois disso, o `docker/install_cli.php` detecta que o banco já tem dados e **não**
recria tabelas nem o usuário admin — ou seja, seus dados são preservados.

## Como gerar o dump do sistema antigo

### Opção A — via linha de comando (mysqldump)
No servidor onde roda o sistema hoje:

```bash
mysqldump -u SEU_USUARIO -p \
  --no-tablespaces --default-character-set=utf8mb4 \
  --skip-add-locks --single-transaction \
  rh-privus > dump.sql
```

> Use o nome do banco atual (provavelmente `rh-privus`). **Não** use `--databases`,
> assim o dump é importado dentro do banco configurado no deploy (`DB_NAME`).

### Opção B — via phpMyAdmin
1. Selecione o banco atual → aba **Exportar**.
2. Método **Personalizado** → formato **SQL**.
3. Em "Objetos", **desmarque** a opção de criar/usar database (CREATE DATABASE /
   USE), para o dump entrar no banco do deploy.
4. Baixe o arquivo e salve como `dump.sql`.

## Onde colocar
1. Renomeie o arquivo para `dump.sql` (ou mantenha `.sql.gz` se compactado).
2. Coloque-o nesta pasta (`database/initdb/`) e faça commit/push.
3. Faça o deploy. Na primeira subida o banco importa o dump sozinho.

## Importante
- A importação só acontece com o **volume do banco vazio** (primeira vez). Se você
  já fez deploy antes, apague o volume do banco no Coolify para reimportar.
- **Não comite dados sensíveis em repositório público.** Este repositório é privado;
  ainda assim, recomenda-se **remover o `dump.sql`** após a migração concluída.
