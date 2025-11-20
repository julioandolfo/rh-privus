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
            $sql = "
            -- Tabela de empresas
            CREATE TABLE IF NOT EXISTS empresas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome_fantasia VARCHAR(255) NOT NULL,
                razao_social VARCHAR(255) NOT NULL,
                cnpj VARCHAR(18) UNIQUE,
                telefone VARCHAR(20),
                email VARCHAR(255),
                cidade VARCHAR(100),
                estado VARCHAR(2),
                status ENUM('ativo', 'inativo') DEFAULT 'ativo',
                percentual_hora_extra DECIMAL(5,2) DEFAULT 50.00,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_cnpj (cnpj),
                INDEX idx_percentual_hora_extra (percentual_hora_extra)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            -- Tabela de setores
            CREATE TABLE IF NOT EXISTS setores (
                id INT AUTO_INCREMENT PRIMARY KEY,
                empresa_id INT NOT NULL,
                nome_setor VARCHAR(255) NOT NULL,
                descricao TEXT,
                status ENUM('ativo', 'inativo') DEFAULT 'ativo',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
                INDEX idx_empresa (empresa_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            -- Tabela de cargos
            CREATE TABLE IF NOT EXISTS cargos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                empresa_id INT NOT NULL,
                nome_cargo VARCHAR(255) NOT NULL,
                descricao TEXT,
                salario_base DECIMAL(10,2),
                status ENUM('ativo', 'inativo') DEFAULT 'ativo',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
                INDEX idx_empresa (empresa_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            -- Tabela de níveis hierárquicos
            CREATE TABLE IF NOT EXISTS niveis_hierarquicos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                codigo VARCHAR(50) UNIQUE NOT NULL,
                nivel INT NOT NULL COMMENT 'Nível na hierarquia (1 = mais alto, maior número = mais baixo)',
                descricao TEXT,
                status ENUM('ativo', 'inativo') DEFAULT 'ativo',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_codigo (codigo),
                INDEX idx_nivel (nivel),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            -- Inserir níveis padrão
            INSERT INTO niveis_hierarquicos (nome, codigo, nivel, descricao, status) VALUES
            ('Diretoria', 'DIRETORIA', 1, 'Nível mais alto da hierarquia', 'ativo'),
            ('Gerência', 'GERENCIA', 2, 'Nível de gerência', 'ativo'),
            ('Supervisão', 'SUPERVISAO', 3, 'Nível de supervisão', 'ativo'),
            ('Coordenação', 'COORDENACAO', 4, 'Nível de coordenação', 'ativo'),
            ('Liderança', 'LIDERANCA', 5, 'Nível de liderança', 'ativo'),
            ('Operacional', 'OPERACIONAL', 6, 'Nível operacional', 'ativo');
            
            -- Tabela de colaboradores
            CREATE TABLE IF NOT EXISTS colaboradores (
                id INT AUTO_INCREMENT PRIMARY KEY,
                empresa_id INT NOT NULL,
                setor_id INT NOT NULL,
                cargo_id INT NOT NULL,
                nivel_hierarquico_id INT NULL,
                lider_id INT NULL,
                nome_completo VARCHAR(255) NOT NULL,
                cpf VARCHAR(14) UNIQUE,
                rg VARCHAR(20),
                data_nascimento DATE,
                telefone VARCHAR(20),
                email_pessoal VARCHAR(255),
                data_inicio DATE NOT NULL,
                status ENUM('ativo', 'pausado', 'desligado') DEFAULT 'ativo',
                tipo_contrato ENUM('PJ', 'CLT', 'Estágio', 'Terceirizado') DEFAULT 'PJ',
                salario DECIMAL(10,2) NULL,
                pix VARCHAR(255) NULL,
                banco VARCHAR(100) NULL,
                agencia VARCHAR(20) NULL,
                conta VARCHAR(30) NULL,
                tipo_conta ENUM('corrente', 'poupanca') NULL,
                cnpj VARCHAR(18) NULL,
                cep VARCHAR(10) NULL,
                logradouro VARCHAR(255) NULL,
                numero VARCHAR(20) NULL,
                complemento VARCHAR(255) NULL,
                bairro VARCHAR(100) NULL,
                cidade_endereco VARCHAR(100) NULL,
                estado_endereco VARCHAR(2) NULL,
                observacoes TEXT,
                foto VARCHAR(255) NULL,
                senha_hash VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
                FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE RESTRICT,
                FOREIGN KEY (cargo_id) REFERENCES cargos(id) ON DELETE RESTRICT,
                FOREIGN KEY (nivel_hierarquico_id) REFERENCES niveis_hierarquicos(id) ON DELETE SET NULL,
                FOREIGN KEY (lider_id) REFERENCES colaboradores(id) ON DELETE SET NULL,
                INDEX idx_empresa (empresa_id),
                INDEX idx_setor (setor_id),
                INDEX idx_cargo (cargo_id),
                INDEX idx_nivel_hierarquico (nivel_hierarquico_id),
                INDEX idx_lider (lider_id),
                INDEX idx_status (status),
                INDEX idx_cpf (cpf)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            -- Tabela de usuários do sistema
            CREATE TABLE IF NOT EXISTS usuarios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                senha_hash VARCHAR(255) NOT NULL,
                role ENUM('ADMIN', 'RH', 'GESTOR', 'COLABORADOR') NOT NULL,
                empresa_id INT NULL,
                setor_id INT NULL,
                colaborador_id INT NULL,
                foto VARCHAR(255) NULL,
                ultimo_login TIMESTAMP NULL,
                status ENUM('ativo', 'inativo') DEFAULT 'ativo',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
                FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE SET NULL,
                FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE SET NULL,
                INDEX idx_email (email),
                INDEX idx_role (role),
                INDEX idx_empresa (empresa_id),
                INDEX idx_setor (setor_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            -- Tabela de ocorrências
            CREATE TABLE IF NOT EXISTS ocorrencias (
                id INT AUTO_INCREMENT PRIMARY KEY,
                colaborador_id INT NOT NULL,
                usuario_id INT NOT NULL,
                tipo VARCHAR(100) NOT NULL,
                descricao LONGTEXT,
                data_ocorrencia DATE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
                INDEX idx_colaborador (colaborador_id),
                INDEX idx_usuario (usuario_id),
                INDEX idx_data (data_ocorrencia),
                INDEX idx_tipo (tipo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            -- Tabela de promoções/salários
            CREATE TABLE IF NOT EXISTS promocoes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                colaborador_id INT NOT NULL,
                salario_anterior DECIMAL(10,2) NOT NULL,
                salario_novo DECIMAL(10,2) NOT NULL,
                motivo TEXT NOT NULL,
                data_promocao DATE NOT NULL,
                usuario_id INT NULL,
                observacoes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
                INDEX idx_colaborador (colaborador_id),
                INDEX idx_data_promocao (data_promocao)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            -- Tabela de horas extras
            CREATE TABLE IF NOT EXISTS horas_extras (
                id INT AUTO_INCREMENT PRIMARY KEY,
                colaborador_id INT NOT NULL,
                data_trabalho DATE NOT NULL,
                quantidade_horas DECIMAL(5,2) NOT NULL COMMENT 'Quantidade de horas extras trabalhadas',
                valor_hora DECIMAL(10,2) NOT NULL COMMENT 'Valor da hora normal do colaborador',
                percentual_adicional DECIMAL(5,2) NOT NULL COMMENT '% adicional de hora extra',
                valor_total DECIMAL(10,2) NOT NULL COMMENT 'Valor total calculado',
                observacoes TEXT,
                usuario_id INT NULL COMMENT 'Usuário que cadastrou',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
                INDEX idx_colaborador (colaborador_id),
                INDEX idx_data_trabalho (data_trabalho),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            -- Tabela de fechamentos de pagamento
            CREATE TABLE IF NOT EXISTS fechamentos_pagamento (
                id INT AUTO_INCREMENT PRIMARY KEY,
                empresa_id INT NOT NULL,
                mes_referencia VARCHAR(7) NOT NULL COMMENT 'Formato: YYYY-MM',
                data_fechamento DATE NOT NULL,
                total_colaboradores INT DEFAULT 0,
                total_pagamento DECIMAL(12,2) DEFAULT 0.00,
                total_horas_extras DECIMAL(12,2) DEFAULT 0.00,
                status ENUM('aberto', 'fechado', 'pago') DEFAULT 'aberto',
                observacoes TEXT,
                usuario_id INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
                INDEX idx_empresa (empresa_id),
                INDEX idx_mes_referencia (mes_referencia),
                INDEX idx_status (status),
                UNIQUE KEY uk_empresa_mes (empresa_id, mes_referencia)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            -- Tabela de itens do fechamento
            CREATE TABLE IF NOT EXISTS fechamentos_pagamento_itens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                fechamento_id INT NOT NULL,
                colaborador_id INT NOT NULL,
                salario_base DECIMAL(10,2) NOT NULL,
                horas_extras DECIMAL(5,2) DEFAULT 0.00,
                valor_horas_extras DECIMAL(10,2) DEFAULT 0.00,
                descontos DECIMAL(10,2) DEFAULT 0.00,
                adicionais DECIMAL(10,2) DEFAULT 0.00,
                valor_total DECIMAL(10,2) NOT NULL,
                observacoes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (fechamento_id) REFERENCES fechamentos_pagamento(id) ON DELETE CASCADE,
                FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE,
                INDEX idx_fechamento (fechamento_id),
                INDEX idx_colaborador (colaborador_id),
                UNIQUE KEY uk_fechamento_colaborador (fechamento_id, colaborador_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            
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

