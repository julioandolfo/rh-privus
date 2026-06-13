<?php
/**
 * Instalador via CLI (usado pelo container Docker / Coolify).
 *
 * Idempotente: pode rodar a cada inicialização do container.
 *  - Cria o banco caso não exista
 *  - Se o banco estiver VAZIO (tabela `usuarios` inexistente), aplica o schema
 *    (CREATE TABLE + dados-semente) e cria o usuário administrador padrão.
 *  - Se o banco JÁ tiver dados (ex.: importados de um dump de migração),
 *    não recria nada e não duplica os dados-semente.
 *
 * Observação: a importação de um dump completo do banco antigo é feita
 * automaticamente pelo serviço de banco (MariaDB) via /docker-entrypoint-initdb.d
 * na primeira inicialização do volume. Veja database/initdb/README.md.
 *
 * Variáveis de ambiente:
 *  DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD
 *  ADMIN_EMAIL    (padrão: admin@privus.com.br)
 *  ADMIN_PASSWORD (padrão: admin123)
 *  ADMIN_NAME     (padrão: Administrador)
 */

$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_NAME') ?: 'rh_privus';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '';

$adminEmail = getenv('ADMIN_EMAIL') ?: 'admin@privus.com.br';
$adminPassword = getenv('ADMIN_PASSWORD') ?: 'admin123';
$adminName = getenv('ADMIN_NAME') ?: 'Administrador';

fwrite(STDOUT, "[install] Conectando em {$host}:{$port} ...\n");

try {
    // Conecta ao servidor (sem selecionar o banco ainda)
    $pdo = new PDO(
        "mysql:host={$host};port={$port};charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Cria o banco se necessário e seleciona
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbname}`");

    // Verifica se o banco já está populado (tabela `usuarios` existe?)
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.tables
         WHERE table_schema = ? AND table_name = 'usuarios'"
    );
    $stmt->execute([$dbname]);
    $jaInstalado = (int) $stmt->fetchColumn() > 0;

    if ($jaInstalado) {
        fwrite(STDOUT, "[install] Banco já possui dados (tabela 'usuarios' existe). Nada a fazer.\n");
        exit(0);
    }

    // Banco vazio: aplica o schema (fonte única, compartilhada com install.php)
    fwrite(STDOUT, "[install] Banco vazio. Aplicando schema ...\n");
    $sql = require __DIR__ . '/../database/schema.php';
    $pdo->exec($sql);
    fwrite(STDOUT, "[install] Schema aplicado.\n");

    // Cria o admin somente se não houver nenhum usuário
    $count = (int) $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    if ($count === 0) {
        $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            "INSERT INTO usuarios (nome, email, senha_hash, role, status) VALUES (?, ?, ?, 'ADMIN', 'ativo')"
        );
        $stmt->execute([$adminName, $adminEmail, $hash]);
        fwrite(STDOUT, "[install] Usuário admin criado: {$adminEmail}\n");
    }

    fwrite(STDOUT, "[install] Concluído com sucesso.\n");
    exit(0);
} catch (PDOException $e) {
    fwrite(STDERR, "[install] ERRO: " . $e->getMessage() . "\n");
    exit(1);
}
