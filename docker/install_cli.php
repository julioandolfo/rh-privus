<?php
/**
 * Instalador via CLI (usado pelo container Docker / Coolify).
 *
 * Idempotente: pode rodar a cada inicialização do container.
 *  - Cria o banco caso não exista
 *  - Aplica o schema (CREATE TABLE IF NOT EXISTS ...)
 *  - Cria o usuário administrador padrão caso ainda não exista nenhum usuário
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

    // Cria o banco se necessário
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbname}`");

    // Aplica o schema (fonte única, compartilhada com install.php)
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
    } else {
        fwrite(STDOUT, "[install] Usuários já existem ({$count}). Admin não recriado.\n");
    }

    fwrite(STDOUT, "[install] Concluído com sucesso.\n");
    exit(0);
} catch (PDOException $e) {
    fwrite(STDERR, "[install] ERRO: " . $e->getMessage() . "\n");
    exit(1);
}
