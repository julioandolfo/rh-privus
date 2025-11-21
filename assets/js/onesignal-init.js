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
        const path = window.location.pathname;
        const basePath = path.includes('/rh-privus') ? '/rh-privus' : '';
        
        // Busca configurações do servidor
        try {
            const response = await fetch(basePath + '/api/onesignal/config.php');
            if (!response.ok) {
                throw new Error('Erro ao buscar configurações');
            }
            const config = await response.json();
            
            if (!config.appId) {
                console.warn('OneSignal App ID não configurado');
                return false;
            }
            
            this.appId = config.appId;
            this.safariWebId = config.safariWebId || null;
            
            // Inicializa OneSignal
            window.OneSignal = window.OneSignal || [];
            const self = this;
            const basePathForSW = basePath;
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
            
            // Detecta base path
            const path = window.location.pathname;
            const basePath = path.includes('/rh-privus') ? '/rh-privus' : '';
            
            const response = await fetch(basePath + '/api/onesignal/subscribe.php', {
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

