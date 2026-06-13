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
                basePath = ''; // raiz do dominio (deploy sem subpasta)
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
            const hostname = window.location.hostname;
            let basePathForSW = ''; // Padrao: raiz do dominio
            
            // Detecta pelo caminho primeiro
            if (pathForSW.includes('/rh-privus/') || pathForSW.startsWith('/rh-privus')) {
                basePathForSW = '/rh-privus';
            } else if (pathForSW.includes('/rh/') || pathForSW.startsWith('/rh')) {
                // Verifica se não é /rh-privus
                if (!pathForSW.includes('/rh-privus')) {
                    basePathForSW = '/rh';
                }
            } else {
                // Fallback: detecta pelo hostname ou script src
                if (hostname === 'localhost' || hostname === '127.0.0.1' || hostname.includes('local')) {
                    basePathForSW = '/rh-privus';
                } else {
                    // Produção: tenta detectar pelo caminho do script atual
                    const scripts = document.getElementsByTagName('script');
                    for (let script of scripts) {
                        if (script.src && script.src.includes('/rh/')) {
                            basePathForSW = '/rh';
                            break;
                        } else if (script.src && script.src.includes('/rh-privus/')) {
                            basePathForSW = '/rh-privus';
                            break;
                        }
                    }
                    // Se ainda não detectou e está em produção, assume /rh
                    if (basePathForSW === '/rh' && !hostname.includes('localhost')) {
                        // Já está correto (/rh)
                    }
                }
            }
            
            console.log('🔧 Base path detectado para Service Worker:', basePathForSW);
            console.log('🔧 Path atual:', pathForSW);
            console.log('🔧 Hostname:', hostname);
            
            // OneSignal está na raiz agora, então usa caminho padrão
            // O SDK vai encontrar automaticamente em /OneSignalSDKWorker.js
            
            // Inicializa OneSignal
            window.OneSignal = window.OneSignal || [];
            const self = this;
            
            OneSignal.push(function() {
                const initConfig = {
                    appId: self.appId,
                    safari_web_id: self.safariWebId,
                    notifyButton: {
                        enable: false, // Desabilita botão padrão, vamos usar nosso próprio
                    },
                    allowLocalhostAsSecureOrigin: true, // Para testes em localhost
                    autoResubscribe: true,
                    serviceWorkerParam: {
                        scope: basePathForSW + '/'
                    }
                    // Não precisa mais especificar serviceWorkerPath - OneSignal vai usar da raiz
                };
                
                console.log('🔧 Inicializando OneSignal com App ID:', self.appId);
                
                OneSignal.init(initConfig);
                
                // Registra quando usuário se inscreve
                OneSignal.on('subscriptionChange', function(isSubscribed) {
                    console.log('📱 OneSignal subscriptionChange:', isSubscribed);
                    if (isSubscribed) {
                        console.log('✅ Usuário permitiu notificações, registrando player...');
                        setTimeout(() => {
                            OneSignalInit.registerPlayer();
                        }, 1000); // Aguarda 1 segundo para garantir que player_id está disponível
                    }
                });
                
                // Verifica se já está inscrito (após alguns segundos)
                setTimeout(() => {
                    OneSignal.isPushNotificationsEnabled(function(isEnabled) {
                        console.log('📱 OneSignal já está habilitado?', isEnabled);
                        if (isEnabled) {
                            console.log('✅ Notificações já habilitadas, registrando player...');
                            OneSignalInit.registerPlayer();
                        } else {
                            // Se não está habilitado, verifica permissão do browser
                            OneSignal.getNotificationPermission(function(permission) {
                                console.log('📱 Permissão do browser:', permission);
                                if (permission === 'default') {
                                    console.log('⚠️ Permissão ainda não foi solicitada. Use o botão para solicitar.');
                                } else if (permission === 'denied') {
                                    console.log('❌ Permissão negada pelo usuário');
                                }
                            });
                        }
                    });
                }, 2000);
                
                // Tenta registrar após 3 segundos também (fallback)
                setTimeout(() => {
                    OneSignal.getUserId(function(userId) {
                        if (userId) {
                            console.log('📱 Player ID encontrado após timeout:', userId);
                            OneSignalInit.registerPlayer();
                        } else {
                            console.warn('⚠️ Player ID ainda não disponível após 3 segundos');
                        }
                    });
                }, 3000);
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
            console.log('🔄 Tentando registrar player...');
            
            const playerId = await this.getPlayerId();
            console.log('📱 Player ID obtido:', playerId);
            
            if (!playerId) {
                console.warn('⚠️ Player ID não disponível ainda');
                return;
            }
            
            // Detecta base path para subscribe
            const path = window.location.pathname;
            let subscribePath;
            let basePathSubscribe = ''; // Padrao: raiz do dominio
            
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
            
            console.log('📡 Registrando subscription em:', subscribePath);
            console.log('📱 Player ID:', playerId);
            
            const response = await fetch(subscribePath, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include', // Importante: envia cookies de sessão
                body: JSON.stringify({
                    player_id: playerId,
                    user_agent: navigator.userAgent
                })
            });
            
            console.log('📡 Resposta HTTP:', response.status, response.statusText);
            
            const responseData = await response.json();
            console.log('📡 Dados da resposta:', responseData);
            
            if (response.ok && responseData.success) {
                console.log('✅ Player registrado com sucesso no servidor!');
                console.log('📊 Dados:', responseData.data);
            } else {
                console.error('❌ Erro ao registrar player:', responseData.message || 'Erro desconhecido');
                if (response.status === 401) {
                    console.error('⚠️ Não autenticado! Faça login primeiro.');
                }
            }
        } catch (error) {
            console.error('❌ Erro ao registrar player:', error);
            console.error('Stack:', error.stack);
        }
    },
    
    // Obtém player_id do OneSignal
    async getPlayerId() {
        return new Promise((resolve) => {
            if (typeof OneSignal === 'undefined') {
                console.warn('⚠️ OneSignal não está definido');
                resolve(null);
                return;
            }
            
            OneSignal.push(function() {
                OneSignal.getUserId(function(userId) {
                    if (userId) {
                        console.log('✅ Player ID obtido:', userId);
                    } else {
                        console.warn('⚠️ Player ID ainda não disponível');
                    }
                    resolve(userId);
                });
            });
        });
    },
    
    // Solicita permissão e inscreve
    async subscribe() {
        if (typeof OneSignal === 'undefined') {
            console.error('❌ OneSignal não está carregado');
            return false;
        }
        
        return new Promise((resolve) => {
            OneSignal.push(function() {
                // Verifica permissão atual
                OneSignal.getNotificationPermission(function(permission) {
                    console.log('📱 Permissão atual:', permission);
                    
                    if (permission === 'granted') {
                        console.log('✅ Permissão já concedida, registrando player...');
                        setTimeout(() => {
                            OneSignalInit.registerPlayer();
                        }, 500);
                        resolve(true);
                    } else if (permission === 'default') {
                        console.log('📱 Solicitando permissão...');
                        
                        // Escuta mudança de permissão
                        OneSignal.on('notificationPermissionChange', function(newPermission) {
                            console.log('📱 Permissão mudou para:', newPermission);
                            if (newPermission === 'granted') {
                                setTimeout(() => {
                                    OneSignalInit.registerPlayer();
                                }, 1000);
                                resolve(true);
                            } else {
                                resolve(false);
                            }
                        });
                        
                        // Mostra prompt nativo
                        OneSignal.showNativePrompt();
                    } else {
                        console.log('❌ Permissão negada pelo usuário');
                        resolve(false);
                    }
                });
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

