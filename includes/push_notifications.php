<?php
/**
 * Funções helper para enviar notificações push
 */

require_once __DIR__ . '/functions.php';

/**
 * Envia notificação push para um colaborador específico
 * 
 * @param int $colaborador_id ID do colaborador
 * @param string $titulo Título da notificação
 * @param string $mensagem Mensagem da notificação
 * @param string $url URL para abrir ao clicar (opcional)
 * @return array ['success' => bool, 'enviadas' => int, 'message' => string]
 */
function enviar_push_colaborador($colaborador_id, $titulo, $mensagem, $url = null) {
    try {
        $pdo = getDB();
        
        // Busca dados do colaborador para URL padrão
        if (!$url) {
            $stmt = $pdo->prepare("SELECT id FROM colaboradores WHERE id = ?");
            $stmt->execute([$colaborador_id]);
            $colab = $stmt->fetch();
            if ($colab) {
                $basePath = get_base_url();
                $url = $basePath . '/pages/colaborador_view.php?id=' . $colaborador_id;
            }
        }
        
        // Prepara URL completa se for relativa
        if (strpos($url, 'http') !== 0) {
            $basePath = get_base_url();
            $url = $basePath . '/' . ltrim($url, '/');
        }
        
        // Chama API do OneSignal internamente
        $ch = curl_init(get_base_url() . '/api/onesignal/send.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'colaborador_id' => $colaborador_id,
            'titulo' => $titulo,
            'mensagem' => $mensagem,
            'url' => $url
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return [
                'success' => true,
                'enviadas' => $data['enviadas'] ?? 0,
                'message' => 'Notificação enviada com sucesso'
            ];
        } else {
            $error = json_decode($response, true);
            return [
                'success' => false,
                'enviadas' => 0,
                'message' => $error['message'] ?? 'Erro ao enviar notificação'
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'enviadas' => 0,
            'message' => 'Erro: ' . $e->getMessage()
        ];
    }
}

/**
 * Envia notificação push para um usuário específico
 * 
 * @param int $usuario_id ID do usuário
 * @param string $titulo Título da notificação
 * @param string $mensagem Mensagem da notificação
 * @param string $url URL para abrir ao clicar (opcional)
 * @return array ['success' => bool, 'enviadas' => int, 'message' => string]
 */
function enviar_push_usuario($usuario_id, $titulo, $mensagem, $url = null) {
    try {
        if (!$url) {
            $basePath = get_base_url();
            $url = $basePath . '/pages/dashboard.php';
        }
        
        // Prepara URL completa se for relativa
        if (strpos($url, 'http') !== 0) {
            $basePath = get_base_url();
            $url = $basePath . '/' . ltrim($url, '/');
        }
        
        $ch = curl_init(get_base_url() . '/api/onesignal/send.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'usuario_id' => $usuario_id,
            'titulo' => $titulo,
            'mensagem' => $mensagem,
            'url' => $url
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return [
                'success' => true,
                'enviadas' => $data['enviadas'] ?? 0,
                'message' => 'Notificação enviada com sucesso'
            ];
        } else {
            $error = json_decode($response, true);
            return [
                'success' => false,
                'enviadas' => 0,
                'message' => $error['message'] ?? 'Erro ao enviar notificação'
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'enviadas' => 0,
            'message' => 'Erro: ' . $e->getMessage()
        ];
    }
}

/**
 * Envia notificação push para múltiplos colaboradores
 * 
 * @param array $colaboradores_ids Array com IDs dos colaboradores
 * @param string $titulo Título da notificação
 * @param string $mensagem Mensagem da notificação
 * @param string $url URL para abrir ao clicar (opcional)
 * @return array ['success' => bool, 'enviadas' => int, 'falhas' => int]
 */
function enviar_push_colaboradores($colaboradores_ids, $titulo, $mensagem, $url = null) {
    $enviadas_total = 0;
    $falhas = 0;
    
    foreach ($colaboradores_ids as $colab_id) {
        $result = enviar_push_colaborador($colab_id, $titulo, $mensagem, $url);
        if ($result['success']) {
            $enviadas_total += $result['enviadas'];
        } else {
            $falhas++;
        }
    }
    
    return [
        'success' => $enviadas_total > 0,
        'enviadas' => $enviadas_total,
        'falhas' => $falhas
    ];
}

