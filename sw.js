// Service Worker - RH Privus PWA
// OneSignal SDK será carregado automaticamente via OneSignalSDKWorker.js
const CACHE_NAME = 'rh-privus-v1';
const BASE_PATH = '/rh-privus';

const urlsToCache = [
  BASE_PATH + '/',
  BASE_PATH + '/login.php',
  BASE_PATH + '/pages/dashboard.php',
  BASE_PATH + '/assets/css/style.bundle.css',
  BASE_PATH + '/assets/js/scripts.bundle.js',
  BASE_PATH + '/assets/plugins/global/plugins.bundle.css',
  BASE_PATH + '/assets/plugins/global/plugins.bundle.js'
];

// Instalação do Service Worker
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Cache aberto');
        return cache.addAll(urlsToCache);
      })
  );
  self.skipWaiting();
});

// Ativação do Service Worker
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('Removendo cache antigo:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  return self.clients.claim();
});

// Interceptação de requisições (cache)
self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request)
      .then((response) => {
        // Cache hit - retorna resposta do cache
        if (response) {
          return response;
        }
        
        // Clone da requisição
        const fetchRequest = event.request.clone();
        
        return fetch(fetchRequest).then((response) => {
          // Verifica se resposta é válida
          if (!response || response.status !== 200 || response.type !== 'basic') {
            return response;
          }
          
          // Clone da resposta
          const responseToCache = response.clone();
          
          caches.open(CACHE_NAME)
            .then((cache) => {
              cache.put(event.request, responseToCache);
            });
          
          return response;
        });
      })
  );
});

// Recebe notificações push
self.addEventListener('push', (event) => {
  const data = event.data ? event.data.json() : {};
  const title = data.title || 'RH Privus';
  const options = {
    body: data.body || 'Nova notificação',
    icon: BASE_PATH + '/assets/media/logos/favicon.png',
    badge: BASE_PATH + '/assets/media/logos/favicon.png',
    data: data.data || {},
    vibrate: [200, 100, 200],
    tag: 'rh-privus-notification',
    requireInteraction: false
  };
  
  event.waitUntil(
    self.registration.showNotification(title, options)
  );
});

// Clique na notificação
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  
  const urlToOpen = event.notification.data.url || BASE_PATH + '/pages/dashboard.php';
  
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then((clientList) => {
        // Tenta focar em janela existente
        for (let client of clientList) {
          if (client.url.includes(BASE_PATH) && 'focus' in client) {
            return client.focus();
          }
        }
        // Abre nova janela
        if (clients.openWindow) {
          return clients.openWindow(urlToOpen);
        }
      })
  );
});

