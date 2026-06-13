<?php
/**
 * Configuração de conexão com o banco de dados.
 *
 * Em produção (Docker/Coolify) os valores vêm de variáveis de ambiente.
 * Sem variáveis de ambiente, mantém os padrões locais de desenvolvimento.
 */
return [
    'host'     => getenv('DB_HOST')     ?: 'localhost',
    'dbname'   => getenv('DB_NAME')     ?: 'rh-privus',
    'username' => getenv('DB_USER')     ?: 'root',
    'password' => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '',
];
