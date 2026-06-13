<?php
/**
 * Sistema de Instalação - RH Privus
 * Este arquivo cria as tabelas necessárias no banco de dados
 */

// Verifica se já está instalado
if (file_exists('config/db.php')) {
    $config = include 'config/db.php';
    if (!empty($config['host'])) {
        die('Sistema já instalado! Delete este arquivo após a instalação.');
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['host'] ?? 'localhost';
    $dbname = $_POST['dbname'] ?? '';
    $username = $_POST['username'] ?? 'root';
    $password = $_POST['password'] ?? '';
    
    if (empty($dbname)) {
        $error = 'Nome do banco de dados é obrigatório!';
    } else {
        try {
            // Testa conexão
            $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Cria o banco se não existir
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$dbname`");
            
            // SQL para criar todas as tabelas
            $sql = require __DIR__ . '/database/schema.php';
            
            // Executa o SQL
            $pdo->exec($sql);
            
            // Cria arquivo de configuração
            $configContent = "<?php\n";
            $configContent .= "return [\n";
            $configContent .= "    'host' => '$host',\n";
            $configContent .= "    'dbname' => '$dbname',\n";
            $configContent .= "    'username' => '$username',\n";
            $configContent .= "    'password' => '$password',\n";
            $configContent .= "];\n";
            
            // Cria diretório config se não existir
            if (!is_dir('config')) {
                mkdir('config', 0755, true);
            }
            
            file_put_contents('config/db.php', $configContent);
            
            // Cria usuário admin padrão
            $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha_hash, role, status) VALUES (?, ?, ?, 'ADMIN', 'ativo')");
            $stmt->execute(['Administrador', 'admin@privus.com.br', $adminPassword]);
            
            $success = 'Instalação concluída com sucesso!<br>Usuário padrão: admin@privus.com.br<br>Senha: admin123<br><strong>IMPORTANTE: Delete o arquivo install.php após o primeiro login!</strong>';
            
        } catch (PDOException $e) {
            $error = 'Erro: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - RH Privus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Instalação - Sistema RH Privus</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                            <a href="login.php" class="btn btn-primary">Ir para Login</a>
                        <?php else: ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Host do Banco</label>
                                    <input type="text" name="host" class="form-control" value="localhost" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nome do Banco de Dados</label>
                                    <input type="text" name="dbname" class="form-control" placeholder="rh_privus" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Usuário</label>
                                    <input type="text" name="username" class="form-control" value="root" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Senha</label>
                                    <input type="password" name="password" class="form-control">
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Instalar</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

