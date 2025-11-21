/**
 * Inicialização do OneSignal para Push Notifications
 */

const OneSignalInit = {
    appId: null,
    safariWebId: null,
    initialized: false,
    
    // Inicializa OneSignal
    async init() {
        if (this.initialized) {
            return;
        }
        
        // Detecta base path automaticamente
        // Funciona tanto em /rh-privus/ (localhost) quanto /rh/ (produção)
        const path = window.location.pathname;
        let apiPath;
        let basePath = '';
        
        // Detecta o caminho base
        if (path.includes('/rh-privus/') || path.startsWith('/rh-privus')) {
            basePath = '/rh-privus';
        } else if (path.includes('/rh/') || path.match(/^\/rh[^a-z]/)) {
            basePath = '/rh';
        } else {
            // Fallback: detecta pelo hostname
            const hostname = window.location.hostname;
            if (hostname === 'localhost' || hostname === '127.0.0.1' || hostname.includes('local')) {
                basePath = '/rh-privus';
            } else {
                basePath = '/rh';
            }
        }
        
        // Monta o caminho da API
        if (path.includes('/pages/')) {
            // Está em uma página dentro de pages/
            apiPath = '../api/onesignal/config.php';
        } else {
            // Está na raiz ou outra subpasta
            apiPath = basePath + '/api/onesignal/config.php';
        }
        
        // Busca configurações do servidor
        try {
            console.log('Buscando configurações em:', apiPath);
            const url = apiPath;
            
            const response = await fetch(url);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Erro HTTP:', response.status, errorText);
                throw new Error(`Erro ao buscar configurações (${response.status}): ${errorText.substring(0, 100)}`);
            }
            
            const config = await response.json();
            
            if (config.error) {
                console.error('Erro na resposta:', config);
                throw new Error(config.error || 'Erro ao buscar configurações');
            }
            
            if (!config.appId) {
                console.warn('OneSignal App ID não configurado');
                return false;
            }
            
            this.appId = config.appId;
            this.safariWebId = config.safariWebId || null;
            
            // Detecta base path para Service Worker
            const pathForSW = window.location.pathname;
            let basePathForSW = '/rh'; // Padrão produção
            
            if (pathForSW.includes('/rh-privus/') || pathForSW.startsWith('/rh-privus')) {
                basePathForSW = '/rh-privus';
            } else if (pathForSW.includes('/rh/') || pathForSW.match(/^\/rh[^a-z]/)) {
                basePathForSW = '/rh';
            } else {
                // Fallback pelo hostname
                const hostname = window.location.hostname;
                if (hostname === 'localhost' || hostname === '127.0.0.1' || hostname.includes('local')) {
                    basePathForSW = '/rh-privus';
                }
            }
            
            // Inicializa OneSignal
            window.OneSignal = window.OneSignal || [];
            const self = this;
            OneSignal.push(function() {
                OneSignal.init({
                    appId: self.appId,
                    safari_web_id: self.safariWebId,
                    notifyButton: {
                        enable: false, // Desabilita botão padrão, vamos usar nosso próprio
                    },
                    allowLocalhostAsSecureOrigin: true, // Para testes em localhost
                    autoResubscribe: true,
                    serviceWorkerParam: {
                        scope: basePathForSW + '/'
                    },
                    serviceWorkerPath: basePathForSW + '/OneSignalSDKWorker.js'
                });
                
                // Registra quando usuário se inscreve
                OneSignal.on('subscriptionChange', function(isSubscribed) {
                    if (isSubscribed) {
                        OneSignalInit.registerPlayer();
                    }
                });
                
                // Verifica se já está inscrito
                OneSignal.isPushNotificationsEnabled(function(isEnabled) {
                    if (isEnabled) {
                        OneSignalInit.registerPlayer();
                    }
                });
            });
            
            this.initialized = true;
            return true;
            
        } catch (error) {
            console.error('Erro ao inicializar OneSignal:', error);
            return false;
        }
    },
    
    // Registra player_id no servidor
    async registerPlayer() {
        try {
            const playerId = await this.getPlayerId();
            if (!playerId) {
                return;
            }
            
            // Detecta base path para subscribe
            const path = window.location.pathname;
            let subscribePath;
            let basePathSubscribe = '/rh'; // Padrão produção
            
            // Detecta o caminho base
            if (path.includes('/rh-privus/') || path.startsWith('/rh-privus')) {
                basePathSubscribe = '/rh-privus';
            } else if (path.includes('/rh/') || path.match(/^\/rh[^a-z]/)) {
                basePathSubscribe = '/rh';
            } else {
                // Fallback pelo hostname
                const hostname = window.location.hostname;
                if (hostname === 'localhost' || hostname === '127.0.0.1' || hostname.includes('local')) {
                    basePathSubscribe = '/rh-privus';
                }
            }
            
            // Monta o caminho
            if (path.includes('/pages/')) {
                subscribePath = '../api/onesignal/subscribe.php';
            } else {
                subscribePath = basePathSubscribe + '/api/onesignal/subscribe.php';
            }
            
            console.log('Registrando subscription em:', subscribePath);
            
            const response = await fetch(subscribePath, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify({
                    player_id: playerId
                })
            });
            
            if (response.ok) {
                console.log('✅ Player registrado no servidor');
            }
        } catch (error) {
            console.error('Erro ao registrar player:', error);
        }
    },
    
    // Obtém player_id do OneSignal
    async getPlayerId() {
        return new Promise((resolve) => {
            if (typeof OneSignal === 'undefined') {
                resolve(null);
                return;
            }
            
            OneSignal.getUserId(function(userId) {
                resolve(userId);
            });
        });
    },
    
    // Solicita permissão e inscreve
    async subscribe() {
        if (typeof OneSignal === 'undefined') {
            return false;
        }
        
        return new Promise((resolve) => {
            OneSignal.registerForPushNotifications(function() {
                OneSignalInit.registerPlayer();
                resolve(true);
            });
        });
    },
    
    // Cancela subscription
    async unsubscribe() {
        if (typeof OneSignal === 'undefined') {
            return;
        }
        
        OneSignal.setSubscription(false);
    }
};

// Exportar globalmente
window.OneSignalInit = OneSignalInit;

// Auto-inicializa quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => OneSignalInit.init(), 1000);
    });
} else {
    setTimeout(() => OneSignalInit.init(), 1000);
}

