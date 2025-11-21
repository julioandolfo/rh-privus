<?php
/**
 * Página de teste para debug de subscription OneSignal
 * Acesse: http://localhost/rh-privus/test_subscription.php
 */

session_start();
require_once __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$usuario = $_SESSION['usuario'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Subscription OneSignal</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; }
        .log {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
            max-height: 400px;
            overflow-y: auto;
            margin-top: 10px;
        }
        .log-item {
            margin: 5px 0;
            padding: 5px;
            border-left: 3px solid #ccc;
            padding-left: 10px;
        }
        .log-success { border-color: #28a745; }
        .log-error { border-color: #dc3545; }
        .log-warning { border-color: #ffc107; }
        .log-info { border-color: #17a2b8; }
        button {
            background: #009ef7;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover { background: #0088d1; }
    </style>
</head>
<body>
    <h1>🔍 Teste de Subscription OneSignal</h1>
    
    <div class="card">
        <h2>Informações do Usuário</h2>
        <p><strong>ID:</strong> <?= $usuario['id'] ?></p>
        <p><strong>Nome:</strong> <?= htmlspecialchars($usuario['nome']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($usuario['email']) ?></p>
        <p><strong>Colaborador ID:</strong> <?= $usuario['colaborador_id'] ?? 'N/A' ?></p>
    </div>
    
    <div class="card">
        <h2>Status do OneSignal</h2>
        <div id="onesignal-status">Carregando...</div>
        <button onclick="checkOneSignal()">Verificar OneSignal</button>
        <button onclick="getPlayerId()">Obter Player ID</button>
        <button onclick="registerPlayer()">Registrar Player</button>
        <button onclick="checkSubscriptions()">Verificar Subscriptions</button>
    </div>
    
    <div class="card">
        <h2>Logs</h2>
        <div id="logs" class="log"></div>
        <button onclick="clearLogs()">Limpar Logs</button>
    </div>
    
    <div class="card">
        <h2>Subscriptions no Banco</h2>
        <div id="subscriptions-list">Carregando...</div>
    </div>
    
    <!-- OneSignal SDK -->
    <script src="https://cdn.onesignal.com/sdks/OneSignalSDK.js" async></script>
    <script src="assets/js/onesignal-init.js"></script>
    
    <script>
        const log = (message, type = 'info') => {
            const logsDiv = document.getElementById('logs');
            const item = document.createElement('div');
            item.className = `log-item log-${type}`;
            item.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
            logsDiv.appendChild(item);
            logsDiv.scrollTop = logsDiv.scrollHeight;
            console.log(message);
        };
        
        const clearLogs = () => {
            document.getElementById('logs').innerHTML = '';
        };
        
        const checkOneSignal = () => {
            log('Verificando OneSignal...', 'info');
            if (typeof OneSignal === 'undefined') {
                log('❌ OneSignal não está carregado', 'error');
                document.getElementById('onesignal-status').innerHTML = '<span class="error">❌ OneSignal não carregado</span>';
            } else {
                log('✅ OneSignal está carregado', 'success');
                document.getElementById('onesignal-status').innerHTML = '<span class="success">✅ OneSignal carregado</span>';
            }
        };
        
        const getPlayerId = () => {
            log('Obtendo Player ID...', 'info');
            OneSignal.push(function() {
                OneSignal.getUserId(function(userId) {
                    if (userId) {
                        log(`✅ Player ID: ${userId}`, 'success');
                        document.getElementById('onesignal-status').innerHTML = `<span class="success">✅ Player ID: ${userId}</span>`;
                    } else {
                        log('⚠️ Player ID não disponível ainda', 'warning');
                        document.getElementById('onesignal-status').innerHTML = '<span class="warning">⚠️ Player ID não disponível</span>';
                    }
                });
            });
        };
        
        const registerPlayer = async () => {
            log('Tentando registrar player...', 'info');
            try {
                await OneSignalInit.registerPlayer();
                log('✅ Registro iniciado', 'success');
            } catch (error) {
                log(`❌ Erro: ${error.message}`, 'error');
            }
        };
        
        const checkSubscriptions = async () => {
            log('Verificando subscriptions no banco...', 'info');
            try {
                const path = window.location.pathname;
                let basePath = '/rh';
                if (path.includes('/rh-privus')) {
                    basePath = '/rh-privus';
                }
                
                const url = basePath + '/api/onesignal/subscribe.php';
                const response = await fetch(url, {
                    method: 'GET',
                    credentials: 'include'
                });
                
                const data = await response.json();
                log(`Resposta: ${JSON.stringify(data)}`, 'info');
            } catch (error) {
                log(`❌ Erro: ${error.message}`, 'error');
            }
        };
        
        // Carrega subscriptions do banco
        const loadSubscriptions = async () => {
            try {
                const path = window.location.pathname;
                let basePath = '/rh';
                if (path.includes('/rh-privus')) {
                    basePath = '/rh-privus';
                }
                
                const response = await fetch(basePath + '/api/onesignal/list_subscriptions.php', {
                    credentials: 'include'
                });
                
                if (response.ok) {
                    const data = await response.json();
                    const listDiv = document.getElementById('subscriptions-list');
                    if (data.success && data.subscriptions.length > 0) {
                        let html = '<table style="width:100%; border-collapse: collapse;">';
                        html += '<tr><th>ID</th><th>Player ID</th><th>Usuário</th><th>Colaborador</th><th>Device</th><th>Data</th></tr>';
                        data.subscriptions.forEach(sub => {
                            html += `<tr>
                                <td>${sub.id}</td>
                                <td>${sub.player_id.substring(0, 20)}...</td>
                                <td>${sub.usuario_id || '-'}</td>
                                <td>${sub.colaborador_id || '-'}</td>
                                <td>${sub.device_type}</td>
                                <td>${sub.created_at}</td>
                            </tr>`;
                        });
                        html += '</table>';
                        listDiv.innerHTML = html;
                    } else {
                        listDiv.innerHTML = '<span class="warning">⚠️ Nenhuma subscription encontrada</span>';
                    }
                }
            } catch (error) {
                document.getElementById('subscriptions-list').innerHTML = `<span class="error">Erro: ${error.message}</span>`;
            }
        };
        
        // Inicializa
        window.addEventListener('load', () => {
            setTimeout(() => {
                checkOneSignal();
                loadSubscriptions();
            }, 2000);
        });
        
        // Intercepta logs do OneSignalInit
        const originalLog = console.log;
        console.log = function(...args) {
            if (args[0] && typeof args[0] === 'string') {
                if (args[0].includes('OneSignal') || args[0].includes('Player') || args[0].includes('subscription')) {
                    log(args.join(' '), 'info');
                }
            }
            originalLog.apply(console, args);
        };
    </script>
</body>
</html>

