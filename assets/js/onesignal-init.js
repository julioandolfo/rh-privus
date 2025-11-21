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
        // Se está em /rh-privus/pages/, usa ../api
        // Se está em /rh-privus/, usa /rh-privus/api
        const path = window.location.pathname;
        let apiPath;
        
        if (path.includes('/pages/')) {
            // Está em uma página dentro de pages/
            apiPath = '../api/onesignal/config.php';
        } else if (path.includes('/rh-privus')) {
            // Está na raiz ou outra subpasta
            apiPath = '/rh-privus/api/onesignal/config.php';
        } else {
            // Está na raiz do servidor
            apiPath = '/api/onesignal/config.php';
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
            let basePathForSW = '';
            if (pathForSW.includes('/rh-privus')) {
                basePathForSW = '/rh-privus';
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
            
            if (path.includes('/pages/')) {
                subscribePath = '../api/onesignal/subscribe.php';
            } else if (path.includes('/rh-privus')) {
                subscribePath = '/rh-privus/api/onesignal/subscribe.php';
            } else {
                subscribePath = '/api/onesignal/subscribe.php';
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

