<?php
/**
 * Sistema de Autenticação
 */

require_once __DIR__ . '/functions.php';

// Inicia sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica se usuário pode acessar colaboradores de uma empresa
 */
function can_access_empresa($empresa_id) {
    if (!isset($_SESSION['usuario'])) {
        return false;
    }
    
    $user = $_SESSION['usuario'];
    
    // ADMIN pode acessar tudo
    if ($user['role'] === 'ADMIN') {
        return true;
    }
    
    // RH pode acessar tudo da sua empresa
    if ($user['role'] === 'RH' && $user['empresa_id'] == $empresa_id) {
        return true;
    }
    
    return false;
}

/**
 * Verifica se usuário pode acessar colaborador de um setor
 */
function can_access_setor($setor_id) {
    if (!isset($_SESSION['usuario'])) {
        return false;
    }
    
    $user = $_SESSION['usuario'];
    
    // ADMIN e RH podem acessar tudo
    if ($user['role'] === 'ADMIN' || $user['role'] === 'RH') {
        return true;
    }
    
    // GESTOR só pode acessar seu setor
    if ($user['role'] === 'GESTOR') {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT setor_id FROM usuarios WHERE id = ?");
        $stmt->execute([$user['id']]);
        $user_data = $stmt->fetch();
        
        // Se usuário tem setor_id definido, verifica
        if (isset($user_data['setor_id']) && $user_data['setor_id'] == $setor_id) {
            return true;
        }
    }
    
    return false;
}

/**
 * Verifica se usuário pode acessar um colaborador específico
 */
function can_access_colaborador($colaborador_id) {
    if (!isset($_SESSION['usuario'])) {
        return false;
    }
    
    $user = $_SESSION['usuario'];
    
    // ADMIN e RH podem acessar tudo
    if ($user['role'] === 'ADMIN' || $user['role'] === 'RH') {
        return true;
    }
    
    // COLABORADOR só pode acessar seu próprio perfil
    if ($user['role'] === 'COLABORADOR' && $user['colaborador_id'] == $colaborador_id) {
        return true;
    }
    
    // GESTOR pode acessar colaboradores do seu setor
    if ($user['role'] === 'GESTOR') {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT c.setor_id 
            FROM colaboradores c
            WHERE c.id = ?
        ");
        $stmt->execute([$colaborador_id]);
        $colaborador = $stmt->fetch();
        
        if ($colaborador) {
            // Busca setor do gestor
            $stmt2 = $pdo->prepare("SELECT setor_id FROM usuarios WHERE id = ?");
            $stmt2->execute([$user['id']]);
            $user_data = $stmt2->fetch();
            
            if (isset($user_data['setor_id']) && $user_data['setor_id'] == $colaborador['setor_id']) {
                return true;
            }
        }
    }
    
    return false;
}

