<?php
/**
 * Retorna configurações do OneSignal para o frontend
 */

require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT app_id, safari_web_id FROM onesignal_config ORDER BY id DESC LIMIT 1");
    $config = $stmt->fetch();
    
    if (!$config) {
        echo json_encode([
            'appId' => null,
            'safariWebId' => null,
            'message' => 'OneSignal não configurado. Configure em pages/configuracoes_onesignal.php'
        ]);
        exit;
    }
    
    echo json_encode([
        'appId' => $config['app_id'],
        'safariWebId' => $config['safari_web_id']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

