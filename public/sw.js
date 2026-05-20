const CACHE_NAME = 'financas-cdm-v1';
// Lista de arquivos locais que o app precisa para rodar offline
const ASSETS_TO_CACHE = [
  './',
  './index.html',
  './manifest.json',
  './icone.png'
];

// Instala o Service Worker e guarda os arquivos no cache do celular
self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE);
    })
  );
});

// Ativa o SW e remove caches antigos se houver atualização
self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    })
  );
});

// Intercepta as requisições: se estiver offline, puxa do cache do celular
self.addEventListener('fetch', (e) => {
  // Ignora requisições de backend (PHP) pois o MySQL precisa de internet
  if (e.request.url.includes('/backend/')) {
    return;
  }

  e.respondWith(
    caches.match(e.request).then((cachedResponse) => {
      if (cachedResponse) {
        return cachedResponse;
      }
      return fetch(e.request);
    })
  );
});